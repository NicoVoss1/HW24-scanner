/* HW24 V4.24 · focused JTL article editor.
 * Existing article lookup is read-only. User-approved field edits are saved
 * ONLY to the existing HW24 curated overlay, never to JTL or WMS.
 * Reference EAN 3474630337398 is resolved from JTL when the page is opened.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id);
const val=x=>String(x??'').trim();
const referenceEan='3474630337398';
const fieldSets={
 identity:[
  ['Artikelname','Artikelbezeichnung','text','wide'],
  ['Hersteller','Hersteller / Marke','list:manufacturerValues'],
  ['HAN','Herstellerartikelnummer (HAN)','text'],
  ['Warengruppe','Warengruppe','list:groupValues'],
  ['Inhalt/Menge','Füllmenge','text'],
  ['Maßeinheit','Maßeinheit','unit'],
  ['Artikeltyp','Artikeltyp','text'],
  ['ASIN','Amazon ASIN','text']
 ],
 texts:[
  ['Kurzbeschreibung','Kurzbeschreibung','textarea','wide'],
  ['Beschreibung','Ausführliche Beschreibung','longtext','wide']
 ],
 logistics:[
  ['Lieferstatus','Lieferstatus','list:statusValues'],
  ['Zusätzliche Bearbeitungszeit','Bearbeitungszeit (Tage)','text'],
  ['Beschaffungszeit manuell setzen','Manuelle Beschaffungszeit (Tage)','text'],
  ['Beschaffungszeit automatisch ermitteln','Beschaffung automatisch ermitteln','yesno01'],
  ['Versandklasse','Versandklasse','text']
 ],
 prices:[
  ['Netto-EK','Einkaufspreis netto (€)','decimal'],
  ['Kosten','Artikelkosten (€)','decimal'],
  ['Netto-VK','Verkaufspreis netto (€)','decimal'],
  ['Brutto-VK','Verkaufspreis brutto (€)','decimal']
 ],
 advanced:[
  ['Lieferant','Lieferant','text'],
  ['Artikelnummer (Lieferant)','Lieferantenartikelnummer','text'],
  ['Kategorie Ebene 1','Kategorie Ebene 1','list:categoryValues'],
  ['Kategorie Ebene 2','Kategorie Ebene 2','list:categoryValues'],
  ['Kategorie Ebene 3','Kategorie Ebene 3','list:categoryValues'],
  ['Steuerklasse','Steuerklasse','text'],
  ['Währung','Währung','text']
 ]
};
const editable=new Set(Object.values(fieldSets).flatMap(group=>group.map(f=>f[0])));
const state={jtl:null,article:null,approved:{},revision:0,controls:new Map(),
 initial:new Map(),reference:null,latestRequest:0,saving:false,sourceFresh:false};
const money=x=>x===null||x===undefined||val(x)===''?'Nicht belegt':
 Number.isFinite(Number(x))?Number(x).toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2})+' €':'Nicht lesbar';
const gtinGood=ean=>{
 if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(ean)||/^0+$/.test(ean))return false;
 let sum=0,w=3;
 for(let i=ean.length-2;i>=0;i--){sum+=Number(ean[i])*w;w=w===3?1:3}
 return (10-sum%10)%10===Number(ean[ean.length-1]);
};
function el(tag,parent,text,className){
 const n=document.createElement(tag);
 if(text!==undefined&&text!==null)n.textContent=String(text);
 if(className)n.className=className;
 parent?.append(n);return n;
}
function status(text,kind=''){
 const out=$('editorStatus');out.textContent=text;out.className='status-line'+(kind?' '+kind:'');
}
function original(key,a){
 if(!a)return '';
 const f=a.jtl_source_fields||{};
 const source={
  'Artikelname':a.artikelname,'Hersteller':a.hersteller,'HAN':a.han,
  'Warengruppe':a.warengruppe_name,
  'Kurzbeschreibung':a.kurzbeschreibung,'Beschreibung':a.beschreibung,
  'Netto-EK':a.netto_ek,'Netto-VK':a.netto_vk,'Kosten':a.kosten,
  'Inhalt/Menge':f.fMassMenge,'Artikeltyp':null,
  'ASIN':a.asin,'Maßeinheit':getMassCode(a),
  'Versandklasse':a.versandklasse_name,'Steuerklasse':a.steuerklasse_name,
  'Lieferstatus':verifiedRelatedValue(a,'lieferstatus','cName'),
  'Zusätzliche Bearbeitungszeit':f.nBearbeitungszeit,
  'Beschaffungszeit manuell setzen':f.nLiefertageWennAusverkauft,
  'Beschaffungszeit automatisch ermitteln':f.nAutomatischeLiefertageberechnung
 };
 return source[key]===undefined||source[key]===null?'':String(source[key]);
}
function verifiedRelatedValue(a,source,column){
 const rel=a?.related_sources?.[source];
 if(rel?.truncated||!Array.isArray(rel?.rows)||rel.rows.length!==1)return '';
 return val(rel.rows[0]?.[column]);
}
function getMassCode(a){
 const rel=a.related_sources?.masseinheit;
 if(rel?.truncated||!Array.isArray(rel?.rows)||rel.rows.length!==1)return '';
 const v=rel.rows[0];return val(v?.cDisplayCode||v?.cCode||v?.cUnit);
}
function fact(parent,label,value){
 const row=el('div',parent,null,'fact-row');
 el('span',row,label);el('strong',row,val(value)||'Nicht belegt');
}
function logistics(a){
 const root=$('currentLogistics');root.replaceChildren();
 const f=a?.jtl_source_fields||{};
 for(const [caption,v] of [
  ['Lieferstatus (JTL-ID)',f.kLieferStatus],
  ['Bearbeitungszeit (JTL)',f.nBearbeitungszeit],
  ['Beschaffungszeit manuell (JTL)',f.nLiefertageWennAusverkauft],
  ['Beschaffungszeit automatisch (JTL)',f.nAutomatischeLiefertageberechnung],
  ['Artikel aktiv (JTL)',f.cAktiv],
  ['Bestandsführung (JTL)',f.cLagerAktiv],
  ['Überverkäufe möglich (JTL)',f.cLagerKleinerNull],
  ['Stückzahl teilbar (JTL)',f.cTeilbar],
  ['Versandklasse (JTL)',a.versandklasse_name||f.kVersandklasse],
  ['Steuerklasse (JTL)',a.steuerklasse_name||f.kSteuerklasse]
 ])fact(root,caption,v===null||v===undefined?'':v);
}
function createField(target,definition){
 const [key,label,type,layout]=definition;
 const old=original(key,state.article);
 const approved=Object.prototype.hasOwnProperty.call(state.approved,key)?
   val(state.approved[key]):null;
 const current=approved!==null?approved:val(old);
 const box=el('div',target,null,'editor-field'+(layout==='wide'?' wide':'')+(current?'':' missing'));
 const tag=el('label',box,label);
 const id='field_'+key.replace(/[^a-zA-Z0-9]/g,'_');
 tag.htmlFor=id;
 let control;
 if(type==='unit'||type==='yesno01'){
   control=el('select',box);
   const choices=type==='yesno01'?
    [['','Bitte auswählen'],['0','Nein (0)'],['1','Ja (1)']]:
    [['','Bitte auswählen'],...['ml','g','l','Stück','kg','cm'].map(v=>[v,v])];
   for(const [v,label] of choices){
    const option=el('option',control,label);option.value=v;
   }
   if(current&&!Array.from(control.options).some(o=>o.value===current)){
    const extra=el('option',control,current);extra.value=current;
   }
 }else if(type==='textarea'||type==='longtext'){
   control=el('textarea',box);
   control.rows=type==='longtext'?6:3;
   if(type==='longtext')control.className='large';
 }else{
   control=el('input',box);
   control.type='text';
   if(type.startsWith('list:'))control.setAttribute('list',type.slice(5));
   if(type==='decimal'){control.inputMode='decimal';control.placeholder='z. B. 4,25';}
 }
 control.id=id;
 control.value=current;
 control.maxLength=key==='Beschreibung'?12000:key==='Kurzbeschreibung'?3500:400;
 control.disabled=!state.sourceFresh||!gtinGood(val(state.article?.ean));
 const hint=el('small',box,null,'source');
 const origin=val(old);
 const provenance=approved!==null?'HW24 freigegebener Entwurf · JTL: '+(origin||'leer'):
   (origin?'Aus JTL-Spiegel':'In JTL nicht belegt');
 hint.textContent=provenance;
 if(approved!==null)hint.classList.add('edited');
 if(!current)hint.classList.add('missing-label');
 state.controls.set(key,{control,box,hint,old:origin});
 state.initial.set(key,current);
 control.addEventListener('input',()=>{box.classList.toggle('missing',!val(control.value));renderChanges()});
 control.addEventListener('change',renderChanges);
}
function changed(){
 const result={};
 for(const [key,entry] of state.controls){
  const now=val(entry.control.value),previous=state.initial.get(key);
  if(now!==previous)result[key]={before:previous,after:now};
 }
 return result;
}
function renderChanges(){
 const differences=changed();
 const root=$('diffList');root.replaceChildren();
 const names=Object.keys(differences);
 if(!names.length)el('p',root,'Noch keine Änderungen.','muted');
 else {
  const table=el('table',root),head=el('tr',el('thead',table));
  for(const x of ['Feld','Bisher','Vorschlag'])el('th',head,x);
  const tbody=el('tbody',table);
  for(const key of names){
   const row=el('tr',tbody);
   el('td',row,key);el('td',row,differences[key].before||'Nicht belegt');
   el('td',row,differences[key].after||'Leer, Speichern nicht erlaubt');
  }
 }
 const invalidClear=names.some(k=>!differences[k].after);
 const allowed=state.article&&state.sourceFresh&&gtinGood(val(state.article.ean));
 $('saveChanges').disabled=state.saving||!allowed||!names.length||invalidClear;
 $('discardChanges').disabled=state.saving||!names.length;
 if(invalidClear)status('Leere Werte können über den vorhandenen sicheren HW24-Entwurf derzeit nicht gespeichert werden. Bitte korrigieren oder verwerfen.','error');
}
function render(){
 state.controls.clear();state.initial.clear();
 for(const [group,defs] of Object.entries(fieldSets)){
  const parent=$(({identity:'identityFields',texts:'textFields',logistics:'logisticsFields',prices:'priceFields',advanced:'advancedFields'})[group]);
  parent.replaceChildren();
  for(const item of defs)createField(parent,item);
 }
 const a=state.article;
 $('articleHeading').textContent=val(a.artikelname)||'(Ohne Artikelname)';
 $('articleIdentifiers').textContent='EAN '+(a.ean||'fehlt')+' · JTL-Art.-Nr. '+(a.artikelnummer||'fehlt')+
   ' · HAN '+(a.han||'fehlt')+' · Hersteller '+(a.hersteller||'fehlt');
 $('stockValue').textContent=a.bestand===null?'Nicht belegt':Number(a.bestand).toLocaleString('de-DE');
 $('jtlEkValue').textContent=money(a.netto_ek);
 $('revisionValue').textContent='Rev. '+state.revision;
 logistics(a);renderChanges();
 const expert=$('expertLink');
 expert.href='index.html?'+(gtinGood(val(a.ean))?'ean='+encodeURIComponent(a.ean):
   'q='+encodeURIComponent(a.artikelnummer||''));
}
async function loadArticle(query){
 const request=++state.latestRequest;
 $('productView').hidden=true;
 state.article=null;state.sourceFresh=false;
 status('JTL-Artikel und vorhandenen HW24-Entwurf werden geladen …');
 try{
  const res=await fetch('api/jtl.php?q='+encodeURIComponent(query),{cache:'no-store',credentials:'same-origin'});
  const data=await res.json();
  if(request!==state.latestRequest)return;
  if(!res.ok||!data.ok||data.jtl_status!=='current_snapshot')
   throw Error('JTL-Synchronisation nicht aktuell ('+(data.jtl_status||data.error||res.status)+'). Keine ungesicherte Bearbeitung.');
  const matches=Array.isArray(data.matches)?data.matches:[];
  if(matches.length!==1){
   if(!matches.length)throw Error('Artikel in JTL nicht gefunden. Für echte Neuanlagen bitte die Scanliste verwenden.');
   const nums=matches.map(x=>x.artikelnummer).filter(Boolean);
   throw Error('Mehrere JTL-Artikel gefunden. Bitte eindeutig über die JTL-Artikelnummer suchen: '+nums.slice(0,6).join(', '));
  }
  const article=matches[0];
  if(!gtinGood(val(article.ean))){
   // JTL can contain incomplete articles. Show them, but never write an overlay
   // through the EAN-keyed API until the product identity is unambiguous.
   state.jtl=data;state.article=article;state.sourceFresh=false;
   state.approved={};state.revision=0;
   $('productView').hidden=false;render();
   status('EAN fehlt oder ist ungültig: Artikeldaten werden nur lesend angezeigt. Erst die Identität in JTL klären; hier keine EAN-basierte HW24-Speicherung möglich.','error');
   return;
  }
  const approvedRes=await fetch('api/curated.php?ean='+encodeURIComponent(article.ean),
   {cache:'no-store',credentials:'same-origin'});
  const overlay=await approvedRes.json();
  if(request!==state.latestRequest)return;
  if(!approvedRes.ok||!overlay.ok)throw Error('HW24-Entwurf konnte nicht sicher geladen werden ('+(overlay.error||approvedRes.status)+').');
  state.jtl=data;state.article=article;state.sourceFresh=true;
  state.approved=overlay.fields&&typeof overlay.fields==='object'?overlay.fields:{};
  state.revision=Number(overlay.revision)||0;
  $('identifier').value=query;
  $('productView').hidden=false;render();
  status('✓ Aktueller JTL-Snapshot und HW24-Entwurf geöffnet. Nur bestätigte Änderungsfelder werden gespeichert.','success');
  if(query===referenceEan)document.getElementById('referenceStatus').textContent='Diese EAN ist die Haarfarben-Referenz. Einstellungen werden rechts verglichen.';
 }catch(e){if(request===state.latestRequest)status(e.message,'error')}
}
async function fillLists(){
 try{
  const res=await fetch('api/jtl-options.php',{credentials:'same-origin',cache:'no-store'});
  const data=await res.json();
  if(!res.ok||!data.ok||data.status!=='ready')return;
  const defs=[['Hersteller','manufacturerValues'],['Warengruppe','groupValues'],
   ['Kategorie Ebene 1','categoryValues'],['Lieferstatus','statusValues']];
  for(const [key,id] of defs){
   const root=$(id);root.replaceChildren();
   for(const raw of (data.lists?.[key]||[]).slice(0,250)){
    if(typeof raw!=='string'||raw.length>180)continue;
    const option=el('option',root);option.value=raw;
   }
  }
 }catch(e){/* Optional observed-values list, manual entry remains available. */}
}
async function loadReference(){
 const root=$('referenceFacts');root.replaceChildren();
 try{
  const r=await fetch('api/jtl.php?q='+referenceEan,{credentials:'same-origin',cache:'no-store'});
  const data=await r.json();
  if(!r.ok||!data.ok||data.jtl_status!=='current_snapshot')throw Error('JTL-Spiegel nicht aktuell.');
  const matches=(data.matches||[]).filter(x=>val(x.ean)===referenceEan);
  if(matches.length!==1)throw Error('Referenz-EAN nicht eindeutig im aktuellen JTL-Spiegel gefunden.');
  const a=matches[0],f=a.jtl_source_fields||{};
  state.reference=a;
  $('referenceStatus').textContent='✓ Originalwerte aus JTL geladen · keine automatische Übernahme';
  $('referenceStatus').className='template-note';
  for(const [caption,v] of [
   ['Referenzprodukt',a.artikelname],['Warengruppe',a.warengruppe_name],
   ['Lieferstatus (techn. ID)',f.kLieferStatus],
   ['Bearbeitungszeit',f.nBearbeitungszeit],
   ['Beschaffungszeit manuell',f.nLiefertageWennAusverkauft],
   ['Automatische Beschaffung',f.nAutomatischeLiefertageberechnung],
   ['Versandklasse',a.versandklasse_name||f.kVersandklasse],
   ['Artikel aktiv',f.cAktiv],['Bestandsführung',f.cLagerAktiv],
   ['Überverkäufe',f.cLagerKleinerNull],['Teilbar',f.cTeilbar],
   ['Steuerklasse',a.steuerklasse_name||f.kSteuerklasse]
  ])fact(root,caption,v==null?'':v);
  $('suggestReference').disabled=false;
 }catch(e){
  state.reference=null;
  $('suggestReference').disabled=true;
  $('referenceStatus').textContent='⚠ '+e.message+' Vorlagenwerte bleiben unbestätigt.';
  $('referenceStatus').className='template-note warning';
 }
}
$('suggestReference').onclick=()=>{
 if(!state.reference||!state.article||!state.sourceFresh)return;
 const reference=state.reference;
 const source=reference.jtl_source_fields||{};
 const safe={
  'Warengruppe':reference.warengruppe_name,
  'Versandklasse':reference.versandklasse_name,
  'Steuerklasse':reference.steuerklasse_name,
  'Lieferstatus':verifiedRelatedValue(reference,'lieferstatus','cName'),
  'Zusätzliche Bearbeitungszeit':source.nBearbeitungszeit,
  'Beschaffungszeit manuell setzen':source.nLiefertageWennAusverkauft,
  'Beschaffungszeit automatisch ermitteln':source.nAutomatischeLiefertageberechnung
 };
 if(!confirm('Nur leere, belegte und eindeutig zugeordnete Felder aus der Haarfarben-Referenz als VORSCHLAG einsetzen? Keine Identitätsdaten, Lieferstatus-IDs, Preise oder Bestände. Noch keine Speicherung in JTL.'))return;
 let count=0;
 for(const [key,value] of Object.entries(safe)){
  const entry=state.controls.get(key);
  if(!entry||val(entry.control.value)||!val(value))continue;
  entry.control.value=val(value);entry.control.dispatchEvent(new Event('input',{bubbles:true}));count++;
 }
 status(count?'✓ '+count+' passende Leerfelder vorbelegt. Bitte Änderungen kontrollieren und danach bestätigen.':
  'Keine passenden leeren Felder oder keine verifizierten Referenzwerte vorhanden.',count?'success':'');
};
$('lookupForm').addEventListener('submit',e=>{
 e.preventDefault();
 const id=val($('identifier').value);
 if(!/^[\p{L}\p{N}][\p{L}\p{N}._\/-]{1,63}$/u.test(id)){
  status('Bitte gültige EAN, HAN oder JTL-Artikelnummer eingeben.','error');return;
 }
 loadArticle(id);
});
$('clearArticle').onclick=()=>{state.latestRequest++;$('identifier').value='';$('productView').hidden=true;state.article=null;status('Artikelwahl zurückgesetzt.');$('identifier').focus()};
$('discardChanges').onclick=()=>{
 for(const [k,entry] of state.controls)entry.control.value=state.initial.get(k);
 renderChanges();status('Nicht gespeicherte Änderungen verworfen.');
};
$('editForm').addEventListener('submit',async e=>{
 e.preventDefault();
 if(!state.article||state.saving||!state.sourceFresh)return;
 const changes=changed();
 const keys=Object.keys(changes);
 if(!keys.length||keys.some(k=>!val(changes[k].after)))return;
 if(!window.confirm(keys.length+' Änderungen am HW24-Entwurf für EAN '+state.article.ean+
  ' bestätigen? Die Daten werden NICHT direkt in JTL/WMS/Shopify gespeichert.'))return;
 const fields=Object.fromEntries(keys.map(key=>[key,changes[key].after]));
 state.saving=true;renderChanges();status('HW24-Änderungen werden revisionsgeschützt gespeichert …');
 try{
  const res=await fetch('api/curated.php',{
   method:'POST',credentials:'same-origin',cache:'no-store',
   headers:{'Content-Type':'application/json','X-HW24-Approval':'confirmed'},
   body:JSON.stringify({ean:state.article.ean,confirmed:true,
     expected_revision:state.revision,fields})
  });
  const data=await res.json();
  if(!res.ok||!data.ok)throw Error(data.error==='revision_conflict'?
   'Der Artikel wurde zwischenzeitlich geändert. Bitte neu laden und vergleichen.':
   (data.error||'Speicherung fehlgeschlagen'));
  state.approved=data.fields||{};
  state.revision=Number(data.revision)||state.revision;
  render();
  status('✓ '+keys.length+' Feldänderungen in HW24 gespeichert (Rev. '+state.revision+
   '). JTL-Wawi und Shopify sind unverändert.','success');
 }catch(error){status('Speicherung abgebrochen: '+error.message,'error')}
 finally{state.saving=false;renderChanges();}
});
fillLists();loadReference();
const query=new URLSearchParams(location.search);
const initial=query.get('ean')||query.get('q');
if(initial&&initial.length<=64){$('identifier').value=initial;loadArticle(initial)}
})();
