/* HW24 Market Prices · v1.0. Prices are fetched only when the user clicks.
 * Uses official Product Pricing API and shows seller IDs, not alleged suppliers.
 * Expects custom event "hw24:amazon-catalog" from index.html.
 */
(()=>{'use strict';
const $=id=>document.getElementById(id);
const el=(tag,text,css)=>{const n=document.createElement(tag);if(css)n.className=css;if(text!==undefined)n.textContent=String(text);return n;};
const money=v=>v&&Number.isFinite(v.amount)&&/^[A-Z]{3}$/.test(v.currency||'')?
 new Intl.NumberFormat('de-DE',{style:'currency',currency:v.currency}).format(v.amount):'Keine Angabe';
let panel=null, request=0, lastAsin='';
function mount(){
 const catalog=$('focusAmazonCard');
 if(!catalog)return null;
 if(!panel){
  panel=el('div');panel.id='hw24AmazonMarket';
  panel.style.cssText='margin:12px 0;padding:12px;border:1px solid #dce5ee;background:#f7faff;border-radius:10px;color:#14233c';
  const slot=$('hw24AmazonMarketSlot');
  if(slot)slot.append(panel);
  else (catalog.closest('.f-section')||catalog).insertAdjacentElement('afterend',panel);
 }
 return panel;
}
function reset(message='Preise sind noch nicht abgefragt.'){
 request++;lastAsin='';
 const target=mount();if(!target)return;
 target.replaceChildren(el('p',message,'note'));
}
function pair(label,value){
 const n=el('div');n.style.cssText='padding:10px 12px;background:#fff;border:1px solid #dde5f0;border-radius:9px';
 const l=el('small',label);l.style.cssText='display:block;color:#566983';
 n.append(l,el('strong',value));return n;
}
function grid(...items){const d=el('div');d.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:12px 0';items.forEach(x=>d.append(x));return d;}
async function showOffers(data,place){
 place.replaceChildren();
 const offers=Array.isArray(data.offers)?data.offers:[];
 const ids=[...new Set(offers.map(o=>o.seller_id).filter(s=>/^[A-Z0-9]{5,40}$/.test(s||'')))].slice(0,20);
 let names={};
 if(ids.length){
  try{
   const res=await fetch('api/amazon-sellers.php?ids='+encodeURIComponent(ids.join(',')),{credentials:'same-origin',cache:'no-store'});
   const result=await res.json();if(res.ok&&result.ok&&result.names&&typeof result.names==='object')names=result.names;
  }catch(e){/* Seller names are optional; do not invent them. */}
 }
 const moneyTotal=o=>o.total?.amount??Number.POSITIVE_INFINITY;
 const winnerOffers=offers.filter(o=>o.is_buybox_winner===true);
 const featuredTotal=(data.buybox_prices||[]).find(o=>o.landed_price?.currency==='EUR')?.landed_price||
  winnerOffers.find(o=>o.total?.currency==='EUR')?.total;
 const featuredItem=(data.buybox_prices||[]).find(o=>o.listing_price?.currency==='EUR')?.listing_price||
  winnerOffers.find(o=>o.item_price?.currency==='EUR')?.item_price;
 const totals=offers.filter(o=>o.total?.currency==='EUR').map(o=>o.total.amount).filter(Number.isFinite);
 const lowest=totals.length?Math.min(...totals):null;
 place.append(grid(
  pair(featuredTotal?'Amazon Buy Box inkl. Versand':'Amazon Buy Box · Artikelpreis',money(featuredTotal||featuredItem)),
  pair('Aktuell markierte Gewinner',String(winnerOffers.length)+' von '+offers.length+' abgerufen'),
  pair('Günstigster Gesamtpreis',lowest!==null?money({amount:lowest,currency:'EUR'}):'Nicht verfügbar'),
  pair('Angebote bei Amazon',String(data.total_offer_count??'–'))
 ));
 const featured=el('p','🏆 Amazon.de-Angebote · mehrere Buy-Box-Gewinner sind möglich. Lieferbarkeit nach Deutschland, konkreter Zustelltermin und Eignung als Bezugsquelle sind aus dieser Abfrage nicht garantiert.','note');
 place.append(featured);
 if(!offers.length){place.append(el('p','Amazon hat keine einzelnen Verkäuferangebote übermittelt.','note'));return;}
 const details=el('details');details.append(el('summary','Verkäuferangebote anzeigen'));place.append(details);
 const control=el('div');control.style.cssText='display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:15px 0 9px';
 const label=el('label','Sortierung · ');
 const select=document.createElement('select');select.style.cssText='min-height:38px;padding:7px 9px;border:1px solid #bfd0e3;border-radius:9px;background:white;color:#14233c';
 [['price','Gesamtpreis aufsteigend'],['featured','Buy Box zuerst'],['rating','Verkäuferbewertung'],['dispatch','Schnellster Versand']].forEach(([v,t])=>{const opt=document.createElement('option');opt.value=v;opt.textContent=t;select.append(opt);});
 label.append(select);control.append(label);
 const note=el('small','Bewertung = positives Verkäuferfeedback der vergangenen 365 Tage; Versandzeit = Zeit bis Versand, nicht Zustelltermin.','note');control.append(note);details.append(control);
 const list=el('div');list.style.cssText='display:grid;gap:10px';
 details.append(list);
 const feedback=o=>o.seller_feedback?.positive_percent;
 const dispatch=o=>o.shipping_time?.max_hours??Number.POSITIVE_INFINITY;
 const dispatchText=o=>{
  const t=o.shipping_time;if(!t)return 'Versandvorbereitung: nicht übermittelt';
  if(t.availability&&t.availability!=='available')return 'Verfügbarkeit: '+t.availability;
  const a=Number.isFinite(t.min_hours)?t.min_hours:null,b=Number.isFinite(t.max_hours)?t.max_hours:null;
  if(a===null&&b===null)return 'Versandvorbereitung: nicht übermittelt';
  const fmt=h=>h>=48?(h/24).toLocaleString('de-DE',{maximumFractionDigits:1})+' Tage':h+' Std.';
  return 'Versandfertig in '+(a!==null?fmt(a):'?')+(b!==null?' bis '+fmt(b):'')+' · keine Lieferzusage';
 };
 function offerCard(o){
  const card=el('article');
  card.style.cssText='background:#fff;border:1px solid '+(o.is_buybox_winner?'#67ae84':'#dae4ee')+';border-radius:12px;padding:13px 14px;box-shadow:0 1px 3px rgba(20,35,60,.04)';
  const top=el('div');top.style.cssText='display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap';
  const who=el('div');
  who.append(el('strong',names[o.seller_id]||'Amazon-Verkäufer '+(o.seller_id||'unbekannt')));
  const badges=el('div');badges.style.cssText='display:flex;flex-wrap:wrap;gap:5px;margin:6px 0';
  const badge=label=>{const n=el('span',label);n.style.cssText='display:inline-block;padding:3px 7px;border-radius:999px;border:1px solid #ccd7e5;background:#f2f6fc;color:#2d4a6b;font-size:11px';badges.append(n);};
  if(o.is_own_offer===true)badge('Eigenes Angebot');
  if(o.is_fba===true)badge('FBA · Versand durch Amazon');
  else if(o.is_fba===false)badge('FBM · Händler-Versand');
  if(o.prime===true)badge('✓ Prime laut Amazon');
  else if(o.prime===false)badge('Kein Prime gemeldet');
  if(o.is_buybox_winner===true)badge('🏆 Aktuell Buy-Box-Gewinner');
  else if(o.is_featured_merchant===true)badge('Buy-Box-berechtigt');
  else if(o.is_featured_merchant===false)badge('Nicht als berechtigt gemeldet');
  else badge('Buy-Box-Berechtigung unbekannt');
  who.append(badges);top.append(who);
  const amount=el('div');amount.style.cssText='text-align:right';
  const price=el('strong',money(o.total));price.style.cssText='font-size:18px;color:#193f60;display:block';
  amount.append(price,el('small','Gesamtpreis inkl. gemeldeter Versandkosten'));
  top.append(amount);card.append(top);
  const facts=el('div');facts.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:7px;margin:10px 0';
  const fact=(a,b)=>{const d=el('div');d.style.cssText='padding:7px 8px;background:#f5f8fc;border-radius:7px';d.append(el('small',a),el('div',b));facts.append(d);};
  fact('Artikel + Versand',money(o.item_price)+' + '+money(o.shipping));
  const f=o.seller_feedback;
  fact('Verkäuferfeedback',f&&Number.isFinite(f.positive_percent)?f.positive_percent.toLocaleString('de-DE')+' % positiv'+(f.feedback_count!==null?' ('+f.feedback_count+' Bewertungen)':''):'Von Amazon nicht übermittelt');
  fact('Versandabwicklung',dispatchText(o));
  fact('Versandort',o.ships_from?.country?o.ships_from.country+(o.ships_from.state?' · '+o.ships_from.state:''):'Nicht übermittelt');
  fact('Lieferung nach Deutschland','Nicht einzeln geprüft','Amazon.de-Listung ist keine verbindliche Versandzusage für eine deutsche Lieferadresse');
  card.append(facts);
  if(/^[A-Z0-9]{5,40}$/.test(o.seller_id||'')){
   const link=document.createElement('a');
   link.href='https://www.amazon.de/sp?seller='+encodeURIComponent(o.seller_id);
   link.target='_blank';link.rel='noopener noreferrer';link.textContent='Händlerprofil bei Amazon öffnen';
   link.style.cssText='display:inline-block;margin:4px 0;font-size:13px;color:#19598b';card.append(link);
   if(!names[o.seller_id]){
    const verify=el('details');verify.style.cssText='margin-top:6px';
    verify.append(el('summary','Verkäufername nach Profilprüfung bestätigen'));
    const input=document.createElement('input');input.placeholder='Name laut Amazon-Verkäuferprofil';input.maxLength=240;
    input.style.cssText='display:block;box-sizing:border-box;max-width:370px;width:100%;padding:8px;margin:6px 0';
    const save=el('button','Verifizierten Namen speichern','scan-secondary');save.type='button';
    const status=el('p','','note');
    save.onclick=async()=>{
     const name=input.value.trim();if(!name)return;
     if(!confirm('Ist der Name '+name+' im Amazon-Verkäuferprofil eindeutig bestätigt?'))return;
     save.disabled=true;
     try{
      const res=await fetch('api/amazon-sellers.php',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','X-HW24-Approval':'confirmed'},
       body:JSON.stringify({seller_id:o.seller_id,display_name:name,source_url:link.href,confirmed:true})});
      const reply=await res.json();
      if(res.ok&&reply.ok){names[o.seller_id]=name;who.querySelector('strong').textContent=name;verify.remove();}
      else status.textContent='Speichern fehlgeschlagen: '+(reply.error||res.status);
     }catch(e){status.textContent='Verkäufer-Namensdienst nicht erreichbar.';}
     finally{save.disabled=false;}
    };
    verify.append(input,save,status);card.append(verify);
   }
  }
  return card;
 }
 function paint(){
  const order=select.value;
  const sorted=[...offers].sort((a,b)=>{
   if(order==='featured')return Number(b.is_buybox_winner)-Number(a.is_buybox_winner)||moneyTotal(a)-moneyTotal(b);
   if(order==='rating')return (feedback(b)??-1)-(feedback(a)??-1)||moneyTotal(a)-moneyTotal(b);
   if(order==='dispatch')return dispatch(a)-dispatch(b)||moneyTotal(a)-moneyTotal(b);
   return moneyTotal(a)-moneyTotal(b);
  });
  list.replaceChildren(...sorted.map(offerCard));
 }
 select.addEventListener('change',paint);paint();
 details.append(el('p','Amazon-Verkäufer sind nicht automatisch Bezugsquellen für Deinen Einkauf. Händlername, Bewertungen und Versandzeiten erscheinen nur, wenn sie übermittelt oder nach Profilprüfung bestätigt wurden.','note'));
}
document.addEventListener('hw24:amazon-reset',()=>reset());
document.addEventListener('hw24:amazon-catalog',e=>{
 const data=e.detail?.data;
 const target=mount();if(!target)return;
 const seq=++request;lastAsin='';
 target.replaceChildren();
 if(!data||data.count!==1||!Array.isArray(data.items)||!data.items[0]?.asin){
  target.append(el('p',data?.count>1?'Bei mehreren ASINs zuerst die Produktidentität bestätigen.':'Kein eindeutiger Amazon-Katalogtreffer für die Preisabfrage.','note'));return;
 }
 const asin=String(data.items[0].asin).trim().toUpperCase();
 if(!/^[A-Z0-9]{10}$/.test(asin)){target.append(el('p','Keine gültige ASIN für die Angebotsabfrage.','note'));return;}
 lastAsin=asin;
 const btn=el('button','💶 Aktuelle Amazon-Angebote abrufen','scan-secondary');
 btn.type='button';
 const state=el('p','Noch keine Preisabfrage ausgeführt.','note');state.setAttribute('role','status');
 const content=el('div');
 const report=status=>document.dispatchEvent(new CustomEvent('hw24:pricing-state',{detail:{asin,status}}));
 btn.onclick=async()=>{
  if(seq!==request||lastAsin!==asin)return;
  btn.disabled=true;state.textContent='Amazon-Angebote werden abgefragt …';content.replaceChildren();
  report('loading');
  let received=false;
  try{
   const res=await fetch('api/amazon-pricing.php?asin='+encodeURIComponent(asin),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
   const result=await res.json();if(seq!==request||lastAsin!==asin)return;
   if(!res.ok||!result.ok){
    const reasons={amazon_rate_limited:'Amazon-API-Limit erreicht. Bitte später erneut versuchen.',
     pricing_role_or_access_missing:'Amazon Pricing API nicht freigegeben. Pricing-Rolle im Entwicklerprofil prüfen.',
     private_config_missing:'Private Amazon-Konfiguration fehlt.',
     site_authentication_required:'Plesk-Anmeldung wird nicht erkannt.',
     amazon_pricing_unavailable:'Amazon-Preisabfrage technisch fehlgeschlagen.'};
    state.textContent='⚠ '+(reasons[result.error]||'Preisabfrage derzeit nicht verfügbar')+' · HTTP '+res.status;
    report('error');
   }else{
    received=true;state.textContent='✓ Preise von Amazon abgerufen.';
    document.dispatchEvent(new CustomEvent('hw24:pricing-result',{detail:{asin,data:result}}));
    await showOffers(result,content);
   }
  }catch(err){
   if(seq!==request)return;
   if(received)state.textContent='Amazon hat Preise geliefert. Die Angebotsliste konnte nicht angezeigt werden.';
   else {state.textContent='⚠ Preisservice nicht erreichbar oder keine gültige JSON-Antwort.';report('error');}
  }
  finally{if(seq===request)btn.disabled=false;}
 };
 target.append(btn,state,content);
});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>reset());
else reset();
})();
