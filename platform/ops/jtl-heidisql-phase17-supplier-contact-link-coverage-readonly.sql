-- HW24 Intelligence Hub / JTL-Wawi 2.2.2.0 / eazybusiness
-- PHASE 17: verified supplier -> representative joins and legacy purchase-code checks
-- Metadata source: operator's Phase-16 HeidiSQL output (2026-11-23).
--
-- READ-ONLY SQL SERVER SELECT statements. Every block is independently executable.
-- NEVER: INSERT / UPDATE / DELETE / MERGE / ALTER / CREATE on JTL.
-- Personal contact rows must be kept privately (no public GitHub paste).
-- Names, telephone, email, addresses and tax identifiers are excluded from
-- mandatory aggregate results. Optional named/private export is labeled.
-- This script has no effect on the existing scanliste, purchase API, or SQL schema.
-- No FK from dbo.tAnsprechpartner.kLieferant to dbo.tLieferant.kLieferant
-- was present in the Phase-16 FK output: validate relational coverage before use.

-- 0. Verify the correct JTL SQL Server database context.
SELECT DB_NAME() AS datenbankname;

-- 1. Supplier row and status census. Status is a raw cAktiv value, not a
-- confirmed soft-delete rule; NULL is not treated as 'N'.
SELECT
    COUNT_BIG(*) AS jtl_supplier_rows,
    COUNT_BIG(CASE WHEN cAktiv='Y' THEN 1 END) AS suppliers_active_Y,
    COUNT_BIG(CASE WHEN cAktiv='N' THEN 1 END) AS suppliers_inactive_N,
    COUNT_BIG(CASE WHEN cAktiv IS NULL THEN 1 END) AS suppliers_active_flag_null,
    COUNT_BIG(CASE WHEN cFirma IS NULL OR LTRIM(RTRIM(cFirma))='' THEN 1 END)
        AS suppliers_without_firm_name,
    COUNT_BIG(CASE WHEN DATALENGTH(bRowversion)=8 THEN 1 END)
        AS suppliers_with_8byte_rowversion
FROM dbo.tlieferant;

-- 2. Contact census and direct supplier-reference coverage.
-- kKunde is a distinct potential relationship; no customer rows are queried.
SELECT
    COUNT_BIG(*) AS all_jtl_contact_rows,
    COUNT_BIG(CASE WHEN a.kLieferant IS NOT NULL THEN 1 END)
        AS contacts_with_supplier_id,
    COUNT_BIG(CASE WHEN a.kLieferant IS NULL AND a.kKunde IS NOT NULL THEN 1 END)
        AS contacts_customer_only,
    COUNT_BIG(CASE WHEN a.kLieferant IS NULL AND a.kKunde IS NULL THEN 1 END)
        AS contacts_without_both_links,
    COUNT_BIG(CASE WHEN a.kLieferant IS NOT NULL AND a.kKunde IS NOT NULL THEN 1 END)
        AS contacts_with_both_links,
    COUNT_BIG(CASE WHEN a.kLieferant IS NOT NULL AND l.kLieferant IS NOT NULL THEN 1 END)
        AS contacts_with_existing_supplier_row,
    COUNT_BIG(CASE WHEN a.kLieferant IS NOT NULL AND l.kLieferant IS NULL THEN 1 END)
        AS contacts_with_missing_supplier_row,
    COUNT_BIG(CASE WHEN a.kLieferant IS NOT NULL AND l.cAktiv='Y' THEN 1 END)
        AS contacts_with_active_supplier_Y,
    COUNT_BIG(CASE WHEN DATALENGTH(a.bRowversion)=8 THEN 1 END)
        AS contacts_with_8byte_rowversion
FROM dbo.tansprechpartner AS a
LEFT JOIN dbo.tlieferant AS l ON l.kLieferant=a.kLieferant;

-- 3. Supplier-to-contact density, ONLY technical IDs / counts.
-- Shows whether one supplier maps to many representatives and vice versa.
SELECT TOP (30)
    l.kLieferant AS jtl_supplier_id,
    l.cAktiv AS supplier_active_raw,
    COUNT_BIG(a.kAnsprechpartner) AS linked_contact_rows,
    COUNT_BIG(CASE WHEN a.kKunde IS NOT NULL THEN 1 END)
        AS contacts_also_linked_to_customer_raw
FROM dbo.tlieferant AS l
LEFT JOIN dbo.tansprechpartner AS a ON a.kLieferant=l.kLieferant
GROUP BY l.kLieferant,l.cAktiv
HAVING COUNT_BIG(a.kAnsprechpartner)>0
ORDER BY COUNT_BIG(a.kAnsprechpartner) DESC,l.kLieferant;

-- 4. OPTIONAL PRIVATE TWO-NAME VERIFICATION (not a public data export).
-- Search only the operator-named Maike/Andre representatives.
-- The same person may appear under multiple suppliers; never pick first row.
-- No phone/email/address fields.
SELECT TOP (30)
    l.kLieferant AS jtl_supplier_id,
    l.cLiefNr AS supplier_number_raw,
    l.cFirma AS supplier_company_raw,
    l.cAktiv AS supplier_active_raw,
    a.kAnsprechpartner AS jtl_contact_id,
    a.cVorName AS contact_first_name_raw,
    a.cName AS contact_last_name_raw,
    CASE WHEN a.kKunde IS NULL THEN 0 ELSE 1 END AS also_linked_to_customer,
    CONVERT(varchar(18),CONVERT(varbinary(8),l.bRowversion),1)
        AS supplier_rowversion_hex,
    CONVERT(varchar(18),CONVERT(varbinary(8),a.bRowversion),1)
        AS contact_rowversion_hex
FROM dbo.tansprechpartner AS a
JOIN dbo.tlieferant AS l ON l.kLieferant=a.kLieferant
WHERE (
    a.cVorName COLLATE Latin1_General_CI_AI LIKE N'%Maike%'
 OR a.cVorName COLLATE Latin1_General_CI_AI LIKE N'%Andre%'
 OR a.cVorName COLLATE Latin1_General_CI_AI LIKE N'%Andi%'
 OR a.cName COLLATE Latin1_General_CI_AI LIKE N'%Brede%'
 OR a.cName COLLATE Latin1_General_CI_AI LIKE N'%Lud%'
)
ORDER BY l.kLieferant,a.kAnsprechpartner;

-- 5. OPTIONAL PRIVATE MINIMAL CONTACT CATALOG for later controlled
-- JTL -> EXISTING HW24 MariaDB read-only reference import, NOT for GitHub.
-- Save as NEW private UTF-8 semicolon CSV only after manually reviewing
-- aggregate rows 1-3. This is a snapshot, not a live binding.
-- No phone, email, postal addresses, customer names, tax numbers or passwords.
-- Distinct kAnsprechpartner stays the stable contact identity; prefix
-- (ML/AB) is a separate HW24 user-approved mapping, NOT derived from names.
SELECT
    l.kLieferant AS jtl_supplier_id,
    l.cLiefNr AS jtl_supplier_number_raw,
    l.cFirma AS jtl_supplier_company_raw,
    l.cAktiv AS jtl_supplier_active_raw,
    a.kAnsprechpartner AS jtl_contact_id,
    a.cVorName AS jtl_contact_first_name_raw,
    a.cName AS jtl_contact_last_name_raw,
    CASE WHEN a.kKunde IS NULL THEN 0 ELSE 1 END AS linked_to_customer_flag,
    CONVERT(varchar(18),CONVERT(varbinary(8),l.bRowversion),1)
        AS jtl_supplier_rowversion_hex,
    CONVERT(varchar(18),CONVERT(varbinary(8),a.bRowversion),1)
        AS jtl_contact_rowversion_hex
FROM dbo.tansprechpartner AS a
JOIN dbo.tlieferant AS l ON l.kLieferant=a.kLieferant
WHERE a.kLieferant IS NOT NULL
ORDER BY l.kLieferant,a.kAnsprechpartner;

-- 6. Earlier purchase-number detection INSIDE JTL purchase orders only.
-- ML202611-/AB202611- are operator-supplied examples, not a proof that
-- historical purchase codes were saved to cEigeneBestellnummer.
-- If no records, historical IDs can still exist OUTSIDE JTL.
WITH source_orders AS (
    SELECT
        CASE WHEN cEigeneBestellnummer LIKE N'ML202611-%'
                  THEN 'ML202611'
             WHEN cEigeneBestellnummer LIKE N'AB202611-%'
                  THEN 'AB202611'
        END AS code_family,
        TRY_CONVERT(int,
            SUBSTRING(cEigeneBestellnummer,
                CHARINDEX('-',cEigeneBestellnummer)+1,100)
        ) AS numeric_suffix
    FROM dbo.tLieferantenBestellung
    WHERE cEigeneBestellnummer LIKE N'ML202611-%'
       OR cEigeneBestellnummer LIKE N'AB202611-%'
)
SELECT code_family,
       COUNT_BIG(*) AS matching_jtl_orders,
       COUNT_BIG(CASE WHEN numeric_suffix IS NULL OR numeric_suffix<1 THEN 1 END)
           AS suffix_unparseable_or_invalid_rows,
       MAX(CASE WHEN numeric_suffix>0 THEN numeric_suffix END)
           AS max_valid_number_seen_in_jtl_orders
FROM source_orders
GROUP BY code_family
ORDER BY code_family;

-- Source facts remain separate:
-- * dbo.tLieferantenBestellung.cEigeneBestellnummer is a STRING field.
-- * dbo.tLieferantenBestellungPos.fEKNetto and .fMenge describe JTL order
--   positions, NOT automatically the actual accepted/billed purchase lot.
-- * No JTL order write, inventory receipt or link to a specific contact
--   can be inferred from these source columns alone.
