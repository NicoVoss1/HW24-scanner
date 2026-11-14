# HW24 JTL field catalog and source priority (14 Nov 2026)

## Sources examined directly
- JTL-Export-Artikelstammdaten-12112026.csv: 872 original column headers, 2683 article rows, Windows-1252, semicolon delimiter.
- JTL WAWI Felder aus DB.csv: real SQL Server 2017 columns of dbo.tArtikel (91), dbo.tArtikelBeschreibung (12), dbo.tArtikelAttribut (5) and dbo.tArtikelMerkmal (4).
- Old Excel workbook is stored in hw24_excel_rows / hw24_excel_identifiers on Plesk and is historical, not current JTL.
- Windows SQL Server current 'eazybusiness' previously contained 2689 JTL articles in an earlier separate query. Do not confuse counts at different times.

## Data source priority
1. Fresh, complete SQL Server 2017 mirror of eazybusiness, with timestamp and JTL article ID.
2. Recent user-supplied JTL-Ameise CSV, explicitly labeled local/offline snapshot.
3. Historical Excel original rows and named headers with sheet/row citation.
4. External product-source APIs (NOT YET IMPLEMENTED). Never invent EAN, ASIN, manufacturer, tax, inventory or price.

## Actual column names and behavior
- Kosten = user-requested "actual EK". **NOT equivalent** to Durchschnittlicher Einkaufspreis (netto).
- Durchschnittlicher Einkaufspreis (netto) = separately displayed JTL fEKNetto (GLD). Last EK is fLetzterEK (with dLetzterEK).
- Auf Lager / In Aufträgen / Verfügbar are three DIFFERENT source fields. Never import them as stock adjustments through an Ameise master-data draft.
- Überverkäufe möglich, Lieferstatus, Stückzahl teilbar, Zusätzliche Bearbeitungszeit, Beschaffungszeit automatisch ermitteln, Beschaffungszeit manuell setzen, Versandklasse, Artikel aktiv, Bestandsführung aktiv, Top-Artikel, Warengruppe, Artikeltyp, Neu im Sortiment.
- Anmerkung and the warehouse-specific Lagerkommentar_1 / Lagerkommentar_2 [ZepCh], [ZepCh - WMS-ZepCh].
- Artikel zuletzt bearbeitet am / Artikel zuletzt bearbeitet durch are read-only history. JTL SQL holds kBenutzerLetzteAenderung (ID), not automatically the person's readable name.
- JTL-Wawi: Amazon B2B Netto / Brutto; Amazon B2C Netto / Brutto; eBay Fb24 Netto / Brutto; Neuer Standardpreis für eBay-Angebote.
- Staffelpreise are intentionally excluded from the fast standard workflow.
- ASIN is important for Amazon; require sourced validation, not fabricated auto-fill.
- Warengruppe and Kategorie Ebene 1/2/3 may point to internal classification. Amazon productType/browseNode matching requires verified mapping by product category and external seller requirements.

## Original export distribution (current 14 Nov 2026; 2691 rows)
- 1928 articles under Friseurbedarf; 750 under "1 Amazon-Import-mit-Bestand".
- 710/750 Amazon-import articles lack GTIN; 709 lack manufacturer; 712 have zero Kosten.
- The reference group Friseurbedarf has 99% GTIN/HAN, 99.9% manufacturer, 98.4% short descriptions and 97.5% descriptions.
- **Überverkäufe möglich N in 2690 / Y in 1; mandatory DEFAULT N.** Only a confirmed manual per-article override may set Y.
- Lieferstatus "2-4 Werktage" for 1842 entries, 849 blank.
- Stückzahl teilbar N for most articles.
- Zusätzliche Bearbeitungszeit: 1 for 1442, 0 for 759, 3 for 471, 2 for 19. Use reference value 1 for comparable products; this is category-sensitive.
- Beschaffungszeit manuell setzen: 2 for 1429, 0 for 776, 3 for 468, 1 for 18.
- Versandklasse standard 2691; Artikel aktiv Y 2691; Bestandsführung aktiv Y 2683.
- Warengruppe Haarfarben 2394, Haarpflege 181, Haarstyling 75, Sammelkarten 18.
- Artikeltyp Normaler Artikel 2685; Neu im Sortiment Y 2612.
- Steuerklasse normaler steuersatz / 19.00% all 2691 export rows, subject to per-item correct category and current tax rules.
- Währung EUR 44, otherwise blank.
- ASIN 2415 present / 276 missing. Never invent ASIN.
- Do not overwrite well-populated Friseurbedarf records based on blind global templates.

## JTL import workflow
- An existing JTL article retains its **existing Artikelnummer**, including numbers equal to EAN.
- For a genuinely new article with a fresh verified JTL EAN check, article number may remain blank IF the Ameise option "Artikelnummern automatisch vergeben, falls nicht vorhanden" is configured for the JTL number range. Test on one item first.
- For existing articles import type "Neue Artikel importieren, vorhandene aktualisieren"; identity via Artikelnummer. SKU blank must not be used to update existing articles.
- JTL Ameise default values affect **new articles**; existing articles require explicit mapped CSV columns to update fields.
- Existing prices, categories, images, stock and warehouse records must not be deleted/overwritten by import settings or zero-priced source records.
- End-to-end marketplace activation requires separate channel requirements and synced marketplace connections, not just a successful article-data import.

## Project state
v3.8 adds an exact-column JTL profile and expands the Ameise test-draft CSV. The front-end defaults are local; hw24_profile_defaults in SQL V07 is prepared but NOT yet a live editable server-side settings API.
JTL Windows synchronization and real MariaDB writes are not active. Plesk deployments and DB migrations require authenticated administrator execution.
