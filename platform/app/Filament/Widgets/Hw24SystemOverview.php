<?php
declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\LegacyReadBridge;
use Filament\Widgets\Widget;
use Livewire\Attributes\Locked;

/**
 * Operator-controlled management cockpit.
 *
 * Rendering the dashboard never performs external HTTP or billable calls.
 * Only an explicit click loads one JTL reference article or its saved AI
 * history via the pinned read-only LIVE bridge. These observations are NOT
 * global JTL counts, current marketplace offers or verified shop prices.
 */
final class Hw24SystemOverview extends Widget
{
    protected string $view = 'filament.widgets.hw24-system-overview';

    protected int | string | array $columnSpan = 'full';

    private const SAMPLE_EAN = '3474630337398';

    #[Locked]
    public array $jtl = [];

    #[Locked]
    public array $archive = [];

    public function isBridgeConfigured(): bool
    {
        return config('hw24_legacy.base_url') === 'https://artikelmanager.hairworld24.de'
            && trim((string) config('hw24_legacy.user')) !== ''
            && trim((string) config('hw24_legacy.password')) !== '';
    }

    /** Exactly one manual read-only JTL GET, no background polling. */
    public function inspectJtl(LegacyReadBridge $bridge): void
    {
        $result = $bridge->fetch('jtl.article', ['q' => self::SAMPLE_EAN]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
        $matches = is_array($raw['matches'] ?? null) ? $raw['matches'] : [];

        $verified = ($result['ok'] ?? false) === true
            && ($raw['jtl_status'] ?? null) === 'current_snapshot'
            && count($matches) === 1
            && is_array($matches[0])
            && (string) ($matches[0]['ean'] ?? '') === self::SAMPLE_EAN;

        $article = $verified ? $matches[0] : [];
        $this->jtl = [
            'ok' => $verified,
            'http' => $result['http_status'] ?? null,
            'snapshot' => (string) ($raw['jtl_status'] ?? 'unbekannt'),
            'at' => is_scalar($raw['snapshot_at'] ?? null)
                ? (string) $raw['snapshot_at'] : null,
            'ean' => self::SAMPLE_EAN,
            'article' => (string) ($article['artikelname'] ?? ''),
            'article_id' => (string) ($article['jtl_article_id'] ?? ''),
            'stock' => is_numeric($article['bestand'] ?? null) ? (string) $article['bestand'] : null,
            'net_price' => is_numeric($article['netto_vk'] ?? null) ? (string) $article['netto_vk'] : null,
            'checked_at' => now()->format('d.m.Y H:i'),
        ];
    }

    /** Reads existing historic records only. No OpenAI API or billing. */
    public function inspectArchive(LegacyReadBridge $bridge): void
    {
        $result = $bridge->fetch('ai.history', ['ean' => self::SAMPLE_EAN, 'limit' => 5]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];

        $verified = ($result['ok'] ?? false) === true
            && is_array($raw['reviews'] ?? null);
        $this->archive = [
            'ok' => $verified,
            'http' => $result['http_status'] ?? null,
            'count' => $verified ? count($raw['reviews']) : null,
            'limited' => true,
            'checked_at' => now()->format('d.m.Y H:i'),
        ];
    }
}
