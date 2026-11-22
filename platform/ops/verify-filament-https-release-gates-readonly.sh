#!/usr/bin/env bash
# READ-ONLY final release gate inventory for future Plesk TEST-only cutover.
# Does not change Plesk, php.ini, git, Laravel config, SQLite, ACL or webroot.
set -u
umask 077
site='test-artikelmanager.hairworld24.de'
legacy='/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de'
app='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform'
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
php='/opt/plesk/php/8.5/bin/php'
owner='nicovoss'
expected='a26a64d09597e41523e224c557bb1d4e39bdf052'
warnings=0
pass(){ printf 'OK: %s\n' "$*"; }
warn(){ printf 'PRUEFEN: %s\n' "$*"; warnings=$((warnings+1)); }

echo '=== HW24 FILAMENT-HTTPS-TESTDOMAIN: RELEASE-GATES NUR LESEN ==='
date -u '+UTC %Y-%m-%d %H:%M:%S'
test "$(id -u)" -eq 0 || { echo 'STOP: root-PuTTY erforderlich';exit 2; }
for bin in plesk runuser curl python3 git; do command -v "$bin" >/dev/null || { echo "STOP: $bin fehlt";exit 2; }; done
test -x "$php" && test -f "$app/artisan" && test -f "$app/.env" || {
 echo 'STOP: Private Laravel-App fehlt';exit 2;
}
echo '1. Aktuelle Plesk-Zuordnung und alte Legacy-Seite'
meta="$(plesk db -Ne "SELECT d.name,h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null)" || meta=''
case "$meta" in
 *"$legacy"*) pass 'Bisherige Testdomain zeigt noch auf den Legacy-Webroot' ;;
 *) warn 'Plesk-DocumentRoot weicht ab oder ist unklar' ;;
esac
if test -f "$legacy/index.html" && test -f "$legacy/api/amazon-asin.php"; then
 pass 'Legacy-Testseite samt geschuetztem ASIN-Endpunkt vorhanden'
else
 warn 'Bisheriger Legacy-Testbestand ist nicht vollstaendig nachweisbar'
fi
echo '2. Plesk-Git-Veroeffentlichung nur auslesen'
if gitinfo="$(plesk ext git --info -domain "$site" -name HW24-scanner.git 2>/dev/null)"; then
 git_summary="$(printf '%s\\n' "$gitinfo" | python3 -c '
import re,sys
text=sys.stdin.read()
mode=""
for line in text.splitlines():
    m=re.match(r"^\s*(?:Deployment\s*(?:mode|type)|Deploy\s*(?:mode|type)|Mode)\s*:\s*(.*)$",line,re.I)
    if m:
        mode=m.group(1).strip().lower()
        break
if mode in ("manual","none","disabled","manual deployment","no deployment"):
    print("OK: Plesk-Git-Modus ist nicht automatisch: "+mode)
elif mode in ("auto","automatic","automatic deployment"):
    print("WARNUNG: Automatisches Plesk-Git-Deployment aktiv; vor Cutover deaktivieren")
else:
    print("HINWEIS: Git-Deploymentmodus aus --info nicht eindeutig auslesbar; manuell in Plesk verifizieren")
')"
 echo "$git_summary"
 case "$git_summary" in
  OK:*) : ;;
  *) warn 'Git-Deploymentmodus vor Plesk-Testdomain-Cutover klären' ;;
 esac
 # Entire --info may contain private remote URLs or settings: never print it.
else
 warn 'Plesk-Git-Repository-Einstellungen nicht per --info pruefbar'
fi
echo 'Plesk-Deploymentpfad und automatische Aktionen vor Cutover im Plesk-Git-Menue separat bestaetigen.'
echo '3. Privater Framework-Stand / keine getrackten Aenderungen'
head="$(runuser -u "$owner" -- git -C "$repo" rev-parse HEAD 2>/dev/null)" || head=''
if [ "$head" = "$expected" ];then
 pass "Installierter Laravel-Code entspricht gepinntem Commit"
else warn 'Privater Framework-Git-HEAD unerwartet';fi
if runuser -u "$owner" -- git -C "$repo" diff --quiet --exit-code &&
   runuser -u "$owner" -- git -C "$repo" diff --cached --quiet --exit-code;then
 pass 'Keine getrackten Git-Dateien lokal geaendert'
else warn 'Lokale getrackte Framework-Aenderungen vorhanden';fi
echo '4. Laravel-Konfiguration ohne geheime Werte (PHP als Site-User)'
php_summary="$(runuser -u "$owner" -- "$php" -r '
chdir($argv[1]);
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$checks=[
 "APP_ENV=production"=>config("app.env")==="production",
 "APP_DEBUG=false"=>config("app.debug")===false,
 "APP_URL=HTTPS-Testdomain"=>rtrim((string)config("app.url"),"/")==="https://test-artikelmanager.hairworld24.de",
 "DB=private SQLite"=>config("database.default")==="sqlite" &&
    Illuminate\Support\Facades\DB::connection()->getDriverName()==="sqlite" &&
    realpath((string)Illuminate\Support\Facades\DB::connection()->getDatabaseName())===
       realpath("database/database.sqlite"),
 "Live-Bridge konfiguriert"=>config("hw24_legacy.base_url")==="https://artikelmanager.hairworld24.de" &&
    is_string(config("hw24_legacy.user")) && config("hw24_legacy.user")!=="" &&
    is_string(config("hw24_legacy.password")) && config("hw24_legacy.password")!=="",
 "Filament-Admin-E-Mail gesetzt"=>filter_var(
    config("hw24_legacy.admin_email"),FILTER_VALIDATE_EMAIL)!==false,
 "Session-Treiber vorbereitet"=>in_array(config("session.driver"),["file","database"],true),
 "Host-only Session-Cookie"=>config("session.domain")===null ||
    config("session.domain")==="test-artikelmanager.hairworld24.de",
];
foreach($checks as $name=>$ok){
 echo ($ok?"OK: ":"PRUEFEN: ").$name."\n";
}
$secure=config("session.secure");
echo ($secure===true?"OK: ":"PRUEFEN: ").
    "SESSION_SECURE_COOKIE explizit true (HTTPS-Cookie-Schutz)\n";
$allow=(string)config("hw24_legacy.admin_email");
if(filter_var($allow,FILTER_VALIDATE_EMAIL)){
 $exists=App\Models\User::query()->where("email",$allow)->exists();
 echo ($exists?"OK: ":"PRUEFEN: ")."Konfigurierter Filament-Admin existiert in privater SQLite\n";
}
' "$app" 2>/dev/null)" && {
 printf '%s\n' "$php_summary"
 issue_count="$(printf '%s\n' "$php_summary" | grep -c '^PRUEFEN:' || true)"
 warnings=$((warnings+issue_count))
} || warn 'Laravel-Boot oder Admin-Konfigurationsdiagnose fehlgeschlagen'
echo '5. Webserver-ACLs und private Dateisperren'
for user in nginx www-data; do
 if runuser -u "$user" -- test -r "$app/public/css/filament/filament/app.css" &&
    runuser -u "$user" -- test -r "$app/public/js/filament/filament/app.js";then
    pass "$user kann Filament-CSS/JS lesen"
 else warn "$user kann Filament-CSS/JS nicht lesen";fi
 for private in "$app/.env" "$app/database/database.sqlite"; do
  if runuser -u "$user" -- test -r "$private";then
   warn "$user kann private Laravel-Datei lesen"
  fi
 done
done
if runuser -u "$owner" -- test -r "$app/public/index.php";then
 pass 'PHP-Site-User kann public/index.php lesen'
else warn 'PHP-Site-User kann public/index.php nicht lesen';fi
echo '6. Aktuelle HTTP-Basic-Absicherung und SSL (ohne Login)'
for host in artikelmanager.hairworld24.de test-artikelmanager.hairworld24.de;do
 code="$(curl --silent --show-error --max-time 10 --proto '=https' \
  --output /dev/null --write-out '%{http_code}' \
  "https://$host/api/jtl.php?ean=3474630337398" 2>/dev/null)" || code='transport_error'
 if [ "$code" = 401 ] || [ "$code" = 403 ];then
  pass "$host: HTTPS-GET anonym HTTP $code"
 else warn "$host: HTTPS-GET anonym unerwartet HTTP $code";fi
done
echo 'ACHTUNG: Schutz des alten Test-Webroots garantiert NICHT automatisch den neuen!'
echo 'Plesk PHP-FPM, open_basedir, HTTPS-Forwarding und Basic-Auth erst am neuen vHost endgueltig abnehmen.'
echo "=== READ-ONLY RELEASE-GATES ABGESCHLOSSEN, SHELL-WARNUNGEN: $warnings ==="
echo 'Keine Aenderungen. Kein Domain-Cutover. Keine Login-Secrets ausgegeben.'
