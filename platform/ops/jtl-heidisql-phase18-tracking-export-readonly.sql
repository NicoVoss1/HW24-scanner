/*
 HW24 Kundenservice: lesender JTL-Wawi Track-&-Trace-Export.
 Quellsystem: SQL Server eazybusiness. Keine Datenänderungen.
 Nur für einen PRIVATEN UTF-8-CSV-Export mit Semikolon, Header und CSV-Quoting.
 Enthält Kundensendungsdaten: CSV NIEMALS in Git/Webroot oder per E-Mail ablegen.
 Für den isolierten Test-Snapshot, noch kein unbeaufsichtigter Produktionssync.
 Verifizierte Quelle: Versand.lvJtlTrackingPakete -> Shipping.tPackage
 (kVersand + cTrackingId) -> Shipping.tState (kPackage).
 Paketstatus cText enthält ggf. die Filialadresse, cLocation darf leer sein.
 dDate-Zeitzone ist NOCH NICHT durch JTL dokumentiert/abgenommen.
*/

SET NOCOUNT ON;

SELECT TOP (50000)
    v.cExterneBestellnummer AS amazon_order_id,
    v.cAuftragsnummer AS jtl_order,
    v.cLieferscheinnummer AS delivery_note,
    v.cTrackingID AS tracking_number,
    v.cVersandart AS carrier,
    p.kPackage AS package_id,
    v.kVersand AS shipment_id,
    s.kState AS state_id,
    CONVERT(nvarchar(23),s.dDate,121) AS event_at_raw,
    s.nStateType AS status_code,
    s.cText AS event_text,
    s.cProviderState AS provider_status,
    s.cLocation AS scan_location,
    s.cCountry AS country
FROM Versand.lvJtlTrackingPakete AS v
INNER JOIN Shipping.tPackage AS p
    ON p.kVersand=v.kVersand
   AND p.cTrackingId=v.cTrackingID
LEFT JOIN Shipping.tState AS s
    ON s.kPackage=p.kPackage
WHERE v.dErstellt >= DATEADD(DAY,-45,GETDATE())
  AND v.cExterneBestellnummer LIKE N'___-_______-_______'
  AND v.cTrackingID IS NOT NULL
  AND v.cTrackingID <> N''
ORDER BY v.cExterneBestellnummer, v.kVersand, p.kPackage, s.dDate, s.kState;

/*
 Zur direkten Kontrolle der bekannten Testsendung die WHERE-Klausel
 alternativ um diese Zeile ERGÄNZEN:
 AND v.cTrackingID=N'00340434887917016344'

 Auftrags- oder Sendungsnummer nie als freies SQL-Fragment konkatenieren;
 die spätere App verwendet ausschließlich geprüfte Parameter und
 PRIVATE Snapshots. Diese Exportabfrage kann bei >=50.000 Zeilen
 abschneiden: vor Produktivsync Chunk-/Vollständigkeitsnachweis ergänzen.
*/
