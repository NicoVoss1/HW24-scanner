'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const html=fs.readFileSync('artikelpflege.html','utf8');
const editor=fs.readFileSync('assets/hw24-article-editor.js','utf8');
const intel=fs.readFileSync('assets/hw24-article-intelligence.js','utf8');
const jtl=fs.readFileSync('api/jtl.php','utf8');
const options=fs.readFileSync('api/jtl-options.php','utf8');
const curated=fs.readFileSync('api/curated.php','utf8');
const amazon=fs.readFileSync('api/amazon.php','utf8');
const amazonAsin=fs.readFileSync('api/amazon-asin.php','utf8');
const review=fs.readFileSync('api/ai-review.php','utf8');
const archive=fs.readFileSync('api/ai-reviews.php','utf8');
for(const id of [
 'identifier','identityFields','textFields','logisticsFields','priceFields',
 'advancedFields','saveChanges','suggestTimes','amazonSearch','amazonKeywordSearch','amazonResults','amazonKeywordResults',
 'aiReviewRun','aiRecheck','aiReviewResults','ameiseDraftFields','exportAmeiseDraft','amazonAsinDirect',
 'confirmHaircolor','referenceFacts','articleTypeValues','shippingValues',
 'taxValues','currencyValues'
]){
 assert.equal((html.match(new RegExp('id="'+id+'"','g'))||[]).length,1, 'Missing or duplicate element '+id);
}
assert(html.includes('assets/hw24-article-intelligence.js?v=1'));
assert(html.includes('id="amazonSearch"'),'Amazon UI must be visible from new editor');
assert(html.includes('<details class="sidebar-panel intel-panel">'),'Manual source tools stay collapsed');
assert(!editor.includes("['Lieferant','Lieferant'"),'Supplier forms intentionally removed');
assert(!editor.includes("['Artikelnummer (Lieferant)'"),'Supplier number not needed yet');
for(const [k,type] of [['Artikeltyp','options:articleTypeValues'],
 ['Steuerklasse','options:taxValues'],['Versandklasse','options:shippingValues'],
 ['Währung','options:currencyValues']])assert(editor.includes("['"+k+"','"+k+"','"+type+"'"),
  k+' should be an actual selection instead of freeform text');
assert(editor.includes("'Brutto-VK':a.brutto_vk"),'Show only verified gross from snapshot');
assert(jtl.includes("raw['HW24_VKBrutto']"),'Verified-gross optional Windows-sync field');
assert(jtl.includes("HW24_VKBruttoQuelle"),'Provenance required for actual gross');
assert(!jtl.includes('netto_vk*1.19'),'Never fabricate JTL stored gross VK');
assert(editor.includes("'Beschaffungszeit automatisch ermitteln':'0'"));
assert(editor.includes("'Beschaffungszeit manuell setzen':'1'"));
assert(editor.includes("'Zusätzliche Bearbeitungszeit':'1'"));
assert(editor.includes("if(!$('confirmHaircolor').checked)"),'Only propose template to verified haircolor');
assert(editor.includes("hw24:article-loaded"),'Article-load event for source integrations');
assert(editor.includes("hw24:article-unloaded"),'Disconnect previous source panels on new article');
assert(intel.includes("api/amazon.php?ean="),'Amazon existing SP endpoint');
assert(intel.includes("api/amazon-pricing.php?asin="),'On-demand Amazon pricing by ASIN');
assert(intel.includes("api/amazon-keywords.php?q="),'Optional keyword fallback');
assert(intel.includes("item.gtins.includes(state.ean)"),'Do not suggest ASIN from keyword search without GTIN evidence');
assert(amazon.includes("'marketplace_id'")||amazon.includes('marketplace_id'),'Existing Amazon snapshot persistence');
assert(intel.includes("api/ai-reviews.php?ean="),'Free archive lookup on article load');
assert(intel.includes("api/ai-review.php"),'Manual OpenAI review available');
assert(intel.includes("'X-HW24-Previous-Review'"),'Rechecks use expected review id');
assert(intel.includes("'X-HW24-Approval':'confirmed'"),'Paid review requires approval');
assert(review.includes("HTTP_X_HW24_PREVIOUS_REVIEW"),'Server protects repeat payment');
assert(archive.includes('actor IN ($actorMarks)')&&archive.includes('hw24ReadableOwners($actor)'),
 'Archive must remain scoped to the authenticated user and explicitly approved legacy owners');
assert(intel.includes('api/curated.php?ean='),'Ameise only loads approved drafts');
assert(intel.includes("expected")||intel.includes('approved.revision!==staged.revision'),
 'Must reject stale approved draft before CSV');
assert(intel.includes("jtl_status!=='current_snapshot'"),'Export requires a fresh JTL identity match');
assert(intel.includes("'Artikelnummer'"),'Export uniquely keys JTL article');
assert(intel.includes('blob=new Blob'),'Only a local proof CSV, not a JTL write');
assert(!intel.includes('/api/jtl-sync.php'),'No JTL SQL writes');
assert(options.includes("'Versandklasse'")&&options.includes("'Steuerklasse'"),'Observed real dropdowns');
assert(curated.includes("expected_revision"),'Curated revision safety preserved');
console.log('HW24 V4.25: Amazon/KI source panels, source-based gross price, dropdowns, reviewed Ameise CSV PASS');
