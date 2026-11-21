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
const refresh=document.createElement('button');refresh.type='button';refresh.textContent='↻';
refresh.title='HW24-Kostenzähler aktualisieren';refresh.style.cssText='font:inherit;padding:7px 9px;border-radius:7px';
actions.append(check,refresh);wrap.append(actions);
const output=document.createElement('div');output.style.cssText='margin-top:7px;white-space:pre-wrap';
output.setAttribute('role','status');wrap.append(output);
document.body.append(wrap);
const fmt=n=>Number(n).toLocaleString('de-DE',{minimumFractionDigits:4,maximumFractionDigits:4});
const val=x=>String(x??'').trim();
let ready=false,busy=false,currentAmazon=null;
document.addEventListener('hw24:amazon-catalog',e=>{currentAmazon=e.detail||null});
document.addEventListener('hw24:scan-catalog',e=>{currentAmazon=e.detail||null});
async function showCosts(){
 try{
  const response=await fetch('api/ai-review.php',{credentials:'same-origin',cache:'no-store'});
  const x=await response.json();
  if(!response.ok||!x.ok)throw Error(x.error||'nicht erreichbar');
  ready=Boolean(x.enabled);
  if(!ready){
   label.textContent='🤖 HW24 KI · nicht aktiviert';
   detail.textContent='Kein aktiver API-Schlüssel. Keine KI-Abfrage möglich. Es werden hier keine API-Kosten ausgelöst.';
   check.disabled=true;return;
  }
  label.textContent='🤖 HW24 KI · '+fmt(x.usd_month)+' USD im Monat';
  detail.textContent=x.calls_month+' Prüfungen · Limit '+Number(x.usd_budget).toFixed(2)+' USD · '+
   x.daily_calls+'/'+x.daily_limit+' heute · '+
   (x.last_call?.usd!==undefined?'letzte Prüfung '+fmt(x.last_call.usd)+' USD':'noch keine Prüfung')+
   ' · Token-basierte Kostenschätzung, keine OpenAI-Rechnung.';
  check.disabled=busy;
 }catch(e){
  label.textContent='🤖 HW24 KI · Kostenstatus nicht verfügbar';
  detail.textContent='Kein nachgewiesener KI-Verbrauch. Prüfungen vorerst gesperrt.';
  check.disabled=true;ready=false;
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
    candidates:window.HW24Enrich?.amazonChoices(catalog,item.ean)?.slice(0,8).map(x=>({asin:x.asin,title:x.title}))||[]};
 }
 const s=window.hw24SourceState?.()||{},r=s.record||{};
 const ean=val(r.GTIN||r.EAN||document.getElementById('ean')?.value);
 const candidates=window.HW24Enrich?.amazonChoices(currentAmazon?.data,ean)||[];
 return{ean,articleName:val(r.Artikelname),brand:val(r.Hersteller),
   warengruppe:val(r.Warengruppe),category:val(r['Kategorie Ebene 1']),
   unit:val(r['Inhalt/Menge'])+' '+val(r.Maßeinheit),
   jtl_status:val(s.jtl?.jtl_status),candidates:candidates.slice(0,8).map(x=>({asin:x.asin,title:x.title}))};
}
check.onclick=async()=>{
 if(!ready||busy)return;
 const payload=article();
 if(!/^[0-9]{8,14}$/.test(payload.ean)||!payload.articleName){
  output.textContent='Bitte zuerst einen Artikel mit EAN und Produktnamen auswählen.';return;
 }
 if(!confirm('Produktdaten (EAN, Artikelname, Hersteller, Warengruppe und bis zu 8 Amazon-Kandidaten) einmalig an OpenAI senden? Hierdurch können kostenpflichtige Tokens entstehen. Es werden keine Wawi-Daten verändert.'))return;
 busy=true;check.disabled=true;output.textContent='KI bewertet Produktdaten. Keine automatische Feldänderung.';
 try{
  const response=await fetch('api/ai-review.php',{method:'POST',credentials:'same-origin',
   cache:'no-store',headers:{'Content-Type':'application/json','X-HW24-Approval':'confirmed'},
   body:JSON.stringify(payload)});
  const x=await response.json();
  if(!response.ok||!x.ok)throw Error(x.error||'KI-Prüfung fehlgeschlagen');
  const rev=x.review||{};
  output.textContent='KI-Einschätzung: '+val(rev.status)+'\n'+val(rev.reason)+
    '\nWarengruppe-Vorschlag: '+val(rev.suggestedWarengruppe||'keiner')+
    '\n'+(rev.asinChecks||[]).map(v=>v.asin+' · '+v.status+' · '+v.reason).join('\n')+
    '\nDiese Angaben sind Vorschläge, keine automatische JTL-Korrektur. Kosten dieser Prüfung: '+fmt(x.usd_call)+' USD.';
 }catch(e){output.textContent='KI-Prüfung nicht abgeschlossen: '+e.message+
   '. Der Monatszähler wird trotzdem aktualisiert, weil die API bereits Tokens verbraucht haben kann.'}
 finally{busy=false;await showCosts()}
};
refresh.onclick=showCosts;
showCosts();
})();
