# HW24 Artikelmanager: JTL-Ameise Importprofil (Arbeitsvorgabe)

Quelle der Feldnamen: Nutzerexport `JTL-Export-Artikelstammdaten-12112026.csv`, 872 Spalten,
Windows-1252-kodiert und mit Semikolon getrennt. Die CSV aus HW24 ist **UTF-8 mit BOM** und
nutzt nur die befüllten, explizit freigegebenen Originalfeldnamen.

## Primärer Prozess

1. EAN/GTIN scannen. Aktuellen JTL-Bestand über SQL-Server-Spiegel verifizieren.
2. Stammdaten aus aktueller Wawi verwenden, fehlende Angaben anhand historischer Belege
   ergänzen und jeweils prüfen.
3. Derselbe Artikelstamm-Entwurf ist für Neuanlage und Aktualisierung vorgesehen.
4. Eindeutige **Artikelnummer** als Abgleichkriterium verwenden.
   `Interner Schlüssel` aus einem bestehenden JTL-Export **nur** für dafür geeignete
   Bestandsdaten, nicht für neue Produkte aus historischen Fremddaten.
5. `Artikel > Artikeldaten` in JTL-Ameise öffnen und als Importart
   **Neue Artikel importieren, vorhandene aktualisieren** wählen.
6. **Identifizierung der zu aktualisierenden Artikel anhand: Artikelnummer**.
   Keine unbeabsichtigte Mehrfachaktualisierung anhand doppelt belegter EAN.
7. Trennzeichen Semikolon, Kopfzeile enthalten, Zeichensatz UTF-8 BOM.
   Feldzuordnung manuell an den tatsächlichen importierbaren JTL-Ameise-Feldern prüfen.
   Spaltennamen des **Exports** garantieren keine 1:1-Importierbarkeit.
8. Einstellungen sicher wählen: leere Textwerte nicht überschreiben, bestehende
   Kategorien/Bilder/Lieferanten/Preise nicht vorsorglich löschen, Bestände nicht
   ungewollt ersetzen oder addieren.
9. **JTL-Wawi-Datenbanksicherung** direkt vor Import, danach **Testen/Trockenlauf**
   mit 1 bis 3 Artikeln. Fehlerberichte prüfen und Ergebnis mit JTL vergleichen.
10. Erst danach tatsächlichen Import ausführen und Importbericht sichern.

## Felder und Grenzen

- JTL-Artikelstammdaten: Identifikation, Texte, Hersteller, Warengruppe,
  Kategorien, steuerliche Zuordnung, validierte Preise und Maße.
- `Inhalt/Menge` betrifft Packungsinhalt/Grundpreis, nicht verfügbaren Bestand.
- Durchschnittlicher EK (`fEKNetto`), letzter EK (`fLetzterEK`) und Buchungsdatum
  sind JTL-Quellinformationen und werden **nicht** künstlich als Importwerte gesetzt.
- Lager/WMS-Bestandsbuchungen, Verkaufskanal-Aktivierungen und Amazon/eBay-Liveangebote
  sind andere Workflows.
- Artikelattribute, Merkmale, Funktionsattribute und Eigene Felder nach Bedarf über
  **separate Ameise-Importarten** übertragen.
- Ein Artikel in JTL bedeutet nicht automatisch, dass er bei Amazon/eBay/Shopify
  aktiv, freigeschaltet oder lieferbar ist.

## Projektstatus

Der HW24 Scanner kann einen Artikelstammdaten-**Entwurf** in eine einzeilige
Ameise-Prüf-CSV exportieren. Er hat **noch kein** durch einen JTL-Ameise-Testlauf
validiertes Importprofil und importiert selbst nicht in JTL. Der JTL-Windows-Synchronisationsdienst
ist noch nicht produktiv aktiviert.

Quellen:
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikeldaten-importieren/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/attribute-merkmale-und-eigene-felder-importieren/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/aufbau-der-import-funktion/
