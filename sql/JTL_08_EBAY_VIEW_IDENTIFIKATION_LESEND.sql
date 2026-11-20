/*
 HW24 · eBay View-Identifikation (20.11.2026).
 NUR METADATEN: sys.objects, sys.columns, sys.schemas.
 In SSMS auf dem Windows-JTL-SQL-Server gegen eazybusiness ausfuehren.
 Kein Zugriff auf Angebotszeilen, kein Schreibzugriff, keine Speicherung.
 Wir kennen den Namen der eBay-View noch NICHT, nur die 91 Spalten
 eines exportierten Teilauszugs. NIEMALS aufgrund der CSV-Zeilen
 cType- oder nStatus-Codes als definitiven Live-Status deklarieren.
*/
USE [eazybusiness];
SET NOCOUNT ON;

-- 1. SQL-Views zuerst; alle genannten Spalten muessen vorhanden sein.
;WITH Kandidaten AS (
 SELECT
   s.name AS SchemaName,
   o.name AS Objektname,
   CASE o.type WHEN 'V' THEN 'VIEW' WHEN 'U' THEN 'TABELLE' END AS Objekttyp,
   SUM(CASE WHEN c.name IN (
     'cType', 'nStatus', 'cItemId', 'cTemplateName', 'cTitle',
     'cArtikelnummer', 'fStartPrice', 'fArtikelEbayPreis',
     'cEbayUserName', 'cPlattform', 'cWaehrungIso',
     'dStarttime', 'dEndtime', 'cHAN'
   ) THEN 1 ELSE 0 END) AS TreffendeSpalten,
   MAX(CASE WHEN c.name = 'cType' THEN 1 ELSE 0 END) AS HatTyp,
   MAX(CASE WHEN c.name = 'nStatus' THEN 1 ELSE 0 END) AS HatStatus,
   MAX(CASE WHEN c.name = 'cItemId' THEN 1 ELSE 0 END) AS HatEbayAngebotsnummer,
   MAX(CASE WHEN c.name = 'fStartPrice' THEN 1 ELSE 0 END) AS HatAngebotspreis,
   MAX(CASE WHEN c.name = 'cEbayUserName' THEN 1 ELSE 0 END) AS HatKontoklarname,
   MAX(CASE WHEN c.name = 'cArtikelnummer' THEN 1 ELSE 0 END) AS HatArtikelnummer
 FROM sys.objects AS o
 JOIN sys.schemas AS s ON s.schema_id=o.schema_id
 JOIN sys.columns AS c ON c.object_id=o.object_id
 WHERE o.type IN ('V','U')
 GROUP BY s.name,o.name,o.type
)
SELECT TOP (30)
 SchemaName, Objektname, Objekttyp, TreffendeSpalten,
 HatTyp,HatStatus,HatEbayAngebotsnummer,HatAngebotspreis,
 HatKontoklarname,HatArtikelnummer
FROM Kandidaten
WHERE TreffendeSpalten >= 5
ORDER BY
 CASE WHEN Objekttyp='VIEW' THEN 0 ELSE 1 END,
 TreffendeSpalten DESC,SchemaName,Objektname;

-- 2. Spezifisch nach View-Namen und den ungewoehnlichen eBay-Spalten suchen,
--    falls Abschnitt 1 keine vollstaendig passende Quelle zeigt.
SELECT TOP (100)
 s.name AS SchemaName,
 o.name AS Objektname,
 CASE o.type WHEN 'V' THEN 'VIEW' ELSE 'TABELLE' END AS Objekttyp,
 c.name AS Spalte
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
JOIN sys.columns AS c ON c.object_id=o.object_id
WHERE o.type IN ('V','U')
  AND (
     (o.name LIKE '%ebay%' AND c.name IN
       ('cType','nStatus','fStartPrice','cEbayUserName','cItemId'))
     OR c.name IN ('cEbayUserName','nVorlageLaufend','fArtikelEbayPreis')
  )
ORDER BY s.name,o.name,c.name;

-- Der Viewname aus Abschnitt 1/2 ist der naechste Eingang fuer die
-- getrennte, lesende Angebots-/Vorlagen-Synchronisation.
-- NICHT dieselbe Artikelnummer als eindeutig pro Angebot behandeln:
-- ein Artikel kann Vorlage, aktuelle und alte Angebotszeilen haben.
