# HW24-Artikelmanager: GitHub → Test → Produktiv (verbindlicher Ablaufplan)
Stand: 19.11.2026. Betreiber: HAIRWORLD24 / FB24. Repository: \`NicoVoss1/HW24-scanner\`.

## Grundsatz
- **GitHub ist die Quelle des Programmcodes.** Keine unterschiedlichen, manuell bearbeiteten \`index.html\` auf Plesk und in GitHub.
- **Staging** = \`test-artikelmanager.hairworld24.de\`, **Produktion** = \`artikelmanager.hairworld24.de\`.
- **Private Konfigurationen** wie \`hw24_private/db.php\`, \`hw24_private/amazon/amazon_spapi.php\` und Passwörter bleiben **außerhalb des öffentlichen Webroots und außerhalb von GitHub**.
- Es wird **nur** das sichere Website-Paket \`index.html\`, \`assets/\`, \`api/\` veröffentlicht. **Niemals** \`sql/\`, \`sync/\`, \`docs/\`, \`.github/\` oder private Verzeichnisse.
- Alle Änderungen durchlaufen GitHub-Tests, Testinstallation, Funktionstest und erst anschließend eine Freigabe zur Produktion.

## Stand und bereits erledigt (GitHub)
- Entwicklungszweig \`feature/hw24-v422-amazon-market-20261119\` mit aktueller v4.22-Artikeloberfläche.
- GitHub Draft PR #3; Hauptzweig \`main\` ist **noch nicht** aktualisiert.
- Neun fehlende UI-Dateien wurden aus \`improve/encoding-dashboard-sync-20261115\` übernommen.
- GitHub Actions prüft PHP, JavaScript, HTML-Skriptbereiche und sämtliche in HTML referenzierten Assets.
- Zusätzlich wird ein Website-ZIP als prüfbares Artifact (30 Tage) erzeugt. Das ist noch **kein** Deployment.
- Ein manuell auslösbarer, abgesicherter SSH/rsync-Deploy-Workflow \`.github/workflows/hw24-deploy.yml\` liegt im Entwicklungszweig.
  **Hinweis:** GitHub bietet \`workflow_dispatch\` standardmäßig erst an, wenn der Workflow auch im Default-Branch liegt. Vor dem späteren Merge muss die Testinstallation daher einmalig durch Work/Serverzugriff oder einen gezielten, genehmigten manuellen Deploy erfolgen.
- Die neuen Funktionen (Löschkreuz, Amazon-Angebotspreise, verifizierte Verkäufernamen, freigegebene aktuelle Artikeldaten, Versionierung) sind **Code**, noch kein bestätigter Live-Betrieb.

## Phase 1 – Einmalige Bestandsaufnahme und Absicherung in Plesk
1. **Exakt überprüfen:** Hosting-Einstellungen und Dokumentenstamm der Test- und Produktivdomain, aktuell vorhandene Git-Zuordnungen/Branches und Bereitstellungsmodus. Keine Vermutungen über die realen Pfade.
2. **Sichern:** beide Webroots separat, die Datenbank \`hw24_artikelmanager\`, bestehende HTTP-Authentifizierung und App-Berechtigungen. Private Passwörter/Token **nicht** in Chats/Repositories kopieren.
3. **Vergleichen:** GitHub mit bereits laufenden Dateien auf Plesk (insbesondere \`index.html\`, \`assets/*\`, \`api/*\`). Neuere, nur auf Plesk vorhandene Änderungen zuerst sorgfältig in GitHub integrieren. Ein nicht geprüfter Git-Pull darf den Plesk-Stand nicht ersetzen.
4. **Authentifizierung:** nachweisen, dass die geschützte Domain und APIs von außen ohne Authentifizierung gesperrt sind. HTTPS muss aktiv sein.
5. Plesk PHP-Version, Erweiterungen (PDO MySQL/cURL), Platz und Dateirechte prüfen.

**Abnahmekriterium:** Backups verifiziert, echte Pfade bekannt, kein Verlust zwischen Plesk und GitHub. Produktion weiterhin unverändert.

## Phase 2 – Einmalige Verbindung GitHub → Plesk
**Empfohlene Variante:** GitHub Actions mit dediziertem SSH-Deploy-Zugang und rsync. Dies erspart manuelle HTML-Uploads und das riskante Ausrollen des ganzen Git-Repositories.

Für die GitHub-Environments \`staging\` und \`production\` sind je folgende Secrets zu hinterlegen:

| Secret | Beschreibung |
| --- | --- |
| \`HW24_SSH_HOST\` | Plesk-SSH-Hostname |
| \`HW24_SSH_PORT\` | SSH-Port, normalerweise 22 |
| \`HW24_SSH_USER\` | SSH-Benutzer mit Schreibrechten im jeweiligen Zielverzeichnis |
| \`HW24_SSH_PRIVATE_KEY\` | Privater Deploy-Schlüssel; nur GitHub Secret, niemals im Repository |
| \`HW24_SSH_KNOWN_HOSTS\` | Geprüfter SSH-Hostkey-Eintrag zur Verhinderung von MITM-Angriffen |
| \`HW24_DEPLOY_DOCROOT\` | Absoluter *bestehender* Zielordner der jeweiligen Website |

- Aus Sicherheitsgründen **separate** Deploy-Berechtigungen für Test und Produktion bevorzugen.
- Production-Environment in GitHub mit Freigabe-/Schutzregeln sichern.
- Workflows/Secrets dürfen nur mit ausdrücklich autorisiertem Admin-Zugriff eingerichtet werden.
- Das Release-Skript \`.github/workflows/hw24-deploy.yml\` hat einen **Trockenlauf**, prüft den Zielordner und synchronisiert **ohne \`--delete\`**.
- Nicht beide Plesk-Domains automatisch auf denselben Branch einstellen.
- Keine doppelten Deploy-Systeme (Plesk Git Autodeploy **plus** GitHub SSH) parallel aktivieren.

**Abnahmekriterium:** sicherer SSH-Test vom GitHub-Runner in das korrekte Test-Zielverzeichnis; Produktion bleibt blockiert.

## Phase 3 – Datenbank einmalig ergänzen
Nach bestätigtem Backup durch einen privilegierten MariaDB-Administrator:
1. \`sql/curated_article_stand.sql\` (aktuelle, ausdrücklich freigegebene Artikeldaten + Audit-Protokoll).
2. \`sql/amazon_verified_sellers.sql\` (nachweislich bestätigte Amazon-Verkäufernamen).
3. Dem Web-Benutzer \`hw24_app\` nur \`SELECT,INSERT,UPDATE\` auf \`hw24_curated_articles\`, \`SELECT,INSERT\` auf \`hw24_curated_audit\` und \`SELECT,INSERT,UPDATE\` auf \`hw24_amazon_seller_names\` erteilen, nachdem Benutzer-Host verifiziert wurde.
4. Die Original-Excel-Tabellen und JTL-Spiegeltabellen **niemals** überschreiben. Kein direkter JTL-Schreibzugriff.

**Abnahmekriterium:** Tabellen/Grants verifiziert und neues Freigabe-Archiv initial leer.

## Phase 4 – Erster Plesk-Teststand (nur Testdomain)
- Genau den geprüft freigegebenen Code mit GitHub-Version/Commit bereitstellen.
- API-Endpunkte gezielt testen: \`api/jtl.php\`, \`api/historie.php\`, \`api/amazon.php\`, \`api/amazon-pricing.php\`, \`api/curated.php\`, \`api/amazon-sellers.php\`.
- \`getItemOffers\` benötigt entsprechende Amazon-Pricing-Berechtigung. 403 ist keine Preisangabe; 429 bedeutet Rate Limit.
- Amazon-Angebote zeigen je nach SP-API-Antwort Verkäuferkennung, ggf. Buy Box, Einzelpreis, Versand und FBA. Lesbare Verkäufernamen nur aus nachweislich verifizierter Quelle. **Marketplace-Verkäufer sind keine Großhandelslieferanten.**
- Keine automatischen Updates von Amazon-Preisen und keine automatische Änderung von JTL.
- Bei Amazon-Katalogkonflikten (EAN 4021609029274, Goldwell Shampoo/Trockenshampoo) keine unkontrollierte Werteübernahme.

**Abnahmekriterium:** Testwebsite sichtbar, APIs sicher erreichbar, keine blockierenden Fehler in Plesk-PHP-Logs.

## Phase 5 – Fachliche Abnahme
- EAN \`4021609029274\` scannen: JTL/Historie/Amazon-Katalog klar getrennt und nachvollziehbar.
- Löschkreuz: EAN/Artikelnummer schnell leeren und sofort neu scannen, auch auf iPhone.
- Amazon: konkrete ASIN, Pricing-Aufruf, klare Fehlermeldung bei fehlender Berechtigung; Verkäufername nur nach Verifikation.
- Freigabe: einzelne Felder auswählen und bestätigen; erneuter Scan zeigt den freigegebenen Stand. Originalhistorie nur per bewusstem Klick sichtbar.
- Änderungsprotokoll: zweite Bearbeitung erhöht die Version; gleichzeitige Änderung erzeugt Konflikt statt stilles Überschreiben.
- CSV-/Ameise-Export, Sonderfälle ohne EAN, Barcode-Scanner, mobile Ansicht, Wartung/Reiter, JTL-Status.
- Authentifizierung: öffentliche Abfrage ohne Anmeldung blockieren; auch für nur lesende JTL-/Historien-APIs.
- Altdaten und JTL weder überschrieben noch gelöscht.

**Abnahmekriterium:** keine kritischen Fehler, alle Prüfpunkte nachweislich bestanden.

## Phase 6 – Freigabe, Produktion, Rollback
1. Review: Draft-PR #3 erst nach fachlicher und technischer Abnahme für Merge vorbereiten.
2. Einen Release-Tag \`hw24-vX.Y.Z\` mit exakt geprüftem Commit erzeugen.
3. GitHub-Production-Environment mit **manueller Freigabe**; nur getaggte Releases erlaubt.
4. Aktuelles Produktions-Webroot vorher sichern; nur kontrollierte \`index.html\`, \`assets/\`, \`api/\` ausrollen.
5. Smoke-Test unmittelbar nach Ausrollen (Authentifizierung, Login, Scanner, JTL, Historie, Amazon, Freigabe).
6. Bei Problemen: vorheriges freigegebenes Release erneut deployen; Datenbankmigrationen zunächst additiv belassen.

**Abnahmekriterium:** Produktion und Test haben nachweisbar nachvollziehbare Git-SHAs. Rückkehr auf vorherigen SHA ist möglich.

## Künftiger Regelbetrieb ohne wiederholte manuelle HTML-Uploads
\`GitHub Feature-Branch → Pull Request → automatische Tests/Website-Paket → Testwebsite → Freigabe → Release-Tag → Produktivwebsite\`.

- Automatisches Test-Deployment kann nach erstem erfolgreichen Plesk-Test und vorhandenen Schutzkontrollen aktiviert werden.
- Produktivdeployment bleibt wegen der geschäftskritischen Warenwirtschaft freigabepflichtig.
- Automatische DB-Migrationen, ungeprüfte JTL-Schreibvorgänge und Amazon-Repricing sind nicht Bestandteil des aktuellen Deploy-Prozesses.

## Was noch echte Interaktion/Autorisierung erfordert
- Plesk-Zielpfade, vorhandene Git-Konfiguration und private SSH-Host-/Deployment-Berechtigungen feststellen.
- Einmalig Deploy-Schlüssel und GitHub-Environment-Secrets einrichten, ohne geheime Inhalte im Chat zu teilen.
- Datenbankmigration und Mindestberechtigungen mit administrativem Zugang ausführen.
- Amazon-Pricing-Rollenfreigabe und Endpunkt mit echter Seller-Central-Autorisierung prüfen.
- Aktuelle Plesk-Webroots mit GitHub vergleichen, bevor die erste Ersetzung passiert.

Diese Tätigkeiten erfordern einen autorisierten Zugriff auf Plesk bzw. GitHub-Einstellungen und können im Chat ohne entsprechende Browser-/Serverberechtigung nicht als erledigt behauptet werden.