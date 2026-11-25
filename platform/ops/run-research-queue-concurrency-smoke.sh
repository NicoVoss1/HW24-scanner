#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 ]] || { echo 'STOP: root required'; exit 1; }
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
php='/opt/plesk/php/8.5/bin/php'
target='01ae21bc1230d47ef705c44063f3cb6a6e7687f5'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root "$@"; }
old=$(g rev-parse HEAD)
g cat-file -e "$target^{commit}"
tmp=$(mktemp -d /tmp/hw24-queue-concurrency.XXXXXXXX)
db="hw24_queue_smoke_$(openssl rand -hex 8)"
db2="hw24_queue_smoke_$(openssl rand -hex 8)"
created=0
created2=0
p1=''
p2=''
cleanup(){
    rc=$?
    for p in "$p1" "$p2"; do
        if [[ -n "$p" ]]; then kill "$p" 2>/dev/null || true; wait "$p" 2>/dev/null || true; fi
    done
    if [[ "$created" == 1 ]]; then
        if sql -e "DROP DATABASE $db;"; then echo 'Lifecycle-Testdatenbank entfernt.'; else rc=1; fi
    fi
    if [[ "$created2" == 1 ]]; then
        if sql -e "DROP DATABASE $db2;"; then echo 'Parallel-Testdatenbank entfernt.'; else rc=1; fi
    fi
    rm -rf -- "$tmp"
    exit "$rc"
}
trap cleanup EXIT
[[ "$db" =~ ^hw24_queue_smoke_[0-9a-f]{16}$ && "$db2" =~ ^hw24_queue_smoke_[0-9a-f]{16}$ && "$db" != "$db2" ]]
mkdir -p "$tmp/platform/app/Services" "$tmp/platform/ops" "$tmp/barrier"
for f in platform/app/Services/ResearchQueue.php platform/ops/hw24-research-queue-v1-manual.sql platform/ops/hw24-research-queue-smoke.php platform/ops/hw24-research-queue-parallel.php; do
    g show "$target:$f" > "$tmp/$f"
done
for f in "$tmp/platform/app/Services/"*.php "$tmp/platform/ops/"*.php; do "$php" -l "$f"; done
sql -e "CREATE DATABASE $db CHARACTER SET utf8mb4;"
created=1
sql "$db" < "$tmp/platform/ops/hw24-research-queue-v1-manual.sql"
HW24_SMOKE_DB="$db" "$php" "$tmp/platform/ops/hw24-research-queue-smoke.php"
echo '=== TWO SIMULTANEOUS PHP PROCESSES ==='
sql -e "CREATE DATABASE $db2 CHARACTER SET utf8mb4;"
created2=1
sql "$db2" < "$tmp/platform/ops/hw24-research-queue-v1-manual.sql"
HW24_SMOKE_DB="$db2" HW24_PARALLEL_DIR="$tmp/barrier" "$php" "$tmp/platform/ops/hw24-research-queue-parallel.php" a > "$tmp/a.json" & p1=$!
HW24_SMOKE_DB="$db2" HW24_PARALLEL_DIR="$tmp/barrier" "$php" "$tmp/platform/ops/hw24-research-queue-parallel.php" b > "$tmp/b.json" & p2=$!
for i in $(seq 1 100); do
    if [[ -f "$tmp/barrier/ready-a" && -f "$tmp/barrier/ready-b" ]]; then break; fi
    sleep 0.1
done
[[ -f "$tmp/barrier/ready-a" && -f "$tmp/barrier/ready-b" ]]
touch "$tmp/barrier/go"
wait "$p1"; p1=''
wait "$p2"; p2=''
"$php" -r '
$a=json_decode(file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
$b=json_decode(file_get_contents($argv[2]),true,16,JSON_THROW_ON_ERROR);
if($a["job"]!==$b["job"]||((int)$a["created"]+(int)$b["created"])!==1||
   ((int)$a["claimed"]+(int)$b["claimed"])!==1)exit(1);
echo "PASS: simultaneous canonical enqueue created exactly one job\n";
echo "PASS: simultaneous claim granted exactly one lease\n";
' "$tmp/a.json" "$tmp/b.json"
[[ "$(sql -Ne "SELECT COUNT(*) FROM $db2.hw24_research_jobs")" == 1 ]]
[[ "$(sql -Ne "SELECT COUNT(*) FROM $db2.hw24_research_jobs WHERE status='leased' AND attempt_count=1")" == 1 ]]
[[ "$(sql -Ne "SELECT COUNT(*) FROM $db2.hw24_research_events WHERE event_type='linked'")" == 1 ]]
echo 'PASS: independent MariaDB counts and linked event'
[[ "$(g rev-parse HEAD)" == "$old" ]]
echo "TEST-HEAD unverändert: $old"
echo '=== HW24 QUEUE CONCURRENCY SMOKE PASS ==='
