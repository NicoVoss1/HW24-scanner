#!/usr/bin/env bash
# Install outside the checkout, root-owned, execute only as nicovoss.
# The release ref is promoted separately after CI. Never track the development ref.
set -Eeuo pipefail
umask 077
[[ $(id -un) == nicovoss ]] || { echo 'STOP: application account only'; exit 1; }
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
state='/var/www/vhosts/hairworld24.de/hw24-console-private/deploy-state'
php='/opt/plesk/php/8.5/bin/php'
ref='refs/remotes/origin/release/hw24-test'
[[ -d "$state" && ! -L "$state" && "$(stat -c '%U %a' "$state")" == 'nicovoss 700' ]]
export GIT_TERMINAL_PROMPT=0 GIT_HTTP_LOW_SPEED_LIMIT=100 GIT_HTTP_LOW_SPEED_TIME=30
g(){ git -C "$repo" -c core.hooksPath=/dev/null "$@"; }
artisan(){ (cd "$app"; "$php" artisan "$@" --no-interaction); }
status(){ printf '%s %s\n' "$(date -u +%FT%TZ)" "$*" > "$state/status"; }
[[ "$(g remote get-url origin)" =~ ^https://github\.com/NicoVoss1/HW24-scanner(\.git)?$ ]] || { status 'BLOCKED: unexpected origin'; exit 1; }
[[ -z "$(g status --porcelain --untracked-files=no)" ]] || { status 'BLOCKED: tracked local changes'; exit 1; }
old=$(g rev-parse HEAD)
if ! g fetch --quiet --no-tags origin "refs/heads/release/hw24-test:$ref" > "$state/fetch.log" 2>&1; then
    status 'BLOCKED: release fetch failed'; exit 1
fi
target=$(g rev-parse "$ref^{commit}")
if [[ "$old" == "$target" ]]; then
    if [[ ! -f "$state/failed.sha" || "$(cat "$state/failed.sha")" != "$target" ]]; then status "CURRENT $old"; fi
    exit 0
fi
if ! g merge-base --is-ancestor "$old" "$target"; then status "BLOCKED: not fast-forward $old $target"; exit 1; fi
if [[ -f "$state/failed.sha" && "$(cat "$state/failed.sha")" == "$target" ]]; then exit 1; fi
# Conservative code-only scope. Configuration, migrations, dependencies, API files
# and workflows must use a separate explicitly approved deployment procedure.
g diff --no-renames --name-only -z "$old" "$target" > "$state/changed"
while IFS= read -r -d '' f; do
    case "$f" in
        platform/app/*.php|platform/resources/views/*.blade.php|platform/tests/*.php|platform/docs/*.md|docs/*.md|platform/ops/*.sh|platform/ops/*.php|platform/ops/*.sql|platform/ops/*.md|platform/ops/import-ameise-reference.py|platform/ops/import-historical-workbook.py) ;;
        *) status "BLOCKED: separate approval required for $f"; exit 1 ;;
    esac
    if g cat-file -e "$target:$f" 2>/dev/null; then
        mode=$(g ls-tree "$target" -- "$f" | cut -d' ' -f1)
        [[ "$mode" == 100644 || "$mode" == 100755 ]] || { status "BLOCKED: unexpected file type $f"; exit 1; }
    fi
done < "$state/changed"
exec > "$state/last-deploy.log" 2>&1
echo "TEST release $old -> $target"
while IFS= read -r -d '' f; do
    case "$f" in
        platform/app/*.php)
            if g cat-file -e "$target:$f" 2>/dev/null; then
                if ! g show "$target:$f" | "$php" -l; then status "FAILED: PHP lint $target"; printf '%s\n' "$target" > "$state/failed.sha"; exit 1; fi
            fi ;;
    esac
done < "$state/changed"
printf '%s\n' "$old" > "$state/previous.sha"
status "DEPLOYING $target"
success=0
cleanup(){
    rc=$?
    trap - EXIT
    if [[ "$success" == 0 ]]; then
        printf '%s\n' "$target" > "$state/failed.sha"
        if [[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]]; then
            if g reset --hard "$old" && artisan view:clear && artisan view:cache; then
                status "FAILED: rollback to $old after $target"
            else status "FAILED: manual recovery required $target"; fi
        else status "FAILED: inspect checkout $target"; fi
    fi
    exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
g merge --ff-only "$target"
[[ "$(g rev-parse HEAD)" == "$target" ]]
artisan view:clear
artisan view:cache
for route in scanliste artikel; do
    code=$(curl -sS --max-time 15 --proto '=https' -o /dev/null -w '%{http_code}' "https://test-artikelmanager.hairworld24.de/admin/$route")
    [[ "$code" == 401 || "$code" == 403 ]]
done
rm -f "$state/failed.sha"
success=1
status "DEPLOYED $target"
echo "TEST deployed $target; authenticated browser acceptance is separate."
