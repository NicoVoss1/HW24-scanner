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
let ean='',asin='',jtl=null,market=null,marketStatus='idle',fees=null,approved={},feeRequest=0;
let panel=null;
const feeLabels=new Map([
 ['ReferralFee',['Verkaufsprovision','Die kategorieabhängige Verkaufsgebühr, gegebenenfalls mit Mindestbetrag.']],
 ['PerItemFee',['Stückgebühr','Pauschale pro verkaufter Einheit im Tarif Einzelanbieter.']],
 ['VariableClosingFee',['Abschlussgebühr für Medien','Zusatzgebühr für bestimmte Kategorien wie Bücher, Musik und DVDs.']],
 ['FBAFees',['Versand durch Amazon','Gebühren für die Versandabwicklung durch Amazon.']]
]);
const feePercent=new Intl.NumberFormat('de-DE',{style:'percent',minimumFractionDigits:2,maximumFractionDigits:2});
const feeShare=(amount,price)=>Number.isFinite(amount)&&Number.isFinite(price)&&price>0?feePercent.format(amount/price):'—';
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
 panel=node('details');panel.id='hw24Economics';panel.hidden=!ean;
 panel.style.cssText='padding:14px;margin:14px 0;border:1px solid #dce5ee;border-radius:12px;background:#fff;color:#172d44';
 const basket=$('hw24LegacyBasket');
 if(basket){
  const slot=node('div');slot.dataset.originalArticle='yes';slot.append(panel);
  basket.insertAdjacentElement('beforebegin',slot);
 }
 else (focus.closest('.f-section')||focus).insertAdjacentElement('afterend',panel);
 const h=add('summary','Preise und Amazon-Gebühren',panel);h.style.cssText='cursor:pointer;font-weight:700';
 const marketSlot=add('div',undefined,panel);marketSlot.id='hw24AmazonMarketSlot';
 const marketPanel=$('hw24AmazonMarket');if(marketPanel)marketSlot.append(marketPanel);
 const grid=node('div');grid.id='hw24EconomicFacts';grid.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin:12px 0';
 panel.append(grid);
 const source=add('p','Lieferantenstammdaten: noch nicht aus dem JTL-Lieferantenverzeichnis angebunden. Amazon-Verkäufer sind keine bestätigten Einkaufs-Lieferanten.',panel);
 source.id='hw24SupplierStatus';source.style.cssText='font-size:12px;padding:9px;background:#fff9eb;border:1px solid #f1dcad;border-radius:8px';
 const details=node('details');details.style.cssText='margin-top:12px;border-top:1px solid #e5ebf1;padding-top:12px';
 const summary=add('summary','Amazon-Verkaufsgebühren berechnen',details);summary.style.cssText='cursor:pointer;font-weight:650';
 const controls=node('div');controls.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:9px';
 function input(id,title,type,val){
  const wrap=node('div');wrap.style.cssText='display:grid;gap:4px;font-size:12px;font-weight:600';
  const caption=add('label',title,wrap);caption.htmlFor=id;
  const element=node(type);element.id=id;element.style.cssText='width:100%;box-sizing:border-box;padding:9px;border-radius:7px;border:1px solid #c5d4e1;background:white;color:#173449';
  if(type==='input'){element.type='number';element.step='0.01';element.min='0';element.value=val;}
  wrap.append(element);controls.append(wrap);return element;
 }
 const ownPrice=input('hw24FeeGross','Amazon-Angebots-VK brutto €','input','');
 const ownShipping=input('hw24FeeShipping','Versandentgelt Käufer €','input','0');
 const rate=input('hw24FeeVat','Artikel-USt. für Netto-VK','select');
 [['auto','Steuersatz aus JTL'],['19','19 % (manuell)'],['7','7 % (manuell)'],['0','0 % (manuell)']].forEach(([v,l])=>{const o=node('option',l);o.value=v;rate.append(o);});
 const fulfillment=input('hw24FeeChannel','Versandart','select');
 [['','Versandart wählen'],['FBM','FBM · eigener Versand'],['FBA','FBA · Amazon-Versand']].forEach(([v,l])=>{const o=node('option',l);o.value=v;fulfillment.append(o);});
 fulfillment.value='FBM';
 details.append(controls);
 const priceInfo=add('p','',details);priceInfo.style.cssText='font-size:12px;color:#53697f;margin:8px 0';
 const tools=node('div');tools.style.cssText='display:flex;flex-wrap:wrap;gap:8px;margin:11px 0';
 const button=node('button','Amazon-Gebühren offiziell abfragen');button.type='button';button.className='scan-secondary';
 const ownButton=node('button','Eigenes Angebot übernehmen');ownButton.type='button';ownButton.className='scan-secondary';
 const marketButton=node('button','Buy Box als Szenario übernehmen');marketButton.type='button';marketButton.className='scan-secondary';
 tools.append(button,ownButton,marketButton);details.append(tools);
 const status=add('p','Gebühren noch nicht abgefragt.',details);status.style.cssText='font-size:12px;color:#526a83';status.setAttribute('role','status');
 const feeDetails=node('div');details.append(feeDetails);panel.append(details);
 let offer=null,articleVat=null,priceMode='own',shippingEdited=false,fulfillmentEdited=false;
 const selectedVat=()=>rate.value==='auto'?articleVat:number(rate.value);
 const netPrice=gross=>{
  const vat=selectedVat();
  return Number.isFinite(gross)&&gross>0&&vat!==null?Math.round(gross*100/(1+vat/100))/100:null;
 };
 const isEur=money=>money?.currency==='EUR'&&Number.isFinite(money.amount);
 const buyBoxScenario=()=>{
  const summary=(market?.buybox_prices||[]).find(row=>isEur(row.listing_price));
  const winner=(market?.offers||[]).find(row=>row.is_buybox_winner===true&&isEur(row.item_price));
  if(summary)return {gross:summary.listing_price.amount,shipping:isEur(summary.shipping)?summary.shipping.amount:null};
  return winner?{gross:winner.item_price.amount,shipping:isEur(winner.shipping)?winner.shipping.amount:null}:null;
 };
 function quoteKey(){
  return [asin,ownPrice.value,ownShipping.value,fulfillment.value,priceMode,priceMode==='own'?offer?.sku:''].join('\u0000');
 }
 function resetEstimate(){
  ++feeRequest;fees=null;feeDetails.replaceChildren();button.disabled=false;
  status.textContent='Gebühren noch nicht abgefragt.';
 }
 function showEstimate(){
  if(!fees)return;
  const gross=fees.listing_gross_price;
  const net=netPrice(gross);
  const box=node('div');box.style.cssText='padding:10px;border:1px solid #d2e3d5;background:#f4fbf5;border-radius:9px;display:grid;gap:8px';
  const showFee=(label,amount,description)=>{
   const row=node('div');row.style.cssText='padding:8px 10px;background:#fff;border:1px solid #dce7df;border-radius:7px';
   const header=node('div');header.style.cssText='display:flex;flex-wrap:wrap;justify-content:space-between;gap:5px';
   header.append(node('strong',label),node('strong',Number.isFinite(amount)?currency(amount):'Nicht ausgewiesen'));
   row.append(header);
   if(description)add('small',description,row);
   if(Number.isFinite(amount)){
    const comparison=add('div','Vom Brutto-VK '+feeShare(amount,gross)+' · vom Netto-VK '+feeShare(amount,net),row);
    comparison.style.cssText='font-size:12px;color:#38546d;margin-top:4px';
   }
   box.append(row);
  };
  showFee('Geschätzte Amazon-Gebühren gesamt',fees.total_estimated_fees);
  const rows=Array.isArray(fees.fee_breakdown)?fees.fee_breakdown:[];
  for(const [key,[label,description]] of feeLabels){
   const matching=rows.filter(fee=>fee.type===key&&Number.isFinite(fee.amount));
   if(matching.length||key!=='FBAFees')showFee(label,matching.length?matching.reduce((sum,fee)=>sum+fee.amount,0):null,description);
  }
  for(const fee of rows)if(!feeLabels.has(fee.type))showFee(fee.type,fee.amount);
  const note=add('small','Beide Anteile verwenden dieselbe Amazon-Gebührenschätzung. Der Bezugswert ist der Artikelpreis ohne Versandentgelt.',box);
  note.style.cssText='color:#526a83';
  feeDetails.replaceChildren(box);
 }
 function refreshQuote(){
  const gross=number(ownPrice.value),net=netPrice(gross),vat=selectedVat();
  let source='Kein eindeutig zugeordnetes eigenes Amazon-Angebot.';
  if(priceMode==='own'&&offer)source='Eigenes Amazon-Angebot · '+offer.source;
  else if(priceMode==='market')source='Preisannahme aus der Amazon Buy Box.';
  else if(priceMode==='manual')source='Manuelle Preisannahme.';
  priceInfo.textContent=source+(gross>0?(net!==null?' · Netto-VK '+currency(net)+' bei '+vat.toLocaleString('de-DE')+' % Artikel-USt.':' · Netto-VK erst mit einem Artikel-USt.-Satz berechenbar.'):'');
  ownButton.hidden=!offer||priceMode==='own';
  marketButton.disabled=!buyBoxScenario();
  showEstimate();
 }
 function fillOwnQuote(){
  ownPrice.value=offer?String(offer.gross):'';
  if(!shippingEdited)ownShipping.value=offer?String(offer.shipping??''):'0';
  if(!fulfillmentEdited)fulfillment.value=offer?(offer.fulfillment||''):'FBM';
 }
 rate.addEventListener('change',render);
 for(const control of [ownPrice,ownShipping,fulfillment])control.addEventListener(control===fulfillment?'change':'input',()=>{
  if(control===ownPrice)priceMode='manual';
  if(control===ownShipping)shippingEdited=true;
  if(control===fulfillment)fulfillmentEdited=true;
  resetEstimate();refreshQuote();
 });
 ownButton.addEventListener('click',()=>{
  priceMode='own';shippingEdited=false;fulfillmentEdited=false;
  fillOwnQuote();resetEstimate();refreshQuote();
 });
 marketButton.addEventListener('click',()=>{
  const scenario=buyBoxScenario();if(!scenario)return;
  priceMode='market';ownPrice.value=String(scenario.gross);ownShipping.value=String(scenario.shipping??'');
  resetEstimate();refreshQuote();
 });
 button.addEventListener('click',async()=>{
  const price=number(ownPrice.value),shipping=number(ownShipping.value);
  if(!/^[A-Z0-9]{10}$/.test(asin)){status.textContent='Zuerst einen eindeutigen Amazon-Katalogtreffer scannen.';return;}
  if(price===null||price<0.01||price>100000||shipping===null||shipping<0||shipping>10000){status.textContent='Bitte einen gültigen Brutto-VK und das Käufer-Versandentgelt eingeben. Bei kostenlosem Versand 0 eintragen.';return;}
  if(!fulfillment.value){status.textContent='Bitte die Versandart für die Gebührenschätzung auswählen.';return;}
  const id=++feeRequest;fees=null;feeDetails.replaceChildren();
  button.disabled=true;status.textContent='Offizielle Amazon-Gebührenschätzung wird abgerufen …';
  try{
   const q=new URLSearchParams({asin,price:String(price),shipping:String(shipping),fulfillment:fulfillment.value});
   if(priceMode==='own'&&offer?.sku)q.set('seller_sku',offer.sku);
   const res=await fetch('api/amazon-fees.php?'+q.toString(),{credentials:'same-origin',cache:'no-store'});
   const data=await res.json();
   if(id!==feeRequest)return;
   if(!res.ok||!data.ok){
    const why={fees_role_or_access_missing:'Amazon Product Fees API noch nicht freigegeben.',
     amazon_rate_limited:'Amazon-Abfragelimit erreicht.',estimate_not_available:'Amazon liefert für dieses Angebot und diesen Preis keine Gebühren.',
     private_config_missing:'Private Amazon-Konfiguration nicht gefunden.'};
    status.textContent='Gebühren derzeit nicht verfügbar: '+(why[data.error]||data.error||'HTTP '+res.status);
   }else{
    fees=data;
    status.textContent='Amazon-Schätzung für '+data.fulfillment+' · '+currency(data.listing_gross_price)+' Brutto-VK · '+currency(data.shipping_charged_to_buyer)+' Käufer-Versandentgelt.';
    showEstimate();
   }
  }catch(e){if(id===feeRequest)status.textContent='Gebühren-API nicht erreichbar oder ungültige Antwort.';}
  finally{if(id===feeRequest)button.disabled=false;}
 });
 panel._refresh=one=>{
  const previous=quoteKey();offer=resolveOwnAmazonOffer(one,market,asin);
  if(priceMode==='own')fillOwnQuote();
  articleVat=resolveArticleVat(one);
  rate.options[0].textContent=articleVat===null?'JTL-Steuersatz nicht eindeutig':articleVat.toLocaleString('de-DE')+' % (JTL-Steuersatz)';
  if(previous!==quoteKey())resetEstimate();
  refreshQuote();
 };
 panel._resetQuote=()=>{
  offer=null;articleVat=null;priceMode='own';rate.value='auto';shippingEdited=false;fulfillmentEdited=false;
  fillOwnQuote();resetEstimate();
 };
 panel._resetEstimate=resetEstimate;
 return panel;
}
function resolveArticleVat(article) {
 const tax = article?.related_sources?.steuer_saetze_de;
 if (tax?.confirmed !== true || tax.truncated || !Array.isArray(tax.rows)) return null;
 const articleClass = String(article.steuerklasse_id ?? '').trim();
 const approvedClass = String(approved.Steuerklasse ?? '').trim().toLocaleLowerCase('de');
 if (approvedClass && approvedClass !== String(article.steuerklasse_name ?? '').trim().toLocaleLowerCase('de')) return null;
 const rows = tax.rows.filter(row => row?.zielland === 'DE' &&
  (!articleClass || String(row.steuerklasse_id ?? '').trim() === articleClass));
 const rates = rows.map(row => number(row.steuersatz_quellwert));
 if (!rates.length || rates.some(value => value === null || value < 0 || value > 100)) return null;
 return rates.every(value => value === rates[0]) ? rates[0] : null;
}
function resolveOwnAmazonOffer(article, marketData, catalogAsin) {
 const asin = String(catalogAsin ?? '').trim().toUpperCase();
 const own = marketData?.asin === asin && Array.isArray(marketData.offers) ?
  marketData.offers.filter(offer => offer?.is_own_offer === true) : [];
 if (own.length === 1) {
  const offer = own[0], gross = number(offer.item_price?.amount), currency = offer.item_price?.currency;
  if (gross > 0 && currency === 'EUR') {
   const shipping = offer.shipping?.currency === currency ? number(offer.shipping.amount) : null;
   return {gross, currency, shipping, sku:null,
    fulfillment:offer.is_fba === true ? 'FBA' : offer.is_fba === false ? 'FBM' : null,
    source:'Amazon Pricing API'};
  }
 }
 const source = article?.related_sources?.amazon_angebote;
 if (source?.truncated || !Array.isArray(source?.rows)) return null;
 // The checked JTL platform code 51 is Amazon.de (also called tPlattform.cID Amazon.de).
 const rows = source.rows.filter(row => ['51', 'amazon.de'].includes(String(row?.plattform ?? '').trim().toLowerCase()) &&
  /^[A-Z0-9]{10}$/.test(String(row.asin ?? '').trim().toUpperCase()) &&
  (!asin || String(row.asin).trim().toUpperCase() === asin));
 if (rows.length !== 1) return null;
 const row = rows[0], gross = number(row.preis_angebot);
 const currency = String(row.waehrung ?? 'EUR').trim().toUpperCase();
 if (!(gross > 0) || currency !== 'EUR') return null;
 const fulfillmentCode = String(row.fulfillment ?? '').trim().toUpperCase();
 const fulfillment = {AMAZON_EU:'FBA', AFN:'FBA', FBA:'FBA', DEFAULT:'FBM', MFN:'FBM', FBM:'FBM'}[fulfillmentCode] ?? null;
 return {gross, currency, shipping:null, fulfillment, sku:String(row.seller_sku ?? '').trim() || null, source:'JTL-Angebotsspiegel'};
}
function resolveMarketBuyBox(marketData, preferLanded = false) {
 const prices = (rows, key) => rows.map(row => row?.[key])
  .filter(price => Number.isFinite(price?.amount) && price.amount > 0 && price.currency === 'EUR');
 const summary = Array.isArray(marketData?.buybox_prices) ? marketData.buybox_prices : [];
 const winners = Array.isArray(marketData?.offers) ? marketData.offers.filter(offer => offer?.is_buybox_winner === true) : [];
 const itemPrices = [[summary, 'listing_price', false], [winners, 'item_price', false]];
 const totals = [[summary, 'landed_price', true], [winners, 'total', true]];
 for (const [rows, key, includesShipping] of preferLanded ? [...totals, ...itemPrices] : [...itemPrices, ...totals]) {
  const amounts = prices(rows, key);
  if (amounts.length) return [...new Map(amounts.map(price => [price.amount, {...price, includesShipping}])).values()];
 }
 return [];
}
function render(){
 if(!mount())return;
 panel.hidden=!ean;
 const grid=$('hw24EconomicFacts');grid.replaceChildren();
 const one=jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1?jtl.matches[0]:null;
 const vatRate = resolveArticleVat(one);
 const vat = vatRate === null ? null : vatRate / 100;
 const jtlNet=number(one?.netto_vk);
 const formNet=number(field('Netto-VK'));
 const savedGross=number(approved['Brutto-VK']),savedNet=number(approved['Netto-VK']);
 const gross=savedGross??number(field('Brutto-VK'));
 const preferredNet=savedNet??jtlNet??formNet;
 const estimatedGross=gross??(preferredNet!==null&&vat!==null?preferredNet*(1+vat):null);
 const estimatedNet=preferredNet??(gross!==null&&vat!==null?gross/(1+vat):null);
 const taxNote=vatRate===null?'Eindeutiger JTL-Steuersatz für DE fehlt':vatRate.toLocaleString('de-DE')+' % laut JTL-Steuerzone DE';
 const grossSource=savedGross!==null?'Geprüfter HW24-Artikelstand':
  gross!==null?'Artikelfeld / CSV':estimatedGross!==null?'Aus Netto-VK bei '+taxNote:taxNote;
 const netSource=preferredNet!==null?(savedNet!==null?'Geprüfter HW24-Artikelstand':
  jtlNet!==null?'Aktueller JTL-Spiegel':'Artikelfeld / CSV'):estimatedNet!==null?'Aus Brutto-VK bei '+taxNote:taxNote;
 fact(grid,'Standard-VK brutto',currency(estimatedGross),grossSource);
 fact(grid,'Standard-VK netto',currency(estimatedNet),netSource);
 const mirrorEk = number(one?.netto_ek);
 const ek=mirrorEk??number(field('Ø EK netto (GLD)'));
 const last=number(one?.letzter_ek)??number(field('Letzter EK (JTL)'));
 fact(grid,'Ø EK netto',currency(ek),mirrorEk!==null?'JTL-Spiegel':ek!==null?'Artikelfeld / CSV':'Kein GLD-EK übermittelt');
 fact(grid,'Letzter EK netto',currency(last),one?.letzter_ek_datum?'JTL-Buchung: '+one.letzter_ek_datum:'Kein geprüfter JTL-Buchungsbeleg verknüpft');
 const savedCost = number(approved.Kosten);
 const mirrorCost = number(one?.kosten);
 const cost = savedCost ?? mirrorCost ?? number(field('Kosten'));
 fact(grid,'Kosten · eigener Artikelstand',currency(cost),
  savedCost !== null ? 'Geprüfter HW24-Artikelstand' : mirrorCost !== null ? 'JTL-Artikelkosten' :
   cost !== null ? 'Artikelfeld / CSV' : 'Keine Artikelkosten übermittelt');
 const stockSource = one?.related_sources?.bestand;
 const stock = !stockSource?.truncated && stockSource?.rows?.length === 1 ? number(stockSource.rows[0]?.fLagerbestand) : null;
 const stockValue = stock !== null && stock >= 0 && mirrorEk !== null && mirrorEk >= 0 ? stock * mirrorEk : null;
 fact(grid,'Bestandswert netto',currency(stockValue),stockValue !== null ?
  stock.toLocaleString('de-DE')+' auf Lager × '+currency(mirrorEk)+' Ø EK netto (JTL)' :
  'Benötigt eindeutigen Lagerbestand und Ø EK netto aus JTL');
 const own = resolveOwnAmazonOffer(one, market, asin);
 const ownNet = own && vatRate !== null ? own.gross / (1 + vatRate / 100) : null;
 const ebayOwn=number(one?.ebay_preis);
 fact(grid,'Eigener Amazon-Angebots-VK brutto',currency(own?.gross),own ?
  own.source + ' · Amazon.de' + (own.sku ? ' · SKU '+own.sku : '') : 'Kein eindeutiger eigener Angebotspreis für Amazon.de');
 fact(grid,'Eigener Amazon-Angebots-VK netto',currency(ownNet),ownNet !== null ?
  'Aus Brutto-VK bei ' + vatRate.toLocaleString('de-DE') + ' % laut JTL-Steuerzone DE' :
  own ? 'Eindeutiger JTL-Steuersatz für DE fehlt' : 'Eigener Amazon-Angebots-VK fehlt');
 fact(grid,'eBay-VK · eigener JTL-Spiegel',currency(ebayOwn),ebayOwn!==null?'Spiegelwert, eBay-Vorlagen noch nicht bestätigt':'eBay-Vorlagenpreise noch nicht synchronisiert');
 const knownShipping = Number.isFinite(own?.shipping) && own.shipping >= 0;
 const buyBox = resolveMarketBuyBox(market, knownShipping);
 const amount = buyBox[0]?.amount;
 const includesShipping = buyBox[0]?.includesShipping;
 const comparable = own && buyBox.length === 1 && buyBox[0].currency === own.currency && (!includesShipping || knownShipping);
 const delta = comparable ? Math.round((own.gross + (includesShipping ? own.shipping : 0) - amount) * 100) / 100 : null;
 const ownLabel = includesShipping ? 'Eigener Gesamtpreis' : 'Eigener Artikel-VK';
 const comparison = delta === null ? '' : delta === 0 ? ' · '+ownLabel+' gleichauf' :
  ' · '+ownLabel+' '+currency(Math.abs(delta))+(delta > 0 ? ' darüber' : ' darunter');
 const buyAmount = buyBox.length > 1 ? currency(Math.min(...buyBox.map(price => price.amount))) + ' bis ' +
  currency(Math.max(...buyBox.map(price => price.amount))) : currency(amount);
 fact(grid,'Amazon Buy Box',buyAmount,buyBox.length ?
  'Amazon.de · '+(includesShipping ? 'Gesamtpreis inkl. Versand' : 'Artikelpreis ohne Versand')+comparison :
  market ? 'Amazon hat keinen Buy-Box-Preis übermittelt' : marketStatus==='loading' ? 'Amazon-Angebote werden abgefragt' :
  marketStatus==='error' ? 'Preisabfrage fehlgeschlagen' : 'Aktuelle Angebote für den Vergleich abrufen');
 const supplier=$('hw24SupplierStatus');
 const supplierSource = one?.related_sources?.lieferantenartikel;
 const supplierRows = Array.isArray(supplierSource?.rows) ? supplierSource.rows.length : 0;
 if (approved.Lieferant || one?.lieferant) {
  const name = approved.Lieferant || one.lieferant;
  const sameSupplier = !approved.Lieferant || approved.Lieferant === one?.lieferant;
  const articleNumber = approved['Artikelnummer (Lieferant)'] || (sameSupplier ? one?.lieferant_artikelnummer : '');
  supplier.textContent = 'Lieferant · '+name+(articleNumber ? ' · Art.-Nr. '+articleNumber : '')+
   (approved.Lieferant ? ' · Geprüfter HW24-Artikelstand' : ' · JTL-Lieferantenstamm');
 } else {
  supplier.textContent = supplierRows ?
   'JTL-Lieferant · '+(supplierSource.truncated ? 'mindestens ' : '')+supplierRows+' Zuordnungen'+
    (one?.lieferant_artikelnummer ? ' · Art.-Nr. '+one.lieferant_artikelnummer : '')+' · Klarname fehlt im Spiegel' :
   'JTL-Lieferant · Keine Bezugsquelle übermittelt';
 }
 if(panel._refresh)panel._refresh(one);
}
function isCurrentLookup(identifier){
 if(!identifier||!ean)return false;
 return identifier===ean||(jtl?.matches?.length===1&&identifier===String(jtl.matches[0]?.ean??'').trim());
}
document.addEventListener('hw24:curated-applied',e=>{
 if(!isCurrentLookup(e.detail?.ean))return;approved=e.detail.fields||{};render();
});
document.addEventListener('hw24:jtl-result',e=>{if(e.detail?.ean!==ean)return;jtl=e.detail.data;render();});
document.addEventListener('hw24:amazon-reset',()=>{
 if(!asin&&!market)return;
 asin='';market=null;marketStatus='idle';panel?._resetEstimate();render();
});
document.addEventListener('hw24:amazon-catalog',e=>{
 if(!isCurrentLookup(e.detail?.ean))return;
 const data=e.detail.data;
 const nextAsin=data?.count===1?String(data.items?.[0]?.asin||'').trim().toUpperCase():'';
 if(nextAsin!==asin){asin=nextAsin;market=null;marketStatus='idle';panel?._resetEstimate();}
 render();
});
document.addEventListener('hw24:pricing-result',e=>{
 if(e.detail?.asin!==asin)return;
 market=e.detail.data;marketStatus='ready';render();
});
document.addEventListener('hw24:pricing-state',e=>{
 if(e.detail?.asin!==asin)return;
 market=null;marketStatus=e.detail.status;render();
});
document.addEventListener('hw24:scan-start',e=>{
 ean=String(e.detail?.ean||'');asin='';jtl=null;market=null;marketStatus='idle';approved={};
 if(mount()){panel.open=Boolean(ean);panel._resetQuote();}
 render();
});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>{mount();render();});
else {mount();render();}
})();
