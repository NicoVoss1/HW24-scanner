#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--deploy-test-hub-data' ]] || { echo 'STOP: TEST approval required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
old='bdfad288f37651cd55ab104b0f9f9eae19f9f056'
target='f2941072baaffb6fa899de7e01fdec6a996f6426'
branch='feature/hw24-mirror-audit-preflight-20261122'
cfg="$private/hw24-hub-reader.json"
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root "$@"; }
artisan(){ runuser -u nicovoss -- "$php" "$app/artisan" "$@" --no-interaction; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -n 9
[[ "$(g rev-parse HEAD)" == "$old" && -z "$(g status --porcelain --untracked-files=no)" ]]
[[ "$(g remote get-url origin)" =~ ^https://github\.com/NicoVoss1/HW24-scanner(\.git)?$ ]]
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
[[ "$(stat -c '%U %G %a' "$private")" == 'nicovoss psacln 750' ]]
[[ ! -e "$cfg" && ! -L "$cfg" ]]
[[ "$(sql -Ne "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='hw24_artikelmanager' AND TABLE_NAME IN ('hw24_hub_mirror_state','hw24_hub_peer_source')")" == 0 ]]
[[ "$(sql -Ne "SELECT COUNT(*) FROM mysql.user WHERE User='hw24_hub_reader'")" == 0 ]]
g fetch --no-tags origin "$branch"
g merge-base --is-ancestor "$target" FETCH_HEAD
g merge-base --is-ancestor "$old" "$target"
g diff --name-status "$old" "$target"
# Existing DB tables are read only. Add only two restricted views.
install -d -m 700 /root/hw24-backups
[[ "$(stat -c '%U %a' /root/hw24-backups)" == 'root 700' && ! -L /root/hw24-backups ]]
backup=$(mktemp -d /root/hw24-backups/hub-data-XXXXXXXX)
printf '%s\n' "$old" > "$backup/git.before"
cp -a "$app/.env" "$backup/env.before"
success=0
cleanup(){
    rc=$?
    trap - EXIT
    if [[ "$success" == 0 ]]; then
        if [[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]]; then
            g -c core.hooksPath=/dev/null reset --hard "$old" || true
        fi
        artisan config:clear >/dev/null || true
        artisan view:clear >/dev/null || true
        echo 'STOP: deployment incomplete. New reader/views, if created, retained for review. Do not repeat blindly.'
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
g show "$target:platform/ops/hw24-hub-peer-views-manual.sql" | sql hw24_artikelmanager
password=$(openssl rand -hex 32)
{
    printf "CREATE USER 'hw24_hub_reader'@'localhost' IDENTIFIED BY '%s';\n" "$password"
    printf "%s\n" "GRANT SELECT ON hw24_artikelmanager.hw24_hub_mirror_state TO 'hw24_hub_reader'@'localhost';" \
    "GRANT SELECT ON hw24_artikelmanager.hw24_hub_peer_source TO 'hw24_hub_reader'@'localhost';"
} | sql
printf '{"user":"hw24_hub_reader","password":"%s"}\n' "$password" > "$cfg"
unset password
chown nicovoss:psacln "$cfg"
chmod 600 "$cfg"
g -c core.hooksPath=/dev/null merge --ff-only "$target"
[[ "$(g rev-parse HEAD)" == "$target" ]]
for f in ScanResearchPresentation HubCategoryPolicy HubPeerDefaults HubPeerMirror; do "$php" -l "$app/app/Services/$f.php"; done
"$php" -l "$app/app/Filament/Pages/ScanWorkbench.php"
artisan config:clear
artisan config:cache
artisan view:clear
artisan view:cache
(
 cd "$app"
 runuser -u nicovoss -- "$php" <<'PHP'
<?php
require 'vendor/autoload.php'; $app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $r=(new App\Services\HubPeerMirror())->read('4021609029274');
    echo json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable) {
    echo "Peer review not confirmed for reference article. Verify mirror freshness and exact group/manufacturer mapping.\n";
}
PHP
)
echo '=== JTL MIRROR STATUS ==='
sql -Ne "SELECT last_completed_utc,last_row_count,age_minutes FROM hw24_artikelmanager.hw24_hub_mirror_state ORDER BY last_completed_utc DESC LIMIT 1"
code=$(curl -sS --max-time 15 --proto '=https' -o /dev/null -w '%{http_code}' https://test-artikelmanager.hairworld24.de/admin/scanliste)
[[ "$code" == 401 || "$code" == 403 ]]
success=1
echo '=== TEST HUB DATA INSTALLED ==='
echo "HEAD: $(g rev-parse HEAD)"
echo "Backup: $backup"
echo 'No LIVE code changes, no JTL writes, no provider calls or automatic worker activation.'
