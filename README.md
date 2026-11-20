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

**GitHub-Testcode:** Die gemeinsame JTL-/Amazon-/Historienvorbelegung ist über [PR #20](https://github.com/NicoVoss1/HW24-scanner/pull/20) und die ASIN-Titel-/Hersteller-Ergänzung über [PR #21](https://github.com/NicoVoss1/HW24-scanner/pull/21) in `deploy/staging` gemerged. **Aktueller SHA: `2f88d5a5dca6a8df80acbc38aba5287d960583c1`.**

**Jetzt:** Plesk Git bleibt `deploy/staging` → **Jetzt Pull ausführen → Jetzt bereitstellen** → Testseite `/scanliste.html` aktualisieren → Stapel `04-001`, EAN `8033219164159` (bestehender Artikel, nur anzeigen) prüfen → eine echte unbekannte EAN scannen und Datenpersistenz/ASIN-Auswahl kontrollieren.

**Nicht wiederholen:** Backups, PuTTY, V11-/V12-SQL-Installation und bisherige Pfadprüfungen wurden erledigt. **Keine Live-Seitenänderung.** GitHub-CI beweist noch keinen echten Server-/Amazon-/Browser-Test.

## Projektziel

1. EAN scannen und zentral in MariaDB sammeln.
2. Bestehenden JTL-Artikel erkennen oder eine geprüfte Neuanlage vorbereiten.
3. JTL, vorhandene Stammdaten, historische Excel-Quellen und Amazon-SP-API **automatisch, cache-/kontingentbewusst** auswerten.
4. Farben und Artikel stapelweise als passende JTL-Ameise-Prüf-CSV exportieren.
5. Nach erfolgreichem Test getrennt auf die Live-Anwendung übertragen.
6. Später Markt-/Einkaufschancen unter Berücksichtigung aller Kosten überwachen, **keine automatischen Käufe**.

**Achtung:** Bereits erstellte Plesk-/MariaDB-Backups und Verzeichniskontrollen werden als abgeschlossen behandelt. SQL-Migrationen der *neuen* Scanlisten-Funktionen und echtes Server-End-to-End-Testing sind nicht automatisch dadurch bestätigt. Genau diese verbleibenden Punkte stehen in der Projektübersicht.

**Deployment-Regel:** `docs/`, `sql/`, `sync/`, `.github/` und `hw24_private/` gehören nicht in den öffentlich erreichbaren Plesk-Deploy-Tree. `main` keinesfalls nur wegen dieser README-Anpassung auf die Testdomain veröffentlichen.
