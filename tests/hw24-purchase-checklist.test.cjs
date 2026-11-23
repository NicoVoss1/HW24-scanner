'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict'),vm=require('node:vm');
const read=p=>fs.readFileSync(require('node:path').join(__dirname,'..',p),'utf8');
const html=read('ankauf.html'),api=read('api/purchase-intake.php'),
    domain=read('api/purchase-intake-domain.php'),
    schema=read('platform/ops/hw24-purchase-checklist-v1-manual-migration.sql'),
    scanApi=read('api/scanliste.php'),
    legacy=read('scanliste.html');
const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
assert.equal(ids.length,new Set(ids).size,'duplicate HTML IDs');
assert(ids.length>=20,'expected usable purchase UI controls');
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)];
assert.equal(scripts.length,1,'one inline purchase controller');
new vm.Script(scripts[0][1],{filename:'ankauf-inline.js'});
assert.match(legacy,/href="ankauf\.html"/);
for(const token of ['api/scanliste.php','api/purchase-intake.php','api/amazon.php',
    'api/amazon-archive.php','api/ai-reviews.php','crypto.randomUUID',
    'pendingKey','expected_cost_updated_at','unpriced_quantity','vat_percent',
    'sourceResults','purchaseCode','contactSelect']){
    assert(html.includes(token), 'missing UI contract '+token);
}
for(const token of ['site_authentication_required','https_required',
    'origin_rejected','approval_header_required','scanlist-db.php',
    'purchase_create','price_set','contact_create','purchase_prefix_or_record_duplicate',
    'purchase_price_revision_conflict','FOR UPDATE','created_by=?',
    "new DateTimeZone('Europe/Berlin')",'hw24_scan_batches','hw24_scan_items',
    'hw24_purchase_cost_audit','hw24PurchaseCode','hw24PurchaseLineCents']){
    assert(api.includes(token),'missing server security/data contract '+token);
}
assert(api.includes("require_once __DIR__.'/purchase-intake-domain.php';"));
assert.match(scanApi,/hw24_scan_events/,'existing scan endpoint must deduplicate event keys');
assert.match(scanApi,/client_event_key/);
assert(!/UPDATE\s+dbo\.|INSERT\s+INTO\s+dbo\.|DELETE\s+FROM\s+dbo\./i.test(api),
   'no direct JTL SQL Server writes');
assert(!/openai|api\/ai-review\.php\?/.test(html),'no paid AI action on scan');
assert(!/\binnerHTML\b|\beval\(/.test(html),'no untrusted markup injection');
assert.match(domain,/hw24PurchaseUnitCents/);
const createTables=[...schema.matchAll(/^CREATE TABLE IF NOT EXISTS\s+(\w+)/gm)].map(m=>m[1]);
assert.deepEqual(createTables,[
 'hw24_purchase_contacts','hw24_purchase_sequences','hw24_purchase_headers',
 'hw24_purchase_costs','hw24_purchase_cost_audit',
 'hw24_purchase_amazon_jobs','hw24_purchase_amazon_events','hw24_purchase_amazon_throttle'
]);
for(const s of ['DROP TABLE','ALTER TABLE','DELETE FROM','TRUNCATE TABLE',
                'eazybusiness.','dbo.tlagerbestand']){
 assert(!schema.includes(s),'schema must not operate on existing JTL tables '+s);
}
// Purchasing uses a separate enrichment queue; existing Amazon and AI
// endpoints must remain untouched and user-cost opt-in must stay separate.
for(const token of ['purchaseSearch','manufacturerFilter','jtlFilter',
  'priceMin','priceMax','priceSort','filteredItems','article_name_display',
  'article_number','han','manufacturer_source','queueAmazon','enqueueAll',
  'loadAiCosts','api/purchase-enrichment.php','refreshQueue','queuePost',
  'aiCostRows','queueSummary']){
  assert(html.includes(token),'missing purchase search/queue/audit feature '+token);
}
for(const token of ['jtl_mirror','scan_draft','jtl_mirror',
  'hw24_jtl_articles','jtl_article_id','ai_costs','usd_cost',
  'tokens_input','tokens_cached','tokens_output',
  'same_actor_and_ean_historical']){
  assert(api.includes(token),'missing purchasing API source/cost '+token);
}
console.log('HW24 purchase checklist contracts: passed');
