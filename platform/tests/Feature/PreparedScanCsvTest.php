<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Services\PreparedScanCsv;
use App\Services\LegacyApprovedActionBridge;
use App\Filament\Pages\ScanWorkbench;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class PreparedScanCsvTest extends TestCase
{
    public function test_parser_preserves_leading_zero_and_metadata(): void
    {
        $p=PreparedScanCsv::parse("\xEF\xBB\xBFEAN;Lagerplatz;Information\n036000291452;04-001;Inventurmenge: 4 Stk\n4006381333931;;Inventurmenge: 2 Stk\n");
        $this->assertCount(2,$p['rows']);
        $this->assertSame('036000291452',$p['rows'][0]['ean']);
        $this->assertSame('Inventurmenge: 4 Stk',$p['rows'][0]['information']);
    }

    public function test_original_inventory_is_not_silently_imported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PreparedScanCsv::parse("EAN;Lagerplatz NEU;LP ALT;Kommentar\n4006381333931;B-Ware;;\n");
    }

    public function test_native_preview_and_confirmed_test_import_reuse_legacy_storage(): void
    {
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-only');
        config()->set('hw24_legacy.password','fake-test-password');
        config()->set('hw24_legacy.scan_writes_enabled',true);
        Http::fake(function($r){
            if(str_contains($r->url(),'op=batches'))return Http::response(['ok'=>true,'batches'=>[['id'=>7,'label'=>'TEST Neuware']]]);
            if(str_contains($r->url(),'op=items'))return Http::response(['ok'=>true,'items'=>[]]);
            if($r->method()==='POST'&&($r->data()['op']??null)==='import_rows')
                return Http::response(['ok'=>true,'received'=>1,'inserted'=>1,'existed'=>0,'restored'=>0]);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        $page=Livewire::test(ScanWorkbench::class)->call('loadBatches')->call('loadBatch',7)
            ->set('csvFile',UploadedFile::fake()->createWithContent('test.csv',"EAN;Information\n036000291452;Inventurmenge: 4 Stk\n"))
            ->call('previewCsv')->assertSet('csvState.ok',true)->assertSet('csvOffset',0);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
        $page->call('importCsvChunk')->assertSet('csvOffset',0);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
        $page->set('confirmCsvImport',true)->call('importCsvChunk')->assertSet('csvOffset',1)
            ->assertSet('csvState.inserted',1);
        Http::assertSent(fn($r)=>$r->method()==='POST'&&($r->data()['op']??null)==='import_rows'
            &&($r->data()['rows'][0]['ean']??null)==='036000291452');
    }
    public function test_csv_picker_has_german_empty_state_and_can_clear_file(): void
    {
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-only');
        config()->set('hw24_legacy.password','fake-test-password');
        Http::fake(function($r){
            if(str_contains($r->url(),'op=batches'))return Http::response(['ok'=>true,'batches'=>[['id'=>7,'label'=>'TEST Neuware']]]);
            if(str_contains($r->url(),'op=items'))return Http::response(['ok'=>true,'items'=>[
                ['id'=>1,'ean'=>'036000291452','artikelname'=>'Testartikel','scan_count'=>1,'resolution'=>'existing','review_state'=>'scan_only'],
            ]]);
            return Http::response(['ok'=>false],404);
        });
        Http::preventStrayRequests();
        Livewire::test(ScanWorkbench::class)->call('loadBatches')->call('loadBatch',7)
            ->assertSee('CSV-Datei auswählen')->assertSee('Noch keine Datei gewählt')
            ->assertSee('Testartikel')->assertSee('Erfassung, Quellen und Archivdetails')
            ->call('previewCsv')->assertHasErrors(['csvFile'=>'required'])
            ->assertSee('Bitte zuerst eine CSV-Datei auswählen.')
            ->set('csvFile',UploadedFile::fake()->createWithContent('probe.csv',"EAN;Information\n036000291452;Test\n"))
            ->assertHasNoErrors(['csvFile'])->assertSee('probe.csv')
            ->call('previewCsv')->assertSet('csvState.ok',true)
            ->call('clearCsvFile')->assertSet('csvFile',null)->assertSet('csvRows',[])
            ->assertSee('Noch keine Datei gewählt');
        Http::assertSentCount(2);
    }

}
