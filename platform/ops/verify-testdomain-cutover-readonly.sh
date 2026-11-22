#!/usr/bin/env bash
# Read-only preflight before Plesk Test-vHost points at Laravel platform/public.
# NO changes to git, webroots, Plesk, credentials, Laravel app, or databases.
set -u
echo '=== HW24 TESTDOMAIN PRE-CUTOVER (NUR LESEN) ==='
date -u '+UTC %Y-%m-%d %H:%M:%S'
site='test-artikelmanager.hairworld24.de'
legacy='/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de'
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
app="$repo/platform"
php='/opt/plesk/php/8.5/bin/php'
issues=0
warn(){ printf 'PRUEFEN: %s\n' "$*"; issues=$((issues+1)); }
ok(){ printf 'OK: %s\n' "$*"; }
[ "$(id -u)" -eq 0 ] || { echo 'STOP: Bitte als root in PuTTY ausfuehren'; exit 2; }
command -v plesk >/dev/null || { echo 'STOP: Plesk-CLI nicht verfuegbar'; exit 2; }
command -v curl >/dev/null || { echo 'STOP: curl nicht verfuegbar'; exit 2; }
echo '1. Plesk DocumentRoot (nur Datenbank-SELECT)'
meta="$(plesk db -Ne "SELECT d.name,h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null)" || meta=''
printf 'Plesk vHost: %s\n' "$meta"
case "$meta" in
 *"$legacy"*) ok 'Derzeitiger Legacy-Test-DocumentRoot bestaetigt' ;;
 *) warn 'Plesk-Domain/DocumentRoot weicht von der erwarteten alten TEST-Domain ab' ;;
esac
[ -f "$legacy/index.html" ] && [ -f "$legacy/api/amazon-asin.php" ] ||
 warn 'Alte Testdomain-Dateien inkl. einzeln ausgerolltem ASIN-Endpunkt nicht vollstaendig'
echo 'Bestehendes manuelles Plesk-Git (nur HEAD lesen):'
if ! plesk ext git --get-last-commit -domain "$site" -name HW24-scanner.git 2>/dev/null; then
 warn 'Bestehendes Plesk-Git-Repository nicht zweifelsfrei aufgeloest'
fi
echo '2. Privater Framework-Git-Stand'
if [ -d "$repo/.git" ] && id nicovoss >/dev/null 2>&1; then
 head="$(runuser -u nicovoss -- git -C "$repo" rev-parse HEAD 2>/dev/null)" || head=''
 echo "Aktueller privater HEAD: $head"
 if [ -n "$head" ] && runuser -u nicovoss -- git -C "$repo" ls-tree -r --name-only HEAD -- platform/app/Domain/Jtl/SourceSnapshot.php | grep -qx 'platform/app/Domain/Jtl/SourceSnapshot.php'; then
  ok 'Framework-Klon enthaelt die neuere JTL-Quellansicht'
 else
  warn 'Framework-Klon enthaelt noch nicht die neuere JTL-Quellansicht aus PR36 (kein blindes Git-Pull)'
 fi
 if runuser -u nicovoss -- git -C "$repo" diff --quiet --exit-code &&
    runuser -u nicovoss -- git -C "$repo" diff --cached --quiet --exit-code; then
  ok 'Keine lokalen getrackten Aenderungen am Framework-Klon'
 else warn 'Lokale getrackte Aenderungen; zuerst getrennt pruefen'; fi
else
 warn 'Privater Framework-Klon oder nicovoss nicht verfuegbar'
fi
echo '3. Dateien nur auf Existenz und Symlink-Sicherheit pruefen'
for file in "$app/artisan" "$app/public/index.php" "$app/vendor/autoload.php"  "$app/.env" "$app/database/database.sqlite"  "$app/public/css/filament/filament/app.css"  "$app/public/js/filament/filament/app.js"; do
 if [ -f "$file" ] && [ ! -L "$file" ]; then
  ok "Vorhanden: $(basename "$file")"
 else warn "Nicht vorhanden oder Symlink: $(basename "$file")"; fi
done
[ -x "$php" ] || warn 'PHP 8.5 CLI nicht verfuegbar'
echo 'Private Dateien und APP_KEY werden nicht gelesen oder ausgegeben.'
echo '4. Anonyme HTTP-Auth auf Live und Test'
for host in artikelmanager.hairworld24.de test-artikelmanager.hairworld24.de; do
 status="$(curl -sS --max-time 10 --output /dev/null --write-out '%{http_code}'   --proto '=https' "https://$host/api/jtl.php?ean=3474630337398" 2>/dev/null)" || status='transport_error'
 if [ "$status" = '401' ] || [ "$status" = '403' ]; then
  ok "$host: HTTP $status geschuetzt"
 else warn "$host: Unerwarteter anonymer Status $status"; fi
done
echo "=== PRE-CUTOVER ENDE: $issues Abweichung(en) ==="
echo 'Nichts veraendert. Kein Git-Pull. Kein DocumentRoot-Wechsel.'
echo 'Echter HTTPS-Login/FPM-/Session-Test bleibt nach Cutover gesondert noetig.'
[ "$issues" -eq 0 ]
