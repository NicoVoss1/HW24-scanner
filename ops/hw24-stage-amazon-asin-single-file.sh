#!/usr/bin/env bash
# HW24 single-file test rollout: verified GET-only Amazon ASIN endpoint.
# Test domain ONLY, NEVER deploy live, no git merge, no SQL/JTL changes.
set -euo pipefail
umask 077
root='/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de'
target="$root/api/amazon-asin.php"
expected='617e2162f20dd012ed8c4fe8cc73d12b5e0d2373'
source='https://raw.githubusercontent.com/NicoVoss1/HW24-scanner/87ad1c38534fd5a59e7332e63bd9e666919ea8b5/api/amazon-asin.php'
php='/opt/plesk/php/8.5/bin/php'
echo '=== HW24: AMAZON-ASIN NUR AUF TEST HINZUFUEGEN ==='
test "$(id -u)" -eq 0 || { echo 'STOP: Root-Rechte erforderlich'; exit 2; }
test -d "$root/api" && test ! -L "$root" && test ! -L "$root/api" || {
 echo 'STOP: Test-Webroot nicht sicher erreichbar'; exit 2;
}
test ! -e "$target" && test ! -L "$target" || {
 echo 'STOP: Ziel existiert bereits. Keine Datei ueberschreiben!'; exit 2;
}
test -x "$php"
test -f "$root/api/jtl.php"
test -f "$root/api/amazon.php"
test "$(git hash-object "$root/api/jtl.php")" = '979e6423691a5bc02bea732486deaf364d0aa961' || {
 echo 'STOP: Test-JTL-API entspricht nicht der abgenommenen V4.25'; exit 2;
}
test "$(git hash-object "$root/api/amazon.php")" = 'f0da276c81ed48972bacc9eeba6188c22f18051e' || {
 echo 'STOP: Test-Amazon-API entspricht nicht V4.25'; exit 2;
}

tmp="$(mktemp /root/hw24-amazon-asin-test.XXXXXXXX)"
cleanup(){ rm -f "$tmp"; }
trap cleanup EXIT
curl --fail --silent --show-error --max-time 25 --proto '=https' \
 "$source" -o "$tmp"
test "$(git hash-object "$tmp")" = "$expected" || {
 echo 'STOP: Quellcode stimmt nicht mit freigegebenem GitHub-Blob ueberein'; exit 2;
}
"$php" -l "$tmp"
install -m 0644 -- "$tmp" "$target"
echo 'TEST-Datei hinzugefuegt: api/amazon-asin.php'
# Fail closed: public anonymous access must be blocked by existing Basic auth.
http="$(curl --silent --show-error --max-time 12 --output /dev/null \
 --write-out '%{http_code}' --proto '=https' \
 'https://test-artikelmanager.hairworld24.de/api/amazon-asin.php?ean=3474630337398&asin=B091J3HV6B' 2>/dev/null)" || http='transport_error'
if [ "$http" != '401' ] && [ "$http" != '403' ]; then
 echo "STOP: Unerwarteter anonymer HTTP-Status $http. Neue Test-Datei wird entfernt."
 rm -f -- "$target"
 exit 1
fi
echo "OK: Anonymer HTTP-Zugriff auf neues TEST-API gesperrt ($http)"
echo '=== TEST-API BEREIT. LIVE UNVERAENDERT. ==='
echo 'Nur manuelle, authentifizierte GET-Abfrage ausfuehren; kein SQL/JTL-Schreibzugriff.'
