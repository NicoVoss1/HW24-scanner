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

## Original export distribution (2683 rows)
- Kosten: 1645 entries at 1,50; 799 at 0,00.
- Überverkäufe möglich: N 2682, Y 1. User now requests Y as default for **new** articles. Never overwrite the N values of existing articles automatically.
- Lieferstatus: "2-4 Werktage" in 1834 nonblank records, 849 blank.
- Stückzahl teilbar N in 2627 records.
- Zusätzliche Bearbeitungszeit: 1 in 1434; 0 in 759; 3 in 471; 2 in 19. User overrides new-item default to 2.
- Beschaffungszeit manuell setzen: 2 in 1428; 0 in 776; 3 in 468; 1 in 11. Default 2 for now, editable to 3.
- Versandklasse: "standard" in all 2683.
- Artikel aktiv Y in all 2683; Bestandsführung aktiv Y in 2675.
- Warengruppe Haarfarben in 2390, Haarpflege in 181, Haarstyling in 76, Sammelkarten in 11. Do not force hair-color category on unrelated products.
- Artikeltyp Normaler Artikel in 2678.
- Neu im Sortiment Y in 2605.
- Steuerklasse normaler steuersatz and Steuersatz in % 19,00 in all 2683 source rows. This is an article-type default, NOT a universal statement of tax law.
- Währung: EUR in only 37 rows, 2646 blank. User requests EUR for new imports.
- ASIN populated in 2415, blank in 268.
- Artikel zuletzt bearbeitet durch includes two named users in the export, while the SQL source stores a numeric editor ID.

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
