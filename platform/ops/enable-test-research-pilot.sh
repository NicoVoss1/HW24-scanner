#!/usr/bin/env bash
# TEST pilot: at most three reserved worker attempts total, not an unlimited schedule.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--enable-three-attempt-test-pilot' ]] || { echo 'STOP: root and explicit pilot approval required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
pilot="$private/research-pilot"
cron='/etc/cron.d/hw24-research-test'
target='038de71bc3f39bbf040a6dde82e81b6e19420fda'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
[[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]]
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
[[ "$(stat -c '%U %G %a' "$private")" == 'nicovoss psacln 750' ]]
[[ ! -e "$pilot" && ! -L "$pilot" && ! -e "$cron" && ! -L "$cron" ]]
systemctl is-active --quiet cron
n=$(plesk db -Ne "SELECT COUNT(*) FROM hw24_artikelmanager.hw24_research_jobs WHERE status='queued'")
[[ "$n" =~ ^[123]$ ]] || { echo 'STOP: pilot requires one to three actual queued articles'; exit 1; }
[[ "$(plesk db -Ne "SELECT COUNT(*) FROM hw24_artikelmanager.hw24_research_jobs WHERE status IN ('leased','retry')")" == 0 ]]
(
 cd "$app"
 runuser -u nicovoss -- "$php" <<'PHP'
<?php
require 'vendor/autoload.php';$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(['enabled','worker_enabled','scan_intake_enabled','scan_auto_enqueue','native_amazon_gate'] as $k)
 if(config('hw24_research_queue.'.$k)!==true)exit(2);
if(config('hw24_legacy.openai_auto_calls')!==false||config('hw24_legacy.amazon_auto_calls')!==false)exit(3);
App\Services\ResearchQueueRuntime::worker($app->make(App\Services\LegacyReadBridge::class));
echo "Worker ready; no provider call.\n";
PHP
)
# Record exact initial pilot jobs; operator must not add other jobs during pilot.
echo '=== QUEUED PILOT JOBS ==='
plesk db -Ne "SELECT id,ean,status FROM hw24_artikelmanager.hw24_research_jobs WHERE status='queued' ORDER BY id"
install -d -o nicovoss -g psacln -m 700 "$pilot"
# Write new files only in a newly created directory, as the application user.
runuser -u nicovoss -- /bin/bash -c 'umask 077; printf "3\n" > "$1/remaining"; printf "%s\n" "$(($(date +%s)+1800))" > "$1/deadline"; : > "$1/worker.log"' hw24 "$pilot"
runuser -u nicovoss -- /bin/bash -c 'umask 077; cat > "$1/run-once.sh"' hw24 "$pilot" <<'WORKER'
#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
p='/var/www/vhosts/hairworld24.de/hw24-console-private/research-pilot'
app='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform'
php='/opt/plesk/php/8.5/bin/php'
exec 9>"$p/lock"
flock -n 9 || exit 0
[[ -f "$p/remaining" && ! -L "$p/remaining" ]]
n=$(cat "$p/remaining")
[[ "$n" =~ ^[0123]$ ]] || exit 1
[[ "$n" != 0 ]] || exit 0
deadline=$(cat "$p/deadline")
[[ "$deadline" =~ ^[0-9]{10,12}$ ]] || exit 1
if (( $(date +%s) >= deadline )); then printf "0\n" > "$p/remaining"; exit 0; fi
# Reserve before running. On crash/unknown result, budget is not restored.
printf '%s\n' "$((n-1))" > "$p/remaining"
cd "$app"
if result=$(/usr/bin/timeout 150 "$php" artisan hw24:research-work-once --confirm --no-interaction 2>&1); then
    # Only a confirmed no-claim result restores the reservation.
    if [[ "$result" == '{"status":"empty_or_paused"}' ]]; then
        printf '%s\n' "$n" > "$p/remaining"
        exit 0
    fi
    printf '%s %s\n' "$(date -u +%FT%TZ)" "$result" >> "$p/worker.log"
else
    printf '%s worker stopped after error; review queue before retry\n' "$(date -u +%FT%TZ)" >> "$p/worker.log"
    printf '0\n' > "$p/remaining"
    exit 1
fi
WORKER
chmod 700 "$pilot/run-once.sh"
# Root-owned cron entry, execution only as application user. No provider work as root.
printf 'SHELL=/bin/bash\nPATH=/usr/bin:/bin\n* * * * * nicovoss /bin/bash %s/run-once.sh >/dev/null 2>&1\n' "$pilot" > "$cron"
chmod 644 "$cron"
echo '=== TEST RESEARCH PILOT SCHEDULED ==='
echo 'Up to three reserved worker attempts, once per minute, maximum 30-minute pilot window, no paid AI.'
echo 'Do not add other jobs or use direct legacy Amazon requests during the pilot.'
echo "Status: cat '$pilot/remaining'; tail -n 10 '$pilot/worker.log'"
echo "Stop: rm -f '$cron' (does not interrupt an already running job)."
