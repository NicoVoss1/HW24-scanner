#!/usr/bin/env bash
# HW24 read-only Laravel -> protected legacy LIVE HTTPS integration smoke.
# One explicit JTL article GET; an archived (free) AI GET; optional JTL fields GET.
# No Amazon SP-API, no OpenAI POST, no SQLite writes/migrations/seeders.
set -euo pipefail
umask 077

app='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform'
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
php='/opt/plesk/php/8.5/bin/php'
owner='nicovoss'
expected='a26a64d09597e41523e224c557bb1d4e39bdf052'
ean='3474630337398'

echo '=== HW24 LARAVEL ZU LIVE-LEGACY READ-BRIDGE: NUR GET ==='
[ "$(id -u)" -eq 0 ] || { echo 'STOP: bitte als root in PuTTY';exit 2; }
[ -x "$php" ] && [ -f "$app/artisan" ] && [ -f "$app/vendor/autoload.php" ] || {
 echo 'STOP: private Laravel-Installation oder PHP fehlt';exit 2;
}
[ -f "$app/.env" ] && [ ! -L "$app/.env" ] && [ -f "$app/database/database.sqlite" ] || {
 echo 'STOP: private Laravel-Konfiguration oder SQLite fehlt';exit 2;
}
[ -d "$repo/.git" ] || { echo 'STOP: privater Git-Klon fehlt';exit 2; }
current="$(runuser -u "$owner" -- git -C "$repo" rev-parse HEAD)"
[ "$current" = "$expected" ] || {
 echo "STOP: erwarteter Plattform-Commit $expected, gefunden $current";exit 2;
}

code='
chdir($argv[1]);
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expectedDb=realpath("database/database.sqlite");
$database=(string) Illuminate\Support\Facades\DB::connection()->getDatabaseName();
$actualDb=realpath($database);
if(config("database.default")!=="sqlite" ||
   $expectedDb===false || $actualDb===false || $actualDb!==$expectedDb ||
   Illuminate\Support\Facades\DB::connection()->getDriverName()!=="sqlite"){
 fwrite(STDERR,"STOP: Laravel nutzt nicht die erwartete private SQLite.\n");exit(3);
}
foreach(["source_field_catalog","source_reference_observations","channel_account_registry"] as $table){
 if(!Illuminate\Support\Facades\Schema::hasTable($table)){
  fwrite(STDERR,"STOP: Quellkatalog-Schema fehlt: ".$table."\n");exit(3);
 }
}
echo "OK: Private Laravel-SQLite und alle drei Quellkatalog-Tabellen\n";

$base=(string)config("hw24_legacy.base_url");
$user=(string)config("hw24_legacy.user");
$password=(string)config("hw24_legacy.password");
if($base!=="https://artikelmanager.hairworld24.de" || $user==="" || $password===""){
 echo "STOP: Live-Lesebruecke nicht konfiguriert (URL oder Basic-Auth fehlt).\n";
 echo "Bitte Zugangsdaten nur auf dem Server in der privaten Laravel-Konfiguration hinterlegen.\n";
 exit(4);
}
echo "OK: Laravel-Leseadapter auf LIVE-Host festgelegt (keine Zugangsdaten ausgegeben)\n";

$ean=$argv[2];
$bridge=app(App\Services\LegacyReadBridge::class);
$result=$bridge->fetch("jtl.article",["q"=>$ean]);
$payload=is_array($result["data"]??null)?$result["data"]:[];
$rows=is_array($payload["matches"]??null)?$payload["matches"]:[];
echo "JTL HTTPS HTTP: ".($result["http_status"]??"nicht erreichbar")."\n";
echo "JTL Snapshotstatus: ".($payload["jtl_status"]??"nicht verfuegbar")."\n";
echo "JTL Trefferanzahl: ".count($rows)."\n";
if(($result["ok"]??false)!==true ||
   ($payload["jtl_status"]??null)!=="current_snapshot" ||
   count($rows)!==1 ||
   (string)($rows[0]["ean"]??"")!==$ean ||
   (string)($rows[0]["jtl_article_id"]??"")!=="1428"){
 fwrite(STDERR,"STOP: Authentifizierte JTL-Artikelidentitaet nicht erfolgreich belegt.\n");
 exit(5);
}
echo "OK: EAN ".$ean." / JTL-Artikel 1428 aus geschuetztem LIVE-Spiegel\n";

$archive=$bridge->fetch("ai.history",["ean"=>$ean,"limit"=>5]);
$history=is_array($archive["data"]??null)?$archive["data"]:[];
echo "KI-Archiv HTTPS HTTP: ".($archive["http_status"]??"nicht erreichbar")."\n";
if(($archive["ok"]??false)!==true || !is_array($history["reviews"]??null)){
 fwrite(STDERR,"STOP: Nur-lesender KI-Archiv-Abruf nicht erreichbar.\n");
 exit(6);
}
echo "OK: KI-Archiv nur gelesen, Eintraege fuer EAN: ".count($history["reviews"])."\n";

$detail=$bridge->fetch("jtl.fields",[
 "article_id"=>"1428","ean"=>$ean,"offset"=>0,"limit"=>1
]);
if(($detail["ok"]??false)===true){
 echo "OK: JTL-Detailfelder-API zusaetzlich verfuegbar (nur GET).\n";
}elseif((int)($detail["http_status"]??0)===404){
 echo "HINWEIS: JTL-Detailfelder-API auf LIVE noch nicht deployed (HTTP 404).\n";
 echo "Die neue Filament-Quellfeldansicht bleibt bis zur eigenen Abnahme unvollstaendig.\n";
}else{
 echo "HINWEIS: JTL-Detailfelder-API separat zu pruefen; HTTP ".
  ($detail["http_status"]??"nicht erreichbar").".\n";
}
echo "=== LIVE-LEGACY-LARAVEL-LESERBRUECKE: JTL + KI-ARCHIV BESTANDEN ===\n";
echo "Kein Amazon-Aufruf, kein OpenAI-POST, keine Plattform-DB-Schreiboperation.\n";
'
runuser -u "$owner" -- "$php" -r "$code" "$app" "$ean"
