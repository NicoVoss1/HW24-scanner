/* JTL Wawi 2017 eazybusiness: identify exact SQL source for
   export column "Kosten" (actual EK), allocated stock, available stock,
   supplier costs and main manufacturer/category names.
   ONLY metadata/SELECT, no changes.
*/
USE [eazybusiness];

SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE
 (COLUMN_NAME LIKE '%Kosten%'
  OR COLUMN_NAME LIKE '%EK%'
  OR COLUMN_NAME LIKE '%Einkauf%'
  OR COLUMN_NAME LIKE '%Reserv%'
  OR COLUMN_NAME LIKE '%Verfueg%'
  OR COLUMN_NAME LIKE '%Verfüg%'
  OR COLUMN_NAME LIKE '%Bestand%')
 AND (
   TABLE_NAME LIKE '%Artikel%'
   OR TABLE_NAME LIKE '%Lager%'
   OR TABLE_NAME LIKE '%Lieferant%'
   OR TABLE_NAME LIKE '%Auftrag%'
   OR TABLE_NAME LIKE '%Bestand%'
 )
ORDER BY TABLE_NAME,COLUMN_NAME;

SELECT TOP (10)
 a.kArtikel,a.cArtNr,a.cBarcode,
 a.fEKNetto AS GLD_Durchschnitt_EK_Netto,
 a.fLetzterEK AS Letzter_EK,
 a.dLetzterEK AS Datum_Letzter_EK,
 a.nLagerbestand AS Lagerbestand_tArtikel
FROM dbo.tArtikel a
WHERE NULLIF(LTRIM(RTRIM(a.cBarcode)),N'') IS NOT NULL
ORDER BY a.kArtikel DESC;
