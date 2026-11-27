-- HW24 / JTL SQL Server 2017 / eazybusiness
-- Phase 8: check corrected rowversion export. READ ONLY. No changes to JTL.
-- Background: the original Phase-6 query converted timestamp directly to
-- varchar(18) and produced blank CSV values. Explicit varbinary(8) is verified.
-- IMPORTANT: SQL 2 of the original Phase-6 article export is NOW corrected.
-- If the article data must be refreshed, rerun that entire SELECT as a new
-- private CSV. Do not merge newer rowversions into an older article snapshot.

-- 0) Confirm exact database context. STOP if not eazybusiness.
SELECT DB_NAME() AS datenbankname;

-- 1) Sample matches operator-verified results (except future JTL updates).
SELECT TOP (5)
    kArtikel AS jtl_article_id,
    DATALENGTH(bRowversion) AS rowversion_bytes,
    CONVERT(varchar(18),CONVERT(varbinary(8),bRowversion),1) AS jtl_rowversion_hex
FROM dbo.tArtikel
ORDER BY kArtikel;

-- 2) Check ALL rows have a physical eight-byte rowversion.
SELECT
    COUNT_BIG(*) AS jtl_articles,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion)=8 THEN 1 END)
       AS rowversion_8_byte_rows,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion)<>8
                          OR bRowversion IS NULL THEN 1 END)
       AS rowversion_unusable_rows
FROM dbo.tArtikel;

-- 3) Independent base-vs-joined check before a fresh full CSV:
-- Expected 1 output row per article if reference joins are unique.
SELECT
    COUNT_BIG(*) AS joined_rows,
    COUNT_BIG(DISTINCT a.kArtikel) AS distinct_article_ids
FROM dbo.tArtikel a
LEFT JOIN dbo.tSteuerklasse tk ON tk.kSteuerklasse=a.kSteuerklasse
LEFT JOIN dbo.tLieferStatus ls
    ON ls.kLieferStatus=a.kLieferStatus AND ls.kSprache=1
LEFT JOIN dbo.tMassEinheit mu ON mu.kMassEinheit=a.kMassEinheit;

-- 4) Inspect snapshot-watermark function outputs, NOT a sync token yet.
-- No transaction semantics / CDC / scheduler changes.
SELECT
    CONVERT(varchar(18),CONVERT(varbinary(8),@@DBTS),1)
       AS allocated_db_rowversion_hex,
    CONVERT(varchar(18),CONVERT(varbinary(8),MIN_ACTIVE_ROWVERSION()),1)
       AS minimum_active_rowversion_hex;
