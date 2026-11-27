-- HW24 JTL | SQL Server 2017 Express | eazybusiness
-- Phase 10: reconcile warehouse rows missing article masters (READ ONLY).
-- Operator Phase-9 confirmed 71 unmatched warehouse rows; private CSV join
-- suggests 12 distinct missing article IDs, 8 positive rows/24 raw units,
-- and 32 article masters with no warehouse stock rows.
-- Please run each SELECT separately and compare CURRENT eazybusiness values.
-- No customer names, order data, passwords, API access, or database writes.

-- 0. Confirm database context.
SELECT DB_NAME() AS datenbankname;

-- 1. Totals directly from CURRENT JTL source; no old CSV joins.
SELECT
 COUNT_BIG(*) AS lagerdatensaetze,
 COUNT_BIG(CASE WHEN a.kArtikel IS NULL THEN 1 END)
    AS lagerzeilen_ohne_artikelstamm,
 COUNT_BIG(DISTINCT CASE WHEN a.kArtikel IS NULL THEN l.kArtikel END)
    AS verschiedene_artikelids_ohne_artikelstamm,
 COUNT_BIG(CASE WHEN a.kArtikel IS NULL AND l.fBestand>0 THEN 1 END)
    AS positive_zeilen_ohne_artikelstamm,
 COALESCE(SUM(CASE WHEN a.kArtikel IS NULL THEN l.fBestand ELSE 0 END),0)
    AS rohbestand_ohne_artikelstamm
FROM dbo.tlagerbestandProLagerLagerartikel AS l
LEFT JOIN dbo.tArtikel AS a ON a.kArtikel=l.kArtikel;

-- 2. Only orphan internal JTL article IDs and raw inventory totals.
-- Treat as diagnostic; do NOT delete/update source records.
SELECT
 l.kArtikel AS jtl_article_id_without_current_master,
 COUNT_BIG(*) AS warehouse_rows,
 SUM(l.fBestand) AS raw_stock_sum,
 SUM(l.fVerfuegbarGesperrt) AS raw_available_blocked_sum
FROM dbo.tlagerbestandProLagerLagerartikel AS l
LEFT JOIN dbo.tArtikel AS a ON a.kArtikel=l.kArtikel
WHERE a.kArtikel IS NULL
GROUP BY l.kArtikel
ORDER BY l.kArtikel;

-- 3. Articles with no per-warehouse row (no barcode/price/name revealed).
SELECT
 COUNT_BIG(*) AS article_master_total,
 COUNT_BIG(CASE WHEN stock_keys.kArtikel IS NULL THEN 1 END)
    AS artikel_ohne_lagerzuordnung
FROM dbo.tArtikel AS a
LEFT JOIN (
 SELECT DISTINCT kArtikel
 FROM dbo.tlagerbestandProLagerLagerartikel
) AS stock_keys ON stock_keys.kArtikel=a.kArtikel;

-- 4. Check that WMS site 4 quantities have raw status flags.
-- "available blocked" is an internal source field, NOT confirmed order reserve.
SELECT
 COUNT_BIG(*) AS wms_warehouse_4_rows,
 SUM(fBestand) AS wms_fBestand_raw,
 SUM(fVerfuegbarGesperrt) AS wms_fVerfuegbarGesperrt_raw,
 SUM(fAuslieferungGesperrt) AS wms_fAuslieferungGesperrt_raw,
 COUNT_BIG(CASE WHEN fVerfuegbarGesperrt<>0 THEN 1 END)
    AS wms_rows_with_available_blocked_raw,
 COUNT_BIG(CASE WHEN fVerfuegbarGesperrt>fBestand THEN 1 END)
    AS wms_rows_where_available_blocked_exceeds_stock
FROM dbo.tlagerbestandProLagerLagerartikel
WHERE kWarenlager=4;
