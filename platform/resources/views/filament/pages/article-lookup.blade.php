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
            .hw24-editor .hw24-panel{min-width:0;background:var(--hw24-bg);border:1px solid var(--hw24-border);border-radius:15px;overflow:hidden;box-shadow:0 1px 2px #0f172a0a}
            .hw24-editor .hw24-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:15px 18px;border-bottom:1px solid var(--hw24-border);background:var(--hw24-soft)}
            .hw24-editor .hw24-panel-head h3{font-size:15px;line-height:1.4;font-weight:750;margin:0}
            .hw24-editor .hw24-panel-body{padding:18px;min-width:0;container-type:inline-size}
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
            .hw24-editor .hw24-result-table{width:100%;max-width:100%;min-width:0;table-layout:fixed;border-collapse:collapse;font-size:12px;text-align:left}
            .hw24-editor .hw24-table-wrap{width:100%;max-width:100%;overflow-x:hidden}
            .hw24-editor .hw24-result-table th{background:var(--hw24-soft);color:var(--hw24-muted);font-size:11px;text-transform:uppercase;letter-spacing:.04em;font-weight:700;padding:11px 12px;white-space:normal;overflow-wrap:anywhere}
            .hw24-editor .hw24-result-table td{padding:12px;border-top:1px solid var(--hw24-border);vertical-align:top;overflow-wrap:anywhere}
            .hw24-editor .hw24-result-table td strong{font-weight:750}
            .hw24-editor .hw24-table-small{font-size:11px;color:var(--hw24-muted);margin-top:4px}
            .hw24-editor .hw24-nowarp{white-space:normal;overflow-wrap:anywhere}
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
            .hw24-editor .hw24-source-table{width:100%;max-width:100%;min-width:0;table-layout:fixed;border-collapse:collapse;font-size:12px;text-align:left}
            .hw24-editor .hw24-source-table th{text-align:left;background:var(--hw24-soft);padding:10px 12px;color:var(--hw24-muted)}
            .hw24-editor .hw24-source-table td{vertical-align:top;padding:11px 12px;border-top:1px solid var(--hw24-border);overflow-wrap:anywhere;max-width:440px}
            .hw24-editor .hw24-source-scroll{width:100%;max-width:100%;max-height:530px;overflow-x:hidden;overflow-y:auto;border:1px solid var(--hw24-border);border-radius:9px}
            .hw24-editor .hw24-source-table pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:11px}
            .hw24-editor .hw24-muted-label{color:var(--hw24-muted);font-size:11px;line-height:1.45}
            .hw24-editor .hw24-error{color:#b91c1c;font-size:12px;margin-top:8px}
            .hw24-editor .hw24-foot{background:var(--hw24-soft);border:1px solid var(--hw24-border);border-radius:12px;padding:15px 18px;font-size:12px;line-height:1.65;color:var(--hw24-muted)}
            .hw24-editor .hw24-foot strong{color:var(--hw24-text)}
            @media(max-width:760px){.hw24-editor .hw24-section-layout,.hw24-editor .hw24-channel-layout{grid-template-columns:minmax(0,1fr)}.hw24-editor .hw24-head{padding:19px}.hw24-editor .hw24-head h2{font-size:20px}.hw24-editor .hw24-panel-body{padding:15px}}
            @media(max-width:460px){.hw24-editor .hw24-price-grid{grid-template-columns:minmax(0,1fr)}.hw24-editor .hw24-fields{gap:11px}.hw24-editor .hw24-field dd{font-size:12px}}
            
/* Quellwerte und Treffer: nie horizontales Scrollen, auch bei ausgeklapptem Filament-Menü. */
.hw24-editor .hw24-result-table td,.hw24-editor .hw24-source-table td,
.hw24-editor .hw24-source-table th,.hw24-editor .hw24-result-table th{overflow-wrap:anywhere;min-width:0}
@container (max-width: 760px){
 .hw24-editor .hw24-source-scroll{max-height:none;overflow-x:hidden;overflow-y:visible}
 .hw24-editor .hw24-result-table,.hw24-editor .hw24-source-table,
 .hw24-editor .hw24-result-table tbody,.hw24-editor .hw24-source-table tbody{display:block;width:100%}
 .hw24-editor .hw24-result-table thead,.hw24-editor .hw24-source-table thead{position:absolute;width:1px;height:1px;clip:rect(0 0 0 0);clip-path:inset(50%);overflow:hidden;white-space:nowrap}
 .hw24-editor .hw24-result-table tr,.hw24-editor .hw24-source-table tr{display:block;border-bottom:1px solid var(--hw24-border);padding:9px 12px}
 .hw24-editor .hw24-result-table tr:last-child,.hw24-editor .hw24-source-table tr:last-child{border-bottom:0}
 .hw24-editor .hw24-result-table td,.hw24-editor .hw24-source-table td{display:grid;grid-template-columns:minmax(110px,33%) minmax(0,1fr);gap:8px;border:0;padding:8px 3px;max-width:100%;align-items:start}
 .hw24-editor .hw24-result-table td::before,.hw24-editor .hw24-source-table td::before{content:attr(data-label);color:var(--hw24-muted);font-size:11px;font-weight:750}
 .hw24-editor .hw24-result-table td > *,.hw24-editor .hw24-source-table td > *{min-width:0}
 .hw24-editor .hw24-source-table td[colspan]{display:block}
 .hw24-editor .hw24-source-table td[colspan]::before{content:none}
}
@container (max-width: 390px){
 .hw24-editor .hw24-result-table td,.hw24-editor .hw24-source-table td{grid-template-columns:minmax(0,1fr);gap:3px}
}
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
                <h2>Artikelzentrale</h2>
                <p>Stammdaten, Bestand und Preise aus dem JTL-Spiegel. Originalquellen und Marktplatzangebote bleiben nachvollziehbar getrennt.</p>
            </div>
            <a class="hw24-link" href="{{ route('filament.admin.pages.dashboard') }}">Zum Management-Cockpit</a>
        </header>

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
            <section class="hw24-panel" aria-labelledby="hw24-search-heading">
                <div class="hw24-panel-head"><h3 id="hw24-search-heading">01 · Artikel finden</h3><span class="hw24-chip">JTL · nur lesend</span></div>
                <div class="hw24-panel-body">
                    <p class="hw24-description">Suche mit EAN, HAN oder JTL-Artikelnummer. Die Abfrage liest ausschließlich den bestehenden JTL-MariaDB-Spiegel.</p>
                    <form wire:submit="findArticle" class="hw24-form">
                        <label class="hw24-search-label" for="hw24-article-identifier">
                            EAN, HAN oder JTL-Artikelnummer
                            <input id="hw24-article-identifier" class="hw24-input" type="search" maxlength="64" wire:model="identifier"
                                autocomplete="off" placeholder="z. B. 3474630337398">
                        </label>
                        <div class="hw24-call-to-action">
                            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="findArticle">Artikel laden</x-filament::button>
                        </div>
                    </form>
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
                                                <td data-label="Artikel"><strong>{{ $row['artikelname'] ?: 'Ohne Bezeichnung' }}</strong>
                                                    <div class="hw24-table-small">Nr. {{ $row['artikelnummer'] ?: 'unbekannt' }} · {{ $row['hersteller'] ?: 'Hersteller offen' }}</div>
                                                </td>
                                                <td data-label="EAN" class="hw24-nowarp">{{ $row['ean'] }}</td>
                                                <td data-label="Bestand JTL">{{ is_numeric($row['bestand']) ? number_format((float) $row['bestand'], 0, ',', '.') : 'Nicht belegt' }}</td>
                                                <td data-label="VK netto JTL">{{ is_numeric($row['netto_vk']) ? number_format((float) $row['netto_vk'], 2, ',', '.') : 'Nicht belegt' }}
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
              <div class="hw24-price-grid">
               <div class="hw24-price-box">
                <small>📦 JTL verfügbar</small>
                <strong>{{ ($articleRelated['stock']['available']??null)!==null ? number_format((float)$articleRelated['stock']['available'],0,',','.') : 'Nicht synchronisiert' }}</strong>
                <p>In Aufträgen: {{ ($articleRelated['stock']['in_orders']??null)!==null ? number_format((float)$articleRelated['stock']['in_orders'],0,',','.') : 'nicht belegt' }} · Zulauf: {{ ($articleRelated['stock']['incoming']??null)!==null ? number_format((float)$articleRelated['stock']['incoming'],0,',','.') : 'nicht belegt' }}</p>
               </div>
               <div class="hw24-price-box">
                <small>🏬 WMS-Fächer</small>
                <strong>{{ ($articleRelated['wms']['confirmed']??false)===true ? ($articleRelated['wms']['count']??0).' Lagerplätze' : 'Spiegel nicht bestätigt' }}</strong>
                <p>Gesperrte Fächer: {{ ($articleRelated['wms']['confirmed']??false)===true ? ($articleRelated['wms']['locked_bins']??0) : 'unbekannt' }} · Fachdetails im WMS-Bereich</p>
               </div>
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
                                        <div class="hw24-field"><dt>JTL-Lagerbestand</dt><dd>{{ is_numeric($item['bestand']) ? number_format((float) $item['bestand'], 0, ',', '.') : 'Nicht belegt' }}</dd><p class="hw24-note">Gespiegelter Bestand, nicht lieferbare Menge</p></div>
                                        <div class="hw24-field"><dt>Füllmenge / Maßmenge</dt><dd>{{ $massLabel }}</dd><p class="hw24-note">Originalwert, Einheit ungeprüft</p></div>
                                        <div class="hw24-field"><dt>Lieferstatus</dt><dd>{{ $item['lieferstatus_name'] ?: (!empty($item['lieferstatus_id']) ? 'ID ' . $item['lieferstatus_id'] . ' · Klartext offen' : 'Nicht belegt') }}</dd></div>
                                        <div class="hw24-field"><dt>Steuerklasse</dt><dd>{{ $item['steuerklasse_name'] ?: (!empty($item['steuerklasse_id']) ? 'ID ' . $item['steuerklasse_id'] . ' · Klartext offen' : 'Nicht belegt') }}</dd></div>
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

            <section class="hw24-panel" aria-labelledby="hw24-source-heading">
                <div class="hw24-panel-head">
                    <h3 id="hw24-source-heading">03 · JTL-Originalfelder und Kontopreise</h3>
                    <span class="hw24-chip">Quelle separat prüfen</span>
                </div>
                <div class="hw24-panel-body">
                    <p class="hw24-description">Die Originalspalten bleiben im MariaDB-Spiegel. Ein manueller Klick lädt maximal 80 Felder pro Seite über eine geschützte GET-API. Keine Schreiboperationen an JTL, SQL Server oder WMS.</p>
                    <div class="hw24-toolbar">
                        <x-filament::button type="button" wire:click="readJtlSources" wire:loading.attr="disabled" wire:target="readJtlSources"
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
                                                <td data-label="SQL-Quelle">{{ $row['source'] }} ({{ $row['row'] }})</td>
                                                <td data-label="Originalfeld"><code>{{ $row['field'] }}</code>
                                                    @if(!empty($row['label'])) <div class="hw24-muted-label">{{ $row['label'] }}</div> @endif
                                                </td>
                                                <td data-label="Originalwert">
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
                                    <x-filament::button type="button" color="gray" wire:click="loadMoreJtlSources"
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
                        <x-filament::button type="button" wire:click="readAmazon" wire:loading.attr="disabled" wire:target="readAmazon"
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
                        <x-filament::button type="button" wire:click="readAiArchive" wire:loading.attr="disabled" wire:target="readAiArchive"
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
