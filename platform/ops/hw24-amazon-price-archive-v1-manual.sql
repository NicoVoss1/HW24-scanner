-- HW24 Phase B1: additive price observation archive. TARGET existing HW24 MariaDB,
-- NOT JTL SQL Server. Manual approval, verified backup, least-privilege grants,
-- retention schedule and isolated schema smoke required BEFORE installation.
-- Deliberately no IF NOT EXISTS: pre-existing names are a collision to investigate.
-- No existing tables changed. No raw provider payload or customer data.
CREATE TABLE hw24_amazon_price_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  observation_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  marketplace_id VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  asin CHAR(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  item_condition VARCHAR(16) CHARACTER SET ascii NOT NULL,
  observed_at_utc DATETIME NOT NULL,
  expires_at_utc DATETIME NOT NULL,
  total_offer_count INT UNSIGNED NULL,
  returned_offer_count INT UNSIGNED NOT NULL,
  buybox_json LONGTEXT NOT NULL,
  lowest_json LONGTEXT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_price_observation (observation_key),
  KEY ix_price_history (marketplace_id,asin,item_condition,observed_at_utc,id),
  KEY ix_price_expiry (expires_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hw24_amazon_price_offers (
  snapshot_id BIGINT UNSIGNED NOT NULL,
  offer_index SMALLINT UNSIGNED NOT NULL,
  seller_id VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NULL,
  item_price DECIMAL(19,4) NULL,
  item_currency CHAR(3) CHARACTER SET ascii NULL,
  shipping_price DECIMAL(19,4) NULL,
  shipping_currency CHAR(3) CHARACTER SET ascii NULL,
  total_price DECIMAL(19,4) NULL,
  total_currency CHAR(3) CHARACTER SET ascii NULL,
  is_fba TINYINT(1) NULL,
  is_buybox_winner TINYINT(1) NULL,
  offer_condition VARCHAR(40) NULL,
  PRIMARY KEY (snapshot_id,offer_index),
  CONSTRAINT fk_hw24_price_offer_snapshot FOREIGN KEY (snapshot_id)
    REFERENCES hw24_amazon_price_snapshots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
