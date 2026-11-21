#!/usr/bin/env bash
# HW24 TEST: install ONLY the append-only MariaDB AI review history and narrow grants.
# No JTL, WMS, order, scan item or old backup table is altered.
set -Eeuo pipefail
umask 077
if [[ "$EUID" -ne 0 ]]; then echo "Bitte als root in PuTTY ausführen."; exit 1; fi
command -v plesk >/dev/null || { echo "Plesk nicht gefunden."; exit 1; }
PRIVATE="/var/www/vhosts/hairworld24.de/hw24_private"
SITE="/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de"
[[ -f "$PRIVATE/scanlist-db.php" ]] || { echo "ABBRUCH: bestehende private Scanlisten-DB-Konfiguration fehlt."; exit 1; }
[[ -f "$SITE/api/ai-review.php" && -f "$SITE/api/ai-reviews.php" ]] || {
 echo "ABBRUCH: Bitte zuerst die neue TEST-Version in Plesk Pull/Bereitstellen. Keine Live-Änderung."; exit 1;
}
echo "HW24 AI-Prüfarchiv V13: ausschließlich NEUE Tabelle hw24_ai_reviews in hw24_artikelmanager."
echo "Die Rechte für den vorhandenen Benutzer hw24_scan_rw werden NUR auf diese neue Tabelle erweitert."
echo "Es werden KEINE Bestände, Artikel, Warengruppen oder JTL-Tabellen geändert."
read -r -p "Zum Fortfahren exakt ARCHIV eingeben: " answer
[[ "$answer" == "ARCHIV" ]] || { echo "Abbruch, keine Änderung."; exit 1; }
printf 'USE hw24_artikelmanager; SELECT 1;\n' | plesk db >/dev/null
plesk db <<'SQL'
USE hw24_artikelmanager;
CREATE TABLE IF NOT EXISTS hw24_ai_reviews (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 actor VARCHAR(180) NOT NULL,
 ean VARCHAR(14) CHARACTER SET ascii NOT NULL,
 article_name VARCHAR(500) NOT NULL,
 model VARCHAR(80) NOT NULL,
 ai_status VARCHAR(24) NOT NULL,
 input_json LONGTEXT NOT NULL,
 review_json LONGTEXT NOT NULL,
 usd_cost DECIMAL(12,8) NOT NULL DEFAULT 0.00000000,
 tokens_input INT UNSIGNED NOT NULL DEFAULT 0,
 tokens_cached INT UNSIGNED NOT NULL DEFAULT 0,
 tokens_output INT UNSIGNED NOT NULL DEFAULT 0,
 created_at_utc DATETIME(6) NOT NULL,
 PRIMARY KEY (id),
 KEY idx_hw24_ai_actor_recent (actor,id),
 KEY idx_hw24_ai_actor_ean (actor,ean,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
GRANT SELECT,INSERT ON hw24_artikelmanager.hw24_ai_reviews TO 'hw24_scan_rw'@'localhost';
GRANT SELECT,INSERT ON hw24_artikelmanager.hw24_ai_reviews TO 'hw24_scan_rw'@'127.0.0.1';
SQL
printf 'USE hw24_artikelmanager; SELECT COUNT(*) AS bisherige_ki_pruefungen FROM hw24_ai_reviews;\n' | plesk db
echo "OK: KI-Prüfarchiv eingerichtet. Ab jetzt erzeugte erfolgreiche KI-Auswertungen werden dauerhaft gespeichert."
echo "WICHTIG: Die bereits VOR dieser Installation durchgeführte erste Prüfung wird nicht rückwirkend aus dem Kosten-Ledger rekonstruiert."
