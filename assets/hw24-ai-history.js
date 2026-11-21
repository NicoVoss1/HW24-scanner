/* HW24 AI review archive: wide, browsable history and explicit draft approvals.
 * A model result is NEVER an automatic JTL or WMS write.
 */
(function(root){
'use strict';
const v=x=>String(x??'').trim();
const money=x=>Number(x||0).toLocaleString('de-DE',{minimumFractionDigits:4,maximumFractionDigits:6});
const node=(tag,parent,content,style)=>{
 const e=document.createElement(tag);
 if(content!==undefined&&content!==null)e.textContent=String(content);
 if(style)e.style.cssText=style;
 parent?.append(e);return e;
};
const layer=node('div',document.body,null,
 'position:fixed;inset:0;z-index:9000;background:rgba(10,30,53,.60);display:none;align-items:center;justify-content:center;padding:14px');
layer.id='hw24AiHistory';
layer.setAttribute('role','presentation');
const panel=node('section',layer,null,
 'background:#fff;color:#17334e;width:min(1060px,98vw);max-height:92vh;overflow:auto;border-radius:15px;padding:22px;box-shadow:0 16px 55px #03152260;font:14px system-ui');
panel.setAttribute('role','dialog');
panel.setAttribute('aria-modal','true');
panel.setAttribute('aria-label','HW24 KI-Analysen und Prüfarchiv');
const top=node('div',panel,null,'display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap');
node('h2',top,'🤖 KI-Auswertungen · HW24-Prüfarchiv','margin:0;font-size:22px');
const close=node('button',top,'✕ Schließen','padding:8px 13px;border-radius:8px;background:#e6eef5;color:#153b5d;border:1px solid #c9d8e5;cursor:pointer');
const help=node('p',panel,'Alle gespeicherten KI-Prüfungen bleiben zur späteren Recherche erhalten. Vorschläge werden nur nach ausdrücklicher Zustimmung in einen neuen HW24-Artikelentwurf übernommen. Bestehende JTL-Artikel und Lagerbestände werden niemals direkt verändert.',
 'line-height:1.5;color:#526579');
const controls=node('div',panel,null,'display:flex;gap:9px;flex-wrap:wrap;align-items:center;margin-bottom:10px');
const allBtn=node('button',controls,'Alle letzten Prüfungen','padding:8px 12px');
const currentBtn=node('button',controls,'Aktuelle EAN','padding:8px 12px');
const reloadBtn=node('button',controls,'↻ Aktualisieren','padding:8px 12px');
const status=node('p',panel,'Lade KI-Prüfungen …','min-height:22px;color:#46647b');
status.setAttribute('role','status');
const layout=node('div',panel,null,'display:grid;grid-template-columns:minmax(240px,1fr) minmax(300px,1.6fr);gap:13px');
const list=node('div',layout,null,'max-height:56vh;overflow:auto;border:1px solid #dbe6ee;border-radius:9px;padding:8px');
const detail=node('div',layout,null,'min-height:230px;border:1px solid #dbe6ee;border-radius:9px;padding:16px;overflow:auto;max-height:62vh');
const css=node('style',panel);
css.textContent='@media(max-width:730px){#hw24AiHistory .hw24-ai-layout{grid-template-columns:1fr!important;max-height:none!important}#hw24AiHistory .hw24-ai-list{max-height:28vh!important}}';
layout.className='hw24-ai-layout';list.className='hw24-ai-list';
let items=[],onlyEan='',selectedId=null,lastFocus=null;
function activeEan(){
 const selected=root.HW24ScanBridge?.selected();
 return v(selected?.ean||root.hw24SourceState?.()?.record?.GTIN||document.getElementById('ean')?.value);
}
function canApply(review){
 const b=root.HW24ScanBridge,item=b?.selected();
 return Boolean(b&&item&&item.resolution==='new_candidate'&&item.review_state!=='removed'&&
  item.ean===review.ean);
}
function notify(text,error=false){status.textContent=text;status.style.color=error?'#9b2c38':'#416984'}
function button(parent,content,handler){
 const b=node('button',parent,content,'padding:7px 10px;margin:4px;border:1px solid #bfd2e2;border-radius:7px;background:#edf5fb;color:#163b5d;cursor:pointer');
 b.type='button';b.onclick=handler;return b;
}
function title(parent,t){node('h3',parent,t,'margin:12px 0 6px;font-size:16px')}
function text(parent,t){node('p',parent,t,'white-space:pre-wrap;line-height:1.5;margin:6px 0')}
async function applyField(review,field,value){
 if(!canApply(review)){
  notify('Zur Übernahme bitte denselben NEUEN Artikel in der Scanliste auswählen. JTL-Bestandsartikel bleiben geschützt.',true);
  return;
 }
 const b=root.HW24ScanBridge;
 if(!root.confirm('KI-Prüfung #'+review.id+': '+field+' = „'+value+'“ als geprüften HW24-ENTWURF übernehmen? JTL-Wawi wird nicht verändert. Bitte JTL-Kategorie/Packung vor Ameise-Import kontrollieren.'))return;
 try{
  const result=await b.apply({[field]:value},{[field]:'KI-Prüfung #'+review.id+' · Nutzerbestätigung, vor Ameise prüfen'},
   {save:true,confirmedChoice:true});
  if(result.saved)notify('✓ '+field+' im HW24-MariaDB-Entwurf gespeichert. Vor JTL-Ameise prüfen.');
  else notify('Nicht gespeichert: '+v(result.error||'Entwurf nicht aktualisiert'),true);
 }catch(e){notify('Übernahme fehlgeschlagen: '+v(e.message),true)}
}
function showDetail(review){
 selectedId=review.id;detail.replaceChildren();
 title(detail,'Prüfung #'+review.id+' · '+review.article_name);
 text(detail,'EAN '+review.ean+' · '+review.created_at_utc+' UTC · Modell '+review.model);
 text(detail,'Ergebnis: '+v(review.review?.status||review.status)+
  ' · Kosten '+money(review.usd_cost)+' USD · Tokens: '+review.tokens?.input+
  ' Eingabe, '+review.tokens?.cached+' gecacht, '+review.tokens?.output+' Ausgabe');
 title(detail,'KI-Begründung');
 text(detail,v(review.review?.reason)||'Keine Begründung gespeichert.');
 title(detail,'Warengruppe');
 const suggested=v(review.review?.suggestedWarengruppe);
 text(detail,suggested?'Vorschlag: '+suggested:'Kein eindeutiger Vorschlag');
 if(suggested&&canApply(review))button(detail,'✓ Warengruppe nach Prüfung in Entwurf übernehmen',
  ()=>applyField(review,'Warengruppe',suggested));
 const facts=review.review?.productFacts;
 if(facts&&typeof facts==='object'){
  title(detail,'Erkannte Produktmerkmale (KI-Vorschläge)');
  const labels={manufacturer:'Hersteller',shade:'Farbcode',volume:'Inhalt',unit:'Einheit',
   packUnits:'Packungsmenge',articleType:'Artikeltyp',suggestedTitle:'Artikelname',notes:'Offene Prüfpunkte'};
  for(const [key,name] of Object.entries(labels)){
   const value=facts[key];
   if(value===null||value===undefined||v(value)==='')continue;
   text(detail,name+': '+v(value));
  }
 }
 title(detail,'ASIN-Einzelprüfungen');
 const checks=Array.isArray(review.review?.asinChecks)?review.review.asinChecks:[];
 const candidates=Array.isArray(review.input?.amazon_candidates)?review.input.amazon_candidates:[];
 if(!checks.length)text(detail,'Keine ASIN-Einzelprüfungen enthalten.');
 for(const c of checks){
  const row=node('div',detail,null,'border-bottom:1px solid #e4ecf4;padding:9px 0');
  const asin=v(c.asin).toUpperCase();
  const match=candidates.find(i=>v(i.asin).toUpperCase()===asin);
  text(row,asin+' · '+v(c.status)+'\n'+(match?.title||'')+'\n'+v(c.reason));
  if(/^[A-Z0-9]{10}$/.test(asin)){
   const link=node('a',row,'Amazon.de · ASIN '+asin+' prüfen','color:#1a649b;display:inline-block;margin:7px');
   link.href='https://www.amazon.de/dp/'+asin;link.target='_blank';link.rel='noopener noreferrer';
   if(c.status==='pass'&&match&&canApply(review))
    button(row,'ASIN nach Amazon-Prüfung in Entwurf übernehmen',
      ()=>applyField(review,'ASIN',asin));
  }
 }
 title(detail,'Eingabedaten dieser Prüfung');
 const source=review.input?.article||{};
 const table=node('div',detail,null,'background:#f2f6fa;padding:12px;border-radius:9px');
 for(const [key,value] of Object.entries(source)){
  if(!v(value))continue;
  text(table,key+': '+v(value));
 }
 const foot=node('p',detail,'Diese KI-Auswertung dokumentiert einen Vorschlag. Sie ist keine Bestätigung des Herstellers und kein automatischer Wawi-Import.',
  'color:#6c5669;font-size:12px;line-height:1.5');
 foot.setAttribute('role','note');
 renderList();
}
function renderList(){
 list.replaceChildren();
 if(!items.length){text(list,'Noch keine KI-Prüfungen für diesen Filter gespeichert.');return}
 for(const item of items){
  const b=node('button',list,null,
   'display:block;width:100%;text-align:left;padding:11px;margin-bottom:7px;border-radius:8px;cursor:pointer;border:1px solid '+
   (item.id===selectedId?'#2476b8':'#d5e4ef')+';background:'+(item.id===selectedId?'#e3f0fc':'#f8fbfe')+';color:#143e60');
  b.type='button';b.onclick=()=>showDetail(item);
  node('b',b,item.article_name,'display:block;white-space:normal');
  node('small',b,item.ean+' · '+item.created_at_utc+' UTC · '+money(item.usd_cost)+' USD',
   'display:block;margin-top:5px;line-height:1.4');
 }
}
async function load(preferredId){
 notify('Gespeicherte KI-Ergebnisse werden aus MariaDB geladen …');
 const query=new URLSearchParams({limit:'30'});
 if(onlyEan)query.set('ean',onlyEan);
 try{
  const res=await fetch('api/ai-reviews.php?'+query.toString(),
   {credentials:'same-origin',cache:'no-store'});
  const data=await res.json();
  if(!res.ok||!data.ok)throw Error(data.error||'Abfrage fehlgeschlagen');
  items=Array.isArray(data.reviews)?data.reviews:[];
  notify(items.length+' gespeicherte KI-Analysen'+(onlyEan?' für EAN '+onlyEan:'')+
   ' · neueste zuerst. Anzeige ohne erneute KI-Kosten.');
  renderList();
  const chosen=items.find(x=>x.id===preferredId)||items.find(x=>x.id===selectedId)||items[0];
  if(chosen)showDetail(chosen);
  else{selectedId=null;detail.replaceChildren();text(detail,'Noch keine dauerhaften KI-Ergebnisse.')}
 }catch(e){items=[];list.replaceChildren();detail.replaceChildren();
  notify('KI-Archiv nicht erreichbar: '+v(e.message)+
   '. Einmalige V13-Tabelleninstallation und Leserechte erforderlich.',true)}
}
function open({ean='',id=null}={}){
 lastFocus=document.activeElement;onlyEan=ean;layer.style.display='flex';
 load(id);close.focus();
}
function closeModal(){layer.style.display='none';lastFocus?.focus?.()}
close.onclick=closeModal;
layer.addEventListener('click',e=>{if(e.target===layer)closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&layer.style.display==='flex')closeModal()});
allBtn.onclick=()=>{onlyEan='';selectedId=null;load()};
currentBtn.onclick=()=>{onlyEan=activeEan();selectedId=null;load()};
reloadBtn.onclick=()=>load(selectedId);
root.HW24AiHistory=Object.freeze({
 open,refresh:()=>load(selectedId),
 afterSaved(data,article){
  if(data?.review_id)open({ean:article?.ean||'',id:Number(data.review_id)});
 }
});
})(window);
