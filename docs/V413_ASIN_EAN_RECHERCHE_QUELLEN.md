# HW24 v4.13 TEST · Externe EAN-Recherche und ASIN-Quellen

Datum: 17.11.2026. Branch: \`improve/encoding-dashboard-sync-20261115\`.
**Nur lokaler Artikelentwurf, keine automatische JTL-Buchung.**

## Beispiel EAN 3474630660540

Die öffentlich auffindbaren Produktseiten von Friseurversand und
shöpping.at ordnen die EAN dem Produkt
**L'Oréal Professionnel INOA Fundamental 5.3 / Hellbraun Gold / 60 ml**
zu. Das ermöglicht eine Kandidatenbildung für die Stammdaten,
belegt aber **keine** bestimmte Amazon-ASIN.

Quellen:
- https://www.friseurversand.com/inoa-loreal-farbe-5-3-hellbraun-gold-goldgrundton
- https://www.shoepping.at/p/haarcolorationen/loreal-inoa-fundamental-53-hellbraun-go-m0000MDP05

Die direkte Seite https://ean-finder.com/codes/3474630660540
ließ sich zum Entwicklungszeitpunkt nicht zuverlässig maschinell
auslesen. Die Website bietet außerdem eine öffentliche
EAN/UPC/ASIN-Recherche:
https://ean-finder.com/tools/amazon-research
Eine stabile und für unsere Verwendung lizenzierte API von
\`ean-finder.com\` wurde nicht nachgewiesen.
**Es gibt keinen automatischen Web-Scraper für diese Seite.**

## Bereits im Testbranch implementiert

1. Ein EAN-, HAN- oder Artikelnummer-Scan startet wie bisher JTL
   und nach Auflösung der EAN die Historienabfrage.
2. \`api/jtl.php\` sammelt aus dem **eindeutig identifizierten
   JTL-Artikel** die bisherigen ASINs:
   - \`hw24_jtl_articles.asin\` (JTL-Artikelstamm)
   - \`related_sources.artikel_asin.rows[].cAsin\`
   - \`related_sources.amazon_angebote.rows[].asin\`
     inklusive Seller-SKU/Plattform als Herkunftskontext.
   Mehrfachkandidaten bleiben ausdrücklich unterscheidbar.
3. Der Browser ergänzt ausschließlich überprüfbare ASIN-Kandidaten
   aus der Historie, wenn dort die ASIN-Spalte tatsächlich
   nachgewiesen und die gesuchte GTIN gültig ist.
4. In der **Artikelpflege** befindet sich die eingeklappte
   Ansicht "ASIN und ergänzende EAN-Produktrecherche".
   Kandidaten besitzen eine Quelle und einen Knopf
   **"ASIN nach Prüfung übernehmen"**.
5. Eine bestehende abweichende ASIN wird **nicht** überschrieben;
   die Benutzerbestätigung schreibt ausschließlich in den
   lokalen Prüfentwurf und den späteren Ameise-Prüfexport.
6. EAN Finder und dessen Amazon-Recherche können durch
   optionale, sichere Links geöffnet werden. Es erfolgt
   **keine automatisierte Extraktion oder Veröffentlichung
   fremder Produkttexte/Bilder**.

Die neuen JTL-Zusatzquellen müssen weiterhin auf Windows
freigeschaltet und synchronisiert werden, sonst können
\`artikel_asin\` und \`amazon_angebote\` fehlen.
Der vorhandene Artikelstamm-ASIN bleibt nutzbar.

## Für zuverlässige vollautomatische ASIN-Auflösung

**Empfohlen:** Nach Autorisierung des Amazon-Seller-Accounts
und Freigabe einer passenden App die offizielle Amazon
Selling Partner API (Catalog Items) als serverseitigen
Provider anbinden.

Amazon beschreibt das Suchverfahren:
https://developer-docs.amazon.com/sp-api/lang-en_US/docs/search-catalog-items

Ein und dieselbe EAN kann mehrere ASINs liefern. Deshalb
muss der automatische Trefferabgleich mindestens folgende
Punkte verwenden: EAN, Marke, Produktlinie, Nuance,
Inhalt/Gebinde, Marketplace, Variantenbezug und Händler-SKU.
Ein eindeutiger Treffer kann als Vorschlag erscheinen;
Widersprüche, Varianten oder Mehrfachtreffer bleiben
manuellen Prüfungen vorbehalten.

Alternativ kommerzielle/lizenzierte Anbieter prüfen
(z. B. EAN-Such-API für Produktmetadaten oder EAN-zu-ASIN
API), aber nicht mit einem unbestätigten Scraper beginnen.
Bei API-Integration gehören Zugangsdaten in die private
Plesk-Konfiguration außerhalb des Webroots, mit
Rate-Limits, API-Fehlerbehandlung, lokalem Cache und
Quellen-/Abrufzeitprotokoll. Keine Tokens ins GitHub-Repo.

Die zugelassenen Feldgruppen sollen abhängig von Quelle
und Nutzungsrechten sein:
- Stammdaten (Marke, Produktlinie, Nuance, Inhalt):
  nur nach EAN-/Produktabgleich
- Produkttexte/Bilder: nicht ohne Rechte kopieren
- Amazon-ASIN: nur Marketplace-/Varianten-zugeordneter
  Produktdatensatz, nicht bloß exakte EAN
- EK, Lagerbestand, Steuerklasse: **niemals** aus
  externen Webseiten in JTL zurückschreiben.

## SQL13 noch nicht abgeschlossen

Vom Nutzer wurde nur Ergebnis 1 mit den Tabellennamen
für Lager-/Buchungs-/Bewegungshistorie geschickt.
Ergebnis 2 mit den **Spaltennamen**, darunter Artikel-,
Lagerfach- und Datumsrelationen, fehlt noch. Aus der
Liste lässt sich kein belastbares letztes Einlagerungsdatum
pro Fach ableiten.
Datei: \`sql/JTL_13_WMS_BEWEGUNGSHISTORIE_METADATEN_LESEND.sql\`.

## Abnahme vor Livegang

- API-Lookup eines Bestandsartikels liefert \`asin_candidates\`
  mit sauberer Herkunft und ohne doppelte ASIN.
- Artikel ohne ASIN zeigt Kandidaten nur im Prüfbereich.
- Mehrere ASINs bleiben wählbar, keine automatische Zuordnung.
- Bestehende abweichende ASIN wird nicht überschrieben.
- Historische EAN-Treffer mit Header \`ASIN\` dürfen nur als
  prüfbare Kandidaten erscheinen.
- Null-/fehlende JTL-Quellen melden keine erfundene ASIN.
- EAN-Finder-Link verweist auf die gescannte EAN, keine
  Web-Anmeldedaten oder Fremdtexte werden an HW24 übertragen.
- Test: iPhone, Plesk-Protokolle, PHP-Syntax, Ameise-
  CSV-Prüfprofil. Erst danach PR #1 freigeben.

**Status:** GitHub-Code geschrieben. Noch keine autorisierte
Amazon-Katalog-API angebunden, kein Nachweis einer ASIN
für 3474630660540 und keine Browser-End-to-End-Abnahme.
