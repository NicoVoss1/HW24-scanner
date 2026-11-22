#!/usr/bin/env bash
# HW24 operator gate: TEST article layout only, existing HTTP/BasicAuth preserved.
# Pinned, clean FF: b679642 -> d5491f2. No composer, migrations, Plesk deploy or JTL writes.
set -Eeuo pipefail
umask 077

repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
legacy='/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de'
owner='nicovoss'
php='/opt/plesk/php/8.5/bin/php'
branch='feature/hw24-platform-laravel13-filament5-20261122'
old='b679642212ac1daace71af7abcc65fe69dc293bb'
target='d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4'
backup_dir='/root/hw24-backups'
db="$app/database/database.sqlite"
sample=''
updated=0
complete=0

gituser(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
get_root(){
 plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null
}
rollback(){
 local rc=$?
 trap - EXIT
 if [ -n "$sample" ];then rm -f -- "$sample";fi
 if [ "$updated" -eq 1 ] && [ "$complete" -eq 0 ];then
  echo 'ABBRUCH NACH GIT-UPDATE: Versuche, geprueften Code-Stand zurueckzustellen.'
  if [ "$(gituser rev-parse HEAD 2>/dev/null || true)" = "$target" ] &&
     [ -z "$(gituser status --porcelain --untracked-files=no 2>/dev/null || echo ERROR)" ];then
   if gituser -c core.hooksPath=/dev/null reset --hard "$old" >/dev/null 2>&1;then
    runuser -u "$owner" -- /bin/bash -c 'cd "$1" && "$2" artisan view:clear --no-interaction >/dev/null' hw24-rollback "$app" "$php" || true
    echo 'ROLLBACK OK: Alter Artikelmasken-Code wiederhergestellt.'
   else
    echo 'ALARM: Code-Ruecknahme nicht abgeschlossen. Keine Plesk-Bereitstellung ausloesen.'
   fi
  else
   echo 'ALARM: Git-Zustand inzwischen anders. Keine automatische Ueberschreibung.'
  fi
 fi
 exit "$rc"
}
trap rollback EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== HW24 TEST: RESPONSIVE ARTIKELZENTRALE SICHER EINRICHTEN ==='
[ "$(id -u)" -eq 0 ] || { echo 'STOP: root-PuTTY erforderlich';exit 2; }
for tool in plesk git runuser sha256sum stat mktemp install curl;do command -v "$tool" >/dev/null || { echo "STOP: $tool fehlt";exit 2; };done
[ -x "$php" ] && [ -d "$repo/.git" ] || { echo 'STOP: Laravel/PHP fehlt';exit 2; }
[ "$(get_root)" = "$app/public" ] || { echo 'STOP: Testdomain nutzt nicht das private Laravel/public';exit 2; }
[ "$(gituser rev-parse HEAD)" = "$old" ] || { echo 'STOP: Privater Git-Stand unerwartet';exit 2; }
[ "$(gituser branch --show-current)" = "$branch" ] || { echo 'STOP: Falscher Git-Branch';exit 2; }
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || { echo 'STOP: getrackte lokale Aenderungen';exit 2; }
for file in "$app/.env" "$db" "$app/composer.lock" "$app/vendor/autoload.php" "$app/resources/views/filament/pages/article-lookup.blade.php";do
 [ -f "$file" ] && [ ! -L "$file" ] || { echo 'STOP: Laravel-Datei fehlt oder Symlink';exit 2; }
done
for file in "$app/public/.htaccess" "$app/public/index.php";do
 for u in nicovoss nginx www-data;do
  runuser -u "$u" -- test -r "$file" || { echo 'STOP: Frontcontroller-ACL nicht intakt';exit 2; }
 done
done
for file in "$app/.env" "$db";do
 for u in nginx www-data;do
  if runuser -u "$u" -- test -r "$file";then echo 'STOP: private Laravel-Datei fuer Webserver lesbar';exit 2;fi
 done
done
old_status="$(curl -sS --max-time 12 --proto '=https' --output /dev/null --write-out '%{http_code}' 'https://test-artikelmanager.hairworld24.de/admin/artikel' 2>/dev/null)" || old_status='transport_error'
[ "$old_status" = 401 ] || [ "$old_status" = 403 ] || {
 echo 'STOP: TEST-Artikelroute ist anonym nicht nachweislich durch BasicAuth geschuetzt';exit 2;
}
echo 'OK: Testroot, Auth, privater Git-Stand und Dateirechte'

echo '1. Gepinnten Zielcommit laden und Diff-Whitelist pruefen'
gituser fetch --no-tags origin "$branch"
gituser cat-file -e "$target^{commit}"
gituser merge-base --is-ancestor "$old" "$target" || { echo 'STOP: kein Fast-Forward';exit 2; }
gituser merge-base --is-ancestor "$target" FETCH_HEAD || { echo 'STOP: Ziel nicht im Remote-Branch';exit 2; }
diff="$(gituser diff --name-status --no-renames "$old" "$target")"
[ -n "$diff" ] || { echo 'STOP: leeres Ziel-Diff';exit 2; }
while IFS="$(printf '\t')" read -r status path;do
 case "$status" in A|M) : ;; *) echo 'STOP: unzulaessiger Datei-Status';exit 2 ;; esac
 case "$path" in
  .github/workflows/hw24-platform-foundation.yml|\
  platform/ops/upgrade-private-framework-cockpit-ffonly.sh|\
  platform/ops/upgrade-test-cockpit-ffonly.sh|\
  platform/resources/views/filament/pages/article-lookup.blade.php|\
  platform/tests/Feature/ArticleWorkspaceLayoutTest.php)
   echo "Freigegeben: $path" ;;
  *) echo "STOP: nicht freigegebene Datei: $path";exit 2 ;;
 esac
done <<<"$diff"
echo '2. Bestehende Ansicht, Datenbank und Metadaten root-privat sichern'
install -d -m 700 "$backup_dir"
[ "$(stat -c '%U %a' "$backup_dir")" = 'root 700' ] || { echo 'STOP: Backupverzeichnis nicht root 0700';exit 2; }
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
file_backup="$(mktemp "$backup_dir/hw24-article-layout-before-$stamp-XXXXXXXX.blade.php")"
chmod 600 "$file_backup"
cp "$app/resources/views/filament/pages/article-lookup.blade.php" "$file_backup"
db_backup="$(mktemp "$backup_dir/hw24-article-layout-db-before-$stamp-XXXXXXXX.sqlite")"
chmod 600 "$db_backup"
sample="$(runuser -u "$owner" -- mktemp "$app/database/.hw24-article-preupgrade-XXXXXXXX.sqlite")"
runuser -u "$owner" -- "$php" -r '
$src=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$src->exec("PRAGMA busy_timeout=5000");
$src->prepare("VACUUM INTO ?")->execute([$argv[2]]);
$dst=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($dst->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(2);
' "$db" "$sample"
install -m 600 "$sample" "$db_backup"
rm -f -- "$sample"
sample=''
env_hash="$(sha256sum "$app/.env" | cut -d' ' -f1)"
lock_hash="$(sha256sum "$app/composer.lock" | cut -d' ' -f1)"
echo "OK: Vorherige Artikelansicht gesichert: $file_backup"
echo "OK: Private SQLite gesichert und geprueft: $db_backup"

echo '3. Nur private Laravel-Codeversion fast-forward aktualisieren'
gituser -c core.hooksPath=/dev/null merge --ff-only "$target"
updated=1
[ "$(gituser rev-parse HEAD)" = "$target" ] &&
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || { echo 'STOP: Nachkontrolle des Git-HEAD fehlgeschlagen';exit 2; }
[ "$(sha256sum "$app/.env" | cut -d' ' -f1)" = "$env_hash" ] &&
[ "$(sha256sum "$app/composer.lock" | cut -d' ' -f1)" = "$lock_hash" ] || { echo 'STOP: Private Konfiguration geaendert';exit 2; }

echo '4. Blade-Vorlagen mit Laravel kompilieren und Cache gezielt leeren'
runuser -u "$owner" -- /bin/bash -c '
 set -euo pipefail
 cd "$1"
 "$2" artisan view:clear --no-interaction >/dev/null
 "$2" artisan view:cache --no-interaction >/dev/null
 "$2" artisan route:list --path=admin --except-vendor >/dev/null
' hw24-artikel "$app" "$php"
for file in "$app/.env" "$db";do
 for u in nginx www-data;do
  if runuser -u "$u" -- test -r "$file";then echo 'STOP: private Datei nun lesbar';exit 2;fi
 done
done
[ "$(get_root)" = "$app/public" ] || { echo 'STOP: Test-Webroot geaendert';exit 2; }
status="$(curl -sS --max-time 12 --proto '=https' --output /dev/null --write-out '%{http_code}' 'https://test-artikelmanager.hairworld24.de/admin/artikel' 2>/dev/null)" || status='transport_error'
[ "$status" = 401 ] || [ "$status" = 403 ] || {
 echo "STOP: TEST-Basic-Auth fehlgeschlagen: HTTP $status";exit 2;
}
complete=1
echo '=== HW24 ARTIKELZENTRALE RESPONSIVE TEST-AKTUALISIERUNG OK ==='
echo "Privater Git-HEAD: $target"
echo "Rolle rueckwaerts (nur gepruefter Git-Code): $old"
echo "Artikelansicht-Backup: $file_backup"
echo "SQLite-Backup: $db_backup"
echo 'JTL, LIVE, Plesk-Git, DB-Schema und .env wurden nicht veraendert.'
echo 'Bitte Browser bei /admin/artikel vollstaendig neu laden und Screenshot vergleichen.'
