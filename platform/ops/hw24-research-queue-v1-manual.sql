-- HW24 central research queue V1. Manual isolated TEST installation only.
-- Supersedes the NOT INSTALLED purchase-specific Amazon queue proposal.
-- Do not install both queue implementations. Preflight existing purchase queue tables.
-- No existing tables changed; no provider payloads stored; no JTL/WMS writes.
CREATE TABLE hw24_research_jobs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 gtin_key CHAR(14) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 ean VARCHAR(14) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 status ENUM('queued','leased','retry','completed','blocked') NOT NULL DEFAULT 'queued',
 attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 requested_at_utc DATETIME NOT NULL,
 next_attempt_utc DATETIME NULL,
 lease_token CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
 lease_until_utc DATETIME NULL,
 leased_by VARCHAR(180) NULL,
 last_error_code VARCHAR(64) CHARACTER SET ascii NULL,
 completed_at_utc DATETIME NULL,
 evidence_json TEXT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_hw24_research_gtin (gtin_key),
 KEY ix_hw24_research_ready(status,next_attempt_utc,requested_at_utc,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE hw24_research_gate (
 scope_key VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 next_allowed_utc DATETIME NOT NULL,
 active_job_id BIGINT UNSIGNED NULL,
 PRIMARY KEY(scope_key),
 CONSTRAINT fk_hw24_research_active FOREIGN KEY(active_job_id) REFERENCES hw24_research_jobs(id)
) ENGINE=InnoDB;
INSERT INTO hw24_research_gate(scope_key,next_allowed_utc) VALUES ('shared_workspace','2000-01-01 00:00:00');
CREATE TABLE hw24_research_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 job_id BIGINT UNSIGNED NOT NULL,
 actor VARCHAR(180) NOT NULL,
 event_type VARCHAR(24) CHARACTER SET ascii NOT NULL,
 detail_code VARCHAR(64) CHARACTER SET ascii NULL,
 occurred_at_utc DATETIME NOT NULL,
 PRIMARY KEY(id),
 KEY ix_hw24_research_events_job(job_id,id),
 CONSTRAINT fk_hw24_research_event_job FOREIGN KEY(job_id) REFERENCES hw24_research_jobs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
