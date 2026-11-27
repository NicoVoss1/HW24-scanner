#!/usr/bin/env python3
"""Archive an original JTL Ameise CSV as a private versioned reference.
No JTL writes, no external API, no automatic approval of historical values.
All original bytes and row relationships are retained, including duplicate article IDs.
"""
import argparse
import collections
import csv
import datetime
import gzip
import hashlib
import io
import json
import os
from pathlib import Path
import sqlite3

def parse(raw):
    if not raw or len(raw)>100*1024*1024:
        raise ValueError("CSV size outside allowed range")
    csv.field_size_limit(2*1024*1024)
    reader=csv.reader(io.StringIO(raw.decode("utf-8-sig"),newline=""),delimiter=";",strict=True)
    headers=next(reader)
    if not 3<=len(headers)<=3000 or len(set(headers))!=len(headers):
        raise ValueError("Invalid or ambiguous export headers")
    for field in ("Interner Schlüssel","Artikelnummer","GTIN"):
        if field not in headers:
            raise ValueError("Required identity column absent: "+field)
    ix={h:i for i,h in enumerate(headers)}
    counts=[0]*len(headers)
    rows=[]
    for number,row in enumerate(reader,1):
        if len(row)!=len(headers):
            raise ValueError("Unexpected width at record "+str(number))
        if not any(row):
            continue
        aid=row[ix["Interner Schlüssel"]]
        sku=row[ix["Artikelnummer"]]
        if not aid.isascii() or not aid.isdigit() or int(aid)<1 or not sku.strip():
            raise ValueError("Missing or invalid article identity at record "+str(number))
        values={}
        for i,value in enumerate(row):
            if value!="":
                values[str(i+1)]=value
                if value.strip():
                    counts[i]+=1
        rows.append((number,aid,sku,row[ix["GTIN"]],json.dumps(values,ensure_ascii=False,separators=(",",":"))))
        if len(rows)>200000:
            raise ValueError("Record limit exceeded")
    if not rows:
        raise ValueError("Empty export")
    return headers,counts,rows

def archive(db,raw,export_date):
    headers,counts,rows=parse(raw)
    digest=hashlib.sha256(raw).hexdigest()
    db.execute("PRAGMA foreign_keys=ON")
    db.execute("PRAGMA busy_timeout=10000")
    db.executescript("""
      CREATE TABLE IF NOT EXISTS ameise_exports(
        id INTEGER PRIMARY KEY, sha256 TEXT NOT NULL UNIQUE,
        export_date TEXT NOT NULL, imported_utc TEXT NOT NULL,
        row_count INTEGER NOT NULL, article_count INTEGER NOT NULL,
        original_gzip BLOB NOT NULL);
      CREATE TABLE IF NOT EXISTS ameise_columns(
        export_id INTEGER NOT NULL REFERENCES ameise_exports(id),
        position INTEGER NOT NULL, name TEXT NOT NULL, filled_count INTEGER NOT NULL,
        PRIMARY KEY(export_id,position));
      CREATE TABLE IF NOT EXISTS ameise_rows(
        export_id INTEGER NOT NULL REFERENCES ameise_exports(id),
        row_number INTEGER NOT NULL, article_id TEXT NOT NULL,
        article_number TEXT NOT NULL, gtin TEXT NOT NULL, values_json TEXT NOT NULL,
        PRIMARY KEY(export_id,row_number));
      CREATE INDEX IF NOT EXISTS ameise_rows_article ON ameise_rows(export_id,article_id);
      CREATE INDEX IF NOT EXISTS ameise_rows_gtin ON ameise_rows(export_id,gtin);
      CREATE INDEX IF NOT EXISTS ameise_rows_sku ON ameise_rows(export_id,article_number);
    """)
    ids=collections.Counter(r[1] for r in rows)
    stats=dict(records=len(rows),articles=len(ids),columns=len(headers),
               populated_columns=sum(c>0 for c in counts),
               fully_populated_columns=sum(c==len(rows) for c in counts),
               empty_columns=sum(c==0 for c in counts),
               populated_values=sum(counts),
               multirow_articles=sum(c>1 for c in ids.values()))
    with db:
        db.execute("BEGIN IMMEDIATE")
        seen=db.execute("SELECT id,export_date FROM ameise_exports WHERE sha256=?",(digest,)).fetchone()
        if seen:
            if seen[1]!=export_date:
                raise ValueError("Identical file already archived with different declared date")
            return dict(status="already_archived",export_id=seen[0],**stats)
        cur=db.execute("INSERT INTO ameise_exports(sha256,export_date,imported_utc,row_count,article_count,original_gzip) VALUES(?,?,?,?,?,?)",
          (digest,export_date,datetime.datetime.now(datetime.timezone.utc).isoformat(),len(rows),len(ids),gzip.compress(raw,mtime=0)))
        eid=cur.lastrowid
        db.executemany("INSERT INTO ameise_columns VALUES(?,?,?,?)",((eid,i+1,h,c) for i,(h,c) in enumerate(zip(headers,counts))))
        db.executemany("INSERT INTO ameise_rows VALUES(?,?,?,?,?,?)",((eid,*r) for r in rows))
        if db.execute("SELECT COUNT(*) FROM ameise_rows WHERE export_id=?",(eid,)).fetchone()[0]!=len(rows):
            raise RuntimeError("Archive verification failed")
        return dict(status="archived",export_id=eid,**stats)

def main():
    p=argparse.ArgumentParser()
    p.add_argument("csv_file",type=Path)
    p.add_argument("--export-date",required=True)
    p.add_argument("--preview",action="store_true")
    p.add_argument("--confirm",action="store_true")
    a=p.parse_args()
    date=datetime.date.fromisoformat(a.export_date).isoformat()
    raw=a.csv_file.read_bytes()
    if a.preview:
        db=sqlite3.connect(":memory:")
    else:
        if not a.confirm:
            p.error("--confirm required for persistent import")
        os.umask(0o077)
        directory=Path("/var/www/vhosts/hairworld24.de/hw24-console-private/ameise-reference")
        path=directory/"reference.sqlite"
        if directory.is_symlink() or not directory.is_dir() or directory.stat().st_mode&0o077 or path.is_symlink() or (path.exists() and path.stat().st_mode&0o077):
            raise RuntimeError("Private archive paths not verified")
        db=sqlite3.connect(path)
    try:
        result=archive(db,raw,date)
        if db.execute("PRAGMA quick_check").fetchall()!=[("ok",)]:
            raise RuntimeError("SQLite integrity check failed")
        print(json.dumps(result,ensure_ascii=False))
    finally:
        db.close()

if __name__=="__main__":
    main()
