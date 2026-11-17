/* HW24 v4.9 test: article/warehouse views, verified title rules, channel checks, camera.
   All edits are local drafts, no writes into JTL or WMS. */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim(),source=()=>window.hw24SourceState?.()||{};
let desk,article,warehouse,warehousePanel,warehouseSummary,warehouseIdentity,slotRows,tax,channels,camBtn,camPanel,video,camStatus,track,controls,active=false;
const el=(tag,label,parent,css)=>{const x=document.createElement(tag);if(label!==undefined)x.textContent=label;if(css)x.className=css;if(parent)parent.append(x);return x};
function update(k,value,msg,pending){
 const s=source(),r=s.record;if(!r||!v(value)||v(r[k])===v(value))return;
 r[k]=value;
 if(pending)window.hw24MarkHistoricalDerivative?.(k,msg);
 else window.hw24MarkRuleDerived?.(k,msg);
}
function formatTitle(name,brand){
 if(!v(name)||!v(brand))return '';
 const m=name.match(/\s*-\s*\(\s*(\d+(?:[,.]\d+)?)\s*(ml|l|g|kg|stk\.?)\s*\)\s*$/i);
 if(!m)return '';
 let main=name.slice(0,m.index).trim();
 if(main.toLocaleLowerCase('de').startsWith(brand.toLocaleLowerCase('de')+' '))main=main.slice(brand.length).trim();
 const shade=main.match(/^(.+?)\s+(\d{1,3}(?:[./]\d{1,3})?)\s+(.+)$/u);
 if(!shade)return '';
 return [brand,shade[1],shade[2],shade[3]].join(' ').replace(/\s+/g,' ').trim()+' - ('+m[1].replace(',','.')+' '+m[2].toLowerCase()+')';
}
function applyRules(){
 const s=source(),r=s.record;
 if(!s.finished||!r||s.jtl?.jtl_status!=='current_snapshot'||s.jtl.matches?.length>1)return;
 const id=v(s.ean);
 if(/^\d{8,14}$/.test(id)){
  if(!v(r.GTIN))update('GTIN',id,'Aus EAN-Scan ergänzt',false);
  if(!v(r.HAN))update('HAN',id,'Aus EAN-Scan ergänzt',false);
 }
 const original=v(r.Artikelname),normalized=formatTitle(original,v(r.Hersteller));
 const unverified=!(window.hw24FieldExportApproved?.('Artikelname')??true)||
  !(window.hw24FieldExportApproved?.('Hersteller')??true);
 if(normalized&&normalized!==original&&!window.hw24WasManuallyEdited?.('Artikelname')){
  r.Artikelname=normalized;
  if(unverified)window.hw24MarkHistoricalDerivative?.('Artikelname','Namensschema aus historischem Vorschlag');
  else window.hw24MarkRuleDerived?.('Artikelname','Aus JTL-Artikelname standardisiert');
 }
 const title=v(r.Artikelname);
 if(title){
  for(const key of ['Kurzbeschreibung','Beschreibung']){
   if(!v(r[key])||(normalized&&original!==normalized&&v(r[key])===original)){
    if(!window.hw24WasManuallyEdited?.(key))update(key,title,'Aus Artikelname übernommen',unverified);
   }
  }
 }
 for(const box of document.querySelectorAll('#fields .field')){
  const key=v(box.querySelector('label')?.textContent),input=box.querySelector('input,textarea');
  if(key&&input&&!input.readOnly&&input.dataset.userEdited!=='1'&&r[key]!==undefined)input.value=v(r[key]);
 }
 window.hw24AmeiseCurrent?.(r);
 window.hw24AmeiseRefresh?.();
 window.hw24WorkdeskRender?.();
 show();
}
function updateModes(){
 const isA=article.getAttribute('aria-selected')==='true';
 article.classList.toggle('selected',isA);warehouse.classList.toggle('selected',!isA);
 for(const c of desk.children){
  if(c===warehousePanel)c.hidden=isA;
  else if(c.classList.contains('hw24-operations-channel')||c.classList.contains('hw24-operations-tax'))c.hidden=!isA;
  else if(c.dataset.originalArticle==='yes')c.hidden=!isA;
 }
}
function mode(isArticle){
 article.setAttribute('aria-selected',String(isArticle));warehouse.setAttribute('aria-selected',String(!isArticle));
 updateModes();show();
}
function show(){
 if(!desk)return;
 updateModes();
 const s=source(),r=s.record||{},match=s.jtl?.jtl_status==='current_snapshot'&&s.jtl.matches?.length===1?s.jtl.matches[0]:null;
 // This identity block is visible in the warehouse view on every scan:
 // article number, GTIN/EAN, HAN, product name, manufacturer and group.
 warehouseIdentity.replaceChildren();
 const identityFields=[
  ['Artikelnummer',match?.artikelnummer||r.Artikelnummer],
  ['EAN / GTIN',match?.ean||r.GTIN],
  ['HAN',match?.han||r.HAN],
  ['Artikelname',match?.artikelname||r.Artikelname],
  ['Hersteller',match?.hersteller||r.Hersteller],
  ['Warengruppe',match?.warengruppe_name||r.Warengruppe]
 ];
 for(const [label,value] of identityFields){
  const cell=el('div',undefined,warehouseIdentity,'hw24-wms-identity-cell');
  el('small',label,cell);
  el('strong',v(value)||'Nicht belegt',cell);
 }
 warehouseSummary.textContent=match?
  'Gesamtbestand '+v(match.bestand??'unbekannt')+
  ' Stück · Verfügbar '+v(match.bestand_verfuegbar??'unbekannt')+
  ' Stück · JTL-Snapshot '+v(s.jtl.snapshot_at)+' UTC':
  'Noch kein eindeutiger JTL-Treffer. Suche nach Artikelnummer, EAN oder HAN.';
 slotRows.replaceChildren();
 const wms=match?.related_sources?.wms_lagerplaetze,slots=Array.isArray(wms?.rows)?wms.rows:[];
 if(!slots.length){
  const row=el('tr',undefined,slotRows);
  const message=wms?.confirmed===true?
    'Keine positiv oder negativ gebuchte Fachmenge im aktuellen JTL-WMS-Snapshot.':
    'WMS-Lagerplatzabfrage noch nicht aktiviert. Gesamtbestand ist keine Fachzuordnung.';
  const cell=el('td',message,row);cell.colSpan=5;
 }else for(const item of slots){
  const row=el('tr',undefined,slotRows);
  const locked=x=>['1','true','ja','y'].includes(v(x).toLowerCase());
  const blocked=locked(item.gesperrt),shippingBlocked=locked(item.auslieferung_gesperrt);
  if(blocked||shippingBlocked)row.className='hw24-wms-blocked';
  el('td','Warenlager '+(v(item.warenlager_id)||'?'),row);
  el('td',v(item.lagerplatz)||'Ohne Fachbezeichnung',row);
  el('td',v(item.menge)||'0',row);
  const status=blocked?'🔒 Fach gesperrt':shippingBlocked?'🔒 Auslieferung gesperrt':'Nicht gesperrt';
  const detail=blocked&&shippingBlocked?' · Auslieferung ebenfalls gesperrt':'';
  el('td',status+detail,row);
  const flags=[];
  if(v(item.lagerplatztyp_id))flags.push('Platztyp-ID '+v(item.lagerplatztyp_id));
  if(v(item.status_id))flags.push('Status-ID '+v(item.status_id));
  if(v(item.inventur_id))flags.push('Inventur-ID '+v(item.inventur_id));
  if(v(item.inventur_datum))flags.push('Inventurdatum '+v(item.inventur_datum));
  if(v(item.vorinventur_status_id))flags.push('Vorinventurstatus-ID '+v(item.vorinventur_status_id));
  if(v(item.inventur_gezaehlt))flags.push('Inventur gezählt (Quellwert) '+v(item.inventur_gezaehlt));
  if(/retour/i.test(v(item.lagerplatz)+' '+v(item.lagerplatz_hinweis)))flags.push('Retourenhinweis in Fachbezeichnung/Kommentar');
  if(v(item.lagerplatz_hinweis))flags.push('Fachkommentar '+v(item.lagerplatz_hinweis));
  el('td',flags.join(' · ')||'Keine weiteren JTL-Hinweise',row);
 }
 if(wms?.truncated){
  const row=el('tr',undefined,slotRows);
  const cell=el('td','Achtung: Mehr als 100 Lagerplätze. Liste unvollständig, nicht zur Inventur verwenden.',row);
  cell.colSpan=5;
 }
 const steuer=v(r.Steuerklasse||match?.steuerklasse_name)||'nicht hinterlegt';
 const taxSource=match?.related_sources?.steuer_saetze_de;
 const rates=Array.isArray(taxSource?.rows)?taxSource.rows:[];
 let taxLabel='Steuerklasse: '+steuer+'. ';
 if(taxSource?.confirmed===true){
  const summary=rates.slice(0,5).map(x=>'Firma '+v(x.firma_id)+' / Zone '+v(x.steuerzone_id)+' / JTL-Quellwert '+v(x.steuersatz_quellwert)).join(' · ');
  taxLabel+=rates.length?
    'Konfigurierte deutsche Steuerzonen: '+summary+(rates.length>5?' · weitere '+(rates.length-5)+' Zonen':'')+'. ':
    'Keine passende deutsche Steuerzonen-Konfiguration im gelesenen Snapshot. ';
 }else taxLabel+='Deutsche Steuersätze aus der JTL-Konfiguration noch nicht synchronisiert. ';
 tax.textContent=taxLabel+'Die konkrete Besteuerung hängt von Firma und Lieferkontext ab; es wird kein ungeprüfter 19-%-Wert in die Ameise-CSV übernommen.';
 channels.replaceChildren();
 const common=[
  ['Amazon',[['GTIN',r.GTIN],['Hersteller',r.Hersteller],['Artikelname',r.Artikelname],['Preis',r['Brutto-VK']||r['Netto-VK']]]],
  ['eBay',[['Artikelname',r.Artikelname],['Marke',r.Hersteller],['Preis',r['Brutto-VK']||r['Netto-VK']]]],
  ['Kaufland',[['EAN',r.GTIN],['Produktname',r.Artikelname],['Kategorie',r['Kategorie Ebene 1']||r.Warengruppe],['Preis',r['Brutto-VK']||r['Netto-VK']]]],
  ['Shopify',[['Titel',r.Artikelname],['Anbieter',r.Hersteller],['Produktkategorie',r['Kategorie Ebene 1']||r.Warengruppe],['Preis',r['Brutto-VK']||r['Netto-VK']]]]
 ];
 for(const [channel,fields] of common){
  const box=el('div',undefined,channels,'hw24-channelcard');
  el('strong',channel,box);
  const missing=fields.filter(pair=>!v(pair[1])).map(pair=>pair[0]);
  el('p',missing.length?'Fehlende Basisfelder: '+missing.join(', '):'Basisfelder vorhanden.',box);
  el('p','Weitere Kategorie- und Kanalanforderungen müssen im jeweiligen Verkaufskanal geprüft werden.',box);
 }
}
function stopCamera(){
 active=false;try{controls?.stop?.()}catch(_){}
 controls=null;try{track?.getTracks().forEach(t=>t.stop())}catch(_){}
 track=null;if(video){video.pause();video.srcObject=null;}
 if(camPanel)camPanel.hidden=true;
}
function finishScan(text){
 const ean=v(text).replace(/\s/g,'');
 if(!/^\d{8,14}$/.test(ean)||!active)return;
 stopCamera();$('ean').value=ean;$('scanStart').click();
}
function loadDecoder(){
 if(window.ZXingBrowser?.BrowserMultiFormatReader)return Promise.resolve();
 return new Promise((resolve,reject)=>{
  const script=document.createElement('script');
  script.src='https://cdn.jsdelivr.net/npm/@zxing/browser@0.2.1/umd/zxing-browser.min.js';
  script.onload=()=>window.ZXingBrowser?.BrowserMultiFormatReader?resolve():reject(new Error('ZXing nicht verfügbar'));
  script.onerror=()=>reject(new Error('Barcode-Bibliothek nicht geladen'));
  document.head.append(script);
 });
}
async function startCamera(){
 camPanel.hidden=false;camStatus.textContent='Bitte Kamerazugriff erlauben.';
 if(!navigator.mediaDevices?.getUserMedia){camStatus.textContent='Kamera erfordert HTTPS und einen kompatiblen Browser.';return}
 try{
  track=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
  video.srcObject=track;await video.play();active=true;
  camStatus.textContent='EAN-Strichcode in den Kamerabereich halten.';
  if(typeof BarcodeDetector==='function'){
   try{
    const detector=new BarcodeDetector({formats:['ean_13','ean_8','upc_a','upc_e','code_128']});
    async function frame(){
     if(!active)return;
     try{const codes=await detector.detect(video);if(codes.length){finishScan(codes[0].rawValue);return}}catch(_){}
     if(active)requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);return;
   }catch(_){}
  }
  await loadDecoder();
  const reader=new window.ZXingBrowser.BrowserMultiFormatReader();
  controls=await reader.decodeFromVideoElement(video,result=>{
   if(result)finishScan(typeof result.getText==='function'?result.getText():result.text);
  });
 }catch(e){
  camStatus.textContent=e?.name==='NotAllowedError'?'Kamerazugriff nicht freigegeben.':
   'Automatischer Scan derzeit nicht verfügbar: '+(e?.message||'Kamerafehler')+'. Bitte EAN eintippen.';
  try{track?.getTracks().forEach(t=>t.stop())}catch(_){}
  track=null;
 }
}
function build(){
 desk=document.querySelector('.hw24-desk');if(!desk)return;
 const bar=desk.querySelector('.hw24-desk-bar');
 if(!bar)return;
 const tabs=el('div',undefined,desk,'hw24-operations-tabs');desk.insertBefore(tabs,bar);
 article=el('button','Artikelübersicht',tabs);warehouse=el('button','Lagerplatzübersicht',tabs);
 article.type='button';warehouse.type='button';
 article.onclick=()=>mode(true);warehouse.onclick=()=>mode(false);
 camBtn=el('button','📷 Kamera',bar,'hw24-camera-button');camBtn.type='button';camBtn.onclick=startCamera;
 const input=$('ean');input.inputMode='numeric';input.autocomplete='off';
 camPanel=el('section',undefined,desk,'hw24-operations-camera');camPanel.hidden=true;
 desk.insertBefore(camPanel,desk.querySelector('.hw24-desk-status'));
 video=el('video',undefined,camPanel);video.autoplay=true;video.muted=true;video.setAttribute('playsinline','');
 camStatus=el('p','Kamerazugriff nur auf ausdrücklichen Wunsch.',camPanel);
 const close=el('button','Kamera schließen',camPanel);close.type='button';close.onclick=stopCamera;
 const before=desk.querySelector('.hw24-desk-actions');
 warehousePanel=el('section',undefined,desk,'hw24-operations-warehouse');desk.insertBefore(warehousePanel,before);
 el('h3','Bestand je Lagerplatz (JTL-WMS)',warehousePanel);
 warehouseIdentity=el('div',undefined,warehousePanel,'hw24-wms-identity');
 warehouseSummary=el('p','Noch kein Artikel gewählt.',warehousePanel);
 const tableWrap=el('div',undefined,warehousePanel,'hw24-wms-table-scroll');
 const table=el('table',undefined,tableWrap),tr=el('tr',undefined,el('thead',undefined,table));
 for(const label of ['Warenlager','Lagerfach','Stück','Sperrstatus','Inventur / Retouren / weitere Hinweise'])el('th',label,tr);
 slotRows=el('tbody',undefined,table);
 el('p','Lagerplatzbuchung ist noch nicht aktiv. Die vorhandene Scanliste bleibt eine lokale Erfassung, keine WMS-Buchung.',warehousePanel);
 tax=el('p','Steuerprüfung ausstehend.',desk,'hw24-operations-tax');desk.insertBefore(tax,before);
 const details=el('details',undefined,desk,'hw24-operations-channel');desk.insertBefore(details,before);
 el('summary','Verkaufskanäle: Amazon, eBay, Kaufland, Shopify',details);
 channels=el('div',undefined,details,'hw24-operations-channelgrid');
 for(const child of desk.children){
  if(child!==warehousePanel&&child!==tax&&child!==details&&child!==camPanel&&child!==tabs&&child!==bar&&
     child.tagName!=='H2'&&!child.classList.contains('hw24-desk-status')&&!child.matches('#status')&&
     !child.classList.contains('hw24-desk-muted'))child.dataset.originalArticle='yes';
 }
 const prior=window.hw24SmartScanFinish;
 if(typeof prior==='function')window.hw24SmartScanFinish=(...args)=>{const ret=prior(...args);applyRules();show();return ret};
 const approval=window.hw24ApproveHistory;
 if(typeof approval==='function')window.hw24ApproveHistory=()=>{approval();show()};
 mode(true);show();
 window.addEventListener('pagehide',stopCamera);
}
window.hw24NormalizeArticleTitle=formatTitle;
build();
})();