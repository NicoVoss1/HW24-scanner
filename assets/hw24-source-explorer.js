/*
 * HW24 JTL raw-source explorer. Read-only; no article edits or SQL writes.
 * All values are textContent, never interpreted as HTML.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id);
const core=window.HW24SourceCore;
if(!core||!$('jtlSourceExplorer'))return;
const state={seq:0,fields:[],prices:null,ean:'',articleId:''};
const node=(tag,parent,value,className)=>{
 const el=document.createElement(tag);
 if(value!==undefined&&value!==null)el.textContent=String(value);
 if(className)el.className=className;
 parent?.append(el);return el;
};
const status=text=>{$('jtlSourceStatus').textContent=text;};
function clear(){
 state.fields=[];state.prices=null;state.ean='';state.articleId='';
 $('channelPriceBoard').replaceChildren();
 $('jtlSourceFields').replaceChildren();
 $('jtlSourceCount').textContent='Noch keine Felder geladen.';
 const selector=$('jtlSourceGroup');
 selector.replaceChildren();
 const opt=node('option',selector,'Alle Quellen');opt.value='all';
 status('Artikel zuerst auswählen. Der Originalspiegel bleibt unverändert.');
}
function showPrices(){
 const root=$('channelPriceBoard');
 root.replaceChildren();
 for(const p of core.channelRows(state.prices||{})){
  const wrap=node('section',root,null,'source-channel');
  node('strong',wrap,p.channel+' · '+p.account);
  node('span',wrap,p.price,'source-channel-price');
  node('small',wrap,p.meaning+' · '+p.state);
  node('small',wrap,'Quelle: '+p.source);
  if(p.price!=='Nicht verfügbar')node('small',wrap,'Währung: '+p.currency);
 }
}
function showFields(){
 const root=$('jtlSourceFields');root.replaceChildren();
 const matches=core.filteredFields(state.fields,$('jtlSourceQuery').value,$('jtlSourceGroup').value);
 $('jtlSourceCount').textContent=matches.length+' von '+state.fields.length+' Quellfeldern angezeigt.';
 if(!matches.length){
  node('p',root,'Keine passenden Felder im aktuellen Snapshot.','muted');return;
 }
 const table=node('table',root,null,'source-table');
 const head=node('thead',table),hr=node('tr',head);
 ['Quelle','SQL-Feld','Originalwert'].forEach(t=>node('th',hr,t));
 const body=node('tbody',table);
 for(const f of matches){
  const tr=node('tr',body);
  const source=node('td',tr,null);
  node('span',source,f.source||'Unbekannt');
  if(f.row)node('small',source,' · Zeile '+f.row);
  const label=node('td',tr);
  node('code',label,f.field||'');
  if(f.label)node('small',label,' '+f.label);
  const value=node('td',tr);
  if(f.value===null||f.value===undefined){
   node('span',value,'Nicht belegt','source-empty');continue;
  }
  const text=String(f.value);
  if(text.length<=260){
   node('span',value,text||'(leer)');
  }else{
   node('span',value,text.slice(0,160)+'…');
   const detail=node('details',value);
   node('summary',detail,'Volltext des geladenen Feldes anzeigen');
   node('pre',detail,text);
  }
  if(f.truncated)node('small',value,' Vorschau technisch gekürzt; Originalwert bleibt im MariaDB-Spiegel.');
 }
}
function setGroups(){
 const select=$('jtlSourceGroup');select.replaceChildren();
 const first=node('option',select,'Alle Quellen');first.value='all';
 for(const name of core.availableGroups(state.fields)){
  const option=node('option',select,name);option.value=name;
 }
 select.value='all';
}
async function openArticle(article,snapshotAt){
 const token=++state.seq;
 clear();
 if(!article||!article.jtl_article_id||!article.ean)return;
 state.articleId=String(article.jtl_article_id);
 state.ean=String(article.ean);
 status('JTL-Quellfelder werden ausschließlich lesend geladen …');
 try{
  const params=new URLSearchParams({article_id:state.articleId,ean:state.ean});
  const response=await fetch('api/jtl-fields.php?'+params,{
   credentials:'same-origin',cache:'no-store'
  });
  const data=await response.json();
  if(token!==state.seq)return;
  if(!response.ok||!data.ok||data.status!=='current_snapshot')
   throw Error('Quellspiegel nicht aktuell oder nicht verfügbar ('+
     String(data.error||data.status||response.status)+').');
  if(String(data.article_id)!==state.articleId||String(data.ean)!==state.ean)
   throw Error('Artikelidentität im Quellenvergleich abweichend.');
  state.fields=Array.isArray(data.fields)?data.fields:[];
  state.prices=data.prices||{};
  setGroups();showPrices();showFields();
  const truncated=(data.source_summary||[]).filter(x=>x.truncated).map(x=>x.source);
  const time=String(data.snapshot_at||snapshotAt||'Zeit unbekannt');
  status('✓ '+state.fields.length+' Felder aus JTL-Snapshot '+time+
   ' UTC. Nur Daten aus bereits synchronisierten Quellen; keine Marktpreis-Liveabfrage.'+
   (truncated.length?' Einige Zusatzquellen wurden im Snapshot begrenzt: '+truncated.join(', '):''));
 }catch(error){
  if(token!==state.seq)return;
  clear();status('⚠ '+error.message);
 }
}
document.addEventListener('hw24:article-unloaded',()=>{++state.seq;clear();});
document.addEventListener('hw24:article-loaded',event=>{
 const detail=event.detail||{};
 openArticle(detail.article,detail.snapshotAt);
});
$('jtlSourceQuery').addEventListener('input',showFields);
$('jtlSourceGroup').addEventListener('change',showFields);
clear();
})();