# HW24 Artikelmanager · Hairworld24

> **Zuerst lesen, dann weiterarbeiten:** Wir starten nicht bei null. Backups, Plesk-Verzeichnisprüfung und GitHub-Deployabgrenzung wurden bereits bearbeitet. Die aktuelle, verbindliche Anwendungsbasis ist **V4.22 TEST** im Branch **`deploy/staging`**, nicht der ältere `main`-Quellcode. Dieser Defaultbranch dient vorerst als dauerhaft auffindbarer **Dokumentations-Einstieg**.

## 📌 Verbindliche Projektübersicht

**[Aktueller Projektstand, erledigte und offene Punkte](docs/HW24_PROJEKTSTAND_AKTUELL.md)**

**[Arbeitsjournal, Entscheidungen und Nachweise](docs/HW24_ARBEITSJOURNAL.md)**

**[Aktuelle UI- und JTL-Feldprüfung anhand der Screenshots](docs/HW24_UI_JTL_FELDMASKE_20261120.md)**

**[Automatische Vorbelegung für JTL, Amazon, Historie und Scanliste – aktueller Stand](docs/HW24_AUTO_VORBELEGUNG_V422_20261120.md)**

[PuTTY-Installation V11/V12 – abgeschlossen](docs/HW24_PUTTY_SCANLIST_SETUP_20261120.md)

Diese beiden Dateien beim Fortsetzen eines Chats, beim Öffnen eines PRs und vor einer Codeänderung zuerst lesen. Alle neuen Entscheidungen, Tests, GitHub-Commits, Fehler und abgeschlossenen Arbeiten dort fortschreiben. Keine bereits bestätigten Schritte ohne neuen konkreten Anlass nochmals verlangen.

## Aktuelle Versions- und Release-Verknüpfung (20.11.2026)

| Zweck | Referenz | Zustand |
|---|---|---|
| Testanwendung | [`deploy/staging`](https://github.com/NicoVoss1/HW24-scanner/tree/deploy/staging) | V4.22 TEST, Plesk zieht diesen Branch **manuell** |
| Verifizierter Staging-SHA | `2175c375fdce4afe2b335dafb43167505f7123a0` | **PR #19** jetzt im GitHub-Staging, Plesk-Pull noch offen |
| Feature-Entwicklung | [PR #16](https://github.com/NicoVoss1/HW24-scanner/pull/16) | V4.22 Scanner-/Amazon-Erweiterung, Draft |
| Website-Testfreigabe | [PR #17](https://github.com/NicoVoss1/HW24-scanner/pull/17) | Nur Webdateien; **in GitHub-Staging gemerged**, Plesk noch nicht neu deployed |
| UI & Scanlisten-Optimierung | [PR #18](https://github.com/NicoVoss1/HW24-scanner/pull/18) | **In GitHub gemerged**, wartet auf Test-Plesk-Pull |
| Historische V4.35-Abzweigung | PR #14, geschlossener PR #15 | **Nicht** ohne bewusste Entscheidung übernehmen |

## Sofortiger nächster Schritt

**Stand 21.11.2026:** **HW24 V4.23 Qualitätszentrum** [PR #31](https://github.com/NicoVoss1/HW24-scanner/pull/31) ist in GitHub-`deploy/staging` gemerged, SHA **`c54894c854c538e79ef8e6fc9b9132102be79a6c`**. [CI #44953759138](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44953759138) grün. Noch **kein** bestätigter Plesk-Deploy oder JTL-Gesamtqualitätslauf.

**Jetzt einmalig nur die Testseite aktualisieren:** Plesk `test-artikelmanager.hairworld24.de` → Git Branch `deploy/staging` → **Jetzt Pull ausführen** → **Jetzt bereitstellen**. Danach die neue `https://test-artikelmanager.hairworld24.de/qualitaetszentrum.html` im angemeldeten Browser öffnen. Dort **Snapshot-Zeit, tatsächliche Artikelzahl, Bestand-ohne-EK- und Hersteller-Lücken** und einen Direktsprung zum vorhandenen JTL-Artikel testen.

**Wichtig:** Der Code liest ausschließlich den bereits vorhandenen vollständigen JTL-MariaDB-Spiegel; keine neue SQL-Installation, keine Kosten für OpenAI, keine Produkt-/Bestandsänderung. **Keine alten Backups/PuTTY-Skripte wiederholen. Die Live-Seite bleibt auf `main` unverändert.** Echte Fehlerraten noch nicht gemessen.

[Ausführliche V4.23-Anleitung](docs/HW24_QUALITAETSZENTRUM_V423_20261121.md) · [Gesamtauftrag Intelligence Console #30](https://github.com/NicoVoss1/HW24-scanner/issues/30).

## Vorheriger Schritt (PR #22)

**GitHub-Testcode:** [PR #22](https://github.com/NicoVoss1/HW24-scanner/pull/22) mit Scan-Korrekturen (−1, Position aus Liste entfernen), CSV-Import bis 5.000 EANs inklusive Lagerplatz/Information/Kommentar sowie JTL-Nummernkreis-Modus ist nach `deploy/staging` gemerged. **Aktueller Staging-SHA: `7db542548f7ba4c31ddf12d65b1e1866ff0592ba`.** Plesk-Bereitstellung dieses neuen Commits steht noch aus.

**Jetzt genau:** Plesk `deploy/staging` → **Jetzt Pull ausführen → Jetzt bereitstellen**. Dann im Browser Doppelscan mit `−1`, archiviertes Element durch erneuten Scan zurückholen, 3-Zeilen-CSV (EAN, Lagerplatz, Information, Kommentar) importieren und nach Reload prüfen. Erst danach die ~1.500 EANs importieren. Vorherige Backups, PuTTY, Datenbankanlage und Verzeichniskontrollen **nicht wiederholen**.

**Offene Grenzen:** Die JTL-Ameise-Nummernvergabe muss im echten Importprofil auf **„Ja, globale fortlaufende Nummer und Präfix beachten“** stehen; die tatsächliche Nummer ist aus dem vorhandenen Read-only-Spiegel nicht vorab zu reservieren. Dusicolor/Hair Styling wird als Konflikt markiert, ursprüngliche Quellzeile noch nicht live verifiziert. Keine JTL-/WMS-Schreibaktion oder Live-Seitenänderung.

[Detailprotokoll: Scanliste, CSV und JTL-Artikelnummer](docs/HW24_SCAN_CSV_NUMMERNKREIS_V422_20261120.md)

## Projektziel

1. EAN scannen und zentral in MariaDB sammeln.
2. Bestehenden JTL-Artikel erkennen oder eine geprüfte Neuanlage vorbereiten.
3. JTL, vorhandene Stammdaten, historische Excel-Quellen und Amazon-SP-API **automatisch, cache-/kontingentbewusst** auswerten.
4. Farben und Artikel stapelweise als passende JTL-Ameise-Prüf-CSV exportieren.
5. Nach erfolgreichem Test getrennt auf die Live-Anwendung übertragen.
6. Später Markt-/Einkaufschancen unter Berücksichtigung aller Kosten überwachen, **keine automatischen Käufe**.

**Achtung:** Bereits erstellte Plesk-/MariaDB-Backups und Verzeichniskontrollen werden als abgeschlossen behandelt. SQL-Migrationen der *neuen* Scanlisten-Funktionen und echtes Server-End-to-End-Testing sind nicht automatisch dadurch bestätigt. Genau diese verbleibenden Punkte stehen in der Projektübersicht.

**Deployment-Regel:** `docs/`, `sql/`, `sync/`, `.github/` und `hw24_private/` gehören nicht in den öffentlich erreichbaren Plesk-Deploy-Tree. `main` keinesfalls nur wegen dieser README-Anpassung auf die Testdomain veröffentlichen.
