#!/usr/bin/env bash
# Install an independent expanded history. Preserve original capture/cron/db.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--enable' ]] || { echo 'STOP: root and --enable required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
history="$private/article-history"
php='/opt/plesk/php/8.5/bin/php'
target='7a321182ec6a94b831f1cfd9808027f1416b044d'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
mysql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root --database=hw24_artikelmanager "$@"; }
exec 9>/run/lock/hw24-article-history.lock
flock -w 30 9
[[ -d "$history" && ! -L "$history" && -s "$history/history.sqlite" ]]
systemctl is-active --quiet cron
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
g show "$target:platform/ops/hw24-hub-history-extended-manual.sql" > "$tmp/view.sql"
g show "$target:platform/ops/hw24-capture-article-history.php" > "$tmp/capture.php"
"$php" -l "$tmp/capture.php"
if [[ $(mysql -Nse "SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='hw24_hub_history_extended'") == 0 ]]; then
    mysql < "$tmp/view.sql"
else
    echo 'Existing extended view: no overwrite.'
fi
mysql -e "GRANT SELECT ON hw24_artikelmanager.hw24_hub_history_extended TO 'hw24_hub_reader'@'localhost';"
mysql -t -e "
SELECT m.source_id,m.last_completed_utc,m.last_row_count,
       COUNT(x.jtl_article_id) AS projected,
       SUM(x.han IS NOT NULL) AS HAN,
       SUM(x.asin IS NOT NULL) AS ASIN,
       SUM(x.netto_vk IS NOT NULL) AS NettoVK,
       SUM(x.source_modified_at IS NOT NULL) AS SourceModified,
       SUM(x.dMod IS NOT NULL) AS dMod,
       SUM(x.kBenutzerLetzteAenderung IS NOT NULL) AS BearbeiterID,
       SUM(x.total_stock IS NOT NULL) AS Lagerbestand
FROM hw24_hub_mirror_state m LEFT JOIN hw24_hub_history_extended x ON x.source_id=m.source_id
GROUP BY m.source_id,m.last_completed_utc,m.last_row_count ORDER BY m.last_completed_utc DESC;"
if [[ -e "$history/capture-expanded.php" ]]; then
    cmp -s "$tmp/capture.php" "$history/capture-expanded.php" || { echo 'STOP: different installed extended script'; exit 1; }
else
    install -o nicovoss -g psacln -m 700 "$tmp/capture.php" "$history/capture-expanded.php"
fi
echo '=== RETAINED SNAPSHOTS / EXPANDED HISTORY ==='
runuser -u nicovoss -- "$php" "$history/capture-expanded.php" --extended
runuser -u nicovoss -- python3 - "$history" <<'PY'
import sqlite3,sys
from pathlib import Path
p=Path(sys.argv[1])
for name in ['history.sqlite','history-expanded.sqlite']:
    with sqlite3.connect(f"file:{p/name}?mode=ro",uri=True) as db:
        assert db.execute('PRAGMA quick_check').fetchall()==[('ok',)]
        print(name, db.execute('SELECT COUNT(*) FROM history_runs').fetchone()[0], 'Spiegelstände')
        print(db.execute("SELECT kind,field_key,COUNT(*) FROM history_events GROUP BY kind,field_key ORDER BY COUNT(*) DESC").fetchall())
PY
cron='/etc/cron.d/hw24-article-history-expanded'
cat > "$tmp/cron" <<'CRON'
SHELL=/bin/bash
PATH=/usr/sbin:/usr/bin:/sbin:/bin
1-59/2 * * * * root /bin/bash -o pipefail -c '/usr/bin/flock -n -E 75 /run/lock/hw24-article-history.lock /usr/sbin/runuser -u nicovoss -- /usr/bin/timeout 90 /opt/plesk/php/8.5/bin/php /var/www/vhosts/hairworld24.de/hw24-console-private/article-history/capture-expanded.php --extended 2>&1 | /usr/bin/logger -t hw24-article-history-expanded'
CRON
if [[ -e "$cron" || -L "$cron" ]]; then
    [[ -f "$cron" && ! -L "$cron" ]] && cmp -s "$tmp/cron" "$cron" || { echo 'STOP: different expanded cron'; exit 1; }
else
    install -o root -g root -m 644 "$tmp/cron" "$cron"
fi
echo '=== EXPANDED HISTORY ENABLED ==='
