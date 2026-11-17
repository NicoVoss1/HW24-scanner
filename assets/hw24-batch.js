/* HW24 v4.15 TEST – rapid serial EAN scan queue with evidence-based field review.
 * Browser-only draft, read-only JTL, never triggers a WMS booking or imports into JTL.
 * New and update articles are distinguished from the completed JTL mirror snapshot.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
const storeKey='hw24_batch_queue_v415',LIMIT=3000;
const required=['Artikelnummer','Artikelname','GTIN','Hersteller','Warengruppe','Steuerklasse'];
const recommended=['HAN','Kurzbeschreibung','Beschreibung','Inhalt/Menge','Maßeinheit','Kosten','Brutto-VK','ASIN'];
const related={Artikelnummer:'artikelnummer',Artikelname:'artikelname',GTIN:'ean',HAN:'han',Hersteller:'hersteller',
  Warengruppe:'warengruppe_name',Steuerklasse:'steuerklasse_name',Kurzbeschreibung:'kurzbeschreibung',
  Beschreibung:'beschreibung',Kosten:'kosten',ASIN:'asin','Netto-VK':'netto_vk'};
const moneyFields=new Set(['Kosten','Netto-VK','Brutto-VK','JTL-Wawi: Amazon B2B Netto',
 'JTL-Wawi: Amazon B2B Brutto','JTL-Wawi: Amazon B2C Netto',
 'JTL-Wawi: Amazon B2C Brutto','JTL-Wawi: eBay Fb24 Netto','JTL-Wawi: eBay Fb24 Brutto',
 'Neuer Standardpreis für eBay-Angebote']);
const num=x=>Number(v(x).replace(',','.'));
const knownGtin=x=>{
 const t=v(x);if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(t)||/^0+$/.test(t))return false;
 let sum=0,weight=3;for(let i=t.length-2;i>=0;i--){sum+=Number(t[i])*weight;weight=weight===3?1:3}
 return (10-sum%10)%10===Number(t[t.length-1]);
};
let list=[],panel,stateLine,stats,validation,acceptButton,exportButton,clearButton,rows,feedback,scanCounter=0,acceptedCounter=-1;
const storage=()=>{
 try{let x=JSON.parse(localStorage.getItem(storeKey)||'[]');return Array.isArray(x)?x.slice(0,LIMIT):[]}catch(_){return []}
};
list=storage();
const src=()=>window.hw24SourceState?.()||{};
const el=(tag,label,parent,cls)=>{const e=document.createElement(tag);if(label!==undefined)e.textContent=label;if(cls)e.className=cls;if(parent)parent.append(e);return e};
const cleanMoney=x=>{
 let n=v(x).replace(/\s/g,'');if(!n)return '';
 if(/^\d{1,3}(?:\.\d{3})+,\d{1,4}$/.test(n))n=n.replace(/\./g,'');
 if(/^\d+\.\d{1,4}$/.test(n))n=n.replace('.',',');
 if(!/^\d+(?:,\d{1,4})?$/.test(n)||num(n)<=0)return '';
 return n;
};
function persist(){
 try{localStorage.setItem(storeKey,JSON.stringify(list));return true}catch(_){
  feedback.textContent='Browserspeicher voll oder gesperrt. Bitte die CSV jetzt exportieren und die Seite nicht schließen.';
  return false;
 }
}
function assess(s){
 const ready=s.finished&&s.jtl?.jtl_status==='current_snapshot';
 const matches=ready?(s.jtl.matches||[]):[];
 const unique=matches.length===1,unseen=matches.length===0;
 const r=s.record||{},j=unique?matches[0]:{};
 const data=window.hw24AmeiseCandidateFields?.()||{};
 const source=k=>window.hw24SourceFor?.(k)||null;
 const errors=[],warnings=[],green=[];
 if(!ready)errors.push('JTL-Spiegel nicht frisch oder Abgleich noch nicht abgeschlossen');
 if(matches.length>1)errors.push('Mehrdeutiger JTL-Treffer');
 if((s.blocked||[]).includes('Warengruppe'))errors.push('Warengruppen-ID vorhanden, Klarname fehlt');
 // A complete JTL article is authoritative; never replace its group with a historical guess.
 if(unique&&v(j.warengruppe_name)&&v(r.Warengruppe)&&v(r.Warengruppe)!==v(j.warengruppe_name)){
  errors.push('Warengruppe weicht von JTL ab: '+v(j.warengruppe_name));
 }
 if(unseen&&ready&&!document.body.classList.contains('hw24-warehouse-mode')&&knownGtin(s.ean)){
   let filled=false;
   for(const k of ['GTIN','HAN','Artikelnummer']){
    if(v(r[k]))continue;
    r[k]=v(s.ean);data[k]=v(s.ean);filled=true;
    window.hw24MarkRuleDerived?.(k,'Eindeutige gescannte GTIN für Neuanlage');
   }
   if(filled)window.hw24WorkdeskRender?.();
 }
 // CandidateFields omits blank values and unapproved history.
 for(const k of required){
  const value=v(data[k]||r[k]),approved=window.hw24FieldExportApproved?.(k)!==false;
  if(!value||!approved){
   errors.push(k+(approved?' fehlt':' ist nicht freigegeben'));
   continue;
  }
  if(k==='GTIN'&&!knownGtin(value)){errors.push('GTIN-Prüfziffer ungültig');continue;}
  const origin=source(k);
  if(origin?.kind==='history'||origin?.kind==='conflict'||origin?.kind==='draft'||origin?.kind==='csv'||origin?.kind==='template'){
   warnings.push(k+' aus ungeprüfter Quelle ('+(origin?.name||'Vorschlag')+')');
  }else green.push(k);
 }
 for(const k of recommended){
  const value=v(data[k]||r[k]),approved=window.hw24FieldExportApproved?.(k)!==false;
  if(!value||!approved)warnings.push(k+' noch nicht vollständig');
  else if(k==='ASIN'&&!/^[A-Z0-9]{10}$/i.test(value))warnings.push('ASIN-Format prüfen');
  else if(moneyFields.has(k)&&!cleanMoney(value))warnings.push(k+' muss positiver Preis sein');
  else if(source(k)?.kind==='history'||source(k)?.kind==='draft')warnings.push(k+' ist noch Vorschlag');
  else green.push(k);
 }
 if(unseen&&ready&&!v(data.Artikelnummer||r.Artikelnummer))errors.push('Neue Artikelnummer nicht bestimmbar');
 if(unique&&ready&&!v(j.artikelnummer))errors.push('JTL-Artikelnummer fehlt');
 const status=errors.length?'red':warnings.length?'yellow':'green';
 return {ready,unique,unseen,record:r,j,fields:data,errors:[...new Set(errors)],warnings:[...new Set(warnings)],
   green:[...new Set(green)],status};
}
function updateExisting(a){
 const changed={Artikelnummer:v(a.j.artikelnummer)};
 const fields=a.fields||{},r=a.record||{};
 const selected=new Set(window.hw24TemplateExportFields?.()||[]);
 for(const key of selected){
  if(key==='Artikelnummer'||key==='GTIN'||key==='HAN'||key==='ASIN')continue;
  if(window.hw24FieldExportApproved?.(key)===false)continue;
  const val=v(fields[key]);
  if(!val)continue;
  const original=v(a.j[related[key]]);
  // Only explicitly approved modifications and missing values go to Ameise.
  const origin=window.hw24SourceFor?.(key);
  if(original===val)continue;
  if(!['manual','rule'].includes(origin?.kind))continue;
  if(key==='Warengruppe'&&v(a.j.warengruppe_name)&&val!==v(a.j.warengruppe_name))continue;
  if(moneyFields.has(key)&&!cleanMoney(val))continue;
  changed[key]=val;
 }
 return changed;
}
function toRow(a){
 const allowed=new Set(window.hw24TemplateExportFields?.()||[]);
 const record=a.record||{},values=a.fields||{};
 if(a.unique){
  const changes=updateExisting(a);
  return Object.keys(changes).length>1?{type:'update',fields:changes}:null;
 }
 const fields={};
 const schema=new Set(window.hw24AmeiseSchemaFields?.()||[]);
 for(const name of allowed){
  if(!schema.has(name)||window.hw24FieldExportApproved?.(name)===false)continue;
  let value=v(values[name]??record[name]);
  if(!value)continue;
  if(moneyFields.has(name))value=cleanMoney(value);
  if(value)fields[name]=value;
 }
 return {type:'new',fields};
}
function inspect(){
 const s=src(),a=assess(s),present=!!s.ean;
 if(!panel)return;
 stateLine.textContent=!present?'EAN scannen und nach dem JTL-Abgleich den Artikel übernehmen':
   a.unique?'In JTL gefunden · Pflege nur bei tatsächlicher Änderung':
   a.unseen&&a.ready?'Neue EAN im frischen JTL-Spiegel nicht vorhanden · Neuanlage prüfen':
   'Quellenprüfung noch nicht abgeschlossen';
 const mark=a.status==='red'?'🔴':a.status==='yellow'?'🟡':'🟢';
 stats.textContent=mark+' '+a.green.length+' geprüfte Felder · '+a.warnings.length+' Hinweise · '+a.errors.length+' Hindernisse';
 validation.replaceChildren();
 for(const [type,values] of [['red',a.errors],['yellow',a.warnings]]){
  for(const message of values.slice(0,6))el('div',(type==='red'?'🔴 ':'🟡 ')+message,validation,'hw24-batch-warning '+type);
 }
 if(a.warnings.length+a.errors.length>12)el('small','Weitere Hinweise in den Feldern prüfen',validation);
 acceptButton.disabled=!a.ready||a.errors.length>0||!present||acceptedCounter===scanCounter;
 exportButton.disabled=!list.length;
 const incoming=new Set(list.map(x=>x.id));
 if(a.ready&&a.record&&incoming.has(v(a.j.artikelnummer||a.record.Artikelnummer)))acceptButton.textContent='Artikel im Stapel aktualisieren';
 else acceptButton.textContent='✓ Artikel übernehmen · weiter scannen';
 renderList();
}
function renderList(){
 rows.replaceChildren();
 stats.dataset.count=String(list.length);
 const total=list.length,updates=list.filter(x=>x.type==='update').length;
 el('span',total+' Positionen im Prüf-Stapel · '+(total-updates)+' neu · '+updates+' Aktualisierungen',rows,'hw24-batch-count');
 for(const item of list.slice(-8).reverse()){
  const row=el('div',undefined,rows,'hw24-batch-item');
  const name=v(item.fields?.Artikelname)||v(item.title)||item.id;
  const label=el('span',undefined,row);
  el('strong',item.id,label);el('small',name+' · '+(item.type==='new'?'Neuanlage':'Aktualisierung')+(item.scans>1?' · '+item.scans+'× gescannt':'') ,label);
  const del=el('button','Entfernen',row);del.type='button';
  del.onclick=()=>{list=list.filter(x=>x.id!==item.id);persist();inspect()};
 }
 if(total>8)el('small','Weitere '+(total-8)+' Artikel im Stapel. Die Exportdatei enthält alle.',rows);
}
function accept(){
 const a=assess(src());
 if(!a.ready||a.errors.length){
  feedback.textContent='Übernahme gesperrt: '+a.errors.join(' · ');
  inspect();return;
 }
 const item=toRow(a),id=v(a.j.artikelnummer||a.record?.Artikelnummer);
 if(!id){feedback.textContent='Keine gültige Artikelnummer. Übernahme gesperrt.';return;}
 if(!item){
  acceptedCounter=scanCounter;
  feedback.textContent='Artikel '+id+' ist bereits vollständig beziehungsweise ohne bestätigte Änderung. Kein unnötiger Update-Import.';
  const input=$('ean');if(input){input.value='';input.focus();}return;
 }
 if(!item.fields.Artikelnummer&&item.type==='new'){feedback.textContent='Neue Artikelnummer fehlt.';return;}
 if(!list.some(x=>x.id===id)&&list.length>=LIMIT){
  feedback.textContent='Limit '+LIMIT+' erreicht. Bitte CSV exportieren und neuen Stapel beginnen.';return;
 }
 const scans=(list.find(x=>x.id===id)?.scans||0)+1;
 const payload={id,type:item.type,fields:item.fields,scans,title:v(a.record?.Artikelname),date:new Date().toISOString(),
  notes:a.warnings.slice(0,12)};
 const old=list;list=list.filter(x=>x.id!==id);list.push(payload);
 if(!persist()){list=old;return;}
 acceptedCounter=scanCounter;
 const input=$('ean');if(input){input.value='';input.focus();input.select?.();}
 feedback.textContent='✓ '+id+' in Stapel übernommen. '+list.length+' Positionen. Nächsten Artikel scannen.';
 inspect();
}
const quote=x=>'"'+v(x).replace(/"/g,'""')+'"';
function exportList(){
 if(!list.length)return;
 const valid=list.filter(x=>v(x.fields?.Artikelnummer)&&
   (x.type==='update'||v(x.fields?.Artikelname)&&knownGtin(x.fields?.GTIN)&&v(x.fields?.Warengruppe)));
 if(valid.length!==list.length){feedback.textContent='Stapel enthält unvollständige Positionen. Export gesperrt.';return;}
 const known=window.hw24AmeiseSchemaFields?.()||[];
 const selected=new Set(window.hw24TemplateExportFields?.()||[]);
 const order=['Artikelnummer',...known.filter(k=>k!=='Artikelnummer'&&selected.has(k))];
 const cols=order.filter(k=>valid.some(x=>Object.prototype.hasOwnProperty.call(x.fields,k)));
 const csv='\ufeff'+cols.map(quote).join(';')+'\r\n'+
  valid.map(item=>cols.map(k=>quote(moneyFields.has(k)?cleanMoney(item.fields[k]):item.fields[k])).join(';')).join('\r\n')+'\r\n';
 const blob=new Blob([csv],{type:'text/csv;charset=utf-8'});
 const a=document.createElement('a'),url=URL.createObjectURL(blob);
 a.href=url;a.download='HW24_Ameise_'+list.length+'_Artikel_PRUEFEXPORT.csv';
 document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),4000);
 feedback.textContent='Prüfexport mit '+valid.length+' Artikelzeilen erstellt. WICHTIG: JTL-Ameise im TESTLAUF, leere Eingabespalten ignorieren und Backup prüfen. Es erfolgte kein Import.';
}
function clearAll(){
 if(!list.length)return;
 if(!window.confirm('Den kompletten Scan-Stapel mit '+list.length+' Positionen wirklich verwerfen?'))return;
 list=[];persist();inspect();feedback.textContent='Scan-Stapel geleert.';
}
function build(){
 const desk=document.querySelector('.hw24-desk');if(!desk)return;
 const actions=desk.querySelector('.hw24-desk-actions');if(!actions)return;
 panel=el('section',undefined,desk,'hw24-batch');panel.dataset.originalArticle='yes';
 desk.insertBefore(panel,actions);
 el('h3','Scan-Stapel für JTL-Ameise',panel);
 el('p','Scannen → Prüfen → Übernehmen → weiter scannen. Nur freigegebene Felder, keine WMS-Buchung.',panel,'hw24-desk-muted');
 stateLine=el('p','Bereit für einen Scan.',panel,'hw24-batch-state');
 stats=el('p','',panel,'hw24-batch-status');
 validation=el('div',undefined,panel,'hw24-batch-notices');
 const buttons=el('div',undefined,panel,'hw24-batch-actions');
 acceptButton=el('button','✓ Artikel übernehmen · weiter scannen',buttons);acceptButton.type='button';acceptButton.onclick=accept;
 exportButton=el('button','JTL-Ameise Stapel-CSV erstellen',buttons);exportButton.type='button';exportButton.onclick=exportList;
 clearButton=el('button','Stapel leeren',buttons);clearButton.type='button';clearButton.onclick=clearAll;
 rows=el('div',undefined,panel,'hw24-batch-items');
 feedback=el('p','',panel,'hw24-batch-feedback');
 const previous=window.hw24SmartScanFinish;
 if(typeof previous==='function')window.hw24SmartScanFinish=(...args)=>{
   const result=previous(...args);scanCounter++;inspect();return result;
 };
 const previousApproved=window.hw24ApproveHistory;
 if(typeof previousApproved==='function')window.hw24ApproveHistory=(...args)=>{
  const result=previousApproved(...args);inspect();return result;
 };
 // Advance scans work with USB/Bluetooth scanners which send Enter, or camera.
 window.hw24BatchRefresh=inspect;
 inspect();
}
build();
})();