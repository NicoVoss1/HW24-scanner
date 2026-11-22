#!/usr/bin/env bash
# Safely upgrade ONLY the PRIVATE Laravel checkout for the already-live TEST panel.
# No JTL/legacy/Shopify/Amazon writes, no migrations, no Plesk root or Git deploy.
set -Eeuo pipefail
umask 077
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
owner='nicovoss'
php='/opt/plesk/php/8.5/bin/php'
old='a26a64d09597e41523e224c557bb1d4e39bdf052'
target='b679642212ac1daace71af7abcc65fe69dc293bb'
branch='feature/hw24-platform-laravel13-filament5-20261122'
backup_dir='/root/hw24-backups'
db="$app/database/database.sqlite"
snapshot=''
backup=''
changed=0
done=0
gituser(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
get_root(){
 plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null
}
cleanup(){
 rc=$?
 trap - EXIT
 if [ -n "$snapshot" ];then rm -f -- "$snapshot";fi
 if [ "$changed" = 1 ] && [ "$done" = 0 ];then
  echo 'FEHLER: Nachkontrolle fehlgeschlagen. Nur getrackten Framework-Code zurückstellen.'
  if [ "$(gituser rev-parse HEAD 2>/dev/null || true)" = "$target" ] &&
     [ -z "$(gituser status --porcelain --untracked-files=no 2>/dev/null || echo ERROR)" ];then
   if gituser -c core.hooksPath=/dev/null reset --hard "$old" >/dev/null 2>&1;then
    echo 'CODE-ROLLBACK OK: Alter privater HEAD wiederhergestellt.'
   else
    echo 'ALARM: Automatische Code-Rücknahme fehlgeschlagen. Kein Plesk-Deploy ausführen.'
   fi
  else
   echo 'ALARM: Git-Stand wurde zwischenzeitlich verändert. Keine automatische Rücknahme.'
  fi
 fi
 exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== HW24 TEST: GESICHERTE COCKPIT-UI-QUELLCODE-AKTUALISIERUNG ==='
[ "$(id -u)" = 0 ] || { echo 'STOP: Bitte als root in PuTTY';exit 2; }
for x in runuser git plesk sha256sum mktemp install;do
 command -v "$x" >/dev/null || { echo "STOP: $x fehlt";exit 2; }
done
[ -x "$php" ] && [ -d "$repo/.git" ] && [ -f "$app/artisan" ] || {
 echo 'STOP: PHP/privates Framework unvollständig';exit 2;
}
[ "$(get_root)" = "$app/public" ] || {
 echo 'STOP: Aktive Testdomain nutzt nicht den erwarteten Laravel-DocumentRoot';exit 2;
}
[ "$(gituser rev-parse HEAD)" = "$old" ] || {
 echo 'STOP: Privater HEAD weicht vom vorliegenden geprüften Serverstand ab';exit 2;
}
[ "$(gituser branch --show-current)" = "$branch" ] || {
 echo 'STOP: Falscher privater Git-Branch';exit 2;
}
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || {
 echo 'STOP: Getrackte lokale Änderungen vorhanden';exit 2;
}
for f in "$app/.env" "$db" "$app/composer.lock" "$app/vendor/autoload.php";do
 [ -f "$f" ] && [ ! -L "$f" ] || { echo 'STOP: Geschützte Laravel-Datei fehlt oder Symlink';exit 2; }
done
for file in "$app/public/.htaccess" "$app/public/index.php";do
 for user in nicovoss nginx www-data;do
  runuser -u "$user" -- test -r "$file" || {
   echo 'STOP: Frontcontroller-ACL nicht mehr intakt';exit 2;
  }
 done
done
for file in "$app/.env" "$db";do
 for user in nginx www-data;do
  if runuser -u "$user" -- test -r "$file";then
   echo 'STOP: Private Datei von Webserver-Benutzer lesbar';exit 2;
  fi
 done
done
echo 'OK: TEST-vHost, aktueller Git-Stand, private Daten und PUBLIC-ACL intakt'

echo '1. Exakte getrackte Zielversion nur aus GitHub laden'
gituser fetch --no-tags origin "$branch"
gituser cat-file -e "$target^{commit}"
gituser merge-base --is-ancestor "$old" "$target" || {
 echo 'STOP: Kein Fast-Forward von installierter Version zum freigegebenen Ziel';exit 2;
}
gituser merge-base --is-ancestor "$target" FETCH_HEAD || {
 echo 'STOP: Fest geprüfter Zielcommit nicht im erwarteten Remote-Branch';exit 2;
}
echo '2. Änderungen exakt gegen UI-/Ops-Allowlist prüfen'
paths="$(gituser diff --name-status --no-renames "$old" "$target")"
[ -n "$paths" ] || { echo 'STOP: Keine Änderungen; Ziel stimmt nicht';exit 2; }
while IFS="$(printf '\t')" read -r status path;do
 [ "$status" = A ] || [ "$status" = M ] || {
  echo 'STOP: Git-Diff mit unerwartetem Dateistatus';exit 2;
 }
 case "$path" in
  .github/workflows/hw24-platform-foundation.yml|\
  platform/app/Domain/Jtl/ArticleCard.php|\
  platform/app/Filament/Pages/ArticleLookup.php|\
  platform/app/Filament/Widgets/Hw24SystemOverview.php|\
  platform/ops/configure-private-legacy-readbridge.py|\
  platform/ops/cutover-testdomain-filament-with-rollback.sh|\
  platform/ops/diagnose-filament-testdomain-403-readonly.py|\
  platform/ops/fix-filament-public-frontcontroller-acl.sh|\
  platform/ops/migrate-source-catalog-isolated.sh|\
  platform/ops/upgrade-private-framework-ffonly.sh|\
  platform/ops/verify-filament-https-release-gates-readonly.sh|\
  platform/ops/verify-legacy-readbridge-live.sh|\
  platform/ops/verify-plesk-cutover-mechanics-readonly.sh|\
  platform/resources/views/filament/pages/article-lookup.blade.php|\
  platform/resources/views/filament/widgets/hw24-system-overview.blade.php|\
  platform/tests/Feature/Hw24DashboardReadOnlyTest.php|\
  platform/tests/Feature/Hw24DashboardSafetyTest.php|\
  platform/tests/Unit/ArticleCardTest.php)
    echo "Geprueft: $path" ;;
  *) echo "STOP: Nicht freigegebene Datei im Upgrade: $path";exit 2 ;;
 esac
done <<<"$paths"

echo '3. Private Daten und Konfiguration vor Upgrade sichern'
install -d -m 700 "$backup_dir"
[ "$(stat -c '%U %a' "$backup_dir")" = 'root 700' ] || {
 echo 'STOP: Root-Backupverzeichnis nicht 0700';exit 2;
}
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
env_hash="$(sha256sum "$app/.env" | cut -d' ' -f1)"
lock_hash="$(sha256sum "$app/composer.lock" | cut -d' ' -f1)"
backup="$(mktemp "$backup_dir/hw24-cockpit-before-$stamp-XXXXXXXX.sqlite")"
chmod 600 "$backup"
snapshot="$(runuser -u "$owner" -- mktemp "$app/database/.hw24-cockpit-preupgrade-XXXXXXXX.sqlite")"
runuser -u "$owner" -- "$php" -r '
$db=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("PRAGMA busy_timeout=5000");
$db->prepare("VACUUM INTO ?")->execute([$argv[2]]);
$copy=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($copy->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(2);
' "$db" "$snapshot"
install -m 600 "$snapshot" "$backup"
rm -f -- "$snapshot"
snapshot=''
echo "OK: Integritätsgeprüftes privates SQLite-Backup: $backup"
metadata="$(mktemp "$backup_dir/hw24-cockpit-git-before-$stamp-XXXXXXXX.txt")"
chmod 600 "$metadata"
printf 'Old private git: %s\nPinned new git: %s\nSQLite backup: %s\n' "$old" "$target" "$backup" >"$metadata"

echo '4. Nur privaten Framework-Checkout fast-forward aktualisieren'
gituser -c core.hooksPath=/dev/null merge --ff-only "$target"
changed=1
[ "$(gituser rev-parse HEAD)" = "$target" ]
[ "$(sha256sum "$app/.env" | cut -d' ' -f1)" = "$env_hash" ] &&
[ "$(sha256sum "$app/composer.lock" | cut -d' ' -f1)" = "$lock_hash" ] || {
 echo 'STOP: Konfigurations-/Composerdatei unerwartet verändert';exit 2;
}
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || {
 echo 'STOP: Getrackte Dateien nach Upgrade verändert';exit 2;
}
for f in "$app/app/Domain/Jtl/ArticleCard.php" "$app/app/Filament/Pages/ArticleLookup.php" "$app/app/Filament/Widgets/Hw24SystemOverview.php";do
 "$php" -l "$f" >/dev/null || { echo 'STOP: PHP-Lint fehlgeschlagen';exit 2; }
done
echo '5. Nur kompilierte Blade-Views der privaten Testanwendung leeren'
runuser -u "$owner" -- /bin/bash -c '
set -e
cd "$1"
"$2" artisan view:clear --no-interaction >/dev/null
"$2" artisan route:list --path=admin --except-vendor >/dev/null
' hw24-ui "$app" "$php"
for f in "$app/public/.htaccess" "$app/public/index.php";do
 for user in nginx www-data;do
  runuser -u "$user" -- test -r "$f" || { echo 'STOP: PUBLIC-ACL verloren';exit 2; }
 done
done
for f in "$app/.env" "$db";do
 for user in nginx www-data;do
  if runuser -u "$user" -- test -r "$f";then
   echo 'STOP: Private Datei nach Upgrade lesbar';exit 2;
  fi
 done
done
[ "$(get_root)" = "$app/public" ] || {
 echo 'STOP: Test-DocumentRoot unerwartet verändert';exit 2;
}
done=1
echo '=== HW24 COCKPIT-QUELLCODE ERFOLGREICH AKTUALISIERT ==='
echo "Privater Git-HEAD: $target"
echo "Datenbank-Sicherung: $backup"
echo "Rollback-Metadaten: $metadata"
echo 'Kein Plesk-Deploy, keine Migration, keine API-/JTL-Schreibaktion.'
echo 'Bitte danach Dashboard und Artikelansicht im HTTPS-Testbrowser aktualisieren.'
