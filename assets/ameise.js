/* HW24 Ameise preparation v1
   Schema based on actual user-supplied JTL export (12 Nov 2026, 872 columns).
   One scanned article -> one explicit JTL-Ameise Artikeldaten draft CSV.
   Not a direct JTL import, not an import profile, not marketplace publication. */
(function(){
'use strict';
const $=id=>document.getElementById(id),v=x=>String(x??'').trim();
const schema=[
 ['Artikelnummer',['Artikelnummer']],
 ['GTIN',['GTIN','EAN']],['HAN',['HAN']],['Artikelname',['Artikelname']],
 ['Kurzbeschreibung',['Kurzbeschreibung']],['Beschreibung',['Beschreibung']],
 ['Anmerkung',['Anmerkung']],['Verkaufskanal-Suchbegriffe',['Verkaufskanal-Suchbegriffe']],
 ['URL-Pfad',['URL-Pfad']],['Titel-Tag (SEO)',['Titel-Tag (SEO)']],
 ['Meta-Description (SEO)',['Meta-Description (SEO)']],
 ['Brutto-VK',['Brutto-VK']],['Netto-VK',['Netto-VK']],
 ['Kosten',['Kosten']], // USER'S actual EK field; different from calculated GLD!
 ['Steuerklasse',['Steuerklasse']],['Hersteller',['Hersteller']],
 ['Warengruppe',['Warengruppe']],['Artikelgewicht',['Artikelgewicht']],
 ['Versandgewicht',['Versandgewicht']],['Verkaufseinheit',['Verkaufseinheit']],
 ['Inhalt/Menge',['Inhalt/Menge']],['Maßeinheit',['Maßeinheit']],
 ['Grundpreis ausweisen',['Grundpreis ausweisen']],
 ['GP-Bezugsmenge',['GP-Bezugsmenge']],['Einheit Bezugsmenge',['Einheit Bezugsmenge']],
 ['Überverkäufe möglich',['Überverkäufe möglich']],
 ['Lieferstatus',['Lieferstatus']],['Stückzahl teilbar',['Stückzahl teilbar']],
 ['Zusätzliche Bearbeitungszeit',['Zusätzliche Bearbeitungszeit']],
 ['Beschaffungszeit automatisch ermitteln',['Beschaffungszeit automatisch ermitteln']],
 ['Beschaffungszeit manuell setzen',['Beschaffungszeit manuell setzen']],
 ['Versandklasse',['Versandklasse']],['Artikel aktiv',['Artikel aktiv']],
 ['Bestandsführung aktiv',['Bestandsführung aktiv']],['Top-Artikel',['Top-Artikel']],
 ['Artikeltyp',['Artikeltyp']],['Neu im Sortiment',['Neu im Sortiment']],
 ['Währung',['Währung']],['ASIN',['ASIN']],
 ['JTL-Wawi: Amazon B2B Netto',['JTL-Wawi: Amazon B2B Netto']],
 ['JTL-Wawi: Amazon B2B Brutto',['JTL-Wawi: Amazon B2B Brutto']],
 ['JTL-Wawi: Amazon B2C Netto',['JTL-Wawi: Amazon B2C Netto']],
 ['JTL-Wawi: Amazon B2C Brutto',['JTL-Wawi: Amazon B2C Brutto']],
 ['JTL-Wawi: eBay Fb24 Netto',['JTL-Wawi: eBay Fb24 Netto']],
 ['JTL-Wawi: eBay Fb24 Brutto',['JTL-Wawi: eBay Fb24 Brutto']],
 ['Neuer Standardpreis für eBay-Angebote',['Neuer Standardpreis für eBay-Angebote']],
 ['Kategorie Ebene 1',['Kategorie Ebene 1']],['Kategorie Ebene 2',['Kategorie Ebene 2']],
 ['Kategorie Ebene 3',['Kategorie Ebene 3']]
];
window.hw24AmeiseSupportedFields=()=>schema.map(row=>row[0]);
const calculated=new Set(['Durchschnittlicher Einkaufspreis (netto)','Letzter EK (JTL)','Datum letzter EK',
  'Auf Lager','In Aufträgen','Verfügbar','Fehlbestand','Menge','Lagerbestand','Artikel zuletzt bearbeitet am','Artikel zuletzt bearbeitet durch','Steuersatz in %']);
let record=null,ean='',jtl=null,loadedArchive=null;
function source(k){for(const key of k){const value=v(record?.[key]);if(value!=='')return value;}return ''}
function nonZeroMoney(k){
 const raw=source(k).replace(/\s/g,'');
 if(!raw)return '';
 let normalized=raw;
 // JTL SQL API uses "." decimal; native JTL exports normally use "," decimal.
 // Reject unrecognized money formatting rather than risking a 100x price error.
 if(/^\d{1,3}(?:\.\d{3})+,\d{1,4}$/.test(raw))normalized=raw.replace(/\./g,'');
 else if(/^\d+\.\d{1,4}$/.test(raw))normalized=raw.replace('.',',');
 else if(!/^\d+(?:,\d{1,4})?$/.test(raw))return '';
 const amount=Number(normalized.replace(',','.'));
 return Number.isFinite(amount)&&amount>0?normalized:'';
}
function snapshot(){
  if(jtl?.jtl_status==='current_snapshot'){
    const n=(jtl.matches||[]).length;
    return n===1?{type:'update',name:'Aktualisierung (JTL-Treffer über EAN)',count:n}
      :n===0?{type:'new',name:'Mögliche Neuanlage (EAN fehlt im JTL-Spiegel; Artikelnummer separat prüfen)',count:0}
      :{type:'duplicate',name:'Mehrdeutiger JTL-Treffer. Artikelnummer/Kennung prüfen',count:n};
  }
  return {type:'unknown',name:'JTL-Synchronisation fehlt; JTL-Ameise entscheidet beim Import anhand der Artikelnummer',count:null};
}
function foundType(){return snapshot().type}
function candidateFields(){
  const p={};
  for(const [key,aliases] of schema){
    if(window.hw24FieldExportApproved && !window.hw24FieldExportApproved(key))continue;
    let value=source(aliases);
    // Never create import fields from zero / derived prices or internal calculations.
    // Never send missing/zero prices or zero purchase cost to Wawi.
    // Zero in the export can mean unknown, and must not erase existing prices.
    if(['Brutto-VK','Netto-VK','Kosten',
      'JTL-Wawi: Amazon B2B Netto','JTL-Wawi: Amazon B2B Brutto',
      'JTL-Wawi: Amazon B2C Netto','JTL-Wawi: Amazon B2C Brutto',
      'JTL-Wawi: eBay Fb24 Netto','JTL-Wawi: eBay Fb24 Brutto',
      'Neuer Standardpreis für eBay-Angebote'].includes(key))value=nonZeroMoney(aliases);
    if(!value)continue;
    p[key]=value;
  }
  return p;
}
function blockReasons(){
  const f=candidateFields(),found=snapshot(),errors=[];
  if(!f.Artikelnummer)errors.push('Artikelnummer fehlt. Nur bei eindeutig neuer Ware darf JTL-Ameise eine Artikelnummer über den konfigurierten Nummernkreis vergeben. Für vorhandene Artikel ist die alte Nummer beizubehalten.');
  if(!f.Artikelname)errors.push('Artikelname fehlt.');
  if(found.type==='duplicate'||record?.Status==='MEHRFACH_CSV')errors.push('Mehrere Artikel teilen sich diese EAN. Artikelnummer und richtigen JTL-Datensatz zunächst eindeutig auswählen.');
  if(found.type==='unknown')errors.push('Aktueller JTL-Abgleich fehlt. JTL-Ameise kann beim Trockenlauf mit Importart „Neue Artikel importieren, vorhandene aktualisieren“ nach Artikelnummer entscheiden.');
  if(window.hw24HasUnresolvedJtlWarengruppe?.())errors.push('JTL-Warengruppen-ID bekannt, Klarname noch nicht synchronisiert. Historische oder ähnliche Artikel dürfen die Gruppe nicht ersetzen.');
  if(!f.Steuerklasse)errors.push('Steuerklasse fehlt. Für Neuanlagen vor Import prüfen oder gesicherten JTL-Standardwert verwenden.');
  if(!f.Hersteller)errors.push('Hersteller/Marke fehlt (für Amazon oftmals wichtig).');
  if(!f.Kosten)errors.push('Kosten/EK fehlt oder ist 0; bitte den tatsächlichen Einkaufspreis prüfen. Ø EK ist ein getrennter JTL-Rechenwert.');
  if(!f.ASIN)errors.push('ASIN fehlt: Recherche für Amazon-Zuordnung prüfen. Keine ASIN automatisch erfinden.');
  if(f['Überverkäufe möglich']==='Y')errors.push('AUSNAHME: Überverkäufe = JA. Nur mit bewusster manueller Einzelfreigabe; bitte Lieferfähigkeit prüfen.');
  if(!f['Brutto-VK']&&!f['Netto-VK'])errors.push('Kein positiver Verkaufspreis im aktuellen Entwurf vorhanden.');
  return errors;
}
function safeName(s){return v(s).replace(/[^a-zA-Z0-9_-]/g,'_').slice(0,65)||'unbekannt'}
function csvCell(s){return '"'+v(s).replace(/"/g,'""')+'"'}
function download(content,filename,mime){
  const blob=new Blob([content],{type:mime}),url=URL.createObjectURL(blob),a=document.createElement('a');
  a.href=url;a.download=filename;a.click();setTimeout(()=>URL.revokeObjectURL(url),5000);
}
function readSettings(){return {semicolon:true,encoding:'UTF-8-BOM',importType:'Artikel > Artikeldaten',matchKey:'Artikelnummer',
 mode:'Neue Artikel importieren, vorhandene aktualisieren',autoNumberIfMissing:true,keepEmpty:true,
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
  const relevant=(typeof window.hw24TemplateExportFields==='function'
    ?window.hw24TemplateExportFields().filter(key=>schema.some(pair=>pair[0]===key))
    :[
    'Artikelnummer','Artikelname','GTIN','HAN','ASIN','Kosten','Steuerklasse','Hersteller','Warengruppe','Überverkäufe möglich','Artikel aktiv','Bestandsführung aktiv',
    'Brutto-VK','Netto-VK','Kurzbeschreibung','Beschreibung','Artikelgewicht','Versandgewicht',
    'Verkaufseinheit','Inhalt/Menge','Maßeinheit','Grundpreis ausweisen',
    'Kategorie Ebene 1','Kategorie Ebene 2','Lieferstatus','Zusätzliche Bearbeitungszeit','Beschaffungszeit manuell setzen'
  ]);
  for(const key of relevant){
    const tr=document.createElement('tr'),td=document.createElement('td'),tv=document.createElement('td');
    td.textContent=key;const value=f[key];const origin=window.hw24SourceFor?.(key);
    tv.textContent=(value===undefined?'Fehlt / noch nicht freigegeben':value.length>110?value.slice(0,107)+'…':value)
      +(origin&&value!==undefined?' · Quelle: '+origin.name:'');
    if(value===undefined)tv.style.color='#a34122';
    tr.append(td,tv);body.append(tr);
  }
  const problems=$('ameiseProblems');problems.replaceChildren();
  if(!reasons.length){const p=document.createElement('p');p.textContent='Basisprüfung bestanden. JTL-Ameise-Feldzuordnung, Importvorlage und Testlauf bleiben verpflichtend.';problems.append(p)}
  else{const l=document.createElement('ul');for(const err of reasons){const li=document.createElement('li');li.textContent=err;l.append(li)}problems.append(l)}
  $('ameiseFieldCount').textContent=Object.keys(f).length+' befüllte Spalten der aktiven Exportvorlage. Nicht freigegebene historische Angaben werden nicht exportiert. Kein Lagerbestands-Import; Ø EK bleibt JTL-Quellinformation.';
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
  if(window.hw24HasUnresolvedJtlWarengruppe?.()){
    $('ameiseSaved').textContent='Export gesperrt: JTL-Warengruppen-ID ist vorhanden, aber der Klarname wurde noch nicht synchronisiert. Keine falsche Warengruppe importieren.';return false;
  }
  if(window.hw24ScanReady&&!window.hw24ScanReady()){
    $('ameiseSaved').textContent='Bitte die aktuelle JTL-Abfrage erst abschließen. Ein Importentwurf ohne bestätigten Spiegel ist gesperrt.';return false;
  }
  const f=candidateFields(),reasons=blockReasons();
  const basket=Array.isArray(window.hw24AuditExtraRows?.())?window.hw24AuditExtraRows():[];
  const extras=basket.filter(row=>v(row.Artikelnummer)&&v(row.Artikelnummer)!==v(f.Artikelnummer)).slice(0,100);
  const chosen=new Set(typeof window.hw24TemplateExportFields==='function'
    ?window.hw24TemplateExportFields():schema.map(x=>x[0]));
  const keys=schema.map(x=>x[0]).filter(k=>chosen.has(k)&&
    (Object.prototype.hasOwnProperty.call(f,k)||extras.some(row=>Object.prototype.hasOwnProperty.call(row,k))));
  if(snapshot().type==='duplicate'||record?.Status==='MEHRFACH_CSV'){$('ameiseSaved').textContent='Mehrdeutige EAN: Bitte zunächst die richtige Artikelnummer auswählen. Kein Export bis zur eindeutigen Zuordnung.';return false}
  const newArticle=foundType()==='new';
  if(!keys.length||!f.Artikelname||(!f.Artikelnummer&&!newArticle)){
    $('ameiseSaved').textContent='Für eine Ameise-Prüfdatei fehlen Artikelnummer oder Artikelname.';return false
  }
  // Include a blank Artikelnummer only if the fresh JTL snapshot established a new EAN.
  // JTL-Ameise must be configured to generate identifiers; never invent a SKU.
  if(newArticle&&!keys.includes('Artikelnummer'))keys.unshift('Artikelnummer');
  const priceKeys=new Set(['Kosten','Brutto-VK','Netto-VK',
    'JTL-Wawi: Amazon B2B Netto','JTL-Wawi: Amazon B2B Brutto',
    'JTL-Wawi: Amazon B2C Netto','JTL-Wawi: Amazon B2C Brutto',
    'JTL-Wawi: eBay Fb24 Netto','JTL-Wawi: eBay Fb24 Brutto',
    'Neuer Standardpreis für eBay-Angebote']);
  const safeCell=(item,k)=>{
    let raw=v(item[k]);
    if(!priceKeys.has(k)||!raw)return raw;
    if(/^\d+(?:\.\d{1,4})?$/.test(raw))raw=raw.replace('.',',');
    return /^\d+(?:,\d{1,4})?$/.test(raw)&&Number(raw.replace(',','.'))>0?raw:'';
  };
  const rows=[f,...extras];
  const csv='\ufeff'+keys.map(csvCell).join(';')+'\r\n'+
    rows.map(row=>keys.map(k=>csvCell(safeCell(row,k))).join(';')).join('\r\n')+'\r\n';
  const status='TESTLAUF_ERFORDERLICH';
  download(csv,'HW24_Ameise_'+status+'_'+safeName(f.Artikelnummer||ean)+'.csv','text/csv;charset=utf-8');
  $('ameiseSaved').textContent='Ameise-Prüf-CSV: '+rows.length+' Zeile(n), '+keys.length+' ausgewählte Spalten. Weitere Artikel enthalten nur vorgemerkte Änderungen. Erst Ameise-Feldzuordnung und Testlauf prüfen.';
  return true;
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
      'Kosten':r.kosten,'Warengruppe':r.warengruppe_name,'Steuerklasse':r.steuerklasse_name,
      'Netto-VK':r.netto_vk
    };
    for(const [key,value] of Object.entries(fields))if(v(value)!=='')record[key]=String(value);
  }
  paint();
};
window.hw24AmeiseRefresh=paint;
window.hw24AmeiseAutoExport=()=>{paint();if(!record||!v(record.Artikelname))return false;if(!v(record.Artikelnummer)&&foundType()!=='new')return false;stage();return exportCSV()};
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