/* HW24 V4.22 · Scanliste automatic enrichment and ASIN selection.
 * The fast scan stays available even when JTL, Amazon or curated sources fail.
 * Existing JTL articles are always view-only here. Nothing writes to JTL or WMS.
 */
(function(){
'use strict';
const E=window.HW24Enrich,B=window.HW24ScanBridge;
if(!E||!B)return;
const memo=new Map(),busy=new Set(),selectedAsins=new Map();
const v=x=>String(x??'').trim();
const workKey='hw24_scan_enrichment_queue_v1';
const pending=(()=>{
 try{
  const raw=JSON.parse(localStorage.getItem(workKey)||'[]');
  return Array.isArray(raw)?raw.filter(x=>Number.isSafeInteger(Number(x?.id))&&Number(x.id)>0&&
    Number.isSafeInteger(Number(x?.batchId))&&E.goodGtin(x?.ean)).map(x=>({...x,id:Number(x.id),batchId:Number(x.batchId)})).slice(0,3000):[];
 }catch(_){return []}
})();
let working=false,paused=false;
const queueState=document.getElementById('enrichQueueStatus');
const retry=document.createElement('button');
retry.type='button';retry.className='secondary';retry.textContent='Anreicherung erneut starten';
retry.style.cssText='font-size:12px;margin-left:9px;padding:6px 10px';retry.hidden=true;
queueState?.append(retry);
function saveQueue(){try{localStorage.setItem(workKey,JSON.stringify(pending));}catch(_){}}
function updateQueue(text){
 if(!queueState)return;
 const msg=queueState.firstChild?.nodeType===3?queueState.firstChild:
   queueState.insertBefore(document.createTextNode(''),queueState.firstChild);
 msg.textContent=pending.length+' Datensätze zur Anreicherung'+(text?' · '+text:'');
 retry.hidden=!paused;
}
function enqueue(id,ean,batchId){
 id=Number(id);batchId=Number(batchId);
 if(!E.goodGtin(ean)||!Number.isSafeInteger(id)||id<1||!Number.isSafeInteger(batchId))return;
 if(!pending.some(x=>x.id===id&&x.batchId===batchId)){
  pending.push({id,ean,batchId});saveQueue();updateQueue();
 }
 if(!working&&!paused)runQueue();
}
async function runQueue(){
 if(working||paused||!pending.length)return;
 working=true;
 while(pending.length&&!paused){
  const job=pending[0];
  if(Number(job.batchId)!==Number(B.batchId())){
   updateQueue('Für andere Stapel später fortsetzen');break;
  }
  const item=B.find(job.id);
  if(!item||item.resolution!=='new_candidate'){
   pending.shift();saveQueue();continue;
  }
  updateQueue('Prüfe EAN '+job.ean+' in JTL, Historie und Amazon');
  try{
   const sources=await Promise.allSettled([
     E.jtl(job.ean),E.curated(job.ean),E.history(job.ean),E.catalog(job.ean)
   ]);
   const jtl=sources[0].status==='fulfilled'?sources[0].value:null;
   if(jtl?.jtl_status!=='current_snapshot'){
    paused=true;updateQueue('JTL nicht aktuell, Anreicherung ausgesetzt');break;
   }
   if(jtl.matches?.length!==0){
    pending.shift();saveQueue();continue; // existing or multiple: never new
   }
   const curated=sources[1].status==='fulfilled'?sources[1].value:null;
   const history=sources[2].status==='fulfilled'?sources[2].value:null;
   const catalog=sources[3].status==='fulfilled'?sources[3].value:null;
   const model=E.build({ean:job.ean,curated,history,catalog,isNew:true});
   const result=await B.backgroundApply(job.id,job.ean,model.fields,model.sources);
   if(!result.ok){
    if(result.error==='revision_conflict'){
      pending.shift();saveQueue();
      updateQueue('Artikel zwischenzeitlich geändert. Zur erneuten Prüfung Liste aktualisieren.');
      continue;
    }
    paused=true;updateQueue('DB-Änderung nicht möglich: '+String(result.error).slice(0,100));break;
   }
   if(sources[3].status!=='fulfilled'){
     paused=true;
     updateQueue('Amazon derzeit nicht erreichbar. Belegte Stammdaten gesichert; EAN für erneute Prüfung vorgemerkt.');
     break;
   }
   pending.shift();saveQueue();
   updateQueue('EAN '+job.ean+' abgeglichen');
   await new Promise(resolve=>setTimeout(resolve,1500));
  }catch(e){
   paused=true;updateQueue('Unterbrochen: '+String(e?.message||e).slice(0,100));break;
  }
 }
 working=false;retry.hidden=!paused;
}
retry.onclick=()=>{paused=false;updateQueue('Wiederaufnahme');runQueue()};
updateQueue();

const status=msg=>B.status(msg);
function active(item){return String(B.selected()?.id)===String(item?.id)}
function clearChoices(){const node=document.getElementById('amazonChoices');if(node)node.replaceChildren()}
function candidates(item){
 const dest=document.getElementById('amazonChoices');
 if(!dest||!active(item))return;
 const catalog=B.catalog(item.ean);
 clearChoices();
 if(!catalog){
  const p=document.createElement('p');p.className='note';
  p.textContent='Amazon-Katalog wird im Hintergrund geprüft. Weitere ASINs und Packungsgrößen erscheinen hier.';
  dest.append(p);
 }
 const choices=E.amazonChoices(catalog,item.ean);
 const known=new Set(choices.map(c=>c.asin));
 const refs=memo.get(item.id);
 const knownJtl=refs?.jtl?.jtl_status==='current_snapshot'&&
  refs?.jtl?.matches?.length===1?refs.jtl.matches[0]:null;
 for(const candidate of knownJtl?.asin_candidates||[]){
  const asin=v(candidate.asin).toUpperCase();
  if(!/^[A-Z0-9]{10}$/.test(asin)||known.has(asin))continue;
  choices.push({asin,title:v(knownJtl.artikelname),brand:v(knownJtl.hersteller),
   pack:false,exact:false,source:'JTL-Amazon-Zuordnung'});
  known.add(asin);
 }
 const hist=E.verifiedHistory(refs?.history||null);
 for(const asin of hist.asins||[]){
  if(known.has(asin))continue;
  choices.push({asin,title:v(item.draft?.Artikelname||item.artikelname),
   brand:v(item.draft?.Hersteller),pack:false,exact:false,source:'Historischer Excel-Beleg'});
  known.add(asin);
 }
 const selected=selectedAsins.get(item.id)||v(B.control('ASIN')?.value)||'';
 if(!choices.length){
  const p=document.createElement('p');p.className='note';
  p.textContent='Keine Amazon-ASIN vorhanden. JTL- und historische Stammdaten bleiben nutzbar.';
  dest.append(p);return;
 }
 E.renderCandidates(dest,choices,selected,async option=>{
  if(!active(item))return;
  if(item.resolution!=='new_candidate'){
   status('Artikel bereits in JTL. In der Scanliste keine neue ASIN-Zuordnung oder Neuanlage.');
   return;
  }
  const info=(option.pack?'Mehrfachpackung/Set':'Einzelartikel')+
    ', '+(option.exact?'EAN im Amazon-Katalog gefunden':'EAN nicht im Amazon-Datensatz bestätigt');
  if(!window.confirm(option.asin+' auswählen? '+info+
    '. Bitte Farbnuance, Produktidentität und Packungsgröße vor der Übernahme prüfen.'))return;
  const existing=v(B.control('ASIN')?.value);
  if(existing&&existing!==option.asin){
   status('ASIN-Feld enthält bereits '+existing+'. Zum Ersetzen bitte den bisherigen Wert manuell löschen und erneut wählen.');
   return;
  }
  const result=await B.apply({ASIN:option.asin},
    {ASIN:'Amazon SP-API · ASIN nach sichtbarer Produktauswahl'}, {save:true});
  if(result.ok){selectedAsins.set(item.id,option.asin);status('✓ ASIN gewählt. Farbnuance und Packungsgröße vor Ameise-Freigabe prüfen.')}
  candidates(item);
 });
 if(item.resolution==='existing'){
  const p=document.createElement('p');p.className='note';
  p.textContent='Bestehende JTL-Artikel sind hier nur lesbar. Zuordnung im Artikelmanager prüfen.';
  dest.append(p);
 }
}
async function enrich(id,ean){
 const item=B.selected();if(!item||String(item.id)!==String(id)||item.ean!==ean||busy.has(id))return;
 busy.add(id);
 try{
  let data=memo.get(id);
  if(!data){
   const sources=await Promise.allSettled([E.jtl(ean),E.curated(ean),E.history(ean)]);
   const jtl=sources[0].status==='fulfilled'?sources[0].value:null;
   const curated=sources[1].status==='fulfilled'?sources[1].value:null;
   const history=sources[2].status==='fulfilled'?sources[2].value:null;
   data={jtl,curated,history};memo.set(id,data);
  }
  if(!active(item))return;
  const jtl=data.jtl,correct=jtl?.jtl_status==='current_snapshot';
  const matches=Array.isArray(jtl?.matches)?jtl.matches:[];
  const match=correct&&matches.length===1?matches[0]:null;
  const isNew=item.resolution==='new_candidate'&&correct&&matches.length===0;
  const model=E.build({ean,match,curated:data.curated,history:data.history,catalog:B.catalog(ean),isNew});
  if(item.resolution!=='new_candidate'){
   B.show(model.fields,model.sources);candidates(item);return;
  }
  if(!isNew){
   status('JTL-Neuanlage noch nicht eindeutig bestätigt. Nur Scan gespeichert, keine automatische Stammdatenfreigabe.');
   candidates(item);return;
  }
  const result=await B.apply(model.fields,model.sources,{save:false});
  if(result.ok)status('✓ Stammdaten vorbelegt. Die zentrale Anreicherungswarteschlange speichert sie in MariaDB.');
  candidates(item);
 }catch(e){
  if(active(item))status('Quellenabgleich eingeschränkt: '+String(e?.message||'unbekannter Fehler')+'. Scan bleibt gespeichert.');
 }finally{busy.delete(id)}
}
document.addEventListener('hw24:scan-selected',e=>{
 const id=e.detail?.id,ean=v(e.detail?.ean);if(!E.goodGtin(ean))return;
 candidates(B.selected());enrich(id,ean);
});
document.addEventListener('hw24:scan-stored',e=>{
 const {id,ean,batchId}=e.detail||{};
 enqueue(id,ean,batchId);
});
document.addEventListener('hw24:scan-items-loaded',e=>{
 const {batchId,items}=e.detail||{};
 for(const it of items||[])if(it.resolution==='new_candidate'&&
    (it.review_state==='scan_only'||!it.completed))
   enqueue(it.id,it.ean,batchId);
 if(pending.length&&!paused)runQueue();
});
document.addEventListener('hw24:scan-catalog',e=>{
 const item=B.selected();if(!item||item.ean!==e.detail?.ean)return;
 candidates(item);
 // JTL/historical data are memoized. Repeat only if Amazon finished during an active save.
 if(busy.has(item.id))setTimeout(()=>{if(active(item))enrich(item.id,item.ean)},900);
 else enrich(item.id,item.ean);
});
})();
