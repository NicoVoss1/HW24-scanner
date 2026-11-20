# HW24: dauerhafte Scanliste für Artikel-Neuanlage (MVP, 20.11.2026)

## Ziel
Paket öffnen, EAN scannen, Scan sofort in MariaDB speichern, nächsten Artikel scannen. Ein unbekannter Artikel darf ohne ausgefüllte Stammdaten in den Stapel gelangen. Die anschließende Bearbeitung ist **von der Scan-Erfassung entkoppelt**. Erst die geprüften Neuanlagen werden als JTL-Ameise-Prüfdatei exportiert.

Neue Seite: `scanliste.html` (über "📦 Scanliste" im Artikelmanager).
Backend: `api/scanliste.php`; SQL: `sql/HW24_11_SCANLISTE.sql`.

## Implementiert
1. Benannte, dauerhaft gespeicherte Scan-Stapel, pro Benutzer.
2. Erfassung per USB-/Bluetooth-Scanner mit Enter sowie manueller EAN-Eingabe; GTIN-Prüfziffernkontrolle.
3. Persistente Einträge für unbekannte EAN, obwohl JTL nicht erreichbar oder veraltet ist.
4. Jede bewusste Scanner-Aktion hat eine eigene UUID, bei Netzwerk-Wiederholung wird derselbe Scan **nicht doppelt gezählt**.
5. Eindeutige EAN pro Stapel, separater Zähler der Scanvorgänge, Scanereignisse dauerhaft erhalten.
6. Bestandsstatus nur bei **frischem vollständigem JTL-Spiegel**: existing/new_candidate/multiple; sonst unverified. Kein automatischer Wawi-Schreibzugriff.
7. Bearbeitbarer Entwurf für mögliche Neuanlagen, mit manueller Haarfarben-Vorlage. Vorhandene JTL-Artikel werden für Neuanlage nicht freigegeben.
8. Optionale Recherche pro Artikel in vorhandener historischer Excel-MariaDB und Amazon SP-API; nur **eindeutige, einzeln akzeptierbare Vorschläge**. Keine automatische globale Massenauslesung oder Amazon-Limitüberschreitung.
9. Felder revisionsgesichert mit Quelle; zentrale Freigabe und erneuter EAN-/Artikelnummern-Abgleich vor Export.
10. Export der kompletten Scanübersicht als Bericht-CSV und Export nur freigegebener Neuanlagen als **UTF-8-BOM, Semikolon-CSV** mit originalen JTL-Exportüberschriften. Kein Live-Wawi-Import. Maximal 200 Importpositionen pro Stapel.

## Administrator-Installation (noch nicht ausgeführt)
1. Vollständige MariaDB-Sicherung erstellen.
2. `sql/HW24_11_SCANLISTE.sql` **nur** in `hw24_artikelmanager` auf der geschützten Testumgebung ausführen.
3. Dedizierten DB-Benutzer anlegen, der nur diese Rechte besitzt:
   - `hw24_scan_batches`, `hw24_scan_items`: SELECT, INSERT, UPDATE
   - `hw24_scan_events`, `hw24_scan_draft_audit`: SELECT, INSERT
   - `hw24_jtl_sync_meta`, `hw24_jtl_articles`: SELECT
   - Keine allgemeinen DDL-Rechte, kein Zugriff auf JTL-Produktivdatenbank, kein DELETE.
4. Im **privaten Bereich außerhalb des Webroots** eine `hw24_private/scanlist-db.php` anlegen, die ein PHP-Array `['user'=>'…','password'=>'…']` zurückgibt. **Keine Zugangsdaten nach GitHub.** Bestehender Nur-Lese-Zugang in `hw24_private/db.php` bleibt unverändert.
5. HTTPS und authentifizierten Zugriff für die neue Seite und die neue API erzwingen.
6. Erst nach grüner GitHub-CI und manueller Codeprüfung die **Testdomain** auf den geprüften Stand deployen. Aktuelle Produktivdomain/Windows-Synchronisierung nicht ändern.
7. Umfassende Sichtprüfung im iPhone-Browser und mit USB-/Bluetooth-Scanner durchführen.

## Abnahmetests
- Unbekannte gültige EAN mit frischem Spiegel scannen: `new_candidate`, ohne Artikelname gespeichert; nächster Scan sofort möglich.
- Bei fehlendem/stalem JTL-Spiegel: `unverified`, Scan **trotzdem gespeichert**; keine automatische Neuanlagefreigabe.
- Bekannte EAN: `existing`; keine Neuanlage-Exportfreigabe.
- Mehrdeutiger Treffer: `multiple`; kein automatisches Matching.
- Dieselbe EAN mit **neuer** Scanner-UUID scannen: Zähler +1; dieselbe UUID nach Verbindungsabbruch wiederholen: Zähler bleibt gleich.
- Browser neu laden oder anderes Gerät verwenden: Stapel und Datensatz bleiben erhalten.
- Historie/Amazon recherchieren: Mehrfachtreffer bewirken **keine** automatische Datenübernahme. Die SP-API bleibt auf bestehende Berechtigungen, Cache und Rate-Limits begrenzt.
- Hersteller, Artikelname, Artikelnummer, Warengruppe, Steuerklasse prüfen, speichern, erneut öffnen, revisionierte Änderungen nachvollziehen.
- Freigabe verweigern, wenn Artikelnummer oder Pflichtdaten fehlen, der Spiegel veraltet ist oder die Artikelnummer bereits in JTL existiert.
- Vor Export frisch gegen JTL abgleichen. Ameise-CSV auf korrekte Kopfzeilen, UTF-8, Semikolon, Quote-Zeichen prüfen.
- In **JTL-Wawi 2.2.2.0**: Backup, `Artikel > Artikeldaten`, importierbare Felder tatsächlich zuordnen, **Trockenlauf mit 1–3 nichtkritischen Testartikeln**, Warnungen/Fehler korrigieren, erst danach Echtimport.

## Grenzen und nächste Erweiterungen
- **Keine Wareneingangs- oder WMS-Buchung:** Scananzahl ist nicht automatisch Bestand. Für echte physische Einbuchung braucht es einen eigenen geprüften WMS-/Wareneingangsprozess.
- Der Stammdatenimport ist nicht automatisch ein Amazon/eBay/Shopify-Listing.
- Originalspalten des JTL-Exports entsprechen nicht zwingend 1:1 den importierbaren Ameise-Feldern. Eine **gespeicherte, getestete Feldzuordnung** ist für die Serienfreigabe noch erforderlich.
- Bilder, Merkmale, Attribute, eigene Felder und gegebenenfalls Kategorien müssen je nach Importart separat gepflegt werden.
- Eine produktive Sammelanreicherung für Tausende Artikel mit überwachten API-Limits, Fehlerliste und Fortschrittsanzeige ist eine weitere Ausbaustufe, **kein** bereits laufender Hintergrundjob.
- Excel-`.xlsx` ist nicht erforderlich für Ameise: CSV ist das Importformat. Die Übersichts-CSV kann in Excel geöffnet werden; beim Öffnen auf führende Nullen in EAN und Artikelnummer achten.
- Produktidentität einschließlich Hersteller, Farbcode, Packungsgröße, ASIN und erlaubter Verwendung von Amazon-Daten muss vor Import sorgfältig geprüft werden.

Quellen:
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikeldaten-importieren/
- https://guide.jtl-software.com/jtl-wawi/jtl-ameise/aufbau-der-import-funktion/
- `docs/JTL_AMEISE_IMPORTPROFIL.md` (Ausgangs-Export: 872 Spalten)
