/*
 * Pure presentation model for the HW24 read-only source explorer.
 * This module does not fetch network data, guess tax rates or write values.
 */
(function(root,make){
 'use strict';
 const model=make();
 if(typeof module!=='undefined'&&module.exports)module.exports=model;
 if(root)root.HW24SourceCore=model;
})(typeof globalThis!=='undefined'?globalThis:null,function(){
 'use strict';
 const clean=x=>String(x??'').trim();
 function priceText(value,decimals=2){
  if(value===null||value===undefined||clean(value)==='')return 'Nicht verfügbar';
  const s=clean(value);
  if(!/^-?\d{1,12}(?:\.\d{1,14})?$/.test(s))return 'Nicht verfügbar';
  return Number(s).toLocaleString('de-DE',{
   minimumFractionDigits:2,maximumFractionDigits:decimals
  });
 }
 // The two eBay labels are operator-supplied names, NOT verified JTL account IDs.
 // Do not invent per-account prices from tArtikel.fEbayPreis.
 function channelRows(p={}){
  const row=(channel,account,amount,meaning,source,state)=>({
   channel,account,price:amount===null?'Nicht verfügbar':priceText(amount,meaning==='Netto-VK'?4:2),
   currency:'Nicht nachgewiesen',
   meaning,source:source||'Keine verifizierte Quelle',state
  });
  return [
   row('JTL-Wawi','Standard-Netto',p.jtl_net??null,'Netto-VK',
       p.jtl_net_source,'JTL-Originalwert'),
   row('JTL-Wawi','Standard-Brutto',p.jtl_gross??null,'Brutto-VK',
       p.jtl_gross_source,p.jtl_gross!=null?'JTL-Bruttoquelle nachgewiesen':'Quelle offen'),
   row('Amazon','Artikelstamm (nicht Live-Angebot)',p.amazon_jtl_value??null,'Preisart nicht verifiziert',
       p.amazon_jtl_source,'JTL-Stammwert, keine Live-Preisbestätigung'),
   row('eBay','Artikelstamm (nicht kontogebunden)',p.ebay_jtl_value??null,'Preisart nicht verifiziert',
       p.ebay_jtl_source,'JTL-Stammwert, nicht kontospezifisch'),
   row('eBay','www.serverler24.shop',null,'Angebotspreis nicht abgefragt',
       null,'Konto-ID und Live-Angebot noch nicht zugeordnet'),
   row('eBay','friseurbedarf24.shop',null,'Angebotspreis nicht abgefragt',
       null,'Konto-ID und Live-Angebot noch nicht zugeordnet'),
   row('Shopify','HAIRWORLD24',null,'Shoppreis nicht abgefragt',
       null,'Shopify-Kanalpreis noch nicht zugeordnet'),
   row('Shopify','Boosterwelt24',null,'Shoppreis nicht abgefragt',
       null,'Shopify-Kanalpreis noch nicht zugeordnet')
  ];
 }
 function filteredFields(fields,query='',group='all'){
  const needle=clean(query).toLocaleLowerCase('de-DE');
  return (Array.isArray(fields)?fields:[]).filter(f=>{
   if(!f||typeof f!=='object')return false;
   const source=clean(f.source);
   if(group!=='all'&&source!==group)return false;
   const hay=[source,f.field,f.label,f.value,f.row].map(clean).join(' ').toLocaleLowerCase('de-DE');
   return !needle||hay.includes(needle);
  });
 }
 function availableGroups(fields){
  return [...new Set((Array.isArray(fields)?fields:[]).map(x=>clean(x?.source)).filter(Boolean))]
   .sort((a,b)=>a.localeCompare(b,'de'));
 }
 return {priceText,channelRows,filteredFields,availableGroups};
});