-- Additive read-only views for TEST article quality; no raw JSON exposed.
-- Install as DB administrator after preflight; grant SELECT on these views only.
CREATE SQL SECURITY DEFINER VIEW hw24_hub_mirror_state AS
SELECT source_id,last_completed_utc,last_row_count,
       TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
FROM hw24_jtl_sync_meta
WHERE status='ready' AND last_completed_utc IS NOT NULL;

CREATE SQL SECURITY DEFINER VIEW hw24_hub_peer_source AS
SELECT a.source_id,a.jtl_article_id,a.ean,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.kWarengruppe')) AS kWarengruppe,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.kHersteller')) AS kHersteller,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.kLieferStatus')) AS kLieferStatus,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.kSteuerklasse')) AS kSteuerklasse,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.kVersandklasse')) AS kVersandklasse,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.cLagerAktiv')) AS cLagerAktiv,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.cLagerKleinerNull')) AS cLagerKleinerNull,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.cTeilbar')) AS cTeilbar
FROM hw24_jtl_articles a;
