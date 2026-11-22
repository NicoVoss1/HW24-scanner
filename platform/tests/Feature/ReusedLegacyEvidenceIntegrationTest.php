<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\ArticleLookup;
use App\Filament\Pages\WarehousePriceWorkbench;
use App\Filament\Pages\IntelligenceWorkbench;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class ReusedLegacyEvidenceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','fake-owner');
        config()->set('hw24_legacy.password','fake-test-pass');
    }

    private static function stockArticle(): array
    {
        return [
            'ok'=>true,'jtl_status'=>'current_snapshot',
            'snapshot_at'=>'2026-11-22 20:00:00',
            'matches'=>[[
                'ean'=>'3474630337398','jtl_article_id'=>1428,
                'artikelnummer'=>'SKU1428','artikelname'=>'Matrix SoColor 6C 90 ml',
                'hersteller'=>'Matrix','bestand'=>'12',
                'netto_ek'=>'1.50','netto_vk'=>'11.7647',
                'related_sources'=>[
                    'bestand'=>['confirmed'=>true,'rows'=>[[
                        'fLagerbestand'=>'12','fVerfuegbar'=>'7','fInAuftraegen'=>'5',
                        'fZulauf'=>'4',
                    ]]],
                    'wms_lagerplaetze'=>['confirmed'=>true,'rows'=>[[
                        'warenlager_name'=>'WMS-Hauptlager','warenlager_id'=>1,
                        'lagerplatz'=>'A-02-05','lagerplatz_id'=>19,'menge'=>'7',
                        'gesperrt'=>false,'auslieferung_gesperrt'=>false,
                    ]]],
                    'amazon_angebote'=>['confirmed'=>true,'rows'=>[[
                        'asin'=>'B091J3HV6B','seller_sku'=>'MAT-6C','preis_angebot'=>'14',
                        'menge_angebot'=>'2',
                    ]]],
                ],
            ]],
        ];
    }

    public function test_after_manual_scan_archive_evidence_is_checked_without_paid_ai_or_live_amazon_api(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::stockArticle(),200);
            if(str_contains($r->url(),'/api/ai-reviews.php'))return Http::response([
                'ok'=>true,'summary'=>true,'summaries'=>[
                    '3474630337398'=>[
                        'review_id'=>17,'count'=>2,'status'=>'review',
                        'model'=>'gpt-test','at_utc'=>'2026-11-22 20:00:00',
                    ],
                ],
            ],200);
            if(str_contains($r->url(),'/api/amazon-archive.php'))return Http::response([
                'ok'=>true,'scope'=>'catalog_ean_not_seller_account',
                'snapshots'=>[
                    ['id'=>90,'count'=>1,'fetched_at_utc'=>'2026-11-22 19:00:00'],
                ],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(ArticleLookup::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->assertSet('existingEvidence.ai.verified',true)
            ->assertSet('existingEvidence.ai.found',true)
            ->assertSet('existingEvidence.ai.count',2)
            ->assertSet('existingEvidence.amazon.verified',true)
            ->assertSet('existingEvidence.amazon.snapshot_id',90)
            ->assertSet('articleRelated.stock.available','7')
            ->assertSet('articleRelated.stock.in_orders','5')
            ->assertSee('Schon geprüft')
            ->assertSee('Schon recherchiert')
            ->assertSee('WMS-Fächer');
        Http::assertSentCount(3);
        Http::assertNotSent(fn($r)=>$r->method()!=='GET');
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/api/amazon.php') ||
            str_contains($r->url(),'/api/amazon-pricing.php') ||
            str_contains($r->url(),'/api/ai-review.php'));
    }

    public function test_missing_catalog_archive_is_not_falsely_called_no_history(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::stockArticle(),200);
            if(str_contains($r->url(),'/api/ai-reviews.php'))return Http::response([
                'ok'=>true,'summary'=>true,'summaries'=>[],
            ],200);
            return Http::response(['ok'=>false,'error'=>'endpoint_not_deployed'],404);
        });
        Http::preventStrayRequests();
        Livewire::test(ArticleLookup::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->assertSet('existingEvidence.amazon.verified',false)
            ->assertSet('existingEvidence.ai.verified',true)
            ->assertSee('Archivquelle noch nicht bereit')
            ->assertDontSee('Schon recherchiert');
        Http::assertSentCount(3);
    }

    public function test_confirmed_jtl_wms_bins_and_existing_marketplace_offer_hints_render_without_second_api(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/jtl.php*'=>Http::response(self::stockArticle(),200),
        ]);
        Http::preventStrayRequests();
        Livewire::test(WarehousePriceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->assertSet('related.stock.available','7')
            ->assertSet('related.stock.in_orders','5')
            ->assertSet('related.wms.bins.0.bin','A-02-05')
            ->assertSet('related.amazon_jtl.offers.0.jtl_price','14')
            ->assertSee('WMS-Hauptlager')
            ->assertSee('In Aufträgen')
            ->assertSee('MAT-6C');
        Http::assertSentCount(1);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
    }

    public function test_full_seller_listing_loads_verified_names_without_changing_prices(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(self::stockArticle(),200);
            if(str_contains($r->url(),'/api/amazon.php'))return Http::response([
                'ok'=>true,'source'=>'cache','count'=>1,
                'items'=>[['asin'=>'B091J3HV6B','title'=>'Matrix Color 6C']],
            ],200);
            if(str_contains($r->url(),'/api/amazon-pricing.php'))return Http::response([
                'ok'=>true,'source'=>'amazon_live','retrieved_at_utc'=>'2026-11-22T20:30:00Z',
                'total_offer_count'=>2,
                'buybox_prices'=>[['landed_price'=>['amount'=>18.50,'currency'=>'EUR']]],
                'lowest_prices'=>[['landed_price'=>['amount'=>15.00,'currency'=>'EUR']]],
                'offers'=>[
                    [
                        'seller_id'=>'SELLER12345','is_buybox_winner'=>true,
                        'total'=>['amount'=>18.50,'currency'=>'EUR'],
                        'seller_feedback'=>['positive_percent'=>98,'feedback_count'=>400],
                    ],
                    [
                        'seller_id'=>'SELLER54321','is_buybox_winner'=>false,
                        'total'=>['amount'=>15.00,'currency'=>'EUR'],
                    ],
                ],
            ],200);
            if(str_contains($r->url(),'/api/amazon-sellers.php'))return Http::response([
                'ok'=>true,'names'=>['SELLER12345'=>'Verifizierter Verkäufer'],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadAmazonCatalog')
            ->call('loadAmazonPricing','B091J3HV6B')
            ->assertSet('pricing.buy_box.amount',18.5)
            ->assertSet('pricing.lowest_summary.amount',15.0)
            ->assertSet('pricing.winner_count',1)
            ->assertSet('pricing.sellers.0.seller_name','Verifizierter Verkäufer')
            ->assertSee('Buy-Box-Gewinner')
            ->assertSee('Verifizierter Verkäufer')
            ->assertSee('15,00');
        Http::assertSentCount(4);
        Http::assertSent(fn($r)=>$r->method()==='GET'&&str_contains($r->url(),'/api/amazon-sellers.php'));
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
    }
}
