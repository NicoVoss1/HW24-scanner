/*
 HW24 SQL17: Firmennamen, Steuerzone und vollständige JTL-WMS-Bewegungen
 Ausschliesslich lesende SELECT-Abfragen für eazybusiness.
 Ausführen in HeidiSQL mit Anmeldung sa und gewählter Datenbank eazybusiness.

 ERGEBNIS 1: Die Felder der betroffenen JTL-Tabellen.
 ERGEBNIS 2: Klarname der Steuerzone 3.
 ERGEBNIS 3: Firmenstammsatz zur ID 1. Nur Firmanamen weitergeben;
             möglicherweise vertrauliche weitere Firmendaten nicht teilen.
 ERGEBNIS 4: VOLLE Bewegungshistorie zu kArtikel=2594. KEIN Datumsfilter.
             Wenn sehr viele Zeilen vorkommen, Ergebnis als CSV exportieren.
 WMS-Erweiterung nicht ausführen, bevor diese Joinfelder bestätigt sind.
*/
SELECT s.name AS SchemaName,o.name AS Tabellenname,
       c.name AS Spalte,t.name AS SQLTyp,c.column_id AS Position
FROM sys.objects AS o
JOIN sys.schemas AS s ON s.schema_id=o.schema_id
JOIN sys.columns AS c ON c.object_id=o.object_id
JOIN sys.types AS t ON t.user_type_id=c.user_type_id
WHERE o.type IN ('U','V') AND (
    (s.name='dbo' AND o.name IN (
      'tFirma','tSteuerzone','tWarenLagerEingang','tWarenLagerEingangHistorie',
      'tBuchungsArt','tWarenLagerPlatz','tLagerbestand','tArtikel'
    ))
  )
ORDER BY s.name,o.name,c.column_id;

SELECT kFirma,kSteuerzone,cName AS Steuerzonenname
FROM dbo.tSteuerzone
WHERE kSteuerzone=3;

-- Abfrage 3: Nur ID und Firmenname lesen, keine Anschrift, Konten oder Kontaktfelder.
-- Falls cName in dieser JTL-Version nicht existiert, bitte Ergebnis 1 senden.
SELECT kFirma,cName AS Firmenname
FROM dbo.tFirma
WHERE kFirma=1;

-- Abfrage 4: vollständig gespeicherte Buchungen des Testartikels,
-- ohne Datumseingrenzung und ohne TOP, auch bei aktuellem Bestand Null.
-- Die genaue Semantik einzelner Bewegungsfelder erst anhand der Ausgabe bestimmen.
SELECT h.*
FROM dbo.tWarenLagerEingangHistorie AS h
INNER JOIN dbo.tWarenLagerEingang AS we
 ON we.kWarenLagerEingang=h.kWarenLagerEingang
WHERE we.kArtikel=2594
ORDER BY h.dZeitstempel DESC;
