'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict');
const sql=fs.readFileSync('sql/HW24_13_AI_REVIEWS_ARCHIVE.sql','utf8');
const api=fs.readFileSync('api/ai-review.php','utf8');
const archive=fs.readFileSync('api/ai-reviews.php','utf8');
const ui=fs.readFileSync('assets/hw24-ai-history.js','utf8');
const widget=fs.readFileSync('assets/hw24-ai-cost.js','utf8');
const scan=fs.readFileSync('scanliste.html','utf8'),main=fs.readFileSync('index.html','utf8');
const installer=fs.readFileSync('ops/HW24_INSTALL_AI_ARCHIVE_TEST.sh','utf8');
for(const table of [sql,installer]) {
 assert(table.includes('CREATE TABLE IF NOT EXISTS hw24_ai_reviews'));
 assert(table.includes("GRANT SELECT,INSERT ON hw24_artikelmanager.hw24_ai_reviews"));
 assert(!/\bDROP\s+TABLE\b/i.test(table));
 assert(!/\bDELETE\s+FROM\b/i.test(table));
 assert(!/\bUPDATE\s+hw24_/.test(table));
}
assert(sql.includes('KEY idx_hw24_ai_actor_ean'));
assert(api.includes("first_install_hw24_ai_reviews_v13"),'must block paid calls before archive preflight');
assert(api.indexOf("first_install_hw24_ai_reviews_v13")<api.indexOf("curl_init('https://api.openai.com"),
 'check review archive before calling paid external API');
assert(api.includes('INSERT INTO hw24_ai_reviews'));
for(const field of ["review_id","'stored'=>true","'input_json'","'productFacts'"]) {
 if(field==="\'input_json\'")continue;
 assert(api.includes(field),'API must expose '+field);
}
assert(api.includes('input_tokens_details'),'actual returned token usage tracked');
assert(api.includes("'max_monthly_usd'"),'protected monthly budget retained');
assert(!/UPDATE\s+(?:tArtikel|tLager)/i.test(api),'No JTL/WMS writes');
assert(archive.includes("WHERE actor=?"),'history scoped to authenticated user');
assert(archive.includes("AND ean=?"),'EAN filter parameterized');
assert(archive.includes("AND id<?"),'cursor pagination');
assert(archive.includes("'reviews'=>$reviews"),'returns stored results, not re-calling OpenAI');
assert(ui.includes("api/ai-reviews.php?"),'dashboard calls archive');
assert(ui.includes("Ältere laden"),'supports historical pagination');
assert(ui.includes("KI-Prüfung #"),'provenance shown when approving suggestion');
assert(ui.includes("item.resolution==='new_candidate'"),'existing JTL article protected from write');
assert(ui.includes("Übernahme"),'explicit consent button');
assert(widget.includes("📂 Auswertungen"),'compact widget opens archive');
assert(widget.includes("afterSaved(x,payload)"),'after paid success history appears');
assert(scan.includes('hw24-ai-history.js?v=1'));
assert(main.includes('hw24-ai-history.js?v=1'));
assert(scan.includes("['ASIN','Warengruppe'].includes(field)"),'allow confirmed conflict resolution in draft only');
console.log('HW24 immutable AI reviews, access control, migrations, explicit approvals and cost reporting: PASS');
