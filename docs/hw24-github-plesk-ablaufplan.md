# HW24: EIN Veröffentlichungsweg – Plesk Git

Stand 19.11.2026. Verbindliche Klarstellung: Der bestehende **Plesk Git-Dienst** bleibt für Test und Produktion zuständig. Ein zusätzlich eingerichteter GitHub-SSH-Deployment-Workflow wurde aus dem Entwicklungsbranch entfernt. GitHub Actions bleibt nur für Syntaxprüfungen und den versionierten Quellcode-Build zuständig.

## Bestätigt anhand der Plesk-Screenshots
- Plesk-Backup HAIRWORLD24.de vom 19.11.2026 19:47, 82,2 MB: „Gesamte Konfiguration und gesamter Inhalt“, grüner Haken. Datenbank zusätzlich lokal gesichert.
- Git-Verknüpfung auf `test-artikelmanager.hairworld24.de` vorhanden mit Remote `https://github.com/NicoVoss1/HW24-scanner.git`.
- Aktiver Testbranch auf Screenshot noch `improve/encoding-dashboard-sync-20261115`.
- Bereitstellung bereits **automatisch** an `/test-artikelmanager.HAIRWORLD24.de`.
- Der neue Entwicklungsstand liegt auf `feature/hw24-v422-amazon-market-20261119`, PR #3 offen (Draft). Produktionsbranch bislang `main`.

## Sicherheitsprüfung VOR dem ersten Branchwechsel
1. Optional das Plesk-Backup mit dem grünen Downloadsymbol lokal sichern. Website-Dateien im Plesk-Dateimanager bei Bedarf markieren → „Zum Archiv hinzufügen“ → ZIP herunterladen. Diese Extras sind kein Blocker, wenn eine vollständige verifizierte Sicherung bereits vorliegt.
2. Bestehenden Plesk-Git-Dialog öffnen und über das **Einstellungs-/Schieberegler-Symbol** prüfen: **Bereitstellungsmodus**, **Bereitstellungspfad**, **zusätzliche Bereitstellungsaktionen** und, soweit vorhanden, **Webhook-/Pull-Einstellungen**. Die bereits aktive automatische Bereitstellung nicht ohne Grund verändern.
3. **Kritisch:** Der GitHub-Branch enthält neben `index.html`, `assets/`, `api/` auch `sql/`, `sync/`, `docs/` und `.github/`. Wenn Plesk den *gesamten* Repository-Baum unmittelbar in den öffentlichen Webroot deployed, dürfen diese nicht ungeprüft öffentlich bereitgestellt werden. Prüfen, ob schon sichere Deploy-Aktionen/Dateiregeln existieren; andernfalls eine saubere getrennte Repository-Checkout-/Veröffentlichungsstruktur bestimmen. Nicht direkt den Branch ändern und „Pull/Deploy“ klicken.
4. Die privaten Dateien `hw24_private/db.php` und `hw24_private/amazon/amazon_spapi.php` müssen unverändert **außerhalb** des öffentlichen Webroots liegen. Keine Zugangsdaten in Chat/GitHub.
5. Optional, **nur lesend** per PuTTY/SSH als Plesk-Administrator:
   `plesk ext git --list -domain test-artikelmanager.hairworld24.de`
   danach mit exakter von Plesk gemeldeter Repository-Bezeichnung:
   `plesk ext git --info -domain test-artikelmanager.hairworld24.de -name HW24-scanner.git`
   Erst dann gezielte Einstellungen planen. Beide Befehle ändern keine Dateien.

## Test-Veröffentlichung
1. Nach Sicherheits- und Pfadprüfung Testdomain auf `feature/hw24-v422-amazon-market-20261119` umstellen, produktive Domain nicht ändern. Den vorhandenen Automatik-Modus beibehalten.
2. Pull/Bereitstellung nur mit verifizierten, nichtöffentlichen Datei-Ausschlüssen oder sicherem Dateipfad ausführen.
3. Mit Browser die Testdomain und die ausgelieferten Moduldateien prüfen, Git-Commit im Ausgabe-/Webroot-Stand protokollieren.
4. Optional neue SQL-Tabellen `sql/curated_article_stand.sql` und `sql/amazon_verified_sellers.sql` nach DB-Backup **einmalig** durch MariaDB-Administrator installieren. Benutzer `hw24_app` nur minimal berechtigen.
5. Sicherheitstest ohne Anmeldung, EAN-Scan, Clear-Kreuz, Amazon-Katalog, Pricing-Rolle/getItemOffers, Verkäuferzuordnung, freigegebene Historie und JTL-Ameise. Keine automatische Änderung an JTL oder Amazon.

## Produktion
Erst nach bestandenen Staging-Tests PR #3 prüfen, veröffentlichen und Main-Branch versionieren. Produktive Website aus `main` über deren vorhandene Plesk-Git-Verbindung bereitstellen; vorher eigenen Produktions-Webroot sichern. Bei Problemen zur freigegebenen Version zurückkehren.

## Nicht als erledigt behaupten
- Neue Version auf Plesk-Testdomain installiert
- MariaDB-Migrationen und Berechtigungen ausgeführt
- Amazon-Pricing-Berechtigung/Buy Box live erfolgreich abgefragt
- Produktivseite aktualisiert
- Webroot-Exklusionen/Deploy-Aktionen geprüft

**Nächste Aktion:** Screenshot der Plesk-Git-Repository-Einstellungen (Einstellungs-/Schieberegler-Symbol), ohne Zugangsdaten. Danach werden Branchwechsel und erster Plesk-Test-Deploy gezielt ausgeführt.
