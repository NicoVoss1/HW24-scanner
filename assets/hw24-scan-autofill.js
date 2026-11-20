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
const status=msg=>B.status(msg);
function active(item){return B.selected()?.id===item?.id}
function clearChoices(){const node=document.getElementById('amazonChoices');if(node)node.replaceChildren()}
function candidates(item){
 const dest=document.getElementById('amazonChoices');
 if(!dest||!active(item))return;
 const catalog=B.catalog(item.ean);
 clearChoices();
 if(!catalog){
  const p=document.createElement('p');p.className='note';
  p.textContent='Amazon-Katalog wird im Hintergrund geprüft. Weitere ASINs und Packungsgrößen erscheinen hier.';
  dest.append(p);return;
 }
 const choices=E.amazonChoices(catalog,item.ean);
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
 const item=B.selected();if(!item||item.id!==id||item.ean!==ean||busy.has(id))return;
 busy.add(id);
 try{
  let data=memo.get(id);
  if(!data){
   const sources=await Promise.allSettled([E.jtl(ean),E.curated(ean)]);
   const jtl=sources[0].status==='fulfilled'?sources[0].value:null;
   const curated=sources[1].status==='fulfilled'?sources[1].value:null;
   data={jtl,curated};memo.set(id,data);
  }
  if(!active(item))return;
  const jtl=data.jtl,correct=jtl?.jtl_status==='current_snapshot';
  const matches=Array.isArray(jtl?.matches)?jtl.matches:[];
  const match=correct&&matches.length===1?matches[0]:null;
  const isNew=item.resolution==='new_candidate'&&correct&&matches.length===0;
  const model=E.build({ean,match,curated:data.curated,catalog:B.catalog(ean),isNew});
  if(item.resolution!=='new_candidate'){
   B.show(model.fields,model.sources);candidates(item);return;
  }
  if(!isNew){
   status('JTL-Neuanlage noch nicht eindeutig bestätigt. Nur Scan gespeichert, keine automatische Stammdatenfreigabe.');
   candidates(item);return;
  }
  const result=await B.apply(model.fields,model.sources,{save:true});
  if(result.saved)status('✓ Stammdaten automatisch vorbelegt und mit Herkunft in MariaDB gespeichert. Bitte vor Import prüfen.');
  candidates(item);
 }catch(e){
  if(active(item))status('Quellenabgleich eingeschränkt: '+String(e?.message||'unbekannter Fehler')+'. Scan bleibt gespeichert.');
 }finally{busy.delete(id)}
}
document.addEventListener('hw24:scan-selected',e=>{
 const id=e.detail?.id,ean=v(e.detail?.ean);if(!E.goodGtin(ean))return;
 candidates(B.selected());enrich(id,ean);
});
document.addEventListener('hw24:scan-catalog',e=>{
 const item=B.selected();if(!item||item.ean!==e.detail?.ean)return;
 candidates(item);
 // JTL/curated are memoized; new Amazon data may fill remaining fields.
 enrich(item.id,item.ean);
});
})();
