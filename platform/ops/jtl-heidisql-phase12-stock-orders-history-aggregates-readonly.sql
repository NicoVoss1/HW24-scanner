-- HW24 / JTL Wawi / SQL Server 2017 Express / eazybusiness
-- PHASE 12: CURRENT stock, shop stock, aggregate reservations and history
-- READ ONLY. Based on operator-confirmed Phase-11 sys.columns/foreign_keys.
--
-- Privacy: NO individual orders, customers, names, addresses, account IDs,
-- comments, user IDs, order numbers, invoice records or API credentials.
-- Only metadata and aggregated counts/sums are returned.
-- IMPORTANT: Orders are restricted source data: the SELECTs aggregate them,
-- and must remain operator-run, without enabling copying order rows to mirror.
-- NO ALTER, INSERT, UPDATE, DELETE, TRUNCATE, index changes, CT, CDC or jobs.
-- Execute every query separately. STOP if DB_NAME() differs.

-- 0. Confirm source DB.
SELECT DB_NAME() AS datenbankname;

-- 1. SOURCE DISCOVERY: all fields of current dbo.tlagerbestand,
-- dbo.tLagerbestandShop and dbo.tLagerbestandVirtuell; selected fields
-- of order headers / eckdaten. Only column names and types, NOT records.
SELECT s.name AS schema_name,
       t.name AS table_name,
       c.column_id AS column_position,
       c.name AS column_name,
       TYPE_NAME(c.user_type_id) AS sql_type,
       c.is_nullable
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
WHERE (s.name=N'dbo' AND t.name IN
       (N'tlagerbestand',N'tLagerbestandShop',N'tLagerbestandVirtuell'))
   OR (s.name=N'Verkauf' AND t.name IN
       (N'tAuftrag',N'tAuftragEckdaten') AND (
          c.name IN (N'kAuftrag',N'nReserviert',N'nStatus',
                     N'nAuftragStatus',N'nIstFuerAuslieferungGesperrt',
                     N'dErstellt',N'dErstelltUTC',N'dErstelltUtc')
          OR c.name LIKE N'%Status%'
          OR c.name LIKE N'%Sperre%'
       ))
ORDER BY s.name,t.name,c.column_id;

-- 2. Explicit primary/unique index KEY columns on the stock tables.
-- Metadata only. Prevents guessing wrong stock identity/upsert semantics.
SELECT s.name AS schema_name,
       t.name AS table_name,
       i.name AS index_name,
       i.is_primary_key,
       i.is_unique,
       ic.key_ordinal AS key_position,
       c.name AS key_column_name
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.indexes AS i ON i.object_id=t.object_id
JOIN sys.index_columns AS ic
   ON ic.object_id=i.object_id AND ic.index_id=i.index_id
JOIN sys.columns AS c
   ON c.object_id=ic.object_id AND c.column_id=ic.column_id
WHERE s.name=N'dbo' AND t.name IN
       (N'tlagerbestand',N'tLagerbestandShop',N'tLagerbestandVirtuell')
  AND (i.is_primary_key=1 OR i.is_unique=1)
  AND ic.key_ordinal>0
ORDER BY t.name,i.name,ic.key_ordinal;

-- 3. Direct comparison of current stock raw fields.
-- No joins, therefore no arbitrary warehouse or shop assignment.
-- JTL fVerfuegbar is a candidate current value, NOT yet a validated
-- per-channel quantity or booking/marketplace stock.
SELECT
 COUNT_BIG(*) AS current_stock_source_rows,
 COUNT_BIG(CASE WHEN fLagerbestand<>0 THEN 1 END)
   AS nonzero_fLagerbestand_rows,
 COUNT_BIG(CASE WHEN fVerfuegbar<>0 THEN 1 END)
   AS nonzero_fVerfuegbar_rows,
 SUM(fLagerbestand) AS sum_fLagerbestand_raw,
 SUM(fLagerbestandEigen) AS sum_fLagerbestandEigen_raw,
 SUM(fVerfuegbar) AS sum_fVerfuegbar_raw,
 SUM(fVerfuegbarGesperrt) AS sum_fVerfuegbarGesperrt_raw,
 SUM(fAuslieferungGesperrt) AS sum_fAuslieferungGesperrt_raw,
 SUM(fProduktionGesperrt) AS sum_fProduktionGesperrt_raw
FROM dbo.tlagerbestand;

-- 4. Shopify / marketplace stock snapshot source grouped by kShop.
-- No individual product/SKU lines. Shop naming from allowlisted dbo.tShop.
-- dBestandsdatum is a SOURCE TIME, not live API freshness guarantee.
SELECT
 s.kShop AS jtl_shop_id,
 COALESCE(sh.cName,N'(unmapped shop)') AS jtl_shop_label,
 COUNT_BIG(*) AS source_rows,
 COUNT_BIG(CASE WHEN s.fAnzahl IS NULL THEN 1 END) AS null_quantity_rows,
 COUNT_BIG(CASE WHEN s.fAnzahl>0 THEN 1 END) AS positive_quantity_rows,
 SUM(s.fAnzahl) AS quantity_sum_raw,
 MIN(s.dBestandsdatum) AS first_stock_date_raw,
 MAX(s.dBestandsdatum) AS last_stock_date_raw
FROM dbo.tLagerbestandShop AS s
LEFT JOIN dbo.tShop AS sh ON sh.kShop=s.kShop
GROUP BY s.kShop,sh.cName
ORDER BY s.kShop;

-- 5. Aggregated source order open quantities by RAW Eckdaten status/type.
-- No order/customer/position identifiers leave SQL Server. DO NOT label
-- these quantities reserved or available before status and type mapping.
SELECT
 e.nAuftragStatus AS jtl_order_status_raw,
 e.nArtikelTyp AS jtl_order_article_type_raw,
 e.nIstUmlagerung AS is_transfer_raw,
 e.nVersandSperre AS shipping_blocked_raw,
 COUNT_BIG(*) AS order_position_details,
 COUNT_BIG(CASE WHEN e.fAnzahlOffen>0 THEN 1 END)
    AS positions_with_open_quantity,
 SUM(e.fAnzahlOffen) AS open_quantity_raw,
 SUM(e.fAnzahlGeliefert) AS delivered_quantity_raw
FROM Verkauf.tAuftragPositionEckdaten AS e
GROUP BY e.nAuftragStatus,e.nArtikelTyp,
         e.nIstUmlagerung,e.nVersandSperre
ORDER BY e.nAuftragStatus,e.nArtikelTyp,
         e.nIstUmlagerung,e.nVersandSperre;

-- 6. Operator-run aggregate: order-position nReserviert flag vs
-- actual open quantity and Eckdaten status; no line items exported.
-- Verified join: Verkauf.tAuftragPositionEckdaten.kAuftragPosition
-- -> Verkauf.tAuftragPosition.kAuftragPosition (FK Phase 11).
SELECT
 p.nReserviert AS position_reserved_flag_raw,
 e.nAuftragStatus AS order_status_raw,
 p.nType AS position_type_raw,
 COUNT_BIG(*) AS source_position_rows,
 COUNT_BIG(CASE WHEN e.kAuftragPosition IS NULL THEN 1 END)
    AS without_eckdaten,
 COUNT_BIG(CASE WHEN e.fAnzahlOffen>0 THEN 1 END)
    AS with_open_quantity,
 SUM(p.fAnzahl) AS position_quantity_raw,
 SUM(e.fAnzahlOffen) AS open_quantity_raw
FROM Verkauf.tAuftragPosition AS p
LEFT JOIN Verkauf.tAuftragPositionEckdaten AS e
  ON e.kAuftragPosition=p.kAuftragPosition
GROUP BY p.nReserviert,e.nAuftragStatus,p.nType
ORDER BY p.nReserviert,e.nAuftragStatus,p.nType;

-- 7. Movement history: bounded recent 14-day aggregate only.
-- Source fReserviert and fVerfuegbar refer to recorded historic states,
-- not a guaranteed current availability feed. No history comments,
-- users, batches, documents, purchase prices or invoice references.
SELECT
 CONVERT(date,h.dGebucht) AS booking_day,
 COUNT_BIG(*) AS movement_rows,
 SUM(h.fAnzahl) AS movement_quantity_sum_raw,
 COUNT_BIG(DISTINCT h.kArtikel) AS distinct_article_ids,
 MIN(h.dGebucht) AS earliest_booking_time,
 MAX(h.dGebucht) AS latest_booking_time
FROM dbo.tArtikelHistory AS h
WHERE h.dGebucht>=DATEADD(DAY,-14,GETDATE())
GROUP BY CONVERT(date,h.dGebucht)
ORDER BY booking_day DESC;
