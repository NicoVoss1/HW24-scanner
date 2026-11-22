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
