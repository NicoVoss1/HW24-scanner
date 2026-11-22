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

### S014 | Sicherheitsbewertung und Folgearbeit | **Code vorbereitet**
- Kein weiterer Fast-Forward nötig. Bestehende `5eae5be...`-Installation unangetastet lassen.
- Neuer ausschließlich lesender Phase-2-Abfragesatz `platform/ops/jtl-heidisql-phase2-readonly.sql` im isolierten GitHub-PR #46 angelegt. Er fragt DB-Dateigrößen inkl. `FILEPROPERTY`, CT-Status samt Anzahl getrackter Tabellen, Primary-Key-Metadaten relevanter Tabellen sowie Artikel-/Bestands-/Offer-Spaltenstruktur ab.
- **Ausführung auf HeidiSQL und PuTTY noch offen; Ergebnis nicht voraussetzen.** Besonders SQL-B-Größe und Change-Tracking-Status sind als Nächstes zwingend. Keine JTL-Tabellenfreigaben, SQL-Rechte, CDC, CT oder Snapshots aktivieren.
- PR #46 ist weiterhin **Draft** und noch nicht auf TEST/LIVE installiert. Offen: Entwurfsprüfung, bestätigter Browser-E2E-Test, Abnahme der 15-Minuten-Deltastrategie, Schutz sensibler Quellen.

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

**Nächster Nachweis:** Betreiber-Ausgabe von Phase-2-HeidiSQL: Dateigrößen, Change-Tracking-Status, Quellschlüssel und Spalten-Metadaten. Keine produktive Replikation oder Server-Änderung.
