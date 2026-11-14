/* HW24 Kanalcheck 3.1: preflight drafts, not marketplace publication */
(function(){
'use strict';
const $=id=>document.getElementById(id),val=v=>String(v??'').trim();
const gtin=s=>{if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(s)||/^0+$/.test(s))return false;let n=0,w=3;for(let i=s.length-2;i>=0;i--){n+=+s[i]*w;w=w===3?1:3}return (10-n%10)%10===+s[s.length-1]};
const schemes={
 amazon:{name:'Amazon',message:'Pflichtattribute hängen von Produkttyp und Angebotsart ab. Ohne offizielle Produkttyp-Vorlage keine vollständige Freigabe.',fields:[
 ['title','Produkttitel','basis'],['brand','Marke','basis'],['product_type','Produkttyp','kategorie'],['asin','Vorhandene ASIN','pruefen'],['gtin','GTIN','pruefen'],
 ['description','Beschreibung','empfohlen'],['bullet1','Merkmal 1','empfohlen'],['bullet2','Merkmal 2','empfohlen'],
 ['price','Brutto-Angebotspreis','verkauf'],['qty','Verkaufbarer Bestand','verkauf'],['image','Hauptbild-URL','verkauf']]},
 shopify:{name:'Shopify',message:'Für die erstmalige Produktanlage per CSV ist Title erforderlich. Zusätzliche Angaben und Variantenregeln separat prüfen.',fields:[
 ['title','Produkttitel','basis'],['vendor','Anbieter / Hersteller','empfohlen'],['product_type','Produkttyp / Kategorie','kategorie'],
 ['handle','URL-Handle','empfohlen'],['sku','SKU / Artikelnummer','empfohlen'],['gtin','GTIN','pruefen'],
 ['description','Beschreibung','empfohlen'],['price','Brutto-Verkaufspreis','verkauf'],['qty','Verkaufbarer Bestand','verkauf'],['image','Hauptbild-URL','verkauf']]},
 ebay:{name:'eBay',message:'Kategorie- und Pflichtmerkmale müssen aus der eBay-Taxonomy-API für die gewählte Kategorie abgerufen werden.',fields:[
 ['title','Angebotstitel','basis'],['brand','Marke','empfohlen'],['category_id','eBay-Kategorie-ID','kategorie'],
 ['specifics','Kategorieabhängige Merkmale','kategorie'],['condition','Artikelzustand','verkauf'],['description','Beschreibung','empfohlen'],
 ['price','Brutto-Angebotspreis','verkauf'],['qty','Verkaufbarer Bestand','verkauf'],['shipping','Versandprofil','verkauf'],['image','Hauptbild-URL','verkauf']]}
};
const metadata={basis:'Basisprüfung',kategorie:'Kategoriepflichten offen',empfohlen:'Empfehlung',verkauf:'Verkaufsdaten',pruefen:'Identität prüfen'};
let ean='',active='amazon',item={},jtl=null,historical=null,forms={},origins={};
function txt(parent,tag,text,cls){let e=document.createElement(tag);e.textContent=text;if(cls)e.className=cls;parent.append(e);return e}
function slug(s){return val(s).toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/ß/g,'ss').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,85)}
function key(){return 'hw24_kanalentwurf_v31_'+ean}
function remembered(){try{return JSON.parse(localStorage.getItem(key())||'{}')}catch(e){return {}}}
function sourceCandidates(k){
 const results=[],seen=new Set();
 function add(raw,src,kind){
  const value=val(raw);if(!value||seen.has(value.toLowerCase()))return;
  if(['gtin'].includes(k)&&!gtin(value))return;
  if(k==='asin'&&!/^[a-z0-9]{10}$/i.test(value))return;
  seen.add(value.toLowerCase());results.push({value,src,kind});
 }
 const r=(jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1)?jtl.matches[0]:null;
 const jm={title:'artikelname',brand:'hersteller',vendor:'hersteller',sku:'artikelnummer',gtin:'ean',asin:'asin'};
 if(r&&jm[k])add(r[jm[k]],'JTL aktuell','jtl');
 const local={title:['Artikelname','Shopify: Titel','Amazon: Produkttitel','eBay: Titel'],brand:['Hersteller','Amazon: Marke','eBay: Marke'],vendor:['Hersteller'],
  sku:['Artikelnummer'],gtin:['GTIN','EAN'],asin:['ASIN'],product_type:['Warengruppe','Kategorie Ebene 1','Shopify: Produkttyp','Amazon: Produkttyp'],
  category_id:['eBay Kategorie-ID'],description:['Beschreibung','Kurzbeschreibung'],price:['Brutto-VK'],qty:['Verfügbarer Bestand'],
  image:['Hauptbild-URL','Bild-URL'],handle:['Shopify: URL-Handle'],specifics:['eBay: Artikelmerkmale'],shipping:['Versandprofil'],
  condition:['Artikelzustand'],bullet1:['Amazon: Bullet 1'],bullet2:['Amazon: Bullet 2']};
 for(const f of local[k]||[])if(item[f]!==undefined)add(item[f],'Lokale Arbeitskopie: '+f,'local');
 const patterns={title:/^(artikelname|name|citemname|produktname|bezeichnung)$/i,brand:/^(hersteller|marke|brand)$/i,
  vendor:/^(hersteller|marke|brand)$/i,gtin:/^(gtin|ean|barcode|cbarcode)$/i,asin:/^(asin|asin aus jtl|casin\d*)$/i,
  sku:/^(artikelnummer|cArtNr|sku)$/i,product_type:/^(warengruppe|produktgruppe|kategorie|produktart)$/i,
  category_id:/^(ebay kategorie[- ]?id|ebay category id)$/i,description:/^(beschreibung|kurzbeschreibung|produktbeschreibung)$/i,
  specifics:/^(artikelmerkmale|attribute|produktmerkmale)$/i};
 for(const row of historical?.matches||[]){
  for(const c of row.cells||[]){
    if(c.header_verified&&patterns[k]?.test(val(c.label))){
      add(c.value,'Excel: '+(row.sheet_name||'Blatt '+row.sheet_no)+', Zeile '+row.excel_row+' · '+c.label,'historical')
    }
  }
  if(results.length>=12)break;
 }
 if(k==='handle'&&item.Artikelname)add(slug(item.Artikelname),'Aus Artikelname abgeleitet','derived');
 return results.slice(0,12);
}
function isValid(k,value,channel){
 const x=val(value);if(!x)return false;
 if(k==='gtin')return gtin(x);
 if(k==='price')return Number(x.replace(',','.'))>0;
 if(k==='qty')return /^\d+(?:[,.]\d+)?$/.test(x);
 if(k==='title'&&channel==='ebay')return x.length<=80;
 if(k==='category_id')return /^\d+$/.test(x);
 if(k==='asin')return /^[A-Z0-9]{10}$/i.test(x);
 if(k==='image')return /^https:\/\/\S+$/i.test(x);
 return true;
}
function score(id){
 const f=schemes[id].fields.filter(x=>x[2]==='basis'||x[2]==='verkauf');
 return {complete:f.filter(x=>isValid(x[0],forms[id][x[0]],id)).length,total:f.length,
  missing:f.filter(x=>!isValid(x[0],forms[id][x[0]],id)).map(x=>x[1])};
}
function status(text){$('channelNotice').textContent=text}
function paint(){
 const root=$('channelWorkbench');if(!root)return;root.hidden=!ean;if(!ean)return;
 $('channelHeadline').textContent='Verkaufskanal-Check · '+ean;
 let jstatus='JTL-Synchronisation noch nicht eingerichtet. Neu oder vorhanden ist unbestätigt.';
 if(jtl?.jtl_status==='current_snapshot'){
  const n=jtl.matches?.length||0;
  jstatus=n===1?'JTL: Artikel gefunden → Produktpflege':n>1?'JTL: Mehrfachtreffer → Zuordnung erforderlich':'JTL: kein Treffer in aktueller Synchronisation → mögliche Neuanlage';
 }else if(jtl?.jtl_status==='stale')jstatus='JTL-Daten veraltet → keine Neuanlageentscheidung';
 $('channelJtlState').textContent=jstatus;
 const tabs=$('channelTabs');tabs.replaceChildren();
 for(const id of ['amazon','shopify','ebay']){
  const b=document.createElement('button'),sc=score(id);b.type='button';b.textContent=schemes[id].name+' '+sc.complete+'/'+sc.total;
  b.style.cssText='margin:4px;background:'+(active===id?'#173b7a':'#e5ebf4')+';color:'+(active===id?'white':'#173b7a');
  b.onclick=()=>{active=id;paint()};tabs.append(b);
 }
 const form=$('channelForms');form.replaceChildren();
 txt(form,'p',schemes[active].message,'note');
 const sc=score(active);
 txt(form,'p','Vorprüfung: '+sc.complete+'/'+sc.total+' Basis-/Verkaufsfelder. '+(sc.missing.length?'Noch offen: '+sc.missing.join(', '):'Alle Basisfelder ausgefüllt.')+' Kategoriepflichten und Veröffentlichung noch ungeprüft.','note');
 const grid=document.createElement('div');grid.className='groupgrid';
 for(const [k,label,kind] of schemes[active].fields){
  const box=document.createElement('div');box.className='field';
  box.style.cssText='min-width:0;border:1px solid #d9e3ee;background:#fff;border-radius:9px';
  txt(box,'label',label);txt(box,'small',metadata[kind],'note');
  const input=['description','specifics','bullet1','bullet2'].includes(k)?document.createElement('textarea'):document.createElement('input');
  if(input.tagName==='TEXTAREA')input.rows=3;
  input.value=val(forms[active][k]);input.style.cssText='width:100%;box-sizing:border-box;margin-top:6px';
  input.oninput=()=>{forms[active][k]=input.value;origins[active][k]='Manuell eingegeben';};
  box.append(input);
  txt(box,'small',origins[active][k]||'Noch kein übernommener Vorschlag','note');
  const candidates=sourceCandidates(k).slice(0,5);
  if(candidates.length){
   const details=document.createElement('details');
   txt(details,'summary','Vorschläge ('+candidates.length+')');
   for(const c of candidates){
    const b=document.createElement('button');b.type='button';
    b.style.cssText='display:block;width:100%;text-align:left;white-space:normal;overflow-wrap:anywhere;background:#edf2fa;color:#173b7a;padding:6px;margin:3px 0';
    b.textContent=(c.value.length>110?c.value.slice(0,107)+'…':c.value)+' · '+c.src;
    b.onclick=()=>{forms[active][k]=c.value;origins[active][k]=c.src+' · als Vorschlag';paint();status('Vorschlag übernommen: '+label+'. Bitte prüfen und lokal speichern.')};
    details.append(b);
   }
   box.append(details);
  }
  grid.append(box);
 }
 form.append(grid);
 const actions=document.createElement('div');actions.style.margin='12px 0';
 function button(text,cb){const b=document.createElement('button');b.type='button';b.textContent=text;b.onclick=cb;actions.append(b)}
 button('✨ Vorschläge für diesen Kanal',suggest);
 button('💾 Kanal lokal speichern',save);
 button('📥 Entwurf als JSON',download);
 form.append(actions);
 txt(form,'p','Noch keine vollständige Amazon-/eBay-Kategorieprüfung, kein JTL-Schreibzugriff und kein zentrales Änderungsprotokoll. Entwürfe verbleiben vorerst lokal im Browser.','note');
}
function suggest(){
 let n=0,review=0;
 for(const [k,,kind] of schemes[active].fields){
  if(val(forms[active][k]))continue;
  const all=sourceCandidates(k);
  const good=all.find(c=>{
   if(['price','qty','asin','category_id','specifics','image','condition','shipping','gtin'].includes(k))return false;
   if(kind==='kategorie')return false;
   return c.kind==='jtl'||c.kind==='local'||(c.kind==='derived'&&k==='handle');
  });
  if(good){forms[active][k]=good.value;origins[active][k]=good.src+' · Entwurf';n++}
  else if(all.length)review++;
 }
 paint();status(n+' Vorschläge übernommen, '+review+' weitere Informationen nur nach Quellenprüfung. Bitte speichern.');
}
function save(){
 try{
  const stored=remembered();stored[active]={values:{...forms[active]},sources:{...origins[active]},savedAt:new Date().toISOString()};
  localStorage.setItem(key(),JSON.stringify(stored));paint();status('✓ '+schemes[active].name+' lokal gespeichert. Zum Sichern JSON herunterladen.');
 }catch(e){status('Browser-Speicherung fehlgeschlagen. Bitte JSON exportieren.')}
}
function download(){
 const blob=new Blob([JSON.stringify({schema:'hw24-channel-draft-v1',ean,exportedAt:new Date().toISOString(),jtl_status:jtl?.jtl_status||'unbekannt',verified_marketplace_requirements:false,channels:forms,sources:origins},null,2)],{type:'application/json'});
 const url=URL.createObjectURL(blob),a=document.createElement('a');
 a.href=url;a.download='HW24_Artikelentwurf_'+ean+'.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),3000);
 status('Entwurf als JSON zur Sicherung bereitgestellt. Noch nicht an JTL oder einen Marktplatz übertragen.');
}
window.hw24ChannelScan=(id,record)=>{
 ean=val(id);item=record||{};jtl=null;historical=null;active='amazon';
 forms={amazon:{},shopify:{},ebay:{}};origins={amazon:{},shopify:{},ebay:{}};
 const saved=remembered();for(const name of Object.keys(schemes)){
  if(saved?.[name]?.values)forms[name]={...saved[name].values};
  if(saved?.[name]?.sources)origins[name]={...saved[name].sources};
 }
 paint();status(Object.keys(saved).length?'Vorhandene lokale Entwürfe geladen.':'Neuer Entwurf. JTL- und historische Recherche laufen.');
};
window.hw24ChannelJtl=data=>{jtl=data||null;paint()};
window.hw24ChannelHistorical=data=>{historical=data||null;paint()};
})();