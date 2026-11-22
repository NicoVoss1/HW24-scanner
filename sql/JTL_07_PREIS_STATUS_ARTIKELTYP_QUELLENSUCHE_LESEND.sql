/*
 HW24 JTL-Wawi: QUELLENPRUEFUNG fuer Original-Brutto-VK, Lieferstatus,
 Artikeltyp und Waehrung. AUSSCHLIESSLICH SELECT (SQL Server, Windows).
 Start: SSMS oder sqlcmd gegen die lokale eazybusiness-Datenbank,
       mit einem eigenen SQL-Benutzer mit NUR LESEBERECHTIGUNG.

 Keine Plesk-/MariaDB-Anweisung, keine JTL-Schreibzugriffe.
 Dieses Skript liefert Tabellen-/Spalten-Kandidaten, KEIN fertiges Preis-Mapping.
 Erst nach Abgleich mit JTL-Wawi/Kundengruppe/Steuerzone Ergebnisse verwenden.

 Referenzartikel EAN 3474630337398:
 bisher im Mirror: fVKNetto=11.7647, kLieferStatus=1,
                   kSteuerklasse=1, fMassMenge=90.000000.
 14.00 EUR ist bei 19% ein RECHENBEISPIEL, NICHT ein verifizierter
 in JTL gespeicherter Bruttoverkaufspreis.
*/
USE [eazybusiness];
SET NOCOUNT ON;

DECLARE @ean nvarchar(32) = N'3474630337398';

PRINT N'01 - Originale tArtikel-Quellfelder fuer die konkrete EAN';
SELECT TOP (10)
  a.kArtikel, a.cArtNr, a.cBarcode, a.cASIN,
  a.fVKNetto, a.fAmazonVK, a.fEbayPreis,
  a.kLieferStatus, a.kSteuerklasse,
  a.kMassEinheit, a.fMassMenge, a.dMod
FROM dbo.tArtikel AS a
WHERE a.cBarcode = @ean
ORDER BY a.kArtikel;

PRINT N'02 - Typ und Datentypen der originalen tArtikel-Felder';
SELECT
 c.column_id, c.name AS spalte, ty.name AS datentyp,
 c.max_length, c.precision, c.scale, c.is_nullable
FROM sys.columns AS c
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE c.object_id=OBJECT_ID(N'dbo.tArtikel')
  AND (
    c.name LIKE N'%VK%' OR c.name LIKE N'%Preis%' OR
    c.name LIKE N'%Steuer%' OR c.name LIKE N'%Liefer%' OR
    c.name LIKE N'%Typ%' OR c.name LIKE N'%Waehr%' OR
    c.name LIKE N'%Währ%' OR c.name LIKE N'%Mass%' OR
    c.name LIKE N'%ArtikelArt%' OR c.name LIKE N'%Kunden%'
  )
ORDER BY c.column_id;

PRINT N'03 - Kandidatentabellen zu Preisen/Kundengruppe/Steuer/Status/Typ/Waehrung';
SELECT TOP (400)
  sc.name AS schemaname, t.name AS tabellenname,
  c.name AS spaltenname, ty.name AS datentyp
FROM sys.tables AS t
JOIN sys.schemas AS sc ON sc.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE (
  t.name LIKE N'%Preis%' OR t.name LIKE N'%Liefer%' OR
  t.name LIKE N'%Steuer%' OR t.name LIKE N'%Kunden%' OR
  t.name LIKE N'%Waehr%' OR t.name LIKE N'%Währ%' OR
  t.name LIKE N'%Typ%' OR t.name LIKE N'%Waren%' OR
  t.name LIKE N'%Plattform%' OR t.name LIKE N'%Artikel%'
) AND (
  c.name LIKE N'%Preis%' OR c.name LIKE N'%VK%' OR
  c.name LIKE N'%Brutto%' OR c.name LIKE N'%Netto%' OR
  c.name LIKE N'%Steuer%' OR c.name LIKE N'%Liefer%' OR
  c.name LIKE N'%Kunden%' OR c.name LIKE N'%Waehr%' OR
  c.name LIKE N'%Währ%' OR c.name LIKE N'%Typ%' OR
  c.name LIKE N'%Name%' OR c.name LIKE N'%Bezeich%' OR
  c.name LIKE N'%Standard%' OR c.name LIKE N'%Artikel%' OR
  c.name LIKE N'%Plattform%' OR c.name LIKE N'%Status%' OR
  c.name LIKE N'%Gueltig%' OR c.name LIKE N'%Gültig%'
)
ORDER BY sc.name,t.name,c.column_id;

PRINT N'04 - Moegliche Nachschlagetabellen mit kLieferStatus/kSteuerklasse';
SELECT TOP (200)
 sc.name AS schemaname, t.name AS tabellenname,
 c.name AS schluesselspalte, ty.name AS datentyp
FROM sys.tables AS t
JOIN sys.schemas AS sc ON sc.schema_id=t.schema_id
JOIN sys.columns AS c ON c.object_id=t.object_id
JOIN sys.types AS ty ON ty.user_type_id=c.user_type_id
WHERE c.name IN (N'kLieferStatus',N'kSteuerklasse',N'kVersandklasse',N'kArtikeltyp')
ORDER BY c.name,sc.name,t.name;

PRINT N'05 - Physisch vorhandene Foreign-Key-Verknuepfungen (falls deklariert)';
SELECT TOP (200)
 fk.name AS fremdschluesselname,
 OBJECT_SCHEMA_NAME(fk.parent_object_id) AS von_schema,
 OBJECT_NAME(fk.parent_object_id) AS von_tabelle,
 COL_NAME(fkc.parent_object_id,fkc.parent_column_id) AS von_spalte,
 OBJECT_SCHEMA_NAME(fk.referenced_object_id) AS nach_schema,
 OBJECT_NAME(fk.referenced_object_id) AS nach_tabelle,
 COL_NAME(fkc.referenced_object_id,fkc.referenced_column_id) AS nach_spalte
FROM sys.foreign_keys AS fk
JOIN sys.foreign_key_columns AS fkc ON fkc.constraint_object_id=fk.object_id
WHERE (
 COL_NAME(fkc.parent_object_id,fkc.parent_column_id) IN
  (N'kLieferStatus',N'kSteuerklasse',N'kVersandklasse',N'kArtikel',N'kArtikeltyp')
 OR COL_NAME(fkc.referenced_object_id,fkc.referenced_column_id) IN
  (N'kLieferStatus',N'kSteuerklasse',N'kVersandklasse',N'kArtikeltyp')
)
ORDER BY von_tabelle,von_spalte;

PRINT N'06 - Gefundene Tabellen mit Artikel-ID und moeglichen VK-Preisspalten';
SELECT TOP (120)
 sc.name AS schemaname,t.name AS tabellenname,
 MAX(CASE WHEN c.name=N'kArtikel' THEN 1 ELSE 0 END) AS enthaelt_kArtikel,
 SUM(CASE WHEN c.name LIKE N'%Preis%' OR c.name LIKE N'%VK%'
               OR c.name LIKE N'%Brutto%' OR c.name LIKE N'%Netto%'
          THEN 1 ELSE 0 END) AS moegliche_preisspalten
FROM sys.tables t
JOIN sys.schemas sc ON sc.schema_id=t.schema_id
JOIN sys.columns c ON c.object_id=t.object_id
GROUP BY sc.name,t.name
HAVING MAX(CASE WHEN c.name=N'kArtikel' THEN 1 ELSE 0 END)=1
   AND SUM(CASE WHEN c.name LIKE N'%Preis%' OR c.name LIKE N'%VK%'
                     OR c.name LIKE N'%Brutto%' OR c.name LIKE N'%Netto%'
                THEN 1 ELSE 0 END)>0
ORDER BY moegliche_preisspalten DESC,sc.name,t.name;

PRINT N'FERTIG: Nur SELECT/Metadaten. Kein Preis, Status oder Artikel in JTL geaendert.';
