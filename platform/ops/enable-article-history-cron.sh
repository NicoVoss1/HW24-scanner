#!/usr/bin/env bash
# Install only the independent read-only mirror history capture schedule.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--enable' ]] || { echo 'STOP: root and --enable required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
history="$private/article-history"
php='/opt/plesk/php/8.5/bin/php'
target='4fd8f04f84f507471136e3c8cd55ae54dd5c98b0'
cron='/etc/cron.d/hw24-article-history'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
exec 9>/run/lock/hw24-article-history.lock
flock -w 20 9
[[ -d "$history" && ! -L "$history" && -f "$history/capture.php" && ! -L "$history/capture.php" && -s "$history/history.sqlite" && ! -L "$history/history.sqlite" ]]
systemctl is-active --quiet cron
expected=$(g show "$target:platform/ops/hw24-capture-article-history.php" | sha256sum | cut -d' ' -f1)
actual=$(sha256sum "$history/capture.php" | cut -d' ' -f1)
[[ "$expected" == "$actual" ]] || { echo 'STOP: installed capture script differs from verified release'; exit 1; }
"$php" -l "$history/capture.php"
tmp=$(mktemp /etc/cron.d/.hw24-article-history.XXXXXXXX)
trap 'rm -f "$tmp"' EXIT
cat > "$tmp" <<'CRON'
SHELL=/bin/bash
PATH=/usr/sbin:/usr/bin:/sbin:/bin
*/2 * * * * root /bin/bash -o pipefail -c '/usr/bin/flock -n -E 75 /run/lock/hw24-article-history.lock /usr/sbin/runuser -u nicovoss -- /usr/bin/timeout 90 /opt/plesk/php/8.5/bin/php /var/www/vhosts/hairworld24.de/hw24-console-private/article-history/capture.php 2>&1 | /usr/bin/logger -t hw24-article-history; rc=$?; if [[ $rc != 0 && $rc != 75 ]]; then /usr/bin/logger -p user.err -t hw24-article-history "Historienlauf fehlgeschlagen: Exit $rc"; fi; exit "$rc"'
CRON
if [[ -e "$cron" || -L "$cron" ]]; then
    [[ -f "$cron" && ! -L "$cron" ]] && cmp -s "$tmp" "$cron" || { echo 'STOP: existing different cron schedule; inspect manually'; exit 1; }
    echo 'Historien-Cron bereits eingerichtet.'
else
    runuser -u nicovoss -- python3 - "$history" <<'PY'
import os, sqlite3, sys, datetime
from pathlib import Path
p=Path(sys.argv[1])
os.umask(0o077)
with sqlite3.connect(f"file:{p/'history.sqlite'}?mode=ro", uri=True) as db:
    assert db.execute("PRAGMA quick_check").fetchall()==[("ok",)]
    assert db.execute("SELECT COUNT(*) FROM history_runs WHERE kind='baseline'").fetchone()[0]>=1
    dest=p/("backup-before-cron-"+datetime.datetime.now(datetime.timezone.utc).strftime("%Y%m%dT%H%M%SZ")+".sqlite")
    assert not dest.exists()
    with sqlite3.connect(dest) as out:
        db.backup(out)
    print("Historien-Sicherung:",dest.name)
PY
    runuser -u nicovoss -- "$php" "$history/capture.php"
    chown root:root "$tmp"
    chmod 644 "$tmp"
    mv -T "$tmp" "$cron"
fi
echo '=== HW24 ARTIKELHISTORIE AUTOMATISCH EINGERICHTET ==='
cat "$cron"
echo 'Protokoll: journalctl -t hw24-article-history -n 20 --no-pager'
