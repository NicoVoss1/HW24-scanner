## DOMAIN-ZUORDNUNG ENDGÜLTIG BESTÄTIGT · 22.11.2026

**PuTTY-Beleg:** `domains.id=9` ist **LIVE** `artikelmanager.hairworld24.de`, `protected_dirs.id=8`, bestehender Haupt-Basic-Benutzer; `domains.id=10` ist **TEST** `test-artikelmanager.hairworld24.de`, `protected_dirs.id=9`, bisheriger Test-Basic-Benutzer. Beide Schutzpfade sind Root (DB-Feld `path` leer), `dom_type=domain`, Plesk Hostingtyp `vrt_hst`. Gemeinsamer `parentDomainId=5`. `plesk-stat` auf `dom_id=1` völlig ausklammern.

**Passendes Original-Dateninventar:** Drei KI-Reviews unter historischer Testkennung, zwei Scanlisten-Stapel je einer unter altem Test- und neuem Login. Das Problem ist **nicht** ein fehlendes Archiv, sondern ein Besitzer-Filter. Der bereits vorhandene `nvoss`-Stapel darf nicht verschwinden. Die zwei-Owner-PHP-Korrektur liegt in [Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37), aktuell CI grün, aber **nicht deployed**.

**Offiziell dokumentiert:** `plesk bin protdir -u <dir> -domain <domain> -update_user <alt> -user_name <neu>` ist der von Plesk vorgesehene Kontenumbenennungsweg, siehe [Plesk Obsidian CLI protdir](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/). Für Root gilt im Plesk-UI das Verzeichnis `/` laut [Plesk-Support](https://support.plesk.com/hc/en-us/articles/12377592309527-How-to-configure-a-password-protected-directory-in-Plesk). Die exakte Server-CLI-Syntax für die konkrete Root-Protection ist **noch vor mutierender Operation zu prüfen**, nicht blind zu raten.

### GENAU NÄCHSTER SERVERBLOCK · deployed Git-Metadaten und Legacy-API-Dateien, nur lesen

```bash
bash <<'HW24_AUTH_DEPLOY_PREFLIGHT'
set -euo pipefail

base='/var/www/vhosts/hairworld24.de'
echo "=== HW24 AUTH UND DEPLOY-VORPRUEFUNG ==="

for spec in   "artikelmanager.HAIRWORLD24.de|HW24-scanner"   "test-artikelmanager.HAIRWORLD24.de|HW24-scanner.git"; do
  domain="${spec%%|*}"
  repo="${spec#*|}"
  echo "=== Plesk Git-Metadaten: $domain ==="
  plesk ext git --info -domain "$domain" -name "$repo" 2>&1 |
    grep -Ei 'branch|deploy|deployment|repository name|local path' |
    grep -Evi 'password|token|remote|url|secret' || true

  webroot="$base/$domain"
  echo "=== Legacy-API-Dateistand: $domain ==="
  for file in api/scanliste.php api/ai-reviews.php api/ai-review.php api/hw24-actor-identity.php; do
    if [ -f "$webroot/$file" ]; then
      printf '%s: ' "$file"
      git hash-object "$webroot/$file"
    else
      echo "$file: NICHT VORHANDEN"
    fi
  done
done

echo "=== PRECHECK ABGESCHLOSSEN: KEIN DEPLOY, KEIN RENAME, KEINE DATENAENDERUNG ==="
HW24_AUTH_DEPLOY_PREFLIGHT
```

**Bitte nur Metadaten und Git-Blob-Hashes teilen, keine Git-Remote-URLs oder API-Ausgaben.** Mit dem Ergebnis wird für **jedes** tatsächliche Webroot die kontrollierte Bereitstellung der drei geänderten PHP-Routen plus neuer Helferdatei vorbereitet. Live und Test verwenden separate Plesk-Git-Installationen, die nicht blind auf einen neuen Branch umgestellt werden dürfen. Vor jedem Deploy ein rootprivates, wiederherstellbares Webroot-Backup erstellen, zuletzt bestätigte `deploy/staging`-Basis und echte Änderungen abgleichen.

**Reihenfolge:** Private `read_aliases`-Mappingdatei erst gezielt nach Backup/Code-Sicherung einsetzen; Feature-PHP-Code nur mit getestetem Rollback bereitstellen; im Browser auf normaler Anmeldung und im neu autorisierten Kontext prüfen, dass drei KI-Reviews und beide Scanlisten-Stapel lesbar sind. **Erst danach** TEST-HTTP-Basic-Benutzer umbenennen. Live-HTTP-Basic-Konto **nicht** ändern, Statistikzugang unangetastet. Separater Laravel-Filament-DocumentRoot-Cutover bleibt später.

[Arbeitsjournalbeleg](./HW24_ARBEITSJOURNAL.md), Commit `009d1589aa49180041ca6e14d0ea9bbc46240bfc`.

---

## NEUESTER PLESK-NACHWEIS · Root-Passwortschutz auf zwei getrennten Domain-IDs (22.11.2026)

**Benutzer hat die korrigierte `HW24_PLESK_AUTH_READ`-Prüfung ausgeführt.** Der Schutz für den bisherigen Test-Basic-Namen ist als `protected_dirs.id=9` / `dom_id=10` / `dom_type=domain` / leerer `path` / `realm=hw24-test` verzeichnet. Der geplante einheitliche Basic-Name existiert in `protected_dirs.id=8` / `dom_id=9` / `dom_type=domain` / leerer `path` / `realm=HW24 Artikelmanager`. Derselbe geplante Basic-Name steht separat in `protected_dirs.id=1` / `dom_id=1` für `plesk-stat` / `Domainstatistiken`; **Statistikschutz nicht ändern**.

Die Zuordnung `domains.id=9/10` zu den konkreten Live-/Test-Hostnamen **steht noch aus**. `realm` ist kein Domainbeweis. Deshalb vor jeder Plesk-CLI-Umbenennung genau einen reinen SQL-SELECT ausführen:

```bash
bash <<'HW24_PLESK_DOMAINS'
set -euo pipefail

echo "=== HW24 DOMAIN-ID-ZUORDNUNG: NUR LESEN ==="

plesk db -Ne "
SELECT d.id, d.name, d.htype, d.parentDomainId,
       pd.id AS protected_dir_id,
       pd.path AS protected_path,
       pd.realm AS auth_realm,
       pu.login AS basic_user
FROM domains d
JOIN protected_dirs pd ON pd.dom_id=d.id AND pd.dom_type='domain'
JOIN pd_users pu ON pu.pd_id=pd.id
WHERE d.id IN (9,10)
  AND pd.id IN (8,9)
ORDER BY d.id,pd.id,pu.login;
"

echo "=== NUR-LESE-PRUEFUNG ABGESCHLOSSEN ==="
HW24_PLESK_DOMAINS
```

Dieser Block gibt lediglich Hostname, Hostingtyp, Domain-IDs, Schutzpfad, Realm und Basic-Anmeldename aus. **Keine Passwörter, Passwort-Hashes, Änderungsbefehle, Migration oder Git-Deployment.** Bei unerwarteter/leerer Ausgabe nicht raten, sondern die Plesk-Subdomain-Zuordnung gesondert prüfen.

**Bisheriger Datenerhalt:** 3 KI-Review-Zeilen liegen unter historischer Kennung, 2 Scanlisten-Stapel sind unter historischer und neuer Kennung 1+1 getrennt. Daher vor Konten-Umbenennung zwingend den Zwei-Owner-PR #37 gezielt deployen und die private read-only-Zuordnung validieren; das ist **noch nicht geschehen**. Die ursprünglichen historischen Eigentümer bleiben für revisionssichere Protokolle unverändert.

[Arbeitsjournalbeleg](./HW24_ARBEITSJOURNAL.md), Commit `840b57fdd04e9a448d7151978a67855edccea2b5`.

---

## Präzisierung: Plesk-Basic-Namen zuerst ohne Annahme über Subdomain-Joins auflisten

Plesk-`protected_dirs` besitzt ausdrücklich `dom_type` zusätzlich zu `dom_id`. Deshalb kann eine direkte Verbindung `domains.id = protected_dirs.dom_id` bei über die Plesk-Subdomaintabelle verwalteten Schutzverzeichnissen unvollständig sein. Die **erste** Ausführung sollte die von der Benutzer-SQL-Schemaausgabe nachgewiesenen Tabellen `pd_users` und `protected_dirs` **ohne voreilige Domain-Zuordnung** nutzen. Diese eng begrenzte Bestandsabfrage listet nur die zwei vom Betreiber vorgegebenen Basic-Nutzernamen.

```bash
bash <<'HW24_PLESK_AUTH_READ'
set -euo pipefail

echo "=== HW24 PLESK: GESCHUETZTE BENUTZER ==="
plesk db -Ne "
SELECT pu.login,
       pd.id AS protected_dir_id,
       pd.dom_id,
       pd.dom_type,
       pd.path,
       pd.realm
FROM pd_users pu
JOIN protected_dirs pd ON pd.id = pu.pd_id
WHERE pu.login IN ('hw24-test','nvoss')
ORDER BY pu.login, pd.dom_type, pd.dom_id, pd.id;
"
echo "=== NUR-LESE-PRUEFUNG ABGESCHLOSSEN ==="
HW24_PLESK_AUTH_READ
```

Diese Abfrage lässt bestehende Konten und deren Zuordnung unverändert. **Wenn keine Zeilen kommen:** Der Schutz könnte z. B. durch eine Webserver-`.htpasswd`-Konfiguration außerhalb von Plesk-`pd_users` erfolgen. Das muss dann separat überprüft werden; keine Account-Umbenennung oder KI-/Scanlisten-Update auf Verdacht. Die konkreten Domänenzuordnungen werden anschließend anhand der im Ergebnis gezeigten `dom_type`-/`dom_id`-Werte korrekt und ausschließlich lesend geprüft.

**Achtung:** Diese Präzisierung hat gegenüber dem weiter unten archivierten früheren SQL-Beispiel mit dem bedingungslosen `domains`-Join Vorrang. Bis zum tatsächlichen Servernachweis ist keinerlei Konto umbenannt.

---

## PLESK-TABELLENSCHEMA VERIFIZIERT · 22.11.2026 · nächster korrekter Nur-Lese-Befehl

Der Betreiber hat `information_schema.COLUMNS` erfolgreich ausgeführt. In Plesk ist `protected_dirs.path` vorhanden, **`protected_dirs.name` existiert nicht**. `pd_users` hat `pd_id`, `login` und `account_id`; `domains` hat `id`, `name` und `parentDomainId`. Zuvor gescheiterte Abfragen mit `pd.name` **nicht** wiederholen.

**Nur die folgende Plesk-SQL-Abfrage ausführen, noch kein Account-Rename, keine Konfigurationsänderung:**

```bash
bash <<'HW24_PLESK_AUTH_READ'
set -euo pipefail

echo "=== HW24 PLESK AUTH-INVENTUR: NUR LESEN ==="

plesk db -Ne "
SELECT d.name AS domain_name,
       pd.id AS protected_dir_id,
       pd.path AS protected_path,
       pd.realm AS auth_realm,
       pd.dom_type AS protected_type,
       COALESCE(pu.login, '[kein Benutzer]') AS login
FROM protected_dirs pd
JOIN domains d ON d.id = pd.dom_id
LEFT JOIN pd_users pu ON pu.pd_id = pd.id
WHERE LOWER(d.name) IN (
 'artikelmanager.hairworld24.de',
 'test-artikelmanager.hairworld24.de'
)
ORDER BY d.name, pd.id, pu.login;
"

echo "=== AUTH-INVENTUR ABGESCHLOSSEN; KEINE AENDERUNGEN ==="
HW24_PLESK_AUTH_READ
```

**Erwartete Auswertung:**
- Zeilen für Live und Test mit beiden Basic-Login-Kennungen: passwortgeschützte Verzeichnisstruktur und Plesk-Verwaltung können nun konkret geplant werden; alte und neue Konten dürfen weder blind zusammengeführt noch vor der Zwei-Owner-Codefreigabe umbenannt werden.
- Keine Zeilen / nur eine Domain: Plesk-Authquelle für fehlende Domain ist **nicht** geklärt. Mögliche andere Schutz-/Subdomain-Zuordnung oder Webserver-`.htpasswd`/eigene Auth-Konfiguration erfordert separate **read-only** Prüfung. Die Leeranzeige ist kein Beweis, dass kein Passwortschutz existiert.
- SQL-Fehler: vollständigen Fehlertyp ohne private Credentials melden, niemals `UPDATE` oder `DELETE` als Ersatz verwenden.

**Unverändert bestätigter Bestand:** drei KI-Berichte unter historischem Login; zwei Scanlisten-Stapel, davon je einer unter historischer und neuer Kennung. Für dieses echte Split-Owner-Szenario ist die naive Ein-Owner-Weiterleitung **ungeeignet**. [PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37) stellt die optionalen, privat autorisierten **Zwei-Owner-Leserechte** und aktuelle neue Schreibzuordnung bereit; CI für `c2cc4e1dc054da00f01087e7221659cf54a9a482` grün. **Nicht auf Plesk deployed.** Laufende Legacy-Anwendung und Laravel-Filament-Cutover getrennt behandeln.

[Chronologischer Schema-Nachweis](./HW24_ARBEITSJOURNAL.md), Commit `56c278793b4665bf22d4c57a6ed13e03fcb158f7`.

---

## AKTUELLE KORREKTUR · 22.11.2026 · **ZWEI Daten-Owner, keine einfache 1:1-Umleitung**

**Tatsächlich in PuTTY bestätigt:**

| Datenbanktabelle | Historischer Basic-Login | Künftiger Basic-Login | Insgesamt |
| --- | ---: | ---: | ---: |
| `hw24_ai_reviews.actor` | **3** | **0** | **3** |
| `hw24_scan_batches.created_by` | **1** | **1** | **2** |

**Achtung:** Der vorherige Entwurf mit `neuer Login -> alter Login` als **einzigen** kanonischen Owner war **nicht** geeignet und würde einen bereits unter dem neuen Login vorhandenen Scan-Stapel ausblenden. Er wurde im **Draft [PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37)** durch einen ausdrücklich autorisierten **Zwei-Owner-Leseumfang** ersetzt. GitHub CI [44978357305](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978357305) und [44978360796](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44978360796) **grün** für Commit `c2cc4e1dc054da00f01087e7221659cf54a9a482`. Noch **kein** Plesk-Deploy, kein Kontenwechsel, keine Datenbankmutation!

**Aktueller Codevertrag für neue Chats:**

- `api/hw24-actor-identity.php` erlaubt nur serverseitig freigegebene **`read_aliases`** aus privater `hw24_private/actor-aliases.php`, z. B. `<?php return ['read_aliases'=>['NEUER_LOGIN'=>['HISTORISCHER_LOGIN']]];`. Die Namen werden hier nur als Platzhalter dokumentiert; keine Zugangsdaten im Repository.
- `api/scanliste.php` zeigt und autorisiert beide bisherigen Stapel mittels gebundener `created_by IN (...)`, protokolliert **neue** Stapel/Scanvorgänge/Audits aber unter dem **aktuell authentifizierten Login**.
- `api/ai-reviews.php` zeigt alte und neue KI-Berichte über begrenzte `actor IN (...)`-Abfragen; `api/ai-review.php` prüft bestehende Reviews über beide Owner, erzeugt **neue** Berichte immer unter der aktuellen Kennung. Keine automatischen OpenAI-Kosten.
- Ohne valide private Aliasdatei ist die Leseberechtigung ausschließlich der eingeloggte Basic-Benutzer. Weder GET-/POST-Parameter noch ein Browserzustand können Berechtigungen erweitern.
- Die alten `actor`, `created_by`, `changed_by`, `scanned_by` bleiben unverändert. Keine Migration und kein SQL-UPDATE.

**Plesk-Diagnoseblock vom vorherigen Stand FEHLGESCHLAGEN:** Die SQL-Abfrage mit `pd.name` meldete `ERROR 1054 Unknown column 'pd.name'`. Das beweist **nur eine falsche Spaltenannahme**, keine Störung der Schutzkonten. **Nicht erneut** eine fehlerhafte SELECT-/Umbenennungsanweisung benutzen.

### GENAU NÄCHSTER AUSZUFÜHRENDER Befehl: nur Plesk-Spaltennamen auslesen

```bash
bash <<'HW24_PLESK_SCHEMA'
set -u
echo "=== HW24 PLESK-PASSWORTSCHUTZ: TABELLENSTRUKTUR ==="
plesk db -Ne "
SELECT TABLE_NAME, COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME IN ('protected_dirs','pd_users','domains')
ORDER BY TABLE_NAME, ORDINAL_POSITION;
"
echo "=== PRUEFUNG ABGESCHLOSSEN; KEINE AENDERUNGEN ==="
HW24_PLESK_SCHEMA
```

**Ausgabeziel:** Nur Tabellen- und Spaltennamen, **keine** Logins, Passwort-Hashes, E-Mail-Adressen oder DB-Inhalte. Nach Erhalt wird die korrekt an die **tatsächliche** Plesk-Datenbank angepasste *read-only* Domain-/Protected-Directory-/Basic-User-Abfrage erstellt. Dabei beide vorhandenen Domains und eventuelle bereits existierende neue Login-Accounts prüfen; keine Kollisionen zulassen. Plesk-Benutzer erst **nach Backup und Test** gezielt umbenennen.

**Voraussetzung vor einer späteren Umbenennung:** Die Test- und Live-Authquelle, beide Benutzer in Plesk, `hw24_private/actor-aliases.php` und der GitHub-PR #37-Rollout müssen für den richtigen Domainkontext konsistent und mit Rollback eingerichtet sein. Ein früherer Loginwechsel ohne den Code würde durch die existierenden Datenfilter **1 Scan-Stapel und 3 KI-Berichte ausblenden**. Die alte Domain-Test-Umstellung auf Laravel/Filament ist ein getrenntes Vorhaben.

---

# HW24 Loginwechsel · Artikelmanager und Scanliste ohne Historienverlust

**Stand 22.11.2026.** Betreiber wünscht die Umbenennung **einer bestehenden HTTP-Basic-Anmeldung** für Artikelmanager und Scanliste. Der konkrete alte/neue Nutzername wird ausschließlich im Betreiberterminal verwendet, **nicht** im öffentlichen GitHub dokumentiert.

## Befunde und Architektur

- `scanliste.html` ruft `api/scanliste.php` über `credentials:'same-origin'` auf. Die PHP-API leitet ihre Identität aus dem vom Webserver authentifizierten `PHP_AUTH_USER / REMOTE_USER / REDIRECT_REMOTE_USER` ab. Die gespeicherten Scan-Stapel gehören `hw24_scan_batches.created_by`; die API prüft diesen Wert beim Auflisten, Öffnen, Bearbeiten und Abschluss. Eine Plesk-Loginumbenennung ohne Datenzuordnung kann historische Scan-Stapel ausblenden.
- `api/ai-reviews.php` filtert archivierte MariaDB-`hw24_ai_reviews` mit `actor=?`. Der KI-Einzelaufruf `api/ai-review.php` benutzt dieselbe Actor-Kennung für frühere KI-Ergebnisse/Deduplikation. Die früher bestätigten drei Berichte waren **nicht** dem damals verdeckt eingegebenen Login zugeordnet, die tatsächliche historische Actor-Kennung wurde noch **nicht** preisgegeben.
- Die neue Laravel-/Filament-Anmeldung in der privaten Anwendung ist **ein anderes Authentifizierungssystem**, nicht automatisch das HTTP-Basic-Login der Legacy-Test-/Live-Seiten. Weder Composer noch SQLite noch Plesk-DocumentRoot erneut ändern.
- `api/curated.php` speichert `approved_by` als Audit-Ereignis; keine eigene Owner-Filterung. Bestehende historische Audit-Autoren bleiben bestehen, neue Author-Felder dürfen bei aktiver neuer Anmeldung den neuen Benutzernamen zur nachvollziehbaren Urheberschaft tragen.

## Bereits in GitHub technisch implementiert, aber noch NICHT ausgerollt

**Draft [PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37)**, Featurebranch `feature/hw24-login-alias-scan-ai-20261122`, Basis `deploy/staging`. CI [#44977971992](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44977971992) **success** (PHP-Syntax, stabile Ownership, Sicherheits-Gates, Legacy-Artikeltests).

- Neues zentrales `api/hw24-actor-identity.php` lädt **optional** eine Betreiber-Konfiguration `hw24_private/actor-aliases.php` außerhalb der Webroots. **Keine** Browserparameter können die Zuordnung setzen. Ohne Konfiguration wird wie bisher die authentifizierte Originalkennung verwendet.
- `api/scanliste.php`, `api/ai-reviews.php`, `api/ai-review.php` rufen erst nach bestehender HTTP-Basic-/HTTPS-Anmeldeprüfung dieselbe Funktion `hw24StoredActor` auf. Scanlisten-Owner, historische KI-Berichte und bereits geprüfte EAN-Wiederverwendung bleiben so im gleichen stabilen Datenbereich.
- **Keine** `UPDATE`-Operationen auf historische `created_by`-, `actor`- oder Audit-Spalten. Kein kostenpflichtiger KI-Aufruf. Keine Änderung von JTL-Wawi/MariaDB-Schema, Amazon, Shopify oder eBay.
- Eine Umbenennung ist **nicht** allein durch Codeimplementierung erfolgt: Die tatsächliche Plesk-/Nginx-/Apache-HTTP-Basic-Anmeldung ist derzeit noch unverändert. Die Operator-Aliasdatei ist noch nicht angelegt.

## Freigabe-Gates für den Betreiber

1. **Nur lesend** Plesk-Accountquelle auf **Live** und **Test** feststellen. Plesk kann Basic Auth über die Funktion `protected_dirs`/`pd_users` verwalten, oder der Webserver benutzt eine eigene `.htpasswd`-Datei. Ein Loginname im Login-Fenster oder HTTP-Realm-Titel allein beweist die tatsächliche Authentifizierungsquelle **nicht**. Nur offizielle Plesk-CLI `protdir -u` benutzen, wenn Plesk die konkrete Anmeldung verwaltet.
2. **Nur lesend** COUNT-Abfrage von `hw24_ai_reviews.actor` und `hw24_scan_batches.created_by` für die bisherige und neue Login-Kennung sowie Gesamtzahl. Ist die historische gespeicherte Kennung schon die neue, **keine Alias-Zuordnung** zu einer falschen alten Kennung einrichten. Sind historische Daten einer dritten Kennung zugeordnet, zuerst diese Zuordnung **privat** bestimmen und verifizieren, bevor eine Aliasdatei entsteht.
3. Vor jeder Anmeldungänderung den alten Passwortschutz und seine Benutzerzuordnung / Wiederherstellung in einem **privaten** Plesk-Backup sichern; keine Passwortdateien, Hashes oder Private-Konfigurationsdaten in GitHub.
4. GitHub-PR #37 vor Serveranwendung auf Test sicher deployen, den im privaten Pfad konfigurierten tatsächlichen stabilen Owner im Test gegen die DB prüfen. **Keine** automatische Live-Bereitstellung der Funktion nur wegen CI-Grün. Kein Merge in Framework-Draft-PR #36.
5. Wenn Plesk den Benutzernamen verwaltet, kann `plesk bin protdir -u <geschuetztes-verzeichnis> -domain <domain> -update_user <ALT> -user_name <NEU>` den **bestehenden** Account umbenennen. Dieser Befehl ist erst nach exakter Domain-/Protected-Directory-/Account-Verifikation und eigenem Rückfallplan zulässig. Bei manueller `.htpasswd` dagegen anderes Verfahren; **keine** direkte SQL-Aktualisierung der Plesk-psa-Datenbank.
6. In privatem Browserfenster auf `/index.html`, `/scanliste.html` und `/api/ai-reviews.php` Anmeldung prüfen. Historische Scan-Stapel, drei alte KI-Berichte (einschließlich Review #1) und Scanlisten-Import/Export in den bestehenden Daten prüfen; keinen neuen kostenpflichtigen OpenAI-POST oder JTL-/WMS-Write auslösen.
7. Falls eine Domain die neue Anmeldung verwendet und eine andere noch die alte, darf kein unkontrolliertes Shared-Backend-Actor-Mapping aktiviert werden. Erst beide Auth-Locations und Daten-Scopes vereinheitlichen, bestehende Archiv-/Stapel-Ergebnisse dokumentieren.

## Rein lesender PuTTY-Abgleich (Benutzernamen werden lokal interaktiv erfragt)

**Noch nicht ausgeführt.** Die Ausgabe zeigt ausschließlich konkrete Domains und Protected-Directory-Pfade, gefundene Basic-Benutzernamen sowie Summen je historischer Identität. Keine Passwörter, keine einzelnen Artikel-/Berichtsinhalte, keine Datenbankänderung.

```bash
bash <<'HW24_LOGIN_READONLY'
set -euo pipefail

echo "=== HW24 LOGIN-SCOPE PRECHECK ==="
read -r -p "Bisheriger Basic-Benutzername: " old
read -r -p "Neuer Basic-Benutzername: " new

for name in "$old" "$new"; do
  [[ "$name" =~ ^[A-Za-z0-9._-]{2,64}$ ]] || {
    echo "STOP: Benutzername nur mit Buchstaben, Ziffern, Punkt, Unterstrich oder Bindestrich"
    exit 1
  }
done

echo "1. Plesk passwortgeschuetzte Verzeichnisse mit relevanten Benutzern:"
plesk db -Ne "
SELECT d.name, pd.name, pu.login
FROM protected_dirs pd
JOIN domains d ON d.id=pd.dom_id
JOIN pd_users pu ON pu.pd_id=pd.id
WHERE LOWER(d.name) IN (
  'artikelmanager.hairworld24.de',
  'test-artikelmanager.hairworld24.de'
) AND pu.login IN ('$old','$new')
ORDER BY d.name, pd.name, pu.login;
" || echo "Plesk-Tabellenabfrage fehlgeschlagen, bitte Schema pruefen"

echo "2. MariaDB nur lesen (keine Actor-Namen ausgeben):"
runuser -u nicovoss -- env HW24_OLD_USER="$old" HW24_NEW_USER="$new" /opt/plesk/php/8.5/bin/php <<'PHP'
<?php
declare(strict_types=1);
$path='/var/www/vhosts/hairworld24.de/hw24_private/scanlist-db.php';
if(!is_readable($path)){echo "STOP: Scan-DB-Konfiguration nicht lesbar\n";exit(2);}
try {
  $cfg=require $path;
  $db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
     (string)$cfg['user'],(string)$cfg['password'],
     [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
  $old=getenv('HW24_OLD_USER');
  $new=getenv('HW24_NEW_USER');
  foreach(['KI-Berichte'=>['hw24_ai_reviews','actor'],
           'Scanlisten-Stapel'=>['hw24_scan_batches','created_by']] as $name=>$spec){
    [$table,$col]=$spec;
    $sql="SELECT COUNT(*) AS total,
                 COALESCE(SUM($col=?),0) AS old_count,
                 COALESCE(SUM($col=?),0) AS new_count
          FROM $table";
    $stmt=$db->prepare($sql);
    $stmt->execute([$old,$new]);
    $r=$stmt->fetch(PDO::FETCH_ASSOC);
    echo $name.": Gesamt ".(int)$r['total'].
         ", unter ALT ".(int)$r['old_count'].
         ", unter NEU ".(int)$r['new_count'].
         ", andere Zuordnung ".((int)$r['total']-(int)$r['old_count']-(int)$r['new_count'])."\n";
  }
} catch(Throwable $e) {
  echo "SQL-Abfrage fehlgeschlagen: ".get_class($e)." / ".$e->getCode()."\n";
  exit(2);
}
PHP

unset old new
echo "=== READONLY ABGESCHLOSSEN; KEINE KONTEN ODER DATEN GEAENDERT ==="
HW24_LOGIN_READONLY
```

**Bewertung:** Die tatsächlichen `actor`-/`created_by`-Summen bestimmen die richtige Zuordnung für einen sicheren Loginwechsel. **Nie** Plesk-Konto umbenennen und anschließend blind alle alten Review-/Scanlisten-Datensätze mit SQL verändern.

**Referenzen:** [Offizieller Plesk-CLI-Befehl zum Umbenennen geschützter Login-Namen](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/), [Offizielle Plesk-Diagnose für HTTP-401-Websites](https://support.plesk.com/hc/en-us/articles/12377477134487-A-website-hosted-in-Plesk-is-not-accessible-401-Authorization-Required).
