<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Services\LegacyReadBridge;
use App\Services\PriceArchiveStore;
use App\Services\ResearchJobProcessor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ResearchJobProcessorTest extends TestCase
{
    private string $ean='4006381333931';
    private function store(): PriceArchiveStore
    {
        return new class implements PriceArchiveStore {
            public array $rows=[];
            public function record(array $raw,string $expectedAsin): array {throw new \LogicException('not used');}
            public function latest(string $asin,int $limit=2): array{return $this->rows;}
            public function purgeExpired(): int{return 0;}
        };
    }
    private function setupHttp(string $mode): void
    {
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-only');
        config()->set('hw24_legacy.password','fake-test-only');
        Http::preventStrayRequests();
        Http::fake(function($r)use($mode){
            $url=$r->url();
            if(str_contains($url,'/api/jtl.php'))return Http::response(['ok'=>true,'jtl_status'=>'current_snapshot','matches'=>[]]);
            if(str_contains($url,'/api/historie.php'))return Http::response(['ok'=>true,'total_returned'=>2]);
            if(str_contains($url,'/api/ai-reviews.php'))return Http::response(['ok'=>true,'scope'=>'authenticated_user','reviews'=>[]]);
            if(str_contains($url,'/api/amazon-sellers.php'))return Http::response(['ok'=>true,'names'=>['TESTSELLER1'=>'Example']]);
            if(str_contains($url,'/api/amazon.php')){
                if($mode==='429')return Http::response(['ok'=>false,'upstream_status'=>429],502,['Retry-After'=>'180']);
                $item=['asin'=>'B000000001','title'=>'Example','identifiers'=>[['identifiers'=>[['identifierType'=>'EAN','identifier'=>$this->ean]]]],
                    'attributes'=>['number_of_items'=>[['value'=>1]]]];
                return Http::response(['ok'=>true,'source'=>'cache','snapshot_id'=>19,'has_next_page'=>false,
                    'items'=>$mode==='ambiguous'?[$item,$item]:[$item]]);
            }
            return Http::response(['ok'=>false],404);
        });
    }
    public function test_reuses_recent_price_archive_without_pricing_or_paid_ai(): void
    {
        $this->setupHttp('normal');
        $store=$this->store();
        $store->rows=[['id'=>2,'observed_at_utc'=>gmdate('Y-m-d H:i:s'),'offers'=>[['seller_id'=>'TESTSELLER1']]]];
        $processor=new ResearchJobProcessor(new LegacyReadBridge(),$store,
            fn($id,$ean)=>$id===19&&$ean===$this->ean,
            fn()=>throw new \LogicException('No new pricing expected'),
            fn()=>throw new \LogicException('No pause needed'));
        $r=$processor->process($this->ean);
        self::assertSame('completed',$r['outcome']);
        self::assertSame('archive_reused',$r['evidence']['price_state']);
        self::assertSame(2,$r['evidence']['price_snapshot_id']);
        self::assertSame('legacy_account',$r['evidence']['ai_scope']);
        self::assertSame(1,$r['evidence']['seller_count']);
        Http::assertSentCount(5);
        Http::assertNotSent(fn($r)=>$r->method()!=='GET'||str_contains($r->url(),'amazon-pricing.php')||str_contains($r->url(),'ai-review.php'));
    }
    public function test_live_capture_requires_independent_archive_read(): void
    {
        $this->setupHttp('normal');
        $store=$this->store();$calls=0;$pause=0;
        $processor=new ResearchJobProcessor(new LegacyReadBridge(),$store,fn()=>true,
            function($asin)use($store,&$calls){
                $calls++;
                $store->rows=[['id'=>3,'observed_at_utc'=>gmdate('Y-m-d H:i:s'),'offers'=>[]]];
                return ['response'=>['ok'=>true,'http_status'=>200,'data'=>['source'=>'amazon_live']],
                    'archive'=>['state'=>'recorded','id'=>3]];
            },function($s)use(&$pause){$pause=$s;});
        $r=$processor->process($this->ean);
        self::assertSame('completed',$r['outcome']);
        self::assertSame('live_archived',$r['evidence']['price_state']);
        self::assertSame(1,$calls);self::assertSame(15,$pause);
    }
    private function assertStopsBeforePrice(string $mode,string $expected): array
    {
        $this->setupHttp($mode);
        $processor=new ResearchJobProcessor(new LegacyReadBridge(),$this->store(),fn()=>true,
            fn()=>throw new \LogicException('No price call'),fn()=>throw new \LogicException('No pause'));
        $r=$processor->process($this->ean);
        self::assertSame($expected,$r['outcome']);
        Http::assertSentCount(4);
        Http::assertNotSent(fn($request)=>str_contains($request->url(),'amazon-pricing.php')||$request->method()!=='GET');
        return $r;
    }
    public function test_identity_conflict_stops_before_price(): void
    {
        $this->assertStopsBeforePrice('ambiguous','identity_conflict');
    }
    public function test_rate_limit_stops_before_price_and_preserves_retry_after(): void
    {
        $r=$this->assertStopsBeforePrice('429','rate_limited');
        self::assertSame(180,$r['retry_after']);
    }
}
