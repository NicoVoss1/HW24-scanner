/* HW24 Ameise preparation v1
   Schema based on actual user-supplied JTL export (12 Nov 2026, 872 columns).
   One scanned article -> one explicit JTL-Ameise Artikeldaten draft CSV.
   Not a direct JTL import, not an import profile, not marketplace publication. */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
const schema=[
  ['Artikelnummer',['Artikelnummer']],
  ['GTIN',['GTIN','EAN']],
  ['HAN',['HAN']],
  ['Artikelname',['Artikelname']],
  ['Kurzbeschreibung',['Kurzbeschreibung']],
  ['Beschreibung',['Beschreibung']],
  ['Verkaufskanal-Suchbegriffe',['Verkaufskanal-Suchbegriffe']],
  ['URL-Pfad',['URL-Pfad']],
  ['Titel-Tag (SEO)',['Titel-Tag (SEO)']],
  ['Meta-Description (SEO)',['Meta-Description (SEO)']],
  ['Brutto-VK',['Brutto-VK']],
  ['Netto-VK',['Netto-VK']],
  ['Steuerklasse',['Steuerklasse']],
  ['Hersteller',['Hersteller']],
  ['Warengruppe',['Warengruppe']],
  ['Artikelgewicht',['Artikelgewicht']],
  ['Versandgewicht',['Versandgewicht']],
  ['Verkaufseinheit',['Verkaufseinheit']],
  ['Inhalt/Menge',['Inhalt/Menge']],
  ['Maßeinheit',['Maßeinheit']],
  ['Grundpreis ausweisen',['Grundpreis ausweisen']],
  ['GP-Bezugsmenge',['GP-Bezugsmenge']],
  ['Einheit Bezugsmenge',['Einheit Bezugsmenge']],
  ['Artikel aktiv',['Artikel aktiv']],
  ['ASIN',['ASIN']],
  ['Kategorie Ebene 1',['Kategorie Ebene 1']],
  ['Kategorie Ebene 2',['Kategorie Ebene 2']],
  ['Kategorie Ebene 3',['Kategorie Ebene 3']]
];
const calculated=new Set(['Durchschnittlicher Einkaufspreis (netto)','Letzter EK (JTL)','Datum letzter EK',
  'Auf Lager','In Aufträgen','Verfügbar','Fehlbestand','Menge','Lagerbestand']);
let record=null,ean='',jtl=null,loadedArchive=null;
function source(k){for(const key of k){const value=v(record?.[key]);if(value!=='')return value;}return ''}
function nonZeroMoney(k){const x=source(k);if(!x)return '';const amount=Number(x.replace(/\./g,'').replace(',','.'));return Number.isFinite(amount)&&amount>0?x:''}
function snapshot(){
  if(jtl?.jtl_status==='current_snapshot'){
    const n=(jtl.matches||[]).length;
    return n===1?{type:'update',name:'Aktualisierung (JTL-Treffer über EAN)',count:n}
      :n===0?{type:'new',name:'Neuanlage (EAN nicht im vollständigen JTL-Spiegel)',count:0}
      :{type:'duplicate',name:'Mehrdeutiger JTL-Treffer. Artikelnummer/Kennung prüfen',count:n};
  }
  return {type:'unknown',name:'JTL-Synchronisation fehlt; JTL-Ameise entscheidet beim Import anhand der Artikelnummer',count:null};
}
function candidateFields(){
  const p={};
  for(const [key,aliases] of schema){
    let value=source(aliases);
    // Never create import fields from zero / derived prices or internal calculations.
    if(['Brutto-VK','Netto-VK'].includes(key))value=nonZeroMoney(aliases);
    if(!value)continue;
    p[key]=value;
  }
  return p;
}
function blockReasons(){
  const f=candidateFields(),found=snapshot(),errors=[];
  if(!f.Artikelnummer)errors.push('Artikelnummer fehlt. Bitte einen dauerhaft eindeutigen JTL-Artikelnummernschlüssel vergeben.');
  if(!f.Artikelname)errors.push('Artikelname fehlt.');
  if(found.type==='duplicate'||record?.Status==='MEHRFACH_CSV')errors.push('Mehrere Artikel teilen sich diese EAN. Artikelnummer und richtigen JTL-Datensatz zunächst eindeutig auswählen.');
  if(found.type==='unknown')errors.push('Aktueller JTL-Abgleich fehlt. JTL-Ameise kann beim Trockenlauf mit Importart „Neue Artikel importieren, vorhandene aktualisieren“ nach Artikelnummer entscheiden.');
  if(!f.Steuerklasse)errors.push('Steuerklasse fehlt. Für Neuanlagen vor Import prüfen oder gesicherten JTL-Standardwert verwenden.');
  if(!f.Hersteller)errors.push('Hersteller/Marke fehlt (für Amazon oftmals wichtig).');
  if(!f.Brutto-VK&&!f['Netto-VK'])errors.push('Kein positiver Verkaufspreis im aktuellen Entwurf vorhanden.');
  return errors;
}
function safeName(s){return v(s).replace(/[^a-zA-Z0-9_-]/g,'_').slice(0,65)||'unbekannt'}
function csvCell(s){return '"'+v(s).replace(/"/g,'""')+'"'}
function download(content,filename,mime){
  const blob=new Blob([content],{type:mime}),url=URL.createObjectURL(blob),a=document.createElement('a');
  a.href=url;a.download=filename;a.click();setTimeout(()=>URL.revokeObjectURL(url),5000);
}
function readSettings(){return {semicolon:true,encoding:'UTF-8-BOM',importType:'Artikel > Artikeldaten',matchKey:'Artikelnummer',
 mode:'Neue Artikel importieren, vorhandene aktualisieren',keepEmpty:true,
 priceUpdates:'Vorhandene Preise nicht vorab löschen',categoryUpdates:'Vorhandene Kategorien nicht vorab löschen',
 images:'Nicht automatisch entfernen',stock:'Bestandsmengen nicht mit importieren'};}
function paint(){
  const root=$('ameisePanel');if(!root)return;
  const hasArticle=Boolean(ean && record);
  for(const id of ['ameisePrepare','ameiseExport','ameiseJSON']){
    const button=$(id);if(button)button.disabled=!hasArticle;
  }
  if(!hasArticle){
    $('ameiseIdentity').textContent='Noch kein Artikel ausgewählt';
    $('ameiseStatus').textContent='Gib oben eine EAN ein und drücke Enter. Alternativ nutze „EAN oben prüfen und Artikel laden“. Der Bereich ist jederzeit aufklappbar.';
    $('ameiseProblems').textContent='Nach einem Scan werden hier die fehlenden JTL-Stammdaten und die Importprüfung angezeigt.';
    $('ameiseFieldCount').textContent='Noch keine Artikeldaten übernommen.';
    $('ameisePreview').replaceChildren();
    return;
  }
  const f=candidateFields(),s=snapshot(),reasons=blockReasons();
  $('ameiseIdentity').textContent='EAN '+ean+' · '+s.name;
  $('ameiseStatus').textContent=s.type==='new'?'JTL-Neuanlage als Entwurf vorbereiten':s.type==='update'?'JTL-Aktualisierung als Entwurf vorbereiten':
    s.type==='duplicate'?'EAN mehrfach vorhanden: Artikelnummer als Importschlüssel prüfen':'JTL-Synchronisation fehlt: Ameise-Testlauf prüft Neuanlage/Aktualisierung anhand Artikelnummer';
  const body=$('ameisePreview');body.replaceChildren();
  const relevant=[
    'Artikelnummer','Artikelname','GTIN','HAN','Steuerklasse','Hersteller','Warengruppe',
    'Brutto-VK','Netto-VK','Kurzbeschreibung','Beschreibung','Artikelgewicht','Versandgewicht',
    'Verkaufseinheit','Inhalt/Menge','Maßeinheit','Grundpreis ausweisen',
    'Kategorie Ebene 1','Kategorie Ebene 2','ASIN'
  ];
  for(const key of relevant){
    const tr=document.createElement('tr'),td=document.createElement('td'),tv=document.createElement('td');
    td.textContent=key;const value=f[key];tv.textContent=value===undefined?'Fehlt / noch nicht freigegeben':value.length>110?value.slice(0,107)+'…':value;
    if(value===undefined)tv.style.color='#a34122';
    tr.append(td,tv);body.append(tr);
  }
  const problems=$('ameiseProblems');problems.replaceChildren();
  if(!reasons.length){const p=document.createElement('p');p.textContent='Basisprüfung bestanden. JTL-Ameise-Feldzuordnung, Importvorlage und Testlauf bleiben verpflichtend.';problems.append(p)}
  else{const l=document.createElement('ul');for(const err of reasons){const li=document.createElement('li');li.textContent=err;l.append(li)}problems.append(l)}
  $('ameiseFieldCount').textContent=Object.keys(f).length+' befüllte Originalspalten aus der 872-Spalten-JTL-Exportstruktur. Keine Lagerbestände, keine internen EK-Berechnungswerte.';
}
function stage(){
  if(!record)return;
  const f=candidateFields(),s=snapshot(),reasons=blockReasons();
  const packet={schema:'HW24_Ameise_Artikelstamm_Entwurf_v1',ean,createdAt:new Date().toISOString(),
    selectedMatchKey:'Artikelnummer',sourceJtl:s,fields:f,checks:reasons,
    importSettings:readSettings(),categoryMappingVerified:false,jtlImportTested:false,
    notes:'Artikelstammdaten. Attribute/Merkmale/Eigene Felder ggf. in eigenem Ameise-Import. Keine Bestandsbuchung. Durchschnittlicher EK/letzter EK ausschliesslich Quellanzeige.'};
  try{localStorage.setItem('hw24_ameise_draft_'+(f.Artikelnummer||ean),JSON.stringify(packet))}catch(e){
    $('ameiseSaved').textContent='Browser-Speicher nicht verfügbar, bitte CSV/JSON sichern.';return
  }
  $('ameiseSaved').textContent='Entwurf lokal gespeichert · '+new Date().toLocaleTimeString('de-DE')+'. Kein zentraler Audit-Log.';
}
function exportCSV(){
  const f=candidateFields(),reasons=blockReasons(),keys=schema.map(x=>x[0]).filter(k=>Object.prototype.hasOwnProperty.call(f,k));
  if(snapshot().type==='duplicate'||record?.Status==='MEHRFACH_CSV'){$('ameiseSaved').textContent='Mehrdeutige EAN: Bitte zunächst die richtige Artikelnummer auswählen. Kein Export bis zur eindeutigen Zuordnung.';return}
  if(!keys.length||!f.Artikelnummer||!f.Artikelname){
    $('ameiseSaved').textContent='Für eine Ameise-Prüfdatei fehlen Artikelnummer oder Artikelname.';return
  }
  const csv='\ufeff'+keys.map(csvCell).join(';')+'\r\n'+keys.map(k=>csvCell(f[k])).join(';')+'\r\n';
  const status='TESTLAUF_ERFORDERLICH';
  download(csv,'HW24_Ameise_'+status+'_'+safeName(f.Artikelnummer)+'.csv','text/csv;charset=utf-8');
  $('ameiseSaved').textContent='Ameise-Testlaufentwurf heruntergeladen. JTL-Ameise wählt anhand der Artikelnummer Neuanlage oder Aktualisierung. Erst Mapping kontrollieren, sichern und Testlauf durchführen.';
}
function exportJSON(){
  const f=candidateFields(),s=snapshot();
  download(JSON.stringify({article:f,identity:s,check:blockReasons(),settings:readSettings(),
    fullJtlSchemaPresent:false,verified:false,exportedAt:new Date().toISOString()},null,2),
    'HW24_Ameise_Pruefung_'+safeName(f.Artikelnummer||ean)+'.json','application/json');
}
window.hw24AmeiseScan=(id,data)=>{ean=v(id);record=data||null;jtl=null;if(ean&&$('ameisePanel'))$('ameisePanel').open=true;paint()};
window.hw24AmeiseCurrent=data=>{record=data||null;paint()};
window.hw24AmeiseJtl=result=>{
  jtl=result||null;
  if(jtl?.jtl_status==='current_snapshot'&&jtl.matches?.length===1&&record){
    const r=jtl.matches[0];
    const fields={
      'Artikelnummer':r.artikelnummer,'GTIN':r.ean,'HAN':r.han,'ASIN':r.asin,
      'Artikelname':r.artikelname,'Kurzbeschreibung':r.kurzbeschreibung,
      'Beschreibung':r.beschreibung,'Hersteller':r.hersteller,
      'Netto-VK':r.netto_vk
    };
    for(const [key,value] of Object.entries(fields))if(v(value)!=='')record[key]=String(value);
  }
  paint();
};
window.hw24AmeiseRefresh=paint;
$('ameiseLoadEan')?.addEventListener('click',()=>{
  const input=$('ean');
  if(!input||!v(input.value)){
    $('ameiseSaved').textContent='Bitte zuerst eine EAN in das Scan-Feld oben eingeben.';
    input?.focus();return;
  }
  input.dispatchEvent(new Event('change',{bubbles:true}));
});
$('ameiseRefresh')?.addEventListener('click',()=>{
  paint();
  $('ameiseSaved').textContent=record?'Ameise-Vorschau aus den aktuellen Stammdaten aktualisiert.':'Bitte zunächst eine EAN scannen.';
});
$('ameisePrepare')?.addEventListener('click',()=>{paint();stage()});
$('ameiseExport')?.addEventListener('click',()=>{paint();exportCSV()});
$('ameiseJSON')?.addEventListener('click',()=>{paint();exportJSON()});
paint();
})();