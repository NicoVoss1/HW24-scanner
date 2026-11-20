const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const context={window:{},document:{getElementById:()=>null}};
vm.runInNewContext(fs.readFileSync('assets/hw24-csv-intake.js','utf8'),context);
const C=context.window.HW24CsvCore;
assert(C);
const gtin=(body)=>{
 let sum=0,w=3;for(let i=body.length-1;i>=0;i--){sum+=Number(body[i])*w;w=w===3?1:3}
 return body+(10-sum%10)%10;
};
const good=s=>/^\d{13}$/.test(s)&&s===gtin(s.slice(0,-1));
const id=gtin('800000000001'),id2=gtin('800000000002');
const data='EAN;Lagerplatz;Information;Kommentar\r\n'+
  id+';01-002;"Color;Mix";"Notiz ""Test"""\r\n'+
  id+';01-002;;Doppelt\r\n'+id2+';01-003;Info;Kommentar';
const result=C.prepare(data,good);
assert.equal(result.items.length,2);
assert.equal(result.duplicates.length,1);
assert.equal(result.items[0].information,'Color;Mix');
assert.equal(result.items[0].kommentar,'Notiz "Test"');
assert.equal(result.items[0].lagerplatz,'01-002');
const unicode=C.prepare('\uFEFFGTIN,Information\n'+id+',"Grün, groß"',good);
assert.equal(unicode.items[0].information,'Grün, groß');
assert.equal(C.csvParse('EAN\tKommentar\n'+id+'\t"Zeile1\nZeile2"', '\t')[1][1],'Zeile1\nZeile2');
const rows=Array.from({length:1500},(_,n)=>gtin(String(800000000000+n).slice(0,12)));
const many=C.prepare('EAN;Kommentar\n'+rows.map(id=>id+';Kommt ins Lager').join('\n'),good);
assert.equal(many.items.length,1500);
assert.equal(many.invalid.length,0);
assert.throws(()=>C.prepare('Artikelnummer;Kommentar\nAR-001;Info',good),/EAN/);
console.log('CSV core: 1500 EANs, quoting, duplicates, comments and validation PASS');
