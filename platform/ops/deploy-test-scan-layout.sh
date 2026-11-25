#!/usr/bin/env bash
# Pinned TEST-only layout rollout; no schema/config/worker/provider changes.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--deploy-test-layout' ]] || { echo 'STOP: explicit TEST approval required'; exit 1; }
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
old='a228d427800161f13e45b028e2f2eb7cd90f04c6'
target='bdfad288f37651cd55ab104b0f9f9eae19f9f056'
branch='feature/hw24-mirror-audit-preflight-20261122'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
artisan(){ runuser -u nicovoss -- "$php" "$app/artisan" "$@" --no-interaction; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -n 9
[[ "$(g rev-parse HEAD)" == "$old" ]]
[[ -z "$(g status --porcelain --untracked-files=no)" ]]
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
[[ "$(g remote get-url origin)" =~ ^https://github\.com/NicoVoss1/HW24-scanner(\.git)?$ ]]
g fetch --no-tags origin "$branch"
g merge-base --is-ancestor "$target" FETCH_HEAD
g merge-base --is-ancestor "$old" "$target"
g diff --name-status "$old" "$target"
# Do not install any unrelated runtime change.
while IFS= read -r f; do
    case "$f" in
        docs/HW24_ARBEITSJOURNAL.md|platform/ops/activate-test-scan-intake.sh|platform/app/Filament/Pages/ScanWorkbench.php|platform/resources/views/filament/pages/scan-workbench.blade.php|platform/tests/Feature/PreparedScanCsvTest.php) ;;
        *) echo "STOP: unexpected file $f"; exit 1;;
    esac
done < <(g diff --name-only "$old" "$target")
g show "$target:platform/app/Filament/Pages/ScanWorkbench.php" | "$php" -l
install -d -m 700 /root/hw24-backups
[[ "$(stat -c '%U %a' /root/hw24-backups)" == 'root 700' && ! -L /root/hw24-backups ]]
backup=$(mktemp -d /root/hw24-backups/scan-layout-XXXXXXXX)
printf '%s\n' "$old" > "$backup/git.before"
cp -a "$app/.env" "$backup/env.before"
success=0
cleanup(){
    rc=$?
    trap - EXIT
    if [[ "$success" == 0 && "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]]; then
        g -c core.hooksPath=/dev/null reset --hard "$old" || true
        artisan view:clear || true
        artisan view:cache || true
        echo 'STOP: Layout-Rollback ausgeführt; Daten unverändert.'
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
g -c core.hooksPath=/dev/null merge --ff-only "$target"
[[ "$(g rev-parse HEAD)" == "$target" ]]
"$php" -l "$app/app/Filament/Pages/ScanWorkbench.php"
artisan view:clear
artisan view:cache
code=$(curl -sS --max-time 15 --proto '=https' -o /dev/null -w '%{http_code}' https://test-artikelmanager.hairworld24.de/admin/scanliste)
[[ "$code" == 401 || "$code" == 403 ]]
success=1
echo '=== TEST SCAN LAYOUT UPDATED ==='
echo "HEAD: $(g rev-parse HEAD)"
echo "Backup: $backup"
echo 'No schema, provider, worker or LIVE changes.'
