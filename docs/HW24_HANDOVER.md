# HW24 · VERBINDLICHE ÜBERGABE FÜR NEUE CHATS

**Stand:** 22.11.2026 nach Betreiber-PuTTY-Ausgabe um ca. 01:46 UTC. **Diese Datei zuerst lesen**, danach [aktuellen Projektstand](./HW24_PROJEKTSTAND_AKTUELL.md), [chronologisches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) und [Zwei-Domain-Rollout](./HW24_TWO_DOMAIN_ROLLOUT_20261122.md). Die Dokumentation ist im öffentlichen GitHub-`main`; keine Secrets ergänzen.

## Aktueller, nachgewiesener Zustand

| Baustein | Status / Beleg |
| --- | --- |
| Legacy-Testdomain `test-artikelmanager.hairworld24.de` | V4.25 mit exaktem GitHub-SHA `444453471b0fd27c73dcc7929f5e17f1d9239c52` am 22.11.2026 manuell aus Plesk deployed. Kritische Dateihashes `artikelpflege.html` und `api/jtl.php` nach Deployment OK. Authentifizierter Browser-/API-E2E noch nicht vollständig belegt. |
| Legacy-Livedomain `artikelmanager.hairworld24.de` | Betreiber hat am 22.11.2026 per Plesk `Active branch: deploy/staging` und letzten Plesk-Repo-Commit exakt `444453471b0fd27c73dcc7929f5e17f1d9239c52` nachgewiesen. **Webroot-Hash und authentifizierter Browser-/API-E2E auf Live noch nicht nachgewiesen.** Nicht automatisch „abgenommen“ melden. |
| Zugriffsschutz | Ohne Credentials je Live und Test `/`, `/api/jtl.php` und `/api/ai-reviews.php` **HTTP 401**, sechs Statuschecks bestanden (vor/um Live-Cutover). Kein umfassender Penetrationstest. |
| Test-Backup | Privates V4.24-Test-Webroot-Archiv vor V4.25-Deploy angelegt und als lesbar geprüft. Weitere früher berichtete Backups vorhanden; Restoreprobe nicht belegt. |
| Framework-Pilot | `feature/hw24-platform-laravel13-filament5-20261122`, Draft [PR #36](https://github.com/NicoVoss1/HW24-scanner/pull/36), zuletzt explizit erfolgreicher Console-CI-Commit `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`: Laravel 13 + Filament 5, Login/Allowlist, Dashboard, GET-only JTL-Artikelsuche, manuelle Amazon-GET-/OpenAI-Archiv-GET-Abfrage. CI [#44972633923](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44972633923) erfolgreich. **Plesk-Installation unvollständig.** |
| Private Serverinstallation | Unter privatem, nicht im Webroot liegendem App-Verzeichnis besteht bereits ein Git-Klon im Unterordner `repo`, von Benutzer `nicovoss` angelegt. `vendor/` fehlt laut Betreiber; Composer hat noch nicht installiert. |
| Konkreter Installationsfehler | Nach erfolgreichem Klon schlug `git -C ... rev-parse HEAD` als `root` mit `fatal: detected dubious ownership` fehl. `set -euo pipefail` in der **interaktiven** root-Shell kann dadurch die SSH-Sitzung beendet haben. **Nicht** pauschal Root-Git-Safe-Directory aktivieren, sondern Git im Repo als Besitzer `nicovoss` ausführen und lange Installer in einer **eigenen Bash-Subshell** laufen lassen. |
| Plesk-PHP | Plesk PHP 8.4-FPM aktiv; CLI **explizit** `/opt/plesk/php/8.4/bin/php` benutzen (Standard-PHP wäre 8.1). Composer 2.11.2, Git vorhanden. Node/npm nicht im PATH; PHP-Erweiterungen MySQL, SQLite, Intl, Zip OK. |
| Datenhaltung | Vorhandene JTL-MariaDB-Daten und JTL-Wawi MSSQL ausschließlich lesend; private Auth/API-Konfigurationen nicht in GitHub. Keine neue Subdomain, keine zusätzliche verwaltete MariaDB. Laravel-eigene private SQLite später zulässig, nur im isolierten `platform/` und nie mit JTL-Schema verwechseln. |
| Legacy-API-Brücke | Im Featurebranch ausschließlich `https://artikelmanager.hairworld24.de` mit Basic Auth per privater Laravel-`.env`. Zunächst nur GET, kein automatischer Amazon-/OpenAI-Kostenaufruf, keine JTL/WMS/Marktplatz-Schreiboperation. |

## Genau nächster Schritt

1. **Nicht erneut klonen und keine Website umstellen.** Im schon bestehenden privaten Repo den Head als Eigentümer prüfen: `runuser -u nicovoss -- git -C "$app/repo" rev-parse HEAD`. Erwartet `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`. Keine globale `safe.directory`-Ausnahme.
2. Bei korrektem SHA Composer **als `nicovoss` und mit PHP 8.4** im isolierten `repo/platform` ausführen, niemals im Legacy-Webroot. Der Composer-Lockfile-/Reproduzierbarkeitsaspekt ist noch zu klären: der aktuelle Pilotworkflow installiert über Composer-Update in CI und prüft zunächst, dass kein `composer.lock` existiert. Für längerfristig reproduzierbares Hosting Lockfile und deploybare Assets festschreiben.
3. Live-V4.25-Webroot-Dateien auf Hash überprüfen und danach die wichtigsten JTL-/Amazon-/KI-Funktionen im angemeldeten Browser bestätigen. **Vorher Test-Webroot nicht auf Laravel umstellen.**
4. Laravel `.env`, private SQLite nur für Laravel-Nutzer/Metadaten, geschützten Filament-Login und Live-Legacy-Basic-Auth-Bridge schrittweise einrichten; Plesk-Testdomain DocumentRoot erst nach erfolgreicher Abnahme ausschließlich `platform/public`.
5. Danach unter `test-artikelmanager.hairworld24.de/admin` zuerst echtes Dashboard und Artikelabfrage testen; Shopify/eBay eigene Kanalpreise, JTL-Brutto-VK mit belegter Steuer-/Kundengruppenquelle und Skalierung 100.000+ folgen.

## PROTOKOLLREGEL: Jeder einzelne Schritt in GitHub

**Verbindlich für jede weitere Sitzung, einschließlich neuer Chats:**
- **Zuerst** diese Übergabe, Projektstand und Arbeitsjournal lesen. Keine bereits bestätigten Diagnosen wiederholen.
- Nach **jeder** Nutzer-PuTTY-Rückmeldung, jedem erfolgreich oder erfolglos abgeschlossenen Deploy, jedem Code-Commit/PR, jedem Test und jeder Architekturentscheidung **unmittelbar** im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) einen neuen chronologischen Eintrag erzeugen. **Auch Fehler, Teilabbrüche und Rückfallmaßnahmen protokollieren.**
- Jeder Eintrag nennt **Zeit/Datum (wenn vorhanden), konkreten Schritt, beobachtetes Ergebnis, Beleg/SHA/URL, Status (bestätigt/verifiziert/offen/fehlgeschlagen), Sicherheitsauswirkung, exakt nächsten Schritt**. Nach jedem Meilenstein auch diese Übergabe und Projektstand fortschreiben; die letzten echten Fakten gelten gegenüber älteren Planabschnitten.
- Für GitHub-Aktionen **den bestehenden GitHub-Connector nutzen** und Änderungen direkt committen; niemals nur im Chat behaupten, etwas sei dokumentiert, wenn kein Commit bestätigt ist. Dem Nutzer stets Dateilink und Dokumentations-Commit nennen.
- **Das Repository ist öffentlich:** niemals Passwörter, API-Keys, Sitzungstoken, private Host-IP-/SSH-Schlüssel, private DB-Konfigurationsinhalte, Dumps, Zugangsdaten oder vertrauliche konkrete Serverpfade/Benutzerdetails veröffentlichen. Technische Benutzerbezeichner nur soweit für Reproduzierbarkeit unerlässlich.
- **Trenne** GitHub-Code-Status, GitHub-CI-Test, Plesk-Git-Commit, tatsächliches Plesk-Deployment, Browser-E2E und Live-API-Verbindung. Keinen Status aus einem anderen erschließen.
- Die App kann GitHub nicht selbsttätig auf dem Server bedienen; diese **Dokumentationsdisziplin wird pro aktiver Arbeitsrunde durchgeführt**, nicht als unbeaufsichtigtes Hintergrundversprechen.

**Keine neue Subdomain. Kein Merge des Framework-Draft-PRs in `deploy/staging`. Kein ungeprüfter JTL-/WMS-/Shopify-Live-Write.**


## LETZTES EREIGNIS · Composer-Wrapper-Fehlversuch am 22.11.2026

**Neu bestätigt:** Der Betreiber hat den **bestehenden** privaten Git-Klon erfolgreich als Site-Benutzer geprüft: HEAD exakt `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`. Keine neue Clone-Aktion nötig, der frühere `dubious ownership`-Fehler ist damit gelöst.

**Neuer Fehlversuch, protokolliert:** Der anschließende Befehl `/opt/plesk/php/8.4/bin/php "$(command -v composer)" ...` startete **nicht** Composer, sondern druckte den Inhalt des **Plesk-Shell-Wrappers**. Die tatsächliche Composer-PHAR-Datei liegt laut Wrapper unter `/opt/psa/var/modules/composer/composer.phar`. **Noch keine Pakete installiert**; `vendor/autoload.php` wurde nicht als vorhanden bestätigt. PuTTY blieb diesmal offen.

**Genau nächster Schritt:** Vorhandenen PHAR-Pfad prüfen, dann `runuser -u nicovoss -- /opt/plesk/php/8.4/bin/php /opt/psa/var/modules/composer/composer.phar ... install` im bestehenden isolierten `repo/platform` ausführen. **Nicht** den Shell-Wrapper mit PHP interpretieren; **nicht** neu klonen; **nicht** globale Git-Sicherheitsausnahmen eintragen; **nicht** bestehende Sites/DB verändern. Skript in Subshell ausführen und dann tatsächliche `vendor/autoload.php`-Existenz prüfen.

Chronologisch auch in [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) dokumentiert, Commit `11ee6bdbfd6fee832149e16418ab720769053f2e`.
