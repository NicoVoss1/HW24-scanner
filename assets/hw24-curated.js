/* HW24 approved article data overlay, v1.0. No automatic writes. */
(()=>{'use strict';
const $=id=>document.getElementById(id), text=(tag,t)=>{const x=document.createElement(tag);x.textContent=String(t);return x;};
const labels=['Artikelname','Hersteller','Kurzbeschreibung','Beschreibung','Warengruppe','Artikelgewicht','Versandgewicht','Brutto-VK','HAN'];
let seq=0,activeEan='',revision=0,record=null;
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
function head(title,subtitle){
 root.replaceChildren();
 const titleNode=text('h3','✅ '+title);titleNode.style.margin='0 0 5px';
 const info=text('p',subtitle);info.className='note';root.append(titleNode,info);
 return info;
}
function render(){
 const info=head(record?'Freigegebener Artikelstand':'Artikelstand dauerhaft pflegen',record?
  'Version '+revision+' · geprüft am '+(record.approved_at_utc||'unbekannt')+' UTC · aktuelle Freigabe hat Vorrang vor alten Excel-Recherchehinweisen.':
  'Hier kannst Du ausgewählte Stammdaten nach Deiner Prüfung dauerhaft freigeben. Keine direkte Änderung in JTL.');
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
 const title=text('summary','✏️ Felder prüfen und als aktuellen Datenstand freigeben');title.style.cssText='cursor:pointer;font-weight:700';
 edit.append(title);
 const explain=text('p','Setze die Häkchen ausschließlich bei den Angaben, die Du geprüft hast. Es werden nur ausgewählte Werte gespeichert. JTL, historische Excel-Zeilen und Amazon bleiben unverändert.');
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
  input.value=fieldValue(name)||record?.fields?.[name]||'';
  input.style.cssText='box-sizing:border-box;width:100%;max-width:100%;min-height:36px;padding:8px;border:1px solid #d3dce8;border-radius:6px;font:inherit';
  if(input.tagName==='TEXTAREA')input.rows=2;
  slot.append(heading,input);wrapper.append(check,slot);list.append(wrapper);inputs.push({name,check,input});
 }
 edit.append(list);
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
 const request=++seq;activeEan=String(ean||'');record=null;revision=0;
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