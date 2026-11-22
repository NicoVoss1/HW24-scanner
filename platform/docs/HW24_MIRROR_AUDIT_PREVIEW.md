# HW24 JTL-Vollinventur, Spiegelstrategie und Abfragehistorie
Stand: 22.11.2026. **Entwurf / TEST. Kein LIVE-Deploy, keine Quell-Datenbankänderung.**

## Ausgangslage aus PuTTY
Der geschützte TEST-Server wurde per gesichertem Fast-Forward von `0d8bb9b...` auf
`5eae5be7e8dc05298dba56ed37267cfc76622d20` aktualisiert, mit
integritätsgeprüftem SQLite-Backup. Das ist **kein** Nachweis für einen
erfolgreichen Browser-E2E-Test, keinen LIVE-Endpunkt-Release und keine
Produktionsfreigabe. Die vorhandene `api/amazon-archive.php` liest
**Katalogsuch-Snapshots**, keine historischen Händlerpreise.

## Zielarchitektur
1. **JTL-Quellsystem**: Windows / SQL Server / `eazybusiness`, **nur lesen**.
   Zuerst DB-Version, Größe, Tabellen-/Spaltenkatalog, PKs/FKs und
   Berechtigungen mit `platform/ops/jtl-schema-inventory-readonly.sql`
   inventarisieren. Inventur-Ergebnisse privat speichern; keine Passwörter
   und keine Datensätze nach GitHub hochladen.
2. **Backup**: Unveränderliche, wiederherstellbare SQL-Server-Vollsicherung
   nach JTL-/SQL-Server-Standard, separat von der Analyseplattform.
3. **Analytics Mirror**: Nach Klassifizierung der Tabellen eine initiale
   konsistente Vollübernahme der freigegebenen Geschäftsdaten in eine **private**
   Datenbank. Danach alle 15 Minuten inkrementeller Sync auf Basis von
   geeigneten Änderungsschlüsseln / Change Tracking / geprüftem CDC oder
   notfalls per geprüften Wasserzeichen. Deletions, Tombstones,
   Wiederanlauf, Schema-Drift und atomare Run-IDs berücksichtigen.
   Change Tracking/CDC und Edition/Version **erst prüfen, niemals
   ungeprüft auf JTL aktivieren**. Nächtlicher Abgleich von Anzahl, Hash
   oder fachlicher Vollständigkeit statt jede Viertelstunde kompletter Kopie.
   Große Tabellen, Belege, Kundendaten, Tokens und Logs werden erst nach
   Datenklassifizierung und Zweck-/Löschregeln übernommen. Kein öffentliches
   1433/TCP; nur vom gesicherten Windows-Host nach außen pushen.
4. **Quellenebene**: Originalwert, Tabellen-/Spaltenherkunft, Mandant,
   `source_id`, Artikel-ID, GTIN/EAN, Erfassungszeit, Änderungszeit,
   Import-Run, Vollständigkeit und Quelle getrennt halten.
   Offizielle JTL-Brutto- und Netto-Preisarten nicht vermischen.
   Unterschiedliche Verkäufer-/Marktplatzkonten eindeutig zuordnen.
5. **App**: Laravel/Filament liest nur fertige, bestätigte Datenstände.
   Schreibwege Richtung JTL bleiben getrennte, ausdrücklich freigegebene
   Import-/API-Prozesse. Bereits vorhandene APIs, WMS- und Scan-Workflows
   bleiben unverändert.

## Amazon-Preis- und Anfragehistorie (separat, noch NICHT aktiviert)
- Jeder **künftig instrumentierte** Request erhält Metadaten
  (Quelle, Operation, verifizierter Artikel, Actor-ID, Startzeit, Resultat,
  HTTP-Status, Laufzeit, Trigger, Request-ID), keine Passwörter, Tokens,
  Roh-URLs, Kunden-PII oder pauschal vollständigen API-Antworten.
- `query_audit_events` ist eine nur in der isolierten Plattformdatenbank
  vorgesehene Tabelle, `HW24_QUERY_AUDIT_ENABLED=false` ist die
  Ausgangseinstellung. `QueryAuditRecorder` ist ein noch **nicht an
  die Legacy-/Amazon-Abfragen angeschlossener** Baustein. Vor Aktivierung
  müssen Migration, Auth-Kontext, Tests, Rollback, Zugriffskontrolle
  und Audit-Vollständigkeit geprüft werden.
- Eine **Anfragehistorie ist keine Preishistorie**. Ein späterer,
  eigener Preisbeobachtungs-Speicher braucht ASIN, marketplace, Seller-ID
  nur bei zulässiger Verarbeitung, Zustand, Artikel-/Versand-/Gesamtpreis
  mit Währung, Prime/FBA, BuyBox, Zeitpunkt und Quellenbezug.
  Gleiche Observationen können dedupliziert werden; `observed_at` bleibt
  unverändert. Fehlende Verkäufer oder API-Limits als Lücke kennzeichnen.
- Amazon `getItemOffers` gibt nur einen **Ausschnitt** an Angeboten und
  laut offizieller API einen Default von 0,5 Requests/Sekunde; ein
  nächtlicher Vollscan bedarf Quotenplanung, Queue, Backoff und
  gültiger ASIN-Zuordnung. Für aktiv angebotene ASINs später
  `ANY_OFFER_CHANGED` statt unnötigem Polling prüfen.
  **Keine** automatischen Anfragen, kein Bulk-Nachtjob, keine API-Änderungen
  oder Preisarchivierung im jetzigen Schritt.
- Amazon-SP-API-DPP/AUP vor Speicherung und Aufbewahrungsdauer prüfen.
  Nicht-PII nur so lange wie tatsächlich erforderlich; PII unterliegt
  zusätzlichen strengen Regeln. Unbegrenzte Aufbewahrung aller API-Rohdaten
  wird nicht zugesichert.
- Gewünschte UI-Zeiträume: Heute, 7, 14, 30, 60, 90 Tage, 3, 6, 9, 12
  Monate, Gesamtbestand **soweit zulässig gespeichert**. Eigener VK
  und Konkurrenzpreise als getrennte Zeitreihen, nur explizit bestätigte
  Händlerkonten, Preise stets mit Marketplace/Währung.

## Pflicht-Abnahme vor weiteren Deploys
- SQL-Edition, Datenbankgröße, Schema- und Volumenanalyse; Erreichbarkeit
  und technisches Backup/Wiederherstellung testen.
- JTL vs. Plattform-Abgleich mindestens 20 unterschiedliche Artikel inkl.
  Varianten, mehrdeutiger GTINs, Steuer-/VK-Brutto-/Netto,
  Lieferstatus, WMS-Fachbeständen und eigenen Kanälen.
- Feature-/Integrations-/Browser-Tests, Tests für Leerlauf, Duplikate,
  Tombstones, Fehler/Retry, 429 und Auth.
- Amazon-Genehmigungen, Speicherfrist, tatsächliche Limits und
  Live-Abfrageberechtigung prüfen; **vorher keine Langzeit-Preisarchivierung**.
- Separate Freigabe für produktive Scheduler, Migrationen und LIVE.
- Kein blindes Merge aus den aufeinander aufbauenden offenen Draft-PRs.
