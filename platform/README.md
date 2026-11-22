# HW24 Intelligence Console · isolierter Laravel-13-/Filament-5-Pilot

**Status 22.11.2026:** Das offizielle Laravel-13-Skeleton liegt im **separaten GitHub-Featurebranch**, nicht im Plesk-Webroot und nicht in `deploy/staging` oder `main`. `filament/filament:^5.0` ist in Composer deklariert. **Laravel-/Filament-Pakete und Adminpanel sind noch nicht auf dem Plesk-Server installiert.** Ohne Composer, NPM und geeignete PHP-Handler ist die App dort noch nicht startbar.

## Sicherheit und Zero-Loss-Vorgehen

- Eigenständige PHP-Anwendung unter **privatem Verzeichnis außerhalb der bestehenden DocumentRoot** installieren. Der neue Vhost muss ausschließlich auf `platform/public` zeigen. **Niemals** das gesamte `platform/` in den alten öffentlich ausgelieferten Webroot deployen.
- `.env` niemals committen oder kopieren. Getrenntes neues SQLite-Test-DB-File (später dedizierte MariaDB) benutzen; vorhandene `hw24_artikelmanager`-Tabellen, JTL-Wawi/MSSQL, Scanliste, Review-Archiv und API-Schlüssel **nicht** migrieren, resetten, überschreiben oder verschieben.
- Der `LegacyReadBridge` greift ausschließlich per **HTTPS GET** auf erlaubte bisherige Test-APIs zu. Credentials bleiben Server-Umgebungsvariablen. Amazon/OpenAI wird nie beim Navigieren automatisch aufgerufen; KI kostenpflichtig nur mit gesonderter Bestätigung nach einem zukünftigen expliziten Workflow.
- Der neue Preisvergleich zeigt `gross` und `net` **getrennt** mit Quelle, Status, Zeitpunkt und Kontext. Fehlende verifizierte JTL-Bruttopreise werden ausdrücklich als unbekannt geführt. Amazon-Marktangebote sind keine eigenen Amazon-Angebote; eBay- und Shopify-Preise sind eigene Kanalbeobachtungen.
- `platform/database/migrations` sind **ausschließlich für die neue, isolierte Plattformdatenbank**. Kein `php artisan migrate` gegen bestehenden JTL-/HW24-Spiegel.

## Hosting zunächst nur prüfen (später bei freigegebenem Serverzugang)

```bash
bash platform/ops/check-host-readonly.sh
```

Das Skript zeigt PHP/Composer/Node/Extensions, führt **keine** Änderungen aus und gibt keine privaten Schlüssel aus.

## Lokaler Entwicklungs-Pilot (nicht in Plesk ausführen, bis Vhost/Backup/Freigaben stimmen)

```bash
cd platform
composer install --no-interaction
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan filament:install --panels
npm install
npm run build
php artisan test
```

Die Standardumgebung ist absichtlich **SQLite**, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=log`; keine Verbindung zum produktiven JTL-Spiegel. `php artisan filament:install --panels` richtet das Filament-Adminpanel ein. Danach Auth-/Rollenprüfung und erst dann echte Datenintegration. Vor einem Serverstart: `APP_DEBUG=false`, `APP_ENV=production`, Zugriffsschutz und CSRF/HTTPS prüfen.

## Datenmodell / Preiskonsole

- `ChannelPrice`: validiert Anbieter `jtl | shopify | amazon | ebay`, Preisart, Währung, Netto und Brutto (beide separat), Preisquellen und Zeitstempel. Status `verified | derived | unverified | unavailable`. Ein verifizierter Wert benötigt eine Quelle, einen Beobachtungszeitpunkt und einen Preis.
- `PriceBoard`: Preise werden **pro Kanal, Preisart, Konto, Marketplace und Angebot** getrennt; fehlende Quellen erscheinen explizit als `unavailable` statt 0,00 €.
- `VerifiedGrossDeriver`: Kann zu einem belegten **JTL-Netto-VK** einen **explizit als berechnet gekennzeichneten Brutto-VK** auf Basis eines ebenfalls belegten Steuersatzes, Steuerlands, Preisquellen-/Kundengruppenkontexts bestimmen. Ohne diese Nachweise bleibt Brutto unbekannt, niemals einfach Netto × 1,19 als „original aus JTL“ ausgeben.
- `channel_price_observations`: indexierte isolierte SQLite/MariaDB-Tabelle für viele Produkte und Beobachtungen. Speicherung aus Amazon/SP-API nur im rechtlich/vertraglich zulässigen Umfang und Aufbewahrungszeitraum.
- `LegacyReadBridge`: gemeinsamer nur-lesender Adapter zu JTL, Amazon-Katalog/-Preisen und OpenAI-Archiv; noch keine Schreiboperation.

## Preisquellen und noch offene Abnahmen

| Kanal | Quelle | Status |
| --- | --- | --- |
| JTL Standard Netto-VK | vorhandener `api/jtl.php`-Spiegel | lesbar |
| JTL Standard Brutto-VK | verifizierte Windows-SQL-Preis-/Steuer-/Kundengruppenzuordnung | **offen, Issue #33** |
| Shopify | Admin GraphQL `ProductVariant.price` + barcode/SKU | angeschlossener Shopify-Shop lesbar, EAN 3474630337398: angezeigter Variantenpreis 14,00 EUR (ACTIVE, Sichtbarkeit/Steuerinklusion nicht geprüft). **Nicht automatisch Brutto** |
| Amazon | vorhandene SP-API `amazon-pricing.php`, Catalog, später eigene Listings | auf Seite integrierter Code, **Live-Abnahme offen** |
| eBay | JTL-eazyAuction-Datenabgleich oder eBay Sell Inventory/Offer API | **nicht verbunden/nicht verifiziert** |

## Freigabe- und Migrationsgates

1. Laravel/Filament-Lizenz und Hosting-Kompatibilität prüfen.
2. Isoliertes Framework in CI starten, nach Möglichkeit mit Composer-Lockdatei.
3. Neue Filament-Anmeldung/Rollen; geschütztes read-only Produkt-/Preisboard.
4. Quellengleichheit der Legacy-APIs und Response-Verträge mit Test-EAN nachweisen.
5. JTL-Brutto-VK-Feldquelle auf Windows verifizieren und integrieren (keine pauschale 19%-Fiktion).
6. Erst nach Vergleich und Backups alte Module schrittweise ablösen; Featureflags und Rollback.
7. Keine Änderungen an `deploy/staging`-/Live-Domain ohne separaten ausdrücklich freigegebenen Release.

Referenzen: [Laravel 13 Deployment](https://laravel.com/framework/docs/deployment), [Filament 5 Installation](https://filamentphp.com/docs/5.x/introduction/installation), [Shopify GraphQL](https://shopify.dev/docs/api/admin-graphql/latest/queries/productVariants), [JTL Preisermittlung](https://guide.jtl-software.com/jtl-wawi/artikel/preisermittlung-in-jtl-wawi/).


## Erste echte Console-Funktionen (GitHub-Featurebranch, 22.11.2026)

Neu implementiert, **noch nicht auf Plesk veröffentlicht**:
- Filament-5-Adminpanel unter `/admin` mit privater Laravel-Benutzeranmeldung.
- Fail-closed Zugriff: nur die in der **privaten** `.env` konfigurierte Adresse `HW24_ADMIN_EMAIL` darf das Filament-Panel verwenden. Ohne Konfiguration kein freigegebener Anwender.
- `/admin/artikel` sucht über die vorhandene Legacy-JTL-GET-API nach EAN, HAN oder JTL-Artikelnummer. Höchstens 15 Trefferdaten, nur freigegebene Anzeigeattribute; keine SQL-Schreiboperation und kein Massenabruf.
- Amazon Catalog GET nur nach bewusstem Knopfdruck, OpenAI-Historie nur lesend nach Knopfdruck; keine automatische kostenauslösende OpenAI-Analyse. Andere Kanäle bleiben zunächst explizit nicht verifiziert.
- `LegacyReadBridge` erlaubt ausschließlich die bewährte Legacy-Live-Referenz `https://artikelmanager.hairworld24.de` als Quelle. Die Bridge arbeitet erst, wenn die private Laravel-`.env` die gültigen HTTP-Basic-Zugangsdaten für die Live-Referenz enthält; niemals im öffentlichen GitHub.
- API-Zustände und HTTP-Fehler werden angezeigt. Brutto-VK wird nicht aus Netto ohne bestätigten Steuerkontext geraten.
- Automatisierte Fail-Closed-/Gastzugriffstests unter `tests/Feature/IntelligenceConsoleSafetyTest.php`.

**Abnahme/Installation:** GitHub Actions bestätigt nur Kompilierung und Tests des Featurebranches, keine Live-/Plesk-Integration. Test-Domain **erst** auf Laravel ändern, wenn `artikelmanager.hairworld24.de` nachweislich den Legacy-V4.25-Stand bereitstellt und im angemeldeten Browser mit API funktioniert. V4.25 wurde auf **test** bereits nachweislich deployed, auf **live** liegt im Gespräch noch kein Abschlussprotokoll vor. Die vorhandene Plesk-Git-Bereitstellung auf der Testdomain darf **nicht** einfach auf den Framework-Branch geändert werden: Das Feature-Verzeichnis `platform/` könnte sonst im alten Website-Webroot liegen und private Anwendungsdateien exponieren.

**Keine neue Subdomain, keine neue MariaDB:** später private Laravel-App im bestehenden Webspace, eigene lokale SQLite-Datei nur für Laravel-Login/Anwendungsmetadaten (nicht für JTL-Daten), Plesk-Test-Webroot ausschließlich auf `platform/public`, PHP 8.4. Filament Panel Builder ist laut Hersteller für den ersten Start grundsätzlich ohne eigene Node-/npm-Buildkette nutzbar; eigene UI-Tailwind-/Vite-Anpassungen erfordern später einen Asset-Build. Initiale Installation der PHP-Pakete muss mit `/opt/plesk/php/8.4/bin/php` als **nicht-root** erfolgen. Die private Datei `.env` enthält `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://test-artikelmanager.hairworld24.de`, `HW24_ADMIN_EMAIL`, `HW24_LEGACY_URL` und die Basic-Auth-Credentials. Der Operator wird nur lokal über `artisan make:filament-user` angelegt.

**Unverändert:** alle Legacy-APIs, JTL-Wawi, WMS, MariaDB-Spiegel, KI-Archiv, Live-/Test-Plesk-Webroots, Branch `deploy/staging`, GitHub-Defaultbranch `main`, Plesk-Git-Einstellungen.
