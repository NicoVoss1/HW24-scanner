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
// Unit-count evidence is separate from bottle volume (60 ml ≠ 60 bottles).
// "Single" is never inferred from the absence of a multi-pack keyword.
function classifyPack(name,item={}){
 const title=normalize(name,700).toLocaleLowerCase('de').replace(/×/g,'x');
 const signals=[],quantities=new Set();
 function add(q,origin){
  q=Number(q);if(Number.isSafeInteger(q)&&q>=1&&q<=100){
   quantities.add(q);signals.push(origin+': '+q+' Stück');
  }
 }
 for(const match of title.matchAll(/(?:^|[\s,;(\[])(\d{1,2})\s*x\s*\d+(?:[,.]\d+)?\s*(?:ml|l|g|kg)\b/g))
   add(match[1],'Titel: Packmenge vor Volumen');
 for(const match of title.matchAll(/(?:^|[\s,;(\[])(\d{1,2})\s*(?:er[\s-]*(?:pack|paket|set)|[- ]?pack|[- ]?stk\.?|[- ]?st[üu]ck|[- ]?flaschen|[- ]?tuben)\b/g))
   add(match[1],'Titel: Packungsangabe');
 for(const match of title.matchAll(/\bpack(?:ungsgr[oö]ße)?\s*(?:mit|of)?\s*(\d{1,2})\b/g))
   add(match[1],'Titel: Pack');
 if(/\b(?:doppelpack|zweierpack|duopack|twin[\s-]*pack|2[\s-]?er[\s-]?set)\b/.test(title))add(2,'Titel: Doppelpack');
 if(/\b(?:dreierpack|triple[\s-]*pack|3[\s-]?er[\s-]?set)\b/.test(title))add(3,'Titel: Dreierpack');
 if(/\b(?:einzelpack|einzelst[üu]ck|single[\s-]?pack|single[\s-]?unit)\b/.test(title))add(1,'Titel: Einzelstück');
 for(const key of ['number_of_items','item_package_quantity','package_quantity']){
  const entries=item?.attributes?.[key];
  if(Array.isArray(entries))for(const entry of entries) add(entry?.value,'Amazon-Attribut '+key);
 }
 const multi=[...quantities].filter(q=>q>1);
 const single=quantities.has(1);
 const conflict=(single&&multi.length>0)||multi.length>1;
 const kind=conflict?'conflict':multi.length?'multipack':single?'single':'unknown';
 return {kind,units:conflict||kind==='unknown'?null:kind==='single'?1:multi[0],
  reasons:signals.length?signals:['Keine ausdrückliche Stückzahl in Titel oder Amazon-Attributen']};
}
function likelyMultipack(name,item){const c=classifyPack(name,item);return c.kind==='multipack'||c.kind==='conflict'}
function shadeOf(title){
 const s=normalize(title,700).replace(/[.]/g,'/');
 const m=s.match(/(?:^|[\s(\[])(([0-9]{1,2})\s*\/\s*([0-9]{1,3}))(?!\d)/);
 return m?m[2]+'/'+m[3]:null;
}
function brandKey(text){
 const s=normalize(text,200).toLocaleLowerCase('de');
 for(const brand of ['wella','schwarzkopf','matrix','goldwell','inebrya','keune','l’oréal',"l'oreal",'hair haus']){
  if(s.includes(brand))return brand.replace(/['’]/g,'');
 }
 return null;
}
function reviewCandidate(candidate,reference={}){
 const reasons=[],checks=[];
 const identity=normalize(reference.articleName||reference.Artikelname||'',600);
 const brand=normalize(reference.brand||reference.Hersteller||'',200);
 const expectedPack=Number(reference.packUnits||1);
 if(candidate.packKind==='multipack'&&expectedPack===1)
   reasons.push('Mehrfachpackung statt erwartetem Einzelartikel ('+candidate.packUnits+' Stück)');
 if(candidate.packKind==='conflict')reasons.push('Amazon-Titel und Packungsattribute widersprechen sich');
 if(candidate.packKind==='unknown')checks.push('Stückzahl nicht bestätigt: Amazon-Detailseite prüfen');
 if(!candidate.exact)checks.push('GTIN nicht als Kennung im Amazon-Treffer bestätigt');
 const a=brandKey(brand)||brandKey(identity),b=brandKey(candidate.brand)||brandKey(candidate.title);
 if(a&&b&&a!==b)reasons.push('Marke weicht vom Stammartikel ab');
 else if(a&&b)checks.push('Marke stimmt überein');
 const x=shadeOf(identity),y=shadeOf(candidate.title);
 if(x&&y&&x!==y)reasons.push('Farbcode weicht ab: '+x+' ≠ '+y);
 else if(x&&y)checks.push('Farbcode '+x+' stimmt überein');
 const local=sizeFromName(identity),remote=sizeFromName(candidate.title);
 if(local&&remote&&local.unit===remote.unit&&local.quantity!==remote.quantity)
   reasons.push('Einzelgebinde-Größe weicht ab: '+local.quantity+local.unit+' ≠ '+remote.quantity+remote.unit);
 else if(local&&remote&&local.unit===remote.unit)checks.push('Gebindegröße stimmt überein');
 const status=reasons.length?'mismatch':
  candidate.packKind==='single'&&candidate.exact&&(!x||!y||x===y)&&(!a||!b||a===b)?'compatible':'review';
 return {status,reasons,checks};
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
  const packInfo=classifyPack(title,a);
  list.push({asin,title,brand:normalize(a?.brand||a?.manufacturer,180),
   pack,packKind:packInfo.kind,packUnits:packInfo.units,packReasons:packInfo.reasons,
   identifiers:ids,exact:ids.includes(ean),source:catalog?.source||'amazon',
   url:'https://www.amazon.de/dp/'+asin});
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
 // Amazon may omit the pack count in the title; never silently assume 1 piece.
 const exactSingles=choices.filter(x=>x.exact&&x.packKind==='single'&&
  reviewCandidate(x,{articleName:fields.Artikelname,brand:fields.Hersteller}).status==='compatible');
 if(exactSingles.length===1&&choices.length===1)chosen=exactSingles[0];
 if(chosen){
  put('ASIN',chosen.asin,'Amazon · eindeutige EAN / Einzelpack');
  put('Artikelname',chosen.title,'Amazon · eindeutige EAN / Einzelpack');
  put('Hersteller',chosen.brand,'Amazon · eindeutige EAN / Einzelpack');
 }
 if(validGtin(ean))put('GTIN',ean,'EAN · gescannt');
 if((isNew||accurate)&&validGtin(ean))put('HAN',ean,
  'HW24-Regel · HAN=GTIN nur als Vorschlag, echte Hersteller-HAN hat Vorrang','rule');
 const title=fields.Artikelname||jtlName;
 const hairColor=/(?:color|colour|koleston|igora|illumina|haarfarb|tönung|färb|pastel|shinfinity|brillant)/i.test(title);
 if(title&&(isNew||accurate)){
  // Manufacturer is often readable in the verified title even if the JTL mirror
  // currently contains only its numeric kHersteller ID.
  const knownBrands=['Inebrya','Wella','Schwarzkopf','Matrix','Goldwell','Keune','L’Oréal','L\'Oréal','Hair Haus'];
  const brand=knownBrands.find(b=>title.toLocaleLowerCase('de').startsWith(b.toLocaleLowerCase('de')+' '));
  if(brand)put('Hersteller',brand,'HW24 · bekannter Markenname aus JTL-/Produktname; prüfen','rule');
  if(hairColor){
   put('Steuerklasse','normaler steuersatz','HW24 · Steuervorschlag für Haarfarben; bitte JTL prüfen','rule');
   put('Artikeltyp','Normaler Artikel','HW24 · Standardtyp-Vorschlag; bitte JTL prüfen','rule');
   put('Warengruppe','Haarfarben','HW24 · Haarfarben-Vorschlag aus Artikelname','rule');
   put('Kategorie Ebene 1','Haarfarben','HW24 · Kategorie-Vorschlag; JTL-Kategorie prüfen','rule');
  }
  const size=sizeFromName(title);
  if(size&&size.quantity&&size.units===1){
   put('Inhalt/Menge',amount(size.quantity),'Produktname · Inhalt prüfen','estimate');
   put('Maßeinheit',size.unit,'Produktname · Einheit prüfen','estimate');
   if(size.unit==='ml'){
    const kg=size.quantity/1000;
    put('Artikelgewicht',amount(kg,4),'HW24-Schätzung: 1 ml ≈ 1 g; tatsächliches Gewicht prüfen','estimate');
    put('Versandgewicht',amount(kg+0.02,4),'HW24-Schätzung: Artikelgewicht + 20 g; Verpackung prüfen','estimate');
   }
  }
 }
 if(isNew){
  // Intentionally leave Artikelnummer blank for JTL-Ameise global auto numbering.
  // Do not guess a free SKU from the read-only mirror or use GTIN as internal SKU.
  put('HAN',ean,'HW24-Regel · HAN = GTIN falls keine echte HAN vorliegt','rule');
  const isCarton=/\b(?:karton|kartonage|versandkarton|faltkarton)\b/i.test(title);
  put('Artikeltyp',isCarton?'Kartonage':'Normaler Artikel',
    isCarton?'HW24 · Kartonage aus Bezeichnung; prüfen':'HW24 · Standard Haarfarben','rule');
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

  if(title){
   put('Kurzbeschreibung',title,'HW24 · aus Produktname','rule');
   put('Titel-Tag (SEO)',(title+' | HAIRWORLD24').slice(0,250),'HW24 · SEO-Titel aus Stammdaten','rule');
   put('Meta-Description (SEO)',(title+'. Produktinformationen und Farbnuancen bei HAIRWORLD24.').slice(0,600),'HW24 · SEO-Vorschlag','rule');
   put('URL-Pfad',slug(title),'HW24 · sprechender URL-Vorschlag','rule');
  }
 }
 // Only derive gross selling price from a documented net price and tax class.
 if(!fields['Brutto-VK']&&fields['Netto-VK']&&v(fields.Steuerklasse).toLocaleLowerCase('de')==='normaler steuersatz'){
   const net=positive(fields['Netto-VK']);
   if(net!==null)put('Brutto-VK',amount(net*1.19,2),
      'HW24-Rechnung aus Netto-VK und bestätigtem Normalsteuersatz','estimate');
 }
 if(hairColor&&/(?:hair\s*styling|haarstyling)/i.test(fields.Warengruppe||'')){
   flags.Warengruppe='Konflikt: Artikelname deutet auf Haarfarbe, Quelle meldet Styling. JTL-Warengruppe prüfen, nicht stillschweigend überschreiben.';
 }
 return{fields,sources,flags,choices,selected:chosen?.asin||null};
}
function renderCandidates(container,choices,selected,onSelect,reference={}){
 if(!container)return;container.replaceChildren();
 if(!choices?.length)return;
 const heading=document.createElement('p');
 heading.textContent=choices.length===1?'Amazon-ASIN-Kandidat · Stückzahl prüfen':
   'Amazon: '+choices.length+' verschiedene ASIN-Kandidaten · bitte prüfen';
 container.append(heading);
 for(const x of choices){
  const article=document.createElement('div');article.className='hw24-asin-choice';
  article.style.cssText='border:1px solid #cbdcea;background:#f5f9ff;border-radius:9px;padding:10px;margin:7px 0';
  const verdict=reviewCandidate(x,reference);
  const label=document.createElement('b');
  label.textContent=x.asin+' · '+(x.title||'Amazon-Titel nicht vorhanden');article.append(label);
  const p=document.createElement('div');
  p.style.cssText='font-size:12px;color:#465e76;margin:6px 0';
  const packText=x.packKind==='single'?'Einzelartikel ausdrücklich belegt':
    x.packKind==='multipack'?('Mehrfachpackung: '+x.packUnits+' Stück'):
    x.packKind==='conflict'?'Widersprüchliche Angaben zur Packung':'Stückzahl unbekannt';
  const state=verdict.status==='mismatch'?'⚠ Nicht passend':
    verdict.status==='compatible'?'✓ Plausibler Einzelartikel':'Prüfen';
  p.textContent=state+' · '+packText+' · Hersteller: '+(x.brand||'unbekannt')+
    ' · '+(x.exact?'EAN als Amazon-Kennung belegt':'EAN nicht im Treffer belegt');
  article.append(p);
  const reasons=verdict.reasons.length?verdict.reasons:
     x.packReasons||[];
  if(reasons.length){
   const detail=document.createElement('small');
   detail.style.cssText='display:block;color:#634f36;margin:5px 0';
   detail.textContent='Prüfung: '+reasons.join(' · ').slice(0,520);
   article.append(detail);
  }
  // Construct URL only from the validated 10-character ASIN. No user-controlled URL.
  if(/^[A-Z0-9]{10}$/.test(x.asin)){
   const link=document.createElement('a');
   link.href='https://www.amazon.de/dp/'+x.asin;
   link.target='_blank';link.rel='noopener noreferrer';
   link.textContent='Auf Amazon.de prüfen (ASIN '+x.asin+')';
   link.style.cssText='display:inline-block;margin:6px 12px 8px 0;color:#135b98;font-weight:600';
   article.append(link);
  }
  const button=document.createElement('button');
  button.type='button';button.className='secondary';
  button.textContent=x.asin===selected?'✓ Auswahl':verdict.status==='mismatch'?'Nach Warnung manuell prüfen und auswählen':'Nach Prüfung ASIN auswählen';
  button.disabled=x.asin===selected;
  button.onclick=()=>onSelect?.(x);
  article.append(button);container.append(article);
 }
}
root.HW24Enrich=Object.freeze({
 goodGtin:validGtin,sizeFromName,likelyMultipack,classifyPack,reviewCandidate,amazonChoices,verifiedHistory,build,renderCandidates,
 jtl:ean=>get('jtl',ean),curated:ean=>get('curated',ean),
 history:ean=>get('history',ean),catalog:ean=>get('catalog',ean)
});
})(window);
