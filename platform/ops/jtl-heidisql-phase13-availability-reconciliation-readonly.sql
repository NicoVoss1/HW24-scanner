-- HW24 JTL-Wawi / SQL Server 2017 Express / database eazybusiness
-- PHASE 13: Verify global stock, open-order and per-warehouse stock
-- Source metadata/PKs confirmed from Phase 12 operator SQL, 2026-11-23.
-- READ ONLY. No individual orders, customers, addresses or private shop credentials.
-- Do NOT infer current available-to-sell quantities from any trial formula.
-- Each SELECT is separate and does not modify JTL, schema or permissions.

-- 0. Verify source DB; STOP unless eazybusiness.
SELECT DB_NAME() AS datenbankname;

-- 1. The missing original JTL field fInAuftraegen, other raw balances,
-- and rowversion readiness. No article IDs in this aggregate.
SELECT
    COUNT_BIG(*) AS artikel_lagerstatus_zeilen,
    COUNT_BIG(CASE WHEN fInAuftraegen > 0 THEN 1 END) AS artikel_mit_auftragsmenge_positiv,
    COUNT_BIG(CASE WHEN fInAuftraegen < 0 THEN 1 END) AS artikel_mit_auftragsmenge_negativ,
    COUNT_BIG(CASE WHEN fVerfuegbar < 0 THEN 1 END) AS artikel_mit_negativer_verfuegbarkeit,
    SUM(fLagerbestand) AS global_bestand_roh,
    SUM(fVerfuegbar) AS global_verfuegbar_roh,
    SUM(fInAuftraegen) AS global_in_auftraegen_roh,
    SUM(fVerfuegbarGesperrt) AS global_verfuegbar_gesperrt_roh,
    SUM(fProduktionGesperrt) AS global_produktion_gesperrt_roh,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion)=8 THEN 1 END)
        AS rowversions_8_bytes
FROM dbo.tlagerbestand;

-- 2. Test the arithmetic residual of a POSSIBLE formula, per article.
-- This is a hypothesis, not yet the confirmed JTL reservation formula.
SELECT
    COUNT_BIG(*) AS article_rows,
    COUNT_BIG(CASE WHEN
        fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen-fVerfuegbar=0
        THEN 1 END) AS matching_hypothesis_rows,
    COUNT_BIG(CASE WHEN
        fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen-fVerfuegbar<>0
        THEN 1 END) AS nonmatching_hypothesis_rows,
    SUM(fLagerbestand-fVerfuegbarGesperrt-fInAuftraegen-fVerfuegbar)
        AS hypothesis_residual_total
FROM dbo.tlagerbestand;

-- 3. FULL OUTER source reconciliation: current JTL dbo.tlagerbestand vs.
-- sum of its warehouse-article rows. Retains both orphan directions.
-- If queries are run at different times, numbers can legitimately change.
WITH per_warehouse AS (
    SELECT kArtikel,
           SUM(fBestand) AS warehouse_stock_sum_raw,
           SUM(fVerfuegbarGesperrt) AS warehouse_available_blocked_sum_raw
    FROM dbo.tlagerbestandProLagerLagerartikel
    GROUP BY kArtikel
)
SELECT
    COUNT_BIG(*) AS union_article_ids,
    COUNT_BIG(CASE WHEN g.kArtikel IS NOT NULL AND w.kArtikel IS NOT NULL
        THEN 1 END) AS matched_ids,
    COUNT_BIG(CASE WHEN g.kArtikel IS NOT NULL AND w.kArtikel IS NULL
        THEN 1 END) AS global_without_warehouse_ids,
    COUNT_BIG(CASE WHEN g.kArtikel IS NULL AND w.kArtikel IS NOT NULL
        THEN 1 END) AS warehouse_without_global_ids,
    COUNT_BIG(CASE WHEN g.kArtikel IS NOT NULL AND w.kArtikel IS NOT NULL
                      AND g.fLagerbestand<>w.warehouse_stock_sum_raw
        THEN 1 END) AS matched_ids_with_stock_difference,
    SUM(COALESCE(g.fLagerbestand,0)) AS global_stock_sum_raw,
    SUM(COALESCE(w.warehouse_stock_sum_raw,0)) AS warehouse_stock_sum_raw,
    SUM(CASE WHEN g.kArtikel IS NOT NULL AND w.kArtikel IS NOT NULL
         THEN g.fLagerbestand ELSE 0 END) AS matched_global_stock_raw,
    SUM(CASE WHEN g.kArtikel IS NOT NULL AND w.kArtikel IS NOT NULL
         THEN w.warehouse_stock_sum_raw ELSE 0 END) AS matched_warehouse_stock_raw,
    SUM(CASE WHEN g.kArtikel IS NOT NULL AND w.kArtikel IS NULL
         THEN g.fLagerbestand ELSE 0 END) AS global_only_stock_raw,
    SUM(CASE WHEN g.kArtikel IS NULL AND w.kArtikel IS NOT NULL
         THEN w.warehouse_stock_sum_raw ELSE 0 END) AS warehouse_only_stock_raw
FROM dbo.tlagerbestand AS g
FULL OUTER JOIN per_warehouse AS w ON w.kArtikel=g.kArtikel;

-- 4. Only the largest raw differences for SOURCE PRODUCT IDs.
-- Technical article IDs and quantities, no names/barcodes/customer rows.
-- Positive and negative differences both kept. No corrections suggested.
WITH per_warehouse AS (
    SELECT kArtikel, SUM(fBestand) AS warehouse_stock_sum_raw,
           SUM(fVerfuegbarGesperrt) AS warehouse_blocked_sum_raw
    FROM dbo.tlagerbestandProLagerLagerartikel
    GROUP BY kArtikel
)
SELECT TOP (20)
    g.kArtikel AS jtl_article_id,
    g.fLagerbestand AS global_stock_raw,
    w.warehouse_stock_sum_raw,
    g.fLagerbestand-w.warehouse_stock_sum_raw AS stock_difference_raw,
    g.fVerfuegbar AS global_available_raw,
    g.fInAuftraegen AS global_in_orders_raw,
    g.fVerfuegbarGesperrt AS global_blocked_raw,
    w.warehouse_blocked_sum_raw,
    g.fProduktionGesperrt AS global_production_blocked_raw
FROM dbo.tlagerbestand AS g
INNER JOIN per_warehouse AS w ON w.kArtikel=g.kArtikel
WHERE g.fLagerbestand<>w.warehouse_stock_sum_raw
ORDER BY ABS(g.fLagerbestand-w.warehouse_stock_sum_raw) DESC,g.kArtikel;

-- 5. Virtual stock: quantity by raw module warehouse consideration flag.
-- No module GUID, no product names. Does not imply adding to physical stock.
SELECT
    nLagerBeachten AS consider_warehouse_raw,
    COUNT_BIG(*) AS virtual_source_rows,
    COUNT_BIG(DISTINCT kArtikel) AS unique_article_ids,
    COUNT_BIG(CASE WHEN fBestand<>0 THEN 1 END) AS nonzero_virtual_rows,
    SUM(fBestand) AS virtual_stock_sum_raw,
    SUM(fZulauf) AS virtual_incoming_sum_raw,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion)=8 THEN 1 END)
       AS rowversions_8_bytes
FROM dbo.tLagerbestandVirtuell
GROUP BY nLagerBeachten
ORDER BY nLagerBeachten;

-- 6. Explicitly confirm dbo.tLagerbestandShop empty/nonempty.
-- Earlier grouped query yielded only a header (zero groups).
SELECT COUNT_BIG(*) AS shop_stock_rows
FROM dbo.tLagerbestandShop;

-- 7. Metadata-only: check whether dbo.tArtikelHistory has an index
-- supporting bounded history lookups without modifying production.
SELECT i.name AS index_name,
       i.is_primary_key,
       i.is_unique,
       ic.key_ordinal,
       c.name AS column_name,
       ic.is_included_column
FROM sys.indexes AS i
JOIN sys.index_columns AS ic ON ic.object_id=i.object_id
                            AND ic.index_id=i.index_id
JOIN sys.columns AS c ON c.object_id=ic.object_id
                      AND c.column_id=ic.column_id
WHERE i.object_id=OBJECT_ID(N'dbo.tArtikelHistory')
  AND c.name IN (N'kArtikel',N'dGebucht',N'bRowversion')
ORDER BY i.name,ic.key_ordinal,c.name;
