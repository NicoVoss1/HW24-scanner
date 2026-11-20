# HW24 V4.22 TEST · Gemeinsame automatische Stammdatenanreicherung

**Stand:** 20.11.2026, nach Merge [PR #20](https://github.com/NicoVoss1/HW24-scanner/pull/20) in `deploy/staging` · GitHub-SHA **`dc9646d4863772dab823283be5d8f4bbec8f3c99`**  
**WICHTIG:** GitHub-Merge ist **kein** Nachweis für Plesk-Pull, Browser-Integration oder erfolgreichen Amazon-/JTL-End-to-End-Test. Diesen Zustand erst nach der Testabnahme als erfolgreich markieren.

## 1. Anlass und gesicherter Screenshotbefund

Die 20.11.2026 übermittelten Screenshots der Testseite zeigen:
- Scan-Stapel `04-001` existiert; die EAN `8033219164159` liegt nach Wiederaufruf **dauerhaft** in MariaDB.
- JTL-Spiegel erkennt diesen Artikel als **bestehend**: `Inebrya Color Pastel Roso Intense Pink 100 ml`; **kein** Neuanlage-Import für diese EAN zulässig.
- In der alten Scanlisten-Eingabemaske waren trotz des JTL-Treffers Hersteller, Artikelnummer, Warengruppe, Steuerklasse, SEO und viele Standards leer; Ursache: Scanlisten-API lieferte nur minimale Metadaten und die bestehende UI rief noch nicht für jede Auswahl die vollständigen aktuellen Quellen ab.
- Scanliste zeigte „Amazon SP-API 0 ausstehend / 0 geprüft“ nach Reload, obwohl dies kein Verbindungsfehler war.
- Im Scan-EAN-Feld fehlte ein eigener Löschbutton.
- Große Stammdateneingabemaske ist bereits in 5 Gruppen strukturiert und wird beibehalten.

## 2. Neue gemeinsame Quelldaten-Engine

- `assets/hw24-enrichment.js`: EAN-Prüfberechnung, deduplizierter GET-Zugriff auf `api/jtl.php`, `api/curated.php`, `api/historie.php` und `api/amazon.php`; Client-TTL 2–5 Minuten, serverseitiger Amazon-Katalog-Snapshot weiterhin 360 Minuten.
- `assets/hw24-scan-autofill.js`: scanlistenbezogene Quellenauflösung, **fortsetzbare browserseitige Hintergrundwarteschlange** und revisionsgesichertes Speichern neuer Artikelentwürfe in **der bereits eingerichteten HW24-MariaDB**. Keine neue Datenbankmigration erforderlich.
- `assets/hw24-main-enrichment.js`: dieselben Produktregeln ergänzen den bestehenden Artikelmanager, den Stammartikelentwurf und dessen Lagerplatzansicht. Kein direkter JTL-/WMS-Schreibzugriff.
- Angepasst: `index.html`, `scanliste.html`. Scanliste hat eigenen ✕-Button am EAN-Feld, Amazon-Status ohne irreführende 0/0-Pflicht und eine rechnerische Brutto-EK-Anzeige.
- **Plesk-Release:** Nur `api/`, `assets/`, `index.html`, `scanliste.html` im Root. Keine privaten Zugangsdaten, SQL-, Sync-, .github- oder Dokumentationsdateien im Webtree.

### Verbindliche Quellenpriorität

1. **Frischer, eindeutiger JTL-Spiegel** für tatsächliche JTL-Artikelstammdaten und Artikelnummer.
2. **Freigegebener HW24-Artikelstand** aus der bisherigen MariaDB.
3. **Historische Excel-Daten nur bei belegten Kopfzeilen und ohne widersprüchliche Werte**; bei Konflikten keine stillschweigende Übernahme.
4. **Amazon SP-API** nur bei eindeutig identifiziertem GTIN-Treffer. Bei mehreren ASINs oder Set-/Mehrfachpackung zwingend Benutzerentscheidung.
5. **HW24-Standardprofile** nur für noch leere Werte. Regel-/Schätzwerte werden durch Quellennamen als solche markiert.

Manuell geänderte oder bestehende JTL-Werte werden **nicht** durch niedrigere Quellen überschrieben.

## 3. Vorbelegung für Haarfarben

| Feld | Regel | Kennzeichnung |
|---|---|---|
| Artikelname | Exakt aus frischem JTL; sonst freigegebene Stammdaten/historischer belegter Name oder eindeutiger Amazon-Einzelartikel | Quelle wird gespeichert |
| Hersteller | JTL-Herstellername; wenn im Spiegel nur Hersteller-ID, bekannte Marke aus verifiziertem JTL-Produktnamen als prüfpflichtiger Vorschlag | Keine ID als Klarname erfinden |
| Artikelnummer | JTL-Artikelnummer bei bestehendem Artikel; für bestätigte neue EAN zunächst EAN als Vorschlag | Vor Ameise prüfen |
| GTIN / HAN | GTIN vom Scanner; HAN bevorzugt echte JTL-HAN, sonst HW24-Regel HAN=GTIN **nur als Vorschlag** | Eine echte Hersteller-HAN ist nicht grundsätzlich identisch mit einer GTIN |
| ASIN | Einziger EAN-bestätigter Amazon-Treffer ohne erkennbares Set darf vorgeschlagen werden; Mehrfach-ASIN aus Amazon/JTL/Historie als Auswahl | Keine unbemerkte Set-/Pack-Zuordnung |
| Warengruppe und Kategorie 1 | JTL-Klarname, sonst `Haarfarben` bei erkennbarer Haarfarbe | JTL-Zuordnung prüfen; Kategorie 2/3 nicht erfinden |
| Steuerklasse | JTL-Klarname oder HW24-Vorschlag `normaler steuersatz` für passende Produkte | Steuerrecht vor Freigabe prüfen |
| Artikeltyp | `Normaler Artikel`, bei erkennbaren Kartons `Kartonage` | Vorlage |
| Währung | EUR | Vorlage |
| Einkaufspreise | Nur belegter tatsächlicher JTL-/Lieferanten-EK. Rechnerischer Brutto-EK bei eindeutigem Netto-EK und 19 % als **Anzeigewert**, kein Ameise-Feld | Durchschnitts-EK ist nicht tatsächlicher EK |
| Lieferant | Belegter Lieferantenname als Textvorschlag, sofern eindeutige aktuelle Zuordnung vorliegt | **Noch keine automatisierte JTL-Lieferanten-ID-Zuordnung** |
| SEO | SEO-Titel, Meta-Description, URL-Slug aus bestätigter Artikelbezeichnung ohne erfundene Produkteigenschaften | Vor Freigabe prüfen |
| Packungsinhalt / Einheit | Menge/ML bzw. andere belegte Maßeinheiten aus Produktbezeichnung | Inhalt ist kein Lagerbestand |
| Artikelgewicht | Bei 60 ml: Vorschlag 0,06 kg, bei 100 ml: 0,10 kg | **Schätzung**: Volumen ist nicht Masse |
| Versandgewicht | Artikelgewicht + 0,02 kg, z.B. 0,08 / 0,12 kg | **Schätzung**, tatsächliche Verpackung prüfen |
| Artikel aktiv / Bestandsführung | Y / Y | Vorlage, **keine WMS-Einbuchung** |
| Überverkäufe / Teilbar | N / N | Standard, Überverkauf weiterhin nur nach ausdrücklicher Freigabe |
| Bearbeitungszeit | 1 Tag | Vorlage |
| Beschaffungszeit automatisch | 0 / Nein | Vorlage |
| Beschaffungszeit manuell | Vorläufig 2 Tage | JTL erwartet eine **Tagezahl**, kein Ja/Nein |
| Neu im Sortiment | Y | Vorlage |
| Herkunftsland, ungeprüfter Lieferstatus, exakte Kategorie 2/3 | Offen, bis eindeutige Quelle vorliegt | Nicht aus EAN/ASIN erfinden |

## 4. Automatische EAN- und Amazon-Prozesse

- **Bei jedem neuen Scan** wird eine eindeutige EAN sofort in der Scanliste gespeichert und der Amazon-Katalog über die vorhandene SP-API mit Cache geprüft.
- Die **fortsetzbare Anreicherungsqueue** ergänzt den zentralen Entwurf auch dann, wenn bereits die nächste EAN ausgewählt wurde. Queue-IDs werden im Browser lokal gespeichert; der Datenbankentwurf bleibt zentral. **Diese Bearbeitung läuft nur bei geöffnetem Browser**, kein serverseitiger Nachtjob.
- Fehler oder 429 führen zu einer Pause und sichtbarer Wiederaufnahmeoption, nicht zu unkontrollierten parallelen API-Anfragen. Innerhalb eines Browserfensters werden identische GETs zusammengelegt; ein serverübergreifendes globales Amazon-Budget für mehrere Geräte bleibt gesondert offen.
- Bei bereits vorhandenen JTL-Artikeln werden Daten **nur lesend** in der Scanliste ergänzt. Bestehende Artikel dürfen nicht als Neuanlagen exportiert werden.
- Mehrere Amazon-ASINs zeigen einzelne Kandidaten mit ASIN, Titel, Marke, Kennungsstatus und Packungsgrößenwarnung; eine Auswahl setzt eine bewusste Bestätigung voraus.

## 5. Qualitätssicherung und offene Punkte

**CODE GEPRÜFT:** [GitHub Actions #44826801120](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44826801120) und nachfolgende Feature-Commits mit Node-Tests. Geprüft: JS-/PHP-Syntax, HTML-Assets, 60/100-ml-Standardregeln, bestehende JTL-Artikelnummer/HAN, Historienkonflikte, ASIN-Mehrfachpackungsschutz, Header.

**NICHT durch GitHub-CI nachgewiesen:** live angebundener Browser-/iPhone-Scanner, tatsächlicher Amazon-SP-API-Snapshot für EAN `8033219164159`, vorhandene JTL-Hersteller-Mappingwerte, echte MariaDB-Entwurfsrevisionen, komplette Rate-Limit-Steuerung und JTL-Ameise-Importprofil.

### Nächster konkreter Testschritt

1. Plesk **`deploy/staging`** (GitHub-SHA `dc9646d4863772dab823283be5d8f4bbec8f3c99`) → **Jetzt Pull ausführen** → **Jetzt bereitstellen**. Keine Backup-/DB-Migration-/Verzeichnisprüfung wiederholen.
2. `https://test-artikelmanager.hairworld24.de/scanliste.html` mit Strg+F5 öffnen, Stapel `04-001`, EAN `8033219164159` auswählen. JTL-Artikelname, Hersteller, Artikelnummer und Warengruppe als vorhandene Quellen ansehen; **keine Neuanlagefreigabe**.
3. Eine nachweislich noch nicht in JTL vorhandene EAN scannen. Quelle/Warteschlangenstatus abwarten, Seite neu laden; Felder, Quellen und Datenbank-Persistenz nachprüfen.
4. Einen Amazon-Fall mit **mehreren ASINs oder einem 2er-Pack** testen: Wahl muss manuell erfolgen.
5. Erst danach geplante Sammelmengen und das JTL-Ameise-Testimportprofil prüfen; keine unkontrollierte Produktionseinbuchung.

**Dokumentationsverknüpfung:** [Projektstand](./HW24_PROJEKTSTAND_AKTUELL.md) · [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) · [JTL-Ameise-Importprofil](./JTL_AMEISE_IMPORTPROFIL.md).
