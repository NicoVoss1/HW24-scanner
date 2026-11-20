-- HW24 curated canonical article states, v1.
-- Run with a privileged MariaDB account on the STAGING database after full backup.
-- Immutable Excel source tables remain unchanged.
CREATE TABLE IF NOT EXISTS hw24_curated_articles (
  ean VARCHAR(14) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
  approved_fields_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  revision INT UNSIGNED NOT NULL DEFAULT 1,
  approved_by VARCHAR(180) NOT NULL,
  approved_at_utc DATETIME NOT NULL,
  CHECK (JSON_VALID(approved_fields_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_curated_audit (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ean VARCHAR(14) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  revision INT UNSIGNED NOT NULL,
  approved_by VARCHAR(180) NOT NULL,
  changed_at_utc DATETIME NOT NULL,
  previous_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  approved_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  INDEX idx_curated_history (ean,revision),
  CHECK (JSON_VALID(previous_json)),
  CHECK (JSON_VALID(approved_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The Plesk application account currently has minimal privileges. Run these
-- only as an actual MariaDB administrator, NOT in the phpMyAdmin app account.
-- Confirm the account's host with SELECT User,Host FROM mysql.user WHERE User='hw24_app';
-- GRANT SELECT, INSERT, UPDATE ON hw24_artikelmanager.hw24_curated_articles TO 'hw24_app'@'localhost';
-- GRANT SELECT, INSERT ON hw24_artikelmanager.hw24_curated_audit TO 'hw24_app'@'localhost';
-- SHOW GRANTS FOR 'hw24_app'@'localhost';
