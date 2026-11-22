<?php
declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Operator-oriented dashboard, not a simulated analytics dashboard.
 *
 * Values describe configured integration capabilities only. Rendering MUST
 * remain local: no Amazon SP-API/OpenAI calls, legacy HTTP polling, SQL mirror
 * bulk queries, or writes. Live evidence is loaded in ArticleLookup on demand.
 */
final class Hw24SystemOverview extends Widget
{
    protected string $view = 'filament.widgets.hw24-system-overview';

    protected int | string | array $columnSpan = 'full';

    protected static ?bool $isLazyLoaded = false;

    /** @return array<int, array{label:string,value:string,detail:string,phase:string}> */
    public function connectionCards(): array
    {
        $bridgeConfigured = config('hw24_legacy.base_url') === 'https://artikelmanager.hairworld24.de'
            && trim((string) config('hw24_legacy.user')) !== ''
            && trim((string) config('hw24_legacy.password')) !== '';

        return [
            [
                'label' => 'JTL-Wawi / MariaDB',
                'value' => $bridgeConfigured ? 'Lesezugang eingerichtet' : 'Zugang fehlt',
                'detail' => 'Aktualität und Artikelzahlen erst nach verifiziertem Quellenabruf.',
                'phase' => $bridgeConfigured ? 'Konfiguriert' : 'Ausstehend',
            ],
            [
                'label' => 'Amazon',
                'value' => $bridgeConfigured ? 'Katalog abrufbar' : 'Zugang nicht geprüft',
                'detail' => 'Nur auf Klick. Ein Katalogtreffer ist kein eigenes Verkäuferangebot.',
                'phase' => 'Kein automatischer Abruf',
            ],
            [
                'label' => 'KI-Archiv',
                'value' => $bridgeConfigured ? 'Archivzugang eingerichtet' : 'Zugang fehlt',
                'detail' => 'Historische Analysen nur lesend, keine kostenpflichtigen Aufrufe.',
                'phase' => 'Nur auf Klick',
            ],
            [
                'label' => 'Shopify / eBay',
                'value' => 'Angebote nicht verifiziert',
                'detail' => 'Eigene Konten, Angebotspreise und Verkaufstatus noch getrennt nachweisen.',
                'phase' => 'In Vorbereitung',
            ],
        ];
    }
}
