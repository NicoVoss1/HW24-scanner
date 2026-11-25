<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\ScanWorkbench;
use App\Services\ScanQueueIntake;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

final class PersistentScanResearchTest extends TestCase
{
    public function test_selected_test_rows_link_to_shared_queue_without_provider_calls(): void
    {
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-only');
        config()->set('hw24_legacy.password','fake-test-only');
        $ean='4006381333931';
        Http::preventStrayRequests();
        Http::fake(function($r)use($ean){
            if(str_contains($r->url(),'op=batches'))return Http::response(['ok'=>true,'batches'=>[['id'=>7,'label'=>'TEST Queue']]]);
            if(str_contains($r->url(),'op=items'))return Http::response(['ok'=>true,'items'=>[
                ['id'=>1,'ean'=>$ean,'scan_count'=>1,'resolution'=>'existing','review_state'=>'scan_only','revision'=>0,'draft'=>[]],
                ['id'=>2,'ean'=>'5901234123457','scan_count'=>1,'resolution'=>'existing','review_state'=>'removed','revision'=>0,'draft'=>[]],
            ]]);
            return Http::response(['ok'=>false],404);
        });
        $mock=Mockery::mock(ScanQueueIntake::class);
        $mock->shouldReceive('enabled')->andReturn(true);
        $mock->shouldReceive('automatic')->andReturn(false);
        $mock->shouldReceive('statuses')->with([$ean])->andReturn([]);
        $mock->shouldReceive('enqueue')->once()->with([$ean])->andReturn(['total'=>1,'new'=>1]);
        $this->app->instance(ScanQueueIntake::class,$mock);
        Livewire::test(ScanWorkbench::class)->call('loadBatches')->call('loadBatch',7)
            ->set('selectedEans',[$ean,'5901234123457','036000291452'])
            ->call('enqueueSelectedResearch')
            ->set('confirmResearch',true)->call('enqueueSelectedResearch')
            ->assertSet('confirmResearch',false)
            ->assertSet('persistentResearchMessage','Recherche: 1 neue Aufträge, 0 bereits vorhanden.');
        Http::assertSentCount(2);
        Http::assertNotSent(fn($r)=>$r->method()!=='GET'||str_contains($r->url(),'amazon.php')||str_contains($r->url(),'ai-review.php'));
    }
}
