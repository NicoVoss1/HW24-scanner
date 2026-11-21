'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict');
const html=fs.readFileSync('artikelpflege.html','utf8');
const js=fs.readFileSync('assets/hw24-article-editor.js','utf8');
const quality=fs.readFileSync('qualitaetszentrum.html','utf8');
const qualityJs=fs.readFileSync('assets/hw24-quality-center.js','utf8');
const php=fs.readFileSync('api/curated.php','utf8');
const audit=fs.readFileSync('api/jtl-quality.php','utf8');
const prior=fs.readFileSync('index.html','utf8');
for(const id of ['identifier','identityFields','textFields','logisticsFields','priceFields',
 'advancedFields','diffList','referenceFacts','referenceStatus','saveChanges','discardChanges']){
 assert(html.includes('id="'+id+'"'),'editor missing '+id);
}
assert(html.includes('3474630337398'),'reference EAN visible');
assert(js.includes("const referenceEan='3474630337398'"));
assert(js.includes("fetch('api/jtl.php?q='+referenceEan"),'template fetched from real JTL');
assert(js.includes('state.reference=a;'),'reference from actual matched JTL item');
assert(js.includes("matches.length!==1"),'reference ambiguity must fail closed');
assert(js.includes('api/curated.php?ean='),'existing overlay read');
assert(js.includes("method:'POST'"),'explicit save uses POST');
assert(js.includes("'X-HW24-Approval':'confirmed'"),'explicit approval');
assert(js.includes("expected_revision:state.revision"),'revision-safe save');
assert(js.includes("Keine Kopie")===false,'no uncontrolled copying within JS');
assert(js.includes("val(entry.control.value)||!val(value)"),'template only fills blank fields with verified values');
assert(js.includes("!val(value)"),'source template fields must exist');
assert(js.includes('state.sourceFresh=false'),'stale snapshots may not write');
assert(js.includes("getMassCode"),'unit mapping uses verified JTL source');
assert(js.includes('verifiedRelatedValue'),'delivery names only from verified related data');
assert(js.includes("!gtinGood(val(article.ean))"),'missing GTINs must remain read only');
assert(js.includes("loadArticle(initial)"),'deep link from quality center works');
for(const id of ['stockFilter','ekFilter','combineFilter','sortOrder','sortStock','sortEk'])
 assert(quality.includes('id="'+id+'"'),'quality control missing '+id);
assert(quality.includes('Bestand größer 0'));
assert(quality.includes('Netto-EK: aufsteigend'));
assert(qualityJs.includes("sort:state.sort"));
assert(qualityJs.includes("stock:state.stock,ek:state.ek,combine:state.combine"));
assert(qualityJs.includes("artikelpflege.html?ean="));
assert(qualityJs.includes("artikelpflege.html?q="));
assert(audit.includes("hw24QualityQuantityMatches($a,$stock,$ek,$combine)"));
assert(audit.includes("hw24QualityCompare($a,$b,$sort)"));
for(const f of ['Lieferstatus','Zusätzliche Bearbeitungszeit',
 'Beschaffungszeit manuell setzen','Beschaffungszeit automatisch ermitteln',
 'Artikel aktiv','Bestandsführung aktiv']){
 assert(php.includes("'"+f+"'"),'curated overlay field not allowed: '+f);
}
assert(php.includes("FROM hw24_curated_articles"),'overlay storage only');
assert(!/UPDATE\s+(?:dbo\.tArtikel|tArtikel|tLager)/i.test(php),'never write directly to JTL');
assert(prior.includes('href="artikelpflege.html"'),'classic page has direct link to clean editor');
console.log('HW24 V4.24 clean JTL editor, UI filters, template guards and overlay persistence: PASS');
