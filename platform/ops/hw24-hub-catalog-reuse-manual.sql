-- Narrow, short-window catalog evidence; no new copies or provider calls.
CREATE SQL SECURITY DEFINER VIEW hw24_hub_catalog_reuse AS
SELECT s.id AS snapshot_id,s.searched_ean,s.fetched_at_utc,i.asin,i.item_name,i.manufacturer,i.brand,i.item_json
FROM hw24_amazon_catalog_snapshots s JOIN hw24_amazon_catalog_items i
 ON i.snapshot_id=s.id AND i.marketplace_id=s.marketplace_id AND i.searched_ean=s.searched_ean
WHERE s.marketplace_id='A1PA6795UKMFR9' AND s.response_status=200 AND s.item_count=1
 AND s.fetched_at_utc >= UTC_TIMESTAMP()-INTERVAL 24 HOUR
 AND s.fetched_at_utc <= UTC_TIMESTAMP();
