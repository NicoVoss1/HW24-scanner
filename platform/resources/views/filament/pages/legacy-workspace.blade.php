<x-filament-panels::page>
    <div class="hw24-legacy-workspaces">
        <style>
            .hw24-legacy-workspaces{color:#182b3f}
            .hw24-legacy-workspaces *{box-sizing:border-box}
            .hw24-legacy-workspaces .hw24-head{padding:24px 26px;background:linear-gradient(115deg,#102b43,#195777);color:#fff;border-radius:17px}
            .hw24-legacy-workspaces .hw24-head h2{font-size:24px;font-weight:800;margin:4px 0 8px}
            .hw24-legacy-workspaces .hw24-head p{font-size:13px;line-height:1.7;color:#daeafa;max-width:850px}
            .hw24-legacy-workspaces .hw24-eyebrow{font-weight:800;font-size:11px;letter-spacing:.1em;color:#a4cdf0;text-transform:uppercase}
            .hw24-legacy-workspaces .hw24-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px;margin-top:18px}
            .hw24-legacy-workspaces .hw24-card{background:#fff;border:1px solid #dde6ee;border-radius:15px;padding:19px;min-width:0}
            .hw24-legacy-workspaces .hw24-card h3{font-size:16px;line-height:1.4;font-weight:800;margin:0 0 8px}
            .hw24-legacy-workspaces .hw24-card p{font-size:12px;line-height:1.65;color:#53687a;max-width:610px;margin:0 0 14px}
            .hw24-legacy-workspaces .hw24-card ul{margin:0 0 15px;padding-left:19px;color:#53687a;font-size:12px;line-height:1.75}
            .hw24-legacy-workspaces .hw24-link{display:inline-flex;align-items:center;justify-content:center;background:#17496e;color:white;border-radius:10px;min-height:40px;padding:10px 14px;text-decoration:none;font-weight:800;font-size:12px}
            .hw24-legacy-workspaces .hw24-link:hover{background:#215f90}
            .hw24-legacy-workspaces .hw24-tag{display:inline-block;border-radius:999px;background:#edf4fb;color:#145079;padding:5px 9px;font-size:10px;font-weight:800;margin:0 0 13px}
            .hw24-legacy-workspaces .hw24-inline{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
            .hw24-legacy-workspaces .hw24-secondary{font-size:12px;font-weight:700;color:#185f92;text-decoration:underline;text-underline-offset:3px}
            .hw24-legacy-workspaces .hw24-note{margin-top:16px;border:1px solid #f0d9ad;background:#fffaec;color:#815113;border-radius:12px;padding:15px 17px;font-size:12px;line-height:1.7}
            .hw24-legacy-workspaces a:focus-visible{outline:3px solid #60a5fa;outline-offset:2px}
            @media(max-width:790px){.hw24-legacy-workspaces .hw24-grid{grid-template-columns:1fr}.hw24-legacy-workspaces .hw24-head{padding:20px}.hw24-legacy-workspaces .hw24-head h2{font-size:21px}}
            html.dark .hw24-legacy-workspaces .hw24-card{background:#192635;border-color:#384a5c}
            html.dark .hw24-legacy-workspaces .hw24-card h3{color:#f8fafc}
            html.dark .hw24-legacy-workspaces .hw24-card p,html.dark .hw24-legacy-workspaces .hw24-card ul{color:#cbd5e1}
        </style>
        <header class="hw24-head">
            <div class="hw24-eyebrow">HW24 · Bestehender Funktionsumfang</div>
            <h2>Alle bisherigen Werkzeuge wieder erreichbar</h2>
            <p>Die neue Laravel-Oberfläche ersetzt noch nicht die vollständigen Arbeitsfunktionen des bisherigen Artikelmanagers. Deshalb erreichst Du hier die originalen, weiterhin geschützten Module. Wir übernehmen sie danach schrittweise, ohne Scan-Stapel, Entwürfe oder API-Zugänge zu verlieren.</p>
        </header>

        <div class="hw24-grid">
            <section class="hw24-card">
                <span class="hw24-tag">Bestehende Artikelbearbeitung</span>
                <h3>Artikelpflege, KI-Abfrage und JTL-Ameise</h3>
                <p>Die komplette bisherige Artikelmaske mit gespeicherten HW24-Entwürfen, Freigaben, Produkttexten, Lieferzeiten, manueller KI-Prüfung und JTL-Ameise-Prüfexport.</p>
                <ul>
                    <li>Stammdaten bearbeiten und Unterschiede zum JTL-Original prüfen</li>
                    <li>KI-Archiv lesen oder neue KI-Analyse nur nach ausdrücklicher Kostenbestätigung starten</li>
                    <li>Einzelartikel als JTL-Ameise-Prüfdatei exportieren</li>
                </ul>
                <div class="hw24-inline">
                    <a class="hw24-link" href="https://artikelmanager.hairworld24.de/artikelpflege.html" target="_blank" rel="noopener noreferrer">Bisherige Artikelpflege öffnen</a>
                    <a class="hw24-secondary" href="/admin/artikel">Neue JTL-Leseansicht</a>
                </div>
            </section>

            <section class="hw24-card">
                <span class="hw24-tag">Scanliste und Import</span>
                <h3>EAN scannen, Stapel verwalten, CSV importieren</h3>
                <p>Die vorhandenen Scan-Stapel in MariaDB, Kamera- und EAN-Eingabe, CSV-Einlesen, Artikelprüfung, Entwürfe und Sammelimport als Prüf-CSV.</p>
                <ul>
                    <li>Scannen, doppelte Scans erfassen und Positionen bearbeiten</li>
                    <li>CSV mit bis zu 5.000 EAN-Zeilen kontrolliert einlesen</li>
                    <li>Bis zu 200 freigegebene Positionen je Ameise-Prüfexport</li>
                </ul>
                <div class="hw24-inline">
                    <a class="hw24-link" href="https://artikelmanager.hairworld24.de/scanliste.html" target="_blank" rel="noopener noreferrer">Vollständige Scanliste öffnen</a>
                    <a class="hw24-secondary" href="/admin/scanliste">Scan-Stapel hier prüfen</a>
                </div>
            </section>

            <section class="hw24-card">
                <span class="hw24-tag">Expertenarbeitsplatz</span>
                <h3>Amazon-Katalog, Produktrecherche und Importentwürfe</h3>
                <p>Die bisherige Expertenansicht mit EAN-Suche, Amazon-Katalogvergleich, optionaler JTL-CSV-Massenprüfung, Qualitätsvorschlägen und Ameise-Entwürfen.</p>
                <ul>
                    <li>Amazon-Produkte nach EAN oder Herstellerhinweisen vergleichen</li>
                    <li>Amazon-Katalogdaten als Recherchegrundlage nutzen, nicht automatisch als eigenes Verkäuferangebot</li>
                    <li>Prüfbare CSV-/JSON-Entwürfe erzeugen statt direkte Wawi-Schreibzugriffe</li>
                </ul>
                <div class="hw24-inline">
                    <a class="hw24-link" href="https://artikelmanager.hairworld24.de/index.html" target="_blank" rel="noopener noreferrer">Expertenansicht öffnen</a>
                    <a class="hw24-secondary" href="https://artikelmanager.hairworld24.de/index.html#ameisePanel" target="_blank" rel="noopener noreferrer">Direkt zur Ameise-Vorschau</a>
                </div>
            </section>

            <section class="hw24-card">
                <span class="hw24-tag">Artikelübersicht und Qualitätskontrolle</span>
                <h3>Qualitätszentrum und offene Artikelaufgaben</h3>
                <p>Die bisherige Qualitätsliste mit Prüfvorschlägen und Einstieg in einzelne Artikel. Diese Listenübersicht ist in Filament bisher noch nicht vollständig migriert.</p>
                <ul>
                    <li>Artikel auf Vollständigkeit und widersprüchliche Quelldaten prüfen</li>
                    <li>Prioritäten und fehlende Pflichtangaben erkennen</li>
                    <li>Aus der Liste zur ausführlichen Artikelpflege wechseln</li>
                </ul>
                <div class="hw24-inline">
                    <a class="hw24-link" href="https://artikelmanager.hairworld24.de/qualitaetszentrum.html" target="_blank" rel="noopener noreferrer">Qualitätszentrum öffnen</a>
                    <a class="hw24-secondary" href="/admin/artikel">Einzelartikel ansehen</a>
                </div>
            </section>
        </div>

        <p class="hw24-note">
            <strong>Wichtig:</strong> Diese Arbeitsbereiche öffnen die bestehende geschützte LIVE-Anwendung auf
            <strong>artikelmanager.hairworld24.de</strong> in einem neuen Tab. Dort gelten die bereits vorhandenen Authentifizierungs- und Freigaberegeln.
            Beim Wechsel kann eine zusätzliche HTTP-Basic-Anmeldung erforderlich sein. Die Links starten selbst keine API-Abfrage, keine KI-Kosten und keine Datenänderung.
            Für Filament werden die Funktionen getrennt und kontrolliert migriert.
        </p>
    </div>
</x-filament-panels::page>
