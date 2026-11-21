'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict'),{spawnSync}=require('node:child_process');
const php=fs.readFileSync('api/jtl-quality.php','utf8');
const filters=fs.readFileSync('api/hw24-quality-filters.php','utf8');
const qualityHtml=fs.readFileSync('qualitaetszentrum.html','utf8');
const qualityJs=fs.readFileSync('assets/hw24-quality-center.js','utf8');
const editorHtml=fs.readFileSync('artikelpflege.html','utf8');
const editorJs=fs.readFileSync('assets/hw24-article-editor.js','utf8');
const curated=fs.readFileSync('api/curated.php','utf8');
const filters=fs.readFileSync('api/hw24-quality-filters.php','utf8');
for(const id of ['stockFilter','ekFilter','combineFilter','sortOrder','sortStock','sortEk']){
 assert(qualityHtml.includes('id="'+id+'"'), 'Quality filter missing '+id);
 assert(qualityJs.includes("'"+id+"'"), 'Quality filter not wired '+id);
}
for(const q of ["'positive'","'zero'","'negative'","'missing'","'missing_both'","'or'","'and'","'stock_desc'","'ek_asc'"]){
 assert(php.includes(q),'Server filter/sort missing '+q);
}
assert(filters.includes("$combine==='or'"), 'OR/AND rule missing');
assert(filters.includes('$bPositive<=>$aPositive'),'Positive stock must be prioritized');
assert(php.includes('hw24QualityQuantityMatches($a,$stock,$ek,$combine)'));
assert(php.includes('hw24QualityCompare($a,$b,$sort)'));
assert(qualityJs.includes("artikelpflege.html?ean="),'Quality rows must link to new editor');
for(const id of ['lookupForm','identifier','productView','identityFields','textFields','priceFields','logisticsFields','advancedFields','diffList','saveChanges','suggestReference','confirmHaircolor','referenceFacts']){
 assert(editorHtml.includes('id="'+id+'"'), 'Editor element missing '+id);
 assert(editorJs.includes("'"+id+"'"), 'Editor control not wired '+id);
}
assert(editorJs.includes("const referenceEan='3474630337398'"));
assert(editorJs.includes('if(!$('+"'confirmHaircolor'"+').checked)'), 'Explicit family confirmation required');
assert(editorJs.includes("api/jtl.php?q="),'Editor must fetch JTL from mirror');
assert(editorJs.includes("api/curated.php"),'Editor must use safe curated overlay');
assert(editorJs.includes("X-HW24-Approval"),'Explicit approval required');
assert(editorJs.includes('expected_revision:state.revision'),'Optimistic concurrency mandatory');
for(const k of ['Lieferstatus','Zusätzliche Bearbeitungszeit','Beschaffungszeit manuell setzen','Artikel aktiv','Bestandsführung aktiv','Überverkäufe möglich','Stückzahl teilbar','Neu im Sortiment']){
 assert(curated.includes("'"+k+"'=>"),'Curated overlay whitelist missing '+k);
}
assert(!editorJs.includes('api/jtl-sync.php'),'No SQL Server update by article editor');
const phpCheck=spawnSync('php',['-r',"require 'api/hw24-quality-rules.php';echo (hw24QualityGtinValid('3474630337398')?'valid':'invalid');"],{encoding:'utf8'});
assert.equal(phpCheck.status,0,phpCheck.stderr);
assert.equal(phpCheck.stdout.trim(),'valid');
console.log('HW24 v4.24: independent stock/EK filters, reference EAN, safe curated editor contracts PASS');
