-- HW24 / JTL eazybusiness - HEIDISQL START-INVENTUR, SQL SERVER T-SQL
-- Stand: 2026-11-23. NUR LESEN. Jede Abfrage einzeln ausfuehren.
-- Nur Metadaten/ungefaehre Zeilenanzahlen; keine Kunden-/Bestellinhalte.
-- SQL A: DB/SQL-Server Version und Umfang
SELECT
  DB_NAME() AS datenbankname,
  CONVERT(nvarchar(120), SERVERPROPERTY('ProductVersion')) AS sql_server_version,
  CONVERT(nvarchar(120), SERVERPROPERTY('Edition')) AS sql_server_edition,
  (SELECT COUNT(*) FROM sys.tables WHERE is_ms_shipped=0) AS anzahl_tabellen,
  (SELECT COUNT(*) FROM sys.views WHERE is_ms_shipped=0) AS anzahl_views,
  (SELECT COUNT(*) FROM sys.columns c
   INNER JOIN sys.tables t ON t.object_id=c.object_id
   WHERE t.is_ms_shipped=0) AS anzahl_spalten;

-- SQL B: Zugeordnete Datenbankdateien in MB; keine physischen Pfade
SELECT type_desc AS dateityp,
       COUNT(*) AS anzahl_dateien,
       CAST(SUM(CONVERT(bigint,size))*8.0/1024 AS decimal(18,1)) AS zugewiesen_mb
FROM sys.database_files
GROUP BY type_desc
ORDER BY type_desc;

-- SQL C: Groesste Tabellen, nur UNGEFAEHRE Zeilenanzahlen
SELECT TOP (40)
       s.name AS schema_name,
       t.name AS tabellenname,
       SUM(CONVERT(bigint,p.rows)) AS ungefaehre_zeilen
FROM sys.tables t
INNER JOIN sys.schemas s ON s.schema_id=t.schema_id
INNER JOIN sys.partitions p ON p.object_id=t.object_id AND p.index_id IN (0,1)
WHERE t.is_ms_shipped=0
GROUP BY s.name,t.name
ORDER BY ungefaehre_zeilen DESC, s.name, t.name;

-- SQL D: Snapshot/CDC und Change-Tracking Status (NUR LESEN)
SELECT name AS datenbank,
       compatibility_level,
       recovery_model_desc,
       is_cdc_enabled,
       is_read_committed_snapshot_on,
       snapshot_isolation_state_desc
FROM sys.databases
WHERE database_id=DB_ID();

SELECT CASE WHEN EXISTS (
    SELECT 1 FROM sys.change_tracking_databases WHERE database_id=DB_ID()
) THEN 1 ELSE 0 END AS change_tracking_aktiv;

-- SQL E: Anzahl von Tabellen je Schema
SELECT s.name AS schema_name, COUNT(*) AS anzahl_tabellen
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
WHERE t.is_ms_shipped=0
GROUP BY s.name
ORDER BY anzahl_tabellen DESC, s.name;
