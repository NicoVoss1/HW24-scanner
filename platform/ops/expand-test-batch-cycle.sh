#!/usr/bin/env bash
# Expand existing confirmed TEST scheduler; retain daily reservations and stop on unknown state.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 2 && "$1" == '--enable-all-test-batches' && "$2" =~ ^([1-9]|[1-9][0-9]|100)$ ]] ||
 { echo 'STOP: root, explicit all-TEST approval and shared daily attempt cap (1-100) required'; exit 1; }
limit="$2"
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"; app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
state="$private/batch-cycle/state.json"
cron='/etc/cron.d/hw24-batch-cycle-test'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -w 30 9 || { echo 'STOP: deployment or worker active'; exit 1; }
[[ -f "$state" && ! -L "$state" && -f "$cron" && ! -L "$cron" ]]
[[ ! -e /etc/cron.d/hw24-research-test ]]
[[ -z "$(g status --porcelain --untracked-files=no)" ]]
g merge-base --is-ancestor 4af4a821206df2f61c8d6b0786b4ce48b676f519 HEAD
[[ "$(g rev-parse HEAD)" == "$(g rev-parse origin/release/hw24-test)" ]]
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
systemctl is-active --quiet cron
# Read and validate exact existing schedule before touching any state.
batch=$(python3 - "$state" "$limit" <<'PY'
import json,sys
s=json.load(open(sys.argv[1]))
limit=int(sys.argv[2])
assert s["version"]==1 and type(s["batch"]) is int and s["batch"]>0
assert s["paused"] is False and s["inflight"] is False
assert type(s["used"]) is int and 0<=s["used"]<=s["limit"] and s["used"]<=limit
assert type(s["cursor"]) is int and s["cursor"]>=0
print(s["batch"])
PY
)
cd "$app"
runuser -u nicovoss -- "$php" artisan help hw24:scheduled-test-batch --no-interaction | grep -q -- '--all-test'
backup=$(mktemp -d /root/hw24-batch-cycle-backup.XXXXXXXX)
cp -p "$state" "$backup/state.json"
cp -p "$cron" "$backup/cron"
tmp=$(mktemp /etc/cron.d/.hw24-batch-cycle.XXXXXXXX)
rollback(){
    rm -f "$tmp"
    install -o nicovoss -g psacln -m 600 "$backup/state.json" "$state"
    install -o root -g root -m 644 "$backup/cron" "$cron"
    echo "STOP: state and cron restored from $backup"
}
trap rollback ERR
runuser -u nicovoss -- python3 - "$state" "$limit" <<'PY'
import json,os,sys,tempfile
p=sys.argv[1]; s=json.load(open(p))
s["limit"]=int(sys.argv[2])
s.setdefault("batch_cursors",{})[str(s["batch"])]=s["cursor"]
s.setdefault("last_batch",0)
fd,tmp=tempfile.mkstemp(dir=os.path.dirname(p))
with os.fdopen(fd,"w") as f:
    json.dump(s,f,separators=(",",":")); f.flush(); os.fsync(f.fileno())
os.replace(tmp,p)
PY
printf 'SHELL=/bin/bash\nPATH=/usr/bin:/bin\n* * * * * root /usr/bin/flock -n /run/lock/hw24-test-price-archive-deploy.lock /usr/sbin/runuser -u nicovoss -- /usr/bin/timeout 150 %s %s/artisan hw24:scheduled-test-batch %s --daily-limit=%s --all-test --confirm --no-interaction >/dev/null 2>&1\n' "$php" "$app" "$batch" "$limit" > "$tmp"
chmod 644 "$tmp"
mv -f "$tmp" "$cron"
trap - ERR
echo "=== ALL AUTHORIZED TEST BATCHES SCHEDULED ==="
echo "Every minute; shared daily attempt cap $limit; existing used count retained. No paid AI."
echo "Backup: $backup"
cat "$state"
