# HW24 Business Hub – Recherche, Artikelbearbeitung und Ameise-Export
Stand: 25.11.2026. Fachliche Anforderungen aus Betreiberentscheidung. Dies ist ein Umsetzungskonzept, kein Nachweis der Implementierung oder eines Deployments.

## Verbindliche Architektur
- Bestehendes Laravel 13 / Filament 5, bestehende Legacy-APIs und gemeinsame HW24-MariaDB wiederverwenden. JTL-Wawi 2.2.2.0 / eazybusiness bleibt führend; nur freigegebene lesende SQL-Quellen, keine direkten JTL-Schreiboperationen.
- Ein gemeinsamer Unternehmens-Workspace, einmalige Produkt- und Recherchebelege, individuelle Rechte und Audit. JTL kArtikel/Artikelnummer als interne Identität; GTIN als Such-/Recherche-Dedup-Schlüssel, ASIN und Packung getrennt verifizieren. Eindeutige Zuordnung vor externer Recherche bei Namenssuche; keine First-Result-Annahme.
- TEST nutzt teilweise dieselbe Legacy-LIVE-API und MariaDB. Isolierte TEST-Jobs, separate Freigabe für Schema- und Legacy-API-Änderungen. Keine Migration, Live-Änderung, JTL/WMS-Buchung oder bezahlte KI ohne entsprechende Freigabe.

## Ablauf
1. EAN-Scan, bestätigte Artikelnummer/Namensauswahl oder CSV-Zeile erzeugt einen persistenten Rechercheauftrag; Scan bleibt unabhängig vom Provider verfügbar. Mehrfachscan zählt physische Menge, erzeugt keinen doppelten frischen Rechercheauftrag.
2. JTL-Spiegel, vorhandene kuratierte/Excel-Historie, Amazon-Katalog-/Preisarchiv, OpenAI-Archiv automatisch lesen. Historische Daten mit Originalzeitpunkt und Quellenstatus anzeigen; unbekannte Zeit niemals erfinden.
3. Amazon-Katalog automatisch aktualisieren, wenn kein ausreichend frischer verifizierter Snapshot vorhanden ist. Eindeutig passendes Produkt/Packung vorausgesetzt: automatische SP-API Preisabfrage pro Marketplace/ASIN/Zustand; Preisarchiv und gespeicherte Händler-Klarnamen automatisch verknüpfen. Unbekannte SellerId bleibt sichtbar und unbestätigt; keine Identität erfinden. Amazon-Händler sind nicht automatisch JTL-Lieferanten.
4. Fehlende Produktfelder aus geeigneten belegten Quellen und freigegebenen deterministischen Regeln als Entwurf ergänzen. Bestehende bestätigte Werte nicht überschreiben. Neue OpenAI-Abfragen ausschließlich einzeln/markierte Auswahl/alle berechtigten fehlenden Fälle mit expliziter Kostenfreigabe, Budget und dedup; Archive kostenlos lesen. Kein bezahlter Retry ohne wirksame Idempotenz/Kostenkontrolle.
5. Zentrale Bearbeitung: Altwert, historischer Wert, recherchierter Vorschlag, Quelle/Zeit, Status, editierbarer Zielwert. Alle/fehlend/abweichend/mehrdeutig filtern; Einzel- und Sammelbearbeitung; kontrollierte Übernahme, Revision/Optimistic Lock, Freigabe je Artikel und Vorlagengruppe. Nur bestätigte Identität, validierte Pflichtfelder und zulässige Werte exportieren.

## Warteschlange und Preisarchiv
Vorhandene Katalogsnapshots, lookup logs, seller names und AI-Archive erhalten. Derzeitige ScanResearch-Aufrufe sind explizit und browsergesteuert; Pricing liefert Live-Offers, aber kein bestätigtes persistentes Pricing-Archiv. Additive Migration in bestehender HW24-MariaDB nach Schema-Inventur und Backup, nicht in JTL. Vorgeschlagene Namen (nach Kollisionsprüfung): hw24_research_jobs, hw24_amazon_price_snapshots, hw24_amazon_price_offers. Gemeinsame Queue mit idempotenter Eindeutigkeit pro Provider/Marketplace/Identität/Abfragetyp und kontrolliertem Refresh, Lease, Retry-After/backoff, globale Rate Limits, Fehlerstatus, UTC-Zeit, Audit. Keine Provider-Calls beim bloßen Rendern; nur bestätigte Erfassung/Auswahl oder autorisierter Refresh. Keine pauschale TTL ohne Rate-/Kosten-/Policy-Prüfung.
Snapshot: Marketplace, ASIN, Zustand, Währung, beobachtet_at_utc, HTTP/Provider-Status, Buy Box, niedrigster Gesamtpreis soweit belegt, Angebotsanzahl, Quellen-/Request-Referenz. Offer: SellerId, verifizierter Anzeigename/Referenz, Artikelpreis, Versand, Gesamtpreis nur bei gleicher Währung und vorhandenen Bestandteilen, FBA/FBM soweit belegt, Versandzeit, Zustand. Keine historischen Preise aus Katalogsnapshots rückrechnen. Cache-Zugriff nicht als frische Beobachtung speichern. Detaillierte Gegenüberstellung alt/neu mit identischem Kontext; fehlende Werte nicht als 0. Eigene VK und JTL-Preis separat, keine automatische Übernahme eines Marktpreises.
Vor Rohpayload-/Bildarchivierung Amazon-Vertrags-/Aufbewahrungs-/Nutzungsrechte prüfen, Datenminimierung und konfigurierbare Löschregeln; keine Kundendaten oder Secrets in GitHub.

## JTL-Klartext und Kategorien
Vorhandene 41 Felder in ScanResearch erweitern, bisherigen umfassenden JTL-Feldkatalog und Originalexporte wiederverwenden, nicht blind sämtliche Exportspalten als importierbar behandeln. Feldregistry: deutsches Label, technischer Schlüssel/ID, Quelltabelle/Spalte, Datentyp, Einheit, erlaubte Referenzwerte, Importziel/-spalte, Quelle, Schreibbarkeit, Pflichtregel, Sprache/Kanal, Revision. Dropdowns aus aktuellen JTL-Referenzdaten, Klartext vor ID. Beispiele aus beobachteten Referenzwerten 23.11.2026: Lieferstatus 1 = 2-4 Werktage; Steuerklasse 1 = Normaler Steuersatz, 2 = Ermäßigter Steuersatz; Maßeinheit 16 = ml, 21 = Stk. Artikeltyp Standard und Währung EUR für geeignete Neuanlagen nur nach bestätigter Zuordnung. Keine geratenen IDs; 0/NULL unterscheiden. Füllmenge 60 bzw. 90 als Zahl, Dezimalkomma in freigegebener CSV-Konvention; keine unbelegte Skalierung.
Betreiber-Kategorieregel: Friseurbedarf > Haarfarben für Haarfarbe; Friseurbedarf > Hairstyling für Haarspray; Friseurbedarf > Haarpflege für Shampoo. Warengruppe ist unabhängig vom Kategoriepfad; echte JTL-Warengruppe anhand Referenzliste zuordnen, niemals Haarfarben pauschal Hairstyling. Bei unklarem Produkttyp Vorschlag sperren. Existierende Artikelkategorien nicht ungeprüft ändern.
JTL-Lieferantendaten: Lieferantenname/Firmierung, Lieferantennummer, Lieferantenartikelnummer, Lieferantenartikelname, EK/Währung, Mindestmenge, Staffel, Lieferzeit, Lieferantenbestand, Standardlieferant, Dropship-Fähigkeit soweit Quellen nachgewiesen. Bestell-/Wareneingangshistorie, offene Mengen und Dropshipping-Vorgänge lesend je verknüpftem Artikel; keine Kunden-/Adressdaten für Artikelpflege übernehmen. Vor SQL-Integration vorhandene Phase-16/17-Ergebnisse und Bestelltabellen/FKs prüfen; kein blindes JOIN.

## Produktcontent und Kalkulation
SEO-Titel, Meta-Description, Kurz-/Langbeschreibung, URL-Pfad, Bild-Alttexte, Kategorien, Attribute/Merkmale, kanalbezogene Texte und Pflichtfelder aus belegten Produktdaten mittels deterministischer Regeln vorbereiten; KI optional mit Freigabe. Farbe/Farbnummer, Produktserie, Inhalt/Einheit, Hersteller, Produkttyp als Beispiele. Keine erfundenen Inhaltsstoffe, Sicherheits-/GPSR-Angaben, Claims oder Produktbilder. Vorhandene JTL-Bilder zuerst; Hersteller-/Lieferantenbilder mit belegtem Nutzungsrecht, Amazon-Bilder nur soweit Vertrags-/Bildrechte erlauben. Bildreferenz, Reihenfolge, Kanalzuordnung, Rechte und Quelle dokumentieren. Neue Verkaufspreise ausschließlich aus Betreiber-Kalkulation und Freigabe (EK, Gebühren, Versand, Steuern, Marge etc.); brutto/netto und Währung explizit. Verkaufskanalvorschläge aus JTL-Zuordnungen/Regeln, keine automatische Publikation.

## Ameise-Exportcenter
Offizielle Referenzen:
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/uebersicht-ueber-jtl-ameise/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikeldaten-importieren/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/attribute-merkmale-und-eigene-felder-importieren/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikelbilder-mit-jtl-ameise-verknuepfen/
- https://guide.jtl-software.com/jtl-eazyauction/weitere-marktplaetze-anbinden/checkliste-artikelstammdaten-fuer-weitere-verkaufskanaele-vorbereiten/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/amazon-angebote-bearbeiten/
- https://guide.jtl-software.com/jtl-eazyauction/bei-amazon-verkaufen/artikel-mit-amazon-angeboten-verknuepfen/
Interne Vorlagen und tatsächlicher Importtyp unterscheiden. Geplante Reihenfolge:
01 Artikeldaten (NEU und UPDATE separat): Artikelnummer, GTIN, HAN, Name, Hersteller, Warengruppe, Kategorien, Steuerklasse, Grundpreis, Maße/Gewichte, Versandklasse, Lieferstatus, freigegebene Texte/SEO und weitere verfügbare Felder.
02 Lieferantenartikel im Artikeldatenimport: Artikelnummer, Lieferant, Lieferantenartikelnr., EK/Währung, Lieferzeiten etc. Lieferantenstammdaten nur separat bei tatsächlich neuer Lieferantenidentität.
03 Artikelpreise: Artikeldaten/Preise-Bestände Quick Sync nach tatsächlicher Vorlage; kundengruppen- und plattformbezogener Preis, keine Bestandsbuchung.
04 Artikelattribute: Artikelnummer, Attributgruppe/-name/-wert, Sprache/Ausgabeweg soweit unterstützt.
05 Artikelmerkmale: Artikelnummer, Merkmalgruppe/-name/-wert(e).
06 Eigene Felder: Artikelnummer, Feldname/-wert.
07 Bilder: Artikeldaten/Artikelbilder pro Plattform, Bildverknüpfung, Bild-SEO nach echter Importmaske und Nutzungsrecht.
08 Gebinde bei Bedarf.
09 Marktplatz-Angebotszuordnung/zulässige Amazon-/eBay-Angebotsupdates separat nach installierter Wawi/Lister-Version; weitere SCX-Angebote nicht pauschal als Ameise-importierbar behandeln. Keine automatische Anlage/Veröffentlichung. Shopify/Kaufland-Kanalzuordnungen und kundengruppenbezogene Preise anhand tatsächlicher Importmaske/Connector prüfen.
10 WMS-Bestände/Lagerplätze/Vorgabeplätze ausschließlich gesondert freigegeben; keine Mengen aus Recherche ableiten.
Versand/Spedition und SEO zunächst als Teil Artikeldaten bzw. passend zur realen Importmaske, nicht frei erfundener eigener Importtyp. Bestellimporte getrennt von Produktpflege.
Versionierte zentrale Vorlagen je Importtyp/Modus: exakte Spaltenüberschriften, Datentyp/Mapping, Pflichtfelder, Konstanten, Sprache, Kanal, leere Werte (kein Überschreiben), Dateiformat/Trennzeichen/Encoding, Reihenfolge, importierbare Felder und Prüfregeln. Export als ZIP mit nur erforderlichen CSVs, Manifest, Artikel-/Fehleranzahl, Prüfbericht und Anleitung zur passenden gespeicherten Ameise-Vorlage. Neue Artikel zuerst anlegen, danach abhängige Importe über stabile Artikelnummer. UPDATE nur bestätigte Änderungen und keine leeren Überschreibungen. Vorlagen erst als validiert kennzeichnen nach Test in installierter JTL 2.2.2.0; Backup, Worker gemäß JTL-Guide pausieren, Import-Trockenlauf.

## Umsetzungs- und Abnahmereihenfolge
A. Plesk-Diagnose des vorhandenen Bridge-/SP-API-Zugriffs (echtes live vs cache, HTTP, Snapshot, eindeutige ASIN, Pricing). Aktuelle Legacy-API-Version/Schema prüfen. Keine Behauptung, dass CI-Fakes Live beweisen.
B. Additive Preisarchivierung und persistente Queue im isolierten TEST; Backup, Schema-/Rechteprüfung; Regressionen, Cache-Dedup, Rate-Limit, Konflikte, Fehler/Resume, zwei parallele Benutzer und mindestens ein echter freigegebener Provider-Test. Keine bezahlte KI.
C. Automatische Trigger für Scan/CSV/ausgewählte Suche, automatische Historie/JTL/Amazon/AI-Archiv, nachgewiesene Klartext-Händlerzuordnung; Preise und Zeitverlauf.
D. Bearbeitungsübersicht, JTL-Referenz-Dropdowns, korrekt getrennte Kategorie/Warengruppe, Lieferanten-/Bestellspiegel; manueller Edit, Quellenkonflikte, Freigabe und Rechte testen.
E. Belegte Content-/Bild-/SEO-/Attribut-/Merkmal-Entwürfe, KI-Batch mit Kostenvorschau und Freigabe; keine erfundenen Werte.
F. Exportvorlagen anhand vorhandener JTL-Originalexporte und tatsächlicher Ameise-Masken 2.2.2.0 verifizieren; drei Testartikel (Haarfarbe, Shampoo, Haarspray), NEU/UPDATE, Umlaute/führende Nullen/Dezimalkomma, keine Löschung, Import-Trockenlauf und Rückabgleich. Erst dann größere CSV.
G. Jeder Code-Schritt mit Regressionstests, CI, Arbeitsjournal, festem Commit, kontrollierter PuTTY-Bereitstellung und Browserabnahme. LIVE erst Betreiberfreigabe.

Offene Nachweise: aktuelle tatsächliche Server-/Legacy-Version, echte SP-API-Live-Antwort, vollständige Preisarchiv-/Queue-Schemata und Rechte, aktuelle JTL-Referenzwerte und vollständige JTL-Lieferantenverknüpfungen, tatsächliche Ameise-Importmasken/Original-Screenshots, Amazon-Aufbewahrungs-/Bildrechte. Keine Implementierung oder Migration durch dieses Dokument.
