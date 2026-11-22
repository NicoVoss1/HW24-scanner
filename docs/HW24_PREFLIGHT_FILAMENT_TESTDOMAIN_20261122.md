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
