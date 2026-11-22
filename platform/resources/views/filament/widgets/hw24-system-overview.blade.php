<x-filament-widgets::widget>
    <div class="hw24-cockpit">
        <style>
            .hw24-cockpit{font-family:inherit;color:var(--gray-950,#182230);max-width:100%}
            .hw24-cockpit *{box-sizing:border-box}
            .hw24-cockpit .hw24-hero{border-radius:18px;padding:24px 26px;background:linear-gradient(115deg,#10263b,#194764);color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap}
            .hw24-cockpit .hw24-eyebrow{margin:0 0 6px;color:#bfdbfe;font-size:12px;font-weight:700;letter-spacing:.09em;text-transform:uppercase}
            .hw24-cockpit .hw24-h{font-size:24px;line-height:1.22;font-weight:750;letter-spacing:-.02em;margin:0}
            .hw24-cockpit .hw24-lead{color:#dbeafe;font-size:13px;line-height:1.7;max-width:650px;margin:8px 0 0}
            .hw24-cockpit .hw24-hero-link{color:#0d3254;background:#fff;padding:11px 17px;border-radius:10px;font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;min-height:40px}
            .hw24-cockpit .hw24-hero-link:hover{background:#dbeafe}
            .hw24-cockpit .hw24-section-heading{margin:24px 0 12px;font-size:16px;font-weight:750;letter-spacing:-.01em}
            .hw24-cockpit .hw24-cardgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
            .hw24-cockpit .hw24-card{padding:20px;min-width:0;background:#fff;border:1px solid #e0e7ef;border-radius:15px;box-shadow:0 1px 3px rgba(15,23,42,.04)}
            .hw24-cockpit .hw24-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
            .hw24-cockpit .hw24-kicker{font-size:12px;color:#64748b;font-weight:700;letter-spacing:.02em}
            .hw24-cockpit .hw24-status{font-size:18px;line-height:1.4;font-weight:750;margin:8px 0 6px;overflow-wrap:anywhere}
            .hw24-cockpit .hw24-note{font-size:13px;color:#566679;line-height:1.6;margin:0}
            .hw24-cockpit .hw24-pill{font-size:11px;line-height:1.3;font-weight:750;white-space:nowrap;border-radius:999px;padding:5px 10px;background:#eff6ff;color:#1d4ed8}
            .hw24-cockpit .hw24-pill-success{background:#ecfdf5;color:#047857}
            .hw24-cockpit .hw24-pill-warn{background:#fff7ed;color:#9a3412}
            .hw24-cockpit .hw24-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:15px}
            .hw24-cockpit .hw24-button{border:1px solid #cbd5e1;border-radius:9px;min-height:37px;padding:8px 12px;background:#fff;color:#153451;font-size:12px;font-weight:750;cursor:pointer}
            .hw24-cockpit .hw24-button:hover{background:#eff6ff}
            .hw24-cockpit .hw24-button:focus-visible,.hw24-cockpit .hw24-hero-link:focus-visible{outline:3px solid #60a5fa;outline-offset:2px}
            .hw24-cockpit .hw24-button:disabled{opacity:.5;cursor:not-allowed}
            .hw24-cockpit .hw24-checked{font-size:11px;color:#64748b}
            .hw24-cockpit .hw24-evidence{margin-top:14px;border-top:1px solid #e2e8f0;padding-top:13px}
            .hw24-cockpit .hw24-row{display:flex;gap:10px;justify-content:space-between;padding:5px 0;font-size:12px}
            .hw24-cockpit .hw24-row span{color:#64748b}
            .hw24-cockpit .hw24-row strong{font-weight:700;max-width:65%;text-align:right;overflow-wrap:anywhere}
            .hw24-cockpit .hw24-bottom{margin-top:16px;border:1px solid #e0e7ef;border-radius:14px;padding:17px 20px;background:#f8fafc;display:flex;align-items:flex-start;gap:14px;justify-content:space-between;flex-wrap:wrap}
            .hw24-cockpit .hw24-bottom strong{font-size:13px}
            .hw24-cockpit .hw24-bottom p{font-size:12px;line-height:1.6;margin:4px 0 0;color:#475569;max-width:750px}
            .hw24-cockpit .hw24-bottom a{font-size:12px;color:#1d4ed8;font-weight:700;text-decoration:underline;text-underline-offset:3px}
            @media(max-width:780px){.hw24-cockpit .hw24-cardgrid{grid-template-columns:1fr}.hw24-cockpit .hw24-hero{padding:20px}.hw24-cockpit .hw24-h{font-size:21px}}
            html.dark .hw24-cockpit .hw24-card{background:#17212f;border-color:#334155}
            html.dark .hw24-cockpit .hw24-status,html.dark .hw24-cockpit .hw24-section-heading{color:#f8fafc}
            html.dark .hw24-cockpit .hw24-note,html.dark .hw24-cockpit .hw24-kicker{color:#cbd5e1}
            html.dark .hw24-cockpit .hw24-bottom{background:#17212f;border-color:#334155}
            html.dark .hw24-cockpit .hw24-bottom p{color:#cbd5e1}
        </style>

        <header class="hw24-hero">
            <div>
                <p class="hw24-eyebrow">HAIRWORLD24 · E-Commerce</p>
                <h2 class="hw24-h">Deine Produktdaten. Ein klarer Überblick.</h2>
                <p class="hw24-lead">
                    JTL, Amazon, KI-Archiv und Verkaufskanäle an einem Ort.
                    Echte Quellnachweise statt geschätzter Preise oder künstlicher Kennzahlen.
                </p>
            </div>
            <a class="hw24-hero-link" href="/admin/artikel">Artikel &amp; Schnittstellen öffnen</a>
        </header>

        <h3 class="hw24-section-heading">Systeme und Datenquellen</h3>
        <div class="hw24-cardgrid">
            <section class="hw24-card" aria-label="JTL Spiegelstatus">
                <div class="hw24-card-top">
                    <span class="hw24-kicker">JTL-Wawi · MariaDB-Spiegel</span>
                    <span class="hw24-pill {{ ($jtl['ok'] ?? false) ? 'hw24-pill-success' : '' }}">
                        {{ ($jtl['ok'] ?? false) ? 'Probe bestätigt' : 'Leseverbindung' }}
                    </span>
                </div>
                <p class="hw24-status">{{ ($jtl['ok'] ?? false) ? 'Referenzartikel gefunden' : ($this->isBridgeConfigured() ? 'Zugang eingerichtet' : 'Zugang fehlt') }}</p>
                <p class="hw24-note">Der aktuelle Snapshot wird nur auf Knopfdruck geprüft. Eine Artikelprobe ist keine Lager-Gesamtstatistik.</p>
                <div class="hw24-actions">
                    <button type="button" class="hw24-button" wire:click="inspectJtl"
                        wire:loading.attr="disabled" wire:target="inspectJtl"
                        @disabled(! $this->isBridgeConfigured())>
                        JTL-Referenz prüfen
                    </button>
                    <span class="hw24-checked">{{ $jtl['checked_at'] ?? 'Noch nicht in dieser Sitzung geprüft' }}</span>
                </div>
                @if($jtl !== [])
                    <div class="hw24-evidence" aria-live="polite">
                        <div class="hw24-row"><span>API / Snapshot</span><strong>HTTP {{ $jtl['http'] ?? '—' }} · {{ $jtl['snapshot'] }}</strong></div>
                        <div class="hw24-row"><span>EAN / JTL-ID</span><strong>{{ $jtl['ean'] }} / {{ $jtl['article_id'] ?: 'nicht bestätigt' }}</strong></div>
                        @if($jtl['ok'])
                            <div class="hw24-row"><span>Artikel</span><strong>{{ $jtl['article'] ?: 'Nicht angegeben' }}</strong></div>
                            <div class="hw24-row"><span>Bestand</span><strong>{{ $jtl['stock'] ?? 'unbekannt' }}</strong></div>
                            <div class="hw24-row"><span>VK netto</span><strong>{{ $jtl['net_price'] !== null ? number_format((float) $jtl['net_price'], 2, ',', '.') . ' (Währung unbestätigt)' : 'nicht belegt' }}</strong></div>
                        @else
                            <p class="hw24-note">Keine eindeutige aktuelle Artikelprobe. Quelle und Synchronisation prüfen.</p>
                        @endif
                    </div>
                @endif
            </section>

            <section class="hw24-card" aria-label="KI Archivstatus">
                <div class="hw24-card-top">
                    <span class="hw24-kicker">OpenAI · historisches KI-Archiv</span>
                    <span class="hw24-pill {{ ($archive['ok'] ?? false) ? 'hw24-pill-success' : '' }}">
                        {{ ($archive['ok'] ?? false) ? 'Archiv geprüft' : 'Nur lesend' }}
                    </span>
                </div>
                <p class="hw24-status">
                    {{ ($archive['ok'] ?? false) ? ($archive['count'] . ' Archivtreffer (max. 5)') : 'Bestehende Analysen abrufen' }}
                </p>
                <p class="hw24-note">Es werden nur bereits gespeicherte Auswertungen zur Referenz-EAN gelesen. Maximal fünf Treffer, keine neue KI-Analyse.</p>
                <div class="hw24-actions">
                    <button type="button" class="hw24-button" wire:click="inspectArchive"
                        wire:loading.attr="disabled" wire:target="inspectArchive"
                        @disabled(! $this->isBridgeConfigured())>
                        KI-Archiv prüfen
                    </button>
                    <span class="hw24-checked">{{ $archive['checked_at'] ?? 'Noch nicht in dieser Sitzung geprüft' }}</span>
                </div>
                @if($archive !== [])
                    <div class="hw24-evidence" aria-live="polite">
                        <div class="hw24-row"><span>Archiv-API</span><strong>HTTP {{ $archive['http'] ?? '—' }}</strong></div>
                        <div class="hw24-row"><span>Ergebnis</span><strong>{{ $archive['ok'] ? 'Gespeicherte Einträge (max. 5)' : 'Nicht bestätigt' }}</strong></div>
                    </div>
                @endif
            </section>

            <section class="hw24-card" aria-label="Amazon Status">
                <div class="hw24-card-top">
                    <span class="hw24-kicker">Amazon Selling Partner API</span>
                    <span class="hw24-pill hw24-pill-warn">Angebote ungeprüft</span>
                </div>
                <p class="hw24-status">Katalog und Angebote trennen</p>
                <p class="hw24-note">Ein Katalogtreffer bestätigt weder Dein Verkäuferangebot noch dessen Verkaufspreis. Amazon-Abfragen starten nur in der Artikelansicht ausdrücklich per Klick.</p>
                <div class="hw24-actions"><a href="/admin/artikel" class="hw24-button" style="text-decoration:none;display:inline-flex;align-items:center">Zur Amazon-Artikelprüfung</a></div>
            </section>

            <section class="hw24-card" aria-label="Shopify und eBay Status">
                <div class="hw24-card-top">
                    <span class="hw24-kicker">Shopify · eBay · Verkaufskonten</span>
                    <span class="hw24-pill hw24-pill-warn">Nicht verifiziert</span>
                </div>
                <p class="hw24-status">Kanalpreise noch offen</p>
                <p class="hw24-note">Spätere Anbindung: tatsächlicher aktiver Preis je Konto, Artikelstatus und Datenquelle. Ohne bestätigten Connector zeigen wir keine erfundenen Umsätze oder Angebote.</p>
            </section>
        </div>

        <div class="hw24-bottom">
            <div>
                <strong>Was zeigt dieses Cockpit bereits zuverlässig?</strong>
                <p>Verbindungsbereitschaft und manuell abgefragte Referenzdaten. Gesamtartikelzahl, Bestellvolumen, Brutto-VK, Kanalangebote und Umsatz bleiben bis zu einer geprüften Datenquelle ausdrücklich offen.</p>
            </div>
            <a href="/admin/artikel">Zur detaillierten Artikelpflege</a>
        </div>
    </div>
</x-filament-widgets::widget>
