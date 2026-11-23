# HW24 Intelligence Hub: Hersteller-/Artikelsuche, Amazon-Warteschlange und OpenAI-Kosten

**ARCHITEKTUR-AKTUALISIERUNG 24.11.2026:** Die neuere Betreiberentscheidung zur **gemeinsamen Produkt- und API-Datenhaltung für alle authentifizierten HW24-Teammitglieder** ersetzt in diesem älteren Dokument alle Aussagen, nach denen jeder Benutzer seinen eigenen KI-Produktbericht, Amazon-Katalogcache oder eine eigene EAN-Warteschlange erhalten soll. Benutzer sind nur für Berechtigungen, Einkaufsbeleg-Zuordnung und Audit zu unterscheiden. Maßgeblich: [HW24_SHARED_WORKSPACE_SINGLE_SOURCE_OF_TRUTH_2026-11-24.md](HW24_SHARED_WORKSPACE_SINGLE_SOURCE_OF_TRUTH_2026-11-24.md). Die 8 noch nicht installierten Kauf-/Amazon-Tabellen müssen mit den *neuen* workspace-eindeutigen EAN- und Throttle-Keys geprüft werden. Keine automatische Schemaumstellung.


**Entwicklungsstand 24.11.2026**, Branch `feature/hw24-mirror-audit-preflight-20261122`, Draft-PR #46.
**Kein TEST-/LIVE-Deployment, keine manuelle MariaDB-Migration ausgeführt, keine tatsächlichen Amazon-/OpenAI-HTTP-Calls gegen private Plesk-Konfigurationen nachgewiesen.**

## Vorhandene Datenquellen und neue Funktionen

| Bereich | Bestehende Quelle | Tatsächlich im Branch integriert |
| --- | --- | --- |
| JTL-Artikelkatalog | `hw24_jtl_sync_meta` / `hw24_jtl_articles` in bestehender HW24-MariaDB | `api/purchase-intake.php?op=article_search`: serverseitig begrenzte, authentifizierte **lesende** Suche nach Artikelnummer, EAN, HAN, Name und Hersteller sowie JTL-Rohfeld `netto_ek`. Max. 60 Treffer; nach Alter >45 Min ausdrücklich `stale`. Der leere Snapshot ist kein sicherer Nichtbestandsnachweis |
| Einkaufsposten | Legacy `hw24_scan_batches`, `hw24_scan_items`, `hw24_scan_events` | Scanzahl bleibt einzige Mengenquelle. `purchaseDetails` ergänzt Hersteller/HAN aus einem **genau per JTL-Artikel-ID** verknüpften, datierten Spiegel; falls nicht vorhanden, separat bezeichnete unbestätigte Werte aus Scan-Entwurf. Keine EAN-First-Result-Annahme |
| Einkaufsfilter | `purchaseDetails.items` | `ankauf.html` filtert innerhalb des ausgewählten Ankaufs nach Name, Artikelnummer, EAN, HAN, Hersteller, `resolution`, Netto-Stück-EK-Spanne; sortiert nach Name/Preis; lässt unbepreiste Positionen erkennbar und Summen des Gesamtankaufs unverändert |
| Amazon-Katalog | bestehendes `api/amazon.php`, `hw24_amazon_catalog_snapshots` und `hw24_amazon_lookup_log` | `api/purchase-enrichment.php` ist ein neuer **actor-isolierter Warteschlangenadapter**, der auf persistierte EAN-Scans verweist. Er ruft Amazon **nicht direkt** selbst auf: ein geöffneter, authentifizierter HW24-Browser-Tab arbeitet Leases über den unveränderten SP-API-Endpunkt ab |
| KI-Kosten je Anfrage | unveränderte `hw24_ai_reviews` mit `actor,ean,model,usd_cost,tokens_input,tokens_cached,tokens_output,created_at_utc` | neuer `GET op=ai_costs`: aggregiert alle gespeicherten OpenAI-Abfragen desselben Benutzers, deren EAN in dem ausgewählten Ankauf vorkommt; zeigt tatsächliche DB-Kosten/Token je Beleg und USD-Gesamtbetrag **ohne neue kostenpflichtige KI-Abfrage** |
| Historische Quellen | `api/ai-reviews.php`, `api/amazon-archive.php`, `api/historie.php` | Pro EAN werden weiter alle drei Originalarchive gelesen, zusätzlich neue Amazon-Warteschlangenereignisse. Quellen/Zeiten/Actor und OpenAI-USD-Kosten werden getrennt dargestellt |

## Dauerhafte Amazon-Abfragewarteschlange

Neue **ausschließlich additive, manuell freizugebende Tabellen** in `platform/ops/hw24-purchase-checklist-v1-manual-migration.sql`:
- Bisherige fünf Einkaufstabellen bleiben additive Grundlage: `hw24_purchase_contacts`, `hw24_purchase_sequences`, `hw24_purchase_headers`, `hw24_purchase_costs`, `hw24_purchase_cost_audit`.
- Neu `hw24_purchase_amazon_jobs` für `created_by`, EAN, Status, `requested_by`, angefragt/zuletzt versucht/abgeschlossen, Lease, Attempts, nächste erlaubte Wiederholung, verifizierte Snapshot-ID, Browserangabe über Cache/Live; `UNIQUE(created_by,ean)`.
- Neu `hw24_purchase_amazon_events` für chronologische Actor-/Zeit-/Status-Nachweise der Vormerkung, Leasing, Bestätigung, Verzögerung, Blockierung und explizitem Neuanlauf.
- Neu `hw24_purchase_amazon_throttle` für per DB-Transaktion geschützte **mindestens 10 Sekunden** zwischen neuen Lease-Vergaben desselben angemeldeten Benutzers. Zusätzlich 11 Sekunden Browser-Pause.
- **Ergebnisverifizierung:** Der Browser kann einen Auftrag nur dann als `completed` bestätigen, wenn eine tatsächliche Amazon-Snapshot-ID mit genau der geleasten EAN und Amazon.de-Marketplace `A1PA6795UKMFR9` in der bestehenden MariaDB mit HTTP 200 nachgewiesen wird. Der Cache-/Live-Status stammt weiterhin aus dem Browserbericht; Snapshot und Herkunftszeit sind gesondert belegt. Kein vorgetäuschter Echtzeitseller-Preis.
- `enqueue`: nur für EAN, die dem eigenen vorhandenen Scan-Stapel zugeordnet ist. `enqueue_batch`: fortsetzbar, maximal 100 Scanpositionen je POST, standardmäßig nur `new_candidate` und `unverified`. `queue`: Status der aktiven Einkaufspositionen, maximal 3.000 Jobs. `events`: gesicherter Prüfverlauf einer EAN. `lease`, `ack`, `requeue` mit Auth/Origin/Approve, Transaktions-/Lease-Token-Kontrolle.
- **Fehlergrenzen:** maximal vier Versuche; Amazon-Rate-Limit -> 30 Min Retry, andere Fehler exponentielle Pause; nach vier Fehlschlägen `blocked`, nur ausdrücklich manuell erneut vormerken. Ausgelaufene vierte Leases werden beim nächsten Queue-Lease blockiert und auditiert. Kein unbegrenztes Blind-Retry, kein 2.000-Call-Spike.
- **Wichtig:** Wenn der Browser schließt, persistiert die Warteschlange, aber es gibt **noch keinen unbeaufsichtigten Serverworker/CRON**. Wiederaufnahme beim nächsten Öffnen oder auf ausdrücklichen Knopfdruck. Echten Serverworker erst mit separat freigegebenem Sicherheits-/Kosten-/Quota-Konzept.
- Die Legacy-`scanliste.html` macht weiterhin ihre eigenen Amazon-Katalog-Anfragen. Der neue Warteschlangen-Akteur ist **nur für die neue Einkaufscheckliste** nachvollziehbar; alte allgemeine Amazon-Suchlogs haben bisher nicht für jede historische Anfrage ein bestätigtes Actor-Feld. Für lückenlose Auditierung alter Endpunkte ist eine zusätzliche, separat getestete Erweiterung erforderlich.

## Preis- und Herkunftslogik

- `purchaseDetails.unit_net_cents`: tatsächlicher beim Ankauf dokumentierter Netto-EK je Stück. USt pro Position nach Beleg, Quelle: eigener Preis-/Änderungsaudit. `netto_ek` aus dem JTL-Artikelspiegel ist ein **anderer und möglicherweise älterer JTL-Quellwert**, niemals als Ankaufspreis überschreiben.
- `hw24_ai_reviews.usd_cost`: archivierter, vom vorhandenen KI-Service erfasster USD-Kostenwert, **keine** berechnete Live-Preisliste oder Abrechnungsgarantie. Bei allen abgefragten EANs werden auch **frühere, vor dem Ankauf gespeicherte** KI-Abfragen desselben Actors mitgezählt; explizit kennzeichnen.
- `hw24_amazon_catalog_snapshots` enthält **Kataloginformationen**, nicht automatisch den tatsächlichen aktiven Amazon-Angebotspreis des eigenen Verkäuferkontos.
- Fehlende Hersteller/HAN bleiben offen; keine automatisch erfundenen Werte. EAN-Doppelbelegung in JTL kann mit JTL-Artikel-ID aufgelöst werden; niemals stillschweigend einen Artikel anhand einer nicht eindeutigen EAN überschreiben.
- Wenn vorübergehend mehrere Benutzer Ankäufe unter demselben Basic-Konto durchführen, ist der gespeicherte Actor nur dieses **gemeinsame Konto**, keine reale Einzelperson. Für persönlichen Abfragenachweis werden getrennte authentifizierte Konten benötigt.

## Installations-/Regressionstest-Gates

1. **Noch nicht** in MariaDB anwenden. Aktuelles Schema der bestehenden `hw24_artikelmanager`-DB, DB-Backup inkl. Restore-Test und Berechtigungen überprüfen. Die drei Queue-Tabellen sind ausschließlich Ergänzungen der separat bereitzustellenden neuen Einkaufstabellen.
2. Authentifizierter TEST gegen die bestehende Legacy-Anwendung (alte Scanliste/EAN-Zähler, JTL-Mirror, OpenAI-/Amazon-Archive) zunächst **lesend**. Neue Schemamigration nur nach ausdrücklicher Freigabe auf geschützter isolierter TEST-Datenbank.
3. API-Tests: fehlende Basic-Auth/HTTPS/Origin/Approve -> 403; fremde `purchase_id` -> 404, CSV- und Batch-Grenzen, doppelte EAN im selben Job -> nur 1 Warteschlangenposition, mehrere Browser-Tabs -> DB-Actor-Drossel, Lease nach 180s und Retries, keine unautorisierten Wawi-/WMS-Schreiboperationen.
4. Echte Browserabnahme mit Test-EAN: JTL-Suche (aktuell und stale), Hersteller-/Preisfilter, 5× gleicher Barcode -> 5 Stück, 1,20 €/19 % -> 6,00 € netto/7,14 € brutto, Amazon-Auftrag erscheint genau einmal, Source-/Actor-/Snapshot-ID und Fehler/Retry korrekt, historisches OpenAI-`usd_cost` ohne OpenAI-Live-Call sichtbar.
5. Belastungsprobe mit z. B. 50, 200, danach 2.000 **reinen Queue-Vormerkungen** (ohne unmittelbaren API-Massenabruf). Echte SP-API-Aufrufe nur separat gegen Quotas und Credentials. Test Domain nur nach Prüfung von Plesk-DocumentRoot, Auth und Legacy-Regression; **niemals** Framework in alten Webroot deployen.
6. Danach separater KI-Dokumentimport (PDF/Bild/XLSX/CSV mit Virenscan, Belegquelle, Mengenprüfung, ausdrücklichem OpenAI-Kostenopt-in), JTL-Ameise-Massenexport und Windows-Wawi-Import mit eigenem Test-/Rollback-Prozess.

## Relevante Dateien

`ankauf.html`, `api/purchase-intake.php`, `api/purchase-enrichment.php`,
`platform/ops/hw24-purchase-checklist-v1-manual-migration.sql`,
`tests/hw24-purchase-checklist.test.cjs`,
`tests/hw24-purchase-enrichment.test.cjs`,
`.github/workflows/hw24-v425-intelligence-test.yml`.
