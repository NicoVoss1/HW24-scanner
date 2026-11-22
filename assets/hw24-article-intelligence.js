/* HW24 V4.25: authenticated article-side Amazon/KI archive and safe Ameise draft export.
 * Amazon is manual and server-cached, AI archives load freely, chargeable requests
 * need explicit opt-in. Suggestions NEVER auto-write to JTL/WMS/Shopify.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id);
const val=v=>String(v??'').trim();
const state={article:null,amazon:[],amazonBusy:false,aiBusy:false,aiLatest:null,
             ean:'',seq:0,ameise:null,ameiseChecks:[]};
function el(tag,parent,text,cls){
 const n=document.createElement(tag);
 if(text!==undefined&&text!==null)n.textContent=String(text);
 if(cls)n.className=cls;
 parent?.append(n);return n;
}
function message(id,text){const n=$(id);if(n)n.textContent=text;}
function field(key){
 return document.getElementById('field_'+key.replace(/[^a-zA-Z0-9]/g,'_'));
}
function present(key){return val(field(key)?.value)}
function validEan(x){
 if(!/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(x)||/^0+$/.test(x))return false;
 let sum=0,weight=3;
 for(let i=x.length-2;i>=0;i--){sum+=Number(x[i])*weight;weight=weight===3?1:3}
 return (10-sum%10)%10===Number(x.at(-1));
}
function available(){return state.article&&state.ean===val(state.article.ean)&&validEan(state.ean)}
function apiError(data,status){
 const key=val(data?.error||status||'unbekannt');
 const stage=val(data?.stage),upstream=data?.upstream_status;
 const hints={
  private_config_missing:'Amazon-Credentials oder private DB-Konfiguration fehlt.',
  site_authentication_required:'Plesk hat die Anmeldung nicht an PHP weitergereicht.',
  amazon_catalog_request_failed:'Die Amazon-SP-API hat die Katalogabfrage abgelehnt.',
  amazon_lookup_or_storage_unavailable:'Amazon-Lookup bzw. MariaDB-Archivierung derzeit nicht verfügbar.',
  review_storage_unavailable:'Gespeicherte KI-Auswertungen konnten nicht geladen werden.',
  daily_request_limit:'Das Tageslimit für die OpenAI-Kostenkontrolle ist erreicht.',
  monthly_budget_limit:'Das konfigurierte monatliche OpenAI-Kostenbudget ist erreicht.',
  review_recheck_conflict:'Ein anderer Vorgang hat den KI-Archivbericht bereits verändert.'
 };
 return (hints[key]||key)+(stage?' Phase: '+stage+'.':'')+
  (upstream?' Amazon HTTP '+upstream+'.':'')+
  ' Es wurde keine automatische Wiederholung gestartet.';
}
function propose(key,proposed,origin){
 const input=field(key),next=val(proposed);
 if(!input||input.disabled||!next||!available())return;
 const old=val(input.value);
 if(old===next)return;
 if(!window.confirm(key+' aus '+origin+' als HW24-VORSCHLAG einsetzen?\n'+
   'Vorher: '+(old||'leer')+'\nVorschlag: '+next+
   '\n\nDie Änderung wird nur in der Maske vorgemerkt. JTL und Shopify bleiben unverändert.'))return;
 input.value=next;input.dispatchEvent(new Event('input',{bubbles:true}));
 message('editorStatus',key+' wurde als ungespeicherter HW24-Vorschlag übernommen. Bitte vor Freigabe prüfen.');
}
function reset(){
 ++state.seq;state.article=null;state.ean='';state.amazon=[];
 state.aiLatest=null;state.ameise=null;state.ameiseChecks=[];
 for(const id of ['amazonResults','aiReviewResults','ameiseDraftFields'])$(id)?.replaceChildren();
 message('amazonState','Für diesen Artikel ist noch keine Amazon-Katalogabfrage gestartet.');
 message('aiReviewState','KI-Archiv wird nach der Artikelauswahl kostenfrei geprüft.');
 message('ameiseDraftState','Nur bestätigte Änderungen des aktuell gewählten JTL-Artikels exportieren.');
 $('exportAmeiseDraft').disabled=true;$('aiRecheck').hidden=true;
}
document.addEventListener('hw24:article-unloaded',reset);
document.addEventListener('hw24:article-loaded',ev=>{
 reset();state.article=ev.detail?.article||null;state.ean=val(state.article?.ean);
 if(!available())return;
 const seq=state.seq;
 readAiArchive(seq);
});
document.addEventListener('hw24:article-curated-saved',()=>{
 state.ameise=null;state.ameiseChecks=[];
 $('exportAmeiseDraft').disabled=true;$('ameiseDraftFields').replaceChildren();
 message('ameiseDraftState','HW24-Entwurf wurde geändert. Bitte freigegebene Felder neu laden.');
});
async function amazonLookup(){
 if(!available()||state.amazonBusy)return;
 const ean=state.ean,seq=state.seq;
 state.amazonBusy=true;$('amazonSearch').disabled=true;
 $('amazonResults').replaceChildren();
 message('amazonState','Amazon.de Catalog Items wird für '+ean+' abgefragt …');
 try{
  const response=await fetch('api/amazon.php?ean='+encodeURIComponent(ean),
   {credentials:'same-origin',cache:'no-store'});
  const data=await response.json();
  if(seq!==state.seq)return;
  if(!response.ok||!data.ok)throw Error(apiError(data,response.status));
  state.amazon=Array.isArray(data.items)?data.items:[];
  message('amazonState',state.amazon.length+' Amazon-Katalogtreffer · '+(data.source==='cache'?
   'aus dem bestehenden Amazon-Cache (kein neuer SP-API-Abruf)':
   'Live-API-Abruf, serverseitig gespeichert')+' · '+(data.fetched_at_utc||'ohne Datumsangabe')+
    (data.has_next_page?' · weitere Treffer vorhanden':''));
  const root=$('amazonResults');
  if(!state.amazon.length)el('p',root,'Keine passenden Amazon-Katalogeinträge zurückgegeben.','muted');
  for(const item of state.amazon.slice(0,12)){
   if(!item||typeof item!=='object')continue;
   const box=el('div',root,null,'intel-result');
   const id=val(item.asin).toUpperCase();
   el('strong',box,val(item.title)||'Ohne Amazon-Produkttitel');
   el('div',box,'ASIN '+(id||'nicht vorhanden')+' · Marke '+(val(item.brand)||'unbekannt'),'muted');
   if(val(item.manufacturer))el('div',box,'Hersteller: '+item.manufacturer,'muted');
   if(/^[A-Z0-9]{10}$/.test(id)){
    const a=el('a',box,'Amazon.de-Produkt öffnen','detail-link');
    a.href='https://www.amazon.de/dp/'+id;a.target='_blank';a.rel='noopener noreferrer';
   }
   const info=el('p',box,'Katalogdaten sind Vorschläge. EAN, Farbton, Volumen und Einzel-/Mehrfachpackung vor Zuordnung kontrollieren.','muted');
   info.style.margin='8px 0';
   const actions=el('div',box,null,'intel-actions');
   for(const [key,value] of [['ASIN',id],['Hersteller',item.brand||item.manufacturer],
      ['Artikelname',item.title]]){
    if(!val(value))continue;
    const btn=el('button',actions,'Vorschlag: '+key,'secondary');
    btn.type='button';btn.addEventListener('click',()=>propose(key,value,'Amazon-Katalog ASIN '+id));
   }
  }
 }catch(err){
  if(seq===state.seq)message('amazonState','⚠ '+err.message);
 }finally{
  state.amazonBusy=false;$('amazonSearch').disabled=false;
 }
}
$('amazonSearch').addEventListener('click',amazonLookup);
function paintAi(review){
 const root=$('aiReviewResults');root.replaceChildren();
 if(!review)return;
 const body=review.review||{};
 const facts=body.productFacts||{};
 const box=el('div',root,null,'intel-result');
 el('strong',box,'KI-Archiv #'+review.id+' · '+(body.status||review.status||'unbekannt'));
 if(review.created_at_utc)el('p',box,'Gespeichert: '+review.created_at_utc+' UTC · Modell: '+(review.model||'unbekannt'),'muted');
 if(body.reason)el('p',box,body.reason,'source');
 const proposals=[
  ['Hersteller',facts.manufacturer],
  ['Artikelname',facts.suggestedTitle],
  ['Warengruppe',body.suggestedWarengruppe]
 ];
 const actions=el('div',box,null,'intel-actions');
 for(const [key,value] of proposals){
  if(!val(value))continue;
  const btn=el('button',actions,'KI-Vorschlag: '+key,'secondary');
  btn.type='button';btn.addEventListener('click',()=>propose(key,value,'KI-Prüfarchiv #'+review.id));
 }
 if(!val(body.reason))el('span',box,'Keine inhaltliche Begründung im gespeicherten Bericht.','muted');
}
async function readAiArchive(seq=state.seq){
 if(!available())return null;
 const ean=state.ean;
 try{
  const response=await fetch('api/ai-reviews.php?ean='+encodeURIComponent(ean)+'&limit=1',
   {credentials:'same-origin',cache:'no-store'});
  const data=await response.json();
  if(seq!==state.seq)return null;
  if(!response.ok||!data.ok)throw Error(apiError(data,response.status));
  const review=Array.isArray(data.reviews)?data.reviews[0]||null:null;
  state.aiLatest=review;
  $('aiRecheck').hidden=!review;
  if(review){
   message('aiReviewState','✓ Gespeicherte KI-Prüfung #'+review.id+
    ' gefunden. Keine OpenAI-Kosten für den Archivabruf. Neue Prüfung nur bewusst über „Neue KI-Prüfung ($)“.');
   paintAi(review);
  }else{
   message('aiReviewState','Noch kein gespeicherter KI-Bericht für diese EAN vorhanden. Eine kostenpflichtige Erstprüfung ist optional.');
   $('aiReviewResults').replaceChildren();
  }
  return review;
 }catch(err){
  if(seq===state.seq)message('aiReviewState','⚠ KI-Archiv nicht verfügbar: '+err.message+' Kein kostenpflichtiger Aufruf gestartet.');
  throw err;
 }
}
async function performAiRecheck(force=false){
 if(!available()||state.aiBusy)return;
 const seq=state.seq,ean=state.ean;
 state.aiBusy=true;$('aiReviewRun').disabled=true;$('aiRecheck').disabled=true;
 try{
  const previous=await readAiArchive(seq);
  if(seq!==state.seq)return;
  if(previous&&!force){
   message('aiReviewState','✓ Bereits geprüft. Archiv #'+previous.id+' kostenlos angezeigt. Für eine neue kostenpflichtige Prüfung „Neue KI-Prüfung ($)“ wählen.');
   return;
  }
  if(force&&!previous){
   message('aiReviewState','Kein Archivbericht vorhanden. Bitte Erstprüfung wählen.');return;
  }
  const readinessResponse=await fetch('api/ai-review.php',{credentials:'same-origin',cache:'no-store'});
  const ready=await readinessResponse.json();
  if(!readinessResponse.ok||!ready.ok||!ready.enabled||!ready.archive_ready)
   throw Error(apiError(ready,readinessResponse.status));
  const prompt=(force?
   'Für diese EAN liegt bereits ein KI-Bericht #'+previous.id+' vor. Erneute kostenpflichtige KI-Analyse ausdrücklich starten?':
   'Diesen vorhandenen JTL-Artikel kostenpflichtig mit OpenAI analysieren und den Bericht in MariaDB speichern?')+
   '\nBudgetanzeige: '+Number(ready.usd_month||0).toFixed(4)+' / '+Number(ready.usd_budget||0).toFixed(2)+' USD im laufenden Monat.';
  if(!window.confirm(prompt))return;
  const article=state.article;
  const oneAmazon=state.amazon.length===1?state.amazon[0]:null;
  const cand=oneAmazon&&/^[A-Z0-9]{10}$/.test(val(oneAmazon.asin))?
   [{asin:oneAmazon.asin,title:val(oneAmazon.title),brand:val(oneAmazon.brand),
     packKind:'unknown',eanConfirmed:false}]:[];
  const payload={ean,articleName:present('Artikelname')||val(article.artikelname),
   brand:present('Hersteller')||val(article.hersteller),
   warengruppe:present('Warengruppe')||val(article.warengruppe_name),
   category:'',unit:present('Maßeinheit'),jtl_status:'current_snapshot',candidates:cand};
  const headers={'Content-Type':'application/json','X-HW24-Approval':'confirmed'};
  if(force){headers['X-HW24-Recheck']='confirmed';headers['X-HW24-Previous-Review']=String(previous.id);}
  message('aiReviewState',force?'Bestätigte kostenpflichtige Neubewertung läuft …':'Bestätigte einmalige KI-Analyse läuft …');
  const response=await fetch('api/ai-review.php',{method:'POST',headers,credentials:'same-origin',
   cache:'no-store',body:JSON.stringify(payload)});
  const data=await response.json();
  if(seq!==state.seq)return;
  if(!response.ok||!data.ok)throw Error(apiError(data,response.status));
  if(data.reused)message('aiReviewState','♻ Vorhandener Bericht #'+data.review_id+' wiederverwendet, 0 USD neue Kosten.');
  else if(!data.stored)throw Error('KI-Abfrage wurde nicht als dauerhaft gespeichert bestätigt.');
  else message('aiReviewState','✓ Bericht #'+data.review_id+' dauerhaft gespeichert · geschätzter OpenAI-Aufruf '+data.usd_call+' USD.');
  await readAiArchive(seq);
 }catch(err){
  if(seq===state.seq)message('aiReviewState','⚠ '+err.message+' Keine automatische Wiederholung; Kosten-/Archivstatus separat prüfen.');
 }finally{
  state.aiBusy=false;$('aiReviewRun').disabled=false;$('aiRecheck').disabled=false;
 }
}
$('aiReviewRun').addEventListener('click',()=>performAiRecheck(false));
$('aiRecheck').addEventListener('click',()=>performAiRecheck(true));
const selectedFields=[
 'Artikelname','Hersteller','HAN','Warengruppe','Kurzbeschreibung','Beschreibung',
 'Kosten','Netto-VK','Brutto-VK','Steuerklasse','Inhalt/Menge','Maßeinheit'
];
// Explicitly exclude delivery time/class/category and article type until their
// verified JTL-Ameise mapping is tested. Never import stock, EK GLD or seller IDs.
function csv(x){
 const cleaned=String(x??'').replace(/\r\n?|\n/g,' ');
 return '"'+cleaned.replace(/"/g,'""')+'"';
}
function amount(x){
 const raw=val(x).replace(/\s/g,'');
 if(!/^\d{1,10}(?:[.,]\d{1,4})?$/.test(raw))return null;
 const n=Number(raw.replace(',','.'));
 return Number.isFinite(n)&&n>0?n.toFixed(2).replace('.',','):null;
}
async function loadAmeise(){
 if(!available())return;
 const seq=state.seq,ean=state.ean;
 $('exportAmeiseDraft').disabled=true;$('ameiseDraftFields').replaceChildren();
 try{
  const res=await fetch('api/curated.php?ean='+encodeURIComponent(ean),
   {credentials:'same-origin',cache:'no-store'});
  const data=await res.json();
  if(seq!==state.seq)return;
  if(!res.ok||!data.ok||!data.exists)throw Error('Keine bestätigten HW24-Stammdaten vorhanden.');
  state.ameise={revision:data.revision,fields:data.fields,ean};
  state.ameiseChecks=[];
  for(const key of selectedFields){
   const v=val(data.fields?.[key]);if(!v)continue;
   const label=el('label',$('ameiseDraftFields'),null,'intel-check');
   const input=el('input',label);input.type='checkbox';input.checked=false;
   el('span',label,key+': '+v.slice(0,110)+(v.length>110?' …':''));
   state.ameiseChecks.push({key,value:v,input});
  }
  if(!state.ameiseChecks.length)throw Error('Keine für die erste sichere Ameise-Vorlage geeigneten, bestätigten Felder.');
  $('exportAmeiseDraft').disabled=false;
  message('ameiseDraftState','HW24-Entwurf Revision '+data.revision+' · '+state.ameiseChecks.length+
    ' zuordenbare Felder. Bitte gewünschte Felder einzeln auswählen. Keine Live-Buchung.');
 }catch(e){if(seq===state.seq)message('ameiseDraftState','⚠ '+e.message)}
}
$('loadAmeiseDraft').addEventListener('click',loadAmeise);
$('exportAmeiseDraft').addEventListener('click',async()=>{
 const staged=state.ameise,ean=state.ean,article=state.article,seq=state.seq;
 if(!available()||!staged||staged.ean!==ean)return;
 const chosen=state.ameiseChecks.filter(x=>x.input.checked);
 if(!chosen.length){message('ameiseDraftState','Bitte mindestens ein Feld auswählen.');return}
 if(!val(article.artikelnummer)){message('ameiseDraftState','JTL-Artikelnummer fehlt. Kein Export möglich.');return}
 for(const c of chosen){
  if(['Kosten','Netto-VK','Brutto-VK'].includes(c.key)&&amount(c.value)===null){
   message('ameiseDraftState','Ungültiger Preis in '+c.key+'. Export gesperrt.');return
  }
  if(/^[=+\-@\t\r\n]/.test(c.value)){
   message('ameiseDraftState','Zellinhalt für '+c.key+' beginnt mit einem potentiell unsicheren Tabellenkalkulationszeichen. Export gesperrt.');return
  }
 }
 try{
  const [currentResponse,approvalResponse]=await Promise.all([
   fetch('api/jtl.php?q='+encodeURIComponent(article.artikelnummer),{credentials:'same-origin',cache:'no-store'}),
   fetch('api/curated.php?ean='+encodeURIComponent(ean),{credentials:'same-origin',cache:'no-store'})
  ]);
  const jtl=await currentResponse.json(),approved=await approvalResponse.json();
  if(seq!==state.seq)return;
  if(!currentResponse.ok||!jtl.ok||jtl.jtl_status!=='current_snapshot'||jtl.limited||
     !Array.isArray(jtl.matches)||jtl.matches.length!==1||
     val(jtl.matches[0].ean)!==ean||val(jtl.matches[0].artikelnummer)!==val(article.artikelnummer))
    throw Error('JTL-Artikelidentität oder Snapshot inzwischen unklar. Erneut öffnen.');
  if(!approvalResponse.ok||!approved.ok||!approved.exists||
     approved.revision!==staged.revision||
     chosen.some(c=>approved.fields?.[c.key]!==c.value))
    throw Error('HW24-Entwurf wurde zwischenzeitlich geändert. Bitte neu laden.');
  if(!window.confirm('JTL-Ameise TEST-CSV für vorhandenen Artikel '+article.artikelnummer+' mit '+chosen.length+
   ' ausdrücklich ausgewählten Feld(er)n erstellen? Importart später „Nur vorhandene aktualisieren“. JTL wird jetzt NICHT geändert.'))return;
  const keys=['Artikelnummer',...chosen.map(x=>x.key)];
  const values=[article.artikelnummer,...chosen.map(x=>['Kosten','Netto-VK','Brutto-VK'].includes(x.key)?
   amount(x.value):x.value)];
  const output='\uFEFF'+keys.map(csv).join(';')+'\r\n'+values.map(csv).join(';')+'\r\n';
  const blob=new Blob([output],{type:'text/csv;charset=utf-8'});
  const url=URL.createObjectURL(blob),link=document.createElement('a');
  link.href=url;link.download='HW24_Ameise_NUR_BESTAETIGTE_'+
   String(article.artikelnummer).replace(/[^a-zA-Z0-9_-]/g,'_')+'_TEST.csv';
  document.body.append(link);link.click();link.remove();URL.revokeObjectURL(url);
  message('ameiseDraftState','✓ Prüfdatei erzeugt. JTL noch NICHT aktualisiert. In JTL-Ameise „Nur vorhandene aktualisieren“ und Feldzuordnung sowie Testlauf vor echtem Import prüfen.');
 }catch(e){if(seq===state.seq)message('ameiseDraftState','⚠ '+e.message+' Keine Datei erzeugt.');}
});
})();
