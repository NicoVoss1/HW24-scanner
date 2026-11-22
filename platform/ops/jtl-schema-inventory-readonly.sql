-- HW24 JTL eazybusiness: read-only schema inventory (NO data rows exported).
-- Run manually in SSMS against the intended database under a read-only login.
-- The script performs NO CREATE / ALTER / UPDATE / DELETE / EXEC.
SET NOCOUNT ON;
IF DB_NAME() <> N'eazybusiness'
    THROW 51000, 'HW24 preflight: expected eazybusiness database. Select the correct database.', 1;

SELECT
    DB_NAME() AS database_name,
    @@SERVERNAME AS sql_server_name,
    SERVERPROPERTY('Edition') AS sql_edition,
    SERVERPROPERTY('ProductVersion') AS sql_version,
    SYSDATETIMEOFFSET() AS inspected_at;

SELECT
    sch.name AS schema_name,
    tbl.name AS table_name,
    col.column_id,
    col.name AS column_name,
    typ.name AS sql_type,
    col.max_length,
    col.precision,
    col.scale,
    col.is_nullable,
    col.is_identity,
    col.is_computed,
    CASE WHEN pkc.column_id IS NOT NULL THEN CAST(1 AS bit) ELSE CAST(0 AS bit) END AS is_primary_key_column,
    CAST(1 AS bit) AS classification_required_before_data_mirror
FROM sys.tables AS tbl
JOIN sys.schemas AS sch ON sch.schema_id = tbl.schema_id
JOIN sys.columns AS col ON col.object_id = tbl.object_id
JOIN sys.types AS typ ON typ.user_type_id = col.user_type_id
LEFT JOIN (
    SELECT ic.object_id, ic.column_id
    FROM sys.indexes AS idx
    JOIN sys.index_columns AS ic ON ic.object_id = idx.object_id AND ic.index_id = idx.index_id
    WHERE idx.is_primary_key = 1
) AS pkc ON pkc.object_id = col.object_id AND pkc.column_id = col.column_id
WHERE tbl.is_ms_shipped = 0
ORDER BY sch.name, tbl.name, col.column_id;

SELECT
    SCHEMA_NAME(parent_tbl.schema_id) AS source_schema,
    parent_tbl.name AS source_table,
    parent_col.name AS source_column,
    SCHEMA_NAME(ref_tbl.schema_id) AS target_schema,
    ref_tbl.name AS target_table,
    ref_col.name AS target_column,
    fk.name AS foreign_key,
    fk.is_disabled
FROM sys.foreign_key_columns AS fkc
JOIN sys.foreign_keys AS fk ON fk.object_id = fkc.constraint_object_id
JOIN sys.tables AS parent_tbl ON parent_tbl.object_id = fkc.parent_object_id
JOIN sys.columns AS parent_col ON parent_col.object_id = fkc.parent_object_id AND parent_col.column_id = fkc.parent_column_id
JOIN sys.tables AS ref_tbl ON ref_tbl.object_id = fkc.referenced_object_id
JOIN sys.columns AS ref_col ON ref_col.object_id = fkc.referenced_object_id AND ref_col.column_id = fkc.referenced_column_id
ORDER BY source_schema, source_table, fk.name, fkc.constraint_column_id;
