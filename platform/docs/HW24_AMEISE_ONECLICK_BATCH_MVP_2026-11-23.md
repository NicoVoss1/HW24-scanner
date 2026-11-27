# HAIRWORLD24 Intelligence Hub: 2.000 Artikel per Scan-Stapel und JTL-Ameise

**Stand:** 2026-11-23. **Status:** Spezifikation und isolierter privater Offline-CSV-Prüfprototyp erstellt; **keine** Änderung an JTL-Wawi, WMS, Plesk, TEST, LIVE oder produktiven APIs.

## Leitentscheidung

Der vorhandene Artikelmanager ist keine Wegwerf-Entwicklung: `assets/ameise.js` kann bereits **einen** Artikel als Ameise-Entwurf vorbereiten, und `assets/scanflow.js` unterscheidet vorhandene JTL-Treffer, Mehrfachtreffer und mögliche Neuanlagen. Die vorhandene Legacy-`scanliste.html` mit `api/scanliste.php` stellt Stapel bereit; im separaten, noch nicht ausgerollten Filament-Pilot `ScanWorkbench` existieren entsprechende getrennt geschützte Scan-Stapel-Anzeigen/-Aktionen. Der nächste Schritt ist ein **gesicherter Sammelworkflow** auf diesen Datenquellen, keine parallele dritte Scan-Datenbank.

**Ziel:** An ca. 2.000 bislang nicht erfassten physischen Produkten EAN einscannen, Daten aus belegten Quellen vorschlagen, eindeutige JTL-Artikelnummer feststellen oder unter kontrollierter Nummernkonvention vorschlagen, Preise/Steuer/Füllmenge und physischen Lagerplatz prüfen, Entwürfe einzeln/für Stapel freigeben, getrennte Ameise-CSV-Dateien erzeugen und später aus dem geschützten Hub eine beaufsichtigte Windows-JTL-Ameise-Importaktion auslösen können.

## Reale Ausgangsbasis

- SQL-Server-`eazybusiness`: korrigierter Artikel-CSV-Export **2.692** Artikel, **19** Felder, **2.692** gültige Artikelstamm-Rowversions. Privater, separater SQLite-Dreiquellenpilot: **2.692** Artikel, **12.717** Warenlagerzeilen, **2.692** Zentralbestandszeilen, keine transaktionskonsistente Gesamtaufnahme.
- Barcodequalität: **738** Artikel ohne EAN; **31 verschiedene mehrfach belegte Barcodes**, daher EAN niemals ohne Einzelfallprüfung als eindeutigen JTL-Upsert-Schlüssel verwenden.
- `dbo.tlagerbestand` hat Originalwerte `fLagerbestand`, `fVerfuegbar`, `fInAuftraegen`, `fProduktionGesperrt`, `fVerfuegbarGesperrt`; beobachtete Gleichung `Bestand - Produktionssperre - InAuftraegen = Verfügbar` bei **2.692/2.692** aktuellen JTL-Zentralbestandszeilen. **Nicht** automatisch Marktmenge/Shop-Verfügbarkeit daraus berechnen.
- **32** JTL-Artikel ohne Warenlagerzuordnung (953 Rohbestand), **12** Lager-only Artikel-IDs (24 Rohbestand). Kein Löschen oder stilles Lager-Umbuchen.
- Es existiert ein echter vom Betreiber bereitgestellter JTL-Ameise-Export mit **872 Spalten** (siehe Kommentar in `assets/ameise.js`). Alle drei Ameise-Importprofile müssen am tatsächlich geöffneten JTL-Wawi 2.2.2.0 anhand der Zielspalten-Zuordnung bestätigt werden. JTL-Quellpreise `fAmazonVK`/`fEbayPreis` sind **keine** bestätigten Live-Angebotspreise.

## In fünf Arbeitsschritten zum MVP

1. **Scan-Stapel erweitern, ohne Verlust:** `Stapel -> Scanposition -> Artikelstatus -> Feldvorschläge -> Freigabe`; Mehrfachscan einer EAN darf Mengen nicht unbemerkt duplizieren. Alte Scanliste, Originaldaten und Rechte weiterverwenden. Lagerbestand ist eine eigene Quelle. Etwa 2.000 Zeilen performant filtern, sortieren und stapelweise bearbeiten.
2. **JTL-Match strikt:** `EAN` inklusive Prüfziffer kontrollieren; auf `Artikelnummer`-Eindeutigkeit gegen **aktuelle** JTL-Referenz prüfen. 0 EAN-Treffer = `NEU_KANDIDAT`, nicht bewiesener JTL-Nichtbestand. 1 Treffer = `BESTEHEND_KANDIDAT`, mehr als 1 = `KLÄRFALL`. Identifizieren und aktualisieren **nur per eindeutiger Artikelnummer**, nicht EAN.
3. **Anreichern mit Belegen:** Lieferanten-/Herstellerdaten, Produktlabel und bereits vorhandene lokale Quellen bevorzugen; EAN-Katalog- oder KI-Vorschläge nur mit Feldquelle, Zeitstempel und Freigabe. Keine erfundene ASIN/HAN, Steuerklasse, MHD, Charge oder Hersteller; Netto/Brutto getrennt, Kosteneingabe nicht mit GLD verwechseln. Einheits-/Inhaltsangabe JTL-originaltreu.
4. **Batch-Prüfexport:** Felder in drei voneinander getrennte **prüfbare** CSV-Typen `NEU`, `UPDATE`, `WMS` aufteilen. Bei UPDATE möglichst nur **tatsächlich gesetzte** Felder je Teilcharge mappen; leere Felder dürfen Bestand/Preis/Texte nicht überschreiben. `EK Netto` aus `Kosten` nur nach freigegebener Feldzuordnung. Nicht in Stammdaten-CSV automatisch Lagerbestand importieren; WMS hat Lagerplatz + Menge, nach bestätigter Neuanlage. Original-Zeilenstatus und Fehlerberichte (doppelte EAN/ArtNr, fehlende Pflichtwerte, veralteter Snapshot) zurück ins Hub.
5. **Ein-Klick-Import erst nach Freigabegates:** In JTL-Ameise einmalig *gespeicherte* GUI-Importvorlagen mit echten `IMP...`-IDs für NEU/UPDATE/WMS einrichten; realen **JTL-Trockenlauf** auf dem autorisierten Windows-Rechner prüfen; Datenbanksicherung, Worker-/Betriebszustand, Rechte und nachvollziehbare Importberichte. Perspektivisch ein abgesicherter Windows-Runner mit Job-ID, Freigaben und Idempotenz statt HTTP->`cmd.exe` oder direktes Webserver-Schreiben in eazybusiness. **Keine** Passwörter in Repo/Logs/Kommandozeilen.

## Offline-Prototyp, erzeugt am 23.11.2026

Ein ausschließlich **privates** ZIP-Paket wurde im aktuellen Gespräch erstellt, **nicht** nach GitHub übertragen:

- `HW24_Scan_und_Ameise_2000_Artikel.xlsx`: fünf Tabs, 2.000 leere Eingabezeilen und 2.692 private JTL-Referenzeinträge.
- `HW24_Scan_Erfassung_2000.csv`: UTF-8 BOM, Semikolon, 2.000 leere Scanpositionen.
- `HW24_JTL_Referenz_2692.csv`: nur private ID-/Artikelnummer-/Barcode-/Status-/Bestandsreferenz; **nicht live**.
- `HW24_1Klick_Pruefexport.py` mit Windows-`HW24_1Klick_Start.cmd`: Offline-Draftgenerator mit explizitem `Aktion=NEU|UPDATE`, `Stammdaten_Freigabe=JA`, GTIN-Prüfziffer, EAN-/SKU-Dublettenprüfung und Importtrennung. Neue Artikelnummer wird nur bei eindeutiger neuer GTIN als `HW24-<GTIN>` **vorgeschlagen**; Konvention vor echtem JTL-Import freigeben. JTL-Artikel-Updates sind nach exakt besetzten Feldsets gruppiert, um leere Werte gar nicht in die Update-CSV aufzunehmen. WMS-Neuanlagen bleiben `NICHT_IMPORTIEREN` bis zu JTL-Anlage; die WMS-Vorbereitung für vorhandene Artikel bleibt ein eigener Freigabevorgang.
- `README_HW24_AMEISE_START.md` mit Bedienung/Sicherheitsgrenzen. **Python 3** wird unter Windows benötigt, sofern nicht später serverseitig integriert.
- Kontrolltest gegen synthetische Scanzeilen: 1 erlaubter NEU-Entwurf, 1 bestehender UPDATE-Entwurf, 1 WMS-Entwurf für einen vorhandenen Artikel, 1 WMS-Zurückstellung für einen neuen Artikel und 4 blockierte Fälle; kein JTL-/API-Zugriff.
- Das **ist nur ein lokaler Ein-Klick-Datei-Prüfexport**, noch **kein** kompletter Ein-Klick-Import und **keine** Installation im alten Artikelmanager. Datenquellen sind zeitversetzte Snapshots.

## Test- und Rollout-Gates (verbindlich)

- **Pilot 5 bis 10 Artikel**, erst dann 50, 200 und nur nach nachgewiesenem Rollback und fehlerfreien Prüfberichten die große 2.000er-Serie. Jeder echte Import erzeugt einen nachvollziehbaren Import-/Update-/Fehlerbericht.
- Vor dem Pilot: tatsächlich eindeutige Artikelnummern und Hersteller/Bezeichnungen/Steuerklasse/VK verifizieren, JTL-Backup plus Wiederherstellungsweg prüfen, richtigen Mandanten/Importer auswählen und JTL-Ameise-Trockenlauf bestätigen. Worker-Regeln aus offizieller JTL-Dokumentation beachten.
- **Nicht** Importoptionen benutzen, die nicht enthaltene Lieferantenartikel deaktivieren, alte Artikel-/Staffelpreise löschen oder vorhandene Bestände überschreiben; keine automatischen Amazon-/eBay-/Shopify-Veröffentlichungen.
- Zuerst nur eine neue, private Filament-Testansicht mit den **existierenden** Scan-Stapeln und reinem CSV-Prüfexport; niemals alte `scanliste`-Funktionen abschalten oder `deploy/staging`/LIVE überschreiben. UI-Fachbegriffe mit Original-JTL-Spalten belegen. CI plus Browser-/E2E-Abnahme und ausdrückliche Betreiberfreigabe vor TEST, und separate Freigabe vor LIVE.

## Offizielle Referenzen

- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikeldaten-importieren/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/aufbau-der-import-funktion/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/bestaende-in-jtl-wms-hinzubuchen/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/cmd-line-version/
