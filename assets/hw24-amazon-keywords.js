/* HW24 v4.22 TEST: source-aware Amazon keyword candidates after a completed search.
 * Does not alter the existing EAN/JTL scan path or auto-import catalog candidates.
 */
(()=>{
'use strict';
const input=document.getElementById('ean');
if(!input||document.getElementById('hw24KeywordLookup'))return;
const el=(tag,text,parent,cls)=>{
 const n=document.createElement(tag);if(text!==undefined)n.textContent=String(text);
 if(cls)n.className=cls;if(parent)parent.append(n);return n;
};
const panel=el('section',undefined,undefined,'group');
panel.id='hw24KeywordLookup';
panel.hidden=true;panel.style.cssText='margin:14px 0;border:1px solid #a9c0d8;border-radius:12px;background:#f6f9ff;padding:15px';
el('h3','🔎 Amazon-Katalog · Artikel- und Stichwortsuche',panel);
const status=el('p','',panel,'note'),results=el('div',undefined,panel);
el('p','Automatischer Abgleich nach einer bestätigten Suche. Mehrdeutige Treffer dienen nur der Identitätsprüfung, niemals als direkte JTL-Neuanlage.',panel,'note');
const destination=document.getElementById('hw24FocusMain')||document.getElementById('hw24ScanWorkspace');
if(destination)destination.insertAdjacentElement('afterend',panel);
else document.body.append(panel);
let serial=0,lastQuery='',lastAt=0;
const asText=s=>String(s??'').trim().replace(/\s+/g,' ');
const gtin=s=>{
 if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(s)||/^0+$/.test(s))return false;
 let sum=0,w=3;for(let i=s.length-2;i>=0;i--){sum+=Number(s[i])*w;w=w===3?1:3}
 return (10-sum%10)%10===Number(s[s.length-1]);
};
function render(info){
 results.replaceChildren();
 if(!info?.items?.length){el('p','Keine passenden Katalogkandidaten. Aus einem fehlenden Amazon-Treffer folgt keine JTL-Neuanlage.',results,'note');return}
 if(info.has_next_page)el('p','Weitere Amazon-Katalogtreffer könnten existieren. Keine automatische Auswahl.',results,'note');
 for(const item of info.items.slice(0,10)){
  const card=el('div',undefined,results);
  card.style.cssText='border:1px solid #d6e2ed;padding:12px;margin:8px 0;border-radius:10px;background:white';
  el('strong',(item.title||'Amazon-Katalogartikel ohne Titel')+' · '+item.asin,card);
  el('p','Marke: '+(item.brand||'unbekannt')+' · Hersteller: '+(item.manufacturer||'unbekannt'),card,'note');
  const ids=(item.gtins||[]).filter(gtin);
  if(ids.length){
   el('p','Amazon-Katalogkennungen: '+ids.join(', '),card,'note');
   for(const id of ids.slice(0,3)){
    const b=el('button','EAN '+id+' im Artikelmanager prüfen',card,'scan-secondary');
    b.type='button';b.style.margin='4px';
    b.onclick=()=>{
      input.value=id;
      input.dispatchEvent(new Event('change',{bubbles:true}));
    };
   }
  }else el('p','Keine bestätigte EAN aus dieser Katalogantwort. ASIN nur zur manuellen Prüfung.',card,'note');
 }
}
async function keywordLookup(value){
 const q=asText(value),now=Date.now();
 if(q.length<3||q.length>120)return;
 if(q===lastQuery&&now-lastAt<1200)return;
 lastQuery=q;lastAt=now;
 const seq=++serial;
 panel.hidden=false;results.replaceChildren();
 status.textContent='Amazon-Katalog: Suche läuft. Zuerst wird der MariaDB-Cache geprüft …';
 try{
   const response=await fetch('api/amazon-keywords.php?'+new URLSearchParams({q}),{
     cache:'no-store',credentials:'same-origin',headers:{Accept:'application/json'}});
   const data=await response.json();
   if(seq!==serial)return;
   if(!response.ok||!data.ok){
     status.textContent='Amazon-Stichwortsuche aktuell nicht verfügbar ('+
       (data.error||response.status)+'). Suche und andere Artikelquellen bleiben verfügbar.';
     return;
   }
   status.textContent='✓ '+data.count+' Kandidaten · '+(data.source==='cache'?'gespeicherter Katalogstand':'Amazon SP-API live')+
     ' · '+(data.fetched_at_utc||'Zeitstempel nicht verfügbar')+'. Bitte Variante und EAN prüfen.';
   render(data);
 }catch(_){
   if(seq!==serial)return;
   status.textContent='Amazon-Suche konnte nicht abgeschlossen werden. Keine automatische Produktzuordnung.';
 }
}
function termFromJtl(e){
 const query=asText(e.detail?.ean);
 if(!query||gtin(query))return;
 const payload=e.detail?.data;
 const matches=payload?.jtl_status==='current_snapshot'?(payload.matches||[]):[];
 if(matches.length===1 && gtin(asText(matches[0]?.ean)))return; // Original V4.22 path handles it.
 const selected=matches.length===1?asText(matches[0]?.artikelname):query;
 if(!selected)return;
 if(/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(query)&&!gtin(query))return;
 keywordLookup(selected);
}
document.addEventListener('hw24:jtl-result',termFromJtl);
function onText(e){
 const q=asText(input.value);
 if(!q.includes(' ')||q.length<3)return;
 // Existing identifier() accepts no spaces. This complementary path runs for
 // completed keyword intent without overwriting the existing scanner handlers.
 e.preventDefault();e.stopImmediatePropagation();
 if(e.type==='keydown'&&e.key!=='Enter')return;
 keywordLookup(q);
}
input.addEventListener('keydown',e=>{if(e.key==='Enter')onText(e)},true);
input.addEventListener('change',onText,true);
document.addEventListener('hw24:scan-start',e=>{
 if(gtin(asText(e.detail?.ean))){panel.hidden=true;++serial;}
});
})();
