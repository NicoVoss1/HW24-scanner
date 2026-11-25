#!/usr/bin/env bash
# TEST only. No migration, provider call, cron change or JTL write.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--deploy-test-workflow' ]] || { echo 'STOP: explicit TEST approval required'; exit 1; }
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
old='f2941072baaffb6fa899de7e01fdec6a996f6426'
target='b194ab804500119247b26333dcd42908cbe18652'
branch='feature/hw24-mirror-audit-preflight-20261122'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
artisan(){ runuser -u nicovoss -- "$php" "$app/artisan" "$@" --no-interaction; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -n 9
[[ "$(g rev-parse HEAD)" == "$old" && -z "$(g status --porcelain --untracked-files=no)" ]]
[[ "$(g remote get-url origin)" =~ ^https://github\.com/NicoVoss1/HW24-scanner(\.git)?$ ]]
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
g fetch --no-tags origin "$branch"
g merge-base --is-ancestor "$target" FETCH_HEAD
g merge-base --is-ancestor "$old" "$target"
while IFS= read -r f; do
    case "$f" in
       docs/HW24_ARBEITSJOURNAL.md|platform/ops/deploy-test-hub-data.sh|platform/app/Domain/Jtl/ArticleCard.php|platform/app/Filament/Pages/ArticleLookup.php|platform/app/Filament/Pages/ScanWorkbench.php|platform/resources/views/filament/pages/article-lookup.blade.php|platform/resources/views/filament/pages/scan-workbench.blade.php|platform/resources/views/filament/partials/hub-release-status.blade.php|platform/tests/Feature/PersistentScanResearchTest.php) ;;
       *) echo "STOP: unexpected file $f"; exit 1;;
    esac
done < <(g diff --name-only "$old" "$target")
g diff --name-status "$old" "$target"
for f in platform/app/Domain/Jtl/ArticleCard.php platform/app/Filament/Pages/ArticleLookup.php platform/app/Filament/Pages/ScanWorkbench.php; do
    g show "$target:$f" | "$php" -l
done
install -d -m 700 /root/hw24-backups
[[ "$(stat -c '%U %a' /root/hw24-backups)" == 'root 700' && ! -L /root/hw24-backups ]]
backup=$(mktemp -d /root/hw24-backups/hub-workflow-XXXXXXXX)
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
        echo 'STOP: previous code restored; no database rollback required.'
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
g -c core.hooksPath=/dev/null merge --ff-only "$target"
[[ "$(g rev-parse HEAD)" == "$target" ]]
artisan view:clear
artisan view:cache
for route in scanliste artikel; do
    code=$(curl -sS --max-time 15 --proto '=https' -o /dev/null -w '%{http_code}' "https://test-artikelmanager.hairworld24.de/admin/$route")
    [[ "$code" == 401 || "$code" == 403 ]]
done
success=1
echo '=== TEST HUB WORKFLOW INSTALLED ==='
echo "HEAD: $(g rev-parse HEAD)"
echo "Backup: $backup"
echo 'No schema, provider, cron, JTL or LIVE changes.'
