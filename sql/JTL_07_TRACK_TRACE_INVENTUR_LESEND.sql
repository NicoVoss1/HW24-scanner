/*
 HW24 KI-Kundenservice: JTL Track-&-Trace Metadaten-Inventur
 Nur LESEND aus der JTL SQL-Server Datenbank (z. B. eazybusiness).
 Keine Kundendaten, keine Trackings, kein Export von Inhalten, kein UPDATE.
 Im SSMS unter dem vorhandenen JTL-Read-only-Konto ausführen.
 Diese Datei beweist NICHT, dass die Daten im HW24-MariaDB-Spiegel vorhanden sind.
*/
SET NOCOUNT ON;

-- A. Kandidaten-Tabellen nach Namen oder einschlägigen Spalten.
;WITH kandidaten AS (
  SELECT DISTINCT s.name AS schema_name,t.name AS table_name,t.object_id
  FROM sys.tables AS t
  JOIN sys.schemas AS s ON s.schema_id=t.schema_id
  LEFT JOIN sys.columns AS c ON c.object_id=t.object_id
  WHERE LOWER(t.name) LIKE '%track%'
     OR LOWER(t.name) LIKE '%trace%'
     OR LOWER(t.name) LIKE '%versand%'
     OR LOWER(t.name) LIKE '%sendung%'
     OR LOWER(t.name) LIKE '%paket%'
     OR LOWER(t.name) LIKE '%shipment%'
     OR LOWER(t.name) LIKE '%carrier%'
     OR LOWER(t.name) LIKE '%dhl%'
     OR LOWER(c.name) LIKE '%tracking%'
     OR LOWER(c.name) LIKE '%sendungsnummer%'
     OR LOWER(c.name) LIKE '%packstation%'
)
SELECT TOP (250) schema_name,table_name
FROM kandidaten
ORDER BY schema_name,table_name;

-- B. Spalten- und Datentypen für Kandidaten. Nur SCHEMA, keine Inhalte.
;WITH kandidaten AS (
 SELECT DISTINCT t.object_id
 FROM sys.tables AS t
 JOIN sys.columns AS c ON c.object_id=t.object_id
 WHERE LOWER(t.name) LIKE '%track%'
    OR LOWER(t.name) LIKE '%trace%'
    OR LOWER(t.name) LIKE '%versand%'
    OR LOWER(t.name) LIKE '%sendung%'
    OR LOWER(t.name) LIKE '%paket%'
    OR LOWER(t.name) LIKE '%shipment%'
    OR LOWER(t.name) LIKE '%carrier%'
    OR LOWER(t.name) LIKE '%dhl%'
    OR LOWER(c.name) LIKE '%tracking%'
    OR LOWER(c.name) LIKE '%sendungsnummer%'
    OR LOWER(c.name) LIKE '%packstation%'
)
SELECT TOP (1500)
 s.name AS schema_name,t.name AS table_name,c.column_id,c.name AS column_name,
 ty.name AS sql_type,c.max_length,c.is_nullable,c.is_identity
FROM kandidaten AS k
JOIN sys.tables AS t ON t.object_id=k.object_id
JOIN sys.schemas AS s ON s.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
ORDER BY s.name,t.name,c.column_id;

-- C. Bekannte FK-Beziehungen zu/von Kandidaten (für Auftrags-/Paket-Mapping).
;WITH kandidaten AS (
 SELECT DISTINCT t.object_id
 FROM sys.tables AS t
 JOIN sys.columns AS c ON c.object_id=t.object_id
 WHERE LOWER(t.name) LIKE '%track%'
    OR LOWER(t.name) LIKE '%trace%'
    OR LOWER(t.name) LIKE '%versand%'
    OR LOWER(t.name) LIKE '%sendung%'
    OR LOWER(t.name) LIKE '%paket%'
    OR LOWER(t.name) LIKE '%shipment%'
    OR LOWER(t.name) LIKE '%carrier%'
    OR LOWER(t.name) LIKE '%dhl%'
    OR LOWER(c.name) LIKE '%tracking%'
    OR LOWER(c.name) LIKE '%sendungsnummer%'
    OR LOWER(c.name) LIKE '%packstation%'
)
SELECT TOP (300)
 fk.name AS foreign_key,
 OBJECT_SCHEMA_NAME(fk.parent_object_id) AS from_schema,
 OBJECT_NAME(fk.parent_object_id) AS from_table,
 pc.name AS from_column,
 OBJECT_SCHEMA_NAME(fk.referenced_object_id) AS to_schema,
 OBJECT_NAME(fk.referenced_object_id) AS to_table,
 rc.name AS to_column
FROM sys.foreign_keys AS fk
JOIN sys.foreign_key_columns AS fkc ON fkc.constraint_object_id=fk.object_id
JOIN sys.columns AS pc ON pc.object_id=fkc.parent_object_id AND pc.column_id=fkc.parent_column_id
JOIN sys.columns AS rc ON rc.object_id=fkc.referenced_object_id AND rc.column_id=fkc.referenced_column_id
WHERE fk.parent_object_id IN (SELECT object_id FROM kandidaten)
   OR fk.referenced_object_id IN (SELECT object_id FROM kandidaten)
ORDER BY from_schema,from_table,foreign_key,from_column;

-- Nach Prüfung der echten JTL-Version die relevanten JOINs separat festlegen.
-- Keine automatischen Volltabellen-Scans, keine unbestätigten Tabellenannahmen.
