<?php
declare(strict_types=1);

namespace Tests\Feature;
use App\Filament\Pages\ScanWorkbench;
use App\Filament\Pages\IntelligenceWorkbench;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class NativeScanResearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-only');
        config()->set('hw24_legacy.password','fake-test-secret');
        config()->set('hw24_legacy.scan_writes_enabled',true);
        config()->set('hw24_legacy.ai_paid_enabled',false);
    }

    public function test_new_gtin_without_jtl_can_be_researched(): void
    {
        Http::fake(['*'=>Http::response(['ok'=>true,'items'=>[],'count'=>0,'source'=>'cache'])]);
        Http::preventStrayRequests();
        Livewire::test(IntelligenceWorkbench::class)
            ->set('identifier','036000291452')
            ->call('useUnidentifiedEan')
            ->assertSet('article.ean','036000291452')
            ->assertSet('article.jtl_status','unverified')
            ->call('loadAmazonCatalog')->assertSet('catalog.ok',true);
        Http::assertSentCount(1);
    }

    public function test_marking_is_free_research_uses_catalog_and_confirmed_draft_edit_only(): void
    {
        $ean='036000291452';
        Http::fake(function($r)use($ean){
            if(str_contains($r->url(),'op=batches'))return Http::response(['ok'=>true,'batches'=>[['id'=>7,'label'=>'TEST Neuware']]]);
            if(str_contains($r->url(),'op=items'))return Http::response(['ok'=>true,'items'=>[
                ['id'=>9,'ean'=>$ean,'scan_count'=>1,'resolution'=>'new_candidate','review_state'=>'scan_only','revision'=>0,'draft'=>[]],
            ]]);
            if(str_contains($r->url(),'/api/jtl.php'))return Http::response(['ok'=>true,'jtl_status'=>'current_snapshot','matches'=>[]]);
            if(str_contains($r->url(),'/api/amazon.php'))return Http::response(['ok'=>true,'source'=>'cache','snapshot_id'=>19,'items'=>[
                ['asin'=>'B000000001','title'=>'Example Product','brand'=>'Example Brand','manufacturer'=>'Example Manufacturer',
                'identifiers'=>[['identifiers'=>[['identifierType'=>'UPC','identifier'=>$ean]]]]],
            ]]);
            if(str_contains($r->url(),'/api/ai-reviews.php'))return Http::response(['ok'=>true,'scope'=>'shared_workspace','reviews'=>[]]);
            if($r->method()==='POST'&&($r->data()['op']??null)==='edit')
                return Http::response(['ok'=>true,'revision'=>1,'review_state'=>'draft','draft'=>$r->data()['fields']]);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        $page=Livewire::test(ScanWorkbench::class)->call('loadBatches')->call('loadBatch',7)
            ->call('selectAllResearch')->assertSet('selectedEans',[$ean]);
        Http::assertSentCount(2);
        $page->call('prepareResearch')->call('researchNext');
        Http::assertSentCount(2);
        $page->set('confirmResearch',true)->call('prepareResearch')->call('researchNext')
            ->assertSet('researchOffset',1)->call('openDraft',$ean)
            ->call('useCatalogSuggestion','B000000001')
            ->assertSet('draftFields.Artikelname','Example Product')
            ->assertSet('draftFields.Hersteller','Example Manufacturer')
            ->assertSet('draftFields.GTIN',$ean);
        Http::assertSentCount(4);
        $page->call('runWorkingAi')->call('saveDraft',false);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
        $page->call('loadWorkingAi')->assertSet('workingAi.found',false)
            ->set('confirmDraft',true)->call('saveDraft',false)
            ->assertSet('items.0.review','draft')->assertSet('items.0.revision',1);
        Http::assertSent(fn($r)=>$r->method()==='POST'&&($r->data()['op']??null)==='edit'
            &&($r->data()['expected_revision']??null)===0&&($r->data()['fields']['GTIN']??null)===$ean);
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/api/ai-review.php'));
    }
}
