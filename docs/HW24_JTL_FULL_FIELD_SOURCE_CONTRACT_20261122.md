# HW24 · Vollständiger JTL-Artikelfelder-Spiegel und getrennte Verkaufskonten

Stand: 22.11.2026. **Entwicklungsstand in einem isolierten GitHub-Featurebranch. Kein Plesk-Deployment.**

## Bereits vorhandene Originaldaten in MariaDB

Der bestehende Windows-SELECT-Snapshot übernimmt `SELECT * FROM dbo.tArtikel` als `hw24_jtl_articles.raw_json` und sämtliche `dbo.tArtikelBeschreibung`-Varianten als `beschreibungen_json`. Die Originalwerte werden nicht umgerechnet oder überschrieben. Auch bereits vom tatsächlich laufenden Windows-Synchronisierer gelieferte `raw.HW24_RelatedSources` bleiben im Spiegel erhalten. Der Windows-Synchronisierer in GitHub `main` enthält allerdings noch **nicht** sämtliche auf dem Server beobachteten Zusatzquellen. Vor jeder Änderung an der laufenden Windows-Aufgabe den echten eingesetzten Skriptstand ermitteln und sichern.

Die neue, nur-lesende API `api/jtl-fields.php` öffnet genau **einen eindeutigen JTL-Artikel** aus dem letzten vollständigen, frischen Snapshot. Sie zeigt **alle zulässigen Original-Skalare des vorhandenen Spiegels** mit ursprünglichem SQL-Feldnamen und Originaltabelle, alle bereits synchronisierten Textvarianten, verifizierte Zusatzquellen und ggf. belegte `HW24_*`-Feldzuordnungen.

Neue Tabellen werden dadurch **nicht automatisch** in Windows JTL gelesen. Fehlende Quellen werden nicht als nicht existent ausgegeben. Große Texte sind in der Anzeige technisch gekürzt, die Daten bleiben vollständig im ursprünglichen `raw_json` beziehungsweise `beschreibungen_json`.

## Quellregister aus der von Betreiber gelieferten HeidiSQL-Metadatenabfrage

Die Abfrage zeigte **Tabellen- und Spaltenexistenz, keine verifizierten Artikelwerte oder gültigen JOINs**. Ausgewählte Kandidaten:

| Bereich | Originalquelle laut Metadaten | Beobachtete Spalten | Nächster Nachweis |
|---|---|---|---|
| Artikeltyp | `Artikel.tArtikelType` | `kArtikel`, `uArtikelType` | Bedeutungen der GUIDs, Artikelbezug |
| Steuersätze | `dbo.tLandSteuersatz` | `cISOLand`, `fNormalerSatz`, `fErmaessigterSatz` | Land, Steuerklasse, Kundengruppe und gültige JTL-Preisregel |
| Preisarten/Kanäle | `SCX.tChannelPriceType` | `kChannel`, `kShop`, `cPriceTypeId`, `kKundenGruppe` | ID-/Konto-Mapping |
| Kostenarten | `BI.tKostenTyp` | `kKostentyp`, `cKostentyp`, `cKommentar` | fachliche Bedeutung, Bezug zu Artikelkosten |
| Währung | `dbo.ebay_de_waehrung` | `Currency`, `Description` | Währung pro Konto/Angebot |
| Amazon-Preise | `dbo.pf_amazon_apa_preis` | `cSellerSKU`, `nPlattform`, `fPreis` | SellerSKU, Marketplace, Zeitbezug |
| Amazon-Steuercodes | `dbo.pf_amazon_Steuercodes` | `kSteuercode`, `cName` | Zuordnung zur Artikel-/Plattformsteuer |
| Sonderpreise | `dbo.tArtikelSonderpreis` | `kArtikel`, `nAktiv`, `dStart`, `dEnde` | Preiszeilen und Kundengruppe |
| Auftragswährung | `Verkauf.tAuftrag` | `cWaehrung` | Auftragswährung ist **keine** Produktstandardwährung |

Die vom Nutzer gepostete Ausgabe war eine `TOP 250`-Metadatenliste. Weitere Tabellen und Spalten dürfen erst nach vollständiger SQL-Quellenprüfung als verfügbar gelten. Metadaten wie Personennamen, Lieferantenkontakte, Zugangs- und Buchhaltungsdaten werden nicht blind für alle Artikel veröffentlicht.

## Preis- und Kontentrennung

Für Referenz-EAN `3474630337398`, JTL Artikel-ID 1428, wurden in HeidiSQL **direkt gelesen**:
- `dbo.tArtikel.fVKNetto = 11.76470588`
- `dbo.tArtikel.fAmazonVK = 14`
- `dbo.tArtikel.fEbayPreis = 11`
- `dbo.tArtikel.kLieferStatus = 1`, `kSteuerklasse = 1`, `fMassMenge = 90`.

**Nicht daraus folgern:** Brutto-VK sei original 14,00 €, Amazon habe einen aktiven Angebotspreis 14,00 €, eBay habe einen aktiven Angebotspreis 11,00 € oder die Währung/Steuerinklusion sei für alle Konten bewiesen.

Neue Anzeige führt unabhängig:
1. JTL Netto original; JTL Brutto nur mit `HW24_VKBrutto` **und** `HW24_VKBruttoQuelle`.
2. Amazon JTL Artikelstammwert ohne behaupteten Live-Offer-Status.
3. eBay JTL Artikelstammwert **ohne Kontenzuordnung**.
4. eBay `www.serverler24.shop`: eigener Platzhalter, Preis **nicht verfügbar**.
5. eBay `friseurbedarf24.shop`: eigener Platzhalter, Preis **nicht verfügbar**.
6. Shopify `HAIRWORLD24` und `Boosterwelt24`: getrennte Kanalansichten, noch ohne belegten Preis aus diesem JTL-Endpunkt.

Die eBay-Kontobezeichnungen stammen vom Betreiber, nicht aus einer bestätigten JTL-eazyAuction-Konto-ID. Für die spätere Produktdatenplattform muss jede Beobachtung mit `source_system`, `channel`, `account_id`, `marketplace`, `offer_id`, `sku`, `currency`, `net/gross/unknown`, `timestamp`, `verification_status` abgelegt werden. Ein Angebot kann mehrere Beobachtungen und ein Artikel mehrere Angebote pro Konto haben.

## Sicherheit und Rahmen für die Framework-Migration

- Read-only HTTP GET und vorbereitete SQL-SELECTs, HTTP-Basic-/HTTPS-Kontrolle, Artikel-ID **und** EAN müssen dem Snapshot entsprechen.
- Geheimnisse/Token werden niemals aus einem optionalen Datenfeld ausgegeben; lange Anzeigezeilen werden begrenzt. Alle Anzeigeinhalte werden im Browser nur per `textContent` ausgegeben.
- `hw24_jtl_articles.raw_json` bleibt die revisionsfähige Rohquelle. Im Framework kein kosten- oder speicherintensives Duplikat sämtlicher Artikelfelder je Synchronisationslauf anlegen, bevor Datenvolumen/Retention fachlich geprüft sind.
- Für Filament/Laravel zunächst HTTPS-GET-Leseadapter zur vorhandenen API. Referenzkataloge können später in einer isolierten Plattformdatenbank als verifizierte, versionierte Quellregister aufgenommen werden.
- **Nicht automatisch** `deploy/staging`, die aktive Plesk-Test-/Live-Domain oder die Windows-Sync-Aufgabe ersetzen. GitHub-Tests beweisen Syntax und Verträge, keinen Browser-/MSSQL-Echtbetrieb.
