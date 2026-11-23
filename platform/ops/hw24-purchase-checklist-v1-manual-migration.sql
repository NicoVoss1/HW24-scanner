-- HW24 purchase checklist V1 | MANUAL / OPT-IN MIGRATION ONLY
-- MariaDB schema: hw24_artikelmanager (NOT JTL SQL Server eazybusiness).
-- Execute ONLY after DB backup, dry-run on separate TEST database,
-- table/type/grant inspection and express operator deployment approval.
-- Never run from a web request or framework migration automatically.
-- Creates NEW isolated structures; NO ALTER/DROP/DELETE on existing tables.
-- Scan quantities remain solely in the existing hw24_scan_items.scan_count.
-- Contact JTL IDs are optional validated operator references, not synced.
-- Before applying, inspect pre-existing names to avoid IF NOT EXISTS drift.

CREATE TABLE IF NOT EXISTS hw24_purchase_contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by VARCHAR(180) NOT NULL,
    supplier_label VARCHAR(180) NOT NULL,
    contact_name VARCHAR(180) NOT NULL,
    code_prefix VARCHAR(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    jtl_supplier_id INT NULL,
    jtl_contact_id INT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_purchase_contact_actor_prefix (created_by, code_prefix),
    KEY ix_purchase_contact_actor (created_by, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_purchase_sequences (
    created_by VARCHAR(180) NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    year_month CHAR(6) CHARACTER SET ascii NOT NULL,
    next_number INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (created_by, contact_id, year_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_purchase_headers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by VARCHAR(180) NOT NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    purchase_code VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    closed_at_utc DATETIME(6) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_purchase_actor_code (created_by,purchase_code),
    UNIQUE KEY uq_purchase_batch (batch_id),
    KEY ix_purchase_actor_date (created_by,created_at_utc),
    KEY ix_purchase_contact (created_by,contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_purchase_costs (
    purchase_id BIGINT UNSIGNED NOT NULL,
    scan_item_id BIGINT UNSIGNED NOT NULL,
    unit_net_cents BIGINT UNSIGNED NOT NULL,
    vat_percent TINYINT UNSIGNED NOT NULL,
    last_changed_by VARCHAR(180) NOT NULL,
    updated_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (purchase_id,scan_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_purchase_cost_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    purchase_id BIGINT UNSIGNED NOT NULL,
    scan_item_id BIGINT UNSIGNED NOT NULL,
    before_unit_net_cents BIGINT UNSIGNED NULL,
    after_unit_net_cents BIGINT UNSIGNED NOT NULL,
    before_vat_percent TINYINT UNSIGNED NULL,
    after_vat_percent TINYINT UNSIGNED NOT NULL,
    changed_by VARCHAR(180) NOT NULL,
    changed_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY(id),
    KEY ix_purchase_cost_audit (purchase_id,scan_item_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SQL grants for hw24_private/scanlist-db.php DB account need a separate
-- reviewed operator approval. Do NOT grant DDL or SQL Server rights.
-- No demo contacts or automatically assigned JTL supplier/contact IDs.
