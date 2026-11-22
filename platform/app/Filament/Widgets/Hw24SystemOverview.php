<?php
declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Configuration overview only. Never probes billable APIs or counts JTL rows
 * during dashboard rendering. Actual source results are shown after explicit
 * operator requests in ArticleLookup.
 */
final class Hw24SystemOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $legacyConfigured = config('hw24_legacy.base_url') === 'https://artikelmanager.hairworld24.de'
            && trim((string) config('hw24_legacy.user')) !== ''
            && trim((string) config('hw24_legacy.password')) !== '';

        return [
            Stat::make('JTL-Wawi / MariaDB-Spiegel', $legacyConfigured ? 'Zugang konfiguriert' : 'Zugang ausstehend')
                ->description('Nur lesend · Quelle und Datenfrische im Artikel prüfen'),
            Stat::make('Amazon Selling Partner API', $legacyConfigured ? 'Legacy-Bridge konfiguriert' : 'Noch nicht verbunden')
                ->description('Katalog nur bei ausdrücklichem Aufruf, Angebote separat'),
            Stat::make('OpenAI KI-Archiv', $legacyConfigured ? 'Legacy-Bridge konfiguriert' : 'Noch nicht verbunden')
                ->description('Bereits gespeicherte Analysen, kein automatischer API-Aufruf'),
            Stat::make('Shopify / eBay', 'Noch nicht verifiziert')
                ->description('Eigene Kanalpreise, Marktplätze und Verkaufsstatus folgen'),
        ];
    }
}
