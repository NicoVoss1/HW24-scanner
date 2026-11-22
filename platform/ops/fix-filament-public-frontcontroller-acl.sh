#!/usr/bin/env bash
# Exactly two PUBLIC Laravel files. Repair confirmed Apache .htaccess 403.
# NEVER change .env, SQLite, directory permissions, Git, Plesk or DocumentRoot.
set -Eeuo pipefail
umask 077
base='/var/www/vhosts/hairworld24.de'
repo="$base/hw24-console-private/repo"
app="$repo/platform"
public="$app/public"
legacy="$base/test-artikelmanager.HAIRWORLD24.de"
site='test-artikelmanager.hairworld24.de'
f1="$public/.htaccess"
f2="$public/index.php"
expected='a26a64d09597e41523e224c557bb1d4e39bdf052'
backup_dir='/root/hw24-backups'
backup=''
changed=0
finished=0

get_root(){
 plesk db -Ne "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'" 2>/dev/null
}
rollback(){
 rc=$?
 trap - EXIT
 if [ "$changed" -eq 1 ] && [ "$finished" -ne 1 ];then
  echo 'FEHLER: Urspruengliche ACLs der zwei PUBLIC-Dateien wiederherstellen.'
  if [ -n "$backup" ] && [ -f "$backup" ] && setfacl --restore="$backup";then
   echo 'ACL-ROLLBACK OK.'
  else
   echo "WARNUNG: ACL-Ruecknahme nicht bestaetigt. Sicherung: $backup"
  fi
 fi
 exit "$rc"
}
trap rollback EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

echo '=== HW24: GEZIELTER FILAMENT-PUBLIC-ACL-FIX ==='
[ "$(id -u)" -eq 0 ] || { echo 'STOP: root-PuTTY erforderlich';exit 2; }
for bin in getfacl setfacl runuser plesk git stat sha256sum curl mktemp;do
 command -v "$bin" >/dev/null || { echo "STOP: $bin fehlt";exit 2; }
done
[ "$(get_root)" = "$legacy" ] || {
 echo 'STOP: Testdomain zeigt nicht mehr auf alten Legacy-Root';exit 2;
}
[ -f "$legacy/index.html" ] && [ -f "$legacy/api/amazon-asin.php" ] || {
 echo 'STOP: Alter Test-Webroot unvollstaendig';exit 2;
}
[ -d "$repo/.git" ] &&
[ "$(runuser -u nicovoss -- git -C "$repo" rev-parse HEAD)" = "$expected" ] || {
 echo 'STOP: Privater Framework-Quellstand abweichend';exit 2;
}
[ -z "$(runuser -u nicovoss -- git -C "$repo" status --porcelain --untracked-files=no)" ] || {
 echo 'STOP: Getrackter Framework-Code lokal veraendert';exit 2;
}
for d in "$base/hw24-console-private" "$repo" "$app" "$public";do
 [ -d "$d" ] && [ ! -L "$d" ] || { echo 'STOP: Verzeichnispfad fehlt oder ist Symlink';exit 2; }
done

echo '1. Eigentum, Inhalt, vorhandene ACL und private Sperren pruefen'
for file in "$f1" "$f2";do
 [ -f "$file" ] && [ ! -L "$file" ] || {
  echo 'STOP: PUBLIC-Datei fehlt oder ist ein Symlink';exit 2;
 }
 [ "$(stat -c '%U' "$file")" = 'nicovoss' ] || {
  echo 'STOP: Unerwarteter PUBLIC-Datei-Eigentuemer';exit 2;
 }
 mode="$(stat -c '%a' "$file")"
 case "$mode" in
  600) : ;;
  *) if ! runuser -u www-data -- test -r "$file" ||
        ! runuser -u nginx -- test -r "$file";then
       echo 'STOP: ACL-/Modus-Zustand unerwartet, keine automatische Korrektur';exit 2;
     fi ;;
 esac
done
for file in "$app/.env" "$app/database/database.sqlite";do
 [ -f "$file" ] && [ ! -L "$file" ] || { echo 'STOP: Private Datei nicht vorhanden';exit 2; }
 for user in nginx www-data;do
  if runuser -u "$user" -- test -r "$file";then
   echo 'STOP: Eine private Datei ist bereits fuer Webserver lesbar';exit 2;
  fi
 done
done
status="$(curl -sS --max-time 10 --proto '=https' \
 --output /dev/null --write-out '%{http_code}' "https://$site/admin/login" 2>/dev/null)" || status='transport_error'
case "$status" in 401|403) : ;; *)
 echo 'STOP: Schutz des alten HTTPS-Testroots nicht nachgewiesen';exit 2 ;;
esac
echo 'OK: Plesk-Testroot unveraendert; .env und SQLite vor Webserver geschuetzt'

echo '2. Bestehende ACLs nur der beiden PUBLIC-Dateien root-privat sichern'
install -d -m 700 "$backup_dir"
[ "$(stat -c '%U %a' "$backup_dir")" = 'root 700' ] || {
 echo 'STOP: ACL-Sicherungsverzeichnis nicht auf root 0700 begrenzt';exit 2;
}
backup="$(mktemp "$backup_dir/hw24-public-acl-before-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX.acl")"
chmod 600 "$backup"
getfacl -p -- "$f1" "$f2" >"$backup"
[ -s "$backup" ] || { echo 'STOP: ACL-Sicherung leer';exit 2; }
echo "ACL-Backup: $backup"

echo '3. Apache und Nginx nur Leserecht fuer die beiden PUBLIC-Dateien geben'
hash1="$(sha256sum "$f1")"
hash2="$(sha256sum "$f2")"
changed=1
setfacl -m u:nginx:r,u:www-data:r -- "$f1"
setfacl -m u:nginx:r,u:www-data:r -- "$f2"
[ "$(sha256sum "$f1")" = "$hash1" ] &&
[ "$(sha256sum "$f2")" = "$hash2" ] || {
 echo 'STOP: Inhalt der PUBLIC-Dateien wurde unerwartet veraendert';exit 2;
}

echo '4. Lesbarkeit der PUBLIC-Dateien und Sperre der PRIVATEN Dateien beweisen'
for file in "$f1" "$f2";do
 for user in nicovoss www-data nginx;do
  runuser -u "$user" -- test -r "$file" || {
   echo "STOP: $user kann erforderliche PUBLIC-Datei nicht lesen";exit 2;
  }
 done
 echo "OK: $(basename "$file") fuer PHP-Site-User, Apache und Nginx lesbar"
done
for file in "$app/.env" "$app/database/database.sqlite";do
 for user in nginx www-data;do
  if runuser -u "$user" -- test -r "$file";then
   echo 'STOP: Webserver kann private Laravel-Datei lesen';exit 2;
  fi
 done
done
echo 'OK: Private .env und SQLite bleiben unlesbar fuer nginx und www-data'
[ "$(get_root)" = "$legacy" ] || {
 echo 'STOP: Test-DocumentRoot wurde unerwartet geaendert';exit 2;
}
finished=1
echo '=== GEZIELTER PUBLIC-ACL-FIX ERFOLGREICH. TEST/LIVE UNVERAENDERT. ==='
echo "Manuelle ACL-Ruecknahme (nur bei Bedarf): setfacl --restore='$backup'"
echo 'Naechste Stufe ist ein gesonderter TEST-HTTPS-Cutover mit Plesk-Rollback.'
