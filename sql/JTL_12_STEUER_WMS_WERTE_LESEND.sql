/* HW24 · Steuer- und Fachmengen-WERTPRUEFUNG fuer 4015600040482
   SQL Server eazybusiness, ausschliesslich SELECT (keine Buchung).
   Vier Ergebnistabellen getrennt als CSV exportieren.
*/
USE [eazybusiness];
SET NOCOUNT ON;

-- Ergebnis 1: tatsaechliche WMS-Fachmengen + Lagersperren.
SELECT TOP (200)
 a.kArtikel,a.cArtNr,a.cBarcode,
 p.kWarenLagerPlatz,
 l.cName AS fachbezeichnung,
 l.kWarenLager AS warenlager_id,
 p.fAnzahl AS menge_im_fach,
 l.nGesperrt AS fach_gesperrt,
 l.nAuslieferungGesperrt AS auslieferung_gesperrt
FROM dbo.tArtikel AS a
LEFT JOIN dbo.vWarenlagerPlatzArtikel AS p ON p.kArtikel=a.kArtikel
LEFT JOIN dbo.tWarenLagerPlatz AS l ON l.kWarenLagerPlatz=p.kWarenLagerPlatz
WHERE a.cBarcode=N'4015600040482'
   OR a.cHAN=N'4015600040482'
   OR a.cArtNr=N'4015600040482'
ORDER BY l.kWarenLager,l.cName,p.kWarenLagerPlatz;

-- Ergebnis 2: Alle fuer den Artikel konfigurierten deutschen Steuersaetze.
-- Erst nach Pruefung kFirma / kSteuerzone / Versandland als effektiven
-- Steuersatz ausgeben. Die Einheit von fSteuersatz wird real geprueft.
SELECT DISTINCT
 a.kArtikel,a.kSteuerklasse,
 sk.cName AS steuerklasse,
 zone.kFirma AS firma_id,zone.kSteuerzone AS steuerzone_id,
 zone.cName AS steuerzone,
 land.cISO AS zielland,
 s.fSteuersatz AS steuersatz_quellwert,
 s.nPrio AS prioritaet
FROM dbo.tArtikel AS a
JOIN dbo.tSteuerklasse AS sk ON sk.kSteuerklasse=a.kSteuerklasse
JOIN dbo.tSteuersatz AS s ON s.kSteuerklasse=a.kSteuerklasse
JOIN dbo.tSteuerzone AS zone ON zone.kSteuerzone=s.kSteuerzone
JOIN dbo.tSteuerzoneLand AS land
  ON land.kSteuerzone=zone.kSteuerzone AND land.cISO=N'DE'
WHERE a.cBarcode=N'4015600040482'
   OR a.cHAN=N'4015600040482'
   OR a.cArtNr=N'4015600040482'
ORDER BY firma_id,steuerzone_id,prioritaet;

-- Ergebnis 3: Haendische Kontrolle des DE-Normalsatzes je Land/Bundesland.
SELECT cISOLand,cKuerzelBundesland,fNormalerSatz,fErmaessigterSatz
FROM dbo.tLandSteuersatz
WHERE cISOLand=N'DE'
ORDER BY cKuerzelBundesland;

-- Ergebnis 4: Vergleichswert der JTL-WMS-Artikelview.
-- fMwSt kann einen kontextabhaengigen Anzeige-Wert enthalten und darf
-- nicht ohne Vergleich mit dem Umsatzsteuer-Schema in die CSV fliessen.
SELECT TOP (5) kArtikel,cArtNr,cBarcode,kSteuerklasse,fMwSt,cHersteller
FROM dbo.vWMSArtikel
WHERE cBarcode=N'4015600040482'
   OR cArtNr=N'4015600040482';
