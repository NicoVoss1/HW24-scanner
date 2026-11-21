'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const {spawnSync}=require('node:child_process');
const hist=fs.readFileSync('api/ai-reviews.php','utf8');
const review=fs.readFileSync('api/ai-review.php','utf8');
const badge=fs.readFileSync('assets/hw24-ai-cost.js','utf8');
const marker=fs.readFileSync('assets/hw24-ai-markers.js','utf8');
const scan=fs.readFileSync('scanliste.html','utf8');
assert(hist.includes("if(array_key_exists('before_id',$_GET))"));
assert(hist.includes('$before=null;'));
assert(hist.includes("AND id<?"));
assert(hist.includes("WHERE actor=? AND ean IN"),'Summary must be actor/EAN scoped');
assert(hist.includes("summary'=>true"));
assert(hist.includes("count($eans)>150"));
assert(review.includes('WHERE actor=? AND ean=? ORDER BY id DESC LIMIT 1'));
assert(review.includes("'reused'=>true"));
assert(review.includes("'usd_call'=>0"));
assert(review.includes("HTTP_X_HW24_RECHECK"));
assert(review.includes("HTTP_X_HW24_PREVIOUS_REVIEW"),'Forced recheck must include expected review revision');
assert(review.includes("review_recheck_conflict"),'Stale recheck must be rejected before billing');
assert(badge.includes("X-HW24-Previous-Review"),'Browser sends expected previous ID');

const pre=review.indexOf('WHERE actor=? AND ean=? ORDER BY id DESC LIMIT 1');
const paid=review.indexOf("curl_init('https://api.openai.com");
assert(pre>=0&&paid>pre,'Server checks EAN BEFORE paid OpenAI');
assert(review.includes('flock($handle,LOCK_EX)'));
assert(badge.includes("lookupStored(payload.ean)"));
assert(badge.includes("X-HW24-Recheck"));
assert(badge.includes('📂 KI geprüft · ansehen'));
assert(badge.includes("archive_ready===true"));
assert(marker.includes('🤖 KI geprüft'));
assert(marker.includes('hw24:scan-items-loaded'));
assert(scan.includes('HW24AiMarkers?.decorate(b,item.ean)'));
assert(scan.includes('hw24:scan-selected'));
assert(scan.includes('hw24-ai-markers.js?v=1'));
for(const js of [badge,marker])assert(js.includes('/^\\d{8,14}$/'),
 'EAN validation must use the actual digit character class');
function apiGet(get){
 const program='$_SERVER=["REQUEST_METHOD"=>"GET","PHP_AUTH_USER"=>"testuser","HTTPS"=>"on"];$_GET='+get+';require "api/ai-reviews.php";';
 const run=spawnSync('php',['-r',program],{encoding:'utf8'});
 assert.equal(run.status,0,run.stderr);return JSON.parse(run.stdout);
}
assert.equal(apiGet('[]').error,'review_storage_unavailable');
assert.equal(apiGet('["before_id"=>""]').error,'invalid_cursor');
assert.equal(apiGet('["before_id"=>"abc"]').error,'invalid_cursor');
assert.equal(apiGet('["summary"=>"1","eans"=>"12a"]').error,'invalid_ean_list');
console.log('AI cursor omission, EAN badges and no-repeat billing contracts: PASS');
