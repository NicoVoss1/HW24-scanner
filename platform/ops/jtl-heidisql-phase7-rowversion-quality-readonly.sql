-- HW24 JTL phase 7 – data quality and rowversion diagnostic (READ ONLY)
-- Microsoft SQL Server 2017, connected to eazybusiness.
-- All statements are SELECT only. Do NOT change JTL indexes or rowversion columns.
-- Execute each query separately and return only the printed metadata/aggregates.
-- No credentials, customer rows, order rows, or raw marketplace APIs are selected.

-- 0. Safety context; STOP if NOT eazybusiness
SELECT DB_NAME() AS datenbankname;

-- 1. Critical: rowversion was EMPTY in all 2692 CSV rows despite NOT NULL.
-- Inspect 5 values directly, including byte length and two SQL encodings.
SELECT TOP (5)
    a.kArtikel,
    DATALENGTH(a.bRowversion) AS rowversion_bytes,
    CASE WHEN a.bRowversion IS NULL THEN 1 ELSE 0 END AS is_rowversion_null,
    CONVERT(varchar(18), CONVERT(varbinary(8),a.bRowversion), 1) AS rowversion_style_1,
    CONVERT(varchar(16), CONVERT(varbinary(8),a.bRowversion), 2) AS rowversion_style_2,
    CONVERT(bigint, CONVERT(binary(8),a.bRowversion)) AS rowversion_bigint_probe
FROM dbo.tArtikel a
ORDER BY a.kArtikel;

-- 2. A more resilient combined metric (no binary fields in the output).
SELECT
    COUNT_BIG(*) AS article_count,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion)=8 THEN 1 END) AS rowversion_8_bytes,
    MIN(CONVERT(bigint,CONVERT(binary(8),bRowversion))) AS min_rowversion_bigint,
    MAX(CONVERT(bigint,CONVERT(binary(8),bRowversion))) AS max_rowversion_bigint
FROM dbo.tArtikel;

-- 3. Raw article unit distribution; raw values are NOT scaled.
SELECT
    a.kMassEinheit AS unit_id,
    COALESCE(mu.cDisplayCode,N'(not assigned)') AS unit_label,
    COUNT_BIG(*) AS article_count,
    COUNT_BIG(CASE WHEN a.fMassMenge=0 THEN 1 END) AS zero_quantity_count,
    COUNT_BIG(CASE WHEN a.fMassMenge>0 AND a.fMassMenge<1 THEN 1 END) AS positive_fraction_under_1
FROM dbo.tArtikel a
LEFT JOIN dbo.tMassEinheit mu ON mu.kMassEinheit=a.kMassEinheit
GROUP BY a.kMassEinheit,mu.cDisplayCode
ORDER BY article_count DESC, unit_id;

-- 4. Barcode integrity, excluding empty and NULL; do not reveal any codes.
WITH barcodes AS (
    SELECT cBarcode, COUNT_BIG(*) AS uses
    FROM dbo.tArtikel
    WHERE cBarcode IS NOT NULL AND LTRIM(RTRIM(cBarcode))<>N''
    GROUP BY cBarcode
)
SELECT
    COUNT_BIG(*) AS distinct_nonempty_barcodes,
    COALESCE(SUM(CASE WHEN uses>1 THEN CONVERT(bigint,1) ELSE CONVERT(bigint,0) END),0)
      AS duplicate_barcode_groups,
    COALESCE(SUM(CASE WHEN uses>1 THEN uses ELSE CONVERT(bigint,0) END),0)
      AS articles_in_duplicate_barcode_groups
FROM barcodes;

-- 5. Check stock base vs per-location summary without claiming full availability.
-- Counts only; does not read any order, address or customer rows.
SELECT
    (SELECT COUNT_BIG(*) FROM dbo.tArtikel) AS article_count,
    (SELECT COUNT_BIG(*) FROM dbo.tArtikel WHERE nLagerbestand<>0) AS nonzero_tArtikel_stock_rows,
    (SELECT COUNT_BIG(*) FROM dbo.tlagerbestandProLagerLagerartikel) AS per_warehouse_stock_rows,
    (SELECT COUNT_BIG(*) FROM dbo.tlagerbestandProLagerLagerartikel
       WHERE fBestand<>0) AS nonzero_per_warehouse_stock_rows;

-- 6. Validate nullable delivery status and missing barcodes (two representations).
SELECT
    COUNT_BIG(*) AS article_count,
    COUNT_BIG(CASE WHEN kLieferStatus=0 THEN 1 END) AS delivery_status_id_0,
    COUNT_BIG(CASE WHEN cBarcode IS NULL THEN 1 END) AS barcode_is_sql_null,
    COUNT_BIG(CASE WHEN cBarcode=N'' THEN 1 END) AS barcode_empty_string,
    COUNT_BIG(CASE WHEN kMassEinheit=0 THEN 1 END) AS mass_unit_id_0,
    COUNT_BIG(CASE WHEN nDelete IS NULL THEN 1 END) AS deleted_flag_sql_null
FROM dbo.tArtikel;
