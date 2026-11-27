-- HW24 eazybusiness / SQL Server 2017 Express / JTL-Wawi 2.2.2.0
-- PHASE 16: identify ACTUAL supplier/representative/contact structure
-- so Einkauf-ID prefixes and names can later be selected from JTL,
-- rather than inventing supplier/contact IDs or duplicating person records.
--
-- ALL QUERIES ARE SELECT ONLY. sys.* METADATA ONLY. No customer/contact
-- name, address, email, mobile phone or payment-data rows are returned.
-- Execute individual statements in HeidiSQL after verifying database.

-- 0. Confirm correct database.
SELECT DB_NAME() AS datenbankname;

-- 1. Candidate tables (may use different schema/name across JTL versions).
SELECT s.name AS schema_name,t.name AS table_name,t.object_id,
       t.create_date AS created_at,t.modify_date AS modified_at
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
WHERE LOWER(t.name) LIKE N'%lieferant%'
   OR LOWER(t.name) LIKE N'%ansprechpartner%'
   OR LOWER(t.name) LIKE N'%kontakt%'
   OR LOWER(t.name) LIKE N'%vertreter%'
ORDER BY s.name,t.name;

-- 2. Raw SQL column schemas/types without returning actual personal rows.
SELECT s.name AS schema_name,t.name AS table_name,
       c.column_id AS position,c.name AS column_name,
       ty.name AS sql_type,c.max_length,c.is_nullable
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.columns c ON c.object_id=t.object_id
JOIN sys.types ty ON ty.user_type_id=c.user_type_id
WHERE LOWER(t.name) LIKE N'%lieferant%'
   OR LOWER(t.name) LIKE N'%ansprechpartner%'
   OR LOWER(t.name) LIKE N'%kontakt%'
   OR LOWER(t.name) LIKE N'%vertreter%'
ORDER BY s.name,t.name,c.column_id;

-- 3. Real FKs between supplier/representative candidate tables.
SELECT f.name AS fk_name,sp.name AS from_schema,tp.name AS from_table,
       cp.name AS from_column,sr.name AS to_schema,tr.name AS to_table,
       cr.name AS to_column,fc.constraint_column_id AS column_order
FROM sys.foreign_keys f
JOIN sys.foreign_key_columns fc ON fc.constraint_object_id=f.object_id
JOIN sys.tables tp ON tp.object_id=f.parent_object_id
JOIN sys.schemas sp ON sp.schema_id=tp.schema_id
JOIN sys.columns cp ON cp.object_id=fc.parent_object_id AND cp.column_id=fc.parent_column_id
JOIN sys.tables tr ON tr.object_id=f.referenced_object_id
JOIN sys.schemas sr ON sr.schema_id=tr.schema_id
JOIN sys.columns cr ON cr.object_id=fc.referenced_object_id AND cr.column_id=fc.referenced_column_id
WHERE LOWER(tp.name) LIKE N'%lieferant%'
   OR LOWER(tp.name) LIKE N'%ansprechpartner%'
   OR LOWER(tp.name) LIKE N'%kontakt%'
   OR LOWER(tp.name) LIKE N'%vertreter%'
   OR LOWER(tr.name) LIKE N'%lieferant%'
   OR LOWER(tr.name) LIKE N'%ansprechpartner%'
   OR LOWER(tr.name) LIKE N'%kontakt%'
   OR LOWER(tr.name) LIKE N'%vertreter%'
ORDER BY sp.name,tp.name,f.name,fc.constraint_column_id;

-- 4. PK/unique index key metadata, no personal values.
SELECT s.name AS schema_name,t.name AS table_name,i.name AS index_name,
       i.is_primary_key,i.is_unique,ic.key_ordinal,c.name AS key_column
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.indexes i ON i.object_id=t.object_id AND i.is_unique=1
JOIN sys.index_columns ic ON ic.object_id=i.object_id AND ic.index_id=i.index_id
JOIN sys.columns c ON c.object_id=ic.object_id AND c.column_id=ic.column_id
WHERE ic.key_ordinal>0 AND
 (LOWER(t.name) LIKE N'%lieferant%'
   OR LOWER(t.name) LIKE N'%ansprechpartner%'
   OR LOWER(t.name) LIKE N'%kontakt%'
   OR LOWER(t.name) LIKE N'%vertreter%')
ORDER BY s.name,t.name,i.name,ic.key_ordinal;

-- No supplier/contact row export at this stage, no JTL permission change.
