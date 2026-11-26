#!/usr/bin/env python3
"""Archive original XLSX privately; index selected operational worksheets without running formulas."""
import argparse, datetime, hashlib, io, json, os, re, sqlite3, zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

N="{http://schemas.openxmlformats.org/spreadsheetml/2006/main}"
REL="{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id"
SHEETS={"JTL-Import aus AB 1Produkte_ALL":(4,68),"Artikel_im_Lager_nicht_angelegt":(2,14)}
def colnum(ref):
    c=0
    for ch in re.match(r"^[A-Z]+",ref).group(): c=c*26+ord(ch)-64
    return c
def parse(raw):
    with zipfile.ZipFile(io.BytesIO(raw)) as z:
        if sum(x.file_size for x in z.infolist())>900_000_000: raise ValueError("Workbook unpacked size limit")
        strings=[]
        if "xl/sharedStrings.xml" in z.namelist():
            strings=["".join(t.text or "" for t in s.iter(N+"t")) for s in ET.fromstring(z.read("xl/sharedStrings.xml"))]
        wb=ET.fromstring(z.read("xl/workbook.xml"))
        rels={r.attrib["Id"]:r.attrib["Target"] for r in ET.fromstring(z.read("xl/_rels/workbook.xml.rels"))}
        inventory=[dict(s.attrib) for s in wb.find(N+"sheets")]
        for s in wb.find(N+"sheets"):
            name=s.attrib["name"]
            if name not in SHEETS: continue
            hr,width=SHEETS[name]; target=rels[s.attrib[REL]]
            target=target.lstrip("/") if target.startswith("/") else "xl/"+target
            if not re.fullmatch(r"xl/worksheets/sheet[0-9]+\.xml",target): raise ValueError("Unexpected worksheet target")
            root=ET.fromstring(z.read(target))
            headers={}; rows=[]; top=[]; counts=[0]*width
            for row in root.find(N+"sheetData"):
                rn=int(row.attrib["r"]); values={}; styles={}; formulas={}
                for c in row.findall(N+"c"):
                    pos=colnum(c.attrib["r"])
                    if pos>width: raise ValueError("Unexpected populated worksheet width")
                    key=str(pos); typ=c.attrib.get("t"); v=c.findtext(N+"v")
                    if typ=="s" and v is not None: v=strings[int(v)]
                    elif typ=="inlineStr": v="".join(t.text or "" for t in c.iter(N+"t"))
                    if v is not None and v!="": values[key]=v
                    if c.attrib.get("s"): styles[key]=c.attrib["s"]
                    f=c.find(N+"f")
                    if f is not None: formulas[key]={"text":f.text,"attributes":dict(f.attrib)}
                if rn<=hr:
                    top.append({"row":rn,"values":values,"styles":styles,"formulas":formulas})
                    if rn==hr: headers=values
                    continue
                if not values and not formulas: continue
                for k,v in values.items():
                    if v.strip(): counts[int(k)-1]+=1
                status="LP geb." if values.get("63")=="LP geb." else ("WE geb." if values.get("62")=="WE geb." else "")
                # Excel columns BJ=62, BK=63. No execution of workbook formulas.
                rows.append((rn,json.dumps(values,ensure_ascii=False),json.dumps(styles),json.dumps(formulas,ensure_ascii=False),status))
            if not headers or not rows: raise ValueError("Expected worksheet data missing")
            meta={"header_row":hr,"width":width,"top_rows":top,"inventory":inventory,
                "columns_xml":[ET.tostring(x,encoding="unicode") for x in root.findall(N+"cols")],
                "conditional_formatting":[ET.tostring(x,encoding="unicode") for x in root.findall(N+"conditionalFormatting")],
                "views":[ET.tostring(x,encoding="unicode") for x in root.findall(N+"sheetViews")]}
            yield name,headers,counts,rows,meta

def archive(db,raw):
    digest=hashlib.sha256(raw).hexdigest()
    db.executescript("""
    PRAGMA foreign_keys=ON;
    CREATE TABLE IF NOT EXISTS workbooks(id INTEGER PRIMARY KEY, sha256 TEXT UNIQUE NOT NULL, imported_utc TEXT NOT NULL, original_xlsx BLOB NOT NULL);
    CREATE TABLE IF NOT EXISTS workbook_sheets(id INTEGER PRIMARY KEY, workbook_id INTEGER NOT NULL REFERENCES workbooks(id), name TEXT NOT NULL, metadata_json TEXT NOT NULL, row_count INTEGER NOT NULL, UNIQUE(workbook_id,name));
    CREATE TABLE IF NOT EXISTS workbook_columns(sheet_id INTEGER NOT NULL REFERENCES workbook_sheets(id), position INTEGER NOT NULL, name TEXT NOT NULL, filled_count INTEGER NOT NULL, PRIMARY KEY(sheet_id,position));
    CREATE TABLE IF NOT EXISTS workbook_rows(sheet_id INTEGER NOT NULL REFERENCES workbook_sheets(id), row_number INTEGER NOT NULL, values_json TEXT NOT NULL, styles_json TEXT NOT NULL, formulas_json TEXT NOT NULL, status TEXT NOT NULL, PRIMARY KEY(sheet_id,row_number));
    """)
    with db:
        db.execute("BEGIN IMMEDIATE")
        seen=db.execute("SELECT id FROM workbooks WHERE sha256=?",(digest,)).fetchone()
        if seen:return {"status":"already_archived","id":seen[0]}
        wid=db.execute("INSERT INTO workbooks(sha256,imported_utc,original_xlsx) VALUES(?,?,?)",(digest,datetime.datetime.now(datetime.timezone.utc).isoformat(),raw)).lastrowid
        stats={}
        for name,headers,counts,rows,meta in parse(raw):
            sid=db.execute("INSERT INTO workbook_sheets(workbook_id,name,metadata_json,row_count) VALUES(?,?,?,?)",(wid,name,json.dumps(meta,ensure_ascii=False),len(rows))).lastrowid
            db.executemany("INSERT INTO workbook_columns VALUES(?,?,?,?)",((sid,i,headers.get(str(i),""),c) for i,c in enumerate(counts,1)))
            db.executemany("INSERT INTO workbook_rows VALUES(?,?,?,?,?,?)",((sid,*r) for r in rows))
            stats[name]={"rows":len(rows),"columns":len(counts)}
        if set(stats)!=set(SHEETS):raise ValueError("Expected worksheet names missing")
        return {"status":"archived","id":wid,"sheets":stats}
def main():
    p=argparse.ArgumentParser()
    p.add_argument("xlsx",type=Path);p.add_argument("--preview",action="store_true");p.add_argument("--confirm",action="store_true")
    a=p.parse_args()
    if a.xlsx.stat().st_size>100_000_000:raise ValueError("Workbook size limit")
    if a.preview:db=sqlite3.connect(":memory:")
    else:
        if not a.confirm:p.error("--confirm required")
        os.umask(0o077)
        d=Path("/var/www/vhosts/hairworld24.de/hw24-console-private/historical-reference");path=d/"reference.sqlite"
        if d.is_symlink() or not d.is_dir() or d.stat().st_mode&0o077 or path.is_symlink() or (path.exists() and path.stat().st_mode&0o077):raise ValueError("Private path not verified")
        db=sqlite3.connect(path)
    try:print(json.dumps(archive(db,a.xlsx.read_bytes()),ensure_ascii=False))
    finally:db.close()
if __name__=="__main__":main()
