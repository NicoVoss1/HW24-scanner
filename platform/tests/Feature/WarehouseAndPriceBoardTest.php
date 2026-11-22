<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\WarehousePriceWorkbench;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class WarehouseAndPriceBoardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-user');
        config()->set('hw24_legacy.password','local-test-only');
    }

    private function jtl(): array
    {
        return [
            'ok'=>true,'jtl_status'=>'current_snapshot',
            'matches'=>[[
                'ean'=>'3474630337398','jtl_article_id'=>1428,'artikelnummer'=>'SKU1428',
                'artikelname'=>'Matrix SoColor 6C','hersteller'=>'Matrix','bestand'=>'0',
                'netto_vk'=>'11.7647','netto_ek'=>'1.50',
                'amazon_vk'=>'14.00','ebay_preis'=>'11.00',
                // Deliberately no verified gross price or currency.
            ]],
        ];
    }

    public function test_dashboard_render_does_not_query_wms_or_marketplace(): void
    {
        Http::preventStrayRequests();
        Livewire::test(WarehousePriceWorkbench::class)
            ->assertSee('WMS und kanalübergreifender Preisvergleich')
            ->assertSee('Eigene Kanalpreise überprüfen')
            ->assertSee('WMS-Bewegungshistorie laden');
        Http::assertNothingSent();
    }

    public function test_jtl_reference_prices_do_not_become_own_verified_seller_offers(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/jtl.php*'=>Http::response($this->jtl(),200),
        ]);
        Http::preventStrayRequests();
        Livewire::test(WarehousePriceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->assertSet('status.ok',true)
            ->assertSet('product.amazon_jtl','14.00')
            ->assertSet('product.ebay_jtl','11.00')
            ->assertSet('product.gross_sell',null)
            ->assertSet('product.currency',null)
            ->assertSee('Kein aktives Angebot')
            ->assertSee('Shopify');
        Http::assertSentCount(1);
        Http::assertNotSent(fn($request)=>$request->method()==='POST');
    }

    public function test_wms_history_get_shows_movements_not_current_bin_stock(): void
    {
        $run=str_repeat('a',32);
        Http::fake(function($r)use($run){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response($this->jtl(),200);
            if(str_contains($r->url(),'/api/jtl-wms-history.php'))return Http::response([
                'ok'=>true,'status'=>'ready','run_id'=>$run,
                'snapshot_at'=>'2026-11-22 20:00:00','total'=>1,
                'offset'=>0,'has_more'=>false,
                'rows'=>[[
                    'movement_id'=>-87,
                    'zeitstempel'=>'2026-11-22 19:00:00',
                    'buchungsart'=>'Umlagerung','menge'=>'5',
                    'lagerplatz_von'=>'A1','lagerplatz_nach'=>'B9',
                ]],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(WarehousePriceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadWmsHistory')
            ->assertSet('wms.ok',true)
            ->assertSet('movements.0.id','-87')
            ->assertSee('Umlagerung')
            ->assertSee('kein aktueller Lagerplatzbestand');
        Http::assertSentCount(2);
        Http::assertSent(fn($r)=>$r->method()==='GET'
            && str_contains($r->url(),'/api/jtl-wms-history.php')
            && str_contains($r->url(),'article_id=1428'));
    }

    public function test_no_wms_mirror_is_reported_without_guessing_inventory(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response($this->jtl(),200);
            return Http::response(['ok'=>true,'status'=>'not_synchronized','rows'=>[]],200);
        });
        Http::preventStrayRequests();
        Livewire::test(WarehousePriceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadWmsHistory')
            ->assertSet('wms.ok',false)
            ->assertSee('Noch kein bereitgestellter WMS-Bewegungsspiegel');
        Http::assertSentCount(2);
    }

    public function test_confirmed_active_seller_price_requires_actual_source_account_and_freshness(): void
    {
        $now=now('UTC');
        DB::table('channel_price_observations')->insert([
            [
                'product_key'=>'SKU1428','ean'=>'3474630337398','channel'=>'shopify',
                'price_type'=>'own_offer','currency'=>'EUR','gross'=>'14.00',
                'net'=>null,'displayed_amount'=>null,'tax_included'=>true,
                'status'=>'verified','source'=>'Shopify Admin GraphQL (approved snapshot)',
                'marketplace'=>'DE','account'=>'hairworld24','offer_id'=>'gid://shopify/ProductVariant/99',
                'publication_status'=>'active','observed_at'=>$now,
                'expires_at'=>$now->copy()->addHour(),'created_at'=>$now,'updated_at'=>$now,
            ],
            [
                'product_key'=>'SKU1428','ean'=>'3474630337398','channel'=>'ebay',
                'price_type'=>'own_offer','currency'=>'EUR','gross'=>'9.00',
                'net'=>null,'displayed_amount'=>null,'tax_included'=>true,
                'status'=>'unverified','source'=>'Unverified local',
                'marketplace'=>'DE','account'=>'seller','offer_id'=>'999',
                'publication_status'=>'active','observed_at'=>$now,
                'expires_at'=>$now->copy()->addHour(),'created_at'=>$now,'updated_at'=>$now,
            ],
        ]);
        Http::fake([
            'artikelmanager.hairworld24.de/api/jtl.php*'=>Http::response($this->jtl(),200),
        ]);
        Http::preventStrayRequests();
        Livewire::test(WarehousePriceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadVerifiedOwnOffers')
            ->assertSet('ownOffersStatus.ok',true)
            ->assertSet('ownOffersStatus.count',1)
            ->assertSet('ownOffers.0.channel','shopify')
            ->assertSee('14,00')
            ->assertDontSee('Unverified local');
        Http::assertSentCount(1);
    }

    public function test_amazon_public_offer_requires_explicit_catalog_then_asin_price_lookup(): void
    {
        Http::fake(function($r){
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response($this->jtl(),200);
            if(str_contains($r->url(),'/api/amazon.php'))return Http::response([
                'ok'=>true,'source'=>'cache','count'=>1,
                'items'=>[['asin'=>'B091J3HV6B','title'=>'Matrix 6C']],
            ],200);
            if(str_contains($r->url(),'/api/amazon-pricing.php'))return Http::response([
                'ok'=>true,'total_offer_count'=>4,
                'buybox_prices'=>[['listing_price'=>['amount'=>14.5,'currency'=>'EUR']]],
            ],200);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(WarehousePriceWorkbench::class)
            ->set('identifier','3474630337398')
            ->call('findArticle')
            ->call('loadAmazonCandidates')
            ->call('loadAmazonMarketPrice','B091J3HV6B')
            ->assertSet('amazonMarket.ok',true)
            ->assertSet('amazonMarket.prices.0.currency','EUR')
            ->assertSee('14,50');
        Http::assertSentCount(3);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
    }

    public function test_anonymous_wms_price_admin_route_requires_login(): void
    {
        $this->get('/admin/lager-preise')->assertRedirect('/admin/login');
    }
}
