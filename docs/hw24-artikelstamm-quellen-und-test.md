# HW24: Artikelpflege als dauerhaftes Stammdaten- und Quellenarchiv

Stand: 19.11.2026. Für die Testdomain wird nach erfolgreichen GitHub-Prüfungen ausschließlich der bereinigte Branch `deploy/staging` veröffentlicht. Plesk-Git ist auf Test derzeit **manuell**, nicht automatisch; Produktivdomain unverändert.

## Vorhanden / implementiert im Entwicklungsbranch
- **Primärartikel**: `hw24_curated_articles`, Freigabe pro EAN, JSON-Felder, Versionen; **Audit**: `hw24_curated_audit` mit revisionssicherem Verlauf. Keine neue parallele Artikelstammtabelle.
- **Wiederherstellung**: beim nächsten EAN-Scan werden bestätigte Werte in das vorhandene Artikelobjekt übernommen und visuell grün markiert.
- **Direktpflege**: explizite Änderungen im Haupt-Artikelarbeitsplatz, in den Stammdaten und im JTL-Profil werden per Auswahl bzw. Enter/Change im eigenen HW24-Artikelstand gespeichert. Nicht berührte Katalogvorschläge werden **nicht** gespeichert.
- **Korrektur**: durch späteres Bearbeiten mit neuer Version; alle bisherigen Werte bleiben in der Audit-Tabelle erhalten. Bei Versionskonflikt keine blinde Überschreibung.
- **Verfügbare Quellen**: JTL-Spiegel (Momentaufnahme, maximal 30 Minuten als aktuell eingestuft), historische Excel-Quellzeilen (Originale bleiben), Amazon-Katalog (Cache/Snapshot mit Zeitstempel), Amazon-Pricing (on demand, nicht als dauerhaft gespeicherter eigener Preis).
- **Kalkulation**: Standard-VK brutto/netto, JTL-Standard-/Amazon-/eBay-Spiegelpreise getrennt. Amazon Fees v0 schätzt Gebühren für einen ausdrücklich ausgewählten eigenen Modell-Verkaufspreis. Ein abweichender Repricer-VK ist ohne Angebotsdaten nicht bekannt.
- **Kaufvergleich**: Amazon.de-Händlerangebote mit Preis inklusive gemeldeter Versandkosten, tatsächlicher Buy-Box-Kennzeichnung, FBA/FBM, Feedback und Versandvorbereitungszeit, soweit Amazon die Felder liefert. Lieferung an deutsche Adresse wird aus Amazon.de **nicht** garantiert.
- **Steuer**: 19 % in der Rechenhilfe ausschließlich als gekennzeichnete Modellannahme. Eine gespeicherte Steuerklasse ist **keine** Prüfung der Steuerbarkeit bei EU-Entfernungsverkäufen.

## Grenzen / künftig zu ergänzen
1. **JTL-Wawi echte Angebote**: Der vorhandene Windows-Read-only-Sync exportiert `fAmazonVK` und `fEbayPreis` aus `dbo.tArtikel`. Die tatsächlichen Amazon-Angebote/Preise nach arbytrage.io sowie eBay-Angebots-/Vorlagenpreise benötigen schema-verifizierte Joins zu weiteren eazybusiness-Tabellen bzw. eine freigegebene Angebots-API.
2. **Lieferantenstamm**: Ein Amazon-Verkäufer ist keine geprüfte Bezugsquelle. JTL-Lieferant und Lieferantenartikelnummer sowie Einkaufspreisstaffeln müssen über verifizierte Tabellen/Joins kommen, keine Schätzung aus IDs.
3. **Steuersätze**: Steuerklasse/Steuerschlüssel/Unternehmens- und Ziellandkontext aus JTL und Steuerberatung prüfen; kein pauschales EU-19-Prozent-Setting.
4. **Amazon Katalogrechte**: Fakten wie EAN/Marke/Packungsmenge können dem Nutzer zur Identitätsprüfung angezeigt werden. Produktbeschreibungen, Bilder, Bullets und Amazon-Rohdaten dürfen nicht pauschal als freie, dauerhaft globale Eigentumsdatenbank oder Produktwerbetexte übernommen werden; SP-API-Bedingungen, Schutzmaßnahmen, Datenaufbewahrung und Bildrechte prüfen.
5. **Versand- und Verkäuferdaten**: Seller ID und Feedback/Dispatch werden nicht als Garantie für eine deutsche Lieferadresse oder als bestätigter Händlername ausgegeben; Namen erst nach Quellenprüfung.
6. **End-to-End Tests erforderlich**: GitHub-CI prüft Syntax und Website-Paket, aber nicht PHP-HTTP-Auth, MariaDB-Revisionskonflikt, Plesk-Deployment, Seller-Feedback-API-Felder, Fees-Berechtigung oder Scanner-Usability.

## Konkreter Test
- GitHub CI erfolgreich und `deploy/staging` aktualisiert → im bestehenden Plesk Git auf **test-artikelmanager.hairworld24.de** `fetch`, dann `deploy`.
- EAN `4021609029274` scannen → Warengruppe auswählen oder Kosten eingeben und Enter → **Dauerhaft gespeichert** muss erscheinen.
- API `/api/curated.php?ean=4021609029274` danach aufrufen → `exists:true`, steigende `revision`, Feldwert und `audit`.
- Nochmals EAN scannen → Feld bleibt grün als **Gespeichert** und Korrektur per Bearbeiten möglich.
- JTL-/Amazon-Quellenstände als Datum/Uhrzeit prüfen; später Gebührenabfrage und erfolgreiche Plesk-Automatik separat abnehmen.
