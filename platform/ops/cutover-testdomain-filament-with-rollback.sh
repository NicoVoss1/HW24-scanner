#!/usr/bin/env bash
# HW24: ONE TEST-subdomain DocumentRoot change with automatic rollback.
# ONLY test-artikelmanager.hairworld24.de is eligible; LIVE is untouched.
# No Git deploy, composer, migrations, shell password, DB or webroot file edits.
set -Eeuo pipefail
umask 077
site='test-artikelmanager.hairworld24.de'
sub='test-artikelmanager'
parent='hairworld24.de'
base='/var/www/vhosts/hairworld24.de'
old_rel='test-artikelmanager.HAIRWORLD24.de'
new_rel='hw24-console-private/repo/platform/public'
old="$base/$old_rel"
new="$base/$new_rel"
repo="$base/hw24-console-private/repo"
app="$repo/platform"
expected='a26a64d09597e41523e224c557bb1d4e39bdf052'
backup_dir='/root/hw24-backups'
switched=0
rollback=''
log=''
get_root(){ plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null; }
restore(){
 rc=$?
 trap - EXIT
 if [ "$switched" -eq 1 ];then
  echo 'FEHLER NACH UMSCHALTUNG: Testdomain wird automatisch zurueckgestellt.'
  if plesk bin subdomain --update "$sub" -domain "$parent" -www-root "$old_rel" >>"$log" 2>&1 &&
     [ "$(get_root)" = "$old" ];then
   echo 'ROLLBACK OK: Bisheriger Test-DocumentRoot wiederhergestellt.'
  else
   echo 'ALARM: Ruecknahme nicht bestaetigt. Root-privates Log und Rollback-Skript verwenden.'
   echo "Rollback-Skript: $rollback"
  fi
 fi
 exit "$rc"
}
trap restore EXIT
echo '=== HW24 TEST-SUBDOMAIN: GESICHERTER FILAMENT-CUTOVER ==='
[ "$(id -u)" -eq 0 ] || { echo 'STOP: Root-PuTTY erforderlich'; exit 2; }
for bin in plesk python3 curl git runuser;do command -v "$bin" >/dev/null;done
[ "$(get_root)" = "$old" ] || { echo 'STOP: Testdomain hat bereits einen anderen Root';exit 2; }
for d in "$old" "$base/hw24-console-private" "$repo" "$app" "$new";do
 [ -d "$d" ] && [ ! -L "$d" ] || { echo "STOP: fehlender/Symlink-Verzeichnispfad: $d";exit 2; }
done
[ -f "$new/index.php" ] && [ -f "$new/.htaccess" ] &&
[ ! -L "$new/index.php" ] && [ ! -L "$new/.htaccess" ] || {
 echo 'STOP: Laravel-Frontcontroller bzw. Rewrite-Datei fehlt';exit 2;
}
[ -f "$old/api/amazon-asin.php" ] && [ -f "$old/index.html" ] || {
 echo 'STOP: Rueckfall-Legacy-Testdateien unvollstaendig';exit 2;
}
for f in "$app/.env" "$app/database/database.sqlite" "$app/vendor/autoload.php";do
 [ -f "$f" ] && [ ! -L "$f" ] || { echo 'STOP: Private Laravel-Datei fehlt/Symlink';exit 2; }
done
[ "$(runuser -u nicovoss -- git -C "$repo" rev-parse HEAD)" = "$expected" ] ||
 { echo 'STOP: Framework-Version weicht vom getesteten Commit ab';exit 2; }
[ -z "$(runuser -u nicovoss -- git -C "$repo" status --porcelain --untracked-files=no)" ] ||
 { echo 'STOP: Getrackte Framework-Dateien veraendert';exit 2; }
plesk bin subdomain --help | grep -q -- '-www-root' || {
 echo 'STOP: DocumentRoot-Option von Plesk nicht nachgewiesen';exit 2;
}
plesk bin subdomain --info "$site" >/dev/null 2>&1 || {
 echo 'STOP: Testdomain ist nicht die erwartete Plesk-Subdomain';exit 2;
}
echo '1. Urspruengliche HTTPS-Absicherung pruefen'
for path in '/' '/admin/login' '/api/jtl.php?ean=3474630337398';do
 code="$(curl -fsS --max-time 12 --proto '=https' --output /dev/null --write-out '%{http_code}' \
  "https://$site$path" 2>/dev/null)" || code="$(curl -sS --max-time 12 --proto '=https' --output /dev/null --write-out '%{http_code}' "https://$site$path" 2>/dev/null || true)"
 [ "$code" = '401' ] || [ "$code" = '403' ] || {
  echo "STOP: bisherige Test-URL nicht geschuetzt: $path (HTTP $code)";exit 2;
 }
done
echo 'OK: alter Test-Webroot dreifach vor unberechtigtem Zugriff geschuetzt'
echo '2. Plesk-Git darf nicht automatisch in das Framework bereitstellen'
info="$(plesk ext git --info -domain "$site" -name HW24-scanner.git 2>/dev/null)" || {
 echo 'STOP: Plesk-Git-Zustand nicht lesbar';exit 2;
}
printf '%s\n' "$info" | python3 -c '
import re,sys
x=sys.stdin.read()
m=re.search(r"(?im)^\s*(?:Deployment\s*(?:mode|type)|Deploy\s*(?:mode|type)|Mode)\s*:\s*(.+)$",x)
p=re.search(r"(?im)^\s*(?:Server\s+path|Deployment\s+(?:path|directory)|Deploy(?:ment)?\s+directory)\s*:\s*(.+)$",x)
if not m or m.group(1).strip().lower()!="manual":
 sys.exit(2)
if not p or "hw24-console-private" in p.group(1) or "/platform/" in p.group(1):
 sys.exit(3)
' || { echo 'STOP: Plesk-Git-Deploy-Modus/Ziel nicht eindeutig sicher';exit 2; }
echo 'OK: Manuelles Plesk-Git mit separatem Ziel'
echo '3. Laravel-Konfigurations- und Authentifizierungsbereitschaft pruefen'
runuser -u nicovoss -- /opt/plesk/php/8.5/bin/php -r '
chdir($argv[1]);require "vendor/autoload.php";
$a=require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ok=config("app.env")==="production" &&
 config("app.debug")===false &&
 rtrim((string)config("app.url"),"/")==="https://test-artikelmanager.hairworld24.de" &&
 config("session.secure")===true &&
 config("database.default")==="sqlite" &&
 realpath((string)Illuminate\Support\Facades\DB::connection()->getDatabaseName())===
 realpath("database/database.sqlite") &&
 config("hw24_legacy.base_url")==="https://artikelmanager.hairworld24.de" &&
 is_string(config("hw24_legacy.user")) && (string)config("hw24_legacy.user")!=="" &&
 is_string(config("hw24_legacy.password")) && (string)config("hw24_legacy.password")!=="" &&
 filter_var(config("hw24_legacy.admin_email"),FILTER_VALIDATE_EMAIL)!==false &&
 App\Models\User::query()->where("email",config("hw24_legacy.admin_email"))->exists();
exit($ok?0:2);
' "$app" 2>/dev/null || { echo 'STOP: sichere Laravel-/Admin-Konfiguration nicht bestaetigt';exit 2; }
echo 'OK: Laravel production, Admin-Identitaet, HTTPS-Cookie, private DB und Live-Bridge'
for u in nginx www-data;do
 runuser -u "$u" -- test -r "$new/css/filament/filament/app.css" &&
 runuser -u "$u" -- test -r "$new/js/filament/filament/app.js" || {
  echo "STOP: Filament-Assets fuer $u nicht lesbar";exit 2;
 }
 for file in "$app/.env" "$app/database/database.sqlite";do
  if runuser -u "$u" -- test -r "$file";then
   echo 'STOP: private Datei waere ueber Webserver-Benutzer lesbar';exit 2;
  fi
 done
done
echo 'OK: Statische Assets lesbar, private Dateien weiterhin gesperrt'
echo '4. Unabhaengige Rueckfall-Prozedur vor der Umschaltung speichern'
install -d -m 700 "$backup_dir"
rollback="$(mktemp "$backup_dir/hw24-testdomain-rollback-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.sh")"
log="$(mktemp "$backup_dir/hw24-testdomain-plesk-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.log")"
chmod 700 "$rollback"
chmod 600 "$log"
cat >"$rollback" <<'RESTORE'
#!/usr/bin/env bash
set -euo pipefail
[ "$(id -u)" = 0 ] || { echo 'STOP: Root erforderlich';exit 2; }
old='/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de'
new='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform/public'
now="$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'")"
if [ "$now" = "$old" ];then echo 'Testdomain ist bereits auf dem alten Stand.';exit 0;fi
[ "$now" = "$new" ] || { echo 'STOP: unerwarteter Test-DocumentRoot; keine Aenderung';exit 2; }
[ -f "$old/index.html" ] && [ -f "$old/api/amazon-asin.php" ] || {
 echo 'STOP: bisheriger Test-Webroot fehlt';exit 2;
}
plesk bin subdomain --update test-artikelmanager -domain hairworld24.de -www-root test-artikelmanager.HAIRWORLD24.de
after="$(plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'")"
[ "$after" = "$old" ] || { echo 'STOP: Rueckfall noch nicht bestaetigt';exit 2; }
echo 'ROLLBACK OK: Alte TEST-Website wieder als DocumentRoot aktiv.'
echo 'Bitte HTTPS-Basic-Auth und Browser pruefen; LIVE-Website ist unveraendert.'
RESTORE
bash -n "$rollback"
echo "Root-privates Rollback-Skript: $rollback"
echo "Root-privates Plesk-Protokoll: $log"
echo '5. Ausschliesslich Test-DocumentRoot auf Laravel/public umstellen'
switched=1
plesk bin subdomain --update "$sub" -domain "$parent" -www-root "$new_rel" >"$log" 2>&1
[ "$(get_root)" = "$new" ] || {
 echo 'STOP: Plesk meldet nicht den erwarteten Laravel-DocumentRoot';exit 1;
}
echo '6. HTTPS-/Basic-Auth-/Filament-/Dateisicherheitsproben'
# Python retrieves existing Basic credentials from private Laravel config into
# process memory. It does not print or pass the secret via command-line arguments,
# follow redirects, or submit any login form.
python3 - "$app" "$site" <<'PY'
import base64,json,ssl,subprocess,sys,time
from urllib.request import build_opener,Request,HTTPSHandler,HTTPRedirectHandler
from urllib.error import HTTPError,URLError
app,host=sys.argv[1:]
if host!='test-artikelmanager.hairworld24.de':raise SystemExit('Unexpected host')
php=r"""
chdir($argv[1]);require "vendor/autoload.php";
$a=require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u=(string)config("hw24_legacy.user");
$p=(string)config("hw24_legacy.password");
if(!$u||!$p||config("hw24_legacy.base_url")!=="https://artikelmanager.hairworld24.de")exit(3);
echo json_encode(["user"=>$u,"password"=>$p],JSON_THROW_ON_ERROR);
"""
auth=subprocess.run(
    ['runuser','-u','nicovoss','--','/opt/plesk/php/8.5/bin/php','-r',php,app],
    capture_output=True,check=False,timeout=18,
)
if auth.returncode:
    raise SystemExit('Private Basic-Credentials not readable')
values=json.loads(auth.stdout)
header='Basic '+base64.b64encode(
    (values['user']+':'+values['password']).encode('utf-8')).decode('ascii')
class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self,req,fp,code,msg,headers,newurl):return None
opener=build_opener(HTTPSHandler(context=ssl.create_default_context()),NoRedirect)
def probe(path,authorized=False,limit=700000):
    req=Request('https://'+host+path,method='GET',
       headers=({'Authorization':header,'Accept':'text/html'} if authorized else {}))
    try:
        with opener.open(req,timeout=10) as res:
            return res.status,res.read(limit+1)
    except HTTPError as err:
        return err.code,b''
def assert_status(path,expected,authorized=False):
    code,body=probe(path,authorized)
    if code not in expected:raise RuntimeError(path+' HTTP '+str(code))
    return code,body
# If auth was lost during Plesk reconfiguration, auto-rollback.
for trial in range(10):
    try:
        assert_status('/admin/login',{401,403},False)
        code,body=assert_status('/admin/login',{200},True)
        if b'filament' not in body.lower():raise RuntimeError('Filament login HTML not detected')
        for path in ['/css/filament/filament/app.css','/js/filament/filament/app.js']:
            code,data=assert_status(path,{200},True)
            if not data:raise RuntimeError('Asset empty '+path)
        for path in ['/admin','/admin/artikel']:
            assert_status(path,{301,302,303,307,308,401,403},True)
        for path in ['/.env','/database/database.sqlite','/vendor/autoload.php']:
            assert_status(path,{403,404},True)
        for path in ['/','/api/jtl.php?ean=3474630337398']:
            assert_status(path,{401,403},False)
        print('HTTPS-Smoketest: anonymer Basic-Schutz = 401/403')
        print('HTTPS-Smoketest: Filament Login = 200 mit bestehender Basic-Anmeldung')
        print('HTTPS-Smoketest: CSS/JS = 200, nicht leer')
        print('HTTPS-Smoketest: Admin-Routen ohne Session gesperrt')
        print('HTTPS-Smoketest: .env, SQLite, vendor nicht oeffentlich erreichbar')
        break
    except (RuntimeError,URLError,TimeoutError,OSError) as err:
        if trial==9:raise SystemExit('STOP: HTTPS-Abnahme fehlgeschlagen: '+str(err))
        time.sleep(2)
PY
switched=0
echo '=== HW24 FILAMENT AUF HTTPS-TESTDOMAIN ERFOLGREICH AKTIVIERT ==='
echo 'LIVE-Artikelmanager und alter Testdateibestand unberuehrt.'
echo "Rollback bei Browser-/Login-Problemen: bash $rollback"
echo 'Als naechstes den echten Filament-Login im Browser testen (kein Passwort senden).'
