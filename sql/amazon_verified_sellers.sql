-- Verified Amazon seller display names. This is not a wholesaler/source-of-supply list.
CREATE TABLE IF NOT EXISTS hw24_amazon_seller_names (
 marketplace_id VARCHAR(32) NOT NULL,
 seller_id VARCHAR(40) NOT NULL,
 display_name VARCHAR(240) NOT NULL,
 source_url VARCHAR(512) NOT NULL,
 verified_by VARCHAR(180) NOT NULL,
 verified_at_utc DATETIME NOT NULL,
 PRIMARY KEY (marketplace_id,seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Apply with a database administrator after backup, then grant narrowly:
-- GRANT SELECT, INSERT, UPDATE ON hw24_artikelmanager.hw24_amazon_seller_names TO 'hw24_app'@'localhost';
-- No permissions on legacy JTL/Excel tables are changed.
