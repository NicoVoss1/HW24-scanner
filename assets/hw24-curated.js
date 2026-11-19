/* HW24 approved article data overlay, v1.0. No automatic writes. */
(()=>{'use strict';
const $=id=>document.getElementById(id), text=(tag,t)=>{const x=document.createElement(tag);x.textContent=String(t);return x;};
const labels=['Artikelname','Hersteller','Kurzbeschreibung','Beschreibung','Warengruppe','Artikelgewicht','Versandgewicht','Brutto-VK','HAN'];
let seq=0,activeEan='',revision=0,record=null,jtlSuggestions={},amazonSuggestions={};
const root=document.createElement('section');
root.id='hw24CuratedPanel';root.className='f-section';root.style.cssText='margin:14px 0;padding:15px;border:1px solid #c5d5e8;border-radius:12px;background:#fff';
const parent=$('focusAmazonCard')?.closest('.f-section');
if(parent?.parentNode)parent.insertAdjacentElement('afterend',root);
else ($('scanCockpit')||document.body).append(root);
function fieldValue(k){
 const label=[...document.querySelectorAll('#fields .field label')].find(x=>x.textContent.trim()===k);
 return label?.closest('.field')?.querySelector('input,textarea')?.value?.trim()||'';
}
function valid(e){return /^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/.test(e);}
function suggestion(name){
 const saved=record?.fields?.[name];
 const jtl=jtlSuggestions[name];
 const local=fieldValue(name);
 const amazon=amazonSuggestions[name];
 if(jtl)return {value:jtl,source:'Aktueller JTL-Spiegel',trusted:true};
 if(local)return {value:local,source:'JTL-/CSV-Artikelformular',trusted:false};
 if(amazon)return {value:amazon,source:'Amazon-Katalog, Identität prüfen',trusted:false};
 if(saved)return {value:saved,source:'Bereits freigegebene Version '+revision,trusted:true};
 return {value:'',source:'Keine verfügbare Quelle',trusted:false};
}
function updateAfterSource(){
 if(!activeEan)return;
 if(root.querySelector('.hw24-curated-edit')){
  const wasOpen=!!root.querySelector('.hw24-curated-edit')?.open;
  render();const edit=root.querySelector('.hw24-curated-edit');if(edit)edit.open=wasOpen;
 }
}
document.addEventListener('hw24:jtl-result',event=>{
 const detail=event.detail||{};
 if(!activeEan||detail.ean!==activeEan)return;
 jtlSuggestions={};
 const data=detail.data;
 if(data?.jtl_status==='current_snapshot'&&data.matches?.length===1){
  const r=data.matches[0];
  const mapping={'Artikelname':r.artikelname,'Hersteller':r.hersteller,
   'Kurzbeschreibung':r.kurzbeschreibung,'Beschreibung':r.beschreibung,
   'Brutto-VK':r.brutto_vk,'HAN':r.han};
  for(const [k,v] of Object.entries(mapping))if(v!==undefined&&v!==null&&String(v).trim())jtlSuggestions[k]=String(v).trim();
 }
 updateAfterSource();
});
document.addEventListener('hw24:amazon-catalog',event=>{
 const detail=event.detail||{};
 if(!activeEan||detail.ean!==activeEan)return;
 amazonSuggestions={};
 if(detail.data?.count===1&&detail.data.items?.length===1){
  const item=detail.data.items[0];
  if(item.title)amazonSuggestions.Artikelname=String(item.title);
  if(item.brand||item.manufacturer)amazonSuggestions.Hersteller=String(item.brand||item.manufacturer);
 }
 updateAfterSource();
});

function head(title,subtitle){
 root.replaceChildren();
 const titleNode=text('h3','✅ '+title);titleNode.style.margin='0 0 5px';
 const info=text('p',subtitle);info.className='note';root.append(titleNode,info);
 return info;
}
function render(){
 const info=head(record?'Freigegebener Artikelstand':'Artikelvorschläge automatisch prüfen',record?
  'Version '+revision+' · geprüft am '+(record.approved_at_utc||'unbekannt')+' UTC · aktuelle Freigabe hat Vorrang vor alten Excel-Recherchehinweisen.':
  'Vorhandene JTL-/CSV-Felder und eindeutige Amazon-Katalogtreffer sind als Vorschläge vorausgefüllt. Du bestätigst nur geprüfte Angaben. Keine direkte Änderung in JTL.');
 if(!activeEan)return;
 if(record?.fields&&Object.keys(record.fields).length){
  const current=document.createElement('details');current.open=true;
  current.append(text('summary','Aktuell bestätigte Angaben ('+Object.keys(record.fields).length+')'));
  const grid=document.createElement('div');grid.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:7px;margin:9px 0';
  for(const [k,v] of Object.entries(record.fields)){
   const cell=document.createElement('div');cell.style.cssText='border:1px solid #dce6ef;background:#f5fbf7;border-radius:9px;padding:8px;min-width:0;overflow-wrap:anywhere';
   const small=text('small',k);small.style.display='block';cell.append(small,text('strong',v));grid.append(cell);
  }
  current.append(grid);root.append(current);
 }
 const edit=document.createElement('details');
 edit.className='hw24-curated-edit';edit.style.marginTop='10px';
 const title=text('summary','✅ Vorbelegte Werte prüfen und übernehmen');title.style.cssText='cursor:pointer;font-weight:700';
 edit.append(title);
 const explain=text('p','Vorschläge erscheinen automatisch aus den verfügbaren Quellen. Bei JTL/CSV-Konflikten prüfst Du die Identität. Nur angehakte Felder und die anschließende ausdrückliche Bestätigung werden gespeichert. JTL, Excel und Amazon bleiben unverändert.');
 explain.className='note';edit.append(explain);
 const list=document.createElement('div');list.style.cssText='display:grid;gap:7px';
 const inputs=[];
 for(const name of labels){
  const wrapper=document.createElement('label');wrapper.style.cssText='display:grid;grid-template-columns:24px minmax(110px,1fr);gap:8px;align-items:center;padding:9px;border:1px solid #dde6ee;border-radius:9px;background:#fafcfe';
  const check=document.createElement('input');check.type='checkbox';check.checked=false;
  const slot=document.createElement('span');
  const heading=text('strong',name);heading.style.display='block';
  const input=document.createElement(name==='Beschreibung'||name==='Kurzbeschreibung'?'textarea':'input');
  if(input.tagName==='INPUT')input.type='text';
  const suggested=suggestion(name);
  input.value=suggested.value;
  input.style.cssText='box-sizing:border-box;width:100%;max-width:100%;min-height:36px;padding:8px;border:1px solid #d3dce8;border-radius:6px;font:inherit';
  if(input.tagName==='TEXTAREA')input.rows=2;
  const provenance=text('small','Quelle: '+suggested.source);provenance.style.cssText='display:block;margin-top:4px;color:#59718c;font-size:11px';
  if(jtlSuggestions[name]&&amazonSuggestions[name]&&String(jtlSuggestions[name]).trim().toLocaleLowerCase('de')!==String(amazonSuggestions[name]).trim().toLocaleLowerCase('de')){
   const warning=text('small','⚠ JTL und Amazon widersprechen sich. Vor Freigabe prüfen.');warning.style.cssText='display:block;color:#a55b16';
   slot.append(heading,input,provenance,warning);
  }else slot.append(heading,input,provenance);
  wrapper.append(check,slot);list.append(wrapper);inputs.push({name,check,input});
 }
 edit.append(list);
 const choose=document.createElement('button');choose.type='button';choose.className='scan-secondary';
 choose.textContent='☑ Vorbelegte Vorschläge zur Prüfung markieren';
 choose.style.marginTop='9px';
 choose.onclick=()=>{let n=0;for(const item of inputs){if(item.input.value.trim()){item.check.checked=true;n++;}}
  choose.textContent=n+' Vorschläge markiert · Angaben kontrollieren und bestätigen';
 };
 edit.append(choose);
 const save=document.createElement('button');save.type='button';save.textContent='✅ Ausgewählte Felder verbindlich freigeben';
 save.style.marginTop='12px';
 const state=text('p','');state.setAttribute('role','status');
 save.onclick=async()=>{
  if(!activeEan||!valid(activeEan))return;
  const chosen=Object.fromEntries(inputs.filter(r=>r.check.checked&&r.input.value.trim()).map(r=>[r.name,r.input.value.trim()]));
  if(!Object.keys(chosen).length){state.textContent='Bitte mindestens ein geprüftes Feld auswählen.';return;}
  // The explicit browser confirm is a second deliberate approval for data writes.
  if(!confirm('Diese '+Object.keys(chosen).length+' geprüften Felder für EAN '+activeEan+' als neuen aktuellen Artikelstand speichern?'))return;
  save.disabled=true;state.textContent='Geprüfter Artikelstand wird gesichert …';
  const ean=activeEan,version=revision,request=seq;
  try{
   const res=await fetch('api/curated.php',{method:'POST',credentials:'same-origin',cache:'no-store',
    headers:{'Content-Type':'application/json','X-HW24-Approval':'confirmed','Accept':'application/json'},
    body:JSON.stringify({ean,fields:chosen,confirmed:true,expected_revision:version})});
   const data=await res.json();if(request!==seq)return;
   if(!res.ok||!data.ok){
    state.textContent=data.error==='revision_conflict'?'⚠ Dieser Artikel wurde zwischenzeitlich bearbeitet. Bitte erneut scannen.':'⚠ Speichern nicht möglich: '+(data.error||'HTTP '+res.status);return;
   }
   revision=data.revision;record=data;render();
  }catch(err){if(request===seq)state.textContent='⚠ Speicherschnittstelle nicht erreichbar.';}
  finally{if(request===seq)save.disabled=false;}
 };
 edit.append(save,state);root.append(edit);
}
window.hw24CuratedLookup=async ean=>{
 const request=++seq;activeEan=String(ean||'');record=null;revision=0;jtlSuggestions={};amazonSuggestions={};
 if(!valid(activeEan)){
  head('Freigegebener Artikelstand','Nach einer gültigen EAN wird hier der zuletzt geprüfte Datenstand angezeigt.');return;
 }
 const state=head('Freigegebener Artikelstand','Freigaben werden aus der Datenbank gelesen …');
 if(location.hostname.endsWith('.github.io')){state.textContent='GitHub Pages: Stammdatenpflege ausschließlich auf dem geschützten Plesk-Server.';return;}
 try{
  const res=await fetch('api/curated.php?ean='+encodeURIComponent(activeEan),{credentials:'same-origin',cache:'no-store'});
  const data=await res.json();if(request!==seq)return;
  if(!res.ok||!data.ok){state.textContent='Noch nicht eingerichtet: Freigabe-Archiv · '+(data.error||'HTTP '+res.status);return;}
  revision=data.revision||0;record=data.exists?data:null;render();
 }catch(err){if(request===seq)state.textContent='Freigabe-Archiv momentan nicht erreichbar. Alte Daten werden nicht verändert.';}
};
head('Freigegebener Artikelstand','Nach einer EAN-Prüfung siehst Du hier Deine bestätigten Stammdaten.');
})();