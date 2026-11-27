-- HW24 encrypted price archive, manual additive migration, existing HW24 MariaDB only.
-- Supersedes the unencrypted V1 proposal for real data. V1 remains synthetic test only.
-- No changes to existing tables. No raw provider payload, ASIN, seller ID or price in cleartext.
-- Requires backup, collision check, dedicated restricted account and retention approval.
CREATE TABLE hw24_amazon_price_history_secure (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lookup_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    observation_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    observed_at_utc DATETIME NOT NULL,
    expires_at_utc DATETIME NOT NULL,
    key_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    ciphertext LONGTEXT CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    PRIMARY KEY(id),
    UNIQUE KEY uq_hw24_secure_observation (observation_key),
    KEY ix_hw24_secure_history (lookup_key,observed_at_utc,id),
    KEY ix_hw24_secure_expiry (expires_at_utc)
) ENGINE=InnoDB;
