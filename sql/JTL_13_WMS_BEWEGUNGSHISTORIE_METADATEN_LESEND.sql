/* HW24 SQL13 – Bewegungshistorie und Einlagerungsdatum identifizieren.
   JTL-Wawi 2.2.2.0, Datenbank eazybusiness.
   Alle Abfragen sind LESEND. Es werden ausschließlich Metadaten abgefragt.
   Ergebnis 1 und 2 als CSV exportieren; keine Buchungen oder Kundendaten.
*/
USE [eazybusiness];
SET NOCOUNT ON;

-- 1. Kandidaten für WMS Bewegungen, Einlagerungen und Buchungshistorie
SELECT s.name AS schema_name, o.name AS objekt_name, o.type_desc AS objekt_typ
FROM sys.objects o
JOIN sys.schemas s ON s.schema_id=o.schema_id
WHERE o.type IN ('U','V')
  AND (
    o.name LIKE '%Beweg%' OR o.name LIKE '%Buchung%'
    OR o.name LIKE '%WarenLagerEingang%' OR o.name LIKE '%Lagerplatz%'
    OR o.name LIKE '%LagerPlatz%' OR o.name LIKE '%Historie%'
    OR o.name LIKE '%Inventur%'
  )
ORDER BY s.name,o.name;

-- 2. Artikel-/Fachschlüssel und Datums-/Bewegungsspalten dieser Objekte
SELECT s.name AS schema_name, o.name AS objekt_name,
  c.name AS spaltenname, ty.name AS datentyp, c.is_nullable AS nullable,
  c.column_id AS position
FROM sys.objects o
JOIN sys.schemas s ON s.schema_id=o.schema_id
JOIN sys.columns c ON c.object_id=o.object_id
JOIN sys.types ty ON ty.user_type_id=c.user_type_id
WHERE o.type IN ('U','V')
  AND (
    o.name LIKE '%Beweg%' OR o.name LIKE '%Buchung%'
    OR o.name LIKE '%WarenLagerEingang%' OR o.name LIKE '%Lagerplatz%'
    OR o.name LIKE '%LagerPlatz%' OR o.name LIKE '%Historie%'
    OR o.name LIKE '%Inventur%'
  )
  AND (
    c.name LIKE '%Artikel%' OR c.name LIKE '%Platz%'
    OR c.name LIKE '%Lager%' OR c.name LIKE '%Datum%'
    OR c.name LIKE '%Erstell%' OR c.name LIKE '%Buch%'
    OR c.name LIKE '%Zeit%' OR c.name LIKE '%Mod%'
    OR c.name LIKE '%Beweg%' OR c.name LIKE '%Anzahl%'
    OR c.name LIKE '%Menge%' OR c.name LIKE '%Typ%'
    OR c.name LIKE '%Von%' OR c.name LIKE '%Nach%'
    OR c.name LIKE '%Quelle%' OR c.name LIKE '%Ziel%'
    OR c.name LIKE '%Kommentar%' OR c.name LIKE '%Status%'
  )
ORDER BY s.name,o.name,c.column_id;

-- Nächster Schritt NUR nach Verifizierung von Artikel-ID, Start-/Ziel-Fach,
-- Bewegungsart und Bewegungszeit: kontrollierte SELECT TOP-Abfrage für
-- kArtikel=2594 und kWarenLagerPlatz=66.
-- dWmsInventurDatum ist KEIN Nachweis einer Wareneinlagerung.
