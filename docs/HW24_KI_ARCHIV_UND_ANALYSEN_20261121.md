# HW24 V4.22 TEST · Dauerhaftes KI-Prüfarchiv und größere Analyseansicht

**Stand 21.11.2026.** [PR #26](https://github.com/NicoVoss1/HW24-scanner/pull/26) ist in `deploy/staging` gemerged, GitHub-Test-SHA **`94141e67482e87a2afaec56d60654d534cae5da8`**. [GitHub Actions #44933326129](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44933326129) grün. Dies ist **kein bestätigter Plesk-/MariaDB-/OpenAI-End-to-End-Test**. Die Live-Domain bleibt unverändert auf `main`.

## Aktueller Fachzustand und Nutzerwunsch

- Die erste erfolgreiche OpenAI-Prüfung für **Wella Color Touch 9/01** zeigte im Screenshot einen Status `review`, einen Warengruppenvorschlag `Haarfarben`, ASIN-Einzelprüfungen zu Einzel- und Mehrfachpackungen sowie **0,0013 USD** tokenbasierte HW24-Kosten.
- **Bisher** speicherte die Anwendung nur Monats-/Aufrufzähler und Tokenkosten in `hw24_private/hw24-ai-usage.json`. Der vollständige KI-Bericht existierte nur vorübergehend im Browserfenster und wurde **nicht** automatisch als JTL-/Ameise-Artikelwert übernommen.
- **Neue Lösung:** Jede nach Aktivierung erfolgreich durchgeführte KI-Prüfung wird als eigener, unveränderlicher Datensatz in **MariaDB** `hw24_artikelmanager.hw24_ai_reviews` gespeichert. Enthalten sind Bearbeiter (Authentifizierung), EAN, Artikelname, Eingabequellen einschließlich Amazon-ASIN-/Packungsbelegen, vollständige strukturierte KI-Auswertung, Modell, Erstellungszeit in UTC, Tokenverbrauch (inklusive Cache) und tokenbasierte USD-Kostenschätzung.
- **Kein rückwirkender Nachtrag aus dem Ledger:** Die frühere erste Analyse kann nicht aus aggregierten Kostenzahlen rekonstruiert werden. Ein zukünftiger erneuter OpenAI-Aufruf kostet Tokens, benötigt ausdrückliche Bestätigung und darf nicht automatisch wiederholt werden.

## Frontend / Datenflüsse

- `assets/hw24-ai-history.js` zeigt eine **große responsive KI-Auswertungsübersicht**, geöffnet über **„📂 Auswertungen“** oben rechts in Scanliste **und** Haupt-Artikelmanager.
- Der Bericht zeigt Historie nach EAN oder global, eigene Datum-/Kosten-/Tokenwerte, Modell, vollständige Begründung, Warengruppenvorschlag, Hersteller, Farbcode, ml-Menge, Stückzahl, Artikeltyp sowie alle einzelnen ASIN-Bewertungen mit externen Amazon.de-Links. „Ältere laden“ ermöglicht seitenweise historisches Stöbern, **ohne neue KI-Kosten**.
- Bei **neuem** Artikel und **nach zusätzlicher manueller Bestätigung** können eine AI-vorgeschlagene Warengruppe oder eine in ursprünglichen Amazon-Daten tatsächlich enthaltene und als `pass` bewertete ASIN in den bereits bestehenden **HW24-Scanlistenentwurf** übernommen werden. `api/scanliste.php` verarbeitet die Änderung revisions- und quellengeprüft; Quelle enthält `KI-Prüfung #<ID>`. Kein automatisches JTL-/WMS-Schreiben, kein automatischer Produkt-/Preisimport.
- **JTL-Bestandsartikel:** weiterhin nur Ansicht/Vergleich. Deren Stammdaten sind nicht durch die Scanliste direkt veränderbar.
- `api/ai-reviews.php` bietet authentifizierte, nur lesende Historie mit EAN-Filter und Cursor-Pagination. Jede Person sieht nur die eigene gespeicherte Historie.
- `api/ai-review.php` prüft **vor einer kostenpflichtigen OpenAI-Anfrage**, dass das neue Archiv verfügbar ist. Ist V13 noch nicht eingerichtet, wird der Call **ohne API-Kosten** mit `review_storage_not_ready` blockiert. Erfolg wird nur nach erfolgreichem Insert mit `stored:true` und `review_id` bestätigt.
- Die bisherige 5-USD-Monatsbudget-/25-Aufrufe-Tageslimit-Konfiguration bleibt erhalten. `hw24-ai-usage.json` führt die Kosten, `hw24_ai_reviews` hält die fachliche Historie. Das HW24-Ledger ist **keine vollständige OpenAI-Rechnung**.

## Testserver: Einmalige V13-Archiv-Installation

**Die bestehende MariaDB-Scanlisten-Installation V11/V12, private Zugangsdaten und frühere Backups nicht noch einmal erstellen.** Das neue Archiv braucht nur **eine zusätzliche Tabelle** und eng begrenzte `SELECT,INSERT`-Rechte für den bereits vorhandenen DB-Benutzer `hw24_scan_rw`. Die Installation verändert keine bestehenden JTL-/WMS-Tabellen.

### A. Zuerst nur TEST-Seite mit GitHub aktualisieren

In Plesk → `test-artikelmanager.hairworld24.de` → Git → Branch **`deploy/staging`** → **„Jetzt Pull ausführen“** → **„Jetzt bereitstellen“**. Ziel-Commit `94141e67482e87a2afaec56d60654d534cae5da8`. **Nicht** die Live-Domain auf `main` pullen.

### B. Danach einmaliges, geprüftes Installationsskript über PuTTY

Als bereits angemeldeter root folgende **Befehle**, nicht die Beispiel-Ausgabezeilen, kopieren:

```bash
curl -fL --proto '=https' --tlsv1.2 'https://raw.githubusercontent.com/NicoVoss1/HW24-scanner/7249fbd70089682f23248c1502d49a3e4ef91699/ops/HW24_INSTALL_AI_ARCHIVE_TEST.sh' -o /root/HW24_INSTALL_AI_ARCHIVE_TEST.sh
bash -n /root/HW24_INSTALL_AI_ARCHIVE_TEST.sh && echo "Syntax OK"
less /root/HW24_INSTALL_AI_ARCHIVE_TEST.sh
bash /root/HW24_INSTALL_AI_ARCHIVE_TEST.sh
```

Bei `less` mit **q** wieder verlassen. Das Skript druckt das **Testziel**, prüft das vorhandene `hw24_private/scanlist-db.php` und neue Test-API-Dateien, und verlangt zur Bestätigung exakt `ARCHIV`. Erst dann wird die eine neue Tabelle erstellt und die engen Rechte erteilt. Keine öffentlichen Passwörter werden angezeigt oder geschrieben.

### C. Prüfung ohne kostenpflichtigen KI-Aufruf

Im angemeldeten Browser aufrufen:

- `https://test-artikelmanager.hairworld24.de/api/ai-reviews.php` → erwartet `ok:true`, `stored:true`, `count:0` bei frischem Archiv.
- Scanliste neu laden und **„📂 Auswertungen“** klicken → die große Archivansicht ist sichtbar, anfangs noch leer.
- Erst danach im Artikel **eine einzelne, bewusst bestätigte** KI-Prüfung durchführen. Anschließend müssen `review_id`, Kosten, vollständiger gespeicherter Bericht, Filter nach EAN und Wiederaufruf nach dem Neuladen funktionieren. Ein Bericht ist erst **nach** diesem Praxistest als dauerhaft gespeichert zu bestätigen.

## Erweiterbare nächste Schritte

1. **Produkt-Stammdatenagentur:** KI soll mehrere unabhängige Quellen (JTL-Spiegel, überprüfte Amazon-SP-API-Daten, Lieferantenbelege, historische Excel-/CSV-Daten) zusammenführen, Fundstellen/Confidence erhalten und kontrollierte Entwürfe für Hersteller, Warengruppe, Farbcode, Inhalt, Verpackung, SEO erstellen. Keine erfundenen Einkaufspreise, JTL-IDs oder Bestände.
2. **Erweiterter Varianten-/Packungsagent:** Trennung Einzelprodukt, 2er-/3er-/6er-Packs, Farbnamen/-nummern, abweichende GTIN und verifizierbare Einzel-ASIN. EAN und Herstellerkennung stets vor automatischer Zuordnung vergleichen.
3. **Preis-/Marge-/Repricer-Agent:** Quellengeprüfte tatsächliche Einstandspreise, Netto-/Brutto-Verkaufspreise, Versandkosten und Gebühren aus JTL/Amazon/eBay/Shopify; getrennte Berechnung/Freigabe, keine automatische Preisänderung ohne Regeln.
4. **Massenabgleich im Hintergrund:** Späterer serverseitiger, gedrosselter Job für EAN-Listen und selektive KI-Prüfung **nur bei Abweichungen**, pro Projekt Kostenlimit/Rate-Limits; nicht mit dem jetzigen Browser-Queue verwechseln.
5. **Andere Projekte:** Je eigenes API-Projekt, private Schlüssel, Autorisierungen, gesonderter Kosten-/Usage-Ledger und passende Sicherheitsgrenzen, statt denselben API-Key unkontrolliert überall zu verwenden.

**Offene Grenzen:** Aktuell existiert noch keine automatisch laufende Hintergrund-KI, kein produktiver Wawi-Schreibzugriff und keine bestätigte Live-Freigabe. Die neue V13-Tabelle muss zuerst auf dem Plesk-Testserver installiert und mit einer neuen bezahlten Prüfung getestet werden.
