-- HW24 eazybusiness / HeidiSQL / SQL Server 2017 Express
-- Phase 2: NUR LESEN / keine Tabellen-Daten exportieren / einzelne SELECTs.
-- Diese Abfragen nur in der JTL-Verbindung ausfuehren, vorab DB-Name pruefen.
-- WICHTIG: Insbesondere SQL-Express-Datendateien sind max. 10 GB pro DB.
-- Query 1: Datenbankzuordnung und Speicherbelegung in MB, ohne Dateipfade.
SELECT DB_NAME() AS datenbankname;
SELECT
    type_desc AS dateityp,
    COUNT(*) AS dateien,
    CAST(SUM(CONVERT(bigint, size)) * 8.0 / 1024 AS decimal(18,1)) AS zugewiesen_mb,
    CAST(SUM(CONVERT(bigint, COALESCE(FILEPROPERTY(name, 'SpaceUsed'), 0)))
         * 8.0 / 1024 AS decimal(18,1)) AS belegte_seiten_mb
FROM sys.database_files
GROUP BY type_desc
ORDER BY type_desc;

-- Query 2: Change Tracking auf DB-Ebene und Anzahl erfasster Tabellen.
-- KEINE Aktivierung und keine DDL!
SELECT
    CASE WHEN EXISTS (
      SELECT 1 FROM sys.change_tracking_databases WHERE database_id = DB_ID()
    ) THEN 1 ELSE 0 END AS change_tracking_aktiv,
    (SELECT COUNT(*) FROM sys.change_tracking_tables) AS anzahl_ct_tabellen;

-- Query 3: relevante JTL-Datenbereiche und technische Strukturen
-- Jede Zeile ist METADATEN (Schema, Tabelle, Spaltenzahl, Primary Key).
SELECT
    s.name AS schema_name,
    t.name AS tabellenname,
    (SELECT COUNT(*) FROM sys.columns c WHERE c.object_id = t.object_id) AS anzahl_spalten,
    CASE WHEN EXISTS (
       SELECT 1 FROM sys.indexes i
       WHERE i.object_id = t.object_id AND i.is_primary_key = 1
    ) THEN 1 ELSE 0 END AS primary_key_vorhanden
FROM sys.tables t
INNER JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE t.is_ms_shipped = 0 AND (
   (s.name = N'dbo' AND t.name IN (
      N'tArtikel', N'tArtikelShop', N'tArtikelHistory',
      N'tWarenLagerPlatzArtikel', N'tlagerbestandProLagerLagerartikel',
      N'pf_amazon_angebot', N'pf_amazon_angebot_ext'
   ))
   OR (s.name = N'SCX' AND t.name IN (
      N'tOffer', N'tOfferPrice', N'tVerkaufskanalArtikelData'
   ))
   OR (s.name = N'Verkauf' AND t.name IN (
      N'tAuftragPosition', N'tAuftragPositionEckdaten'
   ))
   OR (s.name = N'WMS' AND (
      t.name LIKE N'%Lager%' OR t.name LIKE N'%Beweg%' OR t.name LIKE N'%Buch%'
   ))
)
ORDER BY s.name, t.name;

-- Query 4: Spalten-Metadaten fuer Artikel, Bestand und Angebot.
-- Keine Artikel-/Kundenwerte, nur Schema. Optional etwas laengere Ausgabe.
SELECT
    s.name AS schema_name,
    t.name AS tabellenname,
    c.column_id AS spaltenposition,
    c.name AS spaltenname,
    ty.name AS sql_datentyp,
    c.is_nullable,
    c.is_identity
FROM sys.tables t
INNER JOIN sys.schemas s ON s.schema_id = t.schema_id
INNER JOIN sys.columns c ON c.object_id = t.object_id
INNER JOIN sys.types ty ON ty.user_type_id = c.user_type_id
WHERE t.is_ms_shipped = 0
AND (
    (s.name = N'dbo' AND t.name IN (
      N'tArtikel', N'tWarenLagerPlatzArtikel',
      N'tlagerbestandProLagerLagerartikel', N'pf_amazon_angebot'
    ))
    OR (s.name = N'SCX' AND t.name IN (N'tOfferPrice', N'tOffer'))
)
ORDER BY s.name, t.name, c.column_id;
