/* HW24 Einkaufs- und Verkaufskalkulation. Read-only source data; no automatic marketplace or JTL writes. */
(()=>{'use strict';
const $=id=>document.getElementById(id);
const node=(tag,label)=>{const n=document.createElement(tag);if(label!==undefined)n.textContent=String(label);return n;};
const currency=v=>Number.isFinite(v)?new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'}).format(v):'Nicht verfügbar';
const number=v=>{
 if(typeof v==='number')return Number.isFinite(v)?v:null;
 const s=String(v??'').trim().replace(/\s/g,'').replace(/[€]/g,'');
 if(!s)return null;
 const n=Number(s.includes(',')?s.replace(/\./g,'').replace(',','.'):s);
 return Number.isFinite(n)?n:null;
};
let ean='',asin='',jtl=null,market=null,fees=null,feeRequest=0;
let panel=null,rate=null,ownPrice=null,ownShipping=null,fulfillment=null,status=null,feeDetails=null;
function field(name){
 const box=[...document.querySelectorAll('#fields .field')].find(b=>b.querySelector('label')?.textContent.trim()===name);
 return box?.querySelector('input,textarea')?.value?.trim()||'';
}
function add(tag,text,parent){const n=node(tag,text);parent.append(n);return n;}
function fact(parent,title,amount,detail){
 const box=node('div');box.style.cssText='padding:11px;border-radius:10px;background:#f5f8fd;border:1px solid #dce5ee;min-width:0';
 const h=add('small',title,box);h.style.cssText='display:block;color:#526a83;margin-bottom:4px';
 const a=add('strong',amount,box);a.style.cssText='font-size:17px;color:#142b43;overflow-wrap:anywhere';
 const d=add('small',detail,box);d.style.cssText='display:block;margin-top:5px;color:#687b8d';
 parent.append(box);
}
function mount(){
 if(panel)return panel;
 const focus=$('focusAmazonCard');
 if(!focus)return null;
 panel=node('section');panel.id='hw24Economics';
 panel.style.cssText='padding:17px;margin:14px 0;border:1px solid #b7cfe3;border-radius:14px;background:#fff;color:#172d44';
 const section=focus.closest('.f-section');
 if(section) section.insertAdjacentElement('afterend',panel);
 else focus.insertAdjacentElement('afterend',panel);
 const h=add('h3','💼 Einkauf, Verkauf & Amazon-Gebühren',panel);h.style.margin='0 0 6px';
 const desc=add('p','JTL- und Marktdaten werden automatisch übernommen. Fehlende Lieferantenstammdaten oder Gebühren werden nicht geschätzt. Keine Änderungen an JTL oder Amazon.',panel);
 desc.style.cssText='font-size:12px;color:#53697f;line-height:1.5';
 const grid=node('div');grid.id='hw24EconomicFacts';grid.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin:12px 0';
 panel.append(grid);
 const source=add('p','Lieferantenstammdaten: noch nicht aus dem JTL-Lieferantenverzeichnis angebunden. Amazon-Verkäufer sind keine bestätigten Einkaufs-Lieferanten.',panel);
 source.id='hw24SupplierStatus';source.style.cssText='font-size:12px;padding:9px;background:#fff9eb;border:1px solid #f1dcad;border-radius:8px';
 const details=node('details');details.style.cssText='margin-top:12px;border-top:1px solid #e5ebf1;padding-top:12px';
 details.append(add('summary','Amazon-Verkaufsgebühren und Preisannahmen berechnen',details));details.firstChild.style.cssText='cursor:pointer;font-weight:650';
 const label=add('p','Standard-USt-Satz 19 % ist ausschließlich eine Rechenannahme, kein geprüfter JTL-Steuerschlüssel. Für Gebühren zählt Dein angebotener Brutto-Verkaufspreis.',details);
 label.style.cssText='font-size:12px;color:#53697f';
 const controls=node('div');controls.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:9px';
 function input(title,type,val){
  const wrap=node('label');wrap.style.cssText='display:grid;gap:4px;font-size:12px;font-weight:600';
  wrap.append(node('span',title));
  const element=node(type);element.style.cssText='width:100%;box-sizing:border-box;padding:9px;border-radius:7px;border:1px solid #c5d4e1;background:white;color:#173449';
  if(type==='input'){element.type='number';element.step='0.01';element.min='0';element.value=val;}
  wrap.append(element);controls.append(wrap);return element;
 }
 ownPrice=input('Verkaufspreis brutto €','input','');
 ownPrice.placeholder='Aus JTL, wenn vorhanden';
 ownShipping=input('Versandentgelt Käufer €','input','0');
 rate=input('USt-Satz für Modell','select');
 [['19','19 % (Annahme)'],['7','7 % (Annahme)'],['0','0 % (nur wenn zutreffend)']].forEach(([v,l])=>{const o=node('option',l);o.value=v;rate.append(o);});
 fulfillment=input('Eigene Versandart','select');
 [['FBM','FBM · eigener Versand'],['FBA','FBA · Amazon-Versand']].forEach(([v,l])=>{const o=node('option',l);o.value=v;fulfillment.append(o);});
 details.append(controls);
 const tools=node('div');tools.style.cssText='display:flex;flex-wrap:wrap;gap:8px;margin:11px 0';
 const button=node('button','Amazon-Gebühren offiziell abfragen');button.type='button';button.className='scan-secondary';
 const marketButton=node('button','Buy-Box-Marktpreis als Modell verwenden');marketButton.type='button';marketButton.className='scan-secondary';marketButton.disabled=true;
 tools.append(button,marketButton);details.append(tools);
 status=add('p','Gebühren noch nicht abgefragt. Amazon Fees API kann zusätzliche Berechtigungen benötigen.',details);status.style.cssText='font-size:12px;color:#526a83';
 feeDetails=node('div');details.append(feeDetails);panel.append(details);
 function netPreview(){
  const gross=number(ownPrice.value);
  const net=gross!==null?gross/(1+Number(rate.value||19)/100):null;
  const preview=$('hw24EconomicTaxPreview');
  if(preview)preview.textContent=net!==null?'Modell netto: '+currency(net)+' bei '+rate.value+' % angenommener USt':'Modell netto: kein Preis vorhanden';
 }
 const preview=add('small','Modell netto: kein Preis vorhanden',details);preview.id='hw24EconomicTaxPreview';preview.style.cssText='display:block;margin:8px 0;color:#556981';
 rate.addEventListener('change',()=>{netPreview();render();});
 ownPrice.addEventListener('input',netPreview);
 marketButton.addEventListener('click',()=>{
  const buy=market?.buybox_prices?.find(p=>p.landed_price?.amount||p.listing_price?.amount);
  const v=buy?.landed_price?.amount??buy?.listing_price?.amount;
  if(!Number.isFinite(v))return;
  ownPrice.value=String(v);
  status.textContent='Amazon-Marktpreis nur als Kalkulationsszenario übernommen, NICHT als eigener Verkaufspreis bestätigt.';
  netPreview();
 });
 button.addEventListener('click',async()=>{
  const price=number(ownPrice.value),shipping=number(ownShipping.value);
  if(!/^[A-Z0-9]{10}$/.test(asin)){status.textContent='Zuerst einen eindeutigen Amazon-Katalogtreffer scannen.';return;}
  if(price===null||price<=0||shipping===null||shipping<0){status.textContent='Brutto-Verkaufspreis und Käufer-Versandentgelt müssen gültige Zahlen sein.';return;}
  const id=++feeRequest;fees=null;feeDetails.replaceChildren();
  button.disabled=true;status.textContent='Offizielle Amazon-Gebührenschätzung wird abgerufen …';
  try{
   const q=new URLSearchParams({asin,price:String(price),shipping:String(shipping),fulfillment:fulfillment.value});
   const res=await fetch('api/amazon-fees.php?'+q.toString(),{credentials:'same-origin',cache:'no-store'});
   const data=await res.json();
   if(id!==feeRequest)return;
   if(!res.ok||!data.ok){
    const why={fees_role_or_access_missing:'Amazon Product Fees API noch nicht freigegeben.',
     amazon_rate_limited:'Amazon-Abfragelimit erreicht.',estimate_not_available:'Amazon liefert für diesen ASIN/Preis keine Gebühren.',
     private_config_missing:'Private Amazon-Konfiguration nicht gefunden.'};
    status.textContent='Gebühren derzeit nicht verfügbar: '+(why[data.error]||data.error||'HTTP '+res.status);
   }else{
    fees=data;
    status.textContent='✓ Amazon-Schätzung für '+data.fulfillment+' und '+currency(data.listing_gross_price)+' Brutto-VK.';
    const box=node('div');box.style.cssText='padding:10px;border:1px solid #d2e3d5;background:#f4fbf5;border-radius:9px';
    box.append(add('strong','Amazon-Gebühren geschätzt: '+currency(data.total_estimated_fees),box));
    for(const fee of data.fee_breakdown||[]){
     if(fee.amount!==null)box.append(add('p',fee.type+': '+currency(fee.amount),box));
    }
    feeDetails.append(box);
   }
  }catch(e){if(id===feeRequest)status.textContent='Gebühren-API nicht erreichbar oder ungültige Antwort.';}
  finally{if(id===feeRequest)button.disabled=false;}
 });
 panel._refresh=()=>{netPreview();marketButton.disabled=!market?.buybox_prices?.some(p=>Number.isFinite(p.landed_price?.amount??p.listing_price?.amount));};
 return panel;
}
function render(){
 if(!mount())return;
 const grid=$('hw24EconomicFacts');grid.replaceChildren();
 const one=jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1?jtl.matches[0]:null;
 const vat=Number(rate.value||19)/100;
 const jtlNet=number(one?.netto_vk);
 const formNet=number(field('Netto-VK'));
 const gross=number(field('Brutto-VK'));
 const preferredNet=jtlNet??formNet;
 const estimatedGross=gross??(preferredNet!==null?preferredNet*(1+vat):null);
 const estimatedNet=preferredNet??(gross!==null?gross/(1+vat):null);
 const grossSource=gross!==null?'Bestehendes Artikelfeld':'Nur Modell aus Netto-VK und '+rate.value+' % USt';
 const netSource=preferredNet!==null?(jtlNet!==null?'Aktueller JTL-Spiegel':'Artikelfeld / CSV'):'Nur Modell aus Brutto-VK und '+rate.value+' % USt';
 fact(grid,'VK brutto',currency(estimatedGross),estimatedGross!==null?grossSource:'Keine geprüfte Quelle');
 fact(grid,'VK netto',currency(estimatedNet),estimatedNet!==null?netSource:'Keine geprüfte Quelle');
 const ek=number(one?.netto_ek)??number(field('Ø EK netto (GLD)'));
 const last=number(one?.letzter_ek)??number(field('Letzter EK (JTL)'));
 fact(grid,'Ø EK netto',currency(ek),one?'JTL-Stand: '+jtl.snapshot_at:'JTL/CSV, falls vorhanden');
 fact(grid,'Letzter EK netto',currency(last),one?.letzter_ek_datum?'Datum: '+one.letzter_ek_datum:'Kein geprüfter Lieferantenbeleg verknüpft');
 const win=market?.buybox_prices?.find(p=>p.landed_price||p.listing_price);
 const buy=win?.landed_price?.amount??win?.listing_price?.amount;
 fact(grid,'Amazon Buy Box Vergleich',currency(buy),'Marktpreis, kein eigener VK');
 const supplier=$('hw24SupplierStatus');
 supplier.textContent='🏭 Lieferant / Bezugsquelle: im aktuellen JTL-Spiegel nicht als namentlich zugeordneter Lieferant verfügbar. Einkaufs- und Bezugsquellen dürfen nicht aus Amazon-Verkäuferprofilen abgeleitet werden.';
 if(estimatedGross!==null&&!ownPrice.value)ownPrice.value=String(Math.round(estimatedGross*100)/100);
 if(panel._refresh)panel._refresh();
}
document.addEventListener('hw24:jtl-result',e=>{if(e.detail?.ean!==ean)return;jtl=e.detail.data;render();});
document.addEventListener('hw24:amazon-catalog',e=>{
 if(e.detail?.ean!==ean)return;
 const data=e.detail.data;
 asin=data?.count===1?String(data.items?.[0]?.asin||''):'';
 render();
});
document.addEventListener('hw24:pricing-result',e=>{
 if(e.detail?.asin!==asin)return;
 market=e.detail.data;render();
});
document.addEventListener('hw24:scan-start',e=>{
 ean=String(e.detail?.ean||'');asin='';jtl=null;market=null;fees=null;++feeRequest;
 if(mount()){
  ownPrice.value='';ownShipping.value='0';fulfillment.value='FBM';
  if(status)status.textContent='Gebühren noch nicht abgefragt.';
  if(feeDetails)feeDetails.replaceChildren();
 }
 render();
});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>{mount();render();});
else {mount();render();}
})();
