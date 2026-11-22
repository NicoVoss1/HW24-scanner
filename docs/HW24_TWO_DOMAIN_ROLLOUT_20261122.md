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

## Bestätigte Webroots und Plesk-Git-Einträge (PuTTY, 22.11.2026)

**Live Artikelmanager**: `artikelmanager.hairworld24.de`. Separater Plesk-Webroot, SSL/TLS eingeschaltet, PHP-Unterstützung vorhanden, Plesk-Git-Repository `HW24-scanner` vom Typ pull.

**Test Artikelmanager**: `test-artikelmanager.hairworld24.de`. Anderer separater Plesk-Webroot, SSL/TLS eingeschaltet, PHP-Unterstützung vorhanden, Plesk-Git-Repository `HW24-scanner.git` vom Typ pull.

Beide Sites laufen unter demselben Plesk-Subscription-Systembenutzer. **Ob die Deploy-Branches/Modi/Quellstände korrekt sind, ist noch nicht belegt.** Weder die produktive Live-Domain noch der Webroot der Testdomain wurden im Rahmen dieser Prüfung verändert.

**Architekturkritischer Fund:** `platform/app/Services/LegacyReadBridge.php` in Framework-Draft-PR #36 ist derzeit ausdrücklich auf Host `test-artikelmanager.hairworld24.de` fixiert. **Vor der Umstellung** der Testdomain auf Laravel muss die Bridge nach erfolgreichem Legacy-V4.25-Cutover kontrolliert auf `artikelmanager.hairworld24.de` umkonfiguriert werden, einschließlich Auth-/Quellprüfung; andernfalls würde Laravel seine eigenen bisherigen Legacy-PHP-Endpunkte ansprechen und die Integration scheitern. Die Änderung gehört ausschließlich auf den isolierten Framework-Featurebranch und benötigt Tests gegen Redirects und Host-Freigaben.

### Nächste risikoarme Domain-/Git-Inventur

Nur lesen; keine `--fetch`, `--deploy`, `--update`, `git reset` oder Datenbankmigration.

```bash
for spec in "artikelmanager.HAIRWORLD24.de|HW24-scanner" "test-artikelmanager.HAIRWORLD24.de|HW24-scanner.git"; do
  domain="${spec%%|*}"
  repo="${spec#*|}"
  printf '\n=== PLESK GIT: %s ===\n' "$domain"
  plesk ext git --info -domain "$domain" -name "$repo" 2>&1 |
    grep -Ei 'branch|deploy|path|mode|commit|status' |
    grep -Eiv 'remote|url|token|password|credential' || true
done
for domain in artikelmanager.HAIRWORLD24.de test-artikelmanager.HAIRWORLD24.de; do
  root="/var/www/vhosts/hairworld24.de/$domain"
  printf '\n=== Vorhandene WEB-Dateien: %s ===\n' "$domain"
  for f in index.html artikelpflege.html qualitaetszentrum.html scanliste.html api/jtl.php api/amazon.php api/ai-reviews.php; do
    if [ -f "$root/$f" ]; then printf 'VORHANDEN: %s\n' "$f"; else printf 'FEHLT: %s\n' "$f"; fi
  done
done
```

Die Dateiexistenz liefert noch keinen Versions-/Laufzeitnachweis. Den Git-Branch und die deployte Versionskennung anschließend vergleichen, bevor die Legacy-Seite migriert wird.

## Bestätigte Git-Branches und Webdateien auf Plesk (22.11.2026)

**Plesk Live** `artikelmanager.HAIRWORLD24.de`: Repository `HW24-scanner`, manueller Deploy, Branch `main`, Deploypfad `/artikelmanager.HAIRWORLD24.de`. `index.html` und `api/jtl.php` vorhanden; `artikelpflege.html`, `qualitaetszentrum.html`, `scanliste.html`, `api/amazon.php` und `api/ai-reviews.php` fehlen. **Damit ist die Live-Installation älter und unvollständig gegenüber V4.25**, ein reales Versions- oder API-Hosting-Audit bleibt ausstehend.

**Plesk Test** `test-artikelmanager.HAIRWORLD24.de`: Repository `HW24-scanner.git`, manueller Deploy, Branch `deploy/staging`, Deploypfad `/test-artikelmanager.HAIRWORLD24.de`. Alle sieben abgefragten Site-Dateien vorhanden. **Das belegt Dateiexistenz, nicht ob der letzte V4.25-Commit `444453471b0fd27c73dcc7929f5e17f1d9239c52` wirklich deployed wurde.**

### Ab jetzt: gezielter Abgleich, kein erneutes allgemeines Setup

Vor Live-Deployment aktuellen Plesk-Testcode anhand Git-Blob-SHAs gegen GitHub-`deploy/staging` vergleichen:

```bash
testroot=/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de
for spec in \
  'index.html|96d18f937df2ca99bb76fa5754a01451fb4b4e3e' \
  'artikelpflege.html|c146471c1900b51a905e6675ee83381d0221342f' \
  'qualitaetszentrum.html|6b65b5d02764c832a7385d5b13eb916aced40477' \
  'scanliste.html|4b82f66b12e856e4fff83460f8dad3683634f9b5'; do
  file="${spec%%|*}"; expected="${spec##*|}"
  actual=$(git hash-object "$testroot/$file" 2>/dev/null || echo MISSING)
  if [ "$actual" = "$expected" ]; then echo "OK: $file"; else echo "ABWEICHUNG: $file"; fi
done
printf '\nPlesk Git letzter bekannter Commit (nicht zwingend deployter Commit):\n'
plesk ext git --get-last-commit -domain artikelmanager.HAIRWORLD24.de -name HW24-scanner
plesk ext git --get-last-commit -domain test-artikelmanager.HAIRWORLD24.de -name HW24-scanner.git
```

**Freigabeschranke:** Wenn ein Hash abweicht, nicht blind von Test nach Live kopieren oder Plesk-Test überschreiben. Zuerst tatsächliche Abweichung abgleichen.

Anschließend unmittelbar vor dem Cutover ein lokales, nur administrativ lesbares **frisches** Snapshot-Backup der beiden Webroots (ergänzend zu früheren Backups) und eventueller externer Abhängigkeiten anlegen und Restorebarkeit prüfen. Das vorhandene öffentliche Live-Frontend kann eine **andere** HTTP-Authentifizierung oder private Konfiguration als Test haben. Das Kopieren der V4.25-Dateien darf nicht zu unautorisiertem Zugriff auf JTL-, Amazon- oder OpenAI-Endpunkte führen.

**Erst nach Freigabe** Plesk-Live-Pull-Repository `HW24-scanner` von `main` auf `deploy/staging` umstellen und manuell deployen; die dokumentierten CLI-Operationen lauten `plesk ext git --fetch`, `--update -active-branch`, `--deploy`. Weder Test-Branch noch GitHub-`main` direkt verändern. Die neue Laravel-Domain erst umschalten, wenn die Legacy-API auf Live erfolgreich und sicher erreichbar ist.

Dokumentation zu CLI: https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/git-git-repositories.75956/

## Deployment-Gate nach echtem Server-Hashabgleich (22.11.2026)

**Vom Betreiber bestätigt:**
- Plesk `artikelmanager.HAIRWORLD24.de` Branch `main`, letzter Repository-Commit `1c274f605c58f652273c294a9dd559d85518bbc5`.
- Plesk `test-artikelmanager.HAIRWORLD24.de` Branch `deploy/staging`, letzter Repository-Commit `f51ab67240bd1bf0e5be25498269049a24748b85` = V4.24.
- 7 von 7 erwartete Dateien auf Test vorhanden. Exakte V4.25-Git-BLOB-Hashes `index.html`, `qualitaetszentrum.html`, `scanliste.html`, `api/amazon.php`, `api/ai-reviews.php` sind OK. `artikelpflege.html` und `api/jtl.php` sind abweichend.
- GitHub-Vergleich `f51ab672...44445347`: **17 Commits voraus**, **0 zurück**, acht geänderte Dateien inklusive genau dieser beiden, `assets/hw24-article-intelligence.js`, `assets/hw24-article-editor.js` etc. Die Abweichungen sind mit einem noch nicht deployten V4.25-Update vereinbar; kein belegter Verlust lokaler Änderungen.
- **Entscheidung:** Erst bestehende Testsite auf exakt V4.25 `444453471b0fd27c73dcc7929f5e17f1d9239c52` bringen und Browser/API smoke-testen. Danach auf Live migrieren, erst dann die bisherige Testsite für Laravel umstellen. Draft-Framework PR #36 bleibt gesperrt und wird nicht in den Legacy-Deploybranch gemerged.

### Nächster Schritt: Testsite-Deployment mit SHA-Gate und privatem Snapshot

- Nur Plesk-Git-Repository `HW24-scanner.git` der **Testdomain**; keine Änderungen an Live, GitHub-Branches oder JTL-Datenbanken.
- Vor Bereitstellung der aktuellen V4.25-Website einen frischen, privaten Webroot-Snapshot nur für diesen konkreten Cutover unter `/root/hw24-backups/` erstellen (Dateimodus 0600). Kein Archiv im öffentlichen Webroot.
- `plesk ext git --fetch`, danach `--get-last-commit` muss **exakt** `444453471b0fd27c73dcc7929f5e17f1d9239c52` ergeben. Bei Abweichung **STOP** ohne Deployment.
- `plesk ext git --deploy` manuell ausführen und danach kritische Git-Blob-Dateihashes erneut vergleichen; die letzte tatsächliche Live-Abnahme erfolgt erst durch Browser/API-Checks.
- Kein `git reset --hard`, kein Plesk-Live-Deploy, keine SQL-Migration, keine Offenlegung privater `.env`-/API-Dateien.
