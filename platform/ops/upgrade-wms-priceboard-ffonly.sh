#!/usr/bin/env bash
# Operator-only rollout of pinned, CI-verified WMS, Price Board, Native AI/Scan code.
# Keeps existing HTTPS TEST vHost and LIVE legacy app untouched.
# No schema migrations, no Plesk-Git deploy, no paid calls and no JTL writes.
set -Eeuo pipefail
umask 077
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
site='test-artikelmanager.hairworld24.de'
owner='nicovoss'
php='/opt/plesk/php/8.5/bin/php'
target='0d8bb9baf85365d4c07a1c17450ec0674bac3989'
branch='feature/hw24-wms-priceboard-and-release-gates-20261122'
old=''
backup=''
sample=''
metadata=''
started=0
success=0

gituser(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
getroot(){
 plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null
}
cleanup(){
 rc=$?
 trap - EXIT
 if [ -n "$sample" ];then rm -f -- "$sample";fi
 if [ "$started" -eq 1 ] && [ "$success" -eq 0 ];then
  echo 'STOP NACH FAST-FORWARD: Nur geprueften TEST-Framework-Git-Code zurueckstellen.'
  now="$(gituser rev-parse HEAD 2>/dev/null || true)"
  dirty="$(gituser status --porcelain --untracked-files=no 2>/dev/null || echo ERROR)"
  if [ "$now" = "$target" ] && [ -z "$dirty" ] && [ -n "$old" ];then
   if gituser -c core.hooksPath=/dev/null reset --hard "$old" >/dev/null 2>&1;then
    runuser -u "$owner" -- /bin/bash -c 'cd "$1" && "$2" artisan view:clear --no-interaction >/dev/null' hw24-rollback "$app" "$php" || true
    echo 'GIT-ROLLBACK OK: Privater Code wieder auf Ursprungsstand.'
   else
    echo 'WARNUNG: Code-Rollback konnte nicht abgeschlossen werden. Keine weiteren Deployments starten.'
   fi
  else
   echo 'WARNUNG: Git-Stand veraendert, deshalb keine blinde automatische Ruecknahme.'
  fi
 fi
 exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== HW24 TEST: NATIVE KI, AMAZON, WMS, KANALPREISE UND AMEISE ==='
[ "$(id -u)" -eq 0 ] || { echo 'STOP: root-PuTTY erforderlich';exit 2; }
for tool in plesk git runuser sha256sum stat mktemp install curl grep;do
 command -v "$tool" >/dev/null || { echo "STOP: $tool fehlt";exit 2; }
done
[ -x "$php" ] && [ -d "$repo/.git" ] && [ -f "$app/artisan" ] || {
 echo 'STOP: privates Laravel-Framework nicht bereit';exit 2;
}
[ "$(getroot)" = "$app/public" ] || {
 echo 'STOP: Plesk-TEST-DocumentRoot ist nicht mehr das private Laravel/public';exit 2;
}
old="$(gituser rev-parse HEAD)"
case "$old" in
 d5491f28ac27c5be6bc7aba0f714c7cd83ea24d4|\
 285b5523c5ccc8faa00f14607cc40f03be55845d|\
 6c9be5c6258800feb65f20c036e6d7526c7fb803|\
 aa13ff0c7b2416f547835d7badcf29a645c01b5b)
   echo "Privater gepruefter Ausgangsstand: $old";;
 *)
   echo "STOP: Unbekannter privater Framework-HEAD $old, keine Aenderung.";exit 2;;
esac
case "$(gituser branch --show-current)" in
 feature/hw24-platform-laravel13-filament5-20261122|\
 feature/hw24-legacy-workflows-recovery-20261122)
  : ;;
 *) echo 'STOP: Privater Git-Branch unerwartet';exit 2;;
esac
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || {
 echo 'STOP: Getrackte lokale Code-Aenderungen vorhanden';exit 2;
}
for f in "$app/.env" "$app/composer.lock" "$app/vendor/autoload.php" "$app/database/database.sqlite";do
 [ -f "$f" ] && [ ! -L "$f" ] || { echo 'STOP: Private Datei fehlt oder ist Symlink';exit 2; }
done
for f in "$app/public/.htaccess" "$app/public/index.php";do
 for u in nicovoss nginx www-data;do
  runuser -u "$u" -- test -r "$f" || {
   echo 'STOP: Bestehender Filament-PUBLIC-Zugriff fehlerhaft';exit 2;
  }
 done
done
for f in "$app/.env" "$app/database/database.sqlite";do
 for u in nginx www-data;do
  if runuser -u "$u" -- test -r "$f";then
   echo 'STOP: Private Datei fuer einen Webserver-Benutzer lesbar';exit 2;
  fi
 done
done
before_http="$(curl -sS --max-time 10 --proto '=https' --output /dev/null --write-out '%{http_code}' "https://$site/admin/intelligence" 2>/dev/null)" || before_http='transport_error'
case "$before_http" in 401|403) : ;; *)
 echo "STOP: Anonymer Basic-Auth-Schutz fehlt oder nicht nachweisbar: $before_http";exit 2;;
esac
echo 'OK: TEST-HTTPS, private Dateien, PUBLIC-ACLs, Git und BasicAuth sicher'

echo '1. Pinned Source HEAD aus separat gefuehrtem PR43 laden'
gituser fetch --no-tags origin "$branch"
gituser cat-file -e "$target^{commit}"
gituser merge-base --is-ancestor "$old" "$target" || {
 echo 'STOP: Zielcode ist kein Fast-Forward';exit 2;
}
gituser merge-base --is-ancestor "$target" FETCH_HEAD || {
 echo 'STOP: Gepinnter Zielcode nicht im erwarteten PR-Branch';exit 2;
}

echo '2. Nur freigegebenen Quellcode-Dateisatz akzeptieren'
diff="$(gituser diff --name-status --no-renames "$old" "$target")"
[ -n "$diff" ] || { echo 'STOP: Ziel-Diff leer';exit 2; }
while IFS="$(printf '\t')" read -r status path;do
 case "$status" in A|M) : ;; *) echo 'STOP: Git-Dateioperation ausserhalb Freigabe';exit 2;; esac
 case "$path" in
  .github/workflows/hw24-platform-foundation.yml|\
  api/amazon-archive.php|\
  platform/app/Filament/Pages/IntelligenceWorkbench.php|\
  platform/app/Filament/Pages/LegacyWorkspace.php|\
  platform/app/Filament/Pages/ScanWorkbench.php|\
  platform/app/Filament/Pages/WarehousePriceWorkbench.php|\
  platform/app/Providers/Filament/AdminPanelProvider.php|\
  platform/app/Services/LegacyApprovedActionBridge.php|\
  platform/app/Services/LegacyReadBridge.php|\
  platform/config/hw24_legacy.php|\
  platform/ops/upgrade-native-ai-amazon-scan-ffonly.sh|\
  platform/ops/verify-admin-routes-json.php|\
  platform/ops/upgrade-test-article-layout-ffonly.sh|\
  platform/ops/upgrade-test-legacy-workflows-ffonly.sh|\
  platform/resources/views/filament/pages/article-lookup.blade.php|\
  platform/resources/views/filament/pages/intelligence-workbench.blade.php|\
  platform/resources/views/filament/pages/legacy-workspace.blade.php|\
  platform/resources/views/filament/pages/scan-workbench.blade.php|\
  platform/resources/views/filament/pages/warehouse-price-workbench.blade.php|\
  platform/tests/Feature/LegacyWorkflowRecoveryTest.php|\
  platform/tests/Feature/NativeIntelligenceAndScanTest.php|\
  platform/tests/Feature/WarehouseAndPriceBoardTest.php)
   echo "Erlaubt: $path";;
  *) echo "STOP: Unerwartete Datei $path";exit 2;;
 esac
done <<<"$diff"

echo '3. Root-privates, konsistentes SQLite-Backup und Ausgangscode sichern'
dir='/root/hw24-backups'
install -d -m 700 "$dir"
[ "$(stat -c '%U %a' "$dir")" = 'root 700' ] || {
 echo 'STOP: Backup-Verzeichnis ist nicht root 0700';exit 2;
}
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup="$(mktemp "$dir/hw24-native-workflows-before-$stamp-XXXXXXXX.sqlite")"
chmod 600 "$backup"
sample="$(runuser -u "$owner" -- mktemp "$app/database/.hw24-native-preupgrade-XXXXXXXX.sqlite")"
runuser -u "$owner" -- "$php" -r '
$source=new PDO("sqlite:".$argv[1],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$source->exec("PRAGMA busy_timeout=5000");
$source->prepare("VACUUM INTO ?")->execute([$argv[2]]);
$snapshot=new PDO("sqlite:".$argv[2],null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($snapshot->query("PRAGMA integrity_check")->fetchColumn()!=="ok")exit(2);
' "$app/database/database.sqlite" "$sample"
install -m 600 "$sample" "$backup"
rm -f -- "$sample"
sample=''
env_sha="$(sha256sum "$app/.env" | cut -d' ' -f1)"
lock_sha="$(sha256sum "$app/composer.lock" | cut -d' ' -f1)"
metadata="$(mktemp "$dir/hw24-native-workflows-git-$stamp-XXXXXXXX.txt")"
chmod 600 "$metadata"
printf 'before=%s\npinned_after=%s\nsqlite_backup=%s\n' "$old" "$target" "$backup" > "$metadata"
echo "OK: Integritaetsgeprueftes Backup: $backup"

echo '4. Privaten TEST-Framework-Code ausschliesslich per Fast-Forward aktualisieren'
gituser -c core.hooksPath=/dev/null merge --ff-only "$target"
started=1
[ "$(gituser rev-parse HEAD)" = "$target" ] &&
[ -z "$(gituser status --porcelain --untracked-files=no)" ] || {
 echo 'STOP: Git-Nachkontrolle nicht erfolgreich';exit 2;
}
[ "$(sha256sum "$app/.env" | cut -d' ' -f1)" = "$env_sha" ] &&
[ "$(sha256sum "$app/composer.lock" | cut -d' ' -f1)" = "$lock_sha" ] || {
 echo 'STOP: Private Konfiguration oder Abhaengigkeiten veraendert';exit 2;
}

echo '5. Neue PHP-Klassen pruefen, Blade kompilieren und Routen kontrollieren'
for file in \
 "$app/app/Filament/Pages/IntelligenceWorkbench.php" \
 "$app/app/Filament/Pages/ScanWorkbench.php" \
 "$app/app/Filament/Pages/WarehousePriceWorkbench.php" \
 "$app/app/Services/LegacyApprovedActionBridge.php" \
 "$app/app/Providers/Filament/AdminPanelProvider.php";do
 "$php" -l "$file" >/dev/null || { echo 'STOP: PHP-Syntaxfehler';exit 2; }
done
runuser -u "$owner" -- /bin/bash -c '
 set -euo pipefail
 cd "$1"
 "$2" artisan view:clear --no-interaction >/dev/null
 "$2" artisan view:cache --no-interaction >/dev/null
 "$2" artisan route:list --path=admin --except-vendor --json > /dev/null
' hw24-native "$app" "$php"
routes="$(runuser -u "$owner" -- "$php" "$app/artisan" route:list --path=admin --except-vendor --json)"
# Parse Laravel route JSON by exact URI; ignore whitespace in JSON representation.
routes="$(runuser -u "$owner" -- "$php" "$app/artisan" route:list --path=admin --except-vendor --json)"
printf '%s' "$routes" | "$php" "$app/ops/verify-admin-routes-json.php" admin/intelligence admin/scanliste admin/werkzeuge admin/artikel admin/lager-preise
for f in "$app/.env" "$app/database/database.sqlite";do
 for u in nginx www-data;do
  if runuser -u "$u" -- test -r "$f";then
   echo 'STOP: Privates Dateirecht versehentlich geaendert';exit 2;
  fi
 done
done
[ "$(getroot)" = "$app/public" ] || {
 echo 'STOP: Plesk-Testroot unerwartet veraendert';exit 2;
}
after_http="$(curl -sS --max-time 10 --proto '=https' --output /dev/null --write-out '%{http_code}' "https://$site/admin/intelligence" 2>/dev/null)" || after_http='transport_error'
case "$after_http" in 401|403) : ;; *)
 echo "STOP: Anonymer Basic-Auth-Schutz nach Update nicht nachweisbar: $after_http";exit 2;;
esac

success=1
echo '=== HW24 NATIVE WMS- UND PREISFUNKTIONSMODULE AUF HTTPS-TEST AKTUALISIERT ==='
echo "Privater Git-HEAD: $target"
echo "SQLite-Backup: $backup"
echo "Rollback-Metadaten: $metadata"
echo 'Keine Plesk-/LIVE-/MariaDB-/JTL-Aktion, keine OpenAI-/Amazon-Anfrage im Upgrade.'
echo 'Private Freigaben fuer Scan-POST und kostenpflichtige KI bleiben standardmaessig AUS.'
echo 'Amazon-Kataloghistorie braucht weiterhin den getrennten LIVE-GET-Endpunkt.'
echo 'Im Browser /admin/lager-preise, /admin/intelligence und /admin/scanliste pruefen.'
