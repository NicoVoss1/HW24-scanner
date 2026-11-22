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

echo "1. Plesk passwortgeschuetzte Verzeichnisse mit relevanten Benutzern:"
plesk db -Ne "
SELECT d.name, pd.name, pu.login
FROM protected_dirs pd
JOIN domains d ON d.id=pd.dom_id
JOIN pd_users pu ON pu.pd_id=pd.id
WHERE LOWER(d.name) IN (
  'artikelmanager.hairworld24.de',
  'test-artikelmanager.hairworld24.de'
) AND pu.login IN ('hw24-test','nvoss')
ORDER BY d.name, pd.name, pu.login;
" || echo "Plesk-Tabellenabfrage fehlgeschlagen, bitte Schema pruefen"

echo "2. MariaDB nur lesen (keine Actor-Namen ausgeben):"
runuser -u nicovoss -- /opt/plesk/php/8.5/bin/php <<'PHP'
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
  foreach(['KI-Berichte'=>['hw24_ai_reviews','actor'],
           'Scanlisten-Stapel'=>['hw24_scan_batches','created_by']] as $name=>$spec){
    [$table,$col]=$spec;
    $sql="SELECT COUNT(*) AS total,
                 COALESCE(SUM($col=?),0) AS old_count,
                 COALESCE(SUM($col=?),0) AS new_count
          FROM $table";
    $stmt=$db->prepare($sql);
    $stmt->execute(['hw24-test','nvoss']);
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

echo "=== READONLY ABGESCHLOSSEN; KEINE KONTEN ODER DATEN GEAENDERT ==="
HW24_LOGIN_READONLY
```

**Bewertung:** Die tatsächlichen `actor`-/`created_by`-Summen bestimmen die richtige Zuordnung für einen sicheren Loginwechsel. **Nie** Plesk-Konto umbenennen und anschließend blind alle alten Review-/Scanlisten-Datensätze mit SQL verändern.

**Referenzen:** [Offizieller Plesk-CLI-Befehl zum Umbenennen geschützter Login-Namen](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/), [Offizielle Plesk-Diagnose für HTTP-401-Websites](https://support.plesk.com/hc/en-us/articles/12377477134487-A-website-hosted-in-Plesk-is-not-accessible-401-Authorization-Required).
