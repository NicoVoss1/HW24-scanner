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
            .hw24-scan-workbench .hw24-card-head{padding:15px 18px;background:#dceeff;border-bottom:1px solid #b5d6ef;color:#143d62}
            .hw24-scan-workbench .hw24-card-body{padding:18px}
            .hw24-scan-workbench .hw24-description{color:#586b82;font-size:12px;line-height:1.65;margin:0 0 14px}
            .hw24-scan-workbench .hw24-action{display:flex;flex-wrap:wrap;align-items:center;gap:11px}
            .hw24-scan-workbench .hw24-note{border-radius:10px;padding:12px 15px;border:1px solid #cbddeb;background:#f2f8fc;font-size:12px;line-height:1.65;color:#264968;margin-top:13px}
            .hw24-scan-workbench .hw24-note-warn{border-color:#efd2ab;background:#fff8e9;color:#88490c}
            .hw24-scan-workbench .hw24-table-wrap{overflow-x:auto;border:1px solid #dbe5ef;border-radius:10px}
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
            html.dark .hw24-scan-workbench .hw24-card-head,html.dark .hw24-scan-workbench th{background:#244969;border-color:#41698a;color:#e7f4ff}
            html.dark .hw24-scan-workbench td{border-color:#34465c}
            html.dark .hw24-scan-workbench .hw24-description,html.dark .hw24-scan-workbench .hw24-sub{color:#cbd5e1}

            .hw24-scan-workbench .hw24-table-wrap{max-width:100%;overflow-x:auto}
            .hw24-scan-workbench table{min-width:800px;table-layout:auto}
            .hw24-scan-workbench th,.hw24-scan-workbench td{white-space:normal;overflow-wrap:anywhere}
            .hw24-scan-workbench .hw24-btn:disabled{opacity:.48;cursor:not-allowed}
            .hw24-scan-workbench .hw24-upload{display:flex;align-items:center;flex-wrap:wrap;gap:14px;border:1px dashed #9ebbd4;border-radius:12px;padding:16px;background:#f5f9fd}
            .hw24-scan-workbench .hw24-file-pick{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;background:#164b72;color:white;border-radius:9px;padding:12px 16px;font-weight:750;font-size:13px;min-height:44px}
            .hw24-scan-workbench .hw24-file-pick input{position:absolute;inset:0;opacity:0;width:100%;height:100%;cursor:pointer}
            .hw24-scan-workbench .hw24-file-pick:focus-within{outline:3px solid #60a5fa;outline-offset:2px}
            .hw24-scan-workbench .hw24-file-pick:has(input:disabled){opacity:.5;cursor:not-allowed}
            .hw24-scan-workbench .hw24-file-info{flex:1 1 180px;min-width:0;overflow-wrap:anywhere;font-size:13px;line-height:1.55}
            .hw24-scan-workbench .hw24-position-list{container-type:inline-size;border:1px solid #dbe5ef;border-radius:11px;max-height:none;overflow:visible}
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
                <h2>HW24 Business Hub · Scanliste und Sammelimport</h2>
                <p class="hw24-lead">Deine bestehenden Stapel und Positionen bleiben im HW24-Legacy-System erhalten. Du kannst sie hier prüfen und den vollständigen Scan-, CSV- und Ameise-Workflow weiterhin verwenden.</p>
            </div>
            <a class="hw24-open" href="https://artikelmanager.hairworld24.de/scanliste.html"
               target="_blank" rel="noopener noreferrer">Scanliste vollständig öffnen</a>
        </header>

        @include('filament.partials.hub-release-status')
        <p class="hw24-note"><strong>EAN erfassen · automatisch recherchieren · Vorschläge prüfen · Ameise exportieren</strong></p>
        @php($activeBatch=collect($batches)->firstWhere('id',$selectedBatchId))
        <div class="hw24-note" style="border:2px solid #78b9e8;background:#dceeff;color:#143d62;padding:16px" role="status">
            <div style="font-size:12px;font-weight:700">AKTIVER SCAN-STAPEL</div>
            <strong style="font-size:19px">{{ $activeBatch['label']??'Noch kein Stapel ausgewählt' }}</strong>
            @if($activeBatch)<div>Stapel #{{ $activeBatch['id'] }} · {{ \App\Services\HubScanSummary::fromRows($items)['positions'] }} Positionen · {{ \App\Services\HubScanSummary::fromRows($items)['quantity'] }} Stück erfasst</div>@endif
        </div>
        <section class="hw24-card">
            <div class="hw24-card-head"><h3>01 · Vorhandene Scan-Stapel anzeigen</h3></div>
            <div class="hw24-card-body">
                <p class="hw24-description">Die Abfrage ist ausdrücklich nur lesend. Die API liefert höchstens 50 offene Stapel des vorhandenen, durch Basic-Authentifizierung zugeordneten Benutzers. Es werden keine neuen Stapel erstellt.</p>
                <div class="hw24-action">
                    <x-filament::button title="Offene gespeicherte Scan-Stapel mit Positions-, Mengen- und Bestandskennzahlen laden." type="button" wire:click="loadBatches" wire:loading.attr="disabled" wire:target="loadBatches">Gespeicherte Stapel laden</x-filament::button>
                    <button type="button" class="hw24-btn" wire:click="refreshBatchOverview" wire:loading.attr="disabled" title="Kennzahlen aller angezeigten Stapel und aktuelle Wawi-Bestände neu lesen, ohne Positionen zu öffnen.">Kennzahlen aktualisieren</button>
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
                            <thead><tr><th>Stapel</th><th>Positionen / Stück</th><th>Wawi Gesamt / verfügbar</th><th>Letzte Änderung UTC</th><th>Aktion</th></tr></thead>
                            <tbody>
                                @foreach($batches as $batch)
                                    <tr wire:key="hw24-scanbatch-{{ $batch['id'] }}" @if($selectedBatchId===$batch['id']) style="background:#dceeff;color:#143d62" aria-current="true" @endif>
                                        <td><strong>{{ $batch['label'] }}</strong> @if($selectedBatchId===$batch['id'])<span class="hw24-stat">Aktiv</span>@endif<div class="hw24-sub">Stapel-ID {{ $batch['id'] }}</div></td>
                                        @php($overview=$batchOverview[$batch['id']]??[])
                                        <td><strong>{{ $overview['positions']??'Nicht belegt' }}</strong> Positionen<br><strong>{{ $overview['quantity']??'Nicht belegt' }}</strong> Stück erfasst</td>
                                        <td><strong>{{ $overview['stock']['total']??'Nicht belegt' }}</strong> gesamt<br><strong>{{ $overview['stock']['available']??'Nicht belegt' }}</strong> verfügbar
                                            @if(isset($overview['stock']['at']))<div class="hw24-sub">{{ $overview['stock']['existing'] }} JTL-Artikel · {{ $overview['stock']['absent'] }} nicht vorhanden · {{ $overview['stock']['unknown'] }} unklar<br>Stand: {{ $overview['stock']['at'] }}</div>@endif
                                        </td>
                                        <td>{{ $batch['updated'] ?: 'Nicht belegt' }}</td>
                                        <td><button title="Diesen Stapel mit seinen einzelnen Artikeln zur Bearbeitung öffnen." type="button" class="hw24-btn" wire:click="loadBatch({{ $batch['id'] }})" wire:loading.attr="disabled" wire:target="loadBatch">Positionen anzeigen</button>
                                            @if($recycleBinAvailable && $this->canWriteScan())<button type="button" class="hw24-btn" title="Stapel und Positionen nur ausblenden; Recherchearchive bleiben erhalten." wire:click="recycleBatch({{ $batch['id'] }},'remove')" wire:confirm="Ganzen Stapel in den Papierkorb verschieben? Keine Recherche wird gelöscht." wire:loading.attr="disabled">Stapel löschen</button>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="hw24-sub">Wawi-Summen je Stapel über eindeutig zugeordnete Artikel, jeden Wawi-Artikel nur einmal gezählt (auch bei mehrfachen Scans); keine Buchung. Unklare oder fehlende Bestandswerte ergeben keine scheinbar vollständige Summe.</p>
                @elseif(($diagnostic['ok'] ?? false) === true)
                    <div class="hw24-empty">Für das verwendete Basic-Auth-Konto wurden keine offenen Scan-Stapel zurückgegeben. Geschlossene Stapel sind in dieser API-Liste nicht enthalten.</div>
                @endif
            </div>
        </section>

        @if($recycleBinAvailable)
            <section class="hw24-card">
                <div class="hw24-card-head"><h3>Papierkorb · Scan-Stapel</h3></div>
                <div class="hw24-card-body">
                    <button type="button" class="hw24-btn" wire:click="toggleDeletedBatches" wire:loading.attr="disabled">{{ $showDeletedBatches?'Gelöschte Stapel ausblenden':'Gelöschte Stapel anzeigen' }}</button>
                    @if($showDeletedBatches)
                        @forelse($deletedBatches as $batch)
                            <div class="hw24-action" wire:key="deleted-batch-{{ $batch['id'] }}">
                                <strong>{{ $batch['label'] }}</strong><span>Gelöscht: {{ $batch['deleted_at'] }} UTC</span>
                                @if($this->canWriteScan())<button type="button" class="hw24-btn" wire:click="recycleBatch({{ $batch['id'] }},'restore')" wire:confirm="Diesen Stapel wiederherstellen?" wire:loading.attr="disabled">Wiederherstellen</button>@endif
                            </div>
                        @empty
                            <p>Keine gelöschten Stapel zurückgegeben.</p>
                        @endforelse
                    @endif
                </div>
            </section>
        @endif
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
                    <button title="Einen neuen Scan-Stapel in der vorhandenen Datenbank speichern." type="button" class="hw24-btn" wire:click="createBatch"
                            wire:confirm="Neuen Scan-Stapel in der vorhandenen HW24-MariaDB anlegen?"
                            wire:loading.attr="disabled" wire:target="createBatch"
                            @disabled(!$this->canWriteScan() || !$confirmScanWrite)>Neuen Stapel speichern</button>
                </div>
                <label style="font-size:12px;display:flex;gap:8px;align-items:center;margin:8px 0">
                    <input type="checkbox" wire:model.live="confirmScanWrite">
                    <span>Nur für die Neuanlage eines Stapels: Speichern bestätigen.</span>
                </label>
                <div class="hw24-note" style="background:#dceeff;border-color:#78b9e8;color:#143d62">
                    <strong>Fortlaufende Barcode-Erfassung · {{ $activeBatch['label']??'Kein Stapel ausgewählt' }}</strong>
                    <p>Scanner mit Enter-Suffix verwenden. Jeder Scan wird im aktiven Stapel erfasst. Kein Lagerbestand wird gebucht. @if($this->autoQueueResearch()) Recherche wird nach bestätigter Speicherung automatisch angemeldet.@else Automatische Recherche für diesen Stapel nicht bestätigt.@endif</p>
                </div>
                <div wire:ignore x-data="{
                    pending:[], running:false, paused:false, saved:0, message:'',
                    add(){
                        const batch=$wire.selectedBatchId;
                        const ean=this.$refs.barcode.value.trim();
                        if(!batch){this.message='Zuerst einen Scan-Stapel auswählen.';return;}
                        if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(ean)||/^0+$/.test(ean)){this.message='Barcodeformat prüfen.';return;}
                        let sum=0,w=3;for(let i=ean.length-2;i>=0;i--){sum+=Number(ean[i])*w;w=w===3?1:3;}
                        if((10-sum%10)%10!==Number(ean[ean.length-1])){this.message='EAN-Prüfziffer falsch. Barcode bitte korrigieren.';return;}
                        if(this.pending.length>=250){this.message='Erfassungspuffer voll. Bitte Verarbeitung fortsetzen.';return;}
                        if(this.pending.length &amp;&amp; this.pending[0].batch!==batch){this.message='Offene Scans gehören zum vorherigen Stapel. Diesen zuerst auswählen.';return;}
                        if(!window.crypto?.randomUUID){this.message='Sicherer Scanner-Kontext nicht verfügbar.';return;}
                        this.pending.push({batch,ean,key:window.crypto.randomUUID()});
                        this.$refs.barcode.value='';
                        if(!this.paused)this.drain();
                        this.$refs.barcode.focus();
                    },
                    async drain(){
                        if(this.running||this.paused||!this.pending.length)return;
                        this.running=true;
                        const batch=this.pending[0].batch;
                        try{
                            while(this.pending.length){
                                const p=this.pending[0];
                                if($wire.selectedBatchId!==p.batch){this.paused=true;this.message='Stapel gewechselt. Ursprünglichen Stapel auswählen und fortsetzen.';break;}
                                const result=await $wire.captureBarcode(p.batch,p.ean,p.key);
                                if(!result?.ok){this.paused=true;this.message=result?.message??'Speicherung nicht bestätigt.';break;}
                                this.pending.shift();this.saved++;this.message=result.message;
                            }
                            if(!this.pending.length &amp;&amp; !this.paused)await $wire.refreshCapturedBatch(batch);
                        }catch(e){this.paused=true;this.message='Verbindung unterbrochen. Offene Scans bleiben in diesem Browserfenster; nicht erneut scannen.';}
                        finally{this.running=false;if(this.pending.length &amp;&amp; !this.paused)this.drain();}
                    },
                    resume(){this.paused=false;this.drain();this.$refs.barcode.focus();}
                }" x-on:beforeunload.window="if(pending.length){$event.preventDefault();$event.returnValue='';}">
                    <form class="hw24-action" style="margin:10px 0" x-on:submit.prevent="add()">
                        <label for="hw24-scan-ean" style="font-size:12px;font-weight:750;flex:1 1 230px">
                            EAN / GTIN scannen
                            <input class="hw24-btn" style="display:block;width:100%;text-align:left;margin-top:5px;font-size:17px" id="hw24-scan-ean" type="text" maxlength="14" inputmode="numeric" autocomplete="off" x-ref="barcode"
                                x-bind:disabled="!$wire.selectedBatchId" @disabled(!$this->canWriteScan()) placeholder="Barcode scannen oder eintippen und Enter">
                        </label>
                        <button type="submit" class="hw24-btn" x-bind:disabled="!$wire.selectedBatchId" @disabled(!$this->canWriteScan())>EAN erfassen</button>
                    </form>
                    <div class="hw24-sub" role="status" aria-live="polite">
                        <strong x-text="saved+' bestätigt · '+pending.length+' im Puffer'"></strong>
                        <span x-text="message"></span>
                        <span x-show="running"> · Speicherung läuft</span>
                    </div>
                    <button x-show="paused" type="button" class="hw24-btn" x-on:click="resume()" x-bind:disabled="running">Offene Scans mit derselben Ereignis-ID fortsetzen</button>
                    <p class="hw24-sub">Erfassungspuffer bleibt nur in diesem Browserfenster. Bei Störung nicht erneut scannen und Fenster nicht schließen, bis alle Scans bestätigt sind.</p>
                </div>
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
                <p><strong>Aktiver Stapel: {{ $activeBatch['label']??'Bitte oben einen TEST-Stapel auswählen' }} @if($activeBatch) · #{{ $activeBatch['id'] }} @endif</strong></p>
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
                        <button title="Ausgewählte Datei und Importvorschau entfernen; gespeicherte Artikel bleiben erhalten." type="button" class="hw24-btn" wire:click="clearCsvFile" x-bind:disabled="running || uploading" wire:loading.attr="disabled">Datei entfernen</button>
                    @endif
                    <button title="CSV-Struktur, EANs und Importdaten vor dem Speichern prüfen." type="button" class="hw24-btn" wire:click="previewCsv" wire:loading.attr="disabled" wire:target="csvFile,previewCsv" x-bind:disabled="running || uploading || !@js($csvFile !== null && $selectedBatchId !== null)">CSV prüfen</button>
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
                        <button title="Bestätigte CSV in Abschnitten importieren; keine Wawi-Buchung." type="button" class="hw24-btn" x-bind:disabled="running || !@js($this->canWriteScan()) || !$wire.confirmCsvImport || $wire.csvOffset >= $wire.csvRows.length" x-on:click="if (!confirm('CSV in den gemeinsamen HW24-Datenspeicher importieren? Nur TEST-Stapel, keine JTL-Buchung.')) return; running=true; try { for(let i=0;i&lt;100 &amp;&amp; running;i++){ if(!(await $wire.importCsvChunk())) break; } } finally {running=false;}">CSV-Import starten / fortsetzen</button>
                        <button title="Nach dem laufenden Abschnitt pausieren; gespeicherte Ergebnisse bleiben erhalten." type="button" class="hw24-btn" x-bind:disabled="!running" x-on:click="running=false">Nach aktuellem Abschnitt pausieren</button>
                    </div>
                    @if(!$this->canWriteScan())<p class="hw24-note hw24-note-warn">Dateivorschau möglich. Die private Scan-Schreibfreigabe ist noch aus; Import nicht möglich.</p>@endif
                @endif
            </div>
        </section>

        @if($selectedBatchId !== null)
            <section class="hw24-card">
                <div class="hw24-card-head"><h3>04 · Gespeicherte Positionen · {{ $activeBatch['label']??'Stapel' }} (#{{ $selectedBatchId }})</h3></div>
                <div class="hw24-card-body">
                    <p class="hw24-description">Maximal 3.000 Positionen aus der vorhandenen MariaDB. Erfassungszeit in deutscher Ortszeit (MEZ/MESZ). CSV-Import und echte Scan-Ereignisse werden nur bei gespeichertem Nachweis angezeigt. Keine Lagerbuchung oder direkte Artikelanlage in JTL.</p>
                    @php($scanSummary=\App\Services\HubScanSummary::fromRows($items))
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,145px),1fr));gap:8px;margin:10px 0" aria-label="Stapelübersicht">
                        @foreach(['Artikelpositionen'=>$scanSummary['positions'],'Erfasste Stückzahl'=>$scanSummary['quantity'],'Scanvorgänge (aktiv)'=>$scanSummary['events_complete']?$scanSummary['scan_events']:'Nicht vollständig belegt','In JTL vorhanden'=>$scanSummary['existing'],'Neuanlagen'=>$scanSummary['new'],'Zuordnung offen'=>$scanSummary['unresolved']] as $label=>$value)
                            <div style="border:1px solid #dbe5ef;border-radius:9px;padding:10px;min-width:0">
                                <div class="hw24-sub">{{ $label }}</div><strong style="font-size:20px">{{ $value }}</strong>
                            </div>
                        @endforeach
                    </div>
                    <p class="hw24-sub">Erfasste Stückzahl = gespeicherte Menge der aktiven Positionen (inkl. CSV-Initialmenge und manueller Korrekturen), kein gebuchter JTL-/WMS-Bestand. Scanvorgänge zählen gespeicherte Scannerereignisse. Entfernte Positionen: {{ $scanSummary['removed'] }}.</p>
                    <div class="hw24-action">
                        <button title="Aktuelle Wawi-Bestände für den geöffneten Stapel neu lesen." type="button" class="hw24-btn" wire:click="refreshBatchStock" wire:loading.attr="disabled" wire:target="refreshBatchStock">Wawi-Bestände aktualisieren</button>
                        <span class="hw24-sub">{{ ($batchStock['ok']??false)?'JTL-Spiegelstand: '.$batchStock['at']:'Aktueller Wawi-Bestand nicht bestätigt' }}</span>
                    </div>
                    @if($scanSummary['possibly_limited'])<p class="hw24-note hw24-note-warn">Anzeigelimit 3.000 Positionen erreicht; Kennzahlen beziehen sich nur auf die geladenen Zeilen.</p>@endif
                    @if($this->canQueueResearch())
                        <div class="hw24-note">
                            <h3>Artikel gemeinsam aufbereiten</h3>
                            <p>Gespeicherte Rechercheergebnisse wiederverwenden. Ohne Markierung alle offenen Neuanlagen (max. 200). Keine zweite Amazon-Abfrage, keine bezahlte KI.</p>
                            <div class="hw24-action">
                                <button title="Gespeicherte Recherche, KI-Berichte und Vorlagen zu Entwurfsvorschlägen zusammenführen." type="button" class="hw24-btn" wire:click="prepareBatchDrafts" wire:loading.attr="disabled">Artikelentwürfe aufbereiten</button>
                                <button title="Gespeicherten Warteschlangenstatus lesen; keine neue Amazon-Abfrage." type="button" class="hw24-btn" wire:click="refreshPersistentResearch" wire:loading.attr="disabled">Recherche-Status aktualisieren</button>
                            </div>
                            @if($batchPreparationMessage!=='')<p role="status">{{ $batchPreparationMessage }}</p>@endif
                            @if($batchPreparation!==[])
                                <div style="overflow-x:auto">
                                    @foreach($batchPreparation as $ean=>$proposal)
                                        <details style="padding:8px;border-bottom:1px solid #dbe5ef">
                                            <summary>{{ $ean }} · {{ $proposal['draft']['Artikelname']??'' }} · {{ count($proposal['fields']) }} Vorschläge · {{ $proposal['reason'] }}</summary>
                                            @if($proposal['missing']!==[])<p>Basisfelder offen: {{ implode(', ',$proposal['missing']) }}</p>@endif
                                            @if(isset($proposal['ai']))
                                                <p>KI-Archiv: {{ match($proposal['ai']['state']){'verified_review'=>'Geprüfter Bericht','missing'=>'Noch kein gemeinsamer Bericht','stale'=>'Abweichende Recherchegrundlage','identity_conflict'=>'Identitätskonflikt','review'=>'Manuelle Prüfung','not_checked'=>'In diesem Durchgang nicht geprüft',default=>'Nicht bestätigt'} }} @if(isset($proposal['ai']['id'])) · #{{ $proposal['ai']['id'] }} @endif</p>
                                                @if(!empty($proposal['ai']['group_hint']))<p>KI-Warengruppenvorschlag: {{ $proposal['ai']['group_hint'] }} (Übernahme nur aus passender JTL-Vorlage)</p>@endif
                                                @if(($proposal['ai']['content']['state']??null)==='review')
                                                    <details><summary>KI-Texte und belegte Artikelattribute · Prüfvorschläge</summary>
                                                        @foreach($proposal['ai']['content']['fields']??[] as $label=>$value)<p><strong>{{ $label }}</strong>: {{ $value }}</p>@endforeach
                                                        @foreach($proposal['ai']['content']['attributes']??[] as $channel=>$attributes)
                                                            <p><strong>{{ match($channel){'article'=>'Artikelmerkmale','shopify'=>'Shopify','ebay'=>'eBay',default=>'Attribute'} }}</strong></p>
                                                            @foreach($attributes as $label=>$value)<p>{{ $label }}: {{ $value }}</p>@endforeach
                                                        @endforeach
                                                        <p>Keine Veröffentlichung oder direkte Änderung eines Verkaufskanals.</p>
                                                    </details>
                                                @endif
                                                @if(!empty($proposal['ai']['title_hint']))<p>KI-Titelvorschlag: {{ $proposal['ai']['title_hint'] }} (Prüfvorschlag)</p>@endif
                                            @endif
                                            @foreach($proposal['references']??[] as $source=>$reference)
                                                <p>{{ $source==='ameise'?'Ameise-Referenz':'Historische Excel-Liste' }}: {{ match($reference['state']){'found'=>'Treffer','missing'=>'Kein Treffer','ambiguous'=>'Mehrdeutig',default=>'Archiv nicht verfügbar'} }} · {{ $reference['count'] }} Zeilen @if($reference['archive']) · Archiv #{{ $reference['archive'] }} @endif @if(!empty($reference['populated'])) · bis zu {{ $reference['populated'] }} befüllte Originalfelder @endif @if(\App\Filament\Pages\QueryAuditWorkbench::canAccess()) · <a class="hw24-link" href="{{ $source==='ameise'?\App\Filament\Pages\AmeiseReferenceWorkbench::getUrl(['ean'=>(string)$ean]):\App\Filament\Pages\ReferenceTableWorkbench::getUrl(['ean'=>(string)$ean]) }}">Originalfelder durchsuchen</a>@endif</p>
                                            @endforeach
                                            @if(!empty($proposal['reference_hints']['fields']))
                                                <details><summary>Historische Produktfakten · Zeile {{ $proposal['reference_hints']['row'] }} · Prüfvorschläge</summary>
                                                @foreach($proposal['reference_hints']['fields'] as $field=>$value)<p><strong>{{ $field }}:</strong> {{ $value }}</p>@endforeach
                                                <p>Archivwerte sind nicht automatisch aktuelle Artikelwerte. @if($proposal['reference_hints']['requires_review']) Bearbeitungshinweise im Original prüfen. @endif</p></details>
                                            @endif
                                            @if(!empty($proposal['coverage']))
                                                <details><summary>Alle {{ count($proposal['coverage']) }} Ameise-Entwurfsfelder · {{ count(array_filter($proposal['coverage'],fn($c)=>in_array($c['state'],['existing','proposed'],true))) }} belegt / vorgeschlagen</summary>
                                                <div class="hw24-table-wrap"><table><thead><tr><th>Feld</th><th>Status</th><th>Quelle / offene Voraussetzung</th></tr></thead><tbody>
                                                @foreach($proposal['coverage'] as $name=>$coverage)
                                                    <tr><td>{{ $name }} @if($coverage['required']) · Pflicht @endif</td><td>{{ match($coverage['state']){'existing'=>'Vorhanden','proposed'=>'Vorschlag','reserved'=>'JTL-Nummernkreis',default=>'Offen'} }}</td><td>{{ $coverage['source'] }}</td></tr>
                                                @endforeach
                                                </tbody></table></div></details>
                                            @endif
                                            @if(isset($proposal['template_reason']))<p>JTL-Vorlagen: {{ $proposal['template_count']??0 }} · {{ $proposal['template_reason'] }}</p>@endif
                                            @if(isset($proposal['market_price']))
                                                <p>Amazon-Marktpreisarchiv #{{ $proposal['market_price']['id'] }} · {{ $proposal['market_price']['at'] }} UTC · nicht eigener VK</p>
                                                @foreach(['buybox'=>'Buy Box','lowest'=>'Niedrigste Angebote'] as $key=>$label)
                                                    @foreach($proposal['market_price'][$key] as $price)
                                                        @if(isset($price['landed_price']['amount'],$price['landed_price']['currency']))<p>{{ $label }}: {{ $price['landed_price']['amount'] }} {{ $price['landed_price']['currency'] }}</p>@endif
                                                    @endforeach
                                                @endforeach
                                            @endif
                                            @foreach($proposal['fields'] as $field=>$value)
                                                <p><strong>{{ $field }}:</strong> {{ $value }} <small>({{ $proposal['sources'][$field] }})</small></p>
                                            @endforeach
                                        </details>
                                    @endforeach
                                </div>
                                <label><input type="checkbox" wire:model.live="confirmBatchPreparation"> Vorschläge geprüft; nur leere Felder ergänzen.</label>
                                <button title="Bis zu zehn bestätigte Entwürfe speichern; kein JTL-Import." type="button" class="hw24-btn" wire:click="savePreparedBatch" @disabled(!$confirmBatchPreparation) wire:loading.attr="disabled">Bis zu 10 Entwürfe speichern</button>
                            @endif
                        </div>
                    @endif
                    <div class="hw24-note" x-data="{running:false}">
                        <h3>JTL &amp; Amazon Sammelrecherche</h3>
                        <p>Ausgewählte Artikel nacheinander mit vorhandenen APIs prüfen. Katalog kann Cache oder SP-API verwenden. Keine kostenpflichtige KI automatisch.</p>
                        <div class="hw24-action" style="margin-top:10px">
                            <button title="Alle aktiven gültigen Artikelpositionen markieren." type="button" class="hw24-btn" wire:click="selectAllResearch" x-bind:disabled="running">Alle Artikel markieren</button>
                            <button title="Aktuelle Artikelauswahl aufheben." type="button" class="hw24-btn" wire:click="$set('selectedEans', [])" x-bind:disabled="running">Auswahl zurücksetzen</button>
                            <label><input type="checkbox" wire:model.live="confirmResearch" x-bind:disabled="running"> Recherche für Auswahl bestätigen</label>
                            @if($this->canQueueResearch())
                                <button title="Markierte Artikel dedupliziert zur gemeinsamen Hintergrundrecherche anmelden." type="button" class="hw24-btn" wire:click="enqueueSelectedResearch" wire:loading.attr="disabled" @disabled(!$confirmResearch)>Auswahl dauerhaft zur Recherche anmelden</button>
                                <button title="Gespeicherten Warteschlangenstatus lesen; keine neue Amazon-Abfrage." type="button" class="hw24-btn" wire:click="refreshPersistentResearch" wire:loading.attr="disabled">Recherche-Status aktualisieren</button>
                            @else
                            <button title="Markierte Artikel nacheinander recherchieren; mögliche Amazon-Aufrufe." type="button" class="hw24-btn" x-bind:disabled="running || !$wire.confirmResearch" x-on:click="running=true; try { await $wire.prepareResearch(); for(let i=0;i&lt;3000 &amp;&amp; running;i++){ if(!(await $wire.researchNext()))break; await new Promise(r=>setTimeout(r,11000)); } } finally {running=false;}">Auswahl recherchieren</button>
                            <button title="Nach dem laufenden Abschnitt pausieren; gespeicherte Ergebnisse bleiben erhalten." type="button" class="hw24-btn" x-bind:disabled="!running" x-on:click="running=false">Pausieren</button>
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
                        <button title="Vorhandene KI-Archivkennzeichen lesen; keine KI-Kosten." type="button" class="hw24-btn" wire:click="loadBatchAiMarkers" wire:loading.attr="disabled" wire:target="loadBatchAiMarkers">
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
                    <label class="hw24-sub"><input type="checkbox" wire:model.live="showRemovedPositions"> Gelöschte Positionen anzeigen</label>
                    @if($items !== [])
                        <div class="hw24-position-list" style="margin-top:14px" role="list" aria-label="Gespeicherte Artikel">
                            @foreach($items as $i => $item)
                                @if($item['review']==='removed' && !$showRemovedPositions) @continue @endif
                                @php($job=$persistentResearch[$item['ean']]??null)
                                @php($ai=$batchAiMarkers[$item['ean']]??null)
                                @php($evidence=$researchResults[$item['ean']]??null)
                                @php($coverage=\App\Services\ScanResearchPresentation::draftCoverage($item))
                                <article class="hw24-position" role="listitem" wire:key="hw24-scan-item-{{ $selectedBatchId }}-{{ $i }}">
                                    <div class="hw24-position-main">
                                        <div><input type="checkbox" wire:model.live="selectedEans" value="{{ $item['ean'] }}" aria-label="EAN {{ $item['ean'] }} markieren" @disabled($item['review']==='removed')></div>
                                        <div>
                                            <div class="hw24-position-title">{{ $item['name'] ?: (($item['draft']['Artikelname']??'') ?: 'Noch nicht identifiziert') }}</div>
                                            <div class="hw24-sub">{{ $item['name']?'Artikelname aus gespeichertem Scan-/JTL-Stand':'Artikelname aus Entwurf, sofern vorhanden' }}</div>
                                            @if(!empty($item['draft']['Artikelname']) && $item['name'] && $item['draft']['Artikelname']!==$item['name'])<div class="hw24-sub">Entwurfsname: {{ $item['draft']['Artikelname'] }}</div>@endif
                                            <div class="hw24-sub">EAN {{ $item['ean'] }} · {{ $item['article_number'] ?: 'Artikelnummer offen' }}</div>
                                            <div class="hw24-sub"><strong>{{ $item['scans'] }} Stück erfasst</strong> · {{ $item['resolution_label'] }} · {{ $item['review_label'] }}</div>
                                            @if($item['review']!=='removed')
                                                @php($stock=$batchStock['items'][$item['ean']]??[])
                                                @if(($stock['state']??null)==='existing')
                                                    <div style="margin-top:5px"><strong>In Wawi vorhanden</strong></div>
                                                    <div class="hw24-sub">Wawi-Bestand: <strong>{{ $stock['total']??'Nicht belegt' }}</strong> · Verfügbar: <strong>{{ $stock['available']??'Nicht belegt' }}</strong></div>
                                                    <div class="hw24-sub">In Aufträgen: {{ $stock['orders']??'Nicht belegt' }} · Im Zulauf: {{ $stock['incoming']??'Nicht belegt' }}</div>
                                                    @if($item['resolution']!=='existing')<div class="hw24-sub">Abweichung vom gespeicherten Scanstatus – vor Freigabe prüfen.</div>@endif
                                                @elseif(($stock['state']??null)==='absent')
                                                    <div><strong>Im aktuellen Wawi-Spiegel nicht vorhanden</strong></div>
                                                    @if($item['resolution']==='existing')<div class="hw24-sub">Abweichung vom gespeicherten Scanstatus – bitte prüfen.</div>@endif
                                                @elseif(($stock['state']??null)==='multiple')
                                                    <div><strong>Mehrere Wawi-Treffer – Bestand nicht eindeutig</strong></div>
                                                @else
                                                    <div class="hw24-sub">Aktueller Wawi-Bestand nicht bestätigt</div>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="hw24-position-status">
                                            @php($progress=\App\Services\HubScanProgress::present($item,$job??[],$batchStock['items'][$item['ean']]??[]))
                                            <dl class="hw24-facts" style="margin:5px 0">
                                                @foreach($progress as $stage=>$state)
                                                    <div><dt>{{ $stage }}</dt><dd>{{ $state }}</dd></div>
                                                @endforeach
                                            </dl>
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
                                                <div class="hw24-sub">Kein bestätigter Archivtreffer im KI-Archiv</div>
                                            @else
                                                @if(($job['evidence']['ai_state']??null)==='read')
                                                    <div class="hw24-sub">KI-Archiv: {{ $job['evidence']['ai_count']??'?' }} Bericht(e) · {{ ($job['evidence']['ai_scope']??null)==='shared_workspace'?'Gemeinsames Archiv':'Bisheriges Konto' }}</div>
                                                @else
                                                    <div class="hw24-sub">KI-Archiv: noch nicht bestätigt</div>
                                                @endif
                                            @endif
                                            @if($item['resolution']==='existing')
                                                <div class="hw24-sub"><strong>Stammdaten separat prüfen</strong> · Recherche ist keine Pflegefreigabe</div>
                                            @elseif($item['resolution']==='new_candidate')
                                                <div class="hw24-sub">Entwurf: {{ $coverage['filled'] }}/{{ $coverage['total'] }} Felder belegt · {{ count($coverage['missing_required']) }} Basis-Pflichtfelder offen</div>
                                                <div class="hw24-sub">Nächster Schritt: Entwurf prüfen und ergänzen.</div>
                                            @else
                                                <div class="hw24-sub">Nächster Schritt: Produktidentität manuell klären.</div>
                                            @endif
                                        </div>
                                        <div class="hw24-position-actions">
                                            @if($item['review']!=='removed')
                                                @if($this->canWriteScan())
                                                    <button type="button" class="hw24-btn" title="Erfasste Menge um ein Stück reduzieren. Keine Lagerbuchung." wire:click="adjustPosition('{{ $item['ean'] }}','reduce')" wire:confirm="Erfasste Stückzahl um eins reduzieren?" wire:loading.attr="disabled" @disabled($item['scans']<=1)>− 1 Stück</button>
                                                    <button type="button" class="hw24-btn" title="Position logisch löschen; alle Recherchearchive bleiben erhalten." wire:click="adjustPosition('{{ $item['ean'] }}','remove')" wire:confirm="Position aus diesem Stapel entfernen? Recherchearchive bleiben erhalten." wire:loading.attr="disabled">Position löschen</button>
                                                @endif
                                                <button title="Artikelentwurf öffnen und einzelne Felder prüfen oder bearbeiten." type="button" class="hw24-btn" wire:click="openDraft('{{ $item['ean'] }}')">Artikel bearbeiten</button>
                                                <button type="button" class="hw24-btn" title="Öffnet die KI-Ausnahmeprüfung für diese EAN und lädt vorhandene Berichte. Noch keine kostenpflichtige Abfrage." wire:click="openAiResearch('{{ $item['ean'] }}')" wire:loading.attr="disabled" wire:target="openAiResearch">OpenAI · KI recherchieren</button>
                                                <a class="hw24-link" href="{{ url('/admin/intelligence').'?ean='.rawurlencode($item['ean']) }}">Recherche-Details öffnen</a>
                                            @elseif($recycleBinAvailable && $this->canWriteScan())
                                                <button type="button" class="hw24-btn" wire:click="restorePosition('{{ $item['ean'] }}')" wire:confirm="Position mit ursprünglicher erfasster Menge wiederherstellen?" wire:loading.attr="disabled">Position wiederherstellen</button>
                                            @endif
                                        </div>
                                    </div>
                                    <details class="hw24-detail">
                                        <summary>Erfassung, Quellen und Archivdetails</summary>
                                        @if($job)<p class="hw24-sub">Quellenstand des gespeicherten Auftrags; keine neue Abfrage. Einzelne Abfragezeiten sind im Auftrag derzeit nicht vollständig hinterlegt.</p>@endif
                                        <dl class="hw24-facts">
                                            <div><dt>Erfasst am (MEZ/MESZ)</dt><dd>{{ $item['entry_at'] ?? 'Zeitpunkt nicht gespeichert' }}</dd></div>
                                            <div><dt>Gespeicherte Scanvorgänge</dt><dd>{{ $item['scan_events'] ?? 'Nicht belegt' }}</dd></div>
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
                    @php($editEans=$this->editableEans())
                    @php($editIndex=array_search($workingEan,$editEans,true))
                    <nav class="hw24-action" aria-label="Artikel wechseln" style="margin-bottom:12px">
                        <button title="Zum benachbarten Artikel wechseln; ungespeicherte Änderungen können verloren gehen." type="button" class="hw24-btn" wire:click="moveDraft(-1)" wire:confirm="Zum vorherigen Artikel wechseln? Nicht gespeicherte Änderungen werden verworfen." @disabled($editIndex===false||$editIndex===0)>← Vorheriger Artikel</button>
                        <strong>Artikel {{ $editIndex===false?'?':$editIndex+1 }} von {{ count($editEans) }}</strong>
                        <button title="Zum benachbarten Artikel wechseln; ungespeicherte Änderungen können verloren gehen." type="button" class="hw24-btn" wire:click="moveDraft(1)" wire:confirm="Zum nächsten Artikel wechseln? Nicht gespeicherte Änderungen werden verworfen." @disabled($editIndex===false||$editIndex>=count($editEans)-1)>Nächster Artikel →</button>
                    </nav>
                    <div class="hw24-action">
                        <button title="Original-Stammdaten des Artikels aus dem JTL-Spiegel lesen." type="button" class="hw24-btn" wire:click="loadJtlFieldReview" wire:loading.attr="disabled" wire:target="loadJtlFieldReview">JTL-Stammdaten prüfen (nur lesen)</button>
                        <button title="Vergleichsartikel gleichen Herstellers und gleicher Warengruppe lesen." type="button" class="hw24-btn" wire:click="loadPeerReview" wire:loading.attr="disabled" wire:target="loadPeerReview">Vergleichsartikel aus JTL prüfen</button>
                        <button title="JTL und Amazon für diese EAN abfragen; möglicher neuer Amazon-Aufruf." type="button" class="hw24-btn" wire:click="researchOne('{{ $workingEan }}')" wire:loading.attr="disabled" wire:confirm="JTL und Amazon-Katalog für diese EAN abfragen?">JTL &amp; Amazon recherchieren</button>
                        <a class="hw24-link" target="_blank" rel="noopener noreferrer" href="{{ url('/admin/intelligence').'?ean='.rawurlencode($workingEan) }}">KI-Archiv und KI-Einzelprüfung öffnen</a>
                    </div>
                    @if($peerReview!==[])
                        <div class="hw24-note">
                            <h3>Vergleichsartikel · gleiche Warengruppe und gleicher Hersteller</h3>
                            @if($peerReview['ok']??false)
                                <p>{{ $peerReview['peer_count'] }} Vergleichsartikel · Spiegelstand {{ $peerReview['snapshot_at']??'nicht belegt' }}</p>
                                @if($peerReview['suggestions']===[])<p>Keine ausreichend belegte Empfehlung für ein fehlendes Standardfeld.</p>@endif
                                @foreach($peerReview['suggestions'] as $field=>$suggestion)
                                    <p><strong>{{ $field }}: {{ $suggestion['value'] }}</strong> · {{ $suggestion['support'] }} von {{ $suggestion['sample_count'] }} Vergleichsartikeln.
                                    <br>Quelle: JTL {{ $suggestion['source_field'] }} · Artikel-IDs {{ implode(', ',$suggestion['source_article_ids']) }}. Manuelle Entscheidung erforderlich.</p>
                                @endforeach
                                <p>Nur ausgewählte Standardfelder; keine Übernahme von Artikelidentität, Preisen, Bildern, Mengen oder Beschreibungen. Vorschläge sind keine Freigabe.</p>
                            @else
                                <p>{{ $peerReview['message'] }}</p>
                            @endif
                        </div>
                    @endif
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
                                            <dd>{{ match($info['state']){'value'=>$info['value'],'reference'=>$info['value'],'observed_reference'=>$info['value'],'empty_snapshot'=>'Im Spiegel nicht belegt','missing_source'=>'Quelle bekannt, Spiegelwert fehlt',default=>'Quellzuordnung noch offen'} }}</dd>
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
                                    <button title="Bestätigte Katalogvorschläge in noch leere Entwurffelder übernehmen." type="button" class="hw24-btn" wire:click="useCatalogSuggestion('{{ $candidate['asin'] }}')" wire:confirm="Diesen Produktvorschlag in noch leere Entwurffelder übernehmen?">Vorschlag übernehmen</button>
                                    <button title="Marktpreise und Buy Box prüfen; eigener Verkaufspreis bleibt unverändert." type="button" class="hw24-btn" wire:click="priceCandidate('{{ $candidate['asin'] }}')" wire:loading.attr="disabled">Buy Box / Marktpreise prüfen</button>
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
                    <div class="hw24-note" id="hw24-manual-ai" x-data x-on:hw24-ai-research-opened.window="$nextTick(() => $el.scrollIntoView({block:'center',behavior:'smooth'}))">
                        <h3>OpenAI · KI-Ausnahmeprüfung für {{ $workingEan }}</h3>
                        <p>Wenn JTL und Amazon nicht ausreichen: zuerst vorhandenen KI-Bericht kostenlos laden, dann bewusst eine neue Analyse bestätigen. Die reine Datenprüfung benötigt einen vom Etikett belegten Namen; für unbekannte Artikel die Webrecherche auswählen, sobald serverseitig freigegeben.</p>
                        <label>Produktname / Etikett (falls bekannt) <input type="text" class="hw24-btn" maxlength="500" wire:model="draftFields.Artikelname" placeholder="Optional für Webrecherche"></label>
                        <label>Hersteller (falls bekannt) <input type="text" class="hw24-btn" maxlength="180" wire:model="draftFields.Hersteller"></label>
                        <div class="hw24-action">
                            <button title="Bereits archivierte KI-Analyse ohne neue Kosten laden." type="button" class="hw24-btn" wire:click="loadWorkingAi" wire:loading.attr="disabled">Vorhandenes KI-Archiv laden</button>
                            <label><input type="checkbox" wire:model.live="confirmSingleAi"> Kostenpflichtige Einzelprüfung bestätigen</label>
                            <button title="Nach Freigabe fehlenden KI-Bericht anfordern; kann Kosten auslösen." type="button" class="hw24-btn" wire:click="runWorkingAi" wire:loading.attr="disabled" @disabled(!$confirmSingleAi || !config('hw24_legacy.ai_paid_enabled'))>KI-Datenprüfung starten</button>
                            <button title="Kostenpflichtige OpenAI-Websuche nach EAN und Produktangaben. Nur bei aktivierter Suchschnittstelle. Keine automatische Artikelanlage." type="button" class="hw24-btn" wire:click="runWorkingAi(true)" wire:loading.attr="disabled" @disabled(!$confirmSingleAi || !config('hw24_legacy.ai_paid_enabled'))>OpenAI-Webrecherche starten</button>
                        </div>
                        @if($workingAi!==[])
                            <p>{{ $workingAi['scope'] }} · HTTP {{ $workingAi['http']??'—' }} · {{ !$workingAi['ok']?'Archiv nicht verfügbar':($workingAi['found']?'Bericht #'.$workingAi['id']:'Kein Bericht gefunden') }}</p>
                            @if($workingAi['found'])
                                <p>{{ $workingAi['title'] }} · {{ $workingAi['manufacturer'] }} · {{ $workingAi['group'] }}</p>
                                <p>{{ $workingAi['reason'] }} {{ $workingAi['notes'] }}</p>
                                <p>Farbton: {{ $workingAi['shade']?:'Offen' }} · Inhalt: {{ $workingAi['volume']?:'Offen' }} {{ $workingAi['unit'] }} · Typ: {{ $workingAi['type']?:'Offen' }}</p>
                                @if(($workingAi['cost']??null)!==null)<p>Archivierte geschätzte Kosten: {{ number_format($workingAi['cost'],6,',','.') }} USD</p>@endif
                                @if(!empty($workingAi['sources']))
                                    <p><strong>Archivierte Webquellen:</strong></p>
                                    @foreach($workingAi['sources'] as $source)<p><a class="hw24-link" href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['title'] }}</a></p>@endforeach
                                @endif
                                <button title="KI-Vorschläge für leere Entwurffelder zur Prüfung übernehmen." type="button" class="hw24-btn" wire:click="useAiSuggestion" wire:confirm="KI-Vorschläge in leere Entwurffelder übernehmen?">KI-Vorschläge übernehmen</button>
                            @endif
                        @endif
                        @if($draftMessage!=='')<p role="status"><strong>{{ $draftMessage }}</strong></p>@endif
                        <p>Neue Abfragen benötigen bestätigten Archivzugriff, Budget und Deine Kostenfreigabe. Webrecherche ist eine eigene kostenpflichtige Option; Produktdaten aus Webquellen bleiben Prüfvorschläge. Vorhandene Berichte werden wiederverwendet.</p>
                    </div>
                    <div class="hw24-note">
                        <h3>JTL-Vorlagenartikel · Standardfelder</h3>
                        <p>Für Neuanlagen: genaue Artikelnummer Deiner gepflegten Vorlage wählen. Keine Übernahme von EAN, HAN, ASIN, Artikelnummer, Preisen, Mengen oder fremden Beschreibungstexten.</p>
                        <div class="hw24-action">
                            <label>Vorlagenartikelnummer <input type="text" class="hw24-btn" wire:model="templateNumber" placeholder="JTL-Artikelnummer"></label>
                            <button title="Genau bezeichneten JTL-Vorlagenartikel prüfen." type="button" class="hw24-btn" wire:click="loadTemplate" wire:loading.attr="disabled">Vorlage prüfen</button>
                            <button title="Vorlagen nach Hersteller, Warengruppe und bestätigter Füllmenge suchen." type="button" class="hw24-btn" wire:click="findMatchingTemplates" wire:loading.attr="disabled">Passende gekennzeichnete Vorlagen suchen</button>
                            <button title="Leere SEO-Felder anhand des Produktnamens kostenlos vorschlagen." type="button" class="hw24-btn" wire:click="fillSeoDefaults">Leere SEO-Felder vorschlagen</button>
                            <button title="Leere Beschreibungen mit dem eigenen Artikelnamen füllen." type="button" class="hw24-btn" wire:click="fillEmptyTexts">Leere Beschreibungen aus Artikelnamen füllen</button>
                        </div>
                        @if($templateCandidates!==[])
                            @if($templateCandidates['ok']??false)
                                <p>{{ count($templateCandidates['items']) }} passende Vorlagen · {{ $templateCandidates['snapshot_at']??'Zeitpunkt offen' }}</p>
                                @if(!empty($templateCandidates['message']))<p>{{ $templateCandidates['message'] }}</p>@endif
                                @foreach($templateCandidates['items'] as $candidate)
                                    <p>{{ $candidate['name'] }} · {{ $candidate['number'] }} · {{ $candidate['amount'] }} {{ $candidate['unit'] }}
                                    <button title="Diese gefundene Vorlage zur Übernahme prüfen." type="button" class="hw24-btn" wire:click="chooseMatchingTemplate({{ $loop->index }})">Als Vorlage prüfen</button></p>
                                @endforeach
                            @else<p>{{ $templateCandidates['message'] }}</p>@endif
                        @endif
                        @if($templateReview!==[])
                            @if($templateReview['ok']??false)
                                <p><strong>{{ $templateReview['name'] }}</strong> · {{ $templateReview['manufacturer'] }} / {{ $templateReview['group'] }} · {{ $templateReview['snapshot_at']??'Zeitpunkt nicht belegt' }}</p>
                                @if(!$templateReview['category_confirmed'])<p>Produkttyp nicht eindeutig abgeglichen. Eignung der Vorlage manuell kontrollieren.</p>@endif
                                <dl class="hw24-facts">
                                    @forelse($templateReview['fields'] as $f=>$v)
                                        <div><dt>{{ $f }} · Hinzufügen</dt><dd>{{ $v }}</dd></div>
                                    @empty<p>Keine leeren belegbaren Standardfelder.</p>@endforelse
                                </dl>
                                <button title="Nur bestätigte Standardfelder aus der Vorlage ergänzen." type="button" class="hw24-btn" wire:click="applyTemplate" wire:loading.attr="disabled" wire:confirm="Hersteller, Warengruppe und Produkttyp passen? Nur angezeigte leere Felder ergänzen.">Standardfelder übernehmen</button>
                            @else<p>{{ $templateReview['message'] }}</p>@endif
                        @endif
                    </div>
                    @php($categoryProposal=$this->categoryProposal())
                    @if($categoryProposal)
                        <div class="hw24-note">
                            <h3>Kategorie-Vorschlag · HW24 Business Hub</h3>
                            <p>{{ $categoryProposal['Kategorie Ebene 1'] }} / {{ $categoryProposal['Kategorie Ebene 2'] }}</p>
                            <p>Nach festgelegter Produktregel; JTL-Warengruppe und Kategorie bleiben getrennte Felder. Keine automatische Freigabe.</p>
                            <button title="Vorgeschlagenen Kategoriepfad im Entwurf übernehmen." type="button" class="hw24-btn" wire:click="applyCategoryProposal" wire:confirm="Diesen Kategoriepfad im Entwurf übernehmen?">Kategorie-Vorschlag übernehmen</button>
                        </div>
                    @endif
                    <p class="hw24-description">Scanentwurf und JTL-Spiegel sind getrennt. Bei vorhandenen JTL-Artikeln dient der Feldcheck oben zunächst der manuellen Bewertung; Änderungen an bestehenden Artikeln werden hier noch nicht gespeichert. Neue Artikel können nach Prüfung als Entwurf gespeichert werden.</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px">
                        @foreach(\App\Services\ScanResearch::FIELDS as $field)
                            <label style="font-size:12px">
                                {{ $field }}{{ in_array($field,\App\Services\ScanResearch::REQUIRED,true)?' *':'' }}
                                @if(isset($draftProvenance[$field]) && $draftProvenance[$field]['value']===($draftFields[$field]??null))<span class="hw24-sub"> · {{ $draftProvenance[$field]['source'] }}</span>@endif
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
                        <button title="Entwurf speichern oder nach Prüfung für den Ameise-Prüfexport freigeben." type="button" class="hw24-btn" wire:click="saveDraft(false)" @disabled(!$this->canWriteScan()||!$confirmDraft)>Entwurf speichern</button>
                        <button title="Entwurf speichern oder nach Prüfung für den Ameise-Prüfexport freigeben." type="button" class="hw24-btn" wire:click="saveDraft(true)" @disabled(!$this->canWriteScan()||!$confirmDraft)>Prüfen und für Ameise freigeben</button>
                    </div>
                    @if($draftMessage!=='')<p class="hw24-note" role="status">{{ $draftMessage }}</p>@endif
                </div>
            </section>
        @endif

        <section class="hw24-card">
            <div class="hw24-card-head"><h3>05 · JTL-Ameise-Prüfexport direkt in Filament</h3></div>
            <div class="hw24-card-body">
                @if($this->canQueueResearch() && $this->canWriteScan())
                    <div class="hw24-note" x-data="{releasing:false}">
                        <h3>Sammelfreigabe gespeicherter Neuanlagen</h3>
                        <p>Markierte Entwürfe oder ohne Markierung alle offenen Neuanlagen prüfen. Nur vollständig belegte Basisfelder und im aktuellen Wawi-Spiegel nicht vorhandene GTINs sind geeignet. Exportvorlage und fachliche Pflichtfelder weiterhin prüfen.</p>
                        <button title="Vollständige Entwürfe und Ausnahmen vor der Sammelfreigabe prüfen." type="button" class="hw24-btn" wire:click="previewBatchRelease" x-bind:disabled="releasing" wire:loading.attr="disabled">Freigabevorschau erstellen</button>
                        @if($releaseMessage!=='')<p role="status">{{ $releaseMessage }}</p>@endif
                        @if($releaseExceptions!==[])
                            <details open><summary>{{ count($releaseExceptions) }} Ausnahmen</summary>
                                @foreach($releaseExceptions as $exception)<p>{{ $exception['ean'] }} · {{ $exception['reason'] }}</p>@endforeach
                            </details>
                        @endif
                        @if($releaseCandidates!==[])
                            <details open><summary>{{ count($releaseCandidates) }} Entwürfe zur Freigabe</summary>
                                <div style="overflow-x:auto">
                                    @foreach($releaseCandidates as $candidate)
                                        <details><summary>{{ $candidate['ean'] }} · {{ $candidate['name'] }}</summary>
                                            @foreach($candidate['fields'] as $label=>$value)@if(trim((string)$value)!=='')<p><strong>{{ $label }}:</strong> {{ $value }}</p>@endif @endforeach
                                        </details>
                                    @endforeach
                                </div>
                            </details>
                            <label><input type="checkbox" wire:model.live="confirmBatchRelease" x-bind:disabled="releasing"> Angezeigte Entwürfe fachlich geprüft, für Ameise-Prüfexport freigeben.</label>
                            <button title="Geprüfte Entwürfe in Abschnitten für den Prüfexport freigeben." type="button" class="hw24-btn" x-bind:disabled="releasing || !$wire.confirmBatchRelease"
                                x-on:click="if(!confirm('Geprüfte Entwürfe gemeinsam freigeben? Kein JTL-Import.'))return; releasing=true; try{for(let i=0;i&lt;67 &amp;&amp; releasing;i++){if(!(await $wire.releaseBatchChunk()))break;}}finally{releasing=false;}">Geprüfte Entwürfe gemeinsam freigeben</button>
                            <button title="Nach dem laufenden Abschnitt pausieren; gespeicherte Ergebnisse bleiben erhalten." type="button" class="hw24-btn" x-bind:disabled="!releasing" x-on:click="releasing=false">Nach aktuellem Abschnitt pausieren</button>
                        @endif
                    </div>
                @endif
                <p class="hw24-description">Die bestehende Scanlisten-API prüft vor dem CSV-Export den aktuellen JTL-Snapshot, freigegebene Neuanlagen, doppelte Artikelnummern und Pflichtfelder. Maximal 200 freigegebene Positionen. Nur ein Prüfexport, kein JTL-Direktimport.</p>
                <div class="hw24-action">
                    <details style="width:100%;margin:8px 0">
                        <summary>Exportspalten konfigurieren (standardmäßig alle aktiviert)</summary>
                        <button title="Alle verfügbaren Spalten für die Exportprüfung auswählen." type="button" class="hw24-btn" wire:click="allExportFields">Alle Felder auswählen</button>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:8px;margin-top:8px">
                            @foreach(\App\Services\ScanResearch::FIELDS as $field)
                                <label><input type="checkbox" wire:model.live="selectedExportFields" value="{{ $field }}" @disabled($field==='Artikelnummer'||in_array($field,\App\Services\ScanResearch::REQUIRED,true))> {{ $field }}</label>
                            @endforeach
                        </div>
                        <p>Artikelnummer und Pflichtfelder bleiben im Export. Optionale leere Spalten werden ausgelassen. Einstellungen gelten in der aktuellen Sitzung; Stammdaten werden nicht gelöscht.</p>
                    </details>
                    <button title="Freigegebene Positionen und gewählte Exportfelder prüfen." type="button" class="hw24-btn" wire:click="prepareAmeiseExport" wire:loading.attr="disabled" wire:target="prepareAmeiseExport" @disabled($selectedBatchId === null)>Freigegebene Positionen prüfen</button>
                    @if(($exportState['ok']??false)===true)
                        <button title="Geprüfte CSV herunterladen; kein automatischer Import in JTL." type="button" class="hw24-btn" wire:click="downloadAmeiseCsv"
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

        <details class="hw24-card">
            <summary class="hw24-card-head">Expertenansicht · bisherige Scan-Funktionen</summary>
            <div class="hw24-card-body">
                <p class="hw24-description">Die bestehende, funktionsfähige Scanliste bleibt bis zur vollständigen sicheren Migration die führende Oberfläche für Erfassung, Änderung, Freigabe und Prüfexport.</p>
                <div class="hw24-action">
                    <a class="hw24-open" style="background:#1b567b;color:#fff" href="https://artikelmanager.hairworld24.de/scanliste.html" target="_blank" rel="noopener noreferrer">EAN scannen, Stapel bearbeiten und CSV exportieren</a>
                    <a class="hw24-link" href="https://artikelmanager.hairworld24.de/index.html#ameisePanel" target="_blank" rel="noopener noreferrer">Expertenansicht mit JTL-Ameise-Entwurf</a>
                </div>
                <p class="hw24-note hw24-note-warn">Diese Links öffnen die geschützte bisherige LIVE-Anwendung in einem neuen Tab. Dort gelten die bestehenden Berechtigungen und Freigaben. Ein CSV-Prüfexport ist kein direkter Import in JTL-Wawi.</p>
            </div>
        </details>
    </div>
</x-filament-panels::page>
