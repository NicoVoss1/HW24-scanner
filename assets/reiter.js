/* HW24 v4.0 · JTL article tab coverage.
 * Based on exact export column captions. Missing columns are NOT assumed empty
 * in Wawi: they must be obtained from another JTL source if required.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
const tabs=[
 {name:'Allgemein',fields:['Artikelnummer','GTIN','HAN','Artikelname','Hersteller','Warengruppe','Kategorie Ebene 1','Kategorie Ebene 2','Brutto-VK','Kosten','Steuerklasse','Artikel aktiv']},
 {name:'Beschreibung',fields:['Kurzbeschreibung','Beschreibung','Titel-Tag (SEO)','Meta-Description (SEO)']},
 {name:'Bestände',read:true,fields:['Auf Lager','In Aufträgen','Verfügbar','Lagerbestand Lager [WMS-ZepCh+Bug]']},
 {name:'Lieferanten',fields:['Lieferant','Artikelnummer (Lieferant)','Netto-EK in Standardwährung','Lieferzeit in Tagen (Lieferant)']},
 {name:'Bilder',external:true,desc:'Bilddateien werden nicht durch den vorliegenden Artikelstammexport geliefert. Separate JTL-Quelle erforderlich.'},
 {name:'Attribute/Merkmale',external:true,desc:'Attribute und Merkmale sind nicht im Stammdaten-CSV enthalten. Vorhandene Daten nicht automatisch löschen.'},
 {name:'Sonstiges',fields:['Anmerkung','Artikeltyp','Lieferstatus','Herkunftsland','Neu im Sortiment']},
 {name:'Variationen',external:true,desc:'Nicht im Export enthalten. Nur bei einem tatsächlich variablen Produkt notwendig.'},
 {name:'Verkaufskanäle',fields:['ASIN','JTL-Wawi: Amazon B2C Brutto','JTL-Wawi: Amazon B2B Brutto','JTL-Wawi: eBay Fb24 Brutto','Verkaufskanal [Shopify Fb24]: Verkaufskanal aktiv']},
 {name:'Stückliste/Konfigurator',external:true,desc:'Kein Bestandteil des Stammdatenexports. Für einfache Haarfarbtuben normalerweise nicht erforderlich.'},
 {name:'Sonderpreise',optional:true,fields:['Sonderpreise: Amazon B2C brutto','Sonderpreise: eBay Fb24 brutto']},
 {name:'Dateien',external:true,desc:'Upload- und Mediendateien sind nicht im CSV enthalten.'},
 {name:'Eigene Felder',external:true,desc:'Separate JTL-Felder und FIBU-Zuordnungen nur nach geprüfter Quelle übernehmen.'}
];
let record=null,headers=[];
function el(tag,txt){const d=document.createElement(tag);if(txt!==undefined)d.textContent=txt;return d}
function current(){const root=$('hw24Tabs');if(!root)return;root.hidden=!record;const list=$('hw24TabsItems');list.replaceChildren();if(!record)return;
 for(const t of tabs){
  const row=el('div');row.className='hw24tabrow';
  const head=el('strong',t.name),desc=el('span');
  if(t.external){row.dataset.kind='external';desc.textContent='Zusätzliche Datenquelle · '+t.desc}
  else {
   const existing=t.fields.filter(k=>headers.length?headers.includes(k):Object.prototype.hasOwnProperty.call(record,k));
   const filled=existing.filter(k=>{const value=v(record[k]);return value&&!(['0','0,00','0.00'].includes(value)&&['Kosten'].includes(k))});
   const left=existing.length-filled.length;
   row.dataset.kind=t.read?'read':left?'pending':'present';
   desc.textContent=filled.length+'/'+existing.length+' Quellfelder belegt'+(left?' · '+left+' prüfen':'')+(t.read?' · nur lesen':t.optional?' · optional':'');
  }
  row.append(head,desc);list.append(row);
 }
}
window.hw24TabsCsv=rows=>{headers=rows?.length?Object.keys(rows[0]):[];current()};
window.hw24TabsScan=(id,r)=>{record=r||null;current()};
window.hw24TabsUpdate=r=>{record=r||record;current()};
current();
})();