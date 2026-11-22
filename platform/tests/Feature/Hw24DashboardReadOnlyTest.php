<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\Hw24SystemOverview;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class Hw24DashboardReadOnlyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_legacy.base_url', 'https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user', 'test-only');
        config()->set('hw24_legacy.password', 'fake-value-not-a-real-password');
    }

    public function test_dashboard_render_never_fires_any_upstream_http_request(): void
    {
        Http::preventStrayRequests();
        Livewire::test(Hw24SystemOverview::class)
            ->assertSee('Deine Produktdaten. Ein klarer Überblick.')
            ->assertSee('Kanalpreise noch offen');
        Http::assertNothingSent();
    }

    public function test_jtl_probe_is_one_manual_get_and_never_invents_gross_price(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/jtl.php*' => Http::response([
                'ok' => true,
                'jtl_status' => 'current_snapshot',
                'snapshot_at' => '2026-11-22 19:50:00',
                'matches' => [[
                    'ean' => '3474630337398',
                    'jtl_article_id' => '1428',
                    'artikelname' => 'Test Matrix 6C',
                    'bestand' => '8.0000',
                    'netto_vk' => '11.7647',
                ]],
            ], 200),
        ]);
        Http::preventStrayRequests();

        Livewire::test(Hw24SystemOverview::class)
            ->call('inspectJtl')
            ->assertSet('jtl.ok', true)
            ->assertSet('jtl.article_id', '1428')
            ->assertSee('Test Matrix 6C')
            ->assertSee('11,76 EUR');
        Http::assertSentCount(1);
        Http::assertSent(fn ($req) => $req->method() === 'GET'
            && str_contains($req->url(), '/api/jtl.php')
            && str_contains($req->url(), 'q=3474630337398'));
    }

    public function test_stale_jtl_snapshot_cannot_be_shown_as_verified(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/jtl.php*' => Http::response([
                'ok' => true,
                'jtl_status' => 'stale',
                'matches' => [],
            ], 200),
        ]);
        Http::preventStrayRequests();
        Livewire::test(Hw24SystemOverview::class)
            ->call('inspectJtl')
            ->assertSet('jtl.ok', false)
            ->assertSee('Keine eindeutige aktuelle Artikelprobe');
        Http::assertSentCount(1);
    }

    public function test_archive_probe_reads_only_saved_history_after_click(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/ai-reviews.php*' => Http::response([
                'ok' => true,
                'stored' => true,
                'reviews' => [['id' => 123, 'ean' => '3474630337398']],
            ], 200),
        ]);
        Http::preventStrayRequests();
        Livewire::test(Hw24SystemOverview::class)
            ->call('inspectArchive')
            ->assertSet('archive.ok', true)
            ->assertSet('archive.count', 1)
            ->assertSee('1 gespeicherte Einträge');
        Http::assertSentCount(1);
        Http::assertSent(fn ($req) => $req->method() === 'GET'
            && str_contains($req->url(), '/api/ai-reviews.php')
            && str_contains($req->url(), 'limit=5'));
    }
}
