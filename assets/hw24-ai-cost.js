/* HW24 global AI cost badge · no automatic paid requests.
 * Both articlemanager and scanliste use the same read-only cost/status endpoint.
 */
(function(){
'use strict';
const wrap=document.createElement('div');
wrap.id='hw24AiCost';
wrap.style.cssText='position:fixed;right:12px;top:12px;z-index:3000;max-width:310px;background:#fff;border:1px solid #b8cad9;border-radius:12px;box-shadow:0 4px 18px #132e4533;color:#163652;padding:9px 12px;font:12px system-ui;max-height:75vh;overflow:auto';
const label=document.createElement('div');label.style.cssText='font-weight:750';
label.textContent='🤖 HW24 KI · Kosten noch nicht geladen';wrap.append(label);
const detail=document.createElement('div');detail.style.cssText='margin:4px 0;font-size:11px';
detail.textContent='Nur manuelle KI-Prüfung verursacht mögliche OpenAI-Kosten.';wrap.append(detail);
const actions=document.createElement('div');actions.style.cssText='display:flex;gap:7px;flex-wrap:wrap';
const check=document.createElement('button');check.type='button';check.textContent='KI-Prüfung starten';
check.disabled=true;check.style.cssText='font:inherit;padding:7px 9px;border-radius:7px;color:white;background:#165f9d';
const rerun=document.createElement('button');
rerun.type='button';rerun.textContent='🔄 Neu prüfen ($)';rerun.hidden=true;
rerun.title='Bewusst eine neue kostenpflichtige KI-Analyse starten, wenn sich Quellen/Produktdaten geändert haben';
rerun.style.cssText='font:inherit;padding:7px 9px;border-radius:7px;color:#8a3b14;background:#fff0df;border:1px solid #e4b68e';
const refresh=document.createElement('button');refresh.type='button';refresh.textContent='↻';
refresh.title='HW24-Kostenzähler aktualisieren';refresh.style.cssText='font:inherit;padding:7px 9px;border-radius:7px';
const archive=document.createElement('button');
archive.type='button';archive.textContent='📂 Auswertungen';
archive.title='KI-Prüfarchiv mit vollständiger Historie öffnen';
archive.style.cssText='font:inherit;padding:7px 9px;border-radius:7px;background:#e6f1fc;color:#15466d';
archive.onclick=()=>window.HW24AiHistory?.open();
actions.append(check,rerun,archive,refresh);wrap.append(actions);
const output=document.createElement('div');
output.style.cssText='margin-top:7px;white-space:pre-wrap;font-size:12px;line-height:1.4;max-height:130px;overflow:auto';
output.setAttribute('role','status');wrap.append(output);
document.body.append(wrap);
const fmt=n=>Number(n).toLocaleString('de-DE',{minimumFractionDigits:4,maximumFractionDigits:4});
const val=x=>String(x??'').trim();
let ready=false,busy=false,currentAmazon=null,knownReport=null,lookupOk=false,lookupNonce=0;
document.addEventListener('hw24:amazon-catalog',e=>{currentAmazon=e.detail||null});
document.addEventListener('hw24:scan-catalog',e=>{currentAmazon=e.detail||null});
async function showCosts(){
 try{
  const response=await fetch('api/ai-review.php',{credentials:'same-origin',cache:'no-store'});
  const x=await response.json();
  if(!response.ok||!x.ok)throw Error(x.error||'nicht erreichbar');
  ready=Boolean(x.enabled)&&x.archive_ready===true;
  if(x.enabled&&x.archive_ready!==true){
   label.textContent='🤖 HW24 KI · Archivinstallation erforderlich';
   detail.textContent=fmt(x.usd_month)+' USD bisher · Monatsbudget '+
     Number(x.usd_budget).toFixed(2)+' USD. Die neue V13-Archiv-Tabelle muss einmalig installiert werden. Bis dahin keine kostenpflichtige Prüfung.';
   check.disabled=true;rerun.hidden=true;return;
  }
  if(!ready){
   label.textContent='🤖 HW24 KI · nicht aktiviert';
   detail.textContent='Kein aktiver API-Schlüssel. Keine KI-Abfrage möglich. Es werden hier keine API-Kosten ausgelöst.';
   check.disabled=true;rerun.hidden=true;return;
  }
  label.textContent='🤖 HW24 KI · '+fmt(x.usd_month)+' USD im Monat';
  detail.textContent=x.calls_month+' Prüfungen · Limit '+Number(x.usd_budget).toFixed(2)+' USD · '+
   x.daily_calls+'/'+x.daily_limit+' heute · '+
   (x.last_call?.usd!==undefined?'letzte Prüfung '+fmt(x.last_call.usd)+' USD':'noch keine Prüfung')+
   ' · Token-basierte Kostenschätzung, keine OpenAI-Rechnung.';
  check.disabled=busy;
  await refreshArticleStatus();
 }catch(e){
  label.textContent='🤖 HW24 KI · Kostenstatus nicht verfügbar';
  detail.textContent='Kein nachgewiesener KI-Verbrauch. Prüfungen vorerst gesperrt.';
  check.disabled=true;rerun.hidden=true;ready=false;
 }
}
function article(){
 const b=window.HW24ScanBridge,item=b?.selected();
 if(item){
  const field=k=>val(b.control(k)?.value||item.draft?.[k]);
  const catalog=b.catalog(item.ean);
  return{ean:item.ean,articleName:field('Artikelname')||val(item.artikelname),
    brand:field('Hersteller'),warengruppe:field('Warengruppe'),category:field('Kategorie Ebene 1'),
    unit:field('Inhalt/Menge')+' '+field('Maßeinheit'),jtl_status:item.resolution,
    candidates:window.HW24Enrich?.amazonChoices(catalog,item.ean)?.slice(0,8).map(x=>({
     asin:x.asin,title:x.title,brand:x.brand||'',packKind:x.packKind||'unknown',
     packUnits:x.packUnits??null,eanConfirmed:x.exact===true
    }))||[]};
 }
 const s=window.hw24SourceState?.()||{},r=s.record||{};
 const ean=val(r.GTIN||r.EAN||document.getElementById('ean')?.value);
 const candidates=window.HW24Enrich?.amazonChoices(currentAmazon?.data,ean)||[];
 return{ean,articleName:val(r.Artikelname),brand:val(r.Hersteller),
   warengruppe:val(r.Warengruppe),category:val(r['Kategorie Ebene 1']),
   unit:val(r['Inhalt/Menge'])+' '+val(r.Maßeinheit),
   jtl_status:val(s.jtl?.jtl_status),candidates:candidates.slice(0,8).map(x=>({
   asin:x.asin,title:x.title,brand:x.brand||'',packKind:x.packKind||'unknown',
   packUnits:x.packUnits??null,eanConfirmed:x.exact===true
  }))};
}
async function lookupStored(ean){
 const res=await fetch('api/ai-reviews.php?ean='+encodeURIComponent(ean)+'&limit=1',
   {credentials:'same-origin',cache:'no-store'});
 const data=await res.json();
 if(!res.ok||!data.ok||!data.stored)throw Error(data.error||'review_archive_unavailable');
 return Array.isArray(data.reviews)?data.reviews[0]||null:null;
}
async function refreshArticleStatus(){
 const nonce=++lookupNonce;
 const ean=article().ean;
 if(!ready||!/^\d{8,14}$/.test(ean)){
  lookupOk=false;knownReport=null;rerun.hidden=true;
  check.textContent='KI-Prüfung starten';
  check.disabled=!ready||busy;
  return;
 }
 check.disabled=true;rerun.hidden=true;check.textContent='KI-Archiv wird geprüft …';
 try{
  const previous=await lookupStored(ean);
  if(nonce!==lookupNonce)return;
  lookupOk=true;knownReport=previous;
  check.textContent=previous?'📂 KI geprüft · ansehen':'KI-Prüfung starten';
  rerun.hidden=!previous;
  check.disabled=busy;rerun.disabled=busy;
 }catch(e){
  if(nonce!==lookupNonce)return;
  lookupOk=false;knownReport=null;rerun.hidden=true;
  check.textContent='KI-Prüfung nicht verfügbar';check.disabled=true;
  output.textContent='KI-Archiv derzeit nicht erreichbar: '+String(e.message)+
   '. Keine kostenpflichtige Abfrage ausgelöst.';
 }
}
document.addEventListener('hw24:scan-selected',()=>{if(!busy)refreshArticleStatus()});
document.addEventListener('hw24:ai-status-updated',()=>{if(!busy)refreshArticleStatus()});
document.getElementById('ean')?.addEventListener('change',()=>{if(!busy)refreshArticleStatus()});
function explainAiFailure(failure){
 const code=val(failure?.upstream_code),http=Number(failure?.upstream_http);
 const net=Number(failure?.network_errno);
 if(failure?.error==='review_storage_not_ready')
  return 'Das MariaDB-KI-Prüfarchiv V13 fehlt oder hat noch keine Rechte. Keine API-Abfrage wurde ausgelöst.';
 if(failure?.error==='review_storage_write_failed')
  return 'Ergebnis konnte nach der möglicherweise kostenpflichtigen Anfrage nicht archiviert werden. NICHT erneut auslösen, sondern Archiv und Kostenstatus prüfen.';
 if(failure?.error!=='openai_request_failed')return val(failure?.error||'KI-Prüfung fehlgeschlagen');
 if(http===0)return 'OpenAI-Verbindung nicht aufgebaut (cURL-Fehler '+(Number.isFinite(net)?net:'?')+
   '). Plesk-Netzwerk, DNS und TLS prüfen.';
 if(http===401)return 'OpenAI HTTP 401: API-Schlüssel falsch, ungültig oder widerrufen. Schlüssel ausschließlich privat auf Plesk prüfen.';
 if(http===403)return 'OpenAI HTTP 403: API-Projekt oder Modellzugriff nicht erlaubt. Zugriffsrechte in der OpenAI-Plattform prüfen.';
 if(http===429){
   const quota=/quota|credit|billing|balance|usage_limit/i.test(code);
   return 'OpenAI HTTP 429'+(code?' ('+code+')':'')+': '+
     (quota?'API-Guthaben, Billing oder Nutzungslimit des OpenAI-Projekts prüfen.':
      'Rate-Limit oder API-Guthaben prüfen; bei Kontingentproblemen nicht wiederholt klicken.');
 }
 if(http===400)return 'OpenAI HTTP 400'+(code?' ('+code+')':'')+
   ': API-Anfrageformat abgelehnt. Die Anwendung muss den betroffenen Parameter korrigieren.'+
   (failure?.upstream_param?' Parameter: '+val(failure.upstream_param):'');
 if(http===404)return 'OpenAI HTTP 404: Modell oder API-Ressource für dieses Projekt nicht verfügbar.';
 if(http===500||http===502||http===503)return 'OpenAI HTTP '+http+': OpenAI-Dienst aktuell nicht verfügbar.';
 return 'OpenAI HTTP '+http+(code?' ('+code+')':'')+
   ': Anfrage wurde vom API-Anbieter abgelehnt. Technischen HTTP-Status prüfen.';
}
async function runPaid(payload,force){
 if(!ready||busy)return;
 const warning=force?
  'Für diese EAN ist bereits ein KI-Bericht vorhanden. Möchtest Du ausdrücklich ERNEUT kostenpflichtig prüfen? Das ist nur bei neuen oder geänderten Informationen sinnvoll.':
  'Produktdaten (EAN, Artikelname, Hersteller, Warengruppe und Amazon-Kandidaten) einmalig zur kostenpflichtigen KI-Prüfung an OpenAI senden? Vorhandene Prüfungen werden vorher kostenfrei wiederverwendet.';
 if(!window.confirm(warning))return;
 busy=true;check.disabled=true;rerun.disabled=true;
 output.textContent=force?'Erneute KI-Prüfung nach ausdrücklicher Bestätigung …':
   'KI-Prüfung wird ausgeführt. Vorhandene Berichte werden kostenfrei wiederverwendet.';
 try{
  const headers={'Content-Type':'application/json','X-HW24-Approval':'confirmed'};
  if(force)headers['X-HW24-Recheck']='confirmed';
  const response=await fetch('api/ai-review.php',{method:'POST',credentials:'same-origin',
    cache:'no-store',headers,body:JSON.stringify(payload)});
  const x=await response.json();
  if(!response.ok||!x.ok)throw Error(explainAiFailure(x));
  const rev=x.review||{};
  if(x.reused){
    output.textContent='♻ KI-Prüfung #'+x.review_id+
     ' aus MariaDB wiederverwendet. Keine neuen OpenAI-Kosten.'+
     (x.input_changed?' Achtung: Artikel-/Quellendaten haben sich verändert; nur bei Bedarf „Neu prüfen ($)“.':'');
  }else{
    output.textContent=x.stored&&x.review_id?
     '✓ Neue KI-Prüfung #'+x.review_id+' dauerhaft gespeichert · '+val(rev.status)+
       ' · '+fmt(x.usd_call)+' USD. Vollständige Auswertung im Prüfarchiv.':
     '⚠ Neue KI-Prüfung nicht als dauerhaft gespeichert bestätigt. Kostenstatus prüfen.';
  }
  if(x.stored&&x.review_id)window.HW24AiHistory?.afterSaved(x,payload);
 }catch(e){
  output.textContent='KI-Prüfung fehlgeschlagen: '+e.message+
   '\\nBitte nicht mehrfach auslösen. Die Kostenübersicht wird erneut geladen.';
 }finally{
  busy=false;rerun.disabled=false;
  await showCosts();
  window.HW24AiMarkers?.refresh?.();
 }
}
check.onclick=async()=>{
 if(!ready||busy)return;
 const payload=article();
 if(!/^\d{8,14}$/.test(payload.ean)||!payload.articleName){
  output.textContent='Bitte zuerst einen Artikel mit EAN und Produktname auswählen.';return;
 }
 // Always check the authoritative archive again, not just stale browser markers.
 try{
  const previous=await lookupStored(payload.ean);
  if(previous){
   knownReport=previous;lookupOk=true;
   output.textContent='♻ EAN '+payload.ean+' wurde bereits geprüft (#'+previous.id+
    '). Archiv geöffnet statt erneut kostenpflichtig abzufragen.';
   window.HW24AiHistory?.open({ean:payload.ean,id:Number(previous.id)});
   await refreshArticleStatus();return;
  }
 }catch(e){output.textContent='Archivprüfung fehlgeschlagen: '+e.message+
   '. Kein kostenpflichtiger OpenAI-Aufruf ausgelöst.';return}
 await runPaid(payload,false);
};
rerun.onclick=async()=>{
 if(!ready||busy)return;
 const payload=article();
 if(!/^\d{8,14}$/.test(payload.ean)||!payload.articleName)return;
 try{
  const previous=await lookupStored(payload.ean);
  if(!previous){await refreshArticleStatus();return}
 }catch(e){output.textContent='Archiv nicht erreichbar. Neue KI-Prüfung gesperrt.';return}
 await runPaid(payload,true);
};
refresh.onclick=showCosts;
showCosts();
})();
