-- HW24 isolated local SQLite pilot: READ ONLY, no SQL Server connection.
-- This is for HW24_JTL_Artikel_Lager_Isolierter_Datenspiegel_2026-11-23.sqlite
-- Open PRIVATE/local SQLite in DB Browser for SQLite. No scripts in PHP webroot.
-- Existing ARTICLE rows originate from the prior snapshot, whose rowversion
-- column was blank. STOCK rows originate from a newer Phase-9 CSV.
-- They are NOT a transactionally consistent common snapshot.
-- Do not use this as a live e-commerce availability source.

-- 1. Total source row counts (2,692 + 12,717).
SELECT 'article' AS source, COUNT(*) AS total FROM jtl_article_stage
UNION ALL
SELECT 'warehouse', COUNT(*) FROM jtl_warehouse_stock_stage;

-- 2. Product/warehouse view (never silently drop products with no stock).
SELECT jtl_article_id,jtl_article_number,warehouse_name,
       jtl_stock_raw,jtl_available_blocked_raw,
       jtl_shipping_blocked_raw,jtl_warehouse_id,
       warehouse_rowversion_hex
FROM v_jtl_article_warehouse_details
ORDER BY jtl_article_id,jtl_warehouse_id
LIMIT 30;

-- 3. The 71 unmatched warehouse rows remain visible and retained.
SELECT COUNT(*) AS orphan_warehouse_rows,
       COUNT(DISTINCT jtl_article_id) AS orphan_distinct_article_ids
FROM v_jtl_unmatched_warehouse_rows;

-- 4. The 32 article masters with no warehouse rows are still retained.
SELECT COUNT(*) AS articles_without_warehouse_rows
FROM v_jtl_articles_without_warehouse_rows;

-- 5. Source manifest keeps provenance, flags stale rowversions and forbids live use.
SELECT source_database,article_source_table,stock_source_table,
       article_count,stock_rows,unmatched_stock_rows,
       unmatched_article_ids,articles_without_stock_rows,
       article_rowversions_available,stock_rowversions_available,
       is_consistent_cross_table_snapshot,auto_sync_enabled,
       deployed_to_test_or_live
FROM stage_manifest
WHERE id=1;

-- 6. Built-in SQLite consistency check.
PRAGMA integrity_check;
