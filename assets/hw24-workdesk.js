/* HW24 v4.6 TEST: focused one-scan workdesk.
 * Reviewed field edits persist to HW24 curated revisions; NO automatic JTL writes.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
const keyFields=['Artikelname','Hersteller','Warengruppe','Inhalt/Menge','Maßeinheit','Kosten','Netto-VK','Steuerklasse','Versandgewicht','Lieferant','ASIN'];
const prices=new Set(['Kosten','Netto-VK','Brutto-VK']);
const locked=new Set(['Ø EK netto (GLD)','Letzter EK (JTL)','Datum letzter EK']);
const fieldViews=new Map(),drafts=new Map();
let main,fields,additional,extraNumber,meta,stateLine,warning,approveBtn,exportBtn,exportMsg,queue,queueMsg,queueBtn,profileSelect,catalogNote,catalogTable,confirmed={};
function node(tag,label,parent,cls){
 const e=document.createElement(tag);if(label!==undefined)e.textContent=label;
 if(cls)e.className=cls;if(parent)parent.append(e);return e;
}
const source=()=>window.hw24SourceState?.()||{};
const empty=(k,x)=>!v(x)||(prices.has(k)&&!(Number(v(x).replace(',','.'))>0));
const provenance=(k,record)=>Object.hasOwn(confirmed,k)&&v(confirmed[k])===v(record?.[k])?{kind:'reviewed',name:'✓ Dauerhaft geprüft · HW24-Datenbank'}:
 window.hw24SourceFor?.(k)||{kind:'draft',name:'Quelle noch nicht bestätigt'};
function legacyControl(k){
 return [...($('fields')?.querySelectorAll('.field')||[])].find(x=>v(x.querySelector('label')?.textContent)===k)?.querySelector('input,textarea');
}
function applyToWorkspace(k,value,s){
 s.record[k]=value;
 const old=legacyControl(k);
 if(old&&!old.readOnly){old.value=value;old.dispatchEvent(new Event('input',{bubbles:true}));}
 window.hw24MarkEdited?.(k);
 window.hw24BatchRefresh?.();
 window.hw24AmeiseCurrent?.(s.record);
 window.hw24ProfileCurrent?.(s.record);
 window.hw24NamingRefresh?.();
}
function draftField(k,view){
 if(view.input.disabled)return;
 drafts.set(k,{status:'dirty',value:view.input.value});
 updateField(k,view,source());
}
async function saveField(k,view,explicit=false){
 if(view.input.disabled)return;
 const s=source(),previous=drafts.get(k);
 if(!s.record||previous?.status==='saving'||(!explicit&&previous?.status!=='dirty'))return;
 const value=v(view.input.value);
 if(!window.hw24CuratedCanSaveField?.(k)){
  drafts.delete(k);
  applyToWorkspace(k,value,s);
  render();
  return;
 }
 if(!value){
  drafts.set(k,{status:'error',value:view.input.value,message:'Bitte einen Wert zum Speichern eingeben.'});
  updateField(k,view,s);
  return;
 }
 const pending={status:'saving',value};
 drafts.set(k,pending);
 updateField(k,view,s);
 const result=await window.hw24CuratedSaveField(k,value);
 if(source().record!==s.record||fieldViews.get(k)!==view)return;
 if(result.ok){
  confirmed={...confirmed,[k]:value};
  applyToWorkspace(k,value,s);
 }
 if(drafts.get(k)===pending){
  if(result.ok)drafts.delete(k);
  else drafts.set(k,{status:'error',value,message:result.message});
 }
 render();
}
function makeField(k,value){
 const item=node('div',undefined,null,'hw24-desk-field');item.dataset.field=k;
 const label=node('label',undefined,item);
 node('strong',k,label);
 const badge=node('small',undefined,label,'hw24-desk-badge');
 const selectOptions={
  'Warengruppe':['Haarfarben','Haarpflege','Haarstyling','Beauty','Haarschmuck','Sammelkarten'],
  'Steuerklasse':['Normaler Steuersatz','Ermäßigter Steuersatz','Steuerfrei'],
  'Maßeinheit':['ml','l','g','kg','Stk'],
  'Versandklasse':['standard'],
  'Artikel aktiv':['Y','N'],
  'Bestandsführung aktiv':['Y','N'],
  'Überverkäufe möglich':['N','Y']
 };
 const textarea=['Beschreibung','Kurzbeschreibung','Artikelname'].includes(k)&&value.length>160;
 const isSelect=Object.hasOwn(selectOptions,k);
 const editor=node('div',undefined,item,'hw24-desk-editor');
 const input=node(isSelect?'select':textarea?'textarea':'input',undefined,editor);
 input.id='hw24DeskField'+fieldViews.size;
 label.htmlFor=input.id;
 if(isSelect)for(const option of ['',...selectOptions[k]])node('option',option||'Bitte auswählen',input).value=option;
 else if(!textarea)input.type='text';
 if(k==='Inhalt/Menge'||k==='Versandgewicht'||k==='Artikelgewicht')input.inputMode='decimal';
 const apply=node('button','Übernehmen',editor,'hw24-desk-apply');apply.type='button';
 apply.setAttribute('aria-label',k+' übernehmen');
 const origin=node('small',undefined,item,'hw24-desk-origin');
 origin.id=input.id+'Status';
 origin.setAttribute('role','status');
 input.setAttribute('aria-describedby',origin.id);
 const reference=node('small',undefined,item,'hw24-desk-origin');
 const view={item,input,badge,origin,reference,apply,isSelect};
 fieldViews.set(k,view);
 input.addEventListener('input',()=>draftField(k,view));
 input.addEventListener('change',()=>{
  if(!isSelect)return;
  if(drafts.get(k)?.value!==input.value)draftField(k,view);
  void saveField(k,view);
 });
 input.addEventListener('blur',()=>{void saveField(k,view);});
 input.addEventListener('keydown',e=>{
  if(isSelect||e.key!=='Enter'||e.isComposing||e.shiftKey)return;
  e.preventDefault();
  void saveField(k,view,true);
 });
 apply.addEventListener('click',()=>{void saveField(k,view,true);});
 return view;
}
function updateField(k,{item,input,badge,origin,reference,apply,isSelect},s){
 const draft=drafts.get(k),value=draft?.value??v(s.record?.[k]),missing=empty(k,value),src=provenance(k,s.record);
 item.classList.toggle('missing',missing&&!draft);
 item.dataset.saveState=draft?.status||(src.kind==='reviewed'?'reviewed':'');
 const status=draft?.status||(missing?'missing':src.kind);
 badge.className='hw24-desk-badge '+status;
 badge.textContent=({dirty:'Offen',saving:'Speichert …',error:'Nicht gespeichert',missing:'Fehlt',live:'JTL',history:'Historie',manual:'Entwurf',reviewed:'Gespeichert'})[status]||'Prüfen';
 input.disabled=!s.finished||locked.has(k)||(['Artikelnummer','GTIN'].includes(k)&&s.jtl?.matches?.length===1);
 if(isSelect&&value&&![...input.options].some(option=>option.value===value))node('option',value,input).value=value;
 if(input.value!==value)input.value=value;
 if(!isSelect)input.placeholder=missing?'Fehlenden Wert eintragen':'';
 apply.hidden=input.disabled;
 const feedback={dirty:'Mit Enter, Übernehmen oder beim Verlassen des Felds bestätigen.',saving:'Wird in der HW24-Datenbank gespeichert …',error:draft?.message};
 const message=draft?feedback[draft.status]:missing?'Nicht belegt':src.name;
 if(origin.textContent!==message)origin.textContent=message;
 reference.textContent='';
 if(k==='Inhalt/Menge'||k==='Maßeinheit'){
  const article=s.jtl?.jtl_status==='current_snapshot'&&s.jtl.matches?.length===1?s.jtl.matches[0]:null;
  if(article){
   const raw=article.jtl_source_fields||{},mass=article.related_sources?.masseinheit?.rows||[];
   const q=v(article.inhalt_menge??raw.fMassMenge);
   const code=v(article.masseinheit)||(mass.length===1?v(mass[0].cDisplayCode||mass[0].cCode||mass[0].cUnitCode):'');
   if(k==='Inhalt/Menge'&&q&&Number.isFinite(Number(q))&&Number(q)>=0)reference.textContent='JTL-Inhalt: '+Number(q).toLocaleString('de-DE')+(code?' '+code:'');
   if(k==='Maßeinheit'&&code)reference.textContent='JTL-Maßeinheit: '+code;
  }
 }
 reference.hidden=!reference.textContent;
}
function renderFields(target,keys,s){
 for(const item of [...target.children])if(!keys.includes(item.dataset.field))item.remove();
 let next=target.firstElementChild;
 for(const k of keys){
  const view=fieldViews.get(k)||makeField(k,v(s.record?.[k]));
  if(view.item!==next)target.insertBefore(view.item,next);
  next=view.item.nextElementSibling;
  updateField(k,view,s);
 }
}
function renderSourcesCatalog(){
 if(!catalogTable)return;
 const s=source(),article=s.jtl?.jtl_status==='current_snapshot'&&s.jtl.matches?.length===1?s.jtl.matches[0]:null;
 const raw=article?.jtl_source_fields||{},related=article?.related_sources||{},historical=s.history?.ok?s.history.matches||[]:[];
 const friendly={
  kHersteller:'Hersteller (interne ID)',kWarengruppe:'Warengruppe (interne ID)',kSteuerklasse:'Steuerklasse (interne ID)',
  kVersandklasse:'Versandklasse (interne ID)',nLagerbestand:'Gesamtbestand (Artikelstamm)',
  fEKNetto:'Durchschnittlicher Einkaufspreis netto',fLetzterEK:'Letzter Einkaufspreis netto',
  fArtikelkosten:'Artikelkosten',cASIN:'ASIN',fAmazonVK:'Amazon-Verkaufspreis',
  fEbayPreis:'eBay-Verkaufspreis',fVKNetto:'Standard-Verkaufspreis netto',
  cName:'Bezeichnung',fPreis1:'Lieferantenpreis Stufe 1',
  fVerfuegbar:'Verfügbarer Bestand',kLieferant:'Lieferant (ID)',
  cLiefArtNr:'Lieferantenartikelnummer',kKategorie:'Kategorie (ID)',
  kShopScx:'Verkaufskanal (ID)',kArtikel:'Artikel (interne ID)'
 };
 const areaNames={kosten:'Artikelkosten',warengruppe:'Warengruppe',steuerklasse:'Steuerklasse',
  versandklasse:'Versandklasse',masseinheit:'Mengeneinheit',
  lieferantenartikel:'Lieferantenartikel',lieferantenpreise:'Lieferantenpreise',
  bestand:'Verfügbarer Bestand',kategorien:'Kategorien',amazon_zuordnung:'Amazon-Angebotszuordnung',
  amazon_angebote:'Amazon-Angebote',artikel_asin:'Weitere ASINs',
  verkaufskanal:'Verkaufskanaldaten',wms_lagerplaetze:'Lagerplätze',steuer_saetze_de:'Steuerzonen DE'};
 catalogNote.textContent=Object.keys(raw).length+' bekannte JTL-Artikelstammfelder · '+Object.keys(related).length+' zusätzliche verifizierte Quellbereiche · '+historical.length+' historische Fundstellen. Fehlende Bereiche sind noch nicht in der Windows-Synchronisation konfiguriert. IDs sind keine Klarnamen.';
 catalogTable.replaceChildren();if(!article)return;
 const head=node('tr',undefined,node('thead',undefined,catalogTable));
 for(const k of ['Quellfeld','Wert','Bedeutung'])node('th',k,head);
 const body=node('tbody',undefined,catalogTable);
 for(const [key,value] of Object.entries(raw)){
  const row=node('tr',undefined,body);
  node('td',friendly[key]||key,row);node('td',v(value)||'—',row);
  node('td',key.startsWith('k')?'Interne JTL-Zuordnung, Klarname wenn verfügbar in der Artikelübersicht':'JTL-Artikelstamm',row);
 }
 for(const [area,detail] of Object.entries(related)){
  const table=v(detail?.table)||'Zusatzquelle';
  const rows=Array.isArray(detail?.rows)?detail.rows:[];
  for(const record of rows){
   for(const [key,val] of Object.entries(record||{})){
    const row=node('tr',undefined,body);
    node('td',(areaNames[area]||area)+' / '+(friendly[key]||key),row);
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
 main.classList.toggle('hw24-no-article',!s.ean);
 if(!s.ean){
  stateLine.textContent='Bereit. Artikelnummer, EAN oder HAN eingeben und prüfen.';
  meta.textContent='Noch kein Artikel ausgewählt.';
  fields.replaceChildren();additional.replaceChildren();extraNumber.textContent='';
  warning.hidden=true;approveBtn.hidden=true;exportBtn.disabled=true;queueBtn.disabled=true;
  renderQueue();if(catalogTable)catalogTable.replaceChildren();if(catalogNote)catalogNote.textContent='Keine EAN ausgewählt.';return;
 }
 const ready=s.jtl?.jtl_status==='current_snapshot';
 const matches=ready?(s.jtl.matches||[]):[];
 const hist=s.history?.ok?(s.history.archived_reference_count??s.history.matches?.length):undefined;
 stateLine.textContent=s.finished?
  (ready?(matches.length===1?'✓ In JTL vorhanden':matches.length===0?'◌ Mögliche Neuanlage':'⚠ EAN mehrfach vorhanden'):'⚠ JTL-Daten nicht bestätigt')+
  ' · Historie: '+(hist===undefined?'nicht verfügbar':hist+' Fundstellen')+
  ' · JTL-Snapshot: '+(s.jtl?.snapshot_at||'?')+' UTC'+
  (Number.isFinite(Number(s.jtl?.age_minutes))?' · '+s.jtl.age_minutes+' Minuten alt':'')
  :'🔄 JTL und historische Daten werden parallel geprüft …';
 meta.textContent='Art.-Nr. '+(v(matches[0]?.artikelnummer)||v(r.Artikelnummer)||'—')+
  ' · EAN '+(v(matches[0]?.ean)||v(r.GTIN)||'—')+
  ' · HAN '+(v(matches[0]?.han)||v(r.HAN)||'—');
 const selected=window.hw24TemplateExportFields?.()||[];
 const all=[...new Set([...keyFields,...selected])];
 const gaps=all.filter(k=>empty(k,r[k]));
 const rest=all.filter(k=>!keyFields.includes(k));
 renderFields(fields,keyFields,s);
 renderFields(additional,rest,s);
 extraNumber.textContent=' · '+rest.length+' Felder';
 warning.hidden=false;
 warning.textContent=s.finished?gaps.length+' offene Vorlagenfelder (Details aufklappen). '+(s.pending?.length||0)+' historische Vorschläge prüfen.'+((s.blocked||[]).includes('Warengruppe')?' ⚠ JTL-Warengruppe-ID vorhanden, Klarname nicht synchronisiert. Kein Ameise-Export mit vermuteter Kategorie.':''):
  'Prüfung läuft. CSV-Export erst nach dem JTL-Abgleich.';
 approveBtn.hidden=!(s.pending?.length);
 approveBtn.textContent=(s.pending?.length||0)+' historische Ergänzungen gesammelt bestätigen';
 exportBtn.disabled=!s.finished||!ready||matches.length>1||(s.blocked||[]).includes('Warengruppe');
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
 meta=node('p','Noch kein Artikel ausgewählt.',desk,'hw24-desk-muted hw24-primary-meta');
 node('h3','Artikel prüfen',desk,'hw24-primary-heading');
 fields=node('div',undefined,desk,'hw24-desk-grid');
 const advancedFields=node('details',undefined,desk,'hw24-more-fields');
 const title=node('summary','Weitere Artikelfelder',advancedFields);
 extraNumber=node('span','',title);
 additional=node('div',undefined,advancedFields,'hw24-desk-grid');
 warning=node('p','',desk,'hw24-desk-warning');warning.hidden=true;
 const actions=node('div',undefined,desk,'hw24-desk-actions');
 approveBtn=node('button','Historische Werte bestätigen',actions);approveBtn.type='button';approveBtn.hidden=true;
 approveBtn.onclick=()=>{window.hw24ApproveHistory?.();render()};
 exportBtn=node('button','📥 JTL-Ameise-Prüf-CSV exportieren',actions,'hw24-desk-primary');
 exportBtn.type='button';exportBtn.disabled=true;
 exportMsg=node('p','Erst CSV-Prüfexport und JTL-Ameise-Trockenlauf. Noch kein automatischer JTL-Import.',desk,'hw24-desk-muted');
 exportMsg.id='hw24LegacyExportMessage';
 exportBtn.onclick=()=>{
  const s=source();if(!s.finished||s.jtl?.jtl_status!=='current_snapshot'){exportMsg.textContent='Aktueller JTL-Spiegel fehlt. Export gesperrt.';return;}
  const ok=window.hw24AmeiseAutoExport?.(),status=v($('ameiseSaved')?.textContent);
  exportMsg.textContent=(ok?'CSV erstellt. ':'Export noch nicht möglich. ')+status+
    (s.pending?.length?' Ungeprüfte historische Felder wurden ausgelassen.':'');
 };
 const basketWrap=node('details',undefined,desk);
 basketWrap.id='hw24LegacyBasket';
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
 const expert=node('details',undefined,workspace,'hw24-desk-section');
 expert.id='hw24DeskExpert';node('summary','Expertenbereich · Vorlagen, Quellen und technische Details',expert);
 const name=$('hw24NamingRoot');
 if(name){
  const fold=node('details',undefined,expert,'hw24-desk-section');
  node('summary','Artikelname vereinheitlichen',fold);
  fold.append(name);
 }
 const template=$('hw24TemplateSection');
 if(template){const fold=node('details',undefined,expert,'hw24-desk-section');node('summary','Exportvorlagen und Pflichtfelder',fold);fold.append(template);}
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
 // Preserve every original control, but keep secondary workspace panels behind one closed disclosure.
 for(const child of [...workspace.children]){
  if(child===desk||child===expert||child.id==='deploymentNotice'||child===heading)continue;
  expert.append(child);
 }
 const originalStart=window.hw24SmartScanBegin,originalFinish=window.hw24SmartScanFinish;
 if(typeof originalStart==='function')window.hw24SmartScanBegin=(...args)=>{
  fieldViews.clear();drafts.clear();confirmed={};
  fields.replaceChildren();additional.replaceChildren();
  const x=originalStart(...args);render();return x;
 };
 if(typeof originalFinish==='function')window.hw24SmartScanFinish=(...args)=>{
  const x=originalFinish(...args);render();return x;
 };
 render();ean.focus();
}
document.addEventListener('hw24:curated-applied',event=>{
 const data=event.detail||{};
 const record=source().record;
 if(!record||![v(record.GTIN),v(record.EAN),v(record.Suchbegriff)].includes(v(data.ean)))return;
 confirmed=data.fields||{};
 render();
});
window.hw24WorkdeskRender=render;
make();
})();
