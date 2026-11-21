/* HW24 V4.23 Intelligence · quality center.
 * All findings from JTL mirror; no writes, no paid AI calls, no local storage.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id);
const state={page:1,perPage:25,issue:'all',severity:'all',q:'',seq:0,controller:null,ready:false};
const fmt=new Intl.NumberFormat('de-DE');
const amount=new Intl.NumberFormat('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2});
const get=x=>String(x??'').trim();
function el(tag,parent,text,className){
 const n=document.createElement(tag);
 if(text!==undefined&&text!==null)n.textContent=String(text);
 if(className)n.className=className;
 if(parent)parent.append(n);return n;
}
function issueInfo(code,catalog){
 const c=catalog?.[code];
 return c&&typeof c.label==='string'?c:{label:code,severity:'warning',hint:'Manuell prüfen'};
}
function setLoading(){
 $('resultSummary').textContent='Aktueller JTL-Spiegel wird ausgewertet …';
 $('resultRows').replaceChildren();
 const tr=el('tr',$('resultRows'));
 const cell=el('td',tr,'Qualitätsdaten werden gelesen …','empty');cell.colSpan=4;
}
function showError(message){
 $('sourceStatus').textContent='⚠ JTL-Prüfung derzeit nicht verfügbar';
 const alert=$('snapshotWarning');alert.hidden=false;alert.className='banner error';alert.textContent=message;
 $('resultRows').replaceChildren();
 const tr=el('tr',$('resultRows'));const td=el('td',tr,'Keine verlässliche Gesamtauswertung vorhanden.','empty');td.colSpan=4;
 $('resultSummary').textContent='Keine belastbaren Zahlen verfügbar';
 ['kpiTotal','kpiWarnings','kpiCritical','kpiEk','kpiManufacturer'].forEach(id=>$(id).textContent='–');
 $('prevPage').disabled=true;$('nextPage').disabled=true;
}
function showSnapshot(data){
 state.ready=true;
 const at=get(data.snapshot_at_utc);
 const age=Number(data.age_minutes);
 const current=data.snapshot_status==='fresh';
 $('sourceStatus').textContent=
   (current?'✓ Vollständiger JTL-Snapshot':'⚠ JTL-Snapshot veraltet')+
   (at?' · '+at+' UTC':'')+(Number.isFinite(age)?' · '+age+' Minuten alt':'');
 const banner=$('snapshotWarning');
 if(current){banner.hidden=true;return;}
 banner.hidden=false;banner.className='banner warning';
 banner.textContent='Die JTL-Synchronisation ist älter als 45 Minuten. Die Zahlen sind eine historische Momentaufnahme, kein aktueller Lager-/Lieferstatus. Bitte die bestehende Windows-JTL-Synchronisation prüfen; keine pauschalen Datenänderungen ableiten.';
}
function fillIssueFilter(catalog){
 const select=$('issueFilter');
 if(select.options.length>1)return;
 for(const [code,info] of Object.entries(catalog||{})){
  const opt=el('option',select,info.label);opt.value=code;
 }
 select.value=state.issue;
}
function updateKpis(summary){
 const n=key=>Number(summary?.[key]||0);
 $('kpiTotal').textContent=fmt.format(n('total'));
 $('kpiWarnings').textContent=fmt.format(n('with_issues'));
 $('kpiCritical').textContent=fmt.format(n('critical_articles'));
 $('kpiEk').textContent=fmt.format(n('stock_without_ek'));
 $('kpiManufacturer').textContent=fmt.format(Number(summary?.issue_counts?.manufacturer_missing||0));
}
function humanNumber(value){
 if(value===null||value===undefined||value==='')return 'Nicht belegt';
 const n=Number(value);
 return Number.isFinite(n)?fmt.format(n):'Unbekannt';
}
function price(value){
 if(value===null||value===undefined||value==='')return 'Nicht belegt';
 const n=Number(value);
 return Number.isFinite(n)?amount.format(n)+' €':'Nicht lesbar';
}
function identifierUrl(row){
 const ean=get(row.ean);
 if(/^\d{8,14}$/.test(ean))return 'index.html?ean='+encodeURIComponent(ean);
 const number=get(row.artikelnummer);
 if(/^[\p{L}\p{N}][\p{L}\p{N}._\/-]{1,63}$/u.test(number))
  return 'index.html?q='+encodeURIComponent(number);
 return null;
}
function renderItems(data){
 const rows=$('resultRows');rows.replaceChildren();
 const articles=Array.isArray(data.items)?data.items:[];
 const catalog=data.rule_catalog||{};
 if(!articles.length){
  const tr=el('tr',rows);const td=el('td',tr,'Keine Artikel für diesen Filter gefunden.','empty');td.colSpan=4;
  return;
 }
 for(const a of articles){
  const tr=el('tr',rows);
  const identity=el('td',tr);identity.dataset.label='Artikel';
  el('div',identity,get(a.artikelname)||'(Artikelname fehlt)','article-name');
  el('span',identity,'JTL '+(get(a.artikelnummer)||get(a.jtl_article_id)||'unbekannt')+
   ' · EAN '+(get(a.ean)||'fehlt')+' · HAN '+(get(a.han)||'fehlt'),'muted');
  el('span',identity,'Hersteller: '+(get(a.hersteller)||'nicht belegt'),'muted');
  const cost=el('td',tr);cost.dataset.label='Bestand / Einkaufspreis';
  el('div',cost,'Bestand: '+humanNumber(a.bestand),'money');
  el('span',cost,'EK netto: '+price(a.netto_ek)+' · letzter EK: '+price(a.letzter_ek),'muted money');
  if(get(a.warengruppe))el('span',cost,'Warengruppe: '+a.warengruppe,'muted');
  const issues=el('td',tr);issues.dataset.label='Prüfhinweise';
  if(!a.issues?.length)el('span',issues,'✓ Keine Warnung in diesen Regeln','flag clean');
  else for(const code of a.issues){
   const info=issueInfo(code,catalog);
   const f=el('span',issues,info.label,'flag '+(info.severity==='critical'?'critical':'warning'));
   if(info.hint)f.title=info.hint;
  }
  const actions=el('td',tr);actions.dataset.label='Aktion';
  const url=identifierUrl(a);
  if(url){
   const link=el('a',actions,'Artikel prüfen','detail-link');
   link.href=url;
   link.title='Vorhandene JTL-Daten im Artikelmanager prüfen, keine direkte Wawi-Änderung';
  }else el('span',actions,'Keine eindeutige Suchkennung','muted');
 }
}
async function load(){
 const request=++state.seq;
 state.controller?.abort();
 const controller=new AbortController();
 state.controller=controller;
 setLoading();
 const params=new URLSearchParams({
  q:state.q,issue:state.issue,severity:state.severity,
  page:String(state.page),per_page:String(state.perPage)
 });
 try{
  const response=await fetch('api/jtl-quality.php?'+params.toString(),{
   credentials:'same-origin',cache:'no-store',signal:controller.signal});
  const data=await response.json();
  if(request!==state.seq)return;
  if(!response.ok||!data.ok||!data.summary)
   throw Error(data.note||data.error||'Der JTL-Spiegel ist noch nicht lesbar');
  fillIssueFilter(data.rule_catalog);
  updateKpis(data.summary);
  showSnapshot(data);
  renderItems(data);
  const total=Number(data.total_filtered||0);
  const page=Number(data.page||1),pages=Number(data.total_pages||1);
  $('resultSummary').textContent=fmt.format(total)+' passende Artikel · '+
   fmt.format(Number(data.summary.total))+' im vollständigen JTL-Spiegel · '+
   (data.snapshot_status==='fresh'?'aktuell':'historischer Stand');
  $('pageIndicator').textContent='Seite '+page+' von '+pages;
  $('prevPage').disabled=page<=1;
  $('nextPage').disabled=page>=pages;
 }catch(error){
  if(error.name==='AbortError'||request!==state.seq)return;
  showError('Die Qualitätsprüfung konnte nicht abgeschlossen werden: '+
    error.message+'. Keine Daten wurden verändert.');
 }
}
function apply(next){
 Object.assign(state,next,{page:1});
 load();
}
let searchTimer=null;
$('filters').addEventListener('submit',event=>event.preventDefault());
$('search').addEventListener('input',()=>{
 clearTimeout(searchTimer);
 searchTimer=setTimeout(()=>apply({q:$('search').value.trim()}),320);
});
$('issueFilter').addEventListener('change',()=>apply({issue:$('issueFilter').value}));
$('severityFilter').addEventListener('change',()=>apply({severity:$('severityFilter').value}));
$('pageSize').addEventListener('change',()=>apply({perPage:Number($('pageSize').value)}));
$('reload').addEventListener('click',load);
function setFilters(issue,severity){
 state.issue=issue;state.severity=severity;state.page=1;
 $('issueFilter').value=issue;$('severityFilter').value=severity;
 load();
}
$('showAll').onclick=()=>{
 $('search').value='';state.q='';setFilters('all','all');
};
$('showProblems').onclick=()=>setFilters('all','with_issues');
$('showCritical').onclick=()=>setFilters('all','critical');
$('showMissingEk').onclick=()=>setFilters('stock_without_ek','all');
$('showMissingManufacturer').onclick=()=>setFilters('manufacturer_missing','all');
$('prevPage').onclick=()=>{if(state.page>1){state.page--;load()}};
$('nextPage').onclick=()=>{state.page++;load()};
$('prevPage').disabled=true;$('nextPage').disabled=true;
const url=new URLSearchParams(location.search);
const initial=url.get('q');
if(initial&&initial.length<=100){state.q=initial;$('search').value=initial}
load();
})();
