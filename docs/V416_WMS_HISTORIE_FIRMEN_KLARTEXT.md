# HW24 v4.16 TEST: WMS-Artikelübersicht, Firmenklarnamen, vollständige Bewegungshistorie

Datum: 17.11.2026. Branch: improve/encoding-dashboard-sync-20261115.
Produktivsystem und Windows-Task sind bisher nicht verändert.

## Ausgangsdaten: Grüne Quellenprobe

Test-EAN 4015600040482, kArtikel=2594, Wella / Haarfarben.
- WMS-Lager 4 / Platz-ID 66 / Bezeichnung R2-RF-F13-H / Menge 0
- Fachsperre 0, Auslieferungssperre False
- DE-Steuerkontext Firma kFirma=1, Zone kSteuerzone=3, Zone "EU-Zone", fSteuersatz=19
- 12 von 12 verifizierten Zusatzquellen lesbar
- Zwei Lieferantentabellen im Testartikel ohne Treffer
- Verkaufskanaldaten können gekürzt sein

## Stand der v4.16-Testoberfläche

In der Lagerplatzübersicht werden im Testbranch:
- Artikelnummer, EAN/GTIN, HAN, Artikelname, Hersteller, JTL-Warengruppe
- verfügbare Menge aus \`related_sources.bestand.rows[0].fVerfuegbar\`
  über die API-Eigenschaft \`bestand_verfuegbar\` separat hervorgehoben
- Gesamtbestand aus dem tArtikel-Snapshot
- aktuelle JTL-WMS-Fächer einschließlich Menge 0,
  Sperre, Auslieferungssperre und Inventurhinweis
- neuer Abschnitt direkt unter den Fächern:
  **"Vollständige Bewegungshistorie des Artikels"**.

Der neue Historienabschnitt arbeitet derzeit mit einem optionalen
\`related_sources.wms_bewegungshistorie\`-Datensatz:
\`{rows:[{zeitstempel,buchungsart,menge,lagerplatz_von,lagerplatz_nach,beleg}],complete:true}\`.
Die vorhandenen Zeilen werden in Portionen von 50 *angezeigt*, ohne
Datumsfilter. \`complete=true\` ist ausschließlich zulässig, wenn die
Quelle die gesamte gespeicherte Historie des konkreten Artikels
nachweislich geliefert hat. Ohne Quelle zeigt die Oberfläche
"noch nicht angebunden" statt erfundener Buchungen.

**Aktueller Stand:** Der Browserbereich ist vorbereitet, aber die
Bewegungshistorie wird noch NICHT synchronisiert. Die aktuelle
WMS-Fachmenge stammt aus einer anderen JTL-View.

## Firmennamen statt nur IDs

Die JTL-Steuerzone 3 ist bereits unter dem Namen "EU-Zone"
nachgewiesen. Die Firmen-ID 1 muss über die reale Firmendatenquelle
\`dbo.tFirma\` aufgelöst werden.
Der Windows-Steuerleser versucht ab v4.16 nach einer Schema-Prüfung
\`dbo.tFirma(kFirma,cName)\` read-only zu lesen. Wenn diese Tabelle
oder SELECT-Berechtigung fehlt, zeigt der Artikelmanager weiterhin
die ID mit "Klarname fehlt". Keine Vermutung über den Firmennamen.

Prüfen:
\`sql/JTL_17_FIRMENNAMEN_VOLLSTAENDIGE_WMS_HISTORIE_LESEND.sql\`.
Mit sa im vorhandenen HeidiSQL ausführen, NICHT in PowerShell.
Die Abfrage zeigt Metadaten zu dbo.tFirma und Historientabellen,
die Steuerzone und den Firmenstammsatz kFirma=1.

**Sensible Daten:** Den vollständigen tFirma-Datensatz mit
Adressen, Konten, USt-ID usw. nicht ungeprüft in den Chat kopieren.
Für den nächsten Schritt genügen kFirma und der bestätigte
Firmenname, außerdem die Spaltenlisten und der fachlich relevante
Historienauszug.

## Historische Lagerbewegungen ohne Datumsfilter

JTL-WMS dokumentiert die Bewegungshistorie mit Zeitstempeln,
Start-/Zielplatz und Buchungstyp. Für die lokale Datenbankversion
sind die genauen Spalten noch nicht abschließend belegt.
Die öffentlich dokumentierte Relation
\`dbo.tWarenLagerEingangHistorie.kWarenLagerEingang\` ->
\`dbo.tWarenLagerEingang.kWarenLagerEingang\` ->
\`dbo.tWarenLagerEingang.kArtikel\`
ist ein plausibler Kandidat, aber muss unter JTL 2.2.2 validiert werden.

SQL17 zeigt dafür zuerst die Metadaten und anschließend
**sämtliche gespeicherten Datensätze des Testartikels**
ohne Start- oder Enddatum. Das bedeutet **nicht**, dass die
JTL-Datenbank historische Einträge bewahrt, die zuvor bereits
gelöscht/archiviert worden sind.

Die spätere produktive Lösung:
1. Original-JTL-Historienquelle und Buchungsfelder anhand SQL17
   verifizieren; klar benannte Buchungsarten via tBuchungsArt,
   Start- und Zielfach via tWarenLagerPlatz
2. Nur erforderliche SELECT-Rechte für hw24_reader ergänzen:
   mindestens dbo.tFirma, dbo.tWarenLagerEingang,
   dbo.tWarenLagerEingangHistorie und dbo.tBuchungsArt,
   sofern diese Quellen tatsächlich gebraucht werden
3. Historieneinträge in einer **eigenen** MariaDB-Spiegeltabelle
   mit stabilem Bewegungs-/Ereignisschlüssel und SQL-Indizes
   speichern, nicht alle Bewegungen in das riesige Artikel-raw_json
   mit jedem 15-Minuten-Vollabzug packen
4. Alle bis dato vorhandenen Bewegungen einmalig lesen und danach
   nur neue Bewegungen inkrementell übernehmen
5. Lesender API-Endpunkt für EINEN ausgewählten Artikel,
   serverseitig paginiert (z.B. 50 Einträge pro Aufruf),
   ohne jegliche Datumsbeschränkung und mit "weitere laden"
6. Vollständigkeit über Zählung und synchronisierten Zeit-/Schlüsselstand
   dokumentieren; kein "vollständig", solange die Quelle unvollständig ist
7. WMS-Anzeige getrennt von Artikelpflege halten;
   keine Bestandsänderungen durch den Artikelmanager

## Nächste Schritte für Nico

1. \`sql/JTL_17_FIRMENNAMEN_VOLLSTAENDIGE_WMS_HISTORIE_LESEND.sql\`
   in HeidiSQL unter eazybusiness mit sa ausführen.
2. Ergebnisse 1 und 2 und die fachlich nötigen Felder aus
   Ergebnis 3 (nur Firmenname + ID) schicken.
3. Ergebnis 4: Falls groß, als CSV exportieren und vor
   dem Teilen personenbezogene oder vertrauliche Felder entfernen.
4. Erst danach Bewegungstabellen und Spalten verbindlich anbinden,
   Rechte nach dem bisherigen erfolgreichen GRANT-SELECT-Verfahren
   vergeben und den inkrementellen Spiegel implementieren.
5. Die neue Testoberfläche kann nach Git Pull/Bereitstellen
   v4.16 TEST anzeigen. **Windows-WMS-Flags sind aktuell weiterhin
   false**, also noch keine aktuellen Fachmengen auf der Website.
6. Vollständiger Windows-Sync nur nach Backup, geplanter Task
   angehalten und kontrollierter Einzellauf. Keine Änderung des
   bisherigen Sicherheitsschemas.

Externe Quellen:
- https://guide.jtl-software.com/jtl-wms/interne-prozesse/artikelbewegungen-in-der-bewegungshistorie-pruefen/
- https://forum.jtl-software.de/threads/dokumentation-lagerplatz-umbuchung.182542/
