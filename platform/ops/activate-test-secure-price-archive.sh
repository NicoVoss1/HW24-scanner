#!/usr/bin/env bash
# Explicit TEST pilot. Pinned CI + MariaDB smoke evidence; no production code change.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 ]] || { echo 'STOP: root required'; exit 1; }
[[ "$#" == 1 && "$1" == '--activate-test-pilot-7-days' ]] || { echo 'STOP: explicit pilot argument required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
owner='nicovoss'
group='psacln'
branch='feature/hw24-mirror-audit-preflight-20261122'
old='2f39f161d37e4ffcb14d8ce406b783562ca66fec'
target='a1e080b3c785c44aa15202516c54522c5e819026'
site='test-artikelmanager.hairworld24.de'
table='hw24_amazon_price_history_secure'
cron='/etc/cron.d/hw24-price-archive-test'
cfg="$private/hw24-price-archive.json"
key="$private/hw24-price-archive.key"
heartbeat="$private/hw24-price-archive-purge.ok"
lock="$private/hw24-price-archive-purge.lock"
g(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root "$@"; }
artisan(){ runuser -u "$owner" -- "$php" "$app/artisan" "$@" --no-interaction; }
stop(){ echo "STOP: $*"; exit 1; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -n 9 || stop 'another deployment running'

echo '=== 1. PRECHECK ==='
[[ "$(g rev-parse HEAD)" == "$old" ]] || stop 'unexpected TEST HEAD'
[[ -z "$(g status --porcelain --untracked-files=no)" ]] || stop 'tracked code changes'
[[ "$(g remote get-url origin)" =~ ^https://github\.com/NicoVoss1/HW24-scanner(\.git)?$ ]] || stop 'unexpected git origin'
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='$site'")" == "$app/public" ]] || stop 'unexpected TEST webroot'
[[ -f "$app/.env" && ! -L "$app/.env" && -f "$app/database/database.sqlite" && ! -L "$app/database/database.sqlite" ]] || stop 'private app files missing'
[[ -f "$app/vendor/autoload.php" && -f "$app/composer.lock" ]] || stop 'dependencies missing'
[[ "$(stat -c '%U %G %a' "$private")" == "$owner $group 750" ]] || stop 'private directory permissions changed'
systemctl is-active --quiet cron || stop 'cron inactive'
for f in "$cfg" "$key" "$heartbeat" "$lock" "$cron"; do
    [[ ! -e "$f" && ! -L "$f" ]] || stop "existing pilot path: $f"
done
[[ "$(sql -Ne "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='hw24_artikelmanager' AND TABLE_NAME IN ('$table','hw24_amazon_price_snapshots','hw24_amazon_price_offers')")" == 0 ]] || stop 'archive schema already present; no overwrite'
[[ "$(sql -Ne "SELECT COUNT(*) FROM mysql.user WHERE User='hw24_price_archive'")" == 0 ]] || stop 'archive account already present'
python3 - "$app/.env" <<'PY'
import sys,re
from pathlib import Path
s=Path(sys.argv[1]).read_text()
if re.search(r'(?m)^\s*(?:export\s+)?HW24_PRICE_ARCHIVE_ENABLED\s*=',s):
    raise SystemExit('STOP: archive flag already exists; no overwrite')
PY
for f in "$app/.env" "$app/database/database.sqlite"; do
    for u in nginx www-data; do
        if runuser -u "$u" -- test -r "$f"; then stop 'private file exposed'; fi
    done
done
check_http(){
    local code
    code=$(curl -sS --max-time 15 --proto '=https' -o /dev/null -w '%{http_code}' "https://$site/admin/intelligence") || stop 'TEST HTTPS unavailable'
    [[ "$code" == 401 || "$code" == 403 ]] || stop "TEST anonymous protection not confirmed: $code"
}
check_http
g fetch --no-tags origin "$branch"
g merge-base --is-ancestor "$target" FETCH_HEAD || stop 'target outside branch'
g merge-base --is-ancestor "$old" "$target" || stop 'non-fast-forward target'
# Target is the exact immutable commit whose CI and encrypted MariaDB smoke passed.
g diff --name-status "$old" "$target"

echo '=== 2. BACKUPS BEFORE ADDITIVE MIGRATION ==='
install -d -m 700 /root/hw24-backups /root/hw24-backup-keys
for d in /root/hw24-backups /root/hw24-backup-keys; do
    [[ "$(stat -c '%U %a' "$d")" == 'root 700' && ! -L "$d" ]] || stop 'backup directory unsafe'
done
backup=$(mktemp -d /root/hw24-backups/price-pilot-XXXXXXXX)
backupkey=$(mktemp /root/hw24-backup-keys/price-pilot-XXXXXXXX.key)
openssl rand -hex 32 > "$backupkey"
cp -a "$app/.env" "$backup/env.before"
printf '%s\n' "$old" > "$backup/git.before"
printf '%s\n' "$backupkey" > "$backup/backup-key-path"
# Consistent existing DB dump encrypted before disk; no new pilot data or key in dump.
mariadb-dump --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root \
    --single-transaction --quick --skip-lock-tables --hex-blob hw24_artikelmanager |
    gzip -c | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$backupkey" -out "$backup/mariadb.sql.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$backupkey" -in "$backup/mariadb.sql.gz.enc" | gzip -t
sample=$(runuser -u "$owner" -- mktemp "$app/database/.hw24-price-backup-XXXXXXXX.sqlite")
trap 'rm -f -- "$sample"' EXIT
runuser -u "$owner" -- "$php" -r '
$db=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("PRAGMA busy_timeout=5000");
$db->prepare("VACUUM INTO ?")->execute([$argv[2]]);
$copy=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($copy->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(1);
' "$app/database/database.sqlite" "$sample"
install -m 600 "$sample" "$backup/database.sqlite"
rm -f -- "$sample"
trap - EXIT
echo "Backup geprüft: $backup"

# No provider call before activation success. On partial failure preserve new schema
# and secrets for inspection; disable capture and restore previous application if safe.
started=1
success=0
cleanup(){
    local rc=$?
    trap - EXIT
    if [[ "$success" == 0 ]]; then
        echo 'STOP: pilot activation incomplete; no intentional provider call performed.'
        rm -f -- "$cron"
        cp -a "$backup/env.before" "$app/.env"
        if [[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]]; then
            g -c core.hooksPath=/dev/null reset --hard "$old" || true
        fi
        artisan config:clear >/dev/null || true
        artisan route:clear >/dev/null || true
        artisan view:clear >/dev/null || true
        echo 'New archive structures/keys, if created, retained for inspection; do not rerun blindly.'
        echo "Rollback backup: $backup"
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== 3. NEW ENCRYPTED TABLE AND RESTRICTED ACCOUNT ==='
g show "$target:platform/ops/hw24-amazon-price-archive-secure-v2-manual.sql" | sql hw24_artikelmanager
# Only a freshly generated hexadecimal password is interpolated into SQL.
password=$(openssl rand -hex 32)
master=$(openssl rand -hex 32)
printf "CREATE USER 'hw24_price_archive'@'localhost' IDENTIFIED BY '%s';\nGRANT SELECT, INSERT, DELETE ON hw24_artikelmanager.%s TO 'hw24_price_archive'@'localhost';\n" "$password" "$table" | sql
fingerprint=$(printf '%s' "$master" | xxd -r -p | sha256sum | cut -d' ' -f1)
# Root-only shell writes; secrets never passed as process arguments.
printf '%s\n' "$master" > "$key"
printf '{"database":"hw24_artikelmanager","user":"hw24_price_archive","password":"%s","retention_days":7,"key_fingerprint":"%s"}\n' "$password" "$fingerprint" > "$cfg"
unset password master fingerprint
chown "$owner:$group" "$key" "$cfg"
chmod 600 "$key" "$cfg"
# Separate, restricted key recovery copy. Operator must secure off-server separately.
install -m 600 "$key" "$backupkey.archive-master"
printf '%s\n' "$backupkey.archive-master" > "$backup/archive-key-recovery-path"
echo 'Dedicated archive account and private key created.'

echo '=== 4. TEST FAST-FORWARD ==='
g -c core.hooksPath=/dev/null merge --ff-only "$target"
[[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]] || stop 'unexpected deployed tree'
for f in AmazonPriceArchive PriceArchiveStore EncryptedAmazonPriceArchive AmazonPriceArchiveRuntime AmazonPriceResearch ScanResearch; do
    "$php" -l "$app/app/Services/$f.php"
done
"$php" -l "$app/app/Filament/Pages/IntelligenceWorkbench.php"
"$php" -l "$app/routes/console.php"
# Cron installed BEFORE enabling capture. Runtime additionally requires fresh heartbeat.
printf 'SHELL=/bin/bash\nPATH=/usr/bin:/bin\n*/15 * * * * nicovoss /usr/bin/flock -n %s /bin/bash -c '\''cd "%s" && "%s" artisan hw24:price-archive-purge --no-interaction'\'' >/dev/null 2>&1\n' "$lock" "$app" "$php" > "$cron"
chmod 644 "$cron"
# Add only the new flag, preserving all other settings.
runuser -u "$owner" -- /bin/bash -c 'printf "\nHW24_PRICE_ARCHIVE_ENABLED=true\n" >> "$1"' hw24 "$app/.env"
artisan config:clear
artisan route:clear
artisan view:clear
artisan config:cache
artisan view:cache
artisan hw24:price-archive-purge
[[ -s "$heartbeat" && ! -L "$heartbeat" ]] || stop 'purge heartbeat missing'
check_http
[[ "$(sql -Ne "SELECT COUNT(*) FROM hw24_artikelmanager.$table")" == 0 ]] || stop 'unexpected observations before controlled test'
success=1
trap - EXIT
echo '=== TEST PRICE ARCHIVE ACTIVATED ==='
echo "HEAD: $(g rev-parse HEAD)"
echo 'Retention: 7 days; purge every 15 minutes.'
echo 'Archive empty; no provider call made by deployment.'
echo "Backup: $backup"
echo 'LIVE code and JTL/WMS unchanged. Key recovery file is local only; secure separately.'
