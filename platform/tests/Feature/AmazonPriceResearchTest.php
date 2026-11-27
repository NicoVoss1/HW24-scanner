<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AmazonPriceResearch;
use App\Services\ScanResearch;
use App\Services\LegacyReadBridge;
use App\Services\AmazonPriceArchive;
use Illuminate\Support\Facades\Http;
use PDO;
use Tests\TestCase;

final class AmazonPriceResearchTest extends TestCase
{
    private function archive(): AmazonPriceArchive
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA foreign_keys=ON');
        $db->exec('CREATE TABLE hw24_amazon_price_snapshots (
            id INTEGER PRIMARY KEY, observation_key TEXT UNIQUE, marketplace_id TEXT,
            asin TEXT,item_condition TEXT,observed_at_utc TEXT,expires_at_utc TEXT,
            total_offer_count INTEGER,returned_offer_count INTEGER,buybox_json TEXT,lowest_json TEXT)');
        $db->exec('CREATE TABLE hw24_amazon_price_offers (
            snapshot_id INTEGER REFERENCES hw24_amazon_price_snapshots(id) ON DELETE CASCADE,
            offer_index INTEGER,seller_id TEXT,item_price TEXT,item_currency TEXT,shipping_price TEXT,
            shipping_currency TEXT,total_price TEXT,total_currency TEXT,is_fba INTEGER,
            is_buybox_winner INTEGER,offer_condition TEXT,PRIMARY KEY(snapshot_id,offer_index))');
        return new AmazonPriceArchive($db,1);
    }

    public function test_explicit_archive_and_no_extra_provider_calls(): void
    {
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test');
        config()->set('hw24_legacy.password','fake-test-only');
        Http::preventStrayRequests();
        $payload=['ok'=>true,'source'=>'amazon_live','marketplace_id'=>'A1PA6795UKMFR9',
            'asin'=>'B000000001','retrieved_at_utc'=>gmdate('c'),'offers'=>[],
            'total_offer_count'=>0,'buybox_prices'=>[],'lowest_prices'=>[]];
        Http::fake(['https://artikelmanager.hairworld24.de/api/amazon-pricing.php*'=>Http::sequence()
            ->push($payload)->push($payload)->push($payload)->push(['ok'=>false,'error'=>'rate_limit'],429)]);
        $bridge=new LegacyReadBridge();
        $archive=$this->archive();
        $off=ScanResearch::pricing('B000000001',$bridge);
        self::assertTrue($off['ok']);
        self::assertArrayNotHasKey('archive',$off);
        self::assertSame([],$archive->latest('B000000001'));
        $first=ScanResearch::pricing('B000000001',$bridge,$archive);
        self::assertSame('recorded',$first['archive']['state']);
        self::assertTrue($first['archive']['inserted']);
        $replay=AmazonPriceResearch::fetch('B000000001',$bridge,$archive);
        self::assertFalse($replay['archive']['inserted']);
        self::assertCount(1,$replay['archive']['history']);
        $fail=AmazonPriceResearch::fetch('B000000001',$bridge,$archive);
        self::assertSame('not_recorded',$fail['archive']['state']);
        self::assertCount(1,$archive->latest('B000000001'));
        Http::assertSentCount(4);
        Http::assertNotSent(fn($r)=>$r->method()!=='GET'||!str_contains($r->url(),'amazon-pricing.php'));
    }
}
