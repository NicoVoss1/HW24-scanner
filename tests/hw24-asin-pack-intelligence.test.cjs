'use strict';
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
const sandbox={window:{},fetch:()=>{throw new Error('unit-test has no network')},Date,Number,String,Map,Set,RegExp};
vm.runInNewContext(fs.readFileSync('assets/hw24-enrichment.js','utf8'),sandbox);
const E=sandbox.window.HW24Enrich;
assert(E);
const ean='4056800100835';
const mk=(asin,title,attributes={})=>({
 asin,title,brand:'Wella',
 identifiers:[{identifiers:[{identifierType:'EAN',identifier:ean}]}],attributes
});
const single=mk('B001234567','Wella Color Touch 9/01 1 Stück 60 ml');
const two=mk('B001234568','Wella Color Touch 9/01 2 x 60 ml');
const three=mk('B001234569','Wella Color Touch 9/01 3er Pack 60 ml');
const unknown=mk('B001234570','Wella Color Touch 9/01 60 ml');
const contradictory=mk('B001234571','Wella Color Touch 9/01 1 Stück 60 ml',
  {number_of_items:[{value:2}]});
const mismatch=mk('B001234572','Wella Color Touch 9/03 1 Stück 60 ml');
assert.equal(E.classifyPack(single.title,single).kind,'single');
assert.equal(E.classifyPack(two.title,two).kind,'multipack');
assert.equal(E.classifyPack(two.title,two).units,2);
assert.equal(E.classifyPack(three.title,three).units,3);
assert.equal(E.classifyPack(unknown.title,unknown).kind,'unknown');
assert.equal(E.classifyPack(contradictory.title,contradictory).kind,'conflict');
assert.equal(E.reviewCandidate({
 ...E.amazonChoices({items:[two]},ean)[0]
},{articleName:'Wella Color Touch 9/01 60 ml',brand:'Wella'}).status,'mismatch');
assert.equal(E.reviewCandidate({
 ...E.amazonChoices({items:[mismatch]},ean)[0]
},{articleName:'Wella Color Touch 9/01 60 ml',brand:'Wella'}).status,'mismatch');
const context={ean,isNew:true,curated:{exists:true,fields:{
 Artikelname:'Wella Color Touch 9/01 60 ml',Hersteller:'Wella'
}}};
assert.equal(E.build({...context,catalog:{items:[unknown]}}).fields.ASIN,undefined,
 'Unknown pack must never be silently assumed to be 1 unit');
assert.equal(E.build({...context,catalog:{items:[two]}}).fields.ASIN,undefined);
assert.equal(E.build({...context,catalog:{items:[single,two]}}).fields.ASIN,undefined,
 'multiple ASINs always require explicit choice');
assert.equal(E.build({...context,catalog:{items:[mismatch]}}).fields.ASIN,undefined,
 'Shade conflict may not be auto-selected');
assert.equal(E.build({...context,catalog:{items:[single]}}).fields.ASIN,single.asin,
 'only explicit exact EAN single can be assigned automatically');
assert.equal(E.classifyWarengruppe('Wella Color Touch 9/01 60ml').suggested,'Haarfarben');
assert.equal(E.classifyWarengruppe('Dusicolor Creation Mix Kupfer 60 ml').suggested,'Haarfarben');
assert.equal(E.classifyWarengruppe('Haarspray stark 300 ml').suggested,'Haarstyling');
assert.equal(E.classifyWarengruppe('Color Touch Shampoo').confidence,'conflict');
const conflict=E.build({ean,match:{
 ean,artikelname:'Dusicolor Creation Mix Kupfer 60 ml',
 warengruppe_name:'Hair Styling'
}});
assert.match(conflict.flags.Warengruppe,/Warengruppenkonflikt/);
assert.equal(conflict.fields.Warengruppe,'Hair Styling',
  'Warn but do not overwrite current JTL group');
const ids=E.amazonChoices({items:[single]},ean);
assert.equal(ids[0].url,'https://www.amazon.de/dp/B001234567');
assert.equal(ids[0].packKind,'single');
// Simulate DOM to verify there is a real independent Amazon link for each ASIN.
class Node {
 constructor(tag){this.tag=tag;this.children=[];this.textContent='';}
 append(x){this.children.push(x)}
 replaceChildren(){this.children=[]}
}
const dom={createElement:x=>new Node(x)};
sandbox.document=dom;
const list=new Node('div');E.renderCandidates(list,ids,'',()=>{},{articleName:context.curated.fields.Artikelname,brand:'Wella'});
const link=list.children.flatMap(el=>el.children||[])
 .find(el=>el.tag==='a');
assert(link,'ASIN link should render');
assert.equal(link.href,ids[0].url);
assert.equal(link.target,'_blank');
assert.equal(link.rel,'noopener noreferrer');
console.log('HW24 ASIN links, 1/2/3-pack classification, shade checks and category conflict: PASS');
