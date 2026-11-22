#!/usr/bin/env bash
# Read-only hosting diagnostics: NEVER installs, migrates, restarts or changes permissions.
set -euo pipefail
echo 'HW24 Laravel/Filament Plesk-Readiness (Nur lesen)'
command -v php >/dev/null || { echo 'FEHLT: php CLI'; exit 1; }
php -r 'echo "PHP CLI: ".PHP_VERSION."\n"; if(version_compare(PHP_VERSION,"8.3","<")){fwrite(STDERR,"Laravel 13 benötigt PHP >= 8.3\n");exit(2);}'
php -r '$required=["ctype","curl","dom","fileinfo","filter","hash","mbstring","openssl","pdo","session","tokenizer","xml"];foreach($required as $x)echo ($x.": ".(extension_loaded($x)?"OK":"FEHLT"))."\n";'
if command -v composer >/dev/null;then composer --version;else echo 'FEHLT: composer CLI';fi
if command -v node >/dev/null;then node --version;else echo 'FEHLT: node CLI';fi
if command -v npm >/dev/null;then npm --version;else echo 'FEHLT: npm CLI';fi
if command -v plesk >/dev/null;then echo 'Plesk erkannt; PHP-Handler für künftige isolierte Subdomain separat kontrollieren';else echo 'Plesk nicht in PATH';fi
echo 'Keine .env, Datenbank- oder API-Zugangsdaten wurden gelesen.'
echo 'KEIN composer install / KEIN php artisan migrate / KEIN Deployment ausgeführt.'
