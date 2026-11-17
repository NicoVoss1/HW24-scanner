/* HW24 v4.9 · Steuer- und WMS-Lagerplatzquellen
   Nur lesende SQL Server Metadaten aus eazybusiness.
   Keine Bestaende anpassen, keine Lagerplaetze buchen, keine Steuersaetze setzen.
   Ergebnis je Abschnitt als CSV (mit Kopfzeile) speichern.
*/
USE [eazybusiness];
SET NOCOUNT ON;

-- 1. Gesicherte Steuerklassen fuer den Testartikel:
SELECT TOP (10)
 a.kArtikel,a.cArtNr,a.cBarcode,a.kSteuerklasse,
 sk.cName AS steuerklasse_name
FROM dbo.tArtikel AS a
LEFT JOIN dbo.tSteuerklasse AS sk ON sk.kSteuerklasse=a.kSteuerklasse
WHERE a.cArtNr=N'4015600040482'
   OR a.cBarcode=N'4015600040482'
   OR a.cHAN=N'4015600040482';

-- 2. Alle Tabellen und Views fuer Steuerland, -zone und Steuersatz.
SELECT s.name AS SQL_SCHEMA,o.name AS OBJEKT,o.type_desc AS OBJEKTTYP
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
WHERE (o.type='U' OR o.type='V')
  AND (o.name LIKE '%Steuer%' OR o.name LIKE '%Tax%' OR o.name LIKE '%MwSt%'
       OR o.name LIKE '%Umsatz%')
ORDER BY o.type_desc,s.name,o.name;

-- 3. Die dazugehoerigen Spalten; 19 Prozent nicht aus Namen geraten.
SELECT s.name AS SQL_SCHEMA,o.name AS OBJEKT,c.name AS SPALTE,
 t.name AS DATENTYP,c.is_nullable AS NULL_ZULAESSIG
FROM sys.objects o
JOIN sys.schemas s ON s.schema_id=o.schema_id
JOIN sys.columns c ON c.object_id=o.object_id
JOIN sys.types t ON t.user_type_id=c.user_type_id
WHERE (o.type='U' OR o.type='V')
  AND (o.name LIKE '%Steuer%' OR o.name LIKE '%Tax%' OR o.name LIKE '%MwSt%'
       OR o.name LIKE '%Umsatz%')
ORDER BY s.name,o.name,c.column_id;

-- 4. WMS-Tabellen und Views, insbesondere die fachlichen Bestands-VIEWS,
--    deren Werte ggfs. je Lagerplatz und Artikel bereits zusammengefuehrt sind.
SELECT s.name AS SQL_SCHEMA,o.name AS OBJEKT,o.type_desc AS OBJEKTTYP
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
WHERE (o.type='U' OR o.type='V')
  AND (o.name LIKE '%WarenLagerPlatz%' OR o.name LIKE '%LagerPlatz%'
       OR o.name LIKE '%Lagerbestand%' OR o.name LIKE '%WMS%'
       OR o.name LIKE '%WarenlagerEingang%')
ORDER BY o.type_desc,s.name,o.name;

-- 5. Spalten der WMS-Lagerobjekte: Artikelbeziehung, Fachkennung,
--    Menge je Fach und ggf. MHD/Charge ermitteln.
SELECT s.name AS SQL_SCHEMA,o.name AS OBJEKT,c.name AS SPALTE,
 t.name AS DATENTYP,c.is_nullable AS NULL_ZULAESSIG
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
JOIN sys.columns AS c ON c.object_id=o.object_id
JOIN sys.types AS t ON t.user_type_id=c.user_type_id
WHERE (o.type='U' OR o.type='V')
 AND (o.name LIKE '%WarenLagerPlatz%' OR o.name LIKE '%LagerPlatz%'
      OR o.name LIKE '%Lagerbestand%' OR o.name LIKE '%WMS%')
ORDER BY s.name,o.name,c.column_id;
