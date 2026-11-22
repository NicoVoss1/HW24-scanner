#!/usr/bin/env bash
# HW24: exact final read-only Plesk cutover inventory. NEVER changes server.
# No passwords, .env contents, private key, git pull, migrations or vhost writes.
set -u
umask 077
site='test-artikelmanager.hairworld24.de'
old='/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de'
new='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform/public'
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
want='a26a64d09597e41523e224c557bb1d4e39bdf052'
issues=0
ok(){ printf 'OK: %s\n' "$*"; }
warn(){ printf 'PRUEFEN: %s\n' "$*"; issues=$((issues+1)); }

echo '=== HW24 PLESK CUTOVER: LETZTE BESTANDSAUFNAHME (NUR LESEN) ==='
date -u '+UTC %Y-%m-%d %H:%M:%S'
[ "$(id -u)" = 0 ] || { echo 'STOP: bitte als root in PuTTY';exit 2; }
for cmd in plesk python3 curl runuser git; do
 command -v "$cmd" >/dev/null || { echo "STOP: $cmd fehlt";exit 2; }
done
test -d "$old" && test -d "$new" || { echo 'STOP: alter oder neuer Webroot fehlt';exit 2; }
test ! -L "$old" && test ! -L "$new" || { echo 'STOP: Webroot ist ein Symlink';exit 2; }
test -f "$new/index.php" && test -f "$new/.htaccess" || {
 echo 'STOP: Laravel-Frontcontroller oder Apache-Rewrite-Datei fehlt';exit 2;
}
echo '1. Bestätigter alter Root und Plesk-Domaintyp'
root="$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null)" || root=''
if [ "$root" = "$old" ];then ok 'Alte Testdomain weiterhin unveraendert';else warn 'Plesk-DocumentRoot nicht exakt der erwartete alte Pfad';fi
kind='unbekannt'
if plesk bin subdomain --info "$site" >/dev/null 2>&1;then
 kind='subdomain'
 ok 'Plesk-Domaintyp: subdomain'
elif plesk bin domain --info "$site" >/dev/null 2>&1;then
 kind='domain'
 ok 'Plesk-Domaintyp: eigenstaendige Domain'
else
 warn 'Keine eindeutige Plesk-CLI-Zuordnung zu Subdomain/Domain'
fi
echo "Plesk-Typ: $kind"
echo 'Neue, noch NICHT aktivierte Pfadangabe: hw24-console-private/repo/platform/public'

echo '2. Basic-Auth im bisherigen Root oder zentralen vHost'
python3 - "$old" "$new" "$site" <<'PY'
from pathlib import Path
import sys,re
old,new=map(Path,sys.argv[1:3])
site=sys.argv[3]
for label,p in (('ALT',old),('NEU',new)):
    ht=p/'.htaccess'
    text=ht.read_text(errors='replace') if ht.is_file() else ''
    basic=bool(re.search(r'(?im)^\s*(?:AuthType\s+Basic|AuthUserFile\s+|Require\s+valid-user)',text))
    print(f'{label}: .htaccess vorhanden={ht.is_file()}, Basic-Auth-Direktive={basic}')
d=Path('/var/www/vhosts/system')/site/'conf'
names=['nginx.conf','httpd.conf','vhost_nginx.conf','vhost.conf','last_nginx.conf','last_httpd.conf']
found=[]
auth=[]
handler=[]
for name in names:
    f=d/name
    if not f.is_file():continue
    found.append(name)
    try: text=f.read_text(errors='replace')
    except OSError: continue
    if re.search(r'(?im)^\s*(?:auth_basic\s+(?!off\b)|AuthType\s+Basic\b|AuthUserFile\s+)',text):
        auth.append(name)
    if 'php-fpm' in text.lower() or 'proxy:unix:' in text.lower():
        handler.append(name)
print('Plesk-vHost-Konfigurationen gefunden:',len(found))
print('Zentraler Basic-Auth-Hinweis:',','.join(auth) if auth else 'keiner')
print('PHP-FPM-Hinweis:',','.join(handler) if handler else 'keiner')
print('Die Herkunft der 401 muss vor dem Cutover geklärt sein.')
PY
if [ -f "$old/.htaccess" ] &&
   grep -Eiq '^[[:space:]]*(AuthType[[:space:]]+Basic|AuthUserFile[[:space:]]+|Require[[:space:]]+valid-user)' "$old/.htaccess";then
 warn 'Alte .htaccess besitzt Basic-Auth; neuer Laravel-Root erbt sie nicht sicher'
fi

echo '3. Plesk-Git-Ziel vom Framework trennen'
if info="$(plesk ext git --info -domain "$site" -name HW24-scanner.git 2>/dev/null)";then
 summary="$(printf '%s\n' "$info" | python3 -c '
import re,sys
text=sys.stdin.read()
mode=re.search(r"(?im)^\s*(?:Deployment\s*(?:mode|type)|Deploy\s*(?:mode|type)|Mode)\s*:\s*(.+)$",text)
path=re.search(r"(?im)^\s*(?:Server\s+path|Deployment\s+(?:path|directory)|Deploy(?:ment)?\s+directory)\s*:\s*(.+)$",text)
print("Mode="+(mode.group(1).strip().lower() if mode else "unknown"))
if path:
    p=path.group(1).strip()
    print("PathVerdict="+("PRIVATE_UNSAFE" if "hw24-console-private" in p or "/platform/" in p else "NO_PRIVATE_PATH"))
else:
    print("PathVerdict=UNKNOWN")
')"
 echo "$summary"
 case "$summary" in *'Mode=manual'*) ok 'Plesk-Git bleibt manuell' ;; *) warn 'Plesk-Git-Modus nicht eindeutig manuell' ;; esac
 case "$summary" in *'PathVerdict=PRIVATE_UNSAFE'*) warn 'Plesk-Git-Ziel ist das private Framework' ;; *'PathVerdict=UNKNOWN'*) warn 'Plesk-Git-Zielpfad im Panel vor Cutover pruefen' ;; *) ok 'Kein privater Git-Zielpfad in den Metadaten erkannt' ;; esac
else
 warn 'Plesk-Git-Einstellungen sind nicht lesbar'
fi

echo '4. Privater Laravel-Stand und minimale Datei-Zugriffsrechte'
head="$(runuser -u nicovoss -- git -C "$repo" rev-parse HEAD 2>/dev/null)" || head=''
[ "$head" = "$want" ] && ok 'Privater Framework-HEAD gepinnt' || warn 'Framework-HEAD unerwartet'
for f in "$new/index.php" "$new/css/filament/filament/app.css" "$new/js/filament/filament/app.js";do
 [ -f "$f" ] && [ ! -L "$f" ] || warn "Public-Datei fehlt/Symlink: $f"
done
for u in nginx www-data;do
 if runuser -u "$u" -- test -r "$new/css/filament/filament/app.css" &&
    runuser -u "$u" -- test -r "$new/js/filament/filament/app.js";then
  ok "$u kann Filament-Assets lesen"
 else warn "$u kann Filament-Assets nicht lesen";fi
 for private in "$repo/platform/.env" "$repo/platform/database/database.sqlite";do
  if runuser -u "$u" -- test -r "$private";then
    warn "Privater Inhalt fuer Webserver-Nutzer $u lesbar";fi
 done
done
echo '5. Bestehende Testdomain: Schutz auf drei URLs ohne Anmeldung'
for path in '/' '/admin/login' '/api/jtl.php?ean=3474630337398';do
 status="$(curl --silent --show-error --max-time 10 --proto '=https' \
   --output /dev/null --write-out '%{http_code}' "https://$site$path" 2>/dev/null)" || status='transport_error'
 echo "Alter Testroot $path: HTTP $status"
 if [ "$status" != 401 ] && [ "$status" != 403 ];then
  warn 'Bisherige URL nicht einheitlich durch Basic-Auth geschuetzt'
 fi
done
echo "=== NUR-LESEN-INVENTUR: $issues Warnung(en) ==="
echo 'Keine Plesk-/Domain-/Git-/Laravel-Aenderung ausgefuehrt.'
echo 'Vor Umschalten Auth-Quelle, Plesk-Typ, Git-Deploy-Ziel und Rollback festhalten.'
