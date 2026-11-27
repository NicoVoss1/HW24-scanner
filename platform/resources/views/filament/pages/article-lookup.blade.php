<x-filament-panels::page>
    {{-- The core Filament theme does not compile arbitrary Tailwind utility classes
         from this custom Blade page. Use dedicated, scoped CSS instead. --}}
    <div class="hw24-editor">
        <style>
            .hw24-editor{--hw24-border:#dbe3ed;--hw24-bg:#fff;--hw24-soft:#f6f9fc;--hw24-text:#172334;--hw24-muted:#586a7f;--hw24-accent:#194a76;color:var(--hw24-text);max-width:100%;font-size:14px}
            .hw24-editor *{box-sizing:border-box}
            .hw24-editor .hw24-head{background:linear-gradient(115deg,#142d45,#1b4867);border-radius:16px;color:white;padding:22px 25px;display:flex;gap:18px;flex-wrap:wrap;align-items:center;justify-content:space-between}
            .hw24-editor .hw24-head h2{font-weight:750;font-size:23px;line-height:1.2;letter-spacing:-.025em;margin:4px 0 6px}
            .hw24-editor .hw24-head p{font-size:13px;line-height:1.6;color:#dceafb;margin:0;max-width:650px}
            .hw24-editor .hw24-kicker{font-size:11px!important;text-transform:uppercase;letter-spacing:.1em;font-weight:800;color:#99c5f0!important}
            .hw24-editor .hw24-link{display:inline-flex;align-items:center;justify-content:center;border-radius:9px;background:#fff;color:#183957;padding:10px 14px;min-height:40px;font-size:12px;font-weight:750;text-decoration:none}
            .hw24-editor .hw24-link:hover{background:#e8f2fb}
            .hw24-editor .hw24-stack{display:grid;gap:16px;margin-top:17px}
            .hw24-editor .hw24-panel{min-width:0;background:var(--hw24-bg);border:1px solid var(--hw24-border);border-radius:15px;box-shadow:0 1px 2px #0f172a0a}
            .hw24-editor .hw24-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:15px 18px;border-bottom:1px solid var(--hw24-border);background:var(--hw24-soft)}
            .hw24-editor .hw24-panel-head h3{font-size:15px;line-height:1.4;font-weight:750;margin:0}
            .hw24-editor .hw24-panel-body{padding:18px}
            .hw24-editor .hw24-description{font-size:12px;line-height:1.65;color:var(--hw24-muted);margin:0 0 14px}
            .hw24-editor .hw24-subtle{font-size:12px;line-height:1.6;color:var(--hw24-muted)}
            .hw24-editor .hw24-form{display:flex;flex-wrap:wrap;align-items:end;gap:12px}
            .hw24-editor .hw24-search-label{flex:1 1 310px;min-width:0;display:grid;gap:6px;font-size:12px;font-weight:700;color:var(--hw24-text)}
            .hw24-editor .hw24-input{display:block;width:100%;min-height:42px;border:1px solid #b9c8d9;border-radius:9px;padding:10px 12px;background:var(--hw24-bg);color:var(--hw24-text);font:inherit;outline:none}
            .hw24-editor .hw24-input:focus{border-color:#2c79b7;box-shadow:0 0 0 3px #2c79b72b}
            .hw24-editor .hw24-call-to-action{display:flex;align-items:center;min-height:42px}
            .hw24-editor .hw24-meta{display:flex;align-items:center;gap:8px 14px;flex-wrap:wrap;margin-top:14px;font-size:12px;color:var(--hw24-muted)}
            .hw24-editor .hw24-chip{font-weight:750;border-radius:999px;font-size:11px;line-height:1.3;padding:5px 10px;background:#ebf2fb;color:#184b76;white-space:nowrap}
            .hw24-editor .hw24-good{background:#e8f8ef;color:#09603a}
            .hw24-editor .hw24-warning{background:#fff3e1;color:#93480a}
            .hw24-editor .hw24-alert{padding:11px 14px;border-radius:9px;border:1px solid #f2dab4;background:#fffbeb;color:#8a4a12;font-size:12px;line-height:1.6;margin-top:14px}
            .hw24-editor .hw24-success{border-color:#b9e7cf;background:#f0fdf5;color:#17613d}
            .hw24-editor .hw24-result-table{width:100%;border-collapse:collapse;font-size:12px;min-width:540px;text-align:left}
            .hw24-editor .hw24-table-wrap{width:100%;overflow-x:auto}
            .hw24-editor .hw24-result-table th{background:var(--hw24-soft);color:var(--hw24-muted);font-size:11px;text-transform:uppercase;letter-spacing:.04em;font-weight:700;padding:11px 12px;white-space:nowrap}
            .hw24-editor .hw24-result-table td{padding:12px;border-top:1px solid var(--hw24-border);vertical-align:top}
            .hw24-editor .hw24-result-table td strong{font-weight:750}
            .hw24-editor .hw24-table-small{font-size:11px;color:var(--hw24-muted);margin-top:4px}
            .hw24-editor .hw24-nowarp{white-space:nowrap}
            .hw24-editor .hw24-product-title{font-size:17px;font-weight:800;letter-spacing:-.01em;line-height:1.4;margin:0}
            .hw24-editor .hw24-product-subtitle{font-size:12px;color:var(--hw24-muted);margin:5px 0 0}
            .hw24-editor .hw24-section-layout{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:16px}
            .hw24-editor .hw24-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px 18px;margin:0}
            .hw24-editor .hw24-field{min-width:0;padding-bottom:9px;border-bottom:1px solid #e9eef4}
            .hw24-editor .hw24-field dt{font-size:11px;font-weight:650;line-height:1.4;color:var(--hw24-muted);margin:0 0 5px}
            .hw24-editor .hw24-field dd{font-weight:750;font-size:13px;line-height:1.45;overflow-wrap:anywhere;margin:0}
            .hw24-editor .hw24-field .hw24-note{font-weight:400;font-size:11px;color:var(--hw24-muted);margin:4px 0 0;line-height:1.4}
            .hw24-editor .hw24-price-box{border:1px solid var(--hw24-border);padding:13px;border-radius:10px;background:var(--hw24-soft);min-width:0}
            .hw24-editor .hw24-price-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
            .hw24-editor .hw24-price-box small{display:block;color:var(--hw24-muted);font-size:11px;margin-bottom:6px}
            .hw24-editor .hw24-price-box strong{font-weight:800;font-size:17px;line-height:1.3;overflow-wrap:anywhere}
            .hw24-editor .hw24-price-box p{font-size:11px;margin:5px 0 0;color:var(--hw24-muted);line-height:1.4;overflow-wrap:anywhere}
            .hw24-editor .hw24-toolbar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:12px}
            .hw24-editor .hw24-channel-layout{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
            .hw24-editor .hw24-channel{padding:17px 18px}
            .hw24-editor .hw24-channel h3{font-weight:750;font-size:14px;line-height:1.4;margin:0 0 9px}
            .hw24-editor .hw24-channel p{font-size:12px;line-height:1.6;color:var(--hw24-muted);margin:0}
            .hw24-editor .hw24-channel .hw24-toolbar{margin:12px 0 0}
            .hw24-editor .hw24-source-filter{max-width:540px;display:grid;gap:6px;margin:14px 0;font-size:12px;font-weight:700}
            .hw24-editor .hw24-source-table{width:100%;border-collapse:collapse;min-width:660px;font-size:12px;text-align:left}
            .hw24-editor .hw24-source-table th{text-align:left;background:var(--hw24-soft);padding:10px 12px;color:var(--hw24-muted)}
            .hw24-editor .hw24-source-table td{vertical-align:top;padding:11px 12px;border-top:1px solid var(--hw24-border);overflow-wrap:anywhere;max-width:440px}
            .hw24-editor .hw24-source-scroll{overflow-x:auto;border:1px solid var(--hw24-border);border-radius:9px}
            .hw24-editor .hw24-source-table pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:11px}
            .hw24-editor .hw24-muted-label{color:var(--hw24-muted);font-size:11px;line-height:1.45}
            .hw24-editor .hw24-error{color:#b91c1c;font-size:12px;margin-top:8px}
            .hw24-editor .hw24-foot{background:var(--hw24-soft);border:1px solid var(--hw24-border);border-radius:12px;padding:15px 18px;font-size:12px;line-height:1.65;color:var(--hw24-muted)}
            .hw24-editor .hw24-foot strong{color:var(--hw24-text)}
            @media(max-width:760px){.hw24-editor .hw24-section-layout,.hw24-editor .hw24-channel-layout{grid-template-columns:minmax(0,1fr)}.hw24-editor .hw24-head{padding:19px}.hw24-editor .hw24-head h2{font-size:20px}.hw24-editor .hw24-panel-body{padding:15px}}
            @media(max-width:460px){.hw24-editor .hw24-price-grid{grid-template-columns:minmax(0,1fr)}.hw24-editor .hw24-fields{gap:11px}.hw24-editor .hw24-field dd{font-size:12px}}
            html.dark .hw24-editor{--hw24-border:#334155;--hw24-bg:#17212f;--hw24-soft:#1e293b;--hw24-text:#f1f5f9;--hw24-muted:#b9c6d7}
            html.dark .hw24-editor .hw24-field{border-color:#334155}
            html.dark .hw24-editor .hw24-chip{background:#263e58;color:#dbeafe}
            html.dark .hw24-editor .hw24-good{background:#164e37;color:#d1fae5}
            html.dark .hw24-editor .hw24-warning{background:#713f12;color:#ffedd5}
            html.dark .hw24-editor .hw24-alert{background:#453516;color:#fde68a;border-color:#78571d}
            .hw24-editor a:focus-visible,.hw24-editor button:focus-visible,.hw24-editor summary:focus-visible{outline:3px solid #60a5fa;outline-offset:2px}
        </style>

        <header class="hw24-head">
            <div>
                <p class="hw24-kicker">HAIRWORLD24 · Produktdatenplattform</p>
                <h2>Artikelzentrale · Artikelpflege Bestand</h2>
                <p>Stammdaten, Bestand und Preise aus dem JTL-Spiegel. Originalquellen und Marktplatzangebote bleiben nachvollziehbar getrennt.</p>
            </div>
            <a class="hw24-link" href="{{ route('filament.admin.pages.dashboard') }}">Zum Management-Cockpit</a>
        </header>
        @include('filament.partials.hub-release-status')

        <nav aria-label="Bisherige HW24-Funktionen" style="margin-top:15px;padding:15px 18px;border:1px solid #b9d0e5;border-radius:13px;background:#f3f8fe">
            <strong style="font-size:13px;color:#194a76;display:block;margin-bottom:7px">Bisherige Arbeitsfunktionen sind weiterhin verfügbar</strong>
            <p style="font-size:12px;line-height:1.6;margin:0 0 11px;color:#405970">Bis zur vollständigen Migration kannst Du KI-Analysen, Scan-Stapel, JTL-Ameise-Entwürfe und Amazon-Recherche im bestehenden geschützten Artikelmanager weiter nutzen.</p>
            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <a class="hw24-link" href="https://artikelmanager.hairworld24.de/artikelpflege.html" target="_blank" rel="noopener noreferrer" style="background:#174d78;color:white">Vollständige Artikelpflege &amp; KI-Abfrage</a>
                <a class="hw24-link" href="{{ url('/admin/intelligence') }}{{ $this->verifiedEan() !== null ? '?ean=' . urlencode($this->verifiedEan()) : '' }}" style="background:#d3edfa;color:#124a6d">KI-Archiv, neue KI-Prüfung &amp; Amazon-Recherche</a>
                <a class="hw24-link" href="{{ url('/admin/lager-preise') }}{{ $this->verifiedEan() !== null ? '?ean=' . urlencode($this->verifiedEan()) : '' }}" style="background:#d3eff1;color:#135465">WMS &amp; Preisvergleich</a>
                <a class="hw24-link" href="{{ url('/admin/scanliste') }}" style="background:#e1effa;color:#194a76">Scanliste hier öffnen</a>
                <a class="hw24-link" href="{{ url('/admin/werkzeuge') }}" style="background:#e1effa;color:#194a76">Alle bisherigen Werkzeuge</a>
                <a class="hw24-link" href="https://artikelmanager.hairworld24.de/index.html" target="_blank" rel="noopener noreferrer" style="background:#e1effa;color:#194a76">Amazon &amp; JTL-Ameise</a>
            </div>
            <small style="font-size:11px;display:block;margin-top:9px;color:#5b728a">Externe Links führen zur bestehenden LIVE-Anwendung und starten selbst keine kostenpflichtigen KI- oder Amazon-Abfragen.</small>
        </nav>

        <div class="hw24-stack">
            @if($referenceReview && \App\Filament\Pages\QueryAuditWorkbench::canAccess())
            <section class="hw24-panel">
                <div class="hw24-panel-head"><h3>Gemeinsame Referenzrecherche · {{ $referenceEan }}</h3></div>
                <div class="hw24-panel-body">
                    <p class="hw24-description">Historische Originalwerte sind Prüfvorschläge, keine aktuellen Preise oder Bestände. Vollständige Originalzeilen über die Archivlinks öffnen.</p>
                    @foreach($referenceReview as $source=>$reference)
                        <details open>
                            <summary><strong>{{ $source==='ameise'?'JTL-Ameise-Stammdaten':'Historische Artikeldaten' }}</strong> · {{ match($reference['state']){'found'=>'Treffer','missing'=>'Kein Treffer','ambiguous'=>'Mehrdeutig',default=>'Nicht verfügbar'} }} · {{ $reference['total'] }} Zeilen</summary>
                            <p class="hw24-toolbar"><a class="hw24-link" href="{{ $source==='ameise'?\App\Filament\Pages\AmeiseReferenceWorkbench::getUrl(['ean'=>$referenceEan]):\App\Filament\Pages\ReferenceTableWorkbench::getUrl(['ean'=>$referenceEan]) }}">Alle Originalfelder im Archiv durchsuchen</a></p>
                            @foreach($reference['rows'] as $row)
                                <p>Originalzeile {{ $row['row'] }} · {{ $row['populated_count'] }} befüllte Originalfelder @if($row['requires_review']) · Prüfhinweis vorhanden @endif</p>
                                <div class="hw24-table-wrap"><table class="hw24-result-table"><thead><tr><th>Feld</th><th>JTL aktuell</th><th>Archivwert (Auszug)</th></tr></thead><tbody>
                                @forelse($row['fields'] as $field=>$value)
                                    <tr><td>{{ $field }}</td><td>{{ $value['current']!==''?$value['current']:'Nicht bestätigt' }}</td><td style="overflow-wrap:anywhere;max-width:520px">{{ $value['reference'] }}</td></tr>
                                @empty<tr><td colspan="3">Keine Abweichungen in den bisher zugeordneten Feldern.</td></tr>@endforelse
                                </tbody></table></div>
                            @endforeach
                            @if($reference['total']>5)<p>Weitere Treffer im Originalarchiv öffnen.</p>@endif
                        </details>
                    @endforeach
                </div>
            </section>
            @endif
            <section class="hw24-panel" aria-labelledby="hw24-search-heading">
                <div class="hw24-panel-head"><h3 id="hw24-search-heading">01 · Artikel finden</h3><span class="hw24-chip">JTL · nur lesend</span></div>
                <div class="hw24-panel-body">
                    <p class="hw24-description">Suche mit EAN, HAN, JTL-Artikelnummer, Produktname oder ASIN. Die Abfrage liest ausschließlich den bestehenden JTL-MariaDB-Spiegel.</p>
                    <form wire:submit="findArticle" class="hw24-form">
                        <label class="hw24-search-label" for="hw24-article-identifier">
                            EAN, HAN, JTL-Artikelnummer, Name oder ASIN
                            <input id="hw24-article-identifier" class="hw24-input" type="search" maxlength="100" wire:model="identifier"
                                autocomplete="off" placeholder="z. B. 3474630337398">
                        </label>
                        <div class="hw24-call-to-action">
                            <x-filament::button title="Artikel im vorhandenen JTL-Spiegel anhand des Suchbegriffs laden." type="submit" wire:loading.attr="disabled" wire:target="findArticle">Artikel laden</x-filament::button>
                            @if(\App\Filament\Pages\QueryAuditWorkbench::canAccess())<x-filament::button color="gray" type="button" wire:click="searchNameOrAsin" wire:loading.attr="disabled">Name / ASIN suchen</x-filament::button>@endif
                        </div>
                    </form>
                    @if($nameSearchMessage!=='')<p role="status">{{ $nameSearchMessage }}</p>@endif
                    @if($nameMatches)
                        <div class="hw24-table-wrap"><table class="hw24-result-table"><thead><tr><th>Artikelname</th><th>Artikelnummer</th><th>Hersteller</th><th>EAN</th><th>ASIN</th><th>Auswahl</th></tr></thead><tbody>
                        @foreach($nameMatches as $index=>$match)<tr><td>{{ $match['artikelname'] }}</td><td>{{ $match['artikelnummer'] }}</td><td>{{ $match['hersteller'] }}</td><td>{{ $match['ean'] }}</td><td>{{ $match['asin'] }}</td><td><button class="hw24-link" wire:click="loadNameMatch({{ $index }})">Artikel laden</button></td></tr>@endforeach
                        </tbody></table></div>
                    @endif
                    @error('identifier') <p class="hw24-error" role="alert">{{ $message }}</p> @enderror
                    @if($lookup !== [])
                        <div class="hw24-meta" aria-live="polite">
                            <span class="hw24-chip {{ ($lookup['jtl_status'] ?? '') === 'current_snapshot' ? 'hw24-good' : 'hw24-warning' }}">
                                JTL: {{ $lookup['jtl_status'] ?? 'unbekannt' }}
                            </span>
                            <span>API: <strong>{{ $lookup['state'] ?? 'unbekannt' }}</strong></span>
                            <span>HTTP: <strong>{{ $lookup['http_status'] ?? '—' }}</strong></span>
                            <span>{{ count($lookup['matches'] ?? []) }} Treffer</span>
                        </div>
                        @if(count($lookup['matches'] ?? []) === 0)
                            <p class="hw24-alert">Kein eindeutiger Treffer. Bitte auch die Aktualität der JTL-Synchronisation prüfen.</p>
                        @else
                            <div class="hw24-table-wrap" style="margin-top:14px">
                                <table class="hw24-result-table">
                                    <thead>
                                        <tr><th>Artikel</th><th>EAN</th><th>Bestand JTL</th><th>VK netto JTL</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lookup['matches'] as $row)
                                            <tr>
                                                <td><strong>{{ $row['artikelname'] ?: 'Ohne Bezeichnung' }}</strong>
                                                    <div class="hw24-table-small">Nr. {{ $row['artikelnummer'] ?: 'unbekannt' }} · {{ $row['hersteller'] ?: 'Hersteller offen' }}</div>
                                                </td>
                                                <td class="hw24-nowarp">{{ $row['ean'] }}</td>
                                                <td>{{ count($lookup['matches']??[])===1 ? \App\Services\HubStockReader::display($articleStock['item']['total']??null) : 'Einzelartikel prüfen' }}</td>
                                                <td>{{ is_numeric($row['netto_vk']) ? number_format((float) $row['netto_vk'], 2, ',', '.') : 'Nicht belegt' }}
                                                    <div class="hw24-table-small">{{ $row['waehrung'] ?: 'Währung nicht bestätigt' }}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </section>

            @if(count($lookup['matches'] ?? [])===1 && ($lookup['jtl_status']??'')==='current_snapshot')
            <section class="hw24-panel" aria-labelledby="hw24-evidence-heading">
             <div class="hw24-panel-head">
              <h3 id="hw24-evidence-heading">Gespeicherte Daten und Lagerstatus auf einen Blick</h3>
              <span class="hw24-chip">Nur bereits vorhandene Quellen · keine neue KI</span>
             </div>
             <div class="hw24-panel-body">
              @include('filament.partials.hub-stock-board',['stockData'=>$articleStock,'showBins'=>true])
              <div class="hw24-price-grid">
               <div class="hw24-price-box">
                <small>🧠 OpenAI KI-Archiv · benutzerbezogen</small>
                @if(($existingEvidence['ai']['verified']??false)===true)
                 <strong>{{ ($existingEvidence['ai']['found']??false)===true ? '✅ Schon geprüft' : 'Noch kein Bericht' }}</strong>
                 <p>{{ ($existingEvidence['ai']['found']??false)===true ? ($existingEvidence['ai']['count'].' Berichte · zuletzt '.$existingEvidence['ai']['at'].' UTC · '.$existingEvidence['ai']['model']) : 'Kein gespeicherter Bericht für dieses Konto und diese EAN' }}</p>
                @else
                 <strong>⚠ Archivstatus unbekannt</strong><p>Verbindung oder Zuordnung konnte nicht bestätigt werden.</p>
                @endif
               </div>
               <div class="hw24-price-box">
                <small>📚 Amazon-Katalogarchiv · EAN</small>
                @if(($existingEvidence['amazon']['verified']??false)===true)
                 <strong>{{ ($existingEvidence['amazon']['found']??false)===true ? '✅ Schon recherchiert' : 'Kein Katalogsnapshot' }}</strong>
                 <p>{{ ($existingEvidence['amazon']['found']??false)===true ? 'Snapshot #'.$existingEvidence['amazon']['snapshot_id'].' · '.$existingEvidence['amazon']['at'].' UTC · '.$existingEvidence['amazon']['count'].' Katalogtreffer' : 'Keine gespeicherte Recherche zur EAN vorhanden' }}</p>
                @else
                 <strong>⚠ Archivquelle noch nicht bereit</strong><p>Keine automatische neue Amazon-Katalogabfrage. Live-Preise und Buy Box separat abrufen.</p>
                @endif
               </div>
              </div>
              <div class="hw24-toolbar">
               <a class="hw24-link" style="background:#e1effa;color:#184b76" href="{{ url('/admin/lager-preise') }}?ean={{ urlencode($lookup['ean']) }}">📦 WMS, Lagerplätze und Preise</a>
               <a class="hw24-link" style="background:#e1effa;color:#184b76" href="{{ url('/admin/intelligence') }}?ean={{ urlencode($lookup['ean']) }}">🧠 KI-Archiv und Amazon-Händler</a>
              </div>
              <p class="hw24-subtle" style="margin-top:10px">Die KI-Statusprüfung liest ausschließlich das vorhandene benutzergebundene Archiv. Die Amazon-Statusprüfung liest nur gespeicherte Katalog-Snapshots. Händlerpreise und Buy Box werden nicht dauerhaft als aktuelle Werte angenommen.</p>
             </div>
            </section>
            @endif

            @if(count($lookup['matches'] ?? []) === 1)
                @php
                    $item = $lookup['matches'][0];
                    $mass = $item['masseinheit_menge'] ?? null;
                    $massLabel = $mass !== null ? rtrim(rtrim(number_format((float) $mass, 3, ',', '.'), '0'), ',') : 'Nicht belegt';
                @endphp
                <section class="hw24-panel" aria-labelledby="hw24-product-heading">
                    <div class="hw24-panel-head">
                        <h3 id="hw24-product-heading">02 · Artikeldaten</h3>
                        <span class="hw24-chip {{ ($lookup['jtl_status'] ?? '') === 'current_snapshot' ? 'hw24-good' : 'hw24-warning' }}">
                            {{ ($lookup['jtl_status'] ?? '') === 'current_snapshot' ? 'Aktueller JTL-Snapshot' : 'Snapshot prüfen' }}
                        </span>
                    </div>
                    <div class="hw24-panel-body">
                        <h4 class="hw24-product-title">{{ $item['artikelname'] ?: 'Artikel ohne Bezeichnung' }}</h4>
                        <p class="hw24-product-subtitle">Synchronisiert: {{ $item['synced_at_utc'] ?? 'unbekannt' }} UTC · JTL-Artikel-ID {{ $item['jtl_article_id'] ?: 'unbekannt' }}</p>
                        <div class="hw24-section-layout" style="margin-top:19px">
                            <div>
                                <div class="hw24-panel-head" style="border:1px solid var(--hw24-border);border-radius:10px 10px 0 0">
                                    <h3>Stammdaten und Identifikation</h3>
                                </div>
                                <div style="border:1px solid var(--hw24-border);border-top:0;border-radius:0 0 10px 10px;padding:15px">
                                    <dl class="hw24-fields">
                                        <div class="hw24-field"><dt>EAN / GTIN</dt><dd>{{ $item['ean'] ?: 'Nicht belegt' }}</dd></div>
                                        <div class="hw24-field"><dt>Artikelnummer</dt><dd>{{ $item['artikelnummer'] ?: 'Nicht belegt' }}</dd></div>
                                        <div class="hw24-field"><dt>HAN</dt><dd>{{ $item['han'] ?: 'Nicht belegt' }}</dd></div>
                                        <div class="hw24-field"><dt>Hersteller</dt><dd>{{ $item['hersteller'] ?: 'Nicht belegt' }}</dd></div>
                                        <div class="hw24-field"><dt>Warengruppe</dt><dd>{{ $item['warengruppe_name'] ?: 'Nicht zugeordnet' }}</dd></div>
                                        <div class="hw24-field"><dt>Artikeltyp</dt><dd>{{ $item['artikeltyp'] ?: 'Noch nicht aufgelöst' }}</dd></div>
                                        <div class="hw24-field"><dt>ASIN aus JTL</dt><dd>{{ $item['asin'] ?: 'Keine Quelle' }}</dd><p class="hw24-note">Amazon-EAN gesondert prüfen</p></div>
                                        <div class="hw24-field"><dt>JTL-Artikel-ID</dt><dd>{{ $item['jtl_article_id'] ?: 'Nicht belegt' }}</dd></div>
                                    </dl>
                                </div>
                            </div>
                            <div>
                                <div class="hw24-panel-head" style="border:1px solid var(--hw24-border);border-radius:10px 10px 0 0">
                                    <h3>Bestand, Menge und Status</h3>
                                </div>
                                <div style="border:1px solid var(--hw24-border);border-top:0;border-radius:0 0 10px 10px;padding:15px">
                                    <dl class="hw24-fields">
                                        <div class="hw24-field"><dt>Füllmenge / Maßmenge</dt><dd>{{ $massLabel }}</dd><p class="hw24-note">Originalwert, Einheit ungeprüft</p></div>
                                        <div class="hw24-field"><dt>Lieferstatus</dt><dd>{{ $item['lieferstatus_name'] ?: (($fieldReview['fields']['Lieferstatus']['state']??null)==='observed_reference'?$fieldReview['fields']['Lieferstatus']['value']:(!empty($item['lieferstatus_id']) ? 'ID ' . $item['lieferstatus_id'] . ' · Klartext offen' : 'Nicht belegt')) }}</dd></div>
                                        <div class="hw24-field"><dt>Steuerklasse</dt><dd>{{ $item['steuerklasse_name'] ?: (($fieldReview['fields']['Steuerklasse']['state']??null)==='observed_reference'?$fieldReview['fields']['Steuerklasse']['value']:(!empty($item['steuerklasse_id']) ? 'ID ' . $item['steuerklasse_id'] . ' · Klartext offen' : 'Nicht belegt')) }}</dd></div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="hw24-panel-head" style="margin-top:17px;border:1px solid var(--hw24-border);border-radius:10px 10px 0 0">
                            <h3>Preise und Steuergrundlagen</h3><span class="hw24-chip">JTL-Quellwerte</span>
                        </div>
                        <div style="border:1px solid var(--hw24-border);border-top:0;border-radius:0 0 10px 10px;padding:15px">
                            <div class="hw24-price-grid">
                                <div class="hw24-price-box">
                                    <small>EK netto (JTL)</small>
                                    <strong>{{ $item['netto_ek'] !== null ? number_format((float) $item['netto_ek'], 2, ',', '.') : 'Nicht belegt' }}</strong>
                                </div>
                                <div class="hw24-price-box">
                                    <small>VK netto (JTL)</small>
                                    <strong>{{ $item['netto_vk'] !== null ? number_format((float) $item['netto_vk'], 2, ',', '.') : 'Nicht belegt' }}</strong>
                                </div>
                                <div class="hw24-price-box">
                                    <small>VK brutto (JTL)</small>
                                    <strong>{{ $item['brutto_vk'] !== null ? number_format((float) $item['brutto_vk'], 2, ',', '.') : 'Nicht bestätigt' }}</strong>
                                    <p>{{ $item['brutto_vk'] !== null ? 'Quelle: ' . $item['brutto_vk_source'] : 'Gesicherte Steuer-/Preisquelle fehlt' }}</p>
                                </div>
                                <div class="hw24-price-box">
                                    <small>Währung</small>
                                    <strong>{{ $item['waehrung'] ?: 'Offen' }}</strong>
                                    <p>Keine automatische EUR-Annahme</p>
                                </div>
                            </div>
                            <p class="hw24-subtle" style="margin:12px 0 0">Netto- und Bruttopreise werden nicht ohne belegte Steuer- und Quellzuordnung ineinander umgerechnet. Kanalpreise sind keine JTL-Stammverkaufspreise.</p>
                        </div>
                    </div>
                </section>
            @endif

            @if(($lookup['jtl_status']??'')==='current_snapshot' && count($lookup['matches']??[])===1)
            <section class="hw24-panel">
                <div class="hw24-panel-head"><h3>Recherche · direkt am Artikel</h3></div>
                <div class="hw24-panel-body">
                    <div class="hw24-toolbar">
                        <x-filament::button wire:click="queueExistingArticle" wire:loading.attr="disabled" :disabled="$this->verifiedEan()===null">Gemeinsame Recherche anmelden</x-filament::button>
                        <x-filament::button color="gray" wire:click="refreshArticleResearch" wire:loading.attr="disabled" :disabled="$this->verifiedEan()===null">Archivdaten aktualisieren</x-filament::button>
                        <x-filament::button color="gray" wire:click="readAmazon" wire:loading.attr="disabled" :disabled="$this->researchEan()===null">Amazon-Katalog abrufen</x-filament::button>
                        @if(\App\Filament\Pages\QueryAuditWorkbench::canAccess())
                            <x-filament::button color="gray" wire:click="showArticleOffers" wire:loading.attr="disabled">Amazon-Händler &amp; Buy Box</x-filament::button>
                            <x-filament::button color="gray" wire:click="readAiArchive" wire:loading.attr="disabled" :disabled="$this->verifiedEan()===null">KI-Archiv abrufen</x-filament::button>
                        @endif
                    </div>
                    @if($pflegeMessage!=='')<p role="status">{{ $pflegeMessage }}</p>@endif
                    @if($this->verifiedEan()===null && $this->researchEan()!==null)<p>GTIN fehlt. Amazon-Suche nutzt die prüfziffergültige Artikelnummer lediglich als Suchhinweis. Übernahme nur bei bestätigtem GTIN- und ASIN-Treffer.</p>@endif
                    @if($articleCatalog)
                        <details open><summary>Amazon-Katalog · {{ count($articleCatalog['items']??[]) }} angezeigte Treffer</summary>
                            @foreach($articleCatalog['items']??[] as $candidate)
                                <p><strong>{{ $candidate['title'] }}</strong> · ASIN {{ $candidate['asin'] }} · {{ $candidate['manufacturer']?:'Hersteller offen' }} · {{ $candidate['gtin_match']?'GTIN stimmt überein':'GTIN nicht bestätigt' }} · {{ $candidate['pack_kind'] }}</p>
                            @endforeach
                            <p>Eindeutige archivierte Treffer können direkt über „Recherchewerte aufbereiten“ übernommen werden. Kein ungeprüfter Katalogwert wird exportiert.</p>
                        </details>
                    @endif
                    @if(\App\Filament\Pages\QueryAuditWorkbench::canAccess())
                        <details><summary>OpenAI · neue kostenpflichtige Recherche</summary>
                            <p>Vor jedem Auftrag werden Freigabe, gemeinsames Archiv und aktuelles Budget geprüft. Vorhandene Berichte werden nicht doppelt berechnet.</p>
                            <label style="display:flex;align-items:center;gap:8px;margin:10px 0"><input type="checkbox" wire:model.live="confirmArticleAi"> Eine kostenpflichtige Einzelabfrage ausdrücklich bestätigen</label>
                            <x-filament::button wire:click="runArticleAi(false)" wire:loading.attr="disabled" :disabled="!$confirmArticleAi">OpenAI prüfen</x-filament::button>
                            <x-filament::button color="gray" wire:click="runArticleAi(true)" wire:loading.attr="disabled" :disabled="!$confirmArticleAi">OpenAI-Webrecherche</x-filament::button>
                            @if($manualAiResult)<p role="status">{{ $manualAiResult['message']??'' }}</p>@endif
                        </details>
                        <div x-data="{open:false}" x-on:hw24-article-offers-open.window="open=true" x-on:keydown.escape.window="open=false">
                            <div x-show="open" x-cloak style="position:fixed;inset:0;z-index:1000;background:#0f172a99;padding:4vh 3vw;overflow:auto" x-on:click.self="open=false">
                                <section role="dialog" aria-modal="true" aria-label="Amazon-Händlervergleich" style="background:var(--hw24-bg);color:var(--hw24-text);padding:20px;border-radius:12px;width:100%;margin:auto">
                                    <div style="display:flex;justify-content:space-between;align-items:center"><h3 style="font-size:20px;font-weight:700">Amazon-Angebote</h3><button type="button" class="hw24-link" x-on:click="open=false">Schließen ×</button></div>
                                    @include('filament.partials.hub-offer-board',['offers'=>$articleOffers])
                                </section>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
            @endif
            <section class="hw24-panel" aria-label="Datenqualität und Pflegevorschläge">
                <div class="hw24-panel-head"><h3>Datenqualität &amp; Pflegevorschläge</h3></div>
                <div style="padding:16px;display:grid;gap:12px">
                    @if(($lookup['jtl_status']??'')==='current_snapshot' && count($lookup['matches']??[])===1)
                        @php
                            $missingRequired=array_filter(\App\Services\HubFieldPolicy::required(),fn($f)=>!in_array($fieldReview['fields'][$f]['state']??'unverified',['value','observed_reference'],true));
                        @endphp
                        @if($missingRequired)
                            <div role="status" class="hw24-alert">
                                <strong>Pflichtfelder fehlen oder sind nicht bestätigt: {{ implode(', ',$missingRequired) }}</strong>
                                @if(empty($lookup['matches'][0]['ean']))<p>Separate GTIN im aktuellen Spiegel nicht belegt. Eine numerische Artikelnummer ist nicht automatisch die GTIN. Produktidentität zuerst prüfen.</p>@endif
                                @if(empty($lookup['matches'][0]['hersteller']))<p>Hersteller recherchieren und mit vorhandener JTL-Herstellerbezeichnung und ID abgleichen.</p>@endif
                            </div>
                        @endif
                    @endif
                    @if(($fieldReview['ok']??false) && \App\Filament\Pages\QueryAuditWorkbench::canAccess())
                        <section style="border:1px solid #8199b0;border-radius:8px;padding:16px;margin:12px 0">
                            <h3 style="font-size:18px;font-weight:700">Bestandsartikel · Ameise-Aktualisierung</h3>
                            <p>Belegte Ergänzungen und überprüfbare Textkorrekturen. Änderungen vorhandener Texte ausdrücklich einzeln auswählen; kein direkter JTL-Import.</p>
                            <div style="display:flex;flex-wrap:wrap;gap:10px;margin:10px 0">
                                <x-filament::button wire:click="prepareExistingUpdate" wire:loading.attr="disabled">Recherchewerte aufbereiten</x-filament::button>
                                @if($updateOptions)<x-filament::button color="gray" wire:click="selectUniqueUpdates" wire:loading.attr="disabled">Alle eindeutigen Vorschläge auswählen</x-filament::button>@endif
                            </div>
                            @if($updateMessage!=='')<p role="status">{{ $updateMessage }}</p>@endif
                            @if($updateReview)
                                @php
                                    $qualityRows=\App\Services\HubFieldPolicy::review($updateReview,$updateOptions);
                                @endphp
                                <details open><summary>Alle {{ count($qualityRows) }} Zielfelder · Pflichtfeldprüfung und Rechercheabdeckung</summary>
                                    <div class="hw24-table-wrap"><table class="hw24-result-table"><thead><tr><th>Feld</th><th>Aktuell</th><th>Recherche / Status</th></tr></thead><tbody>
                                    @foreach($qualityRows as $f=>$quality)
                                        <tr><td>{{ $f }} @if($quality['required']) <strong>· Pflicht</strong>@endif</td><td style="overflow-wrap:anywhere;max-width:500px">{{ mb_strimwidth((string)$quality['value'],0,180,' …')?:'Nicht belegt' }}</td><td>{{ $quality['candidates']?$quality['candidates'].' Vorschlag/Vorschläge':match($quality['state']){'value','observed_reference'=>'Vorhanden','empty_snapshot'=>'Noch keine belegte Ergänzung','reference'=>'Klartext fehlt',default=>'Zuordnung / Quelle offen'} }} · {{ $quality['source'] }}</td></tr>
                                    @endforeach
                                    </tbody></table></div>
                                </details>
                            @endif
                            @if($updateOptions)
                                <p>Artikelnummer {{ $updateIdentity['artikelnummer'] }} · EAN {{ $updateIdentity['ean'] }}</p>
                                @include('filament.partials.hub-field-decisions',[
                                    'decisionOptions'=>$updateOptions,'decisionModel'=>'updateSelection','decisionKey'=>'existing-update',
                                    'decisionCurrent'=>array_map(fn($v)=>$v['value']!==''?$v['value']:'Nicht befüllt',$fieldReview['fields']??[]),
                                ])
                                <x-filament::button color="gray" wire:click="clearExistingSelections">Alle Entscheidungen zurücksetzen</x-filament::button>
                                <label style="display:flex;gap:8px;align-items:center;margin:12px 0"><input type="checkbox" wire:model.live="confirmUpdateExport"> Ich habe die ausgewählten Werte geprüft und importiere ausschließlich als Aktualisierung vorhandener Artikel.</label>
                                <x-filament::button wire:click="downloadExistingUpdate" wire:loading.attr="disabled"  :disabled="!$confirmUpdateExport">Ameise-Aktualisierungsdatei herunterladen</x-filament::button>
                            @endif
                        </section>
                    @endif
                    @if($fieldReview['ok']??false)
                        <strong>{{ $fieldReview['filled'] }} / {{ $fieldReview['total'] }} geprüfte Zielfelder mit belegtem Wert</strong>
                        <p>Spiegelstand: {{ $fieldReview['snapshot_at']??'nicht belegt' }}. Nicht zugeordnet bedeutet nicht, dass das Feld in JTL fehlt.</p>
                        @if($fieldReview['gross_calculation']??null)
                            <p><strong>Rechnerischer Brutto-VK: {{ $fieldReview['gross_calculation']['value'] }} ({{ $fieldReview['gross_calculation']['rate'] }} %)</strong> · {{ $fieldReview['gross_calculation']['source'] }}</p>
                        @endif
                        @if(is_numeric($fieldReview['average_ek_net']??null))<p>Ø EK netto (JTL fEKNetto): {{ number_format((float)$fieldReview['average_ek_net'],2,',','.') }} · kein bestätigter Lieferanten-EK.</p>@endif
                        @if($seoSuggestions!==[])
                            <details><summary>SEO-Basisvorschläge · {{ count($seoSuggestions) }} Felder</summary>
                                @foreach($seoSuggestions as $label=>$value)<p><strong>{{ $label }}</strong> · {{ $value }}</p>@endforeach
                                <p>Aus belegtem Artikelnamen erzeugt (kostenfreie Textregel, keine neue KI-Abfrage). Noch nicht gespeichert.</p>
                            </details>
                        @endif
                        @if($categoryProposal!==[])<p>Kategorie-Vorschlag: <strong>{{ implode(' / ',$categoryProposal) }}</strong> · manuell prüfen</p>@endif
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <x-filament::button title="Vergleichbare JTL-Artikel und belegte Standardwerte prüfen." wire:click="compareExistingArticle" wire:loading.attr="disabled">Vergleichsartikel prüfen</x-filament::button>
                            <x-filament::button title="Artikel zur gemeinsamen dauerhaften Recherche anmelden; keine sofortige KI-Abfrage." wire:click="queueExistingArticle" wire:confirm="Artikel zur gemeinsamen Recherche anmelden?" wire:loading.attr="disabled">Zur Recherche anmelden</x-filament::button>
                        </div>
                        @if($pflegeMessage!=='')<p role="status">{{ $pflegeMessage }}</p>@endif
                        @if($queueReview!==[])
                            <p>Rechercheauftrag #{{ $queueReview['id'] }} · {{ match($queueReview['status']){'queued'=>'Wartend','leased'=>'In Bearbeitung','retry'=>'Wiederholung geplant','completed'=>'Quellen geprüft','blocked'=>'Prüfung erforderlich',default=>'Unbekannt'} }}</p>
                            <details><summary>Gespeicherte Recherchequellen</summary>
                                @foreach(\App\Services\ScanResearchPresentation::sources($queueReview) as $label=>$value)<p>{{ $label }}: {{ $value }}</p>@endforeach
                            </details>
                        @endif
                        @if($sharedResearchReview!==[])
                            <details open>
                                <summary>Gemeinsame Artikelrecherche · JTL / Amazon</summary>
                                @if(($sharedResearchReview['state']??null)==='identity_conflict')
                                    <p>ASIN-Konflikt: Produktzuordnung zuerst klären. Keine Übernahme.</p>
                                @elseif(($sharedResearchReview['state']??null)==='review')
                                    <p>Katalogarchiv #{{ $sharedResearchReview['catalog_id'] }} · {{ $sharedResearchReview['at']??'Zeitpunkt offen' }} UTC</p>
                                    @forelse($sharedResearchReview['differences'] as $label=>$difference)
                                        <p><strong>{{ $label }}</strong><br>JTL: {{ $difference['old']!==''?$difference['old']:'Kein belegter Spiegelwert' }}<br>Amazon: {{ $difference['candidate'] }} · {{ $difference['source'] }} · manuell prüfen</p>
                                    @empty<p>Keine Abweichung in den bisher verglichenen Katalogfeldern.</p>@endforelse
                                    @if(isset($sharedResearchReview['ai']))
                                        <p><strong>Gemeinsames KI-Archiv:</strong> {{ match($sharedResearchReview['ai']['state']){'verified_review'=>'Geprüfter Bericht','missing'=>'Kein gemeinsamer Bericht','stale'=>'Abweichende Recherchegrundlage','identity_conflict'=>'Identitätskonflikt','review'=>'Manuelle Prüfung',default=>'Nicht bestätigt'} }} @if(isset($sharedResearchReview['ai']['id'])) · #{{ $sharedResearchReview['ai']['id'] }} @endif</p>
                                                @if(($sharedResearchReview['ai']['content']['state']??null)==='review')
                                                    <details><summary>KI-Texte und belegte Artikelattribute · Prüfvorschläge</summary>
                                                        @foreach($sharedResearchReview['ai']['content']['fields']??[] as $label=>$value)<p><strong>{{ $label }}</strong>: {{ $value }}</p>@endforeach
                                                        @foreach($sharedResearchReview['ai']['content']['attributes']??[] as $channel=>$attributes)
                                                            <p><strong>{{ match($channel){'article'=>'Artikelmerkmale','shopify'=>'Shopify','ebay'=>'eBay',default=>'Attribute'} }}</strong></p>
                                                            @foreach($attributes as $label=>$value)<p>{{ $label }}: {{ $value }}</p>@endforeach
                                                        @endforeach
                                                        <p>Keine Veröffentlichung oder direkte Änderung eines Verkaufskanals.</p>
                                                    </details>
                                                @endif
                                        @if(!empty($sharedResearchReview['ai']['title_hint']))<p>KI-Titelvorschlag: {{ $sharedResearchReview['ai']['title_hint'] }} · noch nicht übernommen</p>@endif
                                        @if(!empty($sharedResearchReview['ai']['group_hint']))<p>KI-Warengruppenvorschlag: {{ $sharedResearchReview['ai']['group_hint'] }} · noch nicht übernommen</p>@endif
                                    @endif
                                    @if(isset($sharedResearchReview['market_price']))
                                        <p>Marktpreisarchiv #{{ $sharedResearchReview['market_price']['id'] }} · {{ $sharedResearchReview['market_price']['at'] }} UTC</p>
                                        @foreach(['buybox'=>'Buy Box','lowest'=>'Niedrigste Angebote'] as $key=>$label)
                                            @foreach($sharedResearchReview['market_price'][$key] as $price)
                                                @if(isset($price['landed_price']['amount'],$price['landed_price']['currency']))<p>{{ $label }}: {{ $price['landed_price']['amount'] }} {{ $price['landed_price']['currency'] }}</p>@endif
                                            @endforeach
                                        @endforeach
                                        <p>Marktpreise sind keine eigenen Verkaufspreise.</p>
                                    @endif
                                @else<p>Kein aktuell lesbares, eindeutig zugeordnetes Katalogarchiv. Keine neue Anbieterabfrage ausgelöst.</p>@endif
                            </details>
                        @endif
                        @if($peerReview!==[])
                            @if($peerReview['ok']??false)
                                <p>{{ $peerReview['peer_count'] }} Vergleichsartikel gleicher Warengruppe und Hersteller.</p>
                                @forelse($peerReview['suggestions'] as $label=>$value)
                                    <p>{{ $label }}: {{ $value['value'] }} · {{ $value['support'] }}/{{ $value['sample_count'] }} · manuelle Prüfung</p>
                                @empty<p>Keine ausreichend belegten Vorschläge für die bisher unterstützten Standardfelder.</p>@endforelse
                            @else<p>{{ $peerReview['message'] }}</p>@endif
                        @endif
                        <details>
                            <summary>Alle geprüften Felder anzeigen</summary>
                            <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),1fr));gap:12px;overflow-wrap:anywhere;margin-top:12px">
                                @foreach($fieldReview['fields'] as $label=>$info)
                                    <div><dt><strong>{{ $label }}</strong></dt><dd>{{ match($info['state']){'value','observed_reference','reference'=>$info['value'],'empty_snapshot'=>'Im Spiegel nicht belegt','missing_source'=>'Quelle bekannt, Spiegelwert fehlt',default=>'Quellzuordnung noch offen'} }}</dd><small>{{ $info['source'] }}</small></div>
                                @endforeach
                            </dl>
                        </details>
                        <p>Bestehende JTL-Artikel werden hier noch nicht verändert. Dauerhaft gespeicherte Änderungsvorschläge und Update-Export folgen separat.</p>
                    @else
                        <p>Für die Feldprüfung ist ein eindeutiger aktueller JTL-Artikel erforderlich.</p>
                    @endif
                </div>
             </section>
             <section class="hw24-panel" aria-labelledby="hw24-source-heading">
                <div class="hw24-panel-head">
                    <h3 id="hw24-source-heading">03 · JTL-Originalfelder und Kontopreise</h3>
                    <span class="hw24-chip">Quelle separat prüfen</span>
                </div>
                <div class="hw24-panel-body">
                    <p class="hw24-description">Die Originalspalten bleiben im MariaDB-Spiegel. Ein manueller Klick lädt maximal 80 Felder pro Seite über eine geschützte GET-API. Keine Schreiboperationen an JTL, SQL Server oder WMS.</p>
                    <div class="hw24-toolbar">
                        <x-filament::button title="Originalwerte und gespeicherte Kanalpreise aus dem JTL-Spiegel lesen." type="button" wire:click="readJtlSources" wire:loading.attr="disabled" wire:target="readJtlSources"
                            :disabled="count($lookup['matches'] ?? []) !== 1 || ($lookup['jtl_status'] ?? '') !== 'current_snapshot'">
                            Originalfelder und Kanalpreise lesen
                        </x-filament::button>
                        <span class="hw24-subtle">Nur beim eindeutigen, aktuellen JTL-Artikel verfügbar</span>
                    </div>
                    @if($sourceState !== [])
                        @if(!($sourceState['ok'] ?? false))
                            <p class="hw24-alert" role="status">{{ $sourceState['reason'] ?? 'Quelle derzeit nicht verfügbar' }}</p>
                        @else
                            <div class="hw24-meta">
                                <span class="hw24-chip hw24-good">{{ count($sourceRows) }} / {{ $sourceState['field_count'] ?? 0 }} Felder geladen</span>
                                <span>Snapshot: {{ $sourceState['snapshot_at'] ?? 'unbekannt' }} UTC</span>
                            </div>
                            <p class="hw24-description" style="margin-top:13px">Kanalangebotspreise bleiben unbekannt, bis Konto, Preisart, Währung, Status und Quelle unabhängig belegt sind.</p>
                            <div class="hw24-price-grid">
                                @foreach(($sourceState['prices'] ?? []) as $price)
                                    <div class="hw24-price-box">
                                        <small>{{ $price['channel'] }} · {{ $price['account'] }}</small>
                                        <strong>{{ is_numeric($price['value'] ?? null) ? number_format((float) $price['value'], 4, ',', '.') : 'Nicht verfügbar' }}</strong>
                                        <p>{{ $price['meaning'] }} · {{ $price['status'] }} · Quelle: {{ $price['source'] ?: 'offen' }}</p>
                                        <p>Währung nicht bestätigt</p>
                                    </div>
                                @endforeach
                            </div>
                            <label class="hw24-source-filter" for="hw24-source-search">
                                Geladene SQL-Felder durchsuchen
                                <input id="hw24-source-search" class="hw24-input" type="search" wire:model.live.debounce.300ms="sourceQuery"
                                    placeholder="z. B. kLieferStatus, Steuerklasse, fVKNetto">
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
                            <div class="hw24-source-scroll">
                                <table class="hw24-source-table">
                                    <thead><tr><th>SQL-Quelle</th><th>Originalfeld</th><th>Originalwert</th></tr></thead>
                                    <tbody>
                                        @forelse($visibleSources as $row)
                                            <tr>
                                                <td>{{ $row['source'] }} ({{ $row['row'] }})</td>
                                                <td><code>{{ $row['field'] }}</code>
                                                    @if(!empty($row['label'])) <div class="hw24-muted-label">{{ $row['label'] }}</div> @endif
                                                </td>
                                                <td>
                                                    @if($row['value'] === null)
                                                        <span class="hw24-muted-label">Nicht belegt</span>
                                                    @elseif(mb_strlen($row['value']) > 220)
                                                        {{ mb_substr($row['value'], 0, 180) }}…
                                                        <details><summary>Gesamten geladenen Text anzeigen</summary><pre>{{ $row['value'] }}</pre></details>
                                                    @else
                                                        {{ $row['value'] === '' ? '(leer)' : $row['value'] }}
                                                    @endif
                                                    @if(!empty($row['truncated']))<div class="hw24-muted-label">Vorschau gekürzt, Original im SQL-Spiegel erhalten</div>@endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3">Kein Feld in den geladenen Seiten gefunden.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if($sourceState['has_more'] ?? false)
                                <div class="hw24-toolbar">
                                    <x-filament::button title="Weitere Originalfelder des eindeutig zugeordneten Artikels lesen." type="button" color="gray" wire:click="loadMoreJtlSources"
                                        wire:loading.attr="disabled" wire:target="loadMoreJtlSources">
                                        Weitere Originalfelder laden
                                    </x-filament::button>
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </section>

            <section class="hw24-channel-layout" aria-label="Externe Datenquellen">
                <div class="hw24-panel hw24-channel">
                    <h3>04 · Amazon Selling Partner API</h3>
                    <p>Katalogauskünfte sind nicht automatisch eigene aktive Händlerangebote. Eine Live-Abfrage wird ausschließlich manuell gestartet.</p>
                    <div class="hw24-toolbar">
                        <x-filament::button title="Amazon-Katalog für die verifizierte EAN abrufen; möglicher Anbieteraufruf." type="button" wire:click="readAmazon" wire:loading.attr="disabled" wire:target="readAmazon"
                            :disabled="$this->verifiedEan() === null">Amazon jetzt abfragen</x-filament::button>
                        <span class="hw24-chip hw24-warning">Manueller API-Aufruf</span>
                    </div>
                    @if($amazon !== [])
                        <div class="hw24-meta" aria-live="polite">
                            <span class="hw24-chip {{ $amazon['ok'] ? 'hw24-good' : 'hw24-warning' }}">{{ $amazon['ok'] ? 'API hat geantwortet' : 'Nicht verfügbar' }}</span>
                            <span>HTTP {{ $amazon['http_status'] ?? '—' }}</span>
                            <span>Quelle: {{ $amazon['source'] ?? 'unbekannt' }}</span>
                            <span>Treffer: {{ $amazon['count'] ?? 'unbekannt' }}</span>
                        </div>
                        @if($amazon['error'] !== '')<p class="hw24-alert">{{ $amazon['error'] }} ({{ $amazon['stage'] }})</p>@endif
                    @endif
                </div>
                <div class="hw24-panel hw24-channel">
                    <h3>05 · OpenAI · gespeichertes KI-Archiv</h3>
                    <p>Nur vorhandene Berichte lesen. Es wird keine kostenpflichtige KI-Analyse erzeugt.</p>
                    <div class="hw24-toolbar">
                        <x-filament::button title="Vorhandene KI-Berichte ohne neue KI-Kosten lesen." type="button" wire:click="readAiArchive" wire:loading.attr="disabled" wire:target="readAiArchive"
                            :disabled="$this->verifiedEan() === null">KI-Archiv lesen</x-filament::button>
                        <span class="hw24-chip">Nur GET</span>
                    </div>
                    @if($ai !== [])
                        <div class="hw24-meta" aria-live="polite">
                            <span class="hw24-chip {{ $ai['ok'] ? 'hw24-good' : 'hw24-warning' }}">{{ $ai['ok'] ? 'Archiv erreichbar' : 'Archiv nicht verfügbar' }}</span>
                            <span>HTTP {{ $ai['http_status'] ?? '—' }}</span>
                            <span>Einträge: {{ $ai['count'] ?? 'unbekannt' }}</span>
                        </div>
                        @if($ai['error'] !== '')<p class="hw24-alert">{{ $ai['error'] }}</p>@endif
                    @endif
                </div>
            </section>
            <footer class="hw24-foot">
                <strong>Nächste Module: Shopify, eBay, WMS und Kanalpreisvergleich.</strong>
                Aktive Verkaufspreise und Bestände werden erst nach erfolgreicher Konten- und Quellprüfung angezeigt. Alle vorhandenen Lesezugriffe bleiben von Schreibfunktionen getrennt.
            </footer>
        </div>
    </div>
</x-filament-panels::page>
