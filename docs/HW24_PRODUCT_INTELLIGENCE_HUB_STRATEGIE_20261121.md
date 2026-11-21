# HW24 Product Intelligence Hub · Architektur & Ausbauplan

**Stand:** 21.11.2026 · Konzeptstatus. Keine Shopify-/JTL-/Amazon-/Social-Schreibfunktion damit freigegeben.

## Vision
Eine quellengeprüfte, mehrsprachige Produktdatenplattform für professionelle Friseurprodukte. HAIRWORLD24 ist zuerst Händler und qualitätsgesicherter Datenverantwortlicher. Ein späteres B2B-/Datenlieferanten-Angebot erfordert gesondert geklärte Datenrechte und Kundenverträge. Wachstum ist Unternehmensziel, keine technische Erfolgsgarantie.

## Bestätigter aktueller technischer Stand
- `deploy/staging` V4.22 mit JTL-Artikel-Snapshot aus dem Windows-SQL-Synchronisator nach MariaDB, Scanliste, Amazon-ASIN-Kandidaten, manueller KI-Prüfung, tokenbasierter Kostenkontrolle und revisionsfähigen HW24-Entwürfen.
- Nutzer meldet am 21.11.2026 das einmalig bewachte **V13-Archiv-Installationsskript erfolgreich beendet**: neue Tabelle `hw24_ai_reviews`, enge bestehende Benutzerrechte; das Archiv zeigte direkt nach Installation 0 Datensätze. Dies ist **Nutzer-Terminalnachweis**, noch kein unabhängiger HTTP-End-to-End-Test der Archivroute. Keine erneute V11/V12/V13-Installation ohne Fehlerbefund.
- GitHub-Testrelease [PR #26](https://github.com/NicoVoss1/HW24-scanner/pull/26) + [PR #27](https://github.com/NicoVoss1/HW24-scanner/pull/27), SHA `6e6e7f7dfd536f4bef42c401321cafe95f855004`; Plesk-Pull/Release dieser Version muss noch über den Browserstatus bestätigt werden.
- An Shopify ist der Händler-Shop HAIRWORLD24 über einen **lesenden** App-Connector erreichbar. Eine kleine Stichprobe zeigte, dass Produktklassifikation/SEO-Overrides Pflegepotenzial haben. **Keine automatischen Shopify-Mutationen ausgeführt.**
- Social-App Metricool liefert aktuell andere Markenumgebungen; eine eigenständige HAIRWORLD24-Markenverknüpfung muss vor dem Veröffentlichen geprüft und gegebenenfalls eingerichtet werden. **Keine Beiträge veröffentlicht.**

## Führende Datenquellen / Verantwortlichkeiten
| Daten | Führende Quelle | KI-Aufgabe | Freigabe |
|---|---|---|---|
| JTL-Artikel-ID / Wawi-Artikelnummer | JTL-Wawi | Treffer/Mappings prüfen | Keine Erzeugung/Änderung direkt im SQL Server |
| Lagerbestand, Lagerplatz, Reservierung, Verfügbarkeit | JTL-Wawi / JTL-WMS und bestehende Synchronisation | Auffälligkeiten melden | Niemals durch Sprachmodell erfinden oder in WMS schreiben |
| GTIN, Marke, Farbnummer, Inhalt, INCI, Warnhinweise | Originalhersteller / lizenzierter Lieferant, validiert gegen vorhandene Artikel und Scan | Widersprüche markieren | Nur belegte Werte übernehmen; bei Warnhinweisen keine KI-Erfindungen |
| Markt-ASIN, Mehrfachpack, Angebot | Amazon SP-API, soweit zulässig | Angebote vom physischen Einzelartikel unterscheiden | Lizenz-/Nutzungsregeln beachten; keine automatische EAN=ASIN-Gleichsetzung |
| Preise, Steuerklasse, Einstand, Marge | Belegte JTL-/Lieferanten-/Kanalwerte | Kalkulation und Warnungen | Preise/Steuern nur nach festen Regeln/Prüfung |
| Öffentliche Shop-Inhalte | genehmigter kanalspezifischer Entwurf | SEO, strukturierte Texte, Übersetzung | Veröffentlichungen erst nach Vorschau und Berechtigung |
| KI-Urteile | unveränderliches `hw24_ai_reviews` + separate Review-Entscheidung | Erklärbare Vorschläge mit Quellenbezug | KI selbst ist nie Tatsachenquelle ohne Beleg |

## Zentrales kanonisches Datenmodell (Ziel, noch nicht als neue DB-Migration umgesetzt)
1. **Product Identity:** interne kanonische Produktkennung, GTIN/EAN, Marke, Hersteller-Artikelnummer, JTL-ID, Farbe, Gebinde, Stückzahl. Einzelartikel, Varianten und Multipacks sind getrennte Einheiten/Angebote; identische EAN alleine reicht nicht für identisches Verkaufsgebinde.
2. **Evidence / Field Provenance:** Feldname, exakter Wert, Quelle/Quellen-ID, Lizenz/Freigabestatus, Abrufzeit, Version, ermittelter Konflikt und Status `unbestätigt / bestätigt / abgelehnt`. KI-Ausgaben bleiben getrennt von den tatsächlichen Herstellerbelegen.
3. **Channel Mapping:** JTL-ID/SKU, Shopify Product-/Variant-GID, Amazon ASIN/SKU, eBay-ID, getrennte Varianten- und Bestandssicht sowie Sync-Direction pro Feld.
4. **Content Library:** freigegebene Titel, SEO-Tags, Beschreibungen, Verpackungsgrößen, mehrsprachige Inhalte, Medienherkunft und Verkaufskanal. HTML/SEO in Shop und Social nicht direkt aus unüberprüfter Amazon-Seite kopieren.
5. **Quality & Audit:** Dubletten, fehlende Werte, Validierungsregeln, Review-ID, Benutzerfreigabe, Version, Rücknahme der letzten Kanaländerung, historischer Snapshot und nachvollziehbare Kosten.
6. **Publishing Outbox:** `preview → approved → queued → applied → verified`; für jede Plattform getrennte Sendejobs mit idempotenten Schlüsseln, Revision/ETag- oder Zeitstempelabgleich und überprüfbarem Rollback. Kein ungeschützter bidirektionaler Vollabgleich.

## Architektur
```text
JTL-Wawi (read-only Windows-Sync) ─┐
JTL-WMS (führend für Lager)         │
Shopify Admin GraphQL (zunächst R) ├──> HW24 Evidence/Identity Hub ──> Rules/AI Review Queue
Amazon SP-API (lizenzkonform)       │          │                       │
eBay / Hersteller-Feeds / CSV      ─┘          └──> Source/Audit Ledger <┘
                                                       │
                            Review Dashboard <─ Draft + Approval
                                                       │
                                  JTL Ameise Testlauf / Shopify Preview /
                                  eBay-Vorschau / eigene Social-Brand-Vorschau
                                                       │
                                        kontrollierte, beobachtete Publikation
```

## Konkrete Phasen, in dieser Reihenfolge
**Phase 0 · KI-Archiv abnehmen:** Auf TEST `GET /api/ai-reviews.php` aufrufen. Erwartet: Authentifizierung und `ok:true`. Danach genau eine vom Nutzer bestätigte neue KI-Prüfung; nach Reload muss Bericht und `review_id` erhalten bleiben. Ohne echten HTTP-Nachweis nur `Nutzer hat Installation ausgeführt` behaupten.

**Phase 1 · Shopify-Qualitätsinventur (nur LESEN):** Für eine begrenzte, repräsentative Artikelstichprobe Shopify Admin GraphQL `id`, `vendor`, `productType`, `status`, `seo`, `descriptionHtml`, Varianten/SKU/Barcode, erlaubte Produkt-Metafields und vorhandene JTL/Shopify-Abbildung gegenüberstellen. Bericht: fehlende JTL-Zuordnung, leere Taxonomie, Variantenkonflikte, SEO-Overrides, Packmengen und Dubletten. Produktdaten oder Shop-Preise keinesfalls unbemerkt ändern.

**Phase 2 · Produkt-Fakten-Engine:** Für die vorhandenen ~1.500 EAN-Scans (nach erfolgreicher CSV-Massenabnahme) belegte Produktfakten/Varianten gewinnen. Regelprüfung zuerst, kostenpflichtige KI nur für unklare/neue Fälle, Queue-Ratenlimits und Berechnung der erwarteten Kosten je 100/1.000 Artikel vor Freigabe. Unbekannte Werte bleiben unbekannt; Quellenbedingungen werden geprüft.

**Phase 3 · Content- und Shopify-Pilot:** Genehmigte Produkttitel, SEO, Produkt-Metafields/Metaobjects, Inhalt, Varianten und Beschreibungsvorschläge für **5 einzelne Pilotartikel**. Produktidentität, Preis und Bestand unangetastet. Vorher/Nachher-Diff, Storefront-Test, JTL-Prioritäten und Rollback. Shopify unterstützt Metafields/Metaobjects und `productSet` / GraphQL-Bulk-Operationen; technische Freigabe nur nach Live-Mapping/Scopes.

**Phase 4 · JTL-Ameise/JTL-WMS:** Zunächst Ameise-Testprofil im Trockenlauf für erlaubte Stammdaten. JTL-/WMS-Bestandsfelder bleiben aus KI-/Shopify-Publikationen ausgenommen. Erst dokumentierte Rückwirkung auf JTL-Connector/WMS/Shopify prüfen, dann feldweise genehmigen. Kein direkter SQL-Server-Writes.

**Phase 5 · Multichannel und Social:** Erst eigenständigen HAIRWORLD24-Brand-/Account-Mappingstatus prüfen. KI erstellt unterschiedliche Entwürfe für Shopify, Amazon/eBay-konforme Listings und eigene Social-Medien, mit Upload-/Freigabezeiten, markenspezifischer Trennung und geltenden Plattform-Regeln. Kein Crossposting auf andere Geschäftsmarken.

**Phase 6 · B2B-Datenservice:** Eigene/ausreichend lizenzierte Produktdaten, standardisierte JSON/CSV/Feed-API, Versions- und Abonnementmodell, vertragliche Nutzungsrechte/Service-Level, EU-Sprachen, Datenqualitätskennzahlen und potenziell OEM-/Herstellerpartnerschaften. **Amazon-SP-API-Inhalte nicht ungeprüft weiterverkaufen oder an Dritte weitergeben.**

## Priorisierte KI-Module
- **Identity Agent:** GTIN/ASIN, Einzelgebinde, Varianten und 2er-/3er-/6er-Pack sicher differenzieren.
- **Catalog Quality Agent:** Wella/Schwarzkopf/Matrix/Goldwell/JTL-Feldnamen und Kategorien gegen belegte Quellen prüfen.
- **Content Agent:** gute Produkttitel, SEO, Beschreibung, Sprachversion und strukturierte Metafields als Entwurf; Beauty-Claims nur mit Beleg.
- **Customer Guidance Agent:** auf verifizierten Daten basierende Produktsuche und Beratung im Shopify-Shop; keine erfundenen Wirkungsversprechen/medizinischen Zusagen.
- **Sales/Price Agent:** nur mit belastbaren Einstandspreisen, Gebühren, Steuern und Versandtarifen Margen/VK-Vorschläge errechnen.
- **Social Distribution Agent:** nur für den gesondert genehmigten HAIRWORLD24-Auftritt, zunächst Redaktionsvorschläge statt Autoposting.
- **Supplier Feed Agent:** strukturierte Hersteller-Feeds unter Lizenzbedingungen, Qualitätsmeldungen, Dubletten-/MHD- und Produkt-Änderungen.

## Geschäftsziele und Messung
Wöchentlich: Anzahl identifizierter Produkte, Anteil verifizierter GTIN/Packungsgröße, valide Hersteller/Warengruppen, SEO-Abdeckung, Produktdatenfehler pro 1.000 Artikel, Zeitersparnis, manuelle Review-Warteschlange, Token- und API-Kosten/Artikel, erfolgreiche Shopify-JTL-Mappings, Conversion/Retourenquote/Marge. **Keine Umsatz-/Marktführerschafts-Garantie**; Vertrieb, Einkauf, Logistik und Kundenservice bleiben eigenständige Erfolgsfaktoren.

## Sicherheits- und Rechtsleitplanken
- Separate API-Projekt-Keys/Budgets für HW24/Boosterwelt24/weitere Projekte. Keine Secrets in GitHub, JavaScript oder Chat.
- Rechtmäßige Produktdaten-/Bildnutzung, Herstellerfreigaben und Amazon-SP-API-Bedingungen bei Wiederveröffentlichung oder B2B-Lizenz; personenbezogene Daten nur bei definiertem Zweck und Berechtigung.
- Offizielle Referenzen: [Shopify ProductSet](https://shopify.dev/docs/api/admin-graphql/latest/objects/product), [Shopify Metafields](https://shopify.dev/docs/apps/build/metafields/manage-metafields), [Shopify Metaobjects](https://shopify.dev/docs/apps/build/metaobjects/manage-metaobjects), [JTL-Ameise Testimport](https://guide.jtl-software.com/jtl-wawi/jtl-ameise/aufbau-der-import-funktion/), [Amazon SP-API Policies](https://developer-docs.amazon/sp-api/lang-en_EN/docs/policies-and-agreements), [OpenAI Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs).
