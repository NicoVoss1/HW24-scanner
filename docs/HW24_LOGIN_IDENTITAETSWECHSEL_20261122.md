## STOP · ALTER TEST-BASIC USER WIRD TROTZ UMBENENNUNG NOCH AKZEPTIERT (22.11.2026)

**Neue tatsächliche PuTTY-Evidenz** des Benutzers:
- Neuer HTTP-Basic-User `nvoss` auf TEST: `KI-Berichte unter nvoss: 3`, `Offene Scanlisten-Stapel unter nvoss: 2`; `NVOSS HTTPS-TEST ERFOLGREICH`.
- **Negativtest unter ehemaligem Test-User `hw24-test`: `Alter Testbenutzer: HTTP 200`, `STOP: Alter Zugang nicht wie erwartet gesperrt`**. Das ist ein tatsächliches bestehendes Zugriffsproblem. Nie als 401 oder sichere vollständige Deaktivierung darstellen. Plesk-SQL hatte bereits unmittelbar nach `protdir -u /` im Test-Schutz-Objekt nur `nvoss` und die unveränderte Account-Referenz nachgewiesen, daher effective Auth vs. Plesk-Metadaten explizit differenzieren.
- GUI-Nutzer sagt, Prüfhistorie im „neuen nvoss“ fehle, „nur unter Test noch“. Codebridge PR37 liegt **ausschließlich auf TEST**; LIVE `artikelmanager.hairworld24.de` ist noch alter `deploy/staging`-PHP-Code, obwohl Live-Basic bereits `nvoss` heißt. Die Browseroberfläche `index.html` lädt `assets/hw24-ai-history.js` und `assets/hw24-ai-cost.js`. Button `📂 Auswertungen` → `Alle letzten Prüfungen` auf **TEST** fragt die dort bewiesenen drei Berichte an; `Aktuelle EAN` filtert hingegen.

Offizielle Referenzen: [Plesk vHost-pd-Passwortdateien](https://doc.plesk.com/en-US/obsidian/administrator-guide/web-hosting/website-directory-structure/virtual-host-structure-linux.68806/), [Plesk nginx/Apache Webserver-Konfigpfade](https://doc.plesk.com/en-US/obsidian/administrator-guide/web-servers/apache-and-nginx-web-servers-linux/plesk-for-linux-apache-and-nginx-configuration-files.68678/), [Plesk Apache-Änderungen können verzögert aktiviert werden](https://support.plesk.com/hc/en-us/articles/12377516833559-Apache-settings-are-not-applied-immediately-in-Plesk). Die Ursache ist **noch nicht festgelegt**, daher kein Neustart, kein direktes Editieren von `pd`- oder `.htpasswd`-Dateien und keine neue Plesk-Benutzer- oder Passwortänderung.

### GENAU NÄCHSTER EINZELNER PuTTY-BLOCK: Read-only effective TEST-Basic-Dateien und Webserver-Konfiguration prüfen

```bash
bash <<'HW24_BASIC_AUTH_TRACE'
set -euo pipefail

domain="test-artikelmanager.hairworld24.de"
sys="/var/www/vhosts/system/$domain"
root="/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de"

echo "=== HW24 TEST: PLESK-BASIC NUR LESEN ==="
echo "Plesk-Konten fuer Schutz-ID 9:"
plesk db -Ne "
SELECT pu.login
FROM pd_users pu
JOIN protected_dirs pd ON pd.id=pu.pd_id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
ORDER BY pu.login;
"

echo "=== TEST: USERNAMEN IN PD-DATEIEN (KEINE PASSWORTHASHES) ==="
test -d "$sys/pd"
found=0
while IFS= read -r -d '' file; do
  found=1
  old=nein
  new=nein
  if LC_ALL=C grep -aq '^hw24-test:' "$file"; then old=JA; fi
  if LC_ALL=C grep -aq '^nvoss:' "$file"; then new=JA; fi
  printf '%s: hw24-test=%s nvoss=%s\n' \
    "${file#"$sys/"}" "$old" "$new"
done < <(find "$sys/pd" -maxdepth 2 -type f -print0)
if [ "$found" -eq 0 ]; then
  echo "ACHTUNG: Keine regulaeren pd-Dateien gefunden."
fi

echo "=== TEST: WIRKSAME AUTH-DATEIREFERENZEN ==="
for file in "$sys"/conf/*.conf "$root/.htaccess" "$root/api/.htaccess"; do
  if [ -f "$file" ]; then
    refs="$(grep -Ei '^[[:space:]]*(auth_basic_user_file|AuthUserFile)[[:space:]]' "$file" || true)"
    if [ -n "$refs" ]; then
      echo "Konfigurationsdatei: $file"
      printf '%s\n' "$refs"
    fi
  fi
done

echo "=== DIAGNOSE ABGESCHLOSSEN: KEINE AENDERUNGEN ==="
echo "Keine Hashzeilen, Kennwoerter, Benutzer- oder Dateiaenderungen"
HW24_BASIC_AUTH_TRACE
```

**Sicherheitsgrenze:** Der Befehl gibt aus den Plesk-`pd`-Passwortdateien **nur** boolesche Fundwerte für zwei bekannte Logins aus, keine verschlüsselten Passwort-Hashzeilen. Die Auth-Konfiguration wird auf Dateireferenzdirektiven gefiltert, enthält daher Dateipfade, nicht Secrets. Keine Neustarts und keine mutierenden Vorgänge. Nach der Ausgabe Plesk-/Apache-/nginx-spezifischen sicheren Fix anhand der realen Userdateien bestimmen. Bereits bestehendes privates Test-Plesk-`pd`-TAR `/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-pd.tar.gz` SHA256 `a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f` und Test-Webroot-TAR `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz` SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26` unverändert behalten.

**Browser-Check unabhängig von SSH:** Im privaten/neuen Browserfenster explizit `https://test-artikelmanager.hairworld24.de` und **nicht** die produktive `https://artikelmanager.hairworld24.de` öffnen; `nvoss` anmelden, `📂 Auswertungen` → `Alle letzten Prüfungen` anklicken. Auf der Produktivdomain wird das alte KI-Archiv bis zu einer eigens sicher freigegebenen PR37-Code-Bereitstellung (separater Review/Merge/Deploy) nicht vereinigt.

[Chronologischer kritischer Journal-Eintrag](./HW24_ARBEITSJOURNAL.md), Commit `a5dfa5a75ca92df531219c80f1252e46ab0de93c`. [Aktueller Handover](./HW24_HANDOVER.md), Commit `a09ffdd09b9ef6190fdfbefb90e69332e0b908bf`.

---

## JETZT TECHNISCH BESTÄTIGT · TEST-LOGIN `nvoss` LIEST 3 KI-BERICHTE UND 2 SCAN-STAPEL (22.11.2026)

**Neuer tatsächlicher PuTTY-Nachweis `HW24_NVOSS_GET_VERIFY`:** Mit HTTP Basic `nvoss` und verdeckter interaktiver Passworteingabe funktionieren nach Plesk-Test-Basic-Umbenennung sowohl `GET /api/ai-reviews.php?limit=50` (`KI-Berichte unter nvoss: 3`) als auch `GET /api/scanliste.php?op=batches` (`Offene Scanlisten-Stapel unter nvoss: 2`). Abschluss `NVOSS HTTPS-TEST ERFOLGREICH`, `Keine Daten veraendert`. **Historischer Actor `hw24-test` verbleibt unverändert in MariaDB, ist aber über die explizite private `read_aliases`-Delegation unter dem neuen Test-Login `nvoss` sichtbar.** KEIN SQL-UPDATE, KEIN KI-POST, KEIN LIVE-DEPLOY.

**Bereits bestätigt:** Test-Plesk `protdir -u '/'`-Rename via offizielle CLI erfolgreich mit gleicher Account-Referenz, alte Test-Basic-Zeile weg, HTTP 401 für anonyme GETs; PR37-Code nur auf Test deployed (4 BLOB-Hashes, 4 PHP8.5-Lints), privater Owner-Alias `0600` unter `hw24_private`, vorheriger alter Login las 3 KI-Berichte und 1 Stapel; jetzt neuer Login 3 Berichte und 2 Stapel. Test-Webroot- und Plesk-Auth-Backups mit SHA256 vorhanden. **Nicht als erledigt deklarieren:** tatsächlicher Browser-UI-Smoketest und expliziter negativer alter-Login-HTTPS-Test.

### Genau nächster einzelner Qualitätstest: Alter Test-Basic-Login muss verweigert werden

```bash
bash <<'HW24_OLD_BASIC_DENY'
set -euo pipefail

url="https://test-artikelmanager.hairworld24.de/api/ai-reviews.php"
echo "=== HW24: ALTER TEST-BENUTZER MUSS ABGEWIESEN WERDEN ==="
echo "Bitte bisheriges Passwort von hw24-test NUR verdeckt in curl eingeben."

status="$(curl --silent --show-error \
  --basic --user hw24-test \
  --max-time 20 --proto '=https' \
  --output /dev/null --write-out '%{http_code}' \
  "$url")"

echo "Alter Test-Benutzer: HTTP $status"
if [ "$status" != "401" ]; then
  echo "STOP: Alter Login ist nicht wie erwartet gesperrt."
  exit 1
fi
echo "=== PASS: ALTER TEST-BENUTZER HAT KEINEN API-ZUGANG ==="
echo "Keine Benutzer, Daten oder Dateien veraendert"
HW24_OLD_BASIC_DENY
```

**Hinweis:** `curl --user hw24-test` ohne Passwortzeichen in Shellargumenten fordert interaktiv verdeckt auf; falsche/fehlende Eingabe kann auch 401 ergeben. Da zuvor das **korrekte** alte Passwort bereits für den anonymen vorherigen Login und nach Rename derselbe Passwort-Account getestet wurde, kann die Prüfung mit dem bekannten alten Passwort als gezielter Negativtest verwendet werden. Für eine unabhängige, stärkere Bestätigung zusätzlich Plesk-`pd_users`-SQL und/oder Webserver-Protokolle heranziehen. Nur HTTP-Status ausgeben, keine Passwort-Hashes oder JSON-Antworten.

**Nächster Browsercheck durch Nutzer:** `https://test-artikelmanager.hairworld24.de` unter `nvoss` aufrufen, KI-Archiv 3 und Scanliste 2 offene Stapel visuell prüfen. Kein Test von schreibenden POST-Endpunkten in Produktionsdaten ohne ausdrückliche Freigabe. Das Live-System `https://artikelmanager.hairworld24.de` bleibt Code-alt und unverändert; späteren PR37-Live-Merge/Deploy gesondert freigeben. Auch Laravel-/Filament-Cutover strikt getrennt.

[Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Commit `1ccfd3bdf167a2fe93575e0c3aeab8b1bf3262a5`. [Projektübergabe](./HW24_HANDOVER.md), Commit `172324d99d74ea52170e40f68ca7d43bcee9874e`.

---

## PLESK-BASIC-RENAME AUF TEST ERFOLGREICH · 22.11.2026

**PuTTY-Beleg des Betreibers:** Plesk bestätigte `SUCCESS: Update of protected URL '/' completed.` nach `plesk bin protdir -u '/' -domain test-artikelmanager.hairworld24.de -update_user hw24-test -user_name nvoss`. Skript-Nachchecks: `TESTBENUTZER ERFOLGREICH UMBENANNT`, `Neuer Benutzer: nvoss`, gleiche `account_id`-Referenz wie vorher, alte Test-PD-Login-Zeile verschwunden, anonymer HTTPS-GET weiterhin **401**. Private read_aliases-PHP-Funktion `nvoss => [nvoss,hw24-test]` vorher ebenfalls geprüft. Beide Root-Sicherungsprüfsummen bestanden. **Kein SQL-Daten-Update und kein Live-Deploy.**

### NÄCHSTER EINZEL-BLOCK: authentifizierte GET-Anfragen als neuer TEST-User `nvoss`

```bash
bash <<'HW24_NVOSS_GET_VERIFY'
set -euo pipefail

url="https://test-artikelmanager.hairworld24.de"
php="/opt/plesk/php/8.5/bin/php"

echo "=== NVOSS: HISTORISCHES KI-ARCHIV ==="
echo "Passwort fuer nvoss nur im verdeckten curl-Prompt eingeben."

curl --silent --show-error --fail-with-body \
  --basic --user nvoss --max-time 30 --proto '=https' \
  "$url/api/ai-reviews.php?limit=50" |
"$php" -r '
 $v=json_decode(stream_get_contents(STDIN),true);
 if(!is_array($v)||($v["ok"]??false)!==true||!is_array($v["reviews"]??null)){
   fwrite(STDERR,"STOP: nvoss KI-Archiv-GET nicht lesbar\n");exit(1);
 }
 $n=count($v["reviews"]);
 echo "KI-Berichte unter nvoss sichtbar: $n\n";
 if($n!==3){fwrite(STDERR,"STOP: Erwartet exakt 3 historische Berichte\n");exit(1);}
'

echo "=== NVOSS: OFFENE SCANLISTEN ==="
echo "Passwort erneut verdeckt eingeben."

curl --silent --show-error --fail-with-body \
  --basic --user nvoss --max-time 30 --proto '=https' \
  "$url/api/scanliste.php?op=batches" |
"$php" -r '
 $v=json_decode(stream_get_contents(STDIN),true);
 if(!is_array($v)||($v["ok"]??false)!==true||!is_array($v["batches"]??null)){
   fwrite(STDERR,"STOP: nvoss Scanlisten-GET nicht lesbar\n");exit(1);
 }
 $n=count($v["batches"]);
 echo "Offene Scanlisten-Stapel unter nvoss sichtbar: $n\n";
 if($n<1||$n>2){fwrite(STDERR,"STOP: Erwartet 1 oder 2 offene Stapel\n");exit(1);}
 if($n===1) echo "HINWEIS: SQL-Gesamtbestand 2, zweiter Stapel eventuell geschlossen; Status danach separat pruefen\n";
'
echo "=== NVOSS AUTHENTIFIZIERTER TEST ERFOLGREICH ==="
echo "Keine Rohdaten, keine POSTs, keine Veraenderungen"
HW24_NVOSS_GET_VERIFY
```

**Wichtig:** `curl --user nvoss` ohne angehängtes Kennwort fragt im interaktiven PuTTY-Terminal verdeckt ab. Keine Zugangsdaten in GitHub, Shell-History oder Chat schreiben. `--fail-with-body` und `set -o pipefail` stoppen bei fehlerhaftem HTTP-/JSON-/Archivzustand. Erwartung exakt 3 historische KI-Reviews. In SQL sind **2** Scan-Stapel insgesamt vorhanden, davon 1 unter alt und 1 unter neu, aber die API listet nur **offene** Stapel; ist der neue geschlossen, kann HTTP-GET trotz korrekt installiertem Alias nur **1** zeigen. Deshalb 1 oder 2 hier zulässig, bei 1 anschließend `closed_at_utc` read-only prüfen. Keine Änderung in diesem Test, auch kein weiterer Basic-Rename.

**Sicherungen:** Private Plesk-Test-Auth `/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-pd.tar.gz`, SHA256 `a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f`; Test-Webroot `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`.

[Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Commit `8e94d1236a9773312743a0d0d929bebe3a5700c7`; [Handover](./HW24_HANDOVER.md), Commit `abe47079415fc35418837e91bf7775319fda49b4`.

---

## AKTUELLSTER BELEG · 22.11.2026 · Plesk-Test-Basic-Backup erfolgreich, gezielter Rename als nächster Gate

**PuTTY `HW24_PLESK_BASIC_BACKUP` vollständig erfolgreich:**
- Privates Test-Basic-Sicherungsverzeichnis `/root/hw24-backups/test-plesk-auth-yideOvRf`.
- `plesk-protected-dir-metadata.tsv` **vorhanden**, `plesk-pd.tar.gz` **lesbar**.
- `plesk-pd.tar.gz` SHA-256 `a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f`.
- Plesk protected dir 9, domain 10, alter Test-Basic `hw24-test` vor Backup genau 1 Treffer.
- `TEST-PASSWORTSCHUTZ ERFOLGREICH GESICHERT`, `Keine Benutzer oder Daten veraendert`.
- Älteres privates Test-Webroot-Backup ebenfalls geprüft unter `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`.

**Außerdem bereits funktionsgetestet:** Test-PR37-Code (vier PHP-Blob-Hashes OK), 4 PHP-Syntaxchecks, anonyme drei API-HTTPS-401, authentifiziert mit Test-Basic alt: 3 KI-Berichte + 1 offener Scan-Stapel. Separate private `read_aliases`-Datei `0600` wurde angelegt und als `nicovoss` erfolgreich getestet: `nvoss` liest eigene und historische `hw24-test`-Owner. Keine bisherige SQL-Korrektur, keine Preis-/Lager-/JTL-Änderung, keine KI-Aufrufkosten.

### JETZT gezielt nur TEST-Plesk-Basic-Username von alt nach nvoss ändern

Die [offizielle Plesk Obsidian protdir-CLI-Dokumentation](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/) beschreibt `-u <directory> -domain <domain> -update_user <alter Name> -user_name <neuer Name>`; [Plesk-Support](https://support.plesk.com/hc/en-us/articles/12377592309527-How-to-configure-a-password-protected-directory-in-Plesk) verwendet `/` zum Schutz der gesamten Website. `-u '/'` ist eine **aus beiden Quellen abgeleitete Root-CLI-Parametrisierung**, nicht bereits am konkreten Server verifiziert. Wenn Plesk sie ablehnt, keine anderen Varianten blind probieren.

**EIN EINZELNER PuTTY-BLOCK:**

```bash
bash <<'HW24_PLESK_RENAME_TEST'
set -euo pipefail

domain="test-artikelmanager.hairworld24.de"
auth_backup="/root/hw24-backups/test-plesk-auth-yideOvRf/plesk-pd.tar.gz"
auth_sha="a7aacc096055021c0447d4fd1ca20eeeec459dd0cdb3d72f8fc012ff692d107f"
web_backup="/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz"
web_sha="679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26"
php="/opt/plesk/php/8.5/bin/php"
helper="/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de/api/hw24-actor-identity.php"

echo "=== HW24 NUR TEST-BASIC BENUTZER UMBENENNEN ==="

printf '%s  %s\n' "$auth_sha" "$auth_backup" | sha256sum -c --status
printf '%s  %s\n' "$web_sha" "$web_backup" | sha256sum -c --status
echo "Beide Sicherungen unveraendert: OK"

original="$(plesk db -Ne "
SELECT pu.account_id FROM pd_users pu
JOIN protected_dirs pd ON pd.id=pu.pd_id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
AND pu.login='hw24-test';
")"
test -n "$original" || { echo "STOP: Original-Basic-Konto fehlt"; exit 1; }

test "$(plesk db -Ne "
SELECT COUNT(*) FROM pd_users pu
JOIN protected_dirs pd ON pd.id=pu.pd_id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
AND pu.login='hw24-test';
")" = "1" || { echo "STOP: Unerwartete Alt-Zuordnung"; exit 1; }

test "$(plesk db -Ne "
SELECT COUNT(*) FROM pd_users pu
JOIN protected_dirs pd ON pd.id=pu.pd_id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
AND pu.login='nvoss';
")" = "0" || { echo "STOP: nvoss auf TEST bereits vorhanden"; exit 1; }

runuser -u nicovoss -- "$php" -r '
 require "/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de/api/hw24-actor-identity.php";
 if(hw24ReadableOwners("nvoss")!==["nvoss","hw24-test"])
   exit(1);
'
test "$(curl -sS --max-time 15 -o /dev/null -w '%{http_code}' \
  "https://$domain/api/ai-reviews.php")" = "401" ||
  { echo "STOP: Test-Basic-Schutz vor Rename nicht wirksam"; exit 1; }

echo "Vorpruefung: OK. Nur TEST-Basic-Name wird geaendert."

# Offizielle Plesk-Oberflaechenverwaltung, keine SQL-UPDATEs.
if ! plesk bin protdir -u "/" -domain "$domain" \
  -update_user hw24-test -user_name nvoss; then
  echo "STOP: Plesk-CLI lehnte Root-Rename ab oder meldete Fehler."
  echo "Kein Alternativkommando und kein SQL-UPDATE versuchen."
  exit 1
fi

result="$(plesk db -Ne "
SELECT pu.account_id FROM pd_users pu
JOIN protected_dirs pd ON pd.id=pu.pd_id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
AND pu.login='nvoss';
")"
if [ "$result" != "$original" ]; then
  echo "STOP: Nach Rename unerwartete Passwort-Account-Referenz."
  exit 1
fi
test "$(plesk db -Ne "
SELECT COUNT(*) FROM pd_users pu
JOIN protected_dirs pd ON pd.id=pu.pd_id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
AND pu.login='hw24-test';
")" = "0" || { echo "STOP: Alter Test-Benutzer noch vorhanden"; exit 1; }

status="$(curl -sS --max-time 15 -o /dev/null -w '%{http_code}' \
  "https://$domain/api/ai-reviews.php")"
if [ "$status" != "401" ]; then
  echo "STOP: Test-Passwortschutz nach Rename unplausibel: HTTP $status"
  exit 1
fi

echo "=== TEST-BASIC-USERNAME ERFOLGREICH AUF nvoss UMGESTELLT ==="
echo "Passwort-Konto-Referenz unveraendert; anonymer HTTPS-Zugriff HTTP 401"
echo "Keine Live-, Statistik-, SQL-Daten- oder Artikel-Aenderung"
echo "Als naechstes authentifizierten GET mit nvoss pruefen."
HW24_PLESK_RENAME_TEST
```

**Kein Passwort eingeben oder posten.** Der Plesk-Rename ist die einzige beabsichtigte Änderung. Das Skript führt **keinen** automatischen Rücktausch durch, weil ein abgelehnter CLI-Aufruf nicht beweist, dass Plesk nichts an der Account-Datei geändert hat; bei Fehlstatus zuerst genau den Zustand erneut lesen. In der offiziellen Doku ist die entsprechende Rückoperation als `plesk bin protdir -u '/' -domain test-artikelmanager.hairworld24.de -update_user nvoss -user_name hw24-test` herleitbar, aber nur **nach** konkreter Statusdiagnose verwenden. Niemals `plesk db UPDATE` und niemals Test-Webroot mit Auth-PD-TAR blind überschreiben.

**Danach extra Schritt:** `curl --basic --user nvoss` auf TEST, Passwort interaktiv und verdeckt; nur API-Zählwerte 3 KI-Berichte und Scanliste erwartete bis zu 2 offene Stapel (SQL zeigt insgesamt 2, der alte ist definitiv offen, Status des zweiten offen/geschlossen noch nicht geprüft), anonymer Zugriff 401. Das lokale PHP-Alias-Gate allein ist **kein** Nachweis der HTTPS-Funktion nach Rename. Noch kein LIVE-Code-Deploy, kein Laravel-Filament-Webroot-Cutover.

[Arbeitsjournal-Sicherungsbeleg](./HW24_ARBEITSJOURNAL.md), Commit `b48acdb8a1f14e1a7a1735780fb835fa675ab8f4`.

---

## MEILENSTEIN · PRIVATE OWNER-DELEGATION ANGELEGT UND GEPRÜFT (22.11.2026)

**Direkte PuTTY-Ausgabe des Betreibers:** `HW24_PRIVATE_READ_ALIASES` erfolgreich mit `Private Datei sicher erstellt`, `Dateirechte: 600`, `nvoss: beide Datenzuordnungen freigegeben`, `hw24-test: bisherige Berechtigung erhalten`, `Fremdbenutzer: kein zusaetzlicher Zugriff`, `BENUTZERZUORDNUNG ERFOLGREICH` und `Plesk-Logins und Datenbank unveraendert`. Private `/var/www/vhosts/hairworld24.de/hw24_private/actor-aliases.php` wurde nur einmal angelegt, Owner `nicovoss:psacln`, Modus `0600`, PHP-CLI-Funktionsprüfung bestanden. Nicht in GitHub committen, nicht öffentlich machen.

Die **Test-Website** enthält bereits PR37 mit vier verifizierten PHP-Blobs, vorher 4/4 PHP-Lint OK, anonyme 3/3 HTTPS 401 und authentifiziert unter bisherigem `hw24-test` genau **3** KI-Berichte und **1** offenen Scanlisten-Stapel. Der bestehende weitere `nvoss`-Stapel ist in SQL unverändert vorhanden. Der Test-Basic-Name ist **noch nicht** geändert. Die neue PHP-Delegation gestattet `nvoss` auch Änderungen an ehemals `hw24-test`-Scanlisten, protokolliert diese unter aktuellem Actor, und ist damit keine rein lesende Scanlisten-Freigabe.

### NÄCHSTER EINZEL-PuTTY-BLOCK · TEST-Plesk-Basic-Schutz privat sichern; KEIN Benutzerwechsel

Plesk dokumentiert Passwortschutzdateien unter `/var/www/vhosts/system/<domain>/pd` ([Plesk Virtual Host Structure](https://doc.plesk.com/en-US/obsidian/administrator-guide/web-hosting/website-directory-structure/virtual-host-structure-linux.68806/)). Ein manueller privater TAR-Snapshot dieser Dateien sowie ein nur lesender Export der beiden zusammengehörigen Plesk-Metadatentabellen verhindern die bisherige Lücke zwischen Webroot-Backup und einem späteren Login-Rename.

```bash
bash <<'HW24_PLESK_BASIC_BACKUP'
set -euo pipefail
umask 077

domain="test-artikelmanager.hairworld24.de"
pd="/var/www/vhosts/system/$domain/pd"
base="/root/hw24-backups"

echo "=== HW24 TEST-BASIC-SCHUTZ SICHERN ==="

test -d "$pd" || {
  echo "STOP: Plesk-pd-Verzeichnis fehlt. Keine Aenderung vorgenommen."
  exit 1
}
test ! -L "$pd" || {
  echo "STOP: Plesk-pd-Verzeichnis ist ein Symlink."
  exit 1
}

count="$(plesk db -Ne "
SELECT COUNT(*)
FROM protected_dirs pd
JOIN pd_users pu ON pu.pd_id=pd.id
JOIN domains d ON d.id=pd.dom_id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
  AND d.name='$domain' AND pu.login='hw24-test'
")"
test "$count" = "1" || {
  echo "STOP: Unerwartete Plesk-Zuordnung. Keine Aenderung."
  exit 1
}

install -d -m 700 "$base"
dest="$(mktemp -d "$base/test-plesk-auth-XXXXXXXX")"

plesk db -Ne "
SELECT pd.id,pd.dom_id,pd.dom_type,pd.realm,pd.path,
       pu.id,pu.login,pu.account_id
FROM protected_dirs pd
JOIN pd_users pu ON pu.pd_id=pd.id
WHERE pd.id=9 AND pd.dom_id=10 AND pd.dom_type='domain'
  AND pu.login='hw24-test'
" > "$dest/plesk-protected-dir-metadata.tsv"

test -s "$dest/plesk-protected-dir-metadata.tsv"
chmod 600 "$dest/plesk-protected-dir-metadata.tsv"

tar --one-file-system -czf "$dest/plesk-pd.tar.gz" \
  -C "/var/www/vhosts/system/$domain" pd
chmod 600 "$dest/plesk-pd.tar.gz"
tar -tzf "$dest/plesk-pd.tar.gz" >/dev/null

echo "Private Sicherung: $dest"
echo "Plesk-Metadaten: vorhanden"
echo "Plesk-pd-Archiv: lesbar"
sha256sum "$dest/plesk-pd.tar.gz"
echo "=== TEST-PASSWORTSCHUTZ-SICHERUNG ERFOLGREICH ==="
echo "Keine Benutzer, Websites, Passwoerter oder Daten geaendert"
HW24_PLESK_BASIC_BACKUP
```

**Sicherheit:** Das `pd`-Archiv enthält möglicherweise *Passwort-Hashes und andere Authentifizierungsgeheimnisse*. Es bleibt ausschließlich in einem Root-geschützten `0700`-Ordner unter `/root/hw24-backups`, Archiv und SQL-Metadatendatei sind `0600`; **keine Auth-Dateien, Login-Hashes, SQL-Zeilen oder TAR-Dateien im Chat oder GitHub teilen.** Bitte nur Meldungen und den SHA256-Wert weitergeben. Bei nicht passendem Plesk-vHost-Pfad STOP, zuerst die realen Pfade ohne Geheimnisse inventarisieren. Der TAR-Snapshot + Metaexport sind für die gezielte Rückfalldiagnose und **kein vollständiges Plesk-Metadatenbank-Backup**.

**Nach bestätigter Sicherung:** Erst die konkrete Root-`/`-Parametrisierung der offiziell dokumentierten Plesk-`protdir -u ... -domain ... -update_user ... -user_name ...`-CLI sicher ermitteln und die Reverse-Rename-/HTTP-401-Checks festlegen ([Plesk protdir-Dokumentation](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/protdir-site-directories-protection.39417/)); danach gesondert **nur das TEST-Konto** umbenennen. Das LIVE-Konto `nvoss` und Plesk-Statistikschutz bleiben unangetastet. Noch **kein** HTTP-GET unter Test-`nvoss` ausgeführt.

[Journalbeleg](./HW24_ARBEITSJOURNAL.md), Commit `ddf35c68f8790af1fad3b56599b19bce91d2a00c`.

---

## 22.11.2026 · AUTHENTIFIZIERTER PR37-TEST ERFOLGREICH, NEXT: PRIVATER Lese-OWNER-ALIAS

**PuTTY-Gesamtnachweis:** Bereits auf TEST deployter PR37-Code mit vier verifizierten Git-BLOBs, PHP8.5-Syntax 4/4 grün, anonyme HTTPS-API-Anfragen 3/3 `HTTP 401`. Jetzt wurden mit dem bestehenden **Test-Basic-Benutzer** `hw24-test` die echten verschlüsselten HTTPS-GETs geprüft: `Historische KI-Berichte: 3` und `Offene Scanlisten-Stapel: 1`, `AUTHENTIFIZIERTER GET-TEST ERFOLGREICH`. Keine Passwortanzeige/JSON-Ausgabe, kein Schreibzugriff, keine OpenAI-Gebühren. Historischer SQL-Bestand: drei KI-Berichte (alt), zwei Scan-Stapel (einer alt, einer neu). **Kein Basic-Username-Wechsel bisher.**

### Nächster eigenständiger PuTTY-Schritt: Private Zuordnung exakt einmal anlegen, keinen Login ändern

Der unveränderte PHP-Helfer `api/hw24-actor-identity.php` lädt ausschließlich die **außerhalb beider Webroots** gelegene Datei `/var/www/vhosts/hairworld24.de/hw24_private/actor-aliases.php` und erwartet `['read_aliases'=>['nvoss'=>['hw24-test']]]`. Ohne Konfiguration bleibt er fail-closed auf die eigene Kennung beschränkt. Das folgende Skript prüft den vorhandenen Plesk-Dienstbenutzer und das private Verzeichnis, schreibt **nur falls noch keine Aliasdatei existiert**, nutzt einen Modus-0600-Tempfile-/Hardlink-Erstellungsvorgang (kein Überschreiben von vorhandenem Ziel), und testet die Besitzerliste direkt unter `nicovoss`. Keine Passwörter, keine API-/SQL-/Website-Benutzerkontenmutation.

```bash
bash <<'HW24_PRIVATE_READ_ALIASES'
set -euo pipefail
umask 077

private="/var/www/vhosts/hairworld24.de/hw24_private"
helper="/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de/api/hw24-actor-identity.php"
target="$private/actor-aliases.php"
php="/opt/plesk/php/8.5/bin/php"

echo "=== HW24 PRIVATE OWNER-ZUORDNUNG EINRICHTEN ==="
test -d "$private"
test ! -L "$private"
test -f "$helper"
test -f "$private/scanlist-db.php"
getent passwd nicovoss >/dev/null
getent group psacln >/dev/null
runuser -u nicovoss -- test -r "$private/scanlist-db.php"

if test -e "$target" || test -L "$target"; then
  echo "STOP: Private Zuordnungsdatei bereits vorhanden, keine Ueberschreibung"
  exit 1
fi

tmp="$(mktemp "$private/.actor-aliases.XXXXXXXX")"
trap 'rm -f -- "$tmp"' EXIT

cat >"$tmp" <<'PHP'
<?php
declare(strict_types=1);
return [
  'read_aliases' => [
    'nvoss' => ['hw24-test'],
  ],
];
PHP

chown nicovoss:psacln "$tmp"
chmod 600 "$tmp"
runuser -u nicovoss -- "$php" -l "$tmp" >/dev/null

# Atomar nur falls der Zieldateiname weiterhin frei ist; nicht ueberschreiben
ln -- "$tmp" "$target"
rm -f -- "$tmp"
trap - EXIT

test "$(stat -c '%a:%U:%G' "$target")" = '600:nicovoss:psacln'
runuser -u nicovoss -- "$php" -r '
 require "/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de/api/hw24-actor-identity.php";
 $new=hw24ReadableOwners("nvoss");
 $old=hw24ReadableOwners("hw24-test");
 $other=hw24ReadableOwners("unrelated");
 if($new!==["nvoss","hw24-test"] || $old!==["hw24-test"] || $other!==["unrelated"]){
   fwrite(STDERR,"STOP: Berechtigte Actor-Liste unerwartet\n");exit(1);
 }
 echo "nvoss: beide alten und neuen Daten-Owner freigegeben\n";
 echo "hw24-test: bisheriger eigener Daten-Owner unveraendert\n";
 echo "unrelated: kein Fremdzugriff\n";
'
echo "=== PRIVATE FREIGABE AUF PHP-EBENE ERFOLGREICH ==="
echo "Plesk-Basic-Namen, Datenbank und Webdateien unveraendert"
HW24_PRIVATE_READ_ALIASES
```

**Bei vorhandener Datei oder Laufzeitfehler:** **STOP** und Terminalmeldung abwarten; nichts überschreiben. Nur diese neue PHP-Datei wird absichtlich unter dem bestehenden `hw24_private`-Verzeichnis erstellt, kein gespeicherter Archiv-/Scanlisten-Datensatz verändert. Die durch das Skript getestete Freigabe ist zunächst ein **PHP-CLI-Nachweis**, kein tatsächlicher Auth-HTTP-GET unter `nvoss` auf TEST; dieser kann erst nach der **separaten** Test-Plesk-Basic-Namensumstellung erfolgen. Vor jener Kontooperation muss der Plesk-Root-Passwortschutz einschließlich Rückfallweg **gesondert gesichert** werden. Loginstatus alt TEST = `hw24-test`, Live = `nvoss`; `plesk-stat`-Konto unberührt.

[Authentifizierter Test im Journal](./HW24_ARBEITSJOURNAL.md), Commit `3a9c1848c937424aa2f99f13156b3e8abd0e3812`.

---

## PR37 TEST: PHP-SYNTAX UND BASIC-SCHUTZ BESTANDEN · 22.11.2026

**Read-only Betreiber-PuTTY `HW24_PR37_SECURITY` erfolgreich:** Vier `/opt/plesk/php/8.5/bin/php -l`-Checks für `api/scanliste.php`, `api/ai-reviews.php`, `api/ai-review.php` und `api/hw24-actor-identity.php` alle `No syntax errors detected`. Anonyme HTTPS-GETs auf `/api/scanliste.php`, `/api/ai-reviews.php`, `/api/jtl.php` alle **HTTP 401**. Kein Login-/DB-/Website-Write in diesem Check, äußere Basic-Authentifizierung wirksam. **Noch nicht** authentifizierte Datenfunktion bestätigt. Bereits zuvor Test-PR37 mit allen vier verifizierten PHP-Git-Hashes deployed. Live und Test-Basic-Login unverändert.

### Genau nächster Einzel-Befehl: zwei authentifizierte Test-GETs, nur Anzahlen ausgeben

```bash
bash <<'HW24_AUTHENTICATED_GET'
set -euo pipefail

url="https://test-artikelmanager.hairworld24.de"
php="/opt/plesk/php/8.5/bin/php"

echo "=== TEST-BASIC: GESPEICHERTES KI-ARCHIV ==="
echo "Das Passwort wird von curl verdeckt abgefragt, bitte NICHT im Chat mitteilen."

curl --silent --show-error --fail-with-body \
  --basic --user hw24-test --max-time 30 --proto '=https' \
  "$url/api/ai-reviews.php?limit=50" |
"$php" -r '
 $v=json_decode(stream_get_contents(STDIN),true);
 if(!is_array($v)||($v["ok"]??false)!==true||!is_array($v["reviews"]??null)){
   fwrite(STDERR,"STOP: KI-Archiv-GET fehlgeschlagen\n");exit(1);
 }
 $n=count($v["reviews"]);
 echo "Historische KI-Berichte: $n\n";
 if($n!==3){fwrite(STDERR,"STOP: Erwartet waren 3 Berichte\n");exit(1);}
'

echo "=== TEST-BASIC: OFFENE SCANLISTEN ==="
echo "Passwort erneut verdeckt eingeben."

curl --silent --show-error --fail-with-body \
  --basic --user hw24-test --max-time 30 --proto '=https' \
  "$url/api/scanliste.php?op=batches" |
"$php" -r '
 $v=json_decode(stream_get_contents(STDIN),true);
 if(!is_array($v)||($v["ok"]??false)!==true||!is_array($v["batches"]??null)){
   fwrite(STDERR,"STOP: Scanlisten-GET fehlgeschlagen\n");exit(1);
 }
 $n=count($v["batches"]);
 echo "Offene Scanlisten-Stapel unter altem Benutzer: $n\n";
 if($n>1){fwrite(STDERR,"HINWEIS: Zahl groesser als bisheriger SQL-Stand\n");}
'

echo "=== AUTHENTIFIZIERTE LESERUECKGABE GEPRUEFT ==="
echo "Keine Rohdaten, kein POST, keine API-Kosten, keine Aenderungen"
HW24_AUTHENTICATED_GET
```

**Sicherheit und Grenzen:** `curl --user hw24-test` ohne Doppelpunkt/Passwort im Kommando fordert bei interaktiver PuTTY-Terminalsitzung das Passwort verborgen an; es wird weder in die Shell-Argumente noch in GitHub oder in die Ausgabe geschrieben. Das Passwort **zweimal** eingeben, wenn beide curl-Aufrufe danach fragen. Beide API-Antworten gehen ausschließlich per Pipe an PHP und werden nur als Zählwerte ausgegeben, keine EANs/Review-Texte. `set -o pipefail` und `--fail-with-body` lassen fehlerhafte HTTP-Status/JSON-Formate scheitern.

**Wichtig:** Das KI-Archiv hat insgesamt **3** historische Berichte unter `hw24-test`, daher erwartet dieser Test genau drei. Die Scanlisten-MariaDB hat **1** Stapel unter `hw24-test`, doch die API gibt **nur offene** Stapel `closed_at_utc IS NULL` zurück; 0 ist daher möglich und kein Beweis für Datenverlust. Bei 0 als nächstes `closed_at_utc` **nur lesend** abklären, nicht automatisch INSERT/UPDATE. Noch keine private `hw24_private/actor-aliases.php`, keine Test-Basic-Umbenennung und kein Live-Deploy.

[Arbeitsjournal](./HW24_ARBEITSJOURNAL.md), Commit `645383969ba3b4366d54cd3a09e719257e1b4e04`; [Übergabe](./HW24_HANDOVER.md), Commit `60da44215859f934245fa1e5749ae345c8ae9e7c`.

---

## NEUER MEILENSTEIN · PR37 Git-Quellbranch auf TEST korrekt ausgewählt; NÄCHSTER SCHRITT IST DEPLOY (22.11.2026)

**Betreiber-PuTTY bestätigte:** `HW24_PR37_SELECT` erfolgreich: Sicherungschecksumme OK, Plesk `HW24-scanner.git` für **Test** auf `feature/hw24-login-alias-scan-ai-20261122` umgestellt, exakt Commit `c2cc4e1dc054da00f01087e7221659cf54a9a482` verifiziert, die Legacy-Webroot-Dateien blieben unverändert. **Noch KEIN Plesk-Deploy, kein HTTP-Basic-Loginwechsel und keine Alias-Privatekonfiguration.** Die Live-Website blieb unberührt.

### Jetzt EINMAL kontrolliert nur auf TEST deployen

Vor dem mutierenden Test-Deploy werden Backup-SHA, Plesk-Manuellmodus, aktuelle Featurebranch/Commit sowie die drei bisherigen Webroot-Blob-SHAs erneut kontrolliert. Nach dem Deploy müssen alle **vier** betroffenen PHP-Dateien exakt den frisch bei GitHub verifizierten PR37-Blob-SHAs entsprechen. Alle anderen User-/JTL-/Amazon-/OpenAI-/Datenbank-Systeme bleiben unangetastet.

```bash
bash <<'HW24_PR37_TEST_DEPLOY'
set -euo pipefail
umask 077

d="test-artikelmanager.HAIRWORLD24.de"
r="HW24-scanner.git"
branch="feature/hw24-login-alias-scan-ai-20261122"
commit="c2cc4e1dc054da00f01087e7221659cf54a9a482"
root="/var/www/vhosts/hairworld24.de/$d"
backup="/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz"
checksum="679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26"

echo "=== HW24 PR37: NUR TEST-CODE DEPLOYEN ==="
printf '%s  %s\n' "$checksum" "$backup" | sha256sum -c --status || {
  echo "STOP: Backup-SHA nicht korrekt"; exit 1;
}

info="$(plesk ext git --info -domain "$d" -name "$r")"
printf '%s\n' "$info" | grep -Fq "Active branch: $branch"
printf '%s\n' "$info" | grep -Fq 'Deployment mode: manual'
current="$(plesk ext git --get-last-commit -domain "$d" -name "$r" |
  sed -nE 's/^commit[[:space:]]+([0-9a-f]{40}).*/\1/p')"
test "$current" = "$commit" || {
  echo "STOP: Unbekannter Branch-Commit: $current"; exit 1;
}

for pair in \
  "api/scanliste.php e9fb8075c4f532d7a76adf81e760acf81cab4528" \
  "api/ai-reviews.php 00fb61c9d636434cdc6562edf169811a717c0911" \
  "api/ai-review.php 2eabde207434b8c62162dc553354cd052c6ec98f"; do
  path="${pair%% *}"
  expected="${pair#* }"
  test "$(git hash-object "$root/$path")" = "$expected" || {
    echo "STOP: Test-Quelldatei weicht ab: $path"; exit 1;
  }
done
test ! -e "$root/api/hw24-actor-identity.php" || {
  echo "STOP: Helferdatei bereits vorhanden"; exit 1;
}
echo "Gates OK: Backup, Branch, Commit und bisheriger Testcode"

log="$(mktemp /root/hw24-backups/pr37-test-deploy.XXXXXXXX)"
echo "Fuehre jetzt NUR das manuelle Plesk-Git-Deployment der TESTDOMAIN aus ..."
if ! plesk ext git --deploy -domain "$d" -name "$r" >"$log" 2>&1; then
  echo "STOP: Plesk-Deploy FEHLGESCHLAGEN; privates Log: $log"
  echo "KEIN AUTO-ROLLBACK. Test-Webroot und Git-Zustand zuerst pruefen."
  exit 1
fi

echo "Plesk-Deploy-Befehl abgeschlossen; pruefe PHP-Blob-Hashes ..."
for pair in \
  "api/scanliste.php 1a19331fc030ef473d05cc0b94fee92fd2d436ea" \
  "api/ai-reviews.php 41bfe53eba6f4392e4f0f48e92828fa004ffe311" \
  "api/ai-review.php aba03b6146a34698bae71fe622671945019d2db5" \
  "api/hw24-actor-identity.php cb2722952bd0cad9680d919630e4b2a06ba367fc"; do
  path="${pair%% *}"
  expected="${pair#* }"
  actual="$(git hash-object "$root/$path" 2>/dev/null || echo MISSING)"
  if [ "$actual" != "$expected" ]; then
    echo "STOP: UNERWARTETER Test-Dateihash: $path ($actual)"
    echo "KEIN AUTO-ROLLBACK; verwende privaten Webroot-Snapshot nach Analyse."
    exit 1
  fi
  echo "OK $path"
done

echo "=== PR37 TEST-CODE BEREITGESTELLT UND HASHES GEPRUEFT ==="
echo "Noch KEINE Benutzerumbenennung, keine private read_aliases-Datei"
echo "Live-Website und historische Datenbanken nicht geaendert"
HW24_PR37_TEST_DEPLOY
```

**WICHTIG ZUM ROLLBACK:** Das geprüfte private TAR `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz` ist der Rückfallstand für Test-Webdateien, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`. Kein automatisches `tar -x` im Skript; nach partiellen Dateifehlern muss die Ursache zuerst festgestellt werden. Der Plesk-Git-Testbranch kann separat wieder auf `deploy/staging` gestellt werden, das ist aber kein nachgewiesener Wiederherstellungslauf.

**Nach erfolgreicher rein technischer Bereitstellung, SEPARATE GATES:** 1. HTTP-Anmeldung mit bestehendem Testbenutzer und Scanliste/KI-Archiv prüfen; 2. private serverseitige `read_aliases`-Freigabe mit Rechten außerhalb der Webroots installieren und bewusst validieren; 3. Test-Plesk-Root-Basic-Passwortschutz separat sichern, dann NUR den Testbenutzer umbenennen; 4. unter neuem Login beide Scan-Stapel und drei historischen KI-Berichte browserseitig abnehmen; 5. Live erst nach expliziter Freigabe gesondert behandeln. Laravel-Filament-Webroot bleibt bis dahin unangetastet.

[Prüfjournal](./HW24_ARBEITSJOURNAL.md), Commit `b48c6a1b36fb7fe3b24e1939c94a767b2d120c86`.

---

## AKTUELLSTER BETREIBER-BEFUND · TEST-RÜCKSTELLUNG ERFOLGREICH (22.11.2026)

**Nach vorherigem falschem SHA-Textvergleich jetzt wirklich read-only verifiziert:** `test-artikelmanager.HAIRWORLD24.de` / `HW24-scanner.git` steht wieder auf `deploy/staging`, `Deployment mode: manual`, deploy path `/test-artikelmanager.HAIRWORLD24.de`. Die drei Legacy-APIs haben ihre unveränderten Git-Blob-SHAs: `api/scanliste.php=e9fb8075c4f532d7a76adf81e760acf81cab4528`, `api/ai-reviews.php=00fb61c9d636434cdc6562edf169811a717c0911`, `api/ai-review.php=2eabde207434b8c62162dc553354cd052c6ec98f`. `api/hw24-actor-identity.php` **nicht vorhanden**. Der versuchte Metadaten-Rollback hat somit funktioniert; der Testcode blieb unverändert, kein Deploy.

[Draft PR #37](https://github.com/NicoVoss1/HW24-scanner/pull/37) HEAD **`c2cc4e1dc054da00f01087e7221659cf54a9a482`**, CI-Läufe `44978357305` und `44978360796` beide grün, nochmals am 22.11.2026 über GitHub überprüft. Test-Webroot-Backup: `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, SHA256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`.

### Nächste einzelne Aktion: Test-Git-Branch wählen und Commit-Zeile korrekt parsen; KEIN Deploy

**NUR diesen neuen korrigierten Block verwenden.** Der vorherige Block `HW24_PR37_BRANCH_ONLY` weiter unten in der Historie ist wegen fehlerhafter Commit-Ausgabeformat-Annahme überholt.

```bash
bash <<'HW24_PR37_SELECT'
set -euo pipefail
d="test-artikelmanager.HAIRWORLD24.de"
r="HW24-scanner.git"
b="feature/hw24-login-alias-scan-ai-20261122"
want="c2cc4e1dc054da00f01087e7221659cf54a9a482"
root="/var/www/vhosts/hairworld24.de/$d"
backup="/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz"
sum="679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26"

echo "=== NUR PLESK-TEST-GIT-BRANCH AUSWAEHLEN ==="
printf '%s  %s\n' "$sum" "$backup" | sha256sum -c --status

info="$(plesk ext git --info -domain "$d" -name "$r")"
printf '%s\n' "$info" | grep -Fq 'Deployment mode: manual'
printf '%s\n' "$info" | grep -Fq 'Active branch: deploy/staging'

for pair in \
  "api/scanliste.php e9fb8075c4f532d7a76adf81e760acf81cab4528" \
  "api/ai-reviews.php 00fb61c9d636434cdc6562edf169811a717c0911" \
  "api/ai-review.php 2eabde207434b8c62162dc553354cd052c6ec98f"; do
  path="${pair%% *}"
  sha="${pair#* }"
  test "$(git hash-object "$root/$path")" = "$sha"
done
test ! -e "$root/api/hw24-actor-identity.php"
echo "Backup und Legacy-Dateien: OK"

# Featurebranch wurde im vorherigen Versuch bereits erfolgreich gefetched.
plesk ext git --update -domain "$d" -name "$r" -active-branch "$b"

info="$(plesk ext git --info -domain "$d" -name "$r")"
printf '%s\n' "$info" | grep -Fq "Active branch: $b"
printf '%s\n' "$info" | grep -Fq 'Deployment mode: manual'

# --get-last-commit gibt einen mehrzeiligen Git-Block zurück:
# Nur den SHA aus der Zeile "commit <40 HEX>" extrahieren.
actual="$(plesk ext git --get-last-commit -domain "$d" -name "$r" |
  sed -nE 's/^commit[[:space:]]+([0-9a-f]{40}).*/\1/p')"
echo "Gewaehlter Git-HEAD: $actual"
if [ "$actual" != "$want" ]; then
  echo "STOP: Branch-HEAD nicht erwartet. Kein Deploy ausfuehren."
  exit 1
fi
for pair in \
  "api/scanliste.php e9fb8075c4f532d7a76adf81e760acf81cab4528" \
  "api/ai-reviews.php 00fb61c9d636434cdc6562edf169811a717c0911" \
  "api/ai-review.php 2eabde207434b8c62162dc553354cd052c6ec98f"; do
  path="${pair%% *}"
  sha="${pair#* }"
  test "$(git hash-object "$root/$path")" = "$sha"
done
test ! -e "$root/api/hw24-actor-identity.php"
echo "ERFOLG: Test-Git-Featurebranch gewaehlt, Webroot unveraendert"
echo "KEIN DEPLOY, KEIN ACCOUNT-RENAME, KEINE KI-KOSTEN"
HW24_PR37_SELECT
```

**Wenn `STOP` oder vorheriges Shell-Ende:** Aktiven Testbranch und Webroot erneut nur lesend prüfen. Selbst wenn die Branch-Metadaten auf PR37 zeigen, ist **ohne `--deploy` noch keine PR37-Datei in der Website installiert**. Einen Rollback der Branch-Metadaten vor allem bei unerwartetem SHA erst nach Statusfeststellung ausführen, nicht nochmal blind das alte fehlerhafte Skript verwenden. TEST-Login `hw24-test` weiterhin unverändert; Live-Basic `nvoss` und die drei KI-Berichte/Scan-Stapel bleiben erhalten.

[Beleg im Journal](./HW24_ARBEITSJOURNAL.md), Commit `6382a2816283aa8f0380c70d7d63977e4199b147`.

---

## WICHTIGE KORREKTUR · 22.11.2026 · Git SHA bereits richtig, vorheriges Branch-Skript NICHT erneut ausführen

**Benutzer-PuTTY-Ausgabe:** `HW24_PR37_BRANCH_ONLY` hat das Backup erfolgreich geprüft, nur auf Test `git --fetch` und `--update -active-branch ...` ausgeführt. Der tatsächliche Plesk-`--get-last-commit`-Output beginnt mit `commit c2cc4e1dc054da00f01087e7221659cf54a9a482` und enthält danach **Autor, Datum, Nachricht**. Der im alten Skript verwendete Vergleich nach `tr -d '\r\n'` ist **FALSCH**: Er vergleicht den gesamten zusammengeklebten Block statt der reinen 40-stelligen SHA. Daher der scheinbare `STOP: Unerwarteter Commit`. **Der angezeigte SHA war korrekt.**

**Fehlerbehandlung:** Das alte Skript hat danach `Branch-Rueckstellung versucht` ausgegeben. **NICHT BELEGT**, ob die Rückstellung der Plesk-Test-Git-Metadaten erfolgreich war, da der eigentliche `--update`-Ausgang ins private Log ging und `|| true` verwendet wurde. Kein `--deploy` wurde im Skript aufgerufen. Die drei Test-Webroot-Dateien müssen trotzdem separat erneut nur lesend geprüft werden. **Nicht noch einmal das alte `HW24_PR37_BRANCH_ONLY`-Skript ausführen.**

### JETZT EINZIG AUSFÜHREN: Read-only Status nach dem Abbruch

```bash
bash <<'HW24_PR37_STATUS'
set -euo pipefail

domain="test-artikelmanager.HAIRWORLD24.de"
repo="HW24-scanner.git"
root="/var/www/vhosts/hairworld24.de/$domain"

echo "=== HW24 PR37: TEST-STATUS NACH ROLLBACK-VERSUCH ==="

plesk ext git --info -domain "$domain" -name "$repo" |
  grep -E '^(Deployment path|Deployment mode|Active branch):'

echo "=== Test-Webroot PHP-Dateien (nur Hashes) ==="
for file in api/scanliste.php api/ai-reviews.php api/ai-review.php; do
  if test -f "$root/$file"; then
    printf '%s: ' "$file"
    git hash-object "$root/$file"
  else
    echo "$file: NICHT VORHANDEN"
  fi
done

if test -f "$root/api/hw24-actor-identity.php"; then
  echo "Neue Owner-Hilfe im Test-Webroot: VORHANDEN"
else
  echo "Neue Owner-Hilfe im Test-Webroot: NICHT VORHANDEN"
fi

echo "=== STATUS GELADEN: KEINE AENDERUNG AUSGEFUEHRT ==="
HW24_PR37_STATUS
```

**Für jede spätere Plesk-Commitprüfung:** Nur die SHA aus der Zeile `commit <40 hex>` extrahieren, z. B. per `sed -nE 's/^commit[[:space:]]+([0-9a-f]{40}).*/\1/p'`. Das alte `tr -d '\r\n'`-Vergleichsmuster nicht verwenden. Keine neue Bereitstellung, bis der aktuelle Test-Git-Branch nach dem versuchten Rollback verifiziert ist.

[Einzeljournal](./HW24_ARBEITSJOURNAL.md), Commit `7329b6c88cedda64a6ec93969a7b0b6d2fd636fe`.

---

## 22.11.2026 · Test-Webroot-Backup erfolgreich, PR37 Git-Diff auf 7 Dateien eingegrenzt

**Betreiber-PuTTY-Erfolg:** Test-Webroot gesichert unter `/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz`, TAR-Dateiinhaltscheck bestanden, SHA-256 `679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26`. Live/Test/Basic-Anmeldung unberührt. Backup ist **nicht** automatisch ein Backup der Plesk-HTTP-Basic-Konfiguration oder der MariaDB-Datenbank.

**GitHub-Connector-Compare:** PR37-Featurebranch gegenüber `deploy/staging` **12 Commits vor, 0 zurück**, exakt 7 Dateipfade geändert: `.github/workflows/hw24-v425-intelligence-test.yml`, `api/scanliste.php`, `api/ai-reviews.php`, `api/ai-review.php`, neuer `api/hw24-actor-identity.php`, `tests/hw24-actor-alias.test.php`, `tests/hw24-v425-article-intelligence.test.cjs`. CI für HEAD `c2cc4e1dc054da00f01087e7221659cf54a9a482` zweimal grün. Keine geänderten JS-/HTML-Frontends oder Datenbankmigrationen im PR.

### Nächster abgetrennter Schritt: NUR TEST-Git-Featurebranch vorbereiten, NOCH KEIN DEPLOY

**Ziel:** Nach erfolgreichem Backup nur das **manuelle** Plesk-Git-Test-Repository `HW24-scanner.git` aktualisieren und seine aktive Branch-Metadaten auf den **gepinnten** PR37-Featurebranch setzen. `plesk ext git --update -active-branch ...` ist laut [offizieller Plesk-Obsidian-CLI-Dokumentation](https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/git-git-repositories.75956/) die vorgesehene Option. **Keine** `--deploy`-Operation, keine Live-Änderung.

```bash
bash <<'HW24_PR37_BRANCH_ONLY'
set -euo pipefail
umask 077

domain="test-artikelmanager.HAIRWORLD24.de"
repo="HW24-scanner.git"
branch="feature/hw24-login-alias-scan-ai-20261122"
want="c2cc4e1dc054da00f01087e7221659cf54a9a482"
backup="/root/hw24-backups/test-login-pr37-auHITpmY/test-webroot.tar.gz"
expect_sum="679afdf53fc6520c9f7180919bf26e9b2470811b85c384bb97dede298e41dd26"
webroot="/var/www/vhosts/hairworld24.de/test-artikelmanager.HAIRWORLD24.de"
log="$(mktemp /root/hw24-backups/pr37-git-stage.XXXXXXXX)"
switched=no
verified=no

rollback() {
  if [ "$switched" = yes ] && [ "$verified" != yes ]; then
    plesk ext git --update -domain "$domain" -name "$repo" \
      -active-branch deploy/staging >>"$log" 2>&1 || true
    echo "Fehler: Git-Branch-Rueckstellung versucht, bitte Status nachpruefen."
  fi
}
trap rollback EXIT

echo "=== HW24 PR37 NUR TEST-BRANCH VORBEREITEN ==="
test -f "$backup"
printf '%s  %s\n' "$expect_sum" "$backup" | sha256sum -c --status
echo "Backup SHA256: OK"

info="$(plesk ext git --info -domain "$domain" -name "$repo")"
printf '%s\n' "$info" | grep -Fq 'Deployment mode: manual'
printf '%s\n' "$info" | grep -Fq 'Active branch: deploy/staging'
echo "TEST-Repository: branch deploy/staging, manuell"

before="$(for p in api/scanliste.php api/ai-reviews.php api/ai-review.php; do
  git hash-object "$webroot/$p"
done)"
echo "1. Featurebranch aus GitHub abrufen, ohne Deploy ..."
if ! plesk ext git --fetch -domain "$domain" -name "$repo" >"$log" 2>&1; then
  echo "STOP: Git-Fetch nicht erfolgreich. Privates Log: $log"; exit 1
fi

echo "2. Nur Plesk-Test-Git-Branch auf PR37 setzen ..."
if ! plesk ext git --update -domain "$domain" -name "$repo" \
  -active-branch "$branch" >>"$log" 2>&1; then
  echo "STOP: Branch-Umstellung fehlgeschlagen. Privates Log: $log"; exit 1
fi
switched=yes

after_info="$(plesk ext git --info -domain "$domain" -name "$repo")"
printf '%s\n' "$after_info" | grep -Fq "Active branch: $branch"
printf '%s\n' "$after_info" | grep -Fq 'Deployment mode: manual'

actual="$(plesk ext git --get-last-commit -domain "$domain" -name "$repo" | tr -d '\r\n')"
if [ "$actual" != "$want" ]; then
  echo "STOP: Unerwarteter PR-Commit: $actual"
  exit 1
fi
echo "PR37-Commit: $actual"

after="$(for p in api/scanliste.php api/ai-reviews.php api/ai-review.php; do
  git hash-object "$webroot/$p"
done)"
if [ "$before" != "$after" ]; then
  echo "STOP: Test-Webroot-Inhalte haben sich unerwartet geaendert."
  exit 1
fi
echo "Test-Webroot-Dateihashes unveraendert: JA"
test ! -f "$webroot/api/hw24-actor-identity.php"
echo "KEIN DEPLOY AUSGEFUEHRT; Live und Datenbanken unveraendert"
verified=yes
echo "=== PR37-QUELLBRANCH NUR AUF TEST VORBEREITET ==="
HW24_PR37_BRANCH_ONLY
```

**Abbruch:** Ein misslungener Branchwechsel wird nicht durch einen `--deploy` verschlimmert. Der Skript-Rollback versucht bei einem Fehler nach der Branchänderung lediglich die **Git-Metadaten** auf `deploy/staging` zu setzen, stellt aber keine Webdateien automatisch wieder her. Das erhaltene private Backup bleibt bis zur Endabnahme unangetastet.

**Nächstes Gate nach Erfolg:** separater kontrollierter Test-Webroot-Deploy mit vorab noch zu prüfenden Plesk-HTML-/API-Abhängigkeiten, danach private `read_aliases`-Datei (streng berechtigter `nvoss`-Login liest beide Owner), Browser-Nachweis alte/neue Scan-Stapel und 3 KI-Berichte, und erst danach TEST-Basic-Kennung umbenennen. **Vor der Plesk-Rename-Operation muss der tatsächliche Test-Passwortschutz noch separat gesichert werden.** Nicht über Plesk-Git gleichzeitig Laravel/Filament auf die Testdomain schalten.

---

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
