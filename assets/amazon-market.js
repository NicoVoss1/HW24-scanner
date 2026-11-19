/* HW24 Market Prices · v1.0. Prices are fetched only when the user clicks.
 * Uses official Product Pricing API and shows seller IDs, not alleged suppliers.
 * Expects custom event "hw24:amazon-catalog" from index.html.
 */
(()=>{'use strict';
const $=id=>document.getElementById(id);
const el=(tag,text,css)=>{const n=document.createElement(tag);if(css)n.className=css;if(text!==undefined)n.textContent=String(text);return n;};
const money=v=>v&&Number.isFinite(Number(v.amount))?
 new Intl.NumberFormat('de-DE',{style:'currency',currency:v.currency||'EUR'}).format(v.amount):'Keine Angabe';
let panel=null, request=0, lastAsin='';
function mount(){
 const catalog=$('focusAmazonCard');
 if(!catalog)return null;
 if(!panel){
  panel=el('div');panel.id='hw24AmazonMarket';
  panel.style.cssText='margin:16px 0;padding:16px;border:1px solid #a9c3e3;background:#f7faff;border-radius:14px;color:#14233c';
  catalog.insertAdjacentElement('afterend',panel);
 }
 return panel;
}
function reset(message='Preise sind noch nicht abgefragt.'){
 request++;lastAsin='';
 const target=mount();if(!target)return;
 target.replaceChildren(el('h3','💶 Amazon-Marktpreise und Händler'),el('p',message,'note'));
}
function pair(label,value){
 const n=el('div');n.style.cssText='padding:10px 12px;background:#fff;border:1px solid #dde5f0;border-radius:9px';
 const l=el('small',label);l.style.cssText='display:block;color:#566983';
 n.append(l,el('strong',value));return n;
}
function grid(...items){const d=el('div');d.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:12px 0';items.forEach(x=>d.append(x));return d;}
function showOffers(data,place){
 place.replaceChildren();
 const offers=Array.isArray(data.offers)?data.offers:[];
 const winner=offers.find(o=>o.is_buybox_winner);
 const featured=data.buybox_prices?.find(o=>o.landed_price||o.listing_price);
 const prices=offers.map(o=>o.total?.amount).filter(n=>Number.isFinite(n));
 const min=prices.length?Math.min(...prices):null;
 place.append(grid(
  pair('Buy Box / Featured Offer',winner?money(winner.total||winner.item_price):
   (featured?money(featured.landed_price||featured.listing_price):'Nicht ausgewiesen')),
  pair('Günstigstes erfasstes Gesamtangebot',min!==null?money({amount:min,currency:offers.find(o=>o.total?.amount===min)?.total?.currency||'EUR'}):'Nicht ausgewiesen'),
  pair('Angebote laut Amazon',String(data.total_offer_count??'Keine Angabe')),
  pair('Abgerufene Händlerangebote',String(offers.length))
 ));
 const stamp=el('p','Amazon.de · '+new Date(data.retrieved_at_utc).toLocaleString('de-DE')+' · Neuzustand · Preis inkl. Versand nur, wenn Amazon beides liefert.','note');
 place.append(stamp);
 if(!offers.length){place.append(el('p','Amazon hat keine einzelnen Händlerangebote zurückgegeben. Eine Buy-Box-Zuordnung ist damit nicht möglich.','note'));return;}
 const details=el('details');const summary=el('summary','Einzelangebote und Verkäufer-IDs ('+offers.length+')');
 summary.style.cssText='cursor:pointer;font-weight:650;padding:8px 0';
 details.append(summary);
 const wrap=el('div');wrap.style.cssText='display:grid;gap:8px;padding:8px 0';
 for(const o of offers){
  const d=el('div');d.style.cssText='padding:12px;border:1px solid #e0e6ef;background:white;border-radius:9px';
  const label=o.is_buybox_winner?'🏆 Buy-Box-Angebot':o.is_featured_merchant?'Buy-Box-berechtigt':'Weiteres Angebot';
  d.append(el('strong',label+' · '+money(o.total||o.item_price)));
  d.append(el('p','Händler-ID: '+(o.seller_id||'Nicht angegeben')+' · '+(o.is_fba?'Amazon-Versand (FBA)':'Versandart nicht als FBA bestätigt'),'note'));
  d.append(el('small','Artikelpreis '+money(o.item_price)+' · Versand '+money(o.shipping)));
  wrap.append(d);
 }
 details.append(wrap);place.append(details);
 place.append(el('p','Händler-IDs sind keine verifizierten Lieferanten. Bei mehreren Verkäufern können sich Buy Box und Preise jederzeit ändern.','note'));
}
document.addEventListener('hw24:amazon-reset',()=>reset());
document.addEventListener('hw24:amazon-catalog',e=>{
 const data=e.detail?.data;
 const target=mount();if(!target)return;
 target.replaceChildren(el('h3','💶 Amazon-Marktpreise und Händler'));
 if(!data||data.count!==1||!Array.isArray(data.items)||!data.items[0]?.asin){
  target.append(el('p','Bei mehreren ASINs muss zuerst die richtige Produktidentität bestätigt werden.','note'));return;
 }
 const asin=String(data.items[0].asin);
 if(!/^[A-Z0-9]{10}$/.test(asin)){target.append(el('p','Keine gültige ASIN für die Angebotsabfrage.','note'));return;}
 lastAsin=asin;const seq=++request;
 const description=el('p','Buy Box, weitere Verkäufer und Gesamtpreise lassen sich jetzt abrufen.','note');
 const btn=el('button','💶 Aktuelle Amazon-Angebote abrufen','scan-secondary');
 btn.type='button';
 const state=el('p','Noch keine Preisabfrage ausgeführt.','note');
 const content=el('div');
 btn.onclick=async()=>{
  if(seq!==request||lastAsin!==asin)return;
  btn.disabled=true;state.textContent='Amazon-Angebote werden abgefragt …';content.replaceChildren();
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
   }else{
    state.textContent='✓ Preise von Amazon abgerufen. Kein automatischer Eingriff in Verkaufspreise.';
    showOffers(result,content);
   }
  }catch(err){if(seq===request)state.textContent='⚠ Preisservice nicht erreichbar oder keine gültige JSON-Antwort.';}
  finally{if(seq===request)btn.disabled=false;}
 };
 target.append(description,btn,state,content);
});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>reset());
else reset();
})();
