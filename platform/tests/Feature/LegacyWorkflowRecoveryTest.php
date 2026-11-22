<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\LegacyWorkspace;
use App\Filament\Pages\ScanWorkbench;
use App\Services\LegacyReadBridge;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

final class LegacyWorkflowRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_legacy.base_url', 'https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user', 'test-user-only');
        config()->set('hw24_legacy.password', 'fake-not-real-password');
    }

    public function test_existing_workspaces_render_without_post_or_automatic_http(): void
    {
        Http::preventStrayRequests();
        Livewire::test(LegacyWorkspace::class)
            ->assertSee('Alle bisherigen Werkzeuge wieder erreichbar')
            ->assertSee('Bisherige Artikelpflege öffnen')
            ->assertSee('Vollständige Scanliste öffnen')
            ->assertSee('Qualitätszentrum öffnen')
            ->assertSee('Expertenansicht öffnen');

        Livewire::test(ScanWorkbench::class)
            ->assertSee('Gespeicherte Stapel laden')
            ->assertSee('Scanliste vollständig öffnen');
        Http::assertNothingSent();
    }

    public function test_scan_batch_read_is_an_explicit_single_get(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/scanliste.php*' => Http::response([
                'ok' => true,
                'batches' => [
                    ['id'=>17,'label'=>'Bestand Haarfarben','created_at_utc'=>'2026-11-22 20:00:00','updated_at_utc'=>'2026-11-22 20:15:00'],
                ],
            ], 200),
        ]);
        Http::preventStrayRequests();

        Livewire::test(ScanWorkbench::class)
            ->call('loadBatches')
            ->assertSet('diagnostic.ok', true)
            ->assertSee('Bestand Haarfarben')
            ->assertSee('Stapel-ID 17');
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->method()==='GET' &&
            str_contains($r->url(),'/api/scanliste.php') &&
            str_contains($r->url(),'op=batches'));
    }

    public function test_one_selected_scan_batch_loads_only_one_verified_get_and_limits_rows(): void
    {
        Http::fake(function ($r) {
            $url = $r->url();
            if (str_contains($url, 'op=batches')) {
                return Http::response([
                    'ok' => true,
                    'batches' => [['id'=>17,'label'=>'Testbestand','created_at_utc'=>'','updated_at_utc'=>'']],
                ], 200);
            }

            return Http::response([
                'ok' => true,
                'items' => [
                    ['ean'=>'3474630337398','artikelname'=>'Matrix Farbe 6C',
                     'artikelnummer'=>'SKU1','scan_count'=>3,'resolution'=>'existing',
                     'review_state'=>'scan_only','draft'=>['secret_notes'=>'not-displayed']],
                ],
            ], 200);
        });
        Http::preventStrayRequests();

        Livewire::test(ScanWorkbench::class)
            ->call('loadBatches')
            ->call('loadBatch', 17)
            ->assertSet('selectedBatchId', 17)
            ->assertSet('items.0.scans', 3)
            ->assertSee('Matrix Farbe 6C')
            ->assertDontSee('not-displayed');
        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => $r->method()==='GET' &&
            str_contains($r->url(),'op=items') &&
            str_contains($r->url(),'batch_id=17'));
    }

    public function test_unknown_batch_id_is_blocked_before_any_scan_items_request(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/scanliste.php*' => Http::response([
                'ok'=>true, 'batches'=>[['id'=>17,'label'=>'Meine Liste']],
            ], 200),
        ]);
        Http::preventStrayRequests();

        Livewire::test(ScanWorkbench::class)
            ->call('loadBatches')
            ->call('loadBatch', 999)
            ->assertSet('diagnostic.ok', false)
            ->assertSee('Bitte zunächst einen verfügbaren eigenen Scan-Stapel auswählen.');
        Http::assertSentCount(1);
    }

    public function test_scan_write_endpoint_never_becomes_a_bridge_service(): void
    {
        $this->expectException(RuntimeException::class);
        app(LegacyReadBridge::class)->fetch('scan.write', ['op'=>'scan']);
    }

    public function test_scan_bridge_is_pinned_to_the_original_protected_live_host(): void
    {
        Http::preventStrayRequests();
        config()->set('hw24_legacy.base_url','https://test-artikelmanager.hairworld24.de');
        $result=app(LegacyReadBridge::class)->fetch('scan.batches', ['op'=>'batches']);
        $this->assertFalse($result['ok']);
        $this->assertSame('unconfigured',$result['state']);
        Http::assertNothingSent();
    }

    public function test_filament_panel_routes_are_registered_and_require_auth(): void
    {
        $this->get('/admin/scanliste')->assertRedirect('/admin/login');
        $this->get('/admin/werkzeuge')->assertRedirect('/admin/login');
    }
}
