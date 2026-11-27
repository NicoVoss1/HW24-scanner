#!/usr/bin/env bash
# TEST-only pilot. No LIVE changes, new database schema, paid AI or JTL writes.
# Starts persistent intake; scheduled worker is a separate explicit action.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--activate-test-intake' ]] || { echo 'STOP: root and explicit pilot confirmation required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
owner='nicovoss'
branch='feature/hw24-mirror-audit-preflight-20261122'
old='6802c77a6bd3db16f3157637138f1dfa1d320708'
target='a228d427800161f13e45b028e2f2eb7cd90f04c6'
gate="$private/hw24-native-amazon-gate.state"
g(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
artisan(){ runuser -u "$owner" -- "$php" "$app/artisan" "$@" --no-interaction; }
stop(){ echo "STOP: $*"; exit 1; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -n 9 || stop 'another deployment running'
[[ "$(g rev-parse HEAD)" == "$old" ]] || stop 'unexpected TEST HEAD'
[[ -z "$(g status --porcelain --untracked-files=no)" ]] || stop 'tracked changes'
[[ "$(g remote get-url origin)" =~ ^https://github\.com/NicoVoss1/HW24-scanner(\.git)?$ ]] || stop 'unexpected origin'
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]] || stop 'TEST root mismatch'
[[ "$(stat -c '%U %G %a' "$private")" == 'nicovoss psacln 750' ]] || stop 'private directory permissions changed'
[[ -f "$app/.env" && ! -L "$app/.env" && -f "$app/database/database.sqlite" && ! -L "$app/database/database.sqlite" ]] || stop 'private files missing'
[[ ! -e "$gate" && ! -L "$gate" ]] || stop 'gate file exists'
[[ ! -e /etc/cron.d/hw24-research-test ]] || stop 'worker cron already exists'
[[ "$(plesk db -Ne "SELECT COUNT(*) FROM hw24_artikelmanager.hw24_research_jobs WHERE status IN ('queued','leased','retry')")" == 0 ]] || stop 'pending queue jobs'
systemctl is-active --quiet cron || stop 'cron inactive'
python3 - "$app/.env" <<'PY'
import sys,re
from pathlib import Path
s=Path(sys.argv[1]).read_text()
if re.search(r'(?m)^\s*(?:export\s+)?(?:HW24_RESEARCH_SCAN_INTAKE_ENABLED|HW24_RESEARCH_SCAN_AUTO_ENQUEUE|HW24_NATIVE_AMAZON_GATE_ENABLED)\s*=',s):
    raise SystemExit('STOP: new flags already exist')
PY
check_http(){
    local code
    code=$(curl -sS --max-time 15 --proto '=https' -o /dev/null -w '%{http_code}' https://test-artikelmanager.hairworld24.de/admin/scanliste) || stop 'TEST HTTPS unavailable'
    [[ "$code" == 401 || "$code" == 403 ]] || stop "TEST protection not confirmed: $code"
}
check_http
g fetch --no-tags origin "$branch"
g merge-base --is-ancestor "$target" FETCH_HEAD
g merge-base --is-ancestor "$old" "$target"
g diff --name-status "$old" "$target"
# No schema changes; existing MariaDB archives/queue retained.
install -d -m 700 /root/hw24-backups
[[ "$(stat -c '%U %a' /root/hw24-backups)" == 'root 700' && ! -L /root/hw24-backups ]] || stop 'backup directory unsafe'
backup=$(mktemp -d /root/hw24-backups/scan-intake-XXXXXXXX)
cp -a "$app/.env" "$backup/env.before"
printf '%s\n' "$old" > "$backup/git.before"
sample=$(runuser -u "$owner" -- mktemp "$app/database/.hw24-intake-backup-XXXXXXXX.sqlite")
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
echo "Backup geprüft: $backup"
success=0
cleanup(){
    rc=$?
    trap - EXIT
    if [[ "$success" == 0 ]]; then
        cp -a "$backup/env.before" "$app/.env"
        if [[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]]; then
            g -c core.hooksPath=/dev/null reset --hard "$old" || true
        fi
        artisan config:clear >/dev/null || true
        artisan route:clear >/dev/null || true
        artisan view:clear >/dev/null || true
        echo 'STOP: partial installation. Existing queue rows/gate preserved for review. Do not rerun blindly.'
        echo "Backup: $backup"
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
g -c core.hooksPath=/dev/null merge --ff-only "$target"
[[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]] || stop 'deployed tree mismatch'
for f in "$app/app/Services/"{ResearchQueue,ScanQueueIntake,NativeAmazonGate,LegacyReadBridge}.php "$app/app/Filament/Pages/ScanWorkbench.php" "$app/config/hw24_research_queue.php"; do
    "$php" -l "$f"
done
printf '0\n' > "$gate"
chown nicovoss:psacln "$gate"
chmod 600 "$gate"
runuser -u "$owner" -- /bin/bash -c 'printf "\nHW24_RESEARCH_SCAN_INTAKE_ENABLED=true\nHW24_RESEARCH_SCAN_AUTO_ENQUEUE=true\nHW24_NATIVE_AMAZON_GATE_ENABLED=true\n" >> "$1"' hw24 "$app/.env"
artisan config:clear
artisan route:clear
artisan view:clear
artisan config:cache
artisan view:cache
(
    cd "$app"
    runuser -u "$owner" -- "$php" <<'PHP'
<?php
require "vendor/autoload.php";$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(['enabled','worker_enabled','scan_intake_enabled','scan_auto_enqueue','native_amazon_gate'] as $key)
    if(config('hw24_research_queue.'.$key)!==true)exit(2);
$q=App\Services\ResearchQueueRuntime::queue();
if(($q->status(1)['status']??null)!=='completed')exit(3);
App\Services\ResearchQueueRuntime::worker($app->make(App\Services\LegacyReadBridge::class));
echo "Intake and worker configuration verified; no provider call.\n";
PHP
)
check_http
success=1
trap - EXIT
echo '=== TEST SCAN INTAKE ACTIVATED ==='
echo "HEAD: $(g rev-parse HEAD)"
echo 'TEST scans and acknowledged CSV chunks queue automatically. Worker not scheduled yet.'
echo 'Native Amazon requests share a gate. Direct legacy requests are not covered.'
echo "Backup: $backup"
