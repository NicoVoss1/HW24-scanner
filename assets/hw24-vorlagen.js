/* HW24 v4.4 test-only: lean scan naming + configurable Ameise field selection.
 * Browser-local prototype, no server-side persistence or JTL writes.
 * Available export fields come exclusively from the existing Ameise schema.
 * Unmapped CSV columns remain view-only until reviewed in an import mapping.
 */
(function(){
'use strict';
const $=id=>document.getElementById(id);
const value=x=>String(x??'').trim();
const storeKey='hw24_test_vorlagen_v1';
const seedKeys=['Artikelnummer','Artikelname','GTIN','HAN','Hersteller','Warengruppe',
 'Kosten','Brutto-VK','Netto-VK','Steuerklasse','Kurzbeschreibung','Beschreibung',
 'Inhalt/Menge','Maßeinheit','Grundpreis ausweisen','Versandklasse','Artikel aktiv',
 'Bestandsführung aktiv','Überverkäufe möglich','Kategorie Ebene 1','Kategorie Ebene 2','ASIN'];
const required=new Set(['Artikelnummer','Artikelname','GTIN']);
const allowedDefaults=new Set(['Überverkäufe möglich','Artikel aktiv','Bestandsführung aktiv',
 'Stückzahl teilbar','Top-Artikel','Neu im Sortiment','Währung','Lieferstatus',
 'Versandklasse','Artikeltyp','Steuerklasse','Zusätzliche Bearbeitungszeit',
 'Beschaffungszeit manuell setzen']);
const staticDefaults={'Überverkäufe möglich':'N','Bestandsführung aktiv':'Y','Artikel aktiv':'Y'};
let current=null,ean='',csvHeaders=[],supported=[];
let profiles=[];
function seed(){return [{id:'haarfarben',name:'Haarfarben · Stammdaten',columns:seedKeys.slice(),defaults:{...staticDefaults}}]}
function load(){
 try{const d=JSON.parse(localStorage.getItem(storeKey)||'null');return Array.isArray(d)&&d.length?d:seed()}
 catch(_){return seed()}
}
profiles=load();
let active=profiles[0]?.id||'haarfarben',draft=null;
let root,inputs,fieldList,fieldCount,editorStatus,preview,oldName,saveButton,nameBox;
const style=document.createElement('style');
style.textContent='.hw24-miniwrap{border:1px solid #d5e0ef;border-radius:12px;background:#fff;padding:14px;margin:14px 0}.hw24-miniwrap h3{margin:0 0 8px}.hw24-mini-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,170px),1fr));gap:9px}.hw24-mini-grid label{display:block;font-size:12px;color:#41516b;margin:5px 0}.hw24-mini-grid input,.hw24-mini-grid select,.hw24-miniwrap input[type=search],.hw24-miniwrap select{box-sizing:border-box;width:100%;max-width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#14233c}.hw24-miniwrap .hw24-small{font-size:12px;color:#576983}.hw24-field-row{display:grid;grid-template-columns:minmax(0,1fr) 95px 110px;gap:10px;align-items:center;padding:8px;border-bottom:1px solid #e4e9ef;font-size:13px}.hw24-field-row small{display:block;color:#6b7788}.hw24-field-row label{display:flex;align-items:center;gap:6px;justify-content:center}.hw24-field-row input{width:18px;height:18px}.hw24-field-row.unmapped{opacity:.68}.hw24-miniwrap button{margin:5px 5px 5px 0}.hw24-name-preview{padding:10px;background:#f0f5fc;border-radius:8px;overflow-wrap:anywhere}.hw24-previews{display:grid;gap:8px}.hw24-scroll-list{max-height:330px;overflow:auto;border:1px solid #d8e1ed;border-radius:9px}.hw24-miniwrap details>summary{cursor:pointer;font-weight:700;margin:8px 0}@media(max-width:600px){.hw24-field-row{grid-template-columns:minmax(0,1fr) 65px 75px;gap:4px;font-size:12px}}';
document.head.append(style);
function el(tag,props={},parent){
 const node=document.createElement(tag);
 for(const [key,v] of Object.entries(props)){
  if(key==='text')node.textContent=v;
  else if(key==='class')node.className=v;
  else node.setAttribute(key,String(v));
 }
 if(parent)parent.append(node);
 return node;
}
function preset(id){return profiles.find(p=>p.id===id)||profiles[0]}
function notify(msg){editorStatus.textContent=msg}
function renderPicker(){
 const p=preset(active);if(!p)return;
 draft={...p,columns:[...(p.columns||[])],defaults:{...(p.defaults||{})}};
 $('hw24TemplateSelect').replaceChildren();
 for(const item of profiles)el('option',{value:item.id,text:item.name},$('hw24TemplateSelect'));
 $('hw24TemplateSelect').value=active;
 $('hw24TemplateName').value=p.name;
 renderCatalog();
}
function renderCatalog(){
 if(!fieldList||!draft)return;
 const query=value($('hw24FieldFilter').value).toLocaleLowerCase('de');
 const catalog=[...new Set([...supported,...csvHeaders])];
 const view=catalog.filter(k=>!query||k.toLocaleLowerCase('de').includes(query));
 fieldCount.textContent=catalog.length+' Felder im Katalog · '+supported.length+' im aktuellen Ameise-Exportmodell freigegeben · '+draft.columns.length+' ausgewählt. '+(query?view.length+' Suchtreffer.':'');
 fieldList.replaceChildren();
 for(const key of view.slice(0,75)){
  const allowed=supported.includes(key);
  const row=el('div',{class:'hw24-field-row'+(allowed?'':' unmapped')},fieldList);
  const label=el('div',{text:key},row);
  if(!allowed)el('small',{text:'CSV-Quellfeld · Ameise-Zuordnung noch nicht geprüft'},label);
  else if(current&&value(current[key]))el('small',{text:'Wert im aktuellen Datensatz vorhanden'},label);
  const cklabel=el('label',{title:allowed?'Spalte in die Ameise-Prüf-CSV aufnehmen':'Nur anzeigen; zuerst Ameise-Mapping verifizieren'},row);
  const ck=el('input',{type:'checkbox'},cklabel);
  ck.checked=allowed&&draft.columns.includes(key);
  ck.disabled=!allowed || required.has(key);
  ck.addEventListener('change',()=>{
   const set=new Set(draft.columns);if(ck.checked)set.add(key);else set.delete(key);draft.columns=[...set];window.hw24AmeiseRefresh?.();
   renderCatalog();
  });
  el('span',{text:'Export'},cklabel);
  if(allowed&&allowedDefaults.has(key)){
    const set=el('input',{type:'text',title:'Optionaler Standard nur für neue Artikel',placeholder:'Vorgabe'},row);
    set.style.cssText='min-width:0;width:98px;box-sizing:border-box;padding:5px;border-radius:5px;border:1px solid #c8d4e4';
    set.value=value(draft.defaults[key]);
    set.addEventListener('change',()=>{const vv=value(set.value);if(vv)draft.defaults[key]=vv;else delete draft.defaults[key];notify('Vorgabe geändert. Vorlage bitte speichern.');});
   }else el('span',{text:allowed?'Frei':'Prüfen'},row);
 }
 if(view.length>75)el('p',{class:'hw24-small',text:'Erste 75 Treffer sichtbar. Bitte Suchfeld verwenden; der Katalog ist nicht auf 75 Felder begrenzt.'},fieldList);
}
function persist(){
 if(!draft)return;
 const name=value($('hw24TemplateName').value);
 if(!name){notify('Bitte einen Vorlagennamen eingeben.');return}
 const columns=[...new Set([...required,...draft.columns])].filter(x=>supported.includes(x));
 const updated={id:draft.id,name,columns,defaults:{...draft.defaults}};
 profiles=profiles.map(p=>p.id===updated.id?updated:p);
 try{localStorage.setItem(storeKey,JSON.stringify(profiles));notify('Vorlage für diese Testumgebung im aktuellen Browser gespeichert. Noch keine zentrale Serverspeicherung.')}
 catch(_){notify('Browserspeicher gesperrt. Änderungen sind nicht dauerhaft gesichert.')}
 renderPicker();
 window.hw24AmeiseRefresh?.();
}
function createProfile(){
 const id='profil_'+Date.now().toString(36);
 const name='Neue Artikelvorlage';
 const p={id,name,columns:[...required],defaults:{...staticDefaults}};
 profiles.push(p);active=id;renderPicker();
 notify('Neue Testvorlage erstellt. Wähle die gewünschten Exportspalten und speichere sie.');
}
function makeLayout(){
 const scan=$('scanCockpit');if(!scan)return;
 root=el('section',{class:'hw24-miniwrap',id:'hw24NamingRoot'});
 const heading=el('h3',{text:'✏️ Artikelname vereinheitlichen'},root);
 el('p',{class:'hw24-small',text:'Hersteller, Linie, Nuance und Menge als geprüfte Bausteine erfassen. Der aktuelle Name wird nur nach einem Klick geändert.'},root);
 nameBox=el('div',{class:'hw24-mini-grid'},root);
 const elements=[
  ['brand','Hersteller','Wella'],['line','Produktlinie','Color Touch'],
  ['shade','Nuancennummer','6/75'],['shadeName','Farbname','dunkelblond braun-mahagoni'],
  ['size','Inhalt','60'],['unit','Einheit','ml']
 ];
 for(const [id,label,placeholder] of elements){
  const wrap=el('div',{},nameBox);
  const labelNode=el('label',{text:label,for:'hw24Name_'+id},wrap);
  const inp=el('input',{id:'hw24Name_'+id,type:'text',placeholder},wrap);
  inp.addEventListener('input',refreshName);
 }
 const previews=el('div',{class:'hw24-previews'},root);
 el('span',{class:'hw24-small',text:'Bisheriger Artikelname'},previews);
 oldName=el('div',{class:'hw24-name-preview',text:'EAN scannen, um einen Artikel zu wählen.'},previews);
 el('span',{class:'hw24-small',text:'Vorgeschlagener Artikelname'},previews);
 preview=el('div',{class:'hw24-name-preview',text:'Bitte Produktdaten ergänzen.'},previews);
 saveButton=el('button',{type:'button',text:'✓ Namen nach Prüfung übernehmen'},root);
 saveButton.disabled=true;saveButton.addEventListener('click',applyName);
 el('span',{class:'hw24-small',id:'hw24NameResult'},root);
 scan.after(root);
 const section=el('details',{class:'hw24-miniwrap',id:'hw24TemplateSection'});
 el('summary',{text:'⚙️ Stammdaten-Vorlagen und Exportfelder auswählen'},section);
 el('p',{class:'hw24-small',text:'Testphase: Vorlagen werden nur in diesem Browser gespeichert. Zentrale MariaDB-Speicherung und geprüfte Importunterstützung aller JTL-Quellfelder folgen separat.'},section);
 const form=el('div',{class:'hw24-mini-grid'},section);
 const profileWrap=el('div',{},form);
 el('label',{text:'Aktive Vorlage'},profileWrap);
 const sel=el('select',{id:'hw24TemplateSelect'},profileWrap);
 sel.addEventListener('change',()=>{active=sel.value;renderPicker();});
 const nameWrap=el('div',{},form);
 el('label',{text:'Vorlagenname'},nameWrap);
 el('input',{id:'hw24TemplateName',type:'text'},nameWrap);
 const actions=el('div',{},section);
 const newButton=el('button',{type:'button',text:'Neue Vorlage'},actions);
 newButton.onclick=createProfile;
 const storeButton=el('button',{type:'button',text:'Vorlage speichern'},actions);
 storeButton.onclick=persist;
  const direct=el('button',{type:'button',text:'📥 Prüf-CSV mit dieser Vorlage exportieren'},actions);
  direct.onclick=()=>{const ok=window.hw24AmeiseAutoExport?.();notify(ok?'Prüf-CSV erstellt: ausgewählte, belegte Felder. JTL-Ameise-Testlauf erforderlich.':'Noch kein Export möglich: Bitte EAN und Pflichtangaben prüfen.');};
 editorStatus=el('p',{class:'hw24-small'},section);
 const filterWrap=el('div',{},section);
 el('label',{text:'Spalten suchen'},filterWrap);
 const filter=el('input',{type:'search',id:'hw24FieldFilter',placeholder:'z. B. Hersteller, GTIN, Kosten, Shopify'},filterWrap);
 filter.addEventListener('input',renderCatalog);
 fieldCount=el('p',{class:'hw24-small'},section);
 fieldList=el('div',{class:'hw24-scroll-list'},section);
 root.after(section);
 const optional=$('importArea');
 if(optional){
  const outer=el('details',{class:'hw24-miniwrap'});
  el('summary',{text:'📂 Optional: JTL-CSV zur erweiterten Feldprüfung laden'},outer);
  optional.before(outer);outer.append(optional);
 }
 const ameise=$('ameisePanel');if(ameise)ameise.open=false;
 supported=window.hw24AmeiseSupportedFields?.()||[];
 renderPicker();
}
function extractFromCurrent(){
 if(!current)return;
 for(const id of ['brand','line','shade','shadeName','size','unit'])$('hw24Name_'+id).value='';
 const name=value(current.Artikelname);
 const brand=value(current.Hersteller);
 $('hw24Name_brand').value=brand;
 let body=name;
 const size=body.match(/\s*-\s*\(\s*(\d+(?:[,.]\d+)?)\s*(ml|l|g|kg|Stk\.?)\s*\)\s*$/i);
 if(size){
  $('hw24Name_size').value=size[1];
  $('hw24Name_unit').value=size[2].toLowerCase().replace('stk.','Stk');
  body=body.slice(0,size.index).trim();
 }
 if(brand&&body.toLowerCase().startsWith(brand.toLowerCase()+' '))body=body.slice(brand.length).trim();
 const shade=body.match(/^(.+?)\s+(\d{1,3}(?:[./]\d{1,3})?)\s+(.+)$/);
 if(shade){
  $('hw24Name_line').value=shade[1].trim();
  $('hw24Name_shade').value=shade[2].trim();
  $('hw24Name_shadeName').value=shade[3].trim();
 }else $('hw24Name_line').value=body;
 refreshName();
}
function proposed(){
 if(!current)return '';
 let [brand,line,shade,shadeName,size,unit]=['brand','line','shade','shadeName','size','unit'].map(id=>value($('hw24Name_'+id).value));
 if(!brand||!line||!shade||!shadeName||!size||!unit)return '';
 if(!/^\d+(?:[,.]\d+)?$/.test(size))return '';
 if(!/^[\w\d./-]{1,15}$/.test(shade))return '';
 const prefix=brand.toLowerCase()+' ';
 if(line.toLowerCase().startsWith(prefix))line=line.slice(brand.length).trim();
 return [brand,line,shade,shadeName].join(' ').replace(/\s+/g,' ').trim()+' - ('+size.replace(',','.')+' '+unit+')';
}
function refreshName(){
 if(!oldName||!preview)return;
 oldName.textContent=value(current?.Artikelname)||'Kein Artikelname vorhanden';
 const next=proposed();
 preview.textContent=next||'Bitte alle Namensbausteine und eine gültige Mengenangabe ergänzen.';
 saveButton.disabled=!next||next===value(current?.Artikelname);
}
function applyName(){
 const next=proposed();
 if(!next||!current)return;
 const manufacturer=value($('hw24Name_brand').value);
 // Update only explicitly reviewed fields, leaving all other JTL data untouched.
 const update=(field,newValue)=>{
  current[field]=newValue;
  const controls=[...document.querySelectorAll('#fields .field')];
  const target=controls.find(x=>x.querySelector('label')?.textContent===field);
  const input=target?.querySelector('input,textarea');
  if(input){input.value=newValue;input.dispatchEvent(new Event('input',{bubbles:true}));}
 };
 if(!value(current.Hersteller))update('Hersteller',manufacturer);
 update('Artikelname',next);
 window.hw24AmeiseCurrent?.(current);
 window.hw24AmeiseRefresh?.();
 $('hw24NameResult').textContent='Namensvorschlag in den lokalen Artikelentwurf übernommen. JTL bleibt unverändert.';
 refreshName();
}
function onScan(id,record){ean=value(id);current=record||null;extractFromCurrent();if($('ameisePanel'))$('ameisePanel').open=false;renderCatalog();}
function onJtl(data){
 if(data?.jtl_status==='current_snapshot' && data.matches?.length===1 && current){
  const row=data.matches[0];
  if(value(row.artikelname))current.Artikelname=value(row.artikelname);
  if(value(row.hersteller))current.Hersteller=value(row.hersteller);
  extractFromCurrent();
 }
}
function attach(){
 const originalScan=window.hw24AmeiseScan;
 if(typeof originalScan==='function')window.hw24AmeiseScan=(id,data)=>{originalScan(id,data);onScan(id,data);};
 const originalJtl=window.hw24AmeiseJtl;
 if(typeof originalJtl==='function')window.hw24AmeiseJtl=(data)=>{originalJtl(data);onJtl(data);};
 window.hw24AmeiseSupportedFields=window.hw24AmeiseSupportedFields||(()=>[]);
 window.hw24TemplateExportFields=()=>{const p=draft&&draft.id===active?draft:preset(active);return [...new Set([...required,...(p?.columns||[])])];};
 window.hw24ApplyTemplateDefaults=(record,jtl)=>{
  if(!record||jtl?.jtl_status!=='current_snapshot'||jtl.matches?.length!==0)return [];
  const p=draft&&draft.id===active?draft:preset(active);
  const applied=[];
  for(const [key,raw] of Object.entries(p?.defaults||{})){
    if(key==='Warengruppe')continue; // JTL-ID mapping or explicit review only; no persisted browser default may assign Haarstyling.
    if(!allowedDefaults.has(key)||!supported.includes(key)||value(record[key]))continue;
    const proposed=value(raw);
    if(!proposed||key==='Überverkäufe möglich'&&proposed!=='N')continue;
    record[key]=proposed;applied.push(key);
  }
  return applied;
 };
 window.hw24NamingRefresh=extractFromCurrent;
 window.hw24TemplatesRefresh=()=>{renderCatalog();window.hw24AmeiseRefresh?.()};
 window.hw24TemplatesCatalog=headers=>{if(Array.isArray(headers)){csvHeaders=headers.filter(h=>typeof h==='string'&&h.length<200);renderCatalog();}};
}
attach();
makeLayout();
})();