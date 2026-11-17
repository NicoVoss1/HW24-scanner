# HW24 Artikelmanager v4.12 TEST – Zwei Arbeitsmodi, WMS rein lesend

## Entscheidung

Die Hauptoberfläche zeigt genau zwei gleichwertige Modi:

1. **Artikelpflege**: Einzelartikel scannen, Stammdaten aus dem aktuellen
   JTL-Spiegel und dem historischen Excel-Archiv zusammenführen,
   Namen / Texte / Werte bestätigen oder korrigieren und eine
   JTL-Ameise-Prüf-CSV erstellen. Die vorhandene erweiterte
   Massenpflege ist optional, nicht Teil der Lagerplatzübersicht.
2. **Lagerplatzübersicht**: **nur lesen**. Ein und dasselbe Suchfeld
   versteht Artikelnummer, EAN/GTIN und HAN. Angezeigt werden
   Artikelnummer, EAN, HAN, Artikelname, Hersteller, Warengruppe,
   JTL-Gesamtbestand und aktuell synchronisierte WMS-Fachpositionen.

Beim Aktivieren der Lagerplatzübersicht werden alle
Artikelbearbeitungsfelder, Preis-/Steuerfelder, Exportaktionen und
Expertenmodule aus der Anzeige entfernt. Es gibt dort
**keinen** Speichern-, Buchungs-, Ändern- oder Exportbutton.
Die mobile Darstellung nutzt große Lagerfachkarten.

## Jede Fachposition aus verifizierter JTL-WMS-Quelle

- Warenlager-ID und Fachname
- Menge **einschließlich Null-Buchungen / bestehenden Null-Zuordnungen**
- Fachsperre und Auslieferungssperre (deutlich markiert)
- Inventur-ID, Inventurdatum und interne Typ-/Status-IDs nur als Quellhinweise
- Retourenhinweise nur, wenn Name/Kommentar das Wort „Retour“
  enthält; nicht als amtlich verifizierter Platztyp
- Zeitpunkt der **letzten Lagerbewegung**, sofern zukünftig von
  der echten WMS-Bewegungshistorie geliefert; andernfalls
  ausdrücklich „Noch nicht ermittelt“

**Nicht verwechseln**:
- \`dWmsInventurDatum\` ist **keine** letzte Einlagerung
- Snapshotzeit aus der Windows-Synchronisation ist **keine**
  Buchungs- oder Einlagerungszeit
- Der Zeitpunkt der letzten Bewegung ist **nicht automatisch**
  der Zeitpunkt, zu dem die aktuell auf dem Platz befindliche
  Charge zuerst eingelagert wurde

## Zusätzliche SQL-Metadatenabfrage

\`sql/JTL_13_WMS_BEWEGUNGSHISTORIE_METADATEN_LESEND.sql\`

Die Datei fragt ausschließlich die Namen und Spalten der
wahrscheinlichen Bewegungs-/Buchungs-/Einlagerungs-/Historien-
tabellen und -Views von \`eazybusiness\` ab. Sie ist lesend
und ändert keine Datensätze.

Nachdem die beiden Ergebnisraster bekannt sind, wird anhand
des Testartikels \`kArtikel=2594\` und des bekannten
Lagerfachs \`kWarenLagerPlatz=66\` der **tatsächlich passende**
Bewegungs-/Zeitstempel-Join verifiziert. Erst dann wird
der Wert in \`related_sources.wms_lagerplaetze[].letzte_bewegung_utc\`
synchronisiert und als Datum angezeigt.

Die JTL-WMS-Bewegungshistorie umfasst Zeitstempel sowie
Start- und Zielplätze. Sie wird dokumentiert unter
https://guide.jtl-software.com/jtl-wms/interne-prozesse/artikelbewegungen-in-der-bewegungshistorie-pruefen/

## Testumgebung aktualisieren

In Plesk auf \`test-artikelmanager.hairworld24.de\`
den Branch \`improve/encoding-dashboard-sync-20261115\`
abholen und bereitstellen. Nach Reload muss **v4.12 TEST**
sichtbar sein. Das Live-System \`main\` bleibt unverändert.

Wichtig: Plesk-Git-Pull aktualisiert NICHT den Windows-Task.
Die Zusatzquellen für WMS-Fächer und Steuerdaten
(\`include_wms_bins\`, \`include_tax_de\`) sind in der
GitHub-Beispielkonfiguration weiterhin deaktiviert.

## Noch benötigte Windows-Schritte

1. Die bestehenden Sync-Dateien und den letzten
   vollständigen Snapshot sichern.
2. Den geprüften aktuellen Zweig der drei Windows-Dateien
   nur nach Freigabe in ein getrenntes Sync-Testverzeichnis
   übernehmen; SQL-Rechte SELECT auf betroffene Tabellen.
3. \`jtl-related-sources.json\` lokal und privat konfigurieren.
   Für einen kontrollierten Test \`include_wms_bins\` erst
   nach Schema-/Berechtigungscheck aktivieren.
4. Die geplante 15-Minuten-Aufgabe vor dem Probelauf anhalten,
   damit keine gleichzeitigen vollständigen Synchronisierungen
   stattfinden.
5. Test mit \`?ean=4015600040482\`: Nach einem erfolgreichen
   neuen Snapshot muss das WMS-Fach \`R2-RF-F13-H\`
   mit **0 Stück** angezeigt werden. Prüfen, dass es
   nicht als gesperrt ausgewiesen wird.
6. Test mit einer echten \`AR...\`-Artikelnummer und
   alphanumerischer HAN, sofern tatsächlich vorhanden.
7. Während die Bewegungsquelle noch nicht verifiziert ist,
   muss die Spalte "Letzte Lagerbewegung" korrekt
   **„Noch nicht ermittelt“** melden.

## Mehrere tausend Artikel schnell bearbeiten

**Zwei unterschiedliche Workflows** sind nötig:
- Einzel-Scan: ein Artikel inklusive Historie, manueller
  Korrektur und optionaler Sammelpflege.
- Massenanlage: JTL-Ameise- oder Lieferanten-CSV einmal laden,
  über Indizes nach GTIN/SKU/HAN mit dem vollständigen
  gespiegelten Artikelbestand abgleichen, eindeutig fehlende
  Stammdaten regelbasiert ergänzen und EINE validierte
  CSV mit tausenden Zeilen erzeugen.

Die Massenanlage ist **noch nicht** vollständig umgesetzt
oder auf Geschwindigkeit getestet. Der bisherige Scanner und
die lokale Zusatzartikel-Sammelliste mit max. 100
Korrekturen sind keine nachgewiesene Tausender-Massenanlage.

Die CSV-Vorbereitung kann durch effiziente Batchabfragen und
Verarbeitung leistungsfähig gestaltet werden.
**Die tatsächliche Anlage mehrerer tausend Artikel in
JTL-Wawi in Sekunden kann jedoch nicht zugesichert werden.**
Importdauer hängt von JTL-Ameise, DB, Bildern, Varianten,
weiteren Pflichtfeldern und Kanalsynchronisierung ab.

Die JTL-Ameise unterstützt einen Massendatenimport
im CSV-Format. Bei neuer/aktualisierter Artikelanlage
kann ein Prüfprofil zwischen Neuerfassung und
Aktualisierung unterscheiden. Wir müssen den ersten
Import anhand eines echten, trockenen Testlaufs
und anschließend mit Batch-Profilen benchmarken:
https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikeldaten-importieren/

## Teststatus

Das Repo enthält v4.12 UI und SQL13. Das JS wurde auf
Syntax geprüft. **Keine direkte JTL- oder WMS-Buchung,
kein Massendurchsatz-Benchmark, kein Windows-Lauf,
keine produktive Deployment-Freigabe**.

Das nächste benötigte Artefakt ist die CSV der beiden
Ergebnismengen von SQL13, nicht ein neuer Screenshot
von einem bekannten Inventurdatum.
