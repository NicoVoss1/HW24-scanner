#!/usr/bin/env bash
# HW24 Laravel/Filament isolated LOCAL HTTP smoke test.
# No Plesk docroot switch, no public TCP listener, no login, no DB migration.
# Starts a short-lived artisan HTTP server on 127.0.0.1 and kills it on exit.
set -euo pipefail
umask 077

app='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform'
php='/opt/plesk/php/8.5/bin/php'
port=18974
base="http://127.0.0.1:$port"
owner='nicovoss'

echo '=== HW24 ISOLIERTER LARAVEL HTTP TEST (NUR LOOPBACK) ==='
test "$(id -u)" -eq 0 || { echo 'STOP: mit root in PuTTY ausfuehren'; exit 2; }
test -x "$php" && test -f "$app/artisan" && test -f "$app/vendor/autoload.php" || {
 echo 'STOP: private Laravel-App oder PHP-CLI fehlt'; exit 2;
}
test -f "$app/.env" && test -f "$app/database/database.sqlite" || {
 echo 'STOP: keine private Laravel-Konfiguration oder SQLite'; exit 2;
}
id "$owner" >/dev/null
for tool in curl python3 setsid runuser; do command -v "$tool" >/dev/null; done

# Never connect to a pre-existing unrelated process on this port.
python3 - "$port" <<'PY'
import socket,sys
with socket.socket() as s:
    try:
        s.bind(('127.0.0.1',int(sys.argv[1])))
    except OSError:
        raise SystemExit('STOP: lokaler Port bereits belegt; keine bestehende App ansprechen')
PY

tmpdir="$(mktemp -d /root/hw24-filament-local.XXXXXXXX)"
pgid=''
cleanup(){
  if [ -n "$pgid" ]; then
    kill -TERM -- "-$pgid" 2>/dev/null || true
    wait "$pgid" 2>/dev/null || true
  fi
  rm -f -- "$tmpdir/login.html" "$tmpdir/server.log"
  rmdir "$tmpdir" 2>/dev/null || true
}
trap cleanup EXIT
setsid runuser -u "$owner" -- /bin/bash -c \
 'cd "$1" && exec "$2" artisan serve --host=127.0.0.1 --port="$3" --no-reload' \
 hw24-loopback "$app" "$php" "$port" >"$tmpdir/server.log" 2>&1 &
pgid=$!

login=''
for attempt in $(seq 1 16); do
  login="$(curl -s --connect-timeout 2 --max-time 4 \
    --output "$tmpdir/login.html" --write-out '%{http_code}' \
    "$base/admin/login" || true)"
  [ "$login" = '200' ] && break
  kill -0 "$pgid" 2>/dev/null || break
  sleep 1
done
echo "Filament Login ohne Authentifizierung: HTTP $login"
if [ "$login" != '200' ] || ! grep -qi 'filament' "$tmpdir/login.html"; then
  echo 'STOP: HTML-Login nicht erreichbar; Laravel/PHP-Fehler lokal pruefen.'
  echo 'Privates Serverprotokoll wird nach Ende automatisch entfernt.'
  exit 1
fi

# No cookies or login: private pages must redirect to login.
for route in '/admin' '/admin/artikel'; do
  status="$(curl -s --connect-timeout 2 --max-time 5 --output /dev/null \
   --write-out '%{http_code}' "$base$route" || true)"
  echo "Unangemeldeter Zugriff auf $route: HTTP $status"
  case "$status" in 301|302|303|307|308|401|403) : ;; *)
   echo 'STOP: Admin-Seite unangemeldet ungeschuetzt oder defekt'; exit 1 ;;
  esac
done

for path in '/css/filament/filament/app.css' '/js/filament/filament/app.js'; do
  status="$(curl -s --connect-timeout 2 --max-time 5 --output /dev/null \
   --write-out '%{http_code}' "$base$path" || true)"
  echo "Filament Asset $path: HTTP $status"
  [ "$status" = '200' ] || { echo 'STOP: Filament Asset nicht ueber HTTP auslieferbar'; exit 1; }
done

echo '=== LARAVEL/FILAMENT LOOPBACK: HTTP TEST BESTANDEN ==='
echo 'Nur 127.0.0.1, kein Plesk-vHost-Wechsel, keine externen Credentials.'
echo 'Lokaler Testserver wird beim Verlassen automatisch beendet.'
