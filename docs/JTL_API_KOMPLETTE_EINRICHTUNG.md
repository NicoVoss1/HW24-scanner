# HW24 Artikelmanager: vollständige JTL-Wawi-API-Einrichtung
Stand: 14. November 2026 · Code: v4.2 · Datenbank: eazybusiness / SQL Server 2017 Express · Plesk: MariaDB hw24_artikelmanager

> Ziel: JTL-Wawi **nur lesend** auf Windows abfragen, vollständigen Artikel-Snapshot mit HTTPS an Plesk senden, im separaten MariaDB-Spiegel speichern und per GET /api/jtl.php für den Artikelmanager nutzen. Es erfolgt **KEIN Schreibzugriff auf eazybusiness**. Neuanlage/Aktualisierung später über JTL-Ameise.

## A. Dateien, keine Zugangsdaten ins GitHub

1. sql/HW24_08_MARIADB_STATUS_PRUEFEN_LESEND.sql · bestehende Tabellen und Spalten prüfen
2. sql/HW24_06_JTL_SPIEGEL.sql · CREATE TABLE für hw24_jtl_sync_meta und hw24_jtl_articles
3. sql/HW24_07_QUELLEN_ENTWUERFE_KATEGORIEN_AUDIT.sql · optionaler kompletter Quellen-, Entwurfs-, Kategorie- und Audit-Speicher
4. sql/HW24_09_UEBERVERKAEUFE_STANDARD_NEIN.sql · bestehenden Standard von Y auf **N** korrigieren, falls 07 bereits früher ausgeführt wurde
5. api/jtl-status.php · öffentliche URL nur hinter Plesk Basic-Auth, zeigt Setupstatus ohne Geheimnisse
6. api/jtl-sync.php · POST-Empfänger, verlangt Token + HTTPS + Plesk Basic-Auth
7. api/jtl.php · lesender GET des abgeschlossenen JTL-Spiegels, findet GTIN, HAN und Artikelnummer
8. sync/HW24_JTL_Verbindungstest.ps1 · Test mit SQL SELECT/HTTP GET, **keine Datenänderung**
9. sync/HW24_JTL_Synchronisieren.ps1 · vollständige Snapshot-Übertragung
10. Dieses Dokument: docs/JTL_API_KOMPLETTE_EINRICHTUNG.md

## B. Plesk auf aktuelle GitHub-Version setzen

Plesk > Websites & Domains > artikelmanager.HAIRWORLD24.de > Git:
- Branch main, Deployment-Verzeichnis **/httpdocs** (falls weiterhin tatsächlich Domain-Dokumentenstamm).
- "Jetzt Pull ausführen", anschließend "Jetzt bereitstellen" anklicken.
- Prüfen, ob api/jtl-status.php, api/jtl-sync.php, api/jtl.php im öffentlichen Dokumentenstamm /api liegen.
- Im Browser https://artikelmanager.hairworld24.de/api/jtl-status.php aufrufen.
- Die bestehende **Plesk Passwortgeschütztes Verzeichnis / HTTP Basic-Authentifizierung** muss auch für die APIs gelten. Browser-Login per Formular ist nicht automatisch gleich HTTP Basic.

## C. MariaDB prüfen und Tabellen aufbauen (phpMyAdmin)

1. Plesk > Datenbanken > hw24_artikelmanager > phpMyAdmin.
2. VOR Änderungen vollständige Datenbanksicherung erstellen.
3. SQL-Tab öffnen. Zuerst **vollständigen** Inhalt von sql/HW24_08_MARIADB_STATUS_PRUEFEN_LESEND.sql ausführen.
4. Falls Tabellen hw24_jtl_articles/hw24_jtl_sync_meta fehlen, vollständigen Inhalt von sql/HW24_06_JTL_SPIEGEL.sql ausführen.
5. Optional, aber für das weitere Projekt vorgesehen: sql/HW24_07_QUELLEN_ENTWUERFE_KATEGORIEN_AUDIT.sql ausführen.
6. Wenn 07 bereits früher ausgeführt wurde: sql/HW24_09_UEBERVERKAEUFE_STANDARD_NEIN.sql ausführen, damit Überverkäufe standardmäßig N bleiben.
7. 08 erneut ausführen. **CREATE TABLE IF NOT EXISTS** ergänzt keine fehlenden Spalten einer bestehenden Tabelle. Bei Schema-Abweichung nicht blind löschen oder überschreiben.

Kontrollabfragen (MariaDB):

~~~sql
SELECT DATABASE() AS datenbank;

SHOW TABLES LIKE 'hw24_jtl_%';

SHOW COLUMNS FROM hw24_jtl_articles;
SHOW COLUMNS FROM hw24_jtl_sync_meta;

SELECT source_id,status,last_row_count,last_completed_utc
FROM hw24_jtl_sync_meta
ORDER BY updated_utc DESC LIMIT 5;
~~~

Bei frisch angelegten Tabellen: die letzten SELECTs liefern noch 0 Zeilen. **Kein INSERT von Hand**, kein SET status=ready.

## D. MariaDB-Nutzer trennen (DB-Administrator erforderlich)

Bestehender **hw24_app** bleibt nur lesend für api/jtl.php und api/historie.php.
Separater **hw24_sync** schreibt ausschließlich in die beiden Spiegel-Tabellen.
Plesk kann neue Benutzer anlegen, schränkt aber ggf. nicht auf Tabellenebene ein.
Für exakte Rechte SQL als MariaDB-Administrator ausführen:

~~~sql
-- Geheimnis nur auf dem Server einsetzen, niemals in GitHub oder ChatGPT.
CREATE USER IF NOT EXISTS 'hw24_sync'@'localhost'
  IDENTIFIED BY 'INDIVIDUELLES_LANGES_ZUFALLSPASSWORT';

GRANT SELECT, INSERT, UPDATE, DELETE
ON hw24_artikelmanager.hw24_jtl_articles
TO 'hw24_sync'@'localhost';

GRANT SELECT, INSERT, UPDATE, DELETE
ON hw24_artikelmanager.hw24_jtl_sync_meta
TO 'hw24_sync'@'localhost';

SHOW GRANTS FOR 'hw24_sync'@'localhost';
~~~

Die API nutzt DSN host=127.0.0.1. Bei MariaDB mit getrennter Hostprüfung kann alternativ oder zusätzlich ein **gleichermaßen eingeschränktes** Konto 'hw24_sync'@'127.0.0.1' erforderlich sein. Das nicht pauschal mit Remote-Host '%' lösen. Nur lokale Zugriffe.

Keine ALTER, DROP, FILE, ALL PRIVILEGES, Remote-Zugriffe oder Rechte auf historische Excel-Daten. Vorhandene hw24_app-Privilegien kontrollieren und SELECT nicht entfernen.

## E. Private PHP-Konfiguration (außerhalb Webroot)

Plesk Dateimanager: **nicht** in httpdocs oder /api speichern.
Ablage: /var/www/vhosts/hairworld24.de/hw24_private

1. Datei db.php für **Lesen**: nur neu anlegen, falls nicht bereits für die historische Excel-API eingerichtet. Nicht blind überschreiben!
~~~php
<?php
return [
  'user' => 'hw24_app',
  'password' => 'GEHEIMES_LESER_PASSWORT'
];
~~~

2. Datei jtl_sync.php für **Snapshot-Übertragung**:
~~~php
<?php
return [
  'user' => 'hw24_sync',
  'password' => 'GEHEIMES_SYNC_DATENBANKPASSWORT',
  'token_sha256' => '64_STELLIGER_SHA256_HASH_DES_API_TOKENS',
  'min_rows' => 2500
];
~~~

php.ini/Plesk: PHP-Version >=8.4 (bei letzter Prüfung 8.5.12), Erweiterung pdo_mysql an.
Verzeichnis und Dateien für PHP-FPM-Benutzer lesbar, nicht über HTTP erreichbar.
Keine SQL-Zugangsdaten oder Tokens ins GitHub, in Query-Strings oder Screenshots übernehmen.
Die API benutzt mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager.

Im Browser GET https://artikelmanager.hairworld24.de/api/jtl-status.php prüfen.
Feld next='configure_and_run_windows_sync' bedeutet: Mirror-Tabellen erreichbar, noch kein aktueller Snapshot. 
Auch auf das separate Feld sync_config='present' achten: das Statusskript testet **nicht** das tatsächliche Kennwort.

## F. SQL Server 2017 / eazybusiness (HeidiSQL oder SSMS auf Windows)

HeidiSQL mit vorhandenen berechtigten ADMIN-Anmeldedaten nutzen, aber NICHT 'sa' im Synchronisationsskript verwenden.
Zuerst nur SELECT:

~~~sql
USE [eazybusiness];

SELECT DB_NAME() AS datenbank, COUNT_BIG(*) AS anzahl_artikel
FROM dbo.tArtikel;

SELECT TOP (10) kArtikel,cArtNr,cBarcode,cHAN,cASIN,fVKNetto,fEKNetto,
  fLetzterEK,dLetzterEK,nLagerbestand,dMod
FROM dbo.tArtikel ORDER BY kArtikel DESC;

SELECT kArtikel,cArtNr,cBarcode,cHAN,cASIN
FROM dbo.tArtikel
WHERE cBarcode=N'4015600040482'
   OR cHAN=N'4015600040482'
   OR cArtNr=N'4015600040482';

SELECT COUNT_BIG(*) AS beschreibungen
FROM dbo.tArtikelBeschreibung;
~~~

Separaten Leser **hw24_reader** EINMALIG als Admin anlegen (falls noch nicht existent):

~~~sql
USE [master];
CREATE LOGIN [hw24_reader]
WITH PASSWORD = 'EIGENES_SEHR_STARKES_SQL_LOGIN_PASSWORT';

USE [eazybusiness];
CREATE USER [hw24_reader] FOR LOGIN [hw24_reader];
GRANT SELECT ON OBJECT::dbo.tArtikel TO [hw24_reader];
GRANT SELECT ON OBJECT::dbo.tArtikelBeschreibung TO [hw24_reader];
~~~

Bei bestehendem Login/User nicht CREATE wiederholen, sondern Berechtigungen kontrollieren.
**Keine DB-Schreibrechte.** Der Synchronisierer benutzt NUR diese beiden Tabellen; Hersteller- und Lagerdetailtabellen ergänzen wir später nach verifizierter Struktur.

## G. Windows-Dateien vorbereiten

Auf dem Windows-PC mit LAN-/VPN-Verbindung zum SQL-Server:

~~~powershell
New-Item -ItemType Directory -Force C:\HW24\secrets | Out-Null
# Beide GitHub-PowerShell-Dateien in C:\HW24 kopieren.
# Die beiden folgenden Dialoge erzeugen lokal verschlüsselte Zugangsdaten:
Get-Credential -UserName 'hw24_reader' -Message 'Nur-Lesen-JTL-SQL-Zugang' |
  Export-Clixml -Path C:\HW24\secrets\jtl_reader.xml

Get-Credential -Message 'Plesk HTTP Basic Benutzer' |
  Export-Clixml -Path C:\HW24\secrets\plesk_http.xml
~~~

Beide Dateien sind DPAPI-geschützt und nur unter **demselben Windows-Konto auf demselben Computer** wiederherstellbar. Den Windows-Aufgabenplaner später mit exakt demselben Konto betreiben.

### API-Token sicher erzeugen

~~~powershell
$bytes = New-Object byte[] 32
$rng = [Security.Cryptography.RandomNumberGenerator]::Create()
$rng.GetBytes($bytes)
$rng.Dispose()
$token = [Convert]::ToBase64String($bytes)
$sha = [Security.Cryptography.SHA256]::Create()
$hash = ([BitConverter]::ToString(
  $sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($token))
)).Replace('-','').ToLowerInvariant()
$sha.Dispose()
(ConvertTo-SecureString -String $token -AsPlainText -Force |
  ConvertFrom-SecureString) |
  Set-Content -Path C:\HW24\secrets\api_token.dpapi -Encoding UTF8
Write-Host ('Diesen SHA-256-Hash NUR in jtl_sync.php einsetzen: '+$hash)
Remove-Variable token
~~~

Der angezeigte Hash gehört zu token_sha256 in der privaten Plesk-Datei.
Das echte Token bleibt **ausschließlich** verschlüsselt in api_token.dpapi.

### Konfiguration C:\HW24\jtl-sync.json

~~~json
{
  "endpoint": "https://artikelmanager.hairworld24.de/api/jtl-sync.php",
  "sql_server": "192.168.178.28,1433",
  "sql_credential_file": "C:\\HW24\\secrets\\jtl_reader.xml",
  "http_credential_file": "C:\\HW24\\secrets\\plesk_http.xml",
  "api_token_file": "C:\\HW24\\secrets\\api_token.dpapi",
  "batch_size": 15,
  "min_rows": 2500
}
~~~

Das ist die bisher benannte LAN-IP. WICHTIG: Die Skripte nutzen
Encrypt=true und TrustServerCertificate=false. Wenn das SQL-Server-TLS-Zertifikat
**nicht auf 192.168.178.28** ausgestellt ist, kann die Verbindung scheitern.
In dem Fall den geprüften Server-Hostnamen passend zum Zertifikat in sql_server
verwenden und die entsprechende Namensauflösung sicherstellen.
Nicht die Zertifikatskontrolle abschalten.

Optionaler Netzwerkcheck:

~~~powershell
Test-NetConnection 192.168.178.28 -Port 1433
~~~

Der SQL-Port 1433 muss nur intern/VPN erreichbar sein, niemals über öffentliche Router-Freigabe.

## H. Erst Verbindung testen (KEIN Insert)

PowerShell im Ordner C:\HW24 unter dem ursprünglichen Windows-Benutzer:

~~~powershell
Set-Location C:\HW24
.\HW24_JTL_Verbindungstest.ps1 -ConfigFile C:\HW24\jtl-sync.json -TestEan 4015600040482
~~~

Soll zeigen:
- SQL Server eazybusiness erreichbar
- Artikelanzahl größer/gleich 2500
- 1 oder mehrere Treffer für Test-EAN
- Plesk API erreichbar (PHP, MySQL-Treiber, Mirror-Tabellen)
- Keine Änderung an JTL oder MariaDB

Bei Verbindungsfehler erst SQL-Benutzer, Port, TLS-Hostname/Zertifikat, HTTP-Basic-Schutz, private PHP-Dateien und MariaDB-Rechte prüfen.

## I. Vollständigen Snapshot starten (nur MariaDB wird beschrieben)

Voraussetzung: Test H erfolgreich; DB-Backup vorhanden; Tabellen/DB-Rechte bereit.
Das Skript prüft erst api/jtl-sync.php mit einem authentifizierten
action='probe' (ohne Schreibzugriff) und führt dann SQL SELECT aus.
Danach sendet es die Artikel in Paketen an die API:

~~~powershell
Set-Location C:\HW24
.\HW24_JTL_Synchronisieren.ps1 -ConfigFile C:\HW24\jtl-sync.json
~~~

Die API benutzt intern folgende DB-Aktionen, diese **nicht selbst in phpMyAdmin ausführen**:
1. INSERT in hw24_jtl_sync_meta mit Status **staging** und neuer run_id.
2. INSERT INTO hw24_jtl_articles ... ON DUPLICATE KEY UPDATE in 15er-Paketen;
   Schlüssel (source_id,jtl_article_id).
3. SELECT COUNT(*) zum Abgleich mit SQL Server.
4. UPDATE hw24_jtl_sync_meta auf **ready** nur wenn vollständig und mindestens
   min_rows vorhanden. Abgebrochene Snapshots werden nicht freigegeben.
5. Alte erfolgreiche Spiegel werden auf maximal drei reduziert.

**Keine** direkte JTL-Wawi-Schreiboperation und **kein** JTL-Ameise-Import.

Nach dem Lauf, MariaDB-Kontrolle:

~~~sql
SELECT source_id,status,last_row_count,last_completed_utc
FROM hw24_jtl_sync_meta ORDER BY updated_utc DESC LIMIT 5;

SELECT COUNT(*) AS artikel_im_aktuellen_spiegel
FROM hw24_jtl_articles AS a
INNER JOIN hw24_jtl_sync_meta AS m ON m.source_id=a.source_id
WHERE m.status='ready'
  AND m.last_completed_utc=(
     SELECT MAX(last_completed_utc)
     FROM hw24_jtl_sync_meta WHERE status='ready'
  );

SELECT artikelnummer,ean,han,asin,artikelname,netto_vk,netto_ek,
       letzter_ek,bestand,synced_at_utc
FROM hw24_jtl_articles
WHERE ean='4015600040482' OR han='4015600040482'
   OR artikelnummer='4015600040482'
ORDER BY synced_at_utc DESC LIMIT 10;
~~~

## J. HTTP/API-Endkontrolle

1. https://artikelmanager.hairworld24.de/api/jtl-status.php
2. https://artikelmanager.hairworld24.de/api/jtl.php?ean=4015600040482
3. https://artikelmanager.hairworld24.de/ öffnen → v4.2 → Live-JTL-API prüfen → EAN scannen.
4. Erwartet: 'jtl_status':'current_snapshot' und ein oder mehrere matches. Die Quelle muss höchstens 30 Minuten alt sein. Wenn 0 matches trotz frischem Spiegel, Identifikatoren / SQL-Spalten / Dubletten prüfen.
5. Nach einem erfolgreichen Einmaldurchlauf Windows-Aufgabenplaner für regelmäßige Synchronisation einrichten; Probelauf unter dem dafür vorgesehenen Konto.
6. Eingeschränkte MariaDB-Rechte, Plesk Basic Auth, PHP-Logs, HTTPS und automatisches Backup regelmäßig prüfen.

## K. Nächste Ausbaustufe, nicht Teil der ersten API-Verbindung

Die JTL-Artikelkarte umfasst 13 Reiter. Der Hauptstammspiegel deckt noch nicht alle Informationen ab: verifizierte Hersteller- und Kategorie-Klarnamen, tatsächliche Lagerorte/Reservierungen, Bilddateien, Attribute/Merkmale, kanalabhängige Pflichtfelder. Nach erfolgreichem JTL-Snapshot diese Quelltabellen read-only erweitern. Danach Benutzeroberfläche in kompakte Abschnitte
'Identifikation', 'Kosten und Preise', 'Bestände und Lager', 'Standards', 'Kanäle' umgestalten. 
**Überverkäufe immer N** außer ausdrücklich manuell gewählte Ausnahme.
