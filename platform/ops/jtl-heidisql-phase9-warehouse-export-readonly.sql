-- HW24 / JTL / SQL Server 2017 Express / eazybusiness
-- PHASE 9: first source-controlled, read-only WAREHOUSE STOCK export.
-- Source: dbo.tlagerbestandProLagerLagerartikel, PK(kArtikel,kWarenlager).
-- Operator observations 2026-11-23: 12,717 rows, 1,532 rows fBestand <> 0.
-- IMPORTANT: fBestand, fVerfuegbarGesperrt, fAuslieferungGesperrt are
-- ORIGINAL JTL RAW quantities; no unverified "available" calculation.
-- No write, no customer/order data, no credentials, no SQL configuration,
-- no NOLOCK/READ UNCOMMITTED, no DML or index operations.
-- Read each SELECT separately in the confirmed eazybusiness database.

-- 0. Confirm exact DB context (STOP unless eazybusiness).
SELECT DB_NAME() AS datenbankname;

-- 1. Aggregate quality and validated rowversion lengths (no business IDs).
SELECT
    COUNT_BIG(*) AS lagerdatensaetze,
    COUNT_BIG(CASE WHEN fBestand <> 0 THEN 1 END) AS bestandszeilen_nicht_null,
    COUNT_BIG(CASE WHEN fBestand > 0 THEN 1 END) AS bestandszeilen_positiv,
    COUNT_BIG(CASE WHEN fBestand < 0 THEN 1 END) AS bestandszeilen_negativ,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion) = 8 THEN 1 END) AS rowversion_8_byte,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion) <> 8 OR bRowversion IS NULL
        THEN 1 END) AS rowversion_unbrauchbar
FROM dbo.tlagerbestandProLagerLagerartikel;

-- 2. Group by warehouse ID with names ONLY (no address/person/telephone).
-- These are physical stock RAW values, not available-to-sell quantities.
SELECT
    l.kWarenlager AS jtl_warehouse_id,
    COALESCE(w.cName, N'(no warehouse label)') AS warehouse_name,
    COUNT_BIG(*) AS artikel_lager_zeilen,
    COUNT_BIG(CASE WHEN l.fBestand <> 0 THEN 1 END) AS bestandszeilen_nicht_null,
    COUNT_BIG(CASE WHEN l.fBestand > 0 THEN 1 END) AS bestandszeilen_positiv,
    COUNT_BIG(CASE WHEN l.fBestand < 0 THEN 1 END) AS bestandszeilen_negativ,
    SUM(l.fBestand) AS fBestand_summe_roh,
    SUM(l.fVerfuegbarGesperrt) AS fVerfuegbarGesperrt_summe_roh,
    SUM(l.fAuslieferungGesperrt) AS fAuslieferungGesperrt_summe_roh
FROM dbo.tlagerbestandProLagerLagerartikel AS l
LEFT JOIN dbo.tWarenLager AS w ON w.kWarenLager = l.kWarenlager
GROUP BY l.kWarenlager, w.cName
ORDER BY l.kWarenlager;

-- 3. How many UNIQUE articles have at least one nonzero warehouse-stock row?
-- IMPORTANT: the operator's "1,532" denotes ROWS, not 1,532 unique articles.
SELECT
    COUNT_BIG(DISTINCT CASE WHEN fBestand <> 0 THEN kArtikel END)
        AS artikel_mit_mindestens_einem_bestand_nicht_null,
    COUNT_BIG(DISTINCT kArtikel) AS artikel_mit_lagerzuordnung
FROM dbo.tlagerbestandProLagerLagerartikel;

-- 4. First real warehouse-stock preview (no consumer data).
SELECT TOP (25)
    l.kArtikel AS jtl_article_id,
    l.kWarenlager AS jtl_warehouse_id,
    l.fBestand AS jtl_stock_raw,
    l.fVerfuegbarGesperrt AS jtl_available_blocked_raw,
    l.fAuslieferungGesperrt AS jtl_shipping_blocked_raw,
    CONVERT(varchar(18), CONVERT(varbinary(8), l.bRowversion), 1)
        AS jtl_rowversion_hex
FROM dbo.tlagerbestandProLagerLagerartikel AS l
ORDER BY l.kArtikel, l.kWarenlager;

-- 5. FULL WAREHOUSE-ROW EXPORT, all 12,717 observed key pairs.
-- Save to a NEW PRIVATE local semicolon CSV in HeidiSQL, with column header.
-- Do not post whole rows or stock/SKU values in public GitHub or a web root.
-- The header below is exactly the six-column importer contract.
-- This is NOT an atomic cross-table snapshot with the Phase-6 article export.
SELECT
    l.kArtikel AS jtl_article_id,
    l.kWarenlager AS jtl_warehouse_id,
    l.fBestand AS jtl_stock_raw,
    l.fVerfuegbarGesperrt AS jtl_available_blocked_raw,
    l.fAuslieferungGesperrt AS jtl_shipping_blocked_raw,
    CONVERT(varchar(18), CONVERT(varbinary(8), l.bRowversion), 1)
        AS jtl_rowversion_hex
FROM dbo.tlagerbestandProLagerLagerartikel AS l
ORDER BY l.kArtikel, l.kWarenlager;

-- 6. Count unknown article references without exporting article or order rows.
-- Important before joining JTL article and warehouse pilots.
SELECT
    COUNT_BIG(*) AS stock_rows,
    COUNT_BIG(CASE WHEN a.kArtikel IS NULL THEN 1 END)
        AS stock_rows_without_article_master
FROM dbo.tlagerbestandProLagerLagerartikel AS l
LEFT JOIN dbo.tArtikel AS a ON a.kArtikel=l.kArtikel;
