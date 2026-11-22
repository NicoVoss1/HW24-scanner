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
