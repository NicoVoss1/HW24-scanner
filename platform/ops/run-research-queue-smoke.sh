#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 ]] || { echo 'STOP: root required'; exit 1; }
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
php='/opt/plesk/php/8.5/bin/php'
target='3bb4e6328ac8b6e46419700d07c9e381f5cd360a'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root "$@"; }
old=$(g rev-parse HEAD)
g cat-file -e "$target^{commit}"
tmp=$(mktemp -d /tmp/hw24-queue-smoke.XXXXXXXX)
db="hw24_queue_smoke_$(openssl rand -hex 8)"
created=0
cleanup(){
    rc=$?
    if [[ "$created" == 1 ]]; then
        if sql -e "DROP DATABASE $db;"; then echo 'Temporäre Testdatenbank entfernt.'; else rc=1; echo "WARNUNG: $db blieb bestehen."; fi
    fi
    rm -rf -- "$tmp"
    exit "$rc"
}
trap cleanup EXIT
[[ "$db" =~ ^hw24_queue_smoke_[0-9a-f]{16}$ ]]
mkdir -p "$tmp/platform/app/Services" "$tmp/platform/ops"
for f in platform/app/Services/ResearchQueue.php platform/ops/hw24-research-queue-v1-manual.sql platform/ops/hw24-research-queue-smoke.php; do
    g show "$target:$f" > "$tmp/$f"
done
"$php" -l "$tmp/platform/app/Services/ResearchQueue.php"
"$php" -l "$tmp/platform/ops/hw24-research-queue-smoke.php"
sql -e "CREATE DATABASE $db CHARACTER SET utf8mb4;"
created=1
sql "$db" < "$tmp/platform/ops/hw24-research-queue-v1-manual.sql"
HW24_SMOKE_DB="$db" "$php" "$tmp/platform/ops/hw24-research-queue-smoke.php"
[[ "$(g rev-parse HEAD)" == "$old" ]]
echo "TEST-HEAD unverändert: $old"
echo '=== HW24 QUEUE SMOKE ERFOLGREICH ==='
