#!/usr/bin/env bash
# Operator-only FIRST legacy workflow recovery rollout for private Laravel TEST.
# Pin to PR42 head 6c9be5c; support either proven article-layout TEST starting HEAD.
# DO NOT change LIVE, Plesk, JTL, marketplace, Laravel .env or DB schema.
set -Eeuo pipefail
umask 077

site='test-artikelmanager.hairworld24.de'
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
branch='feature/hw24-legacy-workflows-recovery-20261122'
old_branch='feature/hw24-platform-laravel13-filament5-20261122'
target='6c9be5c6258800feb65f20c036e6d7526c7fb803'
base_one='d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4'
base_two='285b5523c5ccc8faa00f14607cc40f03be55845d'
owner='nicovoss'
php='/opt/plesk/php/8.5/bin/php'
db="$app/database/database.sqlite"
backup_dir='/root/hw24-backups'
before=''
old=''
sample=''
changed=0
passed=0
gituser(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
root_now(){ plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null; }

restore(){
 code=$?
 trap - EXIT
 if [ -n "$sample" ];then rm -f -- "$sample";fi
 if [ "$changed" = 1 ] && [ "$passed" = 0 ];then
  echo 'SICHERHEITSSTOP: Code-Ruecknahme auf vorherigen Framework-Stand wird versucht.'
  current="$(gituser rev-parse HEAD 2>/dev/null || true)"
  tracked="$(gituser status --porcelain --untracked-files=no 2>/dev/null || echo ERROR)"
  if [ "$current" = "$target" ] && [ -z "$tracked" ] && [ -n "$old" ];then
   if gituser -c core.hooksPath=/dev/null reset --hard "$old" >/dev/null 2>&1;then
    runuser -u "$owner" -- /bin/bash -c 'cd "$1" && "$2" artisan view:clear --no-interaction >/dev/null' hw24-reset "$app" "$php" || true
    echo 'CODE-ROLLBACK OK: Vorherige Filament-Oberflaeche wiederhergestellt.'
   else
    echo 'ALARM: Code-Rollback nicht erfolgreich. Keine Plesk-Aktion ausfuehren.'
   fi
  else
   echo 'ALARM: Repo hat inzwischen einen anderen Stand. Kein blindes git reset.'
  fi
 fi
 exit "$code"
}
trap restore EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== HW24 TEST: BESTEHENDE WORKFLOWS IN FILAMENT WIEDER ANBINDEN ==='
[ "$(id -u)" -eq 0 ] || { echo 'STOP: Bitte als root in PuTTY starten';exit 2; }
for x in plesk runuser git sha256sum mktemp install curl stat;do command -v "$x" >/dev/null || { echo "STOP: $x fehlt";exit 2; };done
[ -x "$php" ] && [ -d "$repo/.git" ] && [ -f "$app/artisan" ] || { echo 'STOP: Privates Laravel-Framework fehlt';exit 2; }
[ "$(root_now)" = "$app/public" ] || { echo 'STOP: Nur die bestehende HTTPS-Testdomain ist fuer dieses Update erlaubt';exit 2; }
old="$(gituser rev-parse HEAD)"
case "$old" in
 "$base_one"|"$base_two") echo "Gepruefter privater Ausgangsstand: $old" ;;
 *) echo "STOP: Abweichender privater HEAD: $old. Erst genaue Version pruefen.";exit 2 ;;
esac
[ "$(gituser branch --show-current)" = "$old_branch" ] || {
 echo 'STOP: Der bestehende private Branch ist unerwartet. Kein Wechsel.';exit 2;
}
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || {
 echo 'STOP: Lokale getrackte Aenderungen; keine Ueberschreibung';exit 2;
}
for f in "$app/.env" "$db" "$app/composer.lock" "$app/vendor/autoload.php";do
 [ -f "$f" ] && [ ! -L "$f" ] || { echo 'STOP: Private Laravel-Datei fehlt oder ist ein Symlink';exit 2; }
done
for f in "$app/public/.htaccess" "$app/public/index.php";do
 for u in nicovoss nginx www-data;do
  runuser -u "$u" -- test -r "$f" || { echo 'STOP: Filament-PUBLIC-ACL nicht intakt';exit 2; }
 done
done
for f in "$app/.env" "$db";do
 for u in nginx www-data;do
  if runuser -u "$u" -- test -r "$f";then echo 'STOP: Private Datei ist bereits fuer Webserver lesbar';exit 2;fi
 done
done
http_before="$(curl -sS --max-time 12 --proto '=https' --output /dev/null --write-out '%{http_code}' "https://$site/admin/werkzeuge" 2>/dev/null)" || http_before='transport_error'
case "$http_before" in 401|403) : ;; *)
 echo "STOP: Bisheriger TEST-BasicAuth-Zugriff nicht gesichert: HTTP $http_before";exit 2 ;;
esac
echo 'OK: Git, alter TEST-vHost, BasicAuth und private Dateirechte'

echo '1. Pinned recovery commit aus GitHub laden'
gituser fetch --no-tags origin "$branch"
gituser cat-file -e "$target^{commit}"
gituser merge-base --is-ancestor "$old" "$target" || { echo 'STOP: Ziel ist kein Fast-Forward';exit 2; }
gituser merge-base --is-ancestor "$target" FETCH_HEAD || { echo 'STOP: Ziel nicht im erwarteten PR-Branch';exit 2; }
echo '2. Nur genehmigten Bestand von zehn Pfaden aktualisieren'
changes="$(gituser diff --name-status --no-renames "$old" "$target")"
[ -n "$changes" ] || { echo 'STOP: Leerer Diff';exit 2; }
while IFS="$(printf '\t')" read -r status path;do
 case "$status" in A|M) : ;; *) echo 'STOP: Unzulässige Dateioperation';exit 2;; esac
 case "$path" in
  .github/workflows/hw24-platform-foundation.yml|\
  platform/app/Filament/Pages/LegacyWorkspace.php|\
  platform/app/Filament/Pages/ScanWorkbench.php|\
  platform/app/Providers/Filament/AdminPanelProvider.php|\
  platform/app/Services/LegacyReadBridge.php|\
  platform/ops/upgrade-test-article-layout-ffonly.sh|\
  platform/resources/views/filament/pages/article-lookup.blade.php|\
  platform/resources/views/filament/pages/legacy-workspace.blade.php|\
  platform/resources/views/filament/pages/scan-workbench.blade.php|\
  platform/tests/Feature/LegacyWorkflowRecoveryTest.php)
   echo "Erlaubt: $path";;
  *) echo "STOP: Unerwarteter Pfad im Git-Diff: $path";exit 2;;
 esac
done <<<"$changes"

echo '3. Geschuetzte Sicherungen vor Git-Fast-Forward erstellen'
install -d -m 700 "$backup_dir"
[ "$(stat -c '%U %a' "$backup_dir")" = 'root 700' ] || { echo 'STOP: Backup-Verzeichnis ist nicht root 0700';exit 2; }
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
before="$(mktemp "$backup_dir/hw24-legacy-recovery-before-$stamp-XXXXXXXX.sqlite")"
chmod 600 "$before"
sample="$(runuser -u "$owner" -- mktemp "$app/database/.hw24-legacy-recovery-XXXXXXXX.sqlite")"
runuser -u "$owner" -- "$php" -r '
$db=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("PRAGMA busy_timeout=5000");
$db->prepare("VACUUM INTO ?")->execute([$argv[2]]);
$copy=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($copy->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(2);
' "$db" "$sample"
install -m 600 "$sample" "$before"
rm -f -- "$sample"
sample=''
env_hash="$(sha256sum "$app/.env" | cut -d' ' -f1)"
lock_hash="$(sha256sum "$app/composer.lock" | cut -d' ' -f1)"
meta="$(mktemp "$backup_dir/hw24-legacy-recovery-git-$stamp-XXXXXXXX.txt")"
chmod 600 "$meta"
printf 'previous=%s\ntarget=%s\nsqlite_backup=%s\n' "$old" "$target" "$before" > "$meta"
echo "OK: Integritaetsgeprueftes SQLite-Backup: $before"

echo '4. Nur privaten Git-Checkout per Fast-Forward aktualisieren'
gituser -c core.hooksPath=/dev/null merge --ff-only "$target"
changed=1
[ "$(gituser rev-parse HEAD)" = "$target" ] &&
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || { echo 'STOP: Unerwarteter Codezustand nach Merge';exit 2; }
[ "$(sha256sum "$app/.env" | cut -d' ' -f1)" = "$env_hash" ] &&
[ "$(sha256sum "$app/composer.lock" | cut -d' ' -f1)" = "$lock_hash" ] || { echo 'STOP: .env/composer.lock unerwartet veraendert';exit 2; }
for f in \
 "$app/app/Filament/Pages/LegacyWorkspace.php" \
 "$app/app/Filament/Pages/ScanWorkbench.php" \
 "$app/app/Providers/Filament/AdminPanelProvider.php" \
 "$app/app/Services/LegacyReadBridge.php";do
 "$php" -l "$f" >/dev/null || { echo 'STOP: PHP-Syntaxfehler';exit 2; }
done
echo '5. Blade neu kompilieren und beide neuen Admin-Routen pruefen'
runuser -u "$owner" -- /bin/bash -c '
set -euo pipefail
cd "$1"
"$2" artisan view:clear --no-interaction >/dev/null
"$2" artisan view:cache --no-interaction >/dev/null
"$2" artisan route:list --path=admin --except-vendor
' hw24-panel "$app" "$php"
# Parse Laravel route JSON by exact URI; ignore whitespace in JSON representation.
routes="$(runuser -u "$owner" -- "$php" "$app/artisan" route:list --path=admin --except-vendor --json)"
printf '%s' "$routes" | "$php" "$app/ops/verify-admin-routes-json.php" admin/scanliste admin/werkzeuge
for f in "$app/.env" "$db";do
 for u in nginx www-data;do
  if runuser -u "$u" -- test -r "$f";then echo 'STOP: Private Datei nun lesbar';exit 2;fi
 done
done
[ "$(root_now)" = "$app/public" ] || { echo 'STOP: Test-Webroot wurde veraendert';exit 2; }
http_after="$(curl -sS --max-time 12 --proto '=https' --output /dev/null --write-out '%{http_code}' "https://$site/admin/scanliste" 2>/dev/null)" || http_after='transport_error'
case "$http_after" in 401|403) : ;; *)
 echo "STOP: Auth-Schutz nach Codewechsel nicht nachweisbar: HTTP $http_after";exit 2 ;;
esac
passed=1
echo '=== HW24 TEST: ALTE WERKZEUGE UND SCANLISTE ERFOLGREICH ANGEBUNDEN ==='
echo "Privater Git-HEAD: $target"
echo "SQLite-Backup: $before"
echo "Rollback-Metadaten: $meta"
echo 'Keine Live-/Plesk-Umstellung, keine MariaDB-/JTL-/Amazon-/KI-Schreibaktion.'
echo 'Erst Browser in /admin/werkzeuge, /admin/scanliste, /admin/artikel testen.'
