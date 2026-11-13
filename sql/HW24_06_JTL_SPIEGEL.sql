-- HW24: separate current JTL read mirror (NOT a direct write into JTL).
-- Execute on hw24_artikelmanager with database administrator after backup.
-- This migration does not modify existing Excel archive tables.
CREATE TABLE IF NOT EXISTS hw24_jtl_sync_meta (
    source_id VARCHAR(60) NOT NULL PRIMARY KEY,
    status VARCHAR(24) NOT NULL DEFAULT 'not_configured',
    last_completed_utc DATETIME(6) NULL,
    last_run_id VARCHAR(64) NULL,
    last_row_count BIGINT UNSIGNED NULL,
    last_message VARCHAR(500) NULL,
    updated_utc DATETIME(6) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_jtl_articles (
    source_id VARCHAR(60) NOT NULL,
    jtl_article_id VARCHAR(80) NOT NULL,
    artikelnummer VARCHAR(140) NULL,
    ean VARCHAR(32) NULL,
    han VARCHAR(140) NULL,
    asin VARCHAR(32) NULL,
    artikelname VARCHAR(600) NULL,
    hersteller VARCHAR(255) NULL,
    brutto_vk DECIMAL(16,4) NULL,
    bestand DECIMAL(16,3) NULL,
    raw_json LONGTEXT NULL,
    synced_at_utc DATETIME(6) NOT NULL,
    PRIMARY KEY (source_id, jtl_article_id),
    INDEX idx_hw24_jtl_ean (ean),
    INDEX idx_hw24_jtl_artikelnummer (artikelnummer),
    INDEX idx_hw24_jtl_asin (asin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Do NOT set status='ready' manually.
-- Only a completed, validated, consistent Windows-side synchronisation may
-- set ready/last_completed_utc, after successfully publishing a FULL snapshot.
