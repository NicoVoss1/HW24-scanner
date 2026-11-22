<x-filament-panels::page>
    <div class="hw24-scan-workbench">
        <style>
            .hw24-scan-workbench{font:inherit;color:#1b2b40}
            .hw24-scan-workbench *{box-sizing:border-box}
            .hw24-scan-workbench .hw24-hero{background:linear-gradient(115deg,#12304a,#1c5375);color:#fff;padding:22px 25px;border-radius:16px;display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap}
            .hw24-scan-workbench h2{font-size:22px;line-height:1.3;font-weight:800;margin:3px 0 7px}
            .hw24-scan-workbench h3{font-size:15px;line-height:1.4;font-weight:750;margin:0}
            .hw24-scan-workbench .hw24-kicker{color:#b5daf5;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}
            .hw24-scan-workbench .hw24-lead{font-size:13px;line-height:1.65;color:#e2edf7;max-width:620px}
            .hw24-scan-workbench .hw24-open{background:white;color:#14416b;padding:10px 14px;border-radius:9px;display:inline-flex;align-items:center;text-decoration:none;font-size:12px;font-weight:800}
            .hw24-scan-workbench .hw24-card{margin-top:16px;border:1px solid #dbe5ef;border-radius:14px;background:#fff;overflow:hidden}
            .hw24-scan-workbench .hw24-card-head{padding:15px 18px;background:#f5f8fb;border-bottom:1px solid #dbe5ef}
            .hw24-scan-workbench .hw24-card-body{padding:18px}
            .hw24-scan-workbench .hw24-description{color:#586b82;font-size:12px;line-height:1.65;margin:0 0 14px}
            .hw24-scan-workbench .hw24-action{display:flex;flex-wrap:wrap;align-items:center;gap:11px}
            .hw24-scan-workbench .hw24-note{border-radius:10px;padding:12px 15px;border:1px solid #cbddeb;background:#f2f8fc;font-size:12px;line-height:1.65;color:#264968;margin-top:13px}
            .hw24-scan-workbench .hw24-note-warn{border-color:#efd2ab;background:#fff8e9;color:#88490c}
            .hw24-scan-workbench .hw24-table-wrap{overflow:auto;max-height:530px;border:1px solid #dbe5ef;border-radius:10px}
            .hw24-scan-workbench table{width:100%;min-width:650px;font-size:12px;border-collapse:collapse;text-align:left}
            .hw24-scan-workbench th{padding:11px;color:#54677b;background:#f4f7fa;text-transform:uppercase;letter-spacing:.03em;font-size:11px;white-space:nowrap}
            .hw24-scan-workbench td{padding:12px;border-top:1px solid #e0e8f0;vertical-align:middle}
            .hw24-scan-workbench .hw24-btn{cursor:pointer;border:1px solid #b7d0e5;background:#fff;color:#16466f;font-size:12px;font-weight:750;border-radius:9px;padding:8px 12px;min-height:36px}
            .hw24-scan-workbench .hw24-btn:hover{background:#edf6fc}
            .hw24-scan-workbench .hw24-btn:focus-visible,.hw24-scan-workbench a:focus-visible{outline:3px solid #60a5fa;outline-offset:2px}
            .hw24-scan-workbench .hw24-stat{display:inline-flex;background:#e9f6ee;border-radius:999px;color:#086138;padding:5px 10px;font-size:11px;font-weight:750}
            .hw24-scan-workbench .hw24-sub{color:#64748b;font-size:11px;margin-top:5px}
            .hw24-scan-workbench .hw24-empty{padding:17px;color:#64748b;font-size:13px}
            .hw24-scan-workbench .hw24-link{color:#1d5f92;text-decoration:underline;text-underline-offset:3px}
            @media(max-width:720px){.hw24-scan-workbench .hw24-hero{padding:18px}.hw24-scan-workbench h2{font-size:19px}.hw24-scan-workbench .hw24-card-body{padding:14px}}
            html.dark .hw24-scan-workbench{color:#edf2fb}
            html.dark .hw24-scan-workbench .hw24-card{background:#182434;border-color:#34465c}
            html.dark .hw24-scan-workbench .hw24-card-head,html.dark .hw24-scan-workbench th{background:#223046;border-color:#34465c;color:#e2e8f0}
            html.dark .hw24-scan-workbench td{border-color:#34465c}
            html.dark .hw24-scan-workbench .hw24-description,html.dark .hw24-scan-workbench .hw24-sub{color:#cbd5e1}
        </style>

        <header class="hw24-hero">
            <div>
                <p class="hw24-kicker">HW24 · Bestehende Scan-Daten</p>
                <h2>Scanliste und Sammelimport</h2>
                <p class="hw24-lead">Deine bestehenden Stapel und Positionen bleiben im HW24-Legacy-System erhalten. Du kannst sie hier prüfen und den vollständigen Scan-, CSV- und Ameise-Workflow weiterhin verwenden.</p>
            </div>
            <a class="hw24-open" href="https://artikelmanager.hairworld24.de/scanliste.html"
               target="_blank" rel="noopener noreferrer">Scanliste vollständig öffnen</a>
        </header>

        <section class="hw24-card">
            <div class="hw24-card-head"><h3>01 · Vorhandene Scan-Stapel anzeigen</h3></div>
            <div class="hw24-card-body">
                <p class="hw24-description">Die Abfrage ist ausdrücklich nur lesend. Die API liefert höchstens 50 offene Stapel des vorhandenen, durch Basic-Authentifizierung zugeordneten Benutzers. Es werden keine neuen Stapel erstellt.</p>
                <div class="hw24-action">
                    <x-filament::button type="button" wire:click="loadBatches" wire:loading.attr="disabled" wire:target="loadBatches">Gespeicherte Stapel laden</x-filament::button>
                    <span class="hw24-sub">Keine automatische API-Abfrage beim Öffnen der Seite</span>
                </div>
                @if($diagnostic !== [])
                    <p class="hw24-note {{ ($diagnostic['ok'] ?? false) ? '' : 'hw24-note-warn' }}" role="status">
                        {{ $diagnostic['message'] ?? 'Status nicht bekannt' }}
                        @if(isset($diagnostic['http']) && $diagnostic['http'] !== null) (HTTP {{ $diagnostic['http'] }}) @endif
                    </p>
                @endif
                @if($batches !== [])
                    <div class="hw24-table-wrap" style="margin-top:15px">
                        <table>
                            <thead><tr><th>Stapel</th><th>Erstellt UTC</th><th>Letzte Änderung UTC</th><th>Aktion</th></tr></thead>
                            <tbody>
                                @foreach($batches as $batch)
                                    <tr wire:key="hw24-scanbatch-{{ $batch['id'] }}">
                                        <td><strong>{{ $batch['label'] }}</strong><div class="hw24-sub">Stapel-ID {{ $batch['id'] }}</div></td>
                                        <td>{{ $batch['created'] ?: 'Nicht belegt' }}</td>
                                        <td>{{ $batch['updated'] ?: 'Nicht belegt' }}</td>
                                        <td><button type="button" class="hw24-btn" wire:click="loadBatch({{ $batch['id'] }})" wire:loading.attr="disabled" wire:target="loadBatch">Positionen anzeigen</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(($diagnostic['ok'] ?? false) === true)
                    <div class="hw24-empty">Für das verwendete Basic-Auth-Konto wurden keine offenen Scan-Stapel zurückgegeben. Geschlossene Stapel sind in dieser API-Liste nicht enthalten.</div>
                @endif
            </div>
        </section>

        <section class="hw24-card">
            <div class="hw24-card-head"><h3>02 · Neue Stapel anlegen und EANs direkt in Filament scannen</h3></div>
            <div class="hw24-card-body">
                <p class="hw24-description">Die Daten landen in den bestehenden HW24-MariaDB-Tabellen. Kein neuer Datenbestand, keine Wawi-Buchung. Der Schreibzugang ist zusätzlich durch eine private Serverfreigabe geschützt.</p>
                @if(!$this->canWriteScan())
                    <p class="hw24-note hw24-note-warn">Die private Freigabe für Scan-Schreibaktionen ist noch deaktiviert. Bestehende Scan-Stapel können weiterhin gelesen werden. Du kannst bis zur Freigabe die bisherige Scanner-Oberfläche nutzen.</p>
                @endif
                <div class="hw24-action" style="margin:10px 0">
                    <label for="hw24-newbatch" style="font-size:12px;font-weight:750;flex:1 1 230px">
                        Neuen Scan-Stapel benennen
                        <input class="hw24-btn" style="display:block;width:100%;text-align:left;margin-top:5px" id="hw24-newbatch" type="text" maxlength="160" wire:model="newBatchLabel" placeholder="z. B. Warenlieferung 22.11.2026">
                    </label>
                    <button type="button" class="hw24-btn" wire:click="createBatch"
                            wire:confirm="Neuen Scan-Stapel in der vorhandenen HW24-MariaDB anlegen?"
                            wire:loading.attr="disabled" wire:target="createBatch"
                            @disabled(!$this->canWriteScan() || !$confirmScanWrite)>Neuen Stapel speichern</button>
                </div>
                <div class="hw24-action" style="margin:10px 0">
                    <label for="hw24-scan-ean" style="font-size:12px;font-weight:750;flex:1 1 230px">
                        EAN / GTIN im gewählten Stapel erfassen
                        <input class="hw24-btn" style="display:block;width:100%;text-align:left;margin-top:5px" id="hw24-scan-ean" type="search" maxlength="14" inputmode="numeric" autocomplete="off"
                            wire:model="scanEan" placeholder="Barcode scannen oder eintippen">
                    </label>
                    <button type="button" class="hw24-btn" wire:click="scanEanIntoBatch"
                            wire:confirm="Diese EAN jetzt verbindlich in den ausgewählten HW24-Scan-Stapel schreiben? Keine JTL-Buchung."
                            wire:loading.attr="disabled" wire:target="scanEanIntoBatch"
                            @disabled(!$this->canWriteScan() || !$confirmScanWrite || $selectedBatchId === null)>EAN speichern</button>
                </div>
                <label style="font-size:12px;line-height:1.6;display:flex;gap:9px;align-items:center;margin-top:10px">
                    <input type="checkbox" wire:model.live="confirmScanWrite">
                    <span>Ich bestätige ausdrücklich das Speichern in der bestehenden Scanliste. Keine Lager-/Bestandsbuchung.</span>
                </label>
                @if($actionState !== [])
                    <p class="hw24-note {{ ($actionState['ok']??false) ? '' : 'hw24-note-warn' }}" role="status">
                        {{ $actionState['message'] }}
                        @if(($actionState['http']??null)!==null) · HTTP {{ $actionState['http'] }} @endif
                    </p>
                @endif
            </div>
        </section>

        @if($selectedBatchId !== null)
            <section class="hw24-card">
                <div class="hw24-card-head"><h3>02 · Gespeicherte Positionen aus Stapel {{ $selectedBatchId }}</h3></div>
                <div class="hw24-card-body">
                    <p class="hw24-description">Maximal 3.000 Positionen aus der vorhandenen MariaDB. Diese Ansicht zeigt keine bearbeitbaren Entwürfe und löst keine Lagerbuchung oder Artikelanlage aus.</p>
                    <span class="hw24-stat">{{ count($items) }} Positionen geladen</span>
                    <div class="hw24-action" style="margin:12px 0">
                        <button type="button" class="hw24-btn" wire:click="loadBatchAiMarkers" wire:loading.attr="disabled" wire:target="loadBatchAiMarkers">
                            🧠 Bereits geprüfte KI-Artikel anzeigen
                        </button>
                        <span class="hw24-sub">Ein kostenloser Archiv-GET für maximal 150 EANs, keine neue KI-Abfrage</span>
                    </div>
                    @if($batchAiStatus!==[])
                        <p class="hw24-note {{ ($batchAiStatus['ok']??false)?'':'hw24-note-warn' }}" role="status">
                            {{ $batchAiStatus['message'] }}
                            @if(($batchAiStatus['ok']??false)===true)
                                · {{ count($batchAiMarkers) }} zuvor analysiert
                                @if($batchAiStatus['partial']) · Die Marker-Liste ist auf 150 EANs begrenzt @endif
                            @endif
                        </p>
                    @endif
                    @if($items !== [])
                        <div class="hw24-table-wrap" style="margin-top:14px">
                            <table>
                                <thead><tr><th>EAN</th><th>Artikel</th><th>Scans</th><th>JTL-Abgleich</th><th>Prüfstatus</th><th>KI-Archiv</th></tr></thead>
                                <tbody>
                                    @foreach($items as $i => $item)
                                        <tr wire:key="hw24-scan-item-{{ $selectedBatchId }}-{{ $i }}">
                                            <td><strong>{{ $item['ean'] }}</strong><div class="hw24-sub">{{ $item['article_number'] ?: 'Artikelnummer offen' }}</div></td>
                                            <td>{{ $item['name'] ?: 'Noch nicht identifiziert' }}</td>
                                            <td>{{ $item['scans'] }}</td>
                                            <td>{{ $item['resolution'] }}</td>
                                            <td>{{ $item['review'] }}</td>
                                            <td>
                                                @if(isset($batchAiMarkers[$item['ean']]))
                                                    <span class="hw24-stat">🧠 Bereits geprüft</span>
                                                    <div class="hw24-sub">{{ $batchAiMarkers[$item['ean']]['count'] }} Bericht(e) · zuletzt {{ $batchAiMarkers[$item['ean']]['at'] }} UTC</div>
                                                @elseif(($batchAiStatus['ok']??false)===true)
                                                    <span class="hw24-sub">Kein bestätigter Archivtreffer in der geprüften Teilmenge</span>
                                                @else
                                                    <span class="hw24-sub">Noch nicht geprüft</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="hw24-empty">Dieser Stapel enthält aktuell keine anzeigbaren Positionen.</p>
                    @endif
                </div>
            </section>
        @endif

        <section class="hw24-card">
            <div class="hw24-card-head"><h3>04 · JTL-Ameise-Prüfexport direkt in Filament</h3></div>
            <div class="hw24-card-body">
                <p class="hw24-description">Die bestehende Scanlisten-API prüft vor dem CSV-Export den aktuellen JTL-Snapshot, freigegebene Neuanlagen, doppelte Artikelnummern und Pflichtfelder. Maximal 200 freigegebene Positionen. Nur ein Prüfexport, kein JTL-Direktimport.</p>
                <div class="hw24-action">
                    <button type="button" class="hw24-btn" wire:click="prepareAmeiseExport" wire:loading.attr="disabled" wire:target="prepareAmeiseExport" @disabled($selectedBatchId === null)>Freigegebene Positionen prüfen</button>
                    @if(($exportState['ok']??false)===true)
                        <button type="button" class="hw24-btn" wire:click="downloadAmeiseCsv"
                                wire:confirm="Ameise-Prüf-CSV für {{ $exportState['count'] }} bestätigte Neuanlagen herunterladen? Dies ist KEIN Import in JTL-Wawi."
                                wire:loading.attr="disabled" wire:target="downloadAmeiseCsv">CSV-Prüfexport herunterladen</button>
                    @endif
                </div>
                @if($exportState !== [])
                    <p class="hw24-note {{ ($exportState['ok']??false) ? '' : 'hw24-note-warn' }}" role="status">{{ $exportState['message'] }}
                        @if(($exportState['count']??null)!==null) · {{ $exportState['count'] }} freigegebene Positionen @endif
                    </p>
                @endif
                <p class="hw24-note hw24-note-warn">Vor einem echten JTL-Ameise-Import: Feldzuordnung, Preistypen, Steuersätze und Artikelnummern prüfen, JTL-Datenbank sichern und einen Import-Trockenlauf durchführen. Niemals einen automatischen Import in Wawi auslösen.</p>
            </div>
        </section>

        <section class="hw24-card">
            <div class="hw24-card-head"><h3>03 · Alle vorhandenen Scan-Funktionen weiter nutzen</h3></div>
            <div class="hw24-card-body">
                <p class="hw24-description">Die bestehende, funktionsfähige Scanliste bleibt bis zur vollständigen sicheren Migration die führende Oberfläche für Erfassung, Änderung, Freigabe und Prüfexport.</p>
                <div class="hw24-action">
                    <a class="hw24-open" style="background:#1b567b;color:#fff" href="https://artikelmanager.hairworld24.de/scanliste.html" target="_blank" rel="noopener noreferrer">EAN scannen, Stapel bearbeiten und CSV exportieren</a>
                    <a class="hw24-link" href="https://artikelmanager.hairworld24.de/index.html#ameisePanel" target="_blank" rel="noopener noreferrer">Expertenansicht mit JTL-Ameise-Entwurf</a>
                </div>
                <p class="hw24-note hw24-note-warn">Diese Links öffnen die geschützte bisherige LIVE-Anwendung in einem neuen Tab. Dort gelten die bestehenden Berechtigungen und Freigaben. Ein CSV-Prüfexport ist kein direkter Import in JTL-Wawi.</p>
            </div>
        </section>
    </div>
</x-filament-panels::page>
