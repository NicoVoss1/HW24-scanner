/* HW24 v3.6 Single scan workflow.
   Treat historical fields as suggestions, never as JTL-verified current state. */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
let ean='',current=null,jtl={jtl_status:'loading'},history=null,proposals=[],historyText='Noch nicht abgefragt';
const gtin=value=>{
 const str=v(value);if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(str)||/^0+$/.test(str))return false;
 let total=0,w=3;for(let i=str.length-2;i>=0;i--){total+=Number(str[i])*w;w=w===3?1:3}
 return (10-total%10)%10===Number(str[str.length-1]);
};
function missing(){
 const r=current||{},bad=[];
 const checks=[
 ['Artikelnummer',()=>Boolean(v(r.Artikelnummer))],
 ['Artikelname',()=>Boolean(v(r.Artikelname))],
 ['Hersteller / Marke',()=>Boolean(v(r.Hersteller))],
 ['Steuerklasse',()=>Boolean(v(r.Steuerklasse))],
 ['Verkaufspreis',()=>['Netto-VK','Brutto-VK'].some(k=>{
  const x=v(r[k]).replace(/\s/g,''),normalized=x.includes(',')?x.replace(/\./g,'').replace(',','.'):x;
  return Number(normalized)>0 && Number.isFinite(Number(normalized));
 })],
 ['Gültige GTIN',()=>gtin(v(r.GTIN||r.EAN))]
 ];
 for(const [label,fn] of checks)if(!fn())bad.push(label);
 return bad;
}

function currentJtlMissing(row){
 const r=row||{},missingFields=[];
 if(!v(r.artikelnummer))missingFields.push('Artikelnummer');
 if(!v(r.artikelname))missingFields.push('Artikelname');
 if(!v(r.hersteller))missingFields.push('Hersteller/Marke');
 if(!v(r.steuerklasse))missingFields.push('Steuerklasse');
 const price=Number(v(r.netto_vk).replace(',','.'));
 if(!(Number.isFinite(price)&&price>0))missingFields.push('VK netto');
 if(!gtin(v(r.ean)))missingFields.push('GTIN');
 return missingFields;
}
function state(){
 const m=jtl?.jtl_status==='current_snapshot'?jtl.matches||[]:null;
 if(!ean)return {key:'idle',title:'EAN scannen',desc:'Eine EAN eingeben oder mit dem Scanner erfassen.'};
 if(!m)return {key:'unknown',title:'JTL-Bestand derzeit nicht verifiziert',desc:'Ohne frischen vollständigen JTL-Spiegel ist die Entscheidung „vorhanden oder neu“ noch offen.'};
 if(m.length>1)return {key:'multiple',title:'Mehrfach in JTL vorhanden',desc:'Bitte zunächst die richtige Artikelnummer zuordnen. Kein automatischer Import bei mehrdeutiger EAN.'};
 if(m.length===0)return {key:'new',title:'Mögliche Neuanlage',desc:'Kein EAN-Treffer im letzten vollständigen JTL-Spiegel. Die Artikelnummer muss separat auf Existenz geprüft werden.'};
 const gaps=currentJtlMissing(m[0]);
 return gaps.length?{key:'maintenance',title:'Vorhanden · Produktpflege erforderlich',desc:gaps.length+' noch nicht belegte Stammdaten in der aktuellen JTL-Quelle.'}
 :{key:'complete',title:'Vorhanden · Stammdaten-Basis vollständig',desc:'Die Basisfelder stammen aus dem aktuellen JTL-Spiegel. Marktplatzfreigaben und Lagerplatzabgleich sind separat zu prüfen.'};
}
function put(parent,tag,value){const e=document.createElement(tag);e.textContent=value;parent.append(e);return e}
function render(){
 const root=$('scanCockpit');if(!root)return;
 const s=state();root.dataset.status=s.key;
 $('scanDecision').textContent=s.title;$('scanExplanation').textContent=s.desc;
 $('scanJtlStatus').textContent=jtl?.jtl_status==='current_snapshot'
  ?'JTL-Spiegel · '+v(jtl.snapshot_at)+' UTC'
  :jtl?.jtl_status==='loading'?'JTL wird geprüft …'
  :jtl?.jtl_status==='stale'?'JTL-Daten veraltet · kein Bestandsurteil'
  :'JTL-Synchronisation noch nicht verfügbar';
 $('scanArchiveStatus').textContent=historyText;
 const match=jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1?jtl.matches[0]:null;
 const location=v(match?.lagerplatz||match?.lagerort);
 const csvPlace=v(current?.Lagerplatz||current?.Lagerort||current?.Lagerortbezeichnung);
 $('scanLocation').textContent=location?'JTL-Lagerplatz: '+location
  :csvPlace?'Lagerplatz aus CSV-Arbeitskopie (nicht live): '+csvPlace
  :'Lagerort: Live-Abfrage aus JTL/WMS noch nicht eingerichtet';
 const list=$('scanMissing');list.replaceChildren();
 const gaps=missing();
 if(!ean)put(list,'p','Nach dem Scan erscheinen hier nur die noch offenen Stammdaten.');
 else if(!gaps.length)put(list,'p','✓ Im aktuellen Bearbeitungsentwurf keine Basislücken. Die JTL-Quelle bleibt bis zum erfolgreichen Ameise-Import unverändert.');
 else{
  put(list,'strong','Noch offen oder zu prüfen:');
  const ul=document.createElement('ul');for(const k of gaps)put(ul,'li',k);list.append(ul);
 }
 const selection=jtl?.jtl_status==='current_snapshot'?jtl.matches||[]:null;
 $('scanOneClick').disabled=!ean||Boolean(selection&&selection.length>1);
 $('scanOneClick').textContent=s.key==='complete'?'📥 Optional Ameise-Prüfentwurf':'✨ Artikel automatisch vorbereiten';
 $('scanEdit').disabled=!ean;
 const proposalBox=$('scanSuggestions');proposalBox.replaceChildren();
 if(proposals.length){
  put(proposalBox,'strong',proposals.length+' ergänzte Vorschläge aus historischen Angaben oder Artikelname (ungeprüft)');
  const ul=document.createElement('ul');
  for(const p of proposals.slice(0,6))put(ul,'li',p.key+': '+p.value.slice(0,85)+' · '+p.source);
  proposalBox.append(ul);
 }
}
function mappedLabel(label){
 const m={'artikelname':'Artikelname','name':'Artikelname','citemname':'Artikelname',
  'kurzbeschreibung':'Kurzbeschreibung','beschreibung':'Beschreibung',
  'hersteller':'Hersteller','marke':'Hersteller',
  'han':'HAN','asin':'ASIN','asin aus jtl':'ASIN','casin1':'ASIN'};
 return m[v(label).toLocaleLowerCase('de').replace(/\s+/g,' ')]||null;
}
function historyFill(){
 if(!current||!history?.ok||!history.gtin_valid)return;
 const fields=new Map();
 for(const row of history.matches||[])for(const cell of row.cells||[]){
  const field=cell.header_verified?mappedLabel(cell.label):null,value=v(cell.value);
  if(!field||!value||value.length>7500)continue;
  if(field==='ASIN'&&!/^[A-Z0-9]{10}$/i.test(value))continue;
  if(['Artikelname','Hersteller','HAN'].includes(field)&&value.length>240)continue;
  if(!fields.has(field))fields.set(field,[]);
  fields.get(field).push({value,source:(row.sheet_name||'Blatt '+row.sheet_no)+' Zeile '+row.excel_row,main:row.sheet_no===1});
 }
 for(const [key,all] of fields){
  if(v(current[key]))continue;
  const primary=all.filter(x=>x.main),options=primary.length?primary:all;
  if(new Set(options.map(x=>x.value.toLocaleLowerCase('de'))).size!==1)continue;
  const c=options[0];
  const target=[...$('fields').querySelectorAll('.field')].find(x=>x.querySelector('label')?.textContent===key);
  const input=target?.querySelector('input,textarea');
  if(input?.readOnly||input?.dataset.userEdited==='1')continue;
  current[key]=c.value;
  if(input){input.value=c.value;input.dispatchEvent(new Event('input',{bubbles:true}));target.classList.add('scan-autofill')}
  proposals.push({key,value:c.value,source:'Historie '+c.source});
 }
}
function fromName(){
 if(!current)return;
 const name=v(current.Artikelname),m=[...name.matchAll(/(\d+(?:[,.]\d+)?)\s*(ml|l|g|kg)\b/gi)];
 if(m.length!==1||/\b\d+\s*[x×]\s*\d+/i.test(name))return;
 const amount=m[0][1].replace(',','.'),unit=m[0][2].toLowerCase();
 for(const [key,value] of [['Inhalt/Menge',amount],['Maßeinheit',unit]]){
  if(v(current[key]))continue;
  current[key]=value;proposals.push({key,value,source:'aus Artikelname abgeleitet, bitte prüfen'});
 }
}
function prepare(){
 proposals=[];historyFill();fromName();
 window.hw24AmeiseCurrent?.(current);
 window.hw24ChannelCurrent?.(current);
 render();
}
window.hw24FlowScan=(id,data)=>{
 ean=v(id);current=data||null;jtl={jtl_status:'loading'};history=null;proposals=[];
 historyText='Recherche läuft nach JTL-Prüfung';$('scanActionMsg').textContent='';render();
};
window.hw24FlowJtl=data=>{jtl=data||{jtl_status:'unavailable'};prepare()};
window.hw24FlowHistorical=data=>{
 history=data;historyText=data?.ok
 ?(data.matches?.length||0)+' historische Excel-Fundstellen · keine bestätigte JTL-Quelle'
 :'Historische Quelle nicht erreichbar';
 prepare();
};
window.hw24FlowHistoryUnavailable=()=>{historyText='Historische Quelle nicht erreichbar';render()};
window.hw24FlowHistorySkipped=()=>{historyText='Nicht erforderlich: JTL-Stammdaten-Basis vollständig';render()};
window.hw24FlowNeedsHistory=()=>!(jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1&&currentJtlMissing(jtl.matches[0]).length===0);
window.hw24FlowRefresh=render;
$('scanOneClick')?.addEventListener('click',()=>{
 if(!ean)return;
 const s=state();
 if(s.key==='multiple'){ $('scanActionMsg').textContent='Zuerst die richtige JTL-Artikelnummer auswählen.';return }
 prepare();
 if(!v(current?.Artikelnummer)||!v(current?.Artikelname)){
  $('scanActionMsg').textContent='Artikelnummer oder Artikelname fehlen. Nur diese Felder müssen noch ergänzt werden.';
  $('masterDataDetails').open=true;
  $('masterDataDetails').scrollIntoView({behavior:'smooth',block:'start'});
  return;
 }
 const ok=window.hw24AmeiseAutoExport?.();
 $('scanActionMsg').textContent=ok
  ?'Ameise-Prüf-CSV erzeugt. '+(missing().length?'Offene Angaben: '+missing().join(', ')+'. ':'')
   +'Für den Import Feldzuordnung und Testlauf in JTL-Ameise prüfen.'
  :'Ameise-Prüfdatei konnte noch nicht erstellt werden. Bitte Datenzuordnung prüfen.';
});
$('scanEdit')?.addEventListener('click',()=>{
 $('masterDataDetails').open=true;
 $('masterDataDetails').scrollIntoView({behavior:'smooth',block:'start'});
});
$('scanSources')?.addEventListener('click',()=>{
 $('scanSourceDetails').open=true;
 $('scanSourceDetails').scrollIntoView({behavior:'smooth',block:'start'});
});
render();
})();