<x-filament-panels::page>
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
                                    {{ is_numeric($row['netto_vk']) ? number_format((float) $row['netto_vk'], 2, ',', '.') . ' EUR netto' : 'unbekannt' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-gray-500">JTL-Bruttopreis: nicht bestätigt. Es wird kein Steuersatz erfunden.</p>
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
