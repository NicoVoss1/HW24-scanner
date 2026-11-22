<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\Hw24SystemOverview;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class Hw24DashboardSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_legacy.base_url', 'https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user', 'fake');
        config()->set('hw24_legacy.password', 'fake');
    }

    public function test_rendering_dashboard_does_not_probe_any_external_api(): void
    {
        Http::fake();

        Livewire::test(Hw24SystemOverview::class)
            ->assertSee('E-Commerce')
            ->assertSee('Kanalpreise noch offen');

        Http::assertNothingSent();
    }

    public function test_manual_jtl_probe_checks_one_reference_article_only(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/jtl.php*' => Http::response([
                'ok' => true,
                'jtl_status' => 'current_snapshot',
                'matches' => [[
                    'jtl_article_id' => 1428,
                    'ean' => '3474630337398',
                    'artikelname' => 'Matrix 6C',
                    'bestand' => 0,
                    'netto_vk' => '11.7647',
                ]],
            ], 200),
        ]);

        Livewire::test(Hw24SystemOverview::class)
            ->call('inspectJtl')
            ->assertSet('jtl.ok', true)
            ->assertSet('jtl.article_id', '1428')
            ->assertSet('jtl.stock', '0');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/jtl.php?q=3474630337398'));
    }

    public function test_manual_archive_probe_reads_only_existing_reviews(): void
    {
        Http::fake([
            'artikelmanager.hairworld24.de/api/ai-reviews.php*' => Http::response([
                'ok' => true,
                'reviews' => [['id' => 12]],
            ], 200),
        ]);

        Livewire::test(Hw24SystemOverview::class)
            ->call('inspectArchive')
            ->assertSet('archive.ok', true)
            ->assertSet('archive.count', 1);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) =>
            str_contains($request->url(), '/api/ai-reviews.php?')
            && $request->method() === 'GET'
        );
    }
}
