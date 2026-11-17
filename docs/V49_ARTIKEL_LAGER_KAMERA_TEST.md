# HW24 v4.9 TEST · Artikelübersicht und Lagerplatzübersicht

Stand: 17. November 2026. Alle Änderungen ausschließlich im Entwicklungsbranch
\`improve/encoding-dashboard-sync-20261115\`. Keine JTL-Schreibzugriffe,
kein Merge nach main und noch kein produktiver Ameise- oder WMS-Import.

## Ausgangslage aus der neuen CSV
Die Datei \`mit_ean_1_Artikelmanager DB Mapping.csv\` enthält die EAN
\`4015600040482\` in einem einzigen Datensatz mit 14 Spalten:
- JTL Artikel-ID 2594; Hersteller \`Wella\`; Warengruppe \`Haarfarben\`
- Artikelkosten 1,50 EUR aus \`Artikel.tArtikelkosten.fArtikelkosten\`
- Ø EK 1,50 EUR; letzter EK 0
- Steuerklasse \`Normaler Steuersatz\`, Versandklasse \`standard\`
- Artikelgesamtbestand, verfügbar und in Aufträgen je 0
- **Kein numerischer Steuersatz und keine WMS-Lagerplatzposition**.
Die CSV belegt daher weder eine einzelne Steuerkonfiguration noch
konkrete Fachmengen. Nichts davon darf aus \`0\` oder der Steuerklassen-
bezeichnung als SQL-Wert abgeleitet werden.

## V4.9 UI
- Zwei auf derselben EAN-Eingabe basierende Reiter:
  **Artikelübersicht** und **Lagerplatzübersicht**.
- In der Artikelübersicht vorhandener automatischer JTL- und Historien-
  abgleich plus Quellenangabe/Altersanzeige; Bearbeiten und eine
  Ameise-Prüf-CSV. Bestehende Artikel werden nicht direkt geschrieben.
- Weitere vier Kanal-Basisprüfungen: Amazon, eBay, Kaufland, Shopify.
  **Keine vollständige kanal-/kategoriespezifische Compliance-Freigabe.**
  Pflichtattribute sind vom jeweils in JTL zugeordneten Verkaufskanal
  und der dort ausgewählten Kategorie abhängig; es gibt derzeit
  noch keine automatische Metadaten-Aktualisierung dieser Pflichtfelder.
- Lagerplatzansicht: Gesamt- und verfügbarer Bestand aus dem
  verifizierten JTL-Snapshot, aber eine Fachmengen-Tabelle **erst dann**,
  wenn eine verifizierte WMS-Quelle mit Artikel-ID, Lagerplatz und
  Menge als \`related_sources.wms_lagerplaetze\` synchronisiert wird.
  Solange das fehlt, steht dort ausdrücklich "Noch keine verifizierten
  WMS-Fachmengen". Keine Bestands-/Lagerplatzbuchung.
- Kamera: neue Schaltfläche neben EAN. Nur auf ausdrückliche Aktion
  mit HTTPS und Browser-Kameraberechtigung. Native BarcodeDetector-API,
  sonst clientseitige ZXing-Bibliothek in festgehaltener Version,
  im Test von jsDelivr nachgeladen. Externen JavaScript-Host vor
  Live-Einsatz durch selbst gehostete und geprüfte, versionierte
  Bibliothek mit CSP/SRI ersetzen. iPhone Safari, iOS-In-App-Browser
  und Android müssen noch real getestet werden.
  Die Videodaten werden nicht an den Artikelmanager-Server hochgeladen.

## Artikelregeln ohne blindes Überschreiben
- Gescanntes EAN/GTIN mit 8-14 Ziffern in fehlendes \`GTIN\` und
  fehlendes \`HAN\` ergänzen. Vorhandene Werte bleiben erhalten.
  \`Artikelnummer\` wird bei \`AR...\` oder anderer bestehender Nummer
  **niemals** durch die EAN überschrieben.
- Artikelname nach dem verifizierbar parsebaren Schema:
  \`Hersteller Produktlinie NuanceNr Klartext - (60 ml)\`.
  Fehlende Hersteller, Nuance oder Inhalt werden nicht erfunden.
  Bereits manuell veränderte Namen werden nicht standardisiert.
- \`Kurzbeschreibung\` und \`Beschreibung\` übernehmen den
  standardisierten Artikelnamen nur dann, wenn sie leer oder exakt
  bisherige Kopien des alten Artikelnamens sind.
  Individuelle Beschreibungstexte bleiben unverändert.
- Historische Ergänzungen und daraus generierte Titel/Texte
  benötigen die bestehende Sammelbestätigung vor CSV-Export.
  Alle Veränderungen betreffen lokale Browser-Entwürfe.
- Artikel, deren identifizierende Felder und Texte bereits
  vollständig und korrekt sind, bleiben unverändert.
- Einzelne Artikel können zusätzlich mit bestätigten Änderungen
  für den gemeinsamen CSV-Prüfexport vorgemerkt werden.

## Steuerklasse und 19 %
JTL Artikelstamm \`kSteuerklasse\` und
\`dbo.tSteuerklasse.cName='Normaler Steuersatz'\` sind bestätigt.
Der pro Liefer-/Verkaufsland und Umsatzart gültige Prozentwert
muss jedoch aus den echten JTL-Steuerregeln/-sätzen zugeordnet
werden. Die Oberfläche erklärt den üblichen deutschen Regelsteuersatz
von 19 %, aber schreibt **keine ungeprüfte 19** in die Ameise-CSV.
Die bestehende Ameise-Spalte \`Steuerklasse\` bleibt das bestätigte
Importfeld. Quelle und korrekten Zeitpunkt/Satz erst aus
\`sql/JTL_11_STEUER_WMS_FAECHER_LESEND.sql\` ermitteln.

## WMS-Lagerplätze
Für jeden Artikel benötigen wir mindestens:
- **kArtikel** oder andere verifizierte eindeutige Artikelrelation
- **kWarenLagerPlatz** bzw. Lagerfach-ID und lesbare Fachkennung
- Stückzahl pro Fach, ggf. getrennt nach gesperrt, frei, reserviert,
  Charge/MHD, aber nur wo JTL diese Werte wirklich bereitstellt
- Snapshotzeit und Warenlageridentifikation

Ein Artikel mit Gesamtbestand 15 kann z. B. drei Lagerplätze
besitzen; die Gesamtmenge darf nicht einer frei gewählten
Regalposition zugerechnet werden.

Die SQL11-Quellinventur liest ausschließlich Metadaten der
Steuer-/WMS-Tabellen und -Views und muss vor dem echten Join
auf dem Windows-Server ausgeführt werden.
Die spätere Buchung muss über einen von JTL unterstützten
Import-/Wareneingangsweg erfolgen, NICHT per direktem UPDATE
auf WMS-SQL-Tabellen.

## Schritte
1. In Plesk TEST-Subdomain Git-Pull + Bereitstellen, danach
   im Browser \`v4.9 TEST\` prüfen. Kein Merge nach main.
2. EAN 4015600040482 mit Enter, Button und Kamera auf
   realem Mobiltelefon testen; Kamera nur mit Zustimmung.
3. Bestehende Artikelnummer/GTIN/HAN und individuell
   verfasste Beschreibungen unverändert lassen.
4. Namenbeispiel \`Color Touch 6/75 dunkelblond braun-mahagoni - (60 ml)\`
   und Hersteller \`Wella\` soll einen Vorschlag mit Wella-Präfix
   erzeugen, ohne doppelte Marke.
5. Historie: eindeutige ergänzte Textwerte vor Export gesammelt
   prüfen, Quellanzeige und CSV kontrollieren.
6. In der Lagerplatzübersicht beim Testartikel Bestand 0
   aus JTL anzeigen, aber keine erfundenen Lagerplätze.
7. SQL11 in SSMS auf \`eazybusiness\` ausführen,
   Ergebnisraster für Steuer und WMS als CSV bereitstellen.
8. Reale Steuer- und Lagerplatzquellen anhand SQL11 ergänzen,
   zuerst read-only Windows-Sync, dann API, dann UI.
9. Erforderliche JTL-Ameise-Feldzuordnungen mit echtem Testlauf
   validieren, erst später Artikel- und WMS-Buchung automatisieren.

Kein Backend-, Chrome-, iPhone- oder End-to-End-Test
ist bereits erfolgreich dokumentiert; bisher wurde
nur die Code-Syntax und die Datenquellenlogik auf Branch geprüft.
