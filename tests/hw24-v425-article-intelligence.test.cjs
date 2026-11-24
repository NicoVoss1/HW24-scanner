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
const review=fs.readFileSync('api/ai-review.php','utf8');
const archive=fs.readFileSync('api/ai-reviews.php','utf8');
for(const id of [
 'identifier','identityFields','textFields','logisticsFields','priceFields',
 'advancedFields','saveChanges','suggestTimes','amazonSearch','amazonKeywordSearch','amazonResults','amazonKeywordResults',
 'aiReviewRun','aiRecheck','aiReviewResults','ameiseDraftFields','exportAmeiseDraft',
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
assert(archive.includes("'scope'=>'shared_workspace'"),'All authenticated HW24 staff read the same product archive');
assert(archive.includes("FROM hw24_ai_reviews WHERE ean IN ($marks)"),'Shared AI bulk summary must not be author-partitioned');
assert(!archive.includes("WHERE actor=?"),'Archive is shared; original users remain in the append-only audit only');
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


{
 const vm=require('node:vm'),ctx={window:{}};
 vm.runInNewContext(fs.readFileSync('assets/hw24-csv-intake.js','utf8'),ctx);
 const core=ctx.window.HW24CsvCore;
 const valid=s=>/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(s)&&
    [...s].reverse().reduce((n,x,i)=>n+Number(x)*(i%2?3:1),0)%10===0;
 const r=core.prepare([
  'EAN;EAN+000;Lagerplatz NEU;LP ALT;Kommentar',
  '4006381333931;4006381333931;04-001;ALT;',
  '4006381333931;4006381333931;04-002;ALT;',
  '4006381333931;4006381333931;B-Ware;ALT;',
  '4006381333931;4006381333931;04-001;ALT;Datumsnotiz',
  '4006381333931;4006381333931;04-001;SCHROTT;',
  '4006381333931;4006381333931;Mone;ALT;',
  '036000291452;0036000291452;04-003;ALT;',
  '0036000291452;0036000291452;04-003;ALT;',
  '4006381333931;4006381333932;04-001;ALT;'
 ].join('\n'),valid);
 assert.equal(r.inventory,true);
 assert.equal(r.items.length,2);
 assert.equal(r.counts.new,4);
 assert.equal(r.counts.bware,1);
 assert.equal(r.counts.comment,1);
 assert.equal(r.counts.excluded,2);
 assert.equal(r.invalid.length,1);
 assert.equal(r.classified.length,9);
 assert(r.items[0].information.includes('Inventurmenge: 2 Stk'));
 assert(r.items[0].information.includes('04-001: 1 Stk'));
 assert(r.items[0].information.includes('04-002: 1 Stk'));
 assert.equal(r.items[0].lagerplatz,undefined);
 assert.equal(r.items[1].ean,'036000291452');
 assert.equal(r.items[1].lagerplatz,'04-003');
 assert(r.items[1].information.includes('Inventurmenge: 2 Stk'));
 const old=core.prepare('EAN;Information\n4006381333931;A\n4006381333931;B',valid);
 assert.equal(old.items.length,1);assert.equal(old.duplicates.length,1);
 assert.equal(old.items[0].information,'A');
 const none=core.prepare('EAN;Lagerplatz NEU;LP ALT;Kommentar\n4006381333931;SCHROTT;;',valid);
 assert.equal(none.items.length,0);assert.equal(none.classified.length,1);
 console.log('HW24 inventory CSV: classify before aggregation; leading zeros and generic import preserved');
}

{
 const vm=require('node:vm');
 const scan=fs.readFileSync('scanliste.html','utf8');
 const scripts=[...scan.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)];
 for(const match of scripts)if(match[1].trim())new vm.Script(match[1]);
 for(const id of ['fieldBuilder','fieldChoices','fieldsCompact','fieldsAll','exportBuilder','exportChoices','exportCompact','exportAll']){
  assert.equal((scan.match(new RegExp('id="'+id+'"','g'))||[]).length,1);
 }
 const functionText=(name,next)=>{const a=scan.indexOf('function '+name+'('),b=scan.indexOf('function '+next+'(',a);assert(a>=0&&b>a);return scan.slice(a,b);};
 const ctx={schema:['Artikelnummer','GTIN','Artikelname','Hersteller','Warengruppe','Steuerklasse','Kosten','Beschreibung','ASIN'],
   lockedExport:new Set(['GTIN','Artikelname','Hersteller','Warengruppe','Steuerklasse']),
   val:x=>String(x??'').trim()};
 vm.createContext(ctx);
 vm.runInContext(functionText('validatedFieldSelection','readFieldSelection')+functionText('selectedExportColumns','downloadCSV'),ctx);
 ctx.exportFields=ctx.validatedFieldSelection(['Kosten','ASIN','unknown'],ctx.lockedExport);
 const row={Artikelnummer:'TEST',GTIN:'4006381333931',Artikelname:'Test',Hersteller:'Test',Warengruppe:'Test',Steuerklasse:'normaler steuersatz',Kosten:'1,20',Beschreibung:'saved but hidden',ASIN:''};
 const cols=Array.from(ctx.selectedExportColumns([row]));
 assert.deepEqual(cols,['Artikelnummer','GTIN','Artikelname','Hersteller','Warengruppe','Steuerklasse','Kosten']);
 assert.equal(row.Beschreibung,'saved but hidden');
 assert(scan.includes("box.style.display=viewFields.has(control.name)?'':'none'"));
 assert(scan.includes("querySelectorAll('input,textarea,select'))out[control.name]=val(control.value)"));
 assert(scan.includes('applyFieldVisibility();\n refreshFieldGroups();'));
 console.log('HW24 field builder: mandatory schema, hidden field preservation, empty-column filtering and inline JS syntax passed');
}
