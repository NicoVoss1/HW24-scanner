# HW24 | Zwei bestehende Domains statt einer dritten Testdomain
Stand: 22.11.2026

## Bestätigte Nutzerentscheidung
**Keine zusätzliche Subdomain** für die Framework-Vorschau anlegen. Nutzer verwendet vorhandene Live-/Test-Artikelmanager-Domains; bisherige Live-Domain wird nach eigenem Hinweis operativ derzeit nicht genutzt.

**Gewünschte Zielzuordnung:**
1. Bestehende **Live-Artikelmanager-Domain** dient künftig als gesicherte, funktionierende Referenz der bisherigen Anwendung auf dem getesteten **V4.25-Legacy-Stand** aus GitHub-`deploy/staging` (`444453471b0fd27c73dcc7929f5e17f1d9239c52`). **Noch nicht durchgeführt oder auf dem Server bestätigt.**
2. Bestehende **Testdomain** `test-artikelmanager.hairworld24.de` wird erst **nach** bestätigter Legacy-Funktion auf Live für die neue **Laravel 13 / Filament 5**-Intelligence Console umgenutzt, ohne neue Domain. **Noch nicht umgestellt.**
3. Keine zusätzliche **verwaltete MariaDB-Datenbank** bloß für die erste Oberfläche. Vorhandenen JTL-/HW24-MariaDB-Spiegel nur mit LESERECHTEN anbinden. Für Laravel-eigene Login-/Session-/Jobs-/Entwurfsdaten muss später eine eigene, logisch isolierte Datenhaltung mit explizitem Migrationsplan gewählt werden: ggf. separate Tabellen mit Namensraum in bestehender MariaDB oder eine private SQLite-Datei. **Nichts ohne ausdrückliche Freigabe anlegen.**
4. Ziel >100.000 Artikel, serverseitige Suche/Pagination, Indizes, geprüfte Antwortzeiten; später kontrollierte Schreibprozesse, niemals blindes SQL-Update auf JTL-Wawi/MS SQL.

## Aktuelle Fakten
- PuTTY-SSH-Server Ubuntu 22.04.5 LTS, Plesk; root-Zugang vorhanden (nicht für Composer/Artisan-Anwendungsbetrieb nutzen).
- PHP CLI default **8.1.2**, isoliert Plesk PHP **8.4.28** nutzbar (`/opt/plesk/php/8.4/bin/php`). Composer 2.11.2 und Git verfügbar. Node/npm im PATH nicht vorhanden; 914 GB frei.
- GitHub PR **#36** ist DRAFT, nicht gemerged. Das Framework-Skeleton befindet sich unter `platform/` im Featurebranch `feature/hw24-platform-laravel13-filament5-20261122`; noch kein Plesk-Install, noch kein Filament-Panel.
- Legacy `main` und `deploy/staging` sind divergiert; **nicht unkontrolliert mergen/resetten**.
- Bereits separat gesicherte API- und Server-Private-Konfigurationen bleiben außerhalb des öffentlichen GitHub-Deployments.
- Originale Entwicklungs-/Datenbank-Backups aus der früheren Arbeitsphase sind laut Nutzer bereits vorhanden, aber ihr aktueller Restore-Stand vor einem Domain-Umbau ist **noch nicht verifiziert**.

## Abnahmereihenfolge, ohne dritte Domain
**Gate A: Inventur lesen, nichts verändern.** Plesk-Live- und Testdomain: tatsächliche Namen, Subscriptions, DocumentRoots, zugeordnete Git-Repositories/-Branches, manuelle/automatische Deployart, PHP-Handler, Basis-Authentifizierung, eventuelle Cronjobs und bestehende private Konfigurationspfade **nur anhand Metadaten** inventarisieren. Nicht die Secret-Dateien öffnen oder ausgeben. Auch prüfen, ob Git-Staging-Version schon auf dem Server ist.

**Gate B: Legacy-V4.25 auf vorhandene Live-Seite freigeben.** Vorher aktuellen Live-Webroot sowie Konfiguration und referenzierte Daten sichern/restorebar machen. V4.25-Website-Dateien aus dem **exakt gepinnten Staging-Commit** zielgerichtet ausrollen. Keine blind ausgeführte `git merge`- oder `git reset --hard`-Aktion auf `main`; keine gleichzeitigen Änderungen an Scanlisten-/MariaDB-Daten, privaten API-Schlüsseln oder synchronisierter JTL-Datenquelle. API-Relative-Pfade, Zugriffskontrollen, Dokumentroot, mögliche CORS/Host-Checks, Serverrollen und geschützte Konfigurationen vergleichen. Browser- und API-Regressionstests inklusive JTL/WMS-Historie, EAN, Scanliste, Amazon-Fehlerbehandlung, KI-Archiv, Qualitätszentrum; danach alten Test-Webstand als wiederherstellbar bestätigen.

**Gate C: Testdomain gezielt auf Framework umstellen.** Auf demselben bestehenden Plesk-vHost ein **eigenes privates App-Verzeichnis** anlegen, dessen Code nicht in der bestehenden DocumentRoot und nicht im Git-Legacy-Webroot liegt. GitHub-PR #36 nicht unkontrolliert in `deploy/staging` mergen. Laravel-DocumentRoot muss ausschließlich auf `platform/public` verweisen (offizielle Laravel/Plesk-Empfehlung), App und `.env` bleiben geschützt. PHP 8.4 (Webhandler und CLI), dedizierter Web-/Systembenutzer, HTTPS, Login/Basic Auth/Rollen vor Freigabe. Frontend-Assets über CI bauen, sofern Node nicht installiert werden soll. Bis zum geprüften Login keinen externen Zugriff gewähren.

**Gate D: Erste Vorschau.** Filament-5-Adminpanel, zunächst mit abgesicherten Beispieldaten oder vorhandenen read-only Legacy-APIs. Kein Datenbankschreibzugriff auf JTL oder synchronisierten MariaDB-Spiegel, keine kostenpflichtigen OpenAI-Abfragen automatisch. Authentifizierung mit eigener Datenhaltung erst nach Freigabe. Später geordneter Ausbau zum Artikel-/Preis-/Scan-/Qualitäts-Hub.

## Nächste minimale PuTTY-Abfrage (read-only)
```bash
printf '\n=== HW24 Vorhandene Domains (nur lesen) ===\n'
plesk bin site --list 2>/dev/null | grep -Ei 'artikelmanager|hairworld24' || true
plesk bin subdomain --list 2>/dev/null | grep -Ei 'artikelmanager|hairworld24' || true
printf '\n=== PHP 8.4 FPM-Handler ===\n'
plesk bin php_handler --list | grep -E 'plesk-php84-(fpm|fpm-dedicated)' || true
printf '\n=== Erweiterungen PHP 8.4 ===\n'
/opt/plesk/php/8.4/bin/php -r 'foreach(["pdo_mysql","pdo_sqlite","sqlite3","intl","zip"] as $e) echo $e,": ",(extension_loaded($e)?"OK":"FEHLT"),PHP_EOL;'
printf '\n=== Ende; keine Änderungen ===\n'
```

Nach der Ausgabe gezielt die **beiden konkreten** Plesk-Domainkonfigurationen und ihre DocumentRoots/Deploypfade prüfen. Kein weiteres allgemeines Setup wiederholen.

## Links
- GitHub Legacy V4.25: https://github.com/NicoVoss1/HW24-scanner/pull/34
- Framework-Pilot DRAFT, nicht mergen: https://github.com/NicoVoss1/HW24-scanner/pull/36
- Laravel Deployment: https://laravel.com/framework/docs/13.x/deployment
- Plesk Laravel Toolkit: https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/laravel-toolkit.80010/

## Plesk Readiness bestätigt (PuTTY-Ausgabe, 22.11.2026)

**Verifiziert (Nutzer):** Auf dem Server sind `artikelmanager.HAIRWORLD24.de`, `test-artikelmanager.HAIRWORLD24.de` und `HAIRWORLD24.de` in Plesk gelistet. Eine doppelte Anzeige in `site --list` und `subdomain --list` bedeutet nicht sechs voneinander getrennte Websites. **HAIRWORLD24.de ist eine eigenständige Shopdomain und ausdrücklich nicht Ziel dieses Rollouts.**

- `plesk-php84-fpm` und `plesk-php84-fpm-dedicated` sind aktiviert, jeweils PHP **8.4.28**.
- PHP-8.4-Module **pdo_mysql, pdo_sqlite, sqlite3, intl, zip**: alle vorhanden.
- Keine neue Subdomain oder neue MariaDB erforderlich für Vorbereitungen.
- Plesk-Hostingkonfigurationen, Webroots, tatsächliche Git-Deploypfade/Branches, PHP-Zuordnung je Domain, Auth und benötigte Website-Bindings sind **noch nicht verifiziert**. Keine Website wurde bisher umgestellt, Framework noch nicht installiert.

### Unmittelbar nächste Nur-Lese-Abfrage

```bash
printf '\n=== ARTIKELMANAGER LIVE: Hosting ===\n'
plesk bin site --info artikelmanager.HAIRWORLD24.de | grep -Ei 'domain name|hosting|document root|www.root|www_root|php|ssl|webspace' || true
printf '\n=== ARTIKELMANAGER TEST: Hosting ===\n'
plesk bin site --info test-artikelmanager.HAIRWORLD24.de | grep -Ei 'domain name|hosting|document root|www.root|www_root|php|ssl|webspace' || true
printf '\n=== DOKUMENTROOT + SYSTEMUSER (Plesk DB nur SELECT) ===\n'
plesk db -Ne "SELECT d.name, s.login, h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id JOIN sys_users s ON h.sys_user_id=s.id WHERE d.name IN ('artikelmanager.HAIRWORLD24.de','test-artikelmanager.HAIRWORLD24.de') ORDER BY d.name" 
for domain in artikelmanager.HAIRWORLD24.de test-artikelmanager.HAIRWORLD24.de; do
  printf '\n=== Git Repositories: %s ===\n' "$domain"
  plesk ext git --list -domain "$domain" 2>&1 | head -n 30
done
printf '\n=== Nur lesend abgeschlossen ===\n'
```

**Wichtig:** Ausgabe darf bei Veröffentlichung um private Benutzer-/Pfadkomponenten gekürzt werden; keine `.env`-/Basic-Auth-/DB-Config-Dateien lesen, keine Deploybefehle. Bei Git-Repositories mit hinterlegten Remote-URLs auf etwaige Zugangstoken achten und URL-Querys vor dem Teilen schwärzen.
