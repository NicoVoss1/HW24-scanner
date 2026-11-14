/* JTL-Wawi eazybusiness, SQL Server 2017.
   Schema-Erkundung fuer Kategorien, Attribute, Merkmale und Shopzuordnungen.
   Nur SELECT aus System-Metadaten und lesende Stichprobe. Keine Aenderungen.
*/
USE [eazybusiness];

-- A: Verfuegbare Tabellen, die fuer Artikel-Kategorisierung und Attribute in Frage kommen.
SELECT
    t.TABLE_SCHEMA, t.TABLE_NAME, t.TABLE_TYPE
FROM INFORMATION_SCHEMA.TABLES t
WHERE t.TABLE_TYPE='BASE TABLE'
 AND (
     t.TABLE_NAME LIKE '%Kategori%'
  OR t.TABLE_NAME LIKE '%Merkmal%'
  OR t.TABLE_NAME LIKE '%Attribut%'
  OR t.TABLE_NAME LIKE '%Warengruppe%'
  OR t.TABLE_NAME LIKE '%Plattform%'
  OR t.TABLE_NAME LIKE '%ShopArtikel%'
 )
ORDER BY t.TABLE_SCHEMA,t.TABLE_NAME;

-- B: Bereits bekannte Artikel-Fremdschluessel und Klassifikationsfelder
SELECT
    COLUMN_NAME, DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='tArtikel'
  AND (
    COLUMN_NAME LIKE '%Kategorie%'
 OR COLUMN_NAME LIKE '%Warengruppe%'
 OR COLUMN_NAME LIKE '%Attribut%'
 OR COLUMN_NAME LIKE '%Merkmal%'
 OR COLUMN_NAME LIKE '%Hersteller%'
 OR COLUMN_NAME LIKE '%Steuer%'
 OR COLUMN_NAME LIKE '%Einheit%'
 OR COLUMN_NAME LIKE '%Preis%'
 OR COLUMN_NAME LIKE '%Bestand%'
 OR COLUMN_NAME LIKE '%Artikel%'
 )
ORDER BY ORDINAL_POSITION;

-- C: Spalten der bereits existierenden Zuordnungstabellen
SELECT TABLE_NAME,COLUMN_NAME,DATA_TYPE,ORDINAL_POSITION
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA='dbo'
  AND TABLE_NAME IN ('tArtikelAttribut','tArtikelMerkmal','tArtikelBeschreibung')
ORDER BY TABLE_NAME,ORDINAL_POSITION;

-- D: Datenmenge und Quelle der Zuordnungen
SELECT 'tArtikelAttribut' AS tabelle,COUNT_BIG(*) AS zuordnungen,
       COUNT(DISTINCT kArtikel) AS artikel_mit_zuordnung
FROM dbo.tArtikelAttribut
UNION ALL
SELECT 'tArtikelMerkmal',COUNT_BIG(*),COUNT(DISTINCT kArtikel)
FROM dbo.tArtikelMerkmal;
