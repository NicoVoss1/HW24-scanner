const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const context={window:{},fetch:()=>{throw Error('Network is disabled in tests')}};
vm.runInNewContext(fs.readFileSync('assets/hw24-enrichment.js','utf8'),context);
const E=context.window.HW24Enrich;
const ean='8033219164159';
assert.equal(E.goodGtin(ean),true);
const title='Dusicolor Creation Mix Kupfer 60 ml';
const newArticle=E.build({ean,isNew:true,curated:{exists:true,fields:{Artikelname:title}}});
assert.equal(newArticle.fields.Artikelnummer,undefined,'No guessed SKU for JTL global auto numbering');
assert.equal(newArticle.fields.HAN,ean,'HAN fallback is a clearly labeled suggestion');
assert.equal(newArticle.fields.Maßeinheit,'ml');
assert.equal(newArticle.fields['Artikelgewicht'],'0,06');
assert.equal(newArticle.fields['Versandgewicht'],'0,08');
assert.equal(newArticle.fields.Warengruppe,'Haarfarben');
assert.equal(newArticle.fields.Währung,'EUR');
const current=E.build({ean,match:{ean,artikelname:title,artikelnummer:'JTL-ART-101',
 netto_vk:'100.00',steuerklasse_name:'normaler Steuersatz',warengruppe_name:'Hair Styling',jtl_source_fields:{
 nBearbeitungszeit:'1',nLiefertageWennAusverkauft:'2',nAutomatischeLiefertageberechnung:'0'
}}});
assert.equal(current.fields.Artikelnummer,'JTL-ART-101');
assert.equal(current.fields.Warengruppe,'Hair Styling','Never overwrite actual JTL source');
assert.match(current.flags.Warengruppe,/Konflikt/);
assert.equal(current.fields['Zusätzliche Bearbeitungszeit'],'1');
assert.equal(current.fields['Beschaffungszeit automatisch ermitteln'],'0');
assert.equal(current.fields['Beschaffungszeit manuell setzen'],'2');
assert.equal(current.fields['Brutto-VK'],'119');
console.log('JTL sku, user weight rules, ml lowercase, gross VK and conflict provenance: PASS');
