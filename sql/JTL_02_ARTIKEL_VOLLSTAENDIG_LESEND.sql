/* HW24 – JTL-Quellabfrage, ausschliesslich lesend.
   Datenbank: eazybusiness (SQL Server 2017 Express)
   Auswertung 2026-11-14: 2.689 Artikel; 2.689 Hauptbeschreibungen
   (kSprache=1, kPlattform=1, kShop=0), 1 weitere Beschreibung
   (kSprache=1, kPlattform=30, kShop=0).

   WICHTIG: Barcodes als Zeichenketten uebernehmen (fuehrende Nullen).
   fVKNetto und fEKNetto sind NETTO; nicht als Bruttopreis ausgeben.
   Der DB-Lagerbestand ist nicht automatisch verfuegbarer Verkaufsbestand.
   Die Abfrage gibt keine Daten an HW24 weiter und aendert nichts.
*/
USE [eazybusiness];

-- 1. Eine Haupttext-Zeile je Artikel, ohne willkuerliche TEXT-Auswahl.
SELECT
   a.kArtikel AS jtl_article_id,
   a.cArtNr AS artikelnummer,
   a.cBarcode AS ean,
   a.cHAN AS han,
   a.cASIN AS asin,
   b.cName AS artikelname,
   b.cKurzBeschreibung AS kurzbeschreibung,
   b.cBeschreibung AS beschreibung,
   a.fVKNetto AS netto_vk,
   a.fEKNetto AS durchschnitt_ek_netto,
   a.fLetzterEK AS letzter_ek,
   a.dLetzterEK AS letzter_ek_datum,
   a.fAmazonVK AS amazon_vk,
   a.fEbayPreis AS ebay_preis,
   a.nLagerbestand AS lagerbestand,
   a.dMod AS quelle_geaendert
FROM dbo.tArtikel AS a
LEFT JOIN dbo.tArtikelBeschreibung AS b
   ON b.kArtikel = a.kArtikel
  AND b.kSprache = 1
  AND b.kPlattform = 1
  AND b.kShop = 0
ORDER BY a.kArtikel;

-- 2. ALLE Artikelstammfelder im Original erhalten.
SELECT a.*
FROM dbo.tArtikel AS a
ORDER BY a.kArtikel;

-- 3. ALLE Beschreibungsvarianten behalten, auch Plattform 30.
SELECT b.*
FROM dbo.tArtikelBeschreibung AS b
ORDER BY b.kArtikel, b.kSprache, b.kPlattform, b.kShop;

-- 4. Vollstaendigkeit des Haupttext-JOIN pruefen.
SELECT
  COUNT_BIG(*) AS zeilen_nach_join,
  COUNT(DISTINCT a.kArtikel) AS eindeutige_artikel,
  SUM(CASE WHEN b.kArtikel IS NULL THEN 1 ELSE 0 END) AS artikel_ohne_hauptbeschreibung
FROM dbo.tArtikel AS a
LEFT JOIN dbo.tArtikelBeschreibung AS b
   ON b.kArtikel = a.kArtikel
  AND b.kSprache = 1
  AND b.kPlattform = 1
  AND b.kShop = 0;
