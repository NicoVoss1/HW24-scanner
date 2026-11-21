# HW24 V4.22 · Amazon-ASIN-Prüfung, KI-Vorbereitung und Live-Rollout

**Stand 21.11.2026.** [PR #23](https://github.com/NicoVoss1/HW24-scanner/pull/23) wurde in den bisherigen Testbranch `deploy/staging` gemerged, SHA **`753abbbe4c9e25d99ec1f821d7e058271fbc26d0`**. Änderung ausschließlich an **vier öffentlichen Browser-JS-Dateien**, nicht an Live, SQL oder Credentials. GitHub-Code-Tests erfolgreich: [Run #44910579185](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44910579185).

## Gewünschte Funktion: ASIN-Treffer sicher vergleichen

- In der Scanlisten-ASIN-Auswahl, im Haupt-Artikelmanager und im Amazon-Katalog ist eine **Amazon.de-Direktverknüpfung je gültiger ASIN** ergänzt, Format `https://www.amazon.de/dp/<ASIN>`, mit abgesichertem neuem Tab.
- Amazon-Katalogtext und Attribute werden auf **ausdrücklich bestätigte 1 Stück, 2er- und 3er-Packungen**, `n × ml`, widersprüchliche Stückzahlen, Marke, Farbcode und Gebindegröße geprüft. **Fehlt eine Angabe zur Stückzahl, ist die Menge unbekannt, nicht automatisch 1 Stück.**
- Ein ASIN-Feld wird nur bei genau einem EAN-bestätigten, als Einzelstück ausdrücklich belegten und nicht widersprechenden Treffer automatisch vorgeschlagen; Sets/Mehrfach-ASINs benötigen eine ausdrückliche Auswahl. Jede Auswahl kann vorher per externem Amazon-Link geprüft werden.
- Beispiel **Wella Color Touch 9/01**: Ein 2er-Angebot mit `2 × 60 ml` erhält eine klare Abweichungswarnung, wenn die JTL-Grundposition ein Einzelartikel ist. Varianten mit anderem Farbcode (etwa 9/03) werden als nicht identisch markiert. **Die tatsächliche erste ASIN konnte mangels ASIN-Wert oder konkretem Amazon-Listing noch nicht real geprüft werden.**
- Intelligente **regelbasierte Vorabprüfung**, keine vorgespielte Sprachmodell-KI: Haarfarbe/Haarstyling/Haarpflege werden anhand möglichst eindeutiger Produktklassensignale geprüft; bei Dusicolor/Creation Mix und abweichender JTL-Warengruppe Hair Styling bleibt der JTL-Wert erhalten, aber ein sichtbarer Konflikthinweis erscheint. Unbekannte/verwaschene Artikelbezeichnungen lösen keine erzwungene Kategorie aus.

## Was für echte KI im gesamten Artikelmanager noch erforderlich ist

1. **Verbindliche Stammdaten-Kataloge**: Aktueller JTL-Ameise-Artikelstammexport mit Original-Kopfzeilen; lesbare JTL-Zuordnungstabellen der Warengruppen, Kategorien 1–3, Hersteller, Lieferstatus und Steuersätze. Mindestens 30–50 bereits korrekt klassifizierte Haarfarben-Artikel plus Beispiele für Shampoo, Styling, Kartons, Sets und Varianten als Referenz. Diese Daten erst anonymisiert bzw. autorisiert und nur in der geschützten Umgebung verwenden.
2. **Produktidentität pro Einheit**: Name, GTIN, HAN falls echt vorhanden, Grund-Artikelnummer aus JTL, Marke, Farbcode/Schattierung, Gebindemenge, Packungsanzahl, Artikeltyp, Sprache/Marktplatz DE. **Keine automatische Umstellung von Einzelartikel zu Mehrfachpackung**.
3. **Modellanbindung**: Welcher AI-Provider / welches Modell, maximaler monatlicher KI- und SP-API-Budgetrahmen, welche Artikel bevorzugt geprüft werden, interne Freigabe zur Verarbeitung geschäftlicher Produktdaten. API-Schlüssel ausschließlich serverseitig in `hw24_private/` oder einem Secrets-Store, **niemals im öffentlichen GitHub, JS oder Chat**.
4. **Freigaberegeln**: Welche Felder darf die KI nur empfehlen, welche dürfen nach deterministischer Prüfung automatisch den HW24-Entwurf ergänzen, und welche dürfen erst nach Bestätigung in die JTL-Ameise-Prüfdatei? Empfohlen: Artikelidentität, ASIN-Multipacks, Warengruppe, USt und EK/VK bei Abweichung immer prüfen; tatsächliche Bestände und EK niemals erfinden.
5. **Validierungsverfahren**: Jede Antwort in strukturierten Feldern mit Quelltext, Stand, Prüfergebnis (`passt`, `prüfen`, `abweichend`), Begründung, gewünschtem Ersatzwert und manueller Freigabe. Bestätigte/abgelehnte Vorschläge als Trainings-/Regel-Feedback, zuerst ohne automatische JTL-/WMS-Schreibrechte.

Ohne zusätzliche private KI-Anmeldedaten kann das heutige Projekt die deterministische Prüfung anwenden, aber **keine tatsächliche LLM-Auswertung behaupten**. OpenAI-/andere AI-Schnittstellen sind ein gesonderter Backend-Schritt mit Kosten- und Berechtigungsprüfung. Optional später zentraler nachts gedrosselter Queue-Worker; die aktuelle Browserqueue ist kein autonomer Nachtjob.

## Test → Live ohne Rückschritt

**Test-Domain (bereits eingerichtet):** `test-artikelmanager.hairworld24.de`, GitHub/Plesk Branch `deploy/staging`. **Live-Domain (historische Projektangabe, aktuell nicht serverseitig verifiziert):** `artikelmanager.hairworld24.de`. Der aktuelle GitHub-Code ist noch **kein** bestätigter Live-Stand.

1. Plesk `deploy/staging` auf Commit `753abbbe4c9e25d99ec1f821d7e058271fbc26d0` aktualisieren. Browser: Wella-9/01-ASIN-Karte mit Amazon-Link, Farbcode-/Packwarnung; zweite EAN/2er-Pack; Warengruppenwarnung und vorhandene JTL-Daten testen. **Zuerst die bereits fertigen Funktionen PR #20–#22 erhalten.**
2. **Vor Live-Änderungen** in Plesk bei `artikelmanager.hairworld24.de` die aktuell eingestellte Git-Repository-URL, Branch, Zielverzeichnis und den aktiven Versionsstand **lesen und per Screenshot festhalten**. Prüfen, ob Live bereits eigene unversionierte Änderungen enthält. Separate vorhandene **private** Konfigurationsdateien und authentifizierte API-Pfade prüfen. Test-Datenbank kann mit gleicher MariaDB-Datenbank verbunden sein; diesen gemeinsamen Zustand und die Buchungsgrenzen vorher identifizieren. **Nichts überschreiben, bevor Vergleich und Rollback geklärt sind.**
3. Erst wenn Testabnahme/Live-Vergleich erfolgreich sind, eine klar benannte **`deploy/live`-Branch/Version** aus dem geprüften Commit ableiten. In Plesk gezielt die Live-Domain mit dem dedizierten Zielverzeichnis auf diese Releaseversion setzen, **nicht** die Testseiten-Git-Bereitstellung auf Live umbiegen. Statische Dateien/APIs nur nach Baseline-Diff übertragen; Private-Dateien bleiben auf dem Server.
4. Vor Freischaltung mindestens die bestehenden Funktionen Artikelprüfung, JTL-Lesestatus, Historie, Amazon-Cache, Scanliste, CSV-Import, JTL-Ameise-Probeexport, Authentifizierung und Browserdatenhaltung mit nichtkritischen Testartikeln prüfen. Fehlerfall: klar bezeichneten letzten Live-Commit zurückrollen.
5. Live niemals automatisch in JTL-Wawi, Lager/WMS oder Amazon schreiben; das bleibt ein späterer, separat autorisierter Schritt.

**Für den nächsten Live-Konfigurationsschritt vom Nutzer erforderlich:** Ein Screenshot von **Plesk → Websites & Domains → artikelmanager.hairworld24.de → Git**, auf dem Branch, Remote und Bereitstellungsordner sichtbar sind (keine Passwörter). Zur echten Wella-Prüfung zusätzlich die **ASIN des ersten angezeigten Treffers** oder dessen Amazon-Link, denn der konkrete Treffer ist bisher nicht im Chat/Repository belegt.

**Offene Abnahmen:** Kein echter Plesk-Deploy von PR #23, kein Amazon-Live-Test des konkreten Wella-Listings und keine tatsächliche LLM-Integration/Live-Promotion als erledigt markieren.
