'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict');
const scan=fs.readFileSync('scanliste.html','utf8');
const index=fs.readFileSync('index.html','utf8');
const src=fs.readFileSync('assets/hw24-scan-autofill.js','utf8');
const main=fs.readFileSync('assets/hw24-main-enrichment.js','utf8');
const ref=fs.readFileSync('api/jtl-options.php','utf8');
const ai=fs.readFileSync('api/ai-review.php','utf8');
assert(scan.includes('loadJtlReferenceLists()'),'dynamic reference lookup is triggered');
assert(scan.includes('api/jtl-options.php'),'reference endpoint used in scanliste');
for(const field of ['Hersteller','Warengruppe','Lieferstatus','Kategorie Ebene 1','Kategorie Ebene 2','Kategorie Ebene 3'])
 assert(scan.includes("'"+field+"':"),'must have selection for '+field);
assert(scan.includes("control.disabled=disabled&&control.tagName!=='SELECT'"),'existing JTL articles can open dropdowns');
assert(scan.includes("openMain.href='index.html?ean='"),'JTL existing item has one-click deep-link');
assert(scan.includes("confirmedChoice=false"),'explicit confirmed ASIN override supported');
assert(src.includes('confirmedChoice:true'),'ASIN confirmation calls override');
assert(src.includes('if(result.saved)'), 'only show ASIN success after server saved revision');
assert(!src.includes('Zum Ersetzen bitte den bisherigen Wert manuell löschen'),
 'stale ASIN replacement bug removed');
assert(main.includes('asinInput.dispatchEvent(new Event'), 'main article field changes with confirmed ASIN');
assert(index.includes("new URLSearchParams(window.location.search).get('ean')"),
 'main article manager understands scanliste deep-link');
for(const page of [scan,index])assert(page.includes('hw24-ai-cost.js'),'AI costs visible in every view');
for(const name of ['Hersteller','Warengruppe','Lieferstatus'])assert(ref.includes("'"+name+"'"));
assert(ref.includes('source_id=?'),'reference source is fixed to complete snapshot');
assert(ref.includes('scope'), 'reference coverage limitation is explicit');
assert(!/\b(?:INSERT|DELETE|UPDATE)\s+(?:INTO|FROM|hw24)/i.test(ref), 'reference catalog read-only');
assert(ai.includes("hw24_private/openai-review.php"), 'API key lives privately');
assert(ai.includes('api.openai.com/v1/responses'), 'Responses API targeted');
assert(ai.includes('monthly_budget_limit'),'monthly spending bound');
assert(ai.includes('input_tokens_details'),'cached token usage priced separately');
assert(ai.includes('manual_confirmation_required'),'no automatic charge on scan');
assert(!ai.includes('jtl_source_write'), 'no JTL writing');
console.log('HW24 JTL dropdowns, explicit ASIN, deep-link and AI ledger wiring: PASS');
