/*
 HW24 Quellenanalyse: Eigene Amazon-Angebote aus Arbytrage.io -> JTL,
 eBay-Angebotsvorlagen, WMS-Lagerplätze, Lieferanten.
 100% SELECT/Metadaten, NUR auf SQL Server eazybusiness ausführen,
 NICHT auf Plesk-MariaDB. Keine Rechteänderung, kein Datenimport.
 Ergebnis vor JOIN-Implementierung auf echte SQL-Tabellen und Schlüssel prüfen.
*/
USE [eazybusiness];

-- 1. Existierende Tabellen/Views; liefert nur Namen
SELECT TOP (300) s.name AS [Schema],o.name AS [Quellname],
 CASE WHEN o.type='V' THEN 'VIEW' ELSE 'TABLE' END AS [Art]
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
WHERE o.type IN ('U','V')
 AND (
  o.name LIKE '%amazon%angebot%'
  OR o.name LIKE '%ebay%'
  OR o.name LIKE '%Auktion%'
  OR o.name LIKE '%Angebot%'
  OR o.name LIKE '%Vorlage%'
  OR o.name LIKE '%Warenlager%'
  OR o.name LIKE '%Lagerplatz%'
  OR o.name LIKE '%WMS%'
  OR o.name LIKE '%Lieferant%'
  OR o.name LIKE '%Preis%'
 )
ORDER BY [Art],[Schema],[Quellname];

-- 2. Spalten/SQL-Typen der relevanten, wirklich vorhandenen Objekte
-- Darunter ggf. dbo.pf_amazon_angebot, pf_amazon_angebot_ext,
-- dbo.vWarenlagerPlatzArtikel, dbo.tWarenLagerPlatz;
-- deren Existenz/Feldnamen sind VOR einem JOIN zu bestaetigen.
SELECT TOP (1000)
 s.name AS [Schema],o.name AS [Quellname],c.column_id AS [Position],
 c.name AS [Spalte],ty.name AS [SQLTyp]
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
JOIN sys.columns AS c ON c.object_id=o.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE o.type IN ('U','V')
 AND (
  o.name LIKE '%amazon%angebot%'
  OR o.name LIKE '%ebay%angebot%'
  OR o.name LIKE '%ebay%vorlag%'
  OR o.name LIKE '%Warenlager%Platz%'
  OR o.name LIKE '%Lagerplatz%'
  OR o.name LIKE '%Lieferant%'
 )
ORDER BY [Schema],[Quellname],[Position];

-- 3. Auch bei anders benannten Tabellen finden wir die Fachspalten.
-- Nur Metadaten, keine Preise/Bestell-/Kundendaten.
SELECT TOP (400)
 s.name AS [Schema],o.name AS [Quellname],c.name AS [Spalte],
 ty.name AS [SQLTyp]
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
JOIN sys.columns AS c ON c.object_id=o.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE o.type IN ('U','V')
 AND (
  c.name LIKE '%SKU%'
  OR c.name LIKE '%Preis%'
  OR c.name LIKE '%Buybox%'
  OR c.name LIKE '%MinPreis%'
  OR c.name LIKE '%Lagerplatz%'
 )
 AND (
  o.name LIKE '%Amazon%'
  OR o.name LIKE '%eBay%'
  OR o.name LIKE '%Angebot%'
  OR o.name LIKE '%Artikel%'
  OR o.name LIKE '%Warenlager%'
 )
ORDER BY [Schema],[Quellname],[Spalte];

-- 4. Nur Metadaten für einen späteren belastbaren JOIN:
-- wichtige Schluesselbezeichnungen der bekannten Artikel-Grundtabelle.
SELECT c.name AS [Artikel-Spalte],ty.name AS [SQLTyp]
FROM sys.columns AS c
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE c.object_id=OBJECT_ID(N'dbo.tArtikel')
 AND (c.name LIKE 'k%' OR c.name LIKE 'cASIN%'
      OR c.name LIKE 'cArtNr%' OR c.name LIKE 'fAmazon%'
      OR c.name LIKE 'fEbay%')
ORDER BY c.column_id;
