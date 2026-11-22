'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const core=require('../assets/hw24-source-core.js');
const html=fs.readFileSync('artikelpflege.html','utf8');
const ui=fs.readFileSync('assets/hw24-source-explorer.js','utf8');
const api=fs.readFileSync('api/jtl-fields.php','utf8');

assert.equal(core.priceText('11.76470588',4),'11,7647');
assert.equal(core.priceText('14',2),'14,00');
assert.equal(core.priceText('bogus'),'Nicht verfügbar');
const rows=core.channelRows({
 jtl_net:'11.76470588',jtl_net_source:'dbo.tArtikel.fVKNetto',
 jtl_gross:null,
 amazon_jtl_value:'14',amazon_jtl_source:'dbo.tArtikel.fAmazonVK',
 ebay_jtl_value:'11',ebay_jtl_source:'dbo.tArtikel.fEbayPreis'
});
assert.equal(rows.length,8,'JTL net/gross, Amazon, generic eBay, two eBay accounts, two Shopify views');
assert.equal(rows[0].price,'11,7647');
assert.equal(rows[1].price,'Nicht verfügbar','Never invent standard gross');
assert.equal(rows[2].price,'14,00');
assert.equal(rows[2].meaning,'Preisart nicht verifiziert','Amazon JTL value is NOT proven live offer');
assert.equal(rows[3].price,'11,00');
const ebayAccounts=rows.filter(r=>r.channel==='eBay'&&r.account!=='Artikelstamm (nicht kontogebunden)');
assert.deepEqual(ebayAccounts.map(x=>x.account),['www.serverler24.shop','friseurbedarf24.shop']);
assert(ebayAccounts.every(x=>x.price==='Nicht verfügbar'),'Never copy generic eBay price to accounts');
assert(rows.slice(-2).every(x=>x.price==='Nicht verfügbar'),'Do not fabricate Shopify live prices');
const known=[
 {source:'dbo.tArtikel',field:'kSteuerklasse',value:'1'},
 {source:'dbo.tArtikel',field:'fMassMenge',value:'90'},
 {source:'JTL.Zusatzquelle.steuerklasse',field:'cName',value:'Normaler Steuersatz',row:1}
];
assert.equal(core.filteredFields(known,'steuer', 'all').length,2);
assert.equal(core.filteredFields(known,'90','dbo.tArtikel').length,1);
assert.equal(core.filteredFields(known,'', 'JTL.Zusatzquelle.steuerklasse').length,1);
assert.deepEqual(core.availableGroups(known),['dbo.tArtikel','JTL.Zusatzquelle.steuerklasse']);
for(const id of ['jtlSourceExplorer','jtlSourceStatus','channelPriceBoard','jtlSourceQuery',
 'jtlSourceGroup','jtlSourceCount','jtlSourceFields']){
 assert.equal((html.match(new RegExp('id="'+id+'"','g'))||[]).length,1,'Missing/duplicate '+id);
}
assert(html.includes('assets/hw24-source-core.js?v=1'));
assert(html.includes('assets/hw24-source-explorer.js?v=1'));
assert(ui.includes('hw24:article-loaded'),'New article loading event');
assert(ui.includes('hw24:article-unloaded'),'Must clear stale article fields');
assert(ui.includes("textContent=String(value)"),'HTML must use textContent, never unsafe HTML');
assert(ui.includes("article_id:state.articleId,ean:state.ean"),'Lookup requires exact JTL identity');
assert(api.includes("PHP_AUTH_USER"),'Authenticated source lookup');
assert(api.includes("'https_required'"),'TLS-gated source lookup');
assert(api.includes("WHERE status='ready'"),'Only completed JTL snapshot');
assert(api.includes("WHERE source_id=? AND jtl_article_id=? AND ean=? LIMIT 1"),'Exact article identity');
assert(api.includes("beschreibungen_json"),'Keep all mirrored descriptions');
assert(api.includes("'HW24_RelatedSources'"),'Show verified related source data');
assert(api.includes('is_scalar'),'No arbitrary object output');
assert(api.includes('allowedField'),'No password/token fields returned');
assert(api.includes("'jtl_gross_source'"),'Gross price must have a source');
assert(!/\b(?:INSERT\s+INTO|UPDATE\s+hw24_jtl|DELETE\s+FROM|DROP\s+TABLE|ALTER\s+TABLE)\b/i.test(
 api.replace(/\/\*[\s\S]*?\*\//g,'').replace(/\/\/[^\n]*/g,'')),
 'Source endpoint must never write SQL');
console.log('HW24 source explorer: 8 separate channel rows, provenance, filtered fields, auth and read-only contract PASS');
