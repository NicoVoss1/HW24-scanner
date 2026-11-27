#!/usr/bin/env bash
# Explicit TEST-only central queue installation. No worker schedule or provider calls.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--activate-test-queue' ]] || { echo 'STOP: explicit TEST approval and root required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
owner='nicovoss'
branch='feature/hw24-mirror-audit-preflight-20261122'
old='a1e080b3c785c44aa15202516c54522c5e819026'
target='6802c77a6bd3db16f3157637138f1dfa1d320708'
site='test-artikelmanager.hairworld24.de'
cfg="$private/hw24-research-queue.json"
g(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root "$@"; }
artisan(){ runuser -u "$owner" -- "$php" "$app/artisan" "$@" --no-interaction; }
stop(){ echo "STOP: $*"; exit 1; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -n 9 || stop 'another deployment running'

echo '=== 1. PRECHECK ==='
[[ "$(g rev-parse HEAD)" == "$old" ]] || stop 'unexpected TEST HEAD'
[[ -z "$(g status --porcelain --untracked-files=no)" ]] || stop 'tracked changes'
[[ "$(g remote get-url origin)" =~ ^https://github\.com/NicoVoss1/HW24-scanner(\.git)?$ ]] || stop 'unexpected origin'
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='$site'")" == "$app/public" ]] || stop 'TEST webroot mismatch'
[[ -f "$app/.env" && ! -L "$app/.env" && -f "$app/database/database.sqlite" && ! -L "$app/database/database.sqlite" ]] || stop 'private app files missing'
[[ "$(stat -c '%U %G %a' "$private")" == 'nicovoss psacln 750' ]] || stop 'private permissions mismatch'
[[ ! -e "$cfg" && ! -L "$cfg" ]] || stop 'queue private config already exists'
[[ "$(sql -Ne "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='hw24_artikelmanager' AND (TABLE_NAME LIKE 'hw24_research_%' OR TABLE_NAME LIKE 'hw24_purchase_amazon_%')")" == 0 ]] || stop 'existing queue tables require review'
[[ "$(sql -Ne "SELECT COUNT(*) FROM mysql.user WHERE User='hw24_research_queue'")" == 0 ]] || stop 'queue user already exists'
[[ "$(sql -Ne "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='hw24_artikelmanager' AND TABLE_NAME IN ('hw24_amazon_catalog_snapshots','hw24_amazon_price_history_secure')")" == 2 ]] || stop 'required archives missing'
for tool in mariadb mariadb-dump openssl gzip flock python3 curl; do command -v "$tool" >/dev/null || stop "missing $tool"; done
systemctl is-active --quiet cron || stop 'cron inactive'
[[ -f /etc/cron.d/hw24-price-archive-test ]] || stop 'price archive cron missing'
runuser -u "$owner" -- "$php" -r '
$p=$argv[1]; $ts=is_file($p)&&!is_link($p)?trim((string)file_get_contents($p)):"";
if(!ctype_digit($ts)||(int)$ts<time()-3600||(int)$ts>time()+60)exit(1);
echo "Price purge heartbeat age: ".(time()-(int)$ts)." seconds\n";
' "$private/hw24-price-archive-purge.ok"
python3 - "$app/.env" <<'PY'
import sys,re
from pathlib import Path
s=Path(sys.argv[1]).read_text()
if re.search(r'(?m)^\s*(?:export\s+)?HW24_RESEARCH_(?:QUEUE|WORKER)_ENABLED\s*=',s):
    raise SystemExit('STOP: queue flags already exist')
PY
check_http(){
    local code
    code=$(curl -sS --max-time 15 --proto '=https' -o /dev/null -w '%{http_code}' "https://$site/admin/intelligence") || stop 'TEST HTTPS unavailable'
    [[ "$code" == 401 || "$code" == 403 ]] || stop "TEST anonymous protection not confirmed: $code"
}
check_http
g fetch --no-tags origin "$branch"
g merge-base --is-ancestor "$target" FETCH_HEAD
g merge-base --is-ancestor "$old" "$target"
g diff --name-status "$old" "$target"

echo '=== 2. BACKUP ==='
install -d -m 700 /root/hw24-backups /root/hw24-backup-keys
for d in /root/hw24-backups /root/hw24-backup-keys; do
    [[ "$(stat -c '%U %a' "$d")" == 'root 700' && ! -L "$d" ]] || stop 'backup directory unsafe'
done
backup=$(mktemp -d /root/hw24-backups/queue-pilot-XXXXXXXX)
backupkey=$(mktemp /root/hw24-backup-keys/queue-pilot-XXXXXXXX.key)
openssl rand -hex 32 > "$backupkey"
cp -a "$app/.env" "$backup/env.before"
printf '%s\n' "$old" > "$backup/git.before"
printf '%s\n' "$backupkey" > "$backup/backup-key-path"
# Exclude the short-retention price archive: no existing table will be altered.
mariadb-dump --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root \
    --single-transaction --quick --skip-lock-tables --hex-blob \
    --ignore-table=hw24_artikelmanager.hw24_amazon_price_history_secure hw24_artikelmanager |
    gzip -c | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$backupkey" -out "$backup/mariadb.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$backupkey" -in "$backup/mariadb.sql.gz.enc" | gzip -t
sample=$(runuser -u "$owner" -- mktemp "$app/database/.hw24-queue-backup-XXXXXXXX.sqlite")
trap 'rm -f -- "$sample"' EXIT
runuser -u "$owner" -- "$php" -r '
$db=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("PRAGMA busy_timeout=5000");
$db->prepare("VACUUM INTO ?")->execute([$argv[2]]);
$c=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($c->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(1);
' "$app/database/database.sqlite" "$sample"
install -m 600 "$sample" "$backup/database.sqlite"
rm -f -- "$sample"
trap - EXIT
echo "Backup geprüft: $backup (Preisarchiv nicht dupliziert)"

success=0
cleanup(){
    local rc=$?
    trap - EXIT
    if [[ "$success" == 0 ]]; then
        echo 'STOP: installation incomplete; no intentional provider requests.'
        cp -a "$backup/env.before" "$app/.env"
        if [[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]]; then
            g -c core.hooksPath=/dev/null reset --hard "$old" || true
        fi
        artisan config:clear >/dev/null || true
        artisan route:clear >/dev/null || true
        artisan view:clear >/dev/null || true
        echo 'New schema/account retained for inspection; do not rerun blindly.'
        echo "Backup: $backup"
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== 3. NEW QUEUE TABLES AND RESTRICTED ACCOUNT ==='
g show "$target:platform/ops/hw24-research-queue-v1-manual.sql" | sql hw24_artikelmanager
password=$(openssl rand -hex 32)
{
    printf "CREATE USER 'hw24_research_queue'@'localhost' IDENTIFIED BY '%s';\n" "$password"
    printf "%s\n" \
      "GRANT SELECT,INSERT,UPDATE ON hw24_artikelmanager.hw24_research_jobs TO 'hw24_research_queue'@'localhost';" \
      "GRANT SELECT,UPDATE ON hw24_artikelmanager.hw24_research_gate TO 'hw24_research_queue'@'localhost';" \
      "GRANT INSERT ON hw24_artikelmanager.hw24_research_events TO 'hw24_research_queue'@'localhost';" \
      "GRANT SELECT (id,marketplace_id,searched_ean,response_status) ON hw24_artikelmanager.hw24_amazon_catalog_snapshots TO 'hw24_research_queue'@'localhost';"
} | sql
printf '{"database":"hw24_artikelmanager","user":"hw24_research_queue","password":"%s"}\n' "$password" > "$cfg"
unset password
chown nicovoss:psacln "$cfg"
chmod 600 "$cfg"

echo '=== 4. TEST CODE ==='
g -c core.hooksPath=/dev/null merge --ff-only "$target"
[[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]] || stop 'unexpected deployed tree'
for f in LegacyReadBridge ScanResearch ResearchQueue ResearchQueueRuntime ResearchQueueWorker ResearchJobProcessor; do
    "$php" -l "$app/app/Services/$f.php"
done
"$php" -l "$app/routes/console.php"
runuser -u "$owner" -- /bin/bash -c 'printf "\nHW24_RESEARCH_QUEUE_ENABLED=true\nHW24_RESEARCH_WORKER_ENABLED=true\n" >> "$1"' hw24 "$app/.env"
artisan config:clear
artisan route:clear
artisan view:clear
artisan config:cache
artisan view:cache
# Instantiate both runtime dependencies without claiming or contacting providers.
(
    cd "$app"
    runuser -u "$owner" -- "$php" <<'PHP'
<?php
require "vendor/autoload.php"; $app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$q=App\Services\ResearchQueueRuntime::queue();
if($q->status(1)!==null)exit(2);
App\Services\ResearchQueueRuntime::worker($app->make(App\Services\LegacyReadBridge::class));
echo "Queue and worker runtime ready; no provider call.\n";
PHP
)
check_http
[[ "$(sql -Ne "SELECT COUNT(*) FROM hw24_artikelmanager.hw24_research_jobs")" == 0 ]] || stop 'unexpected queue jobs'
success=1
trap - EXIT
echo '=== TEST RESEARCH QUEUE INSTALLED ==='
echo "HEAD: $(g rev-parse HEAD)"
echo 'Worker enabled for explicit CLI commands only; no scheduled worker.'
echo 'Price archive and its purge cron retained. LIVE and JTL/WMS unchanged.'
echo "Backup: $backup"
