#!/usr/bin/env bash
# HW24 Live-/Test-V4.25-Preflight. Ausschliesslich lesend. Keine Credentials.
set -u
echo "=== HW24 V4.25 HTTP UND PLESK PREFLIGHT ==="
date -u '+UTC %Y-%m-%d %H:%M:%S'
command -v git >/dev/null && command -v curl >/dev/null || exit 2
issues=0

# Exakte Blob-SHAs von freigegebenem Legacy-Commit 444453471b0fd27c73dcc7929f5e17f1d9239c52
for site in artikelmanager.HAIRWORLD24.de test-artikelmanager.HAIRWORLD24.de; do
  root="/var/www/vhosts/hairworld24.de/$site"
  echo "== V4.25-Dateihashes: $site =="
  if [ ! -d "$root" ]; then
    echo "UNGEKLAERT: Webroot fehlt"; issues=$((issues+1)); continue
  fi
  while IFS='|' read -r file expected; do
    if [ ! -f "$root/$file" ]; then
      echo "FEHLT: $file"; issues=$((issues+1)); continue
    fi
    current="$(git hash-object "$root/$file" 2>/dev/null || true)"
    if [ "$current" = "$expected" ]; then
      echo "OK: $file"
    else
      echo "ABWEICHUNG: $file (neuerer oder abweichender Stand?)"
      issues=$((issues+1))
    fi
  done <<'EXPECTED'
index.html|96d18f937df2ca99bb76fa5754a01451fb4b4e3e
artikelpflege.html|c146471c1900b51a905e6675ee83381d0221342f
scanliste.html|4b82f66b12e856e4fff83460f8dad3683634f9b5
api/jtl.php|979e6423691a5bc02bea732486deaf364d0aa961
api/amazon.php|f0da276c81ed48972bacc9eeba6188c22f18051e
EXPECTED
done

echo "== Anonyme HTTPS-Zugriffssperre auf Live und Test =="
for host in artikelmanager.hairworld24.de test-artikelmanager.hairworld24.de; do
  for path in '/artikelpflege.html' '/api/jtl.php?ean=3474630337398' '/api/ai-reviews.php?ean=3474630337398'; do
    code="$(curl -sS -o /dev/null --max-time 12 -w '%{http_code}' "https://$host$path" 2>/dev/null)" || code=transportfehler
    case "$code" in
      401|403) echo "OK geschuetzt: $host$path -> $code" ;;
      200) echo "KRITISCH anonym lesbar: $host$path -> 200"; issues=$((issues+1)) ;;
      *) echo "UNGEKLAERT: $host$path -> $code"; issues=$((issues+1)) ;;
    esac
  done
done

echo "== Dokumentierte Plesk-DocumentRoots (nur Metadaten) =="
if command -v plesk >/dev/null && [ "$(id -u)" -eq 0 ]; then
  plesk db -Ne "SELECT d.name,h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id WHERE LOWER(d.name) IN ('artikelmanager.hairworld24.de','test-artikelmanager.hairworld24.de') ORDER BY d.name" 2>/dev/null || echo "Plesk-Metadaten nicht verfuegbar"
fi

echo "== Private Laravel-CLI und Web-Asset-Rechte =="
app="/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform"
php="/opt/plesk/php/8.5/bin/php"
if [ -x "$php" ] && [ -f "$app/artisan" ] && id nicovoss >/dev/null 2>&1; then
  runuser -u nicovoss -- "$php" "$app/artisan" --version || issues=$((issues+1))
  runuser -u nicovoss -- "$php" "$app/artisan" route:list --path=admin || issues=$((issues+1))
  for user in nginx www-data; do
    id "$user" >/dev/null 2>&1 || continue
    for file in "$app/public/css/filament/filament/app.css" "$app/public/js/filament/filament/app.js"; do
      if runuser -u "$user" -- test -r "$file"; then
        echo "OK Asset lesbar: $user $(basename "$file")"
      else
        echo "ASSET GESPERRT: $user $(basename "$file")"; issues=$((issues+1))
      fi
    done
    for file in "$app/.env" "$app/database/database.sqlite"; do
      if runuser -u "$user" -- test -r "$file"; then
        echo "KRITISCH private Datei lesbar: $user $(basename "$file")"; issues=$((issues+1))
      else
        echo "OK privat gesperrt: $user $(basename "$file")"
      fi
    done
  done
else
  echo "UNGEKLAERT: Laravel unter erwartetem Pfad nicht pruefbar"; issues=$((issues+1))
fi

echo "=== Ende: $issues Abweichung(en), keine Aenderungen vorgenommen ==="
echo "Angemeldeter Live-Browser-E2E und Filament-HTTP-Login weiterhin separat pruefen."
[ "$issues" -eq 0 ]
