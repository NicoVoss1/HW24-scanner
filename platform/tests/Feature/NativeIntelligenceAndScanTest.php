<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\IntelligenceWorkbench;
use App\Filament\Pages\ScanWorkbench;
use App\Services\LegacyApprovedActionBridge;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class NativeIntelligenceAndScanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-only');
        config()->set('hw24_legacy.password','fake-test-only-secret');
        config()->set('hw24_legacy.ai_paid_enabled',false);
        config()->set('hw24_legacy.scan_writes_enabled',false);
    }

    private static function jtlResponse(): array
    {
        return [
            'ok'=>true,'jtl_status'=>'current_snapshot',
            'matches'=>[[
                'ean'=>'3474630337398','jtl_article_id'=>1428,
                'artikelnummer'=>'3474630337398','artikelname'=>'Matrix SoColor Beauty 6C 90 ml',
                'hersteller'=>'Matrix','warengruppe_name'=>'Haarfarben',
                'netto_vk'=>'11.7647',
            ]],
        ];
    }

    public function test_native_pages_render_without_any_automatic_external_requests(): void
    {
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->assertSee('KI-Abfragen und Amazon-Recherche')
            ->assertSee('KI-Archiv zur aktuellen EAN')
            ->assertSee('Alle letzten KI-Prüfungen anzeigen')
            ->assertSee('Neue KI-Prüfung ausdrücklich beauftragen');
        Livewire::test(ScanWorkbench::class)
            ->assertSee('EAN erfassen')
            ->assertSee('AKTIVER SCAN-STAPEL')
            ->assertSee('Fortlaufende Barcode-Erfassung')
            ->assertSee('JTL-Ameise-Prüfexport');
        Http::assertNothingSent();
    }

    public function test_manual_jtl_and_archive_get_shows_actual_historical_fields(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php')) return Http::response(self::jtlResponse(),200);
            if(str_contains($r->url(),'/api/ai-reviews.php')) return Http::response([
                'ok'=>true,'reviews'=>[[
                    'id'=>16,'status'=>'review','model'=>'gpt-test',
                    'created_at_utc'=>'2026-11-22 20:15:00','usd_cost'=>0.000423,
                    'review'=>['reason'=>'Gebinde und Farbton prüfen',
                        'suggestedWarengruppe'=>'Haarfarben','productFacts'=>[
                            'manufacturer'=>'Matrix','suggestedTitle'=>'SoColor Beauty 6C',
                        ]],
                ]],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->assertSet('lookupState.ok',true)
            ->call('loadAiArchive')
            ->assertSet('archiveState.ok',true)
            ->assertSet('reviews.0.id',16)
            ->assertSee('Gebinde und Farbton prüfen');
        Http::assertSentCount(2);
        Http::assertSent(fn($r)=>$r->method()==='GET'
            && str_contains($r->url(),'/api/ai-reviews.php'));
    }

    public function test_global_legacy_archive_can_be_browsed_without_an_ean_or_new_paid_request(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/ai-reviews.php'))return Http::response([
                'ok'=>true,'scope'=>'authenticated_user','count'=>1,
                'next_before_id'=>null,
                'reviews'=>[[
                    'id'=>91,'ean'=>'0615908424980','article_name'=>'Tigi Creative 60 ml',
                    'status'=>'review','model'=>'gpt-test',
                    'created_at_utc'=>'2026-11-20 10:01:00',
                    'usd_cost'=>0.000617,
                    'tokens'=>['input'=>130,'cached'=>10,'output'=>55],
                    'review'=>['reason'=>'Testbegründung', 'productFacts'=>['manufacturer'=>'Tigi']],
                ]],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->call('loadAiArchiveAll')
            ->assertSet('archiveState.ok',true)
            ->assertSet('archiveState.mode','all')
            ->assertSet('archiveState.scope','legacy_authenticated_actor')
            ->assertSet('reviews.0.ean','0615908424980')
            ->assertSet('reviews.0.cost',0.000617)
            ->assertSet('reviews.0.tokens_input',130)
            ->assertSee('Tigi Creative 60 ml');
        Http::assertSentCount(1);
        Http::assertSent(fn($r)=>$r->method()==='GET'
            && str_contains($r->url(),'/api/ai-reviews.php')
            && !str_contains($r->url(),'ean=')
            && !str_contains($r->url(),'before_id='));
    }

    public function test_global_archive_older_pagination_uses_cursor_without_paid_requests(): void
    {
        Http::fake(function($r){
            if(!str_contains($r->url(),'/api/ai-reviews.php'))return Http::response(['ok'=>false],404);
            if(str_contains($r->url(),'before_id=48'))return Http::response([
                'ok'=>true,'next_before_id'=>null,
                'reviews'=>[['id'=>29,'ean'=>'1234567890128','article_name'=>'Älteres Produkt',
                    'model'=>'gpt-test','status'=>'review','created_at_utc'=>'2026-08-11 12:00:00',
                    'usd_cost'=>0.0004,'tokens'=>['input'=>70,'cached'=>0,'output'=>20]]],
            ],200);
            return Http::response([
                'ok'=>true,'next_before_id'=>48,
                'reviews'=>[['id'=>52,'ean'=>'0615908424980','article_name'=>'Neueres Produkt',
                    'model'=>'gpt-test','status'=>'review','created_at_utc'=>'2026-11-20 10:01:00',
                    'usd_cost'=>0.0005,'tokens'=>['input'=>90,'cached'=>10,'output'=>30]]],
            ],200);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->call('loadAiArchiveAll')
            ->assertSet('archiveState.more',48)
            ->call('loadOlderAiArchive')
            ->assertSet('archiveState.more',null)
            ->assertCount('reviews',2)
            ->assertSee('Älteres Produkt');
        Http::assertSentCount(2);
        Http::assertSent(fn($r)=>$r->method()==='GET'
            && str_contains($r->url(),'/api/ai-reviews.php')
            && str_contains($r->url(),'before_id=48'));
    }

    public function test_amazon_catalogue_is_only_requested_after_explicit_click(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::jtlResponse(),200);
            if(str_contains($r->url(),'/api/amazon.php'))return Http::response([
                'ok'=>true,'count'=>1,'source'=>'cache','snapshot_id'=>70,
                'fetched_at_utc'=>'2026-11-22 20:00:00',
                'items'=>[['asin'=>'B091J3HV6B','title'=>'Matrix SoColor Farbe','brand'=>'Matrix']],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadAmazonCatalog')
            ->assertSet('catalog.snapshot_id',70)
            ->assertSee('Matrix SoColor Farbe');
        Http::assertSentCount(2);
        Http::assertSent(fn($r)=>$r->method()==='GET'
            && str_contains($r->url(),'/api/amazon.php'));
    }

    public function test_historical_amazon_snapshots_read_only_with_explicit_get(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::jtlResponse(),200);
            if(str_contains($r->url(),'/api/amazon-archive.php'))return Http::response([
                'ok'=>true,'scope'=>'catalog_ean_not_seller_account','count'=>2,
                'snapshots'=>[
                    ['id'=>220,'fetched_at_utc'=>'2026-11-22 18:10:00','count'=>1],
                    ['id'=>219,'fetched_at_utc'=>'2026-11-22 17:25:00','count'=>0],
                ],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadAmazonArchive')
            ->assertSet('catalogArchive.ok',true)
            ->assertSet('catalogArchive.snapshots.0.id',220)
            ->assertSee('Amazon-Katalogarchiv #220');
        Http::assertSentCount(2);
        Http::assertSent(fn($r)=>$r->method()==='GET'
            && str_contains($r->url(),'/api/amazon-archive.php')
            && str_contains($r->url(),'ean=3474630337398'));
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
    }

    public function test_missing_new_archive_endpoint_is_reported_without_sp_api_or_writes(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::jtlResponse(),200);
            return Http::response(['ok'=>false,'error'=>'not_deployed'],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadAmazonArchive')
            ->assertSet('catalogArchive.ok',false)
            ->assertSee('noch nicht gesondert bereitgestellt');
        Http::assertSentCount(2);
    }

    public function test_paid_ai_cannot_call_upstream_without_private_flag_and_confirmation(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::jtlResponse(),200);
            if(str_contains($r->url(),'/api/ai-review.php'))return Http::response([
                'ok'=>true,'enabled'=>true,'archive_ready'=>true,'usd_month'=>0.001,'usd_budget'=>5.0,
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('inspectAiBudget')
            ->set('confirmPaid',true)
            ->call('runAiReview')
            ->assertSet('reviewResult.ok',false);
        Http::assertSentCount(2);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
    }

    public function test_confirmed_first_ai_review_can_post_only_after_all_gates(): void
    {
        config()->set('hw24_legacy.ai_paid_enabled',true);
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::jtlResponse(),200);
            if(str_contains($r->url(),'/api/ai-reviews.php'))return Http::response([
                'ok'=>true,'reviews'=>[],
            ],200);
            if(str_contains($r->url(),'/api/ai-review.php')&&$r->method()==='GET')
                return Http::response(['ok'=>true,'enabled'=>true,'archive_ready'=>true,
                    'usd_month'=>0.01,'usd_budget'=>5.0],200);
            if(str_contains($r->url(),'/api/ai-review.php')&&$r->method()==='POST')
                return Http::response(['ok'=>true,'stored'=>true,'review_id'=>90,'usd_call'=>0.0003],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('inspectAiBudget')
            ->set('confirmPaid',true)
            ->call('runAiReview')
            ->assertSet('reviewResult.ok',true)
            ->assertSet('reviewResult.id',90);
        Http::assertSent(fn($r)=>$r->method()==='POST'
            && str_contains($r->url(),'/api/ai-review.php')
            && $r->hasHeader('X-HW24-Approval','confirmed'));
    }

    public function test_scanner_write_is_default_off_but_explicitly_available_after_gate(): void
    {
        Http::preventStrayRequests();
        Livewire::test(ScanWorkbench::class)
            ->set('newBatchLabel','Neue Lieferung')
            ->set('confirmScanWrite',true)
            ->call('createBatch')
            ->assertSet('actionState.ok',false);
        Http::assertNothingSent();

        config()->set('hw24_legacy.scan_writes_enabled',true);
        Http::fake(function($r){
            if($r->method()==='POST' && str_contains($r->url(),'scanliste.php')) {
                return Http::response(['ok'=>true,'batch_id'=>77],201);
            }
            if(str_contains($r->url(),'op=batches'))return Http::response([
                'ok'=>true,'batches'=>[['id'=>77,'label'=>'Neue Lieferung']],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(ScanWorkbench::class)
            ->set('newBatchLabel','Neue Lieferung')
            ->set('confirmScanWrite',true)
            ->call('createBatch')
            ->assertSet('actionState.ok',true)
            ->assertSet('batches.0.id',77);
        Http::assertSentCount(2);
        Http::assertSent(fn($r)=>$r->method()==='POST'
            && str_contains($r->url(),'scanliste.php')
            && ($r->data()['op']??null)==='create'
            && $r->hasHeader('Origin','https://artikelmanager.hairworld24.de'));
    }

    public function test_ameise_export_checks_existing_legacy_approval_before_download(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'op=batches'))return Http::response([
                'ok'=>true,'batches'=>[['id'=>8,'label'=>'Importkandidaten']],
            ],200);
            if(str_contains($r->url(),'op=items'))return Http::response([
                'ok'=>true,'items'=>[['ean'=>'3474630337398','scan_count'=>1]],
            ],200);
            if(str_contains($r->url(),'op=export'))return Http::response([
                'ok'=>true,'count'=>1,'fields'=>[[
                    'GTIN'=>'3474630337398','Artikelname'=>'Matrix SoColor',
                    'Hersteller'=>'Matrix','Warengruppe'=>'Haarfarben',
                    'Steuerklasse'=>'Normaler Steuersatz','Artikelnummer'=>'',
                ]],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(ScanWorkbench::class)
            ->call('loadBatches')
            ->call('loadBatch',8)
            ->call('prepareAmeiseExport')
            ->assertSet('exportState.ok',true)
            ->assertSet('exportState.count',1);
        Http::assertSentCount(3);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
    }

    public function test_action_bridge_rejects_marketplace_or_jtl_writes_even_if_flags_true(): void
    {
        config()->set('hw24_legacy.ai_paid_enabled',true);
        config()->set('hw24_legacy.scan_writes_enabled',true);
        Http::preventStrayRequests();
        $this->expectException(\RuntimeException::class);
        app(LegacyApprovedActionBridge::class)->post('jtl.write',['article_id'=>1428]);
    }

    public function test_new_native_route_requires_filament_login(): void
    {
        $this->get('/admin/intelligence')->assertRedirect('/admin/login');
    }
}
