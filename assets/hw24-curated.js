/* HW24 approved article data overlay, v1.0. No automatic writes. */
(()=>{'use strict';
const $=id=>document.getElementById(id), text=(tag,t)=>{const x=document.createElement(tag);x.textContent=String(t);return x;};
const labels=['Artikelname','Hersteller','Warengruppe','Steuerklasse','Inhalt/Menge','Maßeinheit','Inhalt','Inhaltseinheit',
 'Kosten','Lieferant','Artikelnummer (Lieferant)','Brutto-VK','Netto-VK','Artikelgewicht','Versandgewicht',
 'Kurzbeschreibung','Beschreibung','Kategorie Ebene 1','Kategorie Ebene 2','Kategorie Ebene 3',
 'Versandklasse','Währung','Artikeltyp','ASIN','HAN'];
let active={ean:'',revision:0,record:null,ready:false};
let jtlSuggestions={},amazonSuggestions={},saveChain=Promise.resolve();
const root=document.createElement('section');
root.id='hw24CuratedPanel';root.className='f-section';root.style.cssText='margin:14px 0;padding:15px;border:1px solid #c5d5e8;border-radius:12px;background:#fff';
const parent=$('focusAmazonCard')?.closest('.f-section');
if(parent?.parentNode)parent.insertAdjacentElement('afterend',root);
else ($('scanCockpit')||document.body).append(root);
function fieldValue(k){
 const label=[...document.querySelectorAll('#fields .field label')].find(x=>x.textContent.trim()===k);
 return label?.closest('.field')?.querySelector('input,textarea')?.value?.trim()||'';
}
function valid(e){return (typeof window.hw24BatchGtinValid==='function'?window.hw24BatchGtinValid(e):/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/.test(e));}
function suggestion(name){
 const {record,revision}=active;
 const saved=record?.fields?.[name];
 const jtl=jtlSuggestions[name];
 const local=fieldValue(name);
 const amazon=amazonSuggestions[name];
 if(saved!==undefined && saved!==null && String(saved).trim())return {value:String(saved),source:'Dauerhaft freigegeben · Version '+revision,trusted:true};
 if(jtl)return {value:jtl,source:'Aktueller JTL-Spiegel',trusted:true};
 if(local)return {value:local,source:'JTL-/CSV-Artikelformular',trusted:false};
 if(amazon)return {value:amazon,source:'Amazon-Katalog, Identität prüfen',trusted:false};
 return {value:'',source:'Keine verfügbare Quelle',trusted:false};
}
function updateAfterSource(){
 if(!active.ean)return;
 if(root.querySelector('.hw24-curated-edit')){
  const wasOpen=!!root.querySelector('.hw24-curated-edit')?.open;
  render();const edit=root.querySelector('.hw24-curated-edit');if(edit)edit.open=wasOpen;
 }
}
document.addEventListener('hw24:jtl-result',event=>{
 const detail=event.detail||{};
 if(!active.ean||detail.ean!==active.ean)return;
 jtlSuggestions={};
 const data=detail.data;
 if(data?.jtl_status==='current_snapshot'&&data.matches?.length===1){
  const r=data.matches[0];
  const mapping={'Artikelname':r.artikelname,'Hersteller':r.hersteller,
   'Kurzbeschreibung':r.kurzbeschreibung,'Beschreibung':r.beschreibung,
   'Brutto-VK':r.brutto_vk,'Netto-VK':r.netto_vk,'HAN':r.han,
   'Kosten':r.kosten,'Warengruppe':r.warengruppe_name,'Steuerklasse':r.steuerklasse_name,
   'Inhalt/Menge':r.inhalt_menge,'Maßeinheit':r.masseinheit,
   'Artikelgewicht':r.artikelgewicht,'Versandgewicht':r.versandgewicht,
   'Lieferant':r.lieferant,'Artikelnummer (Lieferant)':r.lieferant_artikelnummer};
  for(const [k,v] of Object.entries(mapping))if(v!==undefined&&v!==null&&String(v).trim())jtlSuggestions[k]=String(v).trim();
 }
 updateAfterSource();
 if(active.record?.fields)window.hw24ApplyApprovedArticle?.(active.ean,active.record.fields);
});
document.addEventListener('hw24:amazon-catalog',event=>{
 const detail=event.detail||{};
 if(!active.ean||detail.ean!==active.ean)return;
 amazonSuggestions={};
 if(detail.data?.count===1&&detail.data.items?.length===1){
  const item=detail.data.items[0];
  if(item.title)amazonSuggestions.Artikelname=String(item.title);
  if(item.brand||item.manufacturer)amazonSuggestions.Hersteller=String(item.brand||item.manufacturer);
  const attributes=item.attributes||{};
  const unit=Array.isArray(attributes.unit_count)?attributes.unit_count[0]:null;
  const size=Array.isArray(attributes.size)?attributes.size[0]:null;
  const sizeText=String(size?.value??'').trim();
  // Factual package quantity is only a candidate. Do not reuse Amazon prose.
  if(unit?.value!==undefined&&Number.isFinite(Number(unit.value))&&Number(unit.value)>0)
   amazonSuggestions['Inhalt/Menge']=String(unit.value);
  else if(/^\d+(?:[.,]\d+)?\s*(?:ml|g|l|kg|Stück)?$/i.test(sizeText))
   amazonSuggestions['Inhalt/Menge']=sizeText;
  const unitText=String(unit?.unit??'').trim().toLowerCase();
  const units={milliliter:'ml',milliliters:'ml',ml:'ml',liter:'l',liters:'l',l:'l',gram:'g',grams:'g',g:'g',kilogram:'kg',kilograms:'kg',kg:'kg'};
  if(units[unitText])amazonSuggestions.Inhaltseinheit=units[unitText];

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
 const {ean,record,revision}=active;
 const info=head(record?'✓ Bereits geprüfter Artikel · dauerhaft gespeichert':'Artikelvorschläge automatisch prüfen',record?
  'Version '+revision+' · geprüft am '+(record.approved_at_utc||'unbekannt')+' UTC · aktuelle Freigabe hat Vorrang vor alten Excel-Recherchehinweisen.':
  'Vorhandene JTL-/CSV-Felder und eindeutige Amazon-Katalogtreffer sind als Vorschläge vorausgefüllt. Du bestätigst nur geprüfte Angaben. Keine direkte Änderung in JTL.');
 if(!ean)return;
 if(record?.fields&&Object.keys(record.fields).length){
  const current=document.createElement('details');current.open=true;
  current.append(text('summary','Aktuell bestätigte Angaben ('+Object.keys(record.fields).length+')'));
  const grid=document.createElement('div');grid.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:7px;margin:9px 0';
  for(const [k,v] of Object.entries(record.fields)){
   const cell=document.createElement('div');cell.style.cssText='border:1px solid #dce6ef;background:#f5fbf7;border-radius:9px;padding:8px;min-width:0;overflow-wrap:anywhere';
   const small=text('small',k);small.style.display='block';cell.append(small,text('strong',v));grid.append(cell);
  }
  current.append(grid);root.append(current);
  if(Array.isArray(record.audit)&&record.audit.length){
   const history=document.createElement('details');history.style.cssText='margin-top:9px;padding:7px 0';
   const heading=text('summary','🕓 Änderungsverlauf ('+record.audit.length+' zuletzt gespeicherte Versionen)');
   heading.style.cssText='cursor:pointer;font-size:12px;font-weight:650';history.append(heading);
   const items=document.createElement('ul');items.style.cssText='font-size:12px;color:#435a70;padding-left:22px';
   for(const change of record.audit){
    const li=text('li','Version '+change.revision+' · '+change.at_utc+' UTC · '+(change.changed_fields||[]).join(', '));
    items.append(li);
   }
   history.append(items);root.append(history);
  }
 }
 const edit=document.createElement('details');
 edit.className='hw24-curated-edit';edit.style.marginTop='10px';
 const title=text('summary','✅ Vorbelegte Werte prüfen und übernehmen');title.style.cssText='cursor:pointer;font-weight:700';
 edit.append(title);
 const explain=text('p','JTL-/CSV-Daten, bereits geprüfte HW24-Werte und eindeutige Amazon-Fakten erscheinen als Vorschläge. Per Auswahl oder Enter im Artikelstamm werden Änderungen revisionssicher im HW24-Datenbestand gespeichert. Hier kannst Du mehrere ausgewählte Felder gemeinsam freigeben. Kein direkter JTL- oder Amazon-Schreibzugriff.');
 explain.className='note';edit.append(explain);
 const list=document.createElement('div');list.style.cssText='display:grid;gap:7px';
 const inputs=[];
 for(const name of labels){
  const wrapper=document.createElement('label');wrapper.dataset.reviewedKey=name;wrapper.style.cssText='display:grid;grid-template-columns:24px minmax(110px,1fr);gap:8px;align-items:center;padding:9px;border:1px solid #dde6ee;border-radius:9px;background:#fafcfe';
  const check=document.createElement('input');check.type='checkbox';check.checked=false;
  const slot=document.createElement('span');
  const heading=text('strong',name);heading.style.display='block';
  const input=document.createElement(name==='Beschreibung'||name==='Kurzbeschreibung'?'textarea':'input');
  if(input.tagName==='INPUT')input.type='text';
  const suggested=suggestion(name);
  input.value=suggested.value;
  if(suggested.source.startsWith('Dauerhaft freigegeben'))wrapper.style.borderColor='#8abf9b';
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
  const session=active;
  if(!valid(session.ean))return;
  const chosen=Object.fromEntries(inputs.filter(r=>r.check.checked&&r.input.value.trim()).map(r=>[r.name,r.input.value.trim()]));
  if(!Object.keys(chosen).length){state.textContent='Bitte mindestens ein geprüftes Feld auswählen.';return;}
  if(!confirm('Diese '+Object.keys(chosen).length+' geprüften Felder für EAN '+session.ean+' als neuen aktuellen Artikelstand speichern?'))return;
  save.disabled=true;state.textContent='Geprüfter Artikelstand wird gesichert …';
  const result=await enqueue(()=>storeApproval(session,chosen));
  if(active!==session)return;
  if(result.ok)render();
  else state.textContent=result.message;
  save.disabled=false;
 };
 edit.append(save,state);root.append(edit);
}
function enqueue(task){
 const operation=saveChain.then(task);
 saveChain=operation.catch(()=>{});
 return operation;
}
function approvalMessage(error,status){
 if(error==='revision_conflict')return 'Der Artikel wurde zwischenzeitlich bearbeitet. Bitte erneut scannen und die Eingabe prüfen.';
 if(status===401||status===403)return 'Anmeldung für die HW24-Datenbank fehlt oder ist abgelaufen.';
 if(status===503)return 'Das HW24-Freigabearchiv ist momentan nicht verfügbar.';
 if(error==='invalid_asin')return 'Bitte eine gültige ASIN aus zehn Buchstaben oder Ziffern eingeben.';
 if(error==='invalid_field_length')return 'Dieser Wert überschreitet die zulässige Feldlänge.';
 if(status===200)return 'Die Antwort des Freigabearchivs konnte nicht bestätigt werden.';
 return 'Speichern fehlgeschlagen (HTTP '+status+').';
}
function showApproved(session){
 if(active!==session||!session.record?.fields)return;
 try{
  window.hw24ApplyApprovedArticle?.(session.ean,session.record.fields);
 }catch(err){console.error('HW24: Gespeicherte Felder konnten nicht in allen Ansichten aktualisiert werden.',err);}
}
async function readApproval(session){
 if(location.hostname.endsWith('.github.io'))return {ok:false,message:'Stammdatenpflege ist auf dem geschützten Plesk-Server verfügbar.'};
 try{
  const res=await fetch('api/curated.php?ean='+encodeURIComponent(session.ean),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
  const data=await res.json().catch(()=>null);
  if(!res.ok||!data?.ok||!Number.isInteger(data.revision))
   return {ok:false,message:approvalMessage(data?.error,res.status)};
  session.revision=data.revision;session.record=data.exists?data:null;session.ready=true;
  return {ok:true};
 }catch(err){return {ok:false,message:'Das HW24-Freigabearchiv ist momentan nicht erreichbar.'};}
}
window.hw24CuratedLookup=ean=>{
 const session={ean:String(ean||''),revision:0,record:null,ready:false};
 active=session;jtlSuggestions={};amazonSuggestions={};
 if(!valid(session.ean)){
  head('Freigegebener Artikelstand','Nach einer gültigen EAN wird hier der zuletzt geprüfte Datenstand angezeigt.');return;
 }
 const state=head('Freigegebener Artikelstand','Freigaben werden aus der Datenbank gelesen …');
 return enqueue(async()=>{
  if(active!==session)return;
  const result=await readApproval(session);
  if(active!==session)return;
  if(!result.ok){state.textContent=result.message;return;}
  render();
  showApproved(session);
 });
};
async function storeApproval(session,fields){
 if(!valid(session.ean))return {ok:false,message:'Eine gültige EAN ist zum Speichern erforderlich.'};
 if(!session.ready){
  const result=await readApproval(session);
  if(!result.ok)return result;
 }
 if(Object.entries(fields).every(([key,value])=>session.record?.fields?.[key]===value)){
  showApproved(session);
  return {ok:true,revision:session.revision};
 }
 try{
  const res=await fetch('api/curated.php',{method:'POST',credentials:'same-origin',cache:'no-store',
   headers:{'Content-Type':'application/json','X-HW24-Approval':'confirmed','Accept':'application/json'},
   body:JSON.stringify({ean:session.ean,fields,confirmed:true,expected_revision:session.revision})});
  const data=await res.json().catch(()=>null);
  if(!res.ok||!data?.ok||!Number.isInteger(data.revision)||
    !Object.entries(fields).every(([key,value])=>data.fields?.[key]===value)){
   return {ok:false,message:approvalMessage(data?.error,res.status)};
  }
  session.revision=data.revision;session.record=data;
  showApproved(session);
  return {ok:true,revision:session.revision};
 }catch(err){return {ok:false,message:'Netzwerkfehler beim Speichern. Bitte erneut übernehmen.'};}
}

/* Explicit edits in the existing master-data editor are stored on select/change
 * or Enter. No writes occur for untouched proposals or on an EAN scan.
 * A save only updates the HW24 curated database and its audit trail, NEVER JTL.
 */
const canSave=new Set(labels);
function editedField(target){
 if(!target||!['INPUT','TEXTAREA','SELECT'].includes(target.tagName))return null;
 let key='',box=target.closest('.field');
 if(!box)return null;
 if(box.closest('#fields'))key=box.querySelector('label')?.textContent?.trim()||'';
 else if(box.closest('#jtlProfile'))key=box.previousElementSibling?.textContent?.trim()||'';
 if(!canSave.has(key)||target.disabled||target.readOnly)return null;
 return {key,value:String(target.value??'').trim(),box};
}
function feedback(box,message,error=false){
 if(!box)return;
 let status=box.querySelector('[data-hw24-reviewed-feedback]');
 if(!status){status=text('small','');status.dataset.hw24ReviewedFeedback='1';status.style.cssText='display:block;font-size:11px;margin-top:3px';box.append(status);}
 status.textContent=message;status.style.color=error?'#b23c39':'#17653b';
}
function saveReviewed(change){
 const session=active,{key,value,box}=change;
 feedback(box,'Wird in der HW24-Datenbank gespeichert …');
 return enqueue(async()=>{
  const result=await storeApproval(session,{[key]:value});
  if(active===session){
   feedback(box,result.ok?'✓ Dauerhaft gespeichert · Version '+result.revision:result.message,!result.ok);
   if(result.ok&&!root.querySelector('details[open].hw24-curated-edit'))render();
  }
  return result;
 });
}
// The primary HW24 workdesk has its own controls outside #fields.
window.hw24CuratedCanSaveField=key=>canSave.has(key);
window.hw24CuratedSaveField=(key,value)=>{
 const chosen=String(value??'').trim();
 if(!canSave.has(key)||!chosen)return Promise.resolve({ok:false,message:'Dieses Feld kann so nicht gespeichert werden.'});
 return saveReviewed({key,value:chosen,box:null});
};
document.addEventListener('change',event=>{
 const change=editedField(event.target);
 if(change&&change.value)saveReviewed(change);
},true);
document.addEventListener('keydown',event=>{
 if(event.key!=='Enter'||event.target.tagName!=='INPUT'||event.isComposing)return;
 const change=editedField(event.target);
 if(change&&change.value){event.preventDefault();saveReviewed(change);}
},true);

head('Freigegebener Artikelstand','Nach einer EAN-Prüfung siehst Du hier Deine bestätigten Stammdaten.');
})();
