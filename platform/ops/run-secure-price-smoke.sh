#!/usr/bin/env bash
# Operator-only synthetic smoke. No checkout, no real API data or production DDL.
set -Eeuo pipefail
umask 077
[[ $(id -u) == 0 ]] || { echo 'STOP: root required for local MariaDB smoke'; exit 1; }
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
php='/opt/plesk/php/8.5/bin/php'
target='c1fde9422583bdee18ca9b9b56bbf728eb80e647'
g() { runuser -u nicovoss -- git -C "$repo" "$@"; }
sql() { mariadb --no-defaults --protocol=socket --socket=/run/mysqld/mysqld.sock -u root "$@"; }
old=$(g rev-parse HEAD)
g cat-file -e "$target^{commit}"
tmp=$(mktemp -d /tmp/hw24-secure-smoke.XXXXXXXX)
db="hw24_price_smoke_$(openssl rand -hex 8)"
created=0
cleanup() {
    local rc=$?
    if [[ "$created" == 1 ]]; then
        if sql -e "DROP DATABASE \`$db\`;"; then
            echo 'Temporäre Testdatenbank entfernt.'
        else
            echo "WARNUNG: $db blieb bestehen."
            rc=1
        fi
    fi
    rm -rf -- "$tmp"
    exit "$rc"
}
trap cleanup EXIT
[[ "$db" =~ ^hw24_price_smoke_[0-9a-f]{16}$ ]]
mkdir -p "$tmp/platform/app/Services" "$tmp/platform/ops"
for file in \
    platform/app/Services/PriceArchiveStore.php \
    platform/app/Services/AmazonPriceArchive.php \
    platform/app/Services/EncryptedAmazonPriceArchive.php \
    platform/ops/hw24-amazon-price-secure-smoke.php \
    platform/ops/hw24-amazon-price-archive-secure-v2-manual.sql
do
    g show "$target:$file" > "$tmp/$file"
done
echo '=== PHP SYNTAX ==='
for file in "$tmp/platform/app/Services/"*.php "$tmp/platform/ops/"*.php; do
    "$php" -l "$file"
done
"$php" -r 'if (!in_array("aes-256-gcm", openssl_get_cipher_methods(), true)) exit(1); echo "AES-256-GCM verfügbar.\n";'
echo '=== TEMPORAERE MARIADB ==='
sql -Ne 'SELECT VERSION();'
sql -e "CREATE DATABASE \`$db\` CHARACTER SET utf8mb4;"
created=1
sql "$db" < "$tmp/platform/ops/hw24-amazon-price-archive-secure-v2-manual.sql"
echo '=== SICHERHEITSTEST ==='
HW24_SMOKE_DB="$db" "$php" "$tmp/platform/ops/hw24-amazon-price-secure-smoke.php"
[[ "$(g rev-parse HEAD)" == "$old" ]]
echo "TEST-HEAD unverändert: $old"
echo '=== HW24 SICHERHEITSTEST ERFOLGREICH ==='
