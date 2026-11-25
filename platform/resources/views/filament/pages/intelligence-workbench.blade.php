<x-filament-panels::page>
<div class="hw24-intelligence">
<style>
.hw24-intelligence{--edge:#dce5ee;--ink:#182c40;--muted:#566c82;color:var(--ink);font:inherit}
.hw24-intelligence *{box-sizing:border-box}
.hw24-intelligence .hero{background:linear-gradient(113deg,#143048,#1c5575);border-radius:16px;color:#fff;padding:24px 27px}
.hw24-intelligence .hero small{color:#bce0f8;font-size:11px;letter-spacing:.12em;font-weight:800}
.hw24-intelligence .hero h2{font-size:23px;line-height:1.3;font-weight:800;margin:6px 0}
.hw24-intelligence .hero p{color:#d9ebfa;font-size:13px;line-height:1.65}
.hw24-intelligence .cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px;margin-top:16px}
.hw24-intelligence .card{background:#fff;border:1px solid var(--edge);border-radius:14px;overflow:hidden;min-width:0}
.hw24-intelligence .head{padding:14px 18px;background:#f4f8fc;border-bottom:1px solid var(--edge);font-size:15px;font-weight:800}
.hw24-intelligence .body{padding:17px 18px}
.hw24-intelligence .full{grid-column:1/-1}
.hw24-intelligence .minor{color:var(--muted);font-size:12px;line-height:1.6;margin:0 0 12px}
.hw24-intelligence .row{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:12px 0}
.hw24-intelligence .input{width:min(100%,480px);background:#fff;border:1px solid #b9ccde;border-radius:9px;padding:10px 12px;min-height:42px;color:#192c42}
.hw24-intelligence .action{display:inline-flex;align-items:center;padding:10px 13px;background:#174f7b;color:#fff;border:0;border-radius:9px;font-size:12px;font-weight:800;cursor:pointer;min-height:40px;text-decoration:none}
.hw24-intelligence .action.secondary{background:#e7f1fa;color:#184871;border:1px solid #bfd3e8}
.hw24-intelligence .action:hover{filter:brightness(.93)}
.hw24-intelligence .action:disabled{opacity:.45;cursor:not-allowed}
.hw24-intelligence .badge{border-radius:999px;padding:5px 9px;background:#e8f6ed;color:#13633e;font-size:11px;font-weight:750}
.hw24-intelligence .warning{border:1px solid #f0d8a7;background:#fff8e9;color:#824b10;border-radius:10px;padding:12px 14px;font-size:12px;line-height:1.65;margin-top:12px}
.hw24-intelligence .ok{border-color:#b2e3c6;background:#f1fbf4;color:#17633f}
.hw24-intelligence .item{border:1px solid var(--edge);border-radius:11px;padding:14px;margin-top:11px}
.hw24-intelligence .item h4{font-size:14px;font-weight:800;line-height:1.45;margin:0 0 6px}
.hw24-intelligence .item p{font-size:12px;line-height:1.6;color:var(--muted);margin:5px 0}
.hw24-intelligence .item a{color:#1a5c8d;font-size:12px;text-decoration:underline}
.hw24-intelligence .inline-label{display:flex;align-items:flex-start;gap:9px;font-size:12px;line-height:1.6;margin:9px 0}
.hw24-intelligence .inline-label input{margin-top:5px}
.hw24-intelligence .divider{border-top:1px solid var(--edge);padding-top:12px;margin-top:14px}
.hw24-intelligence .technical{font-size:11px;color:var(--muted)}
.hw24-intelligence .metric{display:inline-block;border-radius:10px;background:#edf5fb;padding:9px 13px;color:#174e7c;font-weight:800;font-size:12px}
.hw24-intelligence .grid-amounts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:11px}
.hw24-intelligence .grid-amounts .item{margin:0}
.hw24-intelligence button:focus-visible,.hw24-intelligence a:focus-visible{outline:3px solid #60a5fa;outline-offset:2px}
@media(max-width:820px){.hw24-intelligence .cards{grid-template-columns:minmax(0,1fr)}.hw24-intelligence .hero{padding:19px}.hw24-intelligence .hero h2{font-size:21px}}
html.dark .hw24-intelligence{--edge:#334155;--ink:#f1f5f9;--muted:#c4d0dd}
html.dark .hw24-intelligence .card{background:#172334}
html.dark .hw24-intelligence .head{background:#213047}
html.dark .hw24-intelligence .item{background:#1b2b40}
html.dark .hw24-intelligence .item h4{color:#f8fafc}
</style>
<header class="hero">
    <small>HW24 · INTELLIGENCE · KATALOG / BERICHTSARCHIV</small>
    <h2>KI-Abfragen und Amazon-Recherche</h2>
    <p>JTL-Identität, Amazon-Katalog, konkrete Angebotswerte und gespeicherte KI-Berichte. Jede externe Anfrage wird einzeln und bewusst gestartet. Neue KI-Kosten nur nach separater Freigabe.</p>
</header>
<div class="cards">
    <section class="card full" aria-labelledby="intel-find">
        <div class="head" id="intel-find">01 · JTL-Artikel als Recherchegrundlage</div>
        <div class="body">
            <form wire:submit="findArticle">
                <label for="intel-ean" class="minor" style="display:block;font-weight:750;margin-bottom:6px">EAN, HAN oder JTL-Artikelnummer</label>
                <div class="row">
                    <input class="input" id="intel-ean" type="search" wire:model="identifier" maxlength="64" autocomplete="off" placeholder="3474630337398">
                    <button type="submit" class="action" wire:loading.attr="disabled" wire:target="findArticle">Artikel aus JTL laden</button>
                </div>
            </form>
            <button type="button" class="action secondary" style="margin-top:8px" wire:click="useUnidentifiedEan">Unbekannte EAN direkt recherchieren</button>
            @if($lookupState !== [])
                <p role="status" class="warning {{ ($lookupState['ok'] ?? false) ? 'ok' : '' }}">
                    {{ $lookupState['message'] }}
                    @if(isset($lookupState['http'])) · HTTP {{ $lookupState['http'] }} @endif
                </p>
            @endif
            @if($article !== [])
                <div class="item">
                    <h4>{{ $article['name'] ?: 'Noch nicht identifizierter Artikel' }}</h4>
                    <p>EAN {{ $article['ean'] }} · JTL-ID {{ $article['jtl_id'] ?: 'offen' }} · Artikelnummer {{ $article['sku'] ?: 'offen' }}</p>
                    <p>Hersteller {{ $article['brand'] ?: 'offen' }} · Warengruppe {{ $article['group'] ?: 'offen' }} · JTL-ASIN {{ $article['asin'] ?: 'offen' }}</p>
                    <span class="badge">{{ ($article['jtl_status']??'')==='current_snapshot'?'Aktueller JTL-Snapshot bestätigt':'Identität noch nicht bestätigt' }}</span>
                    @if(($article['jtl_status']??'')!=='current_snapshot')
                        <label class="minor">Produktname laut Verpackung oder geprüftem Katalogtreffer
                            <input class="input" type="text" maxlength="600" wire:model="researchName">
                        </label>
                    @endif
                </div>
            @endif
        </div>
    </section>
    <section class="card" aria-labelledby="intel-amazon">
        <div class="head" id="intel-amazon">02 · Amazon-Katalog und gespeicherte Treffer</div>
        <div class="body">
            <p class="minor">Eine Katalogprüfung kann den sechs Stunden gültigen HW24-Cache verwenden oder eine echte Amazon-SP-API-Abfrage mit serverseitiger Archivierung auslösen. Nur durch bewussten Klick.</p>
            <div class="row">
                <button type="button" class="action" wire:click="loadAmazonCatalog" wire:loading.attr="disabled" wire:target="loadAmazonCatalog" @disabled($article === [])>Amazon nach EAN prüfen</button>
                <button type="button" class="action secondary" wire:click="searchAmazonByName" wire:loading.attr="disabled" wire:target="searchAmazonByName" @disabled($article === [])>Zusätzlich nach Artikelname suchen</button>
            </div>
            @if($catalog !== [])
                <p class="warning {{ ($catalog['ok'] ?? false) ? 'ok' : '' }}" role="status">
                    {{ $catalog['ok'] ? 'Katalogantwort gespeichert / geladen' : 'Katalog nicht verfügbar' }}
                    · HTTP {{ $catalog['http'] ?? '—' }} · {{ $catalog['count'] ?? 'unbekannt' }} Treffer
                    · Quelle {{ $catalog['source'] }} · Archiv-ID {{ $catalog['snapshot_id'] ?? 'unbekannt' }}
                    @if($catalog['fetched_at']) · Stand {{ $catalog['fetched_at'] }} UTC @endif
                </p>
                @if($catalog['error']) <p class="minor">Fehlerkennung {{ $catalog['error'] }}</p> @endif
                @foreach($catalog['items'] as $item)
                    <div class="item" wire:key="hw24-catalog-{{ $item['asin'] }}">
                        <h4>{{ $item['title'] ?: 'Amazon-Titel nicht belegt' }}</h4>
                        <p>ASIN {{ $item['asin'] }} · Marke {{ $item['brand'] ?: 'unbekannt' }} · Hersteller {{ $item['manufacturer'] ?: 'unbekannt' }}</p>
                        <div class="row">
                            <a href="https://www.amazon.de/dp/{{ $item['asin'] }}" target="_blank" rel="noopener noreferrer">Amazon-Produkt ansehen</a>
                            <button type="button" class="action secondary" wire:click="loadAmazonPricing('{{ $item['asin'] }}')" wire:loading.attr="disabled" wire:target="loadAmazonPricing">Öffentliche Angebotspreise prüfen</button>
                        </div>
                        <p>ASIN, Packung, GTIN und Artikelidentität müssen gesondert bestätigt werden. Kein Import in JTL.</p>
                    </div>
                @endforeach
            @endif
            <div class="divider">
                <h3 style="font-size:14px;font-weight:800">Gespeicherte Amazon-Katalog-Snapshots</h3>
                <p class="minor">Historische Katalogrecherchen anzeigen, ohne erneut die Amazon-SP-API aufzurufen. Die zusätzliche Archiv-GET-API muss separat auf der geschützten LIVE-Domain abgenommen werden.</p>
                <button type="button" class="action secondary" wire:click="loadAmazonArchive"
                    wire:loading.attr="disabled" wire:target="loadAmazonArchive"
                    @disabled($article===[])>Amazon-Archiv laden</button>
                @if($catalogArchive !== [])
                    <p role="status" class="warning {{ ($catalogArchive['ok']??false) ? 'ok' : '' }}">
                        {{ $catalogArchive['message'] }} · HTTP {{ $catalogArchive['http'] ?? '—' }}
                    </p>
                    @foreach(($catalogArchive['snapshots']??[]) as $snapshot)
                        <div class="item" wire:key="hw24-amz-archive-{{ $snapshot['id'] }}">
                            <h4>Amazon-Katalogarchiv #{{ $snapshot['id'] }}</h4>
                            <p>Gespeichert: {{ $snapshot['date'] }} UTC · Katalogtreffer {{ $snapshot['count'] ?? '?' }} · Amazon.de</p>
                            <p>Kataloghistorie, keine bestätigten aktiven Händlerangebote oder individuellen Verkaufspreise.</p>
                        </div>
                    @endforeach
                @endif
            </div>
            @if($keywords !== [])
                <div class="divider">
                    <strong>Zusätzliche Kandidaten nach Artikelname · {{ $keywords['count'] ?? 'unbekannt' }}</strong>
                    <p class="minor">Quelle {{ $keywords['source'] }} · HTTP {{ $keywords['http'] ?? '—' }}</p>
                    @foreach($keywords['items'] as $item)
                        <div class="item" wire:key="hw24-keyword-{{ $item['asin'] }}">
                            <h4>{{ $item['title'] }}</h4>
                            <p>ASIN {{ $item['asin'] }} · {{ $item['ean_match'] ? 'EAN im Kandidaten nachgewiesen' : 'EAN-Identität NICHT bestätigt' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
            @if($pricing !== [])
                <div class="divider">
                    <strong>Amazon-Angebotsdaten · {{ $pricing['asin'] ?? 'unbekannt' }}</strong>
                    <p class="minor">HTTP {{ $pricing['http'] ?? '—' }} · {{ $pricing['offers'] ?? 'unbekannt' }} gemeldete Angebote. Keine eigenen Seller-Angebote verifiziert.</p>
                    @foreach($pricing['prices'] ?? [] as $price)
                        <div class="item">
                            <h4>{{ $price['type'] }} · {{ number_format($price['amount'],2,',','.') }} {{ $price['currency'] }}</h4>
                            <p>{{ $price['fulfillment'] ?: 'Fulfillment unbekannt' }}</p>
                        </div>
                    @endforeach
                    @if(isset($pricing['archive']))
                        @include('filament.partials.amazon-price-history',['archive'=>$pricing['archive']])
                    @endif
                    @if(($pricing['ok']??false)===true)
                        <div class="grid-amounts">
                            @foreach([
                                ['🏆 Buy Box', $pricing['buy_box']??null],
                                ['💶 Niedrigster Amazon-Summarypreis', $pricing['lowest_summary']??null],
                                ['🔎 Günstigster Einzelhändler (abgerufene)', $pricing['lowest_visible']??null]
                            ] as [$title,$amount])
                                <div class="item">
                                    <p>{{ $title }}</p>
                                    <h4>{{ $amount!==null ? number_format($amount['amount'],2,',','.') . ' '.$amount['currency'] : 'Nicht gemeldet' }}</h4>
                                </div>
                            @endforeach
                        </div>
                        <p class="technical">Stand: {{ $pricing['retrieved_at']??'unbekannt' }} UTC. {{ $pricing['returned']??0 }} Einzelangebote gelesen. Der günstigste Verkäufer kann von der Buy Box abweichen.</p>
                        <h3 style="font-size:14px;font-weight:800;margin-top:16px">Amazon-Händler im Vergleich</h3>
                        @if(($pricing['seller_name_lookup']??'')==='seller_name_archive_unavailable')
                            <p class="warning">Die gespeicherten Händlernamen konnten über die Legacy-Seller-API nicht geladen werden. Die Seller-IDs und Preise sind davon unabhängig vorhanden. Bitte Seller-Namen-Archiv und MariaDB-Zugriff prüfen.</p>
                        @elseif(($pricing['verified_seller_names']??0)<count($pricing['sellers']??[]))
                            <p class="minor">Nicht für alle Verkäufer existiert ein verifizierter Name im verbundenen Verkäuferarchiv. Unbekannte Namen werden nicht aus einer Seller-ID geraten.</p>
                        @endif
                        @foreach(($pricing['sellers']??[]) as $seller)
                            <article class="item">
                                <h4>{{ $seller['seller_name'] ?: 'Händlername noch nicht verifiziert' }}</h4>
                                @if($seller['winner'])
                                    <span class="badge">🏆 Buy-Box-Gewinner</span>
                                @elseif($seller['eligible']===true)
                                    <span class="badge" style="background:#fff1db;color:#90500e">Buy-Box-berechtigt</span>
                                @else
                                    <span class="badge" style="background:#eff2f6;color:#53677a">Buy Box ungeprüft</span>
                                @endif
                                <p><strong>{{ $seller['total']!==null ? number_format($seller['total']['amount'],2,',','.') . ' '.$seller['total']['currency'] : 'Gesamtpreis unbekannt' }}</strong>
                                    · Artikelpreis {{ $seller['item']!==null ? number_format($seller['item']['amount'],2,',','.') . ' '.$seller['item']['currency'] : '?' }}
                                    · Versand {{ $seller['shipping']!==null ? number_format($seller['shipping']['amount'],2,',','.') . ' '.$seller['shipping']['currency'] : '?' }}</p>
                                <p>Seller-ID {{ $seller['seller_id'] }} · {{ $seller['fba']?'FBA':'Nicht als FBA gemeldet' }} · {{ $seller['prime']===true?'Prime':'Prime nicht bestätigt' }} · {{ $seller['feedback_percent']!==null ? number_format($seller['feedback_percent'],1,',','.').' % positive Bewertungen' : 'Bewertung nicht verfügbar' }} · {{ $seller['feedback_count']??'?' }} Bewertungen</p>
                                <p>Versand aus {{ $seller['ships_from']??'unbekannt' }}, Bearbeitungsdauer {{ $seller['dispatch_min']??'?' }} bis {{ $seller['dispatch_max']??'?' }} Stunden (nicht garantierter Zustelltermin)</p>
                                <a href="https://www.amazon.de/sp?seller={{ urlencode($seller['seller_id']) }}" target="_blank" rel="noopener noreferrer">Verkäuferprofil öffnen</a>
                            </article>
                        @endforeach
                        @if(($pricing['sellers']??[])===[])
                            <p class="warning">Amazon hat keine einzelnen Verkäufer übermittelt. Buy Box und niedrigster Summary-Preis können trotzdem getrennt vorliegen.</p>
                        @endif
                        <p class="technical">Die aktuelle Preisabfrage und der optionale Archivstatus werden getrennt ausgewiesen. Händler sind nicht automatisch Großhandelslieferanten.</p>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section class="card" aria-labelledby="intel-ai">
        <div class="head" id="intel-ai">03 · KI-Archiv und historische Prüfungen</div>
        <div class="body">
            <p class="minor">Vorhandene Berichte lesen, Modell, Begründung, Produktvorschläge und historische Kosten nachvollziehen. Diese Aktion verursacht keine OpenAI-Kosten.</p>
            <div class="row">
                <button type="button" class="action" wire:click="loadAiArchive" wire:loading.attr="disabled" wire:target="loadAiArchive" @disabled($article === [])>KI-Archiv zur aktuellen EAN</button>
                <button type="button" class="action secondary" wire:click="loadAiArchiveAll" wire:loading.attr="disabled" wire:target="loadAiArchiveAll">Alle letzten KI-Prüfungen anzeigen</button>
                <span class="technical">20 Berichte je Seite · kostenloser Archivabruf</span>
            </div>
            <p class="minor">Die Gesamtübersicht funktioniert ohne EAN und löst keine neue OpenAI-Abfrage aus.
                @if(($archiveState['scope']??null)==='shared_workspace')
                    Alle berechtigten Mitarbeiter lesen denselben gemeinsamen HW24-Archivbestand.
                @else
                    Bis zur Aktivierung der gemeinsamen API ist die Anzeige möglicherweise noch auf das technische Legacy-Konto beschränkt.
                @endif
                Früher nicht übernommene Datenbanken werden nicht automatisch angezeigt.</p>
            @if($archiveState !== [])
                <p class="warning {{ ($archiveState['ok'] ?? false) ? 'ok' : '' }}" role="status">
                    {{ $archiveState['message'] }} HTTP {{ $archiveState['http'] ?? '—' }}
                    @if(($archiveState['ok'] ?? false)) · {{ $archiveState['count'] }} Berichte @endif
                </p>
                @foreach($reviews as $report)
                    <div class="item" wire:key="hw24-ai-{{ $report['id'] }}">
                        <h4>Bericht #{{ $report['id'] }} · {{ $report['status'] }} · {{ $report['article_name'] ?: 'Produktname nicht gespeichert' }}</h4>
                        <p>EAN {{ $report['ean'] ?: 'nicht hinterlegt' }} · {{ $report['date'] }} UTC · {{ $report['model'] }} · Gespeicherte Kostenberechnung {{ $report['cost'] !== null ? number_format($report['cost'],6,',','.') . ' USD' : 'unbekannt' }}</p>
                        <p class="technical">Tokenverbrauch: {{ $report['tokens_input'] }} Eingabe · {{ $report['tokens_cached'] }} gecacht · {{ $report['tokens_output'] }} Ausgabe. Kosten sind aus API-Tokenverbrauch und konfigurierten Tarifen berechnet, keine bestätigte OpenAI-Rechnung.</p>
                        @if($report['reason']) <p><strong>Prüfbegründung:</strong> {{ $report['reason'] }}</p> @endif
                        @if($report['manufacturer'] || $report['title'] || $report['group'])
                            <p><strong>Vorschläge:</strong> Hersteller {{ $report['manufacturer'] ?: 'offen' }};
                                Titel {{ $report['title'] ?: 'offen' }}; Warengruppe {{ $report['group'] ?: 'offen' }}.
                                Keine automatische Übernahme.</p>
                        @endif
                    </div>
                @endforeach
                @if(($archiveState['ok'] ?? false) && ($archiveState['more'] ?? null))
                    <button type="button" class="action secondary" wire:click="loadOlderAiArchive" wire:loading.attr="disabled" wire:target="loadOlderAiArchive">Weitere ältere Archivberichte laden</button>
                @endif
            @endif
            <div class="divider">
                <h3 style="font-weight:800;font-size:14px">Neue KI-Prüfung bewusst starten</h3>
                <p class="minor">Zuerst den aktuellen Kostenstatus prüfen. Eine neue Erstprüfung oder Neubewertung kann OpenAI-Gebühren verursachen und legt anschließend einen Bericht im bisherigen HW24-Archiv ab.</p>
                <button type="button" class="action secondary" wire:click="inspectAiBudget" wire:loading.attr="disabled" wire:target="inspectAiBudget" @disabled($article === [])>KI-Kosten- und Archivstatus prüfen</button>
                @if($readiness !== [])
                    <div class="warning {{ ($readiness['ok'] ?? false) ? 'ok' : '' }}" role="status">
                        API HTTP {{ $readiness['http'] ?? '—' }} · KI {{ $readiness['enabled'] ? 'verfügbar' : 'nicht verfügbar' }}
                        · Archiv {{ $readiness['archive_ready'] ? 'bereit' : 'nicht bereit' }}.
                        Monatsbudget: {{ $readiness['spent'] !== null ? number_format($readiness['spent'],4,',','.') : '?' }}
                        / {{ $readiness['budget'] !== null ? number_format($readiness['budget'],2,',','.') : '?' }} USD.
                        Zusätzliche private Betreiberfreigabe: {{ $readiness['operator_enabled'] ? 'aktiv' : 'noch deaktiviert' }}.
                    </div>
                @endif
                <label class="inline-label">
                    <input type="checkbox" wire:model="confirmPaid">
                    <span>Ich bestätige ausdrücklich, dass diese KI-Prüfung kostenpflichtig sein kann und ein neuer historischer Bericht gespeichert wird.</span>
                </label>
                <label class="inline-label">
                    <input type="checkbox" wire:model="confirmRecheck">
                    <span>Nur bei bereits vorhandenem Bericht: Ich beauftrage eine erneute kostenpflichtige Prüfung.</span>
                </label>
                <div class="row">
                    <button type="button" class="action" wire:click="runAiReview"
                        wire:confirm="Kostenpflichtige KI-Analyse wirklich ausdrücklich auslösen? Bitte Budget und vorhandene Berichte vorher prüfen."
                        wire:loading.attr="disabled" wire:target="runAiReview"
                        @disabled($article === [] || !($readiness['operator_enabled'] ?? false) || !($readiness['ok'] ?? false) || !($readiness['enabled'] ?? false) || !($readiness['archive_ready'] ?? false))>
                        Neue KI-Prüfung ausdrücklich beauftragen
                    </button>
                </div>
                @if($reviewResult !== [])
                    <p class="warning {{ ($reviewResult['ok'] ?? false) ? 'ok' : '' }}" role="status">
                        {{ $reviewResult['message'] }}
                        @if($reviewResult['id'] ?? null) · Archiv-ID {{ $reviewResult['id'] }} @endif
                        @if(($reviewResult['spent'] ?? null) !== null) · Kosten {{ number_format($reviewResult['spent'],6,',','.') }} USD @endif
                    </p>
                @endif
                <p class="technical">Ohne private Serverfreigabe wird keine kostenpflichtige Anfrage gestartet. Nach jeder KI-Aktion zuerst Archiv und Budget erneut prüfen; keine automatische Wiederholung bei Fehlern.</p>
            </div>
        </div>
    </section>
</div>
</div>
</x-filament-panels::page>
