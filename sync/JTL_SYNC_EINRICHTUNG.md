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

## Schritt 1: MariaDB vorbereiten

Zuerst in Plesk/phpMyAdmin ein Backup erstellen. Dann im Schema hw24_artikelmanager
den Inhalt der Datei sql/HW24_06_JTL_SPIEGEL.sql ausführen.

Den bestehenden reinen Lesebenutzer hw24_app unverändert lassen.

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

## Schritt 5: Einmaligen Test und spätere Automatik durchführen

Einmalig unter dem vorgesehenen Windows-Konto starten:

    .\HW24_JTL_Synchronisieren.ps1 -ConfigFile C:\HW24\jtl-sync.json

Vorher vollständige Datenbanksicherungen anfertigen und Verbindung prüfen.
Testen: 2689 bzw. die aktuelle vollständige Artikelanzahl übertragen,
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
