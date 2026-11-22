<?php
declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\LegacyReadBridge;
use Filament\Widgets\Widget;
use Livewire\Attributes\Locked;

/**
 * Responsive dashboard with on-demand, bounded evidence.
 *
 * Rendering performs NO upstream HTTP and NO DB mirror queries. Only a real
 * operator click issues a single GET to an allowlisted authenticated legacy API.
 * No Amazon SP-API, no billable OpenAI POST, no SQL writes or background polling.
 */
final class Hw24SystemOverview extends Widget
{
    protected string $view = 'filament.widgets.hw24-system-overview';

    protected int | string | array $columnSpan = 'full';

    protected static ?bool $isLazyLoaded = false;

    private const REFERENCE_EAN = '3474630337398';
    private const REFERENCE_JTL_ID = '1428';

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

    /** Single explicitly requested probe of a known reference article. */
    public function inspectJtl(LegacyReadBridge $bridge): void
    {
        if (! $this->isBridgeConfigured()) {
            $this->jtl = [];
            return;
        }

        $result = $bridge->fetch('jtl.article', ['q' => self::REFERENCE_EAN]);
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $matches = is_array($data['matches'] ?? null) ? $data['matches'] : [];
        $article = count($matches) === 1 && is_array($matches[0]) ? $matches[0] : [];
        $verified = ($result['ok'] ?? false) === true
            && ($data['jtl_status'] ?? null) === 'current_snapshot'
            && count($matches) === 1
            && (string) ($article['ean'] ?? '') === self::REFERENCE_EAN
            && (string) ($article['jtl_article_id'] ?? '') === self::REFERENCE_JTL_ID;

        $stock = $verified && is_numeric($article['bestand'] ?? null)
            ? (string) $article['bestand'] : null;
        $netPrice = $verified && is_numeric($article['netto_vk'] ?? null)
            ? (float) $article['netto_vk'] : null;

        $this->jtl = [
            'ok' => $verified,
            'http' => $result['http_status'] ?? null,
            'snapshot' => substr((string) ($data['jtl_status'] ?? 'unbekannt'), 0, 35),
            'ean' => self::REFERENCE_EAN,
            'article_id' => $verified ? self::REFERENCE_JTL_ID : null,
            'article' => $verified ? mb_substr((string) ($article['artikelname'] ?? ''), 0, 120) : null,
            'stock' => $stock,
            'net_price' => $netPrice,
            'checked_at' => now()->format('d.m.Y H:i:s'),
        ];
    }

    /** Read no more than five already stored review records for one reference EAN. */
    public function inspectArchive(LegacyReadBridge $bridge): void
    {
        if (! $this->isBridgeConfigured()) {
            $this->archive = [];
            return;
        }

        $result = $bridge->fetch('ai.history', ['ean' => self::REFERENCE_EAN, 'limit' => 5]);
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $ok = ($result['ok'] ?? false) === true && is_array($data['reviews'] ?? null);

        $this->archive = [
            'ok' => $ok,
            'http' => $result['http_status'] ?? null,
            'count' => $ok ? count($data['reviews']) : null,
            'checked_at' => now()->format('d.m.Y H:i:s'),
        ];
    }
}
