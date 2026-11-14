/* HW24 / JTL-Wawi: EK-Kontrolle der EAN 4045787218848
   eazybusiness, Microsoft SQL Server 2017
   Ausschliesslich lesend, aendert keine Daten.
   fEKNetto = durchschnittlicher Netto-EK (GLD).
   fLetzterEK und dLetzterEK = letzter EK mit zugehoerigem Buchungsdatum.
   fVKNetto = Netto-Standardverkaufspreis; kein Brutto-Preis.
   Pruefe alle Zeilen: ein Barcode kann mehreren JTL-Artikeln zugeordnet sein.
*/
USE [eazybusiness];
SELECT
 a.kArtikel AS JTL_Artikel_ID,
 a.cArtNr AS Artikelnummer,
 a.cBarcode AS EAN,
 b.cName AS Artikelname,
 a.fEKNetto AS Durchschnittlicher_EK_Netto,
 a.fLetzterEK AS Letzter_EK_JTL,
 a.dLetzterEK AS Datum_Letzter_EK,
 a.fVKNetto AS VK_Netto,
 a.kSteuerklasse AS Steuerklassen_ID,
 a.dMod AS Letzte_Artikelaenderung
FROM dbo.tArtikel AS a
LEFT JOIN dbo.tArtikelBeschreibung AS b
 ON b.kArtikel=a.kArtikel
 AND b.kSprache=1 AND b.kPlattform=1 AND b.kShop=0
WHERE LTRIM(RTRIM(a.cBarcode))=N'4045787218848'
ORDER BY a.kArtikel;
