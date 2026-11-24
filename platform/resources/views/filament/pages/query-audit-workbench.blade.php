<x-filament-panels::page>
    <div style="display:grid;gap:16px">
        <section style="border:1px solid #94a3b8;border-radius:12px;padding:16px">
            <h2 style="font-weight:700;font-size:18px">Zentrales Abfrageprotokoll · nur Administration</h2>
            <p>Gemeinsame Produktdaten, getrennte Verantwortlichkeit: Jede protokollierte Aktion über die Plattformbrücken erhält den angemeldeten Benutzer, Quelle, Produktkennung, Zeitpunkt und Ergebnis.</p>
            <p style="margin-top:8px;font-weight:600">{{ $auditEnabled ? 'Audit-Aufzeichnung aktiviert' : 'Audit-Aufzeichnung deaktiviert – vorhandene Einträge bleiben lesbar' }}</p>
            <p>Dieses Protokoll erfasst die Plattformbrücken, noch nicht alle direkten Legacy-Aufrufe. Kosten, Cache-/Live-Belege und historische Basic-Auth-Autoren werden separat geführt. Keine Passwörter oder API-Rohdaten.</p>
        </section>
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
            <label>Quelle <select wire:model.live="sourceFilter" style="display:block;border:1px solid #94a3b8;border-radius:6px;padding:8px;background:transparent">
                <option value="">Alle</option><option value="jtl">JTL</option><option value="amazon">Amazon</option><option value="ai">KI</option><option value="scanner">Scanner</option><option value="legacy">Legacy</option><option value="ebay">eBay</option><option value="shopify">Shopify</option>
            </select></label>
            <label>Benutzer-ID <input wire:model.live.debounce.500ms="actorFilter" maxlength="18" inputmode="numeric" style="display:block;border:1px solid #94a3b8;border-radius:6px;padding:8px;background:transparent"></label>
            <label>EAN / ASIN <input wire:model.live.debounce.500ms="identifierFilter" maxlength="14" style="display:block;border:1px solid #94a3b8;border-radius:6px;padding:8px;background:transparent"></label>
            <x-filament::button wire:click="$refresh">Aktualisieren</x-filament::button>
        </div>
        @if(!$storageAvailable)
            <p>Audit-Speicher nicht erreichbar oder Migration noch nicht installiert. Keine Aussage über vorhandene Altbelege möglich.</p>
        @elseif($rows->isEmpty())
            <p>Keine protokollierten Ereignisse zu dieser Auswahl.</p>
        @else
            <p>Neueste maximal 100 passende Ereignisse. Zeiten in UTC; Zeitpunkt der Protokollierung nach Rückkehr der Anfrage.</p>
            <div style="overflow:auto;border:1px solid #94a3b8;border-radius:8px">
                <table style="width:100%;border-collapse:collapse;text-align:left">
                    <thead><tr>@foreach(['Zeit (UTC)','Benutzer','Quelle / Aktion','Produkt','HTTP','Ergebnis','Dauer'] as $label)<th style="padding:10px">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach($rows as $row)
                        <tr style="border-top:1px solid #94a3b8">
                            <td style="padding:10px;white-space:nowrap">{{ $row->requested_at }}</td>
                            <td style="padding:10px">{{ $row->actor_ref ? ($names->get($row->actor_ref) ?? 'Unbekannter Benutzer').' (#'.$row->actor_ref.')' : 'System / nicht zugeordnet' }}</td>
                            <td style="padding:10px">{{ $row->source_system }} · {{ $row->operation }}</td>
                            <td style="padding:10px">{{ $row->identifier ?? '—' }}</td>
                            <td style="padding:10px">{{ $row->http_status ?? '—' }}</td>
                            <td style="padding:10px">{{ $row->result }}</td>
                            <td style="padding:10px">{{ $row->duration_ms !== null ? $row->duration_ms.' ms' : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
