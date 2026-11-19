/* HW24 SQL18: verifizierte Namen, Bestandswerte und alle gespeicherten
   WMS-Bewegungen für Artikel 2594. Nur SELECT, kein Datumsfilter.
   In HeidiSQL / eazybusiness ausführen. Keine Kontakte, Benutzer oder
   freie Kommentare kopieren. Die Bewegungshistorie ist kein aktueller Bestand. */
SELECT kFirma,cName AS Firmenname FROM dbo.tfirma WHERE kFirma=1;

SELECT kArtikel,fLagerbestand,fVerfuegbar,fInAuftraegen,fZulauf,
       fVerfuegbarGesperrt,fAuslieferungGesperrt
FROM dbo.tlagerbestand WHERE kArtikel=2594;

SELECT h.kWarenLagerEingangHistorie AS bewegung_id,e.kArtikel,
       h.dZeitstempel AS zeitstempel,h.kBuchungsArt AS buchungsart_id,
       b.cName AS buchungsart,h.fAnzahl AS menge,
       h.kWarenLagerPlatzStart AS start_id,p1.cName AS lagerplatz_von,
       h.kWarenLagerPlatzZiel AS ziel_id,p2.cName AS lagerplatz_nach
FROM dbo.tWarenLagerEingangHistorie h
JOIN dbo.tWarenLagerEingang e ON e.kWarenLagerEingang=h.kWarenLagerEingang
LEFT JOIN dbo.tBuchungsArt b ON b.kBuchungsart=h.kBuchungsArt
LEFT JOIN dbo.tWarenLagerPlatz p1 ON p1.kWarenLagerPlatz=h.kWarenLagerPlatzStart
LEFT JOIN dbo.tWarenLagerPlatz p2 ON p2.kWarenLagerPlatz=h.kWarenLagerPlatzZiel
WHERE e.kArtikel=2594
ORDER BY h.dZeitstempel DESC,h.kWarenLagerEingangHistorie DESC;

SELECT COUNT_BIG(*) AS gespeicherte_bewegungen,
 COUNT_BIG(DISTINCT h.kWarenLagerEingangHistorie) AS unterschiedliche_ids
FROM dbo.tWarenLagerEingangHistorie h
JOIN dbo.tWarenLagerEingang e ON e.kWarenLagerEingang=h.kWarenLagerEingang
WHERE e.kArtikel=2594;
