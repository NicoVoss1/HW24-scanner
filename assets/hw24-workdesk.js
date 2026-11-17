/* HW24 v4.6 TEST: focused one-scan workdesk.
 * Visual presentation only, no JTL writes or new source assumptions.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
const keyFields=['Artikelname','Hersteller','Warengruppe','ASIN','Kosten','Netto-VK','Steuerklasse','Inhalt/Menge','Maßeinheit'];
const prices=new Set(['Kosten','Netto-VK','Brutto-VK']);
const locked=new Set(['Ø EK netto (GLD)','Letzter EK (JTL)','Datum letzter EK']);
let main,fields,additional,extraNumber,meta,stateLine,warning,approveBtn,exportBtn,exportMsg,queue,queueMsg,queueBtn,profileSelect,catalogNote,catalogTable,manualChanges=new Set();
function node(tag,label,parent,cls){
 const e=document.createElement(tag);if(label!==undefined)e.textContent=label;
 if(cls)e.className=cls;if(parent)parent.append(e);return e;
}
const source=()=>window.hw24SourceState?.()||{};
const empty=(k,x)=>!v(x)||(prices.has(k)&&!(Number(v(x).replace(',','.'))>0));
const provenance=k=>manualChanges.has(k)?{kind:'manual',name:'Manuell bearbeitet · Entwurf'}:
 window.hw24SourceFor?.(k)||{kind:'draft',name:'Quelle noch nicht bestätigt'};
function legacyControl(k){
 return [...($('fields')?.querySelectorAll('.field')||[])].find(x=>v(x.querySelector('label')?.textContent)===k)?.querySelector('input,textarea');
}
function changed(k,newValue){
 const s=source();if(!s.record||!s.finished)return;
 if(['Artikelnummer','GTIN'].includes(k)&&s.jtl?.matches?.length===1)return;
 s.record[k]=v(newValue);manualChanges.add(k);
 const old=legacyControl(k);
 if(old&&!old.readOnly){old.value=v(newValue);old.dispatchEvent(new Event('input',{bubbles:true}));}
 window.hw24MarkEdited?.(k);
 window.hw24AmeiseCurrent?.(s.record);
 window.hw24ProfileCurrent?.(s.record);
 window.hw24NamingRefresh?.();
 render();
}
function field(k,target){
 const s=source(),value=v(s.record?.[k]),missing=empty(k,value),src=provenance(k);
 const item=node('div',undefined,target,'hw24-desk-field'+(missing?' missing':''));
 const label=node('label',undefined,item);
 node('strong',k,label);
 node('small',missing?'Fehlt':src.kind==='live'?'JTL':src.kind==='history'?'Historie':src.kind==='manual'?'Bearbeitet':'Prüfen',label,'hw24-desk-badge '+(missing?'missing':src.kind));
 const textarea=['Beschreibung','Kurzbeschreibung','Artikelname'].includes(k)&&value.length>160;
 const input=node(textarea?'textarea':'input',undefined,item);
 if(!textarea)input.type='text';input.value=value;input.placeholder=missing?'Fehlenden Wert eintragen':'';
 input.disabled=!s.finished||locked.has(k)||(['Artikelnummer','GTIN'].includes(k)&&s.jtl?.matches?.length===1);
 input.addEventListener('change',()=>changed(k,input.value));
 node('small',missing?'Nicht belegt':src.name,item,'hw24-desk-origin');
}
function renderSourcesCatalog(){
 if(!catalogTable)return;
 const s=source(),article=s.jtl?.jtl_status==='current_snapshot'&&s.jtl.matches?.length===1?s.jtl.matches[0]:null;
 const raw=article?.jtl_source_fields||{},related=article?.related_sources||{},historical=s.history?.ok?s.history.matches||[]:[];
 catalogNote.textContent=Object.keys(raw).length+' bekannte JTL-Artikelstammfelder · '+Object.keys(related).length+' zusätzliche verifizierte Quellbereiche · '+historical.length+' historische Fundstellen. Fehlende Bereiche sind noch nicht in der Windows-Synchronisation konfiguriert. IDs sind keine Klarnamen.';
 catalogTable.replaceChildren();if(!article)return;
 const head=node('tr',undefined,node('thead',undefined,catalogTable));
 for(const k of ['Quellfeld','Wert','Bedeutung'])node('th',k,head);
 const body=node('tbody',undefined,catalogTable);
 for(const [key,value] of Object.entries(raw)){
  const row=node('tr',undefined,body);
  node('td',key,row);node('td',v(value)||'—',row);
  node('td',key.startsWith('k')?'Interne ID, noch kein Klarname':'JTL-Artikelstamm',row);
 }
 for(const [area,detail] of Object.entries(related)){
  const table=v(detail?.table)||'Zusatzquelle';
  const rows=Array.isArray(detail?.rows)?detail.rows:[];
  for(const record of rows){
   for(const [key,val] of Object.entries(record||{})){
    const row=node('tr',undefined,body);
    node('td',area+' / '+key,row);
    node('td',v(val)||'—',row);
    node('td',table+' · JTL-Snapshot'+(detail?.truncated?' · begrenzte Trefferliste':'') ,row);
   }
  }
 }
}
function renderQueue(){
 const rows=source().basket||[];queue.replaceChildren();
 queueMsg.textContent=rows.length+' zusätzliche Artikel vorgemerkt. Maximal 100 pro Sitzung. Vor Neuladen CSV exportieren.';
 for(const row of rows){
  const line=node('div',undefined,queue,'hw24-desk-queue');
  node('span',v(row.Artikelnummer)+' · '+(Object.keys(row).length-1)+' Änderungen',line);
  const del=node('button','Entfernen',line);
  del.type='button';del.onclick=()=>{window.hw24RemoveMaintenance?.(row.Artikelnummer);renderQueue()};
 }
}
function render(){
 if(!main)return;
 const s=source(),r=s.record||{};
 if(!s.ean){
  stateLine.textContent='Bereit. Artikelnummer, EAN oder HAN eingeben und prüfen.';
  meta.textContent='Noch kein Artikel ausgewählt.';
  fields.replaceChildren();additional.replaceChildren();extraNumber.textContent='';
  warning.hidden=true;approveBtn.hidden=true;exportBtn.disabled=true;queueBtn.disabled=true;
  renderQueue();if(catalogTable)catalogTable.replaceChildren();if(catalogNote)catalogNote.textContent='Keine EAN ausgewählt.';return;
 }
 const ready=s.jtl?.jtl_status==='current_snapshot';
 const matches=ready?(s.jtl.matches||[]):[];
 const hist=s.history?.ok?s.history.matches?.length:undefined;
 stateLine.textContent=s.finished?
  (ready?(matches.length===1?'✓ In JTL vorhanden':matches.length===0?'◌ Mögliche Neuanlage':'⚠ EAN mehrfach vorhanden'):'⚠ JTL-Daten nicht bestätigt')+
  ' · Historie: '+(hist===undefined?'nicht verfügbar':hist+' Fundstellen')+
  ' · JTL-Snapshot: '+(s.jtl?.snapshot_at||'?')+' UTC'+
  (Number.isFinite(Number(s.jtl?.age_minutes))?' · '+s.jtl.age_minutes+' Minuten alt':'')
  :'🔄 JTL und historische Daten werden parallel geprüft …';
 meta.textContent='Suche '+s.ean+' · '+(v(r.Artikelname)||v(matches[0]?.artikelname)||'Artikelname nicht belegt')+
  ' · Artikelnummer '+(v(r.Artikelnummer)||v(matches[0]?.artikelnummer)||'nicht belegt')+
  ' · EAN '+(v(r.GTIN)||v(matches[0]?.ean)||'nicht belegt')+
  ' · HAN '+(v(r.HAN)||v(matches[0]?.han)||'nicht belegt')+
  ' · Warengruppe '+(v(r.Warengruppe)||v(matches[0]?.warengruppe_name)||'nicht belegt')+
  ' · Historische Werte sind keine Live-Stammdaten.';
 const selected=window.hw24TemplateExportFields?.()||[];
 const all=[...new Set([...keyFields,...selected])];
 const gaps=all.filter(k=>empty(k,r[k]));
 const first=[...new Set([...keyFields,...gaps])];
 fields.replaceChildren();
 for(const k of first.slice(0,16))field(k,fields);
 additional.replaceChildren();
 const visible=new Set(first.slice(0,16)),rest=all.filter(k=>!visible.has(k));
 for(const k of rest)field(k,additional);
 extraNumber.textContent=' · '+rest.length+' Felder';
 warning.hidden=false;
 warning.textContent=s.finished?gaps.length+' unvollständige Felder der aktiven Vorlage. '+(s.pending?.length||0)+' historische Ergänzungen ungeprüft.':
  'Prüfung läuft. CSV-Export erst nach dem JTL-Abgleich.';
 approveBtn.hidden=!(s.pending?.length);
 approveBtn.textContent=(s.pending?.length||0)+' historische Ergänzungen gesammelt bestätigen';
 exportBtn.disabled=!s.finished||!ready||matches.length>1;
 queueBtn.disabled=!s.finished||!ready||matches.length!==1;
 renderQueue();renderSourcesCatalog();
}
function make(){
 const workspace=$('hw24ScanWorkspace'),ean=$('ean'),scan=$('scanStart');
 if(!workspace||!ean||!scan)return;
 document.body.classList.add('hw24-desk-mode');
 const desk=node('section',undefined,null,'hw24-desk');
 desk.id='hw24Desk'; // Must match WMS focus CSS; otherwise the entire workspace is hidden.
 workspace.insertBefore(desk,workspace.firstElementChild);
 main=desk;node('h2','Artikelnummer, EAN oder HAN scannen · prüfen · exportieren',desk);
 node('p','Eine Kennung, ein JTL-Abgleich mit Historie und ein Ameise-Export.',desk,'hw24-desk-muted');
 const bar=node('div',undefined,desk,'hw24-desk-bar');
 const scanWrap=node('div',undefined,bar,'hw24-desk-scan');
 node('label','Artikelnummer / EAN / HAN',scanWrap);scanWrap.append(ean);ean.placeholder='EAN, Artikelnummer (z. B. AR...) oder HAN';
 ean.onchange=null; // One explicit scan on Enter or button, not both blur and click.
 bar.append(scan);
 const profileWrap=node('div',undefined,bar,'hw24-desk-profile');
 node('label','Exportvorlage',profileWrap);
 profileSelect=node('select',undefined,profileWrap);
 const oldProfile=$('hw24TemplateSelect');
 function syncProfiles(){
  if(!oldProfile)return;
  profileSelect.replaceChildren();
  for(const op of oldProfile.options){const next=node('option',op.textContent,profileSelect);next.value=op.value}
  profileSelect.value=oldProfile.value;
 }
 syncProfiles();
 profileSelect.addEventListener('change',()=>{
  if(oldProfile){oldProfile.value=profileSelect.value;oldProfile.dispatchEvent(new Event('change',{bubbles:true}));}
  render();
 });
 oldProfile?.addEventListener('change',()=>{syncProfiles();render()});
 const oldStatus=$('status');if(oldStatus)desk.append(oldStatus);
 stateLine=node('div','Bereit. Einmal EAN eingeben und prüfen.',desk,'hw24-desk-status');
 meta=node('p','Noch kein Artikel ausgewählt.',desk,'hw24-desk-muted');
 node('h3','Artikelangaben · Lücken zuerst',desk);
 fields=node('div',undefined,desk,'hw24-desk-grid');
 const advancedFields=node('details',undefined,desk);
 const title=node('summary','Weitere Felder aus Deiner Vorlage',advancedFields);
 extraNumber=node('span','',title);
 additional=node('div',undefined,advancedFields,'hw24-desk-grid');
 warning=node('p','',desk,'hw24-desk-warning');warning.hidden=true;
 const actions=node('div',undefined,desk,'hw24-desk-actions');
 approveBtn=node('button','Historische Werte bestätigen',actions);approveBtn.type='button';approveBtn.hidden=true;
 approveBtn.onclick=()=>{window.hw24ApproveHistory?.();render()};
 exportBtn=node('button','📥 JTL-Ameise-Prüf-CSV exportieren',actions,'hw24-desk-primary');
 exportBtn.type='button';exportBtn.disabled=true;
 exportMsg=node('p','Erst CSV-Prüfexport und JTL-Ameise-Trockenlauf. Noch kein automatischer JTL-Import.',desk,'hw24-desk-muted');
 exportBtn.onclick=()=>{
  const s=source();if(!s.finished||s.jtl?.jtl_status!=='current_snapshot'){exportMsg.textContent='Aktueller JTL-Spiegel fehlt. Export gesperrt.';return;}
  const ok=window.hw24AmeiseAutoExport?.(),status=v($('ameiseSaved')?.textContent);
  exportMsg.textContent=(ok?'CSV erstellt. ':'Export noch nicht möglich. ')+status+
    (s.pending?.length?' Ungeprüfte historische Felder wurden ausgelassen.':'');
 };
 const basketWrap=node('details',undefined,desk);
 node('summary','Weitere Artikel für denselben Export mitpflegen',basketWrap);
 queueMsg=node('p','Bis zu 100 weitere Artikelkorrekturen.',basketWrap,'hw24-desk-muted');
 queueBtn=node('button','Geänderten Artikel zur Liste hinzufügen',basketWrap);
 queueBtn.type='button';queueBtn.disabled=true;
 const queueResult=node('p','Nur bestätigte Änderungen und eindeutige JTL-Artikelnummern werden übernommen.',basketWrap,'hw24-desk-muted');
 queueBtn.onclick=()=>{
  const before=source().basket?.length||0;window.hw24AddMaintenance?.();
  queueResult.textContent=(source().basket?.length||0)>before?'Artikel vorgemerkt. Nächste EAN scannen.':'Bitte zuerst ein relevantes Feld ändern oder historische Vorschläge freigeben.';
  renderQueue();
 };
 queue=node('div',undefined,basketWrap);
 const name=$('hw24NamingRoot');
 if(name){
  const fold=node('details',undefined,workspace,'hw24-desk-section');
  node('summary','✏️ Optional: Artikelname vereinheitlichen',fold);
  fold.append(name);
 }
 const template=$('hw24TemplateSection');
 if(template)workspace.append(template);
 const expert=node('details',undefined,workspace,'hw24-desk-section');
 expert.id='hw24DeskExpert';node('summary','Weitere Funktionen und technische Details',expert);
 const catalog=node('details',undefined,expert);
 node('summary','Weitere verfügbare JTL-Quellfelder ansehen',catalog);
 catalogNote=node('p','Nach einem Scan erscheinen die vorhandenen Originalfelder.',catalog,'hw24-desk-muted');
 catalogTable=node('table',undefined,catalog);catalogTable.className='hw24-desk-catalog';
 for(const id of ['scanCockpit','hw24MergedSources','unknownActions','historyRetry','scanSourceDetails','masterDataDetails','ameisePanel','channelMore']){
  const elem=$(id);if(elem)expert.append(elem);
 }
 const spare=[...workspace.children].find(x=>x.tagName==='DETAILS'&&v(x.querySelector('summary')?.textContent).startsWith('Separat: Wareneingang'));
 if(spare)expert.append(spare);
 for(const other of [...document.querySelectorAll('details.scan-advanced')]){
  if(!expert.contains(other))expert.append(other);
 }
 const importer=$('importArea');
 if(importer)expert.append(importer.parentElement?.tagName==='DETAILS'?importer.parentElement:importer);
 const heading=[...workspace.children].find(x=>x.tagName==='H3'&&v(x.textContent).startsWith('1. EAN'));
 if(heading)heading.hidden=true;
 const originalStart=window.hw24SmartScanBegin,originalFinish=window.hw24SmartScanFinish;
 if(typeof originalStart==='function')window.hw24SmartScanBegin=(...args)=>{
  manualChanges.clear();const x=originalStart(...args);render();return x;
 };
 if(typeof originalFinish==='function')window.hw24SmartScanFinish=(...args)=>{
  const x=originalFinish(...args);render();return x;
 };
 render();ean.focus();
}
window.hw24WorkdeskRender=render;
make();
})();