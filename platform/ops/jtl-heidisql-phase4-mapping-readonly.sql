-- HW24 / eazybusiness / HeidiSQL Phase 4: dictionary for article, pricing, status, accounts.
-- SQL Server 2017 (14.x). ONLY SELECT from metadata; NO article/customer/order values.
-- Execute queries separately in the verified eazybusiness connection.
-- NEVER switch on CDC/Change Tracking or change JTL table structure in this task.

-- Query 0: confirm database context; STOP if value differs
SELECT DB_NAME() AS datenbankname;

-- Query 1: candidate reference tables, including SQL schema and row-count ESTIMATE
-- Presence is evidence; missing names are not errors.
SELECT s.name AS schema_name, t.name AS table_name,
       SUM(CONVERT(bigint, p.rows)) AS ungefaehre_zeilen
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.partitions AS p ON p.object_id=t.object_id AND p.index_id IN (0,1)
WHERE t.is_ms_shipped=0 AND (
  (s.name=N'Artikel' AND t.name IN (N'tArtikelType',N'tArtikelAsin',N'tArtikelkosten'))
  OR (s.name=N'dbo' AND t.name IN (
    N'tPreis',N'tArtikel',N'tArtikelShop',N'tShop',N'tSteuerklasse',
    N'tSteuersatz',N'tLieferstatus',N'tLieferStatus',
    N'tMassEinheit',N'tWarenlager',N'tWarenLager'))
  OR (s.name=N'SCX' AND t.name IN (
    N'tChannelPriceType',N'tOffer',N'tOfferPrice',N'tShop',
    N'tVerkaufskanalArtikelData'))
)
GROUP BY s.name,t.name
ORDER BY s.name,t.name;

-- Query 2: complete *column names/types* of candidate reference tables.
-- No data rows, prices, private customer details, account credentials or API payloads.
SELECT s.name AS schema_name,t.name AS table_name,c.column_id,
       c.name AS column_name,ty.name AS sql_type,
       c.is_nullable,c.is_identity
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE t.is_ms_shipped=0 AND (
  (s.name=N'Artikel' AND t.name IN (N'tArtikelType',N'tArtikelAsin',N'tArtikelkosten'))
  OR (s.name=N'dbo' AND t.name IN (
    N'tPreis',N'tArtikelShop',N'tShop',N'tSteuerklasse',N'tSteuersatz',
    N'tLieferstatus',N'tLieferStatus',N'tMassEinheit',N'tWarenlager',N'tWarenLager'))
  OR (s.name=N'SCX' AND t.name IN (
    N'tChannelPriceType',N'tVerkaufskanalArtikelData',N'tShop'))
)
ORDER BY s.name,t.name,c.column_id;

-- Query 3: foreign key relationships of reference tables / shops / prices.
SELECT
    ps.name AS parent_schema,pt.name AS parent_table,
    pc.name AS parent_column,
    rs.name AS referenced_schema,rt.name AS referenced_table,
    rc.name AS referenced_column,
    fk.name AS fk_name
FROM sys.foreign_key_columns AS fkc
JOIN sys.foreign_keys AS fk ON fk.object_id=fkc.constraint_object_id
JOIN sys.tables AS pt ON pt.object_id=fkc.parent_object_id
JOIN sys.schemas AS ps ON ps.schema_id=pt.schema_id
JOIN sys.columns AS pc ON pc.object_id=pt.object_id AND pc.column_id=fkc.parent_column_id
JOIN sys.tables AS rt ON rt.object_id=fkc.referenced_object_id
JOIN sys.schemas AS rs ON rs.schema_id=rt.schema_id
JOIN sys.columns AS rc ON rc.object_id=rt.object_id AND rc.column_id=fkc.referenced_column_id
WHERE
  (ps.name=N'dbo' AND pt.name IN (
    N'tPreis',N'tArtikelShop',N'tShop',N'pf_amazon_angebot',N'pf_amazon_angebot_ext'))
  OR (rs.name=N'dbo' AND rt.name IN (
    N'tPreis',N'tShop',N'tSteuerklasse',N'tSteuersatz',
    N'tLieferstatus',N'tLieferStatus',N'tMassEinheit'))
  OR (ps.name=N'Artikel' AND pt.name IN (
    N'tArtikelType',N'tArtikelkosten',N'tArtikelAsin'))
  OR (ps.name=N'SCX' AND pt.name IN (
    N'tChannelPriceType',N'tOffer',N'tOfferPrice',N'tVerkaufskanalArtikelData'))
ORDER BY ps.name,pt.name,fk.name,fkc.constraint_column_id;

-- Query 4: check whether each critical rowversion column is an indexed key.
-- An index INCLUDE is not equivalent to an efficient rowversion range seek.
SELECT
 s.name AS schema_name, t.name AS table_name,c.name AS column_name,
 CASE WHEN EXISTS (
    SELECT 1 FROM sys.index_columns ic
    JOIN sys.indexes i ON i.object_id=ic.object_id AND i.index_id=ic.index_id
    WHERE ic.object_id=c.object_id AND ic.column_id=c.column_id
      AND ic.key_ordinal>0 AND i.is_disabled=0
 ) THEN 1 ELSE 0 END AS rowversion_index_key_vorhanden
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.columns c ON c.object_id=t.object_id
WHERE c.system_type_id=189 AND t.is_ms_shipped=0
AND (
 (s.name=N'dbo' AND t.name IN (
   N'tArtikel',N'tArtikelShop',N'tArtikelHistory',
   N'tlagerbestandProLagerLagerartikel',N'tWarenLagerPlatzArtikel'))
 OR (s.name=N'SCX' AND t.name IN (N'tOffer',N'tOfferPrice'))
)
ORDER BY s.name,t.name,c.name;

-- Query 5: identify candidate lookup tables for status/tax/unit/account
-- Only table names and schema. Review list before expanding them.
SELECT TOP (150) s.name AS schema_name, t.name AS table_name
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
WHERE t.is_ms_shipped=0
AND (
 t.name LIKE N'%Steuer%' OR t.name LIKE N'%Lieferstatus%'
 OR t.name LIKE N'%Einheit%' OR t.name LIKE N'%Preis%'
 OR t.name LIKE N'%Plattform%' OR t.name LIKE N'%Account%'
 OR t.name LIKE N'%Verkaufskanal%' OR t.name LIKE N'%Shop%'
)
ORDER BY s.name,t.name;
