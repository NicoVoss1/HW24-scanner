/*
 HW24 · SQL15: Einfache SELECT-Rechte ohne @objects-Tabellenvariable.
 Grund: SQL14 wurde offenbar aus dem JTL-Wawi SQL-Fenster oder nicht
 als kompletter SQL-Batch ausgefuehrt und scheiterte an @objects.

 NUR in Microsoft SQL Server Management Studio (SSMS) ausfuehren.
 Mit einem SQL-Konto anmelden, das Berechtigungen vergeben darf.
 Datenbank: eazybusiness; Benutzer: hw24_reader.

 Aenderung: exakt 20 SELECT-Rechte. Keine Schreibrechte an JTL.
 Nachher mit C:\HW24\HW24_JTL_LeserechtePruefen.ps1 pruefen.
 Einmal als GANZEN Batch ausfuehren (keine Textauswahl, kein GO).
*/
USE [eazybusiness];
SET NOCOUNT ON;

IF DATABASE_PRINCIPAL_ID(N'hw24_reader') IS NULL
    THROW 51010, 'Abbruch: hw24_reader ist kein Benutzer von eazybusiness.', 1;

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
    PRINT 'HW24 OK: 20 SELECT-Berechtigungen fuer hw24_reader vergeben.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;

SELECT
    N'hw24_reader' AS Datenbankbenutzer,
    N'20 SELECT-Berechtigungen angefordert' AS Ergebnis,
    DB_NAME() AS Datenbank;
