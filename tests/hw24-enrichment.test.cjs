/* Contract tests for user-approved HW24 field rules; run with node. */
'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const mock={};
vm.runInNewContext(fs.readFileSync('assets/hw24-enrichment.js','utf8'),{window:mock,fetch:()=>{throw Error('No network in unit tests')}});
const E=mock.HW24Enrich,ean='8033219164159';
assert.equal(E.goodGtin(ean),true);
const found=E.build({ean,match:{
 ean,artikelnummer:'ART-19',artikelname:'Inebrya Color Pastel Roso Intense Pink 100 ml',
 hersteller:'Inebrya',han:'ABC123',warengruppe_name:'Haarfarben',
 netto_vk:'8.50',kosten:'4.20',
 jtl_source_fields:{cAktiv:'Y',cLagerAktiv:'Y',cLagerKleinerNull:'N'}
},isNew:false});
assert.equal(found.fields.Artikelname,'Inebrya Color Pastel Roso Intense Pink 100 ml');
assert.equal(found.fields.Hersteller,'Inebrya');
assert.equal(found.fields.HAN,'ABC123');
assert.equal(found.fields.Warengruppe,'Haarfarben');
assert.equal(found.fields.Kosten,'4.20');
assert.equal(found.fields.Artikelnummer,'ART-19');
assert.equal(found.fields.ASIN,undefined);
assert.equal(found.fields['Neu im Sortiment'],undefined); // no forced rewrite of existing JTL
const source={ok:true,source:'cache',items:[{
 asin:'B012345678',title:'Inebrya Color Pastel Roso Intense Pink 100 ml',brand:'Inebrya',
 identifiers:[{identifiers:[{identifierType:'EAN',identifier:ean}]}]
}]};
const newArticle=E.build({ean,isNew:true,catalog:source});
assert.equal(newArticle.fields.ASIN,'B012345678');
assert.equal(newArticle.fields['Artikelgewicht'],'0,1');
assert.equal(newArticle.fields['Versandgewicht'],'0,12');
assert.equal(newArticle.fields['Inhalt/Menge'],'100');
assert.equal(newArticle.fields.Maßeinheit,'ML');
assert.equal(newArticle.fields['Artikeltyp'],'Normaler Artikel');
assert.equal(newArticle.fields.Währung,'EUR');
assert.equal(newArticle.fields['Überverkäufe möglich'],'N');
assert.equal(newArticle.fields['Bestandsführung aktiv'],'Y');
assert.equal(newArticle.fields['Neu im Sortiment'],'Y');
assert.equal(newArticle.fields['Beschaffungszeit manuell setzen'],'2');
assert.equal(newArticle.fields.HAN,ean); // explicit business-rule fallback, not a standard identity assertion
assert.equal(newArticle.fields['Kategorie Ebene 1'],'Haarfarben');
assert.ok(newArticle.fields['Titel-Tag (SEO)'].includes('HAIRWORLD24'));
assert.ok(newArticle.fields['URL-Pfad'].includes('inebrya'));
assert.ok(newArticle.flags['Versandgewicht'].includes('prüfen'));
const multi={ok:true,source:'cache',items:[
 ...source.items,
 {asin:'B023456789',title:'Inebrya Color Pastel Roso Intense Pink 2 x 100 ml',brand:'Inebrya',
 identifiers:[{identifiers:[{identifierType:'EAN',identifier:ean}]}]}]};
const ambiguous=E.build({ean,isNew:true,catalog:multi});
assert.equal(ambiguous.fields.ASIN,undefined,'Multiple ASINs must not auto-assign');
assert.equal(ambiguous.choices.length,2);
assert.equal(ambiguous.choices.filter(x=>x.pack).length,1);
const singleBundle=E.build({ean,isNew:true,catalog:{items:[multi.items[1]]}});
assert.equal(singleBundle.fields.ASIN,undefined,'Pack offers require manual choice even when alone');
assert.equal(E.sizeFromName('2 x 100 ml').units,2);
const historical={matches:[{
 sheet_name:'Altware',excel_row:7,cells:[
  {header_verified:true,label:'Hersteller',value:'Inebrya'},
  {header_verified:true,label:'ASIN',value:'B111111111'}]},{
 sheet_name:'Altware',excel_row:13,cells:[
  {header_verified:true,label:'Hersteller',value:'Andere Marke'},
  {header_verified:true,label:'ASIN',value:'B222222222'}]}]};
const checked=E.verifiedHistory(historical);
assert.equal(checked.fields.Hersteller,undefined,'Conflicting historic values must not autofill');
assert.equal(checked.asins.length,2);
const fromCurated=E.build({ean,isNew:true,curated:{exists:true,fields:{
 Hersteller:'Inebrya',Artikelname:'Inebrya Color 60 ml'
}}});
assert.equal(fromCurated.fields.Artikelgewicht,'0,06');
assert.equal(fromCurated.fields.Versandgewicht,'0,08');
assert.equal(fromCurated.sources.Hersteller,'HW24 · freigegebene Stammdaten');
console.log('HW24 shared enrichment unit tests: PASS');
