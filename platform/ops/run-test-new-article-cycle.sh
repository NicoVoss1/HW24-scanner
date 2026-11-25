#!/usr/bin/env bash
# One real article test, scoped to an explicitly selected TEST batch.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 2 && "$1" == '--research-one-test-article' && "$2" =~ ^[1-9][0-9]{0,9}$ ]] || {
    echo 'STOP: explicit root approval and TEST batch ID required'; exit 1;
}
batch="$2"
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
target='1030daefe67338baed959bb0a212764e7575605a'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root --database=hw24_artikelmanager "$@"; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -w 30 9 || { echo 'STOP: deployment or worker active'; exit 1; }
[[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]] ||
    { echo 'STOP: expected TEST release not installed'; exit 1; }
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
# Do not overlap the previous unscoped pilot.
if [[ -e /etc/cron.d/hw24-research-test ]]; then
    remaining=$(cat "$private/research-pilot/remaining")
    deadline=$(cat "$private/research-pilot/deadline")
    [[ "$remaining" =~ ^[0123]$ && "$deadline" =~ ^[0-9]{10,12}$ ]] || {
        echo 'STOP: previous pilot state unknown'; exit 1;
    }
    if [[ "$remaining" != 0 ]] && (( $(date +%s) < deadline )); then
        echo 'STOP: previous research pilot still active'; exit 1
    fi
fi
[[ "$(sql -Ne "SELECT COUNT(*) FROM hw24_scan_batches WHERE id=$batch AND closed_at_utc IS NULL AND label REGEXP '^TEST([[:space:]_-]|$)'")" == 1 ]] ||
    { echo 'STOP: selected batch is not an open TEST batch'; exit 1; }
echo '=== TEST BATCH BEFORE ==='
sql -t -e "
SELECT i.resolution,i.review_state,COUNT(*) AS Positionen,
SUM(i.scan_count) AS Stueck
FROM hw24_scan_items i WHERE i.batch_id=$batch
GROUP BY i.resolution,i.review_state;"
# Exactly one untouched new article; no other pending research in this batch.
candidate=$(sql -Ne "
SELECT i.id FROM hw24_scan_items i
WHERE i.batch_id=$batch AND i.resolution='new_candidate'
AND i.review_state='scan_only' AND i.revision=0
AND (i.draft_json IS NULL OR i.draft_json IN ('','{}','[]'))
LIMIT 2;")
[[ "$candidate" =~ ^[1-9][0-9]*$ ]] ||
    { echo 'NO UNIQUE UNTOUCHED NEW ARTICLE. Scan exactly one real EAN not yet in JTL. No changes.'; exit 0; }
other=$(sql -Ne "
SELECT COUNT(*) FROM hw24_scan_items i
LEFT JOIN hw24_research_jobs j ON j.gtin_key=LPAD(i.ean,14,'0')
WHERE i.batch_id=$batch AND i.id<>$candidate AND i.review_state<>'removed'
AND i.resolution IN ('existing','new_candidate')
AND (j.id IS NULL OR j.status NOT IN ('completed','blocked'));")
[[ "$other" == 0 ]] ||
    { echo 'STOP: other pending research in selected TEST batch; no provider call'; exit 1; }
echo '=== ONE RESEARCH / DRAFT CYCLE ==='
cd "$app"
runuser -u nicovoss -- /usr/bin/timeout 150 "$php" artisan hw24:process-test-batch "$batch" --confirm --research-one --no-interaction
echo '=== NEW ARTICLE RESULT ==='
sql -t -e "
SELECT i.id,i.ean,i.resolution,i.review_state,i.revision,
COALESCE(JSON_LENGTH(i.draft_json),0) AS Entwurfsfelder,
j.status AS Recherche,j.last_error_code AS Fehler
FROM hw24_scan_items i LEFT JOIN hw24_research_jobs j ON j.gtin_key=LPAD(i.ean,14,'0')
WHERE i.batch_id=$batch AND i.id=$candidate;"
echo '=== END OF SINGLE CYCLE; NO PAID AI OR JTL IMPORT ==='
