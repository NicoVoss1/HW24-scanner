# HW24 – Aktueller Projektstand und Wiedereinstieg

**Stand:** 24.11.2026 · **Projekt:** HAIRWORLD24 / FB24 / HW24-scanner  
**Kanonisches vollständiges Journal:** [HW24_ARBEITSJOURNAL.md](HW24_ARBEITSJOURNAL.md)  
**Repository:** [NicoVoss1/HW24-scanner](https://github.com/NicoVoss1/HW24-scanner)  
**Tracking-/Kundenservice-Pilot:** [Draft-PR #49](https://github.com/NicoVoss1/HW24-scanner/pull/49)  
**Branch PR #49:** `feature/hw24-customer-service-tracking-platform-20261124`  
**Basis für PR #49:** `feature/hw24-mirror-audit-preflight-20261122` (PR #46; separat laufende andere Funktionsstränge)

## Verbindliche Anweisung für jede Fortsetzung

**Vor jeder neuen Entwicklung** dieses Dokument, das Ende von `docs/HW24_ARBEITSJOURNAL.md`, den aktuellen PR und seinen CI-Status erneut prüfen. Nicht auf alte Chatannahmen über Server-HEAD, APIs, Daten oder Freigaben vertrauen.

**Nach jedem Arbeitsschritt** Journal-Eintrag mit Datum, ID, Umfang, Umgebung, konkretem Commit/PR, Tests/Nachweis, offenen Punkten und exakt nächstem Schritt ergänzen. Dieses Statusblatt ebenfalls aktualisieren, wenn sich der Gesamtstand ändert. So ist ein neuer Chat unabhängig vom bisherigen Gesprächsverlauf möglich.

## Zielbild (vom Betreiber vorgegeben)

- **Ein gemeinsamer Artikel-/Datenbestand für alle autorisierten Hub-Nutzer**, kein Benutzer-spezifischer Artikeldatenspiegel.
- Daten aus JTL, WMS, Shopify, eBay, Amazon und genehmigter KI-Recherche fließen in einen **gemeinsam genutzten, nachvollziehbaren Spiegel**. Jede Quelle hat Herkunft, Erfassungszeit, Frische und Kennzeichnung; fehlende Daten bleiben unbekannt. Externe Abfrage-Caches sind innerhalb zulässiger Datenschutz-/API-Vertragsgrenzen gemeinsam nutzbar, keine bezahlten Doppelabfragen je Benutzer.
- Benutzerbezogen wird beim Admin vor allem **wer / wann / welchen Vorgang oder Abruf freigegeben hat** protokolliert. Berechtigung und Datenschutz gelten weiterhin, insbesondere für Kundenkommunikation.
- **Kundenservice-Ziel:** Amazon-Nachrichten/Screenshots erkennen, Bestellung zuordnen, gesicherte Paket-/Bestellinformationen anzeigen, Antwort auf Deutsch formulieren, nach manueller Freigabe über einen zulässigen Kommunikationskanal versenden. Keine erfundenen DHL-Status, Erstattungen, Datumsangaben oder Zusagen.
- JTL-Wawi auf SQL Server bleibt ursprüngliche Quelle; zuerst **ausschließlich lesen**. Keine direkte DB-Schreibaktion oder Umstellung bestehender Anwendungen ohne eigene Freigabe.

## Belegter Iststand – nicht mit Livebetrieb verwechseln

| Bereich | Was tatsächlich vorhanden ist | Was noch fehlt |
|---|---|---|
| JTL → Paket → Scan | Verifizierter SQL-JOIN `Versand.lvJtlTrackingPakete` → `Shipping.tPackage` → `Shipping.tState`; echter Beispielvorgang lieferte 7 Ereignisse | automatischer, inkrementeller und vollständig geprüfter Sync |
| SQL-Export | `platform/ops/jtl-heidisql-phase18-tracking-export-readonly.sql` | privater Realexport-Abnahmetest und Pagination statt `TOP(50000)` |
| Privater Tracking-Snapshot | CSV-Validierung/Import in neue isolierte SQLite-Datei, Prüfsumme und Zeilenzahl | dauerhafte zentrale Synchronisation und kontrollierter Snapshot-Wechsel |
| Laravel/Filament | `/admin/kundenservice` im Featurebranch, Scan-Historie, Vorlage, Screenshot Upload/Paste | serverseitiges Test-Deployment und Browser-End-to-End |
| KI | optionaler Entwurfs-Endpunkt mit Server-Schalter, privatem Key und explizitem Benutzerklick | realer erlaubter API-Test, Kosten-/Datenschutzprüfung, Screenshot-Order-Erkennung |
| Amazon-Nachrichten | manuell kopierbarer Antwortentwurf | autorisierter Amazon-Nachrichtenversand; allgemeiner Inbox-Abruf ist nicht zugesichert |
| CI | 85 Laravel-Tests / 500 Assertions zuvor bestanden; separate Support-Staging-Tests liefen zuvor grün | CI nach jeder neuen Änderung erneut auf aktuellem PR-HEAD prüfen |
| Deployment | keine produktive Freigabe oder Live-Installation dieses Moduls | geschützter TEST-Smoke nach gesonderter Abnahme |

### Wichtige technische Besonderheiten

- Der **Abholort kann ausschließlich in `Shipping.tState.cText`** stehen; `cLocation` kann leer sein.
- `Shipping.tState.dDate`: genaue Zeitzonensemantik nicht offiziell abgenommen. Der Pilot kennzeichnet Rohzeit und vermeidet unbelegte Uhrzeitbehauptungen.
- Snapshot-Status wird anhand seines Importzeitpunkts auf Alter geprüft. **Ein alter Snapshot ist kein aktueller DHL-Livebeleg.**
- Im CSV-Stager wurde am 24.11.2026 ein Fail-closed-Cleanup-Fix für ungültige Zeilen eingebracht; Negativtest für zurückbleibende temporäre Daten ergänzt. Bei Fortsetzung aktuellen CI-Erfolg erst neu bestätigen.
- Während der Pilotphase ist eine private SQLite-Testdatei **kein Beleg** für eine etablierte zentrale, von allen Benutzern aktualisierte MariaDB-Spiegelung.
- **Keine** echten Kundendaten, Passwörter, Tokens, vollständigen Trackingnummern/Bestellnummern oder private CSV/DB-Backups in GitHub committen.

## Reihenfolge der nächsten Entwicklungsschritte

1. **Jetzt:** PR #49 nach jüngsten Änderungen prüfen; PHP-/Import- und Laravel-CI müssen auf demselben aktuellen Commit grün sein. Temporärdatei-Cleanup bei ungültigem CSV nachweisen.
2. **Als nächstes:** Mit einem **privat gespeicherten** JTL-Export einen echten End-to-End-Vergleich gegen JTL und DHL durchführen (Mehrpaketfall, Filiale, keine Scans, Retoure, Datum). Keine sensiblen Rohdaten im Chat oder Git speichern.
3. **Danach:** dauerhaften zentralen read-only JTL-Tracking-Spiegel entwerfen/implementieren, mit einem technischen Sync-Job statt individueller User-JTL-Abfragen; kontrollierte Deltas, Wiederanlauf, Delete-/Retoure-Handling, Freshness und Audit.
4. **Danach:** serverseitiges TEST-Deployment mit Rollen, Dateirechten, Retention und Browser-End-to-End. Keine Umstellung des bestehenden Live-Webroots.
5. **Danach:** Screenshot-Erkennung und KI-Entwurf gegen echte, autorisierte Daten überprüfen. OpenAI-Kosten nur explizit; Quellen-/Datenschutz-Freigaben beachten.
6. **Zum Schluss:** Amazon Messaging je Aktion/API-Berechtigung prüfen, menschliche Vorschau/Freigabe vor Versand und Absendestatus-Audit. Keine eigenmächtigen automatischen Erstattungen.

## Arbeitsregeln für jeden weiteren Chat

- **Wahrheit vor Geschwindigkeit:** „im GitHub-Branch“, „CI bestanden“, „auf TEST installiert“, „im Browser abgenommen“, „auf LIVE“ streng unterscheiden.
- **Ein sicherer, überprüfbarer Schritt nach dem anderen.** Kleine Branch-Commits, Tests und Journal parallel.
- **Keine Datenverluste:** JTL-/HW24-Livebestände, bestehende Artikelmanager-APIs, Shopify/Amazon-Verbindungen, Scanner und alte Workflows nicht ändern oder ersetzen, solange ihre neue Entsprechung nicht nachweislich funktioniert.
- **Kein ungefragtes Deploy:** jede Migration, Domain-Umstellung, Schreibfreigabe und Amazon-Nachricht benötigt eigenen kontrollierten Freigabeschritt.
- **PR- und Journal-Pflege sind verpflichtender Bestandteil jeder künftigen Codeänderung.**
