-- HW24 Amazon Catalog archive v1 (MariaDB 10.4+)
-- Only non-PII Catalog Items responses. No access tokens or customer/order data.
-- Run ONCE on database hw24_artikelmanager, ideally after a backup.

CREATE TABLE IF NOT EXISTS hw24_amazon_catalog_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  marketplace_id VARCHAR(32) NOT NULL,
  searched_ean VARCHAR(14) NOT NULL,
  fetched_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  response_status SMALLINT UNSIGNED NOT NULL,
  item_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  response_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  INDEX idx_amazon_snapshot_lookup (marketplace_id, searched_ean, fetched_at_utc),
  CHECK (JSON_VALID(response_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_amazon_catalog_items (
  snapshot_id BIGINT UNSIGNED NOT NULL,
  marketplace_id VARCHAR(32) NOT NULL,
  searched_ean VARCHAR(14) NOT NULL,
  asin VARCHAR(20) NOT NULL,
  item_name VARCHAR(1000) NULL,
  brand VARCHAR(255) NULL,
  manufacturer VARCHAR(255) NULL,
  item_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (snapshot_id, asin),
  KEY idx_amazon_asin (marketplace_id, asin),
  KEY idx_amazon_ean (marketplace_id, searched_ean),
  CONSTRAINT fk_amazon_item_snapshot FOREIGN KEY (snapshot_id)
    REFERENCES hw24_amazon_catalog_snapshots(id) ON DELETE CASCADE,
  CHECK (JSON_VALID(item_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_amazon_lookup_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  requested_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  marketplace_id VARCHAR(32) NOT NULL,
  searched_ean VARCHAR(14) NOT NULL,
  outcome ENUM('live','cache','not_found','amazon_error','storage_error') NOT NULL,
  http_status SMALLINT UNSIGNED NULL,
  snapshot_id BIGINT UNSIGNED NULL,
  KEY idx_amazon_requests (searched_ean, requested_at_utc),
  CONSTRAINT fk_amazon_lookup_snapshot FOREIGN KEY (snapshot_id)
    REFERENCES hw24_amazon_catalog_snapshots(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
