#!/usr/bin/env bash
# HW24 v4.22 TEST · one-time, server-local setup for NEW scanliste tables/user.
# WARNING: execute on the authenticated Plesk TEST host as root, never on Live.
# No secrets, databases, or website files are uploaded to GitHub.
set -Eeuo pipefail
umask 077

die() { printf 'ABBRUCH: %s\n' "$1" >&2; exit 1; }
if [ "$(id -u)" -ne 0 ]; then die 'PuTTY: zuerst sudo -i (root) ausführen.'; fi
command -v plesk >/dev/null || die 'Plesk-CLI nicht gefunden.'
command -v openssl >/dev/null || die 'OpenSSL nicht gefunden.'
command -v find >/dev/null || die 'find nicht gefunden.'
printf 'HW24 Testserver: Installation nur NEUER Scanlisten-Tabellen und eines separaten, eingeschränkten Datenbankbenutzers.\n'

# The live site is never selected. A test-specific API is the only path accepted.
mapfile -t candidates < <(find /var/www/vhosts -type f -path '*/api/scanliste.php' -print 2>/dev/null |
  grep -F 'test-artikelmanager' || true)
matches=()
for candidate in "${candidates[@]}"; do
  candidate_api_dir="$(dirname "$candidate")"
  candidate_site_root="$(dirname "$candidate_api_dir")"
  candidate_private="$(dirname "$candidate_site_root")/hw24_private"
  # Git clones and backup worktrees are not deployment targets; accept only a
  # test site with the already verified working private read-only db.php.
  if [ -f "$candidate_private/db.php" ]; then matches+=("$candidate"); fi
done
if [ "${#matches[@]}" -ne 1 ]; then
  printf 'Gefundene Test-API-Dateien: %s\n' "${#matches[@]}"
  printf '%s\n' "${matches[@]}"
  die 'Testseitige API nicht eindeutig. Bitte keine Datenbankänderung ausführen.'
fi
api_file="${matches[0]}"
api_dir="$(dirname "$api_file")"
site_root="$(dirname "$api_dir")"
private="$(dirname "$site_root")/hw24_private"
old_config="$private/db.php"
new_config="$private/scanlist-db.php"
[ -f "$old_config" ] || die 'Bestehende private db.php unter dem API-relevanten Pfad nicht gefunden.'
[ ! -e "$new_config" ] || die 'Scanlisten-Konfiguration existiert bereits: nichts überschrieben. Diagnose prüfen.'
printf 'Test-API: %s\n' "$api_file"
printf 'Privater Ordner: %s\n' "$private"
printf 'Ziel-Datenbank: hw24_artikelmanager (nur 5 neue Tabellen)\n'
printf 'Es werden keine bestehenden JTL-/WMS-Artikel oder Lagerbestände verändert.\n'
read -r -p 'Zum Fortfahren bitte exakt TEST eingeben: ' approve
[ "$approve" = TEST ] || die 'Keine Freigabe, keine Änderung.'

# Verify that Plesk admin can access the known database before any DDL.
if ! printf 'USE hw24_artikelmanager; SELECT 1;\n' | plesk db >/dev/null 2>&1; then
 die 'Plesk DB-CLI kann hw24_artikelmanager nicht öffnen. Kein Wechsel auf andere Datenbank.'
fi

new_user='hw24_scan_rw'
password="$(openssl rand -hex 32)"
[ "${#password}" -eq 64 ] || die 'Passwortgenerierung fehlgeschlagen.'
# Never print the password or include it in arguments/history.
printf 'Lege Tabellen und eingeschränkten Benutzer an …\n'
if ! {
  printf 'USE hw24_artikelmanager;\n'
  cat <<'SQL'
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
SQL
  printf "CREATE USER '%s'@'localhost' IDENTIFIED BY '%s';\n" "$new_user" "$password"
  printf "CREATE USER '%s'@'127.0.0.1' IDENTIFIED BY '%s';\n" "$new_user" "$password"
  for host in localhost 127.0.0.1; do
    for table in hw24_scan_batches hw24_scan_items; do
      printf "GRANT SELECT,INSERT,UPDATE ON hw24_artikelmanager.%s TO '%s'@'%s';\n" "$table" "$new_user" "$host"
    done
    for table in hw24_scan_events hw24_scan_draft_audit; do
      printf "GRANT SELECT,INSERT ON hw24_artikelmanager.%s TO '%s'@'%s';\n" "$table" "$new_user" "$host"
    done
    printf "GRANT SELECT,INSERT,UPDATE ON hw24_artikelmanager.hw24_amazon_keyword_cache TO '%s'@'%s';\n" "$new_user" "$host"
    for table in hw24_jtl_sync_meta hw24_jtl_articles; do
      printf "GRANT SELECT ON hw24_artikelmanager.%s TO '%s'@'%s';\n" "$table" "$new_user" "$host"
    done
  done
  printf 'FLUSH PRIVILEGES;\n'
} | plesk db >/dev/null; then
  unset password
  die 'MariaDB-Migration oder Benutzeranlage fehlgeschlagen. Konfiguration nicht geschrieben; keine ungezielten Wiederholungen.'
fi

# Restrict the new private secret to the identity/group of the already working
# private read-only config; no secret is printed or stored in this repository.
tmp="$(mktemp "$private/.hw24scan.XXXXXXXX")"
trap 'rm -f "$tmp"' EXIT
printf "<?php\nreturn ['user'=>'%s','password'=>'%s'];\n" "$new_user" "$password" > "$tmp"
chown --reference="$old_config" "$tmp"
chmod 0640 "$tmp"
mv -n "$tmp" "$new_config"
unset password
trap - EXIT
[ -f "$new_config" ] || die 'Private Konfigurationsdatei konnte nicht angelegt werden.'
if command -v php >/dev/null; then php -l "$new_config" >/dev/null || die 'PHP-Syntaxprüfung fehlgeschlagen.'; fi
printf '\nOK: Nur neue Scanlisten-/Keyword-Tabellen und privater Minimalbenutzer eingerichtet.\n'
printf 'Jetzt https://test-artikelmanager.hairworld24.de/scanliste.html neu laden und Diagnose prüfen.\n'
printf 'Keine SQL-Migration, keine Backups und keine Geheimnisse wurden in den öffentlichen Webroot geschrieben.\n'
