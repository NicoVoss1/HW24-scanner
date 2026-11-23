# HW24 Intelligence Hub: Altarchiv finden, echter Mehrbenutzerbetrieb und Admin-Audit

**Datum:** 24.11.2026. **Status:** Diagnose und Rollenarchitektur; erster EAN-unabhängiger, paginierter **Legacy-Actor**-KI-Archivabruf ist implementiert. **Keine** Änderung an MariaDB, JTL, Plesk, PROD/TEST-Webroot, Authentifizierungsrollen, Server-Passwörtern oder Datenbeständen.

## Betreiberbeobachtungen (Screenshot)

- Der gesuchte JTL-Artikel kann korrekt aus einem JTL-Spiegel geliefert werden (HTTP 200). Die gezeigte Amazon-Katalogantwort stammt aus einem **Cache-Snapshot** (HTTP 200, eine Katalogzeile, Archiv-ID 88). Das ist kein Beweis für eine erfolgreiche neue SP-API-Live-Abfrage oder vollständige Altarchivübernahme.
- `HTTP 200 · 0 Berichte` beim KI-Archiv bedeutet: erfolgreiche GET-Anfrage, aber null passende Ergebnisse für **diesen Legacy-Basic-Auth-Benutzer und die ausgewählte EAN**. **Nicht**: „es wurden nie KI-Berichte gespeichert“ oder „alle Berichte wurden gelöscht“.
- Die Filament-Ansicht erlaubte bisher `loadAiArchive()` nur **nach** erfolgreichem aktuellen JTL-EAN-Abgleich. Doch `api/ai-reviews.php` kann technisch bereits **ohne EAN** die 20 letzten Archivberichte des Legacy-Basic-Auth-Akteurs, mit `before_id` ältere, liefern. Es war ein UI-/Scope-Fehler, nicht unbedingt fehlende Datensätze.
- Neues Branch-Verhalten: `loadAiArchiveAll()` macht GET auf dasselbe Legacy-Archiv **ohne EAN**, `loadOlderAiArchive()` blättert mit dem echten `next_before_id` rückwärts; 20 Einträge pro Abruf. Sichtbar: EAN, archivierter Artikelname, Bericht-ID, Modell, Zeitpunkt, Prüfvorschläge, historischer `usd_cost` und gespeicherte Tokenzahlen. **Keine** OpenAI-POST oder Kosten, keinerlei automatischer JTL-Write.
- **Seller-Namen:** `api/amazon-pricing.php` liefert Seller-IDs (keine bestätigten Namen). `api/amazon-sellers.php` liest bestätigte Namen aus `hw24_amazon_seller_names`; ist die Tabelle nicht synchronisiert, ohne Treffer oder API nicht erreichbar, zeigt der HTML-Preisvergleich nun ausdrücklich „Händlername noch nicht verifiziert“ beziehungsweise eine **Seller-Archiv-Störung**, statt eine technische ID als Namen vorzutäuschen. Nie Verkäuferidentitäten aus einer Seller-ID raten.
- `api/ai-review.php` verwendet **separat** privates `hw24_private/hw24-ai-usage.json` für Budgetüberwachung und `hw24_ai_reviews` (MariaDB) für dauerhafte Prüf-/Token-Einzelbelege. Der gespeicherte `usd_cost` ist aus API-Tokens und konfigurierten USD-Tarifen berechnet, keine bestätigte Anbieterrechnung. Den lokalen JSON-Ledger beim Umzug nicht als alleinige Datenbank ansehen.

## Vorhandene Architekturgrenzen (bei Umsetzung zwingend beachten)

1. `platform/app/Models/User.php` gestattet dem isolierten Laravel-/Filament-Pilot **nur die eine ausdrücklich konfigurierte Admin-E-Mail** den Zugang; es gibt **noch keine** Admin-/Editor-/Viewer-Rollen und keine verifizierte Mandanten-/Benutzerzuordnung zwischen Filament-Login und Legacy-Plesk-Basic-Auth.
2. `platform/app/Services/LegacyReadBridge.php` authentifiziert **alle** Legacy-Anfragen derzeit mit **einem serverseitig konfigurierten Legacy-Basic-Konto**. `api/ai-reviews.php` wiederum filtert `actor = PHP_AUTH_USER`. Deshalb werden archivierte Berichte dieses **technischen** Legacy-Kontos gelesen. Das ist **nicht** automatisch die Historie des eingeloggten Filament-Benutzers und definitiv kein Admin-Zugriff auf **alle** Benutzer.
3. `platform/database/migrations/...query_audit_events...` und `QueryAuditRecorder` sind nur **isoliertes, standardmäßig deaktiviertes Metadaten-Auditing** des Filament-Piloten. Sie ersetzen weder die alte MariaDB-Prüfhistorie noch einen lückenlosen produktiven Echtbenutzer-Audit.
4. Amazon-Katalog-`hw24_amazon_catalog_snapshots` ist **EAN/Marketplace**-bezogen (kein per-user Snapshot); altes `hw24_amazon_lookup_log` protokolliert nicht zwangsläufig jeden individuellen Backend-/Admin-Benutzer. Verkäuferidentitäten sind nochmals getrennt; historische **Amazon-Angebotspreise** werden in der Legacy-Pricing-API nicht dauerarchiviert.
5. Alte Excel-Belege kommen aus `api/historie.php` mit separater Quellen-/Dokumenthistorie. Alte Rohdaten nicht automatisch in neue aktuelle Stammdaten zurückschreiben.

## Datenerhalt vor neuer Migration

1. **Kein „Neu aufsetzen“** und kein Restore in die einzige laufende Datenbank. Vorher lesende Inventur `platform/ops/hw24-mariadb-archive-presence-audit-readonly-20261124.sql` auf **MariaDB `hw24_artikelmanager`**, ausdrücklich nicht HeidiSQL `eazybusiness`. Tabellenpräsenz, Anzahl/Akteurszahl, Min/Max-Zeit, Seller-Namen, Screenshot-Snapshot-ID 88 und Scan-Events zählen, keine persönlichen Daten veröffentlichen.
2. Prüfen: aktuelle DB-Host-/vHost-/Benutzerkennung der privaten `hw24_private/db.php` und `scanlist-db.php`; alte Backup-Instanzen, frühere MariaDB-/Plesk-Server, alte Domain-DocumentRoots, archiviertes `hw24-ai-usage.json`; Credentials nicht in GitHub oder Chats übertragen.
3. **Aktuelle DB + private Ledger zuerst sichern und Wiederherstellung in isolierter Instanz testen**. Alte und neue Belege **vergleichend**, nicht überschreibend zusammenführen: Quelle, alte ID, alte Actor-ID, EAN, Zeitstempel, Datensatzhash, Importbatch und Duplikatstatus. Keine Cross-user-Zuordnung durch Namensähnlichkeit.
4. Wenn in derselben MariaDB `all_ki_archive_rows > 0` und für die aktuelle EAN `0`, erst den aktiven Basic-Auth-Akteur vergleichen, nicht kostenpflichtig OpenAI neu starten. Wenn Tabelle nicht existiert oder leer ist, alte Sicherungen/Host-Konfiguration überprüfen.

## Zielbild „ein Hub, mehrere Rechner, mehrere Benutzer“

Die führende operative Quelle kann weiterhin die **bestehende MariaDB** bleiben; JTL-Wawi SQL Server ist ein strikt lesender Quellspiegel. Belege/Bilder/PDFs sind verschlüsselte private Objekte mit Hash, Versions-/Quellenmetadaten in MariaDB. Keine Synchronisierung über Browser-localStorage als führende Wahrheit.

- **Identität:** Jeder Mitarbeiter erhält einen eigenen zentralen Benutzerlogin mit stabiler UUID. Keine geteilte Basic-Auth-Anmeldung als dauerhafte persönliche Identität; 2FA für Administratoren, abgestufte Session-/Gerätefreigabe und Sperr-/Passwort-Reset.
- **Rollen/ACL:** `admin` (Benutzer/Rechte, Archive, Abfrage-/Kosten-/Importaudit, Backup-/Quellstatus); `buyer_editor` (eigene/zugeordnete Ankäufe, Scan, Einkaufspreis-/Belegdaten); `catalog_editor` (Artikelentwürfe, Datenpflege, Hersteller-/Katalogimport) und `viewer` (freigegebene Lesedaten). Sichtbarkeit je Datensatz/Team über serverseitige Policies, **nicht nur** versteckte UI-Buttons.
- **Adminjournal:** append-only `audit_events` mit echtem actor_uuid, Actor-Rolle zum Ereigniszeitpunkt, Aktion, Objekt-ID, Quelle, HTTP-/Resultatstatus, Anfrage-ID, erzeugt/gestartet/beendet in UTC, model/units/USD-Kostenbasis, Freigabe- und Änderungsdiff. Keine API-Tokens/Passwörter oder vollständigen Kundendaten in Logs. Belegbasierte, nachvollziehbare Retention-/DSGVO-Regeln.
- **Archivkopplung:** Käuferkonto/Legacy-Actor-Mapping nur mit bestätigtem Betreiberbesitz; historische `actor`-Werte separat unverändert archivieren. Admin kann alle **zugeordneten** Datensätze lesen, Anwender nur berechtigte. Keine ungeschützte `?admin=1`-Option oder Passwort im Browser, keine globale Filteraufhebung für normale Benutzer.
- **Rückverfolgbarkeit:** AI `usd_cost` und Tokenwerte unterscheiden (i) alter tokenbasierter Kostenvoranschlag/Schätzwert (ii) tatsächlich vom Provider abgerechnete Beträge, nur wenn Beleg vorhanden. Amazon-Aufträge: Anforderer, Queue-Status, Cache/Live-Quelle, verifizierte Snapshot-ID, Quoten-/Fehlerhistorie. JTL-Artikel: Read-only-Snapshot-Zeitpunkt und Rowversion getrennt.
- **Parallelarbeit:** atomare DB-Transaktionen für neue Einkaufscodes, scan UUIDs idempotent, konkurrierende Preis-/Feldänderungen über Version/ETag und Optimistic Lock, Admin-Audit bei jeder Mutation, nie gegenseitig unbemerkt überschreiben.

## Geschützte Umsetzungsgates

**Nicht freischalten**, bevor die Legacy-Bridge pro Filament-Benutzer an eine überprüfte echte Identität gebunden wird. Die derzeitige `canAccessPanel`-Admin-Email-Regel **nicht** durch eine pauschale rollenlose Nutzeröffnung ersetzen.

Nächste technische Schritte: zuerst Datenbestands-/Backup-Audit. Dann nur im isolierten Featurebranch Benutzer-/Rollen-/Policies-Migration für die bestehende zentrale Datenhaltung, Altkontomapping und read-only Admin-Audit; negative Autorisierungstests mit mindestens zwei getrennten Testkonten. Vor TEST-Migration: Backup+Restore-Nachweis, Betreiberfreigabe und CI. Erst nach sicherem Identity Bridge die alte produktive Basic-Auth-Oberfläche ablösen; JTL/TEST/LIVE/Marktplätze bleiben währenddessen unverändert.
