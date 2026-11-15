/* HW24 v4.1: green=source-backed, amber=proposal, red=missing/conflict.
 * Never synthesize identifiers, costs, ASIN, prices or availability. */
(function(){
'use strict';
const get=id=>document.getElementById(id),v=x=>String(x??'').trim();
const norm=x=>v(x).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,' ').trim();
const keys=['GTIN','HAN','Artikelname','Hersteller','Warengruppe','Kategorie Ebene 1','Kategorie Ebene 2','Kurzbeschreibung','Beschreibung','ASIN','Kosten','Brutto-VK','Steuerklasse','Artikel aktiv','Bestandsführung aktiv','Überverkäufe möglich','Versandklasse','Lieferstatus','Stückzahl teilbar'];
let csv=[],curated=[],row=null,ean='',jtl=null,history=null,edited=new Set(),proposals=new Map();
function elm(tag,text){const x=document.createElement(tag);if(text!==undefined)x.textContent=text;return x}
function money(x){const s=v(x);return /^\d+(?:[,.]\d{1,4})?$/.test(s)&&Number(s.replace(',','.'))>0}
function gtin(x){const s=v(x);if(!/^(\d{8}|\d{12,14})$/.test(s))return false;let n=0;for(let i=s.length-2,w=3;i>=0;i--,w=w===3?1:3)n+=Number(s[i])*w;return (10-n%10)%10===Number(s[s.length-1])}
function ok(key,value){
 if(!v(value))return false;
 if(key==='GTIN')return gtin(value);
 if(key==='ASIN')return /^[A-Z0-9]{10}$/i.test(v(value));
 if(['Kosten','Brutto-VK'].includes(key))return money(value);
 if(key==='Überverkäufe möglich')return v(value)==='N';
 if(['Artikel aktiv','Bestandsführung aktiv','Stückzahl teilbar'].includes(key))return ['Y','N'].includes(v(value));
 if(key==='Kategorie Ebene 1')return v(value)==='Friseurbedarf';
 return true;
}
function family(name){
 const tokens=norm(name).split(' ').slice(0,5);
 const firstShade=tokens.findIndex(t=>/^\d+$/.test(t));
 return tokens.slice(0,firstShade>=2?firstShade:Math.min(tokens.length,4)).join(' ');
}
function similar(name){
 const f=family(name);return f.length>=9?curated.filter(r=>family(r.Artikelname)===f):[];
}
function most(peers,key){
 const counts=new Map();for(const r of peers){const value=v(r[key]);if(value)counts.set(value,(counts.get(value)||0)+1)}
 const sorted=[...counts.entries()].sort((a,b)=>b[1]-a[1]);
 return sorted.length&&sorted[0][1]>=3&&sorted[0][1]/peers.length>=.9?sorted[0][0]:null;
}
function suggest(key,value,why){
 if(!v(value))return;
 if(proposals.has(key)&&proposals.get(key).value!==value){proposals.delete(key);return}
 proposals.set(key,{value,why});
}
function gather(){
 proposals.clear();if(!row)return;
 const peers=similar(row.Artikelname);
 if(peers.length>=3)for(const key of ['Hersteller','Warengruppe','Kategorie Ebene 1','Kategorie Ebene 2']){
   const value=most(peers,key);
   if(value&&value!==v(row[key]))suggest(key,value,peers.length+' gepflegte Artikel derselben Produktfamilie');
 }
 const name=v(row.Artikelname);
 if(!v(row.Hersteller)&&name){
  const brands=[...new Set(curated.map(r=>v(r.Hersteller)).filter(Boolean))].sort((a,b)=>b.length-a.length);
  for(const brand of brands){
   if(norm(brand).length>=4&&norm(name).startsWith(norm(brand)+' ')){
    suggest('Hersteller',brand,'Marke aus dem Artikelnamen, mit gepflegten Referenzdaten abgeglichen');break;
   }
  }
 }
 if(name){
  if(!v(row.Kurzbeschreibung))suggest('Kurzbeschreibung',name,'Sachlicher Textentwurf aus vorhandenem Artikelnamen');
  if(!v(row.Beschreibung))suggest('Beschreibung',name+'. Produktinformationen und Anwendung laut Hersteller prüfen.','Textvorschlag, noch keine verifizierte Herstellerbeschreibung');
  if(!v(row['Titel-Tag (SEO)']))suggest('Titel-Tag (SEO)',name.slice(0,65),'SEO-Textentwurf');
 }
 if(history?.ok)for(const match of history.matches||[])for(const cell of match.cells||[]){
  if(!cell.header_verified)continue;
  const key=v(cell.label),value=v(cell.value);
  if(!['GTIN','HAN','ASIN','Hersteller','Warengruppe','Artikelname','Kurzbeschreibung','Beschreibung','Kosten'].includes(key))continue;
  if(key==='GTIN'&&!gtin(value)||key==='ASIN'&&!/^[A-Z0-9]{10}$/i.test(value)||key==='Kosten'&&!money(value))continue;
  suggest(key,value,'Historischer Excel-Treffer, manuell bestätigen');
 }
}
function flag(key){
 const value=v(row?.[key]),idea=proposals.get(key);
 if(!value)return idea?{state:'suggest',desc:'Vorschlag aus Quelle',value:idea.value}:{state:'missing',desc:'Wert fehlt',value:''};
 if(!ok(key,value))return {state:'error',desc:key==='Überverkäufe möglich'?'Überverkauf JA: nur manuelle Ausnahme':'Wert ungültig oder Import-/Prüfkategorie',value};
 if(edited.has(key))return {state:'suggest',desc:'Entwurf geändert, nicht in JTL gespeichert',value};
 if(jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1){
  const j=jtl.matches[0],alias={GTIN:'ean',HAN:'han',Artikelname:'artikelname',ASIN:'asin',Hersteller:'hersteller',Kurzbeschreibung:'kurzbeschreibung',Beschreibung:'beschreibung'};
  if(alias[key]&&v(j[alias[key]]))return norm(j[alias[key]])===norm(value)?
   {state:'good',desc:'Übereinstimmung mit JTL-Spiegel',value}:
   {state:'error',desc:'Abweichung vom aktuellen JTL-Spiegel',value};
 }
 return row?.['Kategorie Ebene 1']==='Friseurbedarf'?
  {state:'good',desc:'Plausibler gepflegter CSV-Wert, kein Live-Nachweis',value}:
  {state:'suggest',desc:'Quellwert vorhanden; JTL-Vergleich fehlt',value};
}
function renderProgress(){
 const root=get('hw24Milestones');if(!root)return;root.replaceChildren();
 const items=[
  ['Quellprüfung','Historischer JTL-Export vom 14.11.2026: 2.691 Artikelzeilen / 872 Spalten analysiert'],
  ['App-Code','v4.3 · produktive Bereitstellung über Plesk separat'],
  ['Optionale CSV',csv.length?csv.length+' CSV-Zeilen im Browser geladen':'Nicht erforderlich für die JTL-Spiegelabfrage'],
  ['JTL-Spiegel',jtl?.jtl_status==='current_snapshot'?'Letzter abgeschlossener Snapshot: '+(jtl.snapshot_at||'Zeit unbekannt')+' UTC':'Aktueller Snapshot noch nicht bestätigt'],
  ['MariaDB',jtl?.jtl_status==='current_snapshot'?'Gespiegelte JTL-Stammdaten für die geprüfte EAN erreichbar':'Spiegelstatus über Synchronisationsanzeige prüfen'],
  ['Ameise','Hauptimport als Test-CSV, Importprofil und Testlauf noch erforderlich']
 ];
 for(const [name,desc] of items){
  const li=elm('li');li.append(elm('strong',name+': '),elm('span',desc));root.append(li);
 }
}
function render(){
 renderProgress();
 const panel=get('hw24Quality');if(!panel)return;panel.hidden=!row;
 if(!row)return;
 gather();const list=get('hw24QualityRows');list.replaceChildren();
 const states=keys.map(key=>({key,...flag(key)}));
 const rank={error:0,missing:1,suggest:2,good:3};
 states.sort((a,b)=>rank[a.state]-rank[b.state]);
 const n={good:0,suggest:0,missing:0,error:0};
 for(const item of states)n[item.state]++;
 get('hw24QualitySummary').textContent=n.good+' grün · '+n.suggest+' orange · '+(n.missing+n.error)+' rot';
 for(const info of states){
  const box=elm('div');box.className='hw24q hw24q-'+info.state;
  const head=elm('strong',info.key+' · '+({good:'OK',suggest:'Vorschlag',missing:'Fehlt',error:'Prüfen'}[info.state]));
  const detail=elm('small',info.desc);
  const cell=elm('div');cell.className='hw24q-detail';cell.append(head,detail);
  box.append(cell,elm('span',info.value||'Nicht befüllt'));
  const p=proposals.get(info.key);
  if(p&&p.value!==v(row[info.key])&&!v(row[info.key])){
   const btn=elm('button','Übernehmen');btn.type='button';btn.onclick=()=>take(info.key);box.append(btn);
  }
  list.append(box);
 }
 const pending=[...proposals.keys()].filter(k=>!v(row[k])&&proposals.get(k).value);
 const btn=get('hw24QualityApply');btn.disabled=!pending.length;btn.textContent=pending.length?'Alle '+pending.length+' leeren Felder vorbelegen':'Keine neuen Vorschläge';
}
function take(key){
 const p=proposals.get(key);if(!row||!p||v(row[key]))return;
 row[key]=p.value;edited.add(key);
 // Keep the existing core master-data form visually synchronized with the
 // accepted recommendation; no artificial input event or second scan required.
 const fields=document.getElementById('fields');
 for(const box of fields?.querySelectorAll('.field')||[]){
  const name=box.querySelector('label')?.textContent;
  if(name!==key)continue;
  const input=box.querySelector('input,textarea');
  if(input&&!input.readOnly){input.value=p.value;box.className=box.className.replace(' bad',' good')}
 }
 window.hw24AmeiseCurrent?.(row);window.hw24ProfileCurrent?.(row);
 window.hw24ChannelCurrent?.(row);window.hw24FlowRefresh?.();
 render();
}
window.hw24QualityCsv=rows=>{
 csv=Array.isArray(rows)?rows:[];
 curated=csv.filter(r=>v(r['Kategorie Ebene 1'])==='Friseurbedarf'&&v(r.GTIN)&&v(r.Hersteller)&&v(r.Artikelname)&&v(r['Kategorie Ebene 2']));
 render();
};
window.hw24QualityScan=(id,record)=>{ean=v(id);row=record||null;jtl=null;history=null;edited.clear();render()};
window.hw24QualityJtl=result=>{jtl=result;render()};
window.hw24QualityHistory=result=>{history=result;render()};
window.hw24QualityEdit=record=>{row=record||null;render()};
window.hw24QualityEdited=(key,record)=>{row=record||row;edited.add(key);render()};
get('hw24QualityApply')?.addEventListener('click',()=>{
 for(const key of [...proposals.keys()])if(!v(row?.[key]))take(key);
});
renderProgress();
})();