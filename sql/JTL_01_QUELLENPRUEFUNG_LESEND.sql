/* HW24 / JTL-Wawi: SQL Server 2017 (eazybusiness)
   Read-only inventory of actual JTL content & description variants.
   Run in HeidiSQL with eazybusiness selected.
   Does NOT create / edit tables, users, or records.
*/
USE [eazybusiness];

-- A: Current source size, identifier coverage, changes
SELECT
    COUNT_BIG(*) AS artikel_gesamt,
    SUM(CASE WHEN NULLIF(LTRIM(RTRIM(cBarcode)), N'') IS NOT NULL THEN 1 ELSE 0 END) AS artikel_mit_barcode,
    COUNT(DISTINCT NULLIF(LTRIM(RTRIM(cBarcode)), N'')) AS unterschiedliche_barcodes,
    SUM(CASE WHEN NULLIF(LTRIM(RTRIM(cASIN)), N'') IS NOT NULL THEN 1 ELSE 0 END) AS artikel_mit_asin,
    SUM(CASE WHEN NULLIF(LTRIM(RTRIM(cHAN)), N'') IS NOT NULL THEN 1 ELSE 0 END) AS artikel_mit_han,
    MIN(dMod) AS aelteste_aenderung,
    MAX(dMod) AS neueste_aenderung
FROM dbo.tArtikel;

-- B: Which language/platform/shop combination is used for descriptions?
-- A single article may have several rows here. Do not arbitrary overwrite.
SELECT
    kSprache,
    kPlattform,
    kShop,
    COUNT_BIG(*) AS beschreibungszeilen,
    COUNT(DISTINCT kArtikel) AS unterschiedliche_artikel,
    SUM(CASE WHEN NULLIF(LTRIM(RTRIM(cName)), N'') IS NOT NULL THEN 1 ELSE 0 END) AS mit_artikelname,
    SUM(CASE WHEN NULLIF(LTRIM(RTRIM(cBeschreibung)), N'') IS NOT NULL THEN 1 ELSE 0 END) AS mit_beschreibung
FROM dbo.tArtikelBeschreibung
GROUP BY kSprache,kPlattform,kShop
ORDER BY beschreibungszeilen DESC;

-- C: Example article identifiers and commercially useful values.
-- Prices are NET; inventory is the database value, not automatically available-for-sale.
SELECT TOP (10)
    kArtikel, cArtNr, cBarcode, cASIN, cHAN,
    fVKNetto, fEKNetto, fAmazonVK, fEbayPreis,
    nLagerbestand, dMod,
    CONVERT(VARCHAR(18), bRowversion, 1) AS artikel_rowversion
FROM dbo.tArtikel
WHERE NULLIF(LTRIM(RTRIM(cBarcode)),N'') IS NOT NULL
ORDER BY kArtikel DESC;

-- D: Detect duplicate barcode mappings (preview).
SELECT TOP (20)
    LTRIM(RTRIM(cBarcode)) AS barcode,
    COUNT_BIG(*) AS anzahl_artikel
FROM dbo.tArtikel
WHERE NULLIF(LTRIM(RTRIM(cBarcode)),N'') IS NOT NULL
GROUP BY LTRIM(RTRIM(cBarcode))
HAVING COUNT_BIG(*) > 1
ORDER BY anzahl_artikel DESC,barcode;
