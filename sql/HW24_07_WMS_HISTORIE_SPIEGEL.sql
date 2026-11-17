-- HW24: In phpMyAdmin auf Plesk, Datenbank hw24_artikelmanager.
-- Additive migration. Keine vorhandenen Tabellen ändern oder löschen.
CREATE TABLE IF NOT EXISTS hw24_wms_history_runs (
 run_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 status ENUM('staging','ready') NOT NULL,
 row_count BIGINT UNSIGNED NULL,
 created_utc DATETIME(6) NOT NULL,
 completed_utc DATETIME(6) NULL,
 PRIMARY KEY(run_id),
 KEY latest_ready(status,completed_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hw24_wms_history (
 run_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 movement_id INT NOT NULL,
 article_id INT UNSIGNED NOT NULL,
 moved_at DATETIME(3) NULL,
 type_id INT NULL,
 type_name VARCHAR(255) NULL,
 qty DECIMAL(28,13) NULL,
 from_id INT NULL,
 from_name VARCHAR(255) NULL,
 to_id INT NULL,
 to_name VARCHAR(255) NULL,
 PRIMARY KEY(run_id,movement_id),
 KEY by_article(run_id,article_id,moved_at,movement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rechte: vorhandene Konten/Hostzuordnung zunächst prüfen.
-- hw24_app benötigt SELECT auf beiden NEUEN Tabellen.
-- hw24_sync benötigt SELECT, INSERT, UPDATE, DELETE ausschließlich
-- auf diesen zwei zusätzlichen Tabellen. Keine generellen DB-Rechte.
