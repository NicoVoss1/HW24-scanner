-- HW24 Intelligence Hub: non-destructive archive discovery after a move
-- TARGET: EXISTING MariaDB database hw24_artikelmanager, NOT JTL SQL Server.
-- Execute with an authorized read-only MariaDB account in HeidiSQL/phpMyAdmin.
-- Never run in eazybusiness, do not use JTL dbo.* schema.
-- All queries below are SELECT only. No data changes or installations.
-- Run QUERY 0-1 first. Run table-specific queries ONLY if each table exists.
-- Aggregate results are safe to share as counts; do not paste account names,
-- keys, customer documents, addresses, raw response_json, or unredacted exports.
--
-- Distinguish source existence, actor-scoped visibility and archive restoration.
-- We never conclude "deleted" from a 200 response with zero actor+EAN rows.

-- 0. Basic DB identity and data audit time; keep actual host private.
SELECT DATABASE() AS maria_database, UTC_TIMESTAMP() AS audit_at_utc,
       @@version AS maria_version;

-- 1. Verify which tables are present and when MariaDB reports they were created.
-- TABLE_ROWS may be an InnoDB ESTIMATE, not a reliable exact count.
SELECT TABLE_NAME, ENGINE, CREATE_TIME, UPDATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
  AND (TABLE_NAME LIKE 'hw24_ai%'
    OR TABLE_NAME LIKE 'hw24_amazon%'
    OR TABLE_NAME LIKE 'hw24_scan%'
    OR TABLE_NAME LIKE 'hw24_excel%'
    OR TABLE_NAME LIKE 'hw24_history%'
    OR TABLE_NAME LIKE 'hw24_purchase%')
ORDER BY TABLE_NAME;

-- 2. Confirm actor/EAN and time coverage of historic OpenAI records.
-- Stored usd_cost is a TOKEN-DERIVED COST ESTIMATE per previous code,
-- NOT a verified official provider invoice.
SELECT COUNT(*) AS all_ki_archive_rows,
       COUNT(DISTINCT actor) AS distinct_legacy_actor_values,
       COUNT(DISTINCT ean) AS distinct_scanned_eans,
       MIN(created_at_utc) AS oldest_ki_record_utc,
       MAX(created_at_utc) AS newest_ki_record_utc,
       COALESCE(SUM(usd_cost),0) AS archived_token_cost_estimate_usd
FROM hw24_ai_reviews;

-- 3. Check the scanned EAN across ALL legacy actors without disclosing
-- any account names. Current UI only shows a single Basic-Auth actor.
SELECT COUNT(*) AS ai_reports_for_ean_all_actors,
       COUNT(DISTINCT actor) AS actors_who_have_this_ean,
       MIN(created_at_utc) AS earliest_utc,
       MAX(created_at_utc) AS latest_utc
FROM hw24_ai_reviews
WHERE ean='0615908424980';

-- 4. Amazon catalog snapshots are separate from Amazon pricing / seller
-- profile names and from AI reviews. Check known UI archive ID 88.
SELECT COUNT(*) AS amazon_catalog_snapshot_rows,
       COUNT(DISTINCT searched_ean) AS distinct_catalog_eans,
       MIN(fetched_at_utc) AS earliest_snapshot_utc,
       MAX(fetched_at_utc) AS latest_snapshot_utc,
       SUM(CASE WHEN id=88 THEN 1 ELSE 0 END) AS screenshot_snapshot_88_present
FROM hw24_amazon_catalog_snapshots;

-- 5. Seller name mapping: a nonzero offers count does not imply a verified
-- display name exists for each seller. Exact table existence checked in 1.
SELECT COUNT(*) AS verified_seller_name_rows,
       COUNT(DISTINCT seller_id) AS distinct_seller_ids,
       MIN(verified_at_utc) AS earliest_verified_utc,
       MAX(verified_at_utc) AS latest_verified_utc
FROM hw24_amazon_seller_names
WHERE marketplace_id='A1PA6795UKMFR9';

-- 6. Existing Amazon lookup audit: generic record count, no actor names.
SELECT COUNT(*) AS legacy_amazon_lookup_events
FROM hw24_amazon_lookup_log;

-- 7. Existing scan history: counts only; different from the AI archive.
SELECT COUNT(*) AS scan_batches FROM hw24_scan_batches;
SELECT COUNT(*) AS scan_items FROM hw24_scan_items;
SELECT COUNT(*) AS idempotent_scan_events FROM hw24_scan_events;

-- 8. OPTIONAL PRIVATE ACTOR-LEVEL AUDIT: only an authorized administrator
-- on a private host, NEVER paste output into public GitHub.
-- Reveals legacy account identifiers, not yet verified physical individuals.
-- SELECT actor, COUNT(*) AS review_count,
--        MIN(created_at_utc) AS first_utc,MAX(created_at_utc) AS last_utc
-- FROM hw24_ai_reviews GROUP BY actor ORDER BY review_count DESC;

-- 9. If counts differ from your previous deployment:
-- * BACK UP current DB before comparison, then locate prior SQL backups;
-- * check previous Plesk vhost and private hw24_private/ directory/JSON ledger;
-- * compare database host, credential file target, imported tables,
--   Basic-Auth actor, account aliases and old EAN spelling;
-- * NEVER restore over the only existing copy or merge without de-duplication
--   and timestamps. Treat locally backed-up files as separate evidence.
