/* HW24 · JTL 2017 Quellstruktur für vollständige Artikelpflege.
   eazybusiness: ONLY read system schema, no updates, no users.
   Use results to map manufacturer/category/fulfilment human names.
*/
USE [eazybusiness];

-- 1. Relevant existing metadata tables: no guessed JOINs.
SELECT
  TABLE_SCHEMA,TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_TYPE='BASE TABLE'
  AND (
   TABLE_NAME LIKE '%Hersteller%'
   OR TABLE_NAME LIKE '%Warengruppe%'
   OR TABLE_NAME LIKE '%LieferStatus%'
   OR TABLE_NAME LIKE '%Versandklasse%'
   OR TABLE_NAME LIKE '%Benutzer%'
   OR TABLE_NAME LIKE '%Lagerbestand%'
   OR TABLE_NAME LIKE '%Lagerplatz%'
   OR TABLE_NAME LIKE '%Auftrag%'
   OR TABLE_NAME LIKE '%Reservier%'
   OR TABLE_NAME LIKE '%Kategorie%'
  )
ORDER BY TABLE_NAME;

-- 2. Which columns identify foreign-key IDs and readable names?
SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE (
  TABLE_NAME LIKE '%Hersteller%'
  OR TABLE_NAME LIKE '%Warengruppe%'
  OR TABLE_NAME LIKE '%LieferStatus%'
  OR TABLE_NAME LIKE '%Versandklasse%'
  OR TABLE_NAME LIKE '%Benutzer%'
  OR TABLE_NAME LIKE '%Lagerplatz%'
)
AND (
  COLUMN_NAME LIKE 'k%'
  OR COLUMN_NAME LIKE 'c%'
  OR COLUMN_NAME LIKE '%Name%'
)
ORDER BY TABLE_NAME,ORDINAL_POSITION;

-- 3. Existing product-level fields that we already confirmed in user's
--    SQL Server metadata export. The result includes internal reference IDs.
SELECT TOP (5)
 a.kArtikel,a.cArtNr,a.cBarcode,a.kHersteller,a.kWarengruppe,
 a.kLieferStatus,a.kVersandklasse,a.kSteuerklasse,
 a.cAktiv,a.cTopArtikel,a.cTeilbar,a.cLagerAktiv,a.cLagerKleinerNull,
 a.nBearbeitungszeit,a.nLiefertageWennAusverkauft,
 a.nAutomatischeLiefertageberechnung,
 a.nLagerbestand,a.fEKNetto,a.fLetzterEK,a.dLetzterEK,
 a.cAnmerkung,a.kBenutzerLetzteAenderung,a.dMod
FROM dbo.tArtikel AS a ORDER BY a.kArtikel DESC;
