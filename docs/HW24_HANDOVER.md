## JETZT BESTÄTIGT · Plesk-Schema mit `protected_dirs.path` (22.11.2026)

**PuTTY-Read-only-Beleg:** `protected_dirs` enthält die Felder `id, non_ssl, ssl, realm, path, dom_id, dom_type, cgi_bin`; **kein** Feld `name`. `pd_users` hat `id,login,account_id,pd_id`, `domains` enthält `id,name,parentDomainId`. Die zuvor fehlgeschlagene Passwortschutz-Abfrage verwendete `pd.name` und muss zwingend durch `pd.path` ersetzt werden. **Keine Plesk-Einstellung geändert.**

**GENAU NÄCHSTE PUtty-AKTION:** Den korrigierten **nur lesenden** Block `HW24_PLESK_AUTH_READ` aus [Loginwechsel-Runbook](./HW24_LOGIN_IDENTITAETSWECHSEL_20261122.md) ausführen. Er prüft auf bestehender Live- und Testdomain die Plesk-geschützten Verzeichnisse/HTTP-Basic-Logins über `domains.id=protected_dirs.dom_id` und `pd_users.pd_id=protected_dirs.id`, mit `pd.path` statt `pd.name`. **Bei leerem Resultat:** Authquelle separat read-only prüfen, nicht blind neue Benutzer erstellen. Keine Passwort-Hashes, kein SQL-Write.

**Bereits unverändert nachgewiesene Daten:** KI-Berichte **3** unter bisherigem Login, **0** unter künftigem; Scanlisten-Stapel **1+1** unter bisherigem bzw. künftigem Login. [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37), HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482`, CI #44978357305/#44978360796 grün, unterstützt ausdrücklich beide **Lesebesitzer**, behält neue Writes/Audit beim aktuellen Login. **Kein Merge, kein Deployment, keine Aliasdatei, kein Account-Rename**, historischer Bestand unangetastet. Vor Plesk-Änderung Authquelle/Backup/Rollback absichern; Filament-Cutover separat.

[Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Schema-Nachweis-Commit `56c278793b4665bf22d4c57a6ed13e03fcb158f7`; [Runbook](./HW24_LOGIN_IDENTITAETSWECHSEL_20261122.md) korrigiert unter Commit `299b50bb342d6e08dac7189a02df5d52261a1e3a`.

---

## Aktuelle Ergänzung zum geplanten Basic-Loginwechsel · 22.11.2026

**Betreiber hat bestätigt, dass keine separate Passwortrücksetzung für den beabsichtigten Benutzernamenwechsel benötigt wird.** Aus Sicherheitsgründen werden Einzelheiten vorhandener Anmeldegeheimnisse **nicht** in diesem öffentlichen Repository protokolliert oder angezeigt. Ein Passwort ist kein Eigentümerschlüssel: Gespeicherte KI-Berichte bleiben an `actor`, Scanlisten-Stapel an `created_by` gebunden.

**Nachgewiesen:** 3 bestehende KI-Reviews unter der bisherigen HTTP-Basic-Kennung; 2 Scanlisten-Stapel, davon 1 unter der bisherigen, 1 bereits unter der zukünftigen Kennung. **Draft [PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37)** implementiert eine opt-in, serverseitig private Mehr-Owner-Lesezuordnung und erhält die Urheberschaft neuer Vorgänge. Beide aktuellen GitHub-CI-Läufe `44978357305` und `44978360796` grün; **kein** Plesk-Deploy/Benutzerwechsel ausgeführt.

**Unverändert nächster Schritt:** Read-only Plesk-Schema/Authentifizierungsquelle mit `HW24_PLESK_SCHEMA` aus [Login-Rolloutanleitung](./HW24_LOGIN_IDENTITAETSWECHSEL_20261122.md) klären, danach Backup/Rollback und getrennte Plesk-Loginprüfung. Kein Passwort in PuTTY-Ausgabe/Chat/GitHub teilen. Im Rahmen der Sicherheitsabnahme altes, nicht mehr benötigtes Login später deaktivieren und neue Anmeldegeheimnisse eigenständig halten.

---

## NEUESTER ARBEITSSTAND · 22.11.2026 · HTTP-Basic-Loginwechsel betrifft Scanliste UND KI-Archiv

**Korrigierter Betreiberwunsch:** Die bestehende HTTP-Basic-Benutzerkennung soll für den Artikelmanager und die Scanliste auf einen neuen gewünschten Namen umgestellt werden. Im vorherigen Chat wurde der alte Loginname mit einer Bereichsbeschriftung verwechselt; **älteres Plesk-Rename-Skript nicht ausführen**. Die beiden konkreten Benutzernamen bleiben im aktuellen Betreiberdialog und werden nicht im öffentlichen GitHub-Kontext gespeichert.

**Echter Code-Nachweis:** `scanliste.html` sendet `credentials:'same-origin'` und `api/scanliste.php` nutzt den Basic-Login als `hw24_scan_batches.created_by`. `api/ai-reviews.php` und `api/ai-review.php` verwenden denselben Basic-Login als `hw24_ai_reviews.actor`. Ein einfacher Plesk-Namenswechsel ohne Kompatibilitätsregel kann bestehende Scanlisten-Stapel und **alle 3 nachweislich gespeicherten KI-Berichte** verstecken. Die genau gespeicherte historische Actor-Kennung ist aber noch **NICHT** mit altem oder neuem Login abgeglichen.

**Jetzt technisch vorbereitet (NOCH NICHT auf Plesk installiert):** [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37), Branch `feature/hw24-login-alias-scan-ai-20261122`, GitHub-CI [#44977971992](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44977971992) **SUCCESS**. Neues `api/hw24-actor-identity.php` erlaubt über eine **nur serverseitige private Datei** einen neuen Basic-Login auf eine stabile historische Owner-Kennung abzubilden. Die drei APIs für Scanliste, KI-Archiv und KI-Prüfung übernehmen diese Logik unter Beibehaltung der Auth-Prüfungen, Actor-Filter und ohne SQL-Rewrites. Private Zuordnung **noch nicht angelegt**. Keine Domain-/DB-/Benutzeränderung.

**GENAU NÄCHSTER BETREIBER-SCHRITT:** Den [Read-only Login- und Datenbestandscheck](./HW24_LOGIN_IDENTITAETSWECHSEL_20261122.md) via PuTTY ausführen und die Plesk-Passwortschutz-Locations für Live und Test sowie Counts für `hw24_ai_reviews.actor` und `hw24_scan_batches.created_by` unter ALT/NEU ermitteln. Nur dann richtigen Alias wählen (oder weglassen, wenn historische Daten bereits unter neuer Kennung sind), Plesk-Zugang mit Rückfallsicherung umbenennen und Scanliste + 3 KI-Berichte im Browser prüfen. Das ist **noch nicht geschehen**. Daten bleiben unverändert.

[Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Commit `434adae20343fa6d0a38fe8e02d42c35f9cb8fe6`.

---

## AKTUELLSTER BENUTZERWUNSCH · Passwortschutz-Login für HW24 Test umbenennen (22.11.2026)

**Betreiber hat eine konkrete neue Benutzerkennung für „HW-24 Test“ vorgegeben.** Aus Datenschutz-/Sicherheitsgründen wird der Login-String **nicht** im öffentlichen Repository notiert, sondern nur im aktuellen Betreiberdialog verwendet. **Die Änderung ist NOCH NICHT AUF PLESK AUSGEFÜHRT.** Kein Plugin-Zugriff auf SSH/Plesk vorhanden; die eigentliche Anmeldekonto-Umbenennung muss nach Auth-Quellenprüfung durch den Betreiber auf Plesk erfolgen. Laut [Plesk-Obsidian-Dokumentation](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/) kann eine vorhandene Plesk-passwortgeschützte Verzeichniskennung mit `plesk bin protdir -u <VERZEICHNIS> -domain <DOMAIN> -update_user <ALT> -user_name <NEU>` geändert werden, aber **nur**, wenn Plesk den tatsächlich betroffenen Basic-Auth-Zugang verwaltet und Verzeichnis / bisherige Benutzerkennung genau geprüft wurden. Nicht blind auf der Live-Domain ausführen.

**WICHTIG:** Drei historische KI-Review-Zeilen in MariaDB gehören bereits nachgewiesen zu einer *anderen* Actor-Kennung als der beim bisherigen Live-Login eingetippten. Das Ändern des Test-Logins benennt `hw24_ai_reviews.actor` **nicht** um; **keine** irreversible Massenaktualisierung der Historie! Das Betreiber-Gesamtarchiv kann stattdessen mit ausdrücklich privater, serverseitiger Berechtigung angeboten werden.

**Getrennte Codearbeit noch NICHT fertig / nicht ausgerollt:** Isolierter Branch `feature/hw24-ai-archive-admin-scope-20261122` hat erste zwei Codeänderungen an Legacy-Archiv-API und UI erhalten, noch ohne Tests, ohne PR und ohne Deployment. Nicht mit Accountumbenennung verwechseln.

[Einzelprotokoll der Account-Umstellungsentscheidung](./HW24_ARBEITSJOURNAL.md), Dokumentationscommit `ce0ed402f51db308c88ac15e3f48e7212f498412`.

---

## VERIFIZIERTE ARCHIV-DATEN · 22.11.2026 · ALLE 3 BERICHTE EXISTIEREN

**PuTTY-MariaDB-`SELECT` erfolgreich:** `hw24_ai_reviews` enthält **3** Berichte unter **1** Benutzerzuordnung für **3** verschiedene EANs. **Review ID 1 / EAN 8027483835211 vorhanden: JA**. Jüngster Review UTC `2026-11-22 01:31:34.913848`. **Kein gelöschtes/leeres Datenbankarchiv!** Live-Browser `/index.html` zeigt dennoch `0`.

**Quellcode-Status:** `api/ai-reviews.php` fragt mit `WHERE actor=?` ausschließlich aktuelle HTTP-Basic-Kennung ab. Die einzige bestehende Datenbank-Actor-Kennung ist noch nicht mit dem Live-Browser-Login verglichen. Das ist der wichtigste verbleibende sichere Diagnoseschritt, nicht mehr die Prüfung, ob überhaupt Berichte existieren. Das getrennte OpenAI-Kostenledger zählt 4 Aufrufe; diese sind nicht notwendigerweise 4 archivierte Berichte.

**GENAU NÄCHSTER PuTTY-SCHRITT:** [`HW24_ACTOR_MATCH` im KI-Archiv-Read-only-Runbook](./HW24_AI_ARCHIVE_AUDIT_20261122.md) (**noch nicht ausgeführt**). Benutzer gibt nur seinen echten **Live-Browser-HTTP-Basic-Anmeldenamen** verdeckt in PuTTY ein, **kein Passwort**. Das Skript führt parameterisierten `SELECT COUNT(*) WHERE actor=?` aus und gibt nur Gesamtzahl / passende / fremde Anzahl aus; keine Namen oder Secrets. Danach zielgerichtete Lösung: Betreiber-Admin-Gesamtarchiv mit **serverseitiger Autorisierung**, bestehender Normalnutzer-`actor`-Scope bleibt unangetastet. Keine KI-POSTs, keine Mutation der 3 archivierten Daten, kein vorzeitiger Plesk-Testdomain-Cutover.

[Archiv-Audit](./HW24_AI_ARCHIVE_AUDIT_20261122.md), Runbook-Commit `79561ed5e3ffc3e4a1e855189819adeba58ab6d3`; [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Datenprüf-Commit `0186b16f11b05be120cc988aae2fdc12ac26d4be`.

---

## AKTUELLSTE KI-ARCHIV-PRÜFUNG · 22.11.2026 · historischer Review #1 nachgewiesen, Live zeigt 0

**Historischer Primärnachweis in der Projektakte:** Am 21.11.2026 lag eine authentifizierte `GET /api/ai-reviews.php`-Antwort mit `ok=true`, `stored=true`, `count=1`, Review-ID **1** und EAN **8027483835211** (Selective Professional ONcare Leave-in Daily Hydrating Spray, 275 ml) vor. Dieser Bericht war damals gespeichert und lesbar. **Aktueller Live-Browser** zeigt am 22.11. in `/index.html` nur `0 gespeicherte KI-Analysen`, bei **4 kostenmäßig gezählten KI-Prüfungen** (0,0053 USD Schätzung).

**Ursachenanalyse des tatsächlichen GitHub-`deploy/staging`-Quellcodes:** `api/ai-reviews.php` liest ausschließlich Actor-gebundene MariaDB-Berichte aus `hw24_ai_reviews`, `WHERE actor=?` anhand der HTTP-Basic-Identität. `api/ai-review.php` zählt Modellkosten **separat** in `hw24-ai-usage.json` und schreibt den SQL-Review erst nach der Kostenbuchung. `assets/hw24-ai-history.js` zeigt bei `reviews:[]` zu allgemein `Noch keine dauerhaften KI-Ergebnisse`, obwohl dies zunächst nur „keine Berichte für den aktuellen Actor/Filter“ bedeutet. Die Domain-/Login-Identitätsabweichung und DB-Zustand sind **noch nicht belegt**.

**EINZIG NÄCHSTE AUSFÜHRBARE AKTION:** [Read-only MariaDB-Audit](./HW24_AI_ARCHIVE_AUDIT_20261122.md) in PuTTY ausführen, das nur Gesamtzeilen, unterschiedliche Actor, EAN-Zahl, anonymisierte Counts und Existenz des historischen Berichts #1 ausgibt. **Noch nicht ausgeführt**. Erst danach DB-/Login-Scope-Ursache bestimmen und eine sichere **explizit autorisierte Admin-Gesamtarchivansicht** entwickeln, ohne `WHERE actor=?` global zu entfernen und ohne kostenpflichtige KI-Abfrage. Kein Legacy-Webroot-/Plesk-Testdomain-Cutover bis zur Freigabe. Der angeschlossene Browser war in dieser Sitzung für direkte Tool-Abfrage nicht verfügbar.

[Einzelbefunde](./HW24_ARBEITSJOURNAL.md), [Archivdiagnose](./HW24_AI_ARCHIVE_AUDIT_20261122.md) (letzter Diagnose-Commit `1499a6ea8705b9987e95d7f0c5946ece8710cb68`).

---

## NEUSTER LIVE-KI-ARCHIV-BEFUND · 0 Berichte bei 4 gezählten Prüfungen (22.11.2026)

**Neuer Betreiber-Screenshot der Live-Expertenansicht** `artikelmanager.hairworld24.de/index.html`: Der Dialog `KI-Auswertungen · HW24-Prüfarchiv` öffnet sich, meldet jedoch `0 gespeicherte KI-Analysen` und `Noch keine dauerhaften KI-Ergebnisse`. Die gesonderte Kostenanzeige zeigt **4 Prüfungen im Monat**, geschätzt **0,0053 USD**, letzte geschätzte **0,0014 USD**, **1/25 heute**. Diese Anzeige ist **keine OpenAI-Rechnung** und keine Garantie, dass ein Bericht dauerhaft gespeichert wurde.

**Quellcode erklärt den möglichen Unterschied, ohne Ursache zu beweisen:** Im GitHub-Branch `deploy/staging` speichert `api/ai-review.php` die Nutzungszähler in privatem `hw24-ai-usage.json`; `api/ai-reviews.php` liest tatsächliche Berichte aus der getrennten MariaDB-Tabelle `hw24_ai_reviews` und filtert nach authentifiziertem `actor` (`WHERE actor=?`), optional nach EAN. Zähler und gespeicherte Berichte können daher abweichen, etwa bei anderem Login-Actor, EAN-Filter oder fehlgeschlagener Persistierung nach Modellkosten. **Keine Ursache und kein Datenverlust bislang nachgewiesen.**

**Genau nächste Browseraktion:** Im geöffneten Live-Archiv `Alle letzten Prüfungen` und `Aktualisieren` wählen, danach nur das Ergebnis mitteilen. **Keine neue kostenpflichtige KI-Prüfung starten.** Falls 0 bleibt, gezielt ausschließlich read-only API-Status und ggf. aggregierte DB-Zeilenanzahl klären, keine Review-Inhalte/Passwörter in Chat oder öffentliches GitHub. Legacy-Live-JTL wurde bereits browserseitig bestätigt; Amazon zeigt Cache-Kandidaten ohne EAN-Identitätsnachweis. **Testdomain noch nicht umstellen**; Plesk-Git-/DocumentRoot-Rollback muss zuvor stehen.

[Quellcode- und Screenshot-Befund in chronologischem Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Commit `61c28b34dd8c29329b5bc947900c45d256ca4e3d`.

---

## AKTUELLSTER LIVE-BROWSERBEFUND · Legacy V4.25 JTL erfolgreich, Amazon nur Cache, KI noch offen (22.11.2026)

**Bestätigt durch zwei Betreiber-Screenshots auf `artikelmanager.hairworld24.de`:**
- Live-Artikelpflege öffnet **EAN 3474630337398**, zeigt grünen `Aktueller JTL-Snapshot und HW24-Entwurf geöffnet`-Hinweis sowie die Artikeldaten `Matrix Socolor Beauty - 6C - Dunkelblond kupfer - (90 ml)`, Marke Matrix, JTL-Spiegelbestand **0**, **EK netto** laut JTL **1,50 €**, HW24-Entwurf Rev. 0. **Keine Echtzeit-WMS-Bestandsaussage und kein Brutto-Verkaufspreisnachweis.**
- Amazon-EAN-Katalog: **0 Treffer aus bestehendem Cache** (Screenshot sagt ausdrücklich `kein neuer SP-API-Abruf`, Stand 21.11.2026 23:45:51). Amazon-Titelsuche: **10 Kandidaten aus Cache**, EAN-Identität jeweils nicht bestätigt. **Nicht** als gematchte ASINs oder aktuelle Händlerpreise behandeln.
- OpenAI-KI-Berichte ist auf Screenshot **eingeklappt**; noch kein GET-/Archiv-Ergebnis nachgewiesen. Kein kostenpflichtiger KI-POST gestartet.
- Die **Live-Domain** ist in der Kopfzeile irreführend als `INTELLIGENCE · TEST` beschriftet. Später als reine UI-Korrektur berücksichtigen, nicht versehentlich den Legacy-Rollout auf den falschen Branch umstellen.
- **Legacy-Live-Browsergate ist TEILWEISE erfolgreich**: JTL-Leseanzeige bestätigt; Amazon-Cache-Komponente sichtbar; frische Amazon-API-/OpenAI-Archiv-Funktion noch offen. Die bisherigen Live-Git-Dateihashes und die erfolgreiche öffentliche Asset-ACL-Freigabe sind bereits bestätigt.

**Genau nächste Benutzeraktion:** Den Live-Bereich `OpenAI Intelligence · KI-Berichte` öffnen und einen Screenshot des **bestehenden kostenfreien Archivstatus** senden; falls kein archivierter Bericht existiert, ist die klare `kein Bericht`-Meldung ebenfalls ein verwertbarer Befund. Einen erneuten Amazon-SP-API-Abruf nicht aus einer Titelsuche oder einem Cache-Treffer ableiten; nur bei Bedarf manuell testen und tatsächliche Cache/Live-Quellenmeldung unterscheiden. **Noch kein Plesk-Testdomain-Webrootwechsel.** Vor Cutover bleibt die Plesk-Git-/DocumentRoot-/Auth-/Rollback-Abnahme erforderlich.

[Vollständiger chronologischer Browserbefund](./HW24_ARBEITSJOURNAL.md), Commit `1b3d618d72082a90c4eb844131c2a9411e360f4e`.

---

## AKTUELLSTER NACHWEIS · Öffentliche Asset-ACLs erfolgreich, private Daten gesperrt (22.11.2026)

**Betreiber hat den vorbereiteten `HW24_PUBLIC_ACL`-Block erfolgreich ausgeführt.** Root-private `getfacl`-Sicherung wurde vor dem Ändern der Rechte angelegt (UTC 02:22:45, privates Root-Backupverzeichnis; genaue Datei nur im Betreiberterminal). Auf Elternverzeichnissen `hw24-console-private`, `repo`, `platform`, `public` wurden für `nginx`/`www-data` ausschließlich Durchquerungsrechte (`--x`) und auf `public/{css,js,fonts}` ausschließlich Lese-/Traverserechte (`rX`) eingerichtet.

**Ausführliche Betreiber-Sicherheitsprüfung:**
- `nginx: ASSETS LESBAR, PRIVATE DATEIEN GESPERRT`
- `www-data: ASSETS LESBAR, PRIVATE DATEIEN GESPERRT`
- Geprüft: Filament CSS und JS **lesbar**, `platform/.env`, SQLite und `public/index.php`-Quelltext **nicht lesbar** durch diese HTTP-Worker. FPM führt Laravel als Besitzer `nicovoss` aus.
- `=== PUBLIC-ACL ERFOLGREICH ===`. **Legacy-Webroots, Plesk, PHP-FPM, Git-Bereitstellung und JTL/MariaDB wurden nicht umgeschaltet.**
- Keine Default-ACL eingerichtet; **nach jeder späteren Asset-Publikation erneut Dateilesbarkeit prüfen**. Root-private ACL-Sicherung nicht löschen; Rücknahme per `setfacl --restore` nur mit dem exakt vom Betreiber protokollierten Pfad.

**Damit ist die bisher nachgewiesene ACL-Blockade behoben.** Nicht verwechselt werden darf das mit einem erfolgreichen Filament-Web-Browserstart: Laravel ist weiter nur privat installiert und noch nicht dem Testdomain-Webroot zugeordnet. **Vor Plesk-Cutover verpflichtend:** (1) authentifizierter Legacy-Live-V4.25-Browser-E2E mit JTL-Referenz-EAN, manuellem Amazon-Katalog-GET und archiviertem OpenAI-GET (kein KI-POST), (2) reversibler Plesk-Testdomain-Webroot-/altes Git-Deployment-/SSL-/Basic-Auth-/PHP-FPM-Rolloutplan. Keinen ungesicherten Wechsel durchführen.

[Chronologischer Einzelbeleg](./HW24_ARBEITSJOURNAL.md), Dokumentations-Commit `888ebe9f549a7a500dde0502edb6722e25da4a1a`.

---

## NEUESTE BESTÄTIGUNG · ACL-Paket installiert (22.11.2026)

**Betreiber-Ausgabe:** Ubuntu-22.04-`acl 2.3.1-1` erfolgreich installiert, `/usr/bin/getfacl` und `/usr/bin/setfacl` vorhanden, privates Laravel-Dateisystem ist `ext4` (rw). Das Betriebssystem meldet zusätzlich **ausstehenden Kernel-/Diensteneustart** (laufend 5.15.0-202, verfügbar 5.15.0-204); das ist ein getrennter Wartungsbedarf. **Kein Reboot, keine Dienstrestarte und kein `apt autoremove`** während des Laravel-Rollouts.

**Aktuelle Sicherheitslage:** Test-FPM läuft als `nicovoss:psacln`, kann private Laravel-`.env`/SQLite 0600 lesen. Nginx/Apache (`nginx`, `www-data`) dürfen statische Filament-CSS/JS/Fonts noch **nicht** lesen; Zugriff auf `public/index.php` als Quelltext soll gesperrt bleiben.

**GENAU NÄCHSTER BETREIBERSCHRITT:** Ein einmaliges ACL-Skript **mit root-privater `getfacl`-Sicherung, automatischem `setfacl --restore` im Fehlerfall, schmalen Traverse-Rechten für Elternverzeichnisse und Leserechten nur für öffentliche `public/{css,js,fonts}`-Assets** ausführen. Der vollständige überprüfbare Block steht unter [HW24_PUBLIC_ACL im Preflight-Runbook](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md), GitHub-Commit `f8dc3fb875fcc0c6534713f47bb65fcf98e8a607`. **Noch nicht ausgeführt.** Überprüfung schließt gezielt `.env`, SQLite und `index.php`-Quelltext für nginx/www-data aus.

**Vor Plesk-Testdomain-Webroot-Umstellung weiterhin offen:** Angemeldeter Legacy-Live-Browser-E2E JTL/Amazon/KI; Rückfallplan für alte Plesk-Test-Git-Bereitstellung, HTTPS und HTTP-Filament-Login. Keine neue Subdomain/DB.

[Alle bestätigten Serveroperationen im Journal](./HW24_ARBEITSJOURNAL.md), ACL-Installations-Commit `c98c4dae803f62f5f87f63270d7f3ac88c857676`.

---

**GENAU NÄCHSTER BESTÄTIGTER ARBEITSAUFTRAG (22.11.2026):** PHP-8.5-FPM-Pool des Test-vHosts läuft als `nicovoss:psacln`, Socket für `psaserv`; nginx/www-data benötigen nur Zugriff auf statische öffentliche Assets. **Noch keine Rechte geändert.** Die frei verfügbaren Ubuntu-`acl`-Werkzeuge (`getfacl`, `setfacl`) werden nun bei Bedarf via separat dokumentiertem [`HW24_ACL_TOOLS`-Block](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) installiert; dieser Schritt ist noch **nicht** ausgeführt. Danach kontrollierte private ACL-Sicherung und gezielte öffentliche Asset-ACLs mit Rollback, ohne `.env`-/SQLite-Freigabe. Live-Legacy-E2E vor Domain-Cutover offen. [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md).

## AKTUELLSTE SERVERPRÜFUNG · PHP-FPM-Testdomain gehört nicovoss (22.11.2026)

**PuTTY bestätigt und im Arbeitsjournal protokolliert:** PHP-8.5-FPM-Pool der bestehenden Testdomain läuft unter `user=nicovoss`, `group=psacln`; Socket `listen.owner=root`, `listen.group=psaserv`, `listen.mode=0660`. `nginx` und `www-data` sind in `psaserv`, nicht `psacln`. Daraus folgt: FPM kann die privaten Laravel-Dateien unter Eigentümerrechten öffnen, aber die statischen `public/css`, `public/js` und `public/fonts` sind Nginx/Apache im Moment nicht lesbar (vorher explizit getestet). Die private `.env` und SQLite bleiben korrekt `0600` für `nicovoss`.

**Änderungsstrategie:** Verzeichnisdurchquerung für nginx/www-data selektiv erlauben und nur statische öffentliche Assets lesbar machen, idealerweise mit POSIX-ACL. Keinesfalls gesamtes privates `platform`/Repository per `chmod -R` öffnen. Prüfe als Nächstes nur Paketverfügbarkeit von `acl`, Dateisystem und vorhandene `public/{css,js,fonts}`-Verzeichnisse, **ohne** Plesk/Webserver-Einstellungen zu verändern. Für die tatsächliche Rechteänderung separaten Rollback-/Backuppunkt und nachfolgende Zugriffsprobe vorsehen.

**Andere verbleibende Freigabe:** Live-V4.25-Artikelmanager wurde bisher auf Git-/Dateiebene, **noch nicht angemeldet mit JTL/Amazon/OpenAI** abgenommen. Vor der Testdomain-Umstellung Pflicht. Die bestehende Plesk-Git-Test-Bereitstellung ist weiter `deploy/staging` manuell im alten Test-Webroot.

[Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) mit Prüfschritten 80–88, Commit `ac9cbd70d710a301d19bd8f7001f65c266471fbf`.

---

## AKTUELLSTE BESTÄTIGTE PRÜFUNG · nginx/www-data können Laravel-Assets nicht lesen (22.11.2026)

**Server-PuTTY nachgewiesen:** Die Webserver-Nutzer `nginx`, `www-data` und sichtbare PHP-FPM-Poolprozesse `www-data` wurden ermittelt. `public/index.php`, Filament `app.css` und `app.js` sind für `nicovoss` **LESBAR**, für `nginx` und `www-data` **GESPERRT**. Vorherige `namei`-Prüfungen zeigten auf dem privaten Laravel-Pfad Verzeichnisse mit `0700` und Dateien mit `0600`. `getfacl`/`setfacl` wurden nicht im PATH gefunden. **Kein Laravel-Laufzeitfehler:** PHP 8.5.13, Composer-Requirements, Laravel 13.43.0 und 4 Filament-Routen waren zuvor OK.

**Sicherheitsentscheidung:** **Keine pauschale Rechteöffnung** (`chmod -R 755`, `777`, allgemeine Gruppenleserechte auf `platform` usw.). `platform/.env` und SQLite müssen 0600 privat bleiben. Plesk-Deployment/DocumentRoot der Testdomain und Legacy-Live weiterhin unverändert; kein Browser-Login bestätigt.

**GENAU NÄCHSTER SCHRITT:** Read-only Benutzer-/Gruppenzuordnung und den konkreten Testdomain-PHP-8.5-FPM-Pool prüfen, ohne private Inhalte auszulesen. [Prüfblock `HW24_POOL_READ` im Runbook](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md). Erst auf Grundlage tatsächlicher Workergruppen und FPM-Poolidentität werden zielgenaue öffentliche CSS/JS/FONT-Berechtigungen sowie der alleinige Laravel-`public`-DocumentRoot geplant. **Vor Umschaltung:** Alte Live-V4.25-App angemeldet für JTL, Amazon und KI-Archiv prüfen.

[Einzelbelege](./HW24_ARBEITSJOURNAL.md), Journal-Commit `caf206b3ceef57a2c5bbb0d633dcf00bca4ea4d3`; aktuelles Runbook `867e34d5f860ce637e7a91bc846413767731b605`.

---

## AKTUELLSTER PREFLIGHT-STATUS · 22.11.2026

**PHP 8.5 ist für den installierten Laravel-/Filament-Stand auf CLI-Paketebene erfolgreich bestätigt:** `PHP 8.5.13`, Composer `check-platform-reqs --no-dev` ausschließlich `success`, Laravel `13.43.0` und alle vier Filament-Adminrouten unter PHP 8.5. Kein versionsbedingter Handlerwechsel nötig.

**Noch ungelöste Bereitstellungsvoraussetzung:** Private Laravel-Projekt- und `public`-/Asset-Verzeichnisse sind `0700`; `public/index.php` und Filament-CSS sind `0600`. Die tatsächlichen Webserver-/FPM-Leseidentitäten und ggf. ACLs sind noch nicht geprüft. Der Webserver könnte CSS/JS/Fonts nicht ausliefern. **Private .env und SQLite bleiben auf 0600. Niemals pauschales chmod -R auf privates Laravel.**

**GENAU NÄCHSTER SCHRITT:** Read-only Nginx-/Apache-Worker-/FPM-Identität und `test -r` für öffentliche Assets prüfen. Ausführbarer Befehlsblock in [HW24 PHP 8.5 und Web-Access Preflight](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md), letzter Runbook-Commit `f4b3dc47d82e5d431a67e211f9d67a8e28b41ea7`. Die alte Live-V4.25-Anwendung muss vor Testdomain-Umschaltung zusätzlich mit gültiger Anmeldung für JTL/Amazon/KI bestätigt werden. **Kein Domain-/Git-/Datenbankwechsel ausgeführt.**

[Einzelprotokoll](./HW24_ARBEITSJOURNAL.md), Commit `1d0a3e55b1a5c8e59ced2e665687ce11984520f6`.

---

**NEUESTER RUNBOOK:** [PHP-8.5-Plesk-Testdomain-Preflight (read-only)](./HW24_PREFLIGHT_FILAMENT_TESTDOMAIN_20261122.md) ist im GitHub-`main` dokumentiert (Commit `dd8410488564defcfae8a8a74769700aec2c9711`). **Dies ist der genau nächste PuTTY-Schritt; noch nicht ausgeführt.** Die Legacy-Live-App muss vor dem Cutover zusätzlich angemeldet mit JTL/Amazon/KI getestet werden. Kein voreiliger Plesk-Handlerwechsel, da Laravel 13 PHP 8.5 grundsätzlich unterstützt.

## NEUESTER TECHNIKSTAND · 22.11.2026 · Plesk-Test-vHost vor Domain-Cutover geprüft

**Betreiberbestätigung via PuTTY:**
- Vorhandene Testdomain zeigt weiterhin auf den bisherigen Legacy-Webroot (nicht auf Laravel).
- Test-Webhandler ist `plesk-php85-fpm` (PHP 8.5), während Laravel/Filament bisher mit Plesk-CLI-PHP 8.4 geprüft und Composer installiert wurde. Die offiziellen Laravel-13-Releaseangaben nennen PHP **8.3 bis 8.5** als kompatibel; dennoch müssen tatsächlich installierte Filament-/Composer-Pakete und FPM-Erweiterungen mit dem Webhandler geprüft werden. Kein ungeprüfter PHP-Wechsel.
- Plesk-Git-Ziel der bestehenden Testsite ist weiterhin der Legacy-Test-Webroot, `deploy/staging`, **manuelle** Bereitstellung. Der neue Laravel-Code liegt in einem separaten privaten Verzeichnis und darf nicht versehentlich zum Ziel des Legacy-Git-Deployments werden.
- Private Laravel-`.env` und SQLite sind Eigentum des Site-Nutzers mit Rechten **0600**. Auch `platform/public/index.php` hat **0600**; die Lesbarkeit und Verzeichnis-Traversierung für PHP-FPM und Nginx/Apache sind **noch offen**, insbesondere für CSS/JS/Fonts.
- **Keine Plesk-Konfigurations- oder Datenbankänderung erfolgte durch diesen Prüfblock.**

**Bereits bestätigt:** Laravel 13.43.0, Filament 5.12.0, private .env/APP_KEY/SQLite und vier Migrationsläufe, Assets, erster Admin sowie positive `canAccessPanel`-Prüfung, Legacy-Live V4.25 Branch/Commit und vier wichtige Webroot-Dateihashes.

**Gates vor Testdomain-Umstellung:** 1. PHP-8.5-Laufzeit mit lokal gelockten Paketen nur lesend testen und private `public`-Pfadrechte prüfen; 2. bisherige Legacy-Live-Anwendung **angemeldet** mit JTL/Amazon/KI testen; 3. Plesk-DocumentRoot-/Legacy-Git-Abgrenzung und Backup-/Rollback dokumentieren; erst dann `platform/public` als ausschließliche Webroot. Benutzer/Passwort/APP_KEY nicht ins öffentliche GitHub.

[Einzelschritte 59–65 im Arbeitsjournal](./HW24_ARBEITSJOURNAL.md); ursprünglicher Dokumentations-Commit `c8040254e7c1c2d8b812c611ce6683a19ffac513`.

---


## NEUESTE BESTÄTIGUNG · Filament Admin-Berechtigung geprüft (22.11.2026)

**Erfolgreiche Tinker/CLI-Read-only-Prüfung auf dem privaten Plesk-Laravel-Projekt:**
- `Administrator vorhanden: OK` durch Suche nach privater `HW24_ADMIN_EMAIL` in der isolierten SQLite-`users`-Tabelle.
- `Filament-Zugriff freigegeben: OK` durch tatsächlichen Aufruf von `canAccessPanel(Filament::getPanel("admin"))`.
- `=== PRÜFUNG ABGESCHLOSSEN ===` und Rückkehr zur SSH-Shell. **Admin ist also erstellt und serverseitig berechtigt.**
- Persönliche E-Mail, Passwort, APP_KEY und sämtliche HTTP-Basic/API-Secrets werden **nicht** im öffentlichen GitHub-Protokoll veröffentlicht.

**Nicht verifiziert:** Login im Browser und die Erreichbarkeit unter `test-artikelmanager.hairworld24.de/admin`, da bestehender Test-vHost noch Legacy. Ebenfalls noch ausstehend: angemeldeter E2E-Funktionstest der Live-V4.25-Artikelpflege (JTL/Amazon/KI), Plesk Test-vHost DocumentRoot/PHP-FPM/Git-Deploy-Verhalten, Laravel-GET-Bridge zu Legacy-Live.

**EXAKT NÄCHSTER SCHRITT:** Authentifizierten Legacy-Live-Funktionstest durchführen und die Plesk-Testdomain-Konfiguration rein lesend prüfen. Erst bei erfolgreichem Test den Rollout der neuen Oberfläche mit separat dokumentiertem Rollback planen. **Keine neue Admin-/DB-Initialisierung und keine Test-Webroot-Umstellung ohne Gate.**

[Chronologisches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Dokumentations-Commit `88cdc61e5e52d184e30a841c970b93b7ef8723a8`.

---

## NEUESTE BESTÄTIGUNG · Erster Filament-Administrator erfolgreich angelegt (22.11.2026)

Der Betreiber hat die interaktive Filament-Administratoranlage in der privaten Laravel-/SQLite-Umgebung erfolgreich ausgeführt. `HW24_ADMIN_EMAIL` ist **privat** in der Laravel-`.env` gesetzt, `artisan config:clear` erfolgreich, `artisan make:filament-user` meldete **Success!** und die künftige URL `https://test-artikelmanager.hairworld24.de/admin/login`. Nutzername/E-Mail/Passwort werden im öffentlichen GitHub **nicht** wiedergegeben. Abschluss `=== ADMINISTRATOR EINRICHTUNG ABGESCHLOSSEN ===`.

**Tatsächlich abgeschlossen:** Git/Composer, Laravel 13.43.0, Filament 5.12.0, App-Key/.env, private SQLite und 4 Migrationen, Filament-Assets, CLI-Routen und erster Admin mit Allowlist. **V4.25 Live:** Git SHA und vier Webroot-Dateihashes bestätigt, sechs vorherige anonyme HTTP-401-Prüfungen auf Live/Test. **Noch OFFEN:** Browser-Login zur Laravel-Konsole, da Plesk-Testdomain weiterhin Legacy; authentifizierter Legacy-Live-E2E inkl. JTL/Amazon/OpenAI; Plesk-Test-DocumentRoot und Zugriffsschutzumstellung; Basic-Auth-Verbindung der Laravel-Bridge; reproducible `composer.lock` im Featurebranch.

**EXAKT NÄCHSTE AKTION:** Erst lokalen Filament-User/Allowlist anonymisiert ohne Ausgabe von E-Mail/Passwort prüfen. Dann bestehende **Live**-Legacy-Artikelpflege authentifiziert im Browser mit Referenz-EAN sowie Amazon-/KI-Lesezugriff abnehmen. Vor Webroot-Cutover die Plesk-Git-/PHP-FPM-/DocumentRoot-Mechanik absichern. **Noch nicht** Testdomain auf Laravel umstellen und keinen API-Schlüssel in GitHub oder Chat kopieren.

[Chronologische Einzelereignisse](./HW24_ARBEITSJOURNAL.md), Dokumentationscommit `95246e6a8a9e2cb499454c4bdad4bcd6c3eb4307`.

---

## AKTUELLSTE BETREIBERABNAHME · Private Laravel-Erstkonfiguration erfolgreich (22.11.2026)

**Neuester Stand (gilt vor älteren historischen Hinweisen dieser Datei):** Der Betreiber hat auf Plesk im privaten Laravel-/Filament-Verzeichnis die Ersteinrichtung **vollständig und erfolgreich** ausgeführt. Private `.env` mit `APP_ENV=production`, `APP_DEBUG=false`, HTTPS-App-URL und Secure-Session erstellt (Dateimodus 0600). `artisan key:generate` bestätigte den neuen geheimen APP_KEY, **nicht im Repo**. Neue eigene private **SQLite-Datenbank** angelegt, ausschließlich vier Laravel-System-/Preistabellen migriert; alle vier im Status `[1] Ran`. `artisan filament:assets` erfolgreich. Ausgabe endete mit `=== HW24 ERSTKONFIGURATION ERFOLGREICH ===`. **Bestehende Testdomain, Live-Domain und JTL/MariaDB-Tabellen wurden dabei nicht umgeschaltet/verändert.**

- **Abgeschlossen:** Git-Klon, Laravel 13.43.0, Filament 5.12.0, Composer, CLI-Routen (/admin, /admin/artikel, /admin/login), private .env/APP_KEY/SQLite, vier Migrationen, publizierte Filament-Assets. Live-Legacy-V4.25 vier kritische Datei-Hashes OK.
- **Offen:** 1. Laravel-Filament-Admin-E-Mail nur lokal in `HW24_ADMIN_EMAIL` festlegen und lokalen Admin via `artisan make:filament-user` interaktiv anlegen. 2. Authentifizierten Legacy-Live-API-/Browser-Funktionstest JTL/Amazon/OpenAI abschließen. 3. GET-only Laravel-Bridge-Zugang zu Live-Legacy konfigurieren, ohne Geheimnisse zu teilen. 4. Testdomain nach Zugriffsschutz-/PHP-FPM-Prüfung ausschließlich auf `platform/public` umstellen und HTTP-Admin-Login verifizieren.
- **Nicht:** nochmals klonen, Composer erneut installieren, bestehenden JTL-/MariaDB-Spiegel migrieren, OAuth/API-Schlüssel nach GitHub kopieren, dritte Subdomain oder weitere verwaltete MariaDB anlegen.
- **Exakt nächste Betreiberaktion:** Interaktiver Filament-Admin mit privater Allowlist; alle Zugangsdaten bleiben im PuTTY-Terminal. Nie den Laravel-APP_KEY oder den .env-Inhalt ausgeben.

[Einzelnachweis im Journal](./HW24_ARBEITSJOURNAL.md), Dokumentations-Commit `f0f0cb0cc19af24fab670552e4eac5e420218c1b`.

---

## AKTUELLSTER BELEG · Laravel und Live-Stand betriebsseitig bestätigt (22.11.2026)

- **Laravel CLI**: PHP 8.4 führt Laravel **13.43.0** fehlerfrei aus.
- **Filament Adminpanel im CLI-Router**: Genau vier Routen: GET/HEAD /admin (Dashboard), GET/HEAD /admin/artikel, GET/HEAD /admin/login, POST /admin/logout.
- **Live-Legacy-V4.25-Dateihashes**: Alle vier direkt geprüften Dateien index.html, artikelpflege.html, api/jtl.php, scanliste.html stimmen mit dem Staging-SHA überein. Live-Branch war bereits deploy/staging mit Commit 444453471b0fd27c73dcc7929f5e17f1d9239c52.
- **Private Laravel-Datendateien**: .env und database/database.sqlite sind **noch NICHT** angelegt. Die fertig installierten Composer-Pakete bleiben erhalten, erneute Installation unnötig.
- Ein zweites Mal eingefügter identischer PuTTY-Shellblock hatte **keine** zweite Abschlussausgabe; nicht als erneute Verifikation zählen.
- **Noch ausstehend**: Legacy-Live-Funktionstest mit gültiger Anmeldung, Laravel-.env, APP_KEY, eigene SQLite-Laravel-Migration, Filament-Adminnutzer, Plesk-Webroot-Umschaltung, echte Laravel-HTTPS-Abnahme.
- **GENAU NÄCHSTER SCHRITT:** Nur im bestehenden privaten Laravel-Projekt als Site-Benutzer .env sicher aus .env.example erzeugen; production/HTTPS/Debug off/secure session; APP_KEY erzeugen; SQLite-Datei (nicht in Git einchecken) und ausschließlich SQLite-Laravel-Tabellen migrieren; Assets lokal publizieren. **Weder bestehenden Test-Webroot noch JTL/MariaDB ändern.**
- Nach jedem Schritt [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) nachtragen. Dokumentations-Commit des aktuellen CLI-/Hash-Belegs: 3b7ee3a26e2a524fe54f136f38453d9d190239c7.

---

# HW24 · VERBINDLICHE ÜBERGABE FÜR NEUE CHATS

**Stand:** 22.11.2026 nach erfolgreicher Composer-/Laravel-/Filament-Installation. **Der neueste bestätigte Status steht direkt hier und am Ende der Datei; ältere Zwischenstände im Verlauf sind historisch.** **Diese Datei zuerst lesen**, danach [aktuellen Projektstand](./HW24_PROJEKTSTAND_AKTUELL.md), [chronologisches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) und [Zwei-Domain-Rollout](./HW24_TWO_DOMAIN_ROLLOUT_20261122.md). Die Dokumentation ist im öffentlichen GitHub-`main`; keine Secrets ergänzen.

## Aktueller, nachgewiesener Zustand

| Baustein | Status / Beleg |
| --- | --- |
| Legacy-Testdomain `test-artikelmanager.hairworld24.de` | V4.25 mit exaktem GitHub-SHA `444453471b0fd27c73dcc7929f5e17f1d9239c52` am 22.11.2026 manuell aus Plesk deployed. Kritische Dateihashes `artikelpflege.html` und `api/jtl.php` nach Deployment OK. Authentifizierter Browser-/API-E2E noch nicht vollständig belegt. |
| Legacy-Livedomain `artikelmanager.hairworld24.de` | Betreiber hat am 22.11.2026 per Plesk `Active branch: deploy/staging` und letzten Plesk-Repo-Commit exakt `444453471b0fd27c73dcc7929f5e17f1d9239c52` nachgewiesen. **Webroot-Hash und authentifizierter Browser-/API-E2E auf Live noch nicht nachgewiesen.** Nicht automatisch „abgenommen“ melden. |
| Zugriffsschutz | Ohne Credentials je Live und Test `/`, `/api/jtl.php` und `/api/ai-reviews.php` **HTTP 401**, sechs Statuschecks bestanden (vor/um Live-Cutover). Kein umfassender Penetrationstest. |
| Test-Backup | Privates V4.24-Test-Webroot-Archiv vor V4.25-Deploy angelegt und als lesbar geprüft. Weitere früher berichtete Backups vorhanden; Restoreprobe nicht belegt. |
| Framework-Pilot | `feature/hw24-platform-laravel13-filament5-20261122`, Draft [PR #36](https://github.com/NicoVoss1/HW24-scanner/pull/36), zuletzt explizit erfolgreicher Console-CI-Commit `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`: Laravel 13 + Filament 5, Login/Allowlist, Dashboard, GET-only JTL-Artikelsuche, manuelle Amazon-GET-/OpenAI-Archiv-GET-Abfrage. CI [#44972633923](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44972633923) erfolgreich. **Plesk-Installation unvollständig.** |
| Private Serverinstallation | Git-Klon im privaten App-Verzeichnis mit verifiziertem HEAD `980bcce...`; Composer/PHP 8.4 als Site-Benutzer **erfolgreich ausgeführt**, Laravel 13.43.0 und Filament 5.12.0 sowie 112 Produktiv-Pakete installiert, `vendor/autoload.php` erfolgreich geprüft. **Noch keine Laravel-Adminseite in Plesk veröffentlicht.** |
| Frühere Installationsfehler (behoben) | Git-Owner-Mismatch als root und PHP-Aufruf des Composer-Shell-Wrappers. **Beides korrigiert:** Git als Site-Benutzer, Composer-PHAR explizit über PHP 8.4. Einzelfehler im Arbeitsjournal historisch festgehalten. |
| Plesk-PHP | Plesk PHP 8.4-FPM aktiv; CLI **explizit** `/opt/plesk/php/8.4/bin/php` benutzen (Standard-PHP wäre 8.1). Composer 2.11.2, Git vorhanden. Node/npm nicht im PATH; PHP-Erweiterungen MySQL, SQLite, Intl, Zip OK. |
| Datenhaltung | Vorhandene JTL-MariaDB-Daten und JTL-Wawi MSSQL ausschließlich lesend; private Auth/API-Konfigurationen nicht in GitHub. Keine neue Subdomain, keine zusätzliche verwaltete MariaDB. Laravel-eigene private SQLite später zulässig, nur im isolierten `platform/` und nie mit JTL-Schema verwechseln. |
| Legacy-API-Brücke | Im Featurebranch ausschließlich `https://artikelmanager.hairworld24.de` mit Basic Auth per privater Laravel-`.env`. Zunächst nur GET, kein automatischer Amazon-/OpenAI-Kostenaufruf, keine JTL/WMS/Marktplatz-Schreiboperation. |

## Genau nächster Schritt

1. **Nicht erneut klonen und Composer nicht erneut installieren.** Git-HEAD, Laravel 13.43.0, Filament 5.12.0 und Vendor-Autoload wurden bereits bestätigt.
2. **Nur lesender Runtime-/Releasecheck:** `/opt/plesk/php/8.4/bin/php artisan --version`, `artisan route:list --path=admin` als Site-Benutzer sowie exakte V4.25-Dateihashes im Live-Webroot. Ergebnisse als nächster Journal-Eintrag aufnehmen.
3. Für reproduzierbares Deployment die serverseitig erzeugte `platform/composer.lock` auf Secret-Freiheit prüfen und später kontrolliert auf den Featurebranch übertragen. Keine lokale SQLite-Datenbank öffentlich committen.
4. Nur im privaten Laravel-Projekt `.env`, APP_KEY und eigene SQLite für Login/Metadaten anlegen, dann Filament-Assets und einen namentlich autorisierten Admin einrichten. Keine JTL-DB-Migration.
5. Erst wenn Live V4.25 auch **angemeldet** erfolgreich funktioniert, Plesk-Testdomain auf `platform/public` umschalten; dann `/admin` in Browser und Legacy-Lesebrücke prüfen.

## PROTOKOLLREGEL: Jeder einzelne Schritt in GitHub

**Verbindlich für jede weitere Sitzung, einschließlich neuer Chats:**
- **Zuerst** diese Übergabe, Projektstand und Arbeitsjournal lesen. Keine bereits bestätigten Diagnosen wiederholen.
- Nach **jeder** Nutzer-PuTTY-Rückmeldung, jedem erfolgreich oder erfolglos abgeschlossenen Deploy, jedem Code-Commit/PR, jedem Test und jeder Architekturentscheidung **unmittelbar** im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) einen neuen chronologischen Eintrag erzeugen. **Auch Fehler, Teilabbrüche und Rückfallmaßnahmen protokollieren.**
- Jeder Eintrag nennt **Zeit/Datum (wenn vorhanden), konkreten Schritt, beobachtetes Ergebnis, Beleg/SHA/URL, Status (bestätigt/verifiziert/offen/fehlgeschlagen), Sicherheitsauswirkung, exakt nächsten Schritt**. Nach jedem Meilenstein auch diese Übergabe und Projektstand fortschreiben; die letzten echten Fakten gelten gegenüber älteren Planabschnitten.
- Für GitHub-Aktionen **den bestehenden GitHub-Connector nutzen** und Änderungen direkt committen; niemals nur im Chat behaupten, etwas sei dokumentiert, wenn kein Commit bestätigt ist. Dem Nutzer stets Dateilink und Dokumentations-Commit nennen.
- **Das Repository ist öffentlich:** niemals Passwörter, API-Keys, Sitzungstoken, private Host-IP-/SSH-Schlüssel, private DB-Konfigurationsinhalte, Dumps, Zugangsdaten oder vertrauliche konkrete Serverpfade/Benutzerdetails veröffentlichen. Technische Benutzerbezeichner nur soweit für Reproduzierbarkeit unerlässlich.
- **Trenne** GitHub-Code-Status, GitHub-CI-Test, Plesk-Git-Commit, tatsächliches Plesk-Deployment, Browser-E2E und Live-API-Verbindung. Keinen Status aus einem anderen erschließen.
- Die App kann GitHub nicht selbsttätig auf dem Server bedienen; diese **Dokumentationsdisziplin wird pro aktiver Arbeitsrunde durchgeführt**, nicht als unbeaufsichtigtes Hintergrundversprechen.

**Keine neue Subdomain. Kein Merge des Framework-Draft-PRs in `deploy/staging`. Kein ungeprüfter JTL-/WMS-/Shopify-Live-Write.**


## LETZTES EREIGNIS · Composer-Wrapper-Fehlversuch am 22.11.2026

**Neu bestätigt:** Der Betreiber hat den **bestehenden** privaten Git-Klon erfolgreich als Site-Benutzer geprüft: HEAD exakt `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`. Keine neue Clone-Aktion nötig, der frühere `dubious ownership`-Fehler ist damit gelöst.

**Neuer Fehlversuch, protokolliert:** Der anschließende Befehl `/opt/plesk/php/8.4/bin/php "$(command -v composer)" ...` startete **nicht** Composer, sondern druckte den Inhalt des **Plesk-Shell-Wrappers**. Die tatsächliche Composer-PHAR-Datei liegt laut Wrapper unter `/opt/psa/var/modules/composer/composer.phar`. **Noch keine Pakete installiert**; `vendor/autoload.php` wurde nicht als vorhanden bestätigt. PuTTY blieb diesmal offen.

**Genau nächster Schritt:** Vorhandenen PHAR-Pfad prüfen, dann `runuser -u nicovoss -- /opt/plesk/php/8.4/bin/php /opt/psa/var/modules/composer/composer.phar ... install` im bestehenden isolierten `repo/platform` ausführen. **Nicht** den Shell-Wrapper mit PHP interpretieren; **nicht** neu klonen; **nicht** globale Git-Sicherheitsausnahmen eintragen; **nicht** bestehende Sites/DB verändern. Skript in Subshell ausführen und dann tatsächliche `vendor/autoload.php`-Existenz prüfen.

Chronologisch auch in [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) dokumentiert, Commit `11ee6bdbfd6fee832149e16418ab720769053f2e`.


## LETZTER BESTÄTIGTER MEILENSTEIN · Composer-Installation erfolgreich (22.11.2026)

**Überholt die zuvor protokollierte Composer-Fehlermeldung.** Der private Laravel-/Filament-Klon ist unter dem erwarteten Git-HEAD `980bcce75a0a3516b7b6364fae5a0fa6aab8d112` vorhanden. Der Betreiber hat mit dem korrekten Plesk-Composer-`composer.phar` und explizit **PHP 8.4.28** als Subscription-Eigentümer die PHP-Abhängigkeiten erfolgreich installiert:

- Composer **2.11.2**, Laravel Framework **13.43.0**, Filament **5.12.0**, Livewire **4.4.9**.
- Ohne vorherige `composer.lock` im Repo **145 Versionen** aufgelöst und in einer privaten **serverlokalen Lockdatei** gespeichert; **112 Produktiv-Pakete** installiert (`--no-dev`).
- Composer Autoloading und Laravel `artisan package:discover` erfolgreich. Die lokale Prüfung `vendor/autoload.php` bestand. PuTTY meldete ausdrücklich `=== INSTALLATION ERFOLGREICH ===` und blieb offen.
- Keine Änderungen von Test-Webroot, Live-Domain oder bestehender JTL-Datenbank im Installerprotokoll.

**NOCH OFFEN:** PHP-/Artisan-Runtime im Server isoliert prüfen (`artisan --version`, `route:list`), Composer-Lockfile nach Secret-Check als reproduzierbare Abhängigkeit versionieren, private Laravel-`.env` (APP_KEY, APP_DEBUG=false, HTTPS und ADMIN-Zugang), separate lokale SQLite für Laravel-Login, Filament-Assets, erste Admin-Benutzeranlage, Laravel-HTTP-Test. Die Legacy-Live-V4.25-**Webroot-Hashes und angemeldeter API-/Browser-E2E** bleiben vor dem Umschalten der Testdomain zu bestätigen.

**NÄCHSTER GENAUER SCHRITT:** Isolierten Composer-Erfolg mit read-only Artisan-Befehlen und Live-Webroot-V4.25-Hashes absichern. Anschließend private Laravel-Konfiguration und nur isolierte SQLite-Migration anlegen. Kein Reclone, keine erneute Composer-Installation, kein zusätzlicher Vhost, kein bestehender JTL-Tabelleneingriff.

[Vollständiges Einzelprotokoll](./HW24_ARBEITSJOURNAL.md), GitHub-Dokumentations-Commit `006fbf833d06b9dd89d5a663f653d75afd3444ee`.
