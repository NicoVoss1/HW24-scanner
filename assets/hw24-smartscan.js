/* HW24 v4.5 test: source-aware single-scan reconciliation and small maintenance basket.
   Read-only source handling; all edits are local drafts. Historical content is
   clearly marked and requires one explicit acceptance before CSV export.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
const labels=['Artikelnummer','GTIN','HAN','ASIN','Hersteller','Artikelname',
 'Kurzbeschreibung','Beschreibung','Warengruppe','Kosten','Netto-VK','Brutto-VK',
 'Steuerklasse','Kategorie Ebene 1','Kategorie Ebene 2','Inhalt/Menge',
 'Maßeinheit','Ø EK netto (GLD)','Letzter EK (JTL)'];
const liveMap={'Artikelnummer':'artikelnummer','GTIN':'ean','HAN':'han',
 'ASIN':'asin','Hersteller':'hersteller','Artikelname':'artikelname',
 'Kurzbeschreibung':'kurzbeschreibung','Beschreibung':'beschreibung',
 'Netto-VK':'netto_vk','Kosten':'kosten','Warengruppe':'warengruppe_name',
 'Steuerklasse':'steuerklasse_name','Ø EK netto (GLD)':'netto_ek',
 'Letzter EK (JTL)':'letzter_ek'};
const alias={'artikelname':'Artikelname','name':'Artikelname','citemname':'Artikelname',
 'hersteller':'Hersteller','marke':'Hersteller','warengruppe':'Warengruppe',
 'kurzbeschreibung':'Kurzbeschreibung','beschreibung':'Beschreibung',
 'han':'HAN','gtin':'GTIN','ean':'GTIN','asin':'ASIN',
 'asin aus jtl':'ASIN','casin1':'ASIN','kosten':'Kosten'};
const autoText=new Set(['Artikelname','Hersteller','Warengruppe','Kurzbeschreibung','Beschreibung']);
const sensitive=new Set(['GTIN','HAN','ASIN','Kosten','Netto-VK','Brutto-VK',
 'Steuerklasse','Artikelnummer','Ø EK netto (GLD)','Letzter EK (JTL)']);
const normalize=x=>v(x).normalize('NFKC').replace(/\s+/g,' ').toLocaleLowerCase('de');
let scan='',record=null,baseline={},jtl=null,history=null,origins=new Map(),pending=new Set(),edited=new Set();
let basket=[],main,info,table,confirmBtn,basketList,basketMessage,finished=false;
const maxBasket=100;
const css=document.createElement('style');
css.textContent='.hw24-sources{background:#fff;border:1px solid #d3dfed;border-radius:12px;padding:14px;margin:13px 0}.hw24-sources h3{margin:0 0 7px}.hw24-sources p{font-size:13px;color:#53667f}.hw24-sources details{margin-top:12px}.hw24-sources details>summary{cursor:pointer;font-weight:700}.hw24-sources table{width:100%;border-collapse:collapse;display:table;max-width:100%}.hw24-sources table tr{display:table-row}.hw24-sources table td,.hw24-sources table th{font-size:12px;display:table-cell;white-space:normal;word-break:break-word;border-bottom:1px solid #e4eaf3;padding:8px 5px;text-align:left}.hw24-source-live{color:#166b42;font-weight:700}.hw24-source-history{color:#905900;font-weight:700}.hw24-source-missing{color:#a83239}.hw24-small-basket{padding:9px;border:1px solid #d3dfed;border-radius:9px;margin:8px 0;background:#f6f9fd}.hw24-small-basket button{background:#eef3fa;color:#163966}.hw24-sources button{margin:6px 8px 6px 0}.hw24-sources [hidden]{display:none!important}';
document.head.append(css);
function node(tag,text,parent){const o=document.createElement(tag);if(text!==undefined)o.textContent=text;if(parent)parent.append(o);return o}
function start(){
 const scanNode=$('scanCockpit');if(!scanNode)return;
 main=node('section',undefined);main.className='hw24-sources';main.id='hw24MergedSources';
 node('h3','🔎 Ein Scan · JTL und historische Daten',main);
 info=node('p','EAN scannen. Die Quellen werden automatisch parallel abgefragt.',main);
 confirmBtn=node('button','Alle historischen Ergänzungen prüfen und freigeben',main);
 confirmBtn.type='button';confirmBtn.hidden=true;confirmBtn.onclick=approve;
 const details=node('details',undefined,main);node('summary','Felder, Werte und Datenherkunft ansehen',details);
 table=node('table',undefined,details);
 const b=node('div',undefined,main);b.className='hw24-small-basket';
 node('strong','📦 Begleitende Artikelpflege · bis zu 100 weitere Artikel',b);
 node('p','Nach der Bearbeitung zur Liste hinzufügen. Beim Ameise-Export kommen nur bestätigte Änderungen der Zusatzartikel hinzu.',b);
 const add=node('button','Aktuellen Artikel zur Pflege-Liste hinzufügen',b);
 add.type='button';add.addEventListener('click',addToBasket);
 basketList=node('div',undefined,b);
 basketMessage=node('p','Noch keine weiteren Artikel vorgemerkt.',b);
 scanNode.after(main);
}
function begin(e,rec){
 scan=v(e);record=rec||null;baseline={...(record||{})};jtl=null;history=null;
 origins.clear();pending.clear();edited.clear();finished=false;render();
}
function storeJtl(e,data){if(scan===v(e))jtl=data;}
function storeHistory(e,data){if(scan===v(e))history=data;}
function historicCandidates(){
 const found=new Map(),conflicts=new Set();
 if(!history?.ok||!Array.isArray(history.matches))return {found,conflicts};
 const vals=new Map();
 for(const row of history.matches){
  for(const c of row.cells||[]){
   if(!c.header_verified)continue;
   const key=alias[normalize(c.label)],vv=v(c.value);
   if(!key||!vv||vv.length>7500)continue;
   if(sensitive.has(key))continue; // identity, tax, prices never auto-filled from old Excel
   if(!vals.has(key))vals.set(key,new Map());
   const m=vals.get(key),sig=normalize(vv);
   if(!m.has(sig))m.set(sig,{value:vv,source:(row.sheet_name||'Excel')+' · Zeile '+row.excel_row});
  }
 }
 for(const [k,m] of vals){
  if(m.size!==1){conflicts.add(k);continue}
  found.set(k,[...m.values()][0]);
 }
 return {found,conflicts};
}
function putField(key,content){
 if(!record||v(record[key])===v(content))return false;
 if(v(record[key]))return false;
 const target=[...document.querySelectorAll('#fields .field')].find(x=>x.querySelector('label')?.textContent===key);
 const control=target?.querySelector('input,textarea');
 if(control?.dataset.userEdited==='1')return false;
 record[key]=content;
 if(control&&!control.readOnly){control.value=content;target.className=target.className.replace('bad','good')}
 return true;
}
function reconcile(e,rec){
 if(scan!==v(e)||!rec||record!==rec)return;
 const live=jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1?jtl.matches[0]:null;
 const {found,conflicts}=historicCandidates();
 const preset=window.hw24ApplyTemplateDefaults?.(record,jtl)||[];
 let filled=preset.length;
 for(const [key,data] of found){
  if(!autoText.has(key))continue;
  if(live&&v(live[liveMap[key]]))continue;
  if(putField(key,data.value))filled++;
 }
 for(const key of labels){
  if(edited.has(key)){origins.set(key,{name:'Manuell geprüft',kind:'manual'});continue}
  if(preset.includes(key)){origins.set(key,{name:'Gespeicherte Anlagevorlage',kind:'template'});continue}
  const cur=v(record[key]);
  const liveValue=live&&liveMap[key]?v(live[liveMap[key]]):'';
  const hist=found.get(key);
  if(liveValue&&normalize(cur)===normalize(liveValue)){
   origins.set(key,{name:'JTL-Spiegel',kind:'live'});continue;
  }
  if(v(baseline[key])&&cur&&normalize(cur)===normalize(baseline[key])){
   origins.set(key,{name:'CSV-Arbeitskopie',kind:'csv'});continue;
  }
  if(hist&&cur&&normalize(cur)===normalize(hist.value)){
   origins.set(key,{name:'Historie: '+hist.source,kind:'history'});
   pending.add(key);continue;
  }
  if(conflicts.has(key)&&!cur){
   origins.set(key,{name:'Historie widersprüchlich',kind:'conflict'});continue;
  }
  origins.set(key,cur?{name:'Entwurf / nicht verifiziert',kind:'draft'}:{name:'Nicht belegt',kind:'missing'});
 }
 if(filled){
  window.hw24AmeiseCurrent?.(record);
  window.hw24ProfileCurrent?.(record);
  window.hw24ChannelCurrent?.(record);
  window.hw24QualityEdit?.(record);
 }
 window.hw24FlowRefresh?.();
 window.hw24AmeiseRefresh?.();
 if(!document.activeElement?.closest?.('#hw24NamingRoot'))window.hw24NamingRefresh?.();
 window.hw24TemplatesRefresh?.();
 finished=true;
 render();
}
function render(){
 if(!info)return;
 if(!scan||!record){info.textContent='EAN scannen. Beide Datenquellen werden ohne weitere Klicks geprüft.';table?.replaceChildren();confirmBtn.hidden=true;renderBasket();return}
 const ready=jtl?.jtl_status==='current_snapshot' ? 'JTL '+(jtl.matches||[]).length+' Treffer' : jtl?'JTL: '+jtl.jtl_status:'JTL wird abgefragt';
 const hist=history?.ok ? 'Historie '+(history.matches||[]).length+' Fundstellen' : history?'Historie nicht verfügbar':'Historie wird abgefragt';
 const missing=labels.filter(k=>!v(record[k])).length;
 info.textContent=ready+' · '+hist+' · '+missing+' offene Felder · '+pending.size+' historische Angaben noch zu bestätigen. Originaldaten bleiben unverändert.';
 confirmBtn.hidden=!pending.size;
 const shown=labels.map(key=>[key,v(record[key]),origins.get(key)||{name:'Abfrage läuft',kind:'missing'}]);
 table.replaceChildren();
 const head=node('thead',undefined,table),tr=node('tr',undefined,head);
 for(const h of ['Feld','Wert im Entwurf','Quelle / Bewertung'])node('th',h,tr);
 const body=node('tbody',undefined,table);
 for(const [key,val,src] of shown){
  const r=node('tr',undefined,body);
  node('td',key,r);node('td',val.length>140?val.slice(0,137)+'…':val||'—',r);
  const mark=node('td',src.name+(pending.has(key)?' · bitte prüfen':''),r);
  mark.className=src.kind==='live'?'hw24-source-live':src.kind==='history'?'hw24-source-history':src.kind==='missing'||src.kind==='conflict'?'hw24-source-missing':'';
 }
 renderBasket();
}
function approve(){
 for(const k of pending){edited.add(k);origins.set(k,{name:'Historie geprüft und freigegeben',kind:'manual'})}
 pending.clear();
 window.hw24AmeiseRefresh?.();render();
}
function isUniqueLive(){
 return jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1&&v(record?.Artikelnummer);
}
function addToBasket(){
 if(!isUniqueLive()){basketMessage.textContent='Nur eindeutig identifizierte vorhandene JTL-Artikel können zur Begleitpflege hinzugefügt werden.';return}
 if(pending.size){basketMessage.textContent='Bitte zunächst historische Ergänzungen einmal gesammelt freigeben.';return}
 const keys=window.hw24TemplateExportFields?.()||[];
 const changes=[...edited].filter(k=>keys.includes(k)&&v(record[k])&&k!=='Artikelnummer'&&k!=='GTIN');
 if(!changes.length){basketMessage.textContent='Noch keine bestätigte Änderung in ausgewählten Exportfeldern. Bearbeite ein Feld oder übernimm historische Ergänzungen.';return}
 const id=v(record.Artikelnummer);
 const row={Artikelnummer:id};
 for(const k of changes)row[k]=v(record[k]);
 basket=basket.filter(x=>v(x.Artikelnummer)!==id);
 if(basket.length>=maxBasket){basketMessage.textContent='Maximal 100 zusätzliche Artikel pro Browser-Sitzung. Bitte die CSV exportieren und eine neue Liste beginnen.';return}
 basket.push(row);
 basketMessage.textContent=changes.length+' geprüfte Änderungen zu Artikel '+id+' vorgemerkt.';
 renderBasket();
}
function renderBasket(){
 if(!basketList)return;
 basketList.replaceChildren();
 for(const b of basket){
  const r=node('div',undefined,basketList);r.className='hw24-small-basket';
  node('span',b.Artikelnummer+' · '+(Object.keys(b).length-1)+' Änderungen',r);
  const del=node('button','Entfernen',r);del.type='button';
  del.onclick=()=>{basket=basket.filter(x=>x!==b);renderBasket()};
 }
}
function onEdit(ev){
 const box=ev.target.closest?.('#fields .field');
 if(!box||!record)return;
 const k=box.querySelector('label')?.textContent;
 if(!k)return;
 edited.add(k);pending.delete(k);origins.set(k,{name:'Manuell geändert',kind:'manual'});
 render();
}
document.addEventListener('input',onEdit);
window.hw24SmartScanBegin=begin;
window.hw24SmartScanJtl=storeJtl;
window.hw24SmartScanHistory=storeHistory;
window.hw24SmartScanFinish=reconcile;
window.hw24SourceFor=key=>origins.get(key)||null;
window.hw24FieldExportApproved=key=>!pending.has(key);
window.hw24ScanReady=()=>finished&&jtl?.jtl_status==='current_snapshot';
window.hw24AuditExtraRows=()=>basket.map(x=>({...x}));
window.hw24MainRecord=()=>record;
window.hw24SourceState=()=>({ean:scan,record,jtl,history,pending:[...pending],finished,basket:basket.map(x=>({...x}))});
window.hw24ApproveHistory=approve;
window.hw24MarkEdited=key=>{
 if(!record||!key)return;
 edited.add(key);pending.delete(key);
 origins.set(key,{name:'Manuell bearbeitet, noch nicht in JTL',kind:'manual'});
 render();
};
window.hw24AddMaintenance=addToBasket;
window.hw24RemoveMaintenance=id=>{basket=basket.filter(x=>v(x.Artikelnummer)!==v(id));renderBasket();};
start();
})();