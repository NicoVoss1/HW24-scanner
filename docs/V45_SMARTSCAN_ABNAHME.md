# HW24 v4.5 TEST: Ein-Scan-Pflege und kombinierter Ameise-Prüfexport

## Ziel / Status
Alle Änderungen liegen im bestehenden Entwicklungsbranch, NICHT in `main`.
Für die Test-Subdomain ist ein **manueller Plesk-Git-Pull und eine Bereitstellung**
erforderlich. Ein Browser-End-to-End-Test ist noch nicht erfolgt.

## Einmal scannen
1. Eine EAN eingeben und "Artikel prüfen" anklicken.
2. Die bisher vorhandenen beiden APIs werden **parallel** abgefragt.
3. Nach Abschluss werden JTL und Historie in einer gemeinsamen Quellenansicht
   zusammengestellt. **Aktuelle JTL-Werte haben Vorrang**.
4. Eindeutige historische Angaben mit bestätigter Spaltenüberschrift dürfen
   fehlende nicht-kritische Textfelder in einem **lokalen Prüfentwurf** füllen:
   Artikelname, Hersteller, Warengruppe, Kurzbeschreibung, Beschreibung.
5. Die Herkunft (JTL, CSV-Arbeitskopie, Historie/Blatt+Zeile,
   Anlagevorlage, manuell, fehlend) bleibt sichtbar. Historische Werte
   sind **nicht als JTL-verifiziert markiert** und werden bis zum Klick
   "Alle historischen Ergänzungen prüfen und freigeben" nicht exportiert.
6. Kritische Preise, Kosten, ASIN, HAN, GTIN, Steuerklasse, Artikelnummer,
   EK-Historie und Bestände **werden nicht automatisch aus Excel ergänzt**.
   Bei widersprüchlichen historischen Angaben erfolgt ebenfalls keine
   automatische Ergänzung.

## Exportvorlagen
- Unter "Stammdaten-Vorlagen und Exportfelder auswählen" Spalten anhaken.
  Die Auswahl gilt direkt für die nächste CSV; "Vorlage speichern" persistiert
  sie aktuell ausschließlich im lokalen Browser, nicht zentral auf MariaDB.
- In der vorläufigen Vorlagenmaske lassen sich zulässige sichere Vorgaben
  für neue Artikel setzen. Sie werden nur übernommen, wenn der aktuelle
  JTL-Spiegel **keinen EAN-Treffer** liefert und das Feld leer ist.
  `Überverkäufe möglich` bleibt zwingend `N`.
- Unbekannte der 872 JTL-CSV-Quellspalten werden im Feldkatalog angezeigt,
  aber nicht automatisch für den Ameise-Export freigegeben: Exportformat und
  exakte Ameise-Feldzuordnung müssen zuerst getestet werden.
- Der Button "Prüf-CSV mit dieser Vorlage exportieren" erzeugt eine CSV mit
  **den ausgewählten, tatsächlich belegten und freigegebenen Feldern**.
  Leere Werte werden nicht als willkürliche Buchungen eingeführt.

## Begleitende Massenpflege (bis 3 zusätzliche Artikel)
- Beim Scannen eines eindeutig gefundenen Bestandsartikels einzelne
  Stammdaten ändern bzw. historische Ergänzungen gesammelt freigeben.
- "Aktuellen Artikel zur Pflege-Liste hinzufügen" merkt dessen Artikelnummer
  und ausschließlich bestätigte **geänderte, angehakte Exportfelder** vor.
- Danach eine weitere EAN scannen und wiederholen, bis 3 Zusatzartikel
  vorgemerkt sind.
- Die Ameise-Prüf-CSV enthält die aktuelle Hauptzeile und darunter bis zu
  3 Pflegezeilen. Die Zusatzzeilen enthalten nur freigegebene Änderungen und
  die Artikelnummer als eindeutigen Schlüssel. Vorhandene leere Spalten dürfen
  in der JTL-Ameise-Importvorlage keine Werte löschen.
- Die Liste ist vorläufig nur im geöffneten Browser verfügbar; noch kein
  zentrales Audit oder dauerhafte Entwurfsspeicherung.

## Hersteller in JTL-Wawi
Im `sync/HW24_JTL_Synchronisieren.ps1` wird nun OPTIONAL über
`INFORMATION_SCHEMA.COLUMNS` die tatsächliche Tabelle
`dbo.tHersteller(kHersteller,cName)` geprüft. Nur wenn beide Spalten
existieren und eine lesende Abfrage gelingt, wird die Hersteller-ID
auf den Klartext abgebildet und in die bestehende MariaDB-Spalte
`hersteller` übertragen. Andernfalls bleiben Name und SQL-Struktur ungeändert.

**Wichtig:** Der PowerShell-Synchronisierer läuft auf Windows, NICHT in Plesk.
Das Deployment der Test-Website installiert dieses Script nicht.
Vor Umstellung die originale Windows-Scriptversion und den Aufgabenplan sichern,
die vorhandenen geheimen Konfigurationen und DPAPI-Dateien unverändert lassen
und zunächst eine isolierte lesende Verbindung prüfen.
Der SQL-Benutzer muss für `dbo.tHersteller` SELECT-Berechtigung erhalten,
sofern die Tabelle nachgewiesen wurde. Nicht pauschal `db_datareader` oder
Schreibrechte vergeben. Die Freigabe erfolgt durch den SQL-Administrator.

Danach Windows-Synchronisationsskript sicher aktualisieren,
einen kontrollierten Synchronisationslauf durchführen und erst dann an
`/api/jtl.php?ean=4015600040482` den nicht-leeren JTL-Hersteller prüfen.
Niemals den kHersteller-Wert `1` ohne DB-Abgleich als "Wella" interpretieren.

## Preise / EK
Der Spiegel liefert `fEKNetto` (durchschnittlicher EK) und
`fLetzterEK` (letzter EK) samt `dLetzterEK`.
Das eigenständige Feld `Kosten` ist noch nicht aus einer bestätigten
Quellspalte abgebildet. Keine automatische Gleichsetzung.
Die nötige SQL-Quellenprüfung liegt in
`sql/JTL_07_HERSTELLER_EK_QUELLEN_LESEND.sql`.

## Abnahmetests vor Merge nach main
- Testdomain zeigt `v4.5 TEST`; JTL-API/History funktionieren hinter Auth.
- EAN 4015600040482 liefert 1 JTL-Treffer; Hersteller-ID und
  ggf. aufgelöster Name mit JTL verifizieren.
- Fehlt der JTL-Herstellername, wird eine eindeutige Historienangabe
  nur als **ungeprüfter lokaler Vorschlag** markiert.
- Historische Werte füllen keine bereits vorhandenen JTL-Daten.
- Bei widersprüchlichen Excel-Werten keine automatische Ergänzung.
- Freigabe aller historischen Vorschläge ist nur eine Aktion, kein JTL-Write.
- Vorlagenfelder an-/abwählen: CSV-Kopf enthält exakt die ausgewählten
  belegten Spalten plus zwingende Identitätsfelder.
- Neue Artikel bekommen erlaubte Vorbelegungen erst nach einem aktuellen,
  eindeutigen Nulltreffer und getrenntem Artikelnummern-Abgleich.
- 1 Hauptartikel + 1..3 eindeutig identifizierte Pflegeartikel erzeugen
  korrekte Zeilen mit gleichem Spaltenschema, nur bestätigte Änderungen
  der zusätzlichen Artikel.
- **Kein automatischer Ameise-Import oder WMS-Bestandsabgleich.**
  JTL-Ameise-Importprofil erst separat an einem Testartikel trockenlaufen
  lassen, dann über den GitHub-PR in main übernehmen.
