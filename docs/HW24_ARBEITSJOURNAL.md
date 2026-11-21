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
