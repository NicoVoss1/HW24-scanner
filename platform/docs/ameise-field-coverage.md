# HW24 – Ameise-Feldabdeckung und Herkunft (26.11.2026)

Referenzen: FB24_JTL_Artikelstamm_REFERENZ_NICHT_IMPORTIEREN.csv (historischer Referenzexport, ausdrücklich kein Import) und HW24_Scan_und_Ameise_2000_Artikel.xlsx (Starterpaket 23.11.2026, Feldmapping). Original ALLE_JTL-Export-Artikelstammdaten-11112026.csv in dieser Sitzung nicht direkt abrufbar. Die 41 aktuellen ScanResearch::FIELDS sind der implementierte Prüfexportvertrag, nicht sämtliche Ameise-Felder.

| Feld / Gruppe | Belegquelle | Behandlung |
|---|---|---|
| GTIN | Scan, Prüfziffer, eindeutige aktuelle Wawi-/Katalogprüfung | Nie durch Vorschlag ersetzen |
| Artikelnummer | JTL oder bestätigter Nummernkreis | Für NEU erst nach geklärtem Nummernkreis; UPDATE über JTL-Artikelnummer |
| HAN, ASIN, Artikelname, Hersteller | eindeutiges Produkt im Archiv, geprüfte Herstellerzuordnung | Fehlende Werte mit Herkunft |
| Warengruppe, Kategorie 1–3 | reale JTL-Vorlage / Produktregeln | Keine erfundenen JTL-Gruppen |
| Inhalt/Menge, Maßeinheit | eindeutige Titelangabe / explizites item_volume | ml- und Literumrechnung nur mit expliziter Einheit; Konflikt blockiert |
| Verkaufseinheit | tatsächliche JTL-Referenz | eigenes Feld, nicht gleich Inhaltsmaßeinheit; noch nicht im 41-Felder-Vertrag |
| Grundpreis ausweisen, GP-Bezugsmenge, Einheit Bezugsmenge | JTL und fachliche Grundpreispflicht | getrennt prüfen; keine unkritische Übernahme von historischen 0-Werten |
| Steuerklasse, Lieferstatus, Bestandsführung aktiv, Überverkäufe möglich, Stückzahl teilbar | gekennzeichnete Vorlagen, einstimmig | vorhandene gemeinsame Regel |
| Artikeltyp, Währung, Versandklasse, zusätzliche Bearbeitungszeit, Beschaffungszeit | verifizierte JTL-Referenz und klartextgemappte Quellwerte | nächste Erweiterung der automatischen Standards; keine frei erfundenen Werte |
| Artikel aktiv, Top-Artikel, Neu im Sortiment | freigegebene betriebliche Richtlinie | keine ungeprüfte Marketing-/Kanalaktivierung |
| Artikelgewicht, Versandgewicht | eindeutige Herstellerangabe, Messung oder geprüfte spezifische Quellen | keine Umrechnung von ml in kg; Versandgewicht separat |
| Herkunftsland | belegte Produkt-/Herstellerangabe | keine Annahme aus Herstelleradresse |
| Kosten, EK, Lieferant, Lieferantenartikelnummer | Einkauf/Rechnung/Lieferant | keine Ableitung aus Amazon-Marktpreis oder anderem Artikel |
| Netto-VK, Brutto-VK | bestätigte eigene Preisregel/Kalkulation | Marktpreis nur Entscheidungsgrundlage |
| Kurzbeschreibung, Beschreibung, SEO, URL-Pfad | eigener Produktname, verifizierte Produktfakten, vorhandene freigegebene KI-Texte | Vorschläge, keine unbelegten Aussagen |
| Physische Scanmenge, WMS-Lagerplatz, MHD, Charge | Erfassung / WMS | niemals in Stammdatenexport als Lagerbuchung |
| Vaterartikel, Identifizierungsspalte, Shopify-/Amazon-Kanal aktiv | JTL/Vertriebspolitik | separat prüfen, nicht automatisch publishen |

Offizielle JTL-Dokumentation: https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikeldaten-importieren/ (Standardwerte gelten bei Neuanlage und werden bei Feldzuordnung bzw. Aktualisierung nicht wie neue Standardwerte angewandt); https://guide.jtl-software.com/jtl-wawi/artikel/artikelstammdaten-allgemein/ (Verkaufseinheit, Inhalt/Grundpreis, Gewicht getrennt). Gespeicherte Importvorlage, Pflichtfelder einschließlich eigener Preisregel und Testlauf vor echter Anlage weiterhin erforderlich.
