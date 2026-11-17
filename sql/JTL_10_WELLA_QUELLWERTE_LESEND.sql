/* HW24 v4.8: Datenwert-Probe fuer EAN 4015600040482
   Alle Tabellennamen und Spalten stammen aus JTL_09 Mapping des Nutzers.
   Nur lesende SQL SELECT Statements auf JTL-Wawi eazybusiness.
   Bitte Ergebnisse ohne Zugangsdaten exportieren. 
*/
USE [eazybusiness];
SET NOCOUNT ON;

-- 1) Kernstamm, Hersteller, Kosten, Warengruppe, Steuer, Versand und verfuegbarer Bestand
SELECT
 a.kArtikel, a.cArtNr, a.cBarcode, a.cASIN,
 h.cName AS hersteller_name,
 ak.fArtikelkosten AS kosten_aus_tArtikelkosten,
 a.fEKNetto AS durchschnittlicher_ek,
 a.fLetzterEK AS letzter_ek,
 wg.cName AS warengruppe_name,
 sk.cName AS steuerklasse_name,
 vk.cName AS versandklasse_name,
 lb.fLagerbestand AS lagerbestand,
 lb.fVerfuegbar AS verfuegbar,
 lb.fInAuftraegen AS in_auftraegen
FROM dbo.tArtikel AS a
LEFT JOIN dbo.tHersteller AS h ON h.kHersteller=a.kHersteller
LEFT JOIN Artikel.tArtikelkosten AS ak ON ak.kArtikel=a.kArtikel
LEFT JOIN dbo.tWarengruppe AS wg ON wg.kWarengruppe=a.kWarengruppe
LEFT JOIN dbo.tSteuerklasse AS sk ON sk.kSteuerklasse=a.kSteuerklasse
LEFT JOIN dbo.tVersandklasse AS vk ON vk.kVersandklasse=a.kVersandklasse
LEFT JOIN dbo.tlagerbestand AS lb ON lb.kArtikel=a.kArtikel
WHERE a.cBarcode=N'4015600040482'
   OR a.cArtNr=N'4015600040482'
   OR a.cHAN=N'4015600040482';

-- 2) Lieferanten und individuelle Einkaufspreise: mehrere Zeilen pro Artikel moeglich
SELECT TOP (25)
 a.kArtikel,a.cArtNr,
 lf.cFirma AS lieferant,
 la.cLiefArtNr AS lieferanten_artikelnummer,
 la.fEKNetto AS lieferanten_ek_netto,
 la.fEKBrutto AS lieferanten_ek_brutto,
 la.nStandard AS standardlieferant,
 ep.fPreis1 AS ek_preis1,ep.nAnzahl1 AS ab_menge1
FROM dbo.tArtikel AS a
JOIN dbo.tliefartikel AS la ON la.tArtikel_kArtikel=a.kArtikel
LEFT JOIN dbo.tlieferant AS lf ON lf.kLieferant=la.tLieferant_kLieferant
LEFT JOIN dbo.teklieferantpreise AS ep
 ON ep.kArtikel=a.kArtikel AND ep.kLieferant=la.tLieferant_kLieferant
WHERE a.cBarcode=N'4015600040482'
   OR a.cArtNr=N'4015600040482'
   OR a.cHAN=N'4015600040482'
ORDER BY a.kArtikel,la.nStandard DESC,la.tLieferant_kLieferant;

-- 3) Amazon-Angebote: Schluessel cSellerSKU + kUser; Ext zusaetzlich nPlattform
SELECT TOP (25)
 a.kArtikel,a.cArtNr,
 m.cSellerSKU AS seller_sku,
 o.nPlattform AS plattform,
 o.cASIN1 AS angebot_asin,
 o.fPrice AS angebotspreis,
 o.nQuantity AS angebotsbestand,
 o.cFulfillmentChannel AS fulfillment,
 e.fPreis AS konfigurierter_preis,
 e.nMaxBestand AS maximalbestand,
 e.nB2BPreiseSenden AS b2b_aktiv
FROM dbo.tArtikel AS a
JOIN dbo.pf_amazon_angebot_mapping AS m ON m.kArtikel=a.kArtikel
JOIN dbo.pf_amazon_angebot AS o
 ON o.cSellerSKU=m.cSellerSKU AND o.kUser=m.kUser
LEFT JOIN dbo.pf_amazon_angebot_ext AS e
 ON e.cSellerSKU=o.cSellerSKU AND e.kUser=o.kUser
 AND e.nPlattform=o.nPlattform
WHERE a.cBarcode=N'4015600040482'
   OR a.cArtNr=N'4015600040482'
   OR a.cHAN=N'4015600040482'
ORDER BY a.kArtikel,o.nPlattform,m.cSellerSKU;

-- 4) Kategorie-Zuordnung, Mehrsprachigkeit und Shops getrennt anzeigen
SELECT TOP (50)
 a.kArtikel,a.cArtNr,
 ca.kKategorie,
 ks.kSprache,ks.kPlattform,ks.kShop,
 ks.cName AS kategoriename
FROM dbo.tArtikel AS a
JOIN dbo.tkategorieartikel AS ca ON ca.kArtikel=a.kArtikel
LEFT JOIN dbo.tKategorieSprache AS ks ON ks.kKategorie=ca.kKategorie
WHERE a.cBarcode=N'4015600040482'
   OR a.cArtNr=N'4015600040482'
   OR a.cHAN=N'4015600040482'
ORDER BY ca.kKategorie,ks.kSprache,ks.kPlattform,ks.kShop;
