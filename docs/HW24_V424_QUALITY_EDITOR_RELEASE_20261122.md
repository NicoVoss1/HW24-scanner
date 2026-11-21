# HW24 V4.24 TEST · Qualitätszentrum und kompakte Artikelpflege

**Aktueller Stand:** Sonntag, 22.11.2026. [PR #32](https://github.com/NicoVoss1/HW24-scanner/pull/32) ist in **`deploy/staging`** gemerged, GitHub-Teststand **`f51ab67240bd1bf0e5be25498269049a24748b85`**. Dies ist **kein nachgewiesenes Plesk-Deployment** und noch kein Browser-/Live-JTL-End-to-End-Test. `main`/Live-Domain sind unverändert.

## Inhalt

### Qualitätszentrum
- Unabhängige Filter **Bestand** `all / positive / zero / negative / missing` und **EK** `all / positive / missing_both / netto_missing`.
- Wenn beide Filter aktiv sind, wird ausdrücklich **ODER** (Standard) oder **UND** angewendet. Ist nur einer aktiv, wird nur dieser berücksichtigt.
- Sortierbarer **Bestand** und **Netto-EK** über Tabellenspalten und Dropdown: je auf-/absteigend, echte numerische Sortierung mit fehlenden Werten am Ende.
- **Standardpriorität:** Artikel mit positivem Bestand vor Null-/negativen/unbekannten Beständen, danach schwere Qualitätswarnungen.
- Die Schaltfläche **Bestand ohne EK** schaltet ausdrücklich Bestand positiv **UND** beide EK-Indizien nicht positiv. Zahlen sind JTL-Spiegelindikatoren, nicht endgültiger Einkaufsbeleg.
- `api/jtl-quality.php` liest unverändert nur den letzten vollständig synchronisierten MariaDB-Snapshot. Die pure Filter-/Sortierfunktion liegt unter `api/hw24-quality-filters.php` und wird durch PHP-Unitregeln geprüft.
- Tabellen: Identität, Bestand, Netto-EK, letzter EK, Qualitätswarnungen, Link zur neuen Pflegeansicht, auch Suche nach JTL-Art.-Nr. ohne gültige EAN.

### Neue Artikelpflege für vorhandene JTL-Artikel
- Neue Seite `artikelpflege.html` mit eigenem Stylesheet/JS. **Eine zentrale Eingabe** für Identifikation, Hersteller, Warengruppe, Inhalte, Kurz- und Langbeschreibung, Preise. Erweiterte Liefer-/Beschaffungs- und zusätzliche Artikelstammdaten in **einer** optionalen Sektion statt unübersichtlicher Einzelmenüs.
- Das Formular lädt eindeutige vorhandene JTL-Stammdaten über `GET api/jtl.php?q=...`. Veralteter JTL-Snapshot, Mehrfachtreffer oder fehlende/ungültige GTIN führen zu eingeschränkter Leseansicht/keiner Freigabe.
- Hersteller, Warengruppe, Kategorie und Lieferstatus werden, soweit im aktuellen JTL-Spiegel beobachtet, als Auswahlvorschläge über `api/jtl-options.php` bereitgestellt. Es wird **kein vollständiger MSSQL-Referenzkatalog behauptet**.
- Bestehende JTL-Originalwerte werden bei jedem Eingabefeld als Quelle gezeigt. Nur tatsächliche Änderungen erscheinen als **Vorher/Nachher-Diff**. Speicherung nach **expliziter Nutzerbestätigung** über die vorhandene revisionsgesicherte `POST api/curated.php`-HW24-Overlay-Tabelle; niemals direkter JTL-/WMS-/Shopify-Schreibzugriff.
- Die `curated.php`-Whitelist umfasst jetzt auch geprüfte **HW24-Entwürfe** zu Lieferstatus, Beschaffungszeit, Artikelstatus/Bestandsführung/Überverkäufen/Teilbarkeit. **Keine automatische Wawi-Anwendung dieser Felder ohne zukünftige gesonderte Ameise-Mapping-/Trockenlauf-Freigabe**.
- Die ursprüngliche `index.html`-Maske bleibt über **Expertenansicht** erhalten; Scanliste bleibt allein für echte JTL-Neuanlagen.

### Haarfarben-Referenz
- Vom Nutzer vorgegeben: **GTIN/EAN `3474630337398`**. EAN-13-Prüfziffer rechnerisch **gültig**.
- Beim Laden der neuen Pflegeansicht wird die GTIN mit dem **aktuellen** JTL-MariaDB-Spiegel abgeglichen; nur bei eindeutigem Artikel zeigt die Seite die dort **tatsächlich** vorhandenen Lieferstatus-IDs, Bearbeitungs-, Beschaffungszeiten, Versand- und Artikelstatuswerte.
- **WICHTIG:** Die konkreten JTL-Werte wurden beim Schreiben dieses Releasestands **nicht direkt aus der privaten Serverdatenbank gelesen und nicht überprüft**. Keine erdachten Werte und keine Annahme zu Marke/Lieferzeit.
- Als Vorlage werden nur **leere** geeignete Standardfelder per zusätzlicher **bewusster Bestätigung**, dass der **Zielartikel eine Haarfarbe** ist, ergänzt. Niemals Identität (EAN/HAN/JTL-Nummer), Markenname, EK, VK oder Lagerbestand übernehmen.
- Vorgeschlagene Werte werden erst als HW24-Entwurf nach einer **zweiten** Freigabe gespeichert, nicht in JTL-Wawi.

## Sicherheits- und Teststatus
- **GitHub Codeprüfungen erfolgreich:** [CI #44963263588](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44963263588) und [#44963263701](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44963263701), PHP-/JS-/HTML-Kontrollen, Bestands-/EK-ODER/UND, stock-first, GTIN-Prüfziffer und Curated-Autorisierung.
- Keine neue Datenbanktabelle, keine Backups, keine private Schlüsseländerung, keine KI-/Amazon-Kosten, keine JTL-/WMS-/Shopify-Live-Schreibaktion. Bestehendes KI-Archiv und Scanliste behalten ihre gespeicherten Daten.
- **E2E offen:** Browser-Prüfung der realen Filter, Referenzdaten des realen JTL-Artikels, saubere Auswahlfelder, Curated-POST/Reload sowie spätere JTL-Ameise-Mappings.

## Genau der nächste Schritt
1. **Nur** Plesk-Testdomain `test-artikelmanager.hairworld24.de` → Git `deploy/staging` → **Jetzt Pull ausführen** → **Jetzt bereitstellen**.
2. `https://test-artikelmanager.hairworld24.de/qualitaetszentrum.html` öffnen. Filter erst **Bestand > 0** allein testen, dann **EK fehlt** allein, dann **ODER** und **UND**. Beide Spaltenköpfe auf-/absteigend sortieren. Zuerst erscheinen bei Standardpriorität Lagerartikel.
3. `https://test-artikelmanager.hairworld24.de/artikelpflege.html?ean=3474630337398` öffnen. JTL-Originalwerte und Standardvorlage vergleichen. Bei der ersten Prüfung **nichts abspeichern**, bis die angezeigten Werte plausibel sind.
4. Anschließend einen Bestandsartikel mit tatsächlichen fehlenden Daten kontrolliert als **HW24-Entwurf** speichern, neu laden und auf korrekten Revisionsstand sowie unveränderten JTL-Wawi-Stamm prüfen. Kein Live-Deploy.

**Weiterer Meilenstein:** Anbindung der auf dieser neuen Maske bestätigten Änderungen an getrennte, mit JTL-Ameise getestete Bestandsartikel-Pflegeimporte und später die quellengeprüfte Shopify-Publikation. Dazu [Issue #30](https://github.com/NicoVoss1/HW24-scanner/issues/30).
