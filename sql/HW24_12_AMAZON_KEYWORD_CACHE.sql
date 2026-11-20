-- HW24 V12: begrenzter Amazon-Katalog-Stichwortcache (separater Testschritt).
-- Nur nach DB-Sicherung und Administratorfreigabe auf Testumgebung einrichten.
-- Keine Änderungen an JTL/WMS, keine Marktpreis- oder Bestandsbuchungen.
CREATE TABLE IF NOT EXISTS hw24_amazon_keyword_cache (
  marketplace_id VARCHAR(40) NOT NULL,
  query_hash CHAR(64) NOT NULL,
  normalized_query VARCHAR(180) NOT NULL,
  response_json LONGTEXT NOT NULL,
  item_count INT UNSIGNED NOT NULL,
  fetched_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (marketplace_id,query_hash),
  INDEX idx_amazon_keyword_cache_when (fetched_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Der Server ersetzt veraltete Treffer; keine unbegrenzte Sammlung historischer
-- Amazon-Katalog-Inhalte. Retention, Datenzugriffsrechte und Löschfristen vor
-- Einsatz separat mit aktuellen Amazon-SP-API-Bedingungen abgleichen.
