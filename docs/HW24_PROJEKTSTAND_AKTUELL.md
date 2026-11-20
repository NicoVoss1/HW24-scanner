# HW24 · Verbindlicher Projektstand und Einstiegspunkt

**Stand:** 20.11.2026, fortgeschrieben nach UI-Release PR #18 · **Projekt:** Hairworld24 Artikelmanager / HW24-scanner  
**Gültigkeit:** Dies ist die zentrale, fortlaufend zu pflegende Projektdokumentation. Sie ist nicht selbst ein Nachweis, dass ein Plesk-Deployment, eine Datenbankmigration oder ein Live-Test durchgeführt wurde.

> **WICHTIG FÜR DIE FORTSETZUNG:** Beim nächsten Chat, Entwicklerauftrag oder GitHub-Arbeitsschritt **zuerst diese Seite lesen** und die letzten Commits/PRs vergleichen. Keine bereits abgeschlossenen Einrichtungs- und Backup-Schritte von vorn verlangen. Ein Sachstand darf nur aufgrund neuer Befunde geändert werden. Nicht stillschweigend auf `main`, eine andere Version oder einen alten PR zurückspringen.

## NÄCHSTER SCHRITT 20.11.2026, 22:15 Uhr · PLESK TEST-DATENBANK

**Vom Nutzer per Screenshot belegt:** Neue Scanliste läuft als Website. Die separate Scanner-MariaDB-Anbindung ist noch nicht eingerichtet; neue Scan-Tabellen (V11) und Keyword-Cache-Tabelle (V12) fehlen laut lesender Diagnose oder sind dem aktuellen DB-Nutzer noch nicht sichtbar. Die Meldung „Bitte zuerst einen Stapel erstellen“ ist Folge der noch nicht funktionsfähigen Stapel-API; noch keine erfolgreiche Speicherung bestätigt.

**GitHub-Code bereits korrigiert:** [PR #19](https://github.com/NicoVoss1/HW24-scanner/pull/19) wurde nur mit `api/amazon-keywords.php` nach `deploy/staging` gemerged, Commit **`2175c375fdce4afe2b335dafb43167505f7123a0`**. Die Amazon-Stichwortcache-Schreibvorgänge verwenden danach das neu anzulegende `hw24_private/scanlist-db.php`, **nicht** die bisherige schreibgeschützte `db.php`. Kein DB-Schritt auf Plesk ausgeführt; Live-Seite unverändert.

**PuTTY-Installer vorbereitet, aber auf Plesk NICHT ausgeführt:** `ops/HW24_INSTALL_SCANLIST_TEST.sh` im Branch `feature/hw24-v422-scanlist-install-20261120`, gepinnter Commit **`a3f0d8592a926fb348b589a76b2170cee1ab67c3`**. Der Installer erkennt ausschließlich die Test-API samt bereits vorhandener privater `db.php`, fordert explizit Eingabe TEST, legt fünf neue Tabellen an, erstellt einen separaten DB-Benutzer mit starkem zufälligem Passwort und minimale Tabellenrechte, erstellt `scanlist-db.php` ausschließlich außerhalb des Webroots. Die bestehende `db.php`, SP-API-Token, JTL/WMS und Livebestände werden nicht verändert. **Einmalige Ausführung durch den Nutzer über PuTTY mit root-/Plesk-DB-Rechten erforderlich**.

**Keine Wiederholung der bisherigen Sicherungen/Ordner-/Plesk-Konfiguration!** Konkrete Copy-Paste-Befehle, Grenzen und Status in [HW24 PuTTY Scanlist-Setup](./HW24_PUTTY_SCANLIST_SETUP_20261120.md).

**Exakt nächster Nutzerschritt:** In Plesk Branch `deploy/staging`: Pull → Bereitstellen (PR #19), danach **einmalig** PuTTY-Installer nach verlinkter Anleitung. Im Browser `/scanliste.html` Diagnose prüfen, Stapel `04-001` erstellen und den Scan erneut durchführen. Passwörter niemals in Chat oder GitHub ausgeben.

## AKTUELLER STATUS NACH DEN NEUEN NUTZER-SCREENSHOTS

- **TESTSEITE IN PLESK SICHTBAR:** Der Nutzer hat am 20.11.2026 Screenshots von V4.22 TEST inklusive neuer `scanliste.html` geliefert. Damit ist die Website-Bereitstellung aus PR #17 praktisch bestätigt; **nicht erneut den vorherigen Release oder allgemeine Backups verlangen**.
- **KONKRETER FEHLER BESTÄTIGT:** Die neue Scanliste meldet **„MariaDB noch nicht konfiguriert“**. Im Code bedeutet das, dass der zusätzliche private Zugang `hw24_private/scanlist-db.php` am erwarteten Ort nicht gefunden wird. **Das ist kein Nachweis für eine defekte bereits bestehende JTL-/MariaDB-Anbindung.** Ob V11-/V12-Tabellen existieren, wurde noch nicht serverseitig festgestellt.
- **NEUER TESTRELEASE IN GITHUB:** [PR #18](https://github.com/NicoVoss1/HW24-scanner/pull/18) gemerged, **`deploy/staging` @ `bcb8a9664f08aeb66de5aa7efd461f284affae99`**. Enthält neue lesbare Header-Statusleiste, SVG-Favicon, 41 geprüfte JTL-Stammdatenfelder in fünf Gruppen sowie read-only `api/scanliste-status.php` zur zielgenauen Diagnose.
- **CODETESTS:** [GitHub Actions #44786470819](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44786470819) grün; geprüft sind PHP-/JS-Syntax, Inline-Skripte, Favicon/SVG, Referenzen und Feld-Schnittstellen. **Kein** Ersatz für echte Browser-/DB-End-to-End-Tests.
- **EXAKT NÄCHSTER SCHRITT:** Plesk bleibt auf `deploy/staging` → `Jetzt Pull ausführen` → `Jetzt bereitstellen` → `https://test-artikelmanager.hairworld24.de/scanliste.html` öffnen → zielgenauen Hinweis aus der neuen **Scanlisten-Diagnose** ablesen. Nur fehlende **neue** V11/V12-Tabellen bzw. die **separate** private Scanlisten-Verbindung ergänzen. **Keine erneuten Backups/Verzeichnis-Checks** ohne konkreten Fehler.
- **SICHERHEIT:** GitHub Staging-Root nach PR #18 geprüft: `api/`, `assets/`, `index.html`, `scanliste.html`; **keine** öffentlichen `sql/`, `docs/`, `sync/`, `hw24_private/`. Live-Seite, Wawi und Bestände unverändert.
- **UI-/Feldabnahme dokumentiert:** [HW24 UI, JTL-Feldmaske und Diagnose](./HW24_UI_JTL_FELDMASKE_20261120.md). Ursprüngliche Wawi-Screenshots aus früheren Chats sind im derzeit durchsuchbaren Dateiindex nicht aufgefunden worden; die Zuordnung basiert auf dem dokumentierten 872-Spalten-JTL-Export und `assets/reiter.js` (13 Reiter).

## 1. Projektziel / verbindlicher End-to-End-Workflow

1. Paket öffnen, EAN/GTIN scannen, Eingangsposition **sofort** als Scanereignis in HW24-MariaDB speichern, weiterscannen.
2. Den aktuellen JTL-Artikelspiegel prüfen: **vorhanden**, **mögliche Neuanlage**, **Mehrfachtreffer**, **JTL-Abgleich offen/veraltet**. Ein unbekannter JTL-Stand ist ausdrücklich **keine bestätigte Neuanlage**.
3. Im Hintergrund bzw. nach erfolgreicher Erfassung Amazon-Katalogdaten **automatisch** und cache-/kontingentbewusst abfragen; bei eindeutiger JTL-Auflösung SKU/HAN → EAN, bei abgeschlossener Produktsuche Amazon-Kandidaten anzeigen. Keine unnötigen mehrfachen SP-API-Calls.
4. Alle unbearbeiteten Farben im **dauerhaften, zentralen** Scan-Stapel sammeln. Zielmenge zunächst ca. 1.000–2.000 Farben; gruppierte Prüfung und Stammdatenpflege statt Einzelanlage während der Erfassung.
5. Felder aus aktuellem JTL-Spiegel, freigegebenem HW24-Artikelstand, historischen Excel-MariaDB-Quellen, geprüften Standards und Amazon-Katalog **mit Herkunft/Prüfstatus** vorschlagen. Unklare ASIN, Farbe, EAN, Variante, Menge, Steuer, Hersteller und EK nicht automatisch als sicher übernehmen.
6. Eindeutige Neuanlagen aus einem freigegebenen Stapel als Excel-kompatible **JTL-Ameise-CSV** exportieren, mit dem **tatsächlich getesteten Ameise-Importprofil** und zugeordneten Kopfzeilen. JTL-Ameise prüft den Import zuerst im kleinen Testlauf.
7. Warenwirtschafts-/WMS-Bestandsbuchung ist **separat** vom Scanlisten-Speichern und von der Artikel-Neuanlage. Kein versehentliches Einbuchen.
8. Perspektivisch günstige *wirklich bestellbare* Amazon-Angebote als Einkaufschancen identifizieren (Buy Box, tatsächlicher Verkaufspreis, Netto/Brutto, Amazon-Gebühren, Versand, EK, Retouren, Produktrechte und Risiken); **keine** automatischen Käufe. Nächtliche Preisüberwachung erst nach getrenntem API-Budget-/Rate-Limit-Konzept.

## 2. Derzeit verbindliche Source of Truth

| Gegenstand | Stand / Link | Rolle |
|---|---|---|
| **Letzter bestätigter Plesk-Seitenstand** | V4.22 TEST und neue Scanliste per Nutzerscreenshot sichtbar | UI aus PR #17 sichtbar; neue Version PR #18 noch nicht in Plesk bestätigt |
| Plesk-Repository | `NicoVoss1/HW24-scanner.git` | Testdomain `test-artikelmanager.hairworld24.de`; Branch `deploy/staging` |
| GitHub-Defaultbranch | `main` · Stand 20.11.2026 älter als V4.22 | **NICHT** als aktuelle Web-Version ausrollen oder als neue Featurebasis verwenden |
| V4.22-Implementierungs-PR | [PR #16](https://github.com/NicoVoss1/HW24-scanner/pull/16) | Offener Draft, Entwicklungsdateien einschließlich SQL/Dokumentation, **nicht deployed** |
| V4.22 erste Scanlistenfreigabe | [PR #17](https://github.com/NicoVoss1/HW24-scanner/pull/17) | Gemerged; Website aus dieser Entwicklungsphase bereits per Screenshot nachgewiesen | 
| **Aktueller Github-Stagingstand** | [PR #18](https://github.com/NicoVoss1/HW24-scanner/pull/18) | **Gemerged**, SHA `bcb8a9664f08aeb66de5aa7efd461f284affae99`; **Plesk-Update noch ausstehend** |
| Prüfkandidat Website-Branch | `release/hw24-v422-scan-test-20261120` · `7dd38d4a69ecc2897653a30029c6410b943016c4` | Auf V4.22 aufgebaut, aber nur Codevorschau; **keine bestätigte Testbereitstellung** |
| Frühere abweichende Entwicklung | PR #14 · V4.35; PR #15 geschlossen | **Nicht** als V4.22-Quelle verwenden. #15 wurde mit Verweis auf #16 abgelöst |
| **Dokumentations-Wahrheit** | `main/docs/HW24_PROJEKTSTAND_AKTUELL.md` | Verbindlicher handlungsorientierter Projektstand; `main` bleibt trotzdem **nicht** Codebasis |

**Sicherheitsprinzip:** `deploy/staging` enthält nur `index.html`, `assets/`, `api/` (neue Scanlisten-Seite kommt nach Freigabe hinzu). `sql/`, `docs/`, `.github/`, `sync/`, `hw24_private/` bleiben außerhalb des öffentlich bereitgestellten Branch-Baums. Keine Datenbankzugangsdaten ins GitHub-Repository.

## 3. Bereits abgeschlossen / nicht erneut von vorn

| Thema | Status / Grundlage | Konsequenz |
|---|---|---|
| Vollständiges Plesk-Backup | **Vom Auftraggeber am 20.11.2026 als bereits durchgeführt bestätigt** | **Nicht erneut anfordern**; tatsächlicher Speicherort/Restore-Test wurden hier nicht nochmals ausgelesen |
| MariaDB-Backup | **Vom Auftraggeber am 20.11.2026 als bereits durchgeführt bestätigt** | **Nicht erneut anfordern**; vorhandene Sicherung als Ausgangspunkt nehmen |
| Verzeichnis-/Private-Pfad-Prüfung | **Vom Auftraggeber als bereits geprüft bestätigt** | Nicht erneut durch Plesk-Oberfläche führen, es sei denn, ein konkreter Laufzeitfehler weist auf einen falschen Pfad |
| Sicherung, Wiederherstellung und Syntaxprüfung der JTL-/WMS-API | In vorangehender Arbeit als erfolgreich bestätigt; API-Backup und SHA-256-Abgleich wurden vorgenommen | Bestehende geprüfte JTL-/WMS-Funktionen erhalten; keine unnötige Neuinstallation |
| Verbindung Plesk ↔ GitHub | Plesk-Screenshot bestätigt Branch `deploy/staging`, manuelle Pull-/Bereitstellungssteuerung | Keine neue Repository- oder Branch-Einrichtung |
| Staging-Release-Dateibaum | GitHub bestätigt: `api/`, `assets/`, `index.html`, `scanliste.html` | **Faktisch geprüft**, interne Verzeichnisse nicht im aktuellen Staging-Tree |
| Bestätigter Testseiten-Code | GitHub SHA `dca30b0` enthält Anzeige `HW24 Artikelmanager v4.22 TEST` | Keine Rückkehr zum veralteten `main` |
| Rückfallstand | `backup/staging-before-pr4-20261120` existiert als GitHub-Branch | Nicht löschen oder überschreiben; allein ein Branch ersetzt aber kein Plesk-/DB-Backup |
| V4.22 Amazon/JTL-Funktionen | In Code vorhanden: Amazon-Katalog mit 360-Minuten-Cache, JTL-Spiegel, WMS/Beleginformationen, historische Daten, Amazon-Preis-/Gebührenabrufe auf Anforderung | Bestehende Feature-Schnittstellen beibehalten |
| Scanliste im Test sichtbar | PR #17 in Plesk sichtbar; PR #18 als neues UI-Release gemerged | **Neue Scanlisten-Datenbank meldet fehlende private Konfiguration; DB-Schritt noch offen** |
| GitHub-Codeprüfungen | Für Entwicklungsbranch #16: GitHub Actions Syntax-/Paketcheck erfolgreich, zuletzt SHA `61c164f` | **Nicht** mit DB-, Browser- oder Plesk-Abnahme verwechseln |

**Nachweis-Trennung:** Benutzerbestätigte erledigte Maßnahmen werden ohne Wiederholung übernommen. Aus GitHub geprüfte Tatsachen sind gesondert bezeichnet. Nicht belegte Details wie exakte Backup-Dateinamen, Restore-Zeitpunkte, DB-Migrationsstatus oder Live-Website-Funktion dürfen nicht erfunden werden.

## 4. Implementierte, aber noch nicht bereitgestellte Funktionsbausteine

| Baustein | Wo | Aktueller Status |
|---|---|---|
| Schnellscan, mehrfaches Erfassen, dauerhafte Stapel und Scan-Historie | `scanliste.html`, `api/scanliste.php` | Oberfläche auf Testseite sichtbar; **Speichern blockiert, solange separate DB-Config fehlt** |
| Identifikation bestehend/neu/mehrdeutig/stale durch JTL-Spiegel | `api/scanliste.php` | Code vorhanden; echte DB- und E2E-Tests ausstehend |
| Versionierte Stammdatenentwürfe, Quell-Audit | SQL `HW24_11_SCANLISTE.sql` | Migration vorbereitet; **Durchführung nicht bestätigt** |
| Automatische Amazon-Katalogabfrage pro gespeicherter EAN | Neue Scanliste + bestehendes `api/amazon.php` | Client-Warteschlange und Cache eingebaut; globales Rate-Limit noch nicht umgesetzt |
| Amazon-Stichwort-/ASIN-Kandidatensuche | `api/amazon-keywords.php`, `assets/hw24-amazon-keywords.js` | Code vorbereitet; SQL `HW24_12_AMAZON_KEYWORD_CACHE.sql` und echte Berechtigungs-/Serverprüfung ausstehend |
| JTL-Ameise Sammelprüf-CSV | `scanliste.html` und `api/scanliste.php` | Code bereit; **Original-Exportkopfspalten sind nicht automatisch ein getestetes Importprofil** |
| Günstige Einkaufschancen / Arbitrage | Entwurf in `docs/HW24_V422_SPAPI_EINKAUFSCHANCEN_20261120.md` auf PR #16 | Konzept, **noch keine aktive Preisüberwachung und keine automatischen Einkäufe** |
| Automatische nächtliche Angebotsprüfung | Konzept | **Nicht aktiviert**, SP-API-Kontingente / Server-Jobsteuerung offen |

## 5. Wirklich offene Aufgaben – nur hier fortsetzen

**Priorität 1: Neue UI-Version PR #18 in Plesk laden und gezielte Scanlist-Diagnose nutzen.**
- PR #17 ist in Plesk **sichtbar**. PR #18 ist in `deploy/staging` gemerged, aber neues Plesk-Pull/Deploy **noch nicht bestätigt**. **Nicht erneut Backups verlangen.**
- Erforderlich ist eine **lesende Statusprüfung** der beiden neuen Tabellenstrukturen (`hw24_scan_batches`, `hw24_scan_items`, `hw24_scan_events`, `hw24_scan_draft_audit`, `hw24_amazon_keyword_cache`) und der privaten Scanlist-Verbindung – **nicht** eine nochmalige Überprüfung aller bereits bestätigten Verzeichnisstrukturen.
- Wenn die zwei SQL-Migrationen V11/V12 bereits durchgeführt worden sind, dies dokumentieren und **keine Migration wiederholen**. Wenn nicht, nach der bereits bestätigten Sicherung kontrolliert ergänzen.
- Zugriff und Nutzerrechte für die neuen Endpunkte im geschützten Plesk-Testhosting prüfen. Admin-Daten/Schlüssel niemals ins Repo oder in den Chat kopieren.
- PR #18 ist bereits auf `deploy/staging` übernommen. In Plesk jetzt **„Jetzt Pull ausführen“**, danach **„Jetzt bereitstellen“**. Anschließend die neue Scanlist-Lesediagnose nutzen.

**Priorität 2: Kurzer, nachvollziehbarer End-to-End-Abnahmetest.**
- 5–10 EANs inkl. vorhanden, neu, doppelt, mehrdeutig und JTL-Ausfall testen.
- Gespeicherten Scan-Stapel nach Browserwechsel erneut öffnen; leere Neuanlage pflegen und wiederfinden.
- Amazon-Katalogabrufe auf Cache-Hit, API-Neuabfrage, 429/Fehler und doppeltes Enter prüfen.
- Ameise-CSV auf Spalten, Semikolon/UTF-8, Preis-/Steuerwerte und Dubletten gegen **JTL-Wawi 2.2.2.0** prüfen. Erst wenige Testartikel in JTL-Ameise, keine unkontrollierte Masseneinbuchung.

**Priorität 3: Wirtschaftsmodul.**
- Serverübergreifendes SP-API-Budget, konkurrierende Sitzungen und Cache-Fehlerhandling.
- Einkaufschance nur bei echter ASIN-/Produktidentität, verifizierbarem Gesamt-Einkaufspreis und realem Deckungsbeitrag.
- Nächtliche Preisprüfung als priorisierte Watchlist, nicht blind 2.000 ungedrosselte Einzelanfragen.

**Priorität 4: Liveübernahme nach bestandener Testabnahme.**
- Erst nach dokumentierter Freigabe, gesondert in Live-Pipeline, mit Rückfallplan. Live-Shop/Wawi bis dahin unverändert.

## 6. Arbeitsregeln für alle folgenden Chat-/GitHub-Schritte

1. **Vor jeder Änderung**: diese Datei sowie `docs/HW24_ARBEITSJOURNAL.md` lesen, dann GitHub-PRs und den tatsächlichen Staging-SHA vergleichen.
2. **Nie** eine neue Arbeitsphase als Start von null behandeln; keine bereits bestätigten Backups, Verzeichnis- oder Integrationsprüfungen erneut anfordern. Nur konkrete neue Probleme prüfen.
3. **Erst Tatsachen und Status dokumentieren**, dann auf dem richtigen Stand ändern. Jede Änderung muss PR/Commit, Zweck, geprüfte Tests, Status und nächste Aktion nennen.
4. **Basis ist stets der tatsächlich bestätigte V4.22-Test-/Stagingstand plus danach ausdrücklich freigegebene Änderungen**; nicht das veraltete `main` und nicht automatisch ein höher nummerierter Branch.
5. Deploybare Dateien und SQL-/Dokumentations-/Synchronisierungs-/Privatdateien trennen. Für Test zuerst nur bewusst freigegebene Website-Dateien; **kein heimliches Auto-Deploy**.
6. **Kein Merge, Live-Deploy, DB-Schema- oder Bestandsänderung ohne passende Freigabe**; vorhandene Backups nicht ständig neu erzeugen.
7. Wenn etwas **nicht geprüft** ist, als **OFFEN/UNKLAR** markieren, nicht zu „fehlend“ oder „fehlgeschlagen“ machen.
8. Die Projektübersicht und das Journal **bei jedem relevanten Fortschritt** aktualisieren, ausdrücklich auch bei Fehlversuchen und Rollbacks. Im fertigen Chat den letzten Commit/PR und den **genau nächsten nicht erledigten Schritt** nennen.
9. GitHub-`main` dient bis zur kontrollierten Konsolidierung der **Dokumentation**, nicht dem aktuellen Webcode. Das vermeidet erneute Versionswechsel.
10. Kein geheimhaltungsbedürftiges Material oder Zugangsdaten im Repository dokumentieren, auch keine Passwortbilder oder Datenbank-Dumps.

## 7. Referenzen

- [UI-/JTL-Feldprüfung, Screenshot-Befunde und Diagnose](./HW24_UI_JTL_FELDMASKE_20261120.md)
- [PR #18 · Neue gruppierte Artikelpflege, Favicon und gezielte Diagnose](https://github.com/NicoVoss1/HW24-scanner/pull/18)

- [Verbindliches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md)
- [PR #17 · Bereinigte Testfreigabe](https://github.com/NicoVoss1/HW24-scanner/pull/17)
- [PR #16 · Entwicklungsbranch V4.22 Scanliste / Amazon](https://github.com/NicoVoss1/HW24-scanner/pull/16)
- [GitHub-Staging](https://github.com/NicoVoss1/HW24-scanner/tree/deploy/staging)
- [Abweichender alter V4.35-Branch PR #14](https://github.com/NicoVoss1/HW24-scanner/pull/14) · **nicht als Grundlage verwenden**
- [Geschlossene PR #15](https://github.com/NicoVoss1/HW24-scanner/pull/15)
