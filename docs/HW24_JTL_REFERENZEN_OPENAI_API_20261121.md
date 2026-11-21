# HW24 V4.22: JTL-Referenzen, ASIN-Übernahme und OpenAI-Kostenkontrolle

**Stand:** 21.11.2026. Entwicklungszweig `feature/hw24-v422-jtl-reference-ai-ledger-20261121`. Vor einer Live-Umstellung zuerst Test-Plesk-Bereitstellung und echte End-to-End-Prüfung. Die frühere Sicherung, private DB-Konfiguration und V11/V12-Tabellen bestehen fort und müssen nicht neu aufgebaut werden.

## Tatsächlich vorhandener Datenbankzugriff

- `api/jtl.php` liest **MariaDB `hw24_artikelmanager`**, zuletzt vollständig mit dem Windows-JTL-SQL-Server-Synchronisator gefüllte Snapshot-Tabellen `hw24_jtl_articles`, `hw24_jtl_sync_meta`. Er liest **nicht unmittelbar** die gesamte `eazybusiness`-SQL-Server-Datenbank.
- Der JTL-Artikelspiegel liefert bereits Artikelnummer, EAN, HAN, Marke, Produktname, Preisfelder und `jtl_source_fields` aus `raw_json`, z. B. `kWarengruppe`, `kLieferStatus`, `kSteuerklasse`, `nBearbeitungszeit`, `nLiefertageWennAusverkauft`, `nAutomatischeLiefertageberechnung`.
- `HW24_RelatedSources` bzw. `HW24_WarengruppeName` enthalten optional nach der Windows-Synchronisation **bereits überprüfte** Klartexte und zugehörige Quellen. Fehlende JTL-Fremdschlüssel-Zuordnungen dürfen **nicht** erfunden werden.
- **Neuer Read-only-Endpunkt:** `api/jtl-options.php`, ausschließlich HTTPS/angemeldeter Nutzer, liest beobachtete Hersteller-/Warengruppen-/Lieferstatus-/Kategorie- und Zeitwerte aus dem letzten vollständigen, höchstens 45 Minuten alten Snapshot. Der Endpunkt meldet ehrlich `scope=observed_values_only`. Er stellt **noch keinen vollständigen Stammtabellen-Katalog** aller Hersteller, Kategorien und Lieferstatus dar.
- Falls eine JTL-Referenzkategorie oder Kategorieebene nicht im Snapshot enthalten ist, muss der bestehende Windows-Synchronisator künftig mit autorisierten **read-only SELECTs** auf die tatsächlich vorhandenen SQL-Server-Referenztabellen erweitert werden. **Keine pauschal neue CSV-Exportanforderung, keine bestehende Wawi-Schreibberechtigung.**

## ASIN-Fehlerbehebung

- Vorher ließ `window.HW24ScanBridge.apply` nur leere Felder befüllen; eine bereits vorgeschlagene ASIN wurde nach bewusster anderer Wahl **blockiert**. 
- Neue Option `confirmedChoice:true` erlaubt die **explizit bestätigte** ASIN in der **Scanlisten-MariaDB** zu speichern, auch wenn ein vorheriger Vorschlag gesetzt war; Anzeige eines Erfolgs **nur nach bestätigtem Server-Write samt Revision**.
- Erneut gewählte, bereits hinterlegte ASIN gilt als vorhanden. ASINs aus Amazon bleiben weiterhin auf Farbe/Marke/Einzel- oder Mehrfachpackungsmenge zu prüfen.
- Im Haupt-Artikelmanager aktualisiert der ASIN-Auswahlklick außerdem das wirkliche Artikelfeld und die Input-Change-Handler. Bei anderer bereits vorhandener ASIN verlangt die UI eine zusätzliche Bestätigung. Diese Änderung ist ein **lokaler/gesondert zu speichernder HW24-Entwurf**, kein direktes SQL-Server-UPDATE.

## Neue Auswahlfelder

- **Scanliste:** Für neue Artikel sind Hersteller, Warengruppe, Lieferstatus, Kategorie 1–3 und bestehende Zeit-/Standardfelder als Auswahl ausgeführt. Dynamische Optionen aus `api/jtl-options.php` werden zu vorhandenen Werten ergänzt, ohne eine eingegebene Auswahl zu verlieren. Alternativ kann ein nicht gelisteter Wert als **ungeprüfter manueller Vorschlag** ergänzt werden.
- Für **bereits vorhandene** JTL-Artikel sind die Referenz-Dropdowns jetzt als **Vergleichsansicht** bedienbar. Die Entwurfs-Speicher-/Neuanlagebuttons bleiben dafür gesperrt. Das verhindert eine versteckte Änderung von JTL-Stammdaten oder deren abhängigen Wawi-Funktionen.
- Neue Aktion **„Zur Artikelpflege öffnen“** verlinkt einen bestehenden Treffer über `index.html?ean=<GTIN>` auf die Hauptmaske. Diese lädt denselben JTL-Artikel und zeigt seine Stammdaten.
- **Hauptmaske/JTL-Profil:** Die gleichen beobachteten JTL-Referenzwerte werden in Hersteller-, Warengruppen-, Lieferstatus- und Kategorie-Auswahlelemente integriert; bisherige Benutzeränderungen werden nicht überschrieben.

## OpenAI API (optional, Projekt HW24)

- **Neu:** `api/ai-review.php` (serverseitige OpenAI Responses API, ausschließlich explizit bestätigte POSTs) und `assets/hw24-ai-cost.js` (oben rechts auf Scanliste und Hauptseite).
- **Standard: Deaktiviert.** Es gibt **keinen** im GitHub-Branch hinterlegten OpenAI-Schlüssel und **keinen** automatischen kostenpflichtigen Call bei Scan oder Laden. Ohne vertrauliche Serverkonfiguration meldet das Widget korrekt „KI nicht aktiviert“ und keine belegten KI-Kosten.
- Für eine echte Anfrage sendet der Nutzer nach Bestätigung nur Artikelkennung/-titel, Hersteller, Warengruppe/Kategorie/Einheit sowie max. 8 Amazon-ASIN-Kandidaten (Titel), **keine SQL-Server-Zugangsdaten**. Die Antwort ist eine **Prüfempfehlung**, sie schreibt keine Daten in JTL/WMS/Marktplätze.
- **Kostenanzeige:** Aus von OpenAI rückgemeldeten `usage.input_tokens`, `usage.input_tokens_details.cached_tokens`, `usage.output_tokens` wird ein USD-Kostenwert anhand der **im privaten Serverprofil festgeschriebenen Modellpreise** berechnet. Monatssumme, Aufrufanzahl und letzter Aufruf sind per GET aus einer privaten Ledger-Datei abrufbar. Das ist eine **tokenbasierte HW24-Kostenschätzung, keine verbindliche OpenAI-Rechnung**. Andere OpenAI-Projekte/Keys sind in dieser HW24-Anzeige nicht enthalten.
- **Gesperrt:** Kein gültiger authentifizierter Plesk-User, falscher Origin, kein HTTPS, keine ausdrückliche Bestätigung, kein privater API-Key, Erreichen des Monatspreisbudgets oder Tagesaufruflimits. Die Ledger-Datei wird mit restriktivem Umask außerhalb des Webroots geführt, der HTTP-Aufruf während des Locks serialisiert.
- **Modell-Vorschlag:** `gpt-5.4-mini`; Stand der offiziellen Modellseite am 21.11.2026: **$0,75/1 Mio. Input-Tokens, $0,075/1 Mio. gecachte Input-Tokens, $4,50/1 Mio. Output-Tokens**. Preise ändern sich; vor Aktivierung kontrollieren: https://developers.openai.com/api/docs/models/gpt-5.4-mini
- **Setup ausschließlich auf dem Plesk-Testserver**: Eine neue private Datei `/var/www/vhosts/hairworld24.de/hw24_private/openai-review.php` im bereits vorhandenen geschützten Ordner anlegen, im selben Eigentümer-/Berechtigungskontext wie die funktionierende private PHP-Konfiguration. Beispielinhalt, **niemals mit echtem Schlüssel im GitHub speichern**:

```php
<?php
return [
  'enabled' => true,
  'api_key' => 'API_KEY_NUR_HIER_AUF_DEM_SERVER_EINTRAGEN',
  'model' => 'gpt-5.4-mini',
  'input_per_million_usd' => 0.75,
  'cached_input_per_million_usd' => 0.075,
  'output_per_million_usd' => 4.50,
  'max_monthly_usd' => 5.00,
  'max_daily_requests' => 25
];
```

  Die Beispielzeichenfolge ist **kein gültiger API-Schlüssel**. Schlüssel nur auf dem Server über Plesk oder PuTTY eintragen, niemals im Chat, in GitHub oder in Browser-JavaScript. Das Serververzeichnis muss die private Ledger-Datei `hw24-ai-usage.json` für den PHP-Website-Benutzer **schreibbar** halten; sonst verweigert die API Anfragen anstatt Kosten ohne Protokoll zu verursachen. Zusätzlich im OpenAI-Platform-Projekt eigene **Budgetbenachrichtigungen/Abrechnungslimits** konfigurieren; die interne HW24-Schranke ersetzt den Anbieter-Abrechnungsstand nicht. Für andere Projekte getrennte API-Keys, Projekte, Budgets und Ledger vorsehen.

## Freigabegate vor Live

1. Aktuellen Entwicklungszweig durch CI prüfen, dann Website-only-Release nach `deploy/staging` übernehmen. Nur **Plesk-Testdomain** pullen und bereitstellen, nicht Live-`main`.
2. JTL-Spiegelstatus prüfen; bei gültigem Snapshot neue Dropdowns in der Scanliste und den bestehenden Artikeln öffnen, Test-Neuanlage speichern, anschließend Seite neu laden. Eine fehlende Kategorie muss als nicht vorhanden/ungeprüft behandelt werden.
3. Amazon-Treffer mit mehreren ASINs wählen und bestätigte ASIN auf Datenbank-Persistenz nach Reload prüfen; Hauptmaske direkt öffnen und vergleichen.
4. KI-Kostenanzeige muss ohne API-Key **deaktiviert** sein. Nach Einrichten der privaten API-Konfiguration **eine** kostenpflichtige Testprüfung durchführen, den Verbrauch pro Aufruf und Monat kontrollieren. Kein automatischer Batchjob.
5. Erst nach erfolgreich dokumentierten Tests die Live-Domain `artikelmanager.hairworld24.de` auf getrenntem `deploy/live`-Release mit festgehaltenem Rückrollstand aktualisieren. Der Live-Plesk-Screenshot vom 21.11.2026 zeigt aktuell `main`; **keinen ungeprüften Branch-Wechsel oder Klick auf Live-Bereitstellen**.
