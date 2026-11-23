# HW24 Intelligence Hub: Einkaufscheckliste – implementierter erster Funktionsstand

**Datum:** 23.11.2026. **Stand:** Entwicklungsbranch / Draft-PR #46. **Noch nicht auf TEST oder LIVE ausgerollt; SQL-Migration nicht ausgeführt; keine realen Amazon-/OpenAI-Credential-Integrationstests erfolgt.**

## Ausgangspunkt und Excel-Frage

Das private `HW24_Ameise_Starterpaket_2000_Artikel_2026-11-23.zip` ist ein **Offline-Fallback**: Excel mit 2.000 Erfassungspositionen, JTL-Referenz-CSV mit 2.692 Artikelidentitäten, manuell ausgeführter Dateiprüfgenerator. Es **importiert nichts automatisch in JTL oder die produktive Scanliste**, ist kein Belegarchiv und muss nicht parallel manuell weitergeführt werden, wenn die geschützte Web-Einkaufscheckliste eingerichtet ist.

## Verifizierte vorhandene Legacy-Funktionalität

- `api/scanliste.php` hält `hw24_scan_batches`, `hw24_scan_items` mit `scan_count` und `hw24_scan_events.client_event_key` für idempotente Wiederholungen. Pro EAN wächst die Menge bei jedem **neuen bestätigten** Scanereignis; gleiche UUID auf Retry zählt nicht erneut.
- `scanliste.html` und bestehende Amazon-/KI-/Ameise-Module bleiben vollständig unangetastet bis auf den ergänzten **Link** zur Einkaufscheckliste.
- Amazon-Katalog `api/amazon.php?ean=...` nutzt existierenden SP-API-Katalogservice und serverseitigen Cache. Historie über `api/amazon-archive.php`, `api/ai-reviews.php` und `api/historie.php`; **keine** automatische OpenAI-Abfrage in der Einkaufscheckliste.
- Keine erneute JTL-Anbindung gebaut; JTL SQL Server bleibt read-only. JTL-Verfügbarkeit/Warenlagerquelle nicht mit diesem Einkaufszähler verwechseln.

## Neu implementierte Dateien, Branch-only

| Datei | Funktion |
| --- | --- |
| `ankauf.html` | Einkaufskontakte, neue Einkaufs-ID, Auswahl historischer Ankäufe, EAN scannen/mehrfach zählen, Stückpreis + Rechnung-USt, Cent-exakte Summen, CSV-Einkaufscheckliste, aktuelle Amazon-Katalogsuche und quellengenaue historische KI-/Amazon-/Excel-Anzeigen |
| `api/purchase-intake.php` | HTTPS-/Basic-/Origin-/Approval-geschützte GET/POST-API auf die vorhandene `hw24_artikelmanager`-MariaDB, actor-isoliert; `contacts`, `purchases`, `detail`, `contact_create`, `purchase_create`, `price_set` |
| `api/purchase-intake-domain.php` | Unabhängig testbare Regeln für `ML202611-1`, `AB202611-1`, exakte Eurocent-Konvertierung und USt pro Positionsgesamtbetrag |
| `platform/ops/hw24-purchase-checklist-v1-manual-migration.sql` | **Nur nach Betreiber-Backup und bestätigter TEST-Freigabe** fünf neue Tabellen in der **bestehenden** MariaDB: Kontakte, monatliche Sequenzen, Einkaufsheader, Zeilenpreise und Preisänderungs-Audit. Keine Alter-/Drop-Statements an bestehenden Tabellen |
| `tests/hw24-purchase-domain.test.php` | Ausführbare Beispiele für Codes, Preisformate, Mengen (5 × 1,20 €), Steuer 0/7/19 und ungültige Eingaben |
| `tests/hw24-purchase-checklist.test.cjs` | DOM-/JS-Syntax-, Schnittstellen-/Autorisierungs- und No-JTL-Write-Contract-Schutz im Legacy-CI |
| `platform/ops/jtl-heidisql-phase16-supplier-contact-metadata-readonly.sql` | Ausschließlich sys.* Tabellen-, Spalten-, PK-/FK-Metadaten der tatsächlichen JTL-Lieferanten-/Kontaktquellen |

## Einkaufs-ID und Stammdaten

- Kontaktkürzel z. B. **ML**, **AB**, frei bestätigbar als 2–8 große A–Z/0–9. Nummernschema `PREFIX` + `YYYYMM` (Berlin-Lokalmonat) + `-` + fortlaufende Nummer **pro Kontakt und Monat**: `ML202611-1`, `ML202611-2`, `AB202611-1`.
- Serverseitige MariaDB-InnoDB-Transaktion auf (Actor, Kontakt, Monat) mit gesperrtem Zähler verhindert doppelte Nummer bei gleichzeitigen Anlageversuchen. Zusätzlich eindeutige (Actor, Einkaufscode) und eindeutige `batch_id`.
- **Historische Code-Kollisionen vermeiden:** Beim erstmaligen Anlegen eines Kontaktes muss der Benutzer **die zuletzt verwendete Nummer im laufenden Monat ausdrücklich angeben**: `0` falls keine, sonst z. B. `3` für ein bereits existierendes `ML202611-3`. Nur dann wird die neue Monatssequenz initialisiert. **Keine** Behauptung eines bereits synchronisierten Altankauf-Archivs. Ab folgendem Monat beginnt die neue Folge bei 1. Alte Einkaufskennungen werden nicht überschrieben.
- Kontaktfirmen-/Ansprechpartnerbezeichnung wird derzeit **lokal manuell** zum bestehenden Scan-Stapel zugeordnet. Die **echten** JTL-Lieferantenkontakt-IDs, deren Schema und Quellrechte sind noch **nicht** verifiziert; bis zur Phase-16-Prüfung weder Fremd-ID erfinden noch Namen aus Kundendaten zusammenführen. In alten JTL-Daten vorhandene Hersteller, Lieferanten-/Kontakteinträge bleiben unverändert.
- Durchgang: `Kontakt auswählen -> Einkauf anlegen -> EAN scannen -> dasselbe EAN erneut scannen -> scan_count wächst`. Ein Event-Key wird vor Versand lokal gesichert; bei Netzausfall kann genau dieser Event-Key wiederholt werden, ohne ein Stück doppelt zu zählen. Sobald der Server die Speicherung quittiert, darf bei einem nachfolgenden reinen Anzeigeproblem nicht erneut gescannt werden.

## Preisbildung, Kontrolllogik und Trennung

- Preise werden **nicht** in JTL geschrieben. Pro vorhandenem `scan_item_id`/Einkauf bleibt der einmal eingegebene Netto-Stückpreis in ganzzahligen Eurocent und der bewusst gewählte Rechnungs-USt-Satz `0 / 7 / 19` gespeichert. Bei Änderungen wird ein alter/neuer Wert mit Akteur/UTC-Zeitpunkt separat auditiert.
- Menge stammt **ausschließlich** aus dem vorhandenen `scan_count`; `Menge × Stückpreis netto`, USt auf Zeilensumme kaufmännisch auf Cent gerundet, Brutto = Netto + USt. 5 Stück × 1,20 € netto × 19 % ergeben 6,00 € netto / 7,14 € brutto. Rechnungsmethode und Vorsteuerberechtigung sind anhand des Belegs zu validieren.
- **Unbepreiste Positionen ausdrücklich zählen und kennzeichnen**, nicht 0 € vortäuschen. Ein auf der Maske hinterlegter Standardpreis wird nur für neue bislang unbepreiste Positionen eingetragen, niemals unbemerkt für vorhandene Preise überschrieben.
- Separate **Einkaufschecklisten-CSV**: Einkaufscode, Lieferant/Kontakt, EAN, JTL-Abgleich, Menge, Stückpreis, Netto-/Bruttogesamtsumme. Diese CSV ist **keine** JTL-Ameise-Artikeldaten- oder WMS-Einbuchungsdatei.
- Bestehender `scanliste.html`-Ameise-Prüfexport bleibt bis zum gezielt geprüften Sammelimport die maßgebliche Importvorbereitung. Kein direktes Importieren einer Lieferantenrechnung in Wawi, kein Bestandszugang und keine marketplace/Shopify-Aktualisierung.

## APIs und KI: geprüfte Codepfade vs. noch offene Live-Abnahme

| Quelle | Im Repository vorhanden / neuer Zugriff | Noch nicht durch diesen Commit bewiesen |
| --- | --- | --- |
| JTL | Scanliste macht GET-Abgleich gegen bisherigen Spiegel; unklar/veraltet wird `unverified` | Frischer SQL-Server-Sync, eindeutige Lieferantenkontakttabelle |
| Amazon Catalog | Neukatalog-Abfrage nur nach gespeichertem einmaligem Scan; 1,6s Abstand, begrenzte Warteschlange, vorhandener 6h-Servercache | Tatsächliches gültiges Amazon-SP-API-Token, Account-/Plesk-Antwort, Rate-Limit-Handling bei >2.000 EAN |
| OpenAI | Nur GET aus `api/ai-reviews.php`; Einzel-KI-Prüfung bleibt separat im alten Artikelmanager | Aktueller Kosten-/Modell-/Credential-Pfad und neuer KI-Live-Call |
| Historisches Excel | `api/historie.php?include_legacy=1&ean=` zeigt im neuen Screen bis zu drei archivierte Treffer mit verifizierten Label-/Zellwerten | Vollständiger neuer Einkauf-/Chargen-/Verkaufsbezug |
| JTL-Ameise | Bestehendes `assets/ameise.js` und `scanliste.html`; getrennter neuer Einkaufs-CSV-Bericht | Echter One-Click-Wawi-Import, gespeicherte Windows-Profil-IDs, Testlauf |
| Belege | Vorhandener CSV/EAN-Import der Alt-Scanliste | Neue PDF-/Bild-/Excel-Upload-OCR-Pipeline mit Virenprüfung, privaten Belegen, Quellenangabe und expliziter KI-Kostenfreigabe |

**Kein pauschaler „API erfolgreich“-Status** aus reinen PHP-/Node-/CI-Tests ableiten. Erst kontrollierte reale HTTP-Browser-Checks auf der von Nutzer freigegebenen TEST-Umgebung und vorhandenen Private Configs bestätigen erreichbare Amazon/OpenAI-Konten.

## Test- und Freigabefolge (nicht automatisch)

1. Alte Scanliste, Amazon- und OpenAI-Lesehistorie im unveränderten Bestand erneut prüfen; bestehende Daten sichern. Offene JTL-Import-/API-Probleme bleiben bis LIVE-Test konkret offen.
2. MariaDB **Backup plus Wiederherstellungsweg**; Tabellen-/Grant-/schema-drift-Check. Nur auf isolierter TEST-DB ein **manuelles** Einspielen des neuen fünfteiligen Schemas. Kein `ALTER` oder `DELETE` in bestehenden Scan-Tabellen.
3. Vorhandenen `hw24_private/scanlist-db.php`-Benutzer auf notwendige neue Rechte prüfen. Keine Credentials in GitHub; HTTP-Basic und HTTPS über denselben Vhost wie Legacy. 403 bei fehlender Auth/Origin/Approval muss demonstriert werden.
4. Echte Testabnahme: neuen Kontakt mit bewusstem bisherigem Suffix aufnehmen -> Einkauf `MLYYYYMM-N` erzeugen -> EAN fünfmal scannen -> exakt fünf Stück sehen -> 1,20 € + 19 % -> 6,00 € / 7,14 €, Retry derselben UUID darf nicht sechs Stück ergeben -> zweite Person AB und neuer Monatszähler getrennt. Vorhandenen Ameise-/Amazon-/KI-/Scanliste-Workflow parallel prüfen.
5. Eindeutige und doppelte EAN, veraltete JTL-Snapshot, leere Preise, Preisrevision, 2.000er-Mengen, Amazon-Ausfall und GET-Archiv ohne gezahlten API-Call sicher testen. Separate Quellen/keine automatischen Feldüberschreibungen.
6. Nach erfolgreichen Tests ausdrückliche Operator-Freigabe für einen TEST-Release. **Kein LIVE/TEST-Cutover und keine produktiven JTL-/WMS-Schreibvorgänge** in diesem Arbeitsschritt.
7. Erst danach die Windows-Ameise-Vorlagen und gesicherten Freigabe-Runner pro Importtyp entwickeln und 5–10 Artikel im kontrollierten JTL-Trockenlauf/Backup testen; neue Artikel und Bestandsbuchungen getrennt.
