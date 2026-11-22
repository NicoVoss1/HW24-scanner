-- HW24 eazybusiness | Phase 6: first real article data extraction (READ-ONLY).
-- SQL Server 2017 / HeidiSQL; only run after verifying DB_NAME() = eazybusiness.
-- NO writes to JTL; no SQL Agent/CDC/CT changes; no OPENROWSET/OUTFILE/EXEC.
-- Never post full article/pricing data or account details in PUBLIC GitHub.
-- Queries 1 and 2 show business-price data; keep results private.
-- No secrets, customer names/IDs, order data or credentials are selected.
-- Purpose: prove source joins, then export the 2,692 observed JTL articles locally.
-- The output is a point-in-time observation, NOT a transactionally consistent
-- cross-table snapshot. Do not feed a live mirror until consistency is designed.

-- 0. Confirm intended database. STOP if name differs.
SELECT DB_NAME() AS datenbankname;

-- 1. First real row preview (max 15 articles).
-- Every alias says clearly whether it is an original source value or a label.
-- Do NOT apply arbitrary /1000 correction to fMassMenge.
SELECT TOP (15)
    a.kArtikel                   AS jtl_article_id,
    a.cArtNr                     AS jtl_article_number,
    a.cBarcode                   AS jtl_barcode,
    a.cAktiv                     AS jtl_active_raw,
    a.nDelete                    AS jtl_deleted_raw,
    a.fVKNetto                   AS jtl_sales_net_raw,
    a.fUVP                       AS jtl_uvp_raw,
    a.fAmazonVK                  AS jtl_amazon_price_field_raw,
    a.fEbayPreis                 AS jtl_ebay_price_field_raw,
    a.kSteuerklasse              AS jtl_tax_class_id,
    tk.cName                     AS jtl_tax_class_label,
    a.kLieferStatus              AS jtl_delivery_status_id,
    ls.cName                     AS jtl_delivery_label_language_1,
    a.kMassEinheit               AS jtl_unit_id,
    mu.cDisplayCode              AS jtl_unit_symbol,
    a.fMassMenge                 AS jtl_unit_quantity_raw,
    a.nLagerbestand              AS jtl_article_stock_raw,
    a.dMod                       AS jtl_last_modified_raw,
    CONVERT(varchar(18), a.bRowversion, 1) AS jtl_rowversion_hex
FROM dbo.tArtikel AS a
LEFT JOIN dbo.tSteuerklasse AS tk ON tk.kSteuerklasse=a.kSteuerklasse
LEFT JOIN dbo.tLieferStatus AS ls
    ON ls.kLieferStatus=a.kLieferStatus AND ls.kSprache=1
LEFT JOIN dbo.tMassEinheit AS mu ON mu.kMassEinheit=a.kMassEinheit
ORDER BY a.kArtikel;

-- 2. First article-base export (ALL articles, same exact allowlist as Query 1).
-- In HeidiSQL, export this SELECT result into a CSV on YOUR PRIVATE computer.
-- Use UTF-8 and column headers; preserve IDs as strings and raw decimal scale.
-- DO NOT upload to a public GitHub repo or paste 2,692 rows into chat.
-- CSV export belongs to operator, not to SQL Server. Query itself is SELECT only.
SELECT
    a.kArtikel                   AS jtl_article_id,
    a.cArtNr                     AS jtl_article_number,
    a.cBarcode                   AS jtl_barcode,
    a.cAktiv                     AS jtl_active_raw,
    a.nDelete                    AS jtl_deleted_raw,
    a.fVKNetto                   AS jtl_sales_net_raw,
    a.fUVP                       AS jtl_uvp_raw,
    a.fAmazonVK                  AS jtl_amazon_price_field_raw,
    a.fEbayPreis                 AS jtl_ebay_price_field_raw,
    a.kSteuerklasse              AS jtl_tax_class_id,
    tk.cName                     AS jtl_tax_class_label,
    a.kLieferStatus              AS jtl_delivery_status_id,
    ls.cName                     AS jtl_delivery_label_language_1,
    a.kMassEinheit               AS jtl_unit_id,
    mu.cDisplayCode              AS jtl_unit_symbol,
    a.fMassMenge                 AS jtl_unit_quantity_raw,
    a.nLagerbestand              AS jtl_article_stock_raw,
    a.dMod                       AS jtl_last_modified_raw,
    CONVERT(varchar(18), a.bRowversion, 1) AS jtl_rowversion_hex
FROM dbo.tArtikel AS a
LEFT JOIN dbo.tSteuerklasse AS tk ON tk.kSteuerklasse=a.kSteuerklasse
LEFT JOIN dbo.tLieferStatus AS ls
    ON ls.kLieferStatus=a.kLieferStatus AND ls.kSprache=1
LEFT JOIN dbo.tMassEinheit AS mu ON mu.kMassEinheit=a.kMassEinheit
ORDER BY a.kArtikel;

-- 3. Aggregate validation: if base count differs from the export count,
-- investigate concurrent writes and joins before any import.
SELECT
    COUNT_BIG(*) AS jtl_articles_total,
    COUNT_BIG(CASE WHEN nDelete=1 THEN 1 END) AS jtl_deleted_flag_1,
    COUNT_BIG(CASE WHEN kLieferStatus IS NULL THEN 1 END) AS jtl_delivery_status_null,
    COUNT_BIG(CASE WHEN kMassEinheit IS NULL THEN 1 END) AS jtl_mass_unit_null,
    COUNT_BIG(CASE WHEN cBarcode IS NULL OR cBarcode=N'' THEN 1 END) AS jtl_missing_barcode
FROM dbo.tArtikel;

-- 4. Price detail preview: DO NOT expose customer IDs.
-- These rows are JTL price contexts, not yet a validated final sales price.
SELECT TOP (25)
    p.kPreis AS jtl_price_header_id,
    p.kArtikel AS jtl_article_id,
    p.kKundenGruppe AS jtl_customer_group_id,
    p.kShop AS jtl_shop_id,
    CASE WHEN p.kKunde=0 THEN N'general'
         ELSE N'customer_specific' END AS price_scope,
    d.nAnzahlAb AS tier_quantity_from,
    d.fNettoPreis AS price_net_raw,
    d.fProzent AS percentage_raw
FROM dbo.tPreis AS p
LEFT JOIN dbo.tPreisDetail AS d ON d.kPreis=p.kPreis
ORDER BY p.kPreis, d.nAnzahlAb;

-- 5. Tax zone label for correct sales-gross mapping; this reveals no personal data.
SELECT kSteuerzone, kFirma, cName, cLandISO
FROM dbo.tSteuerzone
ORDER BY kSteuerzone;

-- 6. Currency dictionary SCHEMA ONLY: column names/types of dbo.tWaehrung.
SELECT c.column_id, c.name AS column_name, ty.name AS sql_type, c.is_nullable
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE s.name=N'dbo' AND t.name=N'tWaehrung'
ORDER BY c.column_id;

-- 7. Marketplace selling-price context (max 25, NO seller ID/API keys).
-- DO NOT label these prices 'live' or 'gross' until API/feed semantics verified.
SELECT TOP (25)
    o.kOffer AS jtl_offer_id,
    o.kArtikel AS jtl_article_id,
    o.kShop AS jtl_shop_id,
    o.nStatus AS offer_status_raw,
    o.nIsListed AS is_listed_in_jtl,
    op.kOfferPrice AS jtl_offer_price_id,
    op.kChannelPriceType AS channel_price_type_id,
    ct.cPriceTypeId AS price_type_code,
    ct.cDisplayName AS price_type_label,
    op.fLastPrice AS last_channel_price_raw,
    op.fQuantity AS channel_price_quantity_raw
FROM SCX.tOfferPrice AS op
JOIN SCX.tOffer AS o ON o.kOffer=op.kOffer
JOIN SCX.tChannelPriceType AS ct
     ON ct.kChannelPriceType=op.kChannelPriceType
ORDER BY op.kOfferPrice;
