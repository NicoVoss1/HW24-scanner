-- HW24 / JTL-Wawi / SQL Server 2017 Express / eazybusiness
-- PHASE 15: independently verify the improved 2692/2692 availability
-- relationship, and inspect the sole outlier in the *earlier* hypothesis.
--
-- OBSERVATION from PRIVATE Phase-14 SQL-6 CSV (2,692 rows, 2026-11-23):
-- Original JTL fVerfuegbar matches
--     fLagerbestand - fProduktionGesperrt - fInAuftraegen
-- exactly for all 2,692 rows. The formula with fVerfuegbarGesperrt
-- matches 2,691/2,692; kArtikel 5720 is the sole exception.
--
-- Important: This shows a mathematical relation in this DATASET, not
-- proof of JTL's internal computation across every shop or workflow.
-- Never derive/update marketplace availability from the hypothesis.
-- All queries are SELECT only; no DDL/DML, customer/order rows, prices,
-- credentials, scheduler or API activity. Execute one at a time.

-- 0. Confirm the database context (STOP unless eazybusiness).
SELECT DB_NAME() AS datenbankname;

-- 1. Independently confirm BOTH candidate formulas in the current JTL source.
SELECT
 COUNT_BIG(*) AS source_stock_rows,
 COUNT_BIG(CASE WHEN
    fLagerbestand - fProduktionGesperrt - fInAuftraegen = fVerfuegbar
    THEN 1 END) AS production_formula_matching_rows,
 COUNT_BIG(CASE WHEN
    fLagerbestand - fProduktionGesperrt - fInAuftraegen <> fVerfuegbar
    THEN 1 END) AS production_formula_mismatch_rows,
 SUM(fLagerbestand - fProduktionGesperrt - fInAuftraegen - fVerfuegbar)
    AS production_formula_residual_sum,
 COUNT_BIG(CASE WHEN
    fLagerbestand - fVerfuegbarGesperrt - fInAuftraegen = fVerfuegbar
    THEN 1 END) AS available_blocked_formula_matching_rows,
 COUNT_BIG(CASE WHEN
    fLagerbestand - fVerfuegbarGesperrt - fInAuftraegen <> fVerfuegbar
    THEN 1 END) AS available_blocked_formula_mismatch_rows
FROM dbo.tlagerbestand;

-- 2. Distinguish production vs available-blocked source fields.
-- Technical article IDs and raw numeric fields only. Expected one
-- article (5720) from the private source snapshot; do not edit JTL.
SELECT
 kArtikel AS jtl_article_id,
 fLagerbestand AS raw_stock,
 fVerfuegbarGesperrt AS raw_available_blocked,
 fProduktionGesperrt AS raw_production_blocked,
 fInAuftraegen AS raw_in_orders,
 fVerfuegbar AS raw_jtl_available,
 CONVERT(varchar(18),CONVERT(varbinary(8),bRowversion),1)
    AS jtl_stock_rowversion_hex
FROM dbo.tlagerbestand
WHERE fProduktionGesperrt<>fVerfuegbarGesperrt
ORDER BY kArtikel;

-- 3. Verify whether the 5720 discrepancy has a corresponding warehouse
-- record, and inspect source ARTICLE status without exposing prices.
-- One-row OUTER APPLY avoids grouping SQL Server rowversion/timestamp.
SELECT
 a.kArtikel AS jtl_article_id,
 a.cArtNr AS jtl_article_number,
 a.cAktiv AS jtl_article_active_raw,
 a.nDelete AS jtl_article_deleted_raw,
 a.nLagerbestand AS jtl_article_stock_field_raw,
 a.dMod AS jtl_article_modified_raw,
 g.fLagerbestand AS global_stock_raw,
 g.fVerfuegbar AS global_available_raw,
 g.fVerfuegbarGesperrt AS global_available_blocked_raw,
 g.fProduktionGesperrt AS global_production_blocked_raw,
 g.fInAuftraegen AS global_in_orders_raw,
 w.warehouse_rows,
 w.warehouse_stock_raw,
 CONVERT(varchar(18),CONVERT(varbinary(8),a.bRowversion),1)
    AS article_master_rowversion_hex,
 CONVERT(varchar(18),CONVERT(varbinary(8),g.bRowversion),1)
    AS global_stock_rowversion_hex
FROM dbo.tArtikel AS a
JOIN dbo.tlagerbestand AS g ON g.kArtikel=a.kArtikel
OUTER APPLY (
 SELECT COUNT_BIG(*) AS warehouse_rows,
        COALESCE(SUM(wa.fBestand),0) AS warehouse_stock_raw
 FROM dbo.tlagerbestandProLagerLagerartikel AS wa
 WHERE wa.kArtikel=a.kArtikel
) AS w
WHERE a.kArtikel=5720;

-- 4. Operator-run aggregate by article active/delete flags for the 32
-- central-only article IDs (observed 953 raw stock units).
-- This still does NOT confirm why warehouse assignments are absent.
SELECT
 a.cAktiv AS jtl_article_active_raw,
 a.nDelete AS jtl_article_deleted_raw,
 COUNT_BIG(*) AS central_only_article_ids,
 COUNT_BIG(CASE WHEN g.fLagerbestand>0 THEN 1 END)
     AS positive_stock_articles,
 SUM(g.fLagerbestand) AS central_only_stock_raw,
 SUM(g.fVerfuegbar) AS central_only_available_raw,
 SUM(g.fInAuftraegen) AS central_only_in_orders_raw
FROM dbo.tlagerbestand AS g
JOIN dbo.tArtikel AS a ON a.kArtikel=g.kArtikel
WHERE NOT EXISTS (
 SELECT 1 FROM dbo.tlagerbestandProLagerLagerartikel AS w
 WHERE w.kArtikel=g.kArtikel
)
GROUP BY a.cAktiv,a.nDelete
ORDER BY a.cAktiv,a.nDelete;

-- 5. Optional: bounded technical history for JUST article 5720.
-- No user, comment, customer, price, invoice or document identifier fields.
-- A history record cannot itself prove a CURRENT JTL availability formula.
SELECT TOP (12)
 h.kArtikelHistory AS history_row_id,
 h.dGebucht AS booked_at,
 h.fAnzahl AS movement_quantity_raw,
 h.fLagerBestandGesamt AS historical_stock_total_raw,
 h.fVerfuegbar AS historical_available_raw,
 h.fReserviert AS historical_reserved_raw,
 h.kBuchungsart AS booking_type_id_raw
FROM dbo.tArtikelHistory AS h
WHERE h.kArtikel=5720
ORDER BY h.dGebucht DESC,h.kArtikelHistory DESC;
