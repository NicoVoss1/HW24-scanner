#!/usr/bin/env bash
# Safe one-file correction of the manual TEST-only Amazon ASIN endpoint.
# Pins both source commit and exact Git blob. No live/domain/JTL/API writes.
set -euo pipefail
umask 077
root='/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de'
target="$root/api/amazon-asin.php"
php='/opt/plesk/php/8.5/bin/php'
old='617e2162f20dd012ed8c4fe8cc73d12b5e0d2373'
new='712d2c4a565258cefac93c4282e06b8604d384b8'
source='https://raw.githubusercontent.com/NicoVoss1/HW24-scanner/e697ee6a2b21cf1884dba1cf95f448e56ea2aee2/api/amazon-asin.php'
backup_dir='/root/hw24-backups'
tmp=''
staged=''
backup=''
changed=0

onexit(){
  rc=$?
  if [ "$rc" -ne 0 ] && [ "$changed" = 1 ]; then
    echo 'FEHLER nach TEST-Update: versuche, nur die ASIN-Testdatei zurueckzusetzen.'
    # Never overwrite a file independently changed after our replacement.
    if [ -n "$backup" ] && [ -f "$backup" ] &&
       [ -f "$target" ] && [ "$(git hash-object "$target" 2>/dev/null || true)" = "$new" ]; then
      restore="$(mktemp "$root/api/.hw24-asin-restore-XXXXXXXX.php")"
      cat "$backup" > "$restore"
      chmod --reference="$target" "$restore"
      chown --reference="$target" "$restore"
      mv -fT "$restore" "$target"
      echo 'TEST-Datei auf gesicherten Vorher-Stand zurueckgesetzt.'
    else
      echo 'WARNUNG: Keine automatische Wiederherstellung moeglich, Backup sicher aufbewahren.'
    fi
  fi
  [ -z "$tmp" ] || rm -f -- "$tmp"
  [ -z "$staged" ] || rm -f -- "$staged"
}
trap onexit EXIT

echo '=== HW24 TEST: AMAZON EAN STRING-FIX ==='
test "$(id -u)" -eq 0
test -d "$root/api" && test ! -L "$root" && test ! -L "$root/api"
test -f "$target" && test ! -L "$target"
test -x "$php"
test -f "$root/api/jtl.php" && test -f "$root/api/amazon.php"

current="$(git hash-object "$target")"
if [ "$current" = "$new" ]; then
  echo 'Korrektur bereits auf TEST vorhanden; kein zweites Update.'
elif [ "$current" = "$old" ]; then
  tmp="$(mktemp /root/hw24-amazon-ean-fix-XXXXXXXX.php)"
  curl --fail --silent --show-error --max-time 25 --proto '=https' "$source" -o "$tmp"
  test "$(git hash-object "$tmp")" = "$new" || {
    echo 'STOP: GitHub-Quell-SHA weicht ab';exit 2;
  }
  "$php" -l "$tmp"
  "$php" "$tmp" --test-ean-evidence

  install -d -m 700 "$backup_dir"
  backup="$(mktemp "$backup_dir/hw24-test-asin-before-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.php")"
  cat "$target" > "$backup"
  chmod 600 "$backup"
  test "$(git hash-object "$backup")" = "$old"

  staged="$(mktemp "$root/api/.hw24-asin-fix-XXXXXXXX.php")"
  cat "$tmp" > "$staged"
  chown --reference="$target" "$staged"
  chmod --reference="$target" "$staged"
  test "$(git hash-object "$staged")" = "$new"
  mv -fT "$staged" "$target"
  staged=''
  changed=1
  echo 'Nur TEST-api/amazon-asin.php ersetzt, gesichertes Original vorhanden.'
else
  echo "STOP: Test-Datei hat abweichenden SHA ($current). Keine Aenderung."
  exit 2
fi

"$php" -l "$target"
"$php" "$target" --test-ean-evidence
test "$(git hash-object "$target")" = "$new"

anon="$(curl --silent --show-error --output /dev/null --max-time 12 \
  --write-out '%{http_code}' --proto '=https' \
  'https://test-artikelmanager.hairworld24.de/api/amazon-asin.php?ean=3474630337398&asin=B091J3HV6B' 2>/dev/null)" || anon='transport_failure'
if [ "$anon" != '401' ] && [ "$anon" != '403' ]; then
  echo "STOP: anonymer Zugriff $anon statt 401/403"
  exit 1
fi
echo "TEST weiterhin anonym geschuetzt: HTTP $anon"
echo '=== AMAZON EAN-FIX AUF TEST ERFOLGREICH. LIVE UNVERAENDERT. ==='
echo 'Naechster Schritt: genau ein authentifizierter Amazon ASIN GET auf TEST.'
