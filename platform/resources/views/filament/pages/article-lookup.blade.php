<x-filament-panels::page>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Produktdaten und Vertriebskanäle</p>
                <h2 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Artikelzentrale</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    JTL-Stammdaten, Bestand und Preise mit belegter Herkunft. Es werden keine fehlenden Werte geschätzt.
                </p>
            </div>
            <a href="{{ route('filament.admin.pages.dashboard') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                Zum Kontrollzentrum
            </a>
        </div>
    </div>
    <x-filament::section heading="JTL-Artikel suchen">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Die Daten kommen aus dem bestehenden JTL-MariaDB-Spiegel. Keine Änderungen an JTL-Wawi oder WMS.
        </p>
        <form wire:submit="findArticle" class="mt-4 flex flex-wrap items-end gap-3">
            <label class="flex-1 min-w-64">
                <span class="block text-sm font-medium mb-1">EAN, HAN oder JTL-Artikelnummer</span>
                <input type="search" maxlength="64" wire:model="identifier" autocomplete="off"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    placeholder="z. B. 3474630337398">
            </label>
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="findArticle">
                Artikel laden
            </x-filament::button>
        </form>
        @error('identifier')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @if ($lookup !== [])
            <p class="mt-3 text-sm">
                JTL: <strong>{{ $lookup['jtl_status'] ?? 'unbekannt' }}</strong>
                | API: {{ $lookup['state'] ?? 'unbekannt' }}
                | HTTP: {{ $lookup['http_status'] ?? '—' }}
            </p>
            @if (count($lookup['matches'] ?? []) === 0)
                <p class="mt-2 text-sm text-amber-700">Kein verifizierter Treffer. Eine leere Liste kann auch an einem veralteten Spiegel liegen.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b">
                            <th class="py-2 pr-4">Artikel</th><th class="py-2 pr-4">EAN</th>
                            <th class="py-2 pr-4">Bestand (JTL)</th><th class="py-2 pr-4">VK netto (JTL)</th>
                        </tr></thead>
                        <tbody>
                        @foreach($lookup['matches'] as $row)
                            <tr class="border-b">
                                <td class="py-2 pr-4">
                                    <strong>{{ $row['artikelname'] }}</strong>
                                    <div class="text-xs text-gray-500">{{ $row['artikelnummer'] }} · {{ $row['hersteller'] }}</div>
                                </td>
                                <td class="py-2 pr-4">{{ $row['ean'] }}</td>
                                <td class="py-2 pr-4">{{ $row['bestand'] ?? 'unbekannt' }}</td>
                                <td class="py-2 pr-4">
                                    {{ is_numeric($row['netto_vk']) ? number_format((float) $row['netto_vk'], 2, ',', '.') . ' netto' : 'unbekannt' }}
                                    @if(!empty($row['waehrung']))<span class="text-xs text-gray-500">{{ $row['waehrung'] }}</span>@endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($lookup['matches'] ?? []) === 1)
                    @php
                        $item = $lookup['matches'][0];
                        $mass = $item['masseinheit_menge'] ?? null;
                        $massLabel = $mass !== null
                            ? rtrim(rtrim(number_format((float) $mass, 3, ',', '.'), '0'), ',')
                            : 'Nicht belegt';
                    @endphp
                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/40">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Artikeldaten aus dem JTL-Spiegel</h3>
                            <span class="text-xs text-gray-500">Synchronisiert: {{ $item['synced_at_utc'] ?? 'unbekannt' }} UTC</span>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-x-5 gap-y-4 text-sm md:grid-cols-3 xl:grid-cols-4">
                            <div class="min-w-0"><dt class="text-gray-500">JTL-Artikel-ID</dt><dd class="mt-1 break-words font-semibold">{{ $item['jtl_article_id'] ?: 'Nicht belegt' }}</dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">Artikelnummer</dt><dd class="mt-1 break-words font-semibold">{{ $item['artikelnummer'] ?: 'Nicht belegt' }}</dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">HAN</dt><dd class="mt-1 break-words font-semibold">{{ $item['han'] ?: 'Nicht belegt' }}</dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">Warengruppe</dt><dd class="mt-1 break-words font-semibold">{{ $item['warengruppe_name'] ?? 'Nicht zugeordnet' }}</dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">Artikeltyp</dt><dd class="mt-1 break-words font-semibold">{{ $item['artikeltyp'] ?? 'Noch nicht aufgelöst' }}</dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">Lieferstatus</dt><dd class="mt-1 break-words font-semibold">
                                {{ $item['lieferstatus_name'] ?? (!empty($item['lieferstatus_id']) ? 'ID ' . $item['lieferstatus_id'] . ' · Klartext offen' : 'Nicht belegt') }}
                            </dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">Steuerklasse</dt><dd class="mt-1 break-words font-semibold">
                                {{ $item['steuerklasse_name'] ?? (!empty($item['steuerklasse_id']) ? 'ID ' . $item['steuerklasse_id'] . ' · Klartext offen' : 'Nicht belegt') }}
                            </dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">Füllmenge / Maßmenge</dt><dd class="mt-1 break-words font-semibold">{{ $massLabel }}</dd>
                                <p class="mt-1 text-xs text-gray-500">Originalwert, Einheit ungeprüft</p>
                            </div>
                            <div class="min-w-0"><dt class="text-gray-500">EK netto (JTL)</dt><dd class="mt-1 break-words font-semibold">
                                {{ $item['netto_ek'] !== null ? number_format((float) $item['netto_ek'], 2, ',', '.') : 'Nicht belegt' }}
                            </dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">VK netto (JTL)</dt><dd class="mt-1 break-words font-semibold">
                                {{ $item['netto_vk'] !== null ? number_format((float) $item['netto_vk'], 2, ',', '.') : 'Nicht belegt' }}
                            </dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">VK brutto (JTL)</dt><dd class="mt-1 break-words font-semibold">
                                {{ $item['brutto_vk'] !== null ? number_format((float) $item['brutto_vk'], 2, ',', '.') : 'Quelle noch nicht bestätigt' }}
                            </dd>
                                @if($item['brutto_vk'] !== null)<p class="mt-1 break-words text-xs text-gray-500">Quelle: {{ $item['brutto_vk_source'] }}</p>@endif
                            </div>
                            <div class="min-w-0"><dt class="text-gray-500">Währung</dt><dd class="mt-1 break-words font-semibold">{{ $item['waehrung'] ?? 'Noch nicht belegt' }}</dd></div>
                            <div class="min-w-0"><dt class="text-gray-500">ASIN aus JTL</dt><dd class="mt-1 break-words font-semibold">{{ $item['asin'] ?? 'Keine Quelle' }}</dd>
                                <p class="mt-1 text-xs text-gray-500">Amazon-EAN und Angebot separat verifizieren</p>
                            </div>
                        </dl>
                    </div>
                @endif
                <p class="mt-3 text-xs text-gray-500">
                    Daten und Preisarten sind quellengebunden. Unbelegte Brutto-VK, Steuerzuordnungen oder Marktplatzangebote
                    werden nicht automatisch berechnet oder als aktiv gekennzeichnet.
                </p>
            @endif
        @endif
    </x-filament::section>

    <x-filament::section heading="JTL-Originalfelder, Steuerquellen und Verkaufskonten">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Alle in der bestehenden MariaDB gespiegelten Originalfelder bleiben dort erhalten.
            Diese Seite liest je Klick höchstens 80 Felder über eine geschützte Legacy-GET-API.
            Weitere Originalspalten und verifizierte Zusatzquellen können bei Bedarf geladen werden.
            Keine Änderungen an JTL-Wawi, SQL Server, WMS oder den Marktplatzangeboten.
        </p>
        <div class="mt-3">
            <x-filament::button type="button" wire:click="readJtlSources"
                wire:loading.attr="disabled" wire:target="readJtlSources"
                :disabled="count($lookup['matches'] ?? []) !== 1 || ($lookup['jtl_status'] ?? '') !== 'current_snapshot'">
                Originalfelder und Kanalpreise lesen
            </x-filament::button>
        </div>
        @if($sourceState !== [])
            @if(!($sourceState['ok'] ?? false))
                <p class="mt-3 text-sm text-amber-700">{{ $sourceState['reason'] ?? 'Quelle derzeit nicht verfügbar' }}</p>
            @else
                <p class="mt-3 text-sm">
                    {{ count($sourceRows) }} von {{ $sourceState['field_count'] ?? 0 }} Original- und Zusatzfeldern
                    aus JTL-Snapshot {{ $sourceState['snapshot_at'] ?? 'unbekannt' }} UTC.
                </p>
                <p class="mt-2 text-xs text-gray-500">
                    Fehlende eBay-Kontopreise, Shopify-Kanalpreise und unbestätigte JTL-Bruttopreise
                    bleiben ausdrücklich unbekannt. Keiner der JTL-Stammwerte ist damit
                    als tatsächlich aktives Marketplace-Angebot verifiziert.
                </p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach(($sourceState['prices'] ?? []) as $price)
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <p class="text-xs font-semibold">{{ $price['channel'] }} · {{ $price['account'] }}</p>
                            <p class="mt-2 text-lg font-bold">
                                {{ is_numeric($price['value'] ?? null) ? number_format((float) $price['value'], 4, ',', '.') : 'Nicht verfügbar' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $price['meaning'] }} · {{ $price['status'] }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">Quelle: {{ $price['source'] ?: 'nicht belegt' }}</p>
                            <p class="mt-1 text-xs text-gray-500">Währung: nicht bestätigt</p>
                        </div>
                    @endforeach
                </div>
                <label class="mt-5 block text-sm font-semibold">
                    Geladene SQL-Felder durchsuchen
                    <input type="search" wire:model.live.debounce.300ms="sourceQuery"
                        class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:bg-gray-800"
                        placeholder="z. B. Steuerklasse, kLieferStatus, Kosten, fVKNetto">
                </label>
                @php
                    $searchTerm = mb_strtolower(trim($sourceQuery));
                    $visibleSources = array_values(array_filter($sourceRows, function ($source) use ($searchTerm) {
                        return $searchTerm === '' || str_contains(
                            mb_strtolower((string) ($source['source'] ?? '') . ' ' .
                                (string) ($source['field'] ?? '') . ' ' . (string) ($source['value'] ?? '')),
                            $searchTerm
                        );
                    }));
                @endphp
                <div class="mt-4 max-h-[36rem] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full min-w-[44rem] text-left text-xs">
                        <thead><tr class="border-b bg-gray-50 dark:bg-gray-900">
                            <th class="p-2">SQL-Quelle</th>
                            <th class="p-2">Originalfeld</th>
                            <th class="p-2">Originalwert</th>
                        </tr></thead>
                        <tbody>
                        @forelse($visibleSources as $row)
                            <tr class="border-b align-top dark:border-gray-700">
                                <td class="p-2 break-all">{{ $row['source'] }} ({{ $row['row'] }})</td>
                                <td class="p-2 break-all">
                                    <code>{{ $row['field'] }}</code>
                                    @if($row['label'])<p class="text-gray-500">{{ $row['label'] }}</p>@endif
                                </td>
                                <td class="p-2 break-all">
                                    @if($row['value'] === null)
                                        <span class="text-amber-700">Nicht belegt</span>
                                    @elseif(mb_strlen($row['value']) > 220)
                                        {{ mb_substr($row['value'], 0, 180) }}…
                                        <details class="mt-2"><summary>Gesamten geladenen Text anzeigen</summary>
                                            <pre class="mt-2 whitespace-pre-wrap break-all">{{ $row['value'] }}</pre>
                                        </details>
                                    @else
                                        {{ $row['value'] === '' ? '(leer)' : $row['value'] }}
                                    @endif
                                    @if($row['truncated'])<p class="text-amber-700">Vorschau gekürzt, Original im SQL-Spiegel erhalten</p>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-3">Kein Feld in den geladenen Seiten gefunden.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($sourceState['has_more'] ?? false)
                    <div class="mt-3">
                        <x-filament::button type="button" color="gray" wire:click="loadMoreJtlSources"
                            wire:loading.attr="disabled" wire:target="loadMoreJtlSources">
                            Weitere Originalfelder laden
                        </x-filament::button>
                    </div>
                @endif
            @endif
        @endif
    </x-filament::section>

    <div class="grid gap-4 md:grid-cols-2">
        <x-filament::section heading="Amazon Selling Partner API">
            <p class="text-sm text-gray-500">Katalogdaten und mögliche Fehlermeldungen. Kein automatischer API-Aufruf.</p>
            <div class="mt-3">
                <x-filament::button type="button" wire:click="readAmazon"
                    wire:loading.attr="disabled" wire:target="readAmazon"
                    :disabled="$this->verifiedEan() === null">Amazon jetzt abfragen</x-filament::button>
            </div>
            @if($amazon !== [])
                <p class="mt-3 text-sm">
                    {{ $amazon['ok'] ? 'Verbunden' : 'Nicht verfügbar' }}
                    · HTTP {{ $amazon['http_status'] ?? '—' }}
                    · Quelle {{ $amazon['source'] ?? 'unbekannt' }}
                    · Treffer {{ $amazon['count'] ?? 'unbekannt' }}
                </p>
                @if($amazon['error'] !== '')<p class="text-sm text-amber-700">{{ $amazon['error'] }} ({{ $amazon['stage'] }})</p>@endif
            @endif
        </x-filament::section>
        <x-filament::section heading="OpenAI · vorhandenes KI-Archiv">
            <p class="text-sm text-gray-500">Nur gespeicherte Auswertungen. Es wird keine kostenpflichtige Analyse gestartet.</p>
            <div class="mt-3">
                <x-filament::button type="button" wire:click="readAiArchive"
                    wire:loading.attr="disabled" wire:target="readAiArchive"
                    :disabled="$this->verifiedEan() === null">KI-Archiv lesen</x-filament::button>
            </div>
            @if($ai !== [])
                <p class="mt-3 text-sm">
                    {{ $ai['ok'] ? 'Archiv erreichbar' : 'Archiv nicht verfügbar' }}
                    · HTTP {{ $ai['http_status'] ?? '—' }}
                    · Einträge {{ $ai['count'] ?? 'unbekannt' }}
                </p>
                @if($ai['error'] !== '')<p class="text-sm text-amber-700">{{ $ai['error'] }}</p>@endif
            @endif
        </x-filament::section>
    </div>
    <x-filament::section heading="Weitere Plattform-Module">
        <p class="text-sm text-gray-500">
            Shopify, eBay, WMS und Kanalpreisvergleich werden schrittweise über geprüfte Leseadapter ergänzt.
            Amazon-Katalogtreffer sind nicht automatisch eigene Verkäuferangebote.
            Direkte Schreibzugriffe auf JTL, Shopify und Marktplätze bleiben gesperrt.
        </p>
    </x-filament::section>
</x-filament-panels::page>
