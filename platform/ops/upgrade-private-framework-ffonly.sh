#!/usr/bin/env bash
# HW24 private Filament source fast-forward ONLY. No domain or DB migrations.
# Pinned source commit checked against operator-confirmed private HEAD.
set -euo pipefail
umask 077
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
owner='nicovoss'
old='980bcce75a0a3516b7b6364fae5a0fa6aab8d112'
target='a26a64d09597e41523e224c557bb1d4e39bdf052'
branch='feature/hw24-platform-laravel13-filament5-20261122'
php='/opt/plesk/php/8.5/bin/php'
backup_dir='/root/hw24-backups'
echo '=== HW24 PRIVATE FRAMEWORK: GESICHERTES FAST-FORWARD ==='
test "$(id -u)" -eq 0 || { echo 'STOP: als root in PuTTY ausfuehren';exit 2; }
for bin in git runuser sha256sum awk mktemp; do command -v "$bin" >/dev/null || { echo "STOP: $bin fehlt";exit 2; }; done
test -d "$repo/.git" && test -f "$app/artisan" && test -x "$php" || {
 echo 'STOP: Privater Laravel-Klon oder PHP nicht gefunden';exit 2;
}
test -f "$app/.env" && test ! -L "$app/.env" &&
test -f "$app/database/database.sqlite" && test ! -L "$app/database/database.sqlite" || {
 echo 'STOP: Private .env oder SQLite nicht sicher vorhanden';exit 2;
}
test -f "$app/composer.lock" && test ! -L "$app/composer.lock" || {
 echo 'STOP: Server-lokale Composer-Lockdatei nicht vorhanden';exit 2;
}
test -f "$app/vendor/autoload.php" || { echo 'STOP: installierte Pakete fehlen';exit 2; }
id "$owner" >/dev/null
gituser(){ runuser -u "$owner" -- git -C "$repo" "$@"; }
phpuser(){ runuser -u "$owner" -- "$php" "$@"; }
current="$(gituser rev-parse HEAD)"
current_branch="$(gituser symbolic-ref -q --short HEAD || true)"
echo "Privater HEAD vorab: $current"
echo "Privater Git-Branch: $current_branch"
test "$current" = "$old" || { echo 'STOP: Privater HEAD geaendert; keine automatische Aktualisierung';exit 2; }
test "$current_branch" = "$branch" || {
 echo 'STOP: Privater Branch nicht eindeutig. Keine Git-Zustandsaenderung';exit 2;
}
test -z "$(gituser status --porcelain --untracked-files=no)" || {
 echo 'STOP: Lokale getrackte Dateien sind modifiziert; keine Ueberschreibung';exit 2;
}
remote="$(gituser remote get-url origin)"
case "$remote" in
 https://github.com/NicoVoss1/HW24-scanner|https://github.com/NicoVoss1/HW24-scanner.git|git@github.com:NicoVoss1/HW24-scanner|git@github.com:NicoVoss1/HW24-scanner.git)
  echo 'Origin: erwartetes GitHub-Repository bestaetigt (ohne Zugangsdaten-Ausgabe)' ;;
 *) echo 'STOP: Git-Remote nicht verifiziert';exit 2 ;;
esac

# Private file hashes stay in memory only; secrets/hashes are not printed.
env_before="$(sha256sum "$app/.env" | awk '{print $1}')"
sqlite_before="$(sha256sum "$app/database/database.sqlite" | awk '{print $1}')"
lock_before="$(sha256sum "$app/composer.lock" | awk '{print $1}')"

echo '1. Nur Remote-Objekte laden, keinerlei Plesk-Deployment ...'
gituser -c core.hooksPath=/dev/null fetch --no-tags --no-recurse-submodules origin "refs/heads/$branch"
gituser cat-file -e "$target^{commit}" || { echo 'STOP: Gepinnter Zielcommit nicht erreichbar';exit 2; }
gituser merge-base --is-ancestor "$old" "$target" || {
 echo 'STOP: Kein nachgewiesener Fast-Forward vom alten Commit';exit 2;
}
if ! gituser merge-base --is-ancestor "$target" FETCH_HEAD; then
 echo 'STOP: GitHub-Branch enthaelt nicht mehr den freigegebenen Zielcommit';exit 2;
fi
echo '2. Unterschiede der fest gepinnten Version pruefen ...'
while IFS= read -r path; do
 case "$path" in
  .github/workflows/hw24-platform-foundation.yml|platform/README.md|platform/app/Domain/Jtl/SourceSnapshot.php|platform/app/Filament/Pages/ArticleLookup.php|platform/app/Services/LegacyReadBridge.php|platform/database/migrations/2026_11_22_000002_create_jtl_source_catalog_tables.php|platform/database/seeders/DatabaseSeeder.php|platform/database/seeders/ObservedSourcesSeeder.php|platform/ops/verify-filament-loopback-http.sh|platform/ops/verify-testdomain-cutover-readonly.sh|platform/resources/views/filament/pages/article-lookup.blade.php|platform/tests/Feature/SourceCatalogSafetyTest.php|platform/tests/Unit/SourceSnapshotTest.php)
   echo "Erlaubte Quellcodeaenderung: $path" ;;
  *) echo "STOP: Unerwarteter Dateipfad $path"; exit 2 ;;
 esac
done < <(gituser diff --name-only "$old" "$target")
if gituser diff --name-status "$old" "$target" | grep -Eq '^[DR][[:space:]]'; then
 echo 'STOP: Unerwartete Dateiloeschung/-umbenennung';exit 2;
fi
install -d -m 700 "$backup_dir"
record="$(mktemp "$backup_dir/hw24-private-fw-fastforward-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.txt")"
chmod 600 "$record"
printf 'BEFORE=%s\nAFTER=%s\nBRANCH=%s\n' "$old" "$target" "$branch" > "$record"
echo "Rueckfall-Referenz (nur SHA, keine Secrets): $record"

echo '3. Nur den privaten Framework-Git-Klon vorwaerts bewegen ...'
gituser -c core.hooksPath=/dev/null merge --ff-only "$target"
test "$(gituser rev-parse HEAD)" = "$target" || { echo 'STOP: HEAD nach Update nicht erwartet';exit 2; }

echo '4. Private Laravel-Konfiguration und SQLite unveraendert pruefen ...'
test "$(sha256sum "$app/.env" | awk '{print $1}')" = "$env_before" || {
 echo 'STOP: .env wurde veraendert, ohne Webroot-Umstellung. Manuelle Pruefung!';exit 2;
}
test "$(sha256sum "$app/database/database.sqlite" | awk '{print $1}')" = "$sqlite_before" || {
 echo 'STOP: SQLite wurde veraendert, ohne Webroot-Umstellung. Manuelle Pruefung!';exit 2;
}
test "$(sha256sum "$app/composer.lock" | awk '{print $1}')" = "$lock_before" || {
 echo 'STOP: Composer Lockfile wurde veraendert. Manuelle Pruefung!';exit 2;
}
echo 'OK: .env, SQLite und composer.lock bytegenau unveraendert'

echo '5. Neue Plattform-PHP-Klassen und Admin-Routen pruefen ...'
for path in  app/Domain/Jtl/SourceSnapshot.php  app/Filament/Pages/ArticleLookup.php  app/Services/LegacyReadBridge.php  database/migrations/2026_11_22_000002_create_jtl_source_catalog_tables.php; do
 phpuser -l "$app/$path"
done
phpuser "$app/artisan" --version
phpuser "$app/artisan" route:list --path=admin
echo 'Migrationen bleiben UNVERAENDERT und werden separat freigegeben.'
echo 'Keine Composer-Aktion, keine Secret-Datei-Ausgabe, kein HTTP-/Git-/Plesk-Live-Deploy.'
echo '=== FRAMEWORK QUELLCODE AKTUALISIERT, TEST- UND LIVE-DOMAIN UNVERAENDERT ==='
