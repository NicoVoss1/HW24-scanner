/* HW24 v4.4 · read-only JTL source discovery for manufacturer and actual costs.
   Run against eazybusiness in SSMS/HeidiSQL with SELECT permission.
   No guessed JOINs, no modifications, no product data written.
*/
USE [eazybusiness];

-- A. Discover manufacturer, purchasing, supplier and units source tables
SELECT TABLE_SCHEMA,TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_TYPE='BASE TABLE' AND (
 TABLE_NAME LIKE '%Hersteller%' OR TABLE_NAME LIKE '%Lieferant%' OR
 TABLE_NAME LIKE '%Einkauf%' OR TABLE_NAME LIKE '%Lager%' OR
 TABLE_NAME LIKE '%Preis%' OR TABLE_NAME LIKE '%Einheit%' OR
 TABLE_NAME LIKE '%Warengruppe%' OR TABLE_NAME LIKE '%Artikel%'
)
ORDER BY TABLE_SCHEMA,TABLE_NAME;

-- B. Discover actual reference IDs, readable names, costs and quantities
SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE (
 TABLE_NAME LIKE '%Hersteller%' OR TABLE_NAME LIKE '%Lieferant%' OR
 TABLE_NAME LIKE '%Einkauf%' OR TABLE_NAME LIKE '%Artikel%' OR
 TABLE_NAME LIKE '%Warengruppe%' OR TABLE_NAME LIKE '%Preis%' OR
 TABLE_NAME LIKE '%Lager%' OR TABLE_NAME LIKE '%Einheit%'
) AND (
 COLUMN_NAME LIKE '%Hersteller%' OR COLUMN_NAME LIKE '%Name%' OR
 COLUMN_NAME LIKE '%Lieferant%' OR COLUMN_NAME LIKE '%Kosten%' OR
 COLUMN_NAME LIKE '%EK%' OR COLUMN_NAME LIKE '%Preis%' OR
 COLUMN_NAME LIKE '%Einheit%' OR COLUMN_NAME LIKE '%Menge%' OR
 COLUMN_NAME LIKE '%Lagerbestand%' OR COLUMN_NAME LIKE '%Barcode%'
)
ORDER BY TABLE_SCHEMA,TABLE_NAME,ORDINAL_POSITION;

-- C. Confirm exact article base values for the known test EAN.
SELECT TOP (10)
 a.kArtikel,a.cArtNr,a.cBarcode,a.cHAN,a.kHersteller,a.kWarengruppe,
 a.fEKNetto AS durchschnitt_ek_netto,
 a.fLetzterEK AS letzter_ek,
 a.dLetzterEK AS letzter_ek_datum,
 a.fVKNetto AS verkaufspreis_netto,
 a.nLagerbestand AS artikelstamm_bestand,
 a.dMod AS zuletzt_geaendert
FROM dbo.tArtikel AS a
WHERE a.cBarcode=N'4015600040482'
 OR a.cHAN=N'4015600040482'
 OR a.cArtNr=N'4015600040482';

-- D. Inspect confirmed manufacturer columns before constructing joins:
SELECT COLUMN_NAME,DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='tHersteller'
ORDER BY ORDINAL_POSITION;

-- IMPORTANT: tHersteller is a candidate table name, not yet confirmed.
-- If D returns no rows, use results from A and B, not an invented join.
-- Costs on the JTL screen may come from another table or calculated view.
-- Verify the source first; do not equate "Kosten" with fEKNetto or fLetzterEK.
