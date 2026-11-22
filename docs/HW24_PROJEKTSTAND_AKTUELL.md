## NEUESTE VERIFIKATION · 22.11.2026 · ARTISAN- UND LIVE-ABNAHME

**PuTTY bestätigt:** Laravel CLI 13.43.0, Filament-4er-Routenliste (/admin, /admin/artikel, /admin/login, /admin/logout), alle vier V4.25-Dateihashes im **Live-Artikelmanager** OK. Eine private Laravel-.env und lokale SQLite existieren noch nicht. Nur ein vollständiger Prüfdurchlauf beobachtet; zweiter geposteter Befehlsblock ohne abschließende Terminalausgabe.

**Nächste Aktion:** isoliertes Laravel-.env mit APP_ENV=production, APP_DEBUG=false, HTTPS und APP_KEY; ausschließlich private SQLite-Datenbank Laravel-Systemmigrationen, Filament-Assets. Keine DB-/Website-Migration des Legacy-Codepfads. Legacy-Live angemeldet testen, ehe die bestehende Testdomain umgeschaltet wird.

[Vollständiger Einzelnachweis](./HW24_ARBEITSJOURNAL.md), Commit 3b7ee3a26e2a524fe54f136f38453d9d190239c7.

---

# AKTUELLSTER PROJEKTSTAND · 22.11.2026 (Composer und Laravel/Filament erfolgreich installiert)

**VERBINDLICHER EINSTIEG:** [Kurz-Übergabe für jeden neuen Chat](./HW24_HANDOVER.md) · [Chronologisches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) · [Zwei-Domain-Rollout](./HW24_TWO_DOMAIN_ROLLOUT_20261122.md). **Dieser neueste Stand ersetzt widersprechende frühere Planungsannahmen weiter unten.**

- **Bestehende Live-Artikelmanager-Domain:** Plesk `Active branch: deploy/staging`, letzter Git-Commit exakt Legacy-V4.25 `444453471b0fd27c73dcc7929f5e17f1d9239c52`. Nutzer über PuTTY bestätigt. **Nicht damit gleichsetzen, dass Webroot-Hashes und angemeldete Live-API-Funktion bereits abgenommen wären.**
- **Bestehende Testdomain:** Legacy-V4.25 per Plesk manuell deployt, zwei bisher abweichende V4.24-Dateien danach auf GitHub-V4.25-BLOB-Hash OK. Privates Backup der alten Testwebsite vorhanden.
- **Sicherheit:** Je 3 nicht authentifizierte GETs auf Live und Test ergaben **HTTP 401** (gesamt 6). Weitere Pfad-/Browserabnahme bleibt offen.
- **Laravel-/Filament-Pilot:** GitHub [Draft PR #36](https://github.com/NicoVoss1/HW24-scanner/pull/36), letzte Console-Basis `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`, CI [#44972633923](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44972633923) **grün**. Implementiert: geschützter Admin-Login, Artikel-JTL-GET, manuelle Amazon-/OpenAI-Archiv-GET, Dashboard; alle JTL-/WMS-/Shop-/API-Schreibzugriffe weiterhin deaktiviert.
- **Plesk-Installation: Composer-Abhängigkeiten ERFOLGREICH.** Privater Git-Klon auf SHA `980bcce75a0a3516b7b6364fae5a0fa6aab8d112` bestätigt; mit Plesk-PHP 8.4/Composer 2.11.2 als Site-Benutzer **112 Pakete installiert**, Laravel **13.43.0**, Filament **5.12.0**, Livewire **4.4.9**, `vendor/autoload.php` vorhanden und `artisan package:discover` erfolgreich. 145 Versionsfestschreibungen in **lokaler** `platform/composer.lock`, noch nicht ins Repo übernommen. Frühere Git-Owner-/Plesk-Composer-Wrapperfehler **behoben**, weiter unten historisch dokumentiert. **App-Umgebung, Admin-Login, SQLite und Test-Webroot noch nicht konfiguriert.**
- **Nächster Serverarbeitsschritt:** Erst read-only Artisan-Boot (`php artisan --version`, `artisan route:list --path=admin`) und Live-V4.25-Webroot-Dateihashes verifizieren. **Keine Neuinstallation!** Erst danach isoliertes `.env`/APP_KEY/SQLite für Laravel-Login und Filament-Assets; Test-Webroot erst nach angemeldetem Legacy-Live-E2E umschalten.
- **Betriebsziel:** Genau zwei bestehende Artikelmanager-Domains, keine neue Subdomain, keine neue MariaDB. Bestehende JTL-MariaDB/JTL-Wawi und Legacy-APIs bleiben unverändert. Laravel-eigene Daten später nur in eigenem privaten SQLite-Datenfile. Legacy-Brücke ist im Featurebranch auf `artikelmanager.hairworld24.de` gepinnt; Basic-Auth-Zugangsdaten nur private Server-`.env`.
- **Ab sofort zwingend:** **Jeden einzelnen Test-, PuTTY-, Git-, Deploy-, Fehler- und Reparaturschritt** sofort im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) als neue Zeile mit Datum, Soll/Ist, SHA, Resultat und nächstem Schritt protokollieren; nach einem Meilenstein Übergabe und Projektstand aktualisieren. Keine Secrets ins öffentliche GitHub.

---

## 22.11.2026 · LARAVEL-13-/FILAMENT-5-FRAMEWORK-PILOT ISOLIERT GESTARTET

- **Userauftrag:** Einheitliches, kostenfrei nutzbares Framework für 100.000 Artikel und JTL-Wawi/WMS, Amazon SP-API, OpenAI, eBay und Shopify. Alle vorhandenen APIs/Daten müssen erhalten bleiben; Netto-/Brutto-/Marktplatzpreise getrennt anzeigen.
- **Framework-Entscheidung:** Laravel 13 + Filament 5 (MIT/Open Source, keine Core-Lizenzkosten), php >=8.3; Filament-5-Panel-Bausteine auf dem neuesten unterstützten Laravel. Pimcore-Community mit POCL-Umsatzgrenze nicht als langfristig kostenloser Standard geeignet. Filament selber als Paket in Composer vorbereitet.
- **Live Shopify-Read-only-Nachweis:** EAN **3474630337398**, `productVariants(query: "barcode:3474630337398")` liefert Matrix SoColor Beauty 6C, 90 ml, Shopify `price=14.00 EUR`, `Product.status=ACTIVE`, SKU und Barcode gleich EAN. **Nicht** geprüft, ob storefront-published/jetzt verkäuflich. **Kein Shopify-Write.**
- **Implementierung:** Isolierter GitHub-Featurebranch `feature/hw24-platform-laravel13-filament5-20261122`, [Draft PR #36](https://github.com/NicoVoss1/HW24-scanner/pull/36). Offizielles Laravel-13-Skeleton unter `platform/`, Filament-5-Composer-Abhängigkeit, neue **nur isolierte** SQLite-Plattformmigration für indexierte Preisbeobachtungen, `ChannelPrice`/ `PriceBoard` mit Quellen-/Status-/Zeit-/Brutto-/Netto-/Anbietertrennung, sicherer GET-only `LegacyReadBridge` für bestehende testgeschützte JTL-/Amazon-/KI-APIs und read-only Plesk-Hostdiagnose. **Keine** API-Passwörter/Keys kopiert, kein Plesk-Deploy.
- **GitHub CI:** [#44966325872](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44966325872) **grün**: Auf GitHub-PHP-8.4-Runner werden Laravel 13 und Filament 5 via Composer aufgelöst/installiert; isolierte SQLite-Migrationen, Framework-Tests, Quellpreis-Contracttests, Node/Vite-Build bestanden. **Keine Plesk-Kompatibilität oder Live-API-End-to-End-Bestätigung.**
- **Sicherheitsregel:** Draft PR **NICHT MERGEN**, sonst könnte der neue Laravel-Verzeichnisbaum in den alten via Git deployten öffentlichen Plesk-Webroot gelangen. Erst **eigenen nichtöffentlichen Projektpfad** und neuen separaten Vhost mit alleiniger `platform/public`-DocumentRoot, PHP/Composer/Node/SSL/Auth/Rollen und DB-Trennung prüfen. Kein `migrate` auf `hw24_artikelmanager`; kein Datenreset. `main` Live bleibt unverändert.
- **Preise:** echte JTL-Brutto-Ermittlung inklusive Steuer-/Kundengruppenkontext noch ausstehend [#33](https://github.com/NicoVoss1/HW24-scanner/issues/33); unbekannt ist nicht null Euro. Amazon Market Offer/BuyBox ≠ eigenes Händlerangebot; eBay Sell-API noch nicht verbunden, eBay-Peise bis dahin `unavailable`.
- **Nächster Schritt:** privates Plesk-Hosting-Readiness-Skript `platform/ops/check-host-readonly.sh` aus Featurebranch mit **nur lesenden** Checks ausführen; anschließend isoliertes Filament-Adminpanel und read-only JTL-Artikel-/Preisboard, Vergleich der API-Contracts, erst danach gesteuerte Übernahme vorhandener Daten. [Anleitung im Branch](https://github.com/NicoVoss1/HW24-scanner/blob/feature/hw24-platform-laravel13-filament5-20261122/platform/README.md).

## AKTUELLSTER CODESTAND · 22.11.2026 · V4.25 AMAZON/KI/AMEISE IN ARTIKELPFLEGE

- **Neue Testversion:** [PR #34](https://github.com/NicoVoss1/HW24-scanner/pull/34), `deploy/staging` SHA **`444453471b0fd27c73dcc7929f5e17f1d9239c52`**. Automatischer [CI-Lauf #44964900380 grün](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44964900380). **Plesk-Deploy und Browser-E2E noch nicht bestätigt.**
- **Ursache Amazon-Problem:** V4.24 `artikelpflege.html` band den bereits existierenden `api/amazon.php`-Katalogservice nicht ein. Neue, kompakt eingeklappte manuelle Amazon-EAN-SP-Abfrage plus Keyword-Fallback `api/amazon-keywords.php` (ASIN-Nachweis nur bei exakter EAN), read-only Angebotspreise `api/amazon-pricing.php?asin=...`. Die Server-API kann weiterhin wegen Zugang/LWA/Role-Konfiguration scheitern; Status inkl. Stage wird angezeigt, noch nicht live getestet.
- **KI:** Kostenfreie persönliche Archivabfrage beim Öffnen, anschließende bewusst bestätigte und serverseitig deduplizierte OpenAI-Prüfung; kein automatischer kostenpflichtiger Call.
- **UI:** Artikeltyp/Steuerklasse/Versandklasse/Währung als echte Dropdowns mit beobachteten JTL-Werten, Lieferantenfelder ausgeblendet, doppeltes `id=logisticsFields` repariert, `0=Nein`/`1=Ja` automatisches Berechnen; nach bestätigter Produktgruppe Haarfarbe nur LEERE Felder für manuell 1 Tag + Bearbeitung 1 Tag und automatik 0 vorschlagen. Kategorie ist JTL- und nicht zwingend Shopify-Kategorie.
- **Ameise:** In der Pflegeansicht neue Einzelartikel-UTF8-Prüfdatei für **explizit ausgewählte, vorher bestätigte HW24-Overlay-Felder**, mit frischer JTL-Identitäts- und Revisionsprüfung. Keine unbestätigten/blanken Preis-/Bestandsfelder, keine direkte JTL-/WMS-Aktion, noch keine Liefer-/Beschaffungs-/Status-Felder ohne bewiesene Mapping-Konfiguration.
- **Brutto-VK verbleibende Abhängigkeit:** Im bestehenden Windows-SQL-Sync werden bislang nur `netto_vk` und weitere begrenzte Felder gespiegelt. `api/jtl.php` kann künftig `raw.HW24_VKBrutto` nur mit expliziter verifizierter `raw.HW24_VKBruttoQuelle` zeigen. Actual JTL-Preis-/Steuerzonenquelle muss noch ermittelt und **Windows-Sync** ergänzt werden: [Issue #33](https://github.com/NicoVoss1/HW24-scanner/issues/33). Keine erfundene 19%-Brutto-SQL-Spalte.
- **Nächste Aktion:** Nur Test-Plesk Git `deploy/staging` Pull/Bereitstellen, `https://test-artikelmanager.hairworld24.de/artikelpflege.html?ean=3474630337398` öffnen und **Amazon-Katalog prüfen** einmal bewusst starten. Eventuelle `error`, `stage`, `upstream_status` zur Ursachenbehebung heranziehen. Danach KI-Archiv, Dropdowns und freigegebene Ameise-CSV testen. Keine DB-Neuinstallation oder Live-Pull.
- [Vollständige Test- und Releaseanleitung](./HW24_V425_ARTIKELPFLEGE_RELEASE_20261122.md).

# HW24 · Verbindlicher Projektstand und Einstiegspunkt

**Stand:** 22.11.2026, aktueller Release PR #32 (ältere Abschnitte dokumentieren historische Projektstände).

## AKTUELLSTE TESTFREIGABE · V4.24

- **Website-only [PR #32](https://github.com/NicoVoss1/HW24-scanner/pull/32)** in `deploy/staging` gemerged, SHA **`f51ab67240bd1bf0e5be25498269049a24748b85`**. Plesk-Deployment noch **nicht nachgewiesen**, Live `main` unverändert.
- **Neue Alltagspflege:** `artikelpflege.html`, eindeutige JTL-Artikelabfrage, klare Eingabebereiche, dokumentierte Vorher/Nachher-Werte, revisionsgeschützte HW24-Entwurfsspeicherung. Alte `index.html` bleibt Expertenansicht.
- **Qualitätsfilter:** Bestand und EK unabhängig, ODER/UND auswählbar, Standard mit positivem Bestand zuerst, echte numerische Sortierung und separate Spalten Bestand / Netto-EK / letzter EK.
- **Haarfarben-Referenz:** EAN **3474630337398** Prüfziffer gültig; deren **echte** Liefer-/Beschaffungs-/Lagerparameter werden erst auf dem authentifizierten Testserver aus JTL gelesen, derzeit keine Kopie unbekannter Werte. Sicherung: nur belegte Leerfelder, Checkbox Produktart Haarfarbe und Bestätigung. Keine direkten JTL-/WMS-/Shopify-Schreibrechte.
- **Automatische Tests:** [#44963263588](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44963263588) und [#44963263701](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44963263701) grün; reale Browser-/MariaDB-Tests offen.
- **Genau nächster Schritt:** Testdomain `test-artikelmanager.hairworld24.de` in Plesk auf `deploy/staging` Pull → Bereitstellen. Anschließend `/qualitaetszentrum.html` und `/artikelpflege.html?ean=3474630337398` prüfen. KEINE Wiederholung der bereits erledigten Datenbankinstallationen/Backups; KEIN Live-Deploy.
- [Vollständiges Releaseprotokoll](./HW24_V424_QUALITY_EDITOR_RELEASE_20261122.md).

---

**Historischer Stand:** 20.11.2026, fortgeschrieben nach UI-Release PR #18 · **Projekt:** Hairworld24 Artikelmanager / HW24-scanner  
**Gültigkeit:** Dies ist die zentrale, fortlaufend zu pflegende Projektdokumentation. Sie ist nicht selbst ein Nachweis, dass ein Plesk-Deployment, eine Datenbankmigration oder ein Live-Test durchgeführt wurde.

> **WICHTIG FÜR DIE FORTSETZUNG:** Beim nächsten Chat, Entwicklerauftrag oder GitHub-Arbeitsschritt **zuerst diese Seite lesen** und die letzten Commits/PRs vergleichen. Keine bereits abgeschlossenen Einrichtungs- und Backup-Schritte von vorn verlangen. Ein Sachstand darf nur aufgrund neuer Befunde geändert werden. Nicht stillschweigend auf `main`, eine andere Version oder einen alten PR zurückspringen.

## NEUESTER UMSETZUNGSSTAND · 21.11.2026 · V4.23 JTL-QUALITÄTSZENTRUM

- **Jetzt im GitHub-Testbranch** `deploy/staging` implementiert: [PR #31](https://github.com/NicoVoss1/HW24-scanner/pull/31), Commit **`c54894c854c538e79ef8e6fc9b9132102be79a6c`**. Automated Tests [#44953759138](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44953759138) grün (PHP-GTIN/EK-/JTL-Regeln, HTML-/JS-Syntax, read-only-Sicherheitsverträge). **Kein bestätigter Plesk-Deploy oder realer MariaDB-Gesamtlauf.**
- **Neue eigenständige Seite:** `qualitaetszentrum.html`, sauberere responsive Oberfläche, Kennzahlen, priorisierte/filtrierbare JTL-Prüffälle. Link aus Haupt-Artikelmanager und Scanliste. Direkter Sprung vom Qualitätstreffer zum bestehenden Artikel per EAN oder JTL-Artikelnummer (`?q=`).
- **Neue API:** `api/jtl-quality.php` mit `api/hw24-quality-rules.php`. Nur aktuelle, vollständige JTL-MariaDB-Quellsnapshots; bei Abgleich letzte `ready`-Quelle und `last_row_count`; stale >45 Minuten sichtbar; fehlende Quelle/unvollständige Zählung -> keine fingierten Teilstatistiken. Keine neue SQL-Migration, keine DB-Schreibrechte, kein OpenAI-Kostenereignis, kein JTL-/WMS-/Shopify-Write.
- **Erste Prüfkriterien:** EAN/GTIN fehlt/Prüfziffer fehlerhaft, HAN/Hersteller/Artikelname/Kurzbeschreibung/Beschreibung fehlen, Warengruppe fehlt oder Fremdschlüsselname ungeklärt, Lieferstatus-ID nicht belegt, **JTL-Spiegelbestand >0, aber weder Netto-EK noch letzter EK positiv** als priorisierter Prüfhinweis. Sonstige Lieferanten-EK, Kommission, WMS-Verfügbarkeit, verantwortliche Person und Kosmetik-Compliance sind **noch nicht gesichert abgedeckt**; keine Behauptung echter Fehler oder vollständiger Qualität.
- **Abnahme als Nächstes:** In Plesk **nur TEST** `deploy/staging` Pull → Bereitstellen. Im eingeloggten Browser `https://test-artikelmanager.hairworld24.de/qualitaetszentrum.html` aufrufen; Snapshotzeit, tatsächliche JTL-Artikelzahl, kritische EK-Lücken und einen Artikel-Deep-Link prüfen. Erst danach Qualitätsquoten aus echten Ergebnissen nennen und Bestandsartikel-Entwurfs-/Freigabesystem entwickeln.
- **Doku:** [HW24 Qualitätszentrum V4.23](./HW24_QUALITAETSZENTRUM_V423_20261121.md), Gesamtarchitektur [Issue #30](https://github.com/NicoVoss1/HW24-scanner/issues/30). **Live-Domain auf `main` unverändert**, alte V11–V13-Skripte/Backups nicht wiederholen.

## NEUER FACHSTAND 21.11.2026 · KI-ARCHIV E2E-BELEG UND INTELLIGENCE-CONSOLE-PLAN

- **Tatsächlich archivierter Datensatz:** Nutzer zeigt authentifizierte Antwort von `GET /api/ai-reviews.php` mit `ok:true`, `stored:true`, `count:1`, `next_before_id:null` und vollständigem KI-Review ID **1**, EAN **8027483835211**, Artikel Selective Professional ONcare Leave-in Daily Hydrating Spray 275 ml. `gpt-5.4-mini`, `status:pass`, `usd_cost:0.0014655`, `input=478`, `output=246`; ASIN B0C1ZSSNJJ. **Damit sind API-Lesbarkeit und mindestens eine vollständige gespeicherte Auswertung real belegt**. Keine beweisbare JTL-/Shopify-Schreibänderung.
- **Qualitätsvorbehalt:** Der KI-Bericht schlägt `Haarstyling` für ein als `Daily Hydrating Leave-in Spray` beschriebenes Produkt vor. Das ist ein **zu prüfender Taxonomie-/Produktart-Konflikt**, kein automatisch verifizierter JTL-Warengruppenwert. `pass` bestätigt nur die modellseitige Produkt- und Packungsidentität innerhalb des angegebenen Quellmaterials.
- **Neuer Nutzerauftrag:** Eine zentrale, sehr übersichtliche Intelligence-Konsole statt parallel wachsender Einzelmasken. JTL-Wawi bleibt führendes Stammdaten-/Bestands-/Kosten-System; HW24 soll alle bestehenden wie neuen Artikel quellengeprüft analysieren, fehlende Felder, EK trotz Bestand, EAN, HAN, Hersteller, Lieferstatus, Beschreibung und produkttypabhängige Compliance anzeigen, nutzerbestätigte Änderungen als Entwurf speichern und später kontrolliert in Ameise/Shopify/Social übernehmen.
- **Architektur-Auftrag:** [Issue #30](https://github.com/NicoVoss1/HW24-scanner/issues/30) mit genauer UI-Informationsarchitektur, Qualitätstaxonomie, JTL/Ameise- und Shopify-Feldhoheit, KI-/Amazon-Deduplikation, Rollenkonzept, kontrollierten Pilot-/Trockenläufen, Grundgerüst-Vergleich **Tabler vs. Laravel/Filament vs. Akeneo/Pimcore** und Testkriterien. Bestehende [Shopify-Read-only-Aufgabe #28](https://github.com/NicoVoss1/HW24-scanner/issues/28) bleibt eine Teilaufgabe.
- **Noch nicht implementiert:** Zentrales neues UI, globales Berechtigungs-/KI-Review-Caching, produktweite Qualitätsstatistik über den JTL-Gesamtbestand und automatische Shopify-/Wawi-/WMS-Publikation. **Keine** Live-, Plesk-, JTL- oder Shopify-Schreibänderungen in dieser Beratung veranlasst.

## NEUESTER CODESTAND · 21.11.2026 · PR #29 KI-DOPPELABFRAGENSPERRE

**21.11.2026 · PR #29 (JETZT TEST):** `deploy/staging` SHA **`1618ebac749a6cc1854a8e0673da4725f5bcf984`**. `GET /api/ai-reviews.php` ohne `before_id` wird nicht länger irrtümlich als `invalid_cursor` abgewiesen. Authentifizierte Scanlistenpositionen zeigen nach erfolgreicher, dauerhaft archivierter KI-Analyse `🤖 KI geprüft`; Wiederholungen derselben EAN verwenden den vorhandenen Prüfbericht mit 0 USD neuen API-Kosten, solange nicht bewusst `🔄 Neu prüfen ($)` gewählt wird. Die erneute Prüfung ist zusätzlich revisionsgesichert gegen zwei gleichzeitige Browser-Tabs. [CI #44950463294 erfolgreich](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44950463294). **V13 vom Nutzer bereits erfolgreich installiert, nicht erneut ausführen.** Plesk-Test-Pull/-Deploy und Browser-E2E der neuen Version noch nicht bestätigt. Live-Branch `main` unverändert. [PR #29](https://github.com/NicoVoss1/HW24-scanner/pull/29).

**Nächste Einzelaktion:** Plesk-Testseite `deploy/staging` Pull → Bereitstellen, danach `GET /api/ai-reviews.php` und Scanliste mit tatsächlich gespeicherter KI-EAN überprüfen. Die **erste** vor V13 bezahlte KI-Prüfung ist nicht als fachlicher Archivbericht gespeichert und daher kein Marker.

## NEUESTER NUTZER-NACHWEIS · 21.11.2026 · V13 INSTALLIERT UND PRODUCT DATA HUB GEPLANT

- Der Nutzer hat per PuTTY bestätigt, dass das einmalige V13-Installationsskript mit `ARCHIV` erfolgreich abgeschlossen wurde; Ausgabe `bisherige_ki_pruefungen=0` und `OK: KI-Prüfarchiv eingerichtet`. **Kein erneuter Installerlauf nötig.**
- Noch offen: Authentifizierter Browser-GET `/api/ai-reviews.php` und ein neuer erfolgreicher KI-Bericht mit `stored:true`, Prüf-ID und Wiederaufruf nach Reload. Ein reiner Terminal-Installationsnachweis ist **kein** vollständiger End-to-End-Test.
- Shopify-Konfiguration über angeschlossenen Store lesend verifiziert; Qualitätslücken in Produktklassifikation/SEO-Overrides bei kleiner Stichprobe entdeckt. Keine Shopify-Schreibänderung ausgeführt; private Shop-Metriken bewusst nicht im öffentlichen GitHub dokumentiert.
- Strategischer nächster Meilenstein: [HW24 Product Intelligence Hub](./HW24_PRODUCT_INTELLIGENCE_HUB_STRATEGIE_20261121.md), zuerst quellengeprüfte Produktfakten mit zentralem Identitäts-/Lizenzmodell, **Shopify/JTL lesender Bestandsabgleich** über [Issue #28](https://github.com/NicoVoss1/HW24-scanner/issues/28), danach einzelne freigegebene Shop-Produktpilotartikel, später kanalweise Wawi/Shop/Social-Verteilung. **Kein** direkter Wawi/WMS-/Shop-Live-Write freigegeben.
- Social App: Vor Kanalpublikation muss eine eigenständige HAIRWORLD24-Markenanbindung abgegrenzt von anderen Projekten geprüft werden.

## AKTUELLSTER TESTSTAND · 21.11.2026 · KI-PRÜFERGEBNISSE PERSISTENT (PR #26/#27)

- **GitHub-Testrelease:** [PR #26](https://github.com/NicoVoss1/HW24-scanner/pull/26) und [PR #27](https://github.com/NicoVoss1/HW24-scanner/pull/27) in `deploy/staging` gemerged. Aktueller TEST-Commit **`6e6e7f7dfd536f4bef42c401321cafe95f855004`**. GitHub CI [#44933326129](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44933326129), [#44933692434](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44933692434) grün. **Noch kein Plesk-Pull/Deploy/realer MariaDB-E2E-Nachweis dieser Version.**
- **Ausgang:** Erste OpenAI-Analyse Wella Color Touch 9/01 lief, Kosten 0,0013 USD. Bisheriger Kostenledger `hw24_private/hw24-ai-usage.json` speicherte nur Monats-/Tokenkosten, nicht das fachliche Ergebnis. Frühere Analyse **nicht rückwirkend rekonstruierbar**.
- **Neu:** Große responsive KI-Auswertungsansicht mit EAN-/Gesamt-Historie, wiederaufladbaren Berichten, Token-/Kosten-/ASIN-/Packungs- und Produktfakten, Quelleneingaben und Amazon-Links. `api/ai-reviews.php` ist authentifiziert, lesend und nach EAN/Datum/Actor indexiert. `api/ai-review.php` erstellt nach jedem neu erfolgreichen, bezahlten Modellaufruf **einen unveränderlichen MariaDB-Datensatz**, erst nach erfolgreichem INSERT Meldung `stored:true`/ID.
- **Übernahme in die Artikelpflege:** Nur nach ausdrücklicher menschlicher Freigabe und nur bei **neuen** Scanlistenartikeln können Warengruppe oder vom Ursprungs-Amazon-Katalog belegte, KI-`pass`-bewertete ASIN in den auditierbaren HW24-Entwurf übernommen werden. **Keine automatische Wawi-/WMS-Änderung oder Neuanlage**.
- **Erforderliches neues Setup:** Einmalige **V13 KI-Archiv-Tabelle** `hw24_ai_reviews` in vorhandener MariaDB plus engste Berechtigung `SELECT,INSERT` für `hw24_scan_rw`; **keine** Wiederholung der erledigten V11/V12-Installation, Backups oder SQL-Basissetups. Sicheres PuTTY-Skript aus Commit `7249fbd70089682f23248c1502d49a3e4ef91699`, Pfad `ops/HW24_INSTALL_AI_ARCHIVE_TEST.sh` (siehe [Installation/Details](./HW24_KI_ARCHIV_UND_ANALYSEN_20261121.md)).
- **Kosten-Gate:** GET `api/ai-review.php` meldet neu `archive_ready`. Bis Tabelle und Berechtigung vorhanden sind, ist der Button **KI-Prüfung starten deaktiviert**. Kein kostenpflichtiger Aufruf vor bereitgestelltem Archiv; Monatslimit 5 USD bleibt zusätzlich aktiv.
- **Nächster konkreter Schritt:** Nur Plesk-Testdomain Git `deploy/staging` → Pull → Bereitstellen, danach V13-PuTTY-Installer **einmalig** ausführen, danach `GET /api/ai-reviews.php` kontrollieren. Erst anschließend **eine** kostenpflichtige Testprüfung und Speicherung nach Reload nachweisen. **Live auf `main` unverändert.**
- **Vollständige Anleitung:** [HW24 KI-Archiv und große Analysen](./HW24_KI_ARCHIV_UND_ANALYSEN_20261121.md).

## AKTUELLER GITHUB-TESTSTAND · PR #25 / OPENAI-FEHLERDIAGNOSE (21.11.2026)

- **PR #25** in `deploy/staging` gemerged, Commit **`2c23e926de791f8055342c724d4345b96fb95b5b`**, [CI #44931041873](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44931041873) grün. Nur `api/ai-review.php` und `assets/hw24-ai-cost.js` geändert; keine SQL-Migration, keine Secrets, keine Live-Änderung.
- **Auslöser:** Sichtbarer Nutzer-Screenshot vom jüngeren Teststand: Scanliste mit 25 Positionen, 23 Neuanlagekandidaten, zwei bereits in JTL, Wella Color Touch 9/01 mit drei Amazon-ASIN-Kandidaten; erster Kandidat `B079LHL53N` enthält Amazon-Einzelstückattribute. HW24 KI-Anzeige meldete `openai_request_failed`, 0 erfasste monatliche API-Aufrufe.
- **Technischer Befund:** `api/ai-review.php` gab `upstream_http` zurück, das Browser-Widget ignorierte diesen HTTP-Status. Patch zeigt nun sichere `upstream_http`, streng gefilterte Provider-Fehlercode/-typ/-parameter oder cURL-Fehlernummer. UI erläutert HTTP 400/401/403/404/429/500 und Netzfehler. Kein automatischer neuer API-Call.
- **Wichtig:** Die reale Ursache hinter `openai_request_failed` ist noch unbekannt. `GET /api/ai-review.php` belegt **nur** die lokale Konfiguration, nicht OpenAI-Authentifizierung/Abrechnung. 0 USD in HW24 ist keine vollständige OpenAI-Rechnung.
- **Nächster Schritt:** **Nur** Testdomain Plesk `deploy/staging` Pull → Bereitstellen; Seite mit Strg+F5 neu laden und **höchstens eine** ausdrücklich bestätigte KI-Abfrage durchführen. Danach sicheren HTTP-Code und Fehlercode melden, niemals API-Key. Erst bei identifiziertem Status über Projekt-Billing/Modellzugang/API-Format entscheiden.
- **Bildhinweis:** Ein zusätzlich übermittelter Screenshot zeigt möglicherweise eine ältere Oberfläche mit nur 1 Position, altem „Vorlage Haarfarben einsetzen“-Button und ohne KI-Widget. Das weicht von dem jüngeren 25-Positionen-Screenshot ab. Vor Diagnose sicherstellen, dass tatsächlich die aktuelle Testseite mit dem neuen GitHub-Commit und nicht eine ältere Aufnahme oder Cache-Version geprüft wird.

## AKTUELLSTER GITHUB-STAND · PR #24 / V4.22 TEST (21.11.2026)

- **GitHub:** [PR #24](https://github.com/NicoVoss1/HW24-scanner/pull/24) gemerged, `deploy/staging` SHA **`41bd862178d23a2e385d253767898318a490fdca`**. CI [#44923871216](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44923871216) grün. **Kein bestätigter Plesk-Pull/Deploy dieses Commits, kein realer JTL-/OpenAI-End-to-End-Test.**
- **Vorhandene Quelle korrekt eingegrenzt:** HW24 nutzt derzeit den MariaDB-Snapshot der JTL-SQL-Server-Synchronisation, **nicht** den uneingeschränkten Direktzugriff auf sämtliche JTL-Wawi-Stammtabellen.
- **Neue Funktionen:** `api/jtl-options.php` liefert die im aktuellen JTL-Spiegel beobachteten Auswahlwerte (Hersteller, Warengruppe, Lieferstatus, verfügbare Kategorien/Zeitwerte). Scanliste erlaubt Dropdowns in der lesenden Vergleichsansicht auch bei Bestandsartikeln; für Neuanlagen sind Optionen und eine deutlich als ungeprüft markierte freie Ergänzung möglich. Haupt-JTL-Profil nutzt dieselbe Quelle. Es gibt einen Direktlink vom Scan zur Artikelpflege.
- **ASIN-Fehler behoben:** Bestätigte ASIN ersetzt einen vorhandenen Vorschlag in der Scanliste **nur nach expliziter Wahl** und wird revisionssicher in MariaDB gespeichert; die Hauptmaske synchronisiert ihre echte ASIN-Eingabe. Kein direkter JTL-Schreibzugriff.
- **Optionales KI-Modul mit Kosten:** `api/ai-review.php` und `assets/hw24-ai-cost.js` bilden eine serverseitige, manuell bestätigte OpenAI-Produktprüfung mit privatem Projektschlüssel, Tokenkosten-Schätzung in USD, Monatsbudget und Tageslimit. **Ohne privaten Schlüssel auf Plesk bleibt die KI deaktiviert und löst keine OpenAI-API-Kosten aus.** Die Anzeige erfasst nur HW24, nicht sämtliche OpenAI-Rechnungen/andere Projekte.
- **Live:** Plesk-Screenshot vom Nutzer zeigt die Live-Domain `artikelmanager.hairworld24.de` weiterhin auf Branch **`main`**. Kein Live-Pull oder Live-Deployment veranlasst.
- **Nächster Schritt:** Testseite in Plesk `deploy/staging` → Pull → Bereitstellen. Dann neue Dropdowns, ASIN-Speicherung nach Reload und den deaktivierten KI-Kostenzähler prüfen. **Erst danach** den eigenen API-Schlüssel mit konservativem Monatsbudget auf dem Server hinterlegen und eine ausdrücklich bestätigte Prüfung vornehmen. Bereits abgeschlossene Plesk-/DB-Backups und Tabelleninstallation nicht wiederholen.
- **Konfiguration & Anforderung:** [JTL-Referenzen und OpenAI API](./HW24_JTL_REFERENZEN_OPENAI_API_20261121.md). Der vollständige Katalog noch fehlender JTL-Referenzwerte ist künftig per gezielter read-only Erweiterung des bestehenden Windows-Synchronisators einzubeziehen, nicht durch erneute pauschale Benutzerexporte.

## AKTUELLER STAND 21.11.2026 · PR #23 AMAZON-LINKS/PRODUKTABGLEICH

**21.11.2026 · PR #23:** Neue Amazon-ASIN-Direktlinks und validierte 1/2/3-Pack-, Farbcode-, Marken- und Gebindeprüfung sind in GitHub-`deploy/staging` gemerged. **Aktueller SHA `753abbbe4c9e25d99ec1f821d7e058271fbc26d0`**, [CI #44910579185 erfolgreich](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44910579185). Warengruppenkonflikte werden als Vorschläge gewarnt, JTL-Quellwerte bleiben unverändert. **Kein bestätigter Plesk-Test-/Live-Deploy.** Weitere Abnahme und exakte Vorbedingungen für KI/Live: [ASIN, KI und Live-Freigabe](./HW24_KI_ASIN_LIVE_FREIGABE_20261121.md). Der konkrete Wella-Color-Touch-9/01-Treffer ist ohne seine ASIN nicht real verifiziert.

**Nächster Schritt:** Testdomain in Plesk `deploy/staging` Pull → Bereitstellen; konkrete Wella-ASIN und Mehrfachpack im Browser prüfen. **Live nicht automatisch umstellen.** Erst Plesk-Live-Git-/Bereitstellungspfad und eigene Datenbank-/Private-Konfiguration vergleichen, dann getrennten, rückrollbaren Live-Release planen.

## AKTUELLSTER GITHUB-TESTSTAND · 20.11.2026 (PR #22)

- **PR #22** erfolgreich in `deploy/staging` gemerged: **`7db542548f7ba4c31ddf12d65b1e1866ff0592ba`**. GitHub-Codefreigabe belegt, **neuer Plesk-Pull/Deploy noch nicht bestätigt**.
- **Umgesetzt:** Pro Scanposition Button `−1` (Doppelscan korrigieren) und `Entfernen` (revisionssicher aus aktiver Liste archivieren; kein DB-DELETE); erneuter Scan oder CSV reaktiviert. CSV-Import mit EAN/GTIN, Lagerplatz/Information/Kommentar für bis zu 5.000 Zeilen, idempotenten, gedrosselten API-Blöcken, Fortschritt, Duplikatkontrolle und serverseitigen Importhinweisen in `source_json.csv_meta`. Kein JTL-/WMS-Schreibzugriff.
- **JTL-Artikelnummernkreis:** Benutzerwunsch geprüft anhand der offiziellen JTL-Guide-Dokumentation. Neue Artikelnummer für mögliche Neuanlagen bleibt **leer**; JTL-Ameise `Artikelnummern automatisch vergeben, falls nicht vorhanden → Ja, globale fortlaufende Nummer und Präfix beachten` ist zu verwenden und im Testimport abzusichern. Die echte globale Nummer ist im aktuellen read-only HW24-Spiegel nicht verfügbar. **Keine Nummer erfunden**.
- **Weitere Regeln:** `ml` statt `ML`, Brutto-VK aus belegtem Netto-VK + bestätigtem Normalsteuersatz, Dusicolor/Hair Styling wird bei widersprüchlicher Quelle als Konflikt markiert (Ursprung in echter JTL-Zeile noch offen), ergänzender Button heißt `Fehlende Haarfarben-Standards ergänzen`; interne JTL-Anmerkung nicht durch CSV-Kommentar überschreiben.
- **Teststand:** [GitHub Actions #44831637094](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44831637094) grün, einschließlich 1.500-EAN-CSV-Parser, PHP-/JS-Syntax, Maßen, Netto-/Brutto-VK und Warengruppenwarnung. **Serverseitige Browser- und MariaDB-Tests dieser neuen Funktion noch offen.**
- **Website-Root:** nur `api/`, `assets/`, `index.html`, `scanliste.html`. Keine SQL-, Sync-, Private- oder Dokumentationsdateien öffentlich. Neue DB-Schema-Migration nicht erforderlich; vorhandene V11/V12 bleiben installiert. Kein Wiederholen von Backups/PuTTY.
- **Exakt nächster Schritt:** Plesk `deploy/staging` → **Jetzt Pull ausführen → Jetzt bereitstellen**. Erst Doppelscan/Entfernen/erneut scannen mit Test-EAN prüfen, danach 3-Zeilen-CSV mit Kommentar und Lagerplatz. Erst nach erfolgreichem Reload-Beleg die rund 1.500 EANs importieren.
- **Dokument:** [Scanliste, CSV und Artikelnummern V4.22](./HW24_SCAN_CSV_NUMMERNKREIS_V422_20261120.md).

## LETZTER BESTÄTIGTER GITHUB-STAND · 20.11.2026 (PR #21)

- **Aktueller Staging-SHA:** `2f88d5a5dca6a8df80acbc38aba5287d960583c1` auf `deploy/staging`. [PR #20](https://github.com/NicoVoss1/HW24-scanner/pull/20) und [PR #21](https://github.com/NicoVoss1/HW24-scanner/pull/21) erfolgreich gemerged, **noch kein bestätigtes Plesk-Deployment oder Server-End-to-End-Test dieser Version**.
- **Zusätzlicher Fix #21:** Bei **manuell geprüfter** Amazon-ASIN-Auswahl werden neben ASIN auch fehlender Produktname und Hersteller direkt aus dem gewählten Treffer übernommen, ohne vorhandene JTL-/Manuallwerte zu überschreiben. Zwei JS-Assets geändert.
- **CI #21:** [GitHub Actions #44827355974](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44827355974) grün. Freigegebener Staging-Root weiterhin ausschließlich `api/`, `assets/`, `index.html`, `scanliste.html`. Keine SQL-, Private- oder Dokumentationsdateien öffentlich.
- **Genau nächster Schritt:** Plesk `deploy/staging` **Pull → Bereitstellen**, Seite `/scanliste.html` aktualisieren, Stapel `04-001`, bestehende EAN `8033219164159` für lesbare JTL-Vorbelegung auswählen; danach echte Neuanlage scannen und Dauerhaftigkeit, Quellnachweis und gegebenenfalls ASIN-Mehrfachauswahl testen. **PuTTY-/SQL-Installation, Backups und Verzeichnisprüfung nicht wiederholen.**
- **Aktuelles Fachprotokoll:** [Automatische Vorbelegung V4.22](./HW24_AUTO_VORBELEGUNG_V422_20261120.md).

## AKTUELLER SOFTWARESTAND · V4.22 TEST MIT GEMEINSAMER VORBELEGUNG (20.11.2026)

- **Nutzerbefund:** Die Scanliste `04-001` hat die EAN `8033219164159` dauerhaft gespeichert, JTL erkennt `Inebrya Color Pastel Roso Intense Pink 100 ml` als bestehend. Damit **funktioniert der Scanlisten-DB-Speicherweg bereits**, aber die bisherige Pflegeanzeige ließ zahlreiche Felder leer. Nochmaliges SQL-/PuTTY-Setup ist **nicht** nötig.
- **Neue Implementierung:** [PR #20](https://github.com/NicoVoss1/HW24-scanner/pull/20) in `deploy/staging` gemerged. **GitHub-Staging-Commit `dc9646d4863772dab823283be5d8f4bbec8f3c99`**. Drei neue zentrale Browsermodule: `assets/hw24-enrichment.js`, `assets/hw24-scan-autofill.js`, `assets/hw24-main-enrichment.js` sowie Änderungen in `index.html` und `scanliste.html`. Exakt **5 Website-Dateien** geändert/ergänzt; weiterhin keine SQL-, Secret- oder internen Dateien im öffentlichen Webroot.
- **Funktionsumfang:** Vorbelegung aus frischem JTL, freigegebenen Stammdaten, konsistenter Excel-Historie und dedupliziertem Amazon-Katalog; source-annotierte Vorschläge; Artikelidentität/Hersteller (auch aus belegtem Markenwort), HAN=GTIN nur als gekennzeichnete Regel, Warengruppe/Kategorie Haarfarben, SEO, EUR, Artikeltyp, Steuervorgabe, ml-Inhalt, geschätzte Gewichte +20g, sichere Lager-/Versandstandards. Bestehende JTL-Artikel werden nur lesbar ergänzt, niemals per Scanliste neu importiert.
- **Amazon:** Automatische Katalogabfrage bei Scan/Auswahl mit bestehendem serverseitigen Cache; **mehrere ASINs** aus Amazon/JTL/Historie als explizit auswählbare Kandidaten; kein stillschweigendes Zuordnen von 2er/3er/4er-Packungen.
- **Mengenverarbeitung:** Schneller Scannerworkflow mit eigener EAN-Löschtaste; browserseitig fortsetzbare Warteschlange speichert neu gescannte Artikelentwürfe mit Quellennachweis auch dann, wenn bereits der nächste Artikel aktiv ist. **Kein serverseitiger Nachtjob**; Queue läuft, solange die Seite geöffnet ist, und wird bei Wiederaufruf fortgesetzt.
- **Geprüft:** [GitHub Actions #44826801120](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44826801120) **grün**, inklusive Node-Regeltests (Hersteller/HAN, Kategorien, 60/100 ml, ASIN-Mehrfachpackungsschutz, widersprüchliche Historie), PHP-/JavaScript-Syntax und HTML-Assetprüfung.
- **Noch NICHT geprüft:** Plesk-Pull/Deploy **dieses** neuen Commits, echte JTL-/Amazon-/MariaDB-Integration mit der neuen Anreicherung, ASIN-Mehrfachauswahl im Browser und revisionssicherer 10–20-Artikel-Test. Steuerklassen- und Preisannahmen vor Import prüfen. Kategorie 2/3, reale Lieferanten-IDs und unbestätigter Lieferstatus **nicht erfunden**.
- **Exakt nächster Schritt:** Plesk Git-Branch `deploy/staging` behalten → **Jetzt Pull ausführen → Jetzt bereitstellen**. Auf `/scanliste.html` EAN `8033219164159` im Stapel `04-001` anklicken und die vorbefüllte Anzeige prüfen; dann eine **nachweislich nicht vorhandene** EAN scannen und nach Reload die persistent gespeicherten Ergänzungen prüfen.
- **Vollständiger Nachweis:** [Automatische Vorbelegung V4.22](./HW24_AUTO_VORBELEGUNG_V422_20261120.md). **Alte Backups, PuTTY-Installation und SQL bleiben als erledigt dokumentiert**.

## AKTUELLER MEILENSTEIN · V11/V12 DATENBANKINSTALLATION ERFOLGREICH GEMELDET

- **Erledigt (Nutzerprotokoll):** PuTTY-Installer auf `root@admiring-elion` ausgeführt, Test-API und privaten Pfad erkannt, `TEST` bestätigt. Skript meldet die erfolgreiche Anlage der fünf neuen Scanlisten-/Amazon-Cache-Tabellen, minimal privilegierten Benutzer und privaten `scanlist-db.php`. Keine erneute Installation durchführen.
- **Noch offen:** Echter End-to-End-Nachweis von Datenbankverbindung und Schreibvorgang im Browser. Nächster Schritt ist Testseite neu laden, Stapel `04-001` erstellen, EAN `8033219164159` erfassen und nach erneutem Laden die gespeicherte Position bestätigen.
- **Fehlerprotokoll:** Nur falls der Scan fehlschlägt, gezielt die konkrete Meldung prüfen; keine allgemeinen Backups, Plesk-Pfade oder Serverinstallationen wiederholen.
- **Quelle:** [GitHub-Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Eintrag „PuTTY-Installation V11/V12 auf Testserver“.

## HISTORISCHER STAND VOR ERFOLGREICHER INSTALLATION · 20.11.2026, 22:15 Uhr · PLESK TEST-DATENBANK

**Vom Nutzer per Screenshot belegt:** Neue Scanliste läuft als Website. Die separate Scanner-MariaDB-Anbindung ist noch nicht eingerichtet; neue Scan-Tabellen (V11) und Keyword-Cache-Tabelle (V12) fehlen laut lesender Diagnose oder sind dem aktuellen DB-Nutzer noch nicht sichtbar. Die Meldung „Bitte zuerst einen Stapel erstellen“ ist Folge der noch nicht funktionsfähigen Stapel-API; noch keine erfolgreiche Speicherung bestätigt.

**GitHub-Code bereits korrigiert:** [PR #19](https://github.com/NicoVoss1/HW24-scanner/pull/19) wurde nur mit `api/amazon-keywords.php` nach `deploy/staging` gemerged, Commit **`2175c375fdce4afe2b335dafb43167505f7123a0`**. Die Amazon-Stichwortcache-Schreibvorgänge verwenden danach das neu anzulegende `hw24_private/scanlist-db.php`, **nicht** die bisherige schreibgeschützte `db.php`. Kein DB-Schritt auf Plesk ausgeführt; Live-Seite unverändert.

**PuTTY-Installer vorbereitet, aber auf Plesk NICHT ausgeführt:** `ops/HW24_INSTALL_SCANLIST_TEST.sh` im Branch `feature/hw24-v422-scanlist-install-20261120`, gepinnter Commit **`a3f0d8592a926fb348b589a76b2170cee1ab67c3`**. Der Installer erkennt ausschließlich die Test-API samt bereits vorhandener privater `db.php`, fordert explizit Eingabe TEST, legt fünf neue Tabellen an, erstellt einen separaten DB-Benutzer mit starkem zufälligem Passwort und minimale Tabellenrechte, erstellt `scanlist-db.php` ausschließlich außerhalb des Webroots. Die bestehende `db.php`, SP-API-Token, JTL/WMS und Livebestände werden nicht verändert. **Einmalige Ausführung durch den Nutzer über PuTTY mit root-/Plesk-DB-Rechten erforderlich**.

**Keine Wiederholung der bisherigen Sicherungen/Ordner-/Plesk-Konfiguration!** Konkrete Copy-Paste-Befehle, Grenzen und Status in [HW24 PuTTY Scanlist-Setup](./HW24_PUTTY_SCANLIST_SETUP_20261120.md).

**Exakt nächster Nutzerschritt:** In Plesk Branch `deploy/staging`: Pull → Bereitstellen (PR #19), danach **einmalig** PuTTY-Installer nach verlinkter Anleitung. Im Browser `/scanliste.html` Diagnose prüfen, Stapel `04-001` erstellen und den Scan erneut durchführen. Passwörter niemals in Chat oder GitHub ausgeben.

## AKTUELLER STATUS NACH DEN NEUEN NUTZER-SCREENSHOTS

- **TESTSEITE IN PLESK SICHTBAR:** Der Nutzer hat am 20.11.2026 Screenshots von V4.22 TEST inklusive neuer `scanliste.html` geliefert. Damit ist die Website-Bereitstellung aus PR #17 praktisch bestätigt; **nicht erneut den vorherigen Release oder allgemeine Backups verlangen**.
- **KONKRETER FEHLER BESTÄTIGT:** Die neue Scanliste meldet **„MariaDB noch nicht konfiguriert“**. Im Code bedeutet das, dass der zusätzliche private Zugang `hw24_private/scanlist-db.php` am erwarteten Ort nicht gefunden wird. **Das ist kein Nachweis für eine defekte bereits bestehende JTL-/MariaDB-Anbindung.** Ob V11-/V12-Tabellen existieren, wurde noch nicht serverseitig festgestellt.
- **NEUER TESTRELEASE IN GITHUB:** [PR #18](https://github.com/NicoVoss1/HW24-scanner/pull/18) gemerged, **`deploy/staging` @ `bcb8a9664f08aeb66de5aa7efd461f284affae99`**. Enthält neue lesbare Header-Statusleiste, SVG-Favicon, 41 geprüfte JTL-Stammdatenfelder in fünf Gruppen sowie read-only `api/scanliste-status.php` zur zielgenauen Diagnose.
- **CODETESTS:** [GitHub Actions #44786470819](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44786470819) grün; geprüft sind PHP-/JS-Syntax, Inline-Skripte, Favicon/SVG, Referenzen und Feld-Schnittstellen. **Kein** Ersatz für echte Browser-/DB-End-to-End-Tests.
- **EXAKT NÄCHSTER SCHRITT:** Plesk bleibt auf `deploy/staging` → `Jetzt Pull ausführen` → `Jetzt bereitstellen` → `https://test-artikelmanager.hairworld24.de/scanliste.html` öffnen → zielgenauen Hinweis aus der neuen **Scanlisten-Diagnose** ablesen. Nur fehlende **neue** V11/V12-Tabellen bzw. die **separate** private Scanlisten-Verbindung ergänzen. **Keine erneuten Backups/Verzeichnis-Checks** ohne konkreten Fehler.
- **SICHERHEIT:** GitHub Staging-Root nach PR #18 geprüft: `api/`, `assets/`, `index.html`, `scanliste.html`; **keine** öffentlichen `sql/`, `docs/`, `sync/`, `hw24_private/`. Live-Seite, Wawi und Bestände unverändert.
- **UI-/Feldabnahme dokumentiert:** [HW24 UI, JTL-Feldmaske und Diagnose](./HW24_UI_JTL_FELDMASKE_20261120.md). Ursprüngliche Wawi-Screenshots aus früheren Chats sind im derzeit durchsuchbaren Dateiindex nicht aufgefunden worden; die Zuordnung basiert auf dem dokumentierten 872-Spalten-JTL-Export und `assets/reiter.js` (13 Reiter).

## 1. Projektziel / verbindlicher End-to-End-Workflow

1. Paket öffnen, EAN/GTIN scannen, Eingangsposition **sofort** als Scanereignis in HW24-MariaDB speichern, weiterscannen.
2. Den aktuellen JTL-Artikelspiegel prüfen: **vorhanden**, **mögliche Neuanlage**, **Mehrfachtreffer**, **JTL-Abgleich offen/veraltet**. Ein unbekannter JTL-Stand ist ausdrücklich **keine bestätigte Neuanlage**.
3. Im Hintergrund bzw. nach erfolgreicher Erfassung Amazon-Katalogdaten **automatisch** und cache-/kontingentbewusst abfragen; bei eindeutiger JTL-Auflösung SKU/HAN → EAN, bei abgeschlossener Produktsuche Amazon-Kandidaten anzeigen. Keine unnötigen mehrfachen SP-API-Calls.
4. Alle unbearbeiteten Farben im **dauerhaften, zentralen** Scan-Stapel sammeln. Zielmenge zunächst ca. 1.000–2.000 Farben; gruppierte Prüfung und Stammdatenpflege statt Einzelanlage während der Erfassung.
5. Felder aus aktuellem JTL-Spiegel, freigegebenem HW24-Artikelstand, historischen Excel-MariaDB-Quellen, geprüften Standards und Amazon-Katalog **mit Herkunft/Prüfstatus** vorschlagen. Unklare ASIN, Farbe, EAN, Variante, Menge, Steuer, Hersteller und EK nicht automatisch als sicher übernehmen.
6. Eindeutige Neuanlagen aus einem freigegebenen Stapel als Excel-kompatible **JTL-Ameise-CSV** exportieren, mit dem **tatsächlich getesteten Ameise-Importprofil** und zugeordneten Kopfzeilen. JTL-Ameise prüft den Import zuerst im kleinen Testlauf.
7. Warenwirtschafts-/WMS-Bestandsbuchung ist **separat** vom Scanlisten-Speichern und von der Artikel-Neuanlage. Kein versehentliches Einbuchen.
8. Perspektivisch günstige *wirklich bestellbare* Amazon-Angebote als Einkaufschancen identifizieren (Buy Box, tatsächlicher Verkaufspreis, Netto/Brutto, Amazon-Gebühren, Versand, EK, Retouren, Produktrechte und Risiken); **keine** automatischen Käufe. Nächtliche Preisüberwachung erst nach getrenntem API-Budget-/Rate-Limit-Konzept.

## 2. Derzeit verbindliche Source of Truth

| Gegenstand | Stand / Link | Rolle |
|---|---|---|
| **Letzter bestätigter Plesk-Seitenstand** | V4.22 TEST und neue Scanliste per Nutzerscreenshot sichtbar | UI aus PR #17 sichtbar; neue Version PR #18 noch nicht in Plesk bestätigt |
| Plesk-Repository | `NicoVoss1/HW24-scanner.git` | Testdomain `test-artikelmanager.hairworld24.de`; Branch `deploy/staging` |
| GitHub-Defaultbranch | `main` · Stand 20.11.2026 älter als V4.22 | **NICHT** als aktuelle Web-Version ausrollen oder als neue Featurebasis verwenden |
| V4.22-Implementierungs-PR | [PR #16](https://github.com/NicoVoss1/HW24-scanner/pull/16) | Offener Draft, Entwicklungsdateien einschließlich SQL/Dokumentation, **nicht deployed** |
| V4.22 erste Scanlistenfreigabe | [PR #17](https://github.com/NicoVoss1/HW24-scanner/pull/17) | Gemerged; Website aus dieser Entwicklungsphase bereits per Screenshot nachgewiesen | 
| **Aktueller Github-Stagingstand** | [PR #18](https://github.com/NicoVoss1/HW24-scanner/pull/18) | **Gemerged**, SHA `bcb8a9664f08aeb66de5aa7efd461f284affae99`; **Plesk-Update noch ausstehend** |
| Prüfkandidat Website-Branch | `release/hw24-v422-scan-test-20261120` · `7dd38d4a69ecc2897653a30029c6410b943016c4` | Auf V4.22 aufgebaut, aber nur Codevorschau; **keine bestätigte Testbereitstellung** |
| Frühere abweichende Entwicklung | PR #14 · V4.35; PR #15 geschlossen | **Nicht** als V4.22-Quelle verwenden. #15 wurde mit Verweis auf #16 abgelöst |
| **Dokumentations-Wahrheit** | `main/docs/HW24_PROJEKTSTAND_AKTUELL.md` | Verbindlicher handlungsorientierter Projektstand; `main` bleibt trotzdem **nicht** Codebasis |

**Sicherheitsprinzip:** `deploy/staging` enthält nur `index.html`, `assets/`, `api/` (neue Scanlisten-Seite kommt nach Freigabe hinzu). `sql/`, `docs/`, `.github/`, `sync/`, `hw24_private/` bleiben außerhalb des öffentlich bereitgestellten Branch-Baums. Keine Datenbankzugangsdaten ins GitHub-Repository.

## 3. Bereits abgeschlossen / nicht erneut von vorn

| Thema | Status / Grundlage | Konsequenz |
|---|---|---|
| Vollständiges Plesk-Backup | **Vom Auftraggeber am 20.11.2026 als bereits durchgeführt bestätigt** | **Nicht erneut anfordern**; tatsächlicher Speicherort/Restore-Test wurden hier nicht nochmals ausgelesen |
| MariaDB-Backup | **Vom Auftraggeber am 20.11.2026 als bereits durchgeführt bestätigt** | **Nicht erneut anfordern**; vorhandene Sicherung als Ausgangspunkt nehmen |
| Verzeichnis-/Private-Pfad-Prüfung | **Vom Auftraggeber als bereits geprüft bestätigt** | Nicht erneut durch Plesk-Oberfläche führen, es sei denn, ein konkreter Laufzeitfehler weist auf einen falschen Pfad |
| Sicherung, Wiederherstellung und Syntaxprüfung der JTL-/WMS-API | In vorangehender Arbeit als erfolgreich bestätigt; API-Backup und SHA-256-Abgleich wurden vorgenommen | Bestehende geprüfte JTL-/WMS-Funktionen erhalten; keine unnötige Neuinstallation |
| Verbindung Plesk ↔ GitHub | Plesk-Screenshot bestätigt Branch `deploy/staging`, manuelle Pull-/Bereitstellungssteuerung | Keine neue Repository- oder Branch-Einrichtung |
| Staging-Release-Dateibaum | GitHub bestätigt: `api/`, `assets/`, `index.html`, `scanliste.html` | **Faktisch geprüft**, interne Verzeichnisse nicht im aktuellen Staging-Tree |
| Bestätigter Testseiten-Code | GitHub SHA `dca30b0` enthält Anzeige `HW24 Artikelmanager v4.22 TEST` | Keine Rückkehr zum veralteten `main` |
| Rückfallstand | `backup/staging-before-pr4-20261120` existiert als GitHub-Branch | Nicht löschen oder überschreiben; allein ein Branch ersetzt aber kein Plesk-/DB-Backup |
| V4.22 Amazon/JTL-Funktionen | In Code vorhanden: Amazon-Katalog mit 360-Minuten-Cache, JTL-Spiegel, WMS/Beleginformationen, historische Daten, Amazon-Preis-/Gebührenabrufe auf Anforderung | Bestehende Feature-Schnittstellen beibehalten |
| Scanliste im Test sichtbar | PR #17 in Plesk sichtbar; PR #18 als neues UI-Release gemerged | **Neue Scanlisten-Datenbank meldet fehlende private Konfiguration; DB-Schritt noch offen** |
| GitHub-Codeprüfungen | Für Entwicklungsbranch #16: GitHub Actions Syntax-/Paketcheck erfolgreich, zuletzt SHA `61c164f` | **Nicht** mit DB-, Browser- oder Plesk-Abnahme verwechseln |

**Nachweis-Trennung:** Benutzerbestätigte erledigte Maßnahmen werden ohne Wiederholung übernommen. Aus GitHub geprüfte Tatsachen sind gesondert bezeichnet. Nicht belegte Details wie exakte Backup-Dateinamen, Restore-Zeitpunkte, DB-Migrationsstatus oder Live-Website-Funktion dürfen nicht erfunden werden.

## 4. Implementierte, aber noch nicht bereitgestellte Funktionsbausteine

| Baustein | Wo | Aktueller Status |
|---|---|---|
| Schnellscan, mehrfaches Erfassen, dauerhafte Stapel und Scan-Historie | `scanliste.html`, `api/scanliste.php` | Oberfläche auf Testseite sichtbar; **Speichern blockiert, solange separate DB-Config fehlt** |
| Identifikation bestehend/neu/mehrdeutig/stale durch JTL-Spiegel | `api/scanliste.php` | Code vorhanden; echte DB- und E2E-Tests ausstehend |
| Versionierte Stammdatenentwürfe, Quell-Audit | SQL `HW24_11_SCANLISTE.sql` | Migration vorbereitet; **Durchführung nicht bestätigt** |
| Automatische Amazon-Katalogabfrage pro gespeicherter EAN | Neue Scanliste + bestehendes `api/amazon.php` | Client-Warteschlange und Cache eingebaut; globales Rate-Limit noch nicht umgesetzt |
| Amazon-Stichwort-/ASIN-Kandidatensuche | `api/amazon-keywords.php`, `assets/hw24-amazon-keywords.js` | Code vorbereitet; SQL `HW24_12_AMAZON_KEYWORD_CACHE.sql` und echte Berechtigungs-/Serverprüfung ausstehend |
| JTL-Ameise Sammelprüf-CSV | `scanliste.html` und `api/scanliste.php` | Code bereit; **Original-Exportkopfspalten sind nicht automatisch ein getestetes Importprofil** |
| Günstige Einkaufschancen / Arbitrage | Entwurf in `docs/HW24_V422_SPAPI_EINKAUFSCHANCEN_20261120.md` auf PR #16 | Konzept, **noch keine aktive Preisüberwachung und keine automatischen Einkäufe** |
| Automatische nächtliche Angebotsprüfung | Konzept | **Nicht aktiviert**, SP-API-Kontingente / Server-Jobsteuerung offen |

## 5. Wirklich offene Aufgaben – nur hier fortsetzen

**Priorität 1: Neue UI-Version PR #18 in Plesk laden und gezielte Scanlist-Diagnose nutzen.**
- PR #17 ist in Plesk **sichtbar**. PR #18 ist in `deploy/staging` gemerged, aber neues Plesk-Pull/Deploy **noch nicht bestätigt**. **Nicht erneut Backups verlangen.**
- Erforderlich ist eine **lesende Statusprüfung** der beiden neuen Tabellenstrukturen (`hw24_scan_batches`, `hw24_scan_items`, `hw24_scan_events`, `hw24_scan_draft_audit`, `hw24_amazon_keyword_cache`) und der privaten Scanlist-Verbindung – **nicht** eine nochmalige Überprüfung aller bereits bestätigten Verzeichnisstrukturen.
- Wenn die zwei SQL-Migrationen V11/V12 bereits durchgeführt worden sind, dies dokumentieren und **keine Migration wiederholen**. Wenn nicht, nach der bereits bestätigten Sicherung kontrolliert ergänzen.
- Zugriff und Nutzerrechte für die neuen Endpunkte im geschützten Plesk-Testhosting prüfen. Admin-Daten/Schlüssel niemals ins Repo oder in den Chat kopieren.
- PR #18 ist bereits auf `deploy/staging` übernommen. In Plesk jetzt **„Jetzt Pull ausführen“**, danach **„Jetzt bereitstellen“**. Anschließend die neue Scanlist-Lesediagnose nutzen.

**Priorität 2: Kurzer, nachvollziehbarer End-to-End-Abnahmetest.**
- 5–10 EANs inkl. vorhanden, neu, doppelt, mehrdeutig und JTL-Ausfall testen.
- Gespeicherten Scan-Stapel nach Browserwechsel erneut öffnen; leere Neuanlage pflegen und wiederfinden.
- Amazon-Katalogabrufe auf Cache-Hit, API-Neuabfrage, 429/Fehler und doppeltes Enter prüfen.
- Ameise-CSV auf Spalten, Semikolon/UTF-8, Preis-/Steuerwerte und Dubletten gegen **JTL-Wawi 2.2.2.0** prüfen. Erst wenige Testartikel in JTL-Ameise, keine unkontrollierte Masseneinbuchung.

**Priorität 3: Wirtschaftsmodul.**
- Serverübergreifendes SP-API-Budget, konkurrierende Sitzungen und Cache-Fehlerhandling.
- Einkaufschance nur bei echter ASIN-/Produktidentität, verifizierbarem Gesamt-Einkaufspreis und realem Deckungsbeitrag.
- Nächtliche Preisprüfung als priorisierte Watchlist, nicht blind 2.000 ungedrosselte Einzelanfragen.

**Priorität 4: Liveübernahme nach bestandener Testabnahme.**
- Erst nach dokumentierter Freigabe, gesondert in Live-Pipeline, mit Rückfallplan. Live-Shop/Wawi bis dahin unverändert.

## 6. Arbeitsregeln für alle folgenden Chat-/GitHub-Schritte

1. **Vor jeder Änderung**: diese Datei sowie `docs/HW24_ARBEITSJOURNAL.md` lesen, dann GitHub-PRs und den tatsächlichen Staging-SHA vergleichen.
2. **Nie** eine neue Arbeitsphase als Start von null behandeln; keine bereits bestätigten Backups, Verzeichnis- oder Integrationsprüfungen erneut anfordern. Nur konkrete neue Probleme prüfen.
3. **Erst Tatsachen und Status dokumentieren**, dann auf dem richtigen Stand ändern. Jede Änderung muss PR/Commit, Zweck, geprüfte Tests, Status und nächste Aktion nennen.
4. **Basis ist stets der tatsächlich bestätigte V4.22-Test-/Stagingstand plus danach ausdrücklich freigegebene Änderungen**; nicht das veraltete `main` und nicht automatisch ein höher nummerierter Branch.
5. Deploybare Dateien und SQL-/Dokumentations-/Synchronisierungs-/Privatdateien trennen. Für Test zuerst nur bewusst freigegebene Website-Dateien; **kein heimliches Auto-Deploy**.
6. **Kein Merge, Live-Deploy, DB-Schema- oder Bestandsänderung ohne passende Freigabe**; vorhandene Backups nicht ständig neu erzeugen.
7. Wenn etwas **nicht geprüft** ist, als **OFFEN/UNKLAR** markieren, nicht zu „fehlend“ oder „fehlgeschlagen“ machen.
8. Die Projektübersicht und das Journal **bei jedem relevanten Fortschritt** aktualisieren, ausdrücklich auch bei Fehlversuchen und Rollbacks. Im fertigen Chat den letzten Commit/PR und den **genau nächsten nicht erledigten Schritt** nennen.
9. GitHub-`main` dient bis zur kontrollierten Konsolidierung der **Dokumentation**, nicht dem aktuellen Webcode. Das vermeidet erneute Versionswechsel.
10. Kein geheimhaltungsbedürftiges Material oder Zugangsdaten im Repository dokumentieren, auch keine Passwortbilder oder Datenbank-Dumps.

## 7. Referenzen

- [UI-/JTL-Feldprüfung, Screenshot-Befunde und Diagnose](./HW24_UI_JTL_FELDMASKE_20261120.md)
- [PR #18 · Neue gruppierte Artikelpflege, Favicon und gezielte Diagnose](https://github.com/NicoVoss1/HW24-scanner/pull/18)

- [Verbindliches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md)
- [PR #17 · Bereinigte Testfreigabe](https://github.com/NicoVoss1/HW24-scanner/pull/17)
- [PR #16 · Entwicklungsbranch V4.22 Scanliste / Amazon](https://github.com/NicoVoss1/HW24-scanner/pull/16)
- [GitHub-Staging](https://github.com/NicoVoss1/HW24-scanner/tree/deploy/staging)
- [Abweichender alter V4.35-Branch PR #14](https://github.com/NicoVoss1/HW24-scanner/pull/14) · **nicht als Grundlage verwenden**
- [Geschlossene PR #15](https://github.com/NicoVoss1/HW24-scanner/pull/15)


### Aktualisierung 22.11.2026: Composer-Shell-Wrapper erkannt

Die nächste PuTTY-Ausgabe bestätigte `runuser -u nicovoss -- git ... rev-parse HEAD` auf dem bestehenden privaten Klon mit **exaktem SHA `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`**. Anschließender Composer-Installationsversuch schlug fehl, weil `command -v composer` auf ein **Plesk-Shell-Skript** statt PHAR verweist und versehentlich `php "$wrapper"` ausgeführt wurde. Nur Skripttext ausgegeben, **Composer noch nicht installiert, Webroots unverändert**. Tatsächliches Composer-PHAR laut Wrapper: `/opt/psa/var/modules/composer/composer.phar`. Nächster Schritt PHP-8.4-Aufruf direkt auf die PHAR als `nicovoss` (isolierte Subshell), danach Vendor-Prüfung; kein erneuter Git-Klon.

[Alle Einzelheiten und Belege im Arbeitsjournal](./HW24_ARBEITSJOURNAL.md).


### 22.11.2026 · UPDATE: Laravel 13 und Filament 5 tatsächlich als PHP-Pakete installiert

**Neue Betreiber-Ausgabe:** Korrigierter Plesk-PHAR-Aufruf unter PHP 8.4 und dem Site-Systembenutzer hat erfolgreich **Composer 2.11.2**, **Laravel 13.43.0**, **Filament 5.12.0**, **Livewire 4.4.9** aufgelöst und im privaten Framework-Klon installiert. Die **145 gesperrten Paketversionen** stehen in einer **serverlokalen** neu geschriebenen `composer.lock`, **112 PHP-Pakete** wurden mit `--no-dev` installiert. Laravel `artisan package:discover` `DONE`, `vendor/autoload.php` bestätigt, Ausgabetext `=== INSTALLATION ERFOLGREICH ===`.

**Der vorherige Composer-Wrapper-Fehlversuch ist damit behoben.** Kein neuer Git-Klon erforderlich. **Nicht verwechseln** mit einem bereits konfigurierten/öffentlich erreichbaren Laravel-Adminpanel: `.env`, App-Key, private SQLite-Migration, Admin-Zugang, Filament-Assets und Test-Webroot-Umschaltung noch **OFFEN**. Auch Live-V4.25-Webroot-Hash-/authentifizierter Browsernachweis noch offen. Bestandsdatenbanken/API-Credentials nicht angerührt.

**Nächste Aktion:** Auf Plesk unter Site-Benutzer read-only `artisan --version`, `artisan route:list --path=admin` und Live-Artikelmanager V4.25-Webroot-Hashes prüfen. Keine neuen Domains/Datenbanken und keine erneute Composer-Installation. Danach private Laravel-Konfiguration.

[Vollständiger chronologischer Einzelbeleg](./HW24_ARBEITSJOURNAL.md) (Commit `006fbf833d06b9dd89d5a663f653d75afd3444ee`).
