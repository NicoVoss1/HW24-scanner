#!/usr/bin/env bash
# One no-paid, no-new-provider smoke test on one unambiguously eligible TEST batch.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--check-test-cycle' ]] || { echo 'STOP: explicit root invocation required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
target='1030daefe67338baed959bb0a212764e7575605a'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
sql(){ mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root --database=hw24_artikelmanager "$@"; }
exec 9>/run/lock/hw24-test-price-archive-deploy.lock
flock -w 30 9 || { echo 'STOP: another worker or deployment active'; exit 1; }
[[ "$(g rev-parse HEAD)" == "$target" && -z "$(g status --porcelain --untracked-files=no)" ]] || { echo 'STOP: TEST release not yet confirmed'; exit 1; }
[[ "$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE d.name='test-artikelmanager.hairworld24.de'")" == "$app/public" ]]
echo '=== OPEN TEST BATCHES AND RESEARCH ==='
sql -t -e "
SELECT b.id AS Stapel,b.label AS Bezeichnung,
COUNT(i.id) AS Positionen,
COALESCE(SUM(i.scan_count),0) AS Stueck,
COALESCE(SUM(i.resolution='new_candidate' AND i.review_state='scan_only' AND i.revision=0),0) AS Unbearbeitet,
COALESCE(SUM(i.resolution='new_candidate' AND i.review_state='scan_only' AND i.revision=0 AND j.status='completed'),0) AS Recherche_fertig
FROM hw24_scan_batches b
LEFT JOIN hw24_scan_items i ON i.batch_id=b.id AND i.review_state<>'removed'
LEFT JOIN hw24_research_jobs j ON j.gtin_key=LPAD(i.ean,14,'0')
WHERE b.closed_at_utc IS NULL AND b.label REGEXP '^TEST([[:space:]_-]|$)'
GROUP BY b.id,b.label ORDER BY b.id;"
echo '=== RESEARCH QUEUE ==='
sql -t -e "SELECT status,COUNT(*) AS Anzahl FROM hw24_research_jobs GROUP BY status;"
# Only a single eligible TEST batch may be selected automatically; no new research.
ids=$(sql -Ne "
SELECT DISTINCT b.id FROM hw24_scan_batches b JOIN hw24_scan_items i ON i.batch_id=b.id
JOIN hw24_research_jobs j ON j.gtin_key=LPAD(i.ean,14,'0')
WHERE b.closed_at_utc IS NULL AND b.label REGEXP '^TEST([[:space:]_-]|$)'
AND i.resolution='new_candidate' AND i.review_state='scan_only' AND i.revision=0
AND j.status='completed'
AND JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(j.evidence_json),j.evidence_json,'{}'),'$.jtl_state'))='new_candidate'
ORDER BY b.id LIMIT 2;")
if [[ ! "$ids" =~ ^[1-9][0-9]*$ ]]; then
 echo 'NO UNIQUE TEST BATCH WITH COMPLETED NEW-ARTICLE RESEARCH; no writes or provider calls.'
 exit 0
fi
echo "=== DRAFT ONLY SMOKE TEST: BATCH $ids ==="
cd "$app"
runuser -u nicovoss -- /usr/bin/timeout 150 "$php" artisan hw24:process-test-batch "$ids" --confirm --no-interaction
echo '=== SAVED DRAFT COUNTS ==='
sql -t -e "SELECT review_state,COUNT(*) AS Anzahl FROM hw24_scan_items WHERE batch_id=$ids GROUP BY review_state;"
