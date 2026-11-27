-- Read-only, narrow projection of the current mirror's original stock evidence.
-- No fallback to tArtikel.nLagerbestand, no calculated availability.
CREATE SQL SECURITY DEFINER VIEW hw24_hub_stock_source AS
SELECT a.source_id,a.jtl_article_id,a.ean,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_RelatedSources.bestand.truncated')) AS truncated_flag,
 JSON_TYPE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_RelatedSources.bestand.rows')) AS rows_type,
 JSON_LENGTH(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_RelatedSources.bestand.rows')) AS row_count,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_RelatedSources.bestand.rows[0].fLagerbestand')) AS total_stock,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_RelatedSources.bestand.rows[0].fVerfuegbar')) AS available_stock,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_RelatedSources.bestand.rows[0].fInAuftraegen')) AS in_orders,
 JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_RelatedSources.bestand.rows[0].fZulauf')) AS incoming
FROM hw24_jtl_articles a;
