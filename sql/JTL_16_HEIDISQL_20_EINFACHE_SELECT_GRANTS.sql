/* HW24 SQL16: Für HeidiSQL und SSMS. EXAKT 20 eigenständige GRANT SELECT Anweisungen.
   Aktive Sitzung muss mit eazybusiness verbunden sein. SQL-Login: sa oder
   ein anderes Konto mit Berechtigung zur Rechtevergabe.
   Ausgewählte Datenbank in HeidiSQL vor der Ausführung kontrollieren!
   Keine DECLARE, BEGIN TRANSACTION, TRY/CATCH, GO oder Tabellenvariablen.
   Ausschließlich SELECT-Rechte. Keine Datenänderung an JTL-Wawi.
   Bei Fehler bitte vollständige SQL-Fehlermeldung melden und NICHT Voll-Sync starten.
*/
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
