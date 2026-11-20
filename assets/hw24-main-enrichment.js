/* HW24 V4.22 · Reuse shared enrichment in the existing articlemanager/workdesk/warehouse.
 * Changes the existing browser-side draft only. Does not write into JTL/WMS or live marketplaces.
 */
(function(){
'use strict';
const E=window.HW24Enrich;if(!E)return;
const value=x=>String(x??'').trim(),seen=new Map();
const parent=document.getElementById('hw24FocusMain')||document.getElementById('hw24ScanWorkspace');
if(!parent)return;
const panel=document.createElement('section');
panel.id='hw24SharedAmazonChoices';
panel.style.cssText='padding:14px 18px;border:1px solid #cbdcea;background:#fff;border-radius:13px;margin:12px 0';
panel.hidden=true;
const title=document.createElement('h3');title.textContent='Amazon ASIN-Auswahl · Packungsgröße prüfen';
panel.append(title);
const info=document.createElement('div');panel.append(info);
parent.insertAdjacentElement('afterend',panel);
function source(){return window.hw24SourceState?.()||{}}
function render(){
 const s=source(),r=s.record;if(!r)return;
 window.hw24AmeiseCurrent?.(r);
 window.hw24ProfileCurrent?.(r);
 window.hw24ChannelCurrent?.(r);
 window.hw24QualityEdit?.(r);
 window.hw24WorkdeskRender?.();
 window.hw24TabsUpdate?.(r);
}
function addFields(model){
 const s=source(),r=s.record;if(!r||!model)return;
 const additions=[];
 for(const [key,proposed] of Object.entries(model.fields||{})){
  if(!value(proposed)||value(r[key])||window.hw24WasManuallyEdited?.(key))continue;
  // JTL ASIN and article number may already be locked; never replace identifiers.
  r[key]=proposed;
  window.hw24MarkRuleDerived?.(key,(model.sources?.[key]||'Quellen')+
    (model.flags?.[key]?' · '+model.flags[key]:''));
  const box=[...document.querySelectorAll('#fields .field')].find(x=>
    value(x.querySelector('label')?.textContent)===key);
  const input=box?.querySelector('input,textarea');
  if(input&&!input.readOnly&&input.dataset.userEdited!=='1'&&!value(input.value)){
    input.value=proposed;
  }
  additions.push(key);
 }
 if(additions.length)render();
}
function asinList(catalog,ean){
 const choices=E.amazonChoices(catalog,ean);
 info.replaceChildren();panel.hidden=!choices.length;
 if(!choices.length)return;
 const s=source(),r=s.record;if(!r)return;
 const existing=value(r.ASIN);
 E.renderCandidates(info,choices,existing,candidate=>{
  const current=source().record;
  if(!current||current!==r)return;
  if(value(current.ASIN)&&value(current.ASIN)!==candidate.asin){
    const p=document.createElement('p');p.className='note';
    p.textContent='Bereits vorhandene ASIN bitte gezielt in der Stammdatenpflege prüfen. Keine automatische Überschreibung.';
    info.append(p);return;
  }
  const description=candidate.pack?'mögliche Mehrfachpackung':'Einzelartikel oder unbekannte Packungsgröße';
  if(!confirm('ASIN '+candidate.asin+' auswählen ('+description+')? Farbnuance und Packungsinhalt zuvor prüfen.'))return;
  current.ASIN=candidate.asin;
  window.hw24MarkEdited?.('ASIN');
  render();
  asinList(catalog,ean);
 });
 const note=document.createElement('p');note.className='note';
 note.textContent='Mehrfachpackungen und Varianten dürfen nicht automatisch derselben JTL-Einzelartikelnummer zugeordnet werden.';
 info.append(note);
}
document.addEventListener('hw24:scan-start',()=>{
 panel.hidden=true;info.replaceChildren();
});
document.addEventListener('hw24:jtl-result',e=>{
 const q=value(e.detail?.ean),data=e.detail?.data||{},m=data.matches||[];
 const s=source(),r=s.record;if(!r)return;
 const ean=E.goodGtin(q)?q:value(m.length===1?m[0]?.ean:'');
 if(!E.goodGtin(ean))return;
 const isNew=data.jtl_status==='current_snapshot'&&m.length===0;
 const match=data.jtl_status==='current_snapshot'&&m.length===1?m[0]:null;
 if(!isNew&&!match)return;
 const model=E.build({ean,match,isNew});
 addFields(model);
 seen.set(ean,{jtl:data});
});
document.addEventListener('hw24:amazon-catalog',e=>{
 const ean=value(e.detail?.ean),data=e.detail?.data;
 if(!E.goodGtin(ean)||!data?.ok)return;
 const s=source(),r=s.record;if(!r)return;
 if(![value(r.GTIN),value(r.EAN),value(r.Suchbegriff)].includes(ean))return;
 const cached=seen.get(ean)||{};
 const jtl=cached.jtl||s.jtl;
 const matches=jtl?.matches||[];
 const isNew=jtl?.jtl_status==='current_snapshot'&&matches.length===0;
 const match=jtl?.jtl_status==='current_snapshot'&&matches.length===1?matches[0]:null;
 const model=E.build({ean,match,catalog:data,isNew});
 addFields(model);
 asinList(data,ean);
});
})();
