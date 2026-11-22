#!/usr/bin/env bash
# HW24 private SQLite migration only, after operator-verified private Git FF.
# STRICT isolation: never reach the JTL-Wawi / HW24 legacy MariaDB.
set -euo pipefail
umask 077
app='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform'
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
db="$app/database/database.sqlite"
owner='nicovoss'
php='/opt/plesk/php/8.5/bin/php'
expected_head='a26a64d09597e41523e224c557bb1d4e39bdf052'
migration='2026_11_22_000002_create_jtl_source_catalog_tables'
migration_file="database/migrations/$migration.php"
backup_dir='/root/hw24-backups'
echo '=== HW24: NUR PLATTFORM-SQLITE-QUELLKATALOG ==='
test "$(id -u)" -eq 0 || { echo 'STOP: bitte als root in PuTTY'; exit 2; }
for tool in git runuser mktemp sha256sum; do command -v "$tool" >/dev/null;done
test -x "$php" && test -f "$app/artisan" && test -f "$app/vendor/autoload.php"
test -f "$db" && test ! -L "$db"
test -f "$app/.env" && test ! -L "$app/.env"
test -f "$app/$migration_file" && test ! -L "$app/$migration_file"
test -d "$repo/.git"
id "$owner" >/dev/null
current="$(runuser -u "$owner" -- git -C "$repo" rev-parse HEAD)"
test "$current" = "$expected_head" || {
 echo "STOP: Framework-HEAD $current weicht vom geprueften Stand ab"; exit 2;
}
test -z "$(runuser -u "$owner" -- git -C "$repo" status --porcelain --untracked-files=no)" || {
 echo 'STOP: getrackte Framework-Dateien geaendert'; exit 2;
}

# Boot Laravel as actual site user to inspect EFFECTIVE DB config.
# This prevents DB_URL / config-cache from silently targeting MariaDB.
inspect='
chdir($argv[1]);
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$driver=config("database.default");
$cfg=config("database.connections.sqlite");
$path=realpath((string)($cfg["database"]??""));
$expect=realpath($argv[2]);
$actual=Illuminate\Support\Facades\DB::connection()->getDatabaseName();
if($driver!=="sqlite" || ($cfg["url"]??null) || $path===false ||
    $expect===false || $path!==$expect || realpath((string)$actual)!==$expect ||
    Illuminate\Support\Facades\DB::connection()->getDriverName()!=="sqlite") {
    fwrite(STDERR,"STOP: effektive Laravel-Datenbank ist nicht die private Plattform-SQLite\n");
    exit(3);
}
$pdo=Illuminate\Support\Facades\DB::connection()->getPdo();
if($pdo->query("PRAGMA integrity_check")->fetchColumn()!=="ok"){
    fwrite(STDERR,"STOP: private SQLite Integrity Check fehlgeschlagen\n");exit(3);
}
$names=["source_field_catalog","source_reference_observations","channel_account_registry"];
$existing=[];
foreach($names as $name){
 $q=$pdo->prepare("SELECT count(*) FROM sqlite_master WHERE type=? AND name=?");
 $q->execute(["table",$name]);
 if((int)$q->fetchColumn()!==0)$existing[]=$name;
}
$q=$pdo->prepare("SELECT count(*) FROM sqlite_master WHERE type=? AND name=?");
$q->execute(["table","migrations"]);
if((int)$q->fetchColumn()!==1){
 fwrite(STDERR,"STOP: Laravel-Migrationstabelle fehlt\n");exit(3);
}
$q=$pdo->prepare("SELECT count(*) FROM migrations WHERE migration=?");
$q->execute([$argv[3]]);
$recorded=(int)$q->fetchColumn();
if($recorded===0 && count($existing)===0){
 echo "NEU: Drei isolierte Quellkatalog-Tabellen fehlen, Migration bereit\n";exit(0);
}
if($recorded===1 && count($existing)===3){
 echo "BEREITS_VORHANDEN: Alle drei Quellkatalog-Tabellen mit Laravel-Migration nachgewiesen\n";exit(10);
}
fwrite(STDERR,"STOP: Inkonsistenter Migrationsstatus, keine weitere Aenderung\n");exit(3);
'
# Stop before backup if already done or inconsistent.
set +e
runuser -u "$owner" -- "$php" -r "$inspect" "$app" "$db" "$migration"
inspect_rc=$?
set -e
if [ "$inspect_rc" = 10 ]; then
 echo '=== KEIN WEITERER MIGRATIONSVORGANG NOETIG ==='
 exit 0
fi
[ "$inspect_rc" = 0 ] || { echo 'STOP: Plattform-SQLite-Konfiguration nicht freigegeben';exit 2; }

echo '1. Root-privates konsistentes SQLite-Backup erstellen ...'
install -d -m 700 "$backup_dir"
backup="$(mktemp "$backup_dir/hw24-platform-before-source-catalog-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.sqlite")"
chmod 600 "$backup"
# VACUUM INTO creates a consistent SQLite snapshot without corrupting an
# active journal/WAL. Root can write to root-private backup path.
"$php" -r '
$db=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("PRAGMA busy_timeout=5000");
$st=$db->prepare("VACUUM INTO ?");
$st->execute([$argv[2]]);
$copy=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($copy->query("PRAGMA integrity_check")->fetchColumn()!=="ok"){
 fwrite(STDERR,"STOP: SQLite-Backup Integritaetscheck fehlgeschlagen\n");exit(2);
}
echo "SQLite-Backup: Integritaet OK\n";
' "$db" "$backup"
echo "Gegen Rueckfall gesichert: $backup"

echo '2. Nur die eine Plattform-Migration ausfuehren, niemals alle ...'
runuser -u "$owner" -- /bin/bash -c '
  set -euo pipefail
  cd "$1"
  exec "$2" artisan migrate --database=sqlite --path="$3" --force
' hw24-migrate "$app" "$php" "$migration_file"

echo '3. Struktur und SQLite-Integritaet erneut pruefen ...'
runuser -u "$owner" -- "$php" -r "$inspect" "$app" "$db" "$migration" >/dev/null 2>&1 && {
 echo 'STOP: neue Migration wurde nicht als ausgefuehrt erkannt';exit 2;
} || rc=$?
[ "$rc" = 10 ] || { echo 'STOP: Migrations-Nachkontrolle fehlgeschlagen'; exit 2; }
"$php" -r '
$p=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($p->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(2);
foreach(["source_field_catalog","source_reference_observations","channel_account_registry"] as $table){
 $n=(int)$p->query("SELECT COUNT(*) FROM ".$table)->fetchColumn();
 if($n!==0){fwrite(STDERR,"STOP: neue Metadatentabelle bereits mit Daten befuellt\n");exit(2);}
 echo "Neu und leer: $table\n";
}
echo "SQLite Integritaet OK\n";
' "$db"
echo '=== PLATTFORM-SQLITE-MIGRATION ERFOLGREICH ==='
echo 'Keine Seed-Daten, keine Benutzer-Neuanlage, keine Live-/Test-Webroot-Aenderung.'
echo 'Hinweis: Bei Fehlern nicht unkontrolliert migrate:fresh oder rollback ausfuehren.'
