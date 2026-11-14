/* HW24 Plesk phpMyAdmin read-only validation BEFORE and AFTER installation.
   Select database hw24_artikelmanager. This SQL changes NOTHING.
*/
SELECT DATABASE() AS aktuelle_datenbank;

SELECT
  TABLE_NAME,
  TABLE_ROWS,
  ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_NAME IN (
   'hw24_jtl_articles',
   'hw24_jtl_sync_meta',
   'hw24_source_files',
   'hw24_source_records',
   'hw24_article_drafts',
   'hw24_category_mappings',
   'hw24_audit_events',
   'hw24_profile_defaults',
   'hw24_excel_rows',
   'hw24_excel_identifiers'
  )
ORDER BY TABLE_NAME;

SELECT
 TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME IN ('hw24_jtl_articles','hw24_jtl_sync_meta')
ORDER BY TABLE_NAME,ORDINAL_POSITION;

SELECT
 TABLE_NAME,INDEX_NAME,COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME IN ('hw24_jtl_articles','hw24_jtl_sync_meta')
ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX;
