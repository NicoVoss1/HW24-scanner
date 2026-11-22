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


### 22.11.2026 · Webserver-Asset-Leseprobe durchgeführt: nginx und www-data ausgesperrt

**BESTÄTIGT durch PuTTY-Ausgabe des rein lesenden `HW24_WEB_ACCESS`-Blocks.**

| Einzelschritt | Tatsächliche Ausgabe | Bewertung |
| --- | --- | --- |
| 73 · Webserver-Prozesse ermitteln | Aktive Worker-Benutzer `nginx` und `www-data` | **BESTÄTIGT** |
| 74 · PHP-FPM-Prozesse ermitteln | Unter den sichtbaren `php-fpm: pool`-Prozessen erscheint `www-data`; **noch kein Zuordnungsnachweis zum konkreten Test-vHost** | **BESTÄTIGT / Zuordnung OFFEN** |
| 75 · Lesbarkeit Front-Controller | `public/index.php`: `nicovoss: LESBAR`, `nginx: GESPERRT`, `www-data: GESPERRT` | **BESTÄTIGT** |
| 76 · Lesbarkeit Stylesheet | `public/css/filament/filament/app.css`: `nicovoss: LESBAR`, `nginx: GESPERRT`, `www-data: GESPERRT` | **BESTÄTIGT** |
| 77 · Lesbarkeit JavaScript | `public/js/filament/filament/app.js`: `nicovoss: LESBAR`, `nginx: GESPERRT`, `www-data: GESPERRT` | **BESTÄTIGT** |
| 78 · ACL-Werkzeuge erkennen | `command -v getfacl` / `command -v setfacl` gaben **keinen Pfad** zurück | **BESTÄTIGT**, ACL-Werkzeuge im aktuellen PATH nicht gefunden, keine Aussage über Kernel-/Dateisystem-ACL-Unterstützung |
| 79 · Prüfausführung | `=== PRUEFUNG ABGESCHLOSSEN ===`, zurück im PuTTY-Prompt | **BESTÄTIGT, keine Änderungen** |

**Technische Ursache:** Wie im vorigen `namei -l`-Check beobachtet, sind `repo/`, `platform/`, `public/` und CSS-/JS-Unterverzeichnisse auf 0700 sowie öffentliche Assets auf 0600 für `nicovoss` gesetzt. Andere Webserverprozess-Nutzer können die Dateien über diesen Pfad derzeit nicht lesen. **Dieses Ergebnis ist nicht gleichbedeutend mit einem Fehlschlag von Laravel oder Composer.** Die konkret zugehörige PHP-8.5-FPM-Poolidentität für die Testdomain ist noch nicht unabhängig bestätigt; die Prozessübersicht nennt nur `www-data`, möglicherweise nicht den vHost-Worker.

**Sicherheitsanforderung:** `platform/.env` und private SQLite bleiben 0600. Niemals `chmod -R 755` auf den privaten Repository-/Laravel-Baum, nie `777` und kein generelles Gruppenzugriffsrecht für sämtliche privaten Konfigurations-/Source-Dateien. Eine pauschale Rechteänderung würde zudem den schon erfolgreichen Schutz der APP_KEY-/Benutzerdaten gefährden.

**Nächster Schritt:** **Nur lesende** Zuordnung der Testdomain zum Plesk-FPM-Pool (Pool-Prozess-/Konfigurationsmetadaten ohne Secrets), der Unix-Gruppen von `nginx`, `www-data`, `nicovoss`, sowie effektiver `test -x/-r`-Zugriffe. Erst dann eine **eng begrenzte** Berechtigungs-/Trennungsstrategie für `platform/public` und das im PHP-FPM benötigte private Laravel-Backend entwerfen. Vor jeglichem Domain-Cutover außerdem **angemeldeten Live-V4.25-JTL-/Amazon-/OpenAI-Browser-E2E** nachweisen und Legacy-Testdomain-Git-Ziel/rollback-sicheres Bereitstellungsverfahren bestimmen.

**Noch NICHT** Plesk-DocumentRoot geändert, PHP-FPM-Handler umgestellt, PHP- oder Webserver-Rechte verändert, Admin neu angelegt oder Migrationen wiederholt. Live/Legacy bleiben unverändert.


### 22.11.2026 · Dokumentations- und Preflight-Nachbereitung nach Webserver-Rechteprüfung

- **GitHub-Ereignis:** Nach Betreiber-Nachweis der verweigerten nginx-/www-data-Asset-Leserechte ist der ausführliche Einzeleintrag in diesem Journal gespeichert, Commit `caf206b3ceef57a2c5bbb0d633dcf00bca4ea4d3`.
- **Runbook:** [HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) dokumentiert den Befund und enthält einen **noch nicht ausgeführten** read-only `HW24_POOL_READ`-Block für Plesk-FPM-Poolidentität, Unix-Gruppen und ausgewählte Rechte-Metadaten. Commit `867e34d5f860ce637e7a91bc846413767731b605`.
- **Cross-Chat-Übergabe:** [HW24_HANDOVER.md](./HW24_HANDOVER.md) auf letzten Stand gebracht, Commit `dc6a94865d4d9c15a9f6b845bad9e60c3e485f01`.
- **Projektstatus:** [HW24_PROJEKTSTAND_AKTUELL.md](./HW24_PROJEKTSTAND_AKTUELL.md) aktualisiert, Commit `ab23ade6fa1ee63591c1d61ad51b29b65812adcf`.
- **Nicht ausgeführt:** Plesk-Konfiguration, chmod/ACL, Git-Deploy, Webroot-Verlagerung, PHP-FPM-Neustart, API-Zugriff oder Datenbankmigration.
- **Nächster echter Betreiber-Schritt:** `HW24_POOL_READ` ausführen und Terminalausgabe übermitteln; außerdem authentifizierten Legacy-Live JTL/Amazon/KI-Test bestätigen. Danach präzisen Sicherheits- und Rollbackplan für Asset-Zugriff und Test-Webroot prüfen.


### 22.11.2026 · PHP-8.5-FPM-Pool und Unix-Gruppen für Testdomain eindeutig identifiziert

**Quelle:** Neue vollständig abgeschlossene Betreiber-PuTTY-Ausgabe des rein lesenden Prüfblocks `HW24_POOL_READ`.

| Einzelschritt | Tatsächliche Beobachtung | Status |
| --- | --- | --- |
| 80 · Site-Benutzer | `nicovoss`: UID 10003, Hauptgruppe `psacln` (GID 1003), keine weitere Ergänzungsgruppe in Ausgabe | **BESTÄTIGT** |
| 81 · Nginx-Prozessnutzer | `nginx`: UID 991, Gruppen `nginx` (996) und `psaserv` (1002) | **BESTÄTIGT** |
| 82 · Apache-Prozessnutzer | `www-data`: UID 33, Gruppen `www-data` (33) und `psaserv` (1002) | **BESTÄTIGT** |
| 83 · Plesk-Gruppen | `psacln` GID 1003, `psaserv` GID 1002; Nginx/Apache gehören `psaserv` an, **nicht** `psacln` | **BESTÄTIGT** |
| 84 · Test-vHost-FPM-Pool | Konkret gefundene Testdomain-Pooldatei; nur `user=nicovoss`, `group=psacln`, `listen.owner=root`, `listen.group=psaserv`, `listen.mode=0660` gelesen | **BESTÄTIGT** |
| 85 · Private Verzeichnisrechte | `platform`, `platform/public`, `platform/public/css` je **0700 nicovoss:psacln** | **BESTÄTIGT** |
| 86 · Öffentlich vorgesehenes CSS | `platform/public/css/filament/filament/app.css` **0600 nicovoss:psacln** | **BESTÄTIGT** |
| 87 · Private Anwendungsdaten | `platform/.env` und `platform/database/database.sqlite` beide **0600 nicovoss:psacln** | **BESTÄTIGT, nicht inhaltlich ausgelesen** |
| 88 · Prüfabschluss | `=== PRUEFUNG ABGESCHLOSSEN ===`, Rückkehr zum PuTTY-Prompt | **BESTÄTIGT, read-only** |

**Technischer Befund:** Der Testdomain-PHP-8.5-FPM-Prozess wird explizit als **App-Eigentümer `nicovoss`** mit Gruppe `psacln` ausgeführt. Deshalb kann der PHP-FPM-Prozess die private Laravel-`.env` und SQLite-Datei weiterhin lesen, obwohl beide 0600 sind; es besteht kein Grund, diese Dateien zu öffnen. Der FPM-Unix-Socket ist für Gruppe `psaserv` (Modus 0660) freigegeben, zu der nginx und Apache gehören. Die eigentliche Sperre betrifft **statische** Laravel-`public/css`, `public/js`, `public/fonts` und die nötige Verzeichnisdurchquerung durch `nginx` / `www-data`. Diese Prozesse gehören nicht zur Eigentümergruppe `psacln`; bislang waren für sie `index.php`, `app.css` und `app.js` **GESPERRT**.

**Bevorzugter sicherer Weg:** Gezielt nur Nginx-/Apache-Worker mit minimalen **POSIX-ACL-Leserechten für öffentliche CSS/JS/Fonts** und Traverserechten auf die erforderlichen privaten Elternverzeichnisse versehen. **Keine** pauschale Rekursion über gesamtes `platform/`, kein `chmod -R 755`, keine Freigabe von `platform/.env`, `database.sqlite`, `storage`, `vendor` oder privatem Anwendungscode. Für die ACL-Werkzeuge wurden im vorigen Check keine Executables im PATH gefunden; Paketverfügbarkeit/Dateisystemunterstützung vor späterer gezielter ACL-Änderung prüfen. ACL-Änderungen erfordern Rollback-Dokumentation.

**Gegenstand dieses Schritts:** Nur Diagnose, noch **keine** ACL-/chmod-/Plesk-DocumentRoot-/Git-/PHP-FPM-Veränderung. Die alte Live-V4.25-Anwendung muss vor Umstellung der Testdomain noch im Browser mit Anmeldung und JTL-/Amazon-/KI-Funktionen geprüft werden. Die Plesk-Git-Altbereitstellung auf Test bleibt manuell auf Legacy-Webroot.

**Exakt nächster Schritt:** (1) Read-only Paket-/Dateisystemprüfung `acl`, `findmnt`, `getfacl/setfacl`, plus Übersicht vorhandener `public/css`, `public/js`, `public/fonts`-Verzeichnisse; (2) erst dann gesonderten, klein begrenzten ACL-Freigabe- und Rollback-Befehl für ausschließlich öffentliche Assets bereitstellen. Angemeldeten Legacy-Live-Browser-E2E parallel nachweisen.


### 22.11.2026 · ACL-Tool-Vorbereitung als nächster einziger Betreiberbefehl dokumentiert

- **GitHub-Dokumentation:** [`HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md`](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) um die bestätigten FPM-Test-vHost-Identitäten, statische Datei-ACL-Strategie, Rollback-Anforderung und Installationsprüfung des Ubuntu-Pakets `acl` ergänzt. Runbook-Commit `0be23d499be097fe5583391fe49a31140ee64878`.
- **Extern bestätigt:** Ubuntu-22.04-Standardpaket `acl` stellt die Dienstprogramme `getfacl` und `setfacl` bereit (Ubuntu-Paketdateiliste). Diese ermöglichen eng begrenzte individuelle Webserver-Asset-Freigaben statt grobem `chmod -R 755` auf das private Laravel-Repository.
- **Nächster Betreiber-Schritt, noch NICHT ausgeführt:** `HW24_ACL_TOOLS` prüft vorhandene ACL-Werkzeuge, installiert nur falls sie fehlen `acl` mit Ubuntu apt, zeigt `findmnt`-Dateisystemtyp/-Mount-Optionen; **ändert keine vorhandenen Laravel-Zugriffsrechte**. Dies ist eine transparente Systempaketinstallation, kein automatischer Deploy.
- **Danach, nur nach erneutem Lognachweis:** root-private Sicherung bestehender ACLs, dann minimale Durchquerungs-ACLs der benötigten übergeordneten privaten Verzeichnisse und Lese-ACLs ausschließlich für `platform/public/css`, `public/js`, `public/fonts`, anschließend als nginx/www-data echte Lesbarkeit testen. **Keine ACL-Lesefreigabe auf `.env`, SQLite, App-Code.**
- **Weiterhin unerledigt:** angemeldeter Legacy-Live-End-to-End-Test und vollständig dokumentiertes Plesk-Git-/DocumentRoot-Rollback. Weder Testdomain noch Live bereits geändert.


### 22.11.2026 · Ubuntu-ACL-Werkzeuge installiert; ext4 und Kernel-Wartungshinweis geprüft

**BESTÄTIGT durch vollständige Nutzer-PuTTY-Ausgabe des Blocks `HW24_ACL_TOOLS`.**

| Einzelschritt | Tatsächlich beobachtete Ausgabe | Status |
| --- | --- | --- |
| 89 · Werkzeug-Verfügbarkeit vorher | Skript nahm den Installationszweig, weil `getfacl` und `setfacl` zuvor nicht auffindbar waren | **BESTÄTIGT** |
| 90 · Paketinstallation | `apt-get install -y --no-install-recommends acl`, Ubuntu Jammy-Paket `acl 2.3.1-1`, 38,5 kB Download, +205 kB Platte, 1 Paket installiert, 0 aktualisiert, 0 entfernt | **BESTÄTIGT, erfolgreich** |
| 91 · ACL-Werkzeuge nach Installation | `/usr/bin/getfacl` und `/usr/bin/setfacl` | **BESTÄTIGT, vorhanden** |
| 92 · Dateisystem | `findmnt -T`: ext4 auf `/`, Optionen `rw,relatime,discard,errors=remount-ro` | **BESTÄTIGT**; tatsächliches Setzen der ACL noch nicht getestet |
| 93 · needrestart / Kernel-Stand | `Pending kernel upgrade!` laufend `5.15.0-202-generic`, verfügbar/erwartet `5.15.0-204-generic`. Mehrere Dienste zum späteren Neustart vorgemerkt/deferred | **BESTÄTIGT als Wartungshinweis**; kein automatischer Gesamtreboot im Log |
| 94 · Abschluss | `=== ACL-VORBEREITUNG ABGESCHLOSSEN ===`, Root-Prompt erneut angezeigt | **BESTÄTIGT** |

**Bewertung:** Das ACL-Toolpaket ist einsatzbereit; keine Laravel-Dateirechte, PHP-Handler, Git-Deployment-Ziele oder Datenbanken wurden durch den gezeigten Installationsblock geändert. Das Ubuntu-Paket meldete zusätzlich **bereits ausstehende** Kernel-/Service-Restarts; diese sind eine **separate** geplante Serverwartung, nicht für das aktuelle HW24-ACL-Vorhaben nötig. **Keinen `reboot`, kein `apt autoremove` und keine pauschalen Service-Restarts** ohne Wartungsfenster und Berücksichtigung der sonstigen Plesk-Websites/Dienste ausführen.

**Sicherheitsregel:** Es werden ausschließlich **statische Dateien** unter `platform/public/css`, `platform/public/js`, `platform/public/fonts` für `nginx`/`www-data` durch gezielte ACLs lesbar gemacht; auf den zwingenden Elternverzeichnissen nur Durchquerung `--x`. **Keine Lese-ACL** auf `platform/public/index.php`, `platform/.env`, `database.sqlite` oder auf privaten Laravel-Quellcode. Vor der Änderung ist eine root-private `getfacl`-Sicherung zu erstellen, bei Fehlern sofort mit `setfacl --restore` rückgängig zu machen. Schreibrechte der beiden Webserverbenutzer ausdrücklich nicht vergeben. Erst danach per `runuser -u nginx/www-data -- test -r` tatsächlichen Zugriff auf CSS/JS und die **fortbestehende Sperre** von `.env`/SQLite prüfen.

**Nächster Betreiber-Schritt:** Gezielte ACL-Freigabe *mit* Sicherung/Rollback auf der noch nicht öffentlich geschalteten Laravel-App; danach Plesk-Test-Webroot-/Git-Rollback-Konzept und **authentifizierter Legacy-V4.25-Live-JTL-/Amazon-/KI-Browsertest** vor Domainumstellung. Admin, SQLite und Composer werden nicht neu erstellt.


### 22.11.2026 · Dokumentationsaktionen nach erfolgreicher Ubuntu-ACL-Installation

- Bestätigte Benutzer-PuTTY-Ergebnisse: ACL-Paketinstall, bestehendes `ext4`-Dateisystem, Werkzeuge und OS-Kernel-Restart-Hinweis im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) unter Commit `c98c4dae803f62f5f87f63270d7f3ac88c857676` eingetragen.
- [Runbook](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) enthält nun das **noch nicht ausgeführte** konservative ACL-Skript für ausschließlich `public/css`, `public/js`, `public/fonts`, mit vorheriger Sicherung und automatischer Fehler-Rücknahme. Runbook-Commit `f8dc3fb875fcc0c6534713f47bb65fcf98e8a607`.
- [Cross-Chat-Übergabe](./HW24_HANDOVER.md) und [Projektstand](./HW24_PROJEKTSTAND_AKTUELL.md) fortgeschrieben. Keine weitere Serveroperation durch die GitHub-Dokumentationsänderungen.
- Nächster Servernachweis: Betreiberausgabe von `HW24_PUBLIC_ACL` samt expliziter Positivprobe CSS/JS und Negativprobe `.env`/SQLite/`index.php`; dann noch angemeldete Legacy-Live-Abnahme und abgesichertes Plesk-Cutover mit Rollback.


### 22.11.2026 02:22:45 UTC · Öffentliche Filament-Asset-ACL erfolgreich freigegeben, private Daten weiterhin gesperrt

**Quelle:** Betreiberausgabe aus PuTTY des vollständigen `HW24_PUBLIC_ACL`-Scripts. Der Block endete ausdrücklich mit `=== PUBLIC-ACL ERFOLGREICH ===`; das im Eingabe-Echo verrutschte Here-Document hat die tatsächliche Ausführung nicht verhindert. Der Skriptabschluss bestätigt die erfolgreichen Positiv- und Negativprüfungen.

| Schritt | Beobachtetes Ergebnis | Status |
| --- | --- | --- |
| 95 · Verzeichnis-/Dateischutzgates | Verzeichnisstruktur `repo/platform/public/{css,js,fonts}` und Dateien geprüft; Symlink-Gates durchlaufen | **BESTÄTIGT** |
| 96 · Root-private Rechte-Sicherung | Vor ACL-Änderung `getfacl -p` und `getfacl -R -p` der exakt betroffenen Pfade in neuem Root-Backup mit Zeitstempel `20261122T022245Z` erstellt; Backup-Dateiname wurde dem Betreiber im Terminal angezeigt, bleibt privat | **BESTÄTIGT** |
| 97 · Minimale Durchquerungs-ACLs | `setfacl -m u:nginx:--x,u:www-data:--x` nur auf notwendigen Elternverzeichnissen `hw24-console-private`, `repo`, `platform` und `public` | **BESTÄTIGT** |
| 98 · Öffentliche Asset-ACLs | `setfacl -R -m u:nginx:rX,u:www-data:rX` ausschließlich auf `public/css`, `public/js`, `public/fonts` | **BESTÄTIGT** |
| 99 · Nginx-Positiv-/Negativprüfung | Ausgabe `nginx: ASSETS LESBAR, PRIVATE DATEIEN GESPERRT`, geprüft Filament CSS/JS, `.env`, SQLite und `public/index.php` | **BESTÄTIGT** |
| 100 · Apache-Positiv-/Negativprüfung | Ausgabe `www-data: ASSETS LESBAR, PRIVATE DATEIEN GESPERRT`, identische Proben | **BESTÄTIGT** |
| 101 · Abschluss | `=== PUBLIC-ACL ERFOLGREICH ===`, `Websites und Datenbanken unveraendert`, Root-Prompt zurück | **BESTÄTIGT** |

**Sicherheitslage nach dem Schritt:** Die FPM-App läuft weiterhin als `nicovoss:psacln`. Die HTTP-Worker `nginx`/`www-data` können ausgewählte öffentliche CSS-/JS-Dateien lesen, aber **nicht** den Laravel-`.env`-Inhalt, den privaten SQLite-Inhalt oder den Frontcontroller als Quelltext. Die Rechteprüfung wurde bereits unter den tatsächlichen Unix-Identitäten ausgeführt. Die neuen ACLs sind **keine Default-ACLs**: Erneut publizierte oder neu hinzugefügte Assets könnten eine zusätzliche Rechteprüfung benötigen. Root-private Sicherung **nicht löschen**; für eine spätere Wiederherstellung den vom Betreiber ausgegebenen exakten Pfad und `setfacl --restore=<backup>` benutzen (nicht ohne Anlass ausführen).

**Grenzen des Nachweises:** Keine Domain-/DocumentRoot-Umstellung, kein Auth-Browser-Test, kein Nginx-HTTP-Asset-Test und kein JTL/Amazon/OpenAI-Live-API-E2E. Die alte Plesk-Test-Git-Bereitstellung verweist weiterhin manuell auf den Legacy-Test-Webroot. Der schon bestätigte Laravel-Admin bleibt privat und noch nicht im Browser erreichbar. Kein zusätzlicher vHost und keine neue MariaDB.

**Nächster vorgeschalteter Freigabeschritt:** Authentifizierten Live-Legacy-Browser-E2E auf `https://artikelmanager.hairworld24.de/artikelpflege.html?ean=3474630337398` durchführen: korrekten JTL-Artikel prüfen, eine ausdrücklich manuelle Amazon-Katalog-GET-Suche, bestehendes OpenAI-KI-Archiv GET (kein kostenpflichtiger KI-POST), und möglichst Scanlisten-/Qualitätszentrum-Erreichbarkeit. Ergebnisse samt Fehlertext dokumentieren, ohne Credentials/Sessions in Chat/GitHub. Anschließend einen **reversiblen** Plesk-Testdomain-DocumentRoot-Cutover mit altem Git-Deploymentziel, Webroot-/SSL-/Basic-Auth-/FPM-Verhalten und Rückfallplan separat planen. **Nicht sofort deployen oder das alte Git-Repository im privaten Laravel-Verzeichnis ausrollen.**


### 22.11.2026 · Dokumentations-Commits zum erfolgreichen öffentlichen Asset-ACL-Rollout

- Nach PuTTY-Bestätigung alle Einzelakte (private Root-ACL-Sicherung, vier Eltern-ACLs, rekursive öffentliche CSS/JS/Fonts-ACLs, echte Positiv-/Negativprüfungen für nginx und www-data, Erfolgsmeldung) chronologisch in diesem Journal festgehalten: Commit `888ebe9f549a7a500dde0502edb6722e25da4a1a`.
- [Neue-Chat-Übergabe](./HW24_HANDOVER.md) mit tatsächlich bestätigten Erfolgen, noch ausstehendem Legacy-Browser-E2E und Plesk-Git-/DocumentRoot-Rollbackgates auf den neusten Stand gesetzt: Commit `7f2517d77335982af8694f85fd7604dbea151350`.
- [Aktueller Projektstand](./HW24_PROJEKTSTAND_AKTUELL.md) ebenso nachgeführt: Commit `f1fbdb6fdd0931645ae95d80646b9c9cae186334`.
- [Technisches Preflight-Runbook](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) mit nachgewiesenem ACL-Erfolg und verbliebenen HTTP-/Legacy-Browser-/Rollbackgates ergänzt: Commit `d18b8f2e254edb1659d9618958032cd3f3bb1248`.
- **Echter nächster Server-/Browserarbeitsschritt:** **Authentifizierte** Legacy-Live-App V4.25 am bestehenden Referenzartikel EAN `3474630337398` öffnen; JTL-Name/Bestand und manuelle Amazon-Katalog-GET-/OpenAI-Archiv-GET-Schaltflächen auf erfolgreiche Ergebnisse prüfen und Ergebnis ohne Cookies/Tokens mitteilen. Vor Freigabe Test-Plesk-Dokumentenstamm-/alte manuelle Git-Bereitstellung für den Rückfall festhalten. Der aktuelle Laravel-Webroot wurde noch nicht veröffentlicht.


### 22.11.2026 · Live-Legacy-V4.25-Artikelpflege visuell getestet: JTL erfolgreich, Amazon-Cache nur teilweise, KI offen

**Quelle:** Zwei unmittelbar vom Betreiber bereitgestellte Browser-Screenshots von `https://artikelmanager.hairworld24.de/artikelpflege.html?ean=3474630337398`. Das sind **Browseranzeige-Belege**, keine serverseitig protokollierten API-Requests. Die Screenshots wurden nicht als Bilddateien ins öffentliche Repository hochgeladen.

| Einzelschritt | Sichtbarer Bildbefund | Belastbarer Status |
| --- | --- | --- |
| 102 · Bestehende Live-Artikelpflege | Live-Adresse `artikelmanager.hairworld24.de` im Browser; Ansicht `Artikel bearbeiten`, EAN `3474630337398` eingetragen | **BESTÄTIGT, Browseransicht erreichbar** |
| 103 · JTL-Snapshot | Grüner Hinweis: `Aktueller JTL-Snapshot und HW24-Entwurf geöffnet. Nur bestätigte Änderungsfelder werden gespeichert.` | **BESTÄTIGT, JTL-Leseanzeige funktioniert** |
| 104 · Artikelidentität | `Matrix Socolor Beauty - 6C - Dunkelblond kupfer - (90 ml)`, Marke Matrix; EAN und angezeigte JTL-Art.-Nr./HAN jeweils `3474630337398` | **BESTÄTIGT, angezeigter Referenzartikel** |
| 105 · JTL-Bestands-/Kostenansicht | `Bestand laut JTL-Spiegel: 0`, `EK netto laut JTL: 1,50 €`, `HW24-Entwurf Rev. 0` | **BESTÄTIGT als Spiegel-Snapshot**, **keine** Aussage über Echtzeit-WMS-Bestand oder JTL-Verkaufspreis/Brutto-VK |
| 106 · Amazon-EAN-Katalogvergleich | Amazon-Bereich zeigt `0 Amazon-Katalogtreffer · aus dem bestehenden Amazon-Cache (kein neuer SP-API-Abruf)` mit Cache-Zeitstempel `2026-11-21 23:45:51` | **BESTÄTIGT, Cacheanzeige**, **KEIN** bestätigter neuer SP-API-Request oder erfolgreicher Amazon-EAN-Match |
| 107 · Amazon-Titelsuche | `10 zusätzliche Amazon-Kandidaten nach Produktname · bestehenden Cache`, sichtbare Beispiel-ASINs; in den Kandidaten ausdrücklich `EAN nicht als identisch bestätigt` | **BESTÄTIGT, zwischengespeicherte Kandidaten**, **nicht** automatisch als Produktidentität/Shopangebot übernehmen |
| 108 · OpenAI-KI-Berichte | Rechtes Modul `OpenAI Intelligence · KI-Berichte` ist eingeklappt, ohne sichtbaren Berichtsinhalt oder GET-Abrufnachweis | **NOCH OFFEN**, keine Kosten oder API-Aktivität unterstellen |
| 109 · Live/Test-Bezeichnung in UI | In der Kopfzeile der **Live-Domain** steht `HAIRWORLD24 INTELLIGENCE · TEST` | **BESTÄTIGT, UI-Kennzeichnungsproblem** für späteres Korrektur-Ticket, kein Beweis für falsche technische Domain |
| 110 · Kein JTL-/Marktplatz-Schreibvorgang | Gezeigte Ansicht zeigt nur Artikel-/Cache-Lesedaten, keine Edit-Speicherung oder Marketplace-Write-Bestätigung | **KEIN SCHREIBNACHWEIS** |

**Freigabegate vor der geplanten Testdomain-Umstellung weiterhin NICHT vollständig abgeschlossen:** Bestätigt ist jetzt der **Live-JTL-Lesetest im Browser**. Für Amazon fehlt ein eindeutiger **frischer/manuell ausgelöster SP-API-GET- oder korrekt zugeordneter Cachebeleg**; der aktuell gezeigte Cache hat null EAN-Treffer und die zehn Kandidaten sind ausdrücklich nicht EAN-identisch bestätigt. Für OpenAI fehlt noch die aufgeklappte **bereits gespeicherte KI-Archiv-Anzeige**. Kein kostenpflichtiger OpenAI-POST erforderlich oder erwünscht. Nicht behaupten, Amazon-API oder OpenAI seien in dieser Runde erfolgreich live getestet.

**Nächster Betreiber-Schritt:** Im existierenden Live-Artikelmanager `OpenAI Intelligence · KI-Berichte` aufklappen und das kostenlose gespeicherte Archiv-GET-Ergebnis (auch ein korrektes `kein Bericht vorhanden`) zeigen. Danach die bereits sichtbare Schaltfläche `Amazon-Katalog prüfen` nur auf bewussten Wunsch einmalig manuell auslösen und genau unterscheiden, ob frischer SP-API-GET, Cache-Hit oder Fehler angezeigt wird. **Keine unbekannten Kandidaten-ASINs automatisch verknüpfen.** Anschließend vor Umstellung des Test-vHosts den bestehenden Plesk-Git-/DocumentRoot-/SSL-/HTTP-Basic-Rückfallweg konkret prüfen. Der erfolgreiche `HW24_PUBLIC_ACL`-Befund, private SQLite und Live-Version bleiben unverändert.

**Datenschutz:** Screenshots enthalten keine Zugangsdaten; dokumentiert wurden nur Produkt-/API-UI-Diagnosen. Kein Login/Token, keine private Konfiguration in GitHub.


### 22.11.2026 · Live-KI-Prüfarchiv geöffnet: 0 gespeicherte Berichte trotz 4 monatlicher Prüfungen; Datenquellen im Code abgeglichen

**Quelle:** Neuer vom Betreiber bereitgestellter Live-Browser-Screenshot von `https://artikelmanager.hairworld24.de/index.html` mit geöffnetem Dialog **`KI-Auswertungen · HW24-Prüfarchiv`**. Zusätzlich **GitHub-Quellcodeverifikation** von `deploy/staging` in `api/ai-reviews.php` und `api/ai-review.php`; dies sind voneinander getrennte Nachweise.

| Einzelschritt | Sichtbarer bzw. code-verifizierter Befund | Status |
| --- | --- | --- |
| 111 · Live-KI-Prüfarchiv öffnen | Der Modal-Dialog `KI-Auswertungen · HW24-Prüfarchiv` erscheint in der Live-Expertenansicht `/index.html`; Schaltflächen für `Alle letzten Prüfungen`, `Aktuelle EAN`, `Aktualisieren` sind sichtbar | **BESTÄTIGT, Browseroberfläche erreichbar** |
| 112 · Archiv-Ausgabe | `0 gespeicherte KI-Analysen · neueste zuerst. Anzeige ohne erneute KI-Kosten.`; daneben `Noch keine KI-Prüfungen für diesen Filter gespeichert.` und `Noch keine dauerhaften KI-Ergebnisse.` | **BESTÄTIGT als angezeigtes Ergebnis**, aber keine gesicherte Ursachenanalyse |
| 113 · Nutzungsanzeige | `4 Prüfungen · Limit 5.00 USD · 1/25 heute · letzte Prüfung 0,0014 USD`; geschätzte Monatskosten `0,0053 USD` | **BESTÄTIGT als App-geschätzte Kosten- und Zähleranzeige**, **keine OpenAI-Rechnung** |
| 114 · Unterschiedliche Quellen im Code | `api/ai-review.php` nutzt das private `hw24-ai-usage.json`-Ledger für Monats-/Tageszähler und geschätzte Tokenkosten; `api/ai-reviews.php` liest dagegen `hw24_ai_reviews` aus MariaDB | **VERIFIZIERT GitHub-Code** |
| 115 · Benutzerfilter im Archiv | `api/ai-reviews.php` nutzt `actor` aus HTTP-/Webserveranmeldung und fragt `WHERE actor=?`; optional zusätzlich EAN | **VERIFIZIERT GitHub-Code** |
| 116 · Möglichkeiten für Differenz | Die vier Zählungen **belegen keine vier persistierten Archivberichte**. Denkbare Ursachen: andere HTTP-Basic-Benutzeridentität, vom Berichtfilter ausgeschlossene EAN, fehlerhafte Modell-Ausgabe oder fehlgeschlagene Datenbankspeicherung nach bereits erfasstem Tokenverbrauch. `api/ai-review.php` schreibt Ledger vor anschließender archivierter Review-Zeile | **TECHNISCH PLAUSIBEL, Ursache OFFEN** |
| 117 · Keine neue Berechnung | Der Dialog sagt `Anzeige ohne erneute KI-Kosten`; kein KI-Prüfung-Start und kein neuer Modell-POST wurden in der Screenshot-Aktion nachgewiesen | **BESTÄTIGT für sichtbare UI-Aktion** |

**WICHTIG:** Der Screenshot ist aus der **Legacy-Expertenansicht** `/index.html` und nicht aus dem eingebetteten KI-Panel der neuen Artikelpflege. Außerdem ist `0 gespeicherte KI-Analysen` ein **konkretes, negatives Testergebnis des Archivs** und kein Nachweis von Datenverlust. Nutzer-/EAN-Filter und voneinander abweichende Ledger-/Datenbankquellen müssen vor eventuellen weiteren KI-Prüfungen diagnostiziert werden. Keine kostenpflichtige KI-Analyse auslösen, um das Archiv zu testen.

**Nächster schonender Diagnoseschritt:** Zuerst `Alle letzten Prüfungen` und `Aktualisieren` im Live-Archiv ohne Neuberechnung testen und das Ergebnis mitteilen; dann ggf. einen einzelnen rein lesenden GET von `/api/ai-reviews.php?limit=5` im bereits authentifizierten Browser prüfen, nur Status/ok/scope/count (keine personenbezogenen Berichtsdetails, keine Credentials) dokumentieren. Wenn weiterhin 0, einen serverseitigen rein lesenden, **datensparsamen** Abgleich der Gesamtanzahl MariaDB-Review-Zeilen und des aktuell eingeloggten `actor`-Scopes planen, ohne Reportinhalte oder Secretdateien im Chat zu veröffentlichen.

**Rolloutgrenze:** JTL-Live-Artikelanzeige ist bereits bestätigt; Amazon zeigte Caches, keinen neuen sicheren EAN-Match, OpenAI zeigt nun **0 Archivberichte ohne sichtbaren Fehler**. Ob das Archiv funktional alle erwarteten Berichte abrufen kann, ist **nicht** bestätigt. Den vorhandenen Test-vHost nicht auf die neue Filament-Konsole umschalten, bevor Legacy-E2E/Archivzustand bewertet und Plesk-Git-/DocumentRoot-Rollback abgesichert ist.


### 22.11.2026 · Vertiefte KI-Archivdiagnose nach Benutzerhinweis „Leer, keinen Zugriff“

**Auslöser:** Der Betreiber sieht in `artikelmanager.hairworld24.de/index.html` ein leeres Prüfarchiv und verlangt die zuverlässige Wiederanzeige **aller berechtigt abrufbaren archivierten Daten**. Die bisherigen Live-Browser-Screenshots zeigen `0 gespeicherte KI-Analysen` bei `4` modellbezogenen Zählerbuchungen.

| Einzelvorgang | Quellbeleg / Ergebnis | Status |
| --- | --- | --- |
| 118 · Browser-Verbindung | Angeschlossener Browser-Kontext war für direkte externe Abfrage **nicht verfügbar**; erfordert ChatGPT in Browser mit verbundener Erweiterung. Keine private Live-DB-Abfrage durch ChatGPT erfolgt | **BESTÄTIGT, Zugriffslimit** |
| 119 · Historischen Archivbeleg aus Projektstand erneut gefunden | `docs/HW24_PROJEKTSTAND_AKTUELL.md`: Am **21.11.2026** wurde eine authentifizierte `GET /api/ai-reviews.php`-Rückgabe mit `ok:true`, `stored:true`, `count:1`, Review-ID **1**, EAN **8027483835211** und vollständigem Review für **Selective Professional ONcare Leave-in Daily Hydrating Spray 275 ml** vom Nutzer vorgelegt | **BESTÄTIGT früherer Test**, aktuelle DB-Existenz **noch nicht nachgeprüft** |
| 120 · Tatsächlichen Archiv-Endpunkt im GitHub `deploy/staging` gelesen | `api/ai-reviews.php`: GET only, HTTPS und HTTP Basic Actor erforderlich, `WHERE actor=?` für aktuelle Anmeldung, optional `AND ean=?` sowie `before_id`/Limit-Pagination, MariaDB `hw24_ai_reviews` | **VERIFIZIERT GitHub-Code** |
| 121 · KI-Kostenzähler und Speicherweg verglichen | `api/ai-review.php`: Zähler in getrenntem privatem `hw24-ai-usage.json`. Nach einem kostenpflichtigen Modellaufruf werden geschätzte Kosten ins Ledger geschrieben; der MariaDB-INSERT erfolgt nachgelagert. **Kostenbeleg ist kein Beweis eines archivierten Datensatzes** | **VERIFIZIERT GitHub-Code** |
| 122 · UI-Pagination und Fehlerpfad geprüft | `assets/hw24-ai-history.js`: GET mit `credentials:'same-origin'`, `cache:'no-store'`, 30 Datensätze je Seite, `Ältere laden` über `before_id`. `reviews:[]` wird als „Keine dauerhaften KI-Ergebnisse“ angezeigt, obwohl dies nur den aktuellen Actor-/EAN-Filter betrifft | **VERIFIZIERT, irreführende Nulltextanzeige** |
| 123 · Konkrete mögliche Datenursachen unterschieden | LIVE-BasicAuth-Actor anders als früherer TEST-Actor, anderer EAN-Filter, fehlgeschlagene Insert-Persistierung, fehlende historische DB-Zeile oder andere DB-/Verbindungskonfiguration sind **noch nicht entschieden** | **OFFEN, keine Vermutung als Tatsache** |
| 124 · Datensparsame Read-only-Diagnose in GitHub dokumentiert | Neues [`docs/HW24_AI_ARCHIVE_AUDIT_20261122.md`](./HW24_AI_ARCHIVE_AUDIT_20261122.md), Commit `c24a1d00fcf62f62b63678cc127d8f14b8105244`: zeigt nur **aggregate** MariaDB-Zeilen-/Actor-/EAN-Zahl, Existenz des früheren Review-ID-1-Datensatzes und gruppierte anonyme Counts, ohne DB-Passwort, Actor-Namen oder Berichtinhalt auszugeben | **VERIFIZIERT GitHub-Dokument**, **noch nicht auf Plesk ausgeführt** |

**Sicherheitsentscheidung:** Den bisherigen Actor-Filter **nicht** global entfernen; fremde KI-Berichte würden dann potentiell jedem Basic-Auth-Benutzer zugänglich. Eine echte „Alle Berichte“-Ansicht ist nur für ausdrücklich autorisierte Betreiber-Adminrollen und mit Live-Berechtigungsnachweis vertretbar; bestehende historische `actor`-Werte nicht ändern und keine KI-Prüfungen teuer neu starten. Bisheriges `0` auf Live ist **kein Beleg für Datenverlust**.

**Exakt nächster Betreiber-Schritt:** Das dokumentierte rein lesende `HW24_AI_ARCHIVE_AUDIT`-PuTTY-Skript ausführen. Aus der Gesamtzeilenzahl und Existenz des historisch gespeicherten Review #1 bestimmen, ob das Problem in Actor-Scoping/Filter oder fehlenden bzw. verschobenen Tabellen/Zeilen liegt. Danach erst gezielten, durch GitHub-Tests abgesicherten PHP-/UI-Fix entwickeln und durch manuellen Plesk-Rollout freigeben, ohne vorhandene Datenbanken und Legacy-Referenz zu gefährden.


### 22.11.2026 · Datensparsames Archiv-Read-only-Audit und Cross-Chat-Übergabe bereitgestellt

- **Neues GitHub-Runbook:** [HW24_AI_ARCHIVE_AUDIT_20261122.md](./HW24_AI_ARCHIVE_AUDIT_20261122.md), erstellt mit Commit `c24a1d00fcf62f62b63678cc127d8f14b8105244`; SQL-Nachweis der historischen Review-ID-1/EAN-ID zur robusten SQL-Mode-Unabhängigkeit auf gebundene Parameter umgestellt, Commit `1499a6ea8705b9987e95d7f0c5946ece8710cb68`. Das Skript ist **noch nicht** vom Betreiber ausgeführt.
- **Sicherheits- und Auswertungsumfang:** Verwendet nur die bereits existierende private MariaDB-Konfiguration, `SELECT COUNT(*)`, `COUNT(DISTINCT actor)`, `COUNT(DISTINCT ean)`, parametrisierten historischen ID-/EAN-Existenztest und anonymisierte `GROUP BY actor`-Counts. Gibt **keine** Authentifizierungskennung/Passwort/API-Tokens/Reviewtexte aus. Kein KI-POST, keine Migration und keine Plesk-Änderung.
- **Übertragung für neue Chats:** [HW24_HANDOVER.md](./HW24_HANDOVER.md) aktualisiert unter Commit `f4324e514fb3309b08456ff70e115a7129e709fa`; [HW24_PROJEKTSTAND_AKTUELL.md](./HW24_PROJEKTSTAND_AKTUELL.md) unter Commit `74c6cbc978d1c18753838cc01c3f5393fcadbe2b`.
- **Nächster wirklicher Erkenntnisschritt:** Der Betreiber führt `HW24_AI_ARCHIVE_AUDIT` per PuTTY auf dem bestehenden Plesk-Server aus und übermittelt nur aggregierte Ausgaben. Ohne die echte MariaDB-Zahl ist weder die Behauptung „alle archivierten Berichte fehlen“ noch die Aussage „nur anderer Actor“ verifiziert. Anschließend targeted Code-/UI-Korrektur auf isoliertem Branch mit Regressionstests und manueller Freigabe zur Live-Bereitstellung.


### 22.11.2026 · MariaDB-KI-Archiv read-only geprüft: 3 Berichte vorhanden, Live-Anzeige 0

**Quelle:** Vollständige Betreiber-PuTTY-Ausgabe des rein lesenden `HW24_AI_ARCHIVE_AUDIT`; der teils verrutschte Kopier-/Eingabetext hat die eigentliche erfolgreiche SQL-Prüfung nicht verhindert.

| Schritt | Direkt beobachtetes Ergebnis | Status |
| --- | --- | --- |
| 125 · Bestehende private MariaDB-Konfiguration | `hw24_private/scanlist-db.php` konnte unter `nicovoss` und Plesk PHP 8.5 geladen werden; PDO-Verbindung zur bestehenden `hw24_artikelmanager` erfolgreich | **BESTÄTIGT, ausschließlich lesend** |
| 126 · Gesamtbestand `hw24_ai_reviews` | `Gespeicherte KI-Berichte: 3` | **BESTÄTIGT: Archiv NICHT leer** |
| 127 · Benutzerzuordnungen | `Unterschiedliche Benutzerzuordnungen: 1` | **BESTÄTIGT**, Actor-Name bewusst nicht ausgegeben |
| 128 · EANs | `Unterschiedliche EANs: 3` | **BESTÄTIGT** |
| 129 · Jüngster Datensatz | `Letzter archivierter Bericht UTC: 2026-11-22 01:31:34.913848` | **BESTÄTIGT** |
| 130 · Historischer Referenzbericht | `Historischer Bericht ID 1 vorhanden: JA`, EAN `8027483835211` per gebundenem parametrisiertem `SELECT` geprüft | **BESTÄTIGT, kein Datenverlust dieses Berichts** |
| 131 · Gruppierung | `Anonyme Benutzergruppe 1: 3 Berichte` | **BESTÄTIGT**, alle drei unter derselben Actor-Zuordnung |
| 132 · Abschluss | `DATENBANKABFRAGE ERFOLGREICH`, `HW24 ARCHIVPRUEFUNG ABGESCHLOSSEN`, Terminalprompt wieder da | **BESTÄTIGT** |

**Schlussfolgerung aus diesen Daten (belegt):** Der in Live sichtbare Zustand `0 gespeicherte KI-Analysen` kann **nicht** die Gesamtzahl der Archivdatensätze bezeichnen. Mindestens drei archivierte Datensätze existieren aktuell in genau der MariaDB, die der Legacy-PHP-Endpunkt laut `deploy/staging` liest. Der Archivendpunkt filtert jedoch per `WHERE actor=?` nach `PHP_AUTH_USER/REMOTE_USER`. Damit ist eine **abweichende HTTP-Basic-Anmeldung/Actor-Kennung** die vorrangig zu prüfende Ursache. Die tatsächliche aktuelle Live-Basic-Auth-Kennung ist noch nicht mit der DB-Actor-Kennung verglichen. **Nicht** global das WHERE-Actor-Filter entfernen, weil das die Datensicht fremder Nutzer freigeben würde.

**Exakt nächster Schritt:** Aus dem authentifizierten Legacy-Live-Browser den aktuell verwendeten **HTTP-Basic-Benutzernamen** (nur Nutzerkennung, kein Passwort) privat in einem interaktiven PuTTY-`read` erfassen und mit `SELECT COUNT(*) FROM hw24_ai_reviews WHERE actor=?` vergleichen, ausschließlich die anonymen Zahlen `0..3` ausgeben. Der Vergleich muss ohne Speicherung/Ausgabe des Accountnamens und ohne Reportinhalt erfolgen. Wenn die Zahl 0 beträgt, ist der Login-Scope-Mismatch bestätigt, und eine separat autorisierte Betreiber-Gesamtarchivansicht ist erforderlich; wenn 3, dann den tatsächlichen Live-PHP-`PHP_AUTH_USER/REMOTE_USER`-Kontext und eventuelle EAN-/Filterabweichung prüfen. Das Prüfskript wird in `docs/HW24_AI_ARCHIVE_AUDIT_20261122.md` ergänzt.

**Nach erfolgreicher Diagnose:** Änderungen nur über separaten GitHub-Featurebranch mit Tests (serverseitig autorisierte Admin-Sicht; bestehender Actor-Scope für Standardnutzer bleibt bestehen), keine Archiveinträge überschreiben, keine zusätzlichen OpenAI-POSTs, kein unangemeldeter globaler DB-Endpoint, kein Live-/Test-Webroot-Cutover ohne Rollback.


### 22.11.2026 · Weiterführender Datenschutz-Actor-Vergleich vorbereitet und Chat-Übergabe aktualisiert

- [HW24_AI_ARCHIVE_AUDIT_20261122.md](./HW24_AI_ARCHIVE_AUDIT_20261122.md) um den **tatsächlich bestätigten** MariaDB-Gesamtbestand `3 Berichte / 1 Actor / 3 EAN / Review #1 vorhanden` ergänzt, Commit `79561ed5e3ffc3e4a1e855189819adeba58ab6d3`.
- Das neue **noch nicht ausgeführte** `HW24_ACTOR_MATCH`-Skript nimmt nur den **HTTP-Basic-Login-Benutzernamen** der Live-Domain verdeckt am Terminal entgegen und vergleicht ihn über `SELECT COUNT(*) WHERE actor=?` mit der **existierenden** Datenbank. Es gibt ausschließlich `gesamt`, `passend` und `fremd` aus; keine Kennung, Passwörter oder Berichtsdetails. Kann bestätigen oder widerlegen, dass der aktuelle Basic-Auth-Actor den drei vorhandenen Berichten entspricht. Benutzername ist explizit **nicht** die Laravel-Admin-E-Mail, falls anderes Login.
- [HW24_HANDOVER.md](./HW24_HANDOVER.md) als Einstieg für neue Chats mit letztem Befund und nächstem Ausführungsschritt aktualisiert, Commit `d9fafc56472dde3ba1c1927f1b354a243b010d3d`.
- [HW24_PROJEKTSTAND_AKTUELL.md](./HW24_PROJEKTSTAND_AKTUELL.md) analog aktualisiert, Commit `c190c32eaa4e11af5f03e0ee43afc5f5b5453c88`.
- **Noch nicht getan:** Kein Live-HTTP-Actor direkt aus der Browser-Sitzung verglichen, keine Datenmigration, keine serverseitige Query-Rechteänderung, keine Testdomain-Umstellung und kein Live-Deploy. Erst das tatsächliche `HW24_ACTOR_MATCH`-Ergebnis bestimmt den nächsten Fix.


### 22.11.2026 · ACTOR-MISMATCH BESTÄTIGT: 3 KI-Berichte für andere Zuordnung als das eingegebene Live-Basic-Login

**Quelle:** Neue tatsächliche Betreiber-PuTTY-Ausgabe von `HW24_ACTOR_MATCH` gegen die bestehende produktive MariaDB `hw24_artikelmanager.hw24_ai_reviews`. Der Browser-Basic-Anmeldename wurde **verdeckt am Terminal** eingegeben, weder im GitHub-Repo noch hier protokolliert. Keine Passwörter oder Datensatzinhalte ausgegeben.

| Schritt | Ist aus PuTTY | Status |
| --- | --- | --- |
| 133 · Archiv-Gesamtzahl | `Archivberichte gesamt: 3` | **BESTÄTIGT** |
| 134 · Live-Basic-Kennung gegen `actor` verglichen | `Berichte fuer Live-Anmeldung: 0` | **BESTÄTIGT** |
| 135 · Unter anderer Actor-Kennung gespeichert | `Andere Benutzerzuordnung: 3` | **BESTÄTIGT** |
| 136 · Nur lesender Test beendet | `READ-ONLY-PRUEFUNG ERFOLGREICH`, `PRUEFUNG ABGESCHLOSSEN` | **BESTÄTIGT**, keine DB-Änderung |

**Feststehende Ursache für den angegebenen Live-Login:** Der Archiv-Endpunkt `api/ai-reviews.php` filtert unter `WHERE actor=?`. Die drei gespeicherten Berichte befinden sich unter **einem anderen** `actor` als dem eingegebenen Live-HTTP-Basic-Anmeldenamen, deshalb zeigt die Oberfläche `0`. **Die Berichte sind nicht gelöscht.** Falls der tatsächlich vom Webserver übergebene HTTP-Actor von der vom Betreiber eingegebenen Kennung abweicht, wäre dessen gesonderte Prüfung möglich; die SQL-Zuordnung zu der eingegebenen Kennung ist jedenfalls eindeutig null.

**Sicherheitsanforderung für die Korrektur:** Original-`actor`-Daten der drei Archivzeilen nicht ändern und vorhandenes Standardbenutzer-`WHERE actor=?` nicht abschalten. Eine explizit **serverseitig autorisierte** begrenzte Archivdelegation / Betreiber-Gesamtansicht bereitstellen; aktuelle HTTP-Basic-Kennung und autorisierter historischer Actor werden ausschließlich in einer **privaten Serverdatei** zugeordnet, niemals per ungeschütztem Query-Parameter, lokalem Browserstatus oder öffentlicher GitHub-Datei. Bestehende normale Benutzer sehen weiter nur ihre eigenen Berichte. Für die Brücke und neue Filament-Konsole keine zusätzliche KI-Anfrage auslösen.

**Als nächstes:** Berechtigungsänderung im GitHub-Code auf isoliertem Featurebranch entwickeln, mit Tests und manuellem Rolloutgate; zuvor die bestehende Lese-Endpunkt-Implementierung und Test-Organisation prüfen. Ohne autorisierten Servereintrag bleibt der Legacy-Archivzugriff unverändert. **Noch keine produktive Codeänderung oder Plesk-Git-Bereitstellung.**


### 22.11.2026 · Betreiberwunsch: Testzugang auf neue persönliche Benutzerkennung umstellen

**Nutzerauftrag:** Die Benutzerkennung der HW24-Testanmeldung soll auf einen vom Betreiber ausdrücklich benannten neuen Login-Namen gesetzt werden. Der konkrete Benutzername wird aus Sicherheits- und Datenschutzgründen **nicht** im öffentlichen GitHub-Repository veröffentlicht. Es handelt sich um eine **Account-/Authentifizierungsänderung**, nicht um die Berechtigung, sämtliche historischen `hw24_ai_reviews.actor`-Werte zu überschreiben.

**Belegte Ausgangslage:** Die drei MariaDB-KI-Berichte existieren; alle sind einer historischen Actor-Kennung zugeordnet, die dem zuvor verdeckt eingegebenen aktuellen Live-Basic-Namen **nicht** entspricht. Eine Namensänderung der Passwortschutz-Anmeldung würde **nicht automatisch** die historischen Actor-Kennungen umschreiben. **Keine** Archiv-Datenbankänderung und **kein** Passwort geteilt.

**Bestätigte Dokumentation:** Plesk Obsidian dokumentiert `plesk bin protdir -u <protected_dir> -domain <domain> -update_user <old> -user_name <new>` zum Umbenennen eines Passwortschutz-Benutzers, **sofern** Plesk diesen Schutz verwaltet. Die tatsächliche verwendete Authentifizierungsquelle (Plesk-geschütztes Verzeichnis, .htaccess oder nginx) und der genaue alte Login-Name / Verzeichnisbezeichner der Testdomain sind **noch nicht** ausgelesen. Domainberechtigungen, die bestehende Live-Umgebung und die Laravel-Admin-E-Mail sind getrennt zu behandeln.

**In Arbeit, nicht live:** Isolierter Featurebranch `feature/hw24-ai-archive-admin-scope-20261122` wurde von `deploy/staging` erzeugt und enthält bisher zwei vorbereitende Commits für einen streng serverseitig freigegebenen GET-Gesamtarchivmodus in `api/ai-reviews.php` sowie einen expliziten Betreiberknopf in `assets/hw24-ai-history.js`. **Ungetesteter Entwicklungsstand, nicht gemergt, nicht deployed, keine Plesk-Autorisierung konfiguriert**. Diese Funktion ist separat von der Test-Basic-Login-Umbenennung.

**Nächster sicherer Schritt:** Authentifizierungsquelle und aktuellen Testdomain-Benutzer **nur lesend** identifizieren, danach gezielt den einen bereits bestehenden Passwortschutz-Login umbenennen und Login in privatem/incognito Browser prüfen. Weder Live-Login noch historischen Actor-Wert überschreiben. Wenn der neue Benutzername später berechtigt das gesamte Archiv sehen soll, serverseitige private Allowlist nach Sicherheits-/Regressionstests konfigurieren und Audit-Logs beachten.


### 22.11.2026 · Betreiber präzisiert Loginwechsel: bestehende Basic-Kennung für Artikelmanager UND Scanliste

**Neu vom Betreiber korrigiert:** Der bisherige Loginname ist eine tatsächlich existierende, exakt benannte HTTP-Basic-Kennung (nicht die im vorherigen Assistant-Befehl irrtümlich angenommene Zeichenfolge mit Leerzeichen). Ziel ist ein ebenfalls exakt benannter neuer Benutzername für **Artikelmanager und Scanliste**. Die konkreten Kennungen werden aus Datenschutzgründen nicht in dieser öffentlichen GitHub-Datei wiederholt. Dies ist eine **Korrektur** des vorherigen, noch nicht ausgeführten Plesk-Umbenennungsbeispiels; bitte jenes alte Skript **NICHT** ausführen.

**Live-Quellcodeprüfung auf `deploy/staging`:**
- `scanliste.html` verwendet `fetch('api/scanliste.php', {credentials:'same-origin'})`; die Scanlisten-API liest den authentifizierten Basic-User aus `PHP_AUTH_USER / REMOTE_USER / REDIRECT_REMOTE_USER` und vergleicht ihn gegen `hw24_scan_batches.created_by`. Historische Stapel würden ohne stabilen Alias nach Umbenennung ggf. verschwinden.
- `api/ai-reviews.php` und `api/ai-review.php` verwenden dieselbe Basic-Kennung, um `hw24_ai_reviews.actor` zu lesen, neue KI-Berichte zuzuordnen und bestehende Prüfungen kostenfrei wiederzuverwenden. Die KI-Historie darf **nicht** durch einen Loginwechsel verborgen oder teuer erneut berechnet werden.
- `api/curated.php` enthält Audit-Autoren, aber keine Actor-gefilterte Eigentümerliste; die historische Provenienz darf nicht umgeschrieben werden.
- Plesk kann mit `protdir -u ... -update_user ... -user_name ...` einen bestehenden geschützten Benutzer umbenennen, aber nur nach Bestätigung der tatsächlichen Plesk-Auth-Quelle und des exakten Domain-Root-Verzeichnisses. `.htpasswd` könnte stattdessen manuell konfiguriert sein. Offizielle Plesk-Dokumentation geprüft.

**GitHub-Implementierung, noch NICHT deployed:**
- Isolierter Branch `feature/hw24-login-alias-scan-ai-20261122` auf `deploy/staging` erzeugt.
- Neues `api/hw24-actor-identity.php`: optionale private Mapping-Datei `hw24_private/actor-aliases.php`, fail-closed auf bisherigen Actor wenn nicht vorhanden/ungültig, nur durch serverseitig authentifizierten Login bestimmt, keine URL-Parameter oder Schemaänderungen. Öffentliche GitHub-Datei enthält keine konkrete private Alias-Zuordnung.
- Bestehende `api/scanliste.php`, `api/ai-review.php`, `api/ai-reviews.php` nach HTTP-Auth- und HTTPS-Checks auf gemeinsamen stabilen historischen Owner umgestellt. Reuse bestehender KI-Reviews und Scanlisten-Owner bleibt erhalten, sobald korrekter Alias privat gesetzt ist.
- Neue PHP-Tests `tests/hw24-actor-alias.test.php` mit Regressionen für Besitzertrennung, nicht autorisierte Namen, fehlerhafte Aliaswerte, bestehende `created_by=?`- und `actor=?`-Restriktionen. CI für Branch ergänzt.
- **Draft [PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37)** geöffnet, Head `178491861f7f3cef8ba2405346d1321489d939c8`. **GitHub Actions [#44977971992](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44977971992) erfolgreich** für diesen Stand. Keine Merge-/Plesk-Änderung.
- [Read-only Prüf-/Rolloutplan für Login und Scanliste](./HW24_LOGIN_IDENTITAETSWECHSEL_20261122.md) im GitHub-`main` erstellt, einschließlich Plesk-Tabelleninventur und MariaDB-Counts für alte/neue Actor-Kennung; konkret eingetippte Loginnamen werden nur im PuTTY-Terminal erfasst und nicht committed. Dokumentations-Commit `b7cf2b491c737b67b4d5317ecc4cdf8baac50ca3`.

**Sicherheits-/Kompatibilitätsgrenzen:** Der frühere Datensatzbefund **3 KI-Reviews / 1 historischer Actor / 3 EAN** ist bestätigt, aber unter welcher konkreten gespeicherten Actor-Kennung diese drei liegen ist noch **NICHT** nachgewiesen. Es ist ausdrücklich **nicht** belegt, dass diese Kennung dem alten HTTP-Basic-Benutzernamen entspricht. Auch die vorhandenen Scanlisten-Stapel müssen erst per `SELECT COUNT(*)` unter alter/neuer gespeicherter Owner-Kennung kontrolliert werden. Deshalb niemals blind eine private Alias-Zuordnung einrichten oder Plesk-Konto umbenennen, solange die lesende Vorprüfung fehlt. Weder Live noch Test-Webroot, Plesk-Login, KI-Berichte, Scanlisteneinträge oder JTL-Datenbank bisher verändert.

**Exakt nächster Betreiber-Schritt:** Das im Runbook dokumentierte `HW24_LOGIN_READONLY`-PuTTY-Skript ausführen, um für Live/Test die tatsächlich Plesk-verwalteten Schutzkonten und für `hw24_ai_reviews.actor`/`hw24_scan_batches.created_by` die Gesamt-/ALT-/NEU-/anderen Zahlen zu erhalten. Bei SQL-/Plesk-Fehler erst Struktur lesen, nicht blind umbenennen. Dann können wir den korrekten privaten Alias und den Plesk-Loginwechsel mit vollständiger Rückfallmöglichkeit gezielt durchführen.

**Achtung:** Der ältere Entwurf `feature/hw24-ai-archive-admin-scope-20261122` enthält eine **separate ungetestete** Admin-Gesamtarchivfunktion, die noch nicht deployed ist und nicht mit dem jetzt geprüften Scanlisten-Kompatibilitäts-PR #37 verwechselt werden darf.


### 22.11.2026 · Plesk-Authentifizierungsprüfung: SQL-Spaltenfehler und tatsächlich geteilte Scanlisten-Bestände

**Quelle:** Betreiber-PuTTY-Ausgabe `HW24_LOGIN_CHECK`, nach präzisiertem Wunsch einer Umbenennung des bestehenden HTTP-Basic-Logins. Ein vorausgehender fehlerhaft doppelt eingefügter `HW24_ACTOR_MATCH`-Block löste lediglich `-bash: syntax error near unexpected token '('` aus und wurde mit Strg+C abgebrochen; dessen Ergebnis nicht als neuen Actor-Nachweis werten.

| Schritt | Konkretes Ergebnis | Bewertung |
| --- | --- | --- |
| 137 · Plesk-Test-/Live-Schutzkonten abfragen | `SELECT d.name, pd.name, pu.login ...` meldet `ERROR 1054 (42S22): Unknown column 'pd.name'` | **FEHLGESCHLAGEN**, Plesk-Schema-/Spaltenannahme falsch; **keine** Benutzer- oder Auth-Änderung erfolgt |
| 138 · Bestandszählung KI-Archiv | `hw24_ai_reviews`: **gesamt 3**, darunter **3** unter alter und **0** unter neuer HTTP-Basic-Kennung | **BESTÄTIGT**, alle drei bestehenden KI-Berichte unter historischer Anmeldung |
| 139 · Bestandszählung Scanliste | `hw24_scan_batches`: **gesamt 2**, darunter **1** unter alter und **1** unter neuer Kennung | **BESTÄTIGT**, **beide** Anmeldungen besitzen bereits einen eigenen Scanlisten-Stapel |
| 140 · SQL-Prüfung abgeschlossen | `SQL-PRUEFUNG ERFOLGREICH`; anschließend `PRUEFUNG ABGESCHLOSSEN` und SSH-Prompt | **BESTÄTIGT**, Read-only auf bestehende MariaDB |
| 141 · Authentifizierungssystem | Plesk-`protected_dirs`/Benutzertabellen durch fehlerhafte Spalte **noch nicht inventarisiert** | **OFFEN**; kein Schluss, dass Plesk die Auth tatsächlich verwaltet |

**WICHTIGE ARCHITEKTURKORREKTUR:** Der bisher in [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37) vorbereitete einzelne kanonische Actor-Alias (`new -> old`) ist **für diesen tatsächlichen Datenbestand nicht ausreichend**: Er würde die drei KI-Reviews und den alten Scan-Stapel anzeigen, aber den **bereits unter dem neuen Login vorhandenen Scan-Stapel ausblenden**. Daher **PR #37 nicht deployen oder mergen**, bis eine getestete read-only **Zwei-Owner-Ansicht** (historischer und neuer Owner) und konsistente sichere Schreibzuordnung implementiert sind. Historische `actor`, `created_by` und Audit-Ereignisse bleiben unverändert. **Neue Vorgänge sollen nur unter der neuen authentifizierten Kennung entstehen.**

**Nächster sicherer Schritt:** (1) Plesk-Tabellenstruktur `SHOW COLUMNS FROM protected_dirs`, `pd_users` und `domains` **nur lesend** ausgeben und danach Authquelle korrekt inventarisieren; (2) PR #37 auf dualen, ausschließlich privat autorisierten Owner-Readzugriff überarbeiten, beide Scan-Stapel und drei KI-Reviews in automatischen Tests abdecken; (3) nach Backups Plesk-Test-Login mit Rückfallweg umstellen und Browser/Scanlisten/KI prüfen. Kein unkontrolliertes SQL-UPDATE, kein Passwort-Posting, kein neuer KI-POST.


### 22.11.2026 · PR #37 auf echte zwei Eigentümer korrigiert; GitHub-CI wieder grün

**Ausgangslage:** Nach der vorigen Betreiber-SQL-Ausgabe sind **2** Scan-Stapel vorhanden, davon je **1** unter altem und neuem Login, sowie **3** KI-Archivberichte unter altem Login. Die erste PR-Variante mit einem kanonischen Actor würde **den neuen Scan-Stapel ausblenden**; diese Variante wurde nicht deployed.

| Aktion | GitHub-Nachweis und technische Folge | Status |
| --- | --- | --- |
| 142 · Helferfunktion korrigiert | `api/hw24-actor-identity.php` von Einzel-Alias auf optionales privates `read_aliases`-Array umgestellt, das **immer** den aktuell eingeloggten User plus ausdrücklich erlaubte historische Actor-Kennungen zurückgibt; fehlende/ungültige Datei fail-closed | **COMMITTED** `5a0434a6` |
| 143 · Scanliste aktualisiert | `api/scanliste.php`: bestehende Liste und sechs `getBatch`-Autorisierungswege prüfen `created_by IN (...)` gegen gebundene Owner-Werte. **Neue** Stapel, Scanereignisse, Audits bleiben unter aktuellem HTTP-Basic-Login. Nach expliziter Aliasfreigabe kann der neue Benutzer auch einen alten freigegebenen Stapel bearbeiten; die Änderungen werden als neuer Bearbeiter auditiert. | **COMMITTED** `749ebc7c` |
| 144 · KI-Lesezugriff aktualisiert | `api/ai-reviews.php`: Listen- und Summary-Abfragen über gebundene `actor IN (...)` für erlaubte Eigentümer, ohne historische Datenbank-Updates | **COMMITTED** `7e62c943` |
| 145 · Wiederverwendung kostenpflichtiger KI-Prüfungen | `api/ai-review.php`: bestehende Berichte über beide ausdrücklich freigegebenen Owner suchen; falls neue Prüfung manuell beauftragt wird, sie weiterhin unter der aktuellen authentifizierten Kennung speichern, keine automatische KI-Kostenbelastung | **COMMITTED** `dd3225d2` |
| 146 · Regressionstests erweitert | `tests/hw24-actor-alias.test.php` validiert 2 Owner, Fremdzugriffsverbot, fehlkonfigurierte Datei, Datenzugriffsfilter, aktuelle Autoren von neuen Ereignissen/Berichten | **COMMITTED** `41bba2d9` |
| 147 · Legacy-Regressionsvertrag angepasst | `tests/hw24-v425-article-intelligence.test.cjs` prüft jetzt den neuen begrenzten Actor-IN-Mechanismus statt starr des alten ein-Actor-SQL-Strings; vor der Anpassung waren CI-Zwischenläufe **rot**, nicht verschweigen | **COMMITTED** `c2cc4e1d` |
| 148 · CI-Abnahme zum endgültigen Code | [GitHub Actions #44978357305](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978357305) und [#44978360796](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978360796) **success** für HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482` | **BESTÄTIGT, Tests grün** |
| 149 · Draft-PR entsprechend beschrieben | [PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37) nun mit **beiden** Scanlisten-Stapeln, den 3 alten KI-Berichten, aktueller Schreibkennung, serverseitiger Privatdatei und offenen Freigabegates beschrieben | **BESTÄTIGT, weiterhin Draft und NICHT deployed** |
| 150 · Plesk-Prüfanleitung korrigiert | [Login-Wechsel-Runbook](./HW24_LOGIN_IDENTITAETSWECHSEL_20261122.md) zeigt das tatsächlich nachgewiesene Alt/Neu-Verhältnis und ersetzt die ungültige Spaltenannahme `pd.name` durch reine `information_schema.COLUMNS`-Abfrage | **COMMITTED** `885197f5a8aa8b661d82637d9e1aa6c13a8943ff` |

**Sicherheitsstatus:** Die Live-Website, vorhandene Testdomain, HTTP-Basic-Anmeldung, Plesk-Passwortschutzdateien, originalen KI-Berichte und Scan-Stapel sind **unverändert**. Eine private `hw24_private/actor-aliases.php` wurde noch **nicht** auf dem Server angelegt. Die neue Freigabe erlaubt ausdrücklich Zugriff auf historisch autorisierte Stapel auch für die bestehenden POST-Editieroperationen, nicht nur reines Lesen; `changed_by`/Scan-Audits verwenden dabei den aktuell eingeloggten Benutzer. Eine unautorisierte oder öffentlich konfigurierbare Alias-Freigabe ist nicht zulässig.

**Nächste einzige PuTTY-Aktion:** Das im Runbook dokumentierte `HW24_PLESK_SCHEMA` nur lesend ausführen. Es liefert nur Namen der Spalten in `protected_dirs`, `pd_users` und `domains`, **keine Passwort- oder Kontoinhalte**. Daraus wird erst eine gültige read-only Plesk-Auth-Inventur entwickelt. Kein Login-Umbenennen und kein Code-Deploy, bevor Backup/Rollback und passende Auth-Zielkonfiguration bestätigt sind.


### 22.11.2026 · Passwort- und Kontenwechsel klar getrennt

- Betreiber hat zusätzliche Anmeldeinformation für die beiden bestehenden HTTP-Basic-Konten geliefert. Aus Sicherheitsgründen **keine Beschreibung, Wiederholung oder Speicherung der konkreten Passwortbeziehung im öffentlichen Repository**. Für die gewünschte Umbenennung wird **keine Passwortoffenlegung und kein automatischer Passwortreset** benötigt.
- **Unverändert nachgewiesen:** Unterschiedliche Basic-Anmeldenamen sind unterschiedliche Identitäten für `hw24_ai_reviews.actor` und `hw24_scan_batches.created_by`, selbst wenn das Passwort kein Unterscheidungsmerkmal ist. Die Zahl **3 KI-Reviews unter alt, 0 unter neu; je 1 Scan-Stapel unter beiden** erfordert explizite Dual-Owner-Berechtigung.
- Draft PR #37 mit Dual-Owner-Lesen und aktuellen Write-/Audit-Namen, zwei grüne GitHub-CI-Läufe; **noch nicht auf dem Server angewendet**.
- Kurz-Übergabe durch Dokumentations-Commit `b3fcd10e44bf00e37e5aa31869954bb4fb9ea990` aktualisiert, ohne Geheimnisse zu nennen.
- Nächster technischer Schritt bleibt unverändert die **nur lesende** `HW24_PLESK_SCHEMA`-Abfrage, damit die zuvor gescheiterte Spaltenannahme `pd.name` korrigiert und die tatsächliche Passwortschutzkonfiguration vor einer Kontenumstellung inventarisiert werden kann.


### 22.11.2026 · Plesk-Passwortschutzschema über information_schema read-only verifiziert

**Beleg:** Neue Betreiber-PuTTY-Ausgabe des vollständigen Blocks `HW24_PLESK_SCHEMA`. Der vorherige `HW24_LOGIN_CHECK` hat bei der Plesk-Abfrage wegen falscher Spalte `protected_dirs.name` den Fehler 1054 ausgelöst, aber die MariaDB-Bestandszählung separat erfolgreich beendet. Die neue, reine Schema-Abfrage war erfolgreich.

| Schritt | Ermitteltes Schema | Status |
| --- | --- | --- |
| 151 · `domains` | Existent; relevante Felder `id`, `name`, `parentDomainId` u. a. | **VERIFIZIERT** |
| 152 · `pd_users` | Existent; Felder `id`, `login`, `account_id`, `pd_id` | **VERIFIZIERT** |
| 153 · `protected_dirs` | Existent; Felder `id`, `non_ssl`, `ssl`, `realm`, **`path`**, `dom_id`, `dom_type`, `cgi_bin`; **keine** Spalte `name` | **VERIFIZIERT** |
| 154 · Abschluss | `PRUEFUNG ABGESCHLOSSEN`, SSH-Prompt wieder da | **BESTÄTIGT, keine Plesk-/DB-Änderung** |

**Konsequenz:** Künftiger nur lesender Login-Inventur-SQL muss `pd.path` verwenden und Benutzer über `pu.pd_id=pd.id`, Subdomain über `pd.dom_id=d.id` verknüpfen. `realm` ist nur eine Bezeichnung, nicht der Login. Die tatsächliche Plesk-Accountverwaltung und Schutzpfade sind weiterhin **nicht nachgewiesen**, bis diese korrigierte `SELECT`-Abfrage ausgeführt wurde. Die zuvor bestätigten DB-Werte bleiben unverändert: KI-Archiv 3 Zeilen unter historischem Login, Scanliste 2 Stapel (je 1 unter historischer/neuer Kennung). **Es wurde weder ein Konto umbenannt noch eine Aliasdatei serverseitig angelegt noch ein Code-Branch deployed.**

**Nächster Befehl:** Korrigierte Plesk-Leseabfrage mit `pd.path`, `pd.realm`, `pu.login` für exakt bestehende Live- und Testdomain sowie bisherige/geplante Login-Kennungen. Keine Passwort-Hashes, keine privaten Dateien, kein SQL-UPDATE. Wenn keine Treffer, Nginx/Apache-/htpasswd-Authquelle separat nur lesend ermitteln. **PR #37** enthält die auf tatsächliche Bestandslage korrigierte Zwei-Owner-Unterstützung und zwei erfolgreich absolvierte CI-Runs, bleibt **Draft und nicht deployed**.


### 22.11.2026 · Plesk-Passwortschutzkonten read-only identifiziert: geschützte Verzeichnisse 8 und 9, Domain-IDs 9 und 10

**Quelle:** Neue tatsächliche Betreiber-PuTTY-Ausgabe von `HW24_PLESK_AUTH_READ` (SELECT über `pd_users` JOIN `protected_dirs` mit den im vorherigen Schritt bestätigten Spalten). Der Block endete mit `PRUEFUNG ABGESCHLOSSEN`; keinerlei Änderungen wurden ausgeführt.

**Verifizierte Plesk-Zuordnungen:**
- `hw24-test`: `protected_dirs.id=9`, `dom_id=10`, `dom_type=domain`, `path` leer (Root-Verzeichnisschutz), `realm=hw24-test`.
- `nvoss`: `protected_dirs.id=8`, `dom_id=9`, `dom_type=domain`, `path` leer (Root-Verzeichnisschutz), `realm=HW24 Artikelmanager`.
- `nvoss` zusätzlich in separatem Statistikschutz: `protected_dirs.id=1`, `dom_id=1`, `path=plesk-stat`, `realm=Domainstatistiken`. **Dieser Statistikzugang ist nicht Teil der Umbenennung und darf nicht verändert werden.**

**Wichtig:** Die `dom_id`-IDs **9 und 10** sind im bisherigen Terminalausdruck noch **nicht** mit den tatsächlichen Hostnamen verknüpft. Nur aus dem Realm-Namen darf weder Test- noch Live-Domain abgeleitet werden. Auch wurde die tatsächliche doppelte Passwortschutz-Konfiguration nicht verändert. Die benannten Domain-IDs sind mit dem jetzt bekannten Plesk-Schema durch den nächsten **nur lesenden** Befehl `SELECT id,name,parentDomainId,htype FROM domains WHERE id IN (9,10)` abzugleichen. `dom_type=domain` ist durch das Terminal bestätigt; damit ist der `domains`-Join für **diese** beiden Zeilen plausibel und wird als nächster Schritt überprüft.

**Bestandsschutz:** Drei unveränderte `hw24_ai_reviews` gehören dem historischen `hw24-test`; zwei unveränderte `hw24_scan_batches` gehören getrennt je `hw24-test` und `nvoss`. Das separate noch nicht ausgerollte **Draft [PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37)** unterstützt serverseitig explizit erlaubten Lesezugriff auf beide Owner bei neuer aktueller Bearbeiterkennung. CI-Prüfungen #44978357305/#44978360796 bestanden. Eine Plesk-Login-Umbenennung **vor** gesicherter Zwei-Owner-Brücke würde Archiv/Stapel weiterhin ausblenden. Kein Live-/Test-Deploy, keine Accountmutation und kein SQL-UPDATE wurden im aktuellen Schritt ausgeführt.

**Nächster Read-only-Operatorblock:** `HW24_PLESK_DOMAINS` nur `domains`-ID/Name/Typ für 9 und 10, optional `protected_dirs`-id, dom_id, realm und passende `pd_users`-Namen ausgeben. **Keine Passwörter, keine Hashes, keine Accountänderung.** Erst nach exakter Zuordnung sowie Plesk-Backup-/Rollbackplanung gezieltes Umbenennen am korrekt ermittelten Test-Schutzbereich vorbereiten. Plesk-Statistikbereich `id=1` dabei ausdrücklich ausklammern.


### 22.11.2026 · Plesk Domain-IDs 9/10 und korrekte HTTP-Basic-Schutzkonten vollständig zugeordnet

**Quelle:** Neue Betreiber-PuTTY-Ausgabe `HW24_PLESK_DOMAINS` mit erfolgreichem SQL-`SELECT`, `PRUEFUNG ABGESCHLOSSEN`; keine Konto- oder Website-Änderung.

| Plesk-Domain-ID | Echte Domain | htype | parentDomainId | protected_dirs.id | Root-Pfad | Realm | Site-Basic-Konto |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **9** | `artikelmanager.hairworld24.de` (**LIVE**) | `vrt_hst` | `5` | **8** | leer = Root | `HW24 Artikelmanager` | bestehende künftig einheitliche Kennung |
| **10** | `test-artikelmanager.hairworld24.de` (**TEST**) | `vrt_hst` | `5` | **9** | leer = Root | `hw24-test` | bisherige Testkennung |

Die separate `protected_dirs.id=1` für `plesk-stat` auf `domains.id=1` ist **nicht** Teil dieses Vorhabens. Die erfolgreiche Abfrage belegt, dass Live und Test getrennte Plesk-passwortgeschützte Root-Verzeichnisse haben und **nur der Testzugang** umbenannt werden soll.

**Datenerhalt aus vorherigem PuTTY, unverändert:** 3 bestehende archivierte KI-Berichte unter altem Test-Basic-Actor, 0 unter neuem; 2 vorhandene Scanlisten-Stapel, jeweils einer unter altem und neuem Login. Daher **nicht** einfach jetzt den Test-Basic-Benutzer umbenennen, da die Legacy-APIs noch auf `WHERE actor=?` und `WHERE created_by=?` filtern; alte Berichte/Stapel würden für den neuen Login sonst unsichtbar. Originalzeilen unverändert lassen.

**GitHub-Korrektur dafür bereit:** [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37), HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482`, CI `44978357305` und `44978360796` erfolgreich, bietet künftig per privater `read_aliases`-Allowlist beide Eigentümer zum Lesen/Bearbeiten frei, neue Writes und Audit stets aktuelle Kennung. **PR noch nicht ausgerollt oder gemergt**, private Aliasdatei **noch nicht** erzeugt.

**Offizielle Plesk-CLI verifiziert:** [Plesk Obsidian `protdir`-Referenz](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/) bestätigt `-u <Verzeichnis> -domain <Domain> -update_user <Alt> -user_name <Neu>`. Die vorliegende Schutzzeile hat leeren DB-Pfad für Site-Root, während die [Plesk-Root-Schutz-Dokumentation](https://support.plesk.com/hc/en-us/articles/12377592309527-How-to-configure-a-password-protected-directory-in-Plesk) im UI `/` verwendet. **Konkrete CLI-Root-Parametrisierung vor einem mutierenden Kommando noch nicht auf dem Server geprüft; kein Blindversuch auf Live oder Test.**

**Sicherer nächster Gate:** Vor dem Auth-Rename die aktuell tatsächlich ausgerollten `api/scanliste.php`, `api/ai-reviews.php`, `api/ai-review.php` und die bestehenden Plesk-Git-Deployziele der **Test**- und **Live**-Site ausschließlich lesen; einen privaten Backup-/Rollbackpfad festlegen; anschließend den Dual-Owner-Code aus PR #37 **kontrolliert zuerst auf Test** bereitstellen und testen. Erst wenn beide Owner und 3 Reviews browserseitig sichtbar sind, den Test-Schutz-Account mit dem offiziellen Plesk-Tool gezielt umbenennen. **Die Testdomain bleibt vorläufig Legacy; Laravel/Filament-Webroot-Cutover ist ein anderes, späteres kontrolliertes Gate.**


### 22.11.2026 · Live- und Test-vHosts: Legacy-API-HASHES IDENTISCH, beide Plesk-Git-Deployments manuell auf `deploy/staging`

**Quelle:** Neue tatsächliche Betreiber-PuTTY-Ausgabe des Blocks `HW24_AUTH_DEPLOY_PREFLIGHT`; erfolgreiche Abschlussmeldung `Keine Dateien oder Benutzer veraendert`. Kein Deployment, kein Rename.

| Gegenstand | Live `artikelmanager.HAIRWORLD24.de` | Test `test-artikelmanager.HAIRWORLD24.de` |
| --- | --- | --- |
| Plesk-Git-Repository | `HW24-scanner` | `HW24-scanner.git` |
| Deployment path | `/artikelmanager.HAIRWORLD24.de` | `/test-artikelmanager.HAIRWORLD24.de` |
| Active branch | `deploy/staging` | `deploy/staging` |
| Deployment mode / Post-actions | `manual` / `disabled` | `manual` / `disabled` |
| `api/scanliste.php` Git-Blob | `e9fb8075c4f532d7a76adf81e760acf81cab4528` | **identisch** |
| `api/ai-reviews.php` Git-Blob | `00fb61c9d636434cdc6562edf169811a717c0911` | **identisch** |
| `api/ai-review.php` Git-Blob | `2eabde207434b8c62162dc553354cd052c6ec98f` | **identisch** |
| `api/hw24-actor-identity.php` | **NICHT VORHANDEN** | **NICHT VORHANDEN** |

**Interpretation:** Beide Webroots liefern in den vier abgefragten API-Dateien den bisherigen Stand aus, der der Basis des geprüften GitHub-Draft-PR #37 entspricht. Der neue Dual-Owner-Helfer ist **nirgends deployed**. Aus `git hash-object` folgt ein lokaler Dateiinhaltsnachweis, kein separater kompletter HTTP-E2E-Test oder Nachweis sämtlicher Codeversionen.

**Plesk-Auth zuvor exakt identifiziert:** Live-Domain-ID 9, Root-Schutz-ID 8 mit Basic-Login `nvoss`; Testdomain-ID 10, Root-Schutz-ID 9 mit Basic-Login `hw24-test`. Separate Statistik-ID 1 **nicht anfassen**.

**Echter Legacy-MariaDB-Bestand:** `hw24_ai_reviews` 3 historische Berichte unter `hw24-test`, keine unter `nvoss`; `hw24_scan_batches` 2 Stapel, je 1 unter `hw24-test` und `nvoss`.

**Code und Tests:** [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37) auf `feature/hw24-login-alias-scan-ai-20261122`, Head `c2cc4e1dc054da00f01087e7221659cf54a9a482`; CI [#44978357305](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978357305) und [#44978360796](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978360796) erfolgreich. Gewolltes Verhalten: `nvoss` liest mit privater und expliziter `read_aliases`-Freigabe historische `hw24-test`-Berichte und beide Scanlisten-Stapel; neue Vorgänge werden unter der aktuellen Basic-Kennung protokolliert; unveränderter Bestand; **kein automatischer KI-POST**.

**Nächste sichere Operation:** Ein *frisches privates, überprüfbares Test-Webroot-Rollback-Backup* erstellen, dessen Pfad nur unter `/root/hw24-backups` liegt, vor jedem manuellen Plesk-Git-Deploy / Account-Rename. Danach die bestehende **TEST**-Plesk-Git-Konfiguration und beabsichtigten Feature-Branch-Rollout genau absichern; **LIVE Plesk-Git-Branch nicht automatisch mitziehen**. Test-HTTP-Login `hw24-test` erst nach erfolgreichem Dual-Owner-Code-Test und privater Alias-Freigabe gezielt auf `nvoss` ändern. Separaten späteren Laravel/Filament-Webroot-Cutover nicht mit diesem Vorgang vermischen.


### 22.11.2026 · Frisches privates Test-Webroot-Backup ERFOLGREICH bestätigt (vor PR #37)

**Direkter Betreiber-PuTTY-Nachweis:** `HW24_TEST_BACKUP` vollständig erfolgreich ausgeführt. Das Skript prüfte die Existenz des Test-Webroots und der drei Legacy-PHP-APIs, legte mit `umask 077` ein neues privates Verzeichnis in `/root/hw24-backups/` an, archivierte nur `test-artikelmanager.HAIRWORLD24.de` mittels `tar --one-file-system` und bestätigte über `tar -tzf` den enthaltenen Pfad `test-artikelmanager.HAIRWORLD24.de/api/ai-reviews.php`. **Keine Website oder Benutzerkonten geändert.**

- **Sicherungsdatei auf Plesk:** `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`
- **Archivtest:** `Archivinhalt geprueft: JA`
- **SHA-256:** `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`
- **Abschluss:** `SICHERUNG ERFOLGREICH`, `Live, Test und Benutzerkonten unveraendert`, PuTTY-Prompt wiederhergestellt
- **Geltungsbereich:** Das ist eine private Sicherung der **Test-Webdateien**, keine eigenständige Sicherung der Plesk-Passwortschutz-Einträge, der produktiven MariaDB-Tabellen oder der Live-Webseite. Vor einem Konten-Rename zusätzlich den Test-Basic-Schutz separat sichern und dessen Rückfallweg prüfen; **keine Wiederherstellung durchgeführt**.

**Vorher bestätigt:** Beide Plesk-Webroots haben auf Branch `deploy/staging` im manuellen Deployment identische drei Legacy-API-Git-Blob-Hashes `e9fb8075...`, `00fb61c9...`, `2eabde20...`; `api/hw24-actor-identity.php` fehlt noch auf beiden Domains. Live Plesk-Basic-Kennung `nvoss`, Test `hw24-test`. SQL-Inventar: 3 KI-Berichte unter altem Actor, Scan-Stapel 1+1 unter alt/neu.

**GitHub-Draft PR #37:** `feature/hw24-login-alias-scan-ai-20261122`, Head `c2cc4e1dc054da00f01087e7221659cf54a9a482`; Tests/GitHub-CI #44978357305 und #44978360796 grün. Er implementiert `read_aliases` mit beiden Eigentümern als lesbarem Scope; neue Scan-/KI-Ereignisse bleiben unter aktueller Basic-Kennung. **PR nicht gemergt oder deployed**.

**Nächste kontrollierte Phase:** Nur die Testdomain: Vorbereitende Plesk-Git-Repository/Quellcode-Prüfung ohne Änderung von `deploy/staging`, kein Plesk-Test-Webroot-Cutover zu Laravel, kein Test-Basic-Namenswechsel bevor Zwei-Owner-Code und private Zuordnung im berechtigten Test-HTTP-Kontext geprüft wurden. Keine JTL-/WMS-/Shopify-Datenänderung oder KI-POST. Live unangetastet.


### 22.11.2026 · GitHub-Diff für sicheren Test-PR37-Rollout geprüft

**Quelle:** GitHub-Connector `compare_commits`: Base `deploy/staging`, Head `feature/hw24-login-alias-scan-ai-20261122`, **12 Commits vor Basis, 0 Commits zurück**, `status=ahead`. Pinned HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482`. Der Vergleich weist **genau 7** veränderte Dateipfade aus:

1. `.github/workflows/hw24-v425-intelligence-test.yml` (Testworkflow);
2. `api/ai-review.php` (bestehende KI-Review-Prüfung, historische Deduplikation);
3. `api/ai-reviews.php` (bestehender KI-Archiv-GET);
4. `api/hw24-actor-identity.php` (**neu**, private read_aliases);
5. `api/scanliste.php` (bestehende Scanlisten-API);
6. `tests/hw24-actor-alias.test.php` (**neu**, Sicherheits-/Bestandstests);
7. `tests/hw24-v425-article-intelligence.test.cjs` (bestehender Regressionstest).

**Nicht im PR-Diff:** Frontend-`index.html`, `scanliste.html`, JTL-/WMS-/Shopify-Connectoren, Datenbank-DDL, Live-Auth-Konfiguration oder Laravel/Filament-Dateien. Das belegt Codeumfang, **noch nicht** browserseitiges Verhalten. Zwei GitHub-CI-Jobs zum aktuellen HEAD sind grün.

**Direkt bestätigt:** Betreiber erstellte vorher das private Test-Webroot-Tar-Backup `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz` und verifizierte SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`. Es bleibt lokal unter `/root` und ist nicht als Datei in GitHub hochgeladen.

**Nun nächster kontrollierter Schritt:** Nur auf dem manuellen TEST-Plesk-Git-Repository `HW24-scanner.git`: Quellbranch fetchen, aktive Branch-Metadaten gezielt auf gepinnten PR37-Featurebranch umstellen, SHA verifizieren und **KEIN `--deploy`** aufrufen. Schutzgates vorher Backup SHA, aktuelle Branch = `deploy/staging`, Deploymentmode = `manual`. Bei Fehlstand gegebenenfalls Branch-Metadaten zurück auf `deploy/staging`; Live-Repository bleibt unverändert. Erst nach erfolgreichem Pin wird ein getrennt freigegebener kontrollierter TEST-Deploy mit optionaler privater Aliasdatei, HTTP-Regression und Plesk-Backup der Benutzerkonten vorbereitet.


### 22.11.2026 · PR37 Test-Plesk-Git-Branchvorbereitung: falscher Commit-Textvergleich, automatischer Metadaten-Rollback VERSUCHT

**Quelle:** Neue Betreiber-PuTTY-Ausgabe von `HW24_PR37_BRANCH_ONLY`. Das zuvor privat gespeicherte Backup wurde verifiziert; Plesk-Test-Git war zuvor `deploy/staging` und `Deployment mode: manual`. `plesk ext git --fetch` erfolgreich und `plesk ext git --update -active-branch feature/hw24-login-alias-scan-ai-20261122` lieferte keinen Fehler. `plesk ext git --get-last-commit` gab **nicht nur den SHA** aus, sondern einen mehrzeiligen Git-Commitblock mit `commit <SHA>`, Autor, Datum und Beschreibung. Das Skript entfernte alle Zeilenwechsel mit `tr -d '\r\n'` und verglich deshalb irrtümlich die komplette Zeichenkette `commit c2cc4e1...Author...Date...message` mit der isolierten Soll-ID.

**Konkrete Ausgabe:** `STOP: Unerwarteter Commit: commit c2cc4e1dc054da00f01087e7221659cf54a9a482Author: ... Date: ... test: update V4.25 ...` und `Branch-Rueckstellung versucht. Bitte Status pruefen.`. Der SHA **im Plesk-Auszug** ist exakt der erwartete GitHub-HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482`, d. h. **kein Git-Commit-Mismatch nachgewiesen**; vielmehr eine unsaubere Ausgabeformat-/Parser-Annahme im von der Assistenz gelieferten Skript.

**Wichtig zum aktuellen Live-Status:**
- Das Skript rief **kein** `plesk ext git --deploy` auf. Die Test-Website-Dateien sollten deshalb im manuellen Deployment unangetastet sein, **sind nach diesem konkreten Fehlversuch aber noch nicht erneut per Hash geprüft**.
- Ein `trap rollback EXIT` versuchte nach dem Prüf-Abbruch per `plesk ext git --update -active-branch deploy/staging` die Plesk-Test-Git-**Metadaten** zurückzustellen, schrieb CLI-Ausgabe aber nur in ein privates Root-Log und verwendete `|| true`; der sichtbare Satz `Branch-Rueckstellung versucht` **beweist nicht, dass der Branch zurückgestellt wurde**. Der tatsächliche Plesk-Branch ist daher aktuell **UNGEKLÄRT**. Vor einer neuen Änderung muss er **read-only** geprüft werden.
- Backup weiterhin gültig: `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`.
- Das Problem betrifft **nur** die Prüfung des ausgegebenen Plesk-Commitformats. Der GitHub-Draft PR #37 bleibt unverändert und GitHub-CI blieb zuvor grün. Noch **kein** Passwort-/Aliasdatei-/SQL-/Laravel-/Live-Deploy.

**Nächster EINZELNER Schritt:** Nur lesende `HW24_PR37_STATUS`-Abfrage für Test-Plesk-Git `HW24-scanner.git`: `Active branch`, `Deployment mode`, `Deployment path` anzeigen und `git hash-object` der drei Test-Webroot-PHP-Routen plus Existenz des neuen Helfers vergleichen. **Keine `--fetch`, `--update` oder `--deploy`**, bevor der Zustand sichtbar ist. Anschließend den Commitvergleich im Runbook unbedingt so reparieren, dass **nur die erste valide 40-stellige SHA nach `commit\s+`** extrahiert wird und nicht der gesamte Commitblock.


### 22.11.2026 · PR37 Git-Branch-Rollback jetzt tatsächlich bestätigt: Test weiterhin Legacy, keine neue PHP-Datei

**Quelle:** Nutzer-PuTTY-Ausgabe von `HW24_PR37_STATUS`, ausdrücklich nach dem fehlerhaften Commit-Parser-Alarm. Befehl nur lesend, beendet mit `STATUSPRUEFUNG ABGESCHLOSSEN`.

| Test-Plesk-Metadaten / Datei | Tatsächlich ausgegeben | Bewertung |
| --- | --- | --- |
| `Deployment path` | `/test-artikelmanager.HAIRWORLD24.de` | **BESTÄTIGT**, kein Laravel-Cutover |
| `Deployment mode` | `manual` | **BESTÄTIGT** |
| `Active branch` | `deploy/staging` | **BESTÄTIGT: Branch-Rollback erfolgreich** |
| `api/scanliste.php` | Git-Blob `e9fb8075c4f532d7a76adf81e760acf81cab4528` | **BESTÄTIGT: unverändert** |
| `api/ai-reviews.php` | Git-Blob `00fb61c9d636434cdc6562edf169811a717c0911` | **BESTÄTIGT: unverändert** |
| `api/ai-review.php` | Git-Blob `2eabde207434b8c62162dc553354cd052c6ec98f` | **BESTÄTIGT: unverändert** |
| `api/hw24-actor-identity.php` | `NICHT VORHANDEN` | **BESTÄTIGT: PR37 noch nicht deployed** |

**Konsequenz:** Die vorherige Plesk-SHA-Prüfung scheiterte durch einen falschen Vergleich der **mehrzeiligen** `--get-last-commit`-Ausgabe, obwohl der darin enthaltene HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482` korrekt war. Der automatische **Metadaten-Branch-Rollback** hat wie gewünscht funktioniert. Bitte das alte `HW24_PR37_BRANCH_ONLY`-Skript **nicht** erneut nutzen.

**GitHub neu verifiziert:** [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37) weiter exakt HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482`, Base `deploy/staging`, Draft. Die zwei zu diesem SHA gehörenden GitHub-CI-Läufe [#44978360796](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978360796) und [#44978357305](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978357305) weiterhin **success**.

**Nächster Schritt:** Nur TEST-Plesk-Git-Branch erneut auf gepinnten PR37-Head setzen, diesmal Commit-ID ausschließlich aus der `^commit <40 HEX>`-Zeile mittels `sed -nE` extrahieren, vorab Backup-SHA und bisherige drei Legacy-Webroot-Blob-Hashes prüfen und nach Branchwechsel nachweisen, dass der Test-Webroot nicht verändert wurde. **Kein** `plesk ext git --deploy`, **kein** Plesk-HTTP-Basic-Rename, **keine** Datenbank- oder Live-Änderung. Bei Fehler selektiv Branch-Metadaten zurück auf `deploy/staging` und tatsächlichen Status melden, keinen unbelegten Erfolg behaupten.


### 22.11.2026 · Plesk-Git Test-PR37-Featurebranch ERFOLGREICH vorbereitet, noch kein Deployment

**Tatsächliche neue Betreiber-PuTTY-Ausgabe** des korrigierten Blocks `HW24_PR37_SELECT`: `Backup: OK`; `plesk ext git --update ... -active-branch feature/hw24-login-alias-scan-ai-20261122` meldet `The repository "HW24-scanner.git" was updated.`; der korrigierte SHA-Parser lieferte exakt `c2cc4e1dc054da00f01087e7221659cf54a9a482`; die drei bestehenden Webroot-PHP-Dateien blieben unverändert; Ausgabe `Kein Deployment ausgefuehrt` und `PR37 BRANCH ERFOLGREICH VORBEREITET`.

**Bedeutung:** Der aktive Plesk-Git-Branch der **Testdomain** ist jetzt auf dem gepinnten Featurebranch, aber die WEBSITE selbst enthält weiter den Legacy-API-Stand. Die Live-Domain blieb auf `deploy/staging`; ihre Git-Konfiguration und ihr HTTP-Basic-Konto wurden nicht geändert. Historische MariaDB-Reviews (3 unter `hw24-test`) und Scan-Stapel (1 unter `hw24-test`, 1 unter `nvoss`) unverändert.

**GitHub unmittelbar erneut geprüft:** PR #37 ist weiter **draft**, Base `deploy/staging`, HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482`. Exakte Git-BLOB-SHAs der vier zu deployenden PHP-Dateien:
- `api/scanliste.php` → `1a19331fc030ef473d05cc0b94fee92fd2d436ea`
- `api/ai-reviews.php` → `41bfe53eba6f4392e4f0f48e92828fa004ffe311`
- `api/ai-review.php` → `aba03b6146a34698bae71fe622671945019d2db5`
- `api/hw24-actor-identity.php` (neu) → `cb2722952bd0cad9680d919630e4b2a06ba367fc`.

**Nun sicherster nächster Test-Schritt:** Plesk-Git-TEST-Repositorium **einmal manuell deployen** nach erneuter SHA-/Branch-/Backup-/Basisdatei-Prüfung und danach alle **vier** Git-BLOBs mit diesen gepinnten SHA-Werten vergleichen. Keinen Plesk-LIVE-Deploy, keine private Aliasdatei oder Test-Accountumbenennung im selben Befehl. Falls Plesk-Deploy fehlschlägt oder Dateihashes abweichen, **STOP** und Zustand lesen; nicht blind ein automatisches Git- oder TAR-Rollback über teilweise deployte Dateien durchführen. Das geprüfte Test-TAR `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz` mit SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26` bleibt Rückfalloption.


### 22.11.2026 · PR37 auf TEST erfolgreich deployed, vier PHP-Git-Blob-Hashes verifiziert (kein Basic-Rename, keine DB-Änderung)

**Direkter Betreiber-PuTTY-Beleg:** Der Block `HW24_PR37_TEST_DEPLOY` lief bis zur Abschlussmeldung durch. Vor Deploy waren `Backup, Branch und Commit: OK`. Danach wurde **nur für die Testdomain** ein manueller Plesk-Git-Deploy ausgeführt und `git hash-object` meldete viermal `OK`:
- `api/scanliste.php` → `1a19331fc030ef473d05cc0b94fee92fd2d436ea`
- `api/ai-reviews.php` → `41bfe53eba6f4392e4f0f48e92828fa004ffe311`
- `api/ai-review.php` → `aba03b6146a34698bae71fe622671945019d2db5`
- `api/hw24-actor-identity.php` → `cb2722952bd0cad9680d919630e4b2a06ba367fc`
- Abschluss `PR37 TEST-DEPLOYMENT ERFOLGREICH`, `Alle vier PHP-Dateien stimmen mit GitHub ueberein`, `Plesk-Benutzer und Datenbank unveraendert`.

**Bewertung:** Test-Legacy-Webroot hat die PR37-Dual-Owner-Implementierung als **Code** erhalten. Dieses Ergebnis ist **noch kein authentifizierter HTTP-/Browser-E2E**; die private `hw24_private/actor-aliases.php` wurde noch nicht eingerichtet, deshalb noch **keine** zusätzliche Owner-Berechtigung aktiviert. Das Test-Basic-Konto heißt weiterhin `hw24-test`; der Live-Basic-Zugang `nvoss` bleibt unverändert. Kein KI-Modellaufruf und keine historische Datenmigration.

**Sicherheits-/Rollback-Referenz:** Getestetes privates Test-Webroot-TAR `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`. Dieses TAR enthält Webdateien, **nicht** separat die Plesk-HTTP-Basic-Kontenkonfiguration.

**Genau nächstes Gate:** Plesk-PHP 8.5 `php -l` aller vier API-Dateien aus dem Test-Webroot und anonyme HTTPS-GET-Statusprüfung von `api/ai-reviews.php`, `api/scanliste.php` und `api/jtl.php` ausschließlich über Statuscode, **ohne Body-Inhalte und ohne API-POST**. Erwartet wird jeweils HTTP **401** (äußerer Plesk-Basic-Schutz). Falls Status anders oder PHP-Syntaxfehler, **STOP**, keine private Aliasdatei und keine Login-Umbenennung. Danach authentifizierten GET für den bisherigen Test-Basic-Benutzer prüfen (erwartet 3 KI-Reviews / 1 Scan-Stapel), private Mappingfreigabe (neue Kennung liest Alt+Neu) separat und mit passenden Rechten einrichten, HTTP-Basic-Konto vorher separat absichern, erst danach gezielt TEST umbenennen. Live-Webroot wird nicht geändert.


### 22.11.2026 · PR #37 Test-Deployment: PHP-Syntax und externer HTTP-Basic-Schutz bestätigt

**Betreiber-PuTTY-Nachweis:** `HW24_PR37_SECURITY` wurde erfolgreich abgeschlossen; ein rein lesender Check des Test-Webroots und drei anonymisierte (ohne Zugangsdaten) HTTPS-GET-Statusprüfungen.

| Sicherheits-/PHP-Gate | Tatsächlich ausgegeben | Status |
| --- | --- | --- |
| `/opt/plesk/php/8.5/bin/php -l api/scanliste.php` | `No syntax errors detected` | **BESTANDEN** |
| `/opt/plesk/php/8.5/bin/php -l api/ai-reviews.php` | `No syntax errors detected` | **BESTANDEN** |
| `/opt/plesk/php/8.5/bin/php -l api/ai-review.php` | `No syntax errors detected` | **BESTANDEN** |
| `/opt/plesk/php/8.5/bin/php -l api/hw24-actor-identity.php` | `No syntax errors detected` | **BESTANDEN** |
| Unauthentifizierter GET `/api/scanliste.php` auf Test | `HTTP 401` | **BESTANDEN: äußerer Basic-Schutz** |
| Unauthentifizierter GET `/api/ai-reviews.php` auf Test | `HTTP 401` | **BESTANDEN: äußerer Basic-Schutz** |
| Unauthentifizierter GET `/api/jtl.php` auf Test | `HTTP 401` | **BESTANDEN: äußerer Basic-Schutz** |
| Abschluss | `SICHERHEITSPRUEFUNG ERFOLGREICH`, `Keine Dateien, Konten oder Daten geaendert` | **BESTÄTIGT** |

**Aussagegrenze:** HTTP 401 ohne Anmeldung beweist wirksame äußere Basic-Authentifizierungsbarriere an diesen drei Routen, aber noch **keine** funktionierende authentifizierte Datenbank-/Browserabfrage, keine Ende-zu-Ende-Validierung alter/neuer Data-Owner-Scopes und keinen Test des KI-Review-POST. Die privat konfigurierbare `read_aliases`-Datei wurde noch nicht erstellt, der Test-Basic-Login bleibt `hw24-test`, Live-Basic-Login `nvoss` unverändert. Bestehender Datenbestand weiter: 3 alte KI-Berichte, Scanliste 1 alter / 1 neuer Stapel, keine historischen UPDATEs.

**Als nächstes genau EIN Betreiberblock:** Authentifizierte **GET-ONLY**-Abfrage beider Test-APIs mit aktuellem `hw24-test`-Login, ohne Passwort auf der Kommandozeile zu übergeben: `curl --user hw24-test` fragt das Passwort direkt verborgen ab. **Keine JSON-Rohdaten ausgeben**, sondern serverseitig per Plesk-PHP 8.5 aus HTTP-GET-JSON nur `ok`, Archivanzahl und offene Batch-Anzahl extrahieren; `set -o pipefail` und `curl --fail-with-body` aktivieren. Für das KI-Archiv sind 3 gespeicherte Reviews zu erwarten, für Scanlisten 1 Stapel **falls nicht geschlossen** (API filtert `closed_at_utc IS NULL`). Bei Abweichung keine Daten nachträglich ändern, sondern Ursache per Read-only-SQL oder API-Fehlercode prüfen. Anschließend erst private Zwei-Owner-Alias-Konfiguration und Plesk-Basic-Umbenennung mit getrenntem Rückfall-Gate.

**Backup:** `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`, bestätigt. Test-PHP-Git-Blob-Hashes entsprechen dem gepinnten PR37-Commit `c2cc4e1dc054da00f01087e7221659cf54a9a482` (vier Dateien). [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37) bleibt unverändert, GitHub-CI zum SHA erfolgreich.


### 22.11.2026 · Authentifizierte HTTPS-Regression nach PR37 erfolgreich: 3 KI-Berichte, 1 offener alter Scan-Stapel

**Quelle:** Tatsächliche neue Betreiber-PuTTY-Ausgabe des Blocks `HW24_AUTHENTICATED_GET`. Der bestehende Test-HTTP-Basic-Benutzer `hw24-test` wurde jeweils mit **interaktiv verborgenem Passwort** bei `curl --basic --user hw24-test` erfolgreich authentifiziert; kein Passwort oder Antwort-JSON wurde ausgegeben.

| Authentifizierte Test-API | PuTTY-Nachweis | Ergebnis |
| --- | --- | --- |
| `GET /api/ai-reviews.php?limit=50` | `Historische KI-Berichte: 3` | **PASS**; alle 3 archivierten Analysen für historischen Actor im neuen PR37-Code lesbar |
| `GET /api/scanliste.php?op=batches` | `Offene Scanlisten-Stapel: 1` | **PASS**; 1 bisheriger offener Scan-Stapel für alten Actor lesbar |
| Abschluss | `AUTHENTIFIZIERTER GET-TEST ERFOLGREICH`; `Keine Daten veraendert` | **PASS**, keine API-POSTs, keine KI-Kosten |

**Wichtiger Status:** Privates `hw24_private/actor-aliases.php` ist bisher **nicht** auf Plesk angelegt, daher der neue Actor `nvoss` auf TEST **noch nicht** autorisiert und die beiden bisherigen getrennten Eigentümeransichten noch nicht zusammengeführt. Das Test-Plesk-Basic-Konto heißt weiter `hw24-test`; Plesk LIVE ist bereits `nvoss`, bleibt unberührt. SQL-Bestand unverändert: 3 KI-Reviews `hw24-test`, Scan-Stapel gesamt 2, davon je 1 `hw24-test` und `nvoss`. Zwei-Owner-Hilfsdatei `api/hw24-actor-identity.php` vom gepinnten PR37-HEAD auf TEST deployed und via `php -l` geprüft; ihre Funktion `hw24ReadableOwners` wurde im GitHub-Featurebranch abermals auf die Pfad-/Config-Form `['read_aliases'=>['nvoss'=>['hw24-test']]]` gegengeprüft.

**Nächstes isoliertes Gate:** Die private, serverseitig lesbare PHP-Zuordnungsdatei **nur falls nicht existent** an `/var/www/vhosts/hairworld24.de/hw24_private/actor-aliases.php` mit genau `nvoss => [hw24-test]` anlegen. Vorher sicherstellen, dass das Verzeichnis existiert, außerhalb des Test-Webroots liegt, kein Symlink ist, der FPM-Systembenutzer `nicovoss` das bestehende `scanlist-db.php` lesen kann und die neu zu schreibende Datei NICHT bereits vorhanden ist. Datei als `nicovoss:psacln`, Modus `0600` erstellen; bei vorhandener Datei **STOP ohne Überschreibung**. Mit demselben Plesk-PHP 8.5 unter `runuser -u nicovoss` nur die neue `hw24ReadableOwners('nvoss')`-Rückgabe testen: exakt `['nvoss','hw24-test']`, `hw24-test` weiterhin ausschließlich sich selbst. Kein Benutzer-Rename, keine DB-/Artikel-/API-Datenmutation und kein neuer KI-POST. **Aktuellen Erfolg nicht als bereits erfolgte Alias-Konfiguration darstellen.**

**Rollback-Absicherung:** Verifiziertes Root-TAR der bisherigen TEST-Webdateien `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`. Kein separates Passwortschutz-Backup: vor späterem Plesk-Test-Basic-Umbenennen noch schaffen.


### 22.11.2026 · Private read_aliases-Datei erfolgreich erstellt, funktional getestet, HTTP-Basic noch unverändert

**Direkter neuer Betreiber-PuTTY-Nachweis `HW24_PRIVATE_READ_ALIASES` mit erfolgreicher Schlussmeldung:**
- `Private Datei sicher erstellt` und `Dateirechte: 600`.
- `nvoss: beide Datenzuordnungen freigegeben` bestätigt, dass die deployed PHP-Helferfunktion bei `hw24ReadableOwners('nvoss')` exakt `['nvoss','hw24-test']` zurückgibt.
- `hw24-test: bisherige Berechtigung erhalten` (nur alter Actor) und `Fremdbenutzer: kein zusaetzlicher Zugriff`.
- `BENUTZERZUORDNUNG ERFOLGREICH`; `Plesk-Logins und Datenbank unveraendert`.

**Technische Ausführung (aus Nutzerausgabe):** Private Datei `/var/www/vhosts/hairworld24.de/hw24_private/actor-aliases.php` wurde mit `read_aliases: nvoss=>['hw24-test']`, `0600`, `nicovoss:psacln` außerhalb der beiden öffentlichen Webroots angelegt. Die neue Test-API-Helferfunktion wurde als Dienstbenutzer `nicovoss` via Plesk PHP 8.5 CLI überprüft. Es erfolgte **kein** Rechtemodell-Upload in GitHub, keine Weitergabe von Passwörtern oder Passwort-Hashes.

**Sicherheitsnuance:** Das konkrete PR37-Modell erlaubt dem autorisierten `nvoss` nicht nur das Lesen der alten Scan-Stapel, sondern über `getBatch` auch bestehende POST-Bearbeitungen; neue Audit/Scan-Einträge werden mit dem tatsächlich angemeldeten `nvoss` geschrieben. Nicht fälschlich als rein lesende Scanliste beschreiben. Kein HTTP-Test `nvoss` auf TEST bisher möglich, weil die TEST-HTTP-Basic-Kennung noch `hw24-test` ist. LIVE hat bereits `nvoss`, aber unveränderte Legacy-Code-Dateien; der private Mapping-Eintrag allein verändert die Live-APIs nicht.

**Vollständiger Datenbeleg vor Mapping:** Authentifizierte zwei Test-HTTPS-GETs unter `hw24-test`: 3 historische KI-Berichte und 1 offener Scanlisten-Stapel. SQL-Stapel gesamt 2 (1 alt + 1 neu). Geprüfter Test-Webroot-Rollback-TAR: `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`.

**Offizielle Recherche:** Plesk Obsidian [Virtual Host Structure (Linux)](https://doc.plesk.com/en-US/obsidian/administrator-guide/web-hosting/website-directory-structure/virtual-host-structure-linux.68806/) dokumentiert Plesk-`pd` als Speicher für Passwortschutzdateien unter `/var/www/vhosts/system/<vhost>/pd`. [Plesk protdir CLI](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/) dokumentiert `-update_user` mit `-user_name` für geschützte Verzeichnisse, wobei die genaue Plesk-Root-Verzeichnis-Parametrisierung für diesen Server vor mutierender Rename noch zu prüfen ist. Sicherungsbedarf bleibt.

**NÄCHSTER EINZELNER Schritt:** Den TEST-Plesk-Passwordschutz read-only inventarisieren und die tatsächlich vorhandene TEST-vHost-`pd`-Dateistruktur unter einem weiteren privaten `/root/hw24-backups/test-plesk-auth-XXXXXXXX` als root-restricted TAR sichern, zusätzlich `protected_dirs`-/`pd_users`-Metadaten ohne Kontopasswörter/Hashes in privater Textdatei. Danach Archiv öffnen/listen **nur zur Verifikation**, in Terminal ausschließlich Dateianzahl, Backup-Pfad und SHA256 ausgeben. Keine SQL-Writes, kein Benutzer-Rename oder Live-`pd`-Eingriff. Wenn `pd` fehlt oder abweicht, STOP und tatsächliche Auth-Datei-Orte zuerst read-only ermitteln.


### 22.11.2026 · TEST-Plesk-HTTP-Basic-Snapshot ERFOLGREICH: Rückfallsicherung vor Benutzernamenwechsel

**Echter Betreiber-PuTTY-Nachweis** `HW24_PLESK_BASIC_BACKUP`, vollständige erfolgreiche Ausgabe:
- Plesk-Test-Protected-Dir-SQL-Zuordnung `pd.id=9`, `dom_id=10`, `dom_type=domain`, `d.name=test-artikelmanager.hairworld24.de`, `pu.login=hw24-test` vor dem Backup exakt 1 Treffer.
- **Privater Metadatenexport:** `/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-protected-dir-metadata.tsv` vorhanden.
- **Privates Test-Auth-Archiv:** `/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-pd.tar.gz` wurde aus `/var/www/vhosts/system/test-artikelmanager.hairworld24.de/pd` erstellt und mittels `tar -tzf` als lesbar nachgewiesen.
- **SHA256 exakt:** `a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f`.
- **Abschluss:** `TEST-PASSWORTSCHUTZ ERFOLGREICH GESICHERT`, `Keine Benutzer oder Daten veraendert`.

**Sicherheit:** Das TAR enthält potenziell HTTP-Basic-Passworthashes und weitere Plesk-Auth-Einträge. Es befindet sich ausschließlich im Root-geschützten `/root/hw24-backups` mit Skript-`umask 077`; weder Archivinhalt, Passwort, Hashzeilen noch Plesk-Account-Anmeldedaten im öffentlichen GitHub dokumentieren. Ein Hashwert der Datei als Prüfsumme ist keine Freigabe ihrer Inhalte. Dieser Snapshot plus SELECT-Metadaten ist **kein vollständiges Plesk-Server-Backup**. Das ältere Test-Webroot-TAR `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz` mit SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26` bleibt ebenfalls vorhanden.

**Vor Rename bereits bestätigt:** PR37 auf TEST deployed (vier PHP-Blobs), 4× PHP-Lint OK, drei anonymisierte HTTPS-GETs 401, authentifiziert unter `hw24-test` alte 3 KI-Berichte + 1 offener Scan-Stapel. Private `hw24_private/actor-aliases.php` `0600`, Owner `nicovoss:psacln`, unter Plesk PHP8.5 getestet: `hw24ReadableOwners('nvoss') = ['nvoss','hw24-test']`, alter User bekommt nur alt, andere User nur sich selbst. Live HTTP-Basic-/Webroot unverändert, `plesk-stat` unberührt.

**Offizielle Plesk-Dokumentation extern geprüft:** [Obsidian protdir](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/) dokumentiert `plesk bin protdir -u <dir> -domain <domain> -update_user <old> -user_name <new>`. [Plesk Root-Passwortschutz](https://support.plesk.com/hc/en-us/articles/12377592309527-How-to-configure-a-password-protected-directory-in-Plesk) schreibt für die gesamte Site `/` vor. Die genaue CLI-Kombination `-u '/'` für diese konkreten `protected_dirs.path`-Leerwerte ist eine **plausible, noch nicht tatsächlich auf diesem Server bestätigte Parametrisierung**. In keinem Fall Login-Änderungen durch direktes UPDATE der Plesk-DB oder manuelles Editieren der Auth-Hashdatei vornehmen.

**Nächster gezielter Schritt:** Der Test-Basic-Name `hw24-test` soll nach Guard-Checks **nur auf `test-artikelmanager.hairworld24.de`** mit `plesk bin protdir -u '/' -domain ... -update_user hw24-test -user_name nvoss` geändert werden; vor und nachher Plesk SQL auf Schutz-ID 9 / Domain-ID 10, vorhandene Sicherungschecksummen, private Alias-Funktion und HTTP-401 prüfen. Wenn Plesk `/` nicht akzeptiert, STOP ohne weiteren CLI-Versuch/kein DB-UPDATE. Bei einem Fehler nach CLI-Ausführung keine unkontrollierte TAR-Rücksicherung; nur gezielte Statusdiagnose. Nach erfolgreichem Plesk-Rename den neuen `nvoss`-HTTPS-GET ohne Passwortausgabe verifizieren: KI-Archiv 3 Berichte, Scanliste für beide Eigentümer (offene Stapel abhängig `closed_at_utc`). Kein Live-Deploy und keine Änderung der drei KI-/zwei Scan-Datensätze.


### 22.11.2026 · TEST-PLESK-Basic-Login tatsächlich erfolgreich von `hw24-test` auf `nvoss` umbenannt

**Direkter Betreiber-PuTTY-Nachweis:** `HW24_PLESK_RENAME_TEST` lief mit der Ausgabe `Beide Sicherungen: OK`, `Private Benutzerzuordnung: OK`, `SUCCESS: Update of protected URL '/' completed.`, `TESTBENUTZER ERFOLGREICH UMBENANNT`, `Neuer Benutzer: nvoss`, `Kontoreferenz unveraendert`, `Anonymer HTTPS-Zugriff: HTTP 401`, `Keine Artikel- oder Datenbankaenderungen` erfolgreich durch.

**Technik:** Nur `plesk bin protdir -u '/' -domain test-artikelmanager.hairworld24.de -update_user hw24-test -user_name nvoss`. Vorher Backup-SHA256 beider rootgeschützten Archive kontrolliert und private read_aliases-PHP-Zuordnung geprüft. Danach SQL: `pd_users` hat neues Login `nvoss` für `protected_dirs.id=9`, `dom_id=10`, **mit identischer `account_id`**; `hw24-test` in dieser Schutz-Zone nicht mehr vorhanden; anonymer HTTPS-GET auf die Test-KI-Archiv-API weiterhin HTTP 401. Kein direktes Plesk-DB-UPDATE, keine Benutzerpasswort-Neuvergabe, kein Live-/Statistik-Schutz-Eingriff. Die Test-Basic-Login-ID wurde geändert; das Passwort muss im nächsten Schritt erstmals praktisch via HTTPS geprüft werden.

**Testdomain-Code:** PR37 ist bereits deployed und vier PHP-Git-Blob-Prüfungen, vier Syntaxprüfungen und drei anonyme 401-Checks bestanden. Die private Datei `/var/www/vhosts/hairworld24.de/hw24_private/actor-aliases.php` existiert mit `read_aliases => ['nvoss'=>['hw24-test']]`, Besitzer `nicovoss:psacln`, Rechte `0600`; Test unter Dienstbenutzer: `hw24ReadableOwners('nvoss') = ['nvoss','hw24-test']`; `hw24-test` und andere Accounts bekommen keine zusätzliche Freigabe. Bereits **vor** Plesk-Rename über HTTPS unter altem Testuser bestätigt: 3 historische KI-Berichte, 1 offener alter Scan-Stapel. SQL-Gesamtbestand: 3 KI-Reviews `hw24-test`, 2 Scan-Stapel (1 `hw24-test`, 1 `nvoss`). Ob der neue Scan-Stapel noch offen ist, bleibt zu prüfen.

**Root-only Rückfall-Sicherungen:** Test-Plesk-`pd`-Archiv `/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-pd.tar.gz`, SHA256 `a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f`, plus `plesk-protected-dir-metadata.tsv`; Test-Webroot-Archiv `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`. **Keine** Passwort-Hashes oder Geheimnisse ins öffentliche GitHub-Projekt übertragen.

**Nächster isolierter Schritt:** Jetzt *nur lesend* HTTPS-GET der TEST-APIs als neuer Basic-User `nvoss`, mit `curl --basic --user nvoss` und Passwortabfrage im Terminal, kein Passwort in Shellargumenten, GitHub oder Chat. Nur PHP-ausgewertete JSON-Zählwerte für `/api/ai-reviews.php?limit=50` (erwartet **3**) und `/api/scanliste.php?op=batches` (offene Stapel, maximal 2, mindestens 1) ausgeben. Optional separat read-only SQL-`closed_at_utc`-Summen pro Actor bestätigen, um erwartete 1–2 offenen Stapel exakt zu unterscheiden. **Kein KI-POST, kein SQL-UPDATE, kein weiterer Plesk-Rename oder Live-Deploy.** Bei falscher Auth/Archivanzahl STOP, Rollback erst nach Zustandsdiagnose.


### 22.11.2026 · AUTHENTIFIZIERTER NVOSS-HTTPS-ENDTEST BESTANDEN: 3/3 KI-Berichte + 2/2 offene Scanlisten-Stapel

**Neuer direkter Betreiber-PuTTY-Nachweis:** Nach dem auf Test erfolgten Plesk-Basic-Rename von `hw24-test` auf `nvoss` wurde `HW24_NVOSS_GET_VERIFY` komplett erfolgreich ausgeführt. Beide echten HTTPS-GETs wurden mit `curl --basic --user nvoss` und interaktiver **verdeckter** Passwortabfrage autorisiert. Das Antwort-JSON wurde nur per lokalem PHP-Parser zu Zählwerten verarbeitet; keine Rohdatensätze, EANs, Passwörter oder KI-POSTs ausgegeben.

| Authentifizierter HTTPS-Test unter `nvoss` | Echte PuTTY-Zeile | Befund |
| --- | --- | --- |
| `GET https://test-artikelmanager.hairworld24.de/api/ai-reviews.php?limit=50` | `KI-Berichte unter nvoss: 3` | **PASS**: alle 3 historischen `hw24-test`-KI-Berichte unter `nvoss` abrufbar |
| `GET https://test-artikelmanager.hairworld24.de/api/scanliste.php?op=batches` | `Offene Scanlisten-Stapel unter nvoss: 2` | **PASS**: beide offenen Stapel, historisch `hw24-test` und `nvoss`, unter `nvoss` im gemeinsam erlaubten Owner-Scope sichtbar |
| Gesamt | `NVOSS HTTPS-TEST ERFOLGREICH`; `Keine Daten veraendert` | **PASS**, keine Datenmigration/Artikeländerung oder kostenpflichtigen KI-Aufrufe |

**Erreichte Test-Login-Ziele:** Der HTTP-Basic-Username wurde über Plesk `protdir -u '/'` ausschließlich für die Testdomain auf `nvoss` geändert; ursprüngliche Passwortkontoreferenz unverändert. Vor- und Nach-Checks des äußeren Basic-Schutzes ergaben HTTP 401 ohne Credentials. Test-Code aus Draft PR #37 (gepinnter HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482`) mit vier verifizierten Git-PHP-BLOBs, vier erfolgreichen PHP8.5-Syntaxchecks, alter Basic-User vor Umbenennung 3 Berichte und 1 Stapel per HTTPS, privater `read_aliases`-Datei `nvoss=>[nvoss,hw24-test]` erfolgreich getestet. Nach Umbenennung der neue User tatsächlich **3 Berichte + 2 offene Stapel** per HTTPS. SQL-Daten nicht umgeschrieben, keine neuen KI-POSTs.

**Wichtige Grenzen:** Dieser GET-Test ist **kein** GUI-/Mobile-/Browser-Smoketest und bestätigt noch nicht explizit den HTTP-Fehlerstatus des zuvor gültigen `hw24-test`-Logins **nach** dem Rename. Er beweist auch nicht alle Änderungs-/POST-Pfade oder JTL/WMS/Amazon/Shopify-Produktion; nicht testen ohne gesonderte Risikoabsicherung. Live-Test-/Live-Git-Versionen nicht angleichen: Die produktive `artikelmanager.hairworld24.de` benutzt weiter Live-API-Altstand `deploy/staging` und Basic `nvoss`; PR37 ist **nur auf TEST deployed**, noch nicht in `deploy/staging` gemergt. Späterer Laravel/Filament-Testdomain-Cutover ist ein getrenntes Projekt.

**Gesicherte Rollback-Artefakte:** Privates TEST-Webroot-TAR `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`; privates TEST-Plesk-`pd`-TAR `/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-pd.tar.gz`, SHA256 `a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f`. Beide unter Root privat belassen, niemals Klartext-/Hash-Authentifizierungsdateien in GitHub veröffentlichen.

**Nächster Qualitäts-Gate, bevor Login-Thema als fertig freigegeben wird:** Ohne Datenmutation die alte Test-Basic-Kennung `hw24-test` mit **gleichem bisherigem Passwort** per interaktiver verdeckter Eingabe an Test-API prüfen und nur HTTP 401 erwarten, und mindestens eine aktuelle Test-API mit `nvoss` nachweislich im echten Browser öffnen (KI-Archiv + Scanliste/UI). Keine User-Rename-Wiederholung und kein produktiver Deploy erforderlich. Danach User ausdrücklich über erfolgreichen Testabschluss und optionalen späteren getrennten Live-Deploymententscheid informieren.


### 22.11.2026 · KRITISCHER NEUER BEFUND: ALTER Basic-Login `hw24-test` ERHÄLT WEITER HTTP 200 trotz Plesk-Rename; GUI-Historie unter anderem Aufruf noch unsichtbar

**Direkte Benutzer-PuTTY-Ausgabe:** Der zuvor vollständig erfolgreiche GET-ONLY-Test unter neuem Basic-Benutzer `nvoss` hat erneut `KI-Berichte unter nvoss: 3` und `Offene Scanlisten-Stapel unter nvoss: 2` bestätigt. Danach wurde `HW24_OLD_BASIC_DENY` unter altem Login `hw24-test` mit **interaktiv verdeckt eingegebenem Passwort** gegen `GET https://test-artikelmanager.hairworld24.de/api/ai-reviews.php` ausgeführt. Tatsächlich **`Alter Testbenutzer: HTTP 200`**, dann `STOP: Alter Zugang nicht wie erwartet gesperrt`. **Der Alt-Login ist nachweislich NOCH AKTIV im effektiven Webserver-HTTP-Basic-Schutz, trotz zuvor bestätigter Plesk-Benutzer-DB-Umbenennung** (oder eine andere Auth-Kette akzeptiert ihn). Nicht sagen, er sei deaktiviert oder das Login-Thema abgeschlossen. Kein SQL-Update, kein neues Plesk-Protdir-Kommando und kein KI-POST im Negativtest.

**Zusätzliche Benutzerbeobachtung:** In seiner Benutzeroberfläche sieht er unter dem „neuen / nvoss“ noch keine Prüfhistorie, „nur unter Test“. Vorher exakt verifiziert: Alias-Code PR37 wurde ausschließlich auf **TEST** bereitgestellt, die **Live-Domain** `artikelmanager.hairworld24.de` ist weiterhin auf **alter** Legacy-API-Variante (`deploy/staging`), ohne `api/hw24-actor-identity.php`; deren Live-HTTP-Basic heißt ohnehin `nvoss`. Wenn er die Live-URL ansieht, ist der Historienverlust dort zunächst **erwartbar**, weil Live altes `hw24-test` nicht in den erlaubten Scope einschließt. Die private read_aliases-Datei allein ändert Live nicht. Auch auf Test kann ein vom Browser gecachter Alt-Basic-Name bei weiterhin aktivem Altuser die Datenansicht beeinflussen; eindeutigen Browser-E2E-Beleg gibt es noch nicht.

**GitHub-Code jetzt erneut direkt geprüft:** `index.html` bindet `assets/hw24-ai-history.js?v=1` und `assets/hw24-ai-cost.js?v=2` ein. `hw24-ai-cost.js` erstellt Button `📂 Auswertungen`, der `window.HW24AiHistory.open()` aufruft; `hw24-ai-history.js` ruft `fetch('api/ai-reviews.php?limit=30')` mit `credentials:'same-origin'` und `cache:'no-store'` auf und bietet `Alle letzten Prüfungen` (schaltet EAN-Filter aus) und `Aktuelle EAN` (filtert). Die UI-KI-Historie müsste **auf TEST mit tatsächlich authentifiziertem `nvoss`-Kontext** die drei Berichte anzeigen, sofern Browser nicht mit `hw24-test`-Session/Altcredentials oder Live-URL arbeitet. Live bleibt bis gesondert freigegebenem Deployment inkompatibel.

**OFFIZIELLE QUELLEN:** Plesk Obsidian [Virtual Host Structure Linux](https://doc.plesk.com/en-US/obsidian/administrator-guide/web-hosting/website-directory-structure/virtual-host-structure-linux.68806/) speichert Auth-Dateien in `/var/www/vhosts/system/<vhost>/pd`. [Apache-Einstellungen verzögert angewandt](https://support.plesk.com/hc/en-us/articles/12377516833559-Apache-settings-are-not-applied-immediately-in-Plesk) nennt Apache-Restart-Intervall als potenzielle Ursache; ist **nicht** als Erklärung für diesen speziellen HTTP200-Fall nachgewiesen. Zu beachten sind mögliche `auth_basic_user_file`-/`AuthUserFile`-Referenzen, Plesk-DB-Einträge und Browser-Caching.

**Nächster isolierter Schritt:** Ein **read-only** PuTTY-Inventar der TEST-`protected_dirs`-/`pd_users`-Logins (ohne Password-Hashes), der Passwortdatei-Benutzernamen in Plesk `pd` (niemals Hashzeilen ausgeben), sowie der `auth_basic_user_file`-/`AuthUserFile`-Referenzen in tatsächlichen Apache-/nginx-/Webroot-Konfigurationsdateien. Erst nach Sichtung gezielte sichere Korrektur der Plesk-Webserver-Authentifizierung planen. **Kein blindes Apache/nginx-Reboot, kein direktes Löschen/Editieren von pd-Dateien, kein Live-Deploy.** Im Browser separat richtige Domain `https://test-artikelmanager.hairworld24.de` und `📂 Auswertungen` > `Alle letzten Prüfungen` in privatem Fenster/ohne alte Basic-Credentials überprüfen.


### 22.11.2026 · ROOT CAUSE nach Nutzer-Trace belegt: Plesk-Test-`pd_users` hat nur `nvoss`, effektive Datei `pd/d..httpdocs` noch beide Logins

**Neue PuTTY-Ausgabe `HW24_BASIC_AUTH_TRACE` erfolgreich, ausschließlich lesend:**
- `plesk db`: Für Test-`protected_dirs.id=9`, `dom_id=10` wird **nur `nvoss`** gelistet.
- Serverdatei `/var/www/vhosts/system/test-artikelmanager.hairworld24.de/pd/d..httpdocs`: **`hw24-test=JA nvoss=JA`**, d. h. beide Einträge weiterhin vorhanden.
- Test-`conf/httpd.conf`, `conf/last_httpd.conf` (Apache `AuthUserFile`) und `conf/nginx.conf`, `conf/last_nginx.conf` (`auth_basic_user_file`) zeigen **alle exakt auf diese Datei**.
- Abschluss `DIAGNOSE ABGESCHLOSSEN`, keine Hash- oder Passwortausgabe und keine Dateien/Accounts geändert.

**Ursache nun konkret:** Plesk-`protdir -update_user` hat zwar den Admin-DB-Benutzer auf `nvoss` geändert, aber die Webserver-Passwortdatei enthält einen **verwaisten alten Eintrag** `hw24-test`. Darum zeigte der anschließende Altuser-Negativtest tatsächlich HTTP 200. Keine generelle Server-/Browsercaching-Erklärung mehr nötig. **Keinen Apache/nginx-Restart benötigen**, da `AuthUserFile`/nginx `auth_basic_user_file` direkt die Datei verwenden.

**Geplante minimalinvasive Korrektur nur Test:** Offiziell dokumentiertes `htpasswd -D <passwdfile> <username>` zum Löschen genau des verwaisten Benutzers aus der tatsächlichen Test-`pd/d..httpdocs` Datei ([Apache htpasswd Doku](https://httpd.apache.org/docs/current/en/programs/htpasswd.html)). Vorher strenge Guards: Plesk-DB-Test-Altuser = 0, neuer = 1; Datei enthält je einen `hw24-test:` und `nvoss:` Eintrag, keine Symlinks; Root-only privates Archiv `/root/hw24-backups/test-pd-cleanup-XXXXXXXX/d..httpdocs.before` mit `cp -a` sichern. Danach `htpasswd -D` genau altes Login, ursprüngliche Owner/Mode durch Referenz auf Snapshot wahren; nochmal ohne Hashinhalte prüfen: alt=0, neu=1, anschließend **echte HTTPS-GETs** mit verdeckter Passwortabfrage: alter Login erwartet 401 und neuer Login 200. Bei Sicherheits- oder HTTP-Abweichungen STOP ohne blinde Wiederherstellung des alten Zugriffs. Niemals Live-`pd`-Datei, Live-Git oder MariaDB verändern. **Dieser Fix ist noch NICHT durchgeführt**; Benutzer muss PuTTY-Block ausführen.

**Unabhängige UI-Frage:** Nutzer sieht auf „neuem nvoss“ keine Prüfhistorie, „nur unter Test“. Auf **TEST** sind 3 KI-Berichte und 2 offene Scan-Stapel via `nvoss` definitiv über HTTPS abrufbar, der geänderte PR37-Backendcode ist ausschließlich dort deployed. **LIVE** `artikelmanager.hairworld24.de` hat weiterhin keine PR37-Owner-Bridge; deshalb historische Prüfungen dort noch nicht unter `nvoss` verfügbar. Der Benutzer kann auf TEST `📂 Auswertungen` → `Alle letzten Prüfungen` nutzen; Live-Rollout erst nach Sicherheits-Abnahme separat vorbereiten.


### 22.11.2026 · TEST-LOGIN SICHERHEITSPROBLEM BEHOBEN: ALT 401, NVOSS 200

**Direkter PuTTY-Nachweis des Nutzers:** `HW24_FIX_OLD_LOGIN` lief vollständig erfolgreich. Preflight: Plesk-DB für Test-Schutz-ID 9 `hw24-test=0`, `nvoss=1`; tatsächliche Test-Passwortdatei enthielt zuvor beide Benutzernamen. Der echte Datei-Stand wurde nach `/root/hw24-backups/test-pd-cleanup-4lfU4lrd/d..httpdocs.before` privat gesichert, dann gezielt **nur** `hw24-test` mittels `htpasswd -D /var/www/vhosts/system/test-artikelmanager.hairworld24.de/pd/d..httpdocs hw24-test` entfernt. Dateiinhaber/Modus anhand Sicherung erhalten und nur `nvoss` nachgewiesen. Anschließender tatsächlicher HTTPS-Basic-Negativtest: **`hw24-test: HTTP 401`**; Positivtest: **`nvoss: HTTP 200`**. Schlussmeldung `TESTANMELDUNG ERFOLGREICH BEREINIGT`, `Alt: gesperrt | Neu: funktionsfaehig`, `Live, Datenbank und Artikeldaten unveraendert`. KEIN Apache/nginx Restart, keine Auth-Passworthashes geteilt.

**Bereits vor diesem gezielten Fix als `nvoss` auf Test bestätigt:** `KI-Berichte unter nvoss: 3`, `Offene Scanlisten-Stapel unter nvoss: 2` über echte authentifizierte HTTPS-GETs. Private PHP-Zuordnung `read_aliases: nvoss=>['hw24-test']`, 0600, `nicovoss:psacln`; alte historische Actor-Spalten und SQL-Bestände unverändert. Draft PR #37 ist ausschließlich auf TEST deployed, vier passende Git-Blob-Hashes / vier PHP8.5-Lints bestätigt, Live weiterhin auf Legacy-Git-Stand und live HTTP Basic `nvoss`. Das **Test-Loginmigration-Ziel ist technisch erfolgreich**. Nur ein optionaler Browser-UI-Smoketest auf Test bleibt; der Unterschied zur fehlenden historischen KI-Historie auf **LIVE** erklärt sich durch den noch nicht erfolgten separaten PR37-Live-Rollout, nicht durch fehlende DB-Daten. Keine Live-Code-Mutation aus diesem Test-Ergebnis ableiten.

**Bestandsbackups (privat auf Server behalten):** vor Test-Deploy Test-Webroot `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz` SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`; vor Auth-Rename Test-Plesk-pd `/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-pd.tar.gz` SHA256 `a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f`; unmittelbar vor gezieltem Altuser-Delete `/root/hw24-backups/test-pd-cleanup-4lfU4lrd/d..httpdocs.before`. Kein direktes Restore alter Passwortdatei ohne genaue Statusdiagnose, da diese den alten Benutzer erneut freischalten würde.

**Nächster Projektabschnitt separat:** Auf `https://test-artikelmanager.hairworld24.de` in privatem Browserfenster mit `nvoss` → `📂 Auswertungen` → `Alle letzten Prüfungen` optional die UI bestätigen. Die *produktive* `https://artikelmanager.hairworld24.de` hat noch den Legacy-PHP-Code und zeigt daher noch nicht die historischen `hw24-test`-KI-Prüfungen unter `nvoss`. Ein Live-Deploy erst nach separater Planung, eigener Live-Webroot-/Plesk-Auth-Absicherung und ausdrücklicher Entscheidung; keine automatische Merge-Aktion auf `deploy/staging`, da Live/Test diesen Branch verwenden.


### 22.11.2026 · LIVE-ZIEL ERREICHT: NVOSS kann historische 3 KI-Berichte + beide Scan-Stapel auf PRODUKTIV lesen

**Unmittelbarer Nutzer-PuTTY-Nachweis des manuellen Test-zu-LIVE-Code-Rollouts** `HW24_LIVE_HISTORY_FIX`, abschließende Ausgabe:
- `=== HW24 LIVE: HISTORISCHE DATEN AKTIVIEREN ===`
- `Private Benutzerzuordnung: OK`
- `Live-Dateistand und Zugriffsschutz: OK`
- **LIVE-Rückfallsicherung:** `/root/hw24-backups/live-history-AJZy3nZS` (Originale der drei alten PHP-Dateien).
- Vier Dateien vor dem Kopieren gegen exakte Git-Blob-Hashes und PHP8.5-Syntax kontrolliert: `scanliste.php`, `ai-reviews.php`, `ai-review.php`, `hw24-actor-identity.php`.
- `Vier PHP-Dateien erfolgreich auf LIVE bereitgestellt`; anonymer GET auf LIVE-Archiv weiterhin `HTTP 401`.
- **Echte HTTPS-GET unter LIVE-Basic-`nvoss`:** `LIVE KI-Berichte: 3` (exakt drei historische) und `LIVE offene Scanlisten-Stapel: 2` (beide), beide Abfragen mit verdeckter interaktiver Passwortabfrage.
- `=== LIVE-HISTORIE ERFOLGREICH AKTIVIERT ===`, `3 KI-Berichte und 2 Scanlisten unter nvoss sichtbar`, `Keine Artikel- oder Datenbankaenderungen`.

**Ziel erfüllt:** Die zuvor ausschließlich auf TEST funktionierende read_aliases-Zuordnung `nvoss=>['nvoss','hw24-test']` gilt jetzt bei den **produktiven LIVE-APIs** unter `https://artikelmanager.hairworld24.de`. Die historische SQL-Provenienz bleibt vollständig erhalten (keine Updates an alten `actor`- oder `created_by`-Feldern). Der getrennte Test-Basic-Login wurde ebenfalls korrekt abgesichert: `hw24-test` 401, `nvoss` 200. Das ursprüngliche Ziel des Nutzers war **LIVE**, nicht ein bloßes Test-Ziel; dies wurde gegenüber dem Nutzer ausdrücklich anerkannt.

**Live-PHP-Blob-Stand (aus bestandenem Skript-Postcheck):**
- `api/scanliste.php` `1a19331fc030ef473d05cc0b94fee92fd2d436ea`
- `api/ai-reviews.php` `41bfe53eba6f4392e4f0f48e92828fa004ffe311`
- `api/ai-review.php` `aba03b6146a34698bae71fe622671945019d2db5`
- `api/hw24-actor-identity.php` `cb2722952bd0cad9680d919630e4b2a06ba367fc`

**Noch wichtig, nicht als erledigt darstellen:** Die vier PHP-Dateien wurden *direkt auf den produktiven Webroot* kopiert; GitHub-Branch `deploy/staging` wurde dabei **noch nicht** geändert. Plesk-Git verwaltet den LIVE-Webroot manuell aus diesem Branch, sodass ein späterer Plesk-Deploy diese vier LIVE-Dateien zurück auf Legacy setzen könnte. Vor künftigen Webroot-Git-Deploys muss die bereits verifizierte Vierdateien-Änderung kontrolliert im Deploymentbranch verankert werden, ohne PR37 pauschal (CI-/Testkonfigurationsdateien) zu mergen. Dazu gesonderte, eng begrenzte Repo-Änderung planen und tatsächliches Branch-HEAD gegenprüfen. Nicht ohne Risikoanalyse und gesonderte Freigabe einen Live-Deploy auslösen. Die ursprüngliche Nutzeraufgabe (LIVE-Archiv sichtbar) ist jedoch bereits erfolgreich abgeschlossen; keine weiteren Test-Basic-Befehle nötig.

**Offener UX-Schritt nur falls User weiter Probleme meldet:** Echter Browsercheck auf `https://artikelmanager.hairworld24.de`, oben `📂 Auswertungen` → `Alle letzten Prüfungen` (kein EAN-Filter), dort drei historische Berichte. HTTPS-Backend mit Live-Basic `nvoss` ist belegt; Browseranzeige noch nicht unabhängig verifiziert. Keine neuen OpenAI-Kosten erzeugt.


## 22.11.2026 · LIVE-Historie in Git deploy/staging dauerhaft verankert (PR #38 MERGED)

**Verifiziertes Ergebnis:** Nach dem bereits erfolgreichen direkten LIVE-Webroot-Rollout mit echtem HTTPS-GET unter `nvoss` (3 historische KI-Berichte, 2 offene Scanlisten-Stapel) wurde **genau der produktiv laufende Vier-PHP-Datei-Stand** zusammen mit einem aktualisierten Bestands-Vertragstest dauerhaft in GitHub-`deploy/staging` aufgenommen. Das ist kein neuer Webserver-Deploy.

- Neue schmale Release-Branch `release/hw24-live-history-nvoss-20261122` aus altem `deploy/staging`-HEAD `444453471b0fd27c73dcc7929f5e17f1d9239c52` angelegt.
- Vier PHP-BLOBs des erfolgreich auf Test und LIVE getesteten PR37-Standes **ohne Inhaltsänderung** atomar als Git-Tree in einen Commit übernommen (PR38-Initialcommit `8e8971c40770b3bdd2cfba506d4bb3fb2ae08e93`).
- <https://github.com/NicoVoss1/HW24-scanner/pull/38> auf `deploy/staging` erstellt; anfänglicher GitHub-Actions-Lauf **rot** wegen altem Test-String-Assert `Archive is actor-scoped` (`WHERE actor=?` passt nicht mehr zur absichtlich parametrierten Zwei-Owner-SQL-Abfrage). **Keine PHP-/LIVE-Datei dafür verändert.**
- Der bereits im geprüften PR #37 korrigierte Vertragstest `tests/hw24-v425-article-intelligence.test.cjs` als 5. reine Testdatei übernommen, Git-BLOB `c3dbf4ab01a6a0bdfc2ed8c4a03d8668826bc330`, um die sichere `actor IN ($actorMarks)` plus `hw24ReadableOwners($actor)`-Berechtigungsprüfung zu verlangen.
- **Neuer GitHub CI-Lauf `44983273900`: `completed/success`.** PR38 konfliktfrei, mit **exakt fünf** geänderten Dateien, davon vier produktive PHP und einer Test.
- **PR38 über GitHub mit erwartetem HEAD `b970ccdd453df02f80c74aa0f7788b62941bb7c4` squash-gemergt**, API bestätigte `merged:true`. **Merge-Commit `bd58ea909c8807f274a4bb9a5759de0d43ef9ee3`**, GitHub-Diff `deploy/staging` gegen altes Basiscommit: fünf erwartete Dateien, keine weiteren.
- Direkt **nach Merge erneut aus GitHub-`deploy/staging`** verifizierte vier API-Git-BLOBs entsprechen den vier bereits auf LIVE verifizierten: `api/scanliste.php`=`1a19331fc030ef473d05cc0b94fee92fd2d436ea`, `api/ai-reviews.php`=`41bfe53eba6f4392e4f0f48e92828fa004ffe311`, `api/ai-review.php`=`aba03b6146a34698bae71fe622671945019d2db5`, `api/hw24-actor-identity.php`=`cb2722952bd0cad9680d919630e4b2a06ba367fc`. Fifth test SHA matches also.

**Status / Grenzen:** LIVE-Webroot ist schon korrekt und wurde bei diesem Git-Merge **NICHT** durch Plesk neu deployed; Git und Webroot-Dateiinhalte sind für vier PHP-Dateien nun logisch gleich. Der historische Backup der drei ursprünglich ersetzten LIVE-Dateien liegt unter `/root/hw24-backups/live-history-AJZy3nZS`. Der private Alias `hw24_private/actor-aliases.php` ist weiter ausschließlich auf Plesk als Mode 0600, **nie ins GitHub-Repository geschrieben**. Test-Login `hw24-test` wurde davor gezielt gesperrt (401), Test-`nvoss` gibt 200. Keine SQL-Updates, KI-POSTs, Plesk-Restarts, Live-Auth-Änderungen, Amazon-, eBay-, Shopify- oder JTL-Änderungen. PR #37 bleibt **separater Draft** und darf nicht unbesehen in `deploy/staging` gemergt werden, da seine übrigen Test/Workflow-Änderungen über PR38 hinausgehen.

**Nächster Projektabschnitt:** User befindet sich im Live-Browser auf `artikelmanager.hairworld24.de/scanliste.html`; wenn Bedarf, die UI-Sichtbarkeit und Integration der beiden offenen Scanlisten-Stapel und der drei Prüfberichte im LIVE-Browser smoke-testen. Auf `artikelmanager.hairworld24.de` `📂 Auswertungen` → `Alle letzten Prüfungen`; auf `scanliste.html` beide Stapel anzeigen. Keine bezahlte KI-Prüfung auslösen. Der aktuelle GitHub-`deploy/staging`-HEAD ist `bd58ea909c8807f274a4bb9a5759de0d43ef9ee3`. Die getrennte Laravel-/Filament-Architektur und künftige Datennormalisierung sind noch nicht durch dieses Security-Update umgesetzt.


### 22.11.2026 · LIVE-Artikelpflege Matrix 6C: echte Quelllücken nach 2 Nutzerscreenshots; PR39 vorbereitet und CI grün

**Benutzerkontext und Quelle:** Zwei vom Betreiber hochgeladene Bildschirmfotos von `artikelpflege.html`, erster auf TEST und zweiter auf LIVE, zeigen Matrix SoColor Beauty 6C Dunkelblond Kupfer (90 ml), Referenz-EAN **3474630337398**. Unveränderte funktionierende UI auf beiden Domains mit noch fehlenden JTL-Herkunftsfeldern:
- Inhalt `fMassMenge` als z. B. `90.000000`, gewünscht Darstellung `90` oder bei Dezimalmenge `2,5`, ohne interne SQL-Werte zu überschreiben.
- `Artikeltyp` aktuell leer / `Bitte auswählen`; keine glaubhafte Zuordnung vom Windows-Sync in `HW24_Artikeltyp`.
- `kLieferStatus=1`, aber zugehörige `tLieferStatus.cName`-Klartextquelle im `HW24_RelatedSources.lieferstatus` fehlt; niemals ID 1 in willkürlichen Klartext umwandeln.
- JTL-`Brutto-VK` fehlt, obwohl `Netto-VK` vorliegt; `api/jtl.php` verlangt korrekt `HW24_VKBrutto` plus `HW24_VKBruttoQuelle` (aus Windows-SQL mit vertrauenswürdiger Kundengruppe/Steuerzone). Eine Ableitung `netto*1.19` ohne Quelle bleibt verboten.
- `Währung` nicht belegt, `EUR` bisher nur Dropdown-Auswahl und **kein** JTL-Quellnachweis. Zunächst `HW24_Waehrung` aus verifizierter Quelle liefern; `Artikeltyp` kann nicht global per Vermutung auf Normaler Artikel gesetzt werden.
- Amazon: Aktueller `api/amazon.php`-Katalogabruf sucht **ausschließlich über EAN** mit 6-Stunden-Cache; `0` EAN-Treffer beweist **nicht**, dass eine im JTL-Datenspiegel verknüpfte ASIN nicht vorhanden ist. Die öffentliche EAN 3474630337398 wurde unabhängig für Matrix SoColor Beauty 6C 90ml über Drittanbieter-Belege bestätigt, aber **kein tatsächlich authentifizierter Amazon.de SP-API-ASIN-Erfolg liegt vor**.
- Auch auf LIVE steht im Seitenheader/Footer irreführend `INTELLIGENCE · TEST`; nur Darstellung, keine abweichende Datenverbindung.

**Source-Code-Abgleich (Remote `deploy/staging`):** `api/jtl.php` liefert Felder `brutto_vk` nur aus `HW24_VKBrutto` + Quellname, `waehrung` aus `HW24_Waehrung`, `artikeltyp` aus `HW24_Artikeltyp`, `lieferstatus` aus verifizierter `HW24_RelatedSources.lieferstatus.rows[0].cName`; Standard-Brutto-VK oder Klartext nie geraten. `assets/hw24-article-editor.js` bildet diese Felder korrekt ab, ist aber dadurch bislang leer. Der konkrete **Windows SQL Server-Synchronisierer** ist nicht im GitHub-`HW24-scanner`-Webroot-Repository; SQL-Schema, tatsächlich auf Windows verfügbare Felder und B2B/Kundengruppenpreiszuordnung müssen read-only verifiziert werden. Die öffentliche JTL-Beispielansicht `vArtikelliste` enthält oft `VerkaufspreisBrutto`, `Lieferstatus`, `Steuerklasse`, `Währung`, aber die konkrete SQL-Server-Version vor Ort ist maßgeblich.

**Neue Entwicklung GitHub PR #39 DRAFT:** <https://github.com/NicoVoss1/HW24-scanner/pull/39>, Featurebranch `feature/hw24-asin-catalog-number-view-20261122`, Basis aktueller `deploy/staging` `bd58ea909c8807f274a4bb9a5759de0d43ef9ee3`, **6 geänderte Dateien**:
- `assets/hw24-article-editor.js`: ausschließlich Darstellung `90.000... => 90`, `2.500 => 2,5`, bei LIVE korrekter LIVE-Header/Footer.
- `artikelpflege.html` und `assets/hw24-article-intelligence.js`: eigenständiger manuell geklickter JTL-ASIN-Katalogvergleich zusätzlich zur bisherigen EAN-Suche; kein automatisches Matching.
- Neu `api/amazon-asin.php`: HTTPS/Basic, EAN-Prüfsumme, ASIN-Format, **gültige Zuordnung zur aktuellen JTL-EAN per PDO-SELECT** erzwingen; über Amazon.de SP-API `getCatalogItem` nur bei explizitem Klick abfragen; Amazon-EAN-Evidenz `exact_ean`/`other_ean`/`ean_not_supplied`; keine SQL INSERT/UPDATE, keine Angebotspreis- oder Bestandsschreibvorgänge.
- Bestehende Node-Source-Contracts + CI-PHP-Lint des neuen Endpunkts aktualisiert.
- **GitHub Actions `HW24 V4.25 Amazon KI Ameise article editor`, Lauf-ID `45027407660`, `completed/success`**. **NOCH NICHT deployed** auf TEST oder LIVE; PR39 bleibt DRAFT zur separaten Validierung der vorhandenen Amazon- und JTL-Schnittstellen. Nicht mit PR37/38 verwechseln.

**Nächste konkrete Aktion, NUR SELECT und ohne Secret-Ausgabe:** auf Plesk unter `nicovoss` aktuelle `hw24_jtl_articles.raw_json`-Einträge für EAN 3474630337398 auf Existenz der fünf HW24-Zuordnungsfelder und `related_sources` prüfen, ebenso aktuelle `hw24_amazon_catalog_snapshots`-Einträge aus dem Cache. Kein SP-API-Aufruf nötig. Danach gezielte Windows SQL Schema-/Preisgruppe-/Steuerbeobachtung, Sicherung des Windows-Sync-Skripts, staged Ergänzung mit Tests und später Test-Snapshot; erst danach LIVE-Entscheidung. Ohne diese Quellprüfung kein autoritatives `Brutto-VK` oder `Artikeltyp` in Wawi/Ameise ausfüllen. Ziel: vollständiger verifizierter JTL-Datenspiegel mit sauberer Preisprovenienz und getrennten Amazon/eBay/Shopify-Kanalpreisen, keine Massenänderung ungeprüfter Artikel.


## 22.11.2026 · LIVE-/TEST-V4.25-PREFLIGHT mit Betreiber-PuTTY ERFOLGREICH (17:21:55 UTC)

**Neuer, vom Betreiber vollständig mitgeteilter Shell-Ausgabenachweis** zur read-only Prüfung `ops/hw24-live-test-preflight-readonly.sh` (Featurebranch `feature/hw24-jtl-all-fields-channel-view-20261122`, Commit `443b0e65d169fb41e60650ee3fe1cb61f80c9d45`):

- Beiden bestehenden Plesk-DocumentRoots gehören weiterhin zum Artikelmanager: `/var/www/vhosts/hairworld24.de/artikelmanager.HAIRWORLD24.de` bzw. `/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de`.
- **10/10 exakte Legacy-V4.25-Git-Blob-Hashes** auf Live und Test stimmen überein: jeweils `index.html`, `artikelpflege.html`, `scanliste.html`, `api/jtl.php`, `api/amazon.php`. Kein Plesk-Webroot umgestellt.
- **6/6 anonyme HTTPS-GETs HTTP 401:** Artikelpflege, JTL-EAN-Lookup und KI-Archiv auf beiden Domains. Die Pfade sind ohne Anmeldung nicht offen.
- Private Laravel-CLI bestätigt `Laravel Framework 13.43.0`; `artisan route:list --path=admin` liefert die vier erwarteten Routen `GET /admin`, `GET /admin/artikel`, `GET /admin/login`, `POST /admin/logout`.
- `nginx` und `www-data` können die öffentlichen CSS-/JS-Assets lesen, **nicht** `platform/.env` oder `platform/database/database.sqlite`.
- Abschließend `=== Ende: 0 Abweichung(en), keine Aenderungen vorgenommen ===`. Betreiber hatte unmittelbar zuvor separat bereits die **authentifizierte LIVE-KI-Historie mit 3 Berichten** und **2 offene Scanlisten-Stapel** unter Basic-User `nvoss` erfolgreich geprüft; Schutz weiter HTTP 401 anonym. Keine Datenbank-/Artikeländerungen.

**Bewusst noch offen und nicht als erfüllt ausgeben:** Ein authentifizierter LIVE-EAN-/JTL-Lese- und Artikelpflege-Browsertest (Referenz EAN `3474630337398`, erwartete JTL-ID `1428`, ASIN `B091J3HV6B`), die manuell ausgelöste Amazon-Selling-Partner-API-Katalogprüfung und der Laravel-/Filament-Login via HTTP nach Test-DocumentRoot-Umstellung. Fehlender EAN-Katalogtreffer beweist nicht fehlende verknüpfte ASIN. PR #39, PR #41 und PR #36 weiterhin **Draft/nicht deployed**; neueste PHP-Anwendungsänderungen nur in GitHub. Zwischen CI-Grün und echter Plesk-HTTP-Abnahme strikt unterscheiden.

**Nächster Schritt:** genau **ein** authentifizierter HTTPS-GET auf `https://artikelmanager.hairworld24.de/api/jtl.php?ean=3474630337398` mit interaktiver/verdeckter Passwortabfrage (keine Secrets im Chat/Repo/Shell-History). Rückgabefelder nur gefiltert anzeigen. Danach Browserartikelpflege und die ausdrückliche manuelle Amazon-GET-Abfrage ansehen. Erst bei erfolgreicher E2E-Abnahme die weitere Legacy-PR-#39/#41-Testbereitstellung und die isolierte Laravel-Testdomain unter Rollback planen; Live unverändert.


## 22.11.2026 · LIVE-JTL-API-E2E unter nvoss ERFOLGREICH

**Echter Betreiber-PuTTY-Nachweis per authentifiziertem HTTPS GET**, **nach** bestandenem read-only Plesk-Preflight. Befehl `curl -fsS --user nvoss "https://artikelmanager.hairworld24.de/api/jtl.php?ean=3474630337398"` mit verdeckt angefordertem Passwort; Antwort lokal per Python gefiltert. Tatsächliche, vom Betreiber gepostete Ausgabe:

```text
JTL-Status: current_snapshot
Snapshot: 2026-11-22 17:17:09.691101
Alter (Minuten): 13
JTL-ID: 1428
EAN: 3474630337398
ASIN: B091J3HV6B
VK netto: 11.7647
VK brutto: None
Amazon JTL-Preis: 14.0000
eBay JTL-Preis: 11.0000
Steuerklasse-ID: 1
Lieferstatus-ID: 1
Fuellmenge: 90.0000000000000
LIVE-JTL-PRUEFUNG ERFOLGREICH
```

**Befund:** Die LIVE-API und die HTTP-Basic-Berechtigung `nvoss` funktionieren tatsächlich; der Spiegel ist zum Prüfzeitpunkt 13 Minuten alt und genau **ein eindeutiger Artikel** mit erwarteter JTL-ID 1428 und erwarteter JTL-ASIN wird bereitgestellt. `fMassMenge` ist weiterhin technisch mit vielen Dezimalstellen gespeichert, sodass PR39-Darstellungsnormalisierung weiterhin benötigt wird. Ein **belegter JTL-Standard-Bruttopreis fehlt**; die tatsächliche Originalquelle muss zuerst über read-only Windows-SQL-JTL ermittelt werden, ohne `fAmazonVK` fälschlich als Standard-Brutto-VK oder LIVE-Angebotspreis zu deklarieren. `fEbayPreis` ist noch keinem der beiden eBay-Konten eindeutig zugeordnet. Live-/Test-Webroots und SQL-Daten wurden durch den GET nicht geändert.

**Nächste Freigabe-Gates:** Amazon Catalog GET auf **LIVE** einmal manuell prüfen (`api/amazon.php?ean=3474630337398`, ggf. Cache/kein EAN-Treffer trotz vorhandener JTL-ASIN), Anzeige in `artikelpflege.html` mit vorhandenen KI-Archivinfos, dann abgesicherter Testdomain-Framework-HTTP-/Filament-Login. Das neue direkte ASIN-API-Modul `api/amazon-asin.php` aus PR39 ist **noch nicht** live installiert. PR39/PR41 bleiben Draft; PR36 bleibt isolierter Pilot. **Keinen Live-Merge/-Deploy vor separater Testabnahme.**


## 22.11.2026 · LIVE-Amazon-SP-API-EAN-Abfrage erfolgreich, 0 Katalogtreffer

**Echte authentifizierte Betreiber-Abfrage** am Produktiv-Endpunkt:
`curl -sS --user nvoss "https://artikelmanager.hairworld24.de/api/amazon.php?ean=3474630337398"`.
Vom Betreiber gepostete lokal gefilterte Antwort:
```text
Amazon API: OK
Datenquelle: live
Katalogtreffer: 0
Fehler: None
Fehlerphase: None
Amazon HTTP: None
```

**Interpretation:** Amazon LWA/SP-API EAN-Suche hat ohne HTTP-Anwendungsfehler live reagiert und im Amazon.de-Katalog **0 EAN-Treffer** ermittelt. Das ist **kein Nachweis**, dass die in JTL original hinterlegte ASIN `B091J3HV6B` bei Amazon nicht existiert. Der Amazon-Katalog-Gegencheck per JTL-verknüpfter ASIN ist der fachlich richtige nächste Schritt; der separat entwickelte GET-only-Endpunkt `api/amazon-asin.php` aus Draft PR39 ist noch **nicht** auf Live/Test deployed.

**Sichere nächste Testvorbereitung:** auf dem gestapelten PR41 (Featurebranch, kein Merge) das Test-only-Skript `ops/hw24-stage-amazon-asin-single-file.sh` vorbereitet. Es lädt die freigegebene PHP-Datei **aus festem PR39-Commit** `87ad1c38534fd5a59e7332e63bd9e666919ea8b5`, verifiziert Git-Blob-SHA `617e2162f20dd012ed8c4fe8cc73d12b5e0d2373`, PHP8.5-Syntax und die zwei bestehenden V4.25-API-Dateihashes. Es erstellt die neue Datei **nur auf der bestehenden Testdomain**, überschreibt nie eine bestehende Datei, prüft anonymen HTTP 401/403 und entfernt den Neuzugang bei unerwarteter öffentlicher Erreichbarkeit. Keine Änderung an Live-Webroot, Datenbanken, Plesk-DocumentRoot oder aktiven Verkaufskanälen. **Nur vorbereitet, nicht vom Betreiber ausgeführt.** Shell-Syntaxprüfung in CI. Danach den Endpunkt mit `nvoss` und EAN/ASIN gezielt über HTTPS GET einmal manuell testen, Ergebnisse `found/title/brand/ean_evidence` protokollieren; Marktplatz-EAN-Nachweis nicht automatisch als Preis-/ASIN-Änderung übernehmen.

**Framework-Gate:** Laravel/Filament private CLI- und Assetberechtigungen bereits durch vorherigen Betreiber-Preflight erfolgreich; ein Testdomain-DocumentRoot-Cutover darf **erst** nach abschließendem Legacy- und Filament-HTTP-/Rollback-Plan erfolgen. Live funktional gesichert lassen.


## 22.11.2026 · ASIN-GET auf TEST erfolgreich: ASIN B091J3HV6B gefunden, EAN-Abweichung

**Betreiber-Prüfnachweis** zur eng begrenzten Staging-Datei `api/amazon-asin.php` (fixierter PR39-BLOB `617e2162f20dd012ed8c4fe8cc73d12b5e0d2373`):
- PuTTY-Skript `ops/hw24-stage-amazon-asin-single-file.sh` ausgeführt: PHP-Syntax `No syntax errors`; neue PHP-Datei ausschließlich unter `test-artikelmanager.HAIRWORLD24.de/api/` hinzugefügt; anonymer HTTPS-GET weiterhin `401`; Abschluss `TEST-API BEREIT. LIVE UNVERAENDERT.`.
- Danach echter, authentifizierter TEST-HTTPS-GET via `--user nvoss` auf `api/amazon-asin.php?ean=3474630337398&asin=B091J3HV6B`:
```text
ok: True
found: True
asin: B091J3HV6B
title: 6C
brand: Matrix
ean_evidence: other_ean
error: None
stage: None
upstream_status: None
```
- **Befund:** Amazon.de hat das ASIN-Produkt gefunden, obwohl die frühere EAN-Katalogsuche auf LIVE `0` Treffer lieferte. **Kritische fachliche Abweichung:** Amazon hat mindestens eine andere EAN gemeldet und **nicht** die JTL-EAN `3474630337398`. Das beweist weder Produktgleichheit noch zwingend Ungleichheit; Verpackung/Varianten/Regionen sind separat manuell zu prüfen. Diese Antwort darf **keine automatische neue ASIN- oder Preiszuordnung** auslösen.
- Der eigentliche HTTP-JSON-Payload enthält bereits `ean_identifiers` (maximal 12 Amazon-EANs), aber die bisherige PuTTY-Ausgabe hat diese nicht angezeigt. Nächster gezielter Schritt: nur `ean_identifiers` lesen und JTL-EAN dagegen vergleichen; keine vollständigen Katalog-/Secret-Payloads in Chat/GitHub.
- **Noch nicht:** HTML/Frontend von PR39 auf Test oder Live ausgerollt, keine Framework-DocumentRoot-Änderung, kein Laravel/Filament-HTTPS-Browser-Login. Neuer Endpoint wurde manuell nur in TEST hinterlegt; bei späterem Plesk-Deploy beachten, da Git `deploy/staging` diese Datei noch nicht enthält.


## 22.11.2026 · Filament-Loopback-HTTP bestanden; Amazon-EAN-Typfehler entdeckt

**Betreiber-PuTTY-Nachweis:** `platform/ops/verify-filament-loopback-http.sh` aus dem Framework-Draft-PR36 wurde auf dem tatsächlichen Plesk-Server ausgeführt. Ausgabe:
- `GET /admin/login` HTTP **200** (Filament-Loginseite)
- `GET /admin` und `GET /admin/artikel` anonym HTTP **302** (Login-Weiterleitung)
- `GET /css/filament/filament/app.css` HTTP **200**
- `GET /js/filament/filament/app.js` HTTP **200**
- Abschluss `LARAVEL/FILAMENT LOOPBACK: HTTP TEST BESTANDEN`; nur `127.0.0.1`, Testserver anschließend beendet, kein Plesk-Cutover, keine Datenbank-/JTL-Schreibvorgänge.

**Amazon-EAN-Diagnose:** Betreiber führte zweimal den empfohlenen Test-GET `api/amazon-asin.php?ean=3474630337398&asin=B091J3HV6B` aus. Amazon-ASIN und Marke wurden ausgegeben, aber der bisherige Python-Ausdruck `", ".join(codes)` schlug mit `TypeError: sequence item 0: expected str instance, int found` fehl. **Grundursache im PHP-Endpunkt** `$codes[$code]=true; $codes=array_keys($codes)`: rein numerische EANs als Array-Keys werden durch PHP häufig in Integer umgewandelt, weshalb `in_array($ean,$codes,true)` mit der JTL-String-EAN stets einen falschen Mismatch liefern kann und `ean_identifiers` teilweise als JSON-Zahlen statt String ausgegeben werden. Die bisherige Evidenz `other_ean` ist **nicht zuverlässig**; kein nachgewiesener GTIN-Konflikt, keine automatische JTL-/Marktplatz-Änderung.

**Quellcode-Fix in GitHub, noch nicht auf Plesk aktiviert:** PR39-Featurebranch `fdec3fa`, CI `e697ee6...`; PR41-Featurebranch `4a1e3b0...`. EAN-IDs als stringwertige Liste speichern, mit `array_unique(..., SORT_STRING)` deduplizieren, strikter Vergleich nun korrekt; führende Nullen erhalten. Serverseitiger CLI-Selbsttest `php api/amazon-asin.php --test-ean-evidence` umfasst exakte, abweichende, fehlende und EANs mit führenden Nullen. Aktueller **exakter PHP-Git-Blob SHA** `712d2c4a565258cefac93c4282e06b8604d384b8`. Auth/HTTPS/JTL-EAN-ASIN-Freshness unverändert.

**Abgesicherte Testdomain-Korrektur als Skript vorbereitet, NICHT ausgeführt:** `ops/hw24-test-amazon-asin-ean-fix.sh` im PR41. Es akzeptiert nur den zuvor auf TEST installierten Endpunkt mit SHA `617e2162f20dd012ed8c4fe8cc73d12b5e0d2373` oder den bereits korrigierten SHA; prüft fest gepinnte GitHub-Quelle, PHP-Lint/Regressionen, sichert Original nur root-privat, tauscht nur **TEST**-`api/amazon-asin.php` atomar aus, prüft anonymen HTTP 401/403 und versucht Rollback bei Fehler. Keine Änderung an LIVE-/Test-DocumentRoots, Plesk-Git-Branch, SQLite, JTL oder Shops. Danach authentifizierte ASIN-GET-Ausgabe `ean_identifiers` erneut und nur als Strings prüfen; danach entscheidet die tatsächliche Übereinstimmung, nicht das vorher falsche `other_ean`.

**Projektstand:** Framework HTTP-Loopback erfolgreich, jedoch **kein öffentlicher HTTPS-Filament-Login** geprüft. Live JTL + Amazon EAN API und Test ASIN API funktionieren; Source-Bug muss zuerst in TEST abgenommen werden. Keine automatische Staging-/Live-Migration oder Kauf-/Preisänderungen.


## 22.11.2026 · Amazon EAN-Fix auf TEST nachgewiesen: echter GTIN-Konflikt und Filament-HTTP-Test erfolgreich

**Betreiber-PuTTY-Ausgabe (direkt vom Server, nicht nur GitHub-CI):**
- `ops/hw24-test-amazon-asin-ean-fix.sh` nur auf TEST ausgeführt: alter Endpunkt exakt geprüft, privates Backup erstellt, `api/amazon-asin.php` durch PHP-Git-BLOB `712d2c4a565258cefac93c4282e06b8604d384b8` ersetzt, PHP-Lint erfolgreich und CLI-Regressionsfälle **exact / mismatch / missing / leading zeros PASS**. Anonymer Zugriff `HTTP 401`. Abschluss `AMAZON EAN-FIX AUF TEST ERFOLGREICH. LIVE UNVERAENDERT.`
- Erneuter **authentifizierter** Amazon.de-ASIN-Catalog-GET mit EAN `3474630337398`, JTL-ASIN `B091J3HV6B`: `ok:True`, `found:True`, `Amazon EANs: 3474636989348`, `JTL EAN: 3474630337398`, `EAN identisch: NEIN`, `Nachweis: other_ean`, `Fehler: None`.
- **Befund jetzt belastbar:** Beide EANs sind Strings; die Amazon-Katalogidentifikatoren für diese ASIN sind wirklich von der JTL-EAN verschieden. **Produktgleichheit bleibt ungeklärt** (Farbton, Volumen, Packung, Variante manuell prüfen). **Keine automatische Artikel-/Preis-/ASIN-Übernahme**.
- Der Betreiber hat parallel erneut `platform/ops/verify-filament-loopback-http.sh` ausgeführt und mit `GET /admin/login HTTP 200`, nicht authentifizierte `/admin` und `/admin/artikel` jeweils `HTTP 302`, Filament CSS/JS jeweils `HTTP 200`, Abschluss `LARAVEL/FILAMENT LOOPBACK: HTTP TEST BESTANDEN` bestätigt. Das ist ein **nur lokal gehosteter HTTP-Test** auf `127.0.0.1`, noch kein öffentliches HTTPS-/Browser-Login.
- Keine produktiven Webroot-, JTL-, SQL-, eBay-, Shopify- oder Amazon-Angebotsänderungen. Die manuell hinzugefügte TEST-`api/amazon-asin.php` ist weiterhin **nicht** im alten Git-Stagingdeployment; künftig sauber sichern/entkoppeln.
- **Nächster Release-Gate:** sichere Inventur der Plesk-Git-Testdomain und Rückfallmöglichkeit, danach ggf. kontrollierter Wechsel **nur Testdomain** auf isoliertes Filament-`platform/public`. Vor der Umstellung dokumentierte Testdomain-Wiederherstellung, Basic-Auth, SSL, PHP-FPM/open_basedir und Laravel-Login prüfen; kein PR36-Merge in `deploy/staging`.


## 22.11.2026 · Testdomain-Plesk-Pre-Cutover-Protokoll: ein erwarteter Framework-Code-Rueckstand

**Betreiber-PuTTY-Ausgabe** des read-only `platform/ops/verify-testdomain-cutover-readonly.sh` vom **22.11.2026 18:40:40 UTC**:

- Plesk-Test-vHost-DocumentRoot ist weiterhin `/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de`. Bestehende Test-Artikelpflege noch intakt; manuell eingespielter Amazon-ASIN-Endpunkt liegt im bisherigen Testroot.
- Plesk `ext git --get-last-commit` meldet für `HW24-scanner.git` Commit **`c2cc4e1dc054da00f01087e7221659cf54a9a482`** (Test-Regressionstest 03:56 Uhr). Dieser Plesk-Git-Metastand weicht von GitHub `deploy/staging` **`bd58ea909c8807f274a4bb9a5759de0d43ef9ee3`** ab und ist laut GitHub-Compare divergiert. **Keinen unvorbereiteten Plesk-Pull/Deploy ausführen**, weil er zuvor manuell korrigierte Test-Dateien oder die alte Live-Historie ueberschreiben koennte.
- Privater Laravel-Git-Klon liegt auf **`980bcce75a0a3516b7b6364fae5a0fa6aab8d112`**. Die neue `platform/app/Domain/Jtl/SourceSnapshot.php` ist **noch nicht** im installierten Klon. `git diff` fuer getrackte lokale Dateien **sauber**.
- `artisan`, `platform/public/index.php`, `vendor/autoload.php`, private `.env`, private `database/database.sqlite`, Filament CSS/JS sind vorhanden. Weder Datenbankinitialisierung noch Admin-Neuanlage erforderlich.
- Anonyme HTTPS-Zugriffe auf Live-/Test-`api/jtl.php` weiter **401/401**. Testabschluss `PRE-CUTOVER ENDE: 1 Abweichung(en)`: erwarteter fehlender neuester Framework-Quellcode; sonst kein Fehler.
- Bereits zuvor separat vom Betreiber belegt: Filament-Testloopback `GET /admin/login = 200`, `/admin` und `/admin/artikel` anonym `302`, CSS und JS `200`; GTIN-Konflikt Amazon EAN `3474636989348` gegen JTL `3474630337398` nach Typfix auf TEST bestaetigt.

**GitHub-Diff geprueft:** Ausgehend von privatem Commit `980bcce` ist der Framework-Draft-Commit `a26a64d09597e41523e224c557bb1d4e39bdf052` ein **Fast-Forward mit 19 Commits**, lediglich 13 Quell-/Test-/Dokumentationsdateien unter `platform/` und einem CI-Workflow betroffen. Insbesondere **kein** `composer.json`, `composer.lock`, private `.env` oder SQLite im Diff.

**Vorbereitet, noch nicht auf Plesk ausgefuehrt:** Sicherheitsbewusstes Skript `platform/ops/upgrade-private-framework-ffonly.sh` auf GitHub-Featurebranch PR36. Prueft gegebene alte/neue SHA, Branch, Origin, sauberen Index, path-whitelist und Commit-Abstammung, sichert SHA-Rueckfallreferenz root-privat, fuehrt ausschließlich Fast-Forward des privaten Framework-Repositories als Site-User aus, vergleicht .env/SQLite/composer.lock bytegenau vor/nach und prueft PHP-Lint/Admin-Routen. **Kein Composer, keine Migration, kein Domain-/Plesk-Git-/Live-Deploy.** Nach Betreiber-Nachweis erst separaten Schema-Test und Test-vHost-FPM-/HTTPS-Cutover mit Rueckfallplan abnehmen.


## 22.11.2026 · Privater Laravel-Framework-Klon erfolgreich fast-forward aktualisiert

**Vom Betreiber in PuTTY tatsächlich ausgeführtes gesichertes Fast-Forward-Skript** `platform/ops/upgrade-private-framework-ffonly.sh`:
- Git-HEAD vorher **`980bcce75a0a3516b7b6364fae5a0fa6aab8d112`**, Branch `feature/hw24-platform-laravel13-filament5-20261122`, erwarteter GitHub-Origin. Plesk-Git der Test-/Live-Domains blieb unberührt.
- Erlaubte **13** verifizierte Dateipfade und ausschließlich Fast-Forward auf **`a26a64d09597e41523e224c557bb1d4e39bdf052`**, 722 hinzugefügte und 15 entfernte Codezeilen. Das ist der *bewusst gepinnte getestete Quellstand*, nicht der neueste Skript-Entwicklungs-HEAD.
- **Bytegenau unverändert** nach Aktualisierung: private `platform/.env`, `platform/database/database.sqlite`, serverlokale `platform/composer.lock`. Root-privates Rückfallprotokoll `/root/hw24-backups/hw24-private-fw-fastforward-20261122T184523Z-qZwugv7L.txt`.
- PHP-8.5-Lint erfolgreich für `SourceSnapshot.php`, `ArticleLookup.php`, `LegacyReadBridge.php` und die neue reine Plattform-DB-Migration; `Laravel Framework 13.43.0` sowie alle vier Filament-Admin-Routen unverändert. Abschluss `FRAMEWORK QUELLCODE AKTUALISIERT, TEST- UND LIVE-DOMAIN UNVERAENDERT`.
- **Noch nicht:** Neue `source_field_catalog`, `source_reference_observations` und `channel_account_registry` in der privaten SQLite angelegt, optionaler Metadaten-Seeder ausgeführt, neue `api/jtl-fields.php` im Legacy-Live-Webroot, öffentliche Testdomain umgestellt oder authentifiziertes HTTPS-Filament-Login geprüft.

**Aktuell entwickelter nächster Gate:** `platform/ops/migrate-source-catalog-isolated.sh` in isoliertem PR36, noch nicht vom Betreiber ausgeführt. Prüft per tatsächlichem Laravel-Boot ausdrücklich `DB_CONNECTION=sqlite` und exakt die private SQLite-Datei statt bestehender `hw24_artikelmanager`/Wawi; vorhandenen Status und SQLite-Integrität; erstellt vor der **einen** Migration mit `VACUUM INTO` ein vollständiges konsistentes Backup zunächst als Site-User (verhindert root-owned WAL/SHM), dann ausschließlich root-privat und mit Integritätscheck; führt als Site-User nur `2026_11_22_000002_create_jtl_source_catalog_tables.php` aus, prüft drei neue Tabellen leer und Integrität. **Keine** User-Neuanlage, kein Seeder, kein Composer, keine JTL-/MariaDB-/Webroot-Änderung. Bei fehlerhafter Quellkonfiguration oder unerwartetem Migrationsstatus verweigert das Skript den DB-Eingriff. Nach Ausführung Belege im Journal ergänzen.


## 22.11.2026 · Plattform-SQLite-Quellkatalogmigration durch Betreiber erfolgreich abgeschlossen

**Direkte PuTTY-Ausgabe des Betreibers nach gesichertem Framework-Fast-Forward** auf `a26a64d09597e41523e224c557bb1d4e39bdf052`:

- `ops/migrate-source-catalog-isolated.sh` aus PR36 wurde auf dem Plesk-Server ausgeführt. Die tatsächliche Laravel-Konfiguration wurde **vorher** gegen genau die private SQLite-Datei geprüft, kein Verweis auf Legacy-MariaDB oder JTL-Wawi.
- Vor Migration wurde mit `VACUUM INTO` als Site-User ein **konsistentes SQLite-Snapshot** erstellt, Integritätsprüfung der Kopie erfolgreich, root-private Sicherung unter `/root/hw24-backups/hw24-platform-before-source-catalog-20261122T185219Z-jwr3zLPf.sqlite`.
- **Nur** `2026_11_22_000002_create_jtl_source_catalog_tables` wurde ausgeführt, Laravel meldete `DONE` nach ca. 14,91 ms.
- Nachher exakt drei neue Tabellen vorhanden und jeweils **leer**: `source_field_catalog`, `source_reference_observations`, `channel_account_registry`. `SQLite Integritaet OK`, Abschluss `PLATTFORM-SQLITE-MIGRATION ERFOLGREICH`.
- **Weder Seed-Daten noch Benutzer-Neuanlage noch Test-/Live-Webroot-Änderungen**, keine JTL-/Legacy-MariaDB-Updates, kein Plesk- oder Shopify-Deploy. `migrate:fresh`/`migrate:rollback` nicht erforderlich und ohne Freigabe verboten.

**Nächster Gate vorbereiteter, noch nicht durch Betreiber ausgeführter Read-only-Test:** `platform/ops/verify-legacy-readbridge-live.sh` (PR36). Prüft tatsächliche private SQLite plus 3 neue Tabellen, erfordert gepinnten privaten Git-HEAD `a26a64d09597e41523e224c557bb1d4e39bdf052`, prüft serverseitig und ohne Passwortausgabe die HTTPS-Lesebrücken-Konfiguration und testet exakt einen authentifizierten JTL-Artikel-GET (`q=3474630337398`, erwartete JTL-ID 1428) und einen GET des bestehenden AI-Archivs für dieselbe EAN. Ein optionaler `jtl.fields`-GET zeigt nur Diagnose/404, da `api/jtl-fields.php` **noch nicht** in Legacy-LIVE ausgerollt ist. **Keine Amazon-/OpenAI-POSTs, kein neuer Preis-/Bestandsschreibzugriff oder Datenbank-Seed.** Öffentlicher Filament-HTTPS-Test und Plesk-Testdomain-Cutover folgen erst nach separater Sicherung/Freigabe.


## 22.11.2026 · Laravel-GET-Lesebrücke gestoppt: private Basic-Auth-Konfiguration fehlt

**Betreiber-PuTTY-E2E-Ausgabe** nach erfolgreich nachgewiesener isolierter SQLite-Quellkatalogmigration:

```text
=== HW24 LARAVEL ZU LIVE-LEGACY READ-BRIDGE: NUR GET ===
OK: Private Laravel-SQLite und alle drei Quellkatalog-Tabellen
STOP: Live-Lesebruecke nicht konfiguriert (URL oder Basic-Auth fehlt).
Bitte Zugangsdaten nur auf dem Server in der privaten Laravel-Konfiguration hinterlegen.
```

**Diagnose:** Kein Netzwerk- oder JTL-SQL-Fehler nachgewiesen. Die private Laravel-Konfiguration `config('hw24_legacy.base_url')`, `.user` oder `.password` ist mindestens an einer Stelle leer/falsch; `LegacyReadBridge` wurde vor dem ersten HTTPS-Abruf sicher gestoppt. `api/jtl.php` am LIVE-Host ist unter Basic-User `nvoss` zuvor authentifiziert funktionstüchtig nachgewiesen, ebenso KI-Archiv, und die drei Plattform-SQLite-Tabellen sind vorhanden. **Niemals Passwort in Chat, GitHub oder Shell-Befehl notieren.**

**Nächster vorbereiteter Schritt, auf Plesk noch nicht durchgeführt:** Interaktives Python-Skript `platform/ops/configure-private-legacy-readbridge.py` im isolierten Draft PR36. Verlangt root-PuTTY-TTY, festgelegten privaten Git-HEAD `a26a64d09597e41523e224c557bb1d4e39bdf052`, private `.env` im Besitz `nicovoss`, Modus 0600, keine getrackten Veränderungen. Passwort verdeckt per `getpass` abfragen, mit genau einem HTTPS GET und Umleitungssperre gegen existierenden LIVE-JTL-Endpunkt prüfen, nur drei `HW24_LEGACY_*`-Variablen in vorhandener `.env` gezielt und atomar speichern, root-private Rückfallsicherung vor Änderungen, optionalen Config-Cache gezielt leeren/bei Fehler wiederherstellen, Laravel-eigene Config-Auswertung mit Passwortwert aus isoliertem STDIN ohne Anzeige vergleichen. Keine Änderung an APP_KEY, Admin-E-Mail, DB_CONNECTION, SQLite, JTL/Legacy-MariaDB, Webroot oder Plesk-Git. Die nachfolgende `verify-legacy-readbridge-live.sh` wird erst nach erfolgreicher privater Konfiguration manuell wiederholt.


## 22.11.2026 · Laravel-LIVE-Lesebrücke erfolgreich: JTL 200, KI-Archiv 200

**Vom Betreiber direkt gepostete PuTTY-Beweise:**
- Interaktives, passwortverdeckt bedientes `platform/ops/configure-private-legacy-readbridge.py` erfolgreich (`HW24 private .env render safeguards: PASS`). Bereits bestehendes LIVE-HTTP-Basic-Konto `nvoss` einmal über HTTPS GET authentifiziert, bevor irgendetwas gespeichert wurde.
- Root-private Sicherung der bisherigen Laravel-`.env` wurde vor den Änderungen auf Plesk erstellt, **ohne** Passwort in Chat/Repo/Shell-Befehlszeile zu drucken. Genau `HW24_LEGACY_URL`, `HW24_LEGACY_BASIC_USER`, `HW24_LEGACY_BASIC_PASSWORD` in der bestehenden `0600`-Konfiguration übernommen; Laravel lädt alle drei Werte korrekt und nutzt weiter die private SQLite. Abschluss `LARAVEL-LESEBRÜCKE SICHER KONFIGURIERT`.
- Direkt danach erfolgreich wiederholter **serverseitiger** `verify-legacy-readbridge-live.sh` (nicht bloß GitHub-CI): private Laravel-SQLite und drei Quellkatalogtabellen **OK**, Bridge auf exakt `https://artikelmanager.hairworld24.de` gepinnt, JTL HTTPS **HTTP 200** mit `jtl_status=current_snapshot`, **genau einem Treffer** EAN `3474630337398` / JTL-ID `1428`; bestehendes KI-Archiv **HTTP 200** mit **1 Eintrag für diese EAN**.
- Optionaler Detailfelder-Endpunkt `api/jtl-fields.php` auf LIVE **HTTP 404**: **erwartet**, da die API aus Draft PR41 noch nicht freigegeben oder bereitgestellt wurde. Die vollständige Filament-Quellfeldanzeige bleibt bis zum separaten, abgesicherten Test-/Live-Deployment eingeschränkt. Kein Fehler am bestehenden JTL-Hauptadapter.
- Test führte **keinen Amazon-Aufruf, keinen OpenAI-POST, keine Plattform- oder JTL-DB-Schreibaktion** durch. Legacy-Live- und Test-Webroots sowie der Plesk-Git-Testdeploymentpfad bleiben unverändert.

**Fortsetzung als sicherer Release-Gate statt voreiliger öffentlicher Freischaltung:** Im Draft PR36 entsteht `platform/ops/verify-filament-https-release-gates-readonly.sh`. Dieses führt nur Plesk-/Git-Info-SELECTs, maskierte Laravel-Konfigurations-Booleans, SQLite-/Adminexistenz-Checks, Dateirechte und anonyme HTTPS-401-Checks durch. Besondere Risiken vor Plesk-Test-vHost-Umstellung: ursprüngliche Git-Auslieferung auf Legacy-Root statt Filament, `APP_DEBUG=false`, `APP_URL=https://test-artikelmanager.hairworld24.de`, `SESSION_SECURE_COOKIE=true`, admin-only Auth, und die Tatsache, dass der 401-Schutz des **alten** DocumentRoots nicht automatisch auf den neuen `platform/public` übertragen werden muss. **Noch kein Plesk-Cutover**, anschließend separater Rückfallplan und echter HTTPS-FPM-/Session-Browser-Test.


## 22.11.2026 · Filament-HTTPS-Sicherheitsgates auf Plesk: 0 Warnungen

**Direkte Betreiber-PuTTY-Ausgabe** des zunächst nur lesenden `platform/ops/verify-filament-https-release-gates-readonly.sh` vom **22.11.2026 19:08:35 UTC**:
- Alte Testdomain bleibt auf Legacy-Webroot; archivierter/ergänzter TEST-ASIN-Endpunkt unverändert.
- Plesk-Git `HW24-scanner.git` meldet **Modus manual**. Eine manuelle Plesk-Bereitstellung ist weiterhin zu vermeiden, bis Plesk-Git-Zielpfad und tatsächlicher DocumentRoot getrennt dokumentiert sind.
- Privater Framework-Klon entspricht gepinntem Commit `a26a64d09597e41523e224c557bb1d4e39bdf052`; keine lokalen getrackten Git-Änderungen.
- Laravel-Konfiguration (ohne Secrets ausgegeben): **APP_ENV=production**, **APP_DEBUG=false**, **APP_URL=https://test-artikelmanager.hairworld24.de**, **DB=private SQLite**, geschützte LIVE-HTTPS-Lesebrücke konfiguriert, Filament-Admin-E-Mail gesetzt und Benutzer in privater SQLite vorhanden, Session-Treiber geeignet, Host-only-Cookie, **SESSION_SECURE_COOKIE=true**.
- `nginx` und `www-data` dürfen CSS/JS lesen, ohne `.env` und SQLite lesen zu dürfen; PHP-Site-User darf Laravel-`public/index.php` lesen.
- Bisherige LIVE-/TEST-JTL-APIs antworten anonym **HTTP 401** unter HTTPS. **Wichtig:** Nur Schutz des *alten* Test-Webroots nachgewiesen. Beim Wechsel des DocumentRoots muss Authentifizierung für `/admin/login`, PHP-FPM, open_basedir, Laravel-Session, CSS/JS und den tatsächlichen Browser separat getestet werden.
- Abschluss `READ-ONLY RELEASE-GATES ABGESCHLOSSEN, SHELL-WARNUNGEN: 0`; kein Change am Server.

**Offener letzter Mechanik-Gate, separat vorbereitet und noch NICHT auf Plesk ausgeführt:** `platform/ops/verify-plesk-cutover-mechanics-readonly.sh` im PR36. Prüft Plesk CLI-Typ (Subdomain oder Domain), exakten alten DocumentRoot, ob Basic-Auth an der alten `.htaccess` oder vHost-Konfiguration hängt (ohne Passwort/Configs zu drucken), Plesk-Git-Manualmodus **und** mögliches separates Deploymentziel sowie 401 an drei alten HTTPS-Test-URLs. Die Plesk-Dokumentation bestätigt, dass `-www-root` bei Domain wie Subdomain relativ zur Subscription interpretiert wird; konkrete Befehlsform hängt vom nachgewiesenen Plesk-Typ ab. **Noch KEIN Domain-Cutover** oder Rollback-Befehl ausgeführt. Diese Mechanikprüfung soll falschen CLI-Aufruf, verlorenen Basic-Auth-Schutz und versehentliche Veröffentlichung privater Anwendungsdateien verhindern.


## 22.11.2026 · Plesk-Cutover-Mechanik: 0 Warnungen, Subdomain, vHost-Basic, Plesk-Git manual

**Betreiberbeleg** aus `platform/ops/verify-plesk-cutover-mechanics-readonly.sh`, UTC **2026-11-22 19:17:01**:
- Bisheriger Test-DocumentRoot weiterhin `/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de`.
- Plesk-Typ **subdomain** (nicht unabhängig angelegte Site). Offizieller Plesk-CLI-Subdomain-Mechanismus ist `plesk bin subdomain --update test-artikelmanager -domain hairworld24.de -www-root <verzeichnis-relativ-zur-subscription>` (Quelle: Plesk Obsidian subdomain CLI).
- Der bisherige Webroot besitzt **keine** `.htaccess`; `platform/public/.htaccess` enthält Laravel-Rewrite, aber keine Basic-Auth. Zentrale Plesk-vHost-Konfigurationen `nginx.conf`, `httpd.conf`, `last_nginx.conf`, `last_httpd.conf` **enthalten Basic-Auth-Anzeichen**, darunter PHP-FPM. Das bedeutet *nur*, dass vHost-Auth vermutlich erhalten bleibt; es beweist noch keinen Schutz **nach** DocumentRoot-Wechsel.
- Plesk-Git-Veröffentlichungsmodus **manual**, `PathVerdict=NO_PRIVATE_PATH`. Keine neue Git-Bereitstellung auslösen; alter Git-Deploypfad und private Laravel-App bleiben unabhängig.
- Framework-HEAD `a26a64d...` bestätigt; CSS/JS unter `platform/public` für `nginx` und `www-data` lesbar; private Dateien aus Sicht dieser Webserver nicht lesbar.
- HTTPS anonym: `/`, `/admin/login`, `/api/jtl.php?ean=3474630337398` auf bisherigem TEST-Domain-Webroot **HTTP 401**. Testabschluss **0 Warnungen**, keine Änderungen.

**Bereitgestelltes, aber auf Plesk NOCH NICHT ausgeführtes Cutover-Skript** im isolierten Draft PR36: `platform/ops/cutover-testdomain-filament-with-rollback.sh`. Ausschließlich TEST-`subdomain` und fest gepinnter privater Laravel-HEAD, alter/new-root exakt geprüft; Plesk-Git muss manuell und auf ein vom Framework getrenntes Ziel zeigen, vorhandenes Admin-/Cookie-/SQLite-/JTL-Bridge-Setup validiert. Erst ein root-privates manuell ausführbares Rollback-Skript plus Plesk-Protokoll erstellen, dann **nur** den TEST-DocumentRoot auf `hw24-console-private/repo/platform/public` schalten. Danach HTTPS mit echtem Basic-Auth-Header nur aus privater Laravel-Umgebung im Speicher testen: *ohne* Zugang 401/403, mit Zugang Filament-/admin/login=200, CSS/JS=200, /admin und /admin/artikel weiterhin auth-geschützt, `/.env`, `database/database.sqlite`, `vendor/autoload.php` mit Basic-Login 403/404. Bei irgendeinem fehlgeschlagenen Check automatisches `subdomain --update ... -www-root test-artikelmanager.HAIRWORLD24.de` und Plesk-DocumentRoot-Check. **Kein Git-Pull/Deploy, kein Composer, kein SQLite-/JTL-Write, kein Veränderung der LIVE-Domain.**
- Die **alten Test-APIs** einschließlich ASIN bleiben auf dem alten Test-Webroot gespeichert, sind nach erfolgreichem Umschalten aber unter der Testdomain **nicht mehr direkt erreichbar**; die Laravel-Seite benutzt stattdessen GET-only die aktuelle LIVE-JTL-/KI-Brücke. `api/jtl-fields.php` auf LIVE weiterhin **404**, daher originale Detailfeld-Ansicht zunächst eingeschränkt.
- Ein erfolgreicher HTTPS-Smoke beweist Loginseite/Assets/Sicherheit, noch **nicht** die wirkliche Filament-Anmeldung mit Administratorpasswort im Browser oder alle Produktfelder; manuellen E2E nach dem Cutover ausführen und bei Bedarf root-privates Rollback-Skript verwenden.


## 22.11.2026 · Erster abgesicherter Filament-HTTPS-Cutover fehlgeschlagen, automatischer Rollback erfolgreich

**Vom Betreiber am 22.11.2026 gepostetes Serverprotokoll des tatsächlichen Testdomain-Wechsels:**

- Das sicherheitsgeprüfte Skript `platform/ops/cutover-testdomain-filament-with-rollback.sh` aus Featurebranch PR36 wurde per gepinnter GitHub-Version `e9cc5ceb6f60f02e5b1667d30740566f2399be7f` auf Plesk ausgeführt. Vorab erfolgreich: alter TEST-Basic-Schutz (3x 401), Plesk-Git `manual` mit getrenntem Ziel, private Laravel-/SQLite-/Session-/Admin-Konfiguration, lesbare CSS/JS-Assets und gesperrte private `.env`/SQLite.
- Vor dem Wechsel root-privates Rollback-Skript **`/root/hw24-backups/hw24-testdomain-rollback-20261122T193255Z-AUeMuYyB.sh`** und Plesk-Protokoll **`/root/hw24-backups/hw24-testdomain-plesk-20261122T193255Z-FV6kIzSw.log`** erstellt. Diese lokalen Dateien bitte nicht löschen.
- Der Plesk-DocumentRoot wurde **nur auf der Test-Subdomain** vorübergehend auf `hw24-console-private/repo/platform/public` umgestellt. Die HTTPS-Abnahme stoppte mit **`/admin/login HTTP 403`** beim authentifizierten GET mit serverseitig bezogenen Basic-Auth-Daten, obwohl der anonyme Basic-Auth-Schutz zuvor im Smoke-Test an `/admin/login` 401/403 erfüllen musste.
- Sicherheits-EXIT-Handler hat automatisch den alten TEST-DocumentRoot wiederhergestellt; explizit: **`ROLLBACK OK: Bisheriger Test-DocumentRoot wiederhergestellt.`** Das bedeutet keine aktive Filament-Testdomain. Keine JTL-/Legacy-/Shop-DB-Migration, keine neue Shopify-/Amazon-Transaktion oder Live-Webroot-Änderung.
- Die genaue Herkunft des authentifizierten 403 ist noch unbekannt. **Keine Behauptung**, dass Benutzerkennwort falsch sei oder Laravel selbst 403 zurückgebe; plausible Ursachen sind unlesbare `public/.htaccess` oder `public/index.php`, fehlende Apache-/Nginx-Traversal-Rechte, DocumentRoot-/vHost-Regeln, verweigerter Index, Plesk-FPM-/open_basedir-Konfiguration. Plesk dokumentiert solche Fälle als Permission-/Config-Probleme.
- **Noch KEIN zweiter Cutover-Versuch.** Für die gezielte Ursachenfindung wurde `platform/ops/diagnose-filament-testdomain-403-readonly.py` im isolierten Featurebranch vorbereitet (noch nicht auf Plesk ausgeführt). Zeigt nur erwarteten alten DocumentRoot, Unix-Dateimodi und Benutzer-Zugriffstests für `nginx`, `www-data`, `nicovoss`, plus **nur gezählte klassifizierte** historische nginx-/Apache-Fehler zum Umschaltzeitpunkt (2026-11-22 ca. 19:32 UTC / 20:32 CET). Keine rohen Logzeilen, IPs, Passwörter, kompletten Konfigurationen oder API-Anmeldedaten. Es verändert weder ACLs noch Webroots, Plesk, SQLite oder Git. Abhängig von den Ergebnissen ausschließlich die konkrete minimale Zugriffskorrektur planen und erst danach mit Rollback erneut testen.


## 22.11.2026 · 403-Ursache durch Apache-Log bestaetigt: public/.htaccess ist 0600

**Echter Betreiber-PuTTY-Nachweis** mit `platform/ops/diagnose-filament-testdomain-403-readonly.py` nach dem fehlgeschlagenen ersten Testdomain-Cutover:

- **Alter Test-DocumentRoot nach automatischem Rollback bestaetigt**, weder die Live-/JTL-Domains noch die private Laravel-SQLite wurden veraendert.
- Private Elternverzeichnisse `hw24-console-private` / Git-`repo` / `platform` / `platform/public` besitzen enge Zugriffsmodi `0750` / `0710` / `0710` / `0710`; `nicovoss`, `nginx`, `www-data` duerfen Verzeichnisgrenzen traversieren, soweit benoetigt.
- **Root-Cause bestaetigt:** `platform/public/.htaccess` ist `0600` Eigentümer `nicovoss`; `nginx=NEIN`, `www-data=NEIN` beim Lesen. `platform/public/index.php` ebenso `0600`, `nginx=NEIN`, `www-data=NEIN`. CSS/JS sind durch vorherige Public-ACL freigegeben (`0640`; `nginx=JA`, `www-data=JA`). Private `.env` und `database.sqlite` bleiben fuer beide Webserver-Nutzer unlesbar.
- Historischer Plesk-`error_log` enthaelt im Cutover-Zeitfenster jeweils **10 erkannte `htaccess_unreadable`**- und **10 `permission_denied_other`**-Anzeichen an zwei ausgewerteten Protokollpfaden. **Nicht 20 unabhaengige Fehler behaupten:** beide Pfade koennen dieselben Logeintraege zeigen. Befund untermauert Apache-403 durch unlesbare Laravel-`.htaccess`.
- **Kein zweiter Cutover durch Betreiber ausgefuehrt.**
- **Sicherheitskorrektur in isoliertem Featurebranch PR36 vorbereitet, auf Plesk noch NICHT ausgefuehrt:** `platform/ops/fix-filament-public-frontcontroller-acl.sh`. Pinned privater Laravel-HEAD `a26a64d09597e41523e224c557bb1d4e39bdf052`, alter Test-DocumentRoot, privater Code sauber und bisheriger 401/403-Basic-Schutz Voraussetzung. Root-privates `getfacl -p`-Backup **ausschliesslich** fuer `public/.htaccess` und `public/index.php`; dann `setfacl -m u:nginx:r,u:www-data:r` exakt auf diesen beiden freigegebenen PUBLIC-Dateien, weder auf deren Elternverzeichnissen noch `-R`; bei Fehler `setfacl --restore` auf urspruengliche ACLs. Fuehrt bytegenaue sha256-Vergleiche vor/nach und Leser-/Privatsperrentests als `nicovoss`, `nginx`, `www-data` durch; kein `.env`-Leserecht, keine SQLite-/Plesk-/Git-Mutation, kein DocumentRoot-Cutover.
- Nach erfolgreichem Betreiber-ACL-Test gesondert den bereits erprobten und abgesicherten Test-HTTPS-Cutover mit automatischer Plesk-Ruecknahme wiederholen; 403 kann durch zusaetzliche vHost/PHP-FPM/open_basedir-Regeln noch weitere Ursachen haben. Die eigentliche Browser-Admin-Anmeldung ist weiterhin nicht erfolgt.


## 22.11.2026 · Öffentliche Laravel-ACLs erfolgreich korrigiert

**Echter Betreiberbeleg aus PuTTY** nach Ausführung von `platform/ops/fix-filament-public-frontcontroller-acl.sh`:
- Alter Plesk-TEST-DocumentRoot nach früherem automatischen Rollback weiterhin aktiv. Zunächst private `.env` und `database.sqlite` für `nginx` und `www-data` unlesbar, alter HTTPS-Basic-Zugriffsschutz aktiv.
- Root-private **ACL-Sicherung vor jeder Änderung:** `/root/hw24-backups/hw24-public-acl-before-20261122T200445Z-XMHatmCP.acl`.
- Ausschließlich `platform/public/.htaccess` und `platform/public/index.php` mit `setfacl -m u:nginx:r,u:www-data:r` für die beiden Webserver-Benutzer freigeschaltet; kein rekursives chmod, keine Änderung an Laravel-Quelltext, privaten Verzeichnissen oder Plesk/Git.
- **Nachher echte User-Proben PASS:** PHP-Site-User `nicovoss`, Apache `www-data` und Nginx `nginx` können BEIDE öffentlichen Dateien lesen. Private `.env` und SQLite bleiben weiterhin unlesbar für Webserver. Abschlusssignal `GEZIELTER PUBLIC-ACL-FIX ERFOLGREICH. TEST/LIVE UNVERAENDERT.`
- Manuelle Restore-Möglichkeit nur wenn berechtigt nötig: `setfacl --restore='/root/hw24-backups/hw24-public-acl-before-20261122T200445Z-XMHatmCP.acl'`. **Die ACLs nicht zurücksetzen, bevor der Filament-Test-vHost erprobt ist.**
- Vor dem **noch nicht erfolgten zweiten TEST-Domain-Cutover** wurde `platform/ops/cutover-testdomain-filament-with-rollback.sh` im GitHub-Draft um ein neues Fail-Closed-Gate ergänzt: Verlangt `public/.htaccess` und `public/index.php` lesbar für `nicovoss`, `nginx` und `www-data`. Nur wenn alle alten Rollback-, Security-, SQLite-/Admin-/Git- und HTTP-Checks weiter grün sind, Test-Subdomain auf `platform/public` schalten, HTTPS-Login/Assets/Private-File-Schutz prüfen und ggf. automatisch rollbacken. Der erste 403-Ursachennachweis war Apache-`htaccess_unreadable`; **neuer HTTPS-Erfolg noch nicht behaupten**, vor tatsächlicher Betreiber-PuTTY-Ausgabe.


## 22.11.2026 · HW24-Filament-HTTPS-TESTDOMAIN ERFOLGREICH AKTIVIERT

**Betreiber-PuTTY-Ausgabe des zweiten abgesicherten Cutovers:** `platform/ops/cutover-testdomain-filament-with-rollback.sh` in gepinnter GitHub-Version `85cadba310241a292daba2ec6725161ed7e6d416` wurde nach erfolgreicher gezielter PUBLIC-ACL-Reparatur ausgeführt.

- Alte HTTPS-TEST-URLs vor Umstellung dreifach durch Basic-Auth geschützt. Plesk-Git-Deployment `manual` mit vom privaten Framework getrenntem Ziel. Laravel `APP_ENV=production`, sichere HTTPS-Cookie-/Admin-/private SQLite-/LIVE-Lese-Konfiguration bestanden. ACLs für CSS/JS und private `.env`/SQLite geprüft.
- Neues explizites Gate `3a`: **`public/.htaccess` und `public/index.php` sind für Apache, Nginx und den PHP-Site-User lesbar**. Vorherige 403-Ursache damit konkret beseitigt.
- Neue Sicherungen **vor** Umschalten angelegt: Rollback-Skript `/root/hw24-backups/hw24-testdomain-rollback-20261122T200720Z-wJVBmgIB.sh` und Plesk-Log `/root/hw24-backups/hw24-testdomain-plesk-20261122T200720Z-raNJ0dwb.log`. Nicht löschen. Vorheriges ACL-Backup weiterhin `/root/hw24-backups/hw24-public-acl-before-20261122T200445Z-XMHatmCP.acl`.
- **Nur der bestehende Plesk-Test-Subdomain-DocumentRoot** `test-artikelmanager.hairworld24.de` wurde auf `hw24-console-private/repo/platform/public` umgestellt. HTTPS-Smoketest **PASS**: anonym Basic-401/403; authentifizierter `GET /admin/login` **HTTP 200** mit Filament-HTML; CSS/JS **HTTP 200** und nicht leer; `/admin`/`/admin/artikel` ohne Filament-Session weiterhin geschützt; `/.env`, `/database/database.sqlite`, `/vendor/autoload.php` trotz Basic-Zugang über HTTPS nicht erreichbar.
- Serverabschluss **`=== HW24 FILAMENT AUF HTTPS-TESTDOMAIN ERFOLGREICH AKTIVIERT ===`**. LIVE-Artikelmanager `https://artikelmanager.hairworld24.de` und bisherige TEST-Legacy-Dateien unverändert; alte TEST-URLs zeigen nun aber auf Filament statt die Legacy-Dateien. Kein Git-Pull, kein Composer, keine JTL-/Shopify-/Amazon-DB-Schreibaktion.
- **Noch nicht nachgewiesen:** tatsächliche Filament-Administratoranmeldung mit E-Mail/Passwort im Browser; Livewire-Session-/CSRF-Funktionalität nach Login; JTL-Artikelbedienung über die neue Oberfläche; KI-Archiv per Filament-Klick; Layout/Mobilnutzung. Die Backend-`LegacyReadBridge` hat den korrekten LIVE-JTL-GET `q=3474630337398`, JTL-ID 1428, und KI-Archiv-GET mit einem Treffer zuvor isoliert erfolgreich bewiesen, aber **nicht** durch die Weboberfläche. Die vollständige Originalfelder-Anzeige bleibt erwartbar eingeschränkt, weil `api/jtl-fields.php` im LIVE-Legacy-Webroot noch 404 liefert.
- **Jetzt Betreiber-Browser-E2E**: unter `https://test-artikelmanager.hairworld24.de/admin/login` erst bestehende Basic-Auth, dann separate Filament-Admin-Anmeldung. Menü `Artikel & APIs` → `JTL-Artikel suchen` → `3474630337398` → `Artikel laden`, idealerweise `current_snapshot`, ein Treffer. Danach `KI-Archiv lesen` (bestehender Eintrag). `Originalfelder und Kanalpreise lesen` wird voraussichtlich mangels LIVE-Endpunkt noch nicht verfügbar gemeldet. Amazon-Live-Aufruf nicht automatisch auslösen. Bei Browser-/Sessionfehlern **kein** blindes Plesk-Git-Deploy, sondern gezielte Diagnose und das oben auf dem Server vorhandene getrennte TEST-Rollback-Skript.


## 22.11.2026 · Management-Cockpit und operative JTL-Artikelzentrale im isolierten Framework-Branch entwickelt

**Entwicklerfortschritt nach realen HTTPS-Browser-Screenshots des Betreibers:**
- Betreiber ist in Filament `test-artikelmanager.hairworld24.de/admin` und `/admin/artikel` angemeldet. Bisherige 4 schmale Statuskarten und Artikelzeile waren weniger übersichtlich als die alte Legacy-Pflege. Bewährte Legacy-Informationsdichte bleibt Ziel; Laravel 13 + Filament 5 bleibt langfristiges, isoliertes Framework.
- In Draft PR36 `feature/hw24-platform-laravel13-filament5-20261122` **neues responsives Management-Cockpit entwickelt**: `Hw24SystemOverview` jetzt Livewire-`Widget` mit separatem Blade-View `filament/widgets/hw24-system-overview.blade.php`, voller Widget-Breite, 2-Spaltenkarten, Mobilumbruch, klarer Navigation, sichtbarer Datenherkunft, nicht bloß ungünstig umbrechende 4 technische Stat-Karten.
- Dashboard lädt **keine** externen APIs automatisch bei Page-Render. Nur manueller Klick für genau eine JTL-Referenz-EAN `3474630337398` (`GET api/jtl.php`), erwartete JTL-ID `1428`, Snapshot `current_snapshot` und quellengebundene Felder; separater manueller Archiv-GET für dieselbe EAN (`GET api/ai-reviews.php`, max. 5 gespeicherte Datensätze). Keine automatische Amazon-SP-API-Abfrage, kein neuer OpenAI-Auftrag/POST und keine DB-Schreiboperation. Shopify/eBay bleiben ausdrücklich nicht verifiziert, Katalogtreffer sind keine aktiven Verkäuferangebote.
- Wichtig: JTL-`netto_vk` ist ein Originalpreis ohne bislang bestätigte Währung; Cockpit markiert Währung unbestätigt statt willkürlich „EUR“. Brutto-VK, Artikeltyp, Steuerklassenname und Lieferstatus werden nicht angenommen.
- Neue Präsentationsklasse `App\Domain\Jtl\ArticleCard` erlaubt nur whitelisted original-/verifiziertes Zusatzfeldmaterial; die Artikelansicht besitzt nun einen reichhaltigeren Stammdatenblock einschließlich JTL-ID, EAN, Artikelnummer, HAN, verifizierter Warengruppe, Artikeltyp nur wenn belegt, Liefer-/Steuerklasse mit Klartext-Unsicherheitsmarkierung, Füllmenge 90.000→90 (nur Darstellung), EK, Netto- und nur quellenbelegten Brutto-VK, Währung, ASIN (ohne Amazon-Angebotsgleichsetzung). Bisheriges `api/jtl-fields.php` LIVE HTTP 404 bleibt offen; keine automatische Neuveröffentlichung der Legacy-API.
- Sicherheits-/Funktionstests im Featurebranch prüfen: kein API-Call beim Dashboard-Rendern, nur explizite und einzeln begrenzte JTL-/Archiv-GETs, stale-Snapshot = nicht verifiziert, source-safe Artikelkarte. Verifizierter CI-Green-Stand für neue UI bis Commit `b679642212ac1daace71af7abcc65fe69dc293bb` (GitHub run `45065849788`), kein Deployment dieses neuen UI-Standes bis Betreiber-PuTTY-Beleg.
- Für die bereits unter `platform/public` aktive TEST-Domain wurde ein separates gesichertes Operator-Skript vorbereitet: `platform/ops/upgrade-private-framework-cockpit-ffonly.sh` (nachfolgender CI-Sicherheitscheck). Gepinnt von privatem Server-HEAD `a26a64d09597e41523e224c557bb1d4e39bdf052` auf freigegebenen UI-HEAD `b679642212ac1daace71af7abcc65fe69dc293bb`, über Git fast-forward, begrenzte Dateipfad-Allowlist, kein `.env`, kein `composer.lock`-Änderung, keine Migration, kein Plesk-Git-Deploy. Bestehenden Test-DocumentRoot, Basic-Auth-/PHP-ACLs, geschützte SQLite, Konfiguration, Admin unverändert; vor UI-Gitwechsel neues konsistentes root-privates SQLite-Snapshot. Danach PHP-Lint und gezielter Laravel-View-Cache-Clear; Fehler löst unter passenden Git-Bedingungen Code-Rollback aus. **Operator hat dieses Upgrade noch nicht ausgeführt**. Bevor Befehl an Betreiber geht CI-Gate für Skript abwarten.


## 22.11.2026 · UX-Vergleich Alt/Neu und Cockpit-Upgrade vorbereitet

**Nutzerentscheidung / Vergleich anhand der echten Login-, Dashboard- und Artikelpflege-Screenshots:** Legacy-Artikelpflege ist aktuell die bessere unmittelbare Arbeitsoberfläche (mehr bewährte fachliche Informationen und Arbeitsabläufe); das neue Laravel-13-/Filament-5-TEST-Framework ist das bessere Sicherheits- und Erweiterungsfundament. Nutzer fordert aktiven Ausbau: mehr verifizierte Echtinformationen, weniger schmale Karten und nutzloser Leerraum, langfristig alle Verkaufskanäle je Konto. Keine künstlichen Besucherzahlen, kein aggressives API-Polling und keine erfundenen Preise.

**Stand auf GitHub im isolierten Draft PR36, noch NICHT zum gehosteten Testserver ausgeliefert:**
- `platform/app/Filament/Widgets/Hw24SystemOverview.php` und `platform/resources/views/filament/widgets/hw24-system-overview.blade.php`: breites Cockpit mit responsiven zwei Spalten, lesbaren Statuskarten, direkten Sprunglinks und manuellen echten Datenproben. `inspectJtl` ruft nur auf ausdrücklichen Klick per bestehender `LegacyReadBridge` die aktuelle LIVE-GET-API für die Referenz-EAN `3474630337398` ab, verifiziert exakte JTL-ID `1428`, `current_snapshot` und eindeutigen Treffer; `inspectArchive` ruft ausschließlich maximal 5 bereits gespeicherte KI-Berichte per GET ab. **Kein automatischer Amazon- oder OpenAI-POST, keine Intervallabfragen, keine Schreiboperation.** Dashboard zeigt bewusst KEINE globale JTL-Artikelzahl oder historische Umsätze aus einer einzigen Referenzprobe. Widget überspringt nicht die Filament-Admin-Anmeldung.
- `platform/app/Domain/Jtl/ArticleCard.php` als reiner Whitelist-Presenter und `ArticleLookup.php` erweitern den gesicherten JTL-GET-Artikel um ausdrücklich quellengebundene Originalfelder: JTL-Artikel-ID, HAN, Artikelnummer, Warengruppe, Artikeltyp, Lieferstatus-ID bzw. nur tatsächlich vorhandener Klartext, Steuerklasse-ID/Klartext, Maßmenge, Netto-VK/Netto-EK, Brutto-VK nur mit angegebener Herkunft, Währung nur wenn belegt, JTL-ASIN ohne automatisches Amazon-Matching, Sync-Zeit. Mengenanzeigen trimmen nur Null-Nachkommastellen (90.000 → 90; 2.500 → 2,5), ohne gemutmaßte Einheit. Responsive HTML im `article-lookup.blade.php`.
- Neue automatisierte Prüfungen `platform/tests/Unit/ArticleCardTest.php` und `platform/tests/Feature/Hw24DashboardSafetyTest.php`, sowie bereits vorhandene `Hw24DashboardReadOnlyTest.php`: JTL-Brutto-/Währungs-/Statuswerte nicht halluzinieren, Dashboard-Rendervorgang sendet **keine** HTTP-Anfrage, manuelle Quelle-GETs führen jeweils genau einen Request aus.
- **Präziser, im CI geprüfter Ziel-Codecommit:** `b679642212ac1daace71af7abcc65fe69dc293bb`. Der aktuelle TEST-Server ist gemäß letzter dokumentierter Betreiber-Ausgabe weiterhin auf **`a26a64d09597e41523e224c557bb1d4e39bdf052`**. Neue UI daher noch nicht live. Die bestehende TEST-Subdomain ist inzwischen auf `platform/public` umgestellt und hat echten Browser-Admin-Login bestanden.
- `platform/ops/upgrade-test-cockpit-ffonly.sh` als gesonderter Operator-Gate, **noch NICHT auf Plesk ausgeführt**: prüft aktuellen TEST-Webroot, privaten alten Git-HEAD, erwarteten Origin/Branch, Whitelist des Code-Diffs und anonymen HTTP-Basic-Schutz; fertigt root-private Rollback-Metadaten und konsistentes SQLite-Backup per Site-User an; verändert **ausschließlich** den bereits aktiven privaten Framework-Git-Code per `merge --ff-only` auf den gepinnten, CI-geprüften Commit; kontrolliert PHP-Lint/Artisan-Routen/HTTPS-Auth/private Dateirechte; bei Fehler automatischer sicherer Rücksprung auf bisherigen Code, sofern Git-HEAD und Arbeitsbaum unverändert. **Keine** Plesk-Subdomain-Umstellung, Composer-Aktion, Migration, Seeding oder JTL-/Amazon-/Shopify-Schreibaktion.
- Bestehende `api/jtl-fields.php` auf Legacy-LIVE bleibt 404 bis gesondertem sicheren Release; die zusätzliche Originalfelder-Einzelseite ist weiterhin unvollständig. Gesamtumsatz, echte Kanalverkaufspreise, eigener eBay-/Shopify-Angebotsstatus sind vor Produktivabnahme **nicht** als verfügbar zu markieren.

**Abnahmekette:** GitHub Laravel-13-/Filament-5-CI muss für die aktuelle UI grün sein; dann Operator-gestützte TEST-only Code-Aktualisierung; Browser-Sichtprüfung Dashboard, manuelle JTL-/KI-Datenprobe und responsive Artikelansicht; erst danach weitere JTL-Feld-/Kanal-API-Rollouts und Dashboard-Aggregate.


## 22.11.2026 · Responsive Cockpit-Upgrade auf der aktiven TEST-Domain durch Betreiber erfolgreich eingespielt

**Direkter PuTTY-Nachweis des Betreibers** vom erfolgreichen `platform/ops/upgrade-private-framework-cockpit-ffonly.sh`, gepinnte Skriptversion `d42f72867f8b095b6c452ca566fa469de894d172`:

- Vorab exakter TEST-Plesk-DocumentRoot `platform/public`, alter privater Framework-HEAD `a26a64d09597e41523e224c557bb1d4e39bdf052`, keine getrackten Änderungen, bestehende ACLs für `public/index.php` und `public/.htaccess` und private `.env`/SQLite-Sperren erfolgreich kontrolliert. Live-Domain dabei nicht berührt.
- **18 exakt erlaubte Pfade** überprüft. `git fetch` holte neuere Remotebranch-Objekte (Remote enthielt bereits `3ba3f54`); **installiert wurde bewusst nur der geprüfte gepinnte UI-Commit `b679642212ac1daace71af7abcc65fe69dc293bb`**, nicht der neuere Remote-HEAD. Fast-Forward von `a26a64d` nach `b679642`, 2.164 Einfügungen und 30 Löschungen. Zur neuen Version gehören responsives HW24-Dashboard als Filament-Custom-Widget, per Klick begrenzte JTL-/KI-Archiv-Lesebelege, um Quellwerte ergänzte JTL-Artikelansicht und Tests, ohne automatische Amazon-/OpenAI-Aufrufe.
- Konsistentes SQLite-Backup mit vorab bestätigter Integrität: **`/root/hw24-backups/hw24-cockpit-before-20261122T202336Z-T9ZXY0Fz.sqlite`**. Git-/Rollback-Metadaten: **`/root/hw24-backups/hw24-cockpit-git-before-20261122T202336Z-OCAOsRon.txt`**. Nicht löschen.
- Nach dem Update wurden die kompilierten Laravel-Blade-Views gezielt geleert. Das Skript meldete `=== HW24 COCKPIT-QUELLCODE ERFOLGREICH AKTUALISIERT ===`. `composer.lock`, Laravel-`.env`, Test-Plesk-Webroot/Basic-Auth, JTL und LIVE-Artikelmanager wurden im Skript nicht verändert. Keine DB-Migration, keine Amazon-/OpenAI-POSTs.
- **Noch nicht direkt vom Betreiber nach dem Upgrade belegt:** tatsächlicher Browser-Render des breiteren Dashboards, Livewire-Klicks `JTL-Referenz prüfen`/`KI-Archiv prüfen`, neue Artikelkarten sowie Mobilansicht. Nächster Schritt: `https://test-artikelmanager.hairworld24.de/admin` und `/admin/artikel` nach `Strg+F5` im authentifizierten Browser prüfen. Kein zweiter Git-Fetch/Deploy nötig.
- Weitere Funktion `api/jtl-fields.php` bleibt auf LIVE-Legacy **HTTP 404** (PR41 Draft, noch kein Rollout), somit ist vollständiges Originalfeld-Screening noch nicht über Filament verfügbar. Brutto-/Währungs-/Lieferstatus-Zuordnung bleibt ausschließlich mit verifizierter Quelle zulässig.


## 22.11.2026 · Browserabnahme nach Cockpit-Upgrade: Dashboard funktioniert, Artikelansicht visuell noch unzureichend

**Quelle:** Drei vom Betreiber direkt bereitgestellte Browser-Screenshots der angemeldeten HTTPS-TEST-Anwendung, Datei-Bezeichnungen `image(20261122-202617).png`, `image(20261122-202650).png`, `image(20261122-202709).png`. Sichtprüfung der tatsächlich auf dem Server installierten **privaten Framework-Version `b679642212ac1daace71af7abcc65fe69dc293bb`**; die Screenshot-Dateien selbst wurden nicht in GitHub hochgeladen.

### 1. Browser: TEST-Dashboard /admin

- **Erfolgreich sichtbar:** Einmaliger Filament-Administrator-Login war bereits erfolgt; Dashboard ist mit der neu gestalteten dunkelblauen Hero-Zeile `Deine Produktdaten. Ein klarer Überblick.` erreichbar. Navigation `Dashboard` und `Artikel & APIs` sowie der Button `Artikel & Schnittstellen öffnen` sind sichtbar. Statt vier schmaler Kacheln hat das Cockpit zwei Spalten mit vier breiteren Karten, und die Informationen passen deutlich besser in die Oberfläche.
- Die beiden bewussten Klickprüfungen sind im Screenshot als **`Probe bestätigt`** für JTL und **`Archiv geprüft`** für das KI-Archiv markiert. JTL `HTTP 200 · current_snapshot`, Referenz-EAN **`3474630337398`**, JTL-Artikel-ID **`1428`**, Artikel **`Matrix Socolor Beauty - 6C - Dunkelblond kupfer - (90 ml)`**, JTL-Bestand **`0.000`**, VK netto dargestellt als **`11,76 (Währung unbestätigt)`**. Dies belegt nur einen geprüften Referenzartikel, keine Gesamtanzahl, keine garantierte Verkaufbarkeit und keinen in JTL ausdrücklich bestätigten EUR-/Bruttopreis.
- KI-Archiv-Karte zeigt **`1 Archivtreffer (max. 5)`**, `HTTP 200`; dies sind bereits gespeicherte Datensätze, kein neuer OpenAI-Aufruf. Im Screenshot stehen manuelle Prüfzeitpunkte **22.11.2026 20:26:00** bzw. **20:26:01**.
- Amazon ist korrekt als **`Angebote ungeprüft`** und Shopify/eBay als **`Nicht verifiziert`** gekennzeichnet; es wurden **keine** fiktiven Brutto-/Kanalpreise, Umsätze oder Marketplace-Angebote eingeblendet. Das Dashboard ist hinsichtlich Lesbarkeit **spürbar besser als die bisherigen vier Statuskarten**. Separater Test auf einem echten Mobilgerät noch offen.

### 2. Browser: TEST-Artikelzentrale /admin/artikel

- **Funktional nachgewiesen:** Artikelkennung **`3474630337398`** eingegeben, `Artikel laden` reagiert; Status **`current_snapshot`**, API `responded`, HTTP **200**. Darstellung enthält Artikelname, EAN, Bestands-/VK-Werte, JTL-ID, Artikelnummer, HAN, Warengruppe, Artikeltyp, Lieferstatus, Steuerklasse, Maßmenge, Einkaufspreis, VK netto, VK brutto, Währung, JTL-ASIN. Die Füllmenge wird als **`90`** statt `90.000` angezeigt, wobei die Maßeinheit als ungeprüft gekennzeichnet ist.
- **Datenlücken transparent sichtbar:** Lieferstatus **ID 1, Klartext offen**, Artikeltyp **noch nicht aufgelöst**, JTL-Brutto-VK **Quelle noch nicht bestätigt**, Währung **noch nicht belegt**. Bekannte JTL-ASIN **`B091J3HV6B`** ist keine bestätigte EAN/ASIN-Identität; frühere Amazon-Katalogprüfung zeigte abweichende Amazon-EAN, daher nicht automatisch verknüpfen.
- **Weiterhin offener Endpunkt:** Beim Klick auf `Originalfelder und Kanalpreise lesen` erscheint **`JTL-Feldquelle nicht verfügbar oder noch nicht bereitgestellt.`**. Der zugehörige LIVE-Legacy-Endpunkt `api/jtl-fields.php` ist nach vorigen tatsächlichen Prüfungen **HTTP 404**, Draft PR41 noch nicht bereitgestellt.
- **Amazon-Bereich:** `Verbunden · HTTP 200 · Quelle cache · Treffer 0`. Das belegt nur die Cacheantwort, **keinen** aktuellen SP-API-Live-Treffer und **kein** aktives Verkäuferangebot. **KI-Archiv:** `Archiv erreichbar · HTTP 200 · Einträge 1`.
- **Sichtbarer UX-/CSS-Mangel:** Der erweiterte Stammdatenbereich fällt im Screenshot auf eine sehr lange, enge, weitgehend **einspaltige Folge von Feldnamen und Werten** zurück. Der Suchbereich wirkt gedrängt, die tabellarischen Spaltenköpfe und Werte stehen ungewöhnlich eng zusammen, die neue Einleitung `Artikelzentrale` wirkt nahezu unformatiert, obwohl Filament-Karten und Buttons selbst korrekt gerendert werden. Das ist für produktive Artikelpflege **nicht abgenommen**. **Ursache noch nicht bewiesen:** Es ist zu untersuchen, ob die in `resources/views/filament/pages/article-lookup.blade.php` eingesetzten Tailwind-Utility-Klassen von der tatsächlich geladenen Filament/Vite-Theme-CSS-Datei erfasst werden. Nicht vorschnell pauschale Dateirechte oder `chmod -R` ändern. Die alte Legacy-Ansicht bleibt beim Artikel-Detail derzeit ergonomischer.

### 3. Status und nächste geprüfte Schritte

1. **Stand der Tests:** Dashboard-HTTP/Livewire/Informationskarten **positiv per Screenshot**; Artikel-Lese-APIs **teilweise positiv**; vollständige Artikel-Detailgestaltung und Originalfeldanzeige **noch nicht abgenommen**.
2. **Nächster Code-Schritt:** CSS-/Theme-Pipeline und Browser-DOM der Filament-Artikelansicht prüfen; ausdrücklich responsive, kompakte, mehrspaltige Feldgruppen mit Quellmarkierungen, nutzbarem Suchformular und klar lesbarer Preismatrix schaffen. Vorher/Nachher über Screenshots vergleichen.
3. **Nächster Daten-Schritt:** READ-ONLY `api/jtl-fields.php` aus PR41 zunächst abgesichert mit Auth/HTTPS/Schema-/Payloadtests bereitstellen und **erst nach** Abnahme die Originalfelder in Filament lesen; JTL-Brutto-/Lieferstatus-/Artikeltyp-/Währungsquellen in Windows-SQL getrennt verifizieren. Amazon/eBay/Shopify-Angebotsdaten nur mit Konto-, Quelle-, Preisart- und Zeitbeleg anzeigen.
4. **Sicherheitsregeln:** Die echte LIVE-Domain und JTL-Wawi/WMS bleiben unverändert; kein Plesk-Git-Deploy, keine automatischen kostenpflichtigen API-Aufrufe, keine SQL-Schreiboperationen. Neue Änderungen nur in isolierten Featurebranches, mit CI, pinned fast-forward, Sicherung/Rollback und erneuter TEST-Browserabnahme.

### Verbindliche Arbeitsjournal-Führung für die weitere Umsetzung

**Jeder nachfolgende substanzielle HW24-/HAIRWORLD24-Arbeitsschritt** wird mit Datum, tatsächlicher Aktion, betroffenem System (GitHub/CI/privater TEST-Klon/Plesk/JTL/API/LIVE), Versions-/Commitstand, Beobachtung oder Testergebnis, Backup-/Rollback-Nachweis, offenem Risiko und nächstem Schritt dokumentiert. **Entwickelt, in CI getestet, auf dem Server ausgeführt und im Browser bestätigt sind getrennte Status**. Keine serverseitige Änderung als durchgeführt dokumentieren, bevor eine passende Betreiber-/Tool-Ausgabe vorliegt. Keine Passwörter, Tokens, Sitzungscookies, geheimen Pfade mit Zugriffswerten oder anderen Secrets ins Journal aufnehmen.


## 22.11.2026 · Artikelzentrale V2 in fünf logisch getrennte Bereiche umgebaut

**Anlass:** Betreiber meldete nach echten TEST-Browser-Screenshots ausdrücklich: `Alles so unübersichtlich. Bring hier erstmal ... Ordnung rein`. Voriger Stand auf TEST-`/admin/artikel` war serverseitiger privater Framework-Git-HEAD **`b679642212ac1daace71af7abcc65fe69dc293bb`**, CSS-Utilities im Custom-Blade-Kontext teilweise nicht wirksam. Das bestätigte sich an einer nahezu einspaltigen Feldliste sowie gedrängtem Suchfeld und kaum erkennbarer Struktur. Der Benutzer hat kein Umschalten auf Legacy, sondern die Qualität der neuen Basis gefordert.

**In GitHub Draft PR36 entwickelt, NICHT bereits auf Plesk ausgeführt:**
- `platform/resources/views/filament/pages/article-lookup.blade.php` im Commit **`d0a92e4bff6f5dd2445df0e0e456af7e3339dafd`** vollständig in explizit gekapseltes CSS mit Präfix `.hw24-editor` überführt, **statt** sich ausschließlich auf im Filament-Build offenbar nicht erfasste Tailwind-Utility-Klassen zu verlassen. Responsiv: große Displays 2-Spalten-Layouts für Stammdaten/Bestand und Amazon/KI, kleine Displays einspaltig; CSS-Klassen nur auf die eigene Seite begrenzt, keine globale Theme- oder andere Legacy-CSS-Änderung. Barriereärmere Feldlabels, Fokusmarkierungen, horizontales Tabellen-Scrolling.
- **Festgelegte Reihenfolge**: `01 Artikel finden` (Suche EAN/HAN/Artikelnummer, API-/Snapshotstatus, kompakte Ergebnistabelle) → `02 Artikeldaten` (eindeutiger Artikel, strukturierte Gruppen *Stammdaten und Identifikation*, *Bestand, Menge und Status*, *Preise und Steuergrundlagen*) → `03 JTL-Originalfelder und Kontopreise` (manuell geladene maximal 80 Quellfelder je Seite, Suchfilter und Kontopreis-Evidenz) → `04 Amazon Selling Partner API` und `05 OpenAI gespeichertes KI-Archiv` nebeneinander → Hinweis auf spätere Shopify/eBay/WMS-Module.
- Beibehaltung der vorhandenen Livewire-Aktionen `findArticle`, `readJtlSources`, `loadMoreJtlSources`, `readAmazon`, `readAiArchive` und Inputbindungen. **Keine** neuen API-Routen, keine DB-Schema- oder JTL-Wawi-Änderung, keine automatische kostenpflichtige OpenAI-/Amazon-Abfrage; Preis- und Kanalquellen weiterhin als ungeprüft markiert.
- `platform/tests/Feature/ArticleWorkspaceLayoutTest.php` ergänzt (Commit **`d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4`**) mit Markern für alle Bereiche und Layoutklassen, Blade-Kompilierung sowie Livewire-Prüfung `Http::assertNothingSent` beim initialen Seitenrender.
- **Gepinnter, nur auf TEST zulässiger UI-Zielcommit:** **`d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4`**, direkter Git-Fast-Forward vom installierten `b679642`, genau fünf erlaubte Dateiänderungen einschließlich CI-/Ops-Änderungen und eine neue View-/Featuretest-Kombination. Fremde getrackte und private Dateien bleiben unverändert.
- Separat vorbereitetes Operator-Skript `platform/ops/upgrade-test-article-layout-ffonly.sh` (Quellcode-Commit **`3e9caa99fa1b1f6fec7c33e4dd519eae9a5e4f8a`**, CI-Referenz `285b5523c5ccc8faa00f14607cc40f03be55845d`), noch **nicht** auf Server ausgeführt: prüft aktive HTTPS-TEST-Subdomain auf `platform/public`, privaten Git-HEAD/Branch/Clean-Tree, anonymen 401/403-Auth-Schutz, ACLs, private .env/SQLite-Sperren, explizite Diff-Allowlist; legt root-private Sicherung der bisherigen Blade-Maske und konsistente SQLite-Snapshot-Sicherung an; installiert nur gepinnten Zielcommit, kompiliert Blade und verifiziert Sicherheitsgates; bei Fehler unter sauberen Git-Bedingungen Rückkehr zum alten Code. Keine Plesk-Git-Bereitstellung, keine Domainumstellung, kein Composer, keine SQL-/JTL-Schreibaktion.
- **Noch offen:** GitHub-CI muss für den exakten gepinnten UI-Zielcommit und den Operator-Workflow grün sein; anschließend Betreiber-PuTTY-Update und **erst danach** erneuter Browser-Screenshot der wirklich installierten Filament-Artikelzentrale. Vorheriges LIVE `api/jtl-fields.php` 404 weiterhin getrennte Arbeit und nicht durch reine Layoutoptimierung gelöst. Rollback-/SQLite-Backups des vorherigen Cockpit-Upgrades nicht löschen.

**Journalprinzip:** Entwicklung, CI-Validierung, tatsächliches Serverupdate und Browserabnahme bleiben vier getrennte protokollierte Zustände; bei jedem neuen Schritt Zielversion, Risiko, Prüfergebnis und ggf. Rollback beifügen. Keine Secrets ins Repository oder Arbeitsjournal.


### Nachtrag 22.11.2026: CI-Abnahme der Artikelzentrale V2

- Laravel-13-/Filament-5-GitHub-Actions-Test für den finalen separaten Operator-Skriptstand `285b5523c5ccc8faa00f14607cc40f03be55845d`: **SUCCESS**, Workflow `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45067203055`. Bestehende Legacy-Regression ebenfalls **SUCCESS**, Run `45067203031`.
- Der in der Script-Allowlist fest gepinnte UI-Zielcommit **`d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4`** hat seinen eigenen Framework-CI-Lauf **SUCCESS**, Run `45067093658`. Die neue `ArticleWorkspaceLayoutTest`-Abdeckung wurde dort zusammen mit den vorhandenen Sicherheits-/Funktionstests durchgeführt. Die Tests ersetzen noch **keinen** realen Browser-Screenshot.
- GitHub-Compare `b679642...d5491f2`: **fast-forward, 6 Commits voraus, 0 zurück, 5 Pfade**: CI-Workflow, zwei Ops-Skripte, `article-lookup.blade.php`, neuer Layout-Featuretest. Das abgeschlossene Operator-Skript ist von `285b5523` aufrufbar, die damit ausgeführte Zielversion ist bewusst `d5491f2` statt des jeweils neueren Remote-HEAD.
- **Stand nach diesem Eintrag:** Der Betreiber hat dieses neue Artikelzentrum-Upgrade weiterhin **nicht** auf dem Plesk-TEST-Server ausgeführt. Server letzter nachgewiesener HEAD `b679642`. Keine Änderung an LIVE-/JTL-SQL-/Plesk-Webroot oder Shopify/Amazon/eBay-Anbindungen. Bei ausgeführtem Update: neue root-private Datenbank- und Blade-Sicherungen aus tatsächlicher PuTTY-Ausgabe separat protokollieren und Browser mit derselben Referenz-EAN wiederholen.


## 22.11.2026 · Funktionslücke Filament vs. Legacy: alte Werkzeuge und Scanliste werden wieder angebunden

**Auslöser:** Betreiber meldete anhand von HTTPS-TEST-Screenshots ausdrücklich, dass im neuen Laravel-/Filament-Artikelmanager wichtige ursprüngliche Module fehlen: vollständige Artikelpflege und Übersicht, manuelle KI-Neuabfrage (nicht bloß gespeichertes Archiv), Amazon-Katalogvergleich und Angebots-/Importvorbereitung, EAN-Scanliste und Stapel, CSV-Einlesen, Ameise-Sammel-/Einzel-Export und Qualitätszentrum. Der neue Frontend-Stand wurde zuvor UI-fokussiert ausgebaut, **nicht** funktionsgleich migriert.

### Belegter Altbestand im GitHub-Repository

Direkt geprüft: Legacy-Code in `deploy/staging` Stand `444453471b0fd27c73dcc7929f5e17f1d9239c52` (nicht einfach aus Erinnerung angenommen). Die originalen Module:
- `artikelpflege.html`: Identität/Stammdaten, Produkttexte, Lieferung/Versand, EK/VK, Formular für bestätigte revisionsgeprüfte **HW24-Entwürfe** über `api/curated.php`; Vergleich mit JTL-Referenz; manueller Amazon-Katalog und Keyword-Recherche; gespeichertes KI-Archiv **und** separat bestätigte, gegebenenfalls kostenpflichtige `api/ai-review.php`-Analyse; Einzelartikel-Ameise-Prüf-CSV.
- `scanliste.html` + `api/scanliste.php`: MariaDB-basierte Stapel (`hw24_scan_batches`), Positionen (`hw24_scan_items`), Scanereignisse und revisionsprüfbare Entwürfe. GET `op=batches` liefert maximal 50 **offene** Stapel des per Basic-Auth identifizierten Actors; GET `op=items&batch_id=...` liefert maximal 3.000 Positionen **nur nach Eigentümerprüfung**. POST-Aktionen `create`, `scan`, `adjust`, `review` etc. verlangen gültige HTTPS-Origin-/Approval-Header. Alte Oberfläche erlaubt Kamera/EAN, CSV-Import bis 5.000 Zeilen, Scan-Entwürfe, Statusfreigabe und CSV-Export bis 200 Positionen; keine direkte JTL-Wawi-/WMS-Lagerbuchung.
- `index.html`: Experten-/Scanner-Ansicht mit Amazon-Katalog-/Diagnose, JTL-CSV-Massenprüfung, Qualität und Ameise-`#ameisePanel`; `qualitaetszentrum.html` als eigenständige JTL-Qualitäts-/Artikelübersicht.
- `api/ai-reviews.php`: archivierte Berichte, GET-only und `actor`-gebunden; `api/ai-review.php`: kostenpflichtiger POST separat geschützt, Budget-, Archiv- und explizite Zustimmungsbedingungen. Ein Amazon-Katalogtreffer aus `api/amazon.php` ist **kein** nachgewiesenes eigenes Amazon-Angebot oder direkter Seller-Import.
- Die bisherige TEST-Subdomain ist im früheren Cutover **nur beim DocumentRoot** nach `platform/public` gewandert. Die Legacy-Dateien wurden dabei nicht gelöscht; LIVE `https://artikelmanager.hairworld24.de` blieb getrennt. **Daraus folgt nicht**, dass alle Legacy-LIVE-Seiten bereits erneut im Browser verifiziert wurden; echte Link-/Funktionsproben stehen noch aus.

### Erste Funktionswiederherstellung im isolierten GitHub-Branch

- Neuer Draft-PR **[#42](https://github.com/NicoVoss1/HW24-scanner/pull/42)**, Branch `feature/hw24-legacy-workflows-recovery-20261122`, **Basis** isolierter bestehender PR36-Branch `feature/hw24-platform-laravel13-filament5-20261122`, Ausgangs-Commit `285b5523c5ccc8faa00f14607cc40f03be55845d`. **Nicht in `deploy/staging` mergen und nicht Plesk-Git deployen**.
- `app/Filament/Pages/LegacyWorkspace.php` und `resources/views/filament/pages/legacy-workspace.blade.php`: neuer geschützter Admin-Menüpunkt `Alle Werkzeuge` (`/admin/werkzeuge`) mit großen Funktionskarten und ausdrücklich markierten externen LIVE-HTTPS-Links zur existierenden `artikelpflege.html` (inkl. HW24-Entwürfe und manuelle KI-/Amazon-Aktionen), `scanliste.html` (Erfassung, CSV, Sammelimport), `index.html` (Expertenansicht/Amazon/Ameise) und `qualitaetszentrum.html`. Links sind statisch, öffnen mit `target=_blank`/`rel=noopener noreferrer`, und führen selbst keinerlei API-Requests, KI-Kosten oder Schreibaktionen aus.
- `LegacyReadBridge.php`: nur zwei neue Services `scan.batches` und `scan.items` als **GET-only** zur festen HTTPS-LIVE-API `api/scanliste.php`; Basic-Zugang ausschließlich aus privater Laravel-`.env`, Redirects weiterhin verboten. Keine scan-/draft-/Amazon-/KI-POST-Freischaltung.
- `app/Filament/Pages/ScanWorkbench.php` und eigener `scan-workbench.blade.php`: geschützter Menüpunkt `Scanliste` (`/admin/scanliste`), manuelles Laden der vorhandenen, nach Actor gebundenen maximal 50 offenen Stapel und maximal 3.000 Scanpositionen eines explizit ausgewählten eigenen Stapels aus **der bestehenden MariaDB**. Nur Whitelist EAN, Artikelname, Artikelnummer, Count, Prüf-/JTL-Status; keine kompletten `draft_json`-/`source_json`-Payloads oder Änderungen. Direkter Einstieg in die ursprüngliche Scan-Oberfläche für sämtliche schreibenden Aktionen, CSV und Ameise. **Keine neue/leere SQLite-Scanliste angelegt, keine Datenkopie.**
- `AdminPanelProvider.php`: beide Seiten im Filament-Admin registriert. `article-lookup.blade.php`: unmittelbare Buttons zu existierender Artikelpflege/KI, Scanliste, Werkzeugübersicht und Amazon/Ameise auf Legacy-LIVE. Bestehender Test-Artikelread und Formular bleiben erhalten.
- `tests/Feature/LegacyWorkflowRecoveryTest.php`: prüft render ohne automatische HTTP-Aufrufe, manuelle genau eine GET pro Stapel-/Positionsaktion, nur bekannte auswählbare Stapel-IDs, keine verbotenen Schreibservice-Einträge in der Bridge, Hostpinnung, geschützte neue Filament-Routen.
- **CI-Befund der Programmversion `6c9be5c6258800feb65f20c036e6d7526c7fb803`:** Laravel13-/Filament5-GitHub Actions `45070009138` SUCCESS, Legacy-Regression `45070009108` SUCCESS.
- Zusätzlich vorbereitet `platform/ops/upgrade-test-legacy-workflows-ffonly.sh` (Operator-Quellcommit `9447814a31efc60e5812bcd7ba39e25e0d80c7e7`, CI-Erweiterung `677e7e71646ff3e8cb6e9a8630bb9058c4f49bcd`): **noch NICHT auf Plesk ausgeführt**. Strikte alte TEST-Framework-HEAD-Gates `d5491f28...` oder `285b5523...`, TEST-only DocumentRoot `platform/public`, BasicAuth 401/403, privater Git-/Dateirechtestatus, Freigabe von 10 Diff-Pfaden; vor Fast-Forward konsistentes root-privates SQLite-Backup und Rollback-Metadaten; installiert **ausschließlich den geprüften Codecommit `6c9be5c...`**, nicht den neueren PR-HEAD; PHP-Lint, Blade-Cache, Admin-Routen und private Rechte nachprüfen; bei Fehlprüfung Code-Rollback. Keine Laravel-Migration, kein Plesk-Domain-/Git-Deploy, kein MariaDB-/JTL-Write und keine automatischen Amazon-/OpenAI-Aufrufe.
- **Unabgeschlossene Arbeit:** Keine tatsächliche Server-Aktualisierung dieses Funktionsschritts durch Betreiber belegt, keine echte Browserprüfung der neuen Menüs oder der Scan-Stapel-GETs, keine Bestätigung, dass alle alten LIVE-Funktionslinks aktuell fehlerfrei funktionieren. Die echte Integration von POST-basiertem Scan/Entwurf, direktem KI-Kostenfreigabedialog und Amazon-Produktübernahme bleibt **separater Migrationsabschnitt** und darf nicht als erledigt dargestellt werden. Die ursprüngliche JTL-Originalfelder-API liefert nach letztem Prüfstand LIVE 404.
- **Richtung:** Erst vollständigen Werkzeugzugang und read-only Scan-Stapel nachweisen, danach Auth-/Owner-/CSRF-/Approval-/Audit-Konzepte für echte Filament-Schreibaktionen klären; die Original-MariaDB nie ohne Backup, Rechteabgleich und E2E-Test beschreiben. Das Arbeitsjournal unterscheidet weiterhin Quellcode, CI, Plesk-Ausführung und Browser-Erfolg.


### Nachtrag 22.11.2026 · PR42 CI grün, gepinnte Funktionswiederherstellung bereit

- GitHub Actions **Laravel13/Filament5 safe-foundation** für PR42-Stand `677e7e71646ff3e8cb6e9a8630bb9058c4f49bcd` **SUCCESS**: `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45070183562`. Legacy-V4.25-Regression für denselben Commit **SUCCESS**: `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45070183497`.
- Fast-forward-Diffs für den fest gepinnten UI-/Funktionscode `6c9be5c6258800feb65f20c036e6d7526c7fb803` geprüft: vom letzten layoutbezogenen Server-Pin `d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4` **10 Commits voraus, 0 hinterher, 10 geprüfte Dateipfade**; vom anschließenden PR36-Stand `285b5523c5ccc8faa00f14607cc40f03be55845d` **8 Commits voraus, 0 hinterher, 8 geprüfte Dateipfade**.
- Operator-Skript `platform/ops/upgrade-test-legacy-workflows-ffonly.sh` liegt im geprüften PR42-Quellcommit `677e7e71646ff3e8cb6e9a8630bb9058c4f49bcd`; das Skript installiert absichtlich **nur** den früher CI-geprüften Zielcommit `6c9be5c...`, mit root-privatem SQLite-Snapshot per `VACUUM INTO`, unveränderten `.env`/`composer.lock`, bestehendem TEST-DocumentRoot, 401/403-BasicAuth, 10-Pfad-Allowlist, CLI-Tests für `/admin/scanliste` und `/admin/werkzeuge`, Notfall-Code-Rollback. Es führt **kein** `artisan migrate`, kein Plesk-Git-Deploy, keine JTL-/Amazon-/KI-POSTs aus.
- **Bis zu einer echten PuTTY-Ausgabe** gilt dieser neue Code als **entwickelt und CI-geprüft, nicht auf dem Server nachgewiesen installiert**. Bis zur Installation bleiben die vorhandenen Funktionsaufrufe über die geschützte LIVE-Legacy-Seite verfügbar, deren einzelne Browserfunktion ebenfalls separat bestätigt werden muss.


## 22.11.2026 · Native KI-, Amazon-, Scan- und Ameise-Funktionen im separaten PR43 entwickelt

**Anlass:** Der Betreiber fordert nach der ersten Filament-/Legacy-Bridge ausdrücklich **kein weiteres Verlinken der Altanwendung als Endzustand**, sondern alle wesentlichen Arbeitsfunktionen **direkt innerhalb der neuen HW24 Intelligence Console**: kostenpflichtige KI-Abfrage mit Berichtarchiv, Amazon-Katalogrecherche und gespeicherte Auskünfte, Scan-Stapel und tatsächliche EAN-Erfassung, JTL-Ameise-Importvorbereitung und den vorhandenen JTL-Artikelüberblick. Voriger PR42 deckt bereits geschützte Lesefunktionen ab, aber noch nicht alle Workflows.

### Umsetzung im isolierten Entwicklungsbranch

- Branch `feature/hw24-native-ai-amazon-scan-20261122`, Ausgangscommit `677e7e71646ff3e8cb6e9a8630bb9058c4f49bcd` aus PR42, neuer **Draft-PR [#43](https://github.com/NicoVoss1/HW24-scanner/pull/43)** gegen diesen Branch. **Noch nicht auf Plesk installiert, keine Änderung an LIVE/JTL/Datenbank tatsächlich ausgeführt.**
- Neue geschützte Filament-Seite `/admin/intelligence`, Klasse `app/Filament/Pages/IntelligenceWorkbench.php` und eigener responsiver Blade-View: JTL-Eindeutigkeit über aktuellen Snapshot prüfen; **explizite** Amazon-Katalog-GETs (Cache oder echte SP-API), Archiv-Snapshot-ID/Fetch-Zeit und bis zwölf ASIN-/Produkt-/Markenhinweise anzeigen; separate Amazon-Titelsuche inklusive EAN-Identitätsindikator; auf Klick Preisabfrage je im Katalog nachgewiesener ASIN. Keine eigenen Verkäuferangebote ohne Kontennachweis behaupten.
- **KI-Archiv nativ:** `api/ai-reviews.php?ean=...&limit=10` nach Betreiberklick, historische Berichts-ID, Datum, Modell, Begründung, Hersteller-/Titel-/Warengruppenvorschlag, ausgewiesene USD-Kosten. Ausschließlich zum bereits bei HTTP-Basic identifizierten Actor gehörende Datensätze; keine automatische Anzeige privater unsanitierter vollständiger JSON-Payloads.
- **Neue OpenAI-KI-Abfrage als streng getrennte Aktion:** zunächst kostenloses `GET api/ai-review.php` für verfügbares Budget/Archiv, Kostenhinweis mit Benutzercheckbox und Livewire-`wire:confirm`, **serverseitig erforderlicher privater Freigabeschalter** `HW24_AI_PAID_ENABLED=true` (default false), frischer KI-Archiv-GET unmittelbar vor POST; vorhandener Bericht wird nicht aus Versehen neu berechnet, Neubewertung erfordert zusätzliche Checkbox und die nachgewiesene Vorgänger-ID für die Legacy-Recheck-Konfliktprüfung. POST wird über `LegacyApprovedActionBridge` mit festem HTTPS-Host, Basic-Auth aus `.env`, `Origin` und `X-HW24-Approval: confirmed` an den vorhandenen Legacy-Cost-Gate-Endpunkt geleitet. Keine automatische Retry-Schleife; nach unklarer Antwort Archiv/Kosten vor neuer Prüfung lesen.
- **Scanliste nativ erweitert:** `ScanWorkbench` verfügt zusätzlich zu GET über bewusst ausgelöste Stapelanlage `scan.create` und EAN-Erfassung `scan.scan` in den bestehenden HW24-MariaDB-Tabellen, mit bestätigter Benutzerauswahl, GTIN-Checkdigit-Validierung, genau einem POST je Klick, UUID-idempotency-event-key für Scans, erneutem Lesen der bestehenden Stapel/Positionen. Auch hier privater Schalter `HW24_SCAN_WRITES_ENABLED=true` **standardmäßig false** und ausdrückliche Checkbox sowie Benutzerbestätigungsdialog; keine JTL-Wawi-/WMS-Buchung. Bei Netz-/Schreibantwortproblemen **kein automatischer Wiederholungs-POST**, damit keine doppelten Scans entstehen.
- **JTL-Ameise-Prüfexport direkt in Filament:** vorhandener **GET `api/scanliste.php?op=export&batch_id=...`** führt vor jedem Download echte Legacy-SQL-Gates (nur bestätigte neue Artikel, maximal 200, aktueller JTL-Snapshot, GTIN/Pflichtfelder, keine identischen JTL-Artikel oder doppelten Artikelnummern) aus. Laravel erstellt daraus nur eine UTF-8-BOM-/Semikolon-getrennte CSV-Prüfdatei mit klarer Importfeld-Allowlist, Escaping von Anführungszeichen und Abbruch bei potentiellen Tabellenformeln. **Kein direkter Import in JTL-Wawi**. Anwender muss vorher Wawi sichern, die Spaltenzuordnung und einen Ameise-Trockenlauf prüfen.
- Bestehender `LegacyReadBridge` um reine GET-Services `scan.export` und `curated.article` ergänzt. Neue `LegacyApprovedActionBridge` besitzt **nur drei erlaubte POST-Dienste** `scan.create`, `scan.scan` und `ai.review`; keine API zum Schreiben von Preisen, Amazon-Angeboten, Lagerbestand oder JTL. Neue Filament-Adminseite und direkter Link aus der Artikelzentrale registriert. Standard-Config bleibt `writes_enabled=false`, `jtl_read_only=true`.
- Neue Featuretests `tests/Feature/NativeIntelligenceAndScanTest.php` prüfen kein HTTP bei Page-Render, gezielte manuelle JTL-/Amazon-/Archiv-GETs, vorhandene Berichtsdetails, default-off POST-Gates, separat freigegebene Operator-/Kostenzustimmungen, bestehende Auth-Routen sowie Scan-/Ameise-Preflight. Quellcode-Prüfstand bis Commit `94ad51ab861cf80abe5e4c50cae765dc7840c1c2`, CI im PR43 gestartet; **Erfolg erst nach tatsächlicher CI-Ausgabe behaupten.**

### Noch offen / Sicherheitsgrenzen

1. Tests und mögliche PHP-/Blade-/Livewire-Anpassungen im PR43 vollständig grün bekommen; weder Plesk-Deployment noch Live-AI-Kosten für Tests.
2. **Alte Amazon-Snapshot-Historie:** Der bestehende `api/amazon.php` liefert einen aktuellen gespeicherten Snapshot mit Archiv-ID/Datum sowie Katalogtreffern, aber noch keine eigenständige paginierte Liste aller historischen Snapshots. Dafür eine gesonderte serverseitige, nur lesende Legacy-Archiv-API mit Auth/Limit/Datensparsamkeit entwickeln und separat freigeben. Aktuelle Katalogcache-Antwort ist kein nachgewiesenes aktives Amazon-Seller-Angebot.
3. **Echte Artikelentwürfe und Amazon-Vorschlagsübernahme** in Filament sind noch kein vollständiger nativer Ersatz für `artikelpflege.html`/`api/curated.php`, ebenso fehlen freigegebene Bearbeitung von Scanentwürfen/CSV-Massenimport und direkte Wawi-Importausführung (letztere bewusst NICHT automatisieren). Bestehende Legacy-Seiten als Rückhalt erhalten.
4. **LIVE-Originalfelder-Endpunkt** `api/jtl-fields.php` nach letzter Prüfung weiterhin 404, sodass Datenvollständigkeit/Brutto-VK/Kanalpreise separat mit geprüfter Windows-SQL-Quelle zu lösen sind. Keine fiktiven Preise oder Steuerklassen.
5. **TEST-Server-Git-Stand** seit dem letzten dokumentierten Upgrade auf `b679642` bzw. nach layoutbezogener Browseransicht nicht erneut per PuTTY bewiesen. Neue PR42-/PR43-Änderungen keinesfalls ohne expliziten HEAD-/Branch-Abgleich/Backup/Rollback in eine laufende TEST-Instanz deployen. `.env`, SQLite, Live-Domain und vorhandene Scan-MariaDB unangetastet lassen.
6. **Arbeitsjournalpflicht:** Quellcodeentwicklung, CI-Tests, Betreiber-PuTTY-Ausführung und echte Browserabnahme sind getrennte Fakten und mit Belegen festzuhalten. Zugangsdaten und technische Secrets werden nicht dokumentiert.


## 22.11.2026 · Amazon-Kataloghistorie GET und gesicherter PR43-Testrollout vorbereitet

**Fortsetzung der nativen KI-/Amazon-/Scan-/Ameise-Umsetzung aus PR43:**

- **Neuer eigener, ausschließlich lesender PHP-Endpunkt** `api/amazon-archive.php` im PR43-Featurebranch, Code-Commit `884c7e30647b216c3b7184c000f6859636cb6735`. Nur GET, bestehende LIVE-HTTP-Basic-Authentifizierung und HTTPS erforderlich; EAN/GTIN-Checksumme wird geprüft, Ausgabe auf maximal 20 Katalog-Snapshots begrenzt. SELECT nur auf `hw24_amazon_catalog_snapshots` mit festem Amazon.de-Marketplace `A1PA6795UKMFR9`, kein Zugriff auf die vollständigen `response_json`-Blobs, keine SP-API-Aufrufe, keine INSERTs. Liefert ausschließlich gespeicherte Archiv-ID, Abrufzeit und Katalogtrefferzahl. Der vorhandene Snapshot-Tisch ist **nicht** auf einzelne Seller/Actors zugeschnitten, deshalb nur Katalog-Recherchenachweise, **keine** verifizierten eigenen Verkäuferangebote oder Kontopreise.
- `LegacyReadBridge` im privaten Laravel-Branch um `amazon.archive` (GET-only) ergänzt. `IntelligenceWorkbench` und Bladeansicht besitzen jetzt einen expliziten Button **Amazon-Archiv laden**. Gibt bei fehlendem separatem LIVE-Archiv-Deploy eine klare 404-/Nicht-verfügbar-Meldung aus; kein automatischer Amazon-Live-API-Fallback. Das vollständige historische Amazon-Archiv erfordert **einen getrennt abgenommenen Deployment-Schritt dieser zusätzlichen neuen Legacy-PHP-Datei** in den bestehenden LIVE-Webroot, der bisher **nicht** ausgeführt wurde. Der private Git-Fast-Forward selbst installiert die Datei **nicht** in den LIVE-Webroot.
- Neue Featuretests für historischen Amazon-Archiv-GET und sichere 404-Degradation sowie CI-PHP-Syntaxprüfung des neuen Endpunkts. **GitHub-CI bestätigt** für Quellcode-Zielcommit `aa13ff0c7b2416f547835d7badcf29a645c01b5b`: Laravel/Filament `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45072876718` **SUCCESS**, Legacy V4.25 Regression `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45072876714` **SUCCESS**. Kein LIVE-KI-/Amazon-Call beim CI.
- Zusätzlich isoliertes Operator-Skript `platform/ops/upgrade-native-ai-amazon-scan-ffonly.sh`, Quellcode-Commit `8b714f0f82c99580d39e41cc8c0b409c7cede2b8`, CI-Gate ergänzt durch Commit `396af8fd6b4b429097823a02758961489f04faca`. Bewusst installiert das Skript **nur** den zuvor grün getesteten **fest gepinnten** Zielcommit `aa13ff0...`, nie den neuesten PR-HEAD. Akzeptiert nur überprüfte frühere TEST-Klone `d5491f2...`, `285b552...` oder `6c9be5c...`, bestehende Plesk-Subdomain muss `platform/public` zeigen und anonyme HTTPS-BasicAuth 401/403 liefern, private `.env`/SQLite weiterhin vor `nginx`/`www-data` geschützt. Maximal 17 vorher abgeglichene erlaubte Quellcode-Pfade einschließlich neuer `api/amazon-archive.php` **nur innerhalb des PRIVATEN Git-Repos**, keine Veröffentlichung an LIVE. Vor FF konsistente root-private SQLite-Sicherung mit `VACUUM INTO`, dann UI-/PHP-/Blade-/Admin-Routen-/BasicAuth-/Privatsperrenprüfungen; falls nicht grün, unter sicheren Git-Bedingungen ausschließlich private getrackte Codeänderungen rücksetzen. Kein Composer, kein `artisan migrate`, kein Plesk-Deploy, keine JTL-/Scan-MariaDB-/Amazon-/OpenAI-Schreibaktion während des Upgrades.
- **Unbedingt unterscheiden:** GitHub-Entwicklung fertig, CI-Anwendungscode bis `aa13ff` grün, Script-CI nach neuer Workflow-Änderung noch anhand finaler Actions prüfen, Betreiber-PuTTY-Ausführung **noch nicht nachgewiesen**. Keine Behauptung, der neue Menüpunkt oder KI-/Scan-POST sei auf TEST bereits verfügbar. Neue private Freigabeschalter `HW24_AI_PAID_ENABLED` und `HW24_SCAN_WRITES_ENABLED` sind standardmäßig `false`. Die vollständige Browser- und Sicherheitsabnahme inklusive Scan-Eigentümerzuordnung und Legacy-LIVE-Linkfunktion steht noch aus.
- **Noch fehlende native Funktionen:** vollständige kuratierte Artikelentwurfsbearbeitung mit Audit, Scan-CSV-Massenaufnahme, Vorschlagsübernahme und Amazon-Seller-Angebotsimport; keine direkte Wawi-Datenbankbearbeitung ohne eigene gesicherte Quell-/Freigabephase. Nicht als abgeschlossen behaupten.


### Nachtrag 22.11.2026 · PR43 Release-Gates und Native-Funktionen grün

- **Laravel13/Filament5-Foundation-CI für gepinnten nativen Quellcode `aa13ff0c7b2416f547835d7badcf29a645c01b5b`: SUCCESS**, Run `45072876718`. Enthält `NativeIntelligenceAndScanTest.php` mit Browser-Render ohne HTTP, gezielter JTL-/Amazon-/AI-Archiv-/Amazon-Snapshot-Historie, kostenpflichtiger KI nur bei Budget/Bestätigung/operator gate, Scan-Anlage nur bei Privatschalter, Ameise-Exportpreflight, Schutz vor externen Write-Service-Zielen und Filament-Login. **Legacy-V4.25-Regression: SUCCESS**, Run `45072876714`.
- **Gepinnter Operator-Skript-CI für Version `396af8fd6b4b429097823a02758961489f04faca`: SUCCESS**, Run `45072990320` (Laravel/Filament) und `45072990322` (Legacy). Prüft Shell-Syntax, unveränderlichen pinned target `aa13ff...`, `merge --ff-only`, SQLite-Snapshot, Code-Rollback, keine Plesk-Domain-Änderung, keine Migration.
- Das neu hinzugefügte Live-Legacy-PHP `api/amazon-archive.php` wird zwar im privaten Framework-Git mit heruntergeladen, aber **nicht** im Live-Legacy-DocumentRoot bereitgestellt. Der Amazon-Archiv-Button darf bis zur separaten API-Freigabe eine 404 anzeigen, ohne eine neue SP-API-Abfrage auszulösen. KI-Archiv und manuelle Amazon-Katalogrecherche verwenden bestehende geschützte Endpunkte.
- **Freigabestatus nach diesem Journalstand:** PR43 steht weiter auf DRAFT, keine Plesk-/PuTTY-Ausführung dieses Rollouts behaupten, kein userseitiges Browser-E2E, keinerlei LIVE-/JTL-Wawi-/Scan-MariaDB-Schreibaktion. Private kostenpflichtige und Scan-Schreibaktionen bleiben per Default deaktiviert bis zur eigenen Betreiberfreigabe. Der nachgewiesene ältere TEST-HEAD ist nicht neu abgerufen; das Script verweigert bei einem unbekannten HEAD jede Änderung.


## 22.11.2026 · WMS, Kontopreise und Reparatur der fehlerhaften Filament-Release-Prüfung

**Neu vorgelegter Betreiber-PuTTY-Nachweis**, anonymisiert als Textupload `Eingefügter Text(6).txt`, zwei getrennte Versuche:
1. Skript `platform/ops/upgrade-test-legacy-workflows-ffonly.sh` (Pin `677e7e7`) wurde von privatem Framework-HEAD `d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4` aus gestartet, private SQLite vor Git-Update konsistent gesichert: `/root/hw24-backups/hw24-legacy-recovery-before-20261122T212158Z-4faL1CbE.sqlite`. Nach FF auf `6c9be5c` zeigten Laravel-Routen im CLI-Text ausdrücklich `admin/artikel`, **`admin/scanliste`**, **`admin/werkzeuge`**. Trotzdem erklärte ein **whitespace-sensitiver grep auf JSON** die reale Route als nicht vorhanden und löste automatischen Code-Rollback aus. Ergebnis `CODE-ROLLBACK OK`.
2. Skript `platform/ops/upgrade-native-ai-amazon-scan-ffonly.sh` (Pin `396af8fd`) erneut vom privaten HEAD `d5491f28...` gestartet, vorher SQLite-Backup `/root/hw24-backups/hw24-native-workflows-before-20261122T213747Z-fzbpbrMF.sqlite` mit Integritätsprüfung. FF nach `aa13ff0` prüfte Quellcode/Blade, stoppte dann fälschlich wegen angeblich fehlendem `admin/intelligence`. Ergebnis `GIT-ROLLBACK OK`. Damit ist **kein erfolgreicher produktiver oder TEST-Verbleib** der PR42-/PR43-Neufunktionen durch diesen Upload belegt; alter private TEST-Code blieb am Ende **`d5491f28...`**. Keine LIVE-Domainumstellung, keine JTL-/WMS-/Marktplatz-Schreiboperation durch diese beiden Skripte. **Die beiden SQLite-Backups nicht löschen!**
3. **Ursachenbehebung im GitHub-Featurebranch** `feature/hw24-wms-priceboard-and-release-gates-20261122`: alt beide Operator-Skripte von `grep -F '"uri":"admin/..."'` auf strukturierten JSON-Vergleich umgebaut. Anschließend gemeinsames PHP-CLI-Gate `platform/ops/verify-admin-routes-json.php` eingeführt; testet `json_decode` und exakte Route-URI unabhängig von Whitespace, bleibt bei tatsächlich fehlender Route strikt gesperrt. CI hat positive und negative JSON-Fixtures; kein Bypass von Auth, kein Auslassen der Routenprüfung.
4. **WMS-Integration umgesetzt**: bestehender geschützter `api/jtl-wms-history.php` lässt sich nach ausdrücklicher Benutzeraktion über `LegacyReadBridge` als `jtl.wms.history` **nur per GET** lesen, nach JTL-Artikel-ID und gepinntem `run_id`-Snapshot, maximal 50 historisch belegte Bewegungen pro Seite. Neue geschützte Filament-Seite `/admin/lager-preise` aus `app/Filament/Pages/WarehousePriceWorkbench.php` + `resources/views/filament/pages/warehouse-price-workbench.blade.php`: eindeutige Artikelidentität, JTL-Stammbestand, Buchungsart, Buchungsmenge, Von-/Nach-Lagerplatz, Buchungszeit, Snapshot, Pagination. **Diese Daten belegen weder aktuelle Lagerplatzmenge, frei verfügbare Menge noch Reservierungen.** Kein aktueller WMS-Binbestand wird aus Bewegungen geraten; der separate WMS-Positions-/Bestandsspiegel ist noch offen und hängt von einer nachgewiesenen Windows-SQL-Mappingquelle ab.
5. **Preisvergleich umgesetzt**: separat ausgewiesener JTL-EK netto, VK netto, nur mit Quelle bestätigter JTL-VK brutto, `amazon_vk` und `ebay_preis` aus dem JTL-Spiegel **ausschließlich als unverifizierte Referenzpreise, nicht als aktive eigene Händlerangebote**. Manueller Amazon-Katalog-GET und je ASIN separate öffentliche Buy-Box-/Lowest-Offer-Abfrage, inkl. Währungs-/Preisart-Nachweis, niemals mit eigenem Seller-Angebot verwechseln. Shopify und eigene Amazon-/eBay-Preise werden nur nach **echtem, aktiven, kontogebundenen, max. 24 h alten, ausdrücklich `verified`** Datensatz aus der vorhandenen isolierten Plattform-SQLite `channel_price_observations` angezeigt; Konto, Zeitpunkt, Quelle, Marketplace, Offer-ID sichtbar. Ohne gültigen Record `nicht verifiziert`, nie Fake-Preis, nie Standard-EUR oder Steuersatz erfinden. Keine API-/DB-/Marketplace-Schreibaktion.
6. Filament-Admin-Menü und Artikelzentrale verfügen über neuen Eintrag `WMS & Preise` bzw. Direktlink mit EAN, ohne die alte UI, die KI-/Scan-/Amazon-API oder das Legacy-Webroot umzuschalten. Neuer <Link> nicht erforderlich: GitHub-Draft-PR **[#44](https://github.com/NicoVoss1/HW24-scanner/pull/44)** (Basis PR43), **noch nicht auf Plesk aktiviert**.
7. Featuretests `platform/tests/Feature/WarehouseAndPriceBoardTest.php` prüfen keinen automatischen Upstream-Aufruf beim Rendern, GET-only WMS-Lagerbewegungen einschließlich negativer Bewegungs-IDs, `not_synchronized` ohne erfundenen Lagerplatzbestand, JTL-netto/brutto/currency-Grenzen, manuelle Amazon-Katalog- und ASIN-Preis-Abfragen, Session-Schutz sowie aktuelle kontogebundene aktive eigene Angebotspreise und Ausschluss nicht bestätigter Beobachtungen.
8. **Tatsächlicher CI-Verlauf**: Run `45073990873` schlug wegen einer **nur in der in-memory-Test-SQLite** fehlenden Preisbeobachtungstabelle fehl (46 Tests bestanden, ein neuer Fixture-Test fehlerhaft). Testfixture darauf korrigiert durch isoliertes `000001_create_channel_price_observations_table` im PHPUnit-Test, **keine** Migration der Betreiber-Datenbank. Danach für App-Code-/Regressionsteststand `0d8bb9baf85365d4c07a1c17450ec0674bac3989` **Laravel/Filament-CI SUCCESS** `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45074149592`, Legacy-CI SUCCESS `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45074149604`. Das sind Entwicklungs-/CI-Tests, nicht Plesk-/Browserbelege.
9. **Neues einziges Operator-Upgrade (vorbereitet, nicht ausgeführt):** `platform/ops/upgrade-wms-priceboard-ffonly.sh`, stabiler gepinnter **Anwendungscode-HEAD `0d8bb9baf85365d4c07a1c17450ec0674bac3989`**, akzeptiert nur explizit bekannte vorherige Framework-HEADs (`d5491f2`, `285b552`, `6c9be5c`, `aa13ff0`), bestehende private Git-Branches, saubere getrackte Änderungen, Origin aus `NicoVoss1/HW24-scanner`, tatsächlichen Plesk-TEST-DocumentRoot `platform/public`, PUBLIC-ACLs, private `.env`-/SQLite-Sperren, anonyme HTTPS-Basic-401/403. Git-Fetch und genaue geänderte Dateipfade mit Allowlist prüfen, vor FF konsistentes root-privates SQLite-Backup und Git-Rollback-Metadaten, dann nur privater Code-FF, PHP-Lint, Blade-Compile, semantischer JSON-Route-Gate incl. `admin/lager-preise`, Schutzprüfung nach Update. Bei Fehler automatischer Code-Rollback, keine Datenbankmigration, kein Composer, kein Plesk-Deployment, keine KI-/Amazon-/JTL-/WMS-Schreibaktion.
10. **Stand dieser Journalaktualisierung:** Neuer Release-Operator-Quellcommit (inkl. zusätzlicher CI-Guard) `52898b91cd791c6d50875be9ade63f53ecaa8275`; CI dieses **Operator-Skriptstands** noch gesondert prüfen. **Nicht behaupten, dass WMS oder Preisvergleich bereits in der echten TEST-Browseroberfläche laufen**, bevor erfolgreiche PuTTY-Ausgabe und Browserabnahme vorliegen. Bitte niemals das vorher fehlgeschlagene aus GitHub fest gepinnte alte Operator-Skript erneut ausführen; es enthält weiterhin den alten fehlerhaften Gate-Code. Native KI-/Scan-Schreibfunktionen bleiben durch private Konfiguration standardmäßig deaktiviert; Freigabe erst nach Test mit tatsächlichen Berechtigungen.


### Nachtrag 22.11.2026 · PR44 Operator-Release-Skript CI grün

- GitHub Actions für gepinnten PR44-Operator-Quellcommit `52898b91cd791c6d50875be9ade63f53ecaa8275` **SUCCESS**: `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45074314543` (Laravel13/Filament5, einschl. `bash -n` und pinned-Skript-Sicherheitsprüfungen, WMS-/Preis-Featuretests, Route-JSON-Regression). Legacy-Regression ebenfalls **SUCCESS**: `https://github.com/NicoVoss1/HW24-scanner/actions/runs/45074314421`.
- Damit freigegeben ist **nur der geprüfte Operator-Ablauf zum Test-Code-Deployment**, nicht produktives Schreiben oder ein LIVE-Plesk-Git-Deploy. Auszuführender Scriptpfad `platform/ops/upgrade-wms-priceboard-ffonly.sh` über Raw-GitHub-URL mit **Skriptcommit `52898b91cd791c6d50875be9ade63f53ecaa8275`**. Darin eingebauter **App-Code-Ziel-HEAD `0d8bb9baf85365d4c07a1c17450ec0674bac3989`** ist separat per Laravel/Filament-CI `45074149592` **SUCCESS** und Legacy-CI `45074149604` **SUCCESS**. **Diese zwei unterschiedlichen SHA-Pins nicht vertauschen.**
- Aus dem Betreiber-Upload sind nur die **vorherigen gescheiterten und zurückgerollten** Versuche belegt. Die neue PR44-Version wurde bis zu diesem Zeitpunkt **nicht** vom Betreiber in PuTTY ausgeführt. Eine neue echte HTTPS-Browserprüfung von `/admin/lager-preise`, `/admin/intelligence`, `/admin/scanliste` sowie der WMS-GET- und Kontopreisquelle bleibt offen.


## 22.11.2026 · WMS-/Preisvergleich-/KI-/Scan-Modul auf TEST-SERVER erfolgreich aktualisiert

**Quelle:** echte, vom Betreiber übermittelte PuTTY-Ausgabe des Operatorskripts `platform/ops/upgrade-wms-priceboard-ffonly.sh`. Nicht nur CI oder Screenshot.

1. Betreiber hat das Skript aus **fest gepinntem GitHub-Quellcommit** `52898b91cd791c6d50875be9ade63f53ecaa8275` mit `bash -n` und anschließender Shell-Ausführung auf dem Plesk-Server gestartet. Startzustand: privater Framework-HEAD **`d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4`**.
2. Sicherheitskontrollen erfolgreich: TEST-HTTPS, privater `.env`-/SQLite-Dateischutz, PUBLIC-ACLs, bestehende HTTP-Basic-Absicherung und Git; aus erwarteten separaten GitHub-Branch `feature/hw24-wms-priceboard-and-release-gates-20261122` nur abgegrenzte Quellcodeobjekte geladen. Diff-Allowlist mit **22 Dateien** bestätigt, keine fremden oder unerwarteten Dateipfade.
3. **Vor Aktualisierung** ein konsistentes, integrationsgeprüftes root-privates SQLite-Backup angelegt: `/root/hw24-backups/hw24-native-workflows-before-20261122T220602Z-HW0ZhxsN.sqlite`. Rückfall-Metadaten: `/root/hw24-backups/hw24-native-workflows-git-20261122T220602Z-mVm4ts5Q.txt`. Beide aufbewahren und nicht automatisiert löschen.
4. **Fast-forward auf PRIVATEN Framework-Code erfolgreich:** `Updating d5491f2..0d8bb9b`, exakt **22 Dateien**, **3.040 Ergänzungen** und eine Entfernung. Neuer serverseitig bestätigter privater Git-HEAD **`0d8bb9baf85365d4c07a1c17450ec0674bac3989`**. Darin native Filament-Seiten `/admin/intelligence`, `/admin/scanliste`, `/admin/werkzeuge` und `/admin/lager-preise` inkl. Filament-Provider, GET-/separat gated POST-Bridges, responsive Blade-Views, geschützte API-Adapter, Quelle-/Preis- und WMS-Auswertung.
5. **Fehlerhafte Route-JSON-Prüfung aus beiden vorigen gescheiterten Upgrades jetzt tatsächlich behoben:** PuTTY-Nachkontrolle `OK: Required Filament routes parsed from JSON`. Danach Erfolgsmarker `=== HW24 NATIVE WMS- UND PREISFUNKTIONSMODULE AUF HTTPS-TEST AKTUALISIERT ===`. **Kein automatischer Code-Rollback** in diesem Lauf. Ursache der früheren Rollbacks war der whitespace-sensitive Grep auf `artisan route:list --json`; jetzt erfolgt semantische JSON-URI-Prüfung.
6. Der ausgeführte Operator meldet **keine** Plesk-/LIVE-/Legacy-/JTL-/WMS-/MariaDB-Schreibaktion, keine Amazon-SP-API-Recherche und keine kostenpflichtige OpenAI-Anfrage **während des Updates**. Die LIVE-Artikelpflege und der TEST-DocumentRoot wurden nicht umgestellt. Die private Plattform-Konfiguration für `HW24_SCAN_WRITES_ENABLED` und `HW24_AI_PAID_ENABLED` bleibt standardmäßig **deaktiviert**, solange keine explizite Betreiberfreigabe nach E2E-Proben erfolgt.
7. **Nur der TEST-CODE ist jetzt installiert und die post-deploy SSH-Gates sind erfolgreich.** Noch **kein** vom Betreiber belegter echter Filament-Browser-Test mit angemeldeter Session; auch **kein** nachgewiesener GET-Datenerfolg bei WMS-Snapshot/Scanliste/KI/Amazon oder Shop-Kanalpreisen. Diese auf HTML-/Livewire-Ebene, Owner-Grenzen und nach EAN `3474630337398` / JTL-ID `1428` ausdrücklich separat verifizieren. Die neue Amazon-Archiv-PHP-Datei ist lediglich im privaten Git-Repo vorhanden, **nicht** automatisch unter dem LIVE-Legacy-Webroot als `api/amazon-archive.php` deployed, deshalb kann das neue Archiv unter LIVE noch HTTP 404 liefern.
8. **Nächste Browserabnahme:** `https://test-artikelmanager.hairworld24.de/admin/lager-preise` mit Referenz-EAN, JTL-EK/VK netto und nur quellverifiziertem brutto VK, separate JTL-Amazon-/eBay-Hinweise, WMS-Historien-GET ggf. `not_synchronized` statt erfundenem Lagerplatzbestand, getrennte Amazon-Katalog-/Preisabfragen per Klick; `/admin/intelligence` mit KI-Archiv, Kostenbudget und manuellem Amazon; `/admin/scanliste` mit vorhandenen User-Stapeln, GET-Positionen und Ameise-Prüfexport. Login, HTTP-Basic, keine automatischen API-Aufrufe und vorhandene Legacy-LIVE-Anwendung geschützt lassen.
9. **Offene echte Datenanbindungen:** WMS-Bewegungshistorie ≠ aktueller WMS-Lagerplatzbestand/frei/Reservierungen; eigene Shopify/eBay/Amazon-Verkäufer-Angebotspreise nur, wenn zeitlich aktuelle, kontogebundene `verified`-Beobachtungen wirklich vorhanden sind. Die API-/Windows-Mirror-Quelldaten bei fehlender Anzeige prüfen; keine Preise oder Bestände ableiten. Live-Archiv-PHP-Deploy und JTL-Originalfelder-/Brutto-VK-Vollständigkeit bleiben separat zu prüfen.
