/* HW24: lesende Kontrollabfragen für produktive JTL-API-Anbindung
 * In Plesk/phpMyAdmin Datenbank hw24_artikelmanager wählen.
 * Keine INSERT, UPDATE, DELETE, CREATE oder DROP.
 */
SELECT DATABASE() AS aktive_datenbank;

SELECT TABLE_NAME,TABLE_ROWS,ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
  AND TABLE_NAME IN ('hw24_jtl_articles','hw24_jtl_sync_meta',
    'hw24_excel_rows','hw24_excel_identifiers','hw24_profile_defaults')
ORDER BY TABLE_NAME;

SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME IN ('hw24_jtl_articles','hw24_jtl_sync_meta')
ORDER BY TABLE_NAME,ORDINAL_POSITION;

SELECT source_id,status,last_run_id,last_row_count,last_completed_utc,
       last_message,updated_utc
FROM hw24_jtl_sync_meta
ORDER BY updated_utc DESC LIMIT 10;

SELECT source_id,COUNT(*) AS artikel,
       COUNT(DISTINCT jtl_article_id) AS unterschiedliche_jtl_ids,
       COUNT(DISTINCT NULLIF(ean,'')) AS verschiedene_eans
FROM hw24_jtl_articles
GROUP BY source_id
ORDER BY source_id DESC;

SELECT a.jtl_article_id,a.artikelnummer,a.ean,a.han,a.asin,
       a.artikelname,a.hersteller,a.netto_vk,a.netto_ek,a.letzter_ek,
       a.letzter_ek_datum,a.bestand,a.synced_at_utc,
       m.status,m.last_completed_utc
FROM hw24_jtl_articles a
INNER JOIN hw24_jtl_sync_meta m ON m.source_id=a.source_id
WHERE a.ean='4015600040482'
   OR a.han='4015600040482'
   OR a.artikelnummer='4015600040482'
ORDER BY m.last_completed_utc DESC LIMIT 15;

SELECT m.source_id,m.last_row_count,
  TIMESTAMPDIFF(MINUTE,m.last_completed_utc,UTC_TIMESTAMP()) AS minuten_alt
FROM hw24_jtl_sync_meta m
WHERE m.status='ready'
ORDER BY m.last_completed_utc DESC LIMIT 1;

/* Only if HW24_07 already installed:
SELECT field_key,default_value,applies_to
FROM hw24_profile_defaults
WHERE field_key='Überverkäufe möglich';
*/
