/* HW24 v4.9 test: article/warehouse views, verified title rules, channel checks, camera.
   All edits are local drafts, no writes into JTL or WMS. */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim(),source=()=>window.hw24SourceState?.()||{};
let desk,article,warehouse,warehousePanel,warehouseSummary,warehouseIdentity,warehouseHighlights,warehouseMobile,slotRows,tax,channels,asinPanel,asinItems,historySection,historyRows,historyNote,historyMore,historyVisible=50,historyArticle='',camBtn,camPanel,video,camStatus,track,controls,active=false;
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
 document.body.classList.toggle('hw24-warehouse-mode',!isA);
 const heading=desk.querySelector('h2');
 if(heading)heading.textContent=isA?'Artikelpflege · scannen, prüfen und exportieren':'Lagerplatzübersicht · nur anzeigen';
 article.classList.toggle('selected',isA);warehouse.classList.toggle('selected',!isA);
 for(const c of desk.children){
  if(c===warehousePanel)c.hidden=isA;
  else if(c.classList.contains('hw24-operations-channel')||c.classList.contains('hw24-operations-tax')||c.classList.contains('hw24-operations-asins'))c.hidden=!isA;
  else if(c.dataset.originalArticle==='yes')c.hidden=!isA;
 }
}
function mode(isArticle){
 article.setAttribute('aria-selected',String(isArticle));warehouse.setAttribute('aria-selected',String(!isArticle));
 updateModes();show();
}

function renderAsinCandidates(s,match){
 if(!asinItems)return;
 asinItems.replaceChildren();
 const current=v(s.record?.ASIN||match?.asin).toUpperCase();
 const suggestions=new Map();
 const add=(value,provenance)=>{
  const asin=v(value).toUpperCase();
  if(!/^[A-Z0-9]{10}$/.test(asin))return;
  if(!suggestions.has(asin))suggestions.set(asin,[]);
  suggestions.get(asin).push(provenance);
 };
 for(const candidate of match?.asin_candidates||[]){
  const sources=(candidate.sources||[]).map(x=>v(x.name)+
    (v(x.seller_sku)?' · SKU '+v(x.seller_sku):'')).join(', ');
  add(candidate.asin,sources||'JTL-Amazon-Zuordnung');
 }
 if(s.history?.ok&&s.history?.gtin_valid===true){
  for(const row of s.history.matches||[]){
   for(const cell of row.cells||[]){
    if(!cell.header_verified)continue;
    const label=v(cell.label).toLowerCase().replace(/[\s_-]+/g,'');
    if(!['asin','asinausjtl','casin1'].includes(label))continue;
    add(cell.value,'Historie: '+v(row.sheet_name||'Excel')+
      ' · Zeile '+v(row.excel_row||'?'));
   }
  }
 }
 el('p',current?'Aktuelle ASIN im Artikelentwurf: '+current:
  'Keine gesicherte ASIN im Artikelentwurf. Kandidaten aus JTL und Historie werden separat geprüft.',asinItems);
 if(suggestions.size){
  if(suggestions.size>1)el('p','Mehrere ASIN-Kandidaten gefunden. Bitte Nuance, Packungsgröße und Amazon-Marketplace vor der Auswahl prüfen.',asinItems,'hw24-desk-warning');
  for(const [asin,sources] of suggestions){
   const card=el('div',undefined,asinItems,'hw24-asin-candidate');
   const line=el('div',undefined,card,'hw24-asin-candidate-main');
   el('strong',asin,line);
   el('small',sources.join(' · '),card);
   if(!current){
    const button=el('button','ASIN nach Prüfung übernehmen',card);
    button.type='button';
    button.onclick=()=>{
     const latest=source();
     if(!latest.finished||!latest.record||v(latest.record.ASIN))return;
     latest.record.ASIN=asin;
     window.hw24MarkEdited?.('ASIN');
     window.hw24AmeiseCurrent?.(latest.record);
     window.hw24WorkdeskRender?.();
     show();
    };
   }else if(current!==asin){
    el('small','Abweichend von bestehender ASIN. Keine automatische Überschreibung.',card);
   }else el('small','Bereits im Artikelentwurf vorhanden.',card);
  }
 }else {
  el('p','Noch kein bestätigter ASIN-Vorschlag aus den angebundenen Datenquellen. EAN Finder oder eine autorisierte Amazon-Katalogsuche können zusätzliche Hinweise liefern.',asinItems);
 }
 const gtin=v(match?.ean||s.record?.GTIN||s.ean);
 if(/^\d{8,14}$/.test(gtin)){
  const link=el('a','EAN Finder zu dieser EAN öffnen',asinItems);
  link.href='https://ean-finder.com/codes/'+encodeURIComponent(gtin);
  link.target='_blank';link.rel='noopener noreferrer';
  const a=el('a','Amazon-Recherche bei EAN Finder öffnen',asinItems);
  a.href='https://ean-finder.com/tools/amazon-research';
  a.target='_blank';a.rel='noopener noreferrer';
 }
 el('small','Externe Webseiten werden nicht automatisch kopiert. Ein Import von Fremddaten erfordert eine freigegebene API bzw. Nutzungsberechtigung und Artikelabgleich.',asinItems);
}

function formatQty(value){
 const raw=v(value);
 if(!raw)return '0';
 const number=Number(raw.replace(',','.'));
 return Number.isFinite(number)?new Intl.NumberFormat('de-DE',{maximumFractionDigits:3}).format(number):raw;
}
let historyState=null,historySequence=0;
function syncBinMovementDates(){
 if(!historyState||historyState.status!=='ready')return;
 for(const node of warehousePanel.querySelectorAll('[data-history-bin]')){
  const stamp=v(historyState.latest?.[node.dataset.historyBin]);
  const prefix=node.dataset.historyPrefix||'';
  node.textContent=prefix+(stamp?stamp+' · JTL-Historienstand':'Keine Bewegung in gespeicherter Historie');
 }
}
function drawHistory(){
 if(!historyState)return;
 const h=historyState;
 historyRows.replaceChildren();
 const date=h.snapshot?' · Historienstand '+h.snapshot+' UTC':'';
 if(h.loading&&!h.rows.length)historyNote.textContent='Bewegungshistorie wird geladen …';
 else if(h.status==='ready'){
  historyNote.textContent=h.total===0?'Keine verknüpften Bewegungen gespeichert'+date:
   h.rows.length+' von '+h.total+' gespeicherten Bewegungen · ohne Datumsfilter'+date;
 }else if(h.status==='error')historyNote.textContent='Historie nicht abrufbar. Bitte erneut versuchen. Aktuelle Lagerfachdaten bleiben davon unabhängig.';
 else historyNote.textContent='Bewegungshistorie noch nicht synchronisiert. Die Lagerfachdaten stammen separat aus JTL.';
 for(const entry of h.rows){
  const card=el('div',undefined,historyRows,'hw24-history-entry');
  const time=v(entry.zeitstempel)||'Zeitstempel nicht belegt';
  const kind=v(entry.buchungsart)||(v(entry.buchungsart_id)?'Buchungsart '+v(entry.buchungsart_id):'Buchungsart nicht belegt');
  el('strong',time+' · '+kind,card);
  el('p','Menge '+(entry.menge===null?'Nicht belegt':formatQty(entry.menge))+
   ' · Von '+(v(entry.lagerplatz_von)||(v(entry.start_id)&&v(entry.start_id)!=='0'?'Fach-ID '+v(entry.start_id):'nicht dokumentiert'))+
   ' · Nach '+(v(entry.lagerplatz_nach)||(v(entry.ziel_id)&&v(entry.ziel_id)!=='0'?'Fach-ID '+v(entry.ziel_id):'nicht dokumentiert')),card);
  el('small','Bewegungs-ID '+v(entry.movement_id),card);
 }
 historyMore.disabled=h.loading;
 historyMore.textContent=h.loading?'Lädt …':h.status==='error'?'Erneut versuchen':'Weitere 50 Bewegungen anzeigen';
 historyMore.hidden=!(h.loading||h.status==='error'||(h.status==='ready'&&h.rows.length<h.total));
 syncBinMovementDates();
}
async function loadHistory(){
 const h=historyState;
 if(!h||h.loading)return;
 h.loading=true;drawHistory();
 try{
  const q=new URLSearchParams({article_id:h.article,offset:String(h.rows.length)});
  if(h.run)q.set('run_id',h.run);
  const reply=await fetch('api/jtl-wms-history.php?'+q,{cache:'no-store',credentials:'same-origin'});
  if(!reply.ok)throw Error('Historie nicht verfügbar');
  const result=await reply.json();
  if(historyState!==h)return;
  if(!result.ok)throw Error('Historie nicht verfügbar');
  if(result.status!=='ready'){
   if(h.run)throw Error('Historienstand nicht mehr verfügbar');
   h.status='not_synchronized';
  }else{
   if(!Array.isArray(result.rows)||!Number.isInteger(result.total)||result.total<h.rows.length+result.rows.length)throw Error('Ungültige Antwort');
   h.run=result.run_id;h.snapshot=result.snapshot_at;
   h.total=result.total;h.rows.push(...result.rows);
   h.latest=result.offset===0?(result.latest_by_bin||{}):h.latest;
   h.status='ready';
  }
 }catch(err){if(historyState===h)h.status='error';}
 finally{if(historyState===h){h.loading=false;drawHistory();}}
}
function renderWarehouseHistory(match){
 if(!historyRows||!historyNote||!historyMore)return;
 // Only retrieve stored movement history in the read-only warehouse tab.
 if(article.getAttribute('aria-selected')==='true')return;
 const id=v(match?.jtl_article_id);
 if(!/^[1-9]\d*$/.test(id)){
  historyState=null;historyRows.replaceChildren();
  historyNote.textContent='Bitte zuerst einen eindeutigen JTL-Artikel scannen.';
  historyMore.hidden=true;return;
 }
 const context=id+'|'+v(source().jtl?.snapshot_at);
 if(!historyState||historyState.context!==context){
  historyState={context,article:id,loading:false,status:'initial',rows:[],total:0,run:'',snapshot:'',latest:{}};
  drawHistory();void loadHistory();
 }else drawHistory();
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
  ['Warengruppe',match?(match.warengruppe_name||(match.warengruppe_id?'JTL-ID '+match.warengruppe_id+' · Klarname noch nicht synchronisiert':'Nicht belegt')):r.Warengruppe]
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
 const stockAvailable=match?.bestand_verfuegbar;
 const stockText=stockAvailable===null||stockAvailable===undefined||v(stockAvailable)===''?'Nicht synchronisiert':formatQty(stockAvailable);
 warehouseHighlights.replaceChildren();warehouseMobile.replaceChildren();
 if(slots.length){
  const kinds=slots.filter(x=>['1','true','ja'].includes(v(x.gesperrt).toLowerCase())||['1','true','ja'].includes(v(x.auslieferung_gesperrt).toLowerCase()));
  const positive=slots.filter(x=>Number(v(x.menge).replace(',','.'))>0);
  const summary=[
    ['Verfügbarer Bestand',stockText],
    ['Erfasste Fächer',String(slots.length)],
    ['Fächer mit Bestand',String(positive.length)],
    ['Gesperrte Fächer',String(kinds.length)]
  ];
  for(const [label,value] of summary){
   const stat=el('div',undefined,warehouseHighlights,'hw24-wms-stat');
   el('small',label,stat);el('strong',value,stat);
  }
  if(wms?.truncated)el('p','Hinweis: Der WMS-Spiegel enthält nicht alle Fachpositionen.',warehouseHighlights,'hw24-desk-warning');
 }else {
  const stat=el('div',undefined,warehouseHighlights,'hw24-wms-stat');
  el('small','Verfügbarer Bestand',stat);el('strong',stockText,stat);
  if(wms?.confirmed===true)
   el('p','Für diesen Artikel sind keine WMS-Fachzuordnungen im letzten Spiegel vorhanden.',warehouseHighlights,'hw24-desk-muted');
 }
 if(!slots.length){
  const row=el('tr',undefined,slotRows);
  const message=wms?.confirmed===true?
    'Keine positiv oder negativ gebuchte Fachmenge im aktuellen JTL-WMS-Snapshot.':
    'WMS-Lagerplatzabfrage noch nicht aktiviert. Gesamtbestand ist keine Fachzuordnung.';
  const cell=el('td',message,row);cell.colSpan=6;
 }else for(const item of slots){
  const row=el('tr',undefined,slotRows);
  const locked=x=>['1','true','ja','y'].includes(v(x).toLowerCase());
  const blocked=locked(item.gesperrt),shippingBlocked=locked(item.auslieferung_gesperrt);
  if(blocked||shippingBlocked)row.className='hw24-wms-blocked';
  el('td','Warenlager '+(v(item.warenlager_id)||'?'),row);
  el('td',v(item.lagerplatz)||'Ohne Fachbezeichnung',row);
  el('td',formatQty(item.menge),row);
  const status=blocked?'🔒 Fach gesperrt':shippingBlocked?'🔒 Auslieferung gesperrt':'Nicht gesperrt';
  const detail=blocked&&shippingBlocked?' · Auslieferung ebenfalls gesperrt':'';
  el('td',status+detail,row);
  const lastMove=v(item.letzte_bewegung_utc||item.letzte_bewegung);
  const dateCell=el('td',lastMove?lastMove+' · '+(v(item.letzte_bewegung_quelle)||'JTL-Bewegungshistorie'):'Noch nicht aus der Bewegungshistorie ermittelt',row);
  if(v(item.lagerplatz_id))dateCell.dataset.historyBin=v(item.lagerplatz_id);
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
  const mobile=el('div',undefined,warehouseMobile,'hw24-wms-mobilecard'+(blocked||shippingBlocked?' locked':''));
  const top=el('div',undefined,mobile,'hw24-wms-mobiletop');
  const location=el('div',undefined,top);
  el('small','Warenlager '+(v(item.warenlager_id)||'?'),location);
  el('strong',v(item.lagerplatz)||'Lagerfach unbekannt',location);
  const amount=el('div',undefined,top,'hw24-wms-mobileamount');
  el('strong',formatQty(item.menge),amount);
  el('small','Stück',amount);
  el('p',status+detail,mobile,'hw24-wms-mobilestatus');
  const mobileDate=el('p','Letzte Bewegung: '+(lastMove||'Noch nicht ermittelt'),mobile,'hw24-wms-mobilehistory');
  if(v(item.lagerplatz_id)){mobileDate.dataset.historyBin=v(item.lagerplatz_id);mobileDate.dataset.historyPrefix='Letzte Bewegung: ';}
  if(flags.length)el('p',flags.join(' · '),mobile,'hw24-wms-mobileflags');
 }
 if(wms?.truncated){
  const row=el('tr',undefined,slotRows);
  const cell=el('td','Achtung: Mehr als 100 Lagerplätze. Liste unvollständig, nicht zur Inventur verwenden.',row);
  cell.colSpan=6;
 }
 renderWarehouseHistory(match);
 const steuer=v(r.Steuerklasse||match?.steuerklasse_name)||'nicht hinterlegt';
 const taxSource=match?.related_sources?.steuer_saetze_de;
 const rates=Array.isArray(taxSource?.rows)?taxSource.rows:[];
 let taxLabel='Steuerklasse: '+steuer+'. ';
 if(taxSource?.confirmed===true){
  const valid=rates.filter(x=>v(x.zielland)==='DE'&&
    Number.isFinite(Number(v(x.steuersatz_quellwert).replace(',','.'))));
  const summary=valid.slice(0,6).map(x=>{
    const value=Number(v(x.steuersatz_quellwert).replace(',','.'));
    const percent=value>=0&&value<=100?formatQty(x.steuersatz_quellwert)+' %':'Quellwert '+v(x.steuersatz_quellwert)+' (Skalierung prüfen)';
    return percent+' · '+(v(x.firmenname)?v(x.firmenname)+' (Firma '+v(x.firma_id)+')':'Firma '+v(x.firma_id)+' (Klarname fehlt)')+' · '+(v(x.steuerzone)||'Steuerzone')+' (Zone '+v(x.steuerzone_id)+')';
  }).join(' | ');
  taxLabel+=valid.length?
    'JTL-Steuersätze für Deutschland: '+summary+(valid.length>6?' | weitere '+(valid.length-6)+' Kontexte':'')+'. ':
    'Kein eindeutig auslesbarer Steuersatz für Deutschland im aktuellen Spiegel. ';
 }else taxLabel+='JTL-Steuersätze für Deutschland noch nicht synchronisiert. ';
 tax.textContent=taxLabel+'Der tatsächlich anzuwendende Satz hängt zusätzlich von Firma, Lieferung und Steuerkontext ab. Kein automatisches Überschreiben der JTL-Steuerregeln.';
 channels.replaceChildren();
 renderAsinCandidates(s,match);
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
 article=el('button','Artikelpflege',tabs);warehouse=el('button','Lagerplatzübersicht',tabs);
 article.type='button';warehouse.type='button';
 article.onclick=()=>mode(true);warehouse.onclick=()=>mode(false);
 camBtn=el('button','📷 Kamera',bar,'hw24-camera-button');camBtn.type='button';camBtn.onclick=startCamera;
 const input=$('ean');input.inputMode='text';input.autocomplete='off'; // Allow AR article numbers and alphanumeric HAN.
 camPanel=el('section',undefined,desk,'hw24-operations-camera');camPanel.hidden=true;
 desk.insertBefore(camPanel,desk.querySelector('.hw24-desk-status'));
 video=el('video',undefined,camPanel);video.autoplay=true;video.muted=true;video.setAttribute('playsinline','');
 camStatus=el('p','Kamerazugriff nur auf ausdrücklichen Wunsch.',camPanel);
 const close=el('button','Kamera schließen',camPanel);close.type='button';close.onclick=stopCamera;
 const before=desk.querySelector('.hw24-desk-actions');
 warehousePanel=el('section',undefined,desk,'hw24-operations-warehouse');desk.insertBefore(warehousePanel,before);
 el('h3','Wo liegt der Artikel?',warehousePanel);
 el('p','Nur lesende JTL-WMS-Ansicht. Hier kannst Du keine Artikel, Bestände oder Lagerplätze verändern.',warehousePanel,'hw24-warehouse-readonly');
 warehouseIdentity=el('div',undefined,warehousePanel,'hw24-wms-identity');
 warehouseSummary=el('p','Noch kein Artikel gewählt.',warehousePanel);
 warehouseHighlights=el('div',undefined,warehousePanel,'hw24-wms-highlights');
 const tableWrap=el('div',undefined,warehousePanel,'hw24-wms-table-scroll');
 const table=el('table',undefined,tableWrap),tr=el('tr',undefined,el('thead',undefined,table));
 for(const label of ['Warenlager','Lagerfach','Stück','Sperrstatus','Letzte Lagerbewegung','Inventur / Retouren / Hinweise'])el('th',label,tr);
 slotRows=el('tbody',undefined,table);
 warehouseMobile=el('div',undefined,warehousePanel,'hw24-wms-mobilelist');
 historySection=el('section',undefined,warehousePanel,'hw24-wms-history');
 el('h3','Vollständige Bewegungshistorie des Artikels',historySection);
 el('p','Ohne Datumsfilter. Alle verfügbaren Bewegungen werden nach der Quellenanbindung vollständig und bei Bedarf seitenweise angezeigt.',historySection,'hw24-desk-muted');
 historyNote=el('p','Bewegungsquelle noch nicht angebunden.',historySection,'hw24-history-note');
 historyRows=el('div',undefined,historySection,'hw24-history-rows');
 historyMore=el('button','Weitere 50 Bewegungen anzeigen',historySection);historyMore.type='button';historyMore.hidden=true;
 historyMore.onclick=()=>{void loadHistory();};
 el('p','Letzte Bewegung und ursprüngliche Einlagerung sind unterschiedliche Werte. Das Inventurdatum ist kein Buchungsdatum. Die Übersicht bleibt ohne Buchungsfunktion.',warehousePanel,'hw24-desk-muted');
 tax=el('p','Steuerprüfung ausstehend.',desk,'hw24-operations-tax');desk.insertBefore(tax,before);
 asinPanel=el('details',undefined,desk,'hw24-operations-asins');desk.insertBefore(asinPanel,before);
 el('summary','ASIN und ergänzende EAN-Produktrecherche',asinPanel);
 asinItems=el('div',undefined,asinPanel,'hw24-asin-results');
 const details=el('details',undefined,desk,'hw24-operations-channel');desk.insertBefore(details,before);
 el('summary','Verkaufskanäle: Amazon, eBay, Kaufland, Shopify',details);
 channels=el('div',undefined,details,'hw24-operations-channelgrid');
 for(const child of desk.children){
  if(child!==warehousePanel&&child!==tax&&child!==details&&child!==asinPanel&&child!==camPanel&&child!==tabs&&child!==bar&&
     child.tagName!=='H2'&&!child.classList.contains('hw24-desk-status')&&!child.matches('#status')&&
     !child.classList.contains('hw24-desk-muted'))child.dataset.originalArticle='yes';
 }
 const prior=window.hw24SmartScanFinish;
 if(typeof prior==='function')window.hw24SmartScanFinish=(...args)=>{const ret=prior(...args);if(article.getAttribute('aria-selected')==='true')applyRules();show();return ret};
 const approval=window.hw24ApproveHistory;
 if(typeof approval==='function')window.hw24ApproveHistory=()=>{approval();show()};
 mode(true);show();
 window.addEventListener('pagehide',stopCamera);
}
window.hw24NormalizeArticleTitle=formatTitle;
build();
})();