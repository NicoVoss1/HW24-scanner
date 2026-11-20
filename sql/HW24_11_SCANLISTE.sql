-- HW24 V11 · dauerhafte EAN-Scanliste, ausschließlich MariaDB / HW24.
-- Nur nach Sicherung und gesonderter Admin-Freigabe ausführen.
-- Keine Änderung an JTL-Wawi, WMS, historischen Quellen oder Beständen.
CREATE TABLE IF NOT EXISTS hw24_scan_batches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(180) NOT NULL,
  created_by VARCHAR(180) NOT NULL,
  created_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  closed_at_utc DATETIME(6) NULL,
  INDEX idx_scan_batch_created (created_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_scan_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  ean VARCHAR(14) NOT NULL,
  scan_count INT UNSIGNED NOT NULL DEFAULT 1,
  resolution VARCHAR(24) NOT NULL DEFAULT 'unverified',
  jtl_article_id VARCHAR(80) NULL,
  artikelnummer VARCHAR(140) NULL,
  artikelname VARCHAR(600) NULL,
  review_state VARCHAR(24) NOT NULL DEFAULT 'scan_only',
  draft_json LONGTEXT NULL,
  source_json LONGTEXT NULL,
  revision INT UNSIGNED NOT NULL DEFAULT 0,
  reviewed_at_utc DATETIME(6) NULL,
  updated_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  snapshot_at_utc DATETIME(6) NULL,
  first_scanned_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  last_scanned_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_hw24_scan_batch_ean (batch_id,ean),
  INDEX idx_scan_batch_modified (batch_id,last_scanned_utc),
  INDEX idx_scan_resolution (batch_id,resolution),
  INDEX idx_scan_review (batch_id,review_state),
  CONSTRAINT fk_scan_batch FOREIGN KEY(batch_id) REFERENCES hw24_scan_batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hw24_scan_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  ean VARCHAR(14) NOT NULL,
  client_event_key CHAR(36) NOT NULL,
  scanned_by VARCHAR(180) NOT NULL,
  event_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_scan_client_event (client_event_key),
  INDEX idx_scan_events_batch_time (batch_id,event_utc),
  CONSTRAINT fk_scan_event_batch FOREIGN KEY(batch_id) REFERENCES hw24_scan_batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS hw24_scan_draft_audit (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  scan_item_id BIGINT UNSIGNED NOT NULL,
  revision INT UNSIGNED NOT NULL,
  changed_by VARCHAR(180) NOT NULL,
  change_kind VARCHAR(24) NOT NULL,
  previous_json LONGTEXT NULL,
  next_json LONGTEXT NOT NULL,
  changed_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY uq_draft_revision (scan_item_id,revision),
  CONSTRAINT fk_draft_item FOREIGN KEY(scan_item_id) REFERENCES hw24_scan_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Für den API-Benutzer ausschließlich benötigte SELECT/INSERT/UPDATE-Rechte
-- auf die vier Scan-Tabellen; keine DDL- oder JTL-Wawi-Schreibrechte.
