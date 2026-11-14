-- HW24 MariaDB V07: research sources, verified draft data, categories and audit.
-- Run on DATABASE hw24_artikelmanager only, after a complete backup.
-- Does not change, delete or mutate existing legacy/Excel/JTL mirror records.
-- All tables use InnoDB, utf8mb4 and original raw JSON text to preserve every source field.
CREATE TABLE IF NOT EXISTS hw24_source_files (
    source_file_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_sha256 CHAR(64) NOT NULL,
    file_size_bytes BIGINT UNSIGNED NULL,
    source_type VARCHAR(40) NOT NULL,
    imported_rows BIGINT UNSIGNED NULL,
    uploaded_by VARCHAR(120) NULL,
    uploaded_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE KEY uq_hw24_source_hash (file_sha256),
    INDEX idx_hw24_source_date (uploaded_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_source_records (
    source_record_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    source_file_id BIGINT UNSIGNED NOT NULL,
    sheet_or_table VARCHAR(255) NULL,
    source_row VARCHAR(140) NULL,
    ean VARCHAR(32) NULL,
    asin VARCHAR(32) NULL,
    artikelnummer VARCHAR(140) NULL,
    raw_json LONGTEXT NOT NULL,
    imported_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_hw24_record_ean (ean),
    INDEX idx_hw24_record_asin (asin),
    INDEX idx_hw24_record_artikelnummer (artikelnummer),
    INDEX idx_hw24_record_source (source_file_id,source_row),
    CONSTRAINT fk_hw24_records_file FOREIGN KEY (source_file_id)
        REFERENCES hw24_source_files (source_file_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_article_drafts (
    draft_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ean VARCHAR(32) NULL,
    jtl_article_id VARCHAR(80) NULL,
    artikelnummer VARCHAR(140) NULL,
    draft_kind VARCHAR(32) NOT NULL DEFAULT 'review',
    draft_status VARCHAR(32) NOT NULL DEFAULT 'open',
    source_snapshot_utc DATETIME(6) NULL,
    draft_json LONGTEXT NOT NULL,
    review_json LONGTEXT NULL,
    created_by VARCHAR(120) NULL,
    created_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_hw24_draft_ean (ean),
    INDEX idx_hw24_draft_artikelnummer (artikelnummer),
    INDEX idx_hw24_draft_status (draft_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_category_mappings (
    mapping_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    jtl_warengruppe VARCHAR(255) NOT NULL,
    jtl_category_path VARCHAR(600) NULL,
    product_family VARCHAR(180) NULL,
    amazon_product_type VARCHAR(150) NULL,
    amazon_browse_node VARCHAR(150) NULL,
    ebay_category_id VARCHAR(80) NULL,
    shopify_product_type VARCHAR(200) NULL,
    mapping_status VARCHAR(24) NOT NULL DEFAULT 'proposed',
    mapping_source VARCHAR(180) NULL,
    last_verified_utc DATETIME(6) NULL,
    created_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_hw24_mapping_wg (jtl_warengruppe),
    INDEX idx_hw24_mapping_status (mapping_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_audit_events (
    event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actor_type VARCHAR(30) NOT NULL,
    actor_id VARCHAR(140) NULL,
    action VARCHAR(100) NOT NULL,
    entity_kind VARCHAR(100) NOT NULL,
    entity_id VARCHAR(180) NULL,
    source_file_id BIGINT UNSIGNED NULL,
    before_json LONGTEXT NULL,
    after_json LONGTEXT NULL,
    details_json LONGTEXT NULL,
    request_id VARCHAR(80) NULL,
    INDEX idx_hw24_audit_when (event_utc),
    INDEX idx_hw24_audit_entity (entity_kind,entity_id),
    INDEX idx_hw24_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Do not add automatic write privileges to hw24_app; it must remain SELECT only.
-- The separate hw24_sync user should have write access only to its TWO JTL mirror tables.
-- A future authenticated application-user requires grants to drafts, source tables,
-- category mappings and audit events, scoped to the respective operations.
-- Creating these tables does not itself enable API writes or audit logging.


-- Central NEW-ARTICLE default profile. Read-only client support can be added later.
-- Existing JTL values always take precedence; never auto-overwrite current articles.
CREATE TABLE IF NOT EXISTS hw24_profile_defaults (
    field_key VARCHAR(150) NOT NULL PRIMARY KEY,
    default_value VARCHAR(500) NOT NULL,
    applies_to VARCHAR(24) NOT NULL DEFAULT 'new_only',
    provenance VARCHAR(40) NOT NULL,
    explanation VARCHAR(500) NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    changed_by VARCHAR(120) NULL,
    changed_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO hw24_profile_defaults
(field_key,default_value,applies_to,provenance,explanation)
VALUES
('Überverkäufe möglich','N','new_only','user+export','Strict default NO overselling. Manual per-item exception only. 2690 of 2691 rows N (14.11.2026).'),
('Lieferstatus','2-4 Werktage','new_only','export','1834 export rows agree.'),
('Stückzahl teilbar','N','new_only','user+export','2627 export rows used N.'),
('Zusätzliche Bearbeitungszeit','1','new_only','curated_export','Preferred maintained JTL reference articles and screenshot have 1; editable per article.'),
('Beschaffungszeit automatisch ermitteln','0','new_only','export','2681 export rows used 0.'),
('Beschaffungszeit manuell setzen','2','new_only','export','Historical mode was 2; user can change to 3.'),
('Versandklasse','standard','new_only','user+export','All 2683 export rows use standard.'),
('Artikel aktiv','Y','new_only','user+export','All 2683 export rows use Y.'),
('Bestandsführung aktiv','Y','new_only','user+export','2675 export rows use Y.'),
('Top-Artikel','N','new_only','user+export','Default N, review individually.'),
('Warengruppe','Haarfarben','new_only','user+export','2390 source rows use Haarfarben. Never classify unrelated products blindly.'),
('Artikeltyp','Normaler Artikel','new_only','user+export','2678 source rows use this value.'),
('Neu im Sortiment','Y','new_only','user+export','2605 export rows use Y.'),
('Steuerklasse','normaler steuersatz','new_only','user+export','19% is a proposed default for normally taxed hair-color products only.'),
('Steuersatz in %','19,00','new_only','user+export','Tax class must be correctly mapped; do not impose this on other tax classes.'),
('Währung','EUR','new_only','user','User standard; original export Währung field usually empty.');
