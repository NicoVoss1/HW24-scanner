/* HW24 V4.22 TEST · CSV EAN import.
 * Import metadata lives only in existing MariaDB scan source_json (csv_meta),
 * never in JTL-Wawi Anmerkung or WMS inventory.
 */
(function(root){
'use strict';
const v=x=>String(x??'').trim();
function normalize(s){return v(s).toLowerCase().normalize('NFKD')
 .replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]/g,'')}
function csvParse(text,delimiter){
 if(![';',',','\t'].includes(delimiter))throw Error('Ungültiges CSV-Trennzeichen');
 const rows=[];let row=[],field='',quoted=false;
 const data=String(text??'').replace(/^\uFEFF/,'');
 for(let i=0;i<data.length;i++){
  const c=data[i],next=data[i+1];
  if(c==='"'){
   if(quoted&&next==='"'){field+='"';i++;}else if(quoted){quoted=false;}else if(!field.trim()){quoted=true;}
   else field+='"';
  }else if(c===delimiter&&!quoted){row.push(field);field='';}
  else if((c==='\n'||c==='\r')&&!quoted){
   if(c==='\r'&&next==='\n')i++;
   row.push(field);
   if(row.some(x=>v(x)!==''))rows.push(row);
   row=[];field='';
   if(rows.length>5100)throw Error('CSV enthält mehr als 5.000 Zeilen. Bitte Datei aufteilen.');
  }else field+=c;
 }
 if(quoted)throw Error('CSV mit nicht geschlossenem Anführungszeichen.');
 row.push(field);
 if(row.some(x=>v(x)!==''))rows.push(row);
 return rows;
}
function detectDelimiter(text){
 const first=String(text??'').slice(0,15000);
 const counts={';':0,',':0,'\t':0};let quoted=false;
 for(let i=0;i<first.length;i++){
  const c=first[i];
  if(c==='"'){if(first[i+1]==='"'&&quoted){i++;continue;}quoted=!quoted;}
  else if(!quoted&&(c==='\r'||c==='\n'))break;
  else if(!quoted&&Object.prototype.hasOwnProperty.call(counts,c))counts[c]++;
 }
 return Object.entries(counts).sort((a,b)=>b[1]-a[1])[0][0];
}
const aliases={
 ean:['ean','gtin','ean13','ean8','gtin13','gtin14','barcode','strichcode','eancode','ean/gtin','eannummer'],
 lagerplatz:['lagerplatz','lagerort','fach','platz','lager'],
 information:['information','info','hinweis','bemerkung','artikelinfo'],
 kommentar:['kommentar','comment','comments','notiz','notes','scan kommentar','zusatzinfo']
};
const normalizedAliases=Object.fromEntries(Object.entries(aliases).map(([key,a])=>[key,new Set(a.map(normalize))]));
function prepare(text,goodEAN){
 const delimiter=detectDelimiter(text),rows=csvParse(text,delimiter);
 if(!rows.length)throw Error('CSV enthält keine Daten.');
 const head=rows[0].map(normalize);
 let eanIndex=head.findIndex(x=>normalizedAliases.ean.has(x));
 const headerPresent=eanIndex>=0;
 if(!headerPresent){
  if(!goodEAN(v(rows[0]?.[0])))throw Error('EAN-/GTIN-Spalte nicht gefunden. Erste Zeile muss EAN oder GTIN heißen.');
  eanIndex=0;
 }
 const lookup=k=>headerPresent?head.findIndex(x=>normalizedAliases[k].has(x)):-1;
 const indexes={ean:eanIndex,lagerplatz:lookup('lagerplatz'),information:lookup('information'),kommentar:lookup('kommentar')};
 const collected=new Map(),bad=[],duplicates=[];
 const dataRows=rows.slice(headerPresent?1:0);
 for(let i=0;i<dataRows.length;i++){
  const cells=dataRows[i],ean=v(cells[eanIndex]).replace(/\s/g,'');
  const n=i+1+(headerPresent?1:0);
  if(!goodEAN(ean)){bad.push({line:n,ean});continue;}
  if(collected.has(ean)){duplicates.push({line:n,ean});continue;}
  const entry={ean};
  for(const key of ['lagerplatz','information','kommentar']){
   if(indexes[key]<0)continue;
   const value=v(cells[indexes[key]]);
   const limit={lagerplatz:100,information:450,kommentar:600}[key];
   if(value.length>limit)throw Error('Zeile '+n+': '+key+' ist zu lang (maximal '+limit+' Zeichen).');
   if(value)entry[key]=value;
  }
  collected.set(ean,entry);
 }
 if(collected.size>5000)throw Error('Maximal 5.000 eindeutige EANs pro Datei.');
 return{delimiter,headerPresent,indexes,items:[...collected.values()],
  lineCount:dataRows.length,invalid:bad,duplicates};
}
root.HW24CsvCore=Object.freeze({csvParse,detectDelimiter,prepare,normalize});
const $=id=>document.getElementById(id),bridge=root.HW24ScanBridge,enrich=root.HW24Enrich;
if(!bridge||!enrich||!$('csvFile'))return;
let prepared=null,offset=0,running=false,stopping=false;
const preview=$('csvPreviewStatus'),progress=$('csvImportProgress');
function paint(message,isError=false){progress.textContent=message;progress.className='note '+(isError?'error':'success')}
function reset(){prepared=null;offset=0;stopping=false;$('csvImport').disabled=true;$('csvPause').disabled=true;$('csvColumns').replaceChildren();}
$('csvFile').addEventListener('change',reset);
$('csvEncoding').addEventListener('change',reset);
$('csvPreview').onclick=async()=>{
 if(running)return;
 reset();const file=$('csvFile').files?.[0];
 if(!file){preview.textContent='Bitte zuerst eine CSV-Datei auswählen.';return;}
 if(file.size>6*1024*1024){preview.textContent='Maximal 6 MB. Große CSV bitte aufteilen.';return;}
 preview.textContent='CSV wird lokal geprüft, noch keine Datenbankänderung.';
 try{
  const buffer=await file.arrayBuffer();
  const selection=$('csvEncoding').value;
  let charset=selection==='auto'?'utf-8':selection;
  let csvText;
  try{csvText=new TextDecoder(charset,{fatal:true}).decode(buffer)}
  catch(e){
   if(selection!=='auto')throw Error('Datei passt nicht zur gewählten Kodierung.');
   charset='windows-1252';csvText=new TextDecoder(charset).decode(buffer);
  }
  prepared=prepare(csvText,enrich.goodGtin);
  if(!prepared.items.length)throw Error('Keine gültigen EANs gefunden. Prüfe Prüfziffern und Spalten.');
  const columns=Object.entries(prepared.indexes).filter(([,index])=>index>=0)
    .map(([key])=>key).join(', ');
  preview.textContent=prepared.items.length+' eindeutige gültige EANs · '+
    prepared.duplicates.length+' CSV-Dubletten · '+prepared.invalid.length+
    ' ungültige EANs · '+prepared.lineCount+' Datenzeilen · '+charset+
    ' · Trennzeichen '+(prepared.delimiter==='\t'?'Tabulator':prepared.delimiter)+'.';
  const info=$('csvColumns');info.replaceChildren();
  const p=document.createElement('p');p.textContent='Erkannte Spalten: '+columns+
    '. Lagerplatz/Information/Kommentar werden als Importhinweise gespeichert, nicht in der Wawi-Anmerkung.';info.append(p);
  if(prepared.invalid.length){
   const bad=document.createElement('p');bad.className='warn';
   bad.textContent='Ungültige Zeilen (erste 10): '+prepared.invalid.slice(0,10)
    .map(x=>'Zeile '+x.line+': '+x.ean).join(', ')+
    '. Numerische EANs bitte in Excel als Text exportieren, nicht als wissenschaftliche Notation.';
   info.append(bad);
  }
  $('csvImport').disabled=false;
  paint('Vorschau bereit. Erst nach Bestätigung werden Daten in den aktiven Stapel importiert.');
 }catch(e){preview.textContent='CSV-Prüfung: '+String(e.message);preview.className='note error';reset()}
};
$('csvPause').onclick=()=>{stopping=true;paint('Import stoppt nach dem laufenden Block von höchstens 25 EANs.')};
$('csvImport').onclick=async()=>{
 if(running||!prepared?.items.length)return;
 const batch=Number(bridge.batchId());
 if(!Number.isSafeInteger(batch)||batch<1){paint('Bitte zunächst einen aktiven Scan-Stapel auswählen.',true);return;}
 if(offset===0&&!confirm(prepared.items.length+
  ' eindeutige EANs in Stapel '+batch+' übernehmen? Bereits vorhandene EANs werden nicht erneut als Scan gezählt.'))return;
 running=true;stopping=false;$('csvImport').disabled=true;$('csvPause').disabled=false;
 let newCount=0,previous=0,restored=0;
 while(offset<prepared.items.length&&!stopping){
  if(Number(bridge.batchId())!==batch){paint('Stapel gewechselt. Import gestoppt.',true);break;}
  const rows=prepared.items.slice(offset,offset+25);
  try{
   const result=await bridge.importRows(rows);
   newCount+=Number(result.inserted||0);previous+=Number(result.existed||0);
   restored+=Number(result.restored||0);
   offset+=rows.length;
   paint(offset+' / '+prepared.items.length+' EANs geprüft · '+newCount+
     ' neu · '+previous+' bereits vorhanden · '+restored+' wiederhergestellt.');
  }catch(e){
   paint('Import bei Zeile '+(offset+1)+' gestoppt: '+String(e.message)+
     '. Erneut starten setzt beim letzten bestätigten Block fort.',true);
   stopping=true;
  }
 }
 running=false;$('csvPause').disabled=true;
 $('csvImport').disabled=offset>=prepared.items.length;
 if(offset>0){
  try{await bridge.reloadItems()}catch(e){paint('Import gespeichert; Liste konnte nicht aktualisiert werden: '+e.message,true)}
 }
 if(offset>=prepared.items.length){
  paint('✓ CSV fertig verarbeitet. '+newCount+' neue Positionen, '+
   previous+' vorhandene, '+restored+' wieder aufgenommen. Quellenprüfung läuft in der Scanliste mit Cache und gedrosselten API-Anfragen.');
 }else if(stopping&&!progress.classList.contains('error'))paint('Import pausiert nach '+offset+' Positionen. Mit Import fortsetzen kannst Du weitermachen.');
};
})(window);
