# HW24 Arbeitsjournal (kanonischer Projektstand)

**Gültig ab:** 2026-11-23 · Projekt: HAIRWORLD24 / HW24-scanner · Repository: `NicoVoss1/HW24-scanner`

## Zweck, verbindliche Arbeitsweise und Datenschutz

Dieses Journal ist der dauerhafte **öffentliche GitHub-Code-/Planungsnachweis** für alle weiteren Chat-Sitzungen zum HW24 Artikelmanager. Am Beginn jedes Folgetermins **diese Datei zusammen mit den offenen PRs lesen**. Anschließend jeden abgeschlossenen oder begonnenen Schritt mit Datum (UTC/Europe-Berlin), Arbeitsschritt-ID, Umgebung (GitHub/TEST/LIVE/HeidiSQL/Windows/PuTTY), geprüftem Ausgangsstand, **tatsächlich ausgeführter** Aktion, Ergebnis/Beleg, Fehlern, verbliebenen Risiken und nächster Aktion **anhängen**. Keine unbestätigten Ergebnisse behaupten.

**Niemals in dieses öffentliche Repository schreiben:** Kennwörter, Zugangstoken, `.env`-Inhalte, IPs privater Zugänge, Kunden-/Bestelldatensätze, ungeschwärzte API-Antworten, personenbezogene Daten, vollständige private SQL-Ausgaben oder Datenbankbackups. Sensible Logs ausschließlich root-/operator-privat auf dem passenden System sichern; im Journal lediglich anonymisierte Prüfsummen, Status und Referenzen nennen.

**Release-Regel:** Kein Merge, keine Installation, keine Migration auf dem Server und keinerlei JTL/Marketplace-Write ohne explizite Abnahme. Getestetes GitHub-CI ist **keine** produktive Freigabe.

## Baseline / bereits belegte Resultate

### 2026-11-22 | B001 | Betreiber-PuTTY-Protokoll | TEST
- Ausgangsstand Privat-TEST-Git `0d8bb9baf85365d4c07a1c17450ec0674bac3989`.
- Per Operator-Script `platform/ops/upgrade-reused-evidence-ffonly.sh` Fast-Forward auf `5eae5be7e8dc05298dba56ed37267cfc76622d20` bestätigt.
- SQLite-Backup per Integritätsprüfung bestätigt: `/root/hw24-backups/hw24-native-workflows-before-20261122T224403Z-InJYyN0r.sqlite`.
- Neue PHP-Klassen, Blade und Route-JSON laut PuTTY-Protokoll geprüft.
- **Keine LIVE-/Plesk-/MariaDB-/JTL-Aktion**, keine OpenAI-/Amazon-Anfrage. Private Scan-/KI-POST-Freigaben blieben aus.
- TEST-Browser-End-to-End und weitere Serveränderungen **nicht** anhand dieser Ausgabe belegt; aktuell installierter Stand kann seitdem abweichen.
- Amazon-Kataloghistorie `api/amazon-archive.php` benötigt weiterhin separat abgesicherte LIVE-GET-Bereitstellung; historisches Amazon-Händlerpreisarchiv **nicht implementiert**.

### 2026-11-22 | B002 | GitHub PR #46 | CODE/CI
- Draft-PR: https://github.com/NicoVoss1/HW24-scanner/pull/46
- Basis: Draft-PR #45; neuer isolierter Branch `feature/hw24-mirror-audit-preflight-20261122`.
- Vorhanden: read-only JTL-Schemainventur `platform/ops/jtl-schema-inventory-readonly.sql`, isolierte Audit-Migration `query_audit_events`, deaktivierter `QueryAuditRecorder`, Tests, Spiegelkonzept.
- GitHub CI für Commit `781b4e7b183a3414465d9a83bcfe627ed9d65377` belegt: 60 Tests, 313 Assertionen erfolgreich; GitHub Actions https://github.com/NicoVoss1/HW24-scanner/actions/runs/45080683786
- Auf TEST/LIVE wurde aus PR #46 **nichts** bereitgestellt. Auditing, SQL-Server-Spiegelung, Amazon-Preisarchiv nicht gestartet.

## Aktuelle Arbeitssitzung 2026-11-23

| ID | Umgebung | Aktion / Nachweis | Status |
|---|---|---|---|
| S001 | GitHub | PR #46 HEAD `781b4e7...` und Pfade geprüft; `docs/HW24_ARBEITSJOURNAL.md` existierte im Branch noch nicht | belegt |
| S002 | GitHub | `platform/ops/jtl-schema-inventory-readonly.sql` gelesen; hat korrekten DB-Namen `eazybusiness` als Sicherheitsbedingung. Tatsächlicher HeidiSQL-DB-Name ist vorab noch unbekannt | belegt |
| S003 | GitHub | Neues `platform/ops/jtl-heidisql-preflight-readonly.sql` als Fünf-Schritte-Startinventur angelegt; alle Statements lesen nur SQL-Server-Systemkataloge, kein Änderungsstatement | Code angelegt, noch nicht auf JTL ausgeführt |
| S004 | GitHub | Dieses öffentliche, redigierte zentrale Arbeitsjournal angelegt; laufende Betreiberergebnisse aus HeidiSQL und PuTTY stehen noch aus | Code angelegt |
| S005 | HeidiSQL | SQL A–E aus `platform/ops/jtl-heidisql-preflight-readonly.sql` jeweils separat ausführen; bei Berechtigungsfehler nur Meldung dokumentieren; keine Zugriffsrechte ändern | **offen: Betreiber** |
| S006 | PuTTY | Nur-lesend Git-HEAD/Branch, Dokumentroot und HTTP-Auth TEST abfragen; **keine** Upgrades, `curl|bash`, Migrationen oder Deployments | **offen: Betreiber** |
| S007 | GitHub | SQL/Server-Ergebnisse redigiert auswerten, Journal um tatsächliche Befunde erweitern, dann Delta-Sync-Design und Sicherheitsfreigaben konkretisieren | **offen** |

## HeidiSQL: freigegebene erste Befehle
SQL Server, **nicht** MySQL/MariaDB. Datenbank in HeidiSQL zuerst bewusst auswählen. Den kompletten Inhalt von `platform/ops/jtl-heidisql-preflight-readonly.sql` öffnen; **SQL A bis SQL E einzeln** ausführen und Ergebnisse als Text/Screenshots ohne sensible Daten weitergeben. Das vorhandene komplexere `jtl-schema-inventory-readonly.sql` erst ausführen, wenn `DB_NAME()` wirklich `eazybusiness` zurückgibt. Bei abweichendem Namen darf dieser Schutz **nicht** eigenmächtig entfernt werden.

Die fünf Ausgaben zeigen: SQL-Edition und Version sowie Tabellen-/Spaltenanzahl; Datenbankgröße; ungefähre Zeilenanzahlen der 40 größten Tabellen; Change Tracking/CDC/Snapshotstatus; Tabellen je Schema. Es werden **keine Geschäftsdatensätze** exportiert. Bei Systemkatalogberechtigungsfehlern stoppen und nur den Fehlercode melden.

## PuTTY: freigegebene erste Befehle (READ ONLY)

```bash
date -u '+UTC %Y-%m-%dT%H:%M:%SZ'
id -un
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
if [ -d "$repo/.git" ]; then
  runuser -u nicovoss -- git -C "$repo" rev-parse HEAD
  runuser -u nicovoss -- git -C "$repo" branch --show-current
  runuser -u nicovoss -- git -C "$repo" status --short
else
  printf 'STOP: privates Git-Repository nicht am erwarteten Pfad\n'
fi
plesk db -Ne "SELECT d.name,h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'"
curl --silent --show-error --max-time 10 --proto '=https' --output /dev/null --write-out 'TEST Admin HTTP %{http_code}\n' 'https://test-artikelmanager.hairworld24.de/admin/artikel'
```

Keine `.env`-Dateien öffnen oder ausgeben. HTTP 401/403 ohne Anmeldedaten ist für BasicAuth erwartbar. 200/302/Fehler gesondert prüfen, nicht blind deployen. Bitte Resultate der Befehle ohne private Zugangsdaten zurückgeben; sie werden vor jeder weiteren Änderung ausgewertet.

## Betreiberergebnisse 2026-11-22 / ausgewertet am 2026-11-23

### S008 | HeidiSQL SQL A: Server-/Datenbankinventur | **belegt**
Vom Betreiber zurückgemeldeter Wert: `DB_NAME()=eazybusiness`, SQL Server `14.0.2150.2`, **Express Edition (64-bit)**. Sichtbar: **1.176 Tabellen, 969 Views, 10.907 Spalten**. Datenbankidentität damit für die Phase-1-Abfragen bestätigt. Vollständiger Schema-/Column-Export noch **nicht** durchgeführt. Versionsfamilie SQL Server 2017; Microsoft nennt für SQL Server 2017 Express maximal **10 GB relationale Datendateien pro Datenbank** (Transaktionslog-Dateigröße nicht mit diesem Limit gleichsetzen). Quelle: https://learn.microsoft.com/en-us/sql/sql-server/editions-and-components-of-sql-server-2017?view=sql-server-ver17

### S009 | HeidiSQL SQL B: Dateigröße | **Ergebnis fehlt**
Der Betreiber hat die SQL-Anweisung, aber **keine Ergebniszeilen** für `sys.database_files` übermittelt. Keine Aussage über aktuellen Füllstand, verfügbaren Platz, ob nahe 10 GB oder über die Gesamtgröße möglich. **Nicht** als SQL-Fehler interpretieren. Wird in Phase 2 erneut abgefragt (separat Datendateien und Logs).

### S010 | HeidiSQL SQL C: 40 größte Tabellen | **belegt**
Ungefähre Zeilenzahlen laut `sys.partitions` (kein exakter COUNT):
- `Sync.tScxLogeintrag` **1.123.710**;
- `Amazon.tVCSSelfInvoiceLogeintrag` **462.577**;
- `SCX.tCategoryAttributeValue` **239.840**;
- `SCX.tCategory` **169.800**;
- `dbo.ebay_xx_categories` **120.604**;
- `Amazon.tAmazonabgleichLogsitzung` **82.095**;
- `dbo.tKunde_suche` **74.961**;
- `SCX.tOfferPrice` **30.522**;
- `dbo.tArtikelShop` **17.856**;
- `dbo.tArtikelHistory` **9.134**;
- `dbo.tWarenLagerPlatzArtikel` **6.295**.
Die vollständigen 40 Tabellen sind im Betreiber-Chat erfasst, **nicht** als private Rohdaten in diesem öffentlichen Journal gespiegelt. Priorität: Artikel-, Preis-, Bestands- und Marktplatzschemata zuerst; große Log-/Kategorie-/Belegtabellen nicht blind im 15-Minuten-Takt voll exportieren. Einige Tabellen sind geschäftlich/privatsphärisch sensibel und benötigen vor Spiegelung Klassifizierung.

### S011 | HeidiSQL SQL D: CDC/Isolation | **teilweise belegt**
`eazybusiness` Compatibility-Level **140**, Recovery Model **SIMPLE**,
`is_cdc_enabled=False`,
`is_read_committed_snapshot_on=False`,
`snapshot_isolation_state_desc=OFF`.
**Das separate Ergebnis `change_tracking_aktiv` wurde nicht übermittelt.**
Daher Change Tracking **nicht als an/aus behaupten**. Vor Replikation Versions- und JTL-Kompatibilität sowie Konsistenzstrategie prüfen; **keine** CDC-/CT-/Snapshot-Einstellungen ohne JTL-Freigabe ändern.

### S012 | HeidiSQL SQL E: Schemas | **belegt**
Beispiele: `dbo` 653, `Amazon` 66, `SCX` 61, `Ticketsystem` 49,
`FulfillmentNetwork` 44, `pps` 43, `Verkauf` 41, `WMS` 40,
`Rechnung` 22, `Shipping` 22, `Artikel` 11, `Ebay` 9, `BI` 8.
Die Aufteilung unterstreicht Beziehungen über mehrere Schemas; eine monolithische UI pro Datenbanktabelle ist nicht zielführend.

### S013 | PuTTY TEST-Server-Sicherheitsdiagnose | **belegt**
Befehl ausgeführt UTC `2026-11-22T23:11:35Z`, Shell-User `root`.
- Privater installierter TEST Git-HEAD `5eae5be7e8dc05298dba56ed37267cfc76622d20` bestätigt.
- Lokaler Branch-Name `feature/hw24-platform-laravel13-filament5-20261122`. Branch-Label ist nicht gleichbedeutend mit HEAD-Zielstand.
- Git-Status nur **untracked**: `platform/composer.lock`, `platform/public/css/`, `platform/public/fonts/`, `platform/public/js/`. Nicht löschen/resetten oder aus `git status` auf Funktionsfehler schließen; Build-Artefakte können für App erforderlich sein.
- Plesk-DocumentRoot TEST: `.../hw24-console-private/repo/platform/public` erwartungsgemäß.
- Anonymer `GET https://test-artikelmanager.hairworld24.de/admin/artikel` HTTP **401**. Entspricht einer aktiven Zugangshürde, beweist allein jedoch nicht vollständige Benutzer-/API-Autorisierung.
- **Kein** Browser-Login-/End-to-End-Test, keine LIVE-Gleichheit und keine neue DB-Replikation hiermit bestätigt.

### S015 | SQL Server Express 2017 Grenzen | **amtliche Herstellerdokumentation geprüft**
- Microsoft SQL Server 2017 Express: maximale relationale Datenbankgröße 10 GB pro Datenbank; **Data/ROWS** von **LOG** bei Speicherprüfung trennen.
- Kein integrierter SQL Server Agent in Express; für späteren 15-Minuten-Scheduler **externen Windows-Taskplaner** oder einen abgesicherten Job-Runner auf dem bereits vorhandenen System evaluieren, nicht SQL-Agent-Jobs voraussetzen.
- SQL Server Change Tracking ist laut SQL-Editionstabelle verfügbar, tatsächliche Aktivierung ist **weiterhin unbekannt**; keine Aktivierung an JTL ohne Prüfung/Vendor-Freigabe.
- Herstellerbeleg: https://learn.microsoft.com/en-us/sql/sql-server/editions-and-components-of-sql-server-2017?preserve-view=true&view=sql-server-2017

### S014 | Sicherheitsbewertung und Folgearbeit | **Code vorbereitet**
- Kein weiterer Fast-Forward nötig. Bestehende `5eae5be...`-Installation unangetastet lassen.
- Neuer ausschließlich lesender Phase-2-Abfragesatz `platform/ops/jtl-heidisql-phase2-readonly.sql` im isolierten GitHub-PR #46 angelegt. Er fragt DB-Dateigrößen inkl. `FILEPROPERTY`, CT-Status samt Anzahl getrackter Tabellen, Primary-Key-Metadaten relevanter Tabellen sowie Artikel-/Bestands-/Offer-Spaltenstruktur ab.
- **Ausführung auf HeidiSQL und PuTTY noch offen; Ergebnis nicht voraussetzen.** Besonders SQL-B-Größe und Change-Tracking-Status sind als Nächstes zwingend. Keine JTL-Tabellenfreigaben, SQL-Rechte, CDC, CT oder Snapshots aktivieren.
- PR #46 ist weiterhin **Draft** und noch nicht auf TEST/LIVE installiert. Offen: Entwurfsprüfung, bestätigter Browser-E2E-Test, Abnahme der 15-Minuten-Deltastrategie, Schutz sensibler Quellen.

## Betreiberergebnisse Phase 2: HeidiSQL (2026-11-23)

### S016 | SQL Server Speicherauslastung | **Belegt: reine SELECT-Ausgaben**
- Bestätigte Datenbank weiterhin `eazybusiness` auf SQL Server 2017 Express.
- SQL-Datendatei **ROWS 1 Datei, 2.500 MB zugewiesen, 1.154 MB belegte Seiten** (ca. 46,2 % der zugewiesenen Daten-Datei).
- SQL-Transaktionslog **LOG 1 Datei, 800 MB zugewiesen**. Zwei Ausführungen zeigen ca. **3,4 MB** und später **5,4 MB belegte Seiten**; keine feste unveränderliche Loggröße daraus ableiten.
- Die Express-Grenze von 10 GB bezieht sich auf relationale Datenbankgröße, **nicht auf die bloße Summe einschließlich LOG**. Der aktuelle belegte Datenumfang ist ca. 1,13 GiB, der zugewiesene ROWS-Speicher ca. 2,44 GiB. Keine unmittelbare Kapazitätsnot allein aus diesen Zahlen, aber Backup/Wachstum/Platz auf dem Datenträger noch unbekannt.
- **Kein** Vollbackup-/Restoretest, keine Disk-Auslastung, kein tatsächliches Kopierprotokoll belegt.

### S017 | Änderungsprotokollierung | **Belegt**
- `sys.change_tracking_databases` bestätigt `change_tracking_aktiv=0`.
- `sys.change_tracking_tables` bestätigt `anzahl_ct_tabellen=0`.
- Zuvor bereits `is_cdc_enabled=False` und Snapshot Isolation `OFF` bestätigt.
- **Weder CDC noch Change Tracking aktivieren**, bevor JTL-Verträglichkeit/Recovery und DBA-Abnahme vorliegen.
- **Alternativer Delta-Kandidat:** `dbo.tArtikel.bRowversion`, `dbo.tlagerbestandProLagerLagerartikel.bRowversion`, `dbo.tWarenLagerPlatzArtikel.bRowversion` sind als SQL-Typ `timestamp` (SQL Server rowversion, **keine Uhrzeit**) in der Betreiber-Metadatenliste nachgewiesen. Diese Spalten können Insert-/Update-Änderungen abbilden, **nicht** Löschungen. Eine korrekte Replikation muss noch konsistenten Wasserzeichenmechanismus, laufende Transaktionen und Delete-Reconciliation entwickeln; Microsoft rät für Transaktionssicherheit zu `MIN_ACTIVE_ROWVERSION()` statt nur `@@DBTS`: https://learn.microsoft.com/en-us/sql/t-sql/functions/min-active-rowversion-transact-sql?view=sql-server-ver15 .
- Es ist **nicht** belegt, dass `SCX.tOffer`, `SCX.tOfferPrice` oder `dbo.pf_amazon_angebot` ein `rowversion` besitzen. Hier zunächst Index-, Änderungszeitstempel- und Schlüsselinventur erforderlich.

### S018 | Priorisierte JTL-Tabellen, Primärschlüssel | **Belegt**
In SQL Phase-2 Query 3 jeweils `primary_key_vorhanden=1`:
- `dbo.pf_amazon_angebot`: 21 Spalten; `dbo.pf_amazon_angebot_ext`: 54; `dbo.tArtikel`: 91; `dbo.tArtikelHistory`: 27; `dbo.tArtikelShop`: 7; `dbo.tlagerbestandProLagerLagerartikel`: 6; `dbo.tWarenLagerPlatzArtikel`: 5.
- `SCX.tOffer`: 23; `SCX.tOfferPrice`: 5; `SCX.tVerkaufskanalArtikelData`: 6.
- `Verkauf.tAuftragPosition`: 31; `Verkauf.tAuftragPositionEckdaten`: 18.
- Gefundene WMS-Tabellen: `WMS.tAutomatikLagerConfig` 8, `WMS.tPermanentInventurWarenLagerPlatzType` 2, `WMS.tPicklisteVorlageStartZielLager` 5, `WMS.tWarenLagerEingangHistorieSerialnumber` 6.
- **Achtung:** Das Vorhandensein irgendeines PK beweist **noch nicht** dessen Schlüsselspalten. Darum Phase-3-PK-/Index-/FK-Abfrage. Diese Namensfilter sind keine vollständige 1.176-Tabellen-Inventur.

### S019 | Datenfeld-Quellenbeleg Artikel | **Belegt: Metadaten, nicht Werte**
`dbo.tArtikel`, 91 Felder. Wichtigste originale Quellen:
- Identität: `kArtikel` int identity, `cArtNr`, `cBarcode`, `cHAN`, `cASIN`, `kHersteller`.
- JTL-Preise: `fVKNetto`, `fEKNetto`, `fLetzterEK`, `fUVP`, `fAmazonVK`, `fEbayPreis`. **fAmazonVK/fEbayPreis nicht automatisch als live bestätigte veröffentlichte Marktplatzpreise ausgeben**.
- Steuer-/Status-Referenzen: `kSteuerklasse`, `kLieferStatus`, `cAktiv`, `nDelete`, `nNichtBestellbar`, `nSonderTyp`. Klartexte, gültige Steuersätze, Preisarten und Artikeltyp-Zuordnung weiter über verifizierte Referenztabellen ermitteln, nicht raten.
- Bestand/Logistik: `nLagerbestand`, `nZulaufVerfuegbarMenge`, `dZulaufVerfuegbarAm`, `nPuffer`, `nPufferTyp`, `kMassEinheit`, `fMassMenge`, `kGrundPreisEinheit`, `fGrundpreisMenge`, `kWarenlager` **nicht** als Artikelspalte belegt, sondern in anderem Lagerkontext. Grundmenge 90 vs. 90000 nur mit bestätigter Maßeinheit/Skalierung normalisieren.
- Änderungsinformationen: `dMod` datetime2, `dErstelldatum` datetime2, `bRowversion` timestamp, `kBenutzerLetzteAenderung`. `nMHD` ist als tinyint-Feldtyp belegt, **kein** tatsächliches Ablaufdatum.

### S020 | Amazon, Verkaufskanal und Lagerquellen | **Belegt: Metadaten, keine Angebotsstände**
- `dbo.pf_amazon_angebot` enthält `cSellerSKU`, `kUser`, `nPlattform`, `fPrice`, `nQuantity`, `cListingId`, `cASIN1..3`, `dOpenDate`, `cFulfillmentChannel`, `nChangedPrice`. **Wahrscheinliche eigene JTL-Amazon-Angebotsquelle**, Konto-/Marketplace-/Aktivstatus-Zuordnung aber erst durch geprüfte Schlüssel und Datenbeispiele bestätigen. **Keine nachgewiesene Konkurrenz-Preishistorie**.
- `SCX.tOffer` enthält `kOffer`, `kArtikel`, `kShop`, `nIsListed`, `nStatus`, `cChannelOfferId`, `dPriceUpdatedUtc`, `dStatusReceivedUtc`, `fZuletztHochgeladenerPreis`, `dLetzterUpload`, `dBeendet`. Quellbezug und Preisart müssen verifiziert werden. Letzter Upload-Preis ist **keine** vollständige Händlerpreishistorie.
- `SCX.tOfferPrice` besitzt `kOfferPrice`, `kOffer`, `kChannelPriceType`, `fLastPrice`, `fQuantity`; **kein Zeitstempel in den fünf gelieferten Spalten**, daher hieraus allein keine Zeitreihe ableiten.
- `dbo.tlagerbestandProLagerLagerartikel`: `kArtikel`, `kWarenlager`, `fBestand`, `fVerfuegbarGesperrt`, `fAuslieferungGesperrt`, `bRowversion`. **Nicht** unbestätigt mit `fVerfuegbar` oder Auftragsbestand gleichsetzen.
- `dbo.tWarenLagerPlatzArtikel`: `kWarenLagerPlatz`, `kArtikel`, zwei Kommentarspalten, `bRowversion`; **keine Menge in dieser Tabelle belegt**. WMS-Lagerplatzmenge über bestätigte andere Quellen beziehen.
- Keine dieser Metadaten-Erhebungen hat Artikel, Angebote, Lager oder JTL verändert.

### S021 | Nächste SQL-Abfragen und Release-Zustand | **Code vorbereitet, Ausführung offen**
- Neuer Quellcode `platform/ops/jtl-heidisql-phase3-keys-delta-readonly.sql` im isolierten Draft-Branch erstellt.
- Queries (a) DB-Name bestätigen; (b) tatsächliche PK-/Unique-Schlüsselspalten inklusive Reihenfolge; (c) FK-Beziehungen; (d) alle rowversion- und Änderungszeitspalten der priorisierten Quelltabellen; (e) bestehende Indizes zur Delta-Eignung. **Nur** SELECT aus sys-Katalogen.
- Nächster Betreiber-Schritt: Queries separat in HeidiSQL ausführen, Ergebnisse ohne echte Geschäftsdatensätze teilen. PuTTY ist für diesen Schritt **nicht notwendig**, bereits bestätigter TEST-HEAD `5eae5be...` bleibt unangetastet.
- Anschließend SQL-Mapping/JTL-View, Account-Zuordnung, Delta-Semantik und Löschbehandlung definieren, dann privaten sicheren Test-Sync entwickeln. Keine Live-Preisarchive aktivieren.
- **Kein** Merge, kein Plesk-/LIVE-/JTL-Deploy dieser Sitzung; neue Informationen und Code nur auf GitHub im Draft-PR.

## Betreiberergebnisse Phase 3: Primärschlüssel, Fremdschlüssel, rowversion, Indizes (2026-11-23)

### S022 | HeidiSQL Phase 3 – Datenbankidentität | **belegt**
- `DB_NAME()=eazybusiness`. Metadata-SELECTs auf der beabsichtigten SQL-Server-Datenbank ausgeführt. Keine DDL/DML/DB-Konfiguration ausgelöst.

### S023 | SQL-Schlüssel und eindeutige Indizes | **belegt**
- `dbo.tArtikel`: PK `kArtikel`; Unique `cArtNr`.
- `dbo.tArtikelHistory`: PK `kArtikelHistory`.
- `dbo.tArtikelShop`: zusammengesetzter PK (`kArtikel`, `kShop`).
- `dbo.tlagerbestandProLagerLagerartikel`: zusammengesetzter PK (`kArtikel`, `kWarenlager`).
- `dbo.tWarenLagerPlatzArtikel`: zusammengesetzter PK (`kWarenLagerPlatz`, `kArtikel`).
- `dbo.pf_amazon_angebot` und `dbo.pf_amazon_angebot_ext`: jeweils zusammengesetzter PK (`cSellerSKU`, `kUser`, `nPlattform`). **Eindeutigkeit ist konto- UND plattformbezogen**, nicht nur SellerSKU.
- `SCX.tOffer`: PK `kOffer`; Unique (`kArtikel`, `kShop`).
- `SCX.tOfferPrice`: PK `kOfferPrice`.
- `SCX.tVerkaufskanalArtikelData`: PK `kVerkaufskanalArtikelData`, Unique (`kArtikel`, `kShopScx`).
- `Verkauf.tAuftragPosition` und `Verkauf.tAuftragPositionEckdaten`: PK `kAuftragPosition`.
- Alle zurückgemeldeten PK-/Unique-Indizes sind `is_disabled=False`. **Kein Datenabgleich oder Schlüsselkollisions-Test erforderlich**, solange die Quelle ihre eigenen Constraints erhält; Spiegel muss gleiche Schlüssel-Zusammensetzungen übernehmen.

### S024 | Bestätigte SQL-Fremdschlüssel und Grenzen | **belegt**
- `SCX.tOfferPrice.kOffer` → `SCX.tOffer.kOffer`.
- `SCX.tOfferPrice.kChannelPriceType` → `SCX.tChannelPriceType.kChannelPriceType`.
- `SCX.tVerkaufskanalArtikelData.kArtikel` → `dbo.tArtikel.kArtikel`.
- `SCX.tOffer` hat zwar `kArtikel` und `kShop` im Unique-Index, aber in **dieser FK-Ergebnismenge keinen belegten FK direkt nach dbo.tArtikel**; Join per Schlüssel muss fachlich/technisch validiert werden.
- `dbo.tPreis.kArtikel` → `dbo.tArtikel.kArtikel`; eigenständige Preisarten/Kundengruppen anhand der tatsächlichen Spalten/Referenztabellen ermitteln.
- `Artikel.tArtikelType.kArtikel`, `Artikel.tArtikelAsin.kArtikel`, `Artikel.tArtikelkosten.kArtikel` → `dbo.tArtikel.kArtikel`. Aus FK allein folgt **keine Klartext-Zuordnung** für Artikeltyp.
- Viele weitere Beziehungen zu `dbo.tArtikel`: `tliefartikel`, `tArtikelAttribut`, `tArtikelbildPlattform`, `tExterneReservierung`, `SCX.tArtikelVerkaufskanalFeldWert`, WMS-Tabellen `tPackageContentItem`, `tSerialNumber`.
- Für `dbo.pf_amazon_angebot` ist in der gemeldeten FK-Menge **keine direkte FK-Beziehung nach tArtikel nachgewiesen**. Artikelzuordnung über SellerSKU/ASIN daher **nicht erfinden**; gesondert bestätigen.
- Der FK-Bericht umfasst nur ausgewählte Filter; Fehlen einer Zeile beweist außerhalb dieses Suchbereichs keine generelle Beziehungslosigkeit.

### S025 | Änderungsspalten / Delta-Möglichkeiten | **belegt und Grenzen**
- `bRowversion` (SQL `timestamp`, 8-Byte-Zähler, keine Zeitangabe): `dbo.tArtikel`, `dbo.tArtikelHistory`, `dbo.tArtikelShop`, `dbo.tlagerbestandProLagerLagerartikel`, `dbo.tWarenLagerPlatzArtikel`.
- Keine `bRowversion` in ausgewiesenen `dbo.pf_amazon_angebot`, `dbo.pf_amazon_angebot_ext`, `SCX.tOffer`, `SCX.tOfferPrice`, `SCX.tVerkaufskanalArtikelData` durch die SQL-Phase-3-Metadaten bestätigt. Bei Tabellen ohne rowversion müssen separate Delta-Strategien oder abgestimmte komplette Reconciliation möglich sein.
- `SCX.tOffer`: u. a. `dQuantityUpdatedAt`, `dPriceUpdatedUtc`, `dStatusReceivedUtc`, `dLetzterUpload`, `dBeendet`. Deren Bedeutung für **jede** Änderung vor Delta-Verwendung durch Verhaltenstest prüfen.
- `SCX.tOfferPrice`: **keine** datetime/rowversion-Spalte. Eine Filterung nach `SCX.tOffer.dPriceUpdatedUtc` muss erst beweisen, dass Änderungen von `tOfferPrice` den Parent aktualisieren; sonst Preisänderungen übersehen.
- `dbo.pf_amazon_angebot_ext`: zahlreiche Ereigniszeitpunkte, einschließlich `dLetztePreisAenderung`, `dLetzteBestandAenderung`, `dDeaktiviertUtc`; **kein Nachweis**, dass Änderungen an `pf_amazon_angebot` zuverlässig solche Felder aktualisieren.
- `dbo.tArtikelHistory.dGebucht`, `dMHD` sind Datumsspalten; tatsächliche Ereignisart, Bedeutung und Abdeckung sind noch offen.
- `rowversion` erkennt **keine Löschungen** und erfordert transaktionssicheren Wasserzeichenschnitt. Siehe Microsoft: https://learn.microsoft.com/en-us/sql/t-sql/functions/min-active-rowversion-transact-sql?view=sql-server-ver15
- Quelle `eazybusiness` läuft mit `READ_COMMITTED_SNAPSHOT=OFF` und `SNAPSHOT_ISOLATION=OFF`. Keine Aktivierung zur Replikation ohne JTL-/DBA-Prüfung.

### S026 | Indizes und Performance | **belegt, weiterer Test nötig**
- `dbo.tArtikel`: vorhandene Indizes auf `cBarcode`, `cHAN`, `cASIN`, `cAktiv`-Kombination, `kArtikel`, `kHersteller`, `fEKNetto`, außerdem PK und cArtNr-Unique.
- `dbo.tlagerbestandProLagerLagerartikel`: Indizes auf `kWarenlager`, (`kArtikel`,`kWarenlager`) sowie PK.
- `dbo.tWarenLagerPlatzArtikel`: Index `kArtikel` und zusammengesetzter PK.
- `SCX.tOffer`: `cChannelOfferId`-Index, PK `kOffer` und Unique (`kArtikel`,`kShop`).
- `SCX.tOfferPrice`: in erfasster Ausgabe nur PK `kOfferPrice`.
- In den gelieferten Indexzeilen **kein rowversion-Schlüsselindex nachgewiesen**; daher kein effizienter Seek auf rowversion voraussetzen. Query in Phase 4 prüft das ausdrücklich. Keinesfalls ungeprüft zusätzliche Indizes in JTL anlegen.
- Getrennte technische Empfehlung: kleine Tabellen mit vollem PK-Abgleich ermöglichen, größere Quellen möglichst delta-orientiert, Löschungen via periodischen Hash-/PK-Abgleich.

### S027 | Geplanter nächster Abfragesatz | **GitHub erstellt, HeidiSQL-Ausführung offen**
- `platform/ops/jtl-heidisql-phase4-mapping-readonly.sql` vorbereitet: Quelle für `Artikel.tArtikelType`, `Artikel.tArtikelAsin`, `dbo.tPreis`, `SCX.tChannelPriceType`, Shop-/Steuer-/Lieferstatus-/Mengeneinheiten-Mapping inventarisieren. Zusätzliche Abfrage belegt, ob rowversion ein Index-Key ist, und findet weitere Kandidatentabellen.
- Ausschließlich Systemkatalog-SELECTs; keine Artikel-, Preis-, Bestell- oder Kundendaten abfragen, keine SQL-Rechte ändern.
- **Bevor 15-Minuten-Sync implementiert wird:** Source-Contract pro Tabelle, konsistenter Initial-Snapshot, Watermark/Long-running-Transactions, Resync nach Fehlern, Tombstones, Schemaänderungen und Rechte/PII-Beschränkung festlegen.
- **Vor LIVE:** echte Browser-Tests und alle Draft-PR-Abhängigkeiten prüfen; keine stillen JTL- oder Amazon-API-Aktionen.

### S028 | GitHub: JTL-Quellvertrag v1 als maschinenlesbare Konfiguration | **Code erstellt, TEST/LIVE nicht verändert**
- Neue `platform/config/hw24_jtl_source_contract.php` mit ausschließlich aus Betreiber-Metadaten bestätigten PK-/Unique-Schlüsselspalten und Auswahl nachgewiesener FK-Beziehungen.
- Tabellen mit `bRowversion` als reine `rowversion_candidate` deklariert. Amazon-/SCX-Tabellen ohne belegte rowversion als `full_reconcile_candidate`, **noch nicht implementiert**. Bestellungstabellen `restricted_order/not_approved`.
- `enabled=false`, keine SQL-Server-Verbindung, kein Datenexport, keine Scheduler-Freischaltung, kein Marketplace-POST, keine Änderungen an JTL.
- Zusätzlicher Test `platform/tests/Unit/JtlSourceContractTest.php`: Sperren, zusammengesetzte PKs und plausible Delta-Kandidaten. CI-Resultat für neuen HEAD separat prüfen; Tests ohne CI-Resultat **nicht** als erfolgreich behaupten.
- **Nächster Operator-Schritt:** Read-only-Phase-4-Abfragen `platform/ops/jtl-heidisql-phase4-mapping-readonly.sql` (SQL 0–5) für JTL-Preisarten, Klartext-Status, Artikeltypen und konto-/shopbezogene Referenzen. Keine Plesk-/PuTTY-Aktionen.

## Betreiberergebnisse Phase 4: Referenzquellen und Preis-/Shop-Mapping (2026-11-23)

### S029 | HeidiSQL Datenbankidentität und Referenztabellen | **bestätigt**
- Datenbankkontext: `eazybusiness`. Nur lesende SQL-Abfragen; keine JTL-Änderungen belegt.
- Ungefähre Zeilen aus `sys.partitions`: `dbo.tArtikel` 2.692; `Artikel.tArtikelAsin` 2.461; `Artikel.tArtikelkosten` 2.692; `Artikel.tArtikelType` **0**; `dbo.tPreis` **49**; `dbo.tLieferStatus` **1**; `dbo.tSteuerklasse` **2**; `dbo.tSteuersatz` **4**; `dbo.tMassEinheit` **31**; `dbo.tShop` **7**; `dbo.tWarenLager` **10**; `SCX.tChannelPriceType` **12**; `SCX.tOffer` **16.049**; `SCX.tOfferPrice` **30.522**; `SCX.tVerkaufskanalArtikelData` **15.809**.
- **Quellbeleg vs. Deutung:** `Artikel.tArtikelType` hat bei Bestandsaufnahme **0 Zeilen**. Aus dieser Tabelle kann **kein** Artikeltyp wie Standard/Variante abgeleitet werden, auch wenn sie die Spalten `kArtikel` und `uArtikelType` (uniqueidentifier) besitzt. Alternative Definition erst nach separater Prüfung der JTL-Artikellogik und ggf. Daten.
- `Artikel.tArtikelAsin` enthält `kArtikelAsin`, `kArtikel`, `cAsin`, `bRowversion`. Es können zusätzliche ASIN-Quellen existieren; Anzahl Datensätze ≠ Anzahl eindeutig zugeordneter Produkte. `Artikel.tArtikelkosten` besitzt `fArtikelkosten`, `bRowversion`.

### S030 | Preis, Steuern, Einheiten und Lieferstatus | **Schema bestätigt, Werte offen**
- `dbo.tPreis` enthält exakt `kPreis`, `kArtikel`, `kKundenGruppe`, `kShop`, `kKunde`, `bRowversion`. **Kein Preisbetrag in diesen sechs gelieferten Spalten**; `dbo.tPreisDetail` als tatsächlich vorhandener Kandidat für Preise identifiziert, **dessen Spalten noch unbekannt**. Preisberechnung nicht erfinden.
- `SCX.tChannelPriceType`: `kChannelPriceType`, `kChannel`, `kShop`, `cPriceTypeId`, `cDisplayName`, `cDescription`, `kKundenGruppe`. Genau 12 ungefähr erfasste Zeilen, aber **Klartext/Werte und Zuordnung zu Shops noch nicht geliefert**. Live-Preis nicht aus einem unbestätigten Label ableiten.
- `dbo.tSteuersatz`: `kSteuersatz`, `kSteuerzone`, `kSteuerklasse`, `fSteuersatz`, `nPrio`. Brutto hängt von korrekter Steuerzone/-klasse und ggf. Kundengruppe/Shop ab; keine pauschale 19%-Annahme ohne Beleg.
- `dbo.tSteuerklasse`: `kSteuerklasse`, `cName`, `nStandard`, `nTyp`; `dbo.tLieferStatus`: `kLieferStatus`, `kSprache`, `cName`, `bRowversion`. Die bisherige `kLieferStatus=1` kann erst mit der jeweiligen tatsächlichen `kSprache` als Klartext angezeigt werden.
- `dbo.tMassEinheit`: `kMassEinheit`, `cCode`, `cDisplayCode`, `kBezugsMassEinheit`, `fBezugsMassEinheitFaktor`, `cUnitCode` + `bRowversion`. Füllmenge wie 90000 → 90 erst nach überprüfter Einheit/Bezugsfaktor-Metadaten und Artikel-Testfall normalisieren.
- Quelle Währung/Preisart noch nicht endgültig bestimmt. `dbo.tShopWaehrung` und `dbo.tPreisDetail` müssen erst auf schema-/referenzbezogene Felder untersucht werden.

### S031 | Bestätigte Foreign Keys / Account-Verbindungen | **Metadaten belegt**
- `SCX.tChannelPriceType.kChannel` → `SCX.tChannel.kChannel`; `SCX.tChannelPriceType.kShop` → `dbo.tShop.kShop`; `SCX.tChannelPriceType.kKundenGruppe` → `dbo.tKundenGruppe.kKundenGruppe`.
- `SCX.tOfferPrice.kChannelPriceType` → `SCX.tChannelPriceType.kChannelPriceType` und `SCX.tOfferPrice.kOffer` → `SCX.tOffer.kOffer`.
- `SCX.tVerkaufskanalArtikelData.kShopScx` → `dbo.tShopScx.kShopScx`.
- `dbo.tShopScx.kShop` → `dbo.tShop.kShop`; weitere Verknüpfungen von `dbo.tShop` zu Shopkunden-/Sync-/Konfigurationstabellen. **Noch keine bestätigte Zusammenführung aller externen Konten/Marketplace-IDs**.
- `Artikel.tArtikelType.kArtikel`, `Artikel.tArtikelAsin.kArtikel`, `Artikel.tArtikelkosten.kArtikel` → `dbo.tArtikel.kArtikel`.
- `dbo.tPreis.kArtikel` → `dbo.tArtikel.kArtikel`.
- `dbo.tSteuerklasse` ist über Auftrags-/Rechnungspositionen referenziert; `dbo.tMassEinheit` auch in Artikel-Dimension-/Teilmengenpositionen. Relevante Tabellen mit Verbraucher- oder Adressdaten bleiben für Spiegelung **nicht freigegeben**.

### S032 | Replikationsperformance / Change Tracking | **bestätigt**
- Auf **allen fünf untersuchten** `bRowversion`-Spalten kein Index, der `bRowversion` als Schlüssel benutzt (`rowversion_index_key_vorhanden=0`): `dbo.tArtikel`, `dbo.tArtikelHistory`, `dbo.tArtikelShop`, `dbo.tlagerbestandProLagerLagerartikel`, `dbo.tWarenLagerPlatzArtikel`.
- Ein SQL-Prädikat `WHERE bRowversion > @marker` nutzt daher **keinen gezielten Rowversion-Index-Seek** aus diesen vorhandenen Indizes. Tabellen- oder Index-Scan möglich; Last vor 15-Minuten-Scheduler unter realen Bedingungen messen. Keine JTL-Indexanlage ohne Hersteller-/DBA-Freigabe.
- Change Tracking weiterhin **0/0** und CDC aus. Es existieren ältere Artikel/Preis-/WMS-Felder, die Delta-Strategie kann **nicht** für sämtliche 1.176 Tabellen einheitlich festgelegt werden.
- Ansatz: zunächst **kleiner sicherer Tabellenkatalog**, gemessene vollständige Reconciliation für moderate Volumen und später überwachte Delta-Logik, deletions/transaktionssichere Wasserzeichen. Kein aktuell aktiver Scheduler.

### S033 | Schutz sensibler Quellfelder | **verbindlich**
- `dbo.tShop` hat unter anderem `cAPIKey`, `cPasswortWeb`, `cBenutzerWeb`, `cServerWeb` sowie weitere betriebliche Felder. **Tabellenweite 1:1-Übernahme in eine allgemein zugängliche Analyse-DB ist nicht freigegeben.**
- Nur beabsichtigte Lesespalten `kShop`, `cName`, `nTyp`, `nAktiv`, `kFirma` nach Zweck-/Rechtekontrolle. Keine Passwörter, Tokens, URLs mit Secrets oder Webshop-Zugangsdaten in Tests, Logs, GitHub oder Dialogausgaben.
- Auch `dbo.tWarenLager` enthält Anschriften und persönliche Ansprechpartner. Analytische Lageridentität (`kWarenLager`, `cName`, `nAktiv`) strikt von Personen-/Kontaktfeldern trennen.
- Der Wunsch nach vollständiger JTL-Datenbasis wird in **Vollinventur aller Tabellen + zweckgebundene, klassifizierte private Quellspiegel** umgesetzt, nicht als pauschal öffentliche Vollkopie.

### S034 | GitHub: Phase-5-Abfragen | **erstellt, noch nicht auf HeidiSQL ausgeführt**
- `platform/ops/jtl-heidisql-phase5-reference-values-readonly.sql`: SQL 0–9; nur `SELECT`, kontrollierte Klartext-/Steuer-/Einheiten-/ChannelPriceType-Lookups, ausdrücklich eingeschränkte Shop-ID/-Namen-Auswahl, ergänzende Schema-Metadaten, aggregierte Marktplatzangebotszahlen ohne Seller-IDs.
- SQL 0 prüft `DB_NAME()=eazybusiness`; SQL 1–6 enthalten verifizierte, benannte Quellspalten. SQL 7–8 lesen nur System-Metadaten über `dbo.tPreisDetail`, `dbo.tKundenGruppe`, `SCX.tChannel`, `dbo.tShopScx`, `dbo.tShopWaehrung` und Währungsreferenzen. SQL 9 liefert Angebotssummen je Shop.
- **Betreiber muss die Abfragen einzeln ausführen** und ggf. Shopnamen privat/redigiert zurückgeben. Bei fehlender Berechtigung/Fehlermeldung nicht an JTL-Rechten drehen.
- `dbo.tShop`: niemals `SELECT *` verwenden oder `cAPIKey` etc. mit exportieren.

### S035 | GitHub: referenzbasierter Datenvertrag und Tests | **erstellt, CI offen**
- Neu `platform/config/hw24_jtl_reference_dictionary.php`: `enabled=false`, erlaubte Felder pro Referenztabelle, Kennzeichnung `dbo.tShop` als geheimhaltungsrelevante Quelle, explizite Unsicherheiten `Artikel.tArtikelType`, `dbo.tPreisDetail`, `SCX.tChannel`.
- Neu `platform/tests/Unit/JtlReferenceDictionaryTest.php`: Kontrolltests für deaktivierte Freigabe, exakte Shop-Spalten-Whitelist und nicht erfundene Artikeltypen/Brutto-Preisfelder. **Kein** automatischer Import oder neue API.
- Aktueller GitHub Draft-PR #46: https://github.com/NicoVoss1/HW24-scanner/pull/46. Neues CI-Ergebnis vor Rollout abwarten und im Journal vermerken. Keine Plesk-/TEST-/LIVE-Datenbankänderung.

## Betreiberergebnisse Phase 5: verifizierte Referenzwerte und erster realer Basisexport (2026-11-23)

### S036 | Datenbank und Klartext Lieferstatus | **belegt**
- `DB_NAME()=eazybusiness`.
- `dbo.tLieferStatus`: `kLieferStatus=1`, `kSprache=1`, `cName='2-4 Werktage'`. Nur für Sprache 1 als bewiesener Klartext verwenden; nicht unbesehen allen Sprach-IDs zuweisen.

### S037 | Steuern und Einheiten | **belegt, Kontext teilweise offen**
- `dbo.tSteuerklasse`: ID 1 Normaler Steuersatz (`nStandard=1`, `nTyp=4`); ID 2 Ermäßigter Steuersatz (`nStandard=0`, `nTyp=3`).
- `dbo.tSteuersatz`: (Satz-ID 5, Klasse 1, Zone 3, **19 %**), (7,1,4,**19 %**), (6,2,3,**7 %**), (8,2,4,**0 %**); `nPrio` NULL in allen gelieferten Zeilen. Noch **keine** Namen/Länder für Zonen 3/4; **keine** pauschale Brutto- oder Land-Zuordnung.
- `dbo.tMassEinheit`: 31 Referenzwerte vollständig geliefert. Wichtige ID 16 `mL` Anzeige `ml`, Bezugs-ID 15 `L`, `fBezugsMassEinheitFaktor=0.001`; ID 21 `Stk`. **Nie automatisch** `fMassMenge=90000` als 90 ml interpretieren; Artikelwert und Einheiten-/Skalierungslogik müssen am konkreten JTL-Artikel geprüft werden.
- Alle 31 Einheitencodes/Umrechnungsfaktoren und vier Steuersatzkontexte als reiner Quellnachweis: `platform/docs/HW24_JTL_PHASE5_VERIFIED_REFERENCE_VALUES.md`.

### S038 | Sieben eigene Shops und zwölf SCX-Preisarten | **belegt**
- `dbo.tShop`, aktiv: **kShop 1 Kaufland/Friseurbedarf24.shop**, **3 Shopify Fb24**, **4 Amazon.de**, **5 Amazon.fr**, **6 Amazon.it**, **7 Amazon.es**, **8 Amazon.co.uk**. `nTyp=1` für Shopify, `nTyp=6` sonst; Firma in allen gelieferten Zeilen 1.
- `SCX.tChannelPriceType`: 12 konkrete Referenzzeilen. Kaufland kShop1: ID 1 `B2CMin` (Mindestpreis), ID 2 `B2C`; Amazon DE kShop4: ID 3 `B2B` und 4 `B2C`; FR kShop5: 5/6; IT kShop6: 7/8; ES kShop7: 9/10; UK kShop8: 11/12. Getrennte Kundengruppen gemäß Originalausgabe, dokumentiert in oben verlinkter Referenzdatei.
- Beschreibung `B2CMin` erwähnt historische `real.de`-Bezeichnung; **nicht** als nachgewiesener aktiver Repricer werten.
- `SCX.tOffer` nach `kShop`: 1 **3205/1159**, 4 **2735/2294**, 5 **2662/1118**, 6 **2658/1275**, 7 **2658/1279**, 8 **2131/788** (jeweils alle / intern `nIsListed=1`). Summe **16049 / 7913**.
- Für `kShop=3` kein Gruppeneintrag in der SCX-Ausgabe, **nicht** gleichbedeutend mit leerem Shopify-Katalog oder bestätigtem Live-Marketplace-Angebot.
- **Keine** seller-ID-/Marketplace-Kontozugänge, API-/Passwortwerte oder echten Kunden-/Auftragsdatensätze abgefragt/gespeichert.

### S039 | Weitere Preis- und Shopstruktur | **belegt**
- `dbo.tPreisDetail` Spalten: `kPreis`, `nAnzahlAb`, `fNettoPreis`, `fProzent`, `bRowversion`. `dbo.tPreis` enthält den `kArtikel`-, Shop-, Kundengruppen- und ggf. Kundenkontext. Ein Preisbetrag steht demnach **in Detail**, nicht allein in `tPreis`. Keine verkaufsfertige Bruttopreis-Zuordnung ohne Zone/Kundengruppe/Währung/Quelle.
- `dbo.tShopWaehrung` weist `kWebShop` und `kWaehrung` zu; `dbo.tWaehrung` entdeckt, dessen Spalten/Werte noch unbekannt.
- `dbo.tSteuerzone` weist `cName`, `cLandISO`, `kFirma` zu; Werte fehlen.
- `dbo.tKundenGruppe` hat `nNettoPreise`, `fRabatt`; Preise sind kundengruppen-/shopabhängig. Keine Kundendaten exportieren.
- `dbo.tShopScx` hat `cSellerId`, das als operativ sensibel **nicht** in GitHub/öffentliche Logs oder unklassifizierten Spiegel gehört.
- `SCX.tChannel` hat `cCurrency`; `cJson` als potenziell geheime Konfiguration **nicht abfragen**.

### S040 | Erste echte Artikel-Basisextraktion vorbereitet | **GitHub-Code erstellt, Datenexport noch NICHT ausgeführt**
- `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql`: SQL 0 prüft Datenbank, SQL 1 zeigt max. 15 konkrete Artikel, SQL 2 erlaubt den vollständigen **explizit spaltenbegrenzten** Basisartikel-SELECT zum privaten lokalen HeidiSQL-CSV-Export, SQL 3 prüft Aggregatzahlen, SQL 4 zeigt max. 25 JTL-Preisdetail-Kontexte ohne Kundennummern, SQL 5 zeigt Steuertzonenbezeichnungen, SQL 6 fragt nur Währungs-Schemametadaten, SQL 7 zeigt max. 25 SCX-Angebotspreise ohne Verkäufer-IDs/Secrets.
- Der Artikel-SELECT enthält originale Artikelidentität, Rohpreise, Status, Referenzlabels, Rohmenge/Einheit, Artikellagerbestand, `dMod` und hex-kodierte `bRowversion`.
- **Kein** `SELECT *`, keine PII, kein Brutto-/Marktplatz-Livepreis als errechnete Tatsache ausgegeben.
- Die CSV darf **nur lokal/privat** gespeichert werden. **Nicht** 2.692 Zeilen in Chat oder öffentliches GitHub übertragen. Eine spätere sichere Importstrecke braucht eigene Integritäts-/Rechte-/Schema-/Snapshot-Prüfungen; getrennte Abfragen sind **kein atomarer Gesamtsnapshot**.
- **HeidiSQL-Ausführung steht noch aus.** Kein Windows-/PuTTY-/TEST-/LIVE-Deployment oder laufender Sync implementiert.

### S041 | Maschinenlesbare Referenzbeobachtungen und Tests | **GitHub erstellt**
- `platform/config/hw24_jtl_reference_observations.php`: verifizierte, **statische Momentaufnahme** von Lieferstatus, Steuern, Maßeinheitsbeispielen, allen sieben Shops, allen zwölf SCX-Preisarten und den aggregierten SCX-Angebotszahlen. `enabled_for_runtime_data=false`, `tax_zone_geography_confirmed=false`, `shopify_catalog_empty_confirmed=false`, keine API-IDs/Secrets.
- `platform/tests/Unit/JtlReferenceObservationsTest.php`: sichert `2-4 Werktage`, steuerliche Unklarheiten, alle zwölf Preisarten und die getrennten Angebotszahlen 16049/7913 ab.
- Vollständige Werte und methodische Grenzen in `platform/docs/HW24_JTL_PHASE5_VERIFIED_REFERENCE_VALUES.md`.
- Code liegt ausschließlich auf Draft-PR #46. **CI des neuesten HEAD muss erneut geprüft werden.**

### S042 | Phase-6-Preisabfrage präzisiert | **GitHub geändert, HeidiSQL noch nicht ausgeführt**
- `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql` SQL 4 gibt `dbo.tPreis.kKunde` **nicht** aus und deutet dessen 0-/Nicht-0-Status nicht ungeprüft als allgemeine bzw. kundenspezifische Preisart. Stattdessen nur `customer_key_state` mit `kKunde=0` oder `kKunde<>0`.
- Damit keine erfundenen fachlichen Preissemantiken oder ungeschützten Kundenschlüssel in den Pilotexport gelangen. Alle Statements bleiben `SELECT`-only.
- Operator-Ausführung SQL 0,1,3,4,5,6,7 für sicher begrenzte Stichprobe; SQL 2 vollständiger Artikel-Basisexport nur **lokal und privat**. Bisher **nicht ausgeführt**.

## Betreiberergebnisse Phase 6: vollständige Artikeldaten und isolierter Staging-Prototyp (2026-11-23)

### S043 | SQL Phase-6-Befunde | **Betreiber-SELECTs bestätigt**
- Datenbank `eazybusiness`, Artikel-Preview und Aggregate erhalten; `dbo.tArtikel`: **2.692** Artikel, `nDelete=1` **0**, `kLieferStatus IS NULL` **0**, `kMassEinheit IS NULL` **0**, `cBarcode IS NULL/''` **738**.
- Preis-Kontext `dbo.tPreis` + `dbo.tPreisDetail`: Netto-Beträge existieren, verbunden mit Kundengruppen/Shop-IDs und Mengenstaffel `nAnzahlAb`; 25 Zeilen Preview bestätigt. `kKunde=0` markiert als **roher Schlüsselzustand**, keine angenommene Kundenart.
- `dbo.tSteuerzone`: Zone **3 'EU-Zone'**, Zone **4 'Nicht EU-Zone'**, jeweils Firma 1, `cLandISO=DE`. **`cLandISO=DE` bei beiden Zonen belegt keine eindeutige Lieferland-Zuordnung oder gültige Steuersatzberechnung je Transaktion.**
- `dbo.tWaehrung` Schema vorhanden, `kWaehrung`, `cName`, `fFaktor`, `cEAMapping`, `nStandard` etc.; **noch keine Währungswerte oder Zuordnung zu konkreten Kanalangeboten**.
- `SCX.tOfferPrice`: erster Roh-Preisdatensatz mit `kOffer`, `kChannelPriceType`, Preis `fLastPrice`, Menge, `nIsListed`, Status; B2C und Mindestpreis für Kaufland sichtbar. **Keine externe LIVE-Verifizierung**, keine aus JTL abgeleitete Händlerpreishistorie.
- Nur lesende JTL-SELECTs durch Betreiber, keine JTL-Betriebsänderungen nachgewiesen.

### S044 | Vollständige Phase-6-Artikeldatei | **private Dateianalyse bestätigt**
- Generierte lange Text-/CSV-Anlage mit 19 Header-Feldern und **2.692 Datenzeilen** unter privatem Chat-Kontext erhalten. Bei programmgesteuerter Prüfung **2.692 eindeutige `jtl_article_id`** und **2.692 eindeutige `jtl_article_number`**; konsistent zur vorherigen SQL-Gesamtzahl.
- **738 fehlende Barcodes:** 718 leere Strings, 20 `\\N` (= SQL NULL); 31 Gruppen mehrfach vorkommender nichtleerer Barcodes mit insgesamt 67 betroffenen Artikeln; 223 Barcodes beginnen mit Ziffer 0; keine Barcode-basierte Upsert-ID.
- Alle 2.692 `cAktiv=Y`; 2.690 `nDelete=0`, zwei `nDelete=NULL`. 844 Artikel besitzen `kLieferStatus=0`, obwohl vorheriger Null-Check 0 zurückgab. 727 Artikel besitzen `kMassEinheit=0`, obwohl vorheriger Null-Check 0 zurückgab.
- Einheitenverteilung: 1.893 ml, 727 ohne Einheit-ID, 46 Stk, 17 g, 5 m, 3 kg, 1 l. Vereinzelt `fMassMenge<1` für mL (etwa 0,06), zu prüfen; keine automatische Skalierung der Rohmenge.
- Alle 2.692 Artikel haben exportiertes `nLagerbestand=0`, aber **kein Beleg für tatsächlich 0 in WMS/per-Warenlager-Tabellen**.
- Alle 2.692 `jtl_rowversion_hex` **leer**. Blocker für belastbaren Delta-Watermark; Herkunft muss an SQL/HeidiSQL-Konvertierung geprüft werden. Vorher **nicht** mit rowversion 15-minütig spiegeln.
- 20 `fVKNetto=0`, 501 `fAmazonVK=0`, 1.629 `fEbayPreis=0`, alle `fUVP=0`. Dies sind **JTL-Originalfelder**, keine extern verifizierten Live-Angebotspreise.
- **Rohdaten und produktbezogene Einzelpreise werden NICHT in diesem öffentlichen Repository gespeichert.** Aggregierte Qualität und Einschränkungen separat in `platform/docs/HW24_JTL_PHASE6_ARTICLE_EXPORT_QA.md`.

### S045 | Neue HeidiSQL-Phase-7-Sicherheitstests | **in GitHub, noch nicht bei Betreiber ausgeführt**
- `platform/ops/jtl-heidisql-phase7-rowversion-quality-readonly.sql` enthält SQL 0–6: sichere DB-Prüfung, Rowversion-DATALENGTH und zwei Hex-Konvertierungen, Rowversion-Aggregate, Einheit-/Barcode-Qualitätszahlen, per-Warenlager-Nonzero-Counts und Sentinel-NULL/0-Unterschied.
- Nur SQL-SELECTs. Keine Änderung an Tabellen, Indizes, SQL-Server-Einstellungen, JTL-API oder Schnittstellen. **Noch keine Betreiberergebnisse**.

### S046 | Erster echter Staging-Code mit Sicherheitsgates | **GitHub vorbereitet, noch nicht deployed**
- `platform/ops/jtl-article-csv-stage.php` ist eigenständiger PHP-CLI-Importer, der im Standardmodus nur den CSV-Aufbau, Spaltentypen, Duplikate und Qualitätsaggregate prüft. Kein Laravel-/JTL-/SQL-Server-Datenbankzugang.
- **Einzige optionale Schreibwirkung:** `--apply` + überprüfte exakte `--sha256` + erwartete Artikelzahl + neuer Zielpfad in bereits vorhandenem privaten Owner-only-Verzeichnis (0700) legt eine **NEUE isolierte SQLite-Stagingdatei** an. Er verweigert vorhandene Ziel-DBs und Ziele innerhalb des Anwendungsbaums, transaktional mit Rowcount-/SQLite-Integritätscheck. **Keine** bestehende Datenbank wird verändert/überschrieben.
- Rohfelder wie `cArtNr`, `cBarcode`, `fVKNetto`, Menge und Preis ohne automatische Skalierung oder Netto-/Brutto-Umetikettierung gespeichert; `\\N` wird als SQL NULL behandelt. Manifest `is_consistent_cross_table_snapshot=0`. `rowversion_watermark_available` bleibt wegen leerer CSV-Werte false.
- `platform/tests/Unit/JtlArticleCsvStageTest.php`: Validierung ohne DB-Schreiben, Prüfsummen-Sperre, führende EAN-Nullen, NULL, bestehende DB nicht überschreiben, falsche doppelte Artikelnummer abweisen, App-Verzeichnis nicht beschreiben. **CI-Erfolg für aktuellen HEAD muss gesondert bestätigt werden.**
- **Noch kein CSV-Staging auf dem Nutzer-Server ausgeführt, kein echter Datenspiegel online, kein PHP-Skript live freigeschaltet und kein 15-Minuten-Scheduler angelegt.**

### S047 | Nächster Freigabeschritt | **offen**
1. Aktuelle GitHub-CI-Tests für PR #46 prüfen, Fehler korrigieren, vorher nicht installieren.
2. Betreiber führt Phase-7-HeidiSQL-Abfragen separat aus; besonders Rowversion muss geklärt werden.
3. Privater CSV-Transfer **außerhalb des DocumentRoots** zum TEST-Server nach Bedienerfreigabe. `php platform/ops/jtl-article-csv-stage.php --input=/PRIVATE/datei` zunächst nur Dry-Run. Für optionales `--apply` vorher schriftliche Prüfsumme und Zielpfad abstimmen, nie vorhandene TEST/LIVE SQLite-Datenbank überschreiben.
4. Quelle nach Vollständigkeit und Performance testen; WMS/per-Lager-, Shopify- und SCX-Preis-Spiegel getrennt und nur mit verifizierten Quellbeziehungen aufbauen; dann nächtliche Reconciliation und 15-minütigen Delta-Job absichern.

### S048 | Echter, isolierter JTL-Artikeldatenpilot lokal erstellt | **private ChatGPT-Datei, NICHT auf Server**
- Auf Basis der vom Betreiber übermittelten 19-spaltigen, vollständigen Phase-6-Datei wurde lokal eine **private separate SQLite-Stagingdatei** `HW24_JTL_Artikel_Isolierter_Pilot_2026-11-23.sqlite` erzeugt. Ort: geschützte aktuelle Chat-Dateiablage, **nicht** GitHub, **nicht** TEST-Server, **nicht** Plesk, **nicht** bestehende Laravel-/Legacy-DB, **nicht** Windows/JTL.
- Verfahren: Python-Standardbibliothek SQLite, neue Datenbank mit `jtl_article_stage` (PK `jtl_article_id`, sonst String-/NULL-Rohfelder) und `stage_manifest` (Quellsystem eazybusiness/dbo.tArtikel, Quell-Checksum, Zeilenzahl, UTC-Erzeugung, `is_consistent_cross_table_snapshot=0`, `production_or_test_deployed=0`). Transaktional angelegt; Prüfsummen/Importstatus mitgespeichert.
- Tatsächlich im neuen SQLite geprüft: **2.692** Stagingzeilen, **2.692** eindeutige Artikel-IDs, **223** Barcodewerte mit führender Null weiterhin erhalten, **738** fehlende Barcodes inkl. 20 NULL korrekt abgebildet, `PRAGMA integrity_check=ok`.
- Dateigröße **352.256 Bytes**. Die Datei wurde dem Betreiber in diesem Chat als privater Download bereitgestellt. **Kein** Upload der sensiblen Geschäftsdaten in das öffentliche GitHub-Repository.
- **Wichtig:** Diese tatsächliche lokale Python-Stagingprobe ist unabhängig vom neuen **PHP**-CLI-Stagingwerkzeug. Für das PHP-Werkzeug liegen GitHub-CI-Tests mit synthetischen Datensätzen vor, aber kein auf dem Betreiber-TEST-Server durchgeführter Import. Keine falsche Gleichsetzung mit produktivem 15-Minuten-Sync oder live Filament-Anzeige.

### S049 | GitHub-CI für letzte Code-/Journalfassung | **belegt**
- Draft PR #46 Git HEAD `6a3c239fd90421dd8fbbe63d200c5f5219e704c8`:
  - `HW24 isolated Laravel 13 Filament 5 foundation`: **success** https://github.com/NicoVoss1/HW24-scanner/actions/runs/45084873188
  - `HW24 V4.25 Amazon KI Ameise article editor`: **success** https://github.com/NicoVoss1/HW24-scanner/actions/runs/45084873183
- Der CI-Lauf erfolgte auf dem GitHub-Runner, **nicht** auf dem Betreiber-Server. Lokale Anwendung, private JTL-Datenbank und API-Freigaben sind davon unberührt. Die neuen PHP-Stagingtests laufen als Teil der getesteten Laravel-Konfiguration, die Job-Prüfungen wurden erfolgreich abgeschlossen.
- **Nächster Schritt bleibt:** Phase-7-HeidiSQL Rowversion/Bestandsdiagnose; sichere private Dateiübertragung und TEST-Pilot-Ausführung mit Bedienerfreigabe. Keine Merge-/Deployfreigabe aus CI-Erfolg allein ableiten.

### S050 | HeidiSQL Rowversion-Diagnose durch Betreiber | **fünf Rohdatensätze bestätigt**
- Neuer Betreiber-SELECT über `eazybusiness.dbo.tArtikel`: `kArtikel` 3, 4, 6, 7, 8; `DATALENGTH(bRowversion)=8` in allen fünf Fällen.
- Vollständige originale Hexwerte: ID 3 `0x0000000004C0BE42`, ID 4 `0x0000000004C0BE32`, ID 6 `0x0000000006122DA5`, ID 7 `0x0000000006122DA6`, ID 8 `0x0000000006122DA7`. Style-2-Ausgabe jeweils 16 Hexzeichen ohne `0x`. Keine Anzeichen fehlender physischer Rowversion in diesen fünf Zeilen.
- **Verifizierter Fix:** `CONVERT(varchar(18), CONVERT(varbinary(8), bRowversion), 1)` liefert die Werte; die ursprüngliche `CONVERT(varchar(18), bRowversion, 1)` lieferte in der Phase-6-CSV bei allen 2.692 Zeilen leere Textwerte.
- Wichtig: **Nur diese fünf Zeilen direkt nachgeprüft.** Die neue Export-Formatierung ist bestätigt, aber kein aktueller vollständiger 2.692-Rowversion-Datensatz, kein Commit-sicherer Sync-Watermark und keine JTL-Löschhistorie vorhanden. Die ältere private SQLite-Pilotdatei besitzt weiterhin leere Rowversion-Spalten. Keine Datenbankänderungen am JTL.

### S051 | GitHub: korrigierter vollständiger Basisexport | **Code korrigiert, Daten neuer CSV noch nicht erzeugt**
- `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql`, sowohl SQL 1 (TOP-15) als auch SQL 2 (alle Artikel), verwenden jetzt `CONVERT(varchar(18), CONVERT(varbinary(8), a.bRowversion), 1) AS jtl_rowversion_hex`; korrigierter SQL-Code ist GitHub-gespeichert.
- Neue Regressionstests `platform/tests/Unit/JtlRowversionExportSqlTest.php` prüfen, dass beide SELECTs explizit in `varbinary(8)` casten und kein altes Muster mehr enthalten. **CI-Status für neueste Änderung muss geprüft werden.**
- Vorherige Rohartikel-, EAN- und Preisfelder sowie alle Joins bleiben **unverändert**. Keine Änderung an APIs, SQL Server, Shopify/Amazon/eBay, bestehenden TEST-/LIVE-Daten.
- Der neue vollwertige Artikel-Export muss separat durch Betreiber per HeidiSQL ausgeführt und privat abgelegt werden. **Keine** neuen Rowversions aus späterer Zeit unbesehen zu älteren Artikelwerten hinzufügen.

### S052 | GitHub: lesende Phase-8-Vollständigkeits- und Watermark-Diagnose | **Code erstellt, Ausführung offen**
- Neue `platform/ops/jtl-heidisql-phase8-rowversion-verified-readonly.sql`: SQL 0 DB-Kontext; SQL 1 Stichprobe korrekter Hexwerte; SQL 2 Anzahl 8-Byte-Rowversion aller Artikel; SQL 3 Kardinalität der Artikelreferenz-Joins; SQL 4 lesende Anzeige von `@@DBTS` und `MIN_ACTIVE_ROWVERSION()`.
- **Noch keine** Aussage, dass Rowversion-Delta für jede Tabelle oder Transaktionssicherheit fertig implementiert ist. Schon bekannt: Rowversion ist auf untersuchten Tabellen nicht als Indexschlüssel vorhanden. Performance, Transaktionen, Deletes und Schema-Drift bleiben vor 15-Minuten-Takt zu prüfen.
- Phase-7-Bestandsdiagnose SQL 5 + Null-/0-Qualität SQL 6 können unabhängig von dieser Formatkorrektur ausgeführt werden.

### S053 | Dokumentation/Datenschutz für bestehende Pilotdatei | **dokumentiert**
- `platform/docs/HW24_JTL_PHASE6_ARTICLE_EXPORT_QA.md` um verifizierten 5-Zeilen-Hexnachweis und klare Einschränkungen ergänzt.
- Die ältere private SQLite-Pilotdatei vom 23.11. bleibt **unverändert**, weiterhin keine brauchbaren Rowversion-Werte. Kein Rollout und keine Synchronisation.
- GitHub-Publikation enthält nur SQL-Struktur, fünf technische Hex-Testwerte, Code und aggregierte Qualitätsbefunde, nicht vollständige Artikel-/Preisdaten, Kontodaten, Logins oder Credentials.

## Betreiberergebnisse Phase 7/8: Rowversion und Lagerdatensätze (2026-11-23)

### S054 | Vollständige Rowversion- und Lagerzählung | **Betreiber-SQL bestätigt**
- `dbo.tArtikel`: `artikel_gesamt=2692`, `gueltige_rowversions=2692`, je `DATALENGTH(bRowversion)=8`: **100 %** der Artikel besitzen physisch einen 8-Byte-Rowversion-Wert. Das ist die **Quellbestätigung**, nicht ein bereits reparierter CSV-Export.
- `dbo.tlagerbestandProLagerLagerartikel`: `lagerdatensaetze=12717`, `bestand_nicht_null=1532` Zeilen mit `fBestand<>0` (ca. 12,05 % der Lagerzeilen). Die übrigen **11.185** Zeilen haben numerisch `fBestand=0` (da die Spalte NOT NULL ist).
- **Unbedingt nicht verwechseln:** Die 1.532 Werte sind **Lager-Artikel-Schlüsselpaare**, keine 1.532 unterschiedlichen verkäuflichen Artikel. Pro Artikel kann es mehrere Warenlager geben, einschließlich negativem Bestand. Anzahl Artikel mit nonzero-Lagerbestand, Summen je Lager und Bedeutung der Sperrbestände noch offen.
- Frühere `dbo.tArtikel.nLagerbestand=0` bei 2.692 Artikeln beweist **nicht** einen leeren Lagerbestand, da jetzt in der separaten Lagerbestandstabelle 1.532 Nonzero-Zeilen belegt sind.
- Die Original-Datei/Pilot-DB aus Phase 6 enthält weiter leere `jtl_rowversion_hex`; erst ein neuer **vollständiger** CSV-Export mit korrigierter SQL-Cast-Anweisung würde die Rowversion-Werte als Text enthalten.

### S055 | GitHub: Phase 9 für echten Warenlager-Datentransfer | **Code vorbereitet, HeidiSQL-Ausführung offen**
- Neue `platform/ops/jtl-heidisql-phase9-warehouse-export-readonly.sql`: SQL 0 DB-Sicherung, SQL 1 Anzahl Nonzero/Positiv/Negativ und 8-Byte-Rowversions der Lagerzeilen, SQL 2 Aggregation je Warenlager mit Name (ohne Adresse/Personen), SQL 3 Anzahl eindeutiger Artikel mit Lagerbestand ungleich null, SQL 4 TOP-25 Lagerdatenvorschau, SQL 5 **vollständiger Lager-CSV-SELECT**, SQL 6 verwaiste Lagerartikelzuordnungen zählen.
- SQL 5 enthält genau sechs Felder: `jtl_article_id`, `jtl_warehouse_id`, `jtl_stock_raw`, `jtl_available_blocked_raw`, `jtl_shipping_blocked_raw`, `jtl_rowversion_hex`. Der originale SQL Server `bRowversion` wird per `CONVERT(varchar(18),CONVERT(varbinary(8),...,1)` als Hextext ausgegeben; keine heimliche Mengen-/Steuer-/Preisberechnung.
- Die Quelle `dbo.tlagerbestandProLagerLagerartikel` wurde über Phase 3 in JTL mit dem PK (`kArtikel`,`kWarenlager`) bestätigt. Export darf nur privat lokal auf dem Betreiber-Rechner gespeichert werden, nicht im öffentlichen GitHub/DocumentRoot.
- **Achtung:** Separat abgefragte JTL-Artikel-/Lager-CSV liefern **keinen** transaktionskonsistenten gemeinsamen Snapshot; keine Live-Reservierung/Bestellverfügbarkeit oder vollständiger WMS-Bestand dadurch bewiesen.

### S056 | GitHub: privater Lager-Stagingimporter und Tests | **Code erstellt, noch NICHT auf Betreiber-TEST ausgeführt**
- Neu `platform/ops/jtl-warehouse-csv-stage.php`: unabhängiges PHP-CLI-Tool, Standard `validate_only` ohne irgendeine SQL-Verbindung oder Schreibaktion; überprüft exakt sechs Header, (kArtikel,kWarenlager) Eindeutigkeit, reine Dezimalwerte ohne Float-Umwandlung, Rowversion-Format, Positiv-/Negativ-/Null-Bestandszeilen, Artikel- und Lager-ID-Anzahl.
- Expliziter `--apply` benötigt `--input`, Quell-`--sha256`, `--expected-count` und neuen `--db`-Pfad. Nur neue PRIVATE SQLite-Datei in vorhandenem 0700-Verzeichnis außerhalb GitHub-/App-Verzeichnis; vorhandene Dateien nicht überschreiben; SQL-Integritätscheck/Zeilenzahl; Originalmengen als TEXT, Rowversion als Text. Keine JTL-DML, keine Amazon/Shopify/eBay-API, kein Scheduler.
- Neu `platform/tests/Unit/JtlWarehouseCsvStageTest.php`: synthetische Tests für Dry-Run, korrekt vorzeichenbehaftete Stockwerte, SHA-Gate, zusammengesetzte Eindeutigkeit, kein Überschreiben, Null-/fehlerhafte Rowversionsperre. CI-Ergebnis für aktuellen GitHub-HEAD separat nachweisen.
- Beide Staging-Datenbanken (Artikel und Lager) bleiben **getrennte Privatpiloten**, noch nicht mit einer live Filament-Ansicht oder synchronisierendem Dienst verbunden.

### S057 | Nächste konkrete Betreiberaufgabe | **offen**
1. Phase-9-HeidiSQL `SQL 0–4 und 6` einzeln laufen lassen; die Ausgabe klärt Warenlager, Anzahl unterschiedlicher Artikel mit fBestand<>0, Rohmengen, Sperrbestände und Warehouse-Rowversion.
2. Danach `SQL 5` nur als neue private lokale CSV exportieren, Header und Semikolon beachten; keine Rohbestände in GitHub veröffentlichen. Bei Bedarf kann der private Export später als Chat-Datei bereitgestellt werden.
3. Neu korrigierte **Phase-6-SQL-2** für 2.692 Artikel wieder als eigenen privaten Export speichern; ältere SQLite-Pilotdatei **nicht** überschreiben. Kein 15-Minuten-Job bis Watermarks, transaktionssicheres Delta, Löschbehandlung und Laufzeit belegt sind.
4. Nach grünem CI: isolierte lokale QA, dann Betreiberfreigabe für höchstens privaten TEST-Staging-Dry-Run. Im JTL/TEST/LIVE wurde durch diese GitHub-Arbeit keine Datenbank geändert.

## Betreiberergebnisse Phase 9: vollständiger Lagerexport und privater Spiegel (2026-11-23)

### S058 | HeidiSQL-Bericht zu allen zehn Lagerorten | **belegt**
- Quelle eazybusiness / dbo.tlagerbestandProLagerLagerartikel: 12.717 Datensätze; 1.532 positiv, 0 negativ, 11.185 mit Nullbestand; alle 12.717 mit Rowversion-DATALENGTH 8. 1.529 unterschiedliche Artikel-IDs besitzen positive Bestandszeilen; 2.672 Artikel-IDs haben wenigstens eine Lagerzuordnung.
- Lager 1 ZepCh: 2.672 Zuordnungen, 9 positive, 77 Rohbestand; Lager 2 Bug: 163, 1 positiv, 1 Rohbestand; Lager 3 Außenlager 69, 0; Lager 4 WMS-ZepCh+Bug: 2.670, 1.522 positiv, 13.561 Rohbestand und 404 fVerfuegbarGesperrt; Lager 5 WMS-Bug: 161, 0; Lager 6 ZepCh - WMS-ZepCh: 2.670, 0; Lager 7 bis 10 (FBA EU/GB inkl. Pending): je 1.078, 0.
- Rohsumme aller zehn Lager: 13.639; nur im WMS-Lager 4 insgesamt 404 im Quellfeld fVerfuegbarGesperrt, in allen Quellen fAuslieferungGesperrt=0. Diese Werte sind NICHT per se frei verfügbar, reserviert für Aufträge oder live bei Amazon.
- SQL-6-Quellenabgleich ergab 71 Lagerzeilen ohne passenden Artikelstammsatz in derselben eazybusiness. Keine Quelle gelöscht, keine JTL-DML ausgeführt.

### S059 | Gelieferte vollständige Lager-CSV privat analysiert | **verifiziert**
- Datei aus Phase-9-SQL-5 enthält 12.717 Datenzeilen und sechs freigegebene Felder. Sämtliche zusammengesetzten Schlüssel (kArtikel,kWarenlager) einzigartig, alle 12.717 Rowversion-Hexwerte korrekt, Summen identisch mit Betreiber-SQL.
- Lager-CSV gegen zuvor erstellten, älteren Artikelpiloten (2.692 Artikel) geprüft: 71 unzugeordnete Lagerzeilen zu 12 unterschiedlichen Artikel-IDs; 8 unzugeordnete Zeilen haben zusammen 24 positive Rohbestandseinheiten. 32 Artikel im älteren Piloten haben keine Lagerzuordnung. Sieben der 1.529 positiven Lager-Artikel-IDs fehlen im älteren Artikelpiloten, 1.522 sind darin vorhanden.
- Im Lagerexport haben 172 Zeilen fVerfuegbarGesperrt ungleich null, Summe 404; 0 negative fBestand-Zeilen. Die 71 unzugeordneten Lagerzeilen wurden unabhängig bereits per aktuellem JTL-LEFT-JOIN als solche belegt; die 32 Artikel ohne Stock basieren bis zur neuen SQL-Quelle nur auf dem zeitlich versetzten lokalen Abgleich.
- Nur private Dateiablage; SHA256 der Lagerquelldatei: 97986ab4ca39256e6ec4ea7fac4dcd1ee6b6580ac74bd3db89418bc3872ce6f8. Keine vollständigen Preise, Artikeldaten oder Lagerzeilen im öffentlichen GitHub gespeichert.

### S060 | Private kombinierte SQLite-Testdatei erstellt und geprüft | **fertig als Datei, NICHT deployed**
- Neue lokale private Chat-Datei: HW24_JTL_Artikel_Lager_Isolierter_Datenspiegel_2026-11-23.sqlite, 995.328 Bytes, Dateimodus 0600. Ursprüngliche Artikel-SQLite blieb unverändert. Beide Datenquellen sind NICHT transaktionskonsistent gemeinsam aus JTL extrahiert.
- Tabellen: jtl_article_stage (2.692 Artikel, alte leere Artikel-Rowversions), jtl_warehouse_stock_stage (12.717 Lagerzeilen, zusammengesetzter PK und gültige Stock-Rowversions), jtl_warehouse_reference (10 Lagerbezeichnungen), stage_manifest (Hashes, Quelle, UTC, Schutzflags).
- SQLite-Views: v_jtl_article_warehouse_details (Artikel-LEFT-JOIN, 12.678 Zeilen), v_jtl_unmatched_warehouse_rows (71), v_jtl_articles_without_warehouse_rows (32). Originale, auch nicht verknüpfte Quellzeilen bleiben erhalten, kein stiller Datenverlust.
- Prüfläufe: 2.692/12.717 Zeilen, keine doppelten Lager-Schlüssel, 71/32 Sonderfälle, PRAGMA integrity_check = ok. Manifest: article_rowversions_available=0, stock_rowversions_available=1, is_consistent_cross_table_snapshot=0, auto_sync_enabled=0, deployed_to_test_or_live=0.
- Das Artefakt entstand ausschließlich lokal durch Datenkopie in eine NEUE Datei und wurde über Chat-Sandbox privat bereitgestellt; kein GitHub-Upload, keine Installation im PHP-/TEST-/LIVE-Webroot, keine JTL-/API-/Datenbankänderung.

### S061 | Phase 10 Reconciliation und Offline-SQL | **GitHub erstellt, Betreiber-Ausführung offen**
- Neu: platform/ops/jtl-heidisql-phase10-reconciliation-readonly.sql. SQL 0-4 lesen nur DB-Kontext, 71 möglicherweise noch vorhandene Orphans inkl. IDs/Summen, Anzahl aktueller Artikel ohne Lager, Rohsperrbestände im WMS-Lager 4. Kein SELECT auf Kundendaten, keinen DDL/DML- oder API-Zugriff.
- Neu: platform/ops/jtl-private-sqlite-pilot-review.sql mit read-only QA-Abfragen für privaten kombinierten SQLite-Piloten und Integritätsprüfung. Keine privaten Geschäftsdaten eingecheckt.
- Bereits in GitHub vorhandene PHP-Importer und Tests bleiben opt-in, nicht live. Nächster Schritt: aktuelle Phase-10-HeidiSQL-Werte und ein frischer, vollständig korrigierter Phase-6-Artikel-CSV mit gültigen Rowversions; vor produktivem Sync Snapshot-/Transaktions-/Lösch-/Auftragsreservekonzept prüfen. CI für letzten GitHub-HEAD separat ermitteln.

## Betreiberergebnisse Phase 10: Quell-Reconciliation und WMS-Verfügbarkeitsprüfung (2026-11-23)

### S062 | Direkter JTL-Quellabgleich: 71 Lagerzeilen ohne Artikelstamm | **HeidiSQL bestätigt**
- `DB_NAME()=eazybusiness`; `dbo.tlagerbestandProLagerLagerartikel`: 12.717 Lagerzeilen. In `dbo.tArtikel` fehlen zu **71** Lagerzeilen aktuell tatsächlich die Artikelstammsätze; **12** verschiedene `kArtikel`-IDs betroffen, darunter **8** positive Lagerzeilen mit zusammen **24** Rohbestandseinheiten.
- Exakte Gruppenergebnisse (Artikel-ID : Lagerzeilen / Rohbestand / Sperre): `1:3/7/0`; `2:6/3/0`; `5:3/9/0`; `5275:3/0/0`; `5748:7/1/0`; `5766:7/1/0`; `5900:7/1/0`; `5901:7/2/0`; `5902:7/0/0`; `5903:7/0/0`; `5904:7/0/0`; `5942:7/0/0`.
- Das bestätigt die zuvor lokal festgestellten Orphans **gegen die momentane JTL-Quelle**, nicht bloß anhand zweier zeitlich versetzter CSV-Dateien. Keine Ursache der Stammsatzlücken bewiesen. **Kein automatisches Löschen/Umnummerieren**, kein Verkauf ohne gültigen Stammsatz; die 71 Quellzeilen bleiben im isolierten Lagerfaktenspiegel separat sichtbar.

### S063 | JTL-Artikel ohne Lagerzuordnung | **HeidiSQL bestätigt**
- `dbo.tArtikel` hat **2.692** aktuelle Stammsätze; davon **32** ohne eine Zeile in `dbo.tlagerbestandProLagerLagerartikel`. Zuvor nur in altem Artikel-/neuem Lagerdatei-Join sichtbar, jetzt unabhängig direkt in JTL bestätigt.
- Keine Interpretation dieser 32 als defekt, gelöscht oder Bestand=0: können unter Umständen nicht lagergeführte/virtuelle Typen sein, aber Artikeltyp und Ursache bleiben **offen**. Im Spiegel als `no_warehouse_assignment` kennzeichnen, nicht stillschweigend entfernen.

### S064 | WMS-Lager 4 Rohbestand und Sperre | **HeidiSQL bestätigt**
- `kWarenlager=4` (`WMS-ZepCh+Bug`): **2.670** Lagerzeilen, `SUM(fBestand)=13.561`, `SUM(fVerfuegbarGesperrt)=404`, `SUM(fAuslieferungGesperrt)=0`.
- **172** Zeilen `fVerfuegbarGesperrt<>0` und **0** Zeilen `fVerfuegbarGesperrt>fBestand`. Rechenwert `13.561 - 404 = 13.157` wäre eine **bloße Differenz zweier Rohsummen**, noch kein belegter frei verkaufbarer Bestand, keine bestätigte Reservierung in offenen Aufträgen. Noch nicht in Amazon/eBay/Shopify als Angebotsmenge ausspielen.
- Beim Rohbestand der zehn Lager zusammen `13.639` sind `24` Einheiten orphanbezogen; eine ungeprüfte Summierung kann nicht gleichzeitig die 71 Orphan-Zeilen verwerfen und trotzdem 13.639 als nur gemappte Ware bezeichnen.
- Ziel-UI: `Quellrohbestand`, `JTL-Verfügbarkeitssperre (Rohfeld)`, `Aufträge/Reserviert (noch ungeklärt)`, `externe Kanalmengen (noch ungeklärt)` strikt getrennt und mit Quellen-/Zeitstempel.

### S065 | GitHub: Phase 11 prüft tatsächliche Verfügbarkeits-/Auftragslogik | **neu erstellt, noch nicht ausgeführt**
- `platform/ops/jtl-heidisql-phase11-availability-order-metadata-readonly.sql`: SQL 0 DB_NAME, SQL 1 Metadaten zu `Verkauf.tAuftragPosition`, `Verkauf.tAuftragPositionEckdaten`, `dbo.tArtikelHistory`, `dbo.tWarenLagerPlatzArtikel`, `dbo.tLagerbestandShop` und bisherigem Lagerbestand; SQL 2 verifizierbare FKs, SQL 3 begrenzte System-Spaltensuche nach Lager-/Reservierungs-/Sperr-/Bestandsfeldern, SQL 4 Aggregat nur WMS-Lager 4, SQL 5 Orphans aggregiert pro Warenlager.
- **Keine** Kunden, Auftrags- oder Rechnungszeilen ausgelesen, keine PII, keine Preis-/Bestandsdaten in öffentliche GitHub-Quellen übernommen, kein SQL-Schreibzugriff. `Verkauf.tAuftragPosition` bleibt gemäß Quellvertrag `restricted_order` / `not_approved`.
- Nach SQL 1-3 erst weitere ausschließlich aggregierte Belegdaten für Auftragsreservierung planen. Keine falschen Joins mit Kunden-/Anschriftstabellen, kein 15-Minuten-Sync oder Live-Anzeige.
- Der zuvor erstellte kombinierte private SQLite-Spiegel vom 23.11. bleibt unverändert und ist **nicht** an TEST/LIVE angeschlossen. Für einen neuen kohärenten Artikelstand muss weiterhin `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql` SQL 2 mit korrigierter Rowversion privat erneut exportiert werden.

## Betreiberergebnisse Phase 11: JTL-Verfügbarkeit, Auftragsstatus und Historie (2026-11-23)

### S066 | Nachgewiesene Quellfelder für die bisherigen Legacy-Funktionen | **HeidiSQL sys.columns bestätigt**
- `DB_NAME()=eazybusiness`. `dbo.tArtikelHistory` hat die bereits gesuchte **Bewegungshistorie** über `kArtikelHistory`, `kArtikel`, `kWarenLagerPlatz`, `fAnzahl`, `dGebucht`, `fLagerBestandGesamt`, `fVerfuegbar`, `fReserviert`, `kBuchungsart`, `fLagerBestand`, `fLagerBestandInLager`, `dMHD`, `bRowversion`. **Historisch gespeicherte Werte** sind kein aktueller Bestands-/Auftragsreservierungswert; Kommentar, Benutzer, Lieferschein-, Chargen-, Lieferanten- und Einkaufsdaten nicht in öffentliche Logs/Spiegel übernehmen.
- `dbo.tlagerbestandProLagerLagerartikel` enthält `fBestand`, `fVerfuegbarGesperrt`, `fAuslieferungGesperrt`, `bRowversion`; für 12.717 Quellenzeilen bereits vollständig bestätigt. Diese Lagerdaten allein beweisen **nicht** `verfügbar=Bestand-Sperre`.
- **Neu als Kandidat für aktuelle Verfügbarkeit gefunden:** `dbo.tlagerbestand` mit `fLagerbestand`, `fLagerbestandEigen`, `fVerfuegbar`, `fVerfuegbarGesperrt`, `fAuslieferungGesperrt`, `fProduktionGesperrt` (Quellenobjekt/Spalten aus Metadatensuche). Der genaue Primärschlüssel, Granularität und WMS-Beziehung sind **noch nicht** bekannt.
- `dbo.tLagerbestandShop` als kanalbezogener Bestands-**Kandidat**: `kShop`, `cArtNr`, `dBestandsdatum`, `fAnzahl`; keine Aussage über live aktualisierte eBay-/Amazon-/Shopify-Angebotsmengen, bevor Aktualisierungs- und Quellregeln geprüft sind.
- `dbo.tLagerbestandVirtuell` ist ebenfalls entdeckt, nur `fBestand` und `kLagerbestandVirtuell` sind bislang belegt. `dbo.tWarenLagerPlatzArtikel` hat Platz-/Artikel-Schlüssel und Rowversion; keine aktuelle Lagerfachmenge aus diesem Objekt belegt.
- `Artikel.tArtikelTeilmengen.nReservierungsModus` und `Artikel.tArtikelMindestLagerbestandProLager.fMindestbestand`/`fMaximalbestand` sind zusätzliche Kandidaten für Artikel-/Lagerregelungen, noch nicht interpretiert.

### S067 | Echte Auftragspositionsstruktur und referenzielle Beziehung | **HeidiSQL belegt, kein Personen-/Auftragszeilenexport**
- `Verkauf.tAuftragPosition`: `kAuftragPosition` (PK), `kArtikel` (nullable), `kAuftrag`, `nReserviert` (bit), `fAnzahl`, `nType`, `fFaktor`, `bRowversion`; außerdem `fVkNettoGesamt`/`fVkBruttoGesamt` (betragsführende Zeilenfelder). **Nur Spaltennamen**, keine Auftrags-/Kunden-/Rechnungsdaten ausgelesen.
- `Verkauf.tAuftragPositionEckdaten`: `kAuftragPosition` (PK/FK zur Position), `fAnzahlOffen`, `fAnzahlGeliefert`, `fAnzahlGutgeschrieben`, `nArtikelTyp`, `nIstUmlagerung`, `nAuftragStatus` (tinyint, Wertzuordnung offen), `nVersandSperre` (bit), `fTeilmengenfaktorLager`, `kArtikel`, `bRowversion`. **Nicht** aus `fAnzahlOffen` allein eine Reservierung ableiten.
- FKs: `Verkauf.tAuftragPosition.kAuftrag -> Verkauf.tAuftrag.kAuftrag`; `Verkauf.tAuftragPositionEckdaten.kAuftragPosition -> Verkauf.tAuftragPosition.kAuftragPosition`; weitere FKs für SCX OrderItem, Amazon/eBay, Rechnung sind vorhanden. Abfrage von personenbezogenen Referenzen/Raw-Order-Zeilen **nicht autorisiert**; Source-Contract bleibt `restricted_order`/`not_approved`.
- `Verkauf.tAuftrag.nReserviert`, `Verkauf.tAuftragEckdaten.nIstFuerAuslieferungGesperrt` als Metadata-Kandidaten; noch keine Status-/Freigabelogik validiert. Ziel UI strikt: Auftrag offen, Position reserviert, Sperrung, Rohbestand, verfügbar, historische Werte und Kanalmengen separat.

### S068 | JTL WMS-Lager 4, Lagerfach- und Orphan-Aggregate | **HeidiSQL bestätigt**
- `kWarenlager=4` hat weiterhin **2.670** Zeilen, **1.522** mit `fBestand>0`, **172** mit `fVerfuegbarGesperrt>0`. Dies betrifft laut `COUNT_BIG(DISTINCT kArtikel)` **172 unterschiedliche Artikel**; `SUM(fBestand)=13.561`, `SUM(fVerfuegbarGesperrt)=404`; **0** Zeilen mit `fVerfuegbarGesperrt>0 AND fBestand=0`. Kein Verkaufskanalbestand daraus ableitbar.
- 71 Orphan-Lagerzeilen nach Warenlager: ID 1 **12 Zeilen/18 Roh-Einheiten**, ID 2 **3/1**, ID 3 **3/0**, ID 4 **10/5**, ID 5 **1/0**, ID 6 **10/0**, IDs 7 bis 10 jeweils **8/0**. Summe 71, 24 Einheiten. Anzahl betroffener IDs je Lager: 12,3,3,10,1,10,8,8,8,8. Der eindeutige Satz von zwölf Orphan-Artikel-IDs ist in S062 enthalten.
- Kein Fachwissen ohne Quelldaten behaupten: 404 Sperrwerte sind **nicht** bewiesene Bestellreservierungen, `dbo.tArtikelHistory.fReserviert` ist ein **historischer** Wert und weder Lager-4- noch Shop-Verfügbarkeit.

### S069 | GitHub Phase 12: Datenschutzfreundliche Quellvalidierung | **erstellt, noch nicht ausgeführt**
- Neues, ausschließlich lesendes SQL-Skript `platform/ops/jtl-heidisql-phase12-stock-orders-history-aggregates-readonly.sql`.
- SQL 0: eazybusiness sicher bestätigen. SQL 1: vollständige **Spalten-Metadaten** von `dbo.tlagerbestand`, `dbo.tLagerbestandShop`, `dbo.tLagerbestandVirtuell`, ausgewählte Metadaten von `Verkauf.tAuftrag` und `Verkauf.tAuftragEckdaten`. SQL 2: Primär-/Unique-Key-Metadaten der drei Bestandsquellen.
- SQL 3: nur summierte Rohwerte von `dbo.tlagerbestand`, unverifiziert als globaler verfügbarer Bestand. SQL 4: `dbo.tLagerbestandShop` anonymisierte kShop-Summen mit nur Shop-Namen und Quell-Datumsbereich. SQL 5: **aggregierte** Eckdaten-Status-/Artikeltyp-/Umlagerungs-/Sperr-Fallzahlen und Mengen; SQL 6: **aggregierte** Position-`nReserviert`-/Status-/Typ-Verteilung ohne Einzelpositionen oder Kunden. SQL 7: maximal die **letzten 14 Tage** tagweise summierte Bewegungshistorie; keine Benutzer/Kommentare/Chargen/Belegnummern/Beträge.
- Keine DML/DDL, kein Direct SQL Server-Zugang durch ChatGPT, kein Auftragszeilen- oder Kundendatenimport, keine Aktivierung bestehender APIs/Jobs. Operative Ausführung in HeidiSQL durch Betreiber, danach Status-Fachmapping nur anhand bewiesener Werte.
- Das neue Skript ist **kein** 15-Minuten-Sync, keine Produktivanbindung, kein Ersatz für getrennte Auftrag-/Reservierungs-/WMS-Lagerplatz- und Kanalmengenvalidierung.

## Betreiberergebnisse Phase 12: JTL-Zentralbestand, Auftragsaggregate und Bewegungshistorie (2026-11-23)

### S070 | Echte globale Bestandsquelle und Primärschlüssel | **HeidiSQL bestätigt**
- Datenbank `eazybusiness`. `dbo.tlagerbestand` hat **PK(kArtikel)** und **2.692** Zeilen. Spalten `fLagerbestand`, `fLagerbestandEigen`, `fVerfuegbar`, `fVerfuegbarGesperrt`, `fZulauf`, `fAufEinkaufsliste`, `dLieferdatum`, **`fInAuftraegen`**, `fAuslieferungGesperrt`, `fProduktionGesperrt`, `bRowversion` sind real als physische Felder bestätigt.
- Globale Quellaggregate: **1.544** Zeilen mit `fLagerbestand<>0`, **1.450** mit `fVerfuegbar<>0`. `SUM(fLagerbestand)=14.568`, `SUM(fLagerbestandEigen)=14.568`, `SUM(fVerfuegbar)=14.107`, `SUM(fVerfuegbarGesperrt)=407`, `SUM(fAuslieferungGesperrt)=0`, `SUM(fProduktionGesperrt)=406`. `SUM(fInAuftraegen)` wurde **NOCH NICHT** abgefragt.
- Frühere zehn Warenlager `SUM(fBestand)=13.639`, `SUM(fVerfuegbarGesperrt)=404`; arithmetische Unterschiede zur globalen Tabelle **+929** Rohbestand und **+3** Sperrwerte. Quellen können unterschiedlichen Datenstands-/Berechnungsregeln folgen; nichts hinzuaddieren oder automatisch angleichen.
- Die Differenz `fLagerbestand - fVerfuegbar=461`; abzüglich globaler Sperre 407 bleibt **54** als rechnerischer Rest. **Hypothese**: `fInAuftraegen` oder weitere JTL-Regeln erklären diesen Rest. Keine Formel freigegeben, bevor per Artikel UND im Aggregat getestet.
- `dbo.tLagerbestandVirtuell`: Schlüssel `kLagerbestandVirtuell`, Unique(`kArtikel`,`iModuleId`), mit `nLagerBeachten`, `fBestand`, `fZulauf`, `dGeandert`, `bRowversion`. Noch keine Rohdaten/Verknüpfungssemantik; virtuelle Bestände dürfen nicht automatisch zum physischen Bestand addiert werden.
- `dbo.tLagerbestandShop`: PK(`kShop`,`cArtNr`). Die SQL-4-Gruppierung gab **nur Header und keine Datenzeilen** zurück. Das spricht für aktuell leere Tabelle, muss bei Bedarf mit direktem `COUNT_BIG(*)` bestätigt werden. Keine Interpretation als leeres Shopify-/Amazon-/eBay-Sortiment.

### S071 | Order-Eckdaten/Reservierungskennzeichen | **nur aggregierte HeidiSQL-Ergebnisse**
- `Verkauf.tAuftragPositionEckdaten`: `nAuftragStatus=0` in **allen 9.040** abgefragten Positions-Eckdaten; Raw-Typen 0 (3.315 Zeilen), 3 (5.722 ohne Umlagerung, 3 mit Umlagerung); `nVersandSperre=false` in den Gruppen. Ohne Status-Dictionary **nicht** schließen, alle Aufträge seien offen oder abgeschlossen.
- **11** Positionen mit `fAnzahlOffen>0`, Gesamt `SUM(fAnzahlOffen)=22` Rohmengeneinheiten; `SUM(fAnzahlGeliefert)=6.810` (kumuliert/historisch, nicht neu ausgeliefert). Die 3 Umlagerungspositionen haben 0 offene und 6 gelieferte Rohmengen. Typ 0: 4 offene Zeilen / 6 Menge; Typ 3: 7 offene /16 Menge.
- `Verkauf.tAuftragPosition` über bestätigten FK `kAuftragPosition` zu Eckdaten: ebenfalls 9.040 Positionszeilen, 0 Eckdaten fehlen; **9.036** `nReserviert=true`, nur **4** false. Trotz historischer `nReserviert=true`-Kennzeichen haben nur 11 Positionen offene Mengen. **Nicht** 9.036 als offene Reservierungen oder 22 als gesicherten aktuellen Lager-4-Reservierungsbestand interpretieren.
- Kundendaten, Adressen, Auftragsreferenzen, Rechnungssummen/Preis-Einzelzeilen wurden nicht exportiert. Verbindung/Status-Fachlogik bleibt gesperrt bis Quellenbestätigung.

### S072 | Bewegungshistorie 14 Tage | **aggregiert und ohne Identifikatoren**
- `dbo.tArtikelHistory` Datumsauswertung vom 09.11. bis 20.11.2026 (11 Tage mit Bewegungen): 58/23/18/35/52/19/83/118/101/108/22 Zeilen nach Tagen 09/10/11/12/13/14/16/17/18/19/20.
- Gesamtergebnis im gelieferten Fenster: **637** Bewegungszeilen, `SUM(fAnzahl)=+500` Rohmengeneinheiten. Am 20.11. 22 Bewegungen mit Netto `-5`; am 10.11. 23 mit `-16`. Diese Bewegungsmenge ist kein gesicherter globaler Bestandszuwachs und kein Ersatz für aktuelle verfügbare Mengen. Keine Buchungsart/Bewegungsart-Zuordnung bisher.
- Historische `fReserviert`/`fVerfuegbar` bleiben separat von aktuellen `dbo.tlagerbestand`-Werten. Künftiger UI-Reiter `Bewegungshistorie` nutzt eigene Provenienz und Benutzerdatenschutz.

### S073 | GitHub Phase 13: aktuelle Bestände und Reservierungen gegentesten | **CODE vorbereitet, Betreiber-Ausführung offen**
- Neu `platform/ops/jtl-heidisql-phase13-availability-reconciliation-readonly.sql` (SQL 0–7). Nur SELECT, keine JTL-Schreibzugriffe.
- SQL 1: erstmalig `fInAuftraegen` summieren, globale Sperr-/Verfügbarkeits- und negative Mengen prüfen, Rowversion-Anzahl. SQL 2: mögliche Rohformel **pro kArtikel** rein diagnostisch testen, kein Verkaufen/Schreiben. SQL 3: **FULL OUTER** globaler kArtikel-PK gegen aggregierte Warenlager-Quelle und fehlende IDs/Differenzmengen zählen. SQL 4: TOP-20 Produkt-ID-Differenzen (ohne Namen/EAN/Kunden); SQL 5: virtuelle Rohwerte nach anonymisiertem nLagerBeachten; SQL 6: Leerzustand tLagerbestandShop direkt zählen; SQL 7: nur sys.indexes-Metadaten zur Historie-Performance.
- SQL 2 ist ausdrücklich eine **Hypothesenprüfung**, keine bestätigte JTL-formel; fProduktionGesperrt, Bestellstatus, Fulfillment/FBA, inAuftraegen und Sperrlogik bislang nur als getrennte Quellfelder gesichert.
- Bei guten Ergebnissen im nächsten Schritt `dbo.tlagerbestand` als zusätzliche **private, isolierte, vollständig quellengetreue** Spiegel-Komponente vorsehen: `kArtikel` primär, Rowversion + raw `fInAuftraegen`, `fVerfuegbar`, `fLagerbestand`, `fVerfuegbarGesperrt`, `fProduktionGesperrt` mit Quell-Zeitstempel. Nicht in öffentliche GitHub-Daten einchecken; JTL-Test/Live bleibt unberührt.
- TEST-Live-Deployment, 15-Minuten-Job, Schema-Index-Änderungen, Order-Zeilenkopie und API-Writes bleiben deaktiviert.

## Betreiberergebnisse Phase 13: JTL-Verfügbarkeitsgleichung und 929-Einheiten-Abweichung (2026-11-23)

### S074 | Zentrale JTL-Verfügbarkeit: Formel für 2.691 von 2.692 Artikeln bestätigt | **HeidiSQL geprüft**
- `DB_NAME()=eazybusiness`; `dbo.tlagerbestand` **2.692/2.692** Zeilen mit gültiger 8-Byte-Rowversion. 32 Artikel haben `fInAuftraegen>0`, 0 negativ; **2** Artikel mit `fVerfuegbar<0`. Global `SUM(fLagerbestand)=14568`, `SUM(fVerfuegbar)=14107`, `SUM(fInAuftraegen)=55`, `SUM(fVerfuegbarGesperrt)=407`, `SUM(fProduktionGesperrt)=406`.
- Hypothese per Artikel: `fLagerbestand - fVerfuegbarGesperrt - fInAuftraegen = fVerfuegbar`. **2691** Zeilen stimmen exakt, **1** Artikel weicht ab; Residuum über alle `SUM(fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen-fVerfuegbar)=-1`. Auf Summenebene ergibt 14568 - 407 - 55 = **14106**, die JTL-Quellsumme `fVerfuegbar` lautet **14107**. Beim Ausnahmeartikel ist JTL-`fVerfuegbar` nach dieser Hypothese also **eine Einheit höher** als die Formel.
- Das ist **kein Beweis einer allgemeingültigen Formel**, insbesondere nicht für Webshop-/Marketplace-Verkaufsmengen oder andere JTL-Stände. Der Ausreißer muss mit echten Quellfeldern / Rowversion identifiziert werden, nicht auf 0 korrigiert. `fProduktionGesperrt` (406) **nicht** nochmals pauschal abziehen: Überlappung mit `fVerfuegbarGesperrt` noch offen.

### S075 | 929 Einheiten Abweichung erklärt durch ID-Abdeckung, nicht gemeinsame Lagerartikel | **HeidiSQL bestätigt**
- `FULL OUTER` global `dbo.tlagerbestand` (PK `kArtikel`) versus nach `kArtikel` summierte `dbo.tlagerbestandProLagerLagerartikel`: **2704** Artikel-IDs in Vereinigung; **2660** gemeinsame IDs, **32** globale Artikel ohne Warenlagerzeile, **12** Warenlager-IDs ohne globale Bestandszeile.
- Von den **2660 gemeinsamen Artikel-IDs weicht keine** in `fLagerbestand` versus `SUM(fBestand)` ab (SQL 4 TOP(20) lieferte **nur Header**). Die gemeinsame Summe ist auf beiden Seiten **13615**.
- **32** nur-globalen IDs haben **953** Rohbestandseinheiten; **12** nur-Warenlager-IDs haben **24** Rohbestandseinheiten. Daher globale Summe **13615+953=14568**, Warenlager-Summe **13615+24=13639**, Differenz **953-24=929** vollständig rechnerisch erklärt. Ursache für Existenz der 32/12 unterschiedlichen ID-Gruppen nicht bewiesen. **Nicht** „953 zusätzlich zu WMS“ oder „24 doppelt“ unterstellen; kein source write, keine stille Bereinigung.
- Die früher belegten **71** Lagerzeilen zu den zwölf Warenlager-only IDs sind weiterhin als separate Rohdatensätze im privaten Spiegel zu halten. Global-only (32) mit Bestandswerten dürfen nicht als physisch zugeordnet oder frei verfügbar etikettiert werden. Status-/Artikeltyp-Abgleich später.

### S076 | Virtuelle und Shop-Lager, Bewegungsindex | **HeidiSQL bestätigt**
- `dbo.tLagerbestandVirtuell` SQL-5-Gruppierung nach `nLagerBeachten` lieferte **keine Zeilen**, also derzeit keine virtuellen Bestandsdatensätze in dieser Quelle. Das ist keine allgemeine Aussage über alle denkbaren externen/virtuellen Bestandsquellen.
- `SELECT COUNT_BIG(*) FROM dbo.tLagerbestandShop` ergibt **0**. Diese Tabelle liefert aktuell keine Kanalmengen und ist kein Beleg dafür, dass Amazon/eBay/Shopify keine aktiven Angebote haben.
- `dbo.tArtikelHistory` Indexmetadaten: `IX_tArtikelHistory_kArtikel` führt `kArtikel` als erste und `dGebucht` als dritte Index-Schlüsselspalte; weiterer Index enthält `dGebucht` als Include-Feld. Ohne Index-/Ausführungsplan keine garantierte Aussage zu optimierter Datumssuche. Kein SQL-Index/Schema verändert.
- **Wichtig:** `dbo.tlagerbestand` bietet Rowversion und Originalwerte zu `fInAuftraegen`/`fVerfuegbar`; das rechtfertigt eine künftige **private schreibgeschützte** neue Quellkomponente, aber keinen aktuellen TEST/LIVE-Push.

### S077 | GitHub Phase 14: 1 Einzelfall und 32 zentrale Artikel identifizieren | **Skript erstellt, Betreiber-Ausführung offen**
- `platform/ops/jtl-heidisql-phase14-availability-anomaly-and-global-stock-readonly.sql`, SQL 0–6, nur lesende SQL-SELECTs und technisch begrenzte Artikel-IDs/Bestandsfelder.
- SQL 1: **ein** Formelausreißer mit Rohfeldwerten, beobachteter Rowversion und Residuum; SQL 2: nur-globaler Bestand 32/953 plus Sperr-/Auftragsaggregate; SQL 3: TOP 15 global-only Artikel nach Rohbestand; SQL 4: zwei negative JTL-Verfügbarkeiten; SQL 5: **Überlappung** Produktion-/Verfügbarkeitssperrkennzeichen; SQL 6: **OPTIONAL** neuer privater Export der 2.692 aktuellen Zentralbestände (12 Originalfelder inklusive `bRowversion` als Hex) für eine spätere isolierte Spiegelkomponente. Kein neuer privater CSV durch Code selbst und kein Deploy.
- **Empfohlene Betreiberaktion:** Zuerst SQL 0–5 jeweils separat in HeidiSQL ausführen; anschließend neuen privaten CSV (SQL 6) und korrigierten Artikel-CSV erst bei gewünschtem vollständigem Stage-Refresh erzeugen. Die ältere lokale SQLite-Pilotdatei bleibt unverändert.
- Weiterhin nicht freigegeben: Schema-/JTL-Schreibzugriffe, automatischer 15-Minuten-Sync, Lösch-/Korrekturlogik, Fremd-API-Änderung, TEST-/LIVE-Deploy und direkte Nutzung unvalidierter Verfügbarkeitswerte für Verkaufskanäle.

## Betreiberergebnisse Phase 14: Zentralbestand und vollständig korrigierte Kontrollformel (2026-11-23)

### S078 | Phase-14-Diagnose exakt gegen eazybusiness bestätigt | **HeidiSQL-Beleg**
- DB `eazybusiness`. Der einzige Ausreißer der anfänglichen Hypothese `fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen=fVerfuegbar` ist Artikel **5720**: `fLagerbestand=1`, `fVerfuegbarGesperrt=1`, `fInAuftraegen=0`, `fVerfuegbar=1`, `fProduktionGesperrt=0`, `fAuslieferungGesperrt=0`, `fLagerbestandEigen=1`, Residuum `-1`, Rowversion `0x0000000005F6F54C`.
- Zentrale Artikel ohne Warenlagerzeile: **32**, davon **22** mit positivem Rohbestand, **953** Bestandseinheiten, **950** verfügbar, **3** im Quellfeld `fVerfuegbarGesperrt`, **1** in Aufträgen, **2** `fProduktionGesperrt`; **0** negative Verfügbarkeiten in diesen 32.
- TOP-15 der 32 zentralen IDs enthält als größte drei: `5961:438`, `5948:93`, `5707:60` Rohbestand. Ursachen für fehlende Warenlagerzuordnungen nicht ermittelt; Quelltabellen nicht verändert.
- Zwei negative JTL-Verfügbarkeiten: `kArtikel=2717` (Bestand 0, verfügbar -1, in Aufträgen 1), `2731` (Bestand 2, verfügbar -1, in Aufträgen 2, verfügbar gesperrt 1, Produktion gesperrt 1).
- Sperrkennzeichen: **174** Zentralartikel `fProduktionGesperrt<>0`, **175** `fVerfuegbarGesperrt<>0`, **174** mit beiden, **0** mit Produktion gesperrt größer als Verfügbarkeit gesperrt; Quellsummen **406** vs **407**. Nur Artikel **5720** unterscheidet die beiden Sperrfelder (derzeitige vollständige CSV); keine Doppel-Subtraktion.

### S079 | Phase-14-SQL-6 vollständige private Original-CSV | **lokal vollständig geprüft**
- Nutzer stellte echte `dbo.tlagerbestand`-CSV mit 12 genehmigten Spalten bereit, **2.692** Zeilen, exakt 2.692 eindeutige positive `kArtikel`-IDs, eindeutige und syntaktisch korrekte **2.692** 8-Byte-Rowversion-Hexwerte; kein unvollständiger Header/Datensatz; UTF-8/HeidiSQL-Semikolon. NULL-Liefertermine als `\\N` für 2.692 Zeilen.
- Quellsummen direkt aus der privaten CSV mittels Python `Decimal` bestätigt: `fLagerbestand=14568`, `fLagerbestandEigen=14568`, `fVerfuegbar=14107`, `fInAuftraegen=55`, `fVerfuegbarGesperrt=407`, `fProduktionGesperrt=406`, `fAuslieferungGesperrt=0`, `fZulauf=0`, `fAufEinkaufsliste=0`. Anzahl Artikel mit negativem `fVerfuegbar` **2**.
- Dateiname nur lokal: `Eingefügter Text(10).txt`; SHA256 `0001a1a6d5ebcf4d64b838a240ad89339bd1a282f230325c28d648bd0df85b10`. Keine komplette Original-CSV, Artikel-/Preis-/EAN-Reihen oder Passwörter in GitHub committed.
- Abgleich mit vorherigem privaten Artikel-/Lager-Pilot: exakt 2.692 gemeinsame Stammdaten-IDs, 2.660 gemeinsame Warenlager-IDs ohne Rohbestandsdifferenz, **32** Global-only `kArtikel` mit **953** Rohbestand und **12** Warehouse-only `kArtikel` mit **24** Rohbestand; 71 Warehouse-only Quellzeilen erhalten.

### S080 | **NEUE ERKENNTNIS**: bessere Verfügbarkeitskontrollformel passt bei 2.692/2.692 | **vollständige CSV geprüft**
- Die alte Probe `fLagerbestand - fVerfuegbarGesperrt - fInAuftraegen` ergibt **2.691/2.692** Gleichheiten mit dem von JTL gelieferten `fVerfuegbar`; Ausnahme Artikel 5720 (-1).
- **Korrigierte datenbasierte Hypothese:** `fLagerbestand - fProduktionGesperrt - fInAuftraegen = fVerfuegbar`. Alle **2.692/2.692** Originalzeilen erfüllen die Gleichung exakt, **0** Restabweichungen. Auf Summenebene `14568 - 406 - 55 = 14107`, exakt JTL-Quelle. Exakt ein Artikel (5720) hat `fVerfuegbarGesperrt=1` und `fProduktionGesperrt=0`.
- Das **beweist nur die Relation im vollständig vorliegenden Datenstand**, nicht zwingend die interne JTL-Berechnungsroutine in anderen Zuständen oder die Amazon-/Shopify-/eBay-Angebotsmenge. Der Spiegel muss weiterhin **JTL `fVerfuegbar` als Originalwert zeigen**, Rohfelder einzeln behalten und beide Formeln höchstens als explizit beschriftete QA-Kontrollrechnungen anbieten. Keine stillschweigende Änderung an JTL oder Shop-Mengen.

### S081 | Neue isolierte private SQLite-Kopie mit **drei** JTL-Datenquellen | **lokal erstellt und geprüft**
- In Chat-Dateiablage neue private Datei `HW24_JTL_Artikel_Lager_Zentralbestand_Isolierter_Datenspiegel_2026-11-23.sqlite`, **1.146.880 Byte**, mode `0600`. Explizite neue Kopie der ursprünglichen kombinierten Pilotdatei; die vorherigen Artikel- und Lagerpiloten sind **unverändert**.
- Vorhandene `jtl_article_stage` (2.692 ältere Artikelstamm-Datensätze und **leere Artikelstamm-Rowversions**) sowie `jtl_warehouse_stock_stage` (12.717 Warehouse-Originalzeilen, Rowversion gültig) bleiben vollständig. Neue `jtl_global_stock_stage` speichert alle **2.692** zentralen Originalzeilen mit Source-Rowversion, Decimal-Originalwerte als **TEXT** und leeres Datum als SQL NULL. PK `jtl_article_id` mit FK zur Artikelstammkopie; alle IDs stimmen überein.
- Neue Diagnosen/Views: `v_jtl_article_inventory_overview` (2.692 Artikel, LEFT JOIN, JTL verfügbar / in Aufträgen / gesperrt, Lagerzuordnungsstatus), `v_jtl_global_stock_formula_anomalies` (ein alter Hypothesenausreißer 5720), `v_jtl_global_stock_production_formula_control` (2.692 Übereinstimmungen und null Abweichungen), `v_jtl_global_stock_sperrfeld_differences` (nur 5720), `v_jtl_global_stock_without_warehouse` (32), `v_jtl_warehouse_stock_without_global` (71 Warehouse-Zeilen).
- `global_stock_stage_manifest` enthält Quellen-Hash, 2.692 Zeilen, 32/12 Sonderfälle, 953/24 Rohbestand, Schutzflags. `global_stock_formula_qa` enthält zwei ausdrücklich benannte Kontrollhypothesen mit 2.692/2.692 und 2.691/2.692 Ergebnissen, beide **is_confirmed_jtl_implementation=0**.
- Integritätsprüfung `PRAGMA integrity_check=ok`, `PRAGMA foreign_key_check` leer, erwartete Spiegelsichten auf 2.692/32/71/1 Zeilen verifiziert. **Kein** periodischer Sync, kein TEST-/LIVE-Deploy, kein SQL-Server-Zugriff aus Python oder lokale Webroot-Änderung. Keine kundenspezifischen Preise/Artikel-/Bestandszeilen in öffentlichen GitHub-Quellen. Original-Artikel-Rowversions bleiben leer; Quellen nicht transaktionskonsistent auf einen gemeinsamen Zeitpunkt gesichert.

### S082 | Phase 15 für unabhängigen Quellbeweis und Artikel-5720-Kontext | **GitHub erstellt, HeidiSQL-Ausführung offen**
- `platform/ops/jtl-heidisql-phase15-production-availability-validation-readonly.sql` enthält SQL 0 DB_NAME, SQL 1 Aggregatkontrolle beider Kontrollformeln DIREKT auf aktuell `dbo.tlagerbestand`, SQL 2 Vergleich `fProduktionGesperrt` vs `fVerfuegbarGesperrt` samt Rowversion, SQL 3 technische Artikel-5720-Daten mit COUNT der Warenlagerzeilen und Stammdatensatz-Flags ohne Preise, SQL 4 Artikel-`cAktiv`/`nDelete`-Aggregat für 32 global-only IDs, SQL 5 optionale auf zwölf Buchungen begrenzte Bewegungshistorie des einen Artikels.
- **Keine** Auftrags-/Kunden-/Preisdatensätze exportieren, keine DML/DDL, keine Datenkorrektur oder automatische Verkaufsfreigabe. Quelleffekte/Statuskriterien für nicht zugeordnete Artikel sind weiter offen.
- Neue Phase-6-SQL-2 **vollständige Artikelstamm-CSV mit korrigierter Rowversion** bleibt der nächste Baseline-Baustein, sofern ein gemeinsamer zukünftiger Delta-Spiegel geplant ist. Lösch-/Transaktions-/Watermark-Risiken und TEST-/LIVE-Cutover noch nicht freigegeben.

## Betreiberergebnisse Phase 15: Verfügbarkeitsformel in aktueller JTL-Datenbank bestätigt (2026-11-23)

### S083 | 2.692/2.692 Gleichheiten DIREKT in eazybusiness | **HeidiSQL nachgewiesen**
- `DB_NAME()=eazybusiness`, `source_stock_rows=2692`. Zentrale Verfügbarkeits-Kontrollgleichung `fLagerbestand - fProduktionGesperrt - fInAuftraegen = fVerfuegbar`: **2692 Übereinstimmungen, null Abweichungen, Residuumssumme 0**.
- Frühere Kandidatengleichung mit `fVerfuegbarGesperrt` statt `fProduktionGesperrt`: **2691 Gleichheiten und genau eine Abweichung**. Exakt dieselben Befunde wie aus der vollständigen privaten Phase-14-CSV, somit nun auch unabhängig direkt im SQL-Server belegt.
- Die Relation ist für den geprüften Datenstand **arithmetisch vollständig bestätigt**, NICHT als JTL-internes Berechnungsverfahren oder als kanalübergreifende verkaufbare Menge bewiesen. Für den Spiegel `fVerfuegbar` **originalgetreu** führen; Kontrollgleichung und Residuum nur separat als QA-Feld, niemals eine abweichende JTL-Zahl heimlich korrigieren. Keine Minus-1-Behandlung durch automatische Korrektur.

### S084 | Einziges Sperrfeld-Differenzpaar und Artikelstatus | **HeidiSQL nachgewiesen**
- Artikel `kArtikel=5720`, aktiv `cAktiv=Y`, `nDelete=0`; Artikelstamm hat `nLagerbestand=0` (bekanntlich kein verlässliches globales Lagerfeld), `dMod=2026-03-23 20:48:49.71`.
- Quelle `dbo.tlagerbestand`: `fLagerbestand=1`, `fVerfuegbar=1`, `fVerfuegbarGesperrt=1`, `fProduktionGesperrt=0`, `fInAuftraegen=0`, Rowversion `0x0000000005F6F54C`. Artikelstamm-RV `0x0000000006123209`, gesondert von Zentralbestand-RV gespeichert (unabhängige Tabellen, keine Gleichheit zu erwarten).
- Warenlagerdiagnose `COUNT_BIG(*)=0`, `warehouse_stock_raw=0` für 5720. Somit **keine** physische Zuordnung in der geprüften Pro-Lager-Tabelle, obwohl ein positiver globaler Bestand und positive globale Verfügbarkeit vorliegen. Nicht als Artikeldefekt oder Lagerbestand einer konkreten Location ausgeben.
- Historie `dbo.tArtikelHistory` SQL 5 für `kArtikel=5720`: **kein Datensatz** zurückgegeben; kein Nachweis einer aktuellen/historischen Bewegung dieses Artikels aus genau dieser Tabelle. Nicht als generelle Inaktivität oder Datenverlust interpretieren.

### S085 | 32 aktive, nicht gelöschte Artikel ohne physische Lagerzuordnung | **HeidiSQL bestätigt**
- Artikelstamm-Flags für **alle 32 global-only Artikel**: `cAktiv=Y`, `nDelete=0`, davon **22** mit positivem globalem Rohbestand. Rohbestände **953**, Original-JTL-`fVerfuegbar` **950**, `fInAuftraegen` **1**. Aus Phase 14 `fProduktionGesperrt=2` bestätigt: 953 - 2 - 1 = 950.
- **Businessrisiko/UX-Regel:** Diese 32 Artikel sind im JTL-Artikelstamm aktiv und teils als zentral verfügbar ausgewiesen, haben aber keine einzige Zeile in `dbo.tlagerbestandProLagerLagerartikel`. Deshalb **nicht** in der UI als „in WMS-ZepCh+Bug / physisch eingelagert“ behaupten, nicht ohne Versand-/Shopregel als marketplace-ready erklären und nicht automatisch Lagerzuordnungen, Bestandswerte oder Verfügbarkeitsfelder verändern.
- Noch offen: Handelt es sich um besondere Artikeltypen/Altmigration/Zuordnungslücken? Phase-15-Beleg reicht hierfür ausdrücklich **nicht**. Anzeigen: `JTL verfügbar (Original)` und `Warenlagerzuordnung: keine` als zwei separate Wahrheiten.

### S086 | Nächster Umsetzungsschritt statt weiterer Testschleifen | **Export-Baseline offen**
- Der **fachliche Kern der Quellverfügbarkeitskontrolle ist abgeschlossen** (2692/2692 im laufenden SQL Server). Kein weiterer Formel-Explorations-SQL-Zyklus erforderlich, solange keine neuen Abweichungen entstehen.
- Jetzt SQL 2 des bereits bestehenden, korrigierten `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql` in HeidiSQL erneut ausführen und als **NEUE private vollständige UTF-8/Semikolon-Datei mit Headern** sichern; 2.692 Artikel, 19 Felder, echte `0x`-Rowversions. Alte Stammdaten-Pilotdateien **NICHT** überschreiben. Artikelpreise/Nummern nur privat, nie ins öffentliche GitHub oder DocumentRoot.
- Danach zunächst **lokaler isolierter QA-/Diff-Dry-Run** gegen den bestehenden privaten Drei-Quellen-Spiegel, Suchen nach Änderungen/fehlenden IDs, Rowversion-/Join-/Quelle-Zeitstempelkontrolle. Erst nach geprüfter Betreiberfreigabe weitere private neue Spiegelkopie; weder produktiver SQL-Server-Index noch JTL-DML, keine Änderung an TEST/LIVE/API, kein 15-Minuten-Job, kein Merge in main.
- Die noch fehlenden Warenlagerzuordnungen (32 global-only vs. 12 lager-only), Amazon/eBay/Shopify-Kanalmengen und Wasserstands-/Delete-/Snapshot-Sicherheit bleiben separate Aufgaben, aber blockieren nicht das **private** QA des Artikelstammexports.

## Betreiberergebnisse korrigierter Phase-6-Artikelstammexport und privater QA-Abgleich (2026-11-23)

### S087 | Vollständige neue Phase-6-CSV mit Rowversions | **lokal überprüft**
- Vom Betreiber privat bereitgestellte Datei `Eingefügter Text(20261123-004010).txt`: `eazybusiness`, korrigiertes SQL 2 von `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql`, **2692** Artikelzeilen, genau **19** Header/Spalten pro Datensatz, **2692** unterschiedliche `kArtikel` und **2692** syntaktisch gültige, eindeutige SQL-Server-8-Byte-Rowversions in `0x...`-Hex. Datei-SHA256 `d6c2e95ba2613945a1214c00e6bb2ade424b9bc587d3811b3ca081db1ae5336e`.
- Diff gegen die **alte lokale Artikelstammtabelle** mit 2692 Datensätzen: **0 neue, 0 fehlende Artikel-IDs**; nach sicherer Normalisierung von `\\N` aus HeidiSQL zu NULL **0 Unterschiede in den 17 weiteren Artikelgeschäftsfeldern**. Die einzige Veränderung in allen 2692 Zeilen sind die ursprünglich **leeren** Artikel-Rowversions, jetzt vollständig gültig. Kein Beleg für im Zeitraum veränderte Artikelpreise, Mengen oder Metadaten; die vollständige Rowversion-Neubelegung ist **kein** Nachweis von 2692 tatsächlichen JTL-Änderungen.
- 2692/2692 `cAktiv=Y`; `nDelete` in **2690** Fällen 0 und in **2** Fällen SQL-NULL (nicht automatisch auf 0 umdeuten). Barcode leer bei **738** Artikeln (korrespondiert zu Phase-6-Quellaggregat), **31** unterschiedliche doppelt verwendete Barcodes mit **36** zusätzlichen betroffenen Artikelzeilen, Artikelnummer `cArtNr` jedoch über alle 2692 eindeutig. Keine automatische EAN-Identitätsannahme: Scanner muss bei doppelten EAN eine **Auswahl von Artikeln** erlauben und bei fehlendem Barcode manuelle Artikelnummer unterstützen.
- Lieferstatus-ID `1` in **1848** Zeilen, konkretes Label `2-4 Werktage`; ID `0` in **844** Zeilen mit dem Join-Label NULL. `jtl_unit_symbol` ist bei **727** Artikeln NULL. Artikelstamm-Feld `nLagerbestand` im konkreten Export **bei ALLEN 2692 Artikeln 0** und deshalb ungeeignet als echter aktuell verfügbarer Bestand; `dbo.tlagerbestand` bleibt die nachgewiesene zentrale Quelle. JTL-Original-Maßeinheiten und Rohmengen nicht durch unbelegte Umrechnung verändern.
- `jtl_sales_net_raw`, `jtl_amazon_price_field_raw`, `jtl_ebay_price_field_raw` sind private **Quellfelder**, keine bestätigten LIVE-Angebotspreise, keine Brutto-Netto-Berechnung und keine automatische Marktplatz-Zuordnung.

### S088 | Neue isolierte SQLite-Kopie mit gültigem Artikelstamm und alter drei-Quellen-Struktur | **lokal erstellt, getestet**
- Neue private **separate** Datei `HW24_JTL_Artikel_Lager_Zentralbestand_Rowversions_Validiert_2026-11-23.sqlite`, Größe **1232896 Byte**, Modus `0600`, SHA256 `f258e7c2c62260d4117adf4e0729087cf2b2a261e567e42534ddedf91760e79a`; originale private Drei-Quellen-Pilotdatei unverändert (SHA256 `918997009277477a77c20936edfc23e30da858a15d6bcfa0ba83b13bee0fa301`).
- `jtl_article_stage`: **2692** Stammsätze, jetzt 2692 echte Rowversions. `jtl_warehouse_stock_stage`: **12717** Zeilen unverändert; `jtl_global_stock_stage`: **2692** Zeilen unverändert; weiterhin **32** zentrale Artikel ohne Warenlagerzeile und **71** Warenlagerzeilen ohne Stammsatz sowie vollständig passende beobachtete Verfügbarkeitskontrollformel.
- Neu `article_master_refresh_manifest`: Quellen-SHA, altes Quellen-SHA, vorheriger SQLite-Hash, 2692 validierte/vervollständigte Rowversions, Null-Diff der Geschäftsattribute, Barcode-/Status-QA, `is_consistent_cross_table_snapshot=0`, `auto_sync_enabled=0`, `deployed_to_test_or_live=0`. Bestehende `stage_manifest` und `global_stock_stage_manifest` auf `article_rowversions_available=1` aktualisiert, alte Warenlager-/Zentralbestands-Hashes bleiben unangetastet.
- Neue lesende View `v_jtl_article_inventory_rowversion_verified` trennt **Artikelstamm-** und **Zentralbestand-Rowversions**, Roh-Nettoverkaufspreis, originale Verfügbarkeit, Auftragsmenge und Zuordnungsstatus. Alte Views erhalten, keine API-Änderungen.
- `PRAGMA integrity_check='ok'`, `PRAGMA foreign_key_check` ohne Befund, fachliche Kontrollen 2692 Artikel/12717 Lagerzeilen/2692 Zentralbestände/32 ohne Lager/71 ohne Stammsatz/0 Produktionsformel-Ausreißer erfolgreich. Alle alten Pilotdateien unberührt. **Kein** konsistenter gemeinsamer SQL-Server-Zeitpunkt, kein Job, keine Änderung an JTL/TEST/LIVE/APIs, kein Merge.
- Neues **privates, nicht committedes** QA-Protokoll `HW24_JTL_Phase6_Artikelstamm_Qualitaetspruefung_2026-11-23.md` (nur Einordnung und Aggregate, keine Geschäftszeilen).

### S089 | Baseline vollständig, Umsetzungsschwerpunkt: scannerfreundlicher schreibgeschützter Artikelmanager
- **Phase-6-Artikelexport ist erledigt**, Phase-9-Lager- und Phase-14-Zentralbestandspilot weiter vollständig vorhanden. Kein weiterer erneuter Voll-CSV-Export nur zum Nachweis der Rowversion nötig.
- Künftige Ansicht: `Artikelstamm`, `Lagerbestand je Warenlager`, `JTL verfügbar`, `In Aufträgen`, `Sperrwerte`, `Bewegungshistorie` und separate `Amazon/eBay/Shopify`-Kanalpreise strikt nach jeweils belegter Quelle trennen. Besonders Scan-EAN-Dubletten (31 verschiedene EAN) als Mehrfachtreffer statt stillen Ersttreffer darstellen.
- Der Zeitpunkt von Stammdaten-/Warenlager-/Zentralbestandsexport ist **nicht** transaktionskonsistent; damit ist dieser Pilot nur für lokale QA, nicht für bestätigte Live-Bestände oder laufende Delta-Synchronisation. Vor irgendeiner automatischen Integration: Löschfälle, Rowversion-Wasserstand, Rechte und idempotente Wiederaufnahme, Herkunftszeitstempel, Staleness, Snapshot- und Auftrags-/Channel-Semantik nachweisen. TEST-Integration nur nach CI/Operator-Abnahme in separatem Schritt.
- Der Draft-PR-Codezweig und bestehende Legacy-APIs/Amazon-KI-Ameise-/Scanner-Workflows bleiben unangetastet.

## Ziel nächster Produktivnutzen: 2.000 Lagerartikel über Scan-Stapel und JTL-Ameise (2026-11-23)

### S090 | Betreiberziel und realer HW24-Bestand | **in Arbeit, keine Migration**
- Betreiber möchte ca. **2.000 physisch im Lager vorhandene, noch nicht in JTL angelegte Artikel** schnell erfassen/erstellen und vorhandene artikelweise oder in Stapeln bearbeiten; gewünschtes Ziel ist **möglichst Ein-Klick-Import** über den Intelligence Hub/Artikelmanager mit JTL-Ameise.
- Bereits vorhandene und zu bewahrende Bausteine: `assets/ameise.js` erzeugt Artikel-CSV-Vorbereitung für EINEN gescannten Artikel, `assets/scanflow.js` bietet Artikel-/EAN-Zustände; Legacy `scanliste.html`/`api/scanliste.php` enthält Scan-Stapel; isolierte Filament-`ScanWorkbench` kann Legacy-Stapel lesend und unter separater Freigabe schreibend bedienen. **Kein weiterer konkurrierender Stammdaten-/Scan-Speicher**.
- Eindeutige `Artikelnummer` als JTL-Importschlüssel, niemals EAN-Alleinimport: **31 verschiedene mehrfach belegte EAN**, 738 ohne EAN laut verifizierter Quelle. Quelle: JTL-Ameise-Guide warnt vor mehrfachen Änderungen, wenn EAN in mehreren Artikeln vorkommt.
- Als harte Grenze für den Produktivimport: einmalige Ameise-Importvorlagen für **NUR NEU**, **NUR VORHANDENE AKTUALISIEREN**, **WMS-BESTÄNDE HINZUBUCHEN**, Testlauf, Backup, Fehlerrückmeldung, konkrete Freigabe. Bereitgestellte Ein-Klick-CSV-Erzeugung ist **kein tatsächlicher JTL-Import**.

### S091 | Privates offline Ameise-Starterpaket lokal erstellt und geprüft | **auslieferbar, nicht in Repo**
- Nur lokal, **nicht auf GitHub**, `HW24_Ameise_Starterpaket_2000_Artikel_2026-11-23.zip`. ZIP enthält private Referenz über 2692 JTL-Artikel (Artikelnummer, EAN, Aktivstatus, rohe Verfügbarkeit, Anzahl Warenlagerzeilen), Excel `HW24_Scan_und_Ameise_2000_Artikel.xlsx` mit fünf Tabs/2000 Eingabezeilen, dieselbe leere Scan-CSV mit 23 Spalten, drei leere Ameise-Mapping-Vorlagen, Bedienungs-README, Offline-Python3-Prüfgenerator, Windows-`HW24_1Klick_Start.cmd`.
- Der Generator erstellt aus einem nach `NEU|UPDATE` und `Stammdaten_Freigabe=JA` markierten Scanbestand **separate CSV-PRÜFDATEIEN**. Er prüft GTIN-Checksumme, Scan-Dubletten, Quell-EAN-Dubletten, eindeutige JTL-Artikelnummer (bei NEU optional `HW24-<GTIN>` als **Vorschlag**), Hersteller/Name/Steuerklasse/positiver Brutto-VK und spaltet Updates nach tatsächlich befüllten Feldern auf (keine leeren Update-Felder). Vorbereitete WMS-Importdaten werden nach WMS-Freigabe erstellt; neue Artikel **nie** in WMS zur Buchung freigegeben, bevor der JTL-Artikel tatsächlich bestätigt ist.
- Lokaler Negativ-/Positivtest: 6 synthetische Positionen ergaben **1 NEU-, 1 UPDATE-, 1 vorhandenen WMS-Prüfentwurf, 1 zurückgestellte NEU-WMS-Position und 4 blockierte** Scanpositionen. Kein SQL/Wawi/API/Marktplatzaufruf; kein produktiver Artikel erstellt. ZIP-Integrität und Excel-Blattstruktur geprüft. `EK_Netto_EUR` absichtlich **nicht** in V1-JTL-Import, bis exaktes Zielmapping belegt ist.
- Ausgangsdaten bleiben mit unterschiedlichen Quellzeitpunkten ein datierter **privater** Referenzstand, nicht Live-Truth. Bei echtem Import muss JTL-Ameise Trockenlauf gegen aktuelle Wawi prüfen, gefolgt von 5–10-Artikel-Pilot; erst nach Abnahme 50/200/2000.
- Kein Schritt innerhalb GitHub/Plesk deployed; bestehende Original-CSV, private SQLite und TEST/LIVE unverändert.

### S092 | Umsetzungspfad für die echte gesicherte Ein-Klick-Buchung im Intelligence Hub | **Spezifikation im Branch**
- `platform/docs/HW24_AMEISE_ONECLICK_BATCH_MVP_2026-11-23.md` hält Architektur, Quellen und Schnittstellen fest: vorhandene Legacy-Scan-Stapel fortverwenden -> Quelle-/Dubletten-/Staleness-Abgleich -> KI-/Lieferanten-**Vorschläge** mit Herkunft und Freigabe -> getrennte NEU/UPDATE/WMS-Prüfdateien -> JTL-Ameise-Vorlagen und offizieller Trockenlauf -> autorisierter Windows-Runner mit idempotenten Jobs und Rückmeldungen.
- Keine Implementierung eines Windows-Direktimports ohne gesonderte Betreiberfreigabe, Rechte, Passwortschutz und Backup-/Rollback-Nachweis. Keine Änderung an bestehenden Amazon-KI-/Ameise-/Legacy-APIs. URL zur JTL-Dokumentation mit offiziellen Importarten, Testlauf und CLI in Spezifikation.
- **Nächster technischer MVP:** im isolierten Featurebranch den vorhandenen `ScanWorkbench`-Freigabestatus und die bestehenden `Scanliste`-APIs für den Batch-Read/CSV-Read-only-Export mit Testfällen verbinden. Drei JTL-Importer-Presets erst nach einmalig am Windows-JTL-Rechner bestätigter Feldzuordnung und genauem Rückgabeverhalten scharf schalten.

## Fachlich beschlossene Zielarchitektur

1. **JTL eazybusiness** bleibt maßgebliche SQL-Server-Datenbank; zunächst nur Leseinventur, DB-Backup/Wiederherstellung als getrennte Aufgabe prüfen.
2. **Analytics-Spiegel** privat und zugriffsbeschränkt: Initialübernahme nach Datenklassifikation, danach 15-Minuten-Deltas (Change Tracking/CDC nur nach Eignungsprüfung) und nächtliche Vollständigkeitskontrolle. Schema-Drift, Löschungen, Idempotenz und Run-Provenienz berücksichtigen.
3. **UI**: getrennte JTL-Original- und abgeleitete Felder; Brutto-/Netto, WMS-Mengen, Status, Konten- und Marktplatzpreise niemals ohne Beleg umetikettieren.
4. **Abfrageaudit**: technisch vorgesehene Metadaten nach Auth-/Migrationstest und ausdrücklicher Aktivierung. `HW24_QUERY_AUDIT_ENABLED` bleibt standardmäßig **false**.
5. **Amazon**: bestehende Live-Angebots-API hat kein Händlerpreisarchiv. Verifizierte ASIN/Seller/Marketplace-Zuordnung, Limits, API-Rechte, Speicherung und zulässige Aufbewahrung zuerst prüfen. Nur tatsächlich beobachtete Preise datieren; keine erfundenen historischen Änderungen. Nachtjob später mit Queue und begrenzter Rate.
6. **Erhaltung Bestand**: Alte Legacy-APIs, Amazon-/KI-/Ameise-/Scanner-Workflows und bestehenden Plesk-Webroot bis zu abgenommenem Test-Cutover unangetastet lassen.

## Vor jedem weiteren Schritt

- **A** Prüfen, ob GitHub- und privater TEST-HEAD noch dem Journal entsprechen.
- **B** Prüfen, welche Schritte wirklich auf HeidiSQL/PuTTY ausgeführt wurden (Betreiberresultat), keine Vermutungen.
- **C** Ergebnisse, Abweichungen und Risiken im Journal festhalten; gegebenenfalls Versionsvergleich des Test-/Live-Codes.
- **D** Nur kleine, rollbackfähige Änderungen isoliert vorbereiten, GitHub CI + Browser-E2E prüfen und Betreiberfreigabe für Deployment einholen.

**Nächster Nachweis:** Bestehende HW24-Scan-Stapel und `assets/ameise.js` mit dem privat getesteten Ein-Klick-Datei-Prüfexport-Format fachlich abgleichen. Danach im isolierten Filament/Legacy-Test einen batchweisen schreibgeschützten Prüfexport (NEU/UPDATE/WMS-Klärfälle) samt Quellen-/Freigabe-/Dubletten-Gates mit Regressionstests vorbereiten, ohne JTL/TEST/LIVE-Deployment. Für tatsächlichen Ein-Klick-Wawi-Import zunächst die drei JTL-Ameise-Vorlagen und Windows-Runner in einem 5–10-Artikel-Trockenlauf mit Backup ausdrücklich abnehmen.
