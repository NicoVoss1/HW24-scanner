/* HW24 v3.8 – JTL original export field profile.
 * Exact field captions from JTL-Export-Artikelstammdaten-12112026.csv (872 headers).
 * Existing values are retained; recommended defaults are proposals for NEW items only.
 * Stock and historical editor audit fields are NEVER written to JTL-Ameise.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id),val=x=>String(x??'').trim();
const grouped=[
 ['Identifikation und Kosten',[
  ['Kosten','text','edit'],['Durchschnittlicher Einkaufspreis (netto)','text','read'],
  ['Lieferant','text','edit'],['Artikelnummer (Lieferant)','text','edit'],
  ['Auf Lager','text','read'],['In Aufträgen','text','read'],['Verfügbar','text','read']]],
 ['Artikelstandards und Versand',[
  ['Überverkäufe möglich','yn','edit'],['Lieferstatus','text','edit'],
  ['Stückzahl teilbar','yn','edit'],['Zusätzliche Bearbeitungszeit','int','edit'],
  ['Beschaffungszeit automatisch ermitteln','yn01','edit'],
  ['Beschaffungszeit manuell setzen','int','edit'],['Versandklasse','text','edit'],
  ['Artikel aktiv','yn','edit'],['Bestandsführung aktiv','yn','edit'],
  ['Top-Artikel','yn','edit'],['Warengruppe','warengruppe','edit'],
  ['Artikeltyp','artikeltyp','edit'],['Neu im Sortiment','yn','edit'],
  ['Steuerklasse','tax','edit'],['Steuersatz in %','percent','read'],['Währung','currency','edit']]],
 ['Marktplatzpreise und Quellen',[
  ['ASIN','text','edit'],['JTL-Wawi: Amazon B2B Netto','money','edit'],
  ['JTL-Wawi: Amazon B2B Brutto','money','edit'],
  ['JTL-Wawi: Amazon B2C Netto','money','edit'],
  ['JTL-Wawi: Amazon B2C Brutto','money','edit'],
  ['JTL-Wawi: eBay Fb24 Netto','money','edit'],
  ['JTL-Wawi: eBay Fb24 Brutto','money','edit'],
  ['Neuer Standardpreis für eBay-Angebote','money','edit']]],
 ['Interne Angaben und Lagerkommentare',[
  ['Anmerkung','textarea','edit'],['Lagerkommentar_1 [ZepCh]','textarea','edit'],
  ['Lagerkommentar_2 [ZepCh]','textarea','edit'],
  ['Lagerkommentar_1 [ZepCh - WMS-ZepCh]','textarea','edit'],
  ['Lagerkommentar_2 [ZepCh - WMS-ZepCh]','textarea','edit'],
  ['Artikel zuletzt bearbeitet am','date','read'],
  ['Artikel zuletzt bearbeitet durch','text','read']]],
 ['Kategoriezuordnung',[
  ['Kategorie Ebene 1','category','edit'],['Kategorie Ebene 2','category','edit'],
  ['Kategorie Ebene 3','category','edit']]]
];
// User-entered new-article defaults. The source export most commonly uses 1/2 for
// the two times, but user explicitly requests 2 and a choice of 2/3.
const newDefaults={
 'Überverkäufe möglich':'N','Lieferstatus':'2-4 Werktage','Stückzahl teilbar':'N',
 'Zusätzliche Bearbeitungszeit':'1','Beschaffungszeit automatisch ermitteln':'0',
 'Beschaffungszeit manuell setzen':'2','Versandklasse':'standard',
 'Artikel aktiv':'Y','Bestandsführung aktiv':'Y','Top-Artikel':'N',
 'Warengruppe':'Haarfarben','Artikeltyp':'Normaler Artikel','Neu im Sortiment':'Y',
 'Steuerklasse':'normaler steuersatz','Steuersatz in %':'19,00','Währung':'EUR'
};
const protectedFields=new Set(['Auf Lager','In Aufträgen','Verfügbar','Durchschnittlicher Einkaufspreis (netto)',
 'Artikel zuletzt bearbeitet am','Artikel zuletzt bearbeitet durch','Steuersatz in %']);
const notAutoImport=new Set([...protectedFields,'Lagerkommentar_1 [ZepCh]',
 'Lagerkommentar_2 [ZepCh]','Lagerkommentar_1 [ZepCh - WMS-ZepCh]',
 'Lagerkommentar_2 [ZepCh - WMS-ZepCh]']);
let rec=null,ean='',jtl=null,source='none',pending=false;
let manuallyChanged=new Set();
let lists={
 warengruppe:['Haarfarben','Haarpflege','Haarstyling','Sammelkarten','Beauty','Haarschmuck'],
 artikeltyp:['Normaler Artikel','Kartonage','Mehrzweckgutschein'],
 category:['Haarfarben','Haarpflege','Haarstyling']
};
const defaults=new Map(Object.entries(newDefaults));
function setListFromRows(rows){
 if(!Array.isArray(rows)||!rows.length)return;
 for(const [kind,key] of [['warengruppe','Warengruppe'],['artikeltyp','Artikeltyp'],['category','Kategorie Ebene 1']]){
  const frequency=new Map();
  for(const row of rows){
   const value=val(row?.[key]);if(!value||value.length>90)continue;
   frequency.set(value,(frequency.get(value)||0)+1);
  }
  const candidates=[...frequency.keys()].sort((a,b)=>frequency.get(b)-frequency.get(a));
  lists[kind]=[...new Set([...lists[kind],...candidates])].slice(0,70);
 }
}
function canPreset(){
 // Existing JTL data must never be overwritten by a new-article template.
 const matches=jtl?.jtl_status==='current_snapshot'?jtl.matches||[]:null;
 if(matches&&matches.length>0)return false;
 if(rec?.Status==='IM_CSV'||rec?.Status==='MEHRFACH_CSV'||rec?.Status==='JTL_GEFUNDEN')return false;
 return true;
}
function applyDefaults(){
 if(!rec||!ean)return 0;
 if(!canPreset()){status('Keine Neuanlage-Vorbelegung: Dieser Artikel ist bereits im JTL-Bestand oder CSV vorhanden.');return 0}
 let count=0;
 for(const [key,value] of defaults){
  if(val(rec[key]))continue;
  if(key==='Warengruppe'){
   const name=val(rec.Artikelname).toLowerCase();
   // Product family cannot be guessed from a generic missing name.
   if(/shampoo|conditioner|haarkur|pflegemaske|haarpflege/.test(name))rec[key]='Haarpflege';
   else if(/styling|haarspray|haargel|haarwachs|haarschaum/.test(name))rec[key]='Haarstyling';
   else if(/booster|pokemon|pokémon|sammelkart/.test(name))rec[key]='Sammelkarten';
   else if(/haarfarb|tönung|koleston|illumina|color touch|igora|majirel|majiblond|tinta color|färbecreme/.test(name))rec[key]='Haarfarben';
   else continue;
  }else rec[key]=value;
  count++;
 }
 // 19% is a proposed default only for the normal tax class, not for gift cards.
 if(val(rec.Steuerklasse).toLowerCase()!=='normaler steuersatz')delete rec['Steuersatz in %'];
 source='new_template';
 window.hw24AmeiseCurrent?.(rec);
 window.hw24ChannelCurrent?.(rec);
 window.hw24FlowRefresh?.();
 window.hw24QualityEdit?.(rec);
 render();
 status(count+' Standardwerte für Neuanlage ergänzt. Überverkäufe bleiben NEIN. Bitte Produktgruppe, Steuerklasse und Preise kontrollieren.');
 return count;
}
function status(msg){const o=$('profileStatus');if(o)o.textContent=msg}
function sel(parent,options,value,onValue){
 const select=document.createElement('select');
 const opts=[...new Set(['',value,...options])];
 for(const name of opts){const o=document.createElement('option');o.value=name;o.textContent=name||'Bitte auswählen';select.append(o)}
 select.value=value;select.onchange=()=>onValue(select.value);parent.append(select);return select;
}
function createControl(container,key,type,read){
 const value=val(rec?.[key]),label=document.createElement('label');
 label.textContent=key;container.append(label);
 const field=document.createElement('div');field.className='field';field.style.padding='6px';
 const update=n=>{
   if(read)return;
   rec[key]=n;manuallyChanged.add(key);
   if(key==='Steuerklasse'){
    // A rate is only suitable for its actual tax class, never keep a stale 19% suggestion.
    if(n!=='normaler steuersatz')delete rec['Steuersatz in %'];
    else if(!val(rec['Steuersatz in %'])&&canPreset())rec['Steuersatz in %']='19,00';
   }
   window.hw24AmeiseCurrent?.(rec);
   window.hw24QualityEdited?.(key,rec);
   window.hw24ChannelCurrent?.(rec);
   window.hw24FlowRefresh?.();
 };
 if(type==='yn'){
  sel(field,['N','Y'],value,next=>{
   if(key==='Überverkäufe möglich'&&next==='Y'&&value!=='Y'){
    if(!window.confirm?.('ACHTUNG: Überverkäufe sind standardmäßig verboten. Möchtest Du diese Ausnahme ausdrücklich nur für diesen Artikel aktivieren?')){render();return;}
   }
   update(next);
  });
 }
 else if(type==='yn01')sel(field,['0','1'],value,update);
 else if(type==='artikeltyp')sel(field,lists.artikeltyp,value,update);
 else if(type==='warengruppe')sel(field,lists.warengruppe,value,update);
 else if(type==='category')sel(field,lists.category,value,update);
 else if(type==='tax')sel(field,['normaler steuersatz','ermäßigter steuersatz','steuerfrei'],value,update);
 else if(type==='currency')sel(field,['EUR'],value,update);
 else if(type==='int')sel(field,['0','1','2','3','4','5','7','10'],value,update);
 else{
  const input=type==='textarea'?document.createElement('textarea'):document.createElement('input');
  if(type==='textarea')input.rows=2;
  if(type==='date'){input.type='text';input.placeholder='JTL-Quellwert, nicht manuell überschreiben'}
  input.value=value;
  input.readOnly=read;
  input.style.cssText='width:100%;box-sizing:border-box';
  if(!read)input.onchange=()=>update(input.value);
  field.append(input);
 }
 const foot=document.createElement('small');foot.className='note';
 const preset=defaults.get(key);
 foot.textContent=read?'Aus JTL/CSV anzeigen, niemals über den Ameise-Stammdatenentwurf zurückschreiben':
  value?'Vorhandener Wert aus Quelle/Entwurf erhalten':
  preset?'Standardvorschlag Neuanlage: '+preset:'Kein geprüfter Wert';
 field.append(foot);container.append(field);
}
function render(){
 const root=$('jtlProfile');if(!root)return;
 root.hidden=!rec;
 if(!rec){$('profileState').textContent='Zuerst EAN scannen.';$('profileGroups').replaceChildren();$('profileApply').disabled=true;return}
 $('profileState').textContent='EAN '+ean+' · '+(canPreset()?'Vorlage für neue Artikel verfügbar':'Vorhandene Artikelwerte bleiben geschützt')+
  ' · Quelle: '+(source==='new_template'?'Neuanlage-Vorschläge':source==='jtl'?'JTL-Spiegel':source==='csv'?'JTL-CSV':'Datenentwurf');
 $('profileApply').disabled=!canPreset();
 const groups=$('profileGroups');groups.replaceChildren();
 for(const [groupName,fields] of grouped){
  const section=document.createElement('details');section.open=['Identifikation und Kosten','Artikelstandards und Versand'].includes(groupName);
  const summary=document.createElement('summary');summary.textContent=groupName;section.append(summary);
  const grid=document.createElement('div');grid.className='groupgrid';
  for(const [key,type,mode] of fields){const cell=document.createElement('div');cell.style.minWidth='0';createControl(cell,key,type,mode==='read');grid.append(cell)}
  section.append(grid);groups.append(section);
 }
}
window.hw24ProfileOptions=setListFromRows;
window.hw24ProfileScan=(id,record)=>{
 ean=val(id);rec=record||null;jtl=null;source=rec?.Status==='IM_CSV'?'csv':'none';pending=false;manuallyChanged.clear();
 render();
};
window.hw24ProfileJtl=response=>{
 jtl=response||null;
 if(jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1&&rec){
  source='jtl';
  const r=jtl.matches[0],fields=r.jtl_source_fields||{};
  const names={
   'Überverkäufe möglich':'cLagerKleinerNull',
   'Stückzahl teilbar':'cTeilbar',
   'Bestandsführung aktiv':'cLagerAktiv',
   'Artikel aktiv':'cAktiv',
   'Top-Artikel':'cTopArtikel',
   'Neu im Sortiment':'cNeu',
   'Zusätzliche Bearbeitungszeit':'nBearbeitungszeit',
   'Beschaffungszeit automatisch ermitteln':'nAutomatischeLiefertageberechnung',
   'Anmerkung':'cAnmerkung',
   'Auf Lager':'nLagerbestand',
   'Artikel zuletzt bearbeitet am':'dMod'
  };
  // Current SQL source outranks historical CSV unless the user edited a value.
  for(const [target,jtlKey] of Object.entries(names)){
   const value=fields[jtlKey];
   if(value!==undefined&&value!==null&&String(value).trim()&&!manuallyChanged.has(target))rec[target]=String(value);
  }
  // The kBenutzerLetzteAenderung is only an ID. User's name needs a verified join.
  if(fields.kBenutzerLetzteAenderung!=null&&!manuallyChanged.has('Artikel zuletzt bearbeitet durch')){
   rec['Artikel zuletzt bearbeitet durch']='Benutzer-ID '+fields.kBenutzerLetzteAenderung+' (Name noch nicht verknüpft)';
  }
  if(r.netto_ek!=null&&!manuallyChanged.has('Durchschnittlicher Einkaufspreis (netto)')){
   rec['Durchschnittlicher Einkaufspreis (netto)']=String(r.netto_ek);
  }
  if(!val(rec.ASIN)&&val(r.asin))rec.ASIN=String(r.asin);
 }

 if(jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===0&&canPreset()&&!pending){
  pending=true;applyDefaults();
 }else render();
};
window.hw24ProfileCurrent=record=>{rec=record||null;render()};
window.hw24ProfileDefaults=newDefaults;
window.hw24ProfileNotAutoImport=notAutoImport;
$('profileApply')?.addEventListener('click',applyDefaults);
render();
})();