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

            .hw24-scan-workbench .hw24-table-wrap{max-width:100%;overflow-y:auto;overflow-x:hidden}
            .hw24-scan-workbench table{min-width:0;table-layout:fixed}
            .hw24-scan-workbench th,.hw24-scan-workbench td{white-space:normal;overflow-wrap:anywhere}
            .hw24-scan-workbench .hw24-btn:disabled{opacity:.48;cursor:not-allowed}
            .hw24-scan-workbench .hw24-upload{display:flex;align-items:center;flex-wrap:wrap;gap:14px;border:1px dashed #9ebbd4;border-radius:12px;padding:16px;background:#f5f9fd}
            .hw24-scan-workbench .hw24-file-pick{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;background:#164b72;color:white;border-radius:9px;padding:12px 16px;font-weight:750;font-size:13px;min-height:44px}
            .hw24-scan-workbench .hw24-file-pick input{position:absolute;inset:0;opacity:0;width:100%;height:100%;cursor:pointer}
            .hw24-scan-workbench .hw24-file-pick:focus-within{outline:3px solid #60a5fa;outline-offset:2px}
            .hw24-scan-workbench .hw24-file-pick:has(input:disabled){opacity:.5;cursor:not-allowed}
            .hw24-scan-workbench .hw24-file-info{flex:1 1 180px;min-width:0;overflow-wrap:anywhere;font-size:13px;line-height:1.55}
            .hw24-scan-workbench .hw24-position-list{container-type:inline-size;border:1px solid #dbe5ef;border-radius:11px;max-height:680px;overflow-y:auto}
            .hw24-scan-workbench .hw24-position{padding:13px 14px;border-top:1px solid #e0e8f0;font-size:13px;line-height:1.5}
            .hw24-scan-workbench .hw24-position:first-child{border-top:0}
            .hw24-scan-workbench .hw24-position-main{display:grid;grid-template-columns:24px minmax(0,1.65fr) minmax(0,1fr) minmax(0,.85fr);align-items:start;gap:12px}
            .hw24-scan-workbench .hw24-position-main>*{min-width:0;overflow-wrap:anywhere}
            .hw24-scan-workbench .hw24-position-title{font-size:14px;font-weight:750;line-height:1.45}
            .hw24-scan-workbench .hw24-position-actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
            .hw24-scan-workbench .hw24-position-actions .hw24-btn{max-width:100%;white-space:normal}
            .hw24-scan-workbench .hw24-detail{margin:8px 0 0 36px;font-size:12px}
            .hw24-scan-workbench .hw24-detail summary{cursor:pointer;color:#1d5f92;font-weight:650;width:fit-content}
            .hw24-scan-workbench .hw24-facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,175px),1fr));gap:10px;background:#f5f8fb;padding:12px;border-radius:9px;margin-top:8px}
            .hw24-scan-workbench .hw24-facts div{min-width:0;overflow-wrap:anywhere}
            .hw24-scan-workbench .hw24-facts dt{font-size:11px;color:#64748b;font-weight:650;margin-bottom:3px}
            .hw24-scan-workbench .hw24-facts dd{margin:0}
            .hw24-scan-workbench .hw24-job-status{display:inline-block;border-radius:7px;padding:4px 8px;font-weight:700;background:#eef2f7;color:#475569;line-height:1.4}
            .hw24-scan-workbench .hw24-job-status[data-status="completed"]{background:#e9f6ee;color:#086138}
            .hw24-scan-workbench .hw24-job-status[data-status="leased"]{background:#e9f2ff;color:#1e4da0}
            .hw24-scan-workbench .hw24-job-status[data-status="blocked"],.hw24-scan-workbench .hw24-job-status[data-status="retry"]{background:#fff3d9;color:#88490c}
            @container (max-width:760px){.hw24-scan-workbench .hw24-position-main{grid-template-columns:24px minmax(0,1fr) minmax(0,1fr);gap:9px}.hw24-scan-workbench .hw24-position-actions{grid-column:2/-1}}
            @container (max-width:480px){.hw24-scan-workbench .hw24-position-main{grid-template-columns:24px minmax(0,1fr)}.hw24-scan-workbench .hw24-position-status,.hw24-scan-workbench .hw24-position-actions{grid-column:2/-1}.hw24-scan-workbench .hw24-detail{margin-left:0}}
            html.dark .hw24-scan-workbench .hw24-upload,html.dark .hw24-scan-workbench .hw24-facts{background:#223046;border-color:#34465c}
            html.dark .hw24-scan-workbench .hw24-position,html.dark .hw24-scan-workbench .hw24-position-list{border-color:#34465c}
            html.dark .hw24-scan-workbench .hw24-facts dt,html.dark .hw24-scan-workbench .hw24-detail summary{color:#cbd5e1}
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
                    <span>Ich bestätige ausdrücklich das Speichern in der bestehenden Scanliste. Keine Lager-/Bestandsbuchung.@if($this->autoQueueResearch()) Die serverseitige Recherche wird nach bestätigter Speicherung angemeldet.@endif</span>
                </label>
                @if($actionState !== [])
                    <p class="hw24-note {{ ($actionState['ok']??false) ? '' : 'hw24-note-warn' }}" role="status">
                        {{ $actionState['message'] }}
                        @if(($actionState['http']??null)!==null) · HTTP {{ $actionState['http'] }} @endif
                    </p>
                @endif
            </div>
        </section>

        <section class="hw24-card" x-data="{running:false}">
            <div class="hw24-card-head"><h3>03 · CSV-Datei direkt importieren (TEST)</h3></div>
            <div class="hw24-card-body">
                <p class="hw24-description">Vorbereiteten Neuware-Export als UTF-8-CSV auswählen. Excel-Dateien zuvor als CSV speichern. Die ursprüngliche Inventurliste mit B-Ware und Kommentaren muss zuerst klassifiziert werden. Keine JTL- oder WMS-Buchung. @if($this->autoQueueResearch()) Bestätigte Positionen werden zur serverseitigen Recherche angemeldet.@else Keine automatische externe Recherche.@endif</p>
                <p>Aktiver Stapel: {{ $selectedBatchId ?? 'Bitte oben einen TEST-Stapel auswählen' }}</p>
                <div class="hw24-upload" style="margin-top:12px" x-data="{uploading:false}" x-on:livewire-upload-start="uploading=true" x-on:livewire-upload-finish="uploading=false" x-on:livewire-upload-error="uploading=false" x-on:livewire-upload-cancel="uploading=false">
                    <label class="hw24-file-pick">
                        <span aria-hidden="true">＋</span> CSV-Datei auswählen
                        <input type="file" accept=".csv,text/csv" wire:model="csvFile" aria-label="CSV-Datei auswählen" x-bind:disabled="running || uploading" wire:key="hw24-csv-file-{{ $csvFile ? 'selected' : 'empty' }}">
                    </label>
                    <div class="hw24-file-info" aria-live="polite">
                        @if($csvFile)
                            <strong>{{ $csvFile->getClientOriginalName() }}</strong>
                            <div class="hw24-sub">Datei bereit zur Prüfung · {{ number_format($csvFile->getSize()/1024,1,',','.') }} KB</div>
                        @else
                            <strong>Noch keine Datei gewählt</strong>
                            <div class="hw24-sub">UTF-8 CSV · max. 1 MB · max. 1.000 Positionen</div>
                        @endif
                        <span x-show="uploading" x-cloak>Datei wird geladen …</span>
                    </div>
                    @if($csvFile)
                        <button type="button" class="hw24-btn" wire:click="clearCsvFile" x-bind:disabled="running || uploading" wire:loading.attr="disabled">Datei entfernen</button>
                    @endif
                    <button type="button" class="hw24-btn" wire:click="previewCsv" wire:loading.attr="disabled" wire:target="csvFile,previewCsv" x-bind:disabled="running || uploading || !@js($csvFile !== null && $selectedBatchId !== null)">CSV prüfen</button>
                </div>
                @error('csvFile')<p class="hw24-note hw24-note-warn">{{ $message }}</p>@enderror
                @if($csvState!==[])
                    <p class="hw24-note {{ ($csvState['ok']??false)?'':'hw24-note-warn' }}" role="status">{{ $csvState['message'] }}</p>
                @endif
                @if(($csvState['ok']??false) && $csvBatchId===$selectedBatchId)
                    <p class="hw24-note">{{ count($csvRows) }} Produktkennungen · {{ $csvState['duplicates'] }} identische CSV-Dubletten · {{ $csvOffset }} bearbeitet · {{ $csvState['inserted'] }} neu · {{ $csvState['existed'] }} vorhanden · {{ $csvState['restored'] }} wiederhergestellt</p>
                    <div class="hw24-table-wrap" style="margin-top:10px">
                        <table><thead><tr><th>EAN</th><th>Lagerplatz</th><th>Information / Inventurmenge</th></tr></thead><tbody>
                        @foreach(array_slice($csvRows,0,10) as $row)
                            <tr><td>{{ $row['ean'] }}</td><td>{{ $row['lagerplatz']??'' }}</td><td>{{ $row['information']??'' }}</td></tr>
                        @endforeach
                        </tbody></table>
                    </div>
                    <p class="hw24-description">Vorschau der ersten zehn Positionen. Mengen sind Importinformationen, keine Bestandsbuchungen. Speichern nur in ausdrücklich mit TEST bezeichneten Stapeln.</p>
                    <label class="hw24-action"><input type="checkbox" wire:model.live="confirmCsvImport" x-bind:disabled="running"> Ich bestätige den Import in den ausgewählten TEST-Stapel.@if($this->autoQueueResearch()) Gespeicherte Positionen auch zur Recherche anmelden.@endif</label>
                    <div class="hw24-action" style="margin-top:12px">
                        <button type="button" class="hw24-btn" x-bind:disabled="running || !@js($this->canWriteScan()) || !$wire.confirmCsvImport || $wire.csvOffset >= $wire.csvRows.length" x-on:click="if (!confirm('CSV in den gemeinsamen HW24-Datenspeicher importieren? Nur TEST-Stapel, keine JTL-Buchung.')) return; running=true; try { for(let i=0;i&lt;100 &amp;&amp; running;i++){ if(!(await $wire.importCsvChunk())) break; } } finally {running=false;}">CSV-Import starten / fortsetzen</button>
                        <button type="button" class="hw24-btn" x-bind:disabled="!running" x-on:click="running=false">Nach aktuellem Abschnitt pausieren</button>
                    </div>
                    @if(!$this->canWriteScan())<p class="hw24-note hw24-note-warn">Dateivorschau möglich. Die private Scan-Schreibfreigabe ist noch aus; Import nicht möglich.</p>@endif
                @endif
            </div>
        </section>

        @if($selectedBatchId !== null)
            <section class="hw24-card">
                <div class="hw24-card-head"><h3>04 · Gespeicherte Positionen aus Stapel {{ $selectedBatchId }}</h3></div>
                <div class="hw24-card-body">
                    <p class="hw24-description">Maximal 3.000 Positionen aus der vorhandenen MariaDB. Erfassungszeit in deutscher Ortszeit (MEZ/MESZ). CSV-Import und echte Scan-Ereignisse werden nur bei gespeichertem Nachweis angezeigt. Keine Lagerbuchung oder direkte Artikelanlage in JTL.</p>
                    <span class="hw24-stat">{{ count($items) }} Positionen geladen</span>
                    <div class="hw24-note" x-data="{running:false}">
                        <h3>JTL &amp; Amazon Sammelrecherche</h3>
                        <p>Ausgewählte Artikel nacheinander mit vorhandenen APIs prüfen. Katalog kann Cache oder SP-API verwenden. Keine kostenpflichtige KI automatisch.</p>
                        <div class="hw24-action" style="margin-top:10px">
                            <button type="button" class="hw24-btn" wire:click="selectAllResearch" x-bind:disabled="running">Alle Artikel markieren</button>
                            <button type="button" class="hw24-btn" wire:click="$set('selectedEans', [])" x-bind:disabled="running">Auswahl zurücksetzen</button>
                            <label><input type="checkbox" wire:model.live="confirmResearch" x-bind:disabled="running"> Recherche für Auswahl bestätigen</label>
                            @if($this->canQueueResearch())
                                <button type="button" class="hw24-btn" wire:click="enqueueSelectedResearch" wire:loading.attr="disabled" @disabled(!$confirmResearch)>Auswahl dauerhaft zur Recherche anmelden</button>
                                <button type="button" class="hw24-btn" wire:click="refreshPersistentResearch" wire:loading.attr="disabled">Recherche-Status aktualisieren</button>
                            @else
                            <button type="button" class="hw24-btn" x-bind:disabled="running || !$wire.confirmResearch" x-on:click="running=true; try { await $wire.prepareResearch(); for(let i=0;i&lt;3000 &amp;&amp; running;i++){ if(!(await $wire.researchNext()))break; await new Promise(r=>setTimeout(r,11000)); } } finally {running=false;}">Auswahl recherchieren</button>
                            <button type="button" class="hw24-btn" x-bind:disabled="!running" x-on:click="running=false">Pausieren</button>
                            @endif
                        </div>
                        @if($this->canQueueResearch())
                            <p role="status">{{ count($selectedEans) }} markiert · {{ count($persistentResearch) }} Positionen in der gemeinsamen Warteschlange · {{ $persistentResearchMessage }}</p>
                            <p>Aufträge bleiben beim Schließen des Browsers erhalten. Verarbeitung nur bei laufendem serverseitigem Worker. Keine automatische kostenpflichtige KI.</p>
                        @else
                            <p role="status">{{ count($selectedEans) }} markiert · {{ $researchOffset }} / {{ count($researchQueue) }} recherchiert · {{ $researchMessage }}</p>
                            <p>Rechercheübersicht gilt für diese Browser-Sitzung. Die Amazon-Katalogantwort selbst wird durch die bestehende API archiviert, soweit deren Speicherfunktion aktiv ist.</p>
                        @endif
                    </div>
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
                                · {{ $batchAiStatus['found']??0 }} zuvor analysiert
                                @if($batchAiStatus['partial']) · Die Marker-Liste ist auf 150 EANs begrenzt @endif
                            @endif
                        </p>
                    @endif
                    @if($items !== [])
                        <div class="hw24-position-list" style="margin-top:14px" role="list" aria-label="Gespeicherte Artikel">
                            @foreach($items as $i => $item)
                                @php($job=$persistentResearch[$item['ean']]??null)
                                @php($ai=$batchAiMarkers[$item['ean']]??null)
                                @php($evidence=$researchResults[$item['ean']]??null)
                                @php($coverage=\App\Services\ScanResearchPresentation::draftCoverage($item))
                                <article class="hw24-position" role="listitem" wire:key="hw24-scan-item-{{ $selectedBatchId }}-{{ $i }}">
                                    <div class="hw24-position-main">
                                        <div><input type="checkbox" wire:model.live="selectedEans" value="{{ $item['ean'] }}" aria-label="EAN {{ $item['ean'] }} markieren" @disabled($item['review']==='removed')></div>
                                        <div>
                                            <div class="hw24-position-title">{{ $item['name'] ?: ($item['draft']['Artikelname']??'Noch nicht identifiziert') }}</div>
                                            <div class="hw24-sub">{{ $item['name']?'Artikelname aus gespeichertem Scan-/JTL-Stand':'Artikelname aus Entwurf, sofern vorhanden' }}</div>
                                            @if(!empty($item['draft']['Artikelname']) && $item['name'] && $item['draft']['Artikelname']!==$item['name'])<div class="hw24-sub">Entwurfsname: {{ $item['draft']['Artikelname'] }}</div>@endif
                                            <div class="hw24-sub">EAN {{ $item['ean'] }} · {{ $item['article_number'] ?: 'Artikelnummer offen' }}</div>
                                            <div class="hw24-sub">{{ $item['scans'] }} Scan(s) · {{ $item['resolution_label'] }} · {{ $item['review_label'] }}</div>
                                        </div>
                                        <div class="hw24-position-status">
                                            @if($this->canQueueResearch())
                                                <span class="hw24-job-status" data-status="{{ $job['status']??'missing' }}">{{ match($job['status']??'missing'){'queued'=>'Wartend','leased'=>'In Bearbeitung','retry'=>'Wiederholung geplant','completed'=>'Quellenrecherche durchgeführt','blocked'=>'Prüfung erforderlich','missing'=>'Nicht angemeldet',default=>'Unbekannt'} }}</span>
                                                @if($job)
                                                    <div class="hw24-sub">Auftrag #{{ $job['id'] }} · {{ $job['attempt_count'] }} Versuch(e)</div>
                                                    @if($job['completed_at_utc']??null)<div class="hw24-sub">Abschluss: {{ \App\Services\ScanEntryPresentation::germanDate($job['completed_at_utc'])??'Zeit nicht verifiziert' }}</div>@endif
                                                @endif
                                            @elseif($evidence)
                                                <span class="hw24-job-status">{{ $evidence['amazon_ok']?count($evidence['items']).' Katalogtreffer':'Recherche fehlgeschlagen' }}</span>
                                            @else
                                                <span class="hw24-job-status">Noch nicht recherchiert</span>
                                            @endif
                                            @if(($ai['found']??false)===true)
                                                <div class="hw24-sub">KI-Archiv: {{ $ai['count'] }} Bericht(e)</div>
                                            @elseif($ai!==null)
                                                <div class="hw24-sub">Kein bestätigter KI-Archivtreffer</div>
                                            @else
                                                @if(($job['evidence']['ai_state']??null)==='read')
                                                    <div class="hw24-sub">KI-Archiv: {{ $job['evidence']['ai_count']??'?' }} Bericht(e) · {{ ($job['evidence']['ai_scope']??null)==='shared_workspace'?'Gemeinsames Archiv':'Bisheriges Konto' }}</div>
                                                @else
                                                    <div class="hw24-sub">KI-Archiv: noch nicht bestätigt</div>
                                                @endif
                                            @endif
                                            @if($item['resolution']==='existing')
                                                <div class="hw24-sub"><strong>Stammdatenprüfung separat erforderlich</strong> · aktueller JTL-Feldstand noch nicht bewertet</div>
                                            @elseif($item['resolution']==='new_candidate')
                                                <div class="hw24-sub">Entwurf: {{ $coverage['filled'] }}/{{ $coverage['total'] }} Felder belegt · {{ count($coverage['missing_required']) }} Basis-Pflichtfelder offen</div>
                                                <div class="hw24-sub">Nächster Schritt: Entwurf prüfen und ergänzen.</div>
                                            @else
                                                <div class="hw24-sub">Nächster Schritt: Produktidentität manuell klären.</div>
                                            @endif
                                        </div>
                                        <div class="hw24-position-actions">
                                            @if($item['review']!=='removed')
                                                <button type="button" class="hw24-btn" wire:click="openDraft('{{ $item['ean'] }}')">Artikel bearbeiten</button>
                                                <a class="hw24-link" href="{{ url('/admin/intelligence').'?ean='.rawurlencode($item['ean']) }}">Recherche-Details öffnen</a>
                                            @endif
                                        </div>
                                    </div>
                                    <details class="hw24-detail">
                                        <summary>Erfassung, Quellen und Archivdetails</summary>
                                        @if($job)<p class="hw24-sub">Quellenstand des gespeicherten Auftrags; keine neue Abfrage. Einzelne Abfragezeiten sind im Auftrag derzeit nicht vollständig hinterlegt.</p>@endif
                                        <dl class="hw24-facts">
                                            <div><dt>Erfasst am (MEZ/MESZ)</dt><dd>{{ $item['entry_at'] ?? 'Zeitpunkt nicht gespeichert' }}</dd></div>
                                            <div><dt>Letzter Scan</dt><dd>{{ $item['last_scan_at'] ?: 'Nicht belegt' }}</dd></div>
                                            <div><dt>Zuletzt geändert</dt><dd>{{ $item['updated_at'] ?: 'Nicht belegt' }}</dd></div>
                                            <div><dt>Erfassungsart</dt><dd>{{ $item['origin_label'] }} @if($item['import_label'])<div>{{ $item['import_label'] }}</div>@endif</dd></div>
                                            @if(($ai['found']??false)===true)<div><dt>Letzter KI-Bericht (UTC)</dt><dd>{{ $ai['at'] }}</dd></div>@endif
                                            @if($job)
                                                @foreach(\App\Services\ScanResearchPresentation::sources($job) as $source=>$result)
                                                    <div><dt>{{ $source }}</dt><dd>{{ $result }}</dd></div>
                                                @endforeach
                                                <div><dt>Amazon-Katalogarchiv</dt><dd>{{ isset($job['evidence']['catalog_snapshot_id'])?'#'.$job['evidence']['catalog_snapshot_id']:'Nicht bestätigt' }}</dd></div>
                                                <div><dt>Amazon-Preisarchiv</dt><dd>{{ isset($job['evidence']['price_snapshot_id'])?'#'.$job['evidence']['price_snapshot_id']:'Nicht bestätigt' }}</dd></div>
                                                @if($job['last_error_code'])<div><dt>Fehlercode</dt><dd>{{ $job['last_error_code'] }}</dd></div>@endif
                                            @endif
                                            @if($evidence)
                                                <div><dt>Manuelle Amazon-Recherche</dt><dd>{{ $evidence['amazon_ok']?count($evidence['items']).' Treffer':'Fehlgeschlagen' }} · {{ $evidence['source'] ?: 'unbekannt' }} · HTTP {{ $evidence['http'] ?? '—' }}</dd></div>
                                                @if($evidence['items']!==[])<div><dt>Produktvorschlag</dt><dd>{{ $evidence['items'][0]['title'] }}</dd></div>@endif
                                            @endif
                                        </dl>
                                    </details>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="hw24-empty">Dieser Stapel enthält aktuell keine anzeigbaren Positionen.</p>
                    @endif
                </div>
            </section>
        @endif

        @if($workingEan!==null)
            <section class="hw24-card">
                <div class="hw24-card-head"><h3>Artikel identifizieren und Entwurf bearbeiten · {{ $workingEan }}</h3></div>
                <div class="hw24-card-body">
                    <div class="hw24-action">
                        <button type="button" class="hw24-btn" wire:click="loadJtlFieldReview" wire:loading.attr="disabled" wire:target="loadJtlFieldReview">JTL-Stammdaten prüfen (nur lesen)</button>
                        <button type="button" class="hw24-btn" wire:click="researchOne('{{ $workingEan }}')" wire:loading.attr="disabled" wire:confirm="JTL und Amazon-Katalog für diese EAN abfragen?">JTL &amp; Amazon recherchieren</button>
                        <a class="hw24-link" target="_blank" rel="noopener noreferrer" href="{{ url('/admin/intelligence').'?ean='.rawurlencode($workingEan) }}">KI-Archiv und KI-Einzelprüfung öffnen</a>
                    </div>
                    @if($jtlFieldReview!==[])
                        <div class="hw24-note">
                            <h3>JTL-Feldprüfung · keine Änderung in JTL</h3>
                            @if($jtlFieldReview['ok']??false)
                                <p><strong>{{ $jtlFieldReview['name'] }}</strong></p>
                                <p>Spiegelstand: {{ $jtlFieldReview['snapshot_at']??'nicht belegt' }} · {{ $jtlFieldReview['filled'] }}/{{ $jtlFieldReview['total'] }} Werte in diesem Feldkatalog belegt.</p>
                                <p>Leere Spiegelwerte und fehlende Zuordnungen beweisen keine fehlenden Werte in JTL. Nicht jedes Feld ist für jeden Artikel erforderlich. Keine automatische Übernahme oder Freigabe.</p>
                                <dl class="hw24-facts">
                                    @foreach($jtlFieldReview['fields'] as $field=>$info)
                                        <div>
                                            <dt>{{ $field }}</dt>
                                            <dd>{{ match($info['state']){'value'=>$info['value'],'reference'=>$info['value'],'empty_snapshot'=>'Im Spiegel nicht belegt',default=>'Noch nicht zugeordnet'} }}</dd>
                                            <div class="hw24-sub">{{ $info['source'] }}</div>
                                        </div>
                                    @endforeach
                                </dl>
                            @else
                                <p>{{ $jtlFieldReview['message'] }}</p>
                            @endif
                        </div>
                    @endif
                    @if(isset($researchResults[$workingEan]))
                        @php($evidence=$researchResults[$workingEan])
                        <p class="hw24-note">JTL: {{ match($evidence['jtl']){'existing'=>'vorhanden','new_candidate'=>'Neuanlage-Kandidat','multiple'=>'mehrdeutig',default=>'nicht verifiziert'} }} · Amazon HTTP {{ $evidence['http']??'—' }} · {{ $evidence['source']?:'unbekannt' }} · Archiv {{ $evidence['snapshot_id']?:'offen' }}</p>
                        @foreach($evidence['items'] as $candidate)
                            <div class="hw24-note">
                                <strong>{{ $candidate['title'] ?: 'Titel fehlt' }}</strong> · ASIN {{ $candidate['asin'] }} · Hersteller {{ $candidate['manufacturer'] ?: 'offen' }} · Marke {{ $candidate['brand'] ?: 'offen' }}
                                <p>{{ $candidate['gtin_match']?'GTIN im Katalog belegt; Packung und Produkt dennoch prüfen.':'GTIN nicht im Katalog belegt; keine automatische Übernahme.' }}</p>
                                <p>Verpackung: {{ match($candidate['pack_kind']??'unknown'){'single'=>'Einzelpack belegt','multipack'=>($candidate['pack_units']??'?').' Stück pro Pack','conflict'=>'Widersprüchliche Packungsangaben',default=>'Packungsgröße nicht belegt'} }}</p>
                                @if($candidate['gtin_match'] && ($candidate['pack_kind']??'')!=='conflict')
                                    <button type="button" class="hw24-btn" wire:click="useCatalogSuggestion('{{ $candidate['asin'] }}')" wire:confirm="Diesen Produktvorschlag in noch leere Entwurffelder übernehmen?">Vorschlag übernehmen</button>
                                    <button type="button" class="hw24-btn" wire:click="priceCandidate('{{ $candidate['asin'] }}')" wire:loading.attr="disabled">Buy Box / Marktpreise prüfen</button>
                                @endif
                            </div>
                        @endforeach
                        @if($evidence['items']===[])<p>Kein nutzbarer Katalogtreffer. Produktname/Hersteller von der Verpackung erfassen und KI-Einzelrecherche nutzen.</p>@endif
                    @endif
                    @if($workingPrice!==[])
                        <p class="hw24-note">ASIN {{ $workingPrice['asin'] }} · {{ $workingPrice['ok']?'Amazon-Angebotsdaten':'Preisabfrage nicht erfolgreich' }} · Stand {{ $workingPrice['at']??'unbekannt' }} UTC
                            @foreach(['buy_box'=>'Buy Box / Marktpreisreferenz','lowest'=>'Niedrigstes Angebot'] as $key=>$label)
                                @if($workingPrice[$key]!==null) · {{ $label }}: {{ number_format($workingPrice[$key]['amount'],2,',','.') }} {{ $workingPrice[$key]['currency'] }} @endif
                            @endforeach
                            . Kein automatisch kalkulierter eigener VK; Einkauf, Gebühren, Versand und Steuer berücksichtigen.
                        </p>
                        @if(isset($workingPrice['archive']))
                            @include('filament.partials.amazon-price-history',['archive'=>$workingPrice['archive']])
                        @endif
                    @endif
                    <div class="hw24-note">
                        <h3>KI-Recherche zur ausgewählten EAN</h3>
                        <div class="hw24-action">
                            <button type="button" class="hw24-btn" wire:click="loadWorkingAi" wire:loading.attr="disabled">Vorhandenes KI-Archiv laden</button>
                            <label><input type="checkbox" wire:model.live="confirmSingleAi"> Kostenpflichtige Einzelprüfung bestätigen</label>
                            <button type="button" class="hw24-btn" wire:click="runWorkingAi" wire:loading.attr="disabled" @disabled(!$confirmSingleAi || !config('hw24_legacy.ai_paid_enabled'))>Fehlenden KI-Bericht erstellen</button>
                        </div>
                        @if($workingAi!==[])
                            <p>{{ $workingAi['scope'] }} · HTTP {{ $workingAi['http']??'—' }} · {{ !$workingAi['ok']?'Archiv nicht verfügbar':($workingAi['found']?'Bericht #'.$workingAi['id']:'Kein Bericht gefunden') }}</p>
                            @if($workingAi['found'])
                                <p>{{ $workingAi['title'] }} · {{ $workingAi['manufacturer'] }} · {{ $workingAi['group'] }}</p>
                                <p>{{ $workingAi['reason'] }} {{ $workingAi['notes'] }}</p>
                                <button type="button" class="hw24-btn" wire:click="useAiSuggestion" wire:confirm="KI-Vorschläge in leere Entwurffelder übernehmen?">KI-Vorschläge übernehmen</button>
                            @endif
                        @endif
                        <p>Eine neue Einzelprüfung benötigt ein bestätigtes gemeinsames Archiv, Betreiberfreigabe, Budget und Produktnamen. Vorhandene Berichte werden wiederverwendet.</p>
                    </div>
                    <p class="hw24-description">Scanentwurf und JTL-Spiegel sind getrennt. Bei vorhandenen JTL-Artikeln dient der Feldcheck oben zunächst der manuellen Bewertung; Änderungen an bestehenden Artikeln werden hier noch nicht gespeichert. Neue Artikel können nach Prüfung als Entwurf gespeichert werden.</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px">
                        @foreach(\App\Services\ScanResearch::FIELDS as $field)
                            <label style="font-size:12px">
                                {{ $field }}{{ in_array($field,\App\Services\ScanResearch::REQUIRED,true)?' *':'' }}
                                @if(in_array($field,['Kurzbeschreibung','Beschreibung','Anmerkung','Meta-Description (SEO)'],true))
                                    <textarea class="hw24-btn" style="display:block;width:100%;text-align:left" wire:model.blur="draftFields.{{ $field }}"></textarea>
                                @else
                                    <input class="hw24-btn" style="display:block;width:100%;text-align:left" type="text" wire:model="draftFields.{{ $field }}" @readonly($field==='GTIN')>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    <p class="hw24-note">Pflichtfelder: {{ $this->missingDraftFields()===[]?'Basisfelder vollständig (fachliche Prüfung weiterhin erforderlich).':'Noch offen: '.implode(', ',$this->missingDraftFields()) }}</p>
                    <label class="hw24-action" style="margin-top:12px"><input type="checkbox" wire:model.live="confirmDraft"> Produktidentität und eingetragene Werte geprüft; Entwurf speichern.</label>
                    <div class="hw24-action">
                        <button type="button" class="hw24-btn" wire:click="saveDraft(false)" @disabled(!$this->canWriteScan()||!$confirmDraft)>Entwurf speichern</button>
                        <button type="button" class="hw24-btn" wire:click="saveDraft(true)" @disabled(!$this->canWriteScan()||!$confirmDraft)>Prüfen und für Ameise freigeben</button>
                    </div>
                    @if($draftMessage!=='')<p class="hw24-note" role="status">{{ $draftMessage }}</p>@endif
                </div>
            </section>
        @endif

        <section class="hw24-card">
            <div class="hw24-card-head"><h3>05 · JTL-Ameise-Prüfexport direkt in Filament</h3></div>
            <div class="hw24-card-body">
                <p class="hw24-description">Die bestehende Scanlisten-API prüft vor dem CSV-Export den aktuellen JTL-Snapshot, freigegebene Neuanlagen, doppelte Artikelnummern und Pflichtfelder. Maximal 200 freigegebene Positionen. Nur ein Prüfexport, kein JTL-Direktimport.</p>
                <div class="hw24-action">
                    <details style="width:100%;margin:8px 0">
                        <summary>Exportspalten konfigurieren (standardmäßig alle aktiviert)</summary>
                        <button type="button" class="hw24-btn" wire:click="allExportFields">Alle Felder auswählen</button>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:8px;margin-top:8px">
                            @foreach(\App\Services\ScanResearch::FIELDS as $field)
                                <label><input type="checkbox" wire:model.live="selectedExportFields" value="{{ $field }}" @disabled($field==='Artikelnummer'||in_array($field,\App\Services\ScanResearch::REQUIRED,true))> {{ $field }}</label>
                            @endforeach
                        </div>
                        <p>Artikelnummer und Pflichtfelder bleiben im Export. Optionale leere Spalten werden ausgelassen. Einstellungen gelten in der aktuellen Sitzung; Stammdaten werden nicht gelöscht.</p>
                    </details>
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
            <div class="hw24-card-head"><h3>06 · Alle vorhandenen Scan-Funktionen weiter nutzen</h3></div>
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
