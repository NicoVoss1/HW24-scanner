# HW24 Laravel / Filament – PuTTY-Start und sicherer Zugriff
Stand: 22.11.2026. Dieses Dokument enthält **keine Zugangsdaten**.

## Bestätigte Arbeitsmittel und Quellen
- Serververwaltung: **PuTTY über SSH**, mit administrativen Rechten laut Nutzer.
- Hosting: bestehender **Plesk-Server**. Änderungen erst nach Prüfung des tatsächlichen PHP-Handlers und separaten DocumentRoots.
- JTL-Wawi: Microsoft SQL Server; **HeidiSQL** zur lesenden Prüfung der Originalquellen.
- JTL-MariaDB: vorhandener eigenständiger HW24-Spiegel; zunächst ausschließlich lesend.
- Bestehende API-/Server-Zugangsdaten werden vom Nutzer getrennt und privat verwaltet. Niemals Inhalte aus geschützten Konfigurationsdateien oder aus `.env` veröffentlichen.
- Code: `NicoVoss1/HW24-scanner`; aktive ältere Testbasis `deploy/staging` (V4.25, `444453471b0fd27c73dcc7929f5e17f1d9239c52`); Framework-Pilot isoliert in `feature/hw24-platform-laravel13-filament5-20261122` (Draft PR #36).

## Nächster Schritt: PuTTY-Prüfung (read-only)
Den folgenden Block auf dem **Plesk-Server** in PuTTY ausführen. Er installiert nichts, verändert keine Datenbank, öffnet keine Privatschlüsseldateien und führt kein Deployment aus.

```bash
bash <<'HW24_READONLY'
printf '\n=== HW24 / Plesk: Servercheck NUR LESEND ===\n'
printf 'System: '; ( . /etc/os-release 2>/dev/null; printf '%s\n' "${PRETTY_NAME:-unbekannt}" )
printf 'Benutzer: '; id -un
printf '\nPHP-Versionen und Erweiterungen:\n'
for p in php /opt/plesk/php/8.3/bin/php /opt/plesk/php/8.4/bin/php /opt/plesk/php/8.5/bin/php; do
  if command -v "$p" >/dev/null 2>&1; then
    "$p" -r '$n=["ctype","curl","dom","fileinfo","filter","hash","mbstring","openssl","pcre","pdo","session","tokenizer","xml","pdo_mysql"]; $missing=array_filter($n,fn($e)=>!extension_loaded($e)); echo PHP_BINARY," | PHP ",PHP_VERSION," | Fehlende Erweiterungen: ",($missing?implode(", ",$missing):"keine"),PHP_EOL;'
  fi
done
printf '\nWerkzeuge:\n'
for t in composer node npm git; do if command -v "$t" >/dev/null 2>&1; then printf '%s: ' "$t"; "$t" --version 2>&1 | head -n 1; else printf '%s: nicht im PATH\n' "$t"; fi; done
printf '\nPlesk-PHP-Handler:\n'
if command -v plesk >/dev/null 2>&1; then plesk bin php_handler --list 2>&1 | head -n 30; else echo 'Plesk-CLI nicht im PATH'; fi
printf '\nFreier Speicherplatz:\n'; df -h / | tail -n 1
printf '\nFERTIG: Keine Installation und keine Datenbankänderung.\n'
HW24_READONLY
```

## Nächste Auswertung
1. Prüfen, ob PHP CLI und Plesk-Domain-PHP >= 8.3, notwendige Extensions, Composer, Node/npm, Git und ausreichend Festplattenspeicher vorliegen.
2. **Nur isoliertes, zugangsgeschütztes** Projektverzeichnis vorbereiten. Neuer DocumentRoot muss ausschließlich auf `platform/public` verweisen, niemals auf den bestehenden GitHub-Webroot oder das Plattform-Verzeichnis als Ganzes.
3. Neuen Filament-5-Adminpanel-Pilot mit **eigenständiger SQLite-Testdatenbank** und Testbenutzer installieren; bis zur Authentifizierungsprüfung kein öffentlicher Zugriff.
4. Erste Produktliste mit read-only `LegacyReadBridge` zu vorhandenem JTL-/Amazon-/KI-Testbestand zeigen. Bestehende APIs und Tabellen unangetastet lassen.
5. Nach Test mit realistischen Artikellisten von >100.000 Datensätzen Indexierung, serverseitige Pagination, Antwortzeiten und gleichzeitige Zugriffe benchmarken.
6. Direkte DB-Schreibzugriffe sind ein **späteres Architekturziel**, nicht Bestandteil des Piloten. JTL-Wawi/MS SQL und WMS bleiben führende Systeme; Änderungen später nur über explizite freigegebene, getestete Integrationswege, niemals per unkontrolliertem SQL-UPDATE.

## Wichtige Sicherheitsregel
GitHub-Repository ist öffentlich. In dieser Datei deshalb ausschließlich allgemeine Werkzeuge und Prozessregeln. Keine Hostnames interner Systeme, Ports, persönlichen Kontonamen, Datenbank-Passwörter, Tokens, API-Schlüssel, geheimen Dateipfade oder SQL-Dumps ins öffentliche Repository schreiben.

## Read-only Ergebnis vom 22.11.2026 (vom Betreiber in PuTTY bestätigt)

- Ubuntu 22.04.5 LTS; SSH mit administrativer Berechtigung; Plesk ist installiert.
- Standard-PHP-CLI: **8.1.2** und daher **nicht** für Laravel 13 verwenden.
- Gesonderte Plesk-CLI-Binaries: **8.3.37**, **8.4.28**, **8.5.13**; im abgefragten Erweiterungssatz keine fehlenden Extensions. Für das isolierte Pilotprojekt **/opt/plesk/php/8.4/bin/php** fest wählen.
- Composer **2.11.2**, Git **2.34.1** vorhanden.
- Node/npm **nicht im SSH-PATH**; dies beweist nicht, dass Plesk intern keine Node-Version installiert hat. Frontend-Build in CI oder separate Node-Installation erst nach Projektisolation planen.
- Speicherplatz auf Root-Filesystem: ca. **914 GB frei**. Keine Aussage über RAM-Kapazität oder Performance für 100.000 Artikel.
- **Noch ungeprüft:** Plesk-PHP-8.4-FPM-Handler (erste Ausgabe war auf 30 Zeilen begrenzt), PHP-8.4-SQLite-Erweiterungen (pdo_sqlite/sqlite3), Domain/vHost-Zuordnung, isolierter DocumentRoot, Auth/ACL und Node-Build-Strategie. Kein Plesk-Deploy, keine Laravel-/Filament-Installation.
- Sicherheitsbeachtung: Originalausgabe enthielt Infrastruktur-Kennungen. **Absichtlich nicht in das öffentliche GitHub-Repository übernommen.**

### Nur-lesender nächster PuTTY-Check

```bash
printf '\n=== HW24 Pilot: fehlende Voraussetzungen ===\n'
printf '\nPHP 8.4 Datenbank-/Hilfserweiterungen:\n'
/opt/plesk/php/8.4/bin/php -r 'foreach(["pdo_sqlite","sqlite3","pdo_mysql","intl","zip","gd","bcmath"] as $e){echo $e,": ",(extension_loaded($e)?"OK":"FEHLT/OPTIONAL"),PHP_EOL;}'
printf '\nPlesk PHP-8.4-Handler:\n'
plesk bin php_handler --list | grep -E 'plesk-php84-(fpm|fpm-dedicated)' || true
printf '\nVorhandene HW24-Domains/Subdomains (Namen nur bei Bedarf weitergeben):\n'
plesk bin site --list | grep -Ei 'hairworld24|artikelmanager' || true
printf '\nNode-Binaries außerhalb des PATH:\n'
for f in /usr/bin/node /usr/local/bin/node /opt/plesk/node/*/bin/node; do if [ -x "$f" ]; then "$f" --version; fi; done
printf '\n=== Nur-lesender Check fertig ===\n'
```

**Noch keine** Installations- oder Hoständerungsbefehle ausführen. Nach diesem Ergebnis private Subdomain/DocumentRoot-Plan und gesonderten SSH/Plesk-Systembenutzer festlegen. Composer und Artisan **nie** mit der alten Standard-PHP-8.1-CLI und nach Möglichkeit nicht als root starten.
