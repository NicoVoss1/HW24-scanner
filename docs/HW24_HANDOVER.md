## AKTUELLSTER BELEG · Laravel und Live-Stand betriebsseitig bestätigt (22.11.2026)

- **Laravel CLI**: PHP 8.4 führt Laravel **13.43.0** fehlerfrei aus.
- **Filament Adminpanel im CLI-Router**: Genau vier Routen: GET/HEAD /admin (Dashboard), GET/HEAD /admin/artikel, GET/HEAD /admin/login, POST /admin/logout.
- **Live-Legacy-V4.25-Dateihashes**: Alle vier direkt geprüften Dateien index.html, artikelpflege.html, api/jtl.php, scanliste.html stimmen mit dem Staging-SHA überein. Live-Branch war bereits deploy/staging mit Commit 444453471b0fd27c73dcc7929f5e17f1d9239c52.
- **Private Laravel-Datendateien**: .env und database/database.sqlite sind **noch NICHT** angelegt. Die fertig installierten Composer-Pakete bleiben erhalten, erneute Installation unnötig.
- Ein zweites Mal eingefügter identischer PuTTY-Shellblock hatte **keine** zweite Abschlussausgabe; nicht als erneute Verifikation zählen.
- **Noch ausstehend**: Legacy-Live-Funktionstest mit gültiger Anmeldung, Laravel-.env, APP_KEY, eigene SQLite-Laravel-Migration, Filament-Adminnutzer, Plesk-Webroot-Umschaltung, echte Laravel-HTTPS-Abnahme.
- **GENAU NÄCHSTER SCHRITT:** Nur im bestehenden privaten Laravel-Projekt als Site-Benutzer .env sicher aus .env.example erzeugen; production/HTTPS/Debug off/secure session; APP_KEY erzeugen; SQLite-Datei (nicht in Git einchecken) und ausschließlich SQLite-Laravel-Tabellen migrieren; Assets lokal publizieren. **Weder bestehenden Test-Webroot noch JTL/MariaDB ändern.**
- Nach jedem Schritt [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) nachtragen. Dokumentations-Commit des aktuellen CLI-/Hash-Belegs: 3b7ee3a26e2a524fe54f136f38453d9d190239c7.

---

# HW24 · VERBINDLICHE ÜBERGABE FÜR NEUE CHATS

**Stand:** 22.11.2026 nach erfolgreicher Composer-/Laravel-/Filament-Installation. **Der neueste bestätigte Status steht direkt hier und am Ende der Datei; ältere Zwischenstände im Verlauf sind historisch.** **Diese Datei zuerst lesen**, danach [aktuellen Projektstand](./HW24_PROJEKTSTAND_AKTUELL.md), [chronologisches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) und [Zwei-Domain-Rollout](./HW24_TWO_DOMAIN_ROLLOUT_20261122.md). Die Dokumentation ist im öffentlichen GitHub-`main`; keine Secrets ergänzen.

## Aktueller, nachgewiesener Zustand

| Baustein | Status / Beleg |
| --- | --- |
| Legacy-Testdomain `test-artikelmanager.hairworld24.de` | V4.25 mit exaktem GitHub-SHA `444453471b0fd27c73dcc7929f5e17f1d9239c52` am 22.11.2026 manuell aus Plesk deployed. Kritische Dateihashes `artikelpflege.html` und `api/jtl.php` nach Deployment OK. Authentifizierter Browser-/API-E2E noch nicht vollständig belegt. |
| Legacy-Livedomain `artikelmanager.hairworld24.de` | Betreiber hat am 22.11.2026 per Plesk `Active branch: deploy/staging` und letzten Plesk-Repo-Commit exakt `444453471b0fd27c73dcc7929f5e17f1d9239c52` nachgewiesen. **Webroot-Hash und authentifizierter Browser-/API-E2E auf Live noch nicht nachgewiesen.** Nicht automatisch „abgenommen“ melden. |
| Zugriffsschutz | Ohne Credentials je Live und Test `/`, `/api/jtl.php` und `/api/ai-reviews.php` **HTTP 401**, sechs Statuschecks bestanden (vor/um Live-Cutover). Kein umfassender Penetrationstest. |
| Test-Backup | Privates V4.24-Test-Webroot-Archiv vor V4.25-Deploy angelegt und als lesbar geprüft. Weitere früher berichtete Backups vorhanden; Restoreprobe nicht belegt. |
| Framework-Pilot | `feature/hw24-platform-laravel13-filament5-20261122`, Draft [PR #36](https://github.com/NicoVoss1/HW24-scanner/pull/36), zuletzt explizit erfolgreicher Console-CI-Commit `980bcce75a0a3516b7b6364fae5a0fa6aab8d112`: Laravel 13 + Filament 5, Login/Allowlist, Dashboard, GET-only JTL-Artikelsuche, manuelle Amazon-GET-/OpenAI-Archiv-GET-Abfrage. CI [#44972633923](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44972633923) erfolgreich. **Plesk-Installation unvollständig.** |
| Private Serverinstallation | Git-Klon im privaten App-Verzeichnis mit verifiziertem HEAD `980bcce...`; Composer/PHP 8.4 als Site-Benutzer **erfolgreich ausgeführt**, Laravel 13.43.0 und Filament 5.12.0 sowie 112 Produktiv-Pakete installiert, `vendor/autoload.php` erfolgreich geprüft. **Noch keine Laravel-Adminseite in Plesk veröffentlicht.** |
| Frühere Installationsfehler (behoben) | Git-Owner-Mismatch als root und PHP-Aufruf des Composer-Shell-Wrappers. **Beides korrigiert:** Git als Site-Benutzer, Composer-PHAR explizit über PHP 8.4. Einzelfehler im Arbeitsjournal historisch festgehalten. |
| Plesk-PHP | Plesk PHP 8.4-FPM aktiv; CLI **explizit** `/opt/plesk/php/8.4/bin/php` benutzen (Standard-PHP wäre 8.1). Composer 2.11.2, Git vorhanden. Node/npm nicht im PATH; PHP-Erweiterungen MySQL, SQLite, Intl, Zip OK. |
| Datenhaltung | Vorhandene JTL-MariaDB-Daten und JTL-Wawi MSSQL ausschließlich lesend; private Auth/API-Konfigurationen nicht in GitHub. Keine neue Subdomain, keine zusätzliche verwaltete MariaDB. Laravel-eigene private SQLite später zulässig, nur im isolierten `platform/` und nie mit JTL-Schema verwechseln. |
| Legacy-API-Brücke | Im Featurebranch ausschließlich `https://artikelmanager.hairworld24.de` mit Basic Auth per privater Laravel-`.env`. Zunächst nur GET, kein automatischer Amazon-/OpenAI-Kostenaufruf, keine JTL/WMS/Marktplatz-Schreiboperation. |

## Genau nächster Schritt

1. **Nicht erneut klonen und Composer nicht erneut installieren.** Git-HEAD, Laravel 13.43.0, Filament 5.12.0 und Vendor-Autoload wurden bereits bestätigt.
2. **Nur lesender Runtime-/Releasecheck:** `/opt/plesk/php/8.4/bin/php artisan --version`, `artisan route:list --path=admin` als Site-Benutzer sowie exakte V4.25-Dateihashes im Live-Webroot. Ergebnisse als nächster Journal-Eintrag aufnehmen.
3. Für reproduzierbares Deployment die serverseitig erzeugte `platform/composer.lock` auf Secret-Freiheit prüfen und später kontrolliert auf den Featurebranch übertragen. Keine lokale SQLite-Datenbank öffentlich committen.
4. Nur im privaten Laravel-Projekt `.env`, APP_KEY und eigene SQLite für Login/Metadaten anlegen, dann Filament-Assets und einen namentlich autorisierten Admin einrichten. Keine JTL-DB-Migration.
5. Erst wenn Live V4.25 auch **angemeldet** erfolgreich funktioniert, Plesk-Testdomain auf `platform/public` umschalten; dann `/admin` in Browser und Legacy-Lesebrücke prüfen.

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


## LETZTER BESTÄTIGTER MEILENSTEIN · Composer-Installation erfolgreich (22.11.2026)

**Überholt die zuvor protokollierte Composer-Fehlermeldung.** Der private Laravel-/Filament-Klon ist unter dem erwarteten Git-HEAD `980bcce75a0a3516b7b6364fae5a0fa6aab8d112` vorhanden. Der Betreiber hat mit dem korrekten Plesk-Composer-`composer.phar` und explizit **PHP 8.4.28** als Subscription-Eigentümer die PHP-Abhängigkeiten erfolgreich installiert:

- Composer **2.11.2**, Laravel Framework **13.43.0**, Filament **5.12.0**, Livewire **4.4.9**.
- Ohne vorherige `composer.lock` im Repo **145 Versionen** aufgelöst und in einer privaten **serverlokalen Lockdatei** gespeichert; **112 Produktiv-Pakete** installiert (`--no-dev`).
- Composer Autoloading und Laravel `artisan package:discover` erfolgreich. Die lokale Prüfung `vendor/autoload.php` bestand. PuTTY meldete ausdrücklich `=== INSTALLATION ERFOLGREICH ===` und blieb offen.
- Keine Änderungen von Test-Webroot, Live-Domain oder bestehender JTL-Datenbank im Installerprotokoll.

**NOCH OFFEN:** PHP-/Artisan-Runtime im Server isoliert prüfen (`artisan --version`, `route:list`), Composer-Lockfile nach Secret-Check als reproduzierbare Abhängigkeit versionieren, private Laravel-`.env` (APP_KEY, APP_DEBUG=false, HTTPS und ADMIN-Zugang), separate lokale SQLite für Laravel-Login, Filament-Assets, erste Admin-Benutzeranlage, Laravel-HTTP-Test. Die Legacy-Live-V4.25-**Webroot-Hashes und angemeldeter API-/Browser-E2E** bleiben vor dem Umschalten der Testdomain zu bestätigen.

**NÄCHSTER GENAUER SCHRITT:** Isolierten Composer-Erfolg mit read-only Artisan-Befehlen und Live-Webroot-V4.25-Hashes absichern. Anschließend private Laravel-Konfiguration und nur isolierte SQLite-Migration anlegen. Kein Reclone, keine erneute Composer-Installation, kein zusätzlicher Vhost, kein bestehender JTL-Tabelleneingriff.

[Vollständiges Einzelprotokoll](./HW24_ARBEITSJOURNAL.md), GitHub-Dokumentations-Commit `006fbf833d06b9dd89d5a663f653d75afd3444ee`.
