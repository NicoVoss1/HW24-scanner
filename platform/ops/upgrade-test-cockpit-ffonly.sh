#!/usr/bin/env bash
# HW24 cockpit release, TEST-only. Operators execute via root PuTTY.
# ONLY fast-forward private framework checkout already serving TEST/public.
# No Plesk changes, composer, migration, seeding, JTL/marketplace writes.
set -Eeuo pipefail
umask 077

base='/var/www/vhosts/hairworld24.de'
repo="$base/hw24-console-private/repo"
app="$repo/platform"
site='test-artikelmanager.hairworld24.de'
webroot="$app/public"
old='a26a64d09597e41523e224c557bb1d4e39bdf052'
target='b679642212ac1daace71af7abcc65fe69dc293bb'
branch='feature/hw24-platform-laravel13-filament5-20261122'
owner='nicovoss'
php='/opt/plesk/php/8.5/bin/php'
backupdir='/root/hw24-backups'
changed=0
complete=0
rollback_metadata=''
snapshot=''

gituser(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
phpuser(){ runuser -u "$owner" -- "$php" "$@"; }
get_root(){ plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null; }
restore(){
 local rc=$?
 trap - EXIT
 if [ -n "$snapshot" ];then rm -f -- "$snapshot" || true;fi
 if [ "$changed" = 1 ] && [ "$complete" = 0 ];then
  echo 'POSTCHECK FEHLGESCHLAGEN: private Test-Codeversion wird sicher zurueckgesetzt.'
  if [ "$(gituser rev-parse HEAD 2>/dev/null || true)" = "$target" ] &&
     [ -z "$(gituser status --porcelain --untracked-files=no)" ] &&
     gituser -c core.hooksPath=/dev/null reset --hard "$old" >/dev/null;then
    echo 'GIT-ROLLBACK OK: Vorheriger Code wieder aktiv. Plesk-Webroot blieb unveraendert.'
  else
    echo "ALARM: automatischer Code-Rollback nicht sicher moeglich. Referenz: $rollback_metadata"
  fi
 fi
 exit "$rc"
}
trap restore EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== HW24: GEPRUEFTER COCKPIT-RELEASE NUR FUER TEST ==='
[ "$(id -u)" = 0 ] || { echo 'STOP: root-PuTTY erforderlich';exit 2; }
for tool in git runuser sha256sum mktemp plesk curl python3 install;do
 command -v "$tool" >/dev/null || { echo "STOP: $tool nicht verfuegbar";exit 2; }
done
[ -x "$php" ] && [ -f "$app/artisan" ] && [ -f "$app/vendor/autoload.php" ] || {
 echo 'STOP: Private Laravel-Installation fehlt';exit 2;
}
[ "$(get_root)" = "$webroot" ] || {
 echo 'STOP: Testdomain zeigt NICHT auf den Laravel-public-Root';exit 2;
}
for private in "$app/.env" "$app/database/database.sqlite" "$app/composer.lock";do
 [ -f "$private" ] && [ ! -L "$private" ] || {
  echo 'STOP: private Laravel-Datei fehlt oder ist Symlink';exit 2;
 }
done
[ "$(gituser rev-parse HEAD)" = "$old" ] && [ "$(gituser symbolic-ref -q --short HEAD)" = "$branch" ] || {
 echo 'STOP: vorheriger Framework-Commit/Branch weicht ab';exit 2;
}
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || {
 echo 'STOP: lokale getrackte Dateien veraendert, keine Ueberschreibung';exit 2;
}
origin="$(gituser remote get-url origin)"
case "$origin" in
 https://github.com/NicoVoss1/HW24-scanner|https://github.com/NicoVoss1/HW24-scanner.git|git@github.com:NicoVoss1/HW24-scanner|git@github.com:NicoVoss1/HW24-scanner.git) : ;;
 *) echo 'STOP: GitHub-Origin nicht verifiziert';exit 2 ;;
esac
echo 'OK: Aktive TEST-Domain, alter Framework-HEAD und Git-Remote verifiziert'
for path in '/admin/login' '/.env';do
 code="$(curl -sS --max-time 12 --proto '=https' -o /dev/null -w '%{http_code}' \
 "https://$site$path" 2>/dev/null)" || code='transport_error'
 case "$code" in 401|403) : ;; *)
  echo "STOP: anonymer HTTPS-Zugriffsschutz nicht bestaetigt ($path HTTP $code)";exit 2 ;;
 esac
done

echo '1. Nur geprüfte Remote-Objekte laden, kein Plesk-Git-Deployment'
gituser -c core.hooksPath=/dev/null fetch --no-tags --no-recurse-submodules origin "refs/heads/$branch"
gituser cat-file -e "$target^{commit}" || { echo 'STOP: Zielcommit fehlt';exit 2; }
gituser merge-base --is-ancestor "$old" "$target" || { echo 'STOP: kein Fast-Forward';exit 2; }
gituser merge-base --is-ancestor "$target" FETCH_HEAD || {
 echo 'STOP: gepinnter Zielcommit nicht im remote Branch enthalten';exit 2;
}
echo '2. Aenderungsumfang gegen Whitelist pruefen'
while IFS= read -r path;do
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
  platform/tests/Unit/ArticleCardTest.php) : ;;
  *) echo "STOP: unerwartete Dateiaenderung: $path";exit 2 ;;
 esac
done < <(gituser diff --name-only "$old" "$target")
if gituser diff --name-status "$old" "$target" | grep -Eq '^[DR][[:space:]]';then
 echo 'STOP: unerwartete Dateiloeschung oder Umbenennung';exit 2;
fi

echo '3. Private Umgebung und Daten konsistent sichern'
install -d -m 700 "$backupdir"
[ "$(stat -c '%U %a' "$backupdir")" = 'root 700' ] || {
 echo 'STOP: Root-Backupverzeichnis nicht 0700';exit 2;
}
env_hash="$(sha256sum "$app/.env" | cut -d' ' -f1)"
lock_hash="$(sha256sum "$app/composer.lock" | cut -d' ' -f1)"
rollback_metadata="$(mktemp "$backupdir/hw24-cockpit-rollback-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.txt")"
chmod 600 "$rollback_metadata"
printf 'BEFORE=%s\nAFTER=%s\nTEST_DOMAIN=%s\n' "$old" "$target" "$site" >"$rollback_metadata"
echo "Code-Rueckfallreferenz: $rollback_metadata"
snapshot="$(runuser -u "$owner" -- mktemp "$app/database/.hw24-cockpit-backup-XXXXXXXX.sqlite")"
runuser -u "$owner" -- "$php" -r '
$source=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$source->exec("PRAGMA busy_timeout=5000");
$source->prepare("VACUUM INTO ?")->execute([$argv[2]]);
$copy=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($copy->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(2);
' "$app/database/database.sqlite" "$snapshot"
sqlite_backup="$(mktemp "$backupdir/hw24-cockpit-before-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.sqlite")"
install -m 600 "$snapshot" "$sqlite_backup"
rm -f -- "$snapshot"
snapshot=''
echo "Konsistentes SQLite-Backup: $sqlite_backup"
echo 'Kein Datenbank-Migrationslauf und keine Konto- oder Preisaenderung.'

echo '4. Nur privaten Framework-Code auf vorab CI-getesteten Commit aktualisieren'
changed=1
gituser -c core.hooksPath=/dev/null merge --ff-only "$target"
[ "$(gituser rev-parse HEAD)" = "$target" ] || { echo 'STOP: falscher Git-HEAD nach Update';exit 2; }
[ "$(sha256sum "$app/.env" | cut -d' ' -f1)" = "$env_hash" ] &&
[ "$(sha256sum "$app/composer.lock" | cut -d' ' -f1)" = "$lock_hash" ] || {
 echo 'STOP: private .env oder Composer-Lock unerwartet veraendert';exit 2;
}
for phpfile in app/Domain/Jtl/ArticleCard.php app/Filament/Pages/ArticleLookup.php app/Filament/Widgets/Hw24SystemOverview.php;do
 phpuser -l "$app/$phpfile"
done
phpuser "$app/artisan" route:list --path=admin

echo '5. Geschuetzte TEST-HTTPS-Routen nach Codeupdate pruefen'
for path in '/admin/login' '/admin' '/admin/artikel' '/.env';do
 code="$(curl -sS --max-time 12 --proto '=https' -o /dev/null -w '%{http_code}' \
 "https://$site$path" 2>/dev/null)" || code='transport_error'
 case "$code" in 401|403) : ;;
 *) echo "STOP: anonyme Route $path unerwartet HTTP $code";exit 2 ;;
 esac
done
for user in nginx www-data;do
 for path in "$webroot/index.php" "$webroot/.htaccess" "$webroot/css/filament/filament/app.css" "$webroot/js/filament/filament/app.js";do
  runuser -u "$user" -- test -r "$path" || {
   echo "STOP: Webserver kann notwendige PUBLIC-Datei nicht lesen";exit 2;
  }
 done
 for path in "$app/.env" "$app/database/database.sqlite";do
  if runuser -u "$user" -- test -r "$path";then
   echo 'STOP: private Laravel-Datei waere durch Webserver lesbar';exit 2;
  fi
 done
done
[ "$(get_root)" = "$webroot" ] || {
 echo 'STOP: Plesk TEST-Webroot unerwartet umgestellt';exit 2;
}
complete=1
echo '=== NEUES HW24 COCKPIT AUF TEST QUELLCODESEITIG AKTIVIERT ==='
echo 'BasicAuth und private Dateien weiter geschuetzt. LIVE-Domain unveraendert.'
echo "Rückfallstand: $old (Metadaten $rollback_metadata)"
echo 'Bitte nach dem Wechsel neu einloggen/Browser aktualisieren und JTL-/KI-Buttons testen.'
