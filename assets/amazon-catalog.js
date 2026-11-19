/* HW24 Amazon Catalog card. No secrets on client and no automatic JTL writes. */
(()=>{
'use strict';
let sequence=0;
const $=id=>document.getElementById(id);
function make(tag,content='',className='') {
  const el=document.createElement(tag);
  if(className)el.className=className;
  el.textContent=String(content);
  return el;
}
const host=document.createElement('div');
host.id='hw24AmazonPanel';
host.style.cssText='margin:14px 0;border:1px solid #b5d4e8;background:#f3f9ff;border-radius:12px;padding:15px';
const heading=make('h3','🛒 Amazon SP-API · Katalogabgleich');
heading.style.margin='0 0 8px';
const status=make('p','Noch keine EAN geprüft.','note');
status.id='hw24AmazonStatus';
const results=document.createElement('div');
results.id='hw24AmazonResults';
const foot=make('p','Amazon-Daten werden serverseitig in MariaDB archiviert. Kein automatischer Schreibzugriff auf JTL oder Amazon.','note');
host.append(heading,status,results,foot);
const target=$('jtlPanel');
if(target?.parentNode) target.parentNode.insertBefore(host,target.nextSibling);
else document.body.append(host);
const details=$('scanSourceDetails');
if(details) {
  const badge=document.createElement('button');
  badge.type='button';badge.textContent='🛒 Amazon-Katalog anzeigen';
  badge.className='scan-secondary';
  badge.onclick=()=>{details.open=true;host.scrollIntoView({behavior:'smooth',block:'center'});};
  $('historyRetry')?.insertAdjacentElement('afterend',badge);
}
function currentField(name) {
  return [...document.querySelectorAll('#fields .field')].find(box=>box.querySelector('label')?.textContent.trim()===name)?.querySelector('input,textarea');
}
function suggest(item) {
  const options=[['ASIN',item.asin],['Hersteller',item.brand||item.manufacturer],['Artikelname',item.title]];
  const eligible=options.filter(([key,value])=>value && currentField(key) && !currentField(key).value.trim() && !currentField(key).readOnly);
  if(!eligible.length)return null;
  const button=make('button','Fehlende Werte als Entwurf übernehmen','scan-secondary');
  button.type='button';
  button.onclick=()=>{
    let count=0;
    for(const [key,value] of eligible) {
      const field=currentField(key);
      if(field && !field.value.trim() && !field.readOnly){
        field.value=value;field.dispatchEvent(new Event('input',{bubbles:true}));count++;
      }
    }
    button.textContent=count+' Vorschlagswerte übernommen · bitte prüfen';
    button.disabled=true;
  };
  return button;
}
function renderItem(item,index,total) {
  const box=document.createElement('div');
  box.style.cssText='background:white;border:1px solid #ccd9e7;border-radius:9px;margin:9px 0;padding:12px';
  box.append(make('strong',(item.title||'Ohne Titel')+' · '+(item.asin||'ASIN unbekannt')));
  box.append(make('p','Marke: '+(item.brand||'nicht verfügbar')+' · Hersteller: '+(item.manufacturer||'nicht verfügbar'),'note'));
  if(total>1)box.append(make('p','Mehrere Amazon-Treffer: keine automatische Zuordnung. Artikelidentität prüfen.','note'));
  const identifiers=item.identifiers||[];
  if(identifiers.length)box.append(make('p','Amazon-Kennungen im vollständigen JSON-Archiv vorhanden.','note'));
  const attr=item.attributes||{};
  const keys=Object.keys(attr);
  if(keys.length) {
    const d=document.createElement('details');
    const sum=make('summary','Amazon-Merkmale anzeigen ('+keys.length+')');d.append(sum);
    const preview=document.createElement('pre');
    preview.style.cssText='white-space:pre-wrap;overflow-wrap:anywhere;max-height:230px;overflow:auto;font-size:12px';
    preview.textContent=JSON.stringify(attr,null,2).slice(0,10000);
    d.append(preview);box.append(d);
  }
  if(total===1) {
    const proposal=suggest(item);
    if(proposal)box.append(proposal);
  }
  return box;
}
window.hw24AmazonLookup=async function(ean) {
  const seq=++sequence;
  results.replaceChildren();
  if(!/^\d{8,14}$/.test(String(ean||''))) {status.textContent='EAN prüfen';return;}
  if(location.hostname.endsWith('.github.io')) {
    status.textContent='GitHub Pages zeigt nur die statische Vorschau. Amazon wird ausschließlich auf der geschützten HAIRWORLD24-Domain abgerufen.';return;
  }
  status.textContent='Amazon wird abgefragt und in MariaDB dokumentiert …';
  try{
    const res=await fetch('api/amazon.php?ean='+encodeURIComponent(ean),{credentials:'same-origin',cache:'no-store'});
    const data=await res.json();
    if(seq!==sequence)return;
    if(!res.ok||!data.ok) {
      const messages={site_authentication_required:'HTTP-Zugriffsschutz für diese API nicht erkennbar. Bitte Plesk-Authentifizierung prüfen.',
       https_required:'HTTPS erforderlich.',private_config_missing:'Private Amazon-Konfiguration fehlt.',
       amazon_lookup_or_storage_unavailable:'Amazon-Verbindung oder Datenbankarchiv noch nicht bereit. Bitte Serverprotokoll prüfen.',
       invalid_ean_checksum:'EAN-Prüfziffer ungültig.',amazon_catalog_request_failed:'Amazon-Katalogabfrage fehlgeschlagen.'};
      status.textContent='⚠ '+(messages[data.error]||'Amazon derzeit nicht abrufbar')+' ('+res.status+')';
      return;
    }
    status.textContent='✓ '+data.count+' Amazon-Treffer · Quelle: '+(data.source==='cache'?'MariaDB-Cache':'Amazon live')+' · Archiv-ID '+data.snapshot_id;
    for(const [i,item] of (data.items||[]).entries())results.append(renderItem(item,i,data.count));
    if(!data.count)results.append(make('p','Kein Amazon-Katalogtreffer für diese EAN. Nicht automatisch neu anlegen.'));
    if(data.has_next_page)results.append(make('p','Weitere Amazon-Ergebnisse vorhanden; Folgeseitenabruf noch nicht implementiert.','note'));
  }catch(e){
    if(seq!==sequence)return;
    status.textContent='⚠ Amazon-Abfrage konnte nicht abgeschlossen werden. Netzwerk und Serverkonfiguration prüfen.';
  }
};
})();