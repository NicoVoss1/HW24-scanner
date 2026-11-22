-- HW24 JTL Phase 3: PK/Index/Rowversion/Relations preflight, only SQL Server system catalogs.
-- Only run against confirmed database eazybusiness. SQL Server 2017 (compatibility 140).
-- No rows from orders, customers, offers or articles are read; no DDL, DML, EXEC or changes.
-- Run each SELECT separately in HeidiSQL; keep outputs private if they contain environment details.

-- 0) Confirm database. If value is not eazybusiness: STOP.
SELECT DB_NAME() AS datenbankname;

-- 1) PRIMARY KEY AND UNIQUE INDEX KEY COLUMNS for priority sources.
-- Multiple rows per index represent the ordered composite key.
SELECT
    s.name AS schema_name, t.name AS table_name,
    i.name AS index_name, i.is_primary_key, i.is_unique,
    ic.key_ordinal, c.name AS key_column,
    i.is_disabled
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.indexes AS i ON i.object_id=t.object_id
JOIN sys.index_columns AS ic ON ic.object_id=i.object_id AND ic.index_id=i.index_id
JOIN sys.columns AS c ON c.object_id=ic.object_id AND c.column_id=ic.column_id
WHERE t.is_ms_shipped=0 AND i.is_unique=1
AND ic.key_ordinal>0
AND (
  (s.name=N'dbo' AND t.name IN (
    N'tArtikel',N'tArtikelHistory',N'tArtikelShop',
    N'tlagerbestandProLagerLagerartikel',N'tWarenLagerPlatzArtikel',
    N'pf_amazon_angebot',N'pf_amazon_angebot_ext'
  ))
  OR (s.name=N'SCX' AND t.name IN (N'tOffer',N'tOfferPrice',N'tVerkaufskanalArtikelData'))
  OR (s.name=N'Verkauf' AND t.name IN (N'tAuftragPosition',N'tAuftragPositionEckdaten'))
)
ORDER BY s.name,t.name,i.is_primary_key DESC,i.name,ic.key_ordinal;

-- 2) Foreign-key relations touching selected tables (metadata only).
SELECT
    ps.name AS parent_schema, pt.name AS parent_table, pc.name AS parent_column,
    rs.name AS referenced_schema, rt.name AS referenced_table, rc.name AS referenced_column,
    fk.name AS fk_name, fkc.constraint_column_id AS fk_column_order,
    fk.is_disabled
FROM sys.foreign_keys AS fk
JOIN sys.foreign_key_columns AS fkc ON fkc.constraint_object_id=fk.object_id
JOIN sys.tables AS pt ON pt.object_id=fkc.parent_object_id
JOIN sys.schemas AS ps ON ps.schema_id=pt.schema_id
JOIN sys.columns AS pc ON pc.object_id=pt.object_id AND pc.column_id=fkc.parent_column_id
JOIN sys.tables AS rt ON rt.object_id=fkc.referenced_object_id
JOIN sys.schemas AS rs ON rs.schema_id=rt.schema_id
JOIN sys.columns AS rc ON rc.object_id=rt.object_id AND rc.column_id=fkc.referenced_column_id
WHERE
    (ps.name=N'dbo' AND pt.name IN (N'tArtikel',N'tWarenLagerPlatzArtikel',N'tlagerbestandProLagerLagerartikel',N'pf_amazon_angebot'))
 OR (rs.name=N'dbo' AND rt.name IN (N'tArtikel',N'tWarenLagerPlatzArtikel',N'tlagerbestandProLagerLagerartikel',N'pf_amazon_angebot'))
 OR (ps.name=N'SCX' AND pt.name IN (N'tOffer',N'tOfferPrice'))
 OR (rs.name=N'SCX' AND rt.name IN (N'tOffer',N'tOfferPrice'))
ORDER BY ps.name,pt.name,fk.name,fkc.constraint_column_id;

-- 3) Confirm all rowversion columns and candidate change-timestamp fields on sources.
-- 'timestamp' in SQL Server is a rowversion binary type, not a date/time.
SELECT
    s.name AS schema_name, t.name AS table_name,
    c.name AS column_name, ty.name AS sql_type,
    c.is_nullable, c.is_computed
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE t.is_ms_shipped=0
AND (
  (s.name=N'dbo' AND t.name IN (
    N'tArtikel',N'tArtikelHistory',N'tArtikelShop',
    N'tlagerbestandProLagerLagerartikel',N'tWarenLagerPlatzArtikel',
    N'pf_amazon_angebot',N'pf_amazon_angebot_ext'))
  OR (s.name=N'SCX' AND t.name IN (N'tOffer',N'tOfferPrice',N'tVerkaufskanalArtikelData'))
)
AND (
   c.system_type_id=189 -- rowversion / timestamp
   OR ty.name IN (N'date',N'datetime',N'datetime2',N'datetimeoffset',N'smalldatetime')
)
ORDER BY s.name,t.name,c.column_id;

-- 4) Indexes that may speed up read-only delta queries, no performance assumptions.
SELECT
    s.name AS schema_name, t.name AS table_name,
    i.name AS index_name, i.is_primary_key, i.is_unique,
    i.is_disabled, ic.key_ordinal, ic.is_included_column,
    c.name AS index_column
FROM sys.tables AS t
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.indexes AS i ON i.object_id=t.object_id
JOIN sys.index_columns AS ic ON ic.object_id=i.object_id AND ic.index_id=i.index_id
JOIN sys.columns AS c ON c.object_id=ic.object_id AND c.column_id=ic.column_id
WHERE t.is_ms_shipped=0 AND i.index_id>0
AND (
    (s.name=N'dbo' AND t.name IN (N'tArtikel',N'tlagerbestandProLagerLagerartikel',N'tWarenLagerPlatzArtikel'))
    OR (s.name=N'SCX' AND t.name IN (N'tOffer',N'tOfferPrice'))
)
ORDER BY s.name,t.name,i.name,ic.key_ordinal,ic.index_column_id;
