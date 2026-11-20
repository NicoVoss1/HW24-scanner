/* HW24 V4.22 · Shared safe field enrichment, catalog dedup and ASIN candidates.
 * Used by scanliste and main articlemanager. No JTL/WMS writes.
 */
(function(root){
'use strict';
const v=x=>String(x??'').trim();
const active=new Map(),recent=new Map();
const ttl={jtl:120000,curated:300000,history:300000,catalog:300000};
function validGtin(s){
 s=v(s);if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(s)||/^0+$/.test(s))return false;
 let n=0,w=3;for(let i=s.length-2;i>=0;i--){n+=Number(s[i])*w;w=w===3?1:3}
 return(10-n%10)%10===Number(s[s.length-1]);
}
async function get(type,ean){
 ean=v(ean);if(!validGtin(ean))throw Error('invalid_gtin');
 const key=type+':'+ean,entry=recent.get(key);
 if(entry&&Date.now()-entry.at<ttl[type])return entry.value;
 if(active.has(key))return active.get(key);
 const endpoints={
  jtl:'api/jtl.php?ean=',curated:'api/curated.php?ean=',
  history:'api/historie.php?include_legacy=1&ean=',catalog:'api/amazon.php?ean='};
 if(!endpoints[type])throw Error('unsupported_source');
 const promise=(async()=>{
  const resp=await fetch(endpoints[type]+encodeURIComponent(ean),{credentials:'same-origin',cache:'no-store'});
  let result;try{result=await resp.json()}catch(_){throw Error('invalid_source_response')}
  if(!resp.ok||result?.ok!==true)throw Error(result?.error||('HTTP '+resp.status));
  recent.set(key,{at:Date.now(),value:result});return result;
 })();
 active.set(key,promise);
 try{return await promise}finally{active.delete(key)}
}
const normalize=(s,max=500)=>v(s).replace(/\s+/g,' ').slice(0,max);
function positive(n){const x=Number(String(n??'').replace(',','.'));return Number.isFinite(x)&&x>0?x:null}
function amount(n,digits=3){return Number(n.toFixed(digits)).toString().replace('.',',')}
function sizeFromName(name){
 const s=normalize(name);if(!s)return null;
 const a=s.match(/(\d{1,2})\s*(?:x|×|er[\s-]*pack|er)\s*(\d+(?:[,.]\d+)?)\s*(ml|l|g|kg)\b/i);
 if(a)return{units:Number(a[1]),quantity:positive(a[2]),unit:a[3].toLowerCase(),estimated:true};
 const b=s.match(/(\d+(?:[,.]\d+)?)\s*(ml|l|g|kg)\b/i);
 return b?{units:1,quantity:positive(b[1]),unit:b[2].toLowerCase(),estimated:true}:null;
}
function likelyMultipack(name,item){
 const s=normalize(name).toLowerCase();
 if(/\b(?:2er|3er|4er|5er|6er|10er|12er)[\s-]*(?:pack|set|bundle)\b/.test(s))return true;
 if(/\b(?:set|bundle|multipack|doppelpack|mehrfachpack)\b/.test(s))return true;
 if(/\b[2-9]\s*(?:x|×)\s*\d/.test(s))return true;
 for(const qty of [item?.attributes?.number_of_items,item?.attributes?.item_package_quantity]){
  if(Array.isArray(qty)&&positive(qty[0]?.value)>1)return true;
 }
 return false;
}
function itemIdentifiers(item){
 const all=new Set();
 for(const group of item?.identifiers||[]){
  for(const id of group?.identifiers||[]){
   const type=v(id?.identifierType).toUpperCase();
   const number=v(id?.identifier);
   if(['EAN','UPC','GTIN'].includes(type)&&validGtin(number))all.add(number);
  }
 }
 return [...all];
}
function amazonChoices(catalog,ean){
 const list=[];
 const seen=new Set();
 for(const a of catalog?.items||[]){
  const asin=v(a?.asin).toUpperCase();if(!/^[A-Z0-9]{10}$/.test(asin)||seen.has(asin))continue;
  seen.add(asin);
  const title=normalize(a?.title,600);
  const ids=itemIdentifiers(a);
  const pack=likelyMultipack(title,a);
  list.push({asin,title,brand:normalize(a?.brand||a?.manufacturer,180),
   pack,identifiers:ids,exact:ids.includes(ean),source:catalog?.source||'amazon'});
 }
 return list;
}
function slug(name){
 const str=normalize(name,240).toLowerCase()
 .replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss')
 .normalize('NFKD').replace(/[\u0300-\u036f]/g,'');
 return str.replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,170).replace(/-+$/,'');
}
function fieldMap(){
 const fields={},sources={},flags={};
 function put(key,value,source,kind='source'){
  value=v(value);if(!value||fields[key])return;
  fields[key]=value;sources[key]=normalize(source,170);
  if(kind==='estimate')flags[key]='Kalkulationswert vor Import prüfen';
  if(kind==='rule')flags[key]='HW24-Regel vor Import prüfen';
 }
 return{fields,sources,flags,put};
}
function verifiedHistory(history){
 const fields={},sources={},candidates=new Map(),conflicts=new Set();
 const approved=history?.approved?.fields;
 if(approved&&typeof approved==='object'){
  for(const [key,raw] of Object.entries(approved)){
   if(!v(raw))continue;fields[key]=v(raw);sources[key]='HW24 · historisch geprüfter Artikelstand';
  }
 }
 const allowed=new Map([
  ['artikelname','Artikelname'],['name','Artikelname'],['citemname','Artikelname'],
  ['hersteller','Hersteller'],['marke','Hersteller'],['warengruppe','Warengruppe'],
  ['kurzbeschreibung','Kurzbeschreibung'],['beschreibung','Beschreibung'],
  ['han','HAN'],['kategorieebene1','Kategorie Ebene 1'],
  ['kategorieebene2','Kategorie Ebene 2'],['kategorieebene3','Kategorie Ebene 3'],
  ['asin','ASIN']
 ]);
 const asins=[];
 for(const row of history?.matches||[])for(const cell of row.cells||[]){
  if(!cell.header_verified)continue;
  const key=allowed.get(v(cell.label).toLocaleLowerCase('de').replace(/[\s_-]+/g,''));
  const value=v(cell.value);
  if(!key||!value)continue;
  if(key==='ASIN'){
   if(/^[A-Z0-9]{10}$/i.test(value)&&!asins.includes(value.toUpperCase()))asins.push(value.toUpperCase());
   continue;
  }
  if(!candidates.has(key))candidates.set(key,{
   value,source:'Historischer Excel-Beleg · '+v(row.sheet_name||'Quelle')+' / Zeile '+v(row.excel_row||'?')});
  else if(candidates.get(key).value.toLocaleLowerCase('de')!==value.toLocaleLowerCase('de'))conflicts.add(key);
 }
 for(const [key,x] of candidates)if(!conflicts.has(key)&&!fields[key]){
  fields[key]=x.value;sources[key]=x.source+' · vor Import prüfen';
 }
 return{fields,sources,asins};
}
function build({ean,match=null,curated=null,history=null,catalog=null,isNew=false}){
 ean=v(ean);const state=fieldMap();const {fields,sources,flags,put}=state;
 const snap=match?.jtl_source_fields||{};
 const accurate=match&&v(match.ean)===ean;
 const jtlName=accurate?normalize(match.artikelname):'';
 const approved=curated?.exists===true&&curated?.fields&&typeof curated.fields==='object'?curated.fields:{};
 if(accurate){
  const source='JTL · aktueller Spiegel';
  for(const [key,value] of Object.entries({
    Artikelnummer:match.artikelnummer,GTIN:match.ean,HAN:match.han,
    Artikelname:match.artikelname,Hersteller:match.hersteller,
    ASIN:match.asin,Warengruppe:match.warengruppe_name,
    Steuerklasse:match.steuerklasse_name,Kosten:match.kosten,
    'Netto-VK':match.netto_vk,Kurzbeschreibung:match.kurzbeschreibung,
    Beschreibung:match.beschreibung,'Titel-Tag (SEO)':match.seo_titel,
    'Meta-Description (SEO)':match.seo_beschreibung,
    Versandklasse:match.versandklasse_name,
    'Artikel aktiv':snap.cAktiv,'Bestandsführung aktiv':snap.cLagerAktiv,
    'Überverkäufe möglich':snap.cLagerKleinerNull,'Stückzahl teilbar':snap.cTeilbar,
    'Zusätzliche Bearbeitungszeit':snap.nBearbeitungszeit,
    'Beschaffungszeit automatisch ermitteln':snap.nAutomatischeLiefertageberechnung,
    'Beschaffungszeit manuell setzen':snap.nLiefertageWennAusverkauft,
    'Neu im Sortiment':snap.cNeu,'Top-Artikel':snap.cTopArtikel,
    Anmerkung:snap.cAnmerkung
  })){
    // JTL booleans may be encoded as 0/1. Never insert an unsupported value.
    if(['Artikel aktiv','Bestandsführung aktiv','Überverkäufe möglich',
      'Stückzahl teilbar','Neu im Sortiment','Top-Artikel'].includes(key)){
      const t=v(value).toUpperCase();
      if(!['Y','N','1','0'].includes(t))continue;
      put(key,(t==='1'||t==='Y')?'Y':'N',source);continue;
    }
    if(key==='Beschaffungszeit automatisch ermitteln'){
      if(['Y','1','N','0'].includes(v(value).toUpperCase()))
        put(key,['Y','1'].includes(v(value).toUpperCase())?'1':'0',source);
      continue;
    }
    put(key,value,source);
  }
  const related=match.related_sources||{};
  for(const [sourceName,labelKey,field] of [
    ['lieferanten','cName','Lieferant'],['lieferanten','cArtNr','Artikelnummer (Lieferant)'],
    ['kategorien','cName','Kategorie Ebene 1'],['lieferstatus','cName','Lieferstatus']]){
    const rows=related[sourceName]?.rows;
    if(Array.isArray(rows)&&rows.length===1&&!related[sourceName]?.truncated){
      put(field,rows[0]?.[labelKey],'JTL · geprüfte '+sourceName);
    }
  }
 }
 for(const [key,value] of Object.entries(approved))put(key,value,'HW24 · freigegebene Stammdaten');
 const historic=verifiedHistory(history);
 for(const [key,value] of Object.entries(historic.fields))put(key,value,historic.sources[key]);
 // JTL-derived / approved identity is stronger than Amazon catalog guesses.
 let chosen=null;
 const choices=amazonChoices(catalog,ean);
 const exactSingles=choices.filter(x=>x.exact&&!x.pack);
 if(exactSingles.length===1&&choices.length===1)chosen=exactSingles[0];
 if(chosen){
  put('ASIN',chosen.asin,'Amazon · eindeutige EAN / Einzelpack');
  put('Artikelname',chosen.title,'Amazon · eindeutige EAN / Einzelpack');
  put('Hersteller',chosen.brand,'Amazon · eindeutige EAN / Einzelpack');
 }
 if(validGtin(ean))put('GTIN',ean,'EAN · gescannt');
 const title=fields.Artikelname||jtlName;
 if(isNew){
  put('Artikelnummer',ean,'HW24 · vorläufige EAN-Artikelnummer','rule');
  put('HAN',ean,'HW24-Regel · HAN = GTIN falls keine echte HAN vorliegt','rule');
  put('Artikeltyp','Normaler Artikel','HW24 · Standard Haarfarben','rule');
  // Deliberate user-approved defaults, not data obtained from Amazon.
  if(/(?:color|colour|koleston|igora|illumina|haarfarb|tönung|färb|pastel|shinfinity|brillant)/i.test(title)){
    put('Warengruppe','Haarfarben','HW24 · Haarfarben-Zuordnung aus Produktname','rule');
    put('Kategorie Ebene 1','Haarfarben','HW24 · Kategorie-Vorschlag; JTL-Zuordnung prüfen','rule');
  }
  put('Steuerklasse','normaler steuersatz','HW24 · deutscher Regelsteuersatz; prüfen','rule');
  put('Währung','EUR','HW24 · Standardwährung','rule');
  put('Versandklasse','standard','HW24 · Versandprofil prüfen','rule');
  put('Artikel aktiv','Y','HW24 · Vorlage','rule');
  put('Bestandsführung aktiv','Y','HW24 · Vorlage','rule');
  put('Überverkäufe möglich','N','HW24 · Vorlage','rule');
  put('Stückzahl teilbar','N','HW24 · Vorlage','rule');
  put('Zusätzliche Bearbeitungszeit','1','HW24 · Tage-Vorgabe','rule');
  put('Beschaffungszeit automatisch ermitteln','0','HW24 · manuelle Beschaffungszeit','rule');
  // In JTL "manuell setzen" is a count of days, not a boolean.
  put('Beschaffungszeit manuell setzen','2','HW24 · vorläufig 2 Tage; Nutzerprüfung','rule');
  // This JTL field expects a *number of days*, not Ja/Nein.
  put('Neu im Sortiment','Y','HW24 · Vorlage','rule');
  put('Top-Artikel','N','HW24 · Vorlage','rule');
  const size=sizeFromName(title);
  if(size&&size.quantity&&size.units===1){
   const q=size.quantity;
   put('Inhalt/Menge',amount(q),'Produktname · Packungsinhalt prüfen','estimate');
   put('Maßeinheit',size.unit.toUpperCase(),'Produktname · Einheit prüfen','estimate');
   if(size.unit==='ml'){
    const kg=q/1000;
    put('Artikelgewicht',amount(kg,4),'HW24-Schätzung · 1 ml ≈ 1 g (Dichte prüfen)','estimate');
    put('Versandgewicht',amount(kg+0.02,4),'HW24-Schätzung · + 20 g Verpackung','estimate');
   }
  }
  if(title){
   put('Kurzbeschreibung',title,'HW24 · aus Produktname','rule');
   put('Titel-Tag (SEO)',(title+' | HAIRWORLD24').slice(0,250),'HW24 · SEO-Titel aus Stammdaten','rule');
   put('Meta-Description (SEO)',(title+'. Produktinformationen und Farbnuancen bei HAIRWORLD24.').slice(0,600),'HW24 · SEO-Vorschlag','rule');
   put('URL-Pfad',slug(title),'HW24 · sprechender URL-Vorschlag','rule');
  }
 }
 return{fields,sources,flags,choices,selected:chosen?.asin||null};
}
function renderCandidates(container,choices,selected,onSelect){
 if(!container)return;container.replaceChildren();
 if(!choices?.length)return;
 const heading=document.createElement('p');
 heading.textContent=choices.length===1?'Amazon-ASIN-Kandidat · Verpackung prüfen':'Amazon: '+choices.length+' verschiedene ASIN-Kandidaten · bitte auswählen';
 container.append(heading);
 for(const x of choices){
  const article=document.createElement('div');article.className='hw24-asin-choice';
  article.style.cssText='border:1px solid #cbdcea;background:#f5f9ff;border-radius:9px;padding:10px;margin:7px 0';
  const b=document.createElement('b');b.textContent=x.asin+' · '+(x.title||'Titel nicht vorhanden');article.append(b);
  const p=document.createElement('div');
  p.style.cssText='font-size:12px;color:#465e76;margin:6px 0';
  p.textContent='Hersteller: '+(x.brand||'unbekannt')+' · '+(x.pack?'Mehrfachpackung oder Set':'Packungsgröße prüfen')+
  ' · '+(x.exact?'EAN im Amazon-Treffer bestätigt':'EAN nicht als Kennung im Treffer belegt');
  article.append(p);
  const button=document.createElement('button');button.type='button';button.className='secondary';
  button.textContent=x.asin===selected?'✓ Auswahl': 'Nach Prüfung ASIN auswählen';
  button.disabled=x.asin===selected;
  button.onclick=()=>onSelect?.(x);
  article.append(button);container.append(article);
 }
}
root.HW24Enrich=Object.freeze({
 goodGtin:validGtin,sizeFromName,likelyMultipack,amazonChoices,verifiedHistory,build,renderCandidates,
 jtl:ean=>get('jtl',ean),curated:ean=>get('curated',ean),
 history:ean=>get('history',ean),catalog:ean=>get('catalog',ean)
});
})(window);
