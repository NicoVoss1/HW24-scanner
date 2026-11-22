/* HW24 SQL19: nur benötigte Spalten; als sa in HeidiSQL / eazybusiness.
   Die vorhandenen 22 Objekt-SELECT-Rechte bleiben unverändert.
   Keine Schreibrechte. Kein SELECT * auf dem Firmenstamm. */
GRANT SELECT ON OBJECT::[dbo].[tfirma] ([kFirma],[cName]) TO [hw24_reader];
GRANT SELECT ON OBJECT::[dbo].[tWarenLagerEingang] ([kWarenLagerEingang],[kArtikel]) TO [hw24_reader];
GRANT SELECT ON OBJECT::[dbo].[tWarenLagerEingangHistorie] ([kWarenLagerEingangHistorie],[kWarenLagerEingang],[dZeitstempel],[kBuchungsArt],[fAnzahl],[kWarenLagerPlatzStart],[kWarenLagerPlatzZiel]) TO [hw24_reader];
GRANT SELECT ON OBJECT::[dbo].[tBuchungsArt] ([kBuchungsart],[cName]) TO [hw24_reader];
