# HW24 Preiswächter für eigene Amazon-Angebote (Konzept, noch NICHT aktiv)

Stand: 19.11.2026. Quelle für den tatsächlichen **eigenen, zuletzt von Arbytrage.io repricten Angebotspreis** ist nach Aussage des Betreibers der zurückimportierte Angebotsdatensatz in JTL-Wawi, NICHT pauschal tArtikel.fAmazonVK. Die Tabellen/Schlüssel müssen im SQL Server zunächst per sql/JTL_07_MARKTPLATZ_WMS_QUELLEN_LESEND.sql nachgewiesen werden.

## Daten je SKU/Marketplace
- JTL kArtikel/Artikelnummer/EAN; ASIN; Amazon Seller SKU; Account/Marketplace (z. B. DE).
- Letzter **eigener Live-Angebotspreis** aus verifiziertem JTL-Amazon-Angebot, Abgleichzeit und Fulfillment.
- Arbytrage Mindest-/Max-Preis und Zielmodus, NUR sofern aus autorisierter Quelle lieferbar (Schnittstelle oder eigener Export).
- Marktangebote aus autorisierter Amazon Product Pricing API: Seller ID, Item+Versand=Gesamtpreis, FBA/FBM, Zustand, timestamp und Buy-Box-Flag.
- Eigene Seller IDs ausschließen; identische ASIN/Marktplatz/Zustand vergleichen. Nicht jede Amazon.de-Listung garantiert Zustellung an eine bestimmte deutsche Adresse.

## Vorläufige, anpassbare Ausreißer-Regeln
- **Auffällig**: fremdes neues Angebot (inkl. Versand) mindestens 30 % UND mindestens 3 EUR unter eigenem aktuellen Gesamtangebot.
- **Kritisch**: fremdes Angebot mindestens 50 % UND mindestens 5 EUR darunter, ODER unter nachgewiesener eigener Profitabilitätsuntergrenze; diese Untergrenze muss aus EK, realen Amazon-Gebühren und eigenen Versandkosten berechnet sein.
- **Keine automatische Preisangleichung unter die Arbytrage-Minimum-Grenze**.
- Ein einmaliger extrem niedriger Einzelpreis ist NICHT automatisch ein Verkäufer-Preisfehler. Erst nach zwei plausiblen Abfragen, gleicher ASIN/Verfügbarkeit und aktuellem Angebot eine Benachrichtigung versenden. Prüffälle inkl. Datum/Preis/SellerID protokollieren.
- Quoten/Rate-Limits berücksichtigen, zuerst nur aktive eigene Angebote prüfen; Pollingfrequenz dynamisch nach Amazon-SP-API-Limit und Arbytrage-Benachrichtigungskanal wählen. Kein Dauer-Polling im Browser.

## Maßnahmen
1. Sofort Arbytrage-Dashboard auf bereits vorhandene Konkurrenten-Preisänderungs-Benachrichtigungen prüfen: https://app.arbytrage.io. Nach Produktinformation von Arbytrage sind solche Benachrichtigungen Teil des Systems; ob sie beim Betreiber aktiviert sind, ist noch unbekannt.
2. Nach SQL-Metadatenprüfung echte JTL-Angebotspreise und Seller SKUs LESEND synchronisieren; fremde Preisinformationen nur nach den gültigen Amazon-Datenregeln verwenden.
3. Servergesteuerten HW24-Wächter mit revisionsfähiger Alarmtabelle, Schwellen je SKU, Entwarnung, Sperrfristen und Benachrichtigungskanal aufbauen. Keine automatischen JTL-/Amazon-Preisänderungen.
4. Erst Testdaten/Alarm-Simulation, danach Plesk-Scheduler aktivieren. Nicht behaupten, dass Preiswächter bereits live ist.

## Repricer-Ziele laut arbytrage.io
LOWEST_OFFER = billigstes Gesamtangebot FBA/FBM, LOWEST_FBA_OFFER = nur FBA,
LOWER_BUY_BOX = unter dem Buy-Box-Preis konkurrieren (preisorientierter),
HIGHER_BUY_BOX = oberhalb des Buy-Box-Preises konkurrieren (margenorientierter).
Offizielle Erklärung: https://arbytrage.io/blog/repricing-configuration-guide
