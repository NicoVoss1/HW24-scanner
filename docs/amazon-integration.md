# HW24 Amazon SP-API Katalogintegration (erste Version)

## Sicherheitsmodell
- Private LWA-Daten verbleiben auf dem Plesk-Server in `hw24_private/amazon/amazon_spapi.php`; **nie in GitHub**.
- `api/amazon.php` verweigert Zugriffe, wenn kein durch den Webserver authentifizierter Benutzer erkennbar ist. HTTPS erforderlich.
- Die Website muss weiterhin durch Plesk-HTTP-Authentifizierung geschützt sein. Prüfe den Endpunkt mit und ohne Anmeldung; ohne Anmeldung darf es niemals Katalogdaten geben.
- Schreibzugriff nur in neue MariaDB-Tabellen. Keine Änderungen an JTL, bestehenden Artikeln, Listings oder Amazon-Preisen.
- Die Google-/GitHub-Pages-Vorschau enthält keine private API und stellt nur die Oberfläche dar.

## Einmalige Inbetriebnahme auf Plesk
1. MariaDB-Datenbank `hw24_artikelmanager` **sichern**.
2. Über Plesk/phpMyAdmin `sql/amazon_catalog_archive.sql` einmalig ausführen. Prüfen, dass drei Tabellen existieren.
3. Sicherstellen, dass die existierende `hw24_private/db.php` Datenbankzugang bereitstellt und der Datenbankbenutzer CREATE (nur für Migration), SELECT, INSERT auf die drei neuen Tabellen erhalten hat.
4. Vorhandene private Amazon-Konfiguration `hw24_private/amazon/amazon_spapi.php` beibehalten. Nicht anzeigen, exportieren oder in GitHub speichern. Auf `600` beschränken, privater Ordner `700` nur, wenn PHP mit entsprechendem Benutzer läuft.
5. Von diesem Branch nur `api/amazon.php`, `assets/amazon-catalog.js` und die aktualisierte `index.html` zunächst auf `test-artikelmanager.HAIRWORLD24.de` ausrollen. Der Dateipfad für die private Konfiguration ist relativ zum Webspace-Root; in der Testumgebung denselben Plesk-Webspace verwenden.
6. Kontrollieren, dass `GET /api/amazon.php?ean=4021609029274` **ohne Authentifizierung gesperrt** ist. Mit authentifiziertem Zugriff soll eine JSON-Antwort mit `ok: true`, `source: live` und `snapshot_id` erscheinen. Falls `site_authentication_required` erscheint, prüfen, ob Plesk `REMOTE_USER` oder `PHP_AUTH_USER` an PHP-FPM weitergibt; nicht einfach die Schutzprüfung entfernen.
7. Dieselbe EAN ein zweites Mal abfragen. Erwartet: `source: cache`; die Tabelle `hw24_amazon_lookup_log` enthält für **jede** Abfrage eine zusätzliche Zeile. `hw24_amazon_catalog_snapshots` enthält die vollständige Amazon-Katalog-JSON-Antwort; `hw24_amazon_catalog_items` die einzelnen Artikel.
8. Oberfläche unter der geschützten **Testdomain** öffnen. EAN scannen und Bereich „Amazon-Katalog anzeigen“ prüfen. Danach erst produktiv freigeben. Bestehende geplante Testaufgabe `test_spapi.php` deaktivieren.

## Verhalten
- Abfrage nur Amazon.de (`A1PA6795UKMFR9`), EU-Endpunkt, EAN/GTIN mit korrekter Prüfziffer.
- Mit `includedData`: `attributes, classifications, dimensions, identifiers, images, productTypes, relationships, salesRanks, summaries`. Vollständige *erhaltene* Suchantwort als JSON archiviert, getrennt von der vereinfachten Anzeige.
- Nach 6 Stunden wird eine neue Live-Abfrage vorgenommen; innerhalb dieser Zeit wird aus der Datenbank geliefert. Jede Anforderung erhält einen eigenen Audit-Log-Eintrag. API-Fehler erhalten einen Fehlerlog ohne Geheimnisse.
- Bei mehreren ASIN-Treffern keine automatische Übernahme. Bei nur einem Treffer können **fehlende** Felder bewusst als Entwurf ergänzt werden; keine automatische Datenüberschreibung.
- Ein Amazon-Treffer ist **kein** Beleg für Produktidentität. Besonders `4021609029274` (Goldwell Shampoo/Trockenshampoo) manuell prüfen.
- Die erste Version erfasst die erste Amazon-Ergebnisseite. Bei `has_next_page: true` wird in der Oberfläche auf weitere Ergebnisse hingewiesen. Zusätzliche Seiten und detailreiche `getCatalogItem`-Abfragen folgen.
- **Kein LLM/echtes KI-Modell** ist angebunden. Die eingebauten Hersteller-/Produktartwarnungen sind regelbasierte intelligente Prüflogik. Für wirkliche KI-Unterstützung ist eine separat abgesicherte Modellanbindung erforderlich.
- Aufbewahrungsfristen, Datennutzung und zulässiges Caching vor langfristiger Produktivspeicherung anhand der aktuellen Amazon-Richtlinien prüfen.

## Fehlerdiagnose (ohne Geheimnisse teilen)
- `site_authentication_required`: Webserver-Authentifizierung wird von PHP nicht erkannt.
- `private_config_missing`: Plesk-Konfigurationspfad nicht gefunden.
- `amazon_lookup_or_storage_unavailable`: Serverlog prüfen, Datenbankschema/PHP-cURL/LWA-Berechtigung/Zugriffsrechte.
- `amazon_catalog_request_failed`: Amazon HTTP-Status notieren; bei 429 Rate Limit berücksichtigen.
- `source: cache` beim zweiten Aufruf ist erwartetes Verhalten.
