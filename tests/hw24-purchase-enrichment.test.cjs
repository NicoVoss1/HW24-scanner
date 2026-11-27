'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const read=p=>fs.readFileSync(path.join(__dirname,'..',p),'utf8');
const api=read('api/purchase-enrichment.php');
const purchase=read('api/purchase-intake.php');
const html=read('ankauf.html');
const sql=read('platform/ops/hw24-purchase-checklist-v1-manual-migration.sql');
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)];
assert.equal(scripts.length,1);
new vm.Script(scripts[0][1],{filename:'ankauf.html-inline.js'});
for(const token of [
 'site_authentication_required','https_required','origin_rejected',
 'approval_header_required','scanlist-db.php','created_by',
 'enqueueOwnedPurchase','enqueue_batch','attempt_count<4',
 'lease_until_utc','UTC_TIMESTAMP(6)','rate_limited',
 'hw24_purchase_amazon_throttle','hw24_purchase_amazon_jobs',
 'hw24_purchase_amazon_events','FOR UPDATE',
 'purchase_not_found_or_closed','scan_item_not_found',
 'catalog_snapshot_not_verified','hw24_amazon_catalog_snapshots',
 'amazon_rate_limited','completed','blocked','requeue',
 'event_actor','last_catalog_at_utc','requested_by',
 'source_kind','min(3600','limit 100'
]){
 const caseSensitive=token==='limit 100';
 assert((caseSensitive?api.toLowerCase():api).includes(token),'missing security/queue contract '+token);
}
assert(api.includes('LIMIT 100'),'each bulk request must be bounded');
assert(api.includes('LIMIT 3000'),'queue list must be bounded');
assert(api.includes("enqueueEvent($db"),'provenance events required');
assert(api.includes("AND searched_ean=? AND marketplace_id=? AND response_status=200"),
 'a client cannot mark success without the actual stored catalog snapshot');
assert(!/\b(?:DROP|TRUNCATE|ALTER)\b\s+TABLE/i.test(sql),'no destructive schema changes');
assert(!/\b(?:INSERT|UPDATE|DELETE)\b\s+(?:INTO\s+|FROM\s+)?(?:dbo\.|eazybusiness\.)/i.test(api),
 'no JTL SQL Server writes');
assert(!api.includes('openai.com')&&!api.includes('sellingpartnerapi-eu.amazon.com'),
 'queue must reuse approved legacy catalog API, not silently add credentials or direct API calls');
assert(!/\binnerHTML\b|\beval\(/.test(html),'no untrusted DOM HTML rendering');
for(const token of ['refreshQueue','queueMessage','queueGet','queuePost','runAmazon',
 'enqueueAll','loadAiCosts','api/amazon.php?ean=','api/ai-reviews.php?ean=',
 'api/amazon-archive.php?ean=','api/historie.php?include_legacy=1',
 'getJson(','workAudit','usd_cost','tokens_cached']){
 assert(html.includes(token),'missing UI source/cost integration '+token);
}
assert(purchase.includes("if($op==='ai_costs'"),'per-purchase actual recorded OpenAI costs required');
assert(sql.includes('UNIQUE KEY uq_purchase_amazon_workspace_ean'),'one deduplicated Amazon queue per EAN across all HW24 users');
console.log('HW24 procurement audit/queue contracts: passed');
