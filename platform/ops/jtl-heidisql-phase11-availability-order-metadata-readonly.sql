-- HAIRWORLD24 / JTL-Wawi 2.2.2.0 / Microsoft SQL Server 2017 Express
-- Phase 11: read-only METADATA for availability, orders, and stock history.
-- Source database: eazybusiness.
-- This script does NOT read customer/order records; only catalog metadata
-- and aggregate quantities from previously verified warehouse tables.
-- NEVER SELECT * from orders, customers, invoices, shops, or API config.
-- No data writes, index creation, privilege changes, CT/CDC or jobs.
-- Execute one statement at a time; stop unless query 0 is eazybusiness.

-- 0. Safety: confirm DB.
SELECT DB_NAME() AS datenbankname;

-- 1. Verify columns of relevant inventory/order/history tables; metadata only.
-- We do NOT assume an order status, reservation, or field semantic in advance.
SELECT s.name AS schema_name,
       t.name AS table_name,
       c.column_id,
       c.name AS column_name,
       TYPE_NAME(c.user_type_id) AS sql_type,
       c.is_nullable
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
WHERE (s.name=N'dbo' AND t.name IN (
         N'tArtikelHistory',N'tWarenLagerPlatzArtikel',
         N'tLagerbestandShop',N'tlagerbestandProLagerLagerartikel'))
   OR (s.name=N'Verkauf' AND t.name IN (
         N'tAuftragPosition', N'tAuftragPositionEckdaten'))
ORDER BY s.name,t.name,c.column_id;

-- 2. FK relationships involving chosen tables (not business/PII rows).
SELECT OBJECT_SCHEMA_NAME(fk.parent_object_id) AS parent_schema,
       OBJECT_NAME(fk.parent_object_id) AS parent_table,
       pc.name AS parent_column,
       OBJECT_SCHEMA_NAME(fk.referenced_object_id) AS referenced_schema,
       OBJECT_NAME(fk.referenced_object_id) AS referenced_table,
       rc.name AS referenced_column,
       fk.name AS fk_name
FROM sys.foreign_keys AS fk
JOIN sys.foreign_key_columns AS fkc
  ON fkc.constraint_object_id=fk.object_id
JOIN sys.columns AS pc
  ON pc.object_id=fkc.parent_object_id
 AND pc.column_id=fkc.parent_column_id
JOIN sys.columns AS rc
  ON rc.object_id=fkc.referenced_object_id
 AND rc.column_id=fkc.referenced_column_id
WHERE
   (OBJECT_SCHEMA_NAME(fk.parent_object_id)=N'Verkauf'
      AND OBJECT_NAME(fk.parent_object_id) IN
        (N'tAuftragPosition',N'tAuftragPositionEckdaten'))
   OR (OBJECT_SCHEMA_NAME(fk.referenced_object_id)=N'Verkauf'
      AND OBJECT_NAME(fk.referenced_object_id) IN
        (N'tAuftragPosition',N'tAuftragPositionEckdaten'))
   OR (OBJECT_SCHEMA_NAME(fk.parent_object_id)=N'dbo'
      AND OBJECT_NAME(fk.parent_object_id) IN
        (N'tArtikelHistory',N'tWarenLagerPlatzArtikel',
         N'tLagerbestandShop',N'tlagerbestandProLagerLagerartikel'))
ORDER BY parent_schema,parent_table,fk.name,fkc.constraint_column_id;

-- 3. Locate promising reservation/available/stock-history candidates.
-- Only SYSTEM COLUMN NAMES, not customer data, order details or records.
SELECT TOP (150)
       s.name AS schema_name,t.name AS table_name,
       c.name AS column_name,TYPE_NAME(c.user_type_id) AS sql_type
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
WHERE t.is_ms_shipped=0
  AND (c.name LIKE N'%Reserv%'
       OR c.name LIKE N'%Verfuegbar%'
       OR c.name LIKE N'%Gesperrt%'
       OR c.name LIKE N'%Bestand%'
       OR c.name LIKE N'%Bewegung%')
  AND (t.name LIKE N'%Lager%'
       OR t.name LIKE N'%Artikel%'
       OR t.name LIKE N'%Auftrag%'
       OR s.name=N'WMS'
       OR s.name=N'Verkauf')
ORDER BY s.name,t.name,c.name;

-- 4. Warehouse-4 raw "blocked" data quality WITHOUT interpreting orders.
SELECT
    COUNT_BIG(*) AS wms_lager_4_zeilen,
    COUNT_BIG(CASE WHEN fBestand>0 THEN 1 END)
       AS zeilen_mit_rohbestand,
    COUNT_BIG(CASE WHEN fVerfuegbarGesperrt>0 THEN 1 END)
       AS zeilen_mit_verfuegbarkeitssperre,
    COUNT_BIG(DISTINCT CASE WHEN fVerfuegbarGesperrt>0
                  THEN kArtikel END)
       AS unterschiedliche_artikel_mit_sperrwert,
    SUM(fBestand) AS rohbestand,
    SUM(fVerfuegbarGesperrt) AS gesperrt_roh,
    COUNT_BIG(CASE WHEN fVerfuegbarGesperrt>0 AND fBestand=0 THEN 1 END)
       AS sperrwert_bei_bestand_null
FROM dbo.tlagerbestandProLagerLagerartikel
WHERE kWarenlager=4;

-- 5. Orphans grouped by warehouse (only aggregate IDs and stock).
-- DO NOT delete these 71 source rows or assume they are unavailable.
SELECT
 l.kWarenlager AS jtl_warehouse_id,
 COUNT_BIG(*) AS orphan_lagerzeilen,
 COUNT_BIG(DISTINCT l.kArtikel) AS orphan_artikelids,
 COUNT_BIG(CASE WHEN l.fBestand>0 THEN 1 END) AS positiv,
 SUM(l.fBestand) AS rohbestand_orphans
FROM dbo.tlagerbestandProLagerLagerartikel AS l
LEFT JOIN dbo.tArtikel AS a ON a.kArtikel=l.kArtikel
WHERE a.kArtikel IS NULL
GROUP BY l.kWarenlager
ORDER BY l.kWarenlager;
