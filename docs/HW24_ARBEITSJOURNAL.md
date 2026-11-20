# HW24 · Arbeitsjournal, Entscheidungen und Nachweise

**Eröffnet:** 20.11.2026.  
**Regel:** Alle weiteren Arbeitsschritte hier ergänzen, jeweils mit Datum, Änderung, Beleg/Commit/PR, Teststatus und nächster Aktion. **Historische Einträge werden nicht gelöscht oder umgeschrieben**, Korrekturen als neue Ergänzung protokollieren.

**Bezeichnungen**
- **BESTÄTIGT (Nutzer):** Der Auftraggeber hat den Schritt als bereits erledigt gemeldet.
- **VERIFIZIERT (GitHub/Code):** Aus den aktuellen Repo-/PR-Daten nachweisbar.
- **BESTÄTIGT (vorheriger Test):** Aus früherer Test-/Fehlerbehebungsarbeit bekannt, aber in dieser Dokumentationsrunde nicht erneut ausgeführt.
- **ENTWICKLUNG:** Code vorhanden und/oder CI geprüft, aber noch kein Live- oder DB-Nachweis.
- **OFFEN/UNKLAR:** Nicht erneut durch bloße Vermutung als fehlend oder erledigt kennzeichnen.

## A. Historischer Arbeitsstand und Erhaltungsanforderungen

| Datum / Phase | Vorgang | Nachweis / Status |
|---|---|---|
| Bis 14.11.2026 | JTL-Originalexport/Feldkatalog für Ameise dokumentiert; historische Artikel-/Quellenanbindung entwickelt | `docs/JTL_AMEISE_IMPORTPROFIL.md`, `docs/JTL_FELDKATALOG_STANDARDS_20261114.md`, `docs/PROJEKTSTAND_20261114.md` auf dem älteren `main`; **bestehende Erkenntnisse beibehalten** |
| 19./20.11.2026 | JTL-/WMS-API repariert, Sicherung, Wiederherstellung, SHA-256-Vergleich und Syntaxprüfung | **BESTÄTIGT (vorheriger Test)**; nicht einfach erneut überschreiben |
| 19./20.11.2026 | WMS-Daten und Lagerplatzhistorie abrufbar; Testartikel EAN `4016660006753`: 2 verfügbar, 0 Aufträge, 0 Zulauf, Platz 09-011 / 2 Stück | **BESTÄTIGT (vorheriger Test)**; nur historische Momentaufnahme, nicht als heutiger Lagerbestand ausgeben |
| 20.11.2026, früh | V4.22 TEST als Staging-Basis; bestehende Amazon-Katalog-API funktionsfähig, Berliner Datumsausgabe korrigiert | **BESTÄTIGT (vorheriger Test)**; Staging-Kommitt später bestätigt |
| 20.11.2026 | GitHub-Branch `deploy/staging` (SHA `dca30b015f062955c29306d2a5e6fa5265d0f45b`) beinhaltet V4.22-TEST-Webcode | **VERIFIZIERT (GitHub)** |
| 20.11.2026 | Plesk-Testdomain nutzt Repo `HW24-scanner.git`, `deploy/staging`, manuelles Pull/Deploy | **BESTÄTIGT (Screenshot)** |
| 20.11.2026 | Komplettes Plesk-Backup und MariaDB-Backup bereits erstellt | **BESTÄTIGT (Nutzer)**; kein erneuter Backup-Auftrag. Exakte Dateien hier nicht erneut geprüft |
| 20.11.2026 | Verzeichnisse, private Ablage und Umfang der GitHub-Veröffentlichung bereits geprüft | **BESTÄTIGT (Nutzer)** |
| 20.11.2026 | Website-Staging-Baum enthält ausschließlich `api/`, `assets/`, `index.html` | **VERIFIZIERT (GitHub)**; Geheimnisse, `docs/`, `sql/`, `sync/` nicht in diesem Tree |
| 20.11.2026 | PR #14 (V4.35) als abweichender Stand erkannt; PR #15 basierte darauf und wurde geschlossen | **VERIFIZIERT (GitHub)**; nicht zur V4.22-Basis machen |
| 20.11.2026 | PR #16: V4.22-Scanliste, automatisierter Amazon-Katalog- und Keyword-Abgleich, getrennte SQL-Migrationen vorbereitet | **ENTWICKLUNG**; Code-/CI-Prüfungen erfolgreich; keine Serverintegration bestätigt |
| 20.11.2026 | PR #17: bereinigte Freigabe ausschließlich mit fünf Site-Dateien, Basis `deploy/staging` | **VERIFIZIERT (GitHub)**, offener Draft, nicht gemerged, nicht deployed |
| 20.11.2026 | Release-Kandidat `release/hw24-v422-scan-test-20261120` enthält `index.html`, `scanliste.html`, `assets/`, `api/` und keine internen Repo-Verzeichnisse | **VERIFIZIERT (GitHub)** |
| 20.11.2026 | Auftraggeber verlangt lückenlose, fortlaufende Dokumentation statt wiederholter Einrichtung | **VERBINDLICHE ARBEITSREGEL**; Dokumentationsbasis auf `main` erstellt |

## B. Entscheidungen mit Begründung

**E-001 · Versionsdisziplin.** `deploy/staging` mit V4.22 TEST ist maßgeblich; der Defaultbranch `main` ist älter. Ein PR mit einer größeren Versionsnummer ist nicht automatisch richtiger. **Keine Neuimplementierung auf falscher Codebasis.**

**E-002 · Freigaben trennen.** Entwicklungs-PR #16 enthält Code, SQL und Dokumentation. Website-PR #17 enthält nur Webdateien. **Nicht PR #16 als kompletten öffentlichen Plesk-Baum bereitstellen.**

**E-003 · Backup-Bestand berücksichtigen.** Vollständige Plesk- und MariaDB-Backups sowie Verzeichnisprüfung sind **laut Auftraggeber abgeschlossen**. Folgende Arbeit darf nicht routinemäßig nochmals bei Schritt 1 beginnen. Ein neuer Sicherungsbedarf darf nur bei **konkreten nachfolgenden Änderungen mit neuer Risikolage** begründet werden, nicht pauschal als Wiederholung.

**E-004 · Scanner-Arbeitsablauf priorisieren.** Artikel wird beim Scan sofort in HW24 gespeichert; Datenanreicherung und Stammdatenpflege danach gesammelt; JTL-WMS-Buchung ist ein gesonderter Prozess.

**E-005 · SP-API verpflichtend, aber sparsam.** Automatische Katalogprüfung ist Standard nach eindeutigem Scan bzw. abgeschlossener Suchaktion. Bereits vorhandene Katalog-Snapshots nutzen; aktuelle Preis-/Buy-Box-Abfragen sind getrennte, kürzer zu aktualisierende Quellen. Keine ungedrosselten Massen-Calls.

**E-006 · Sichere Quellenhierarchie.** Frischer aktueller JTL-Spiegel und freigegebene eigene Stammdaten haben Vorrang; historische Excel-Werte und Amazon-Produktangaben sind Vorschläge. ASIN, Farbnuance, Packungsgröße, Preise, Steuern und Marken nicht aus Ähnlichkeit erzwingen.

**E-007 · Einkaufschancen.** Günstige Amazon-Angebote später rechnerisch mit realen Netto-/Brutto-Kosten, Amazon-Gebühren, Marktpreisen und Rechten bewerten. Kein automatischer Wareneinkauf oder Listing.

**E-008 · Fehlersuche nur am tatsächlichen Defekt.** Bei einem neuen Fehler den exakten API-Endpunkt, Zeitpunkt, Antwortstatus, Branch-/Commitstand und die zuletzt funktionierende Version sichern; andere funktionierende Module unangetastet lassen.

**E-009 · Kein „alles noch mal“.** Der Arbeitsstand wird vor dem nächsten GitHub-/Plesk-Auftrag gelesen. Ein erneuter allgemeiner Setup-, Backup- oder Ordnercheck ist ohne begründeten Anlass untersagt.

## C. Aktuelle offene Arbeiten / Eingrenzung

| ID | Task | Status | Voraussetzung / Blocker | Ergebnisnachweis |
|---|---|---|---|---|
| O-01 | Neue Scanlisten-Tabellen (SQL V11) und Amazon-Keyword-Cache (SQL V12) im **bereits gesicherten** Test-MariaDB-Stand abgleichen | **OFFEN: Ausführung nicht belegt** | Kurz prüfen, ob neu erforderliche Tabellen bereits vorhanden sind; keine Backup-Wiederholung | DB-Tabellennamen, Migrationsversion und Ergebnis ohne Secrets |
| O-02 | Privaten Scanlist-DB-Connector und Rechte für die **neuen Endpunkte** abnehmen | **OFFEN: Bereitstellung nicht belegt** | Vorhandene geprüfte Verzeichnisstruktur nicht erneut grundsätzlich prüfen | HTTP-Status und erlaubte Operationen ohne Passwort |
| O-03 | **PR #17** als V4.22 Website-Testfreigabe durchsehen und kontrolliert deployen | **OFFEN: Draft, kein Merge, kein Deploy** | O-01, O-02 und Freigabe | Merge-SHA, Plesk-Pull/Deploy-Zeitpunkt, Versionsanzeige |
| O-04 | Scanlisten-Funktionstest / Amazon-Cache, Stichwortsuche, SP-API-Fehler und API-Limits | **OFFEN: keine echten Server-End-to-End Tests** | O-03 | Testfälle inkl. Screenshot/Status, Pass/Fail, Fehler-SHA |
| O-05 | Ameise-Massenimport mit echten Importfeldnamen, Sicherung von Änderungsdaten und Testartikeln | **OFFEN: Testlauf nicht belegt** | O-04 | Gültige Importvorlage und Testlaufprotokoll |
| O-06 | Globales SP-API-Budget, Nachtverarbeitung, Kalkulation von Einkaufschancen | **KONZEPT / NOCH NICHT BETRIEBSBEREIT** | O-04/O-05, Berechtigungen/Quoten | Separater freigegebener Entwicklungs-PR |
| O-07 | Nach vollständig bestandenem Test abgenommene Version auf Live übertragen | **NICHT BEGONNEN** | O-04/O-05 erfolgreich, explizite Live-Freigabe | Eigener Release und Live-Abnahme |

## D. GitHub-Links und Belegstellen

- Staging V4.22: https://github.com/NicoVoss1/HW24-scanner/tree/deploy/staging
- V4.22 Entwicklungs-PR #16: https://github.com/NicoVoss1/HW24-scanner/pull/16
- Nur-Webdateien-PR #17: https://github.com/NicoVoss1/HW24-scanner/pull/17
- Alten V4.35-PR #14 nicht als Basis wählen: https://github.com/NicoVoss1/HW24-scanner/pull/14
- Geschlossener falscher Basis-PR #15: https://github.com/NicoVoss1/HW24-scanner/pull/15
- Codeprüfungen V4.22-Entwicklungsbranch: https://github.com/NicoVoss1/HW24-scanner/actions
- Backup-Branch als Code-Rückfallreferenz: `backup/staging-before-pr4-20261120`

## E. Fortlaufendes Eintragsschema

### [Datum + Uhrzeit] [Komponente] [Status: erledigt/offen/fehlerhaft]
- **Ausgangspunkt:** GitHub-Basis-SHA + Plesk-Stand.
- **Aufgabe:** Was war fachlich zu erreichen?
- **Durchgeführt:** Exakte Datei-/API-/DB-Änderung, ohne Zugangsdaten.
- **Nachweis:** PR / Commit / Test / Nutzerbestätigung / Screenshot.
- **Ergebnis:** Bestanden, blockiert oder unklar; nichts verschweigen.
- **Nicht angefasst:** Welche Live-/Wawi-/DB-Funktionen unverändert bleiben.
- **Nächster EINZIGER konkreter Schritt:** Keine bereits erledigten Tätigkeiten wiederholen.

---

### 20.11.2026 · Dokumentations-Konsolidierung · ERLEDIGT
- **Ausgangspunkt:** V4.22 TEST bei `deploy/staging`, PR #16/#17 vorbereitet, frühere PR #14/#15 nicht maßgeblich.
- **Aufgabe:** Wiederholte Backups/Ordnerprüfungen vermeiden, aktuelle Architektur und belegte Ergebnisse dauerhaft in GitHub dokumentieren.
- **Durchgeführt:** `docs/HW24_PROJEKTSTAND_AKTUELL.md`, dieses Journal und README-Verlinkung als verbindliche Einstiegspunkte auf dem **Dokumentationsbranch `main`**.
- **Nachweis:** GitHub-Commits der Dokumentationsdateien; weitere Änderungen im Chatprotokoll referenzieren.
- **Ergebnis:** Dokumentationskonsolidierung; keine Codesynchronisation zwischen `main` und V4.22-Staging ausgelöst.
- **Nicht angefasst:** Plesk-Test-/Live-Dateien, JTL-Wawi-/WMS-Bestand, bestehende MariaDB-Daten und Backup-Dateien.
- **Nächster Schritt:** **O-01**: nur den Status **der neu hinzugefügten Scanlisten-/Keyword-Cache-Tabellen** feststellen; bereits bestätigte Backup-/Pfadprüfungen nicht wiederholen.


### 20.11.2026 · Verbindliche GitHub-Dokumentation und PR-Prozess · ERLEDIGT
- **Projektstatus erstellt:** `docs/HW24_PROJEKTSTAND_AKTUELL.md`, Commit `fb70fae26de60132851c283be85f42324e05b918`.
- **Arbeitsjournal erstellt:** `docs/HW24_ARBEITSJOURNAL.md`, Erst-Commit `99975588741718948b2869baa4f39c05a5bd7b04`.
- **README als Einstieg aktualisiert:** Commit `ccaca0d242a9bf6cde0be7e3c4ce307ca94932d1`. `main` bleibt dabei ausdrücklich nur Dokumentationseinstieg und nicht die freigegebene Webversion.
- **GitHub-Pull-Request-Vorlage angelegt:** `.github/PULL_REQUEST_TEMPLATE.md`, Commit `7d68b1a8435b3eb829f79cb0e7ea55766f1d0dbc`. Version, Tests, Rückfallplan, Dokumentation und nächste Aktion werden für künftige PRs abgefragt.
- **Bestehende Arbeitszweige verknüpft:** Verbindliche Dokumentation in den PRs #16 und #17 als GitHub-Kommentar hinterlegt, damit beim Fortsetzen kein Rücksprung zu Initialschritten geschieht.
- **Nicht geändert:** `deploy/staging`, Plesk-Deployment, produktive JTL-/WMS-Bestände, MariaDB-Tabellen und bestehende Backups.
- **Nächster konkreter Arbeitspunkt bleibt:** O-01, **nur Status der neu erforderlichen V11/V12-Tabellen** und der zugehörigen API-Berechtigungen; nicht erneut allgemeines Backup/Ordner-/GitHub-Setup.


### 20.11.2026 · V4.22-Testrelease auf GitHub freigegeben · ERLEDIGT (GitHub), PLESK NOCH OFFEN
- **Ausgangspunkt:** Bestätigter GitHub-Staging-Commit `dca30b015f062955c29306d2a5e6fa5265d0f45b`, V4.22 TEST; Plesk pullt `deploy/staging` manuell.
- **GitHub-Maßnahme:** PR [#17](https://github.com/NicoVoss1/HW24-scanner/pull/17) nach Review-Statuswechsel gemerged. **Neuer Staging-SHA `c0da9033a8373d7e6d210f075a7a4210a9670bc6`**.
- **Deployment-Schutz verifiziert:** Nur `api/`, `assets/`, `index.html`, `scanliste.html` im GitHub-Stagingbaum. Keine SQL-/Sync-/Dokumentations-/Private-Dateien.
- **Exakte nächste Nutzeraktion:** Plesk `deploy/staging` unverändert lassen → „Jetzt Pull ausführen“ → „Jetzt bereitstellen“ → `https://test-artikelmanager.hairworld24.de/scanliste.html` öffnen und Ergebnis berichten.
- **Nicht automatisch als erledigt behandeln:** GitHub-Merge ist **kein** Plesk-Pull/Deployment. V11/V12-Migration, Scanlist-DB-Config und Server-End-to-End-Test sind weiter nicht bestätigt. Bei erstem Laden sind DB-bezogene Fehler möglich.
- **Github Private geprüft:** GitHub Free erlaubt private Repositories; Plesk-Zugang benötigt nach Sichtbarkeitswechsel SSH-Schlüssel bzw. aktualisierte Git-Verbindung. Noch **keine** Änderung der Sichtbarkeit.
- **Nicht angefasst:** Live-Seite, JTL-Wawi/WMS, MariaDB-Daten, vorliegende Backups.
