#!/usr/bin/env bash
# Explicitly authorized deployment of only the two legacy AI endpoints.
# No OpenAI POST, no key disclosure, no DB migration.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--deploy-manual-ai-api' ]] ||
 { echo 'STOP: explicit root approval required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"; app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
target='a8a4a83b413d084cabd6442704eda124f302c44d'
branch='feature/hw24-ai-content-v2-api'
live='/var/www/vhosts/hairworld24.de/artikelmanager.HAIRWORLD24.de'
secret='/var/www/vhosts/hairworld24.de/hw24_private'
flag="$secret/hw24-web-research.enabled"
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -w 30 9 || { echo 'STOP: deployment/worker active'; exit 1; }
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='artikelmanager.hairworld24.de'")" == "$live" ]]
[[ -d "$live/api" && ! -L "$live/api" && -d "$secret" && ! -L "$secret" ]]
[[ -f "$secret/openai-review.php" && ! -L "$secret/openai-review.php" ]]
[[ ! -e "$flag" && ! -L "$flag" ]] || { echo 'STOP: manual web flag already exists'; exit 1; }
g fetch --no-tags origin "$branch"
g merge-base --is-ancestor "$target" FETCH_HEAD
backup=$(mktemp -d /root/hw24-ai-api-backup.XXXXXXXX)
files=(ai-review.php ai-reviews.php)
for f in "${files[@]}"; do
    [[ -f "$live/api/$f" && ! -L "$live/api/$f" ]]
    cp -p -- "$live/api/$f" "$backup/$f.old"
    g show "$target:api/$f" > "$backup/$f.new"
    "$php" -l "$backup/$f.new"
done
# Read-only authenticated preflight through the same server-side bridge.
probe(){
 cd "$app"
 runuser -u nicovoss -- "$php" <<'PHP'
<?php
require 'vendor/autoload.php';$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$b=$app->make(App\Services\LegacyReadBridge::class);
$a=$b->fetch('ai.history',['ean'=>'8432225100548','limit'=>1]);
$r=$b->fetch('ai.budget');$d=$r['data']??[];
echo json_encode(['archive_ok'=>$a['ok']??false,'scope'=>$a['data']['scope']??null,
 'budget_ok'=>$r['ok']??false,'enabled'=>$d['enabled']??false,
 'archive_ready'=>$d['archive_ready']??false,'review_versions'=>$d['review_versions']??[],
 'web_research_enabled'=>$d['web_research_enabled']??false,
 'manual_paid_enabled'=>config('hw24_legacy.ai_paid_enabled')===true,
 'usd_budget'=>$d['usd_budget']??null,'usd_month'=>$d['usd_month']??null,
 'daily_limit'=>$d['daily_limit']??null,'web_reserve_usd'=>$d['web_reserve_usd']??null],
 JSON_THROW_ON_ERROR).PHP_EOL;
if(($a['ok']??false)!==true||($r['ok']??false)!==true||($d['enabled']??false)!==true||($d['archive_ready']??false)!==true)exit(2);
PHP
}
echo '=== BEFORE: READ-ONLY API STATUS ==='
probe
rollback(){
  rc=$?
  trap - ERR
  set +e
  for f in "${files[@]}"; do
    cp -p -- "$backup/$f.old" "$live/api/.$f.hw24-rollback"
    mv -fT -- "$live/api/.$f.hw24-rollback" "$live/api/$f"
  done
  rm -f -- "$flag"
  echo "STOP: API deployment failed; originals restored from $backup."
  exit "$rc"
}
trap rollback ERR
for f in "${files[@]}"; do
    stage="$live/api/.$f.hw24-new"
    [[ ! -e "$stage" && ! -L "$stage" ]] || false
    cp -p -- "$backup/$f.old" "$stage"
    cat "$backup/$f.new" > "$stage"
    "$php" -l "$stage"
    mv -fT -- "$stage" "$live/api/$f"
done
# Explicit manual-only flag. Keep existing key and all configured limits unchanged.
printf 'manual-only-v3\n' > "$backup/manual.flag"
install -o root -g psacln -m 640 "$backup/manual.flag" "$flag"
echo '=== AFTER: READ-ONLY API STATUS ==='
result=$(probe)
printf '%s\n' "$result"
printf '%s' "$result" | "$php" -r '$x=json_decode(stream_get_contents(STDIN),true);exit(is_array($x)&&($x["scope"]??null)==="shared_workspace"&&($x["web_research_enabled"]??false)===true&&in_array(3,$x["review_versions"]??[],true)?0:1);'
trap - ERR
echo "=== MANUAL AI API INSTALLED; NO PAID REQUEST ==="
echo "Originals: $backup"
