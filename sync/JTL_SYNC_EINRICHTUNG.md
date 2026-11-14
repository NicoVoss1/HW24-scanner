# HW24 · sichere JTL-Wawi-Synchronisation

Status: vorbereitetes Paket, NOCH NICHT installiert oder auf dem Windows-Server getestet.
Quelle: eazybusiness (Microsoft SQL Server 2017, Windows-Rechner).
Ziel: hw24_artikelmanager (MariaDB auf artikelmanager.hairworld24.de).

## Grundprinzip

- Der Windows-Rechner darf JTL ausschließlich LESEN.
- Er sendet in Paketen per HTTPS an api/jtl-sync.php auf Plesk.
- Der JTL-Port 1433 bleibt von außerhalb des lokalen Netzes gesperrt.
- Nur vollständige, auf Zeilenanzahl geprüfte Snapshots werden veröffentlicht.
- Drei erfolgreiche Snapshots bleiben verfügbar. Die alte Excel-Historie bleibt erhalten.
- Ein vollständiges Änderungs-Audit fehlt noch und muss separat umgesetzt werden.
- Ameise führt spätere Neuanlagen/Aktualisierungen durch; keine direkte Wawi-Schreibanbindung.

## Sofortstart: Erst prüfen, dann verbinden (ohne Datenänderung)

1. In Plesk unter artikelmanager.HAIRWORLD24.de > Git zuerst **Jetzt Pull ausführen**
   und anschließend **Jetzt bereitstellen**. Die neuen API-Dateien müssen
   wirklich im Serververzeichnis /api liegen. GitHub Pages ist hierfür nicht geeignet.
2. Im Browser die folgende Adresse öffnen und sich ggf. beim bestehenden
   Website-Passwortschutz anmelden:
   https://artikelmanager.hairworld24.de/api/jtl-status.php
3. Die angezeigte JSON-Antwort enthält keine Kennwörter, Tokens oder privaten
   Dateipfade und kann zur Diagnose weitergegeben werden.
4. Bedeutung des Feldes `next`:

   - `upgrade_php`: PHP auf 8.4 oder neuer einstellen (Plesk verwendet bereits 8.5.12).
   - `enable_pdo_mysql`: MySQL-PDO-Erweiterung einschalten.
   - `configure_read_api`: private Datei hw24_private/db.php außerhalb der
     Website prüfen, vorhandenen Lesebenutzer hw24_app unverändert lassen.
   - `install_mirror_tables`: nach Backup SQL-Datei HW24_06_JTL_SPIEGEL.sql ausführen.
   - `check_mirror_schema`: vorhandene Tabellen/Spalten im phpMyAdmin
     kontrollieren, keine Tabelle blind überschreiben.
   - `fix_mariadb_access`: MariaDB-Berechtigungen bzw. Verbindungsparameter prüfen.
   - `configure_and_run_windows_sync`: MariaDB-Spiegel bereit,
     synchronisierten Windows-Dienst noch nicht gestartet.
   - `rerun_windows_sync`: letzter vollständiger Spiegel älter als 30 Minuten.
   - `query_known_ean`: aktueller Spiegel verfügbar, bekannte Kennung testen.

Diese Diagnose macht **keine Änderungen** an JTL oder MariaDB.
Der neue Windows-Test `sync/HW24_JTL_Verbindungstest.ps1` ist ebenfalls rein lesend.
Der Schreibempfänger `api/jtl-sync.php` unterstützt zusätzlich
`{"action":"probe"}` mit echtem Token, aber ebenfalls ohne Änderung an Datensätzen.

## Schritt 1: MariaDB vorbereiten

Zuerst in Plesk/phpMyAdmin ein vollständiges Backup erstellen.
Danach in der ausgewählten Datenbank hw24_artikelmanager die Skripte in dieser
Reihenfolge verwenden:

1. sql/HW24_08_MARIADB_STATUS_PRUEFEN_LESEND.sql (nur prüfen).
2. sql/HW24_06_JTL_SPIEGEL.sql (JTL-Artikelsnapshot und Status).
3. sql/HW24_07_QUELLEN_ENTWUERFE_KATEGORIEN_AUDIT.sql (Quellen, Drafts,
   Kategorie-Mapping, Log-Tabellen und Standardprofil).
4. Schritt 1 erneut prüfen. CREATE TABLE IF NOT EXISTS aktualisiert keine
   bereits vorhandene alte Spaltenstruktur. Bei abweichenden bestehenden
   JTL-Mirror-Tabellen nicht blind ALTER/DROP verwenden.

Den bestehenden reinen Lesebenutzer hw24_app unverändert lassen.
Die neuen Tabellen allein aktivieren noch kein serverseitiges Schreiben oder Audit.

Über einen MariaDB-Administrator einen NEUEN Benutzer hw24_sync anlegen,
der nur SELECT/INSERT/UPDATE/DELETE für diese beiden Tabellen bekommt:

    hw24_artikelmanager.hw24_jtl_articles
    hw24_artikelmanager.hw24_jtl_sync_meta

Keine globalen Privilegien, keine Rechte auf historische Excel-Tabellen,
keine ALTER/DROP-Privilegien, keine Remote-Verbindungen.

## Schritt 2: Private Plesk-Konfiguration

Außerhalb des öffentlich erreichbaren Website-Verzeichnisses:

    /var/www/vhosts/hairworld24.de/hw24_private/jtl_sync.php

Inhalt:

    <?php
    return [
      'user'=>'hw24_sync',
      'password'=>'INDIVIDUELLES_GEHEIMES_PASSWORT',
      'token_sha256'=>'SHA256_HASH_DES_ZUFAELLIGEN_API_TOKENS',
      'min_rows'=>2500
    ];

Token zufällig, mindestens 32 Zeichen lang erzeugen.
Nur den SHA-256-Hash ins PHP-Konfigurationsfile schreiben.
Das echte Token ausschließlich verschlüsselt auf dem Windows-Server speichern.
Die bereits eingerichtete Website-Passwortabfrage bleibt zusätzlich aktiv.
Niemals Zugangsdaten oder Tokens in ChatGPT oder GitHub einfügen.

## Schritt 3: SQL Server nur lesend berechtigen

Mit SQL-Server-Administratorberechtigung auf dem Windows-Host, Beispiel:

    USE [master];
    CREATE LOGIN [hw24_reader] WITH PASSWORD='SEHR_STARKES_INDVIDUELLES_PASSWORT';
    USE [eazybusiness];
    CREATE USER [hw24_reader] FOR LOGIN [hw24_reader];
    GRANT SELECT ON OBJECT::dbo.tArtikel TO [hw24_reader];
    GRANT SELECT ON OBJECT::dbo.tArtikelBeschreibung TO [hw24_reader];

NICHT den bestehenden sa-Zugang verwenden.

## Schritt 4: Windows-Konfiguration

Datei sync/HW24_JTL_Synchronisieren.ps1 auf dem Windows-Rechner bereitstellen.
Eine lokale Konfiguration, z.B. C:\HW24\jtl-sync.json, außerhalb von Git anlegen:

    {
      "endpoint": "https://artikelmanager.hairworld24.de/api/jtl-sync.php",
      "sql_server": "192.168.178.28,1433",
      "sql_credential_file": "C:\\HW24\\secrets\\jtl_reader.xml",
      "http_credential_file": "C:\\HW24\\secrets\\plesk_http.xml",
      "api_token_file": "C:\\HW24\\secrets\\api_token.dpapi",
      "batch_size": 15,
      "min_rows": 2500
    }

Die SQL-Credentials und Website-Basic-Auth-Credentials per PowerShell
Get-Credential und Export-Clixml DPAPI-verschlüsselt abspeichern.
Das API-Token mit ConvertFrom-SecureString DPAPI-geschützt ablegen.
Der Windows-Aufgabenplaner muss exakt unter demselben Windows-Konto laufen.
Achtung: Der SQL-Server benötigt ein gültiges, vertrauenswürdiges TLS-Zertifikat.
Das Skript verwendet Encrypt=true und TrustServerCertificate=false.

## Vor dem ersten vollständigen Sync: Verbindungstest

Die Windows-Konfiguration und die DPAPI-Dateien müssen lokal auf dem
Windows-Rechner gespeichert sein. Ohne Login- oder Tokenweitergabe an ChatGPT:

    .\HW24_JTL_Verbindungstest.ps1 -ConfigFile C:\HW24\jtl-sync.json

Das Skript liest SQL Server 2017 (SELECT COUNT_BIG und Testkennungen)
und fragt die Plesk-Status-API ab. Es überträgt **keine** Artikel.
Die SQL-Verbindung verwendet Encrypt=true und TrustServerCertificate=false:
bei Zertifikatsfehlern nicht einfach die Prüfung abschalten; stattdessen
SQL-Server-Zertifikat und Vertrauenskette korrekt einrichten.

## Schritt 5: Einmaligen Test und spätere Automatik durchführen

Einmalig unter dem vorgesehenen Windows-Konto starten:

    .\HW24_JTL_Synchronisieren.ps1 -ConfigFile C:\HW24\jtl-sync.json

Vorher vollständige Datenbanksicherungen anfertigen und Verbindung prüfen.
Testen: die zur Laufzeit aktuelle vollständige Artikelanzahl übertragen,
eine bekannte EAN im Artikelmanager finden, Dubletten erkennen und einen
abgebrochenen Lauf simulieren. Ein abgebrochener Lauf darf den letzten
vollständigen Snapshot nicht überschreiben.

Erst nach erfolgreichem Test im Windows-Aufgabenplaner eine wiederholte
Ausführung einrichten (als Ziel etwa alle fünf Minuten).

## Fachlich noch offen

- Herstellernamen und interne Kategorien über die echten JTL-Fremdtabellen auflösen.
- Reale JTL-WMS-Lagerorte aus zusätzlichen Tabellen/Schnittstellen anbinden.
- JTL-Steuerklassen und Netto-/Brutto-Preise sauber abgleichen.
- Separates vollständiges Änderungs- und Import-Audit einrichten.
- JTL-Ameise-Importprofile mit wenigen Artikeln validieren.
- Amazon- und eBay-Pflichtattribute über die echten Markt-APIs prüfen.

Das Paket ist kein direkter JTL-Import. Keine Produktionsautomatik aktivieren,
bevor Rechte, TLS, Datenqualität, Testläufe und Backups geprüft wurden.


## Datenqualität und Importregeln

Das aktuelle Originalexport-Schema 14.11.2026 mit 872 Spalten und 2691 Artikeln
wurde geprüft. Siehe docs/JTL_FELDKATALOG_STANDARDS_20261114.md.
Kosten ist vom durchschnittlichen GLD-EK getrennt; Auf Lager / In Aufträgen /
Verfügbar sind verschiedene Anzeige- und Prüfdaten, keine Ameise-Bestandsbuchung.
Alle Preisfelder werden vor Ameise im Export mit dem definierten Komma-Dezimalformat
kontrolliert. Bestehende Artikelnummern bleiben erhalten. Für echte Neuanlagen
muss die JTL-Ameise-Nummernvergabe vorher als Test validiert werden.
