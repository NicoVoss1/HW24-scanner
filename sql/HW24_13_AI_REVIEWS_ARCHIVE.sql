-- HW24 V13 · AI review archive. Run ONCE using plesk db (NOT via Plesk public Git webroot).
-- No existing JTL/WMS tables, inventory, orders, or earlier scan tables changed.
USE hw24_artikelmanager;

CREATE TABLE IF NOT EXISTS hw24_ai_reviews (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 actor VARCHAR(180) NOT NULL,
 ean VARCHAR(14) CHARACTER SET ascii NOT NULL,
 article_name VARCHAR(500) NOT NULL,
 model VARCHAR(80) NOT NULL,
 ai_status VARCHAR(24) NOT NULL,
 input_json LONGTEXT NOT NULL,
 review_json LONGTEXT NOT NULL,
 usd_cost DECIMAL(12,8) NOT NULL DEFAULT 0.00000000,
 tokens_input INT UNSIGNED NOT NULL DEFAULT 0,
 tokens_cached INT UNSIGNED NOT NULL DEFAULT 0,
 tokens_output INT UNSIGNED NOT NULL DEFAULT 0,
 created_at_utc DATETIME(6) NOT NULL,
 PRIMARY KEY (id),
 KEY idx_hw24_ai_actor_recent (actor,id),
 KEY idx_hw24_ai_actor_ean (actor,ean,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

GRANT SELECT,INSERT ON hw24_artikelmanager.hw24_ai_reviews
 TO 'hw24_scan_rw'@'localhost';
GRANT SELECT,INSERT ON hw24_artikelmanager.hw24_ai_reviews
 TO 'hw24_scan_rw'@'127.0.0.1';
-- No UPDATE/DELETE privilege needed: historical analysis records remain immutable.
