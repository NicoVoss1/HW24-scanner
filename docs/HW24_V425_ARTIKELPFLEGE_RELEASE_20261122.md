# HW24 V4.25 TEST · Artikelpflege mit Amazon, KI und Ameise

**Datum:** 22.11.2026. [PR #34](https://github.com/NicoVoss1/HW24-scanner/pull/34) in `deploy/staging` gemerged, SHA **`444453471b0fd27c73dcc7929f5e17f1d9239c52`**. Kein Plesk-Deploy und kein Browser-E2E aus GitHub allein nachgewiesen. Website-Live-`main` unverändert.

## Verifizierte Hauptursache Amazon-Abfrage in neuer Artikelpflege
- `artikelpflege.html` in V4.24 band trotz existierender `api/amazon.php` und Amazon-Bestandsfunktionen **keinen Amazon-Client** ein. Beim Artikelöffnen erfolgte daher keine sichtbare SP-API-Abfrage auf der neuen Pflegeansicht.
- V4.25 hat ein manuell auslösbares, eingeklapptes Amazon-Modul. `GET /api/amazon.php?ean=...` verwendet seine vorhandene 360-Minuten-Snapshot-Cache- und MariaDB-Logik. Fehlermeldungen mit `stage` und `upstream_status` werden angezeigt, sodass etwaige zusätzliche Plesk-LWA-/Rollensperren nach Testbereitstellung eingegrenzt werden können.
- Weitere ausdrücklich manuell auslösbare Quellen: `api/amazon-keywords.php?q=...` als Titel-Fallback und `api/amazon-pricing.php?asin=...` für Kaufbox-/niedrigste-Angebotspreise. Bei Titel-Suche darf nur ein Treffer **mit belegter passender GTIN** als ASIN-Vorschlag übernommen werden; andere Treffer sind ungeprüfte Kandidaten. Kein Repricer und kein Marktpreis-Import, keine automatische Amazon-Abfrage bei Seitenaufruf. Amazon-Lizenz-/Data-use-Regeln für eigene B2B-/Shop-Publikation gesondert beachten.

## KI-Berichte
- Beim Öffnen eines bestehenden Artikels wird nur das **kostenfreie** persönliche KI-Archiv `GET /api/ai-reviews.php?ean=...&limit=1` geladen; vorhandene Begründung und einzelne Vorschläge sichtbar, keinerlei automatische OpenAI-Abfrage.
- Neue kostenpflichtige `POST /api/ai-review.php` nur nach ausdrücklich bestätigt manuellem Klick und Readiness. Bereits archivierte EANs werden ohne neuen Call wiederverwendet; Rechecks mit letztem Archiv-ID-Header gegen Zwei-Tab-Doppelabrechnung abgesichert. Nur manuell genehmigte Änderungen werden als UI-Vorschlag vorgemerkt; separate HW24-Freigabe folgt.
- **Stand:** V13 Archiv und PR #29 existieren; individuelle Providerabrechnung kann von tokenbasierter Kostenschätzung abweichen.

## Artikelpflege und Datenhoheit
- Doppelt vorkommendes `id=logisticsFields` aus V4.24 entfernt.
- Lieferant und Lieferantenartikelnummer aus dem aktiven Pflegeformular entfernt (ursprüngliche Backend-Daten bleiben unberührt).
- Artikeltyp, Steuerklasse, Versandklasse und Währung besitzen nun echte Dropdown-Auswahlen; beobachtete Quellen kommen aus `api/jtl-options.php`. Bestehende individuelle Werte bleiben unverändert, eigene geprüfte manuelle Einträge bleiben möglich. JTL-Lieferstatus-/Versandklasse-/Steuerklasse-Klartexte sind abhängig von geprüften Windows-Fremdschlüsseln, können im Spiegel noch fehlen.
- Haarfarben-Vorlage EAN `3474630337398`: Normaler Artikel als **Vorschlag nach bestätigter Produktgruppe**; Zeitvorschlag separat mit `nAutomatischeLiefertageberechnung=0` (Nein), `nLiefertageWennAusverkauft=1` Tag, `nBearbeitungszeit=1` Tag, **nur leere Felder**. Der Gesamtwert ist keine garantierte Lieferzeit. `1` automatisch bedeutet Ja, JTL-Berechnung.
- Kategorien in der erweiterten Maske sind JTL-Kategoriezuordnungen, nicht automatisch Shopify-Shop-Kategorien. Währung `EUR` ist ohne geprüften SQL-Wert eine manuell auswählbare Option, **keine behauptete JTL-Originalquelle**.

## JTL Brutto-VK: explizite Grenze
- `api/jtl.php` liefert `brutto_vk` nur, wenn der Windows-MariaDB-Snapshot die **bestätigte** positive Preisangabe `raw.HW24_VKBrutto` **und** `raw.HW24_VKBruttoQuelle` enthält. Kein automatisches `netto_vk * 1,19` als vermeintlicher Datenbankinhalt.
- Diese beiden Werte müssen in der Windows-Synchronisation nach verifizierter JTL-Preisermittlung/Steuerzone zuerst ergänzt werden; das Windows-Synchronisationsskript liegt derzeit nicht in GitHub. Die Quelle kann die aktive Preisberechnung aus Steuerzone, Kunde und Steuerklasse sein, nicht notwendigerweise `tArtikel.fVKBrutto`. Siehe [offene Feldmapping-Aufgabe #33](https://github.com/NicoVoss1/HW24-scanner/issues/33).

## Ameise-Export vorhandener Artikel
- Eingeklappter Bereich im Artikel zeigt erst nach `GET api/curated.php?ean=...` die **tatsächlich bestätigten** und für die alte, manuell überprüfte Ameise-Vorlage geeigneten Felder. Anwender wählt jede Exportspalte explizit.
- Direkt vor dem Download werden frischer JTL-Snapshot, Artikelnummer und GTIN sowie unveränderte HW24-Overlay-Revisionsnummer serverseitig neu abgefragt. No-price-zero, keine leer importierten Spalten, keine Formelformeln. Ergebnis: JTL-Ameise **Ein-Zeilen-Prüfdatei**, kein API-Schreibvorgang.
- Lieferstatus, Versandklasse, Artikelstandard-/Beschaffungsfelder sind noch **nicht** Teil dieses exports, bis das korrekte Ameise-Mapping anhand der realen Windows-JTL-Wawi geprüft ist. Bereits vorhandene und im ersten bestehenden Import erprobte Spalten siehe `assets/ameise.js`.
- Die Importprüfung muss in JTL-Ameise mit **Nur vorhandene aktualisieren** und separatem Testlauf ausgeführt werden. Der HW24-Entwurf **Rev. 0** bedeutet: bisher keine bestätigte Revision für diese EAN, nicht „JTL ist leer“.

## Tests und nächste Aktivierung
- [CI #44964900380](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44964900380) erfolgreich: JavaScript Syntax, PHP-Lint, einzigartig vorhandene DOM-IDs, Amazon-Katalog-/Keyword-/Pricing-Abrufe nur bei Nutzerinteraktion, EAN-Nachweis für Titelfallback, OpenAI-Archiv/Kostenkontrolle, Revisions-/Identitätssperren Ameise.
- **Nächster Schritt:** Nur **Plesk-Testdomain** `test-artikelmanager.hairworld24.de`, Branch `deploy/staging` → Pull → Deploy. Dann `https://test-artikelmanager.hairworld24.de/artikelpflege.html?ean=3474630337398` öffnen; **Amazon-Katalog prüfen** einmal klicken und ggf. `stage/upstream_status` mitteilen, eine gespeicherte KI-Analyse kostenfrei anzeigen sowie Dropdowns testen. Brutto-VK kann bis zum Windows-Preis-Mapping fehlen.
- Alle bestehenden Tabellen, Käuferdaten, Amazon-Katalogsnapshots, KI-Berichte und JTL-/WMS-Artikel bleiben erhalten. Keine PuTTY- oder DB-Neuinstallation. `main` bleibt Live-Branch, **nicht** in Plesk deployen.
