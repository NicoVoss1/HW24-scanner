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


### 20.11.2026 · Scanliste screenshot, UI-Reparatur und Testfreigabe #18 · CODE ERLEDIGT, PLESK/DB OFFEN
- **Ausgangspunkt:** GitHub `deploy/staging` @ `c0da9033a8373d7e6d210f075a7a4210a9670bc6`, V4.22 TEST. Nutzer-Screenshots belegen: Hauptseite und neue Scanliste sichtbar, Statusleiste kontrastarm, Favicon fehlt, Scanliste meldet **„MariaDB noch nicht konfiguriert“**.
- **Quellen:** 872 dokumentierte JTL-Ameise-Originalspalten, `main/docs/JTL_FELDKATALOG_STANDARDS_20261114.md`, `main/docs/JTL_AMEISE_IMPORTPROFIL.md`, `assets/reiter.js` (13 JTL-Reiter) sowie offizieller JTL-Guide. Die zuvor hochgeladenen Original-Screenshots der Wawi-Eingabemaske ließen sich über den verfügbaren Dateiindex nicht zuverlässig wiederfinden. Kein behaupteter Fotoabgleich.
- **Code umgesetzt:** `assets/hw24-favicon.svg`, `assets/hw24-ui-polish.css`, `api/scanliste-status.php`, Verbesserungen in `index.html`, `scanliste.html` und `api/scanliste.php`. Statusleiste kontraststärker, Favicon, fünf strukturierte Formularbereiche mit 41 überprüfbaren JTL-Feldern, Pflichtfeldzähler, gezielte **lesende** Scanlist-Konfig-/Schema-Diagnose.
- **Sicherheitsregeln erhalten:** V4.22, kein Live-/JTL-/WMS-Schreiben, keine ungeprüfte Neuanlage, Überverkäufe standardmäßig N, keine automatische Preis-/Bestandsänderung.
- **Statische Tests:** GitHub Actions [#44786470819](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44786470819) grün (PHP, JS, SVG, Feld-Schnittstellen); 41 Formularfelder, keine Duplikate, alle serverseitig zugelassen.
- **Release:** [PR #18](https://github.com/NicoVoss1/HW24-scanner/pull/18) in `deploy/staging` gemerged, **neuer Staging-Commit `bcb8a9664f08aeb66de5aa7efd461f284affae99`**. GitHub Root geprüft: nur `api/`, `assets/`, `index.html`, `scanliste.html`. Keine SQL-/Private-/Sync-/Dokumentationsverzeichnisse öffentlich.
- **Noch nicht bestätigt:** Plesk-Pull und erneute Bereitstellung **dieses** neuen Commits, serverseitige V11/V12-Tabellen, Scanlisten-Schreibbenutzer, Scanner-E2E-Tests, JTL-Ameise-Importprofil.
- **Vorher erledigte Maßnahmen:** Plesk- und MariaDB-Backups sowie allgemeine Verzeichniskontrollen bleiben **abgeschlossen**. Kein erneuter Komplettcheck.
- **Genau nächster Schritt:** Plesk Branch `deploy/staging` unverändert lassen → **Jetzt Pull ausführen** → **Jetzt bereitstellen** → `/scanliste.html` öffnen → neu angezeigte DB-**Diagnose** mitteilen. Nur das dort tatsächlich fehlende neue Modul nachrüsten.
- **Dokument:** `docs/HW24_UI_JTL_FELDMASKE_20261120.md`.


### 20.11.2026 22:15 MEZ · DB-Fehler isoliert und PuTTY-Installer vorbereitet · CODE ERLEDIGT, DB OFFEN
- **Eingang:** Aktueller Nutzer-Screenshot der Testseite `/scanliste.html`; EAN `8033219164159`, neuer Stapelname `04-001` im Formular, aber noch kein erfolgreicher Scan. Diagnose zeigt: V11-Scanlisten-Tabellen und V12-Amazon-Cache fehlen/unvollständig, neue private `scanlist-db.php` fehlt; bestehender HW24-Datenbankzugang ist nicht grundsätzlich defekt.
- **Quellprüfung:** `sql/HW24_11_SCANLISTE.sql` (vier Tabellen) und `sql/HW24_12_AMAZON_KEYWORD_CACHE.sql` (eine Tabelle) im vorhandenen V4.22-Entwicklungszweig ausgelesen, `api/scanliste.php`, `api/scanliste-status.php` und `api/amazon-keywords.php` im aktiven Staging geprüft. Kein neuer Backupauftrag.
- **Codekorrektur:** Amazon-Stichwortcache arbeitet zukünftig mit dedizierter Schreibkonfiguration `hw24_private/scanlist-db.php`, ohne die bestehende schreibgeschützte `db.php` zu verändern. [PR #19](https://github.com/NicoVoss1/HW24-scanner/pull/19) gemerged; `deploy/staging` SHA **`2175c375fdce4afe2b335dafb43167505f7123a0`**. Neues Plesk-Pull/Deploy noch nicht bestätigt.
- **PuTTY:** `ops/HW24_INSTALL_SCANLIST_TEST.sh` (GitHub Commit **`a3f0d8592a926fb348b589a76b2170cee1ab67c3`**) auf dem Entwicklerbranch vorbereitet. Erzeugt fünf Tabellen, eingeschränkten Scanlisten-MariaDB-Benutzer und privates Passwort ausschließlich auf dem Server, sofern Root-/Plesk-DB-Zugriff, Testpfad und die bestehende `db.php` erfolgreich geprüft werden. Abbruch bei Unklarheit.
- **Tests:** Erster GitHub-Actions-Check Shell/PHP [#44819368745](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44819368745) erfolgreich; nach verbesserter Filterung auf den eindeutigen Plesk-Testpfad erneuter [Run #44819742904](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44819742904) angestoßen. **Keine Ausführung des Installers auf dem echten Plesk-Server und keine realen SQL-/PHP-Integrationstests behaupten.**
- **Dokumentation:** `docs/HW24_PUTTY_SCANLIST_SETUP_20261120.md` enthält die gepinnten PuTTY-Befehle, Bestätigungs- und Testschritte.
- **Nicht angefasst:** Vorhandene Plesk-/DB-Backups, ursprüngliche JTL-/WMS-Datenbank, produktive Live-Seite, SP-API-Token, alte `db.php`.
- **Nächster Schritt:** Plesk `deploy/staging` neu pullen und bereitstellen, dann den Einmal-Installer via PuTTY durch den autorisierten Nutzer starten und den Scan-Stapeltest durchführen. Nur das Ergebnis bzw. Fehlermeldung ohne Secrets berichten.


### 20.11.2026 · PuTTY-Erstversuch: Skriptdatei fehlt · FEHLER DOKUMENTIERT, KEINE DB-ÄNDERUNG BESTÄTIGT
- **Quelle:** Vom Auftraggeber übermitteltes PuTTY-Terminalprotokoll, Sitzung als `root@admiring-elion`.
- **Beobachtung:** Erneut `sudo -i` trotz aktiver Root-Shell aufgerufen. Die anschließenden Download-/Prüfbefehle wurden im Protokoll nicht ausgeführt bzw. erzeugten keine sichtbaren Ergebnisse; `bash /root/HW24_INSTALL_SCANLIST_TEST.sh` meldet `No such file or directory`.
- **Schlussfolgerung:** Das Skript ist auf dem Server nicht am angegebenen Pfad vorhanden; **die SQL-Installation wurde dadurch nicht gestartet**. Kein Beleg für einen Datenbank- oder Plesk-Defekt.
- **Korrektur:** Ohne erneutes `sudo -i` die gepinnte Skriptdatei mit `curl -fL` direkt nach `/root/HW24_INSTALL_SCANLIST_TEST.sh` herunterladen; mit `ls -lh` und `bash -n ... && echo 'Syntax OK'` prüfen; danach erst den Dateiinhalten und Ausführung widmen.
- **Noch offen:** Sichtbares Download- und Syntaxprüfergebnis, sichere manuelle Freigabe, serverseitige V11-/V12-Migration, Rechte-/Loginprüfung, funktionierender Testscan.
- **Nicht angefasst:** GitHub-Stagingcode, vorhandene Backups, Datenbanken, Live-Wawi, SP-API-Zugangsdaten.


### 20.11.2026 · PuTTY-Download und Bash-Syntax bestätigt · ERLEDIGT (Installationsausführung weiter OFFEN)
- **Nutzerprotokoll:** `curl -fL` des gepinnten Installers `a3f0d8592a926fb348b589a76b2170cee1ab67c3` nach `/root/HW24_INSTALL_SCANLIST_TEST.sh` meldet 8.280 empfangene Bytes.
- **Dateiprüfung:** `ls -lh /root/HW24_INSTALL_SCANLIST_TEST.sh` meldet 8,1K und Eigentümer root.
- **Syntaxprüfung:** `bash -n /root/HW24_INSTALL_SCANLIST_TEST.sh && echo "Syntax OK"` liefert `Syntax OK`.
- **Noch nicht ausgeführt:** Der Installationstest `bash /root/HW24_INSTALL_SCANLIST_TEST.sh` ist im neuesten Protokoll **nicht** gestartet; daher keine DB-/Benutzeranlage behaupten.
- **Genau nächster Schritt:** Datei mit `less /root/HW24_INSTALL_SCANLIST_TEST.sh` ansehen, mit `q` beenden, Installer einmal mit `bash /root/HW24_INSTALL_SCANLIST_TEST.sh` starten. Nur bei eindeutigem Testpfad `TEST` bestätigen. Bei Fehler stoppen, Text ohne Geheimnisse melden.
- **Nicht berührt:** Bisherige Backups, JTL/WMS, Live-Seite, bestehende SP-API-Konfiguration.


### 20.11.2026 · PuTTY-Installation V11/V12 auf Testserver · ERFOLGREICH (Nutzerprotokoll)
- **Quelle:** Vollständige PuTTY-Konsolenausgabe des Auftraggebers als `root@admiring-elion`.
- **Prüfungen vorher:** Lokales Installationsskript vorhanden (`/root/HW24_INSTALL_SCANLIST_TEST.sh`, 8,1 KB), `bash -n` mit `Syntax OK`, Datei mit `less` eingesehen.
- **Ausführung:** `bash /root/HW24_INSTALL_SCANLIST_TEST.sh`; bestätigt wurde eindeutig der Testserver-Endpunkt `/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de/api/scanliste.php`, privater Ordner `/var/www/vhosts/hairworld24.de/hw24_private`, Ziel-DB `hw24_artikelmanager`. Manuelle Bestätigung `TEST`.
- **Ergebnis laut Skript:** `OK: Nur neue Scanlisten-/Keyword-Tabellen und privater Minimalbenutzer eingerichtet.` Damit wurde die Anlage der vier V11-Tabellen und einer V12-Tabelle sowie die Erstellung des separaten Datenbankzugangs und von `scanlist-db.php` erfolgreich gemeldet. **Kein Datenbank-Dump oder Secret im Repository.**
- **Bestehende Systeme:** Das Skript verändert keine bestehenden JTL-/WMS-Artikel und keine Live-Seite. Die bereits abgeschlossenen Backups bleiben anerkannt.
- **Noch NICHT bestätigt:** Erfolgreicher browserseitiger Zugriff, Scan-Stapel erstellen, EAN speichern und nach Reload wiederfinden, Amazon-SP-API-/Stichwortcache-Funktion sowie Ameise-Export.
- **Genau nächster Schritt:** `https://test-artikelmanager.hairworld24.de/scanliste.html` neu laden, Stapel `04-001` anlegen, EAN `8033219164159` erfassen, Seite erneut laden und Persistenz der Zeile prüfen. Bei Fehler nur Fehlermeldung/HTTP-Status ohne Zugangsdaten senden. **Installer NICHT erneut ausführen.**


### 20.11.2026 · Scanlisten-Stammdaten auf V4.22 vereinheitlicht · GITHUB ERLEDIGT, PLESK-TEST OFFEN
- **Ausgangsbefund aus 5 Nutzer-Screenshots:** Stapel `04-001`, dauerhaft gespeicherte GTIN `8033219164159`, JTL-Artikel `Inebrya Color Pastel Roso Intense Pink 100 ml` bereits vorhanden. Bei bestehenden Artikeln zeigt die alte Scanliste zwar Name/GTIN, nicht aber alle verfügbaren JTL-Stammdaten; Amazon-Status 0/0 irreführend, expliziter EAN-Löschbutton fehlt. Alle fünf Gruppen der JTL-Maske sichtbar.
- **Basis:** GitHub `deploy/staging` `2175c375fdce4afe2b335dafb43167505f7123a0` vor Änderung. Bestehende erfolgreiche PuTTY-/MariaDB-V11/V12-Installation, vorherige Backups und Verzeichnisprüfungen **bleiben erledigt**.
- **Codeänderung:** Featurebranch `feature/hw24-v422-shared-enrichment-20261120`: zentrale JTL-/curated-/historische Excel-/Amazon-Datenquelle; Standardisierung von Artikeltyp, Farbe, Hersteller, Texten/SEO, Steuer/Währung, Größen/geschätzten Gewichten und Beschaffungsstandard. Bewusst keine nicht belegten Preis-/Lieferanten-/Warengruppen-ID-Bindungen. Bei Mehrfach-ASIN (auch Set) manuelle Packungswahl. Bestehende JTL-Artikel nur als vorhandene Stammdatensicht.
- **Scan-Arbeitsablauf:** EAN-Feld mit ✕; automatisch deduplizierte Amazon-Abfrage, begrenzter Client-Cache; resumable Hintergrundwarteschlange mit Status; neue Artikelentwürfe werden unabhängig von der aktuellen Auswahl nach JTL-Identifikation und Amazon-/Historien-Abgleich revisionsgesichert in der bestehenden HW24-MariaDB gespeichert, sobald die Seite geöffnet ist. Keine Wawi-/WMS-Lagerbuchung.
- **Haupt-Artikelmanager/Lagerplatzübersicht:** gemeinsame Browser-Regeln ergänzen die bereits vorhandenen JTL-/Amazon-Module ohne bestehende Funktionen zu ersetzen. Keine stillschweigende neue Artikelanlage vorhandener EAN.
- **Sicherheit & Test:** [GitHub Actions #44826801120](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44826801120) **erfolgreich** (JavaScript/PHP-Syntax, Inline-HTML-Assets, Regeltests inkl. 60/100 ml, multipacks, Konflikt-Historie). Echte API-, MariaDB- und Browser-/Scanner-Tests der neuen Module **stehen weiterhin aus**.
- **Freigabe in GitHub:** Website-only [PR #20](https://github.com/NicoVoss1/HW24-scanner/pull/20) erfolgreich gemerged, neuer `deploy/staging`-SHA **`dc9646d4863772dab823283be5d8f4bbec8f3c99`**. GitHub-Root enthält nur `api/`, `assets/`, `index.html`, `scanliste.html`; keine privaten, SQL-, Dokumentations- oder Sync-Dateien in Plesk-Deploybaum.
- **Dokumentation:** `docs/HW24_AUTO_VORBELEGUNG_V422_20261120.md` mit allen Regeln, Quellenvorrang und Abnahmefällen.
- **Noch offen:** Neuer Plesk-Pull/Deploy, tatsächlich gespeicherter automatischer Neuanlagen-Entwurf nach Reload, reale Mehrfach-ASIN-Auswahl, JTL-Ameise-Importmapping und spätere serverübergreifende Rate-Limit-/Nachtjoblösung.
- **Genau nächster Schritt:** Plesk `deploy/staging` → „Jetzt Pull ausführen“ → „Jetzt bereitstellen“ → Stapel `04-001`, EAN `8033219164159` erneut öffnen und **vorbelegte Ist-Daten** prüfen; anschließend eine unbekannte EAN scannen, Autofill-Queue und Persistenz testen. Bei Fehler nur konkrete Meldung, keine Wiederholung von Backups/SQL.


### 20.11.2026 · ASIN-Auswahl vervollständigt, Website-Versionststand aktualisiert · GITHUB ERLEDIGT, SERVERTEST OFFEN
- **Basis:** PR #20 mit gemeinsamer JTL/Amazon/Excel-Vorbelegung bereits auf `deploy/staging` gemerged, SHA `dc9646d4863772dab823283be5d8f4bbec8f3c99`.
- **Ergänzung:** Der Benutzer möchte bei bewusst ausgewählter Amazon-ASIN auch den zugehörigen Titel und Hersteller erhalten. `assets/hw24-scan-autofill.js` und `assets/hw24-main-enrichment.js` übernehmen nun **nur die zuvor leeren Felder** aus dem gewählten ASIN-Treffer. Bestehende JTL-/manuell bearbeitete Artikelwerte bleiben erhalten; Mehrfachpackungen erfordern explizite Entscheidung.
- **GitHub-PR:** [#21](https://github.com/NicoVoss1/HW24-scanner/pull/21) gemerged, aktueller `deploy/staging` SHA **`2f88d5a5dca6a8df80acbc38aba5287d960583c1`**; Website-Dateibaum weiterhin nur `api/`, `assets/`, `index.html`, `scanliste.html`.
- **Syntax/Regeltests:** [GitHub Actions #44827355974](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44827355974) grün. Keine Behauptung eines realen Plesk-/SP-API-/MariaDB-End-to-End-Tests dieser neuen Version.
- **Dokumentation:** `docs/HW24_AUTO_VORBELEGUNG_V422_20261120.md` und `docs/HW24_PROJEKTSTAND_AKTUELL.md` fortgeschrieben.
- **Unverändert:** Produktiv-/Live-Wawi, WMS, Sicherungen, SQL, frühere Plesk-Pfadprüfung, private Datenbankkonfiguration.
- **Nächster Schritt:** In Plesk Testbranch `deploy/staging` Pull → Bereitstellen; bekannte EAN `8033219164159` auf Vorbelegung/Bestandsschutz prüfen, danach eine unbekannte EAN und Mehrfach-ASIN-Fall inklusive Reload testen.


### 20.11.2026 · PR #22 Test-Code für Scan-Korrektur, CSV-Bulkimport und JTL-SKU fertig · PLESK-TEST OFFEN
- **Anlass:** Nutzer möchte doppelte Scans entfernen, Positionen aus Scanliste löschen, ca. 1.500 EANs mit Lagerplatz/Information/Kommentar importieren und anschließend auf neue JTL-Artikel prüfen. Außerdem fragt er nach automatischem JTL-Nummernkreis, fehlerhafter Dusicolor/Hair-Styling-Warengruppe, Netto-/Bruttopreisen, `ml`, JTL-Lieferstatus/Zeitfeldern und dem bisher unklaren Button „Vorlage Haarfarben einsetzen“.
- **Quellenprüfung:** Aktueller Website-Branch `deploy/staging` SHA `2f88d5a5dca6a8df80acbc38aba5287d960583c1`, `api/scanliste.php`, `scanliste.html`, `assets/hw24-enrichment.js`, `assets/hw24-scan-autofill.js`, `assets/jtl-profil.js`, `api/jtl.php` geprüft; JTL-Dokumentation zur globalen Artikelnummernvergabe recherchiert. **Die reale Nummernkreis-Tabelle und die konkrete Dusicolor-Warengruppen-Quellzeile standen in dieser Sitzung nicht über SQL zur Verfügung.**
- **Implementierung:** `api/scanliste.php` bietet jetzt `adjust` (archiviert/vermindert mit Revision/Audit) und `import_rows` (transaktionale idempotente Blöcke). Bereits vorhandene EANs zählen nicht als weitere Scans; Import-Lagerplatz/Information/Kommentar werden unter `source_json.csv_meta` gespeichert, NICHT als Wawi-Anmerkung und nicht als WMS-Lagerbuchung.
- **UI:** `scanliste.html` hat „−1“, „Entfernen“, Filter `Entfernte Positionen`, CSV-Dateiauswahl/Vorschau, Kodierung, Fortschrittsanzeige, wiederaufnehmbare API-Batches und Report-Metadaten. Button umbenannt in „Fehlende Haarfarben-Standards ergänzen“, vorhandene Werte bleiben unangetastet.
- **JTL-Import:** Neue Artikelnummer wird nicht aus EAN oder „letzter Nummer +1“ erfunden. Bei möglicher Neuanlage Artikelnummer zunächst leer und Ameise **globale fortlaufende Nummernvergabe** im Importprofil verwenden; getrennte UI-Aktion „Artikelnummer von JTL vergeben lassen“ löscht alte vorbelegte SKU-Vorschläge. Erst nach echtem Ameise-Test-/Echtimport kann die vergebene SKU in einem frischen Spiegel bestätigt werden.
- **Stammdaten:** `ml` klein, berechneter Brutto-VK aus belegtem Netto-VK und bestätigter Steuerklasse, Gewicht weiterhin als Schätzung; Lieferstatus nur aus belegtem JTL-Klarnamen, ID ist keine Lieferstatus-Bezeichnung. Bei `Hair Styling` trotz Haarfarbentitel Quellenkonfliktwarnung, keine ungeprüfte Änderung von JTL.
- **Tests:** [GitHub Actions #44831637094](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44831637094) grün. Node-Regeltests für 1.500 EANs, Quoting, Validierung, Dusicolor-Konflikt und globale SKU-Vergabe; PHP-/JS-Syntax. Server-DB-End-to-End mit neuen API-Operationen **noch NICHT** bestätigt.
- **GitHub-Release:** [PR #22](https://github.com/NicoVoss1/HW24-scanner/pull/22) gemerged; `deploy/staging` neuer SHA **`7db542548f7ba4c31ddf12d65b1e1866ff0592ba`**. Website-Root auf nur `api/`, `assets/`, `index.html`, `scanliste.html` geprüft, keine Secrets, SQL oder Docs im Webroot.
- **Dokumentation:** `docs/HW24_SCAN_CSV_NUMMERNKREIS_V422_20261120.md`, `docs/HW24_PROJEKTSTAND_AKTUELL.md`. Alte MariaDB-/Plesk-Backups, Verzeichnisprüfungen und V11/V12-Schema-Installation weiterhin **erledigt**, kein Neustart.
- **Nächster Schritt:** Plesk Git `deploy/staging` Pull/Bereitstellen; danach Test-EAN 2× scannen, `−1` testen, Position archivieren und erneut scannen, dann drei CSV-Zeilen (EAN/Lagerplatz/Information/Kommentar) importieren, Reload und Bericht. Erst nach Erfolg große Importdatei für ca. 1.500 EANs verwenden. 


### 21.11.2026 · Direkte ASIN-Links und verantwortliche KI-/Live-Planung
- **21.11.2026 · PR #23:** Neue Amazon-ASIN-Direktlinks und validierte 1/2/3-Pack-, Farbcode-, Marken- und Gebindeprüfung sind in GitHub-`deploy/staging` gemerged. **Aktueller SHA `753abbbe4c9e25d99ec1f821d7e058271fbc26d0`**, [CI #44910579185 erfolgreich](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44910579185). Warengruppenkonflikte werden als Vorschläge gewarnt, JTL-Quellwerte bleiben unverändert. **Kein bestätigter Plesk-Test-/Live-Deploy.** Weitere Abnahme und exakte Vorbedingungen für KI/Live: [ASIN, KI und Live-Freigabe](./HW24_KI_ASIN_LIVE_FREIGABE_20261121.md). Der konkrete Wella-Color-Touch-9/01-Treffer ist ohne seine ASIN nicht real verifiziert.
- Vier Browser-JS-Dateien aus einer geprüften V4.22-Basis geändert; PR #23 in `deploy/staging` gemerged. Keine SQL-, Credentials- oder Live-Veränderung, keine JTL-/WMS-Schreiboperation.
- Mehrfachpackung wird aus ausdrücklicher Angabe (z. B. 2×60 ml, 3er-Pack) oder Amazon-Attribut erkannt; ohne Angabe gilt die Stückzahl als unbekannt. Direkter Amazon.de-/dp/ASIN-Link ist pro Kandidat sichtbar. Farbcode 9/01 vs. 9/03 und falsche Warengruppen werden gewarnt.
- Für **echte** KI fehlt noch ein gesonderter serverseitiger Modellzugang, JTL-Referenzkatalog/Beispiele, Freigabe- und Budgetregel. Für Live fehlen aktuelle Plesk-Branch-/Pfad-Baseline und bestätigter Test-E2E.


### 21.11.2026 · PR #24: JTL-Dropdowns, ASIN-Bug und freiwillige OpenAI-Kostenkontrolle · GITHUB ERLEDIGT, SERVERTEST OFFEN
- **Quelle:** Nutzer möchte keine redundanten JTL-Exporte, bestehende/neue Artikel mit bedienbaren Dropdowns, bestätigte ASIN direkt im Feld, Artikelpflege statt ungeprüfter DB-Schreibaktionen und einen überall sichtbaren OpenAI-Kostenzähler. Tatsächlicher GitHub-Code `api/jtl.php`, `api/jtl-sync.php`, `scanliste.html`, `assets/hw24-scan-autofill.js`, `assets/jtl-profil.js` überprüft.
- **DB-Feststellung:** Aktuelle HW24-API fragt nur **MariaDB `hw24_artikelmanager`** mit Windows-gespiegelten `hw24_jtl_articles` und `hw24_jtl_sync_meta` ab; ein uneingeschränkter direkter SQL-Server-Zugriff auf sämtliche tHersteller/tWarengruppe/tKategorie/tLieferstatus-Einträge ist **nicht belegt**. Im Spiegel enthaltene `HW24_RelatedSources` und `HW24_WarengruppeName` sind teilweise bereits überprüfte Nachweise; fehlende Fremdschlüssel/Kategorien dürfen nicht erfunden werden.
- **Neue JTL-Referenzroute:** `api/jtl-options.php`, lesend, HTTPS/Authentifizierung, nur neuester vollständig synchronisierter JTL-Snapshot bis 45 Minuten. Beobachtete Werte aus Hersteller, Warengruppen, Lieferstatus, verfügbaren Kategorie-/Zeitfeldern; ausdrücklich `scope=observed_values_only`, kein vollständiger Katalog. Keine neuen SQL-Tabellen, keine Zugangsweitergabe.
- **UI:** `scanliste.html` erlaubt Dropdowns auch bei JTL-Bestandsartikeln im **reinen Vergleichsmodus**; Ameise-Neuanlage und direkte JTL-Schreibrechte bleiben für Bestandsartikel gesperrt. Link „Zur Artikelpflege öffnen“ nach `index.html?ean=...`; Haupt-JTL-Profil lädt dieselben Referenzvorschläge. Bei fehlender Liste explizit ungeprüften Wert manuell ergänzen statt fälschlich „in JTL bestätigt“ behaupten.
- **ASIN-Fix:** `HW24ScanBridge.apply` akzeptiert `confirmedChoice:true`, um **bewusst bestätigte** ASIN-Vorschläge zu ersetzen und revisionssicher im HW24-Scanlistenentwurf zu speichern; Erfolg erst nach erfolgreichem API-Schreibvorgang. Die Hauptmaske aktualisiert zusätzlich den sichtbaren echten ASIN-Eingabekontrollwert.
- **OpenAI:** `api/ai-review.php` mit serverseitigem, getrenntem Projekt-Key außerhalb Webroot, authentifizierter manuell bestätigter Responses-Abfrage, ohne JTL/WMS/Marktplatz-Schreibaktion; `assets/hw24-ai-cost.js` zeigt letzte geschätzte Tokenkosten, Monatssumme, manuelle Prüfungen und Budget oben rechts in beiden Ansichten. **Noch kein API-Schlüssel auf Server angelegt oder KI-Abfrage ausgeführt.** Ohne Schlüssel standardmäßig deaktiviert und keine kostenpflichtigen automatischen Scans.
- **Budget:** Konfigurierbares privates Monatsbudget (Beispiel 5 USD) und max. 25 manuelle Tagesaufrufe; parallele Aufrufe durch exklusiven Dateilock begrenzt. Lokales USD-Ledger basiert auf OpenAI-API-Usage-Werten, kein vollständiger Abrechnungsnachweis. Beispieltarife für GPT-5.4 mini aus offizieller Modellseite am 21.11.2026. Die private Konfigurationsdatei ist weder im GitHub-Staging noch im öffentlichen Webroot.
- **CI/Release:** [GitHub Actions #44923871216](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44923871216) grün (PHP-/JS-/Inline-JS-Syntax, sicherer Referenzpfad, ASIN-UI-Verkabelung, API-Budget-Gates). Website-only [PR #24](https://github.com/NicoVoss1/HW24-scanner/pull/24) gemerged, `deploy/staging` SHA **`41bd862178d23a2e385d253767898318a490fdca`**. GitHub-Root überprüft: nur `api/`, `assets/`, `index.html`, `scanliste.html`.
- **Unverändert:** Live-Plesk-Branch `main`, Wawi-SQL-Server, JTL/WMS-Lagerbestände, alte MariaDB-Backups, V11/V12-Tabellen und private Zugangsdateien. Keine neue SQL-Migration.
- **Nächster Schritt:** Nur Plesk-Testdomain `deploy/staging` Pull → Bereitstellen; dann JTL-Dropdowns, direktes Öffnen eines Bestandsartikels und ASIN-Auswahl nach Reload testen. Kostenwidget ohne Key als deaktiviert prüfen. Für produktive OpenAI-Nutzung eigenen Key und Budget **nur privat auf Server** einrichten. **Kein** Live-Pull und **kein** erneutes Backup-/PuTTY-/SQL-Basissetup.
- **Dauerhafte Dokumentation:** `docs/HW24_JTL_REFERENZEN_OPENAI_API_20261121.md`.


### 21.11.2026 · OpenAI-Testaufruf scheitert, Fehlerdiagnose ergänzt · GITHUB ERLEDIGT, PLESK-TEST OFFEN
- **Nutzer-Screenshot:** Scanliste ist aktiv (25 Positionen, 23 mögliche Neuanlagen, 2 bestehende JTL-Artikel). Amazon für Wella Color Touch 9/01 zeigt drei ASIN-Kandidaten, davon ASIN `B079LHL53N` mit Amazon-Attributen `number_of_items=1` und `item_package_quantity=1`. Produktprüfung auf dem Server ist davon getrennt.
- **KI-Fehler:** Das HW24-Kostenwidget zeigt `KI-Prüfung nicht abgeschlossen: openai_request_failed`, bislang `0,0000 USD` im Monat und 0 erfolgreiche im HW24-Ledger gebuchte Aufrufe. Status-GET allein testet **nicht** die tatsächliche OpenAI-Autorisierung. Die bisherige UI hat den vom Server bereits gelieferten `upstream_http` nicht ausgegeben.
- **Patch:** [PR #25](https://github.com/NicoVoss1/HW24-scanner/pull/25) gemerged, `deploy/staging` SHA **`2c23e926de791f8055342c724d4345b96fb95b5b`**. Nur `api/ai-review.php` und `assets/hw24-ai-cost.js` geändert. Backend meldet numerischen OpenAI-HTTP-Status und streng gefilterten Fehlercode/-typ/-parameter; bei Verbindungsfehler cURL-Fehlernummer. Keine Schlüssel/Produkt-Payload im Browser. UI übersetzt in deutsche Hinweise für HTTP 400/401/403/404/429/500–503/0, kein automatischer Retry oder neuer kostenpflichtiger Aufruf.
- **Tests:** [GitHub Actions #44931041873](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44931041873) grün, PHP-/JavaScript-Syntax und sichere Fehlertext-Verkabelung. Der konkrete upstream HTTP-Status ist **noch unbekannt** und wurde nicht erfunden. Möglich sind u. a. Key-/Projektberechtigung, API-Guthaben/Quota, ungültiges Request-Schema oder Netz-/TLS-Fehler.
- **Nächster Schritt:** Nur Plesk-Testdomain mit `deploy/staging` Pull → Bereitstellen, Scanliste mit Strg+F5 neu laden, genau eine explizit bestätigte KI-Prüfung. Ergebnis `OpenAI HTTP <code>` + Fehlercode anfordern, aber **niemals** Schlüssel oder private Konfiguration. Bei HTTP 429 zuerst OpenAI-Platform Billing/Credits statt kostenpflichtige Retries; ChatGPT-Abonnement separat. Kein Live-Deploy, keine SQL-/PuTTY-Installationen und keine weiteren Backups.


### 21.11.2026 · Nach erster erfolgreicher OpenAI-Auswertung: persistente KI-Wissensbasis in TEST programmiert
- **Nutzerbefund:** Wella Color Touch 9/01 wurde erfolgreich mit OpenAI geprüft; Screenshot zeigt Analyse/ASIN-Bewertungen und **0,0013 USD** Kosten. Nutzer möchte vollständige Ergebnisse dauerhaft speichern, vergrößerte Anzeige, historischen Verlauf, bestätigte Übernahme in Datenbank, später viele API-/Bot-Funktionen. **Bisher** speicherte nur `hw24_private/hw24-ai-usage.json` Tokenkosten/Anzahl, kein fachlicher Bericht; zuvor durchgeführte erste Analyse kann daraus nicht rückwirkend rekonstruiert werden.
- **Website-Release:** [PR #26](https://github.com/NicoVoss1/HW24-scanner/pull/26) in `deploy/staging` gemerged, SHA `94141e67482e87a2afaec56d60654d534cae5da8`. Sechs Website-Dateien: `api/ai-review.php`, `api/ai-reviews.php`, `assets/hw24-ai-cost.js`, `assets/hw24-ai-history.js`, `scanliste.html`, `index.html`. Große responsive KI-Auswertungsübersicht, EAN-Filter, historische Cursor-Pagination, vollständige AI-Begründung, ASIN-Einzelprüfung und Amazon-Links, Quelle/Token/Kosten. Nur nach Nutzerbestätigung und nur für **neue** Scanpositionen kann ein KI-Warengruppenvorschlag/ein ASIN-`pass`-Treffer mit Quellenvermerk in den bestehenden, revisionsgesicherten HW24-Entwurf übernommen werden; keine direkte JTL-/WMS-Änderung.
- **Backend:** Vor kostenpflichtigem API-Aufruf wird V13-Archiv `hw24_ai_reviews` unter eingeschränktem DB-Account geprüft. Die erfolgreiche Antwort wird unveränderlich als Datensatz mit authentifiziertem Bearbeiter, EAN, Artikelname, vollständiger KI-Auswertung, Eingabe-/Amazon-Quellen, Modell, tatsächlichen Tokens und USD-Kostenschätzung gespeichert. API gibt erst nach INSERT `stored:true` und `review_id` zurück. Zusätzliche KI-Felder u.a. Hersteller, Farbnuance, Inhalt/Einheit, Packstückzahl, Typ und Namensvorschlag, nicht belegte Werte null. Neue Fragen können später ergänzt werden, ohne Altberichte zu überschreiben.
- **Datenbankmigration nur auf Testserver:** `sql/HW24_13_AI_REVIEWS_ARCHIVE.sql` und bewachtes `ops/HW24_INSTALL_AI_ARCHIVE_TEST.sh` im Featurebranch `feature/hw24-persist-ai-reviews-20261121`. Neue Tabelle und ausschließlich `SELECT,INSERT`-Rechte für vorhandenen `hw24_scan_rw`; keine Änderung an bestehenden JTL-/Scan-Tabellen. Skript mit Versions-Commit `7249fbd70089682f23248c1502d49a3e4ef91699` fest adressierbar, prüft neue TEST-Dateien/DB und verlangt `ARCHIV`. **Noch nicht in PuTTY ausgeführt!**
- **Kosten vor Migration geschützt:** [PR #27](https://github.com/NicoVoss1/HW24-scanner/pull/27) in `deploy/staging` gemerged, SHA **`6e6e7f7dfd536f4bef42c401321cafe95f855004`**. API-Status meldet `archive_ready`, Kostenwidget deaktiviert neue KI-Prüfungen solange Archiv fehlt; kein billable KI-Call. Fehlermeldung bei archivbedingtem Fehler ausdrücklich.
- **Tests:** [GitHub Actions #44933326129](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44933326129) für Archiv/JS/PHP/Quellen, [#44933692434](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44933692434) für Kostensperre grün. **Kein** tatsächlicher Plesk-/OpenAI-/MariaDB-End-to-End-Test des neuen Archivs oder bestätigte Datenpersistenz auf dem Server.
- **Nächste Aktion:** Plesk TEST `deploy/staging` SHA `6e6e7f7dfd536f4bef42c401321cafe95f855004` Pull → Bereitstellen, dann **einmalig** bewachtes PuTTY-Installerskript V13 (nicht das bereits erledigte V11/V12-Skript!) → `GET /api/ai-reviews.php` muss vor erstem neuen Review `ok:true,stored:true,count:0` liefern → einmal explizit bezahlte KI-Prüfung → Archiv öffnen, neu laden, ASIN-/Warengruppenvorschlag nach menschlicher Prüfung nur auf neuem HW24-Entwurf übernehmen. KEINE Live-/Wawi-/WMS-Schreibfreigabe, keine alten Backups oder SQL erneut.
- **Doku:** `docs/HW24_KI_ARCHIV_UND_ANALYSEN_20261121.md` führt Installations-, Test- und Ausbauplan; weitere APIs und Bots nur mit eigenen Projekten/Kostenlimits und Nachweisen.


### 21.11.2026 · Nutzer bestätigt V13-Installation, strategischer HW24 Product Intelligence Hub angelegt
- **Neue Terminal-Evidenz direkt vom Nutzer:** Bewachtes `HW24_INSTALL_AI_ARCHIVE_TEST.sh` wurde als root ausgeführt, exakt `ARCHIV` bestätigt, Installation meldete `OK` und `bisherige_ki_pruefungen = 0`. **Damit wurde V13 serverseitig laut Nutzerausgabe eingerichtet.** Es gibt noch keinen eigenständigen Browser-`GET /api/ai-reviews.php`-Nachweis und noch keinen **nach** Installation erfolgreich gespeicherten KI-Bericht. V11/V12, Backups und Schlüssel nicht erneut einrichten.
- **Zielbild des Nutzers:** HW24 soll zur qualitätsgeprüften Produktdaten- und Handelsplattform für Friseurbedarf in Deutschland/Europa wachsen, mit JTL-Wawi, JTL-WMS, Shopify, Amazon/eBay, Herstellerdaten, KI und markengetrenntem Social Media. Dieses strategische Ziel ist keine technische Garantie oder Freigabe für sämtliche Kanalschreibzugriffe.
- **Verbindung geprüft:** Der vorhandene Shopify-Connector erreicht HAIRWORLD24 (EUR/Deutschland); Produkt-, Varianten- und SEO-Daten sind lesbar. Eine **begrenzte Shopify-Read-only-Stichprobe** zeigte Pflegebedarf insbesondere bei Produkttypen und expliziten SEO-Overrides; konkrete private Mengen/Bestandszahlen wurden bewusst **nicht** im öffentlichen GitHub gespeichert. **Keine** Produktmutation vorgenommen.
- **Social-Konnektor geprüft:** Derzeit vorhandene Metricool-Marken gehören anderen Projekten. Ein dedizierter HAIRWORLD24-Auftritt/Brand ist vor einer Veröffentlichung separat zuzuordnen; keine fremden Markenkanäle ungeprüft nutzen. Keine Beiträge veröffentlicht.
- **Neue Architektur:** [HW24 Product Intelligence Hub](./HW24_PRODUCT_INTELLIGENCE_HUB_STRATEGIE_20261121.md) definiert kanonische Produktidentität, Produkt-Fakten inklusive Quellen-/Lizenzstatus, Varianten vs. Bundles, versionierte Freigaben, KI-Queue und kanalweise idempotente Veröffentlichungsentwürfe. Führend für Bestand bleibt JTL/WMS, Shop-Inhalte sollen erst nach Shopify-Preview/Freigabe geändert werden.
- **Konkreter nächster GitHub-Arbeitsauftrag:** [Issue #28](https://github.com/NicoVoss1/HW24-scanner/issues/28), ausschließlich read-only JTL↔Shopify-Qualitätsinventur auf der Testversion als Basis einer späteren kontrollierten Shopify-Publishing-Pipeline. Kein Plesk-, Shopify-, Wawi- oder Social-Live-Write im Rahmen dieser Recherche.
- **Nächster Nutzer-Test:** Browser bei angemeldeter Testseite `https://test-artikelmanager.hairworld24.de/api/ai-reviews.php` prüfen (erwartet `ok:true` und zunächst `count:0`). Danach in der Scanliste **genau eine** ausdrücklich bestätigte KI-Abfrage, Ergebnisarchiv erneut nach Reload öffnen. Erst wenn dieser Nachweis erfolgreich ist, AI Produktdaten-Integration erweitern.


### 21.11.2026 · KI-Archiv invalid_cursor repariert und kostenpflichtige EAN-Doppelprüfungen verhindert
- **Nutzerbefund:** Authentifizierter Browser-GET `/api/ai-reviews.php` meldete `{"ok":false,"error":"invalid_cursor"}` **ohne** gesetzten `before_id`. Nutzer möchte bereits über OpenAI geprüfte Artikel deutlich markieren und doppelte oder dreifache Modellabfragen vermeiden.
- **Ursache und Fix:** `filter_var($_GET['before_id']??null,FILTER_VALIDATE_INT)` lieferte `false` für einen fehlenden Parameter. Neue API prüft `before_id` nur, wenn er tatsächlich übermittelt wurde; eine erste Archivseite ist wieder zulässig, fehlerhafte angegebene Cursor bleiben ungültig.
- **Kennzeichnung:** Neuer lesender `GET /api/ai-reviews.php?summary=1&eans=...` liefert für den authentifizierten Bearbeiter je EAN Anzahl, letzte Prüf-ID, Status und Zeitpunkt. `assets/hw24-ai-markers.js` zeigt in der Scanliste bei dauerhaft geprüften EANs **`🤖 KI geprüft`** (bei mehrfachen Berichten mit Anzahl) und aktualisiert nach Auswahl/Archivierung, ohne eine KI-Abfrage auszulösen.
- **Backend-Kostenschutz:** `api/ai-review.php` prüft **vor jedem kostenpflichtigen OpenAI-Aufruf** unter exklusiver Dateisperre die letzte archivierte Prüfung nach Bearbeiter und EAN. Wenn bereits vorhanden, wird diese mit `reused:true`, Archiv-ID und **`usd_call:0`** zurückgegeben. Abweichende Quelleneingaben werden mit `input_changed:true` gekennzeichnet. Ein kostenpflichtiger Recheck erfordert eine ausdrücklich bestätigte `X-HW24-Recheck`-Aktion und die als `X-HW24-Previous-Review` übermittelte letzte Prüf-ID; bei zwischenzeitlich geändertem Archivstand Abbruch ohne OpenAI-Call.
- **UI:** Standardknopf bei bestehendem Ergebnis `📂 KI geprüft · ansehen` öffnet kostenfrei den alten Bericht. Separate Aktion `🔄 Neu prüfen ($)` erlaubt nur nach zusätzlicher ausdrücklicher Bestätigung eine neue kostenpflichtige Modellprüfung. Bei Archivproblemen schlägt die UI fehl, **ohne** die teure API zu rufen. Cache-Busting für `index.html` und `scanliste.html`.
- **Grenze:** Die vor der einmaligen V13-Installation aufgelaufene erste KI-Auswertung (0,0013 USD) liegt nicht im `hw24_ai_reviews`-Archiv und kann aus dem reinen Kostenledger nicht als vollständiger Bericht wiederhergestellt oder nachträglich als „KI geprüft“ angezeigt werden. Nur neue dauerhaft gespeicherte Berichte zählen.
- **GitHub:** [PR #29](https://github.com/NicoVoss1/HW24-scanner/pull/29) in `deploy/staging` gemerged. **Testbranch SHA `1618ebac749a6cc1854a8e0673da4725f5bcf984`**. [GitHub Actions #44950463294](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44950463294) grün: initialer GET ohne Cursor, ungültiger Cursor, EAN-Summary, doppelte Aufrufe, Recheck-Sperre, JS/PHP-Syntax. Nur sechs Website-Dateien geändert; keine SQL-Migration, keine JTL-/WMS- oder Shopify-Schreibaktion, kein Live-Deployment.
- **Nächster Schritt:** **Nur** Plesk-Testdomain `deploy/staging` Pull → Bereitstellen. Im angemeldeten Browser `/api/ai-reviews.php` erneut aufrufen, danach Scanliste `Strg+F5` und eine bereits **nach V13** gespeicherte EAN auswählen. Falls Archiv 0 Prüfungen enthält, erscheint noch kein Marker; zunächst genau **eine** ausdrücklich bestätigte neue KI-Prüfung durchführen, danach Seite neu laden, „🤖 KI geprüft“ sehen und archivierten Bericht ohne erneute Kosten öffnen. V13-Installer **nicht erneut ausführen**. Live bleibt `main`.


### 21.11.2026 · HW24 V4.23 Qualitätszentrum · erste umgesetzte Intelligence-Konsole in TEST
- **Nutzerfreigabe:** „Dann gehen wir jetzt den nächsten Schritt an. Starten wir. Ready go.“ Umsetzung des zuvor empfohlenen ersten Schritts, der **Qualitätsinventur aller vorhandenen JTL-Artikel**. Kein Auftrag zur unkontrollierten Live-/Wawi-/WMS-Änderung.
- **Grundlage im GitHub-Code geprüft:** `api/jtl.php`, `api/jtl-status.php`, `api/jtl-sync.php`, `api/jtl-options.php`, `assets/qualitaet.js`, `assets/jtl-profil.js`, `assets/hw24-workdesk.js` und Haupt-`index.html`. Existierende MariaDB-Spiegeltabellen `hw24_jtl_articles` und `hw24_jtl_sync_meta` enthalten 23 JTL-Artikelspalten, darunter `ean`, `han`, `hersteller`, `netto_ek`, `letzter_ek`, `bestand`, Kurz-/Langbeschreibung und `raw_json`. Kein vollständiger direkter JTL-SQL-Serverzugriff behauptet.
- **Programmierter neuer Prüf-Endpunkt:** `api/jtl-quality.php` (GET, HTTPS, Plesk Basic Auth, no-store) und `api/hw24-quality-rules.php` (GTIN-Prüfsummen, Netto-EK-/Bestandswarnung, fehlender Hersteller, EAN, HAN, Artikelname, Texte, Warengruppe und Lieferstatus-Indizien). Nur jüngster `ready`-JTL-MariaDB-Snapshot, `last_row_count` muss exakt aktueller Quellzeilenanzahl entsprechen; älter als 45 Minuten wird als **historischer Stand** gekennzeichnet; >20.000 Zeilen sichere Kapazitätsgrenze bis zum späteren Streaming-Audit. Keine Schreib- oder OpenAI-API-Aufrufe.
- **Wichtige Genauigkeit:** „Bestand vorhanden, EK im Spiegel nicht belegt“ gilt bei positivem Bestand und weder positivem Netto- noch letztem EK. Dies **beweist nicht**, dass es keinen anderen Beleg / keinen Kommissionsartikel gibt. Rechtlich verantwortliche Person, Kosmetikdaten, INCI, echte Lieferzeit und WMS-Verfügbarkeit noch nicht vollständig belegbar; als `not_measurable` benannt statt falscher Vollständigkeitsbehauptung. Keine pauschale Forderung, alle 872 JTL-Exportspalten für alle Produkttypen zu füllen.
- **Neue Qualitätssicht:** `qualitaetszentrum.html` mit `assets/hw24-quality-center.css` und `assets/hw24-quality-center.js`: eigene responsive Dashboard-Navigation, vollständige Spiegelkennzahlen, priorisierte Prüfaufträge, Filter nach Datenlücken und Schweregrad, Suche nach Artikelnummer/EAN/HAN/Hersteller, paginierte Qualitätsfälle und Direktlink in den bestehenden HW24 Artikelmanager. `index.html` und `scanliste.html` erhalten den Link; Artikelstamm kann auch über JTL-Artikelnummer `?q=` geöffnet werden. Scanliste bleibt für **neue** EANs, Qualitätszentrum für **vorhandene** JTL-Artikel.
- **Tests:** [GitHub Actions #44953759138](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44953759138) **grün**: PHP-GTIN-/EK-/Artikelgruppenregeln, PHP/JS-Syntax, statische Source-/Auth-/UI-Verknüpfung, Inline-JS und Asset-Verfügbarkeit. Keine realen JTL-MariaDB-Aggregate überprüft, keine Qualitätsquote erfunden.
- **Freigabe:** Website-only [PR #31](https://github.com/NicoVoss1/HW24-scanner/pull/31) nach `deploy/staging` gemerged. **Testcommit `c54894c854c538e79ef8e6fc9b9132102be79a6c`**, sieben geänderte Website-Dateien (zwei neue PHP, zwei neue JS/CSS, eine neue HTML, zwei Navigationen). Keine private Datei, keine neue SQL-Migration, kein Live-Deployment, kein JTL-/WMS-/Shopify-Schreibzugriff.
- **Nächster Schritt:** Nur **Plesk-Testdomain** `deploy/staging` Pull → Bereitstellen. Anschließend im authentifizierten Browser `/qualitaetszentrum.html` öffnen; JTL-Snapshotalter und **tatsächliche** Gesamt-/Fehlerzahlen kontrollieren. Einen Fall mit `Bestand >0 ohne nachgewiesenen EK` und einen mit fehlendem Hersteller prüfen und Artikellink testen. **Vorher** noch keine Bestandsartikel-Datenkorrekturen. Bereits erledigte MariaDB/V11-V12-V13-Installationen und Backups **nicht** erneut durchführen.
- **Doku:** `docs/HW24_QUALITAETSZENTRUM_V423_20261121.md`, langfristiger [Intelligence-Console-Auftrag #30](https://github.com/NicoVoss1/HW24-scanner/issues/30).


### 22.11.2026 · V4.24 Testrelease: neue Artikelpflege und unabhängige Bestand-/EK-Filter
- **Nutzerwunsch:** bestehende Artikelpflege ist unübersichtlich; separate Filter für Bestand **oder** EK statt implizitem UND; Bestand-/EK-Spalten jeweils direkt sortierbar; bei Standardansicht **positive Lagerbestände zuerst**. Haarfarben-Referenz-EAN **3474630337398** soll nur bestätigte Artikel-Grundstandards liefern.
- **Evidenz:** EAN-13-Prüfziffer der Referenz ist rechnerisch gültig. JTL-Spiegel enthält technisch kLieferStatus, nBearbeitungszeit, nLiefertageWennAusverkauft, nAutomatischeLiefertageberechnung und Artikel-Flags. **Die tatsächlichen Werte der Referenz-EAN sind noch nicht anhand des privaten Plesk-JTL-Servers abgerufen/bestätigt.**
- **GitHub:** [PR #32](https://github.com/NicoVoss1/HW24-scanner/pull/32) in `deploy/staging` gemerged. SHA **`f51ab67240bd1bf0e5be25498269049a24748b85`**. Zehn bestehende/neue Website-Dateien plus schlanke Scanlisten-Navigation, keine SQL-Migration und keine Änderung an Live-`main`.
- **Qualitätszentrum:** `api/hw24-quality-filters.php` kapselt belegte Bestands-/EK-Filter, echte numerische Spaltensortierung und stock-first-Prio. `api/jtl-quality.php` verwendet die Helper; Oberfläche getrennte Bestand-/EK-Auswahl mit UND/ODER (Standard ODER), Kopfklick Bestand/EK, direkter Link zur Artikelpflege.
- **Artikelpflege:** `artikelpflege.html` + `assets/hw24-article-editor.js/css`, wenige sichtbare Gruppen: Identität, Texte, Preise. Lieferstatus/Beschaffung/Artikelstatus und Zusatzfelder in einer Expertenoption. Quellenvergleich JTL vs. bestätigter HW24-Entwurf. Speichern nur geänderte, nicht leere Felder über `api/curated.php` nach expliziter Freigabe mit Revision und Audit, **kein JTL-/WMS-/Shopify-Schreibzugriff**. Die klassische `index.html` bleibt als Expertenansicht erhalten.
- **Haarfarben-Vorlage:** Vergleicht die tatsächlich beim Aufruf über `api/jtl.php` gelesenen Referenzwerte; nur belegte, technisch eindeutig aufgelöste Standardwerte in leere Felder, nur nach gesonderter Bestätigung der Produktfamilie Haarfarbe. EAN, HAN, Art.-Nr., Hersteller, Preise und Bestände niemals kopieren. HW24-Overlay-Felder sind **noch keine verifizierten JTL-Ameise-Mappingfelder**.
- **Tests:** [Feature-CI #44963263588](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44963263588) und [#44963263701](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44963263701) grün; auslösende frühere fehlgeschlagene Workflow-Versionen wurden auf dem Featurebranch korrigiert. Server-/Browser-E2E offen.
- **Nächster Schritt:** Ausschließlich Test-Plesk-Git `deploy/staging` Pull → Bereitstellen; Qualitätszentrum getrennt filtern/sortieren; `artikelpflege.html?ean=3474630337398` öffnen und echte Standardwerte prüfen. Kein erneutes Backup/DB-Setup und **kein** Live-Pull. [Vollständige Release- und Prüfanleitung](./HW24_V424_QUALITY_EDITOR_RELEASE_20261122.md).


### 22.11.2026 · V4.25: Amazon-SP-API/Preisangebote, KI, Dropdowns und Ameise-Export direkt in Artikelpflege
- **Nutzerbefund:** Artikelpflege V4.24 wesentlich übersichtlicher, aber kein Amazon-Modul; fehlender Artikeltyp, Steuer-/Versand-/Währungsdropdown; im Spiegel scheinbar fehlender JTL-Brutto-VK; Lieferstatus-/Beschaffungszeit-IDs unverständlich; Lieferantenfelder aktuell unerwünscht; separate Ameise-CSV und OpenAI-KI direkt neben Bestandsartikeln gewünscht.
- **Ursache Amazon:** In `artikelpflege.html` war bislang kein Client für die schon bestehende `api/amazon.php` eingebunden. Neue `assets/hw24-article-intelligence.js` führt per ausdrücklich manuellem Klick EAN-Katalog und optional Produktnamen-Titelsuche (`amazon-keywords.php`), manuelle ASIN-Angebotspreise (`amazon-pricing.php`), KI-Archiv und bestätigten Ameise-Prüfexport aus. Kein automatischer, unnötiger SP-/OpenAI-Aufruf.
- **KI-Sicherheit:** Beim Artikelaufruf nur kostenloses `GET /api/ai-reviews.php`. Bezahlter `POST /api/ai-review.php` nur nach ausdrücklicher Zustimmung, Backend-Limit, gesichertem bestehendem Archiv und eventuellem explizitem Recheck samt voriger Berichts-ID. Kein unbemerkter API-Call.
- **UI:** doppeltes `logisticsFields`-ID entfernt, Lieferantenfelder ausgeblendet. Artikeltyp, Steuerklasse, Versandklasse, Währung als Select-Elemente mit beobachteten JTL-Optionswerten. Haarfarben-Vorlage nur nach Bestätigung als Quelle für fehlende Felder; alternative Zeitvorschläge `0` automatisch (Nein), 1 Tag manuelle Beschaffung, 1 Tag Bearbeitung. Keine automatische Massenänderung.
- **JTL-Brutto-VK:** Normale JTL-MariaDB-Synchronisation enthält `netto_vk`, aber keine nachgewiesene Brutto-Preisquelle. `api/jtl.php` liest künftig ausschließlich geprüfte `raw.HW24_VKBrutto` **mit** `raw.HW24_VKBruttoQuelle`, ebenso zusätzliche Art.-Typ-/Währungs-Rohwerte. Windows-Sync-Mapping bleibt ausstehend: [Issue #33](https://github.com/NicoVoss1/HW24-scanner/issues/33). Noch keine tatsächlichen JTL-Bruttopreise aus der privaten SQL-Server-Datenbank verifiziert.
- **Ameise:** Aktuelle Artikelnummer/EAN und Revision werden vor Export erneut aus JTL/Curated gelesen. Nur ausgewählte, dauerhaft bestätigte, konservativ auf Ameise geprüfte Stammdatenfelder als Ein-Zeilen-CSV. Liefer-/Beschaffungs-/Statuswerte bis zum separaten Ameise-Feldmapping ausgeschlossen. Kein Live-Schreibzugriff.
- **GitHub:** [PR #34](https://github.com/NicoVoss1/HW24-scanner/pull/34) in `deploy/staging` gemerged, **SHA `444453471b0fd27c73dcc7929f5e17f1d9239c52`**, [CI #44964900380](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44964900380) grün. Live-`main` nicht deployt. Keine privaten Credentials oder SQL-Migration.
- **Nächster Schritt:** Plesk TEST `deploy/staging` Pull/Bereitstellen, direkt `artikelpflege.html?ean=3474630337398`, einmalige manuelle Amazon-Suche und ggf. Fehlerphase melden, kostenfreien KI-Archivabruf testen, Dropdowns und freigegebene Ameise-Datei prüfen. JTL-Brutto-VK erst nach Windows-Datenmapping. [Detaillierte Releaseanleitung](./HW24_V425_ARTIKELPFLEGE_RELEASE_20261122.md).


---

## 22.11.2026 · PuTTY-Framework-Rollout: bestätigte Einzelereignisse und Fehlerursache

**Quelle:** Bediener-Ausgaben aus PuTTY/SSH und direkt abgefragte GitHub-Daten; alle technischen Befunde differenziert nach tatsächlicher Verifikation. Die nachfolgenden Einträge ergänzen historische Abschnitte **append-only**, ohne diese rückwirkend zu ändern.

| Schritt | Tatsächlicher Vorgang | Ergebnis und Beleg | Status |
| --- | --- | --- | --- |
| 01 | Bestehende Infrastruktur per PuTTY überprüft | Ubuntu 22.04.5, Plesk, PHP CLI 8.1, Plesk PHP 8.3/8.4/8.5, Composer 2.11.2, Git 2.34.1, freie Kapazität ca. 914 GB; Node/npm im PATH nicht verfügbar | **BESTÄTIGT, read-only** |
| 02 | PHP-8.4-Eignung konkret geprüft | Plesk-Handler `plesk-php84-fpm` und `-fpm-dedicated` aktiv; `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `intl`, `zip` vorhanden | **BESTÄTIGT, read-only** |
| 03 | Domain-/Webroot-Aufteilung kontrolliert | Bestehende getrennte Legacy-Live-/Test-Artikelmanager-Webroots und gemeinsame Subscription; keine dritte Subdomain benötigt | **BESTÄTIGT, read-only** |
| 04 | Plesk-Git-Quellen abgerufen | Live: `HW24-scanner` auf `main`, Test: `HW24-scanner.git` auf `deploy/staging`; beide `manual` | **BESTÄTIGT, damaliger Zustand** |
| 05 | Sieben Test-Webdateien mit GitHub V4.25-BLOB-SHA abgeglichen | 5 OK, `artikelpflege.html` und `api/jtl.php` abweichend; Test-Plesk-Git zeigte zu diesem Zeitpunkt V4.24-Commit `f51ab672...` | **BESTÄTIGT, damals V4.24** |
| 06 | Bestehende Testsite vor V4.25-Deploy geschützt | Lesbares privates Test-Webroot-Archiv mit UTC-Zeitstempel angelegt; Archivdatei unter Root-Backupablage, nicht im öffentlichen Webroot | **BESTÄTIGT** |
| 07 | Test-Git-Pull und manuelle V4.25-Bereitstellung | `plesk ext git --fetch` und `--deploy` erfolgreich; zuvor geforderter SHA `444453471b0fd27c73dcc7929f5e17f1d9239c52` geprüft; nachher `artikelpflege.html`, `api/jtl.php` OK | **BESTÄTIGT, Code/Dateihash** |
| 08 | Anonyme HTTPS-Prüfung von Live und Test | Je Domain `/`, `/api/jtl.php?ean=3474630337398` und `/api/ai-reviews.php`: insgesamt 6 × HTTP **401** | **BESTÄTIGT, Zugriffsschutz an abgefragten URLs** |
| 09 | Live-Git-Branch auf Legacy V4.25 umgestellt | Neue PuTTY-Ausgabe: Live `Active branch: deploy/staging`, Plesk `--get-last-commit` = `444453471b0fd27c73dcc7929f5e17f1d9239c52` | **BESTÄTIGT, Git-Branch/Commit**; **Live-Webroot-Hashes und angemeldeter Browser-E2E weiter OFFEN** |
| 10 | Isolierten Framework-Klon vorbereitet | Ein privates App-Verzeichnis mit `repo/.git` existiert auf Plesk; Git-Klon unter Betreiber-Site-Systembenutzer `nicovoss` | **BESTÄTIGT, Dateien existieren**, tatsächlicher Git-HEAD noch nicht abgefragt |
| 11 | Prüfung des Git-HEAD im ursprünglichen Installationsskript | `git -C ... rev-parse HEAD` wurde als `root` auf fremdbesessenes Repo angewandt; Terminal meldete `fatal: detected dubious ownership` | **BESTÄTIGT, Fehlerursache der Git-Prüfung** |
| 12 | Wirkung auf PuTTY | Ursprünglicher Befehl `set -euo pipefail` wurde in der interaktiven Root-Shell eingegeben und konnte nach Git-Fehler die Shell und PuTTY-Sitzung beenden | **WAHRSCHEINLICHE Ursache für Schließen des Fensters**; kein gezielter Serverdienst-Absturz nachgewiesen |
| 13 | Laravel-PHP-Abhängigkeiten auf Server | Im privaten Repo fehlt `platform/vendor`; Composer-Installation somit **nicht** erfolgt | **BESTÄTIGT** |
| 14 | GitHub Framework-Pilot unabhängig entwickelt | [Draft PR #36](https://github.com/NicoVoss1/HW24-scanner/pull/36), Laravel 13 + Filament 5, Admin-Login/Allowlist, read-only JTL-Artikelsuche, explizite Amazon-/KI-GET-Buttons und API-Konfigurationsdashboard; Feature-Commit `980bcce75a0a3516b7b6364fae5a0fa6aab8d112` | **VERIFIZIERT GitHub, noch nicht Plesk-ausgeführt** |
| 15 | GitHub Actions für Console-Fundament | [CI #44972633923](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44972633923) und [Legacy-Regression #44972633843](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44972633843) `success` für Commit `980bcce...` | **VERIFIZIERT CI**, keine Plesk-Live-API-Tests |
| 16 | Cross-Chat-Übergabe eingerichtet | [`docs/HW24_HANDOVER.md`](./HW24_HANDOVER.md) als kurzer, verbindlicher Fortschrittsstand und Protokollregel für neue Chats geschrieben | **VERIFIZIERT GitHub**, Commit `81130adfe12233a868648149ea711b2de8eb15b1` |

### Präzise Fehlerbehebung ohne Neustart oder unkontrollierte Rechteausweitung

- Bestehenden Klon **nicht nochmals klonen oder löschen**.
- `git` immer unter dem **Eigentümer des geklonten Repositorys** ausführen; `runuser -u nicovoss -- git -C "$app/repo" rev-parse HEAD`, nicht als Root mit globaler `safe.directory`-Ausnahme. Erwarteter Head für bisher installierten Klon: `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`, **bis zur nächsten PuTTY-Ausgabe OFFEN**.
- Shellskripte mit `set -euo pipefail` in **separatem** `bash <<'MARKER'`-Prozess, nicht direkt in die interaktive PuTTY-Root-Shell einfügen. Abbruch und Fehlercode sollen sichtbar bleiben.
- `composer install` ausschließlich als Site-Benutzer mit explizitem PHP 8.4, im isolierten `repo/platform`. Aktuell noch **nicht ausgeführt**. Der Pilot führt mangels `composer.lock` eine Auflösung aus; vor reproduzierbarem Release Lockfile und Package-Validierung festlegen.
- Keine Änderung am PHP-Webhandler/DocumentRoot der Testdomain und keine Laravel-Datenbankmigration, bis Legacy V4.25 auf Live durch Webroot-Hash und authentifizierte Funktionsprüfung bestätigt ist.

### Projektregeln ab sofort

1. **JEDER relevante Schritt** in GitHub als neuer chronologischer Tagebucheintrag: auch Einzelprüfungen, Abbrüche, Sicherungen, Deployments, Prüfergebnisse, Codeänderungen, CI und Rollbacks. **Nicht nur Abschlussmeilensteine.**
2. Für neue Chats zuerst [Kurz-Übergabe](./HW24_HANDOVER.md) und diesen Eintrag lesen; keine zurückliegenden Prüfschritte unnötig wiederholen.
3. Jeder Eintrag: Datum, ausführender Kontext, Soll/Ist, **wirklich beobachtetes** Resultat, Beleg, Status, Sicherheitsrisiko und exakt nächste Aktion. Nach relevanten Änderungen Kurz-Übergabe und [Projektstand](./HW24_PROJEKTSTAND_AKTUELL.md) aktualisieren.
4. GitHub-Änderungen durch echten Commit nachweisen. Keine Geheimnisse, Token, privaten Konfigurationsinhalte, Server-Zugangsdaten oder SQL-Dumps in das **öffentliche** Repo.
5. Keine automatischen Datenbank-Schreibzugriffe oder unkontrollierten Plesk-/Live-Deployments.


### 22.11.2026 · Nachweis der GitHub-Dokumentationsschritte für neue Chats

| Einzelakt | GitHub-Nachweis | Ergebnis |
| --- | --- | --- |
| 17 · Kurz-Übergabe neu angelegt | [`HW24_HANDOVER.md`](./HW24_HANDOVER.md), Commit `81130adfe12233a868648149ea711b2de8eb15b1` | **ERFOLGREICH**; verbindlicher Startpunkt |
| 18 · Arbeitsjournal um sämtliche beobachteten PuTTY-/Git-/CI-Vorgänge und den `dubious ownership`-Fehler ergänzt | Commit `5b4ade9b86e9b603d9987ac8ce769d78a5eb0235` | **ERFOLGREICH**; append-only |
| 19 · Projektstand oben auf aktuelle bestätigte Fakten gebracht | [`HW24_PROJEKTSTAND_AKTUELL.md`](./HW24_PROJEKTSTAND_AKTUELL.md), Commit `a475d2a1429f8aa977dd33df36a06a8b73f7d7e7` | **ERFOLGREICH**, frühere Planannahmen bleiben als historische Abschnitte gekennzeichnet |
| 20 · Zwei-Domain-Migration mit Live-Branch und Clone-Teilerfolg ergänzt | [`HW24_TWO_DOMAIN_ROLLOUT_20261122.md`](./HW24_TWO_DOMAIN_ROLLOUT_20261122.md), Commit `b3fd0eb3ff6ed114e4e96a9a4050aa18ba085ad6` | **ERFOLGREICH** |
| 21 · GitHub-Repository-Startseite verlinkt zuerst aktuelle Übergabe | [`README.md`](../README.md), Commit `616c0bc65c118a1acd101b2f34dca3da0b951ecb` | **ERFOLGREICH**; auch neue Chats folgen sofort dem richtigen Kontext |

**OFFEN (nicht als abgeschlossen melden):** Repo-HEAD-Prüfung als Site-Benutzer, Composer-/Vendor-Installation, private Laravel-Konfiguration, separate Laravel-SQLite, Live-Webroot-Hash-/Browser-E2E, Umschaltung der Testdomain auf `platform/public`, echte JTL-/Amazon-/OpenAI-API-End-to-End-Abnahme.

**Nächste Betreiberaktion:** Schon vorhandenen privaten Klon als Besitzer `nicovoss` auf erwarteten Head `980bcce75a0a3516b7b6364fae5a0fa6aab8d112` prüfen. Nur dann Composer im privaten `repo/platform` mit PHP 8.4 als `nicovoss` installieren, niemals als Root und ohne neue Git-Klone. Kommende PuTTY-Ausgabe unmittelbar hier dokumentieren.


### 22.11.2026 · Installationsversuch: Plesk-Composer-Wrapper identifiziert

**BESTÄTIGT (Nutzer, PuTTY):** In separater Bash-Subshell mit `set -euo pipefail` wurde der vorhandene private Git-Klon mit `runuser -u nicovoss -- git -C "$repo" rev-parse HEAD` erfolgreich gelesen. Tatsächlicher Repo-HEAD exakt `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`. Der vorherige `dubious ownership`-Fehler ist dadurch **ohne globale Safe-Directory-Ausnahme** überwunden.

**FEHLVERSUCH:** `runuser -u nicovoss -- /opt/plesk/php/8.4/bin/php "$(command -v composer)" --working-dir=... install ...` gab den **Text** des Plesk-Shell-Wrappers aus:
```text
# Copyright 1999-2020. Plesk International GmbH. All Rights Reserved.
COMPOSER_BIN="/opt/psa/var/modules/composer/composer.phar"
...
"$COMPOSER_BIN" "$@"
```
**Ursache:** `command -v composer` zeigt auf einen **Shell-Wrapper**, nicht direkt auf ein PHP-PHAR. Wenn `php` den Shell-Wrapper statt des PHAR interpretiert, gibt es dessen Shell-Text aus und **führt Composer nicht aus**. Da anschließend der `vendor/autoload.php`-Existenztest fehlschlug, endete die separate Subshell, und PuTTY blieb offen. **Kein Nachweis einer durchgeführten Composer-Installation oder erstellten Vendor-Ordnerstruktur.**

**KORREKTUR, noch nicht durch den Nutzer ausgeführt:** `/opt/plesk/php/8.4/bin/php /opt/psa/var/modules/composer/composer.phar install --working-dir="$platform" ...` unter `runuser -u nicovoss --`. PHAR zunächst auf Existenz prüfen und Composer-Version mit explizitem PHP-8.4-Binary anzeigen. Danach Composer-Install nur im bestehenden privaten `repo/platform`, ohne neuen Clone, ohne Änderung von Plesk-Domains, MariaDB oder JTL, und als eigene Bash-Subshell. Falls `composer.lock` fehlt, löst `composer install` Pakete neu auf; das für reproduzierbare Builds anschließend prüfen/dokumentieren.

**Bisher abgeschlossen:** Privater Klon mit korrektem HEAD. **Offen:** Composer erfolgreich installiert, `platform/vendor/autoload.php` vorhanden, Package-/Runtime-Tests, private Laravel-Umgebung, Live-V4.25-Webroot-/Browserabnahme und Test-Webroot-Umschaltung.

**Dokumentationspflicht:** Dieser Fehler ist chronologisch protokolliert, nicht verschwiegen oder rückwirkend als Erfolg umgedeutet. Exakt nächste Aktion: PHAR-basierten PHP-8.4-Composer-Aufruf durchführen und Ausgabe vollständig prüfen.


### 22.11.2026 · PHP 8.4 Composer/Filament auf Plesk erfolgreich installiert

**BELEG:** Vollständige Betreiber-PuTTY-Ausgabe (448 Zeilen) des korrigierten Isolationsskripts `HW24_COMPOSER`, nach dem zuvor fehlgeschlagenen Plesk-Shell-Wrapper-Aufruf. Keine Secrets im Protokoll.

| Einzelakt | Bestätigtes Ergebnis | Status |
| --- | --- | --- |
| 22 · Composer-PHAR explizit unter PHP 8.4 prüfen | `Composer 2.11.2`, `PHP 8.4.28`, ausführbar als Site-Benutzer | **BESTÄTIGT, erfolgreich** |
| 23 · Composer-Auflösung im isolierten privaten Laravel-`platform/` | Ohne vorhandene `composer.lock` wurde der Versionssatz neu aufgelöst; Lockdatei auf dem Server geschrieben, **145 Paketversionen gesperrt** | **BESTÄTIGT** |
| 24 · Prod-PHP-Abhängigkeiten installieren | `composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader` hat **112 Pakete** heruntergeladen/extrahiert. Kein Paketinstallationsfehler im Log | **BESTÄTIGT, erfolgreich** |
| 25 · Reale Framework-Versionen prüfen | `laravel/framework v13.43.0`, `filament/filament v5.12.0`, `livewire/livewire v4.4.9` laut Composerauflösung/-Installation | **BESTÄTIGT** |
| 26 · Laravel-Paketerkennung | `@php artisan package:discover` erfolgreich: Filament, Livewire, Laravel-Komponenten und Pakete mit Status `DONE` | **BESTÄTIGT, erfolgreich** |
| 27 · Composer Post-Update-Skript | `artisan vendor:publish --tag=laravel-assets --force`: `No publishable resources`, **kein Fehler** | **BESTÄTIGT** |
| 28 · Composer-Endgate | Das Bash-Skript prüfte `test -f "$platform/vendor/autoload.php"` mit `set -e`, danach tatsächlich `=== INSTALLATION ERFOLGREICH ===`, `Laravel-/Filament-Pakete vorhanden.`, Rückkehr zur PuTTY-Shell | **BESTÄTIGT, erfolgreich** |
| 29 · Auswirkungen auf Legacy-/Datenbankbereich | Dieser Installer rief ausschließlich Composer im privaten `platform/` auf; keine Plesk-Vhost-Änderung oder JTL-Migration im Log erkennbar | **BESTÄTIGT für ausgeführten Block**, keine globale Systemprüfung |
| 30 · Reproduzierbarkeit | `platform/composer.lock` wurde **auf Server erzeugt**, befindet sich nicht automatisch im GitHub-Featurebranch. Vor späteren Deployments Versionsstand reproduzierbar committen, ggf. auf Secret-Freiheit prüfen | **OFFEN: Lockfile noch nicht im Repo** |

**Damit KORRIGIERT gegenüber alten Notizen:** Composer und Laravel/Filament sind **installiert**. **Nicht** fälschlich „Laravel im Browser eingerichtet“ behaupten: Es gibt bislang noch kein privates `.env`-/APP_KEY-/SQLite-Initialisierungsprotokoll, keine bestätigte Administratoranlage, kein gesichertes `/admin`-HTTP-Ergebnis und keine Plesk-Test-Webroot-Umschaltung.

**Exakt nächste technisch sinnvolle Aktion (read-only):** Laravel-/Filament-Artefakte per `php artisan --version` und `php artisan route:list --path=admin` als Site-Benutzer prüfen, vorhandene Live-V4.25-Dateihashes abgleichen. Erst danach ausschließlich Laravel-eigene `.env` und isolierte SQLite-Datenhaltung initialisieren. Legacy V4.25 vor Umschaltung von Test im authentifizierten Browser prüfen. **Kein** erneuter Composer-Install und **kein** Repository-Reclone nötig.

**Dokumentationsregel:** Jede neue PuTTY-Ausgabe sofort chronologisch ergänzen und Kurz-Übergabe/Projektstatus fortschreiben; öffentliches GitHub enthält keine Authentifizierungsschlüssel.


### 22.11.2026 · Laravel-CLI, Filament-Routen, Legacy-Live-Dateihashes und private Datendateien geprüft

**Quelle:** Neue PuTTY-Ausgabe des Betreibers. Eine erneut eingefügte Kopie des identischen HW24_CHECK-Befehls enthält **keine zweite Ausgabebestätigung**; deshalb wird nur der erste abgeschlossene Durchlauf als bestätigt geführt.

| Schritt | Tatsächlich beobachteter Nachweis | Bewertung |
| --- | --- | --- |
| 31 · Laravel CLI-Boot | Als Subscription-Benutzer mit Plesk-PHP 8.4: **Laravel Framework 13.43.0** | **BESTÄTIGT, erfolgreich** |
| 32 · Filament Panel Routing | artisan route:list --path=admin zeigt **4 Routen**: GET/HEAD admin Dashboard, GET/HEAD admin/artikel Artikel, GET/HEAD admin/login Login, POST admin/logout Logout | **BESTÄTIGT, erfolgreich**; noch kein HTTP-/Login-Test |
| 33 · Legacy-V4.25-Live-Webroot-Dateihashes | **OK** für index.html, artikelpflege.html, api/jtl.php und scanliste.html; exakter Abgleich gegen freigegebenen GitHub-Staging-Stand | **BESTÄTIGT, erfolgreich** |
| 34 · Private Laravel-Konfiguration | platform/.env **noch nicht vorhanden** | **BESTÄTIGT, bewusst noch offen** |
| 35 · Private Laravel-SQLite-Datei | platform/database/database.sqlite **noch nicht vorhanden** | **BESTÄTIGT, bewusst noch offen** |
| 36 · Wiederholter Eingabeblock | Ein zweiter identischer Shellblock wurde als Eingabe gepostet, aber ohne nachfolgende Ausgabe; kein zweites erfolgreiches Ergebnis ableiten | **OFFEN/KEIN ZWEITER NACHWEIS**, nicht erneut nötig |

**Aktuelle Sicherheitsgrenzen:** Beide Legacy-Artikelmanager-Domains bleiben weiterhin getrennt; Test-Webroot unverändert; Live-V4.25-Git-Commit und vier Live-Dateihashes bestätigt. Die sechs früheren HTTP-401-Prüfungen belegten Zugriffsschutz ohne Login auf diesen URLs. **Noch offen:** authentifizierter Legacy-Live-Browser-/API-E2E mit JTL, Amazon und OpenAI. Neue Laravel-App nur CLI-seitig startfähig; private .env, App-Key, separate SQLite-Migration, Filament-Admin und HTTP-Veröffentlichung fehlen.

**Genau nächster Schritt:** Ausschließlich im vorhandenen privaten Laravel-Projekt als Site-Benutzer eine .env aus .env.example (APP_ENV=production, APP_DEBUG=false, APP_URL=https://test-artikelmanager.hairworld24.de, LOG_LEVEL=warning, SESSION_SECURE_COOKIE=true), privaten APP_KEY und SQLite-Datei für Laravel-Systemtabellen erstellen; NUR für SQLite php artisan migrate --database=sqlite --force, Filament-Assets lokal publizieren. Kein JTL-/MariaDB-Schreibvorgang, keine Webroot-Änderung, keine Secrets in GitHub oder Chat.

**Danach:** Tatsächlichen SQLite-Migrationsstatus, Session-Konfiguration und Filament-Login als autorisierten User bestätigen, Legacy-Live-Browser-/API-E2E abschließen und erst dann vorhandene Testdomain auf Laravel platform/public umstellen.


### 22.11.2026 · Private Laravel-Erstkonfiguration und SQLite-Migration erfolgreich

**Quelle und Beleg:** Vollständige neue Betreiber-PuTTY-Ausgabe des Blocks `HW24_INIT`. Der am Anfang der Chatnachricht abgeschnittene Text ist nur die eingekopierte Shell-Eingabe; das Terminal bestätigt anschließend den **vollständigen Abschluss** bis `=== HW24 ERSTKONFIGURATION ERFOLGREICH ===`. Alle Schritte fanden in der bestehenden privaten Laravel-App statt; kein Plesk-Testwebroot-Cutover.

| Schritt | Tatsächlicher Befund | Status |
| --- | --- | --- |
| 37 · Sicherheitsgates | Vorheriger Block prüfte PHP-Laravel-Autoload und Nichtvorhandensein einer bisherigen privaten `.env`-/SQLite-Datei sowie SQLite-`.gitignore`. Der folgende Ablauf erreichte die Einrichtung | **BESTÄTIGT, Gates durchlaufen** |
| 38 · Private Laravel-.env | Aus `.env.example` als Site-User mit Modus 0600 angelegt, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://test-artikelmanager.hairworld24.de`, `LOG_LEVEL=warning`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` | **BESTÄTIGT durch Skript-Erfolg**; Werte nicht erneut ausgelesen |
| 39 · Neuer privater Laravel-Anwendungsschlüssel | `artisan key:generate --force --no-interaction` meldete `Application key set successfully` | **BESTÄTIGT**; Schlüssel **nicht** im GitHub/Chat dokumentieren |
| 40 · Private Laravel-SQLite-Datenbank | `database/database.sqlite` als Site-User mit Modus 0600 erstellt; keine bestehende JTL-/MariaDB-Datenbank als Ziel | **BESTÄTIGT** |
| 41 · Konfigurationscache | `artisan config:clear` meldete `Configuration cache cleared successfully` | **BESTÄTIGT** |
| 42 · SQLite-Systemmigration vorbereiten | `artisan migrate --database=sqlite --force --no-interaction`, Migrationstabelle erfolgreich erstellt | **BESTÄTIGT** |
| 43 · Laravel-Systemtabellen | `0001_01_01_000000_create_users_table` [1] Ran | **BESTÄTIGT** |
| 44 · Laravel Cache | `0001_01_01_000001_create_cache_table` [1] Ran | **BESTÄTIGT** |
| 45 · Laravel Queues/Jobs | `0001_01_01_000002_create_jobs_table` [1] Ran | **BESTÄTIGT** |
| 46 · Preisquellen-Beobachtungen | `2026_11_22_000001_create_channel_price_observations_table` [1] Ran | **BESTÄTIGT**, nur **leere interne Plattformtabelle**, keine JTL-Preismigration |
| 47 · Filament-Assets | `artisan filament:assets` publizierte JS, CSS und Font-Assets, meldete `Successfully published assets!` | **BESTÄTIGT** |
| 48 · SQLite-Migrationsstatus | Vier Migrationen in Batch [1], jeweils `Ran` | **BESTÄTIGT, erfolgreich** |
| 49 · Skriptabschluss / Isolation | `=== HW24 ERSTKONFIGURATION ERFOLGREICH ===`, `Testdomain und JTL-Datenbank unverändert`, Rückkehr an Root-PuTTY-Prompt | **BESTÄTIGT für ausgeführten Befehl** |

**Status nach dieser Phase:** Laravel 13.43.0/Filament 5.12.0 mit erfolgreichem CLI-Boot, vier Admin-Routen, private .env/APP_KEY/SQLite und Filament-Assets. Legacy-V4.25 auf Live hat erwarteten Plesk-Branch/Commit und vier Datei-BLOB-Hashes. Neue Laravel-App **noch nicht im Browser veröffentlicht**. 

**Wichtige offene Punkte, nicht als erledigt melden:** Filament-Administrator muss erst erstellt und seine E-Mail in privater `HW24_ADMIN_EMAIL`-Allowlist hinterlegt werden; private HTTP-Basic-Credentials für GET-only Live-Legacy-Bridge noch nicht eingerichtet; Authentifizierter Browser-/API-E2E auf Legacy-Live steht aus; Plesk-Test-Dokumentenstamm ist unverändert; serverlokal erzeugtes Composer-Lockfile noch nicht versioniert.

**Exakt nächster Schritt:** Ohne Offenlegung von E-Mail/Passwort im Chat **interaktiv** nur im privaten Laravel-Projekt `HW24_ADMIN_EMAIL` setzen und `artisan make:filament-user` als Site-User starten, Nutzerpasswort verdeckt am PuTTY-Terminal eingeben. Nur erlaubter User soll das Panel betreten. Nicht `env` oder `APP_KEY` ausgeben, keine lokale/öffentliche Datenbankreset-Migration, keine Veröffentlichung des `platform/`-Gesamtverzeichnisses. Danach verantwortungsvoll die vorhandene Legacy-Live-Domain angemeldet im Browser abnehmen; Testdomain erst danach auf `platform/public` umschalten.

**Chronologie-Regel:** Jeder nächste Terminal-/Browser-Befund und jede Anpassung an GitHub-Featurebranch oder Infrastruktur wird unmittelbar hier als eigener Folgeeintrag dokumentiert.


### 22.11.2026 · Erster Filament-Administrator erfolgreich erstellt

**Quelle:** Vom Betreiber vollständig bereitgestellte PuTTY-Ausgabe des interaktiven Blocks zur Admin-Erstellung nach zuvor erfolgreich installierter Laravel-Anwendung und separater SQLite-Migration.

| Schritt | Nachgewiesener Vorgang | Ergebnis |
| --- | --- | --- |
| 50 · Konfigurations-/DB-Gates | Vorhandensein privater `.env`, privater SQLite und Composer-Autoload bestätigt; sonst wäre das Skript mit `set -e` abgebrochen | **BESTÄTIGT** |
| 51 · Admin-E-Mail eingegeben | Admin-E-Mail wurde interaktiv eingegeben und gegen ein einfaches E-Mail-Syntaxmuster geprüft | **BESTÄTIGT**; persönliche E-Mail ausdrücklich **nicht** im öffentlichen Repository protokolliert |
| 52 · Filament-Zugriffsfreigabe | `HW24_ADMIN_EMAIL` in privater Laravel-`.env` gesetzt; Ausgabe `Private Admin-Freigabe gespeichert.` | **BESTÄTIGT**; keine Secrets oder private `.env` gesichert |
| 53 · Laravel Konfigurationscache | `artisan config:clear` meldete `Configuration cache cleared successfully.` | **BESTÄTIGT** |
| 54 · Neuer Filament-Benutzer | `artisan make:filament-user` interaktiv ausgeführt, Benutzername und gleiche E-Mail eingegeben, Passwort verdeckt erfasst; Filament meldete `Success! ... may now log in at https://test-artikelmanager.hairworld24.de/admin/login.` | **BESTÄTIGT, SQLite-Benutzeranlage erfolgreich** |
| 55 · Skriptabschluss | `=== ADMINISTRATOR EINRICHTUNG ABGESCHLOSSEN ===` und Rückkehr zum Root-PuTTY-Prompt | **BESTÄTIGT** |

**Datenschutz:** Persönliche Administrator-E-Mail sowie geheimes Passwort wurden **nicht** in die öffentliche Dokumentation übernommen; nur das vorhandene interne Konfigurationsmerkmal `HW24_ADMIN_EMAIL` und die erfolgreiche Benutzeranlage. Der Server hat die Zugangsdaten lokal erhalten.

**Wichtige Statusgrenze:** Die Filament-Meldung enthält die später gültige HTTPS-Login-URL, aber die bestehende Testdomain wurde bisher **nicht** auf Laravel umgeschaltet. Deshalb ist die Erreichbarkeit des Filament-Logins **NICHT durch HTTP oder Browser verifiziert**. Die Berechtigungsmatrix wird erst im HTTP-Test geprüft. Die Live-Legacy-V4.25-Dateihashes sind bestätigt, aber JTL/Amazon/OpenAI im angemeldeten Live-Browser noch nicht vollständig abgenommen.

**Exakt nächster Schritt:** Read-only Auth-Gate der neuen privaten SQLite-Anwendung ohne Ausgabe der Admin-E-Mail: Benutzerzahl, Übereinstimmung `users.email` mit `HW24_ADMIN_EMAIL` und Panel-Login-Routen, danach den vorhandenen Legacy-Live-Artikelmanager **angemeldet** mit Referenz-EAN und API-Funktionen im Browser testen. Vor dem Umschalten der Testdomain Plesk-DocumentRoot/PHP-8.4-FPM und die bestehende manuelle Plesk-Git-Deploymentkopplung absichern, sonst könnte die neue Laravel-App ungewollt überschrieben bzw. privat gespeicherter Code öffentlich werden. **Noch keine Domainumstellung, kein JTL-Write, keine API-Secret-Übernahme.**

**Pflicht:** Erfolg, weitere Tests und auch Fehler weiterhin sofort in GitHub festhalten.


### 22.11.2026 · Filament-Administrator und Panel-Allowlist erfolgreich geprüft

**BESTÄTIGT durch neue PuTTY-Ausgabe**, nach der vorangegangenen erfolgreichen interaktiven Filament-Adminanlage. Kommando lief als Site-Benutzer über Plesk PHP 8.4 und Laravel Tinker im privaten Framework-`platform`:

| Schritt | Prüfkriterium | Tatsächliche Ausgabe | Status |
| --- | --- | --- | --- |
| 56 · Laravel-Admin in eigener SQLite | Benutzer per `App\Models\User::query()->where("email", config("hw24_legacy.admin_email"))->first()` gegen private Allowlist gesucht | `Administrator vorhanden: OK` | **BESTÄTIGT** |
| 57 · Filament-Panel-Berechtigung | `Filament::getPanel("admin")` und `$user->canAccessPanel($panel)` | `Filament-Zugriff freigegeben: OK` | **BESTÄTIGT** |
| 58 · Abschluss Read-only Adminprüfung | `=== PRÜFUNG ABGESCHLOSSEN ===`; Rückkehr an PuTTY-Prompt | **BESTÄTIGT** |

**Datenschutz:** Das persönliche Admin-E-Mail-Postfach wird bewusst nicht im öffentlichen Repository veröffentlicht; nur Boolesche Ergebnisse werden protokolliert. Der passwortbasierte Browserlogin selbst ist **noch nicht getestet**. Beide Domain-Webroots wurden durch diese Laravel-Tinker-Abfrage nicht verändert.

**Bereits bestätigt:** Laravel 13.43.0/Filament 5.12.0, vier Admin-Routen, private produktionskonfigurierte `.env` und APP_KEY, 4 ausgeführte SQLite-Migrationen, Filament-Assets sowie erfolgreiche Benutzeranlage und Panel-Allowlist. Legacy-Live-Artikelmanager auf `deploy/staging` V4.25 und vier kritische Webroot-Datei-Hashes OK, anonymer HTTP-Zugriff auf die geprüften Endpunkte 401. **Noch offen:** authentifizierter E2E-Live-Legacy-Test (JTL-Artikel EAN, Amazon-/KI-Lesewege) und getrennte Plesk-Test-vHost-Zielkonfiguration/DocumentRoot/PHP-Handler, danach erst sicherer Testdomain-Cutover.

**Exakt nächste Aktion:** Live-Legacy-Artikelpflege im angemeldeten Browser mit Referenz-EAN öffnen und tatsächliche JTL-Artikelanzeige, Amazon-Katalog manuell sowie OpenAI-Archiv kostenlos testen. Parallel vor jeder Änderung Plesk-Testdomain-DocumentRoot, PHP-Handler und alte Git-Deploy-Zielverzeichnisse **read-only** prüfen. Keine weiteren SQLite-Migrationen/Neuanlage von Admin, keine neue Domain/DB und keine Credentials ausgeben oder committen.


### 22.11.2026 · Plesk-Testdomain: DocumentRoot, PHP-FPM, Git-Verknüpfung, Dateirechte geprüft

**BESTÄTIGT durch neue Betreiber-PuTTY-Ausgabe des ausschließlich lesenden Blocks `HW24_PLESK_CHECK`.**

| Schritt | Auslesung (keine Secrets) | Bestätigter Ist-Zustand |
| --- | --- | --- |
| 59 · Domain-Hostingkonfiguration | Plesk `hosting.www_root` für vorhandene Testdomain | `/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de` (**alter Legacy-Webroot**) |
| 60 · Test-Web-PHP-Handler | Plesk `hosting.php_handler_id` | `plesk-php85-fpm` (**PHP 8.5 im Web; bisher PHP 8.4 in CLI und Composer verwendet**) |
| 61 · Plesk-Git-Anbindung | `plesk ext git --info -domain test-artikelmanager.hairworld24.de -name HW24-scanner.git` | `Deployment path: /test-artikelmanager.HAIRWORLD24.de`, `Deployment mode: manual`, `Active branch: deploy/staging` |
| 62 · Private Laravel-Umgebungsdatei | `stat` ohne Dateiinhalt | `600`, Eigentümer: Site-Benutzer, Gruppe: Plesk-Webspacegruppe; **Zugangsdaten nicht ausgegeben** |
| 63 · Private SQLite | `stat` ohne Dateiinhalt | `600`, Eigentümer: Site-Benutzer, Gruppe: Plesk-Webspacegruppe |
| 64 · Laravel public/index.php | `stat` ohne Dateiinhalt | `600`, Eigentümer: Site-Benutzer, Gruppe: Plesk-Webspacegruppe; FPM-Lesbarkeit und Webserver-Pfadrechte noch nicht verifiziert |
| 65 · Ende des Read-only-Audits | `PRUEFUNG ABGESCHLOSSEN`, Root-PuTTY-Prompt | **ERFOLGREICH**, keine Konfigurationsänderung vorgenommen |

**Technische Bewertung:** Die Testdomain läuft **weiterhin auf dem Legacy-Webroot** und die bisherige manuelle Plesk-Git-Deployment-Zielstruktur ist weiterhin mit ihm gekoppelt. Die Laravel-App ist privat außerhalb dieses Webroots installiert. Die Abweichung zwischen `plesk-php85-fpm` (Test-Web) und `/opt/plesk/php/8.4/bin/php` (Composer/Artisan) muss vor dem ersten HTTP-Deploy bewusst behandelt werden. PHP-8.5-Kompatibilität mit der konkret gelockten Composer-Abhängigkeitsmenge und die nötigen PHP-Module/Dateisystemrechte im FPM-Kontext sind **noch nicht** geprüft. Dass `index.php` Modus 0600 hat, beweist nicht automatisch einen Fehler: PHP-FPM könnte als Dateieigentümer laufen; Webserver-Pfadzugriff auf das tatsächlich neue öffentliche Verzeichnis ist gesondert zu prüfen.

**Wichtige Sperren:** Kein Plesk-DocumentRoot-/PHP-Handler-Wechsel, kein Unlink/Rewrite des bisherigen Plesk-Git-Repos, kein Symlink, kein Kopieren des gesamten privaten `platform/`-Verzeichnisses in den öffentlich zugänglichen Alt-Webroot, keine DB-/JTL-/API-Schreiboperation. Keine dritte Subdomain und keine neue MariaDB. Kein Rollout vor verifizierter Rückfallfähigkeit.

**Nächster Schritt:** Read-only Prüfbefehle für Test-PHP-FPM-Benutzer und PHP-Version/-Erweiterungen, private Verzeichnis-Traversierung und mögliche DocumentRoot-Zielpfade; dazu auf der Legacy-Live-Domain angemeldeten JTL-/Amazon-/KI-Funktionstest bestätigen. Erst danach **separaten** rollback-fähigen Testdomain-Cutoverplan mit PHP-8.4-Handler und eindeutig nur `platform/public` als Webroot ausführen.

**Aktueller Gesamtfortschritt:** Laravel/Filament und alle vier SQLite-Migrationen installiert; Admin-Account und `canAccessPanel` bestätigt; Live-V4.25-Git-SHA und vier Datei-Hashes bestätigt; Test-Web-Plesk-Konfiguration jetzt read-only inventarisiert. Laravel-Login via Browser und Legacy-API-E2E noch nicht bestätigt.


### 22.11.2026 · Vorabprüfung für Filament-Testdomain schriftlich vorbereitet

- **VERIFIZIERT GitHub:** [Read-only Preflight-Rolloutanleitung](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) in `main` angelegt, Commit `dd8410488564defcfae8a8a74769700aec2c9711`.
- **Warum:** Test-vHost verwendet Plesk PHP-8.5-FPM; installierte PHP-Pakete wurden über PHP 8.4 aufgelöst; Laravel 13 unterstützt laut offiziellen Release Notes PHP 8.3 bis 8.5, aber exakte PHP-8.5-Composer-Plattformanforderungen und FPM-/Dateipfad-/Asset-Rechte noch nicht verifiziert. `public/index.php` ist derzeit 0600, was im Webkontext erst überprüft werden muss. Plesk-Git deployt manuell in den alten Legacy-Test-Webroot.
- **Prüfinhalt:** PHP-8.5-Version und Composer-`check-platform-reqs`, Laravel-Version und Filament-Routen unter PHP 8.5 als Site-User, `namei -l` auf Laravel-public/index.php und Filament-CSS; **nur lesen**, kein Domain-/Datenbankwechsel.
- **OFFEN, nicht als erledigt werten:** Betreiber muss den Preflight in PuTTY ausführen und das Ergebnis senden; angemeldeter Legacy-Live-JTL/Amazon/KI-Funktionstest ist weiterhin Voraussetzung vor der Testdomain-Umstellung.
- **Keine** eigenen Serveraktionen durch GitHub-Dokumentationsarbeit. Kein API-Token/APP_KEY/Passwort offen gelegt.


### 22.11.2026 · PHP-8.5-Plesk-Preflight erfolgreich, Webserver-Asset-Rechte als Cutover-Gate erkannt

**Quelle:** Vollständige neue PuTTY-Ausgabe des vom Betreiber ausgeführten rein lesenden `HW24_PREFLIGHT`-Blocks; keine neue Plesk-Konfiguration, kein JTL-Schreibzugriff.

| Schritt | Konkreter Nachweis | Status |
| --- | --- | --- |
| 66 · PHP-8.5-CLI | `PHP 8.5.13 (cli)` aus Plesk `/opt/plesk/php/8.5/bin/php` unter Site-User | **BESTÄTIGT** |
| 67 · Composer-Plattform unter 8.5 | `composer.phar check-platform-reqs --no-dev` für derzeit installierte `vendor`-Pakete zeigte für PHP 8.5.13, composer-runtime-api und alle geprüften Pflicht-Extensions **success**, teils via Symfony-Polyfills | **BESTÄTIGT, keine Plattformverletzung** |
| 68 · Laravel-Boot mit 8.5 | `artisan --version` meldete `Laravel Framework 13.43.0` | **BESTÄTIGT** |
| 69 · Filament-Panel-Routen mit 8.5 | `artisan route:list --path=admin`: vier erwartete GET/POST-Routen `admin`, `admin/artikel`, `admin/login`, `admin/logout` | **BESTÄTIGT** |
| 70 · Laravel-public/index.php-Pfadrechte | `namei -l`: Subscription-Pfad `drwx--x---` mit Plesk-Servergruppe; private App `drwxr-x---`; `repo`, `platform`, `public` jeweils **0700** des Site-Users; `public/index.php` **0600** des Site-Users | **BESTÄTIGT**, tatsächlicher FPM-Webserver-Zugriff noch OFFEN |
| 71 · CSS-Asset-Pfadrechte | `namei -l`: sämtliche `public/css/filament/filament`-Zwischenverzeichnisse **0700**, `app.css` **0600**, Eigentümer Site-User | **BESTÄTIGT; statische Nginx-/Apache-Auslieferung wahrscheinlich blockiert**, sofern Worker nicht Site-User ist |
| 72 · Read-only Preflight beendet | `=== PREFLIGHT ABGESCHLOSSEN ===`, PuTTY-Prompt wieder erschienen | **BESTÄTIGT** |

**Bewertung:** PHP-Versionen und installierte Laravel-/Filament-Pakete sind unter PHP 8.5 gemäß Paketplattform und CLI kompatibel; **kein vorschneller PHP-Handlerwechsel**. CLI-Erfolg beweist noch keinen erfolgreichen tatsächlichen PHP-8.5-FPM-/Browser-Test. Private `.env` und SQLite mit **0600 müssen privat bleiben**; keine globale Rechtefreigabe und niemals `chmod -R 755` auf die Anwendung. Statische CSS-/JS-/Font-Dateien müssen später vom Webserver gelesen werden können, aber `repo` und `platform` sind aktuell nur für den Site-User begehbar. Es gibt noch keine bestätigte Nginx-/Apache-Worker-Identität oder Plesk-FPM-User-Identität für den Vhost; erst diese und ACL-Wirksamkeit lesen, dann gezielte Rechte für public-Assets und nötige Traverse-Berechtigungen in einem gesonderten Cutover planen.

**Offene verpflichtende Vorabnahme:** Die Legacy-V4.25-App auf Live wurde auf Git-/Dateiebene bestätigt; authentifizierter Browser-E2E mit JTL/Amazon/OpenAI fehlt weiterhin. Git-Deploy der bestehenden Testdomain zeigt weiterhin auf den alten Webroot (`deploy/staging`, manuell). Kein DocumentRoot-/PHP-FPM-/Git-Schalter wurde ausgeführt.

**Exakt nächster Schritt:** Ausschließlich read-only (a) effektive Nginx-/Apache-Worker-Nutzergruppe und PHP-FPM-vHost-Betriebsbenutzer ermitteln, (b) Lesbarkeit von `platform/public/index.php` und CSS unter Webserveridentität mit `runuser ... -- test -r` prüfen, (c) ACL-/Mode-Vererbung und das bestehende Plesk-Git-Deployziel erfassen. Keine Secret-Dateien oder deren Inhalt auslesen, keine pauschalen Berechtigungsänderungen. Parallel Legacy-Live angemeldet testen; erst dann einen reversiblen Testdomain-Cutover durchführen.

**Dokumentationsregel:** Jeder einzelne Folgebefund in GitHub fortschreiben; persönlichen Admin, Access-Tokens und private Laravel-`.env` nicht veröffentlichen.


### 22.11.2026 · GitHub-Runbook und Kurz-Übergabe nach erfolgreich ausgeführtem PHP-8.5-Preflight nachgeführt

- GitHub-`main` [`HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md`](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) um **tatsächlich bestätigte** PHP-8.5.13-Kompatibilität, vier Admin-Routen und die beobachteten 0700/0600-Pfadrechte ergänzt. Commit `f4b3dc47d82e5d431a67e211f9d67a8e28b41ea7`.
- Neuer, **noch nicht ausgeführter** read-only `HW24_WEB_ACCESS`-Block prüft aktive Nginx-/Apache-/FPM-Worker-Benutzer sowie effektive Lesbarkeit ausgewählter öffentlicher CSS-/JS-Dateien als App-User und www-data, ohne Geheimnisse auszulesen und ohne Datei-/Webserver-/Domain-/DB-Änderung.
- [Kurz-Übergabe](./HW24_HANDOVER.md) und [Projektstatus](./HW24_PROJEKTSTAND_AKTUELL.md) an die Spitze auf diesen tatsächlich zuletzt bestätigten Zustand aktualisiert. **Exakt nächster Betreiber-Schritt:** nur diesen Worker-/Rechtecheck ausführen, daneben alten Live-V4.25-JTL-/Amazon-/KI-Browser-E2E bestätigen, dann gezielte Asset-Berechtigungen/Rollback planen.
