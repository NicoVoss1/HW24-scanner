-- Windows / SQL Server eazybusiness, read-only metadata audit for complete article mapping.
IF DB_NAME() <> N'eazybusiness' THROW 50001, 'Expected eazybusiness', 1;

-- Exact physical source of weights, origin, labels, gross/net, currency and SEO.
SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME IN (N'tArtikel',N'vArtikelliste',N'tArtikelBeschreibung',N'tWaehrung',
 N'tSteuersatz',N'tSteuerzone',N'tSteuerzoneLand',N'tkategorieartikel',N'tKategorie',
 N'tKategorieSprache',N'tArtikelAttribut',N'tAttribut',N'tAttributSprache',
 N'tArtikelMerkmal',N'tMerkmal',N'tMerkmalWert',N'tliefartikel',N'tlieferant',N'tArtikelType')
AND (COLUMN_NAME LIKE N'%Gewicht%' OR COLUMN_NAME LIKE N'%Herkunft%'
 OR COLUMN_NAME LIKE N'%Label%' OR COLUMN_NAME LIKE N'%Etikett%'
 OR COLUMN_NAME LIKE N'%Brutto%' OR COLUMN_NAME LIKE N'%Netto%'
 OR COLUMN_NAME LIKE N'%Waehr%' OR COLUMN_NAME LIKE N'%Steuer%'
 OR COLUMN_NAME LIKE N'%SEO%' OR COLUMN_NAME LIKE N'%Meta%'
 OR COLUMN_NAME LIKE N'%URL%' OR COLUMN_NAME LIKE N'%Kategorie%'
 OR COLUMN_NAME LIKE N'%Attribut%' OR COLUMN_NAME LIKE N'%Merkmal%'
 OR COLUMN_NAME LIKE N'%Lief%' OR COLUMN_NAME LIKE N'%ArtNr%'
 OR COLUMN_NAME LIKE N'%Mass%' OR COLUMN_NAME LIKE N'%Beschreib%'
 OR COLUMN_NAME LIKE N'%Artikel%' OR COLUMN_NAME=N'cName'
 OR COLUMN_NAME=N'cWert' OR COLUMN_NAME=N'kSprache' OR COLUMN_NAME=N'kShop')
ORDER BY TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME;

-- Label/attribute/category table names also cover version-specific layouts.
SELECT s.name AS schema_name,t.name AS table_name,c.name AS column_name,ty.name AS sql_type
FROM sys.tables t JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.columns c ON c.object_id=t.object_id
JOIN sys.types ty ON ty.user_type_id=c.user_type_id
WHERE t.is_ms_shipped=0 AND
(t.name LIKE N'%Label%' OR t.name LIKE N'%Etikett%' OR t.name LIKE N'%KategorieArtikel%'
 OR t.name LIKE N'%ArtikelAttribut%' OR t.name LIKE N'%ArtikelMerkmal%')
AND c.name NOT LIKE N'%Passwort%' AND c.name NOT LIKE N'%Token%' AND c.name NOT LIKE N'%Secret%'
ORDER BY s.name,t.name,c.column_id;
