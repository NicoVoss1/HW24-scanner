-- Narrow read-only scan intake projection. Closed batches and removed rows excluded.
CREATE SQL SECURITY DEFINER VIEW hw24_hub_scan_summary_source AS
SELECT b.id AS batch_id,b.created_by,i.ean,i.scan_count
FROM hw24_scan_batches b JOIN hw24_scan_items i ON i.batch_id=b.id
WHERE b.closed_at_utc IS NULL AND i.review_state<>'removed';
