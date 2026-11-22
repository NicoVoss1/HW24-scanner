# HW24 Artikelmanager v4.15 TEST – Scan, Ampel, Übernehmen, nächste EAN, Ameise

Stand: 17.11.2026. Entwicklungsbranch: \`improve/encoding-dashboard-sync-20261115\`.
Das ist Code in GitHub, **noch keine produktiv getestete Windows-Synchronisation**.

## Hauptworkflow

Die zwei Hauptbereiche bleiben:

1. **Artikelpflege**: EAN, Artikelnummer oder HAN erfassen; frischen JTL-Spiegel
   und historischen Excel-Import prüfen; nur fehlende oder autorisierte
   Änderungen ergänzen. Relevante Felder erscheinen in einer Ampelprüfung.
   Button **„Artikel übernehmen · weiter scannen“** legt die geprüfte
   Position im lokalen Scan-Stapel ab und fokussiert das Eingabefeld.
2. **Lagerplatzübersicht**: ausschließlich LESEN, keine Artikelpflege,
   keine Inventur- oder WMS-Buchung. Fach, Menge, Lagerort,
   Sperrhinweise und (später) letzte verifizierte Lagerbewegung.

**Vorbereitete Scan-Liste:** Bis 3000 verschiedene Artikelnummern pro Browser-Sitzung,
vorheriges Exportieren bei vollem Browserspeicher erforderlich.
Wiederholte Scans derselben Artikelnummer erzeugen nur eine Ameise-Zeile,
aber die Zahl der Scans wird angezeigt. Das ist KEINE Warenbuchung.

**CSV-Export:** Nur Felder, die in der gewählten JTL-Ameise-Vorlage existieren
und einen geprüften/erlaubten Wert besitzen. Bei Neuanlagen müssen
Artikelnummer, Artikelname, GTIN, Hersteller, Warengruppe und
Steuerklasse vorliegen. Bei bestehenden Artikeln werden nur
**bestätigte geänderte Werte** zur Ameise vorgemerkt.
Bei unverändertem Bestandsartikel erfolgt kein unnötiger Updateimport.
Historische Ergänzungen müssen vor Export bestätigt werden.
Die CSV ist eine **Prüfdatei**, kein ausgeführter Import.
Bei gemischten Neuanlage-/Update-Dateien in JTL-Ameise zwingend
„Leere Felder ignorieren“, Backup, Importprofil und Testlauf prüfen.

## Quellen- und Ampellogik

- Grün: eindeutiger JTL-Stammdatenwert oder bewusst manuell geprüfter Wert.
- Gelb: vorhandener, aber ungeprüfter / abgeleiteter Wert,
  fehlender optionaler Kanalwert (zum Beispiel ASIN), unvollständige
  Zusatzquelle.
- Rot: fehlender Pflichtwert, widersprüchlicher JTL-Abgleich,
  unaufgelöste Warengruppen-ID oder veralteter/unvollständiger Spiegel.

Keine ASIN oder Kosten aus anderen Produktseiten erfinden.

## Haarfarben versus Haarstyling

Für **bestehende** JTL-Artikel ist die Beziehung
\`tArtikel.kWarengruppe -> dbo.tWarengruppe.kWarengruppe -> cName\`
die **einzige autoritative JTL-Warengruppe**. Ähnliche
Artikelnamen, alte CSVs und Shopify-Produkttypen dürfen den JTL-
Klarnamen nicht ersetzen. Die Quelle wird serverseitig in
\`HW24_WarengruppeName\` und \`warengruppe_name\` abgebildet.

Der alte Qualitätsassistent schlägt keine Warengruppe mehr aus
ähnlichen Produktnamen oder Excel-Historie vor und vergibt nicht
mehr allein aufgrund eines gepflegt wirkenden CSV-Eintrags Grün.
In der Dropdown-Auswahl von Hand gewählte Warengruppen sind
**manuelle Angaben**, keine unabhängig bestätigten JTL-Stammdaten.
Ein aktueller JTL-Artikel mit Gruppe Haarfarben wird blockiert,
wenn der Bearbeitungsentwurf Haarstyling behauptet.

## JTL-Feldgruppen

Stand der angebundenen bzw. vorbereiteten Daten, nicht mit
Live-Abnahme verwechseln:

| Feldgruppe | Quelle | Status |
| --- | --- | --- |
| Artikelnummer / EAN / HAN / ASIN | dbo.tArtikel, JTL-Mirror | Bestandteil der Basis-Synchronisation |
| Artikelname, Kurzbeschreibung, Beschreibung, SEO | dbo.tArtikelBeschreibung | Bestandteil der Basis-Synchronisation |
| Herstellername | dbo.tHersteller.cName | Im Windows-Sync-Code, Live-Lauf prüfen |
| Warengruppenname | dbo.tWarengruppe.cName | Im Windows-Sync-Code, Live-Lauf prüfen |
| Artikelkosten | Artikel.tArtikelkosten.fArtikelkosten | Verifiziertes optionales SQL-Mapping, Windows-Aktivierung prüfen |
| Steuerklasse | dbo.tSteuerklasse.cName | Verifiziertes optionales SQL-Mapping |
| USt-Satz DE | dbo.tSteuersatz, Steuerzone, Firma und Zielland | Opt-in, **kein** globaler 19-%-Standard |
| Versandklasse | dbo.tVersandklasse.cName | Optionales SQL-Mapping |
| Mengeneinheiten | dbo.tMassEinheit | Optionales SQL-Mapping |
| Lieferanten-EK | dbo.tliefartikel, dbo.teklieferantpreise | Mehrfachwerte, nur Quellenanzeige bis eindeutig |
| Amazon-Angebote/ASINs | pf_amazon_angebot / mapping, Artikel.tArtikelAsin | Optional; nicht jede EAN hat nur eine ASIN |
| Kategorien | tkategorieartikel + tKategorieSprache | Kategorie-ID belegt; Klarname je Sprache/Shop noch nicht vollständig gelöst |
| Bestand pro Lagerfach | dbo.vWarenlagerPlatzArtikel + tWarenLagerPlatz | Opt-in \`include_wms_bins\`, Windows-Aktivierung nötig |
| WMS-Bewegungszeitpunkt | SQL13 Spaltenzuordnung | Noch nicht belegt; kein Inventurdatum als Einlagerung ausgeben |

Die bisherige JTL-Ameise-Exportvorlage \`Haarfarben · Vollständige Anlage\`
enthält über 40 bekannte Importfeldbezeichnungen aus dem
bereits vorhandenen JTL-Exportprofil. Es ist KEINE
Garantie, dass alle 872 bekannten Exportspalten direkt
aus der aktuellen read-only SQL-Spiegelung gefüllt werden.
Private Nutzer-Vorlagen werden nicht überschrieben.

## Windows-Synchronisierung: Entscheidend für echte Klarnamen und Fachmengen

GitHub und Plesk deployen **keine** Windows-PS1-Datei.
Die tatsächliche Sync-Aufgabe benötigt:

- \`sync/HW24_JTL_Synchronisieren.ps1\`
- \`sync/HW24_JTL_Zusatzquellen.ps1\`
- \`sync/HW24_JTL_SteuerWMS.ps1\`

Der Windows-Benutzer braucht nur SELECT-Rechte auf die
bestätigten Tabellen. Der bestehende HTTPS-Empfänger
und die privaten DPAPI-Zugangsdaten bleiben unverändert.

Eine lokale \`jtl-related-sources.json\` muss im selben Verzeichnis
wie die private \`jtl-sync.json\` liegen oder von deren
\`related_sources_file\` referenziert werden. Beispielbasis:
\`sync/jtl-related-sources.verified.example.json\`.

Für die kontrollierte WMS-Abnahme muss
\`"include_wms_bins": true\` gesetzt werden, erst nach Backup
des letzten Snapshotstands und angehaltener geplanter 15-Minuten-Aufgabe.
Für DE-Steuerquellen analog \`include_tax_de\`.
**Die Beispielkonfiguration in GitHub bleibt deaktiviert.**

Kontrollartikel:
EAN 4015600040482, JTL-Artikel-ID 2594,
Hersteller Wella, Warengruppe Haarfarben,
Warenlager 4, Fach R2-RF-F13-H (Fach-ID 66),
Stückzahl 0, nicht gesperrt. Steuerkontext:
Firma 1, DE, Steuerzone 3, fSteuersatz=19 %.

Der erste echte Lauf muss die Quelle \`related_sources.wms_lagerplaetze\`
und \`warengruppe_name='Haarfarben'\` im API-Ergebnis liefern.
Fehlen die Werte nach erfolgreicher Website-Bereitstellung,
liegt es an Windows-Sync/Schema/SELECT-Rechten/Snapshot,
nicht an der Auswahlliste im Browser.

## Externe APIs zur Produktfindung

**Beste nächste API für ASINs:** Amazon SP-API Catalog Items
\`searchCatalogItems\` (EAN-Gruppen bis zu 20 Kennungen
je Anfrage möglich). Die Product Type Definitions API
gibt kategoriespezifische Amazon-Attributregeln.
Links:
- https://developer-docs.amazon.com/sp-api/lang-en_EN/docs/catalog-items-api
- https://developer-docs.amazon.com/sp-api/lang-en_EN/docs/sp-api-registration-overview
- https://developer-docs.amazon.com/sp-api/lang-en_EN/docs/retrieve-a-product-type-definition

**Schritte zur Anbindung**
1. Amazon-Seller-Central mit professionellem Verkäuferkonto;
   Berechtigung zur Entwicklerregistrierung prüfen.
2. Privaten SP-API-Entwickler für den eigenen Verkäuferaccount
   beantragen, passende Produktkatalog-/Listings-Rollen.
3. Private App registrieren und für den eigenen Seller-Account
   autorisieren; LWA-Zugang und Refresh Token erhalten.
4. LWA-Client-ID, Secret und Refresh Token **nur serverseitig**
   in privater Plesk-Konfiguration außerhalb des Webroots
   verwahren. Keine Tokens/Passwörter in GitHub, JS oder CSV.
5. Backend-Worker implementieren, der EANs gesammelt,
   gedrosselt, gecacht und nachvollziehbar gegen den deutschen
   Amazon-Marketplace abfragt. Anhand von
   GTIN/Hersteller/Produktlinie/Nuance/Inhalt Varianten prüfen.
6. Nur eindeutig zugeordnete ASINs als Quellenkandidaten im
   Artikelmanager anbieten; bei Abweichungen manuelle Freigabe.
   Artikelbilder/-texte nur bei entsprechenden Nutzungsrechten.

**Fachliche Primärquelle für Haarfarben:** offizielle Hersteller-
oder Lieferantendaten, falls mit Nutzungslizenz erhältlich.
**GS1** für GTIN/Marke/Grunddaten, soweit Datensätze vorhanden.
EAN Finder als sekundärer Prüf-Link, nicht als unbestätigter
Crawler oder Hauptquelle.

Auch mit API müssen eBay/Shopify/Kaufland-Kategorieanforderungen
pro Kanal gesondert auf ihren offiziellen Schnittstellen
und beim JTL-Plattformexport geprüft werden.

## Abnahme v4.15

- Testdomain über Plesk Git Pull/Deploy aktualisieren;
  **v4.15 TEST** prüfen.
- EAN mit frischem JTL-Snapshot scannen.
- Bestehender Wella-Artikel zeigt JTL-Warengruppe Haarfarben.
- Einspeisung \`Haarstyling\` bei eindeutigem Haarfarben-
  Artikel führt zu ROT statt stiller Übernahme.
- Neue EAN liefert Rot bei fehlenden Pflichtfeldern.
- Beschreibungen bleiben erhalten, wenn manuell bearbeitet.
- Abgeschlossener Scan kann genau einmal übernommen werden;
  wiederholter Scan gleicher Artikelnummer führt zu
  **einer** Importzeile (mit internem Scan-Zähler).
- Mehrere EANs zur Warteschlange hinzufügen.
- Batch-CSV als Datei herunterladen, Spalten und Dezimalformat
  prüfen, JTL-Ameise **Testlauf** mit einem kleinen Batch.
- In Lagerplatzübersicht ist kein Bearbeitungs-/Exportbutton.
- iPhone-Kamera und physischen Scanner separat testen.

**Getestet:** JavaScript-Syntax und isolierte Statuslogik
inklusive JTL-Gruppe Haarfarben/Haarstyling-Kollision,
nicht die echte API- oder Windows-/Plesk-Ausführung.
Keine Anlage von tausenden Artikeln in JTL durchgeführt.
