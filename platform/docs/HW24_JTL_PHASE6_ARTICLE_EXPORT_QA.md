# HW24 / JTL Phase 6 – Vollartikel-Export Qualitätsnachweis

**Erhalten/analysiert:** 2026-11-23 · **Quelle:** Betreiber exportiert `eazybusiness` / `dbo.tArtikel` via Phase-6-HeidiSQL-Abfrage. Vollständige semikolongetrennte Datei mit 19 Spalten als private Chat-Anlage erhalten und programmgesteuert untersucht. **Keine Rohzeilen, Preise, Barcodes oder Kundendaten werden in diesem öffentlichen Repository abgelegt.**

## Belegte technische Prüfung (reiner Dateizugriff)

| Datenprüfung | Belegter Befund |
|---|---:|
| Datensätze mit 19 Spalten | 2.692 |
| Eindeutige `jtl_article_id` | 2.692 |
| Eindeutige `jtl_article_number` | 2.692 |
| Ursprungssumme `dbo.tArtikel` aus SQL 3 | 2.692 |
| Artikelstatus `Y` | 2.692 |
| Löschkennzeichen `0` | 2.690 |
| Löschkennzeichen SQL-NULL (`\\N` im Export) | 2 |
| Barcode leer | 718 |
| Barcode SQL-NULL (`\\N`) | 20 |
| Barcode gesamt fehlend | 738 (27,4 %) |
| Nichtleere Barcodes in mehr als einer Artikelzeile, Gruppen | 31 |
| Betroffene Artikelzeilen in diesen Gruppen | 67 |
| Barcode mit führender 0 | 223 |
| Maßeinheit-ID `0` (unzugeordnet) | 727 (27,0 %) |
| Lieferstatus-ID `0` | 844 |
| Einheit `ml` (ID 16) | 1.893 |
| Stück `Stk` (ID 21) | 46 |
| Gramm (ID 9) | 17 |
| Raw-Artikelbestand `nLagerbestand=0` | 2.692 |
| Leere exportierte `jtl_rowversion_hex` | 2.692 |
| `fVKNetto=0` | 20 |
| `fAmazonVK=0` | 501 |
| `fEbayPreis=0` | 1.629 |
| `fUVP=0` | 2.692 |

Beobachteter jüngster `dMod` im Dateiexport: 2026-11-23 00:05:56.90 (Zeitzone dieses SQL-Server-Felds aus der Datei nicht identifiziert). Die Daten können bereits nach Ausführung des SELECT wieder veraltet sein.

## Fachliche Grenzen und Datenqualitätswarnungen

1. **Vollständigkeit:** 2.692 Quellartikel und 2.692 eindeutige Artikel-IDs im Export; das bestätigt nur `dbo.tArtikel` zu der beobachteten Zeit, **nicht** alle Daten der 1.176 JTL-Tabellen und keinen konsistenten Multi-Table-Snapshot. Laufende JTL-Schreibaktivität kann zwischen SQL-Abfragen Änderungen verursachen.
2. **EAN als Text:** Führende Nullen sind vorhanden. Barcode ist **kein** eindeutiger Schlüssel (31 wiederholte Gruppen). Einige Barcodes enthalten führende Leerzeichen; die CSV darf Werte nicht trimmen, Excel nicht als Zahl interpretieren.
3. **Fehlende Felder:** SQL-Quellzählung `kMassEinheit IS NULL = 0` ist fachlich unzureichend: 727 Artikel haben tatsächlich `kMassEinheit=0`. Ebenso `kLieferStatus IS NULL=0` trotz 844 Artikeln mit ID 0. NULL und Sentinel 0 im späteren UI unterschiedlich behandeln.
4. **Mengen:** `fMassMenge` ist in den meisten Artikeln als z. B. 60/90 bei `ml` vorhanden. Bei einzelnen weiteren Artikeln sind positive Werte unter 1 mit Einheit `ml` beobachtet (z. B. 0,06). **Nicht** automatisch mit 1.000 multiplizieren oder einheitlich 90 erzwingen; die Quelle braucht Qualitätsprüfung, originaler Rohwert bleibt erhalten.
5. **JTL-Verkaufspreis netto:** `fVKNetto` ist als originaler Nettowert vorhanden. Bei passender bestätigter Steuerzone/Klasse kann ein **abgeleiteter** Bruttopreis angezeigt werden, immer mit Steuerkontext, Zeitpunkt und Herkunft. Die Feldwerte `fAmazonVK` und `fEbayPreis` sind keine bestätigten aktuell veröffentlichten Marketplace-Angebote.
6. **Bestände:** Dass `nLagerbestand=0` bei allen 2.692 Artikeln steht, beweist keinen leeren WMS-Bestand. Per-Warenlager-Bestände und Auftragsreservierung aus gesonderten JTL/WMS-Quellen lesen.
7. **Rowversion-Hindernis:** `dbo.tArtikel.bRowversion` wurde als NOT NULL `timestamp` gefunden, aber der SELECT-Export `CONVERT(varchar(18),...,1)` enthält bei allen Artikeln **leere Strings**. Vor jeder rowversion-Delta-Strategie durch direkte HeidiSQL-Abfrage von `DATALENGTH` und alternativen Binär/Hex-Konvertierungen klären. Bis dahin `rowversion_watermark_available=false`.
8. **Preise:** Alle Null-/0-Werte in VK-/Amazon-/eBay-Feldern bleiben Rohwerte. Keine Preisänderung, kein Sync, keine JTL-Ameise-Schreibaktion aus dieser Statistik ableiten.

## Tatsächlicher Entwicklungsschritt

- `platform/ops/jtl-heidisql-phase7-rowversion-quality-readonly.sql` enthält sieben ausschließlich lesende Abfragen für Rowversion-Format, Einheiten- und Barcodequalität sowie tatsächliche per-Warenlager-Bestandszählung. Noch nicht vom Betreiber ausgeführt.
- `platform/ops/jtl-article-csv-stage.php` ist eine **standalone PHP-CLI**. Standardmäßig validiert sie nur eine private lokale CSV-Datei; sie schreibt keine Daten. `--apply` benötigt exakte Quellprüfsumme, erwartete Zeilenanzahl und eine **neue** SQLite-Datei außerhalb der App in einem privaten 0700-Verzeichnis. Sie legt nur eine isolierte Stagingdatenbank an, keine Verbindung zu JTL/Legacy/API oder Laravel-Hauptdatenbank.
- `platform/tests/Unit/JtlArticleCsvStageTest.php` prüft Dry-Run, unveränderte führende EAN-Nullen, Rohwerte/Nulls, SHA256-Freigabe, No-Overwrite und Fehlerfall bei doppelter Artikelnummer. CI-Prüfung vor jeder Freigabe erforderlich.
- **Kein** PuTTY-, Windows-, Plesk-, TEST- oder LIVE-Deploy, keine CSV-Übernahme auf den Webserver, kein laufender 15-Minuten-Job, kein verwertbarer Rowversion-Watermark. Die bestehende TEST-Version bleibt unangetastet.

## Nächste Betreiberaktion
1. Phase-7-HeidiSQL (SQL 0–6) ausführen und nur Ergebnisaggregate bzw. max. fünf Rowversion-Testzeilen senden.
2. Sicheren privaten Transfer der vorhandenen Artikel-CSV auf die TEST-Maschine planen. Nicht in `platform/public`, nicht ins GitHub-Repository.
3. Erst **nach** CI und ausdrücklicher Bedienerabnahme mit `--input` einen Dry-Run des Staging-Skripts auf privater Maschine durchführen; danach optional isolierter `--apply`-Test mit checksum-gated neuer DB.
4. Für echten periodischen Spiegel sind stabile Snapshot-/Lösch-/Änderungsregeln, kundendatenfreie Source-Allowlist, 15-Minuten-Job und gesonderte Backend-/UI-Integration erforderlich.

## Nachtrag 2026-11-23: Rowversion-Konvertierung erfolgreich verifiziert

Der Betreiber hat nach dem ursprünglichen Phase-6-CSV einen neuen direkten SQL-Server-SELECT geliefert:

| kArtikel | DATALENGTH(bRowversion) | CONVERT(varchar(18),CONVERT(varbinary(8),bRowversion),1) |
|---:|---:|---|
| 3 | 8 | `0x0000000004C0BE42` |
| 4 | 8 | `0x0000000004C0BE32` |
| 6 | 8 | `0x0000000006122DA5` |
| 7 | 8 | `0x0000000006122DA6` |
| 8 | 8 | `0x0000000006122DA7` |

**Auswertung:** Bei diesen fünf Artikeln existieren echte, 8 Byte lange Rowversion-Daten. Die direkte frühere `CONVERT(varchar(18), a.bRowversion,1)`-Anweisung hat im konkreten Export leere Felder geliefert. **Nachgewiesene Abhilfe:** `CONVERT(varchar(18), CONVERT(varbinary(8), a.bRowversion), 1)`. Beide SQL-SELECTs in `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql` sind entsprechend berichtigt. Ein PHP-Regressionstest schützt die zwei SELECT-Varianten vor dem alten Muster.

**Einschränkungen:** Die fünf Beispielzeilen verifizieren nicht die Daten sämtlicher 2.692 Artikel und auch nicht deren historische Rowversion zum Zeitpunkt des alten Exports. `platform/ops/jtl-heidisql-phase8-rowversion-verified-readonly.sql` enthält Aggregatprüfungen, Join-Kardinalität und den lesenden Vergleich der SQL Server Funktionen `@@DBTS` / `MIN_ACTIVE_ROWVERSION()`; Betreiber-Ausführung noch offen. Die bereits erzeugte **private SQLite-Pilotdatei enthält weiterhin die alten leeren Rowversion-Felder** und darf nicht als Delta-Basis benutzt werden. Keinerlei automatische Delta-Synchronisation aktiviert.

**Sichere Folge:** Für einen zusammengehörigen neuen Artikelstand SQL 2 der korrigierten Phase 6 erneut **als neuen privaten CSV-Export** ausführen. Nicht neue Rowversion-Werte unkontrolliert in ein altes Artikeldatenabbild einfügen. Geprüfte Quelle und Ziel mit SHA256 protokollieren, ohne Geschäftsdaten in GitHub zu schreiben.
