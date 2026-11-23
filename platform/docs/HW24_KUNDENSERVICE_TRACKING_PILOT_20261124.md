# HW24 KI-Kundenservice · Track-&-Trace Pilot (24.11.2026)

STATUS: Quellcode im isolierten GitHub-Draft, noch nicht deployed. Keine produktiven Datenbanken oder JTL-/Amazon-/Shopify-Anbindungen verändert.

## Bestätigter JTL-Datenweg

- Versand.lvJtlTrackingPakete liefert Amazon-Bestellnummer, JTL-Auftrag, Lieferschein, kVersand, cTrackingID und Versandart.
- Shipping.tPackage verbindet kVersand/cTrackingId mit kPackage.
- Shipping.tState enthält dDate, kState, cText, cProviderState, cLocation und cCountry. Abholadressen stehen teils ausschließlich im cText-Freitext, obwohl cLocation leer ist.
- Shipping.tMessage speichert ergänzende technische Paketmeldungen, nicht unbedingt DHL-Scans.
- Die Zeitzone von Shipping.tState.dDate ist nicht formell bestätigt. Beim Test lag die JTL-Zeit um eine Stunde hinter der DHL-Webansicht. Der Pilot zeigt Rohzeiten und nennt in Vorlagen nur das Datum.

## Implementierte Pilotkomponenten

1. platform/ops/jtl-heidisql-phase18-tracking-export-readonly.sql: SQL-Server-SELECT der verifizierten JTL-Relation, ohne Kundenstammdaten, max. 50.000 Zeilen und auf 45 Tage eingeschränkt. Nur Vorab-/Testexport, kein vollständiger produktiver Delta-Sync.
2. platform/ops/jtl-tracking-csv-stage.php: Validierung einer privaten UTF-8-CSV (Semikolon, Header, CSV-Quoting). Nur nach bestätigter SHA256 und Zeilenzahl wird eine NEUE isolierte SQLite-Datei angelegt. Kein Überschreiben.
3. platform/app/Services/TrackingSnapshotReader.php: Bestellnummernvalidierung, private isolierte read-only Snapshotabfrage und Warnung bei veralteten Ergebnissen.
4. platform/app/Filament/Pages/CustomerServiceWorkbench.php: authentifizierte neue Filament-5-Seite /admin/kundenservice mit Sendungshistorie und manuell kopierbarer Vorlage.
5. Screenshot-Upload/Paste-Feld und optionaler KI-Entwurf über platform/app/Services/CustomerSupportDraftAi.php. Nur nach Betreiber-Featureflag, privatem API-Key und bewusstem Klick inklusive Einwilligung. Keine automatischen KI-Aufrufe, keine Amazon-Nachrichten.
6. Tests: platform/tests/Feature/CustomerServiceTrackingTest.php und platform/scripts/verify-tracking-stage.php sowie CI-Lint und synthetischer Import-Smoke.

## Sichere Test-Einrichtung

1. Windows-SSMS/HeidiSQL: den SQL-Export in eazybusiness ausführen und als geschützte UTF-8-CSV (Semikolon, Header, korrektes CSV-Quoting) speichern. Die CSV enthält Kundenauftrags- und Trackingnummern, daher niemals in Git, E-Mail oder öffentlichen Webroot übertragen.
2. Auf dem privaten Laravel-Server unter platform/storage/app/private einen Unterordner tracking mit Modus 0700 anlegen. Die CSV außerhalb der öffentlichen DocumentRoot bereitstellen.
3. Mit PHP-CLI im Ordner platform dry-run ausführen:

    php ops/jtl-tracking-csv-stage.php --input=/privat/jtl-tracking.csv

4. Die ermittelten Werte rows und sha256 kontrollieren; anschließend in eine NEUE private SQLite-Datei importieren:

    php ops/jtl-tracking-csv-stage.php --input=/privat/jtl-tracking.csv --db=/privat/laravel/storage/app/private/tracking/tracking-20261124.sqlite --expected-count=ANZAHL --sha256=SHA256 --apply

5. Ausschließlich in der privaten Laravel-.env konfigurieren:

    HW24_SUPPORT_TRACKING_SQLITE=/privat/laravel/storage/app/private/tracking/tracking-20261124.sqlite
    HW24_SUPPORT_SNAPSHOT_MAX_AGE_HOURS=12
    HW24_SUPPORT_AI_ENABLED=false

6. Erst nach gesonderter Deployment-Freigabe auf der isolierten Testplattform unter /admin/kundenservice testen. Keine Livesystem- oder Domain-Umschaltung im Rahmen dieses Drafts.

## Nicht erledigt / zwingende Gates

- Noch kein automatisch laufender JTL→MariaDB-Datenspiegel. Der Snapshot wird manuell und getrennt erstellt. Für den Produktivweg fehlen paginierte Delta-Synchronisation, Vollständigkeits-/Rowcountprüfung, Scheduler, Zeitvalidierung und gesicherte Retention.
- Noch keine automatische Amazon-Kundeninbox. SP-API-Messaging hat keine allgemeine Inbox-GET-Schnittstelle. Die manuell bestätigte Amazon-Bestellnummer ist bislang erforderlich, der Screenshot kann optional zur KI-Auswertung genutzt werden.
- Kein Amazon-SP-API-Versand: Für ausgehende Nachrichten muss die je Bestellung zulässige Messaging-Aktion geprüft und die manuelle Freigabe umgesetzt werden. Aktuell nur Text kopieren und in Seller Central senden.
- Keine Amazon-Erstattung, keine JTL-/WMS-Buchung und kein automatischer E-Mail-Versand.
- Live-DHL-Status wird nicht behauptet. Abholort nur nennen, wenn im belegten DHL-Ereignis enthalten und Statuszeitpunkt transparent bleibt.
- Vor externem KI-Einsatz Vertragsgrundlagen/DSGVO, Aufbewahrung/Löschung, Berechtigungen und ggf. Amazon-Nutzungsbedingungen kontrollieren.

## Abnahme

Mindestens Mehrpaketbestellung, leere Statusliste, Storno/Retoure, Filiale mit fehlendem cLocation, Alt-Snapshot, UTC/DST, doppelter Import, fehlender privater DB-Pfad und Auth-/Datenfreigabe testen. Keine bestehenden APIs ersetzen. Erst nach CI und Betreiberprüfung über einen separaten Releasepfad ausrollen.
