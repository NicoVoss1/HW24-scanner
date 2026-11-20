# HW24 · Verbindlicher Projektstand und Einstiegspunkt

**Stand:** 20.11.2026, fortgeschrieben nach PR-#17-Merge · **Projekt:** Hairworld24 Artikelmanager / HW24-scanner  
**Gültigkeit:** Dies ist die zentrale, fortlaufend zu pflegende Projektdokumentation. Sie ist nicht selbst ein Nachweis, dass ein Plesk-Deployment, eine Datenbankmigration oder ein Live-Test durchgeführt wurde.

> **WICHTIG FÜR DIE FORTSETZUNG:** Beim nächsten Chat, Entwicklerauftrag oder GitHub-Arbeitsschritt **zuerst diese Seite lesen** und die letzten Commits/PRs vergleichen. Keine bereits abgeschlossenen Einrichtungs- und Backup-Schritte von vorn verlangen. Ein Sachstand darf nur aufgrund neuer Befunde geändert werden. Nicht stillschweigend auf `main`, eine andere Version oder einen alten PR zurückspringen.

## AKTUELLE ENTSCHEIDUNG – Website-Testfreigabe nach GitHub-Merge

- **JETZT für Plesk bereit:** PR [#17](https://github.com/NicoVoss1/HW24-scanner/pull/17) wurde nach `deploy/staging` gemerged. Neuer GitHub-Staging-Commit: **`c0da9033a8373d7e6d210f075a7a4210a9670bc6`**. Plesk ist **noch nicht nachweislich gepullt oder deployed**.
- **Nächster Nutzerschritt:** Plesk Branch `deploy/staging` unverändert lassen → **Jetzt Pull ausführen** → **Jetzt bereitstellen** → `https://test-artikelmanager.hairworld24.de/scanliste.html` aufrufen und Anzeige/Fehlerstatus mitteilen.
- **Sicherheitsprüfung GitHub:** Nach Merge besteht der Staging-Root ausschließlich aus `api/`, `assets/`, `index.html`, `scanliste.html`; keine `sql/`, `docs/`, `.github/`, `sync/`, `hw24_private/` veröffentlicht.
- **DB-Funktion noch nicht nachgewiesen:** Die zusätzlichen V11/V12-Tabellen und die nur für die neue Scanliste erforderliche private DB-Verbindung sind nicht als eingerichtet bestätigt. Erst nach dem UI-Deploy gezielt deren Status prüfen, **kein neuer Komplettbackup-/Ordnercheck**.
- **GitHub privat:** GitHub Free unterstützt private Repositories. **Erst Plesk-Zugriff auf private Repo per SSH-Deploy-Key einrichten**, dann Sichtbarkeit ändern, sonst kann das bisherige HTTPS-Repository-Pull scheitern. GitHub Pro ist für Privatheit nicht nötig.

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
| **Letzter bestätigter Plesk-Seitenstand** | Ursprüngliche V4.22 TEST · `dca30b015f062955c29306d2a5e6fa5265d0f45b` | Bisherige UI-Basis; neuer Plesk-Pull/Deploy noch offen |
| Plesk-Repository | `NicoVoss1/HW24-scanner.git` | Testdomain `test-artikelmanager.hairworld24.de`; Branch `deploy/staging` |
| GitHub-Defaultbranch | `main` · Stand 20.11.2026 älter als V4.22 | **NICHT** als aktuelle Web-Version ausrollen oder als neue Featurebasis verwenden |
| V4.22-Implementierungs-PR | [PR #16](https://github.com/NicoVoss1/HW24-scanner/pull/16) | Offener Draft, Entwicklungsdateien einschließlich SQL/Dokumentation, **nicht deployed** |
| V4.22 bereinigter Website-Release | [PR #17](https://github.com/NicoVoss1/HW24-scanner/pull/17) | **Gemerged**, GitHub-Staging-SHA `c0da9033a8373d7e6d210f075a7a4210a9670bc6`; Plesk-Deploy noch offen |
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
| Staging-Release-Dateibaum | Aktuell im GitHub geprüft: `api/`, `assets/`, `index.html` | **Faktisch geprüft**, interne Verzeichnisse werden nicht im aktuellen Staging-Tree ausgeliefert |
| Bestätigter Testseiten-Code | GitHub SHA `dca30b0` enthält Anzeige `HW24 Artikelmanager v4.22 TEST` | Keine Rückkehr zum veralteten `main` |
| Rückfallstand | `backup/staging-before-pr4-20261120` existiert als GitHub-Branch | Nicht löschen oder überschreiben; allein ein Branch ersetzt aber kein Plesk-/DB-Backup |
| V4.22 Amazon/JTL-Funktionen | In Code vorhanden: Amazon-Katalog mit 360-Minuten-Cache, JTL-Spiegel, WMS/Beleginformationen, historische Daten, Amazon-Preis-/Gebührenabrufe auf Anforderung | Bestehende Feature-Schnittstellen beibehalten |
| Neue Scanlisten-Vorbereitung | PR #16 Entwicklungsquelle, Website-PR #17 gemerged | **GitHub-Dateien bereit ≠ Plesk-Deploy/DB-Integration erfolgreich getestet** |
| GitHub-Codeprüfungen | Für Entwicklungsbranch #16: GitHub Actions Syntax-/Paketcheck erfolgreich, zuletzt SHA `61c164f` | **Nicht** mit DB-, Browser- oder Plesk-Abnahme verwechseln |

**Nachweis-Trennung:** Benutzerbestätigte erledigte Maßnahmen werden ohne Wiederholung übernommen. Aus GitHub geprüfte Tatsachen sind gesondert bezeichnet. Nicht belegte Details wie exakte Backup-Dateinamen, Restore-Zeitpunkte, DB-Migrationsstatus oder Live-Website-Funktion dürfen nicht erfunden werden.

## 4. Implementierte, aber noch nicht bereitgestellte Funktionsbausteine

| Baustein | Wo | Aktueller Status |
|---|---|---|
| Schnellscan, mehrfaches Erfassen, dauerhafte Stapel und Scan-Historie | `scanliste.html`, `api/scanliste.php` | Code im PR #16/#17, **nicht live installiert** |
| Identifikation bestehend/neu/mehrdeutig/stale durch JTL-Spiegel | `api/scanliste.php` | Code vorhanden; echte DB- und E2E-Tests ausstehend |
| Versionierte Stammdatenentwürfe, Quell-Audit | SQL `HW24_11_SCANLISTE.sql` | Migration vorbereitet; **Durchführung nicht bestätigt** |
| Automatische Amazon-Katalogabfrage pro gespeicherter EAN | Neue Scanliste + bestehendes `api/amazon.php` | Client-Warteschlange und Cache eingebaut; globales Rate-Limit noch nicht umgesetzt |
| Amazon-Stichwort-/ASIN-Kandidatensuche | `api/amazon-keywords.php`, `assets/hw24-amazon-keywords.js` | Code vorbereitet; SQL `HW24_12_AMAZON_KEYWORD_CACHE.sql` und echte Berechtigungs-/Serverprüfung ausstehend |
| JTL-Ameise Sammelprüf-CSV | `scanliste.html` und `api/scanliste.php` | Code bereit; **Original-Exportkopfspalten sind nicht automatisch ein getestetes Importprofil** |
| Günstige Einkaufschancen / Arbitrage | Entwurf in `docs/HW24_V422_SPAPI_EINKAUFSCHANCEN_20261120.md` auf PR #16 | Konzept, **noch keine aktive Preisüberwachung und keine automatischen Einkäufe** |
| Automatische nächtliche Angebotsprüfung | Konzept | **Nicht aktiviert**, SP-API-Kontingente / Server-Jobsteuerung offen |

## 5. Wirklich offene Aufgaben – nur hier fortsetzen

**Priorität 1: Plesk-Pull und manueller Testdeploy ohne Rückschritt.**
- PR #17 ist **gemerged**, aber Plesk-Pull/Deploy **noch nicht bestätigt**. **Nicht erneut Backups verlangen.**
- Erforderlich ist eine **lesende Statusprüfung** der beiden neuen Tabellenstrukturen (`hw24_scan_batches`, `hw24_scan_items`, `hw24_scan_events`, `hw24_scan_draft_audit`, `hw24_amazon_keyword_cache`) und der privaten Scanlist-Verbindung – **nicht** eine nochmalige Überprüfung aller bereits bestätigten Verzeichnisstrukturen.
- Wenn die zwei SQL-Migrationen V11/V12 bereits durchgeführt worden sind, dies dokumentieren und **keine Migration wiederholen**. Wenn nicht, nach der bereits bestätigten Sicherung kontrolliert ergänzen.
- Zugriff und Nutzerrechte für die neuen Endpunkte im geschützten Plesk-Testhosting prüfen. Admin-Daten/Schlüssel niemals ins Repo oder in den Chat kopieren.
- PR #17 ist bereits auf `deploy/staging` übernommen. In Plesk jetzt **„Jetzt Pull ausführen“**, danach **„Jetzt bereitstellen“**. Nur die bereits geprüften Webdateien sind auf diesem Branch.

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

- [Verbindliches Arbeitsjournal](./HW24_ARBEITSJOURNAL.md)
- [PR #17 · Bereinigte Testfreigabe](https://github.com/NicoVoss1/HW24-scanner/pull/17)
- [PR #16 · Entwicklungsbranch V4.22 Scanliste / Amazon](https://github.com/NicoVoss1/HW24-scanner/pull/16)
- [GitHub-Staging](https://github.com/NicoVoss1/HW24-scanner/tree/deploy/staging)
- [Abweichender alter V4.35-Branch PR #14](https://github.com/NicoVoss1/HW24-scanner/pull/14) · **nicht als Grundlage verwenden**
- [Geschlossene PR #15](https://github.com/NicoVoss1/HW24-scanner/pull/15)
