#!/usr/bin/env bash
# Long-running but bounded TEST cycle. Explicit operator activation, no paid AI.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 3 && "$1" == '--enable-test-batch-cycle' && "$2" =~ ^[1-9][0-9]{0,9}$ && "$3" =~ ^([1-9]|[1-9][0-9]|100)$ ]] ||
 { echo 'STOP: root, explicit approval, TEST batch ID and daily attempt limit required'; exit 1; }
batch="$2"; limit="$3"
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"; app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
target='5df467cc115322c2441c9b2f12f0d54b2718cc58'
state="$private/batch-cycle"
cron='/etc/cron.d/hw24-batch-cycle-test'
old='/etc/cron.d/hw24-research-test'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root --database=hw24_artikelmanager "$@"; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -w 30 9 || { echo 'STOP: deployment/worker active'; exit 1; }
[[ -z "$(g status --porcelain --untracked-files=no)" ]] && g merge-base --is-ancestor "$target" HEAD
[[ "$(g rev-parse HEAD)" == "$(g rev-parse origin/release/hw24-test)" ]]
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
[[ "$(stat -c '%U %G %a' "$private")" == 'nicovoss psacln 750' ]]
[[ ! -e "$state" && ! -L "$state" && ! -e "$cron" && ! -L "$cron" ]] || { echo 'STOP: schedule already exists; inspect state'; exit 1; }
systemctl is-active --quiet cron
[[ "$(sql -Ne "SELECT COUNT(*) FROM hw24_scan_batches WHERE id=$batch AND closed_at_utc IS NULL AND label REGEXP '^TEST([[:space:]_-]|$)'")" == 1 ]]
if [[ -e "$old" ]]; then
    remaining=$(cat "$private/research-pilot/remaining")
    deadline=$(cat "$private/research-pilot/deadline")
    [[ "$remaining" =~ ^[0123]$ && "$deadline" =~ ^[0-9]{10,12}$ ]]
    if [[ "$remaining" != 0 ]] && (( $(date +%s)<deadline )); then echo 'STOP: old pilot still active'; exit 1; fi
fi
cd "$app"
# Check runtime without any new provider query; verifies app account and mirror.
runuser -u nicovoss -- "$php" <<'PHP'
<?php
require 'vendor/autoload.php';$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(['enabled','worker_enabled','scan_intake_enabled','native_amazon_gate'] as $k)
 if(config('hw24_research_queue.'.$k)!==true)exit(2);
if(config('hw24_legacy.scan_writes_enabled')!==true||config('hw24_legacy.openai_auto_calls')!==false||config('hw24_legacy.amazon_auto_calls')!==false)exit(3);
App\Services\ResearchQueueRuntime::worker($app->make(App\Services\LegacyReadBridge::class));
echo "Worker ready; no provider request.\n";
PHP
echo '=== FIRST CYCLE USING ARCHIVE ONLY ==='
runuser -u nicovoss -- /usr/bin/timeout 150 "$php" artisan hw24:process-test-batch "$batch" --confirm --no-interaction
install -d -o nicovoss -g psacln -m 700 "$state"
runuser -u nicovoss -- /bin/bash -c 'umask 077; printf "{\"version\":1,\"batch\":%s,\"limit\":%s,\"day\":\"%s\",\"used\":0,\"cursor\":0,\"paused\":false,\"inflight\":false}\n" "$2" "$3" "$(date -u +%F)" > "$1/state.json"' hw24 "$state" "$batch" "$limit"
if [[ -e "$old" ]]; then
    [[ ! -L "$old" && -f "$old" ]] || { echo 'STOP: unexpected old cron'; exit 1; }
    install -m 600 "$old" "/root/hw24-old-research-cron-$(date -u +%Y%m%d%H%M%S).bak"
    rm -- "$old"
fi
printf 'SHELL=/bin/bash\nPATH=/usr/bin:/bin\n*/5 * * * * root /usr/bin/flock -n /run/lock/hw24-test-price-archive-deploy.lock /usr/sbin/runuser -u nicovoss -- /usr/bin/timeout 150 %s %s/artisan hw24:scheduled-test-batch %s --daily-limit=%s --confirm --no-interaction >/dev/null 2>&1\n' "$php" "$app" "$batch" "$limit" > "$cron"
chmod 644 "$cron"
echo '=== BOUNDED TEST SCHEDULE INSTALLED ==='
echo "Batch $batch; every 5 minutes; max $limit research attempts per UTC day; archive-only drafts continue after cap; no paid OpenAI or import."
echo "Status: cat '$state/state.json'"
echo "Stop: rm -f '$cron' (does not interrupt current cycle)."
