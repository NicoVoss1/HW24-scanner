#!/usr/bin/env bash
# Private operator-assisted import. No JTL, WMS or provider writes.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 && "$#" == 1 && "$1" == '--confirm' ]] || { echo 'STOP: root and --confirm required'; exit 1; }
private='/var/www/vhosts/hairworld24.de/hw24-console-private'
repo="$private/repo"
inbox="$private/reference-inbox"
csv="$inbox/ameise.csv"
xlsx="$inbox/historisch.xlsx"
ref='7795b61d0888db5b8e808a36df3d9bfbf7f6b0c0'
g(){ runuser -u nicovoss -- git -C "$repo" "$@"; }
install -d -o nicovoss -g psacln -m 700 "$inbox" "$private/ameise-reference" "$private/historical-reference"
for file in "$csv" "$xlsx"; do
    [[ -f "$file" && ! -L "$file" ]] || { echo "STOP: Originaldatei fehlt: $file"; exit 1; }
    runuser -u nicovoss -- test -r "$file"
done
exec 9>/run/lock/hw24-reference-import.lock
flock -w 20 9
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
chmod 755 "$tmp"
for name in import-ameise-reference.py import-historical-workbook.py; do
    g show "$ref:platform/ops/$name" > "$tmp/$name"
    chmod 644 "$tmp/$name"
    python3 -m py_compile "$tmp/$name"
done
echo '=== ORIGINALDATEIEN VORSCHAU ==='
sha256sum "$csv" "$xlsx"
runuser -u nicovoss -- python3 "$tmp/import-ameise-reference.py" "$csv" --export-date 2026-11-26 --preview
runuser -u nicovoss -- python3 "$tmp/import-historical-workbook.py" "$xlsx" --preview
echo '=== PRIVATE ARCHIVE IMPORTIEREN ==='
runuser -u nicovoss -- python3 "$tmp/import-ameise-reference.py" "$csv" --export-date 2026-11-26 --confirm
runuser -u nicovoss -- python3 "$tmp/import-historical-workbook.py" "$xlsx" --confirm
runuser -u nicovoss -- python3 - "$private" <<'PY'
import sqlite3,sys
from pathlib import Path
p=Path(sys.argv[1])
for d,table in [('ameise-reference','ameise_exports'),('historical-reference','workbooks')]:
    with sqlite3.connect(f"file:{p/d/'reference.sqlite'}?mode=ro",uri=True) as db:
        assert db.execute('PRAGMA quick_check').fetchall()==[('ok',)]
        print(d, 'Archivversionen:',db.execute('SELECT COUNT(*) FROM '+table).fetchone()[0])
PY
echo '=== HW24 PRIVATE REFERENZARCHIVE IMPORTIERT ==='
