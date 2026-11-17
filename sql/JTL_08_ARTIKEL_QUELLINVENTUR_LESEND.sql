/* HW24 Artikelmanager · Quellinventur fuer alle artikelbezogenen JTL-Tabellen
   Quelle: Windows SQL Server eazybusiness, JTL-Wawi 2.2.2.0.
   NUR LESEND: keine Aenderung an Artikeln, Preisen, Amazon, Lagern oder Benutzern.
   Ausfuehren in SSMS (SQL Server Management Studio) auf dem JTL-Windows-Server.
   Ergebnis als CSV speichern und die ERGEBNISSE ohne geheime Zugangsdaten teilen.
   Keine Schreibrechte erforderlich.
*/
USE [eazybusiness];
SET NOCOUNT ON;

-- 01. Umfang der aktuell relevanten Tabellen (KEINE Annahme ueber Namen).
SELECT
  t.TABLE_SCHEMA, t.TABLE_NAME,
  CASE
    WHEN t.TABLE_NAME LIKE '%Hersteller%' THEN N'Hersteller'
    WHEN t.TABLE_NAME LIKE '%Amazon%' OR t.TABLE_NAME LIKE '%Marketplace%'
      OR t.TABLE_NAME LIKE '%Marktplatz%' OR t.TABLE_NAME LIKE '%Angebot%' THEN N'Marktplatz / Amazon'
    WHEN t.TABLE_NAME LIKE '%Lieferant%' OR t.TABLE_NAME LIKE '%Einkauf%'
      OR t.TABLE_NAME LIKE '%Kosten%' THEN N'Lieferant / EK / Kosten'
    WHEN t.TABLE_NAME LIKE '%Preis%' OR t.TABLE_NAME LIKE '%Steuer%' THEN N'Preise / Steuer'
    WHEN t.TABLE_NAME LIKE '%Kategorie%' OR t.TABLE_NAME LIKE '%Warengruppe%' THEN N'Kategorien / Warengruppen'
    WHEN t.TABLE_NAME LIKE '%Bestand%' OR t.TABLE_NAME LIKE '%Lager%'
      OR t.TABLE_NAME LIKE '%WMS%' THEN N'Lager / Bestand'
    WHEN t.TABLE_NAME LIKE '%Attribut%' OR t.TABLE_NAME LIKE '%Merkmal%' THEN N'Attribute / Merkmale'
    WHEN t.TABLE_NAME LIKE '%Einheit%' OR t.TABLE_NAME LIKE '%Versand%' THEN N'Einheiten / Versand'
    WHEN t.TABLE_NAME LIKE '%Artikel%' THEN N'Artikel / Beziehungen'
    ELSE N'Sonstiges'
  END AS datenbereich
FROM INFORMATION_SCHEMA.TABLES AS t
WHERE t.TABLE_TYPE='BASE TABLE'
  AND t.TABLE_SCHEMA NOT IN ('sys','INFORMATION_SCHEMA')
  AND (
    t.TABLE_NAME LIKE '%Artikel%' OR t.TABLE_NAME LIKE '%Hersteller%'
    OR t.TABLE_NAME LIKE '%Amazon%' OR t.TABLE_NAME LIKE '%Marketplace%'
    OR t.TABLE_NAME LIKE '%Marktplatz%' OR t.TABLE_NAME LIKE '%Angebot%'
    OR t.TABLE_NAME LIKE '%Lieferant%' OR t.TABLE_NAME LIKE '%Einkauf%'
    OR t.TABLE_NAME LIKE '%Kosten%' OR t.TABLE_NAME LIKE '%Preis%'
    OR t.TABLE_NAME LIKE '%Steuer%' OR t.TABLE_NAME LIKE '%Kategorie%'
    OR t.TABLE_NAME LIKE '%Warengruppe%' OR t.TABLE_NAME LIKE '%Bestand%'
    OR t.TABLE_NAME LIKE '%Lager%' OR t.TABLE_NAME LIKE '%WMS%'
    OR t.TABLE_NAME LIKE '%Attribut%' OR t.TABLE_NAME LIKE '%Merkmal%'
    OR t.TABLE_NAME LIKE '%Einheit%' OR t.TABLE_NAME LIKE '%Versand%'
  )
ORDER BY datenbereich,t.TABLE_SCHEMA,t.TABLE_NAME;

-- 02. Tatsachlich vorhandene Tabellen/SPALTEN mit Bezug auf Stammdaten,
--     Amazon, ASIN, EK/Kosten, Hersteller, Kategorie, Lieferanten, Bestand.
--     Hiermit lassen sich die richtigen JOIN-Schluessel ermitteln.
SELECT c.TABLE_SCHEMA,c.TABLE_NAME,c.COLUMN_NAME,c.DATA_TYPE,
       c.CHARACTER_MAXIMUM_LENGTH,c.IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS AS c
WHERE c.TABLE_SCHEMA NOT IN ('sys','INFORMATION_SCHEMA')
  AND (
    c.TABLE_NAME LIKE '%Artikel%' OR c.TABLE_NAME LIKE '%Hersteller%'
    OR c.TABLE_NAME LIKE '%Amazon%' OR c.TABLE_NAME LIKE '%Marketplace%'
    OR c.TABLE_NAME LIKE '%Marktplatz%' OR c.TABLE_NAME LIKE '%Angebot%'
    OR c.TABLE_NAME LIKE '%Lieferant%' OR c.TABLE_NAME LIKE '%Einkauf%'
    OR c.TABLE_NAME LIKE '%Kosten%' OR c.TABLE_NAME LIKE '%Preis%'
    OR c.TABLE_NAME LIKE '%Steuer%' OR c.TABLE_NAME LIKE '%Kategorie%'
    OR c.TABLE_NAME LIKE '%Warengruppe%' OR c.TABLE_NAME LIKE '%Bestand%'
    OR c.TABLE_NAME LIKE '%Lager%' OR c.TABLE_NAME LIKE '%WMS%'
    OR c.TABLE_NAME LIKE '%Attribut%' OR c.TABLE_NAME LIKE '%Merkmal%'
    OR c.TABLE_NAME LIKE '%Einheit%' OR c.TABLE_NAME LIKE '%Versand%'
  )
  AND (
    c.COLUMN_NAME LIKE '%Artikel%' OR c.COLUMN_NAME LIKE '%Hersteller%'
    OR c.COLUMN_NAME LIKE '%Amazon%' OR c.COLUMN_NAME LIKE '%ASIN%'
    OR c.COLUMN_NAME LIKE '%Marketplace%' OR c.COLUMN_NAME LIKE '%Angebot%'
    OR c.COLUMN_NAME LIKE '%Lieferant%' OR c.COLUMN_NAME LIKE '%EK%'
    OR c.COLUMN_NAME LIKE '%Kosten%' OR c.COLUMN_NAME LIKE '%Einkauf%'
    OR c.COLUMN_NAME LIKE '%Preis%' OR c.COLUMN_NAME LIKE '%VK%'
    OR c.COLUMN_NAME LIKE '%Warengruppe%' OR c.COLUMN_NAME LIKE '%Kategorie%'
    OR c.COLUMN_NAME LIKE '%Steuer%' OR c.COLUMN_NAME LIKE '%Bestand%'
    OR c.COLUMN_NAME LIKE '%Menge%' OR c.COLUMN_NAME LIKE '%Einheit%'
    OR c.COLUMN_NAME LIKE '%Barcode%' OR c.COLUMN_NAME LIKE '%Lagerplatz%'
    OR c.COLUMN_NAME IN ('kArtikel','kHersteller','cName','cBezeichnung','cAktiv','dMod')
  )
ORDER BY c.TABLE_SCHEMA,c.TABLE_NAME,c.ORDINAL_POSITION;

-- 03. Verifizierbare Fremdschluessel zur Artikelbasis. Fehlende
--     FK-Eintraege bedeuten NICHT, dass es keine logischen Beziehungen gibt.
SELECT fk.name AS foreign_key_name,
       OBJECT_SCHEMA_NAME(fk.parent_object_id) AS parent_schema,
       OBJECT_NAME(fk.parent_object_id) AS parent_table,
       pc.name AS parent_column,
       OBJECT_SCHEMA_NAME(fk.referenced_object_id) AS referenced_schema,
       OBJECT_NAME(fk.referenced_object_id) AS referenced_table,
       rc.name AS referenced_column
FROM sys.foreign_keys AS fk
JOIN sys.foreign_key_columns AS fkc ON fkc.constraint_object_id=fk.object_id
JOIN sys.columns AS pc ON pc.object_id=fkc.parent_object_id AND pc.column_id=fkc.parent_column_id
JOIN sys.columns AS rc ON rc.object_id=fkc.referenced_object_id AND rc.column_id=fkc.referenced_column_id
WHERE OBJECT_NAME(fk.parent_object_id) LIKE '%Artikel%'
   OR OBJECT_NAME(fk.referenced_object_id) LIKE '%Artikel%'
   OR OBJECT_NAME(fk.parent_object_id) LIKE '%Hersteller%'
   OR OBJECT_NAME(fk.referenced_object_id) LIKE '%Hersteller%'
ORDER BY parent_table,foreign_key_name;

-- 04. Bestaetigte Werte unseres TESTARTIKELS aus der tArtikel-Basistabelle.
SELECT TOP (5)
  a.kArtikel,a.cArtNr,a.cBarcode,a.cHAN,a.cASIN,
  a.kHersteller,a.kWarengruppe,a.kSteuerklasse,
  a.fEKNetto AS durchschnittlicher_ek_netto,
  a.fLetzterEK AS letzter_ek,
  a.dLetzterEK AS letzter_ek_datum,
  a.fVKNetto AS standard_vk_netto,
  a.fAmazonVK AS amazon_vk_im_artikel,
  a.fEbayPreis AS ebay_preis_im_artikel,
  a.nLagerbestand AS artikelbestand,
  a.dMod AS zuletzt_geaendert
FROM dbo.tArtikel AS a
WHERE a.cBarcode=N'4015600040482'
   OR a.cHAN=N'4015600040482'
   OR a.cArtNr=N'4015600040482';

-- 05. Vorhandene Hersteller-Tabelle und Namen erkennen, ohne
--     eine moeglicherweise falsche Tabellenstruktur vorauszusetzen.
SELECT COLUMN_NAME,DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='tHersteller'
ORDER BY ORDINAL_POSITION;
