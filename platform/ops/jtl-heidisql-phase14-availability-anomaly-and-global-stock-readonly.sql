-- HW24 / JTL-Wawi / SQL Server 2017 Express / eazybusiness
-- PHASE 14: explain ONE availability residual and 32 global-only stock rows.
--
-- Operator's Phase 13 results (2026-11-23): source 2,692 rows with 8-byte
-- rowversions; 2,691 matched hypothesis; one row residual -1.
-- Global: fLagerbestand=14568, fVerfuegbar=14107,
-- fVerfuegbarGesperrt=407, fInAuftraegen=55.
-- 2,660 article IDs matched across two stock sources with ZERO differences.
-- 32 central-only IDs had raw 953 units; 12 warehouse-only IDs had 24.
--
-- READ ONLY. No orders, customer data, prices, names, credentials or writes.
-- Only technical kArtikel identifiers and source raw quantities appear.
-- No source row is corrected/deleted. Do not treat a source formula as
-- an approved marketplace/Shopify/eBay available-to-sell formula.
-- Each SELECT is independent and can be run separately.
-- Stop unless the first query returns 'eazybusiness'.

-- 0. Confirm live source database context.
SELECT DB_NAME() AS datenbankname;

-- 1. Pinpoint the ONE article where the proposed availability formula fails.
-- The arithmetic residual is fLagerbestand - fVerfuegbarGesperrt
--                         - fInAuftraegen - fVerfuegbar.
-- A negative residual of -1 means JTL fVerfuegbar is one unit HIGHER.
SELECT
    kArtikel AS jtl_article_id,
    fLagerbestand AS raw_stock,
    fVerfuegbarGesperrt AS raw_available_blocked,
    fInAuftraegen AS raw_in_orders,
    fVerfuegbar AS raw_available,
    fProduktionGesperrt AS raw_production_blocked,
    fAuslieferungGesperrt AS raw_shipping_blocked,
    fLagerbestandEigen AS raw_owned_stock,
    fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen
        AS hypothesis_available,
    fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen-fVerfuegbar
        AS hypothesis_residual,
    CONVERT(varchar(18),CONVERT(varbinary(8),bRowversion),1)
        AS source_rowversion_hex
FROM dbo.tlagerbestand
WHERE fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen
      -fVerfuegbar <> 0
ORDER BY ABS(fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen
             -fVerfuegbar) DESC,kArtikel;

-- 2. Central records with NO per-warehouse rows. This verifies the
-- 32 article IDs and 953 source raw units, without returning names or EANs.
SELECT
    COUNT_BIG(*) AS central_only_article_ids,
    COUNT_BIG(CASE WHEN g.fLagerbestand>0 THEN 1 END)
        AS central_only_positive_stock_article_ids,
    COUNT_BIG(CASE WHEN g.fVerfuegbar<0 THEN 1 END)
        AS central_only_negative_available_article_ids,
    SUM(g.fLagerbestand) AS central_only_stock_raw,
    SUM(g.fVerfuegbar) AS central_only_available_raw,
    SUM(g.fVerfuegbarGesperrt) AS central_only_blocked_raw,
    SUM(g.fInAuftraegen) AS central_only_in_orders_raw,
    SUM(g.fProduktionGesperrt) AS central_only_production_blocked_raw
FROM dbo.tlagerbestand AS g
WHERE NOT EXISTS (
    SELECT 1
    FROM dbo.tlagerbestandProLagerLagerartikel AS w
    WHERE w.kArtikel=g.kArtikel
);

-- 3. Inspect at most 15 largest central-only records to determine their
-- raw stock class; DO NOT silently create warehouse records or infer a location.
SELECT TOP (15)
    g.kArtikel AS jtl_article_id,
    g.fLagerbestand AS raw_stock,
    g.fVerfuegbar AS raw_available,
    g.fInAuftraegen AS raw_in_orders,
    g.fVerfuegbarGesperrt AS raw_available_blocked,
    g.fProduktionGesperrt AS raw_production_blocked
FROM dbo.tlagerbestand AS g
WHERE NOT EXISTS (
    SELECT 1
    FROM dbo.tlagerbestandProLagerLagerartikel AS w
    WHERE w.kArtikel=g.kArtikel
)
ORDER BY ABS(g.fLagerbestand) DESC,g.kArtikel;

-- 4. The two negative JTL available amounts are RAW states.
-- Do NOT change, suppress or coerce them to zero in the source/mirror.
SELECT
    g.kArtikel AS jtl_article_id,
    g.fLagerbestand AS raw_stock,
    g.fVerfuegbar AS raw_available,
    g.fInAuftraegen AS raw_in_orders,
    g.fVerfuegbarGesperrt AS raw_available_blocked,
    g.fProduktionGesperrt AS raw_production_blocked
FROM dbo.tlagerbestand AS g
WHERE g.fVerfuegbar<0
ORDER BY g.fVerfuegbar,g.kArtikel;

-- 5. Production-blocked versus available-blocked overlap, do not subtract
-- both fields independently until their documented JTL semantics are verified.
SELECT
    COUNT_BIG(*) AS central_rows,
    COUNT_BIG(CASE WHEN fProduktionGesperrt<>0 THEN 1 END)
        AS rows_production_blocked_nonzero,
    COUNT_BIG(CASE WHEN fVerfuegbarGesperrt<>0 THEN 1 END)
        AS rows_available_blocked_nonzero,
    COUNT_BIG(CASE WHEN fProduktionGesperrt<>0
                        AND fVerfuegbarGesperrt<>0 THEN 1 END)
        AS rows_with_both_block_flags,
    COUNT_BIG(CASE WHEN fProduktionGesperrt>fVerfuegbarGesperrt
                   THEN 1 END)
        AS rows_prod_blocked_gt_avail_blocked,
    SUM(fProduktionGesperrt) AS production_blocked_sum_raw,
    SUM(fVerfuegbarGesperrt) AS available_blocked_sum_raw
FROM dbo.tlagerbestand;

-- 6. OPTIONAL NEXT PRIVATE BASELINE EXPORT (not needed for SQL 0-5).
-- Only after the discrepancy is reviewed, execute separately in HeidiSQL.
-- Save with headers as a NEW private semicolon CSV OUTSIDE application root.
-- No SQL Server write, no overwrite of existing article/warehouse snapshots.
-- This is a separate-time snapshot, NOT a multi-table consistent snapshot.
-- All quantities are original SQL decimal values and do NOT have a derived
-- "shop quantity" field. 8-byte rowversion is converted to exact hex text.
SELECT
    g.kArtikel AS jtl_article_id,
    g.fLagerbestand AS jtl_stock_raw,
    g.fLagerbestandEigen AS jtl_owned_stock_raw,
    g.fVerfuegbar AS jtl_available_raw,
    g.fInAuftraegen AS jtl_in_orders_raw,
    g.fVerfuegbarGesperrt AS jtl_available_blocked_raw,
    g.fProduktionGesperrt AS jtl_production_blocked_raw,
    g.fAuslieferungGesperrt AS jtl_shipping_blocked_raw,
    g.fZulauf AS jtl_incoming_raw,
    g.fAufEinkaufsliste AS jtl_purchase_list_raw,
    CONVERT(varchar(23),g.dLieferdatum,121) AS jtl_delivery_date_raw,
    CONVERT(varchar(18),CONVERT(varbinary(8),g.bRowversion),1)
        AS jtl_rowversion_hex
FROM dbo.tlagerbestand AS g
ORDER BY g.kArtikel;
