# HW24 SQL-Inventur · Auswertung der hochgeladenen DB-Mapping-CSV

Quelle: Nutzerexport \`Artikelmanager DB Mapping.csv\`, 17. November 2026.
Die CSV enthält **309 Tabelleneinträge** und ausschließlich die Spalten
\`TABLE_SCHEMA\`, \`TABLE_NAME\` und \`datenbereich\`.

## Tatsächliche Struktur (Nachweis aus dem Export)

| Datenbereich | Tabellen |
| --- | ---: |
| Marktplatz / Amazon | 55 |
| Lager / Bestand | 53 |
| Attribute / Merkmale | 45 |
| Einheiten / Versand | 42 |
| Artikel / Beziehungen | 39 |
| Preise / Steuer | 26 |
| Lieferant / EK / Kosten | 24 |
| Kategorien / Warengruppen | 21 |
| Hersteller | 4 |
| **Summe** | **309** |

Es gibt 16 unterschiedliche SQL-Schemas im Export. Die wichtigsten
artikelbezogenen Datenquellen sind nachweislich:

- Hersteller: \`dbo.tHersteller\`, \`dbo.tHerstellerSprache\`
- Einkauf: \`Artikel.tArtikelkosten\`, \`dbo.teklieferantpreise\`,
  \`dbo.tlieferant\`, \`dbo.tliefartikel\`, \`dbo.tLiefArtikelPreis\`
- Amazon: \`dbo.pf_amazon_angebot\`, \`dbo.pf_amazon_angebot_ext\`,
  \`dbo.pf_amazon_angebot_mapping\`, \`Artikel.tArtikelAsin\`
- Verkaufskanäle: \`SCX.tVerkaufskanalArtikelData\`,
  \`SCX.tArtikelVerkaufskanalFeldWert\`
- Kategorien: \`dbo.tWarengruppe\`, \`dbo.tkategorie\`,
  \`dbo.tkategorieartikel\`, \`dbo.tKategorieSprache\`
- Weitere artikelbezogene Quellen: \`dbo.tPreis\`,
  \`dbo.tPreisDetail\`, \`dbo.tArtikelAttribut\`,
  \`dbo.tArtikelMerkmal\`, \`dbo.tSteuerklasse\`,
  \`dbo.tVersandklasse\`

WICHTIG: Es ist noch NICHT nachgewiesen, wie diese Tabellen im
konkreten JTL-Wawi-Schema verknüpft sind. Tabellenname ist keine
Bestätigung von JOIN-Spalten, Werten, fachlicher Preisdefinition oder
Eindeutigkeit. Auch \`dbo.tHersteller.cName\` muss erst geprüft werden.

## Nächster manueller Schritt am Windows SQL Server

1. Datei \`sql/JTL_09_ARTIKEL_TABELLEN_SPALTEN_LESEND.sql\`
   im Repository öffnen.
2. In SQL Server Management Studio mit \`eazybusiness\` verbinden.
3. Komplette Abfrage ausführen (ausschließlich lesende Metadaten).
4. Das **eine** Ergebnisraster mitsamt Spaltenüberschriften als
   CSV exportieren. Keine Passwörter, Tokens oder Kundendatensätze
   nötig.
5. CSV im Projekt hochladen.

Die neue SQL09-Abfrage erfasst exakt 33 relevante und in der ersten
Datei tatsächlich vorhandene Tabellen. Sie gibt Tabellen-/Spaltennamen,
SQL-Datentypen, Nullbarkeit, IDENTITY/COMPUTED, primäre
Schlüssel und etwaige deklarierte Fremdschlüssel aus.

Danach können die Hersteller-, Kosten-, Amazon-, Kategorie- und
Lieferantenwerte **mit echten SQL-Join-Schlüsseln** gemappt und im
Windows-Synchronisierer (read-only) aktiviert werden.

## In welcher Reihenfolge anbinden?

1. Hersteller-ID / Sprachvarianten -> Herstellerklarname.
2. Kosten: separates JTL-Exportfeld "Kosten" bestimmen;
   vom Durchschnitts-EK \`fEKNetto\` und letzten EK
   \`fLetzterEK\` unbedingt getrennt lassen.
3. Lieferant / EK pro Artikel / Lieferantenartikelnummer.
4. Amazon / Angebot / ASIN / Preise; nur konkret verifizierte
   Produkt- und Angebotsbeziehungen, keine Amazon-Bestellungen.
5. Warengruppen, Kategorien, Preislisten, Versandeinstellungen,
   Artikelattribute und Merkmale.
6. WMS-Lagerplätze und buchbarer Bestand erst mit separater
   Bestandsbuchungs- und Teststrategie.

Neue Erweiterungen bleiben im **Test-Branch**. Sie sind nicht
automatisch am Windows-Server oder in \`main\` aktiv; ein
unveränderter Artikelimport ohne realen JTL-Ameise-Trockenlauf
ist nicht als erfolgreich nachgewiesen.
