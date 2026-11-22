# HW24 · KI-Archiv: Live-Nullanzeige, Zugriff, vollständige Historie

**Erstellt:** 22.11.2026. **Status:** Quellcode analysiert, mindestens ein historisch gespeicherter Bericht belegt. **Aktueller MariaDB-Inhalt noch nicht direkt per SSH verifiziert.** Keine produktive API-Anpassung, kein KI-POST, kein Datenbank-Write und keine Domainumstellung.

## I. Belegte Fakten

1. **Historisch nachweislich archiviert:** In [HW24_PROJEKTSTAND_AKTUELL.md](./HW24_PROJEKTSTAND_AKTUELL.md) ist ein authentifizierter `GET /api/ai-reviews.php`-Rückgabewert vom **21.11.2026** dokumentiert: `ok=true`, `stored=true`, `count=1`, Review-ID **1**, EAN **8027483835211** (Selective Professional ONcare Leave-in Daily Hydrating Spray, 275 ml). Bericht war vollständig lesbar; das bedeutet **nicht**, dass er zwingend dem aktuell auf Live angemeldeten HTTP-Basic-Benutzer gehört.
2. **Aktuell auf Live sichtbar:** Am 22.11.2026 zeigt der Browser in `/index.html` beim kostenfreien `KI-Auswertungen · HW24-Prüfarchiv` **0 gespeicherte KI-Analysen**. Gleichzeitig meldet das Kostenbadge **4 Prüfungen**, ca. **0,0053 USD** als Tokenkostenschätzung. Dies allein ist kein Datenverlustnachweis.
3. **Aktueller Code** [`api/ai-reviews.php`](https://github.com/NicoVoss1/HW24-scanner/blob/deploy/staging/api/ai-reviews.php) führt `SELECT … FROM hw24_ai_reviews WHERE actor=?` aus. Der Actor stammt aus `PHP_AUTH_USER`, `REMOTE_USER` oder `REDIRECT_REMOTE_USER`. Optionaler EAN-Filter und `before_id`-Pagination. Erfolgreiche leere `reviews:[]` bedeutet **nur keine Treffer unter dem aktuellen Actor/Filter**, nicht zwingend keine Daten in der DB.
4. [`api/ai-review.php`](https://github.com/NicoVoss1/HW24-scanner/blob/deploy/staging/api/ai-review.php) zählt Monatsaufrufe und Tokenkosten in **privatem Ledger** `hw24-ai-usage.json`. Das kostenfreie GET-Archiv liest hingegen eine **MariaDB-Tabelle**. Der Code aktualisiert das Kostenledger nach einem Modellaufruf und versucht danach erst den Review-Insert. Auch deshalb kann der Aufrufzähler höher als die Zahl persistierter Berichte sein.
5. [`assets/hw24-ai-history.js`](https://github.com/NicoVoss1/HW24-scanner/blob/deploy/staging/assets/hw24-ai-history.js) ruft das Archiv mit `credentials:'same-origin'`, `cache:'no-store'` und `limit=30` ab; ältere Ergebnisse lassen sich per `Ältere laden` mit Cursor abrufen. **Es gibt derzeit keine ausdrücklich berechtigte Gesamtarchivansicht über mehrere Actor-Identitäten.**
6. Das Legacy-Live-Webroot entspricht in den zuvor geprüften vier Dateien V4.25; die Testdomain wird noch nicht auf Laravel umgeschaltet. Privater Laravel-Admin ist separat eingerichtet und berechtigt, das ist **nicht automatisch** eine Berechtigung zum Anzeigen fremder Legacy-HTTP-Basic-Benutzerberichte.

## II. Sichere Diagnose des tatsächlichen Datenbestands (noch nicht ausgeführt)

Dieses Skript ist **rein lesend** und nutzt die bestehende, bereits private DB-Konfiguration über PHP CLI. Es gibt **keine Zugangsdaten, Benutzernamen, E-Mail-Adressen, Berichtstexte, EAN-Listen oder API-Schlüssel** aus, sondern ausschließlich aggregierte Zahlen. Es nutzt exakt den in `api/ai-reviews.php` fest eingebauten DB-Host und Datenbanknamen sowie den bestehenden privaten Konfigurationspfad.

```bash
bash <<'HW24_AI_ARCHIVE_AUDIT'
set -euo pipefail

php="/opt/plesk/php/8.5/bin/php"
echo "=== HW24 KI-ARCHIV READ-ONLY-AUDIT ==="

runuser -u nicovoss -- "$php" <<'PHP'
<?php
declare(strict_types=1);
error_reporting(E_ALL);

$cfgPath='/var/www/vhosts/hairworld24.de/hw24_private/scanlist-db.php';
if (!is_file($cfgPath) || !is_readable($cfgPath)) {
    echo "DB-KONFIGURATION: NICHT LESBAR\n";
    exit(2);
}

try {
    $cfg=require $cfgPath;
    if (!is_array($cfg) || empty($cfg['user']) || !array_key_exists('password', $cfg)) {
        echo "DB-KONFIGURATION: UNVOLLSTAENDIG\n";
        exit(2);
    }

    $db=new PDO(
      'mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
      (string)$cfg['user'],
      (string)$cfg['password'],
      [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_TIMEOUT=>5
      ]
    );

    $all=$db->query(
      'SELECT COUNT(*) AS total_rows,
              COUNT(DISTINCT actor) AS actor_count,
              COUNT(DISTINCT ean) AS ean_count,
              MAX(created_at_utc) AS last_report
         FROM hw24_ai_reviews'
    )->fetch();

    echo "MariaDB-Archiv erreichbar: JA\n";
    echo "Gespeicherte Berichte gesamt: ".(int)$all['total_rows']."\n";
    echo "Unterschiedliche Benutzerzuordnungen: ".(int)$all['actor_count']."\n";
    echo "Unterschiedliche EANs: ".(int)$all['ean_count']."\n";
    $historic=$db->prepare(
      'SELECT COUNT(*) FROM hw24_ai_reviews WHERE id=? AND ean=?'
    );
    $historic->execute([1, '8027483835211']);
    echo "Historischer Bericht ID 1 / EAN 8027483835211 vorhanden: ".
      ((int)$historic->fetchColumn()>0?'JA':'NEIN')."\n";
    echo "Letzte erfolgreiche Archivspeicherung UTC: ".
      ($all['last_report']??'KEINE')."\n";

    $stmt=$db->query(
      'SELECT COUNT(*) AS reports
         FROM hw24_ai_reviews
        GROUP BY actor
        ORDER BY MAX(id) DESC
        LIMIT 20'
    );
    $i=0;
    foreach($stmt as $row){
      $i++;
      echo "Anonyme Actor-Gruppe ". $i .": ".(int)$row['reports']." Berichte\n";
    }

    echo "AUDIT: READ-ONLY ERFOLGREICH\n";
} catch(Throwable $e) {
    echo "AUDIT FEHLGESCHLAGEN: ".get_class($e).
       " / Fehlerklasse ".(string)$e->getCode()."\n";
    exit(2);
}
PHP

echo "=== HW24 ARCHIVPRUEFUNG ABGESCHLOSSEN ==="
HW24_AI_ARCHIVE_AUDIT
```

**Wichtig:** Nicht das private Config-PHP öffnen oder seinen Inhalt posten. Dieses Skript führt ausschließlich `SELECT`-Abfragen aus; es startet keine OpenAI-Anfrage und schreibt keine Daten. Ein `SQLSTATE[42S02]`-/42S02-Fehler deutet auf eine nicht vorhandene Tabelle/unsichtbare Tabelle, 1044/1045 auf DB-Berechtigungs-/Loginprobleme. Die Datenbankursache ist anhand des **tatsächlichen** Terminalausgangs zu beurteilen; nicht vorab behaupten.

## III. Entscheidungsbaum nach dem Ergebnis

| Diagnose | Konsequenz |
|---|---|
| **Gesamt >0**, aktuelle Live-Archivansicht 0 | Wahrscheinlich Scope-/Basic-Auth-Actor-Unterschied oder Filter. Bestehende Berichte erhalten. Vergleiche Actor-Zuordnung **intern**, ohne Nutzernamen zu veröffentlichen. |
| **Gesamt 0**, früherer Bericht auf Test nachweislich gespeichert | Datenbank-/Konfigurationsziel, Migration, Zugriff oder Backupstand kontrollieren; noch keine Annahme über Löschung und keine Datenbankmigration erneut starten. |
| Tabelle/DB nicht erreichbar | Einzeln DB-Berechtigung, Pfad zur privaten Konfiguration und PDO/MariaDB prüfen. Keine Änderung des Nutzungsledgers oder der OpenAI-Konfiguration. |
| Gesamtberichte vorhanden, aber ein archivierter Text fehlerhaft/unvollständig | Gesicherte DB-Daten und Fehlermeldung intern vergleichen, Erhaltung vor Manipulation. Kein kostenpflichtiger Recheck als Debugging. |

## IV. Sichere Zielkorrektur nach Datenbestandsnachweis

- **Standard-Nutzer:** Bewährtes Actor-Scoping erhalten; Lesefehler und „0 für diese Anmeldung“ eindeutig unterscheidbar anzeigen, vorhandene `Ältere laden`-Pagination und kostenloses GET beibehalten.
- **Betreiber-Admin:** Ein **ausdrücklich und serverseitig autorisiertes** Gesamtarchiv über Actor-Zuordnungen schaffen, das das bereits konfigurierte Admin-/Allowlist-Prinzip nutzt. Kein globales Entfernen von `WHERE actor=?`; keine ungeprüfte Einsicht anderer Basic-Auth-Nutzer. Privat und authentifiziert, nur GET; EAN-Suche, Datum, paginierte Berichte, Quellen-/Modellstatus und Kosten anzeigen.
- **Datenmigration:** Bestehende gespeicherte Prüfberichte bleiben unveränderlich; keinen automatischen Import/Umbenennung von Actor-Werten. Keine Daten in einer neuen SQLite kopieren, bevor ihre Herkunft und Berechtigungen geklärt sind. Neue Laravel Console kann später ausschließlich GET und autorisierte Darstellung der Legacy-Berichte integrieren.
- **Abnahme:** Mindestens den früher nachgewiesenen Bericht ID 1 / EAN 8027483835211 **mit passender Berechtigung** erneut sichtbar machen; Archivliste vollständig paginieren; keine ungeprüften ASIN-Identitäten; 403/keine fremden Berichte für nicht berechtigte Nutzer; Null neue OpenAI-Kosten; manuelle Plesk-Bereitstellung und Rollback erst nach bestandenen Tests.

**Status:** Der vollständige aktuelle Datenbankbestand wurde in dieser ChatGPT-Sitzung nicht direkt ausgelesen. Es wird **kein** Zugriff auf die private Plesk-MariaDB behauptet. Der Betreiber kann das Script direkt per PuTTY ausführen, danach kann die genaue Korrektur auf einem isolierten GitHub-Branch implementiert und geprüft werden.


## 22.11.2026 · Tatsächliche SQL-Bestandsaufnahme erfolgreich, Live-Actor-Filter als nächste Ursache eingrenzen

**Vom Betreiber per PuTTY bestätigt, read-only:** Die bereits bestehende MariaDB `hw24_artikelmanager` ist mit der bestehenden privaten Konfiguration erreichbar. `hw24_ai_reviews` enthält **3 gespeicherte Berichte**, **1 unterschiedliche Actor-Zuordnung** und **3 unterschiedliche EANs**. Der historische Bericht ID **1** / EAN **8027483835211** ist **vorhanden**. Jüngster Datensatz `2026-11-22 01:31:34.913848` UTC. Damit ist die Archive-Datenbank **nicht leer**. Die Live-Browseransicht zeigte dennoch **0**. [Einzelnachweis](./HW24_ARBEITSJOURNAL.md), Commit `0186b16f11b05be120cc988aae2fdc12ac26d4be`.

Der vorrangige Verdacht ist jetzt der **`WHERE actor=?`-Benutzerfilter**. Bevor Adminrechte eingeführt oder Code verändert wird, nur den aktuell verwendeten HTTP-Basic-Nutzernamen gegen die unveränderte MariaDB-Benutzerzuordnung vergleichen.

### Nächster nur lesender Test: aktuelle Live-Basic-Benutzerkennung mit Archiv-Actor vergleichen

**Achtung:** Bitte bei der Aufforderung **ausschließlich den Benutzernamen der Browser-HTTP-Basic-Anmeldung für `artikelmanager.hairworld24.de` eingeben, niemals das Passwort oder die Laravel-Admin-E-Mail, falls diese nicht der HTTP-Basic-Anmeldung entspricht.** Der eingegebene Name wird verdeckt am Terminal aufgenommen und nur über STDIN an die PHP-CLI übergeben; er wird nicht im Shell-Commandline-Argument oder Terminalergebnis angezeigt. Es gibt **keinen** Plesk-, SQL- oder Archiv-Schreibzugriff.

```bash
bash <<'HW24_ACTOR_MATCH'
set -euo pipefail

echo "=== HW24 ARCHIV-BENUTZERZUORDNUNG ==="
echo "Gib NUR den Browser-Basic-Auth-Benutzernamen der Live-Artikelmanager-Domain ein."
IFS= read -r -s -p "Live-Basic-Benutzername (Eingabe verdeckt): " actor </dev/tty
echo
test -n "$actor" || { echo "STOP: Kein Benutzername eingegeben"; exit 1; }

printf '%s' "$actor" | runuser -u nicovoss --   /opt/plesk/php/8.5/bin/php -r '
$actor = stream_get_contents(STDIN);
if (!is_string($actor) || $actor === "") {
    echo "STOP: Kein Login-Kontext\n";
    exit(2);
}
$path="/var/www/vhosts/hairworld24.de/hw24_private/scanlist-db.php";
if (!is_readable($path)) {
    echo "STOP: Private DB-Konfiguration nicht lesbar\n";
    exit(2);
}
try {
    $cfg=require $path;
    if (!is_array($cfg) || empty($cfg["user"]) ||
        !array_key_exists("password",$cfg)) {
        echo "STOP: DB-Konfiguration unvollstaendig\n";
        exit(2);
    }
    $db=new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4",
        (string)$cfg["user"],
        (string)$cfg["password"],
        [
          PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false,
          PDO::ATTR_TIMEOUT=>5
        ]
    );
    $all=(int)$db->query("SELECT COUNT(*) FROM hw24_ai_reviews")->fetchColumn();
    $stmt=$db->prepare("SELECT COUNT(*) FROM hw24_ai_reviews WHERE actor=?");
    $stmt->execute([$actor]);
    $own=(int)$stmt->fetchColumn();
    echo "Archiv gesamt: ".$all."\n";
    echo "Berichte fuer eingegebene Live-Basic-Kennung: ".$own."\n";
    echo "Davon fuer andere Kennung(en): ".($all-$own)."\n";
    echo "READ-ONLY-BENUTZERABGLEICH ERFOLGREICH\n";
} catch(Throwable $e) {
    echo "FEHLER: ".get_class($e)." / ".$e->getCode()."\n";
    exit(2);
}
'
unset actor
echo "=== BENUTZERZUORDNUNGSPRUEFUNG ABGESCHLOSSEN ==="
HW24_ACTOR_MATCH
```

**Auswertung:** 
- `Archiv gesamt: 3` / `Berichte fuer eingegebene Live-Basic-Kennung: 0` = **Actor-Mismatch für diese eingegebene Kennung nachgewiesen**. Die Berichte sind in der DB erhalten; sichere Betreiber-Gesamtarchivberechtigung einrichten, statt einen globalen Public-Endpoint zu öffnen.
- `Archiv gesamt: 3` / `...: 3` = die eingegebene Login-Kennung entspricht dem einzigen historischen Actor. Dann **nur** den echten HTTP-PHP-Actor-Kontext, Frontend-EAN-Filter/Request und Response im angemeldeten Browser prüfen. Nicht einfach den Scope entfernen.
- `0` in der DB, 503/Abfragefehler oder anderer Gesamtwert = Zustand neu bewerten und **nicht** als früheres Ergebnis wiederholen.

**Ziel nach Ergebnis:** Sicherer Admin-Gesamtarchivzugriff **nur für einen serverseitig ausdrücklich autorisierten Operator**, reguläre User-Archive weiterhin actor-gebunden. Drei Original-MariaDB-Einträge unangetastet und kostenfrei abrufbar; vollständiger EAN-/Titel-/Datumsfilter und Pagination in der neuen Intelligence Console erst nach Zugriffsabnahme. Keine neue kostenpflichtige OpenAI-Prüfung, keine ungesicherten Datenexporte.
