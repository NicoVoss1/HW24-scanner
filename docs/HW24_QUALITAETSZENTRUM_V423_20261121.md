# HW24 Intelligence Qualitätszentrum · V4.23 TEST

**Stand: 21.11.2026.** [PR #31](https://github.com/NicoVoss1/HW24-scanner/pull/31) ist in den GitHub-Testbranch `deploy/staging` gemerged. **Commit `c54894c854c538e79ef8e6fc9b9132102be79a6c`**. Die automatisierten Tests [GitHub Actions #44953759138](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44953759138) sind erfolgreich. **Der Test-Plesk-Deploy und reale MariaDB-Abnahme wurden hier noch nicht durchgeführt.** Keine Änderung an Live `main`.

## Warum dieser neue Bereich?

Die bestehende `scanliste.html` bleibt ausschließlich für EAN-Neuanlagen und Sammelprüfung. Die neue `qualitaetszentrum.html` betrachtet dagegen **alle vorhandenen JTL-Artikel** des aktuellen vollständigen MariaDB-Spiegels. `index.html` bleibt die Detailansicht einzelner Artikel, die künftig für Quellen-/KI-Vorschläge und spätere, geprüfte Bestandsartikeländerungen genutzt werden soll.

**Bestehende Quellen verwendet, kein zusätzlicher manueller JTL-Export:**
- `hw24_jtl_sync_meta`: jüngster vollständig abgeschlossener `status=ready`-Snapshot, Abschlusszeit und vorgesehene Anzahl von Artikeln.
- `hw24_jtl_articles`: lesender Bestand aus derselben `source_id`. Vor jeder Qualitätsaussage wird `COUNT(*)` mit `last_row_count` geprüft.
- Verwendete Spiegelfelder: Artikelnummer, EAN, HAN, Artikelname, Hersteller, Kurzbeschreibung, Beschreibung, Netto-EK, letzter EK, JTL-Gesamtbestand, `raw_json.kWarengruppe`, `raw_json.HW24_WarengruppeName`, `raw_json.kLieferStatus`.
- Datenstand über 45 Minuten wird ausdrücklich als **historisch / veraltet** gekennzeichnet; es wird kein Live-Bestand behauptet. Ein unvollständiger Snapshot führt zur sicheren Fehlermeldung statt Teilstatistiken.
- Die erste Version ist bewusst auf **20.000 gespiegelte Artikel pro Audit** begrenzt. Für spätere größere Kataloge wird ein serverseitiges Streaming-/Queue-Audit entwickelt; keine Teilzahlen fälschlich als komplette Qualität bezeichnen.

## Im neuen Qualitätszentrum verfügbare Prüffälle

| Code | Prüfhinweis | Bedeutung |
| --- | --- | --- |
| `stock_without_ek` | Bestand vorhanden, EK im Spiegel nicht belegt | JTL-Bestand positiv und **weder** Netto-EK **noch** letzter EK aus zwei Spiegelfeldern positiv; **andere belegte Lieferantenkosten/Kommission noch gesondert prüfen** |
| `gtin_invalid` | Ungültige EAN/GTIN | Numerische GTIN-8/12/13/14 Prüfziffer stimmt nicht |
| `gtin_missing` | GTIN fehlt | Bei physischen Handelsartikeln prüfen; nicht automatisch für jeden Dienstleistungs-/Sonderartikel Pflicht |
| `manufacturer_missing` | Hersteller fehlt | Im Artikelfeld des Spiegels fehlt der Klartext; ggf. Quelle/Referenzmapping prüfen |
| `han_missing` | HAN fehlt | **HAN nicht einfach aus EAN ableiten oder gleichsetzen** |
| `name_missing` | Bezeichnung fehlt | Artikelname ist leer |
| `short_missing` und `description_missing` | Text fehlt | Spiegelfelder unbefüllt; inhaltliche Aussagekraft vorhandener HTML-Texte noch gesondert prüfen |
| `group_missing` | Warengruppe fehlt | Weder zugeordnete Gruppe noch JTL-Klartext vorhanden |
| `group_unresolved` | Warengruppen-ID ohne Klartext | JTL-Fremdschlüssel ist gesetzt; Windows-Sync muss den zutreffenden Referenznamen auflösen |
| `delivery_unknown` | Lieferstatus im Spiegel nicht belegt | Die JTL-ID ist nicht vorhanden/0; das bedeutet **noch nicht**, dass die echte Lieferzeit fehlt |

Nicht vollständig im aktuellen Mirror verifizierbar: **rechtlich verantwortliche Person, INCI, Herstellerwarnhinweise, rechtliche Kosmetikkennzeichnung, Verkaufskanalpflichten, vollständige belegte Einkaufs-/Kommissionskosten und reale WMS-Lagerverfügbarkeit**. Das Qualitätszentrum weist auf diese fehlende Datenabdeckung hin und **erfindet keine** Werte.

## Bedienung nach Plesk-Test-Bereitstellung

1. In **Plesk Testdomain** `test-artikelmanager.hairworld24.de` das Git-Repository auf Branch **`deploy/staging`** belassen, `Jetzt Pull ausführen`, `Jetzt bereitstellen`. Ziel-Commit `c54894c854c538e79ef8e6fc9b9132102be79a6c`.
2. Im angemeldeten Browser `https://test-artikelmanager.hairworld24.de/qualitaetszentrum.html` öffnen, oder über den neuen Link im Haupt-Artikelmanager beziehungsweise der Scanliste navigieren.
3. **Quelle überprüfen:** JTL-Snapshot-Zeit (UTC), Alter und Zahl der tatsächlich vollständig gespiegelten Artikel. Bei `stale` bitte nur den vorhandenen Windows-JTL-Sync-Status prüfen, keine aktuellen Live-Bestände daraus ableiten.
4. Nacheinander `Mit Regelwarnung`, `Hohe Priorität`, `Bestand ohne belegten EK`, `Hersteller fehlt` und `GTIN ungültig` als Filter testen; Suche nach einer bekannten Artikelnummer oder EAN verwenden.
5. Einen Treffer mit EAN oder JTL-Artikelnummer anklicken. Er soll in `index.html` **lesend** geöffnet werden. **Noch keine** Wawi-/WMS-/Shopify-Änderungen, kein Datenimport.
6. Nur bei echten, nachvollziehbaren Fehlfällen den nächsten Meilenstein implementieren: **Versionierte HW24-Änderungsentwürfe für vorhandene JTL-Artikel**, inklusive `Istwert → KI-/Quellenvorschlag → Beleg → Nutzerfreigabe → sichere JTL-Ameise-Prüfdatei`.

## Technische Dateien

- `api/jtl-quality.php`: privater, HTTPS-/Basic-Auth-geschützter GET, komplette Read-only-Auswertung des letzten vollständig synchronisierten JTL-Spiegels, Filter, Gesamtkennzahlen, Priorisierung, paginierte Ergebnisse. **Keine SQL-Schreibbefehle, keine kostenpflichtige OpenAI-Anfrage.**
- `api/hw24-quality-rules.php`: wiederverwendbare fachliche JTL-Prüfregeln.
- `qualitaetszentrum.html`, `assets/hw24-quality-center.js`, `assets/hw24-quality-center.css`: eigenständige, responsive Qualitätsoberfläche.
- `index.html`, `scanliste.html`: neue Navigation; Hauptmaske versteht `?q=<Artikelnummer>` auch ohne vorhandene GTIN.
- `tests/hw24-quality-rules.test.php`, `tests/hw24-quality-wiring.test.cjs`: GTIN, EK trotz Bestand, Trennung JTL-FK/Name, fehlende Pflichtwerte, Security und UI-Verkabelung. [CI erfolgreich](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44953759138).

**Wichtig:** Keine vorherigen SQL-/Plesk-/V11-/V12-/V13-Installationsskripte und keine Sicherungen erneut ausführen. Die neue Oberfläche verwendet ausschließlich die bestehende Datenbankverbindung `hw24_private/db.php`. Vertrauliche Artikel-/EK-Daten bleiben hinter der bestehenden Testseiten-Authentifizierung und gehören nicht in öffentliche GitHub-Dokumentation.

## Weiterführung

[Issue #30](https://github.com/NicoVoss1/HW24-scanner/issues/30) verfolgt den kompletten Intelligence-Console-Aufbau. [Issue #28](https://github.com/NicoVoss1/HW24-scanner/issues/28) befasst sich mit dem späteren **lesenden Shopify-JTL-Mapping**. Feldhoheit bleibt: JTL-Wawi für Artikelnummer/EK/Bestand; JTL-WMS für Lager; HW24 für Versionen/Belege/Prüfung; Shopify/Metricool erst nach separater, kontrollierter Freigabe. Der vorliegende Schritt ist der belastbare Einstieg, **noch keine vollständige PIM-/KI-Agenten-/Publishing-Lösung**.
