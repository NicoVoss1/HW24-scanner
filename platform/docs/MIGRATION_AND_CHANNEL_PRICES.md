# Migration und technische Entscheidungen

## Rahmen
- **BESTEHEND:** Testversion `deploy/staging` V4.25, alte BASIC-geschützte PHP-API, JTL-MariaDB-Spiegel, Scanlisten, HW24-Curated-Entwürfe, persistente KI-Berichte und Kostenledger. Alles bleibt zunächst unverändert.
- **NEU:** Offizielle Laravel-13-Projectstruktur unter `platform/`, Filament-5-Abhängigkeit, getrennte SQL-Migrationsdateien und nur lesender `LegacyReadBridge`. Nur im **Featurebranch**, nicht in Plesk und ohne Migration alter Daten.
- **Anforderung 100.000 Artikel:** indexierte EAN/JTL/Produktkennungen, serverseitige Filter und Pagination, limitierte Jobs, Beobachtungszeit/Cache sowie getrennte Preisarten statt alle Daten in einer Tabelle im Browser.
- **Preise:** brutto/netto niemals zusammenfassen, je Kanal/Marketplace/Account/Kundengruppe/Tarif/Steuerkontext, tatsächliche Quelle und letzter Abruf. Kein unbekannter Preis wird als 0,00 € angezeigt.

## Verglichene Optionen
| Stack | Lizenz | Nutzen | Risiko |
|---|---|---|---|
| Laravel 13 & Filament 5 | Basis Open Source frei nutzbar | Eigene PHP-Domainlogik und Standard-Adminoberfläche, Filters, Tabellen, Auth/Rollen | PHP >= 8.3, Composer/npm und Deployment-Migration erforderlich |
| Laravel + Tabler | MIT Tabler | UI schnell modernisierbar | Keine fertigen serverseitigen Autorisierungs- und CRUD-Workflows |
| Pimcore Community | POCL, nur bei Bedingungen lizenzkostenfrei | Vollständiges PIM/Assets | Zusatzkomplexität und künftiger Lizenzprüfbedarf |
| Direkte PHP-Spezialimplementierung | keine Lizenz | Bestehender Code weiter nutzbar | Sehr hoher langfristiger Integrations-/Regressionstestaufwand |

**Entscheidung für Pilot:** Laravel/Filament. Kein Abschalten anderer Funktionen vor erfolgreicher Gleichwertigkeits-/Datenmigration. Voraussetzung für einen echten Plesk-Test: Prüfung der PHP-Version, PHP-Extensions, Composer, Node/npm, separater DocumentRoot ausschließlich `platform/public`, SSL und Administratoranmeldung/ACL.

## Integrationsmatrix

| System | Connector in Alt-HW24 | Pilot im Framework | Risiko/Abhängigkeit |
|---|---|---|---|
| JTL-Wawi | `GET api/jtl.php` und Qualitäts-/Optionsendpunkte | `LegacyReadBridge` GET, Quelle read-only | Brutto-VK Windows-SQL-Mapping Issue #33, Snapshot-Frische |
| JTL-WMS | JTL-Mirror-/Historienendpunkte | später separater Leseadapter | Bestand wird nicht von KI geschrieben |
| Amazon SP-API | Catalog/Keywords/Pricing/Fees/Sellers PHP | nur gezielter GET über bestehende abgesicherte API | LWA-Rollen, Quota, vertragliche Datennutzung und Cache |
| OpenAI | `GET ai-reviews.php`, `GET ai-review.php`, explizit bezahlter POST | zunächst nur GET bestehendes Archiv/Budget | keine neuen Billable Calls oder parallelen Rechecks ohne neue Freigabe |
| Shopify | ChatGPT-Connector, Admin GraphQL aktuell lesbar | separater zukünftiger serverseitiger Read-only Adapter mit eigener Auth | kundenspezifische Markets, SKU/EAN und Kanalstatus |
| eBay | JTL/eazyAuction, aber keine eigenständige verifizierte API in Platform | vorerst `unavailable` bis Sell Inventory/Offer oder erlaubter JTL-Snapshot bereitgestellt | eigene eBay-Angebotspreise ≠ Konkurrenzpreise |

## Releasegates
1. GitHub Actions verifiziert **nur Entwicklerumgebung** und isolierte SQLite-Tests.
2. Persönlich autorisierter Plesk-Runtime-Audit, ohne Installationen.
3. Eigener privater Vhost außerhalb des alten öffentlich erreichbaren Git-Webroots, nur `platform/public` zugänglich.
4. Saubere Authentifizierung/Autorisierung für Filament, kein anonymer Zugriff auf Artikel-/Preisdaten.
5. Read-only-Contracttest EAN `3474630337398` und Bestand ohne EK; keine JTL-/Shop-Updates.
6. Integrationsmatrix vier Kanäle; bekannte Werte und unbekannte Werte deutlich getrennt, Quellenaudit.
7. Dokumentiertes Backup, Tests, bewusst autorisierte Freigabe und Rollback erst vor produktivem Cutover.

Keine Passwörter, Tokens oder produktiven DB-Dumps in GitHub. Bestehende Plesk-TEST-Synchronisation darf das neue `platform/` **noch nicht** öffentlich bereitstellen.
