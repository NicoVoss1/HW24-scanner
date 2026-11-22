# HW24 · Preflight vor Filament-Cutover der bestehenden Testdomain

**Stand 22.11.2026.** Diese Anleitung enthält **keinen** Deployment-Befehl. Ziel ist die risikofreie Diagnose der PHP-FPM-/Plesk-/Dateisystemvoraussetzungen. Bereits bestätigte Schritte stehen im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), der jeweils neueste Stand in der [Übergabe](./HW24_HANDOVER.md).

## Nachgewiesene Ausgangslage

- Legacy-Live auf Git-Branch `deploy/staging`, Commit `444453471b0fd27c73dcc7929f5e17f1d9239c52`. Vier geprüfte Webroot-Dateien haben exakt die erwarteten Git-Blob-Hashes. Angemeldeter Legacy-E2E mit JTL/Amazon/OpenAI noch offen.
- Legacy-Test `test-artikelmanager.hairworld24.de`: Webroot weiterhin auf Altanwendung, manuelles Plesk-Git-Deployment `HW24-scanner.git` auf denselben Legacy-Webroot, Branch `deploy/staging`.
- Test-vHost PHP-Handler `plesk-php85-fpm`; private Laravel-CLI wurde mit Plesk PHP 8.4 geprüft, Laravel 13.43.0 und Filament 5.12.0 sind installiert. Laravel 13 unterstützt PHP 8.3 bis 8.5 grundsätzlich. **Paket-/Extension-Kompatibilität auf diesem Server noch prüfen.**
- Private Laravel-`.env` und SQLite sowie `public/index.php` laut Nutzer `0600`, Site-User als Eigentümer. Nginx/Apache-Static-Asset-Lesbarkeit bisher ungeklärt. **Nicht pauschal chmod -R verwenden.**
- Alle Laravel-System-Migrationen liefen nur auf eigener SQLite, Filament-Assets vorhanden, User und `canAccessPanel` geprüft. Login im Web noch nicht getestet.

## Ausschließlich lesender nächster Prüfschritt (PuTTY)

```bash
bash <<'HW24_PREFLIGHT'
set -u

app="/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform"
php85="/opt/plesk/php/8.5/bin/php"
phar="/opt/psa/var/modules/composer/composer.phar"

echo "=== HW24 PHP 8.5 UND DATEIRECHTE ==="

if [ ! -x "$php85" ]; then
  echo "FEHLT: PHP-8.5-CLI-Binary"
else
  echo "1. PHP-Version des vorhandenen Test-FPM-Handlers:"
  runuser -u nicovoss -- "$php85" -v | head -n 1

  echo "2. Composer-Paketvoraussetzungen unter PHP 8.5:"
  runuser -u nicovoss -- "$php85" "$phar"     --working-dir="$app" check-platform-reqs --no-dev     || echo "WARNUNG: PHP-8.5-Paketvoraussetzung nicht komplett erfuellt"

  echo "3. Laravel-CLI und Filament-Routen unter PHP 8.5:"
  runuser -u nicovoss -- "$php85" "$app/artisan" --version     || echo "WARNUNG: Laravel-PHP-8.5-Boot fehlgeschlagen"
  runuser -u nicovoss -- "$php85" "$app/artisan" route:list --path=admin     || echo "WARNUNG: Filament unter PHP 8.5 nicht geladen"
fi

echo "4. Directory-Traverse-Rechte des Webroots:"
namei -l "$app/public/index.php"

echo "5. Directory-Traverse-Rechte der CSS-Assets:"
namei -l "$app/public/css/filament/filament/app.css"

echo "=== PREFLIGHT ABGESCHLOSSEN ==="
HW24_PREFLIGHT
```

Die Ausgabe enthält keine Secrets oder private Konfigurationsinhalte. Sie prüft die lokale PHP-8.5-CLI; die **tatsächliche** Web-FPM-Laufzeit und Asset-Rechte bleiben bis zur HTTP-Abnahme getrennt zu bestätigen. Ein Fehler in einer Teilprüfung soll die PuTTY-Hauptshell nicht beenden.

## Freigabegates für eine spätere Testdomain-Umstellung

1. Erfolgreiches `check-platform-reqs` und Laravel-CLI-Boot unter dem für die Testdomain vorgesehenen PHP-Handler (oder bewusst und gesondert dokumentierter Wechsel auf PHP 8.4-FPM).
2. Private Laravel-App- und Webserver-Asset-Zugriffsrechte bewusst und minimal festlegen, ausschließlich `platform/public` öffentlich. Keine `.env`, `vendor`, `database`, `storage`, `bootstrap` als öffentliche Website.
3. **Zwingend zuvor:** Alte Live-V4.25-App im authentifizierten Browser mit Referenz-EAN und GET-only Amazon-/OpenAI-Archiv-Abfrage testen; kein teurer KI-POST nötig.
4. Die manuelle Plesk-Git-Altbereitstellung dokumentiert entkoppeln oder eindeutig auf alten Legacy-Stand beschränken, ohne unkontrolliertes Überschreiben des Laravel-DocumentRoots; Rückfallpfad vorab prüfen.
5. Für den einzigen bestehenden Test-vHost die Plesk-DocumentRoot-Änderung zur **bestehenden** privaten Laravel-`platform/public`-Struktur vorbereiten (innerhalb Subscription-Verzeichnis), PHP-FPM-Benutzer und `open_basedir` prüfen; bestehendes Live-Legacy unangetastet lassen.
6. HTTP-Zugriffsschutz/HTTPS, Login-Session, 403 bei nicht autorisiertem Benutzer, Filament CSS/JS und GET-only Legacy-Brücke nach Cutover testen. Rückfall auf alten Test-Webroot muss möglich sein. Kein zusätzlicher Subdomain-/MariaDB-Bedarf.
7. Jeder tatsächliche Schritt samt PuTTY-/Browser-Befund, Zeitpunkt, SHA/Commit und Rückfallinformation ins öffentliche [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) **ohne** Passwörter, persönliche Admin-Mail, Tokens oder Konfigurationsdatei-Inhalte protokollieren.

Referenzen: [Laravel 13 Support](https://laravel.com/framework/docs/13.x/releases), [Plesk Document Root](https://support.plesk.com/hc/en-us/articles/12377087631255-How-to-change-the-document-root-for-a-single-domain-on-Plesk), [Plesk Git Deployment](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/git-support.75824/).


## Nachtrag 22.11.2026: Preflight tatsächlich durchgeführt, PHP 8.5 kompatibel

Betreiber-PuTTY-Ausgabe bestätigt **PHP 8.5.13**, `composer check-platform-reqs --no-dev` **durchgehend success**, Laravel **13.43.0** unter PHP 8.5 und alle **vier Filament-Adminrouten**. Ein PHP-Handlerwechsel ist **nicht erforderlich** allein wegen der Versionsnummer. Der tatsächliche FPM-HTTP-Boot ist dadurch noch nicht belegt.

**Dateisystem:** `repo/`, `platform/`, `public/` und sämtliche CSS-Asset-Zwischenverzeichnisse derzeit **0700** für Site-Owner. `public/index.php` und `public/css/filament/filament/app.css` **0600**. PHP-FPM kann als Owner Dateien lesen; ein eigenständiger Webserver-Worker kann damit **nicht automatisch** die öffentlichen CSS-/JS-/Schriften ausliefern. Eigentümer/ACL der Webserver-Prozesse sind noch nicht geprüft. `.env` und SQLite müssen weiterhin privat auf 0600 bleiben. **Niemals chmod -R 755 auf gesamtes Laravel-Verzeichnis**.

### Nächster strikt read-only Webserver-/Rechtecheck

```bash
bash <<'HW24_WEB_ACCESS'
set -u

app="/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform"
css="$app/public/css/filament/filament/app.css"
js="$app/public/js/filament/filament/app.js"

echo "=== HW24 WEB-ACCESS-PRUEFUNG ==="

echo "1. Aktive nginx-Worker-Benutzer:"
ps -eo user=,args= | awk '/nginx: worker process/ {print $1}' | sort -u

echo "2. Aktive Apache-Worker-Benutzer:"
ps -eo user=,comm= | awk '$2=="apache2" && $1!="root" {print $1}' | sort -u

echo "3. PHP-FPM-Pool-Benutzer (nur Namen):"
ps -eo user=,args= | awk '/php-fpm: pool/ {print $1}' | sort -u

echo "4. Lesbarkeit aus Sicht des App-Eigentuemers:"
for file in "$app/public/index.php" "$css" "$js"; do
  if runuser -u nicovoss -- test -r "$file"; then
    echo "OK: $(basename "$file") fuer App-Eigentuemer"
  else
    echo "BLOCKIERT: $(basename "$file") fuer App-Eigentuemer"
  fi
done

echo "5. Lesbarkeit fuer HTTP-Worker (falls www-data existiert):"
if id www-data >/dev/null 2>&1; then
  for file in "$css" "$js"; do
    if runuser -u www-data -- test -r "$file"; then
      echo "OK: $(basename "$file") fuer www-data"
    else
      echo "BLOCKIERT: $(basename "$file") fuer www-data"
    fi
  done
else
  echo "www-data nicht vorhanden; aktiven nginx/Apache-Worker gesondert pruefen"
fi

echo "6. ACL-Werkzeug vorhanden:"
command -v getfacl || true
command -v setfacl || true

echo "=== PRUEFUNG ABGESCHLOSSEN; KEINE AENDERUNGEN ==="
HW24_WEB_ACCESS
```

Der Ablauf liest weder `.env` noch Datenbankinhalte oder Passwörter, ändert keine Benutzer-/Dateirechte und führt keinen Deploy aus. Bei anderem Webserver-Worker als `www-data` die konkrete Identität nach Auswertung der `ps`-Ausgabe entsprechend prüfen. **Erst dann** gezielte ACL-/Mode-Freigaben für den öffentlichen Assetpfad und notwendige Traverse-Rechte planen, ohne App-Private-Dateien lesbar zu machen.

**Weiterhin erforderlich vor dem Cutover:** Authentifizierter Browser-E2E auf der alten Live-V4.25-Domain (JTL, Amazon, vorhandenes KI-Archiv) sowie ein Rollback der bestehenden Testdomain und Entkopplung des alten manuellen Git-Deploy-Ziels.

[Einzelbeleg im Journal](./HW24_ARBEITSJOURNAL.md), ursprünglicher Prüfprotokoll-Commit `1d0a3e55b1a5c8e59ced2e665687ce11984520f6`.


## Nachtrag: Web-Access-Prüfung abgeschlossen, Rechteblockade nachgewiesen

PuTTY bestätigt die aktiven Webserver-Prozessbenutzer `nginx` und `www-data`; `php-fpm: pool`-Prozesse zeigen `www-data`, ihre Zuordnung zum konkreten Testdomain-Pool ist noch nicht geklärt. Für `public/index.php`, Filament-CSS und Filament-JS ergaben `test -r` jeweils `nicovoss: LESBAR`, `nginx: GESPERRT`, `www-data: GESPERRT`. `getfacl` und `setfacl` wurden im PATH nicht gefunden. Der ausführliche Nachweis steht im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Commit `caf206b3ceef57a2c5bbb0d633dcf00bca4ea4d3`.

**Keine pauschalen `chmod`-Operationen durchführen.** Linux-Standardrechte reichen derzeit nicht für die öffentliche Dateiauslieferung durch die geprüften Worker. Die tatsächlichen Test-vHost-PHP-FPM-Pool-Metadaten und Unix-Gruppenzuordnungen müssen zunächst geklärt werden. Laut [Plesk-Support](https://support.plesk.com/hc/en-us/articles/12377800927127-How-to-find-and-edit-PHP-configuration-files-in-Plesk-for-a-domain-or-for-global-PHP-handler) liegen pro Domain generierte Plesk-FPM-Pooldateien typischerweise unter `/opt/plesk/php/8.5/etc/php-fpm.d/`. Hier nur erlaubte `user/group/listen.owner/listen.group`-Metadaten lesen, niemals private Konfigurationen oder Secrets ausgeben.

### GENAU NÄCHSTE PRÜFUNG: Unix-Gruppen und PHP-8.5-Pool (read-only)

```bash
bash <<'HW24_POOL_READ'
set -u
echo "=== HW24 POOL UND UNIX-GRUPPEN ==="

echo "1. Aktuelle Gruppen der drei beteiligten Benutzer:"
for u in nicovoss nginx www-data; do
  if id "$u" >/dev/null 2>&1; then
    id "$u"
  else
    echo "Benutzer nicht gefunden: $u"
  fi
done

echo "2. GitHub-Framework-/Domain-Gruppe (nur Metadaten):"
for g in psacln psaserv; do
  getent group "$g" | cut -d: -f1,3,4 || true
done

echo "3. Plesk PHP-8.5-FPM-Konfiguration der Testdomain:"
pool="/opt/plesk/php/8.5/etc/php-fpm.d"
if [ -d "$pool" ]; then
  find "$pool" -maxdepth 1 -type f -iname '*test-artikelmanager*.conf' -print |
  while IFS= read -r file; do
    echo "Pool-Konfiguration vorhanden"
    awk -F= '
      /^[[:space:]]*(user|group|listen.owner|listen.group|listen.mode)[[:space:]]*=/ {
        gsub(/[[:space:]]+/, "", $1)
        sub(/[;#].*$/, "", $2)
        gsub(/^[[:space:]]+|[[:space:]]+$/, "", $2)
        print $1 "=" $2
      }
    ' "$file"
  done
else
  echo "Pool-Verzeichnis nicht vorhanden"
fi

echo "4. Asset-Verzeichnisrechte im App-Baum:"
app="/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform"
stat -c '%a %U:%G %n' \
  "$app" "$app/public" "$app/public/css" \
  "$app/public/css/filament" \
  "$app/public/css/filament/filament" \
  "$app/public/css/filament/filament/app.css" \
  "$app/.env" "$app/database/database.sqlite"

echo "=== NUR LESEPRUEFUNG ABGESCHLOSSEN ==="
HW24_POOL_READ
```

Dieser Block verändert **keine** Dateirechte, legt **keine** ACL an, liest nur Unix-User-/Gruppen-IDs, ausgewählte Pool-`user/group`-Schlüssel und `stat`-Metadaten und gibt **keinen** `.env`-/SQLite-Inhalt aus. Keinen PHP-FPM-/Webserver-Neustart durchführen. Erst nach Analyse dieser Angaben eine minimal erforderliche, rückrollbare Asset-Freigabe und FPM-Trennung konzipieren.

Weiterhin gilt: Keine Domain-Umstellung vor authentifiziertem Legacy-Live-Artikel-/Amazon-/KI-Funktionstest.


## Bestätigt: echter Test-vHost-PHP-8.5-FPM-Pool und Webservergruppen

Neue Betreiber-PuTTY-Ausgabe hat die noch offenen Poolmetadaten vollständig bestätigt:

- `user=nicovoss`, `group=psacln` im tatsächlichen Plesk-PHP-8.5-FPM-Pool der **Testdomain**. PHP-FPM erhält damit die Besitzerrechte auf `.env` und Laravel-SQLite (beide 0600) und muss **keine** Gruppe `psaserv` in die Laravel-Konfigurationsdateien aufgenommen bekommen.
- FPM-Socket: `listen.owner=root`, `listen.group=psaserv`, `listen.mode=0660`. Die aktiven Prozessnutzer `nginx` und `www-data` sind in `psaserv`, jedoch nicht in `psacln`.
- Laravel `platform/`, `public/`, `public/css/` weiter 0700; statische Filament-CSS 0600. Damit ist die bisherige Lesesperre für Nginx/Apache hinsichtlich CSS/JS/Fonts **durch die Unix-Gruppenzuordnung erklärbar**; kein PHP-/Laravel-Fehler.
- `getfacl` und `setfacl` wurden vorher im aktuellen PATH nicht gefunden. Das kostenlose Ubuntu-22.04-Paket `acl` liefert beide Programme: https://packages.ubuntu.com/jammy/amd64/acl/filelist.
- Die Bestätigung steht im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Commit `ac9cbd70d710a301d19bd8f7001f65c266471fbf`.

### Nächste kontrollierte Vorbereitungsaktion: ACL-Werkzeuge bereitstellen

**Noch nicht ausgeführt.** Der folgende Block installiert **nur falls erforderlich** das freie Ubuntu-Paket `acl` als Root. Er **ändert keine Laravel-Dateirechte, kein Plesk-Domainsetting und keine bestehende JTL-Datenbank**. Da dies eine Systempaketinstallation ist, soll der Betreiber den Befehl bewusst freigeben und ausführen.

```bash
bash <<'HW24_ACL_TOOLS'
set -euo pipefail
echo "=== HW24 ACL-WERKZEUGE ==="

if command -v getfacl >/dev/null 2>&1 &&
   command -v setfacl >/dev/null 2>&1; then
  echo "ACL-Werkzeuge bereits vorhanden"
else
  echo "Installiere ausschliesslich Ubuntu-Paket acl ..."
  DEBIAN_FRONTEND=noninteractive     apt-get install -y --no-install-recommends acl
fi

command -v getfacl
command -v setfacl

echo "Dateisystem der privaten Laravel-App:"
findmnt -T /var/www/vhosts/hairworld24.de/hw24-console-private   -no TARGET,FSTYPE,OPTIONS

echo "=== ACL-VORBEREITUNG ABGESCHLOSSEN ==="
HW24_ACL_TOOLS
```

**Danach (gesondert und nicht automatisch):** Vor jeder `setfacl`-Änderung eine root-private `getfacl`-Sicherung der ausgewählten Pfade einschließlich Verzeichnisse und Asset-Unterbäume sowie eine `setfacl --restore`-Rückfallanleitung erstellen. Für nginx und www-data ausschließlich **`--x` auf notwendige private Elternverzeichnisse** und **`r-x` auf die öffentlichen statischen `public/css`, `public/js`, `public/fonts`** verwenden; **keine Lese-ACL** auf `.env`, SQLite, `vendor`, `storage`, `app` oder andere Laravel-Privatquellen. Nach Prüfung die CSS/JS-Benutzer-Leseproben wiederholen. Der Frontcontroller `public/index.php` bleibt für PHP-FPM-Site-User erreichbar, ohne ihn für nginx als Quelltext freizugeben.

**Weiteres Gate vor Webroot-Cutover:** alten Legacy-Live-Artikelmanager mit gültiger Anmeldung, JTL-Artikel, manuellem Amazon-GET und archiviertem KI-GET abnehmen; Plesk-Git-Deployment-Ziel/rollback dokumentieren; nur `platform/public` als Docroot wählen. Webserver-Static-Asset-Auslieferung unter Plesk erfolgt je nach nginx/Apache-Setup mit deren Prozessrechten. Offizielle Hinweise: https://laravel.com/framework/docs/13.x/deployment und https://docs.plesk.com/en-US/obsidian/customer-guide/websites-and-domains/hosting-settings/web-server-settings/apache-and-nginx-settings.72320/.
