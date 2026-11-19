/* HW24 · 20 gezielte SELECT-Berechtigungen für den reinen JTL-Lesezugriff.
   Quelle: tatsächlicher HW24_JTL_LeserechtePruefen.ps1-Lauf 17.11.2026.
   Ausführen AUSSCHLIESSLICH in SQL Server Management Studio als berechtigter
   Datenbankadministrator, nach eigener Prüfung. NICHT in PowerShell.
   Die Datei ändert SQL-Berechtigungen, aber keine Produkt- oder Lagerdaten.
   SQL-Benutzer: hw24_reader · Datenbank: eazybusiness.
   Sicher: alle Objekte + Benutzer müssen existieren, sonst Abbruch vor GRANT.
*/
USE [eazybusiness];
SET NOCOUNT ON;
IF DATABASE_PRINCIPAL_ID(N'hw24_reader') IS NULL
    THROW 51000, 'HW24: Datenbankbenutzer hw24_reader existiert nicht. Keine Aenderung.', 1;

DECLARE @objects TABLE(schema_name sysname NOT NULL, object_name sysname NOT NULL);
INSERT INTO @objects(schema_name,object_name) VALUES
 (N'Artikel',N'tArtikelAsin'),
 (N'Artikel',N'tArtikelkosten'),
 (N'dbo',N'pf_amazon_angebot'),
 (N'dbo',N'pf_amazon_angebot_ext'),
 (N'dbo',N'pf_amazon_angebot_mapping'),
 (N'dbo',N'teklieferantpreise'),
 (N'dbo',N'tHersteller'),
 (N'dbo',N'tkategorieartikel'),
 (N'dbo',N'tlagerbestand'),
 (N'dbo',N'tliefartikel'),
 (N'dbo',N'tMassEinheit'),
 (N'dbo',N'tSteuerklasse'),
 (N'dbo',N'tSteuersatz'),
 (N'dbo',N'tSteuerzone'),
 (N'dbo',N'tSteuerzoneLand'),
 (N'dbo',N'tVersandklasse'),
 (N'dbo',N'tWarengruppe'),
 (N'dbo',N'tWarenLagerPlatz'),
 (N'dbo',N'vWarenlagerPlatzArtikel'),
 (N'SCX',N'tVerkaufskanalArtikelData');

IF EXISTS (
  SELECT 1
  FROM @objects AS planned
  LEFT JOIN sys.schemas AS sc ON sc.name=planned.schema_name
  LEFT JOIN sys.objects AS ob ON ob.schema_id=sc.schema_id AND ob.name=planned.object_name
  WHERE ob.object_id IS NULL
)
BEGIN
    SELECT planned.schema_name,planned.object_name
    FROM @objects AS planned
    LEFT JOIN sys.schemas AS sc ON sc.name=planned.schema_name
    LEFT JOIN sys.objects AS ob ON ob.schema_id=sc.schema_id AND ob.name=planned.object_name
    WHERE ob.object_id IS NULL;
    THROW 51001, 'HW24: Mindestens eine Tabelle/View fehlt oder ist fuer den SQL-Admin nicht sichtbar. Nichts freigeben.', 1;
END;

BEGIN TRY
    BEGIN TRANSACTION;
    GRANT SELECT ON OBJECT::[Artikel].[tArtikelAsin] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[Artikel].[tArtikelkosten] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[pf_amazon_angebot] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[pf_amazon_angebot_ext] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[pf_amazon_angebot_mapping] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[teklieferantpreise] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tHersteller] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tkategorieartikel] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tlagerbestand] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tliefartikel] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tMassEinheit] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tSteuerklasse] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tSteuersatz] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tSteuerzone] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tSteuerzoneLand] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tVersandklasse] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tWarengruppe] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[tWarenLagerPlatz] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[dbo].[vWarenlagerPlatzArtikel] TO [hw24_reader];
    GRANT SELECT ON OBJECT::[SCX].[tVerkaufskanalArtikelData] TO [hw24_reader];
    COMMIT TRANSACTION;
    PRINT 'HW24: 20 gezielte SELECT-Berechtigungen fuer hw24_reader erfolgreich gesetzt.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;

-- Verbindlichen Nachweis mit derselben SQL-Anmeldung liefert danach
-- C:\HW24\HW24_JTL_LeserechtePruefen.ps1 (Erwartung: OK 22, FEHLT 0).
-- Danach lesende Quellenprobe, NICHT gleich Vollsynchronisation.
