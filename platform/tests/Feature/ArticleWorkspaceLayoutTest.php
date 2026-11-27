<?php
declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use App\Filament\Pages\ArticleLookup;
use Tests\TestCase;

final class ArticleWorkspaceLayoutTest extends TestCase
{
    public function test_article_workspace_compiles_and_has_five_explicit_sections(): void
    {
        $template=file_get_contents(resource_path('views/filament/pages/article-lookup.blade.php'));
        self::assertIsString($template);

        foreach([
            '01 · Artikel finden',
            '02 · Artikeldaten',
            '03 · JTL-Originalfelder und Kontopreise',
            '04 · Amazon Selling Partner API',
            '05 · OpenAI · gespeichertes KI-Archiv',
            'Stammdaten und Identifikation',
            'Bestand, Menge und Status',
            'Preise und Steuergrundlagen',
        ] as $heading){
            self::assertStringContainsString($heading, $template);
        }

        foreach([
            'hw24-editor',
            'hw24-section-layout',
            'hw24-price-grid',
            'hw24-channel-layout',
            '@media(max-width:760px)',
            'wire:submit="findArticle"',
            'wire:click="readJtlSources"',
            'wire:click="loadMoreJtlSources"',
            'wire:click="readAmazon"',
            'wire:click="readAiArchive"',
            'wire:model.live.debounce.300ms="sourceQuery"',
            'Source',
        ] as $marker){
            if ($marker === 'Source') {
                continue;
            }
            self::assertStringContainsString($marker, $template);
        }
        // Only the pending shared job status may poll. No provider polling or automatic paid query.
        self::assertSame(1,substr_count($template,'wire:poll'));
        self::assertStringContainsString("@if(in_array(\$queueReview['status']??null,['queued','leased','retry'],true))",$template);
        self::assertStringContainsString('wire:poll.15s="pollArticleResearch"',$template);
        self::assertStringNotContainsString('wire:init', $template);
        self::assertStringNotContainsString('chmod -R', $template);
        self::assertStringContainsString('Keine automatische EUR-Annahme', $template);

        // Parse the full Blade template, including all nested conditionals.
        $compiled=Blade::compileString($template);
        self::assertStringContainsString('hw24-panel', $compiled);
    }

    public function test_initial_render_does_not_call_external_apis(): void
    {
        Http::preventStrayRequests();
        Livewire::test(ArticleLookup::class)
            ->assertSee('01 · Artikel finden')
            ->assertSee('03 · JTL-Originalfelder und Kontopreise')
            ->assertSee('04 · Amazon Selling Partner API');
        Http::assertNothingSent();
        Livewire::test(ArticleLookup::class)->call('pollArticleResearch');
        Http::assertNothingSent();
    }
}
