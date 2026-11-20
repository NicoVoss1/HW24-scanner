# HW24 v4.22 TEST · UI-, Scanlisten- und JTL-Feldprüfung
**Datum:** 20.11.2026 · **Auftrag:** Screenshots des Nutzers, Header-Kontrast, Favicon, geordnete Artikelstammdaten, Scanlisten-Datenbankfehler.

## 1. Direkt aus den gelieferten Screenshots (IST)

| Bereich | Befund | Reaktion |
|---|---|---|
| `/index.html` | Das Hauptfenster zeigt **HW24 Artikelmanager v4.22 TEST**; die Navigation enthält **Scanliste**. | Aktuelle Version als Basis beibehalten; nicht auf älteres `main` oder V4.35 wechseln |
| JTL-Status im Header | Helle/weiß wirkende Fläche mit sehr kontrastarmer Statusschrift, oben schwer lesbar. | Kontraststarker, opaker Bereich mit sichtbarem Button, Statusfarben und mobiler Umbruchregel |
| Favicon | Kein eigenes HW24-Favicon im Quell-HTML. | `assets/hw24-favicon.svg` und Link in beiden Seiten |
| `/scanliste.html` | Neue Seite lädt; Scan-Stapel-Eingabe und Export-UI sind sichtbar. | Form optisch neu strukturiert, Anzeige mobil/desktop lesbar |
| Scanlisten-API | **„Fehler: MariaDB noch nicht konfiguriert“** unter dem Scan-Feld. | Codeanalyse: `api/scanliste.php` verlangt zusätzliches **privates** `hw24_private/scanlist-db.php`. Der Fehler beweist **nicht**, dass die bestehende Datenbank oder JTL-Integration generell defekt ist |
| Stammdaten | Bisher flache Liste, Produktinformationen erst nach Auswahl/Scan. | Bei einer bestätigten Neuanlage fünf Wawi-basierte Gruppen, Pflichtfeldzähler und eindeutige Feldhinweise |

## 2. Bereits vorhandene und überprüfte Fachquellen

- **Original-JTL-Artikelstammdatenexport:** 872 Spalten, als Quelle dokumentiert in [JTL-Feldkatalog](./JTL_FELDKATALOG_STANDARDS_20261114.md) und [Ameise-Importprofil](./JTL_AMEISE_IMPORTPROFIL.md).
- **13 JTL-Wawi-Reiter im bestehenden Code:** `assets/reiter.js` im bestätigten V4.22-Staging (Allgemein, Beschreibung, Bestände, Lieferanten, Bilder, Attribute/Merkmale, Sonstiges, Variationen, Verkaufskanäle, Stückliste/Konfigurator, Sonderpreise, Dateien, Eigene Felder).
- **Ursprüngliche Fotos/Screenshots der Wawi-Eingabemaske aus älteren Chats:** In der hier verfügbaren Library-/Conversation-Bildsuche **nicht zuverlässig auffindbar**. Daher nicht behaupten, diese direkt analysiert zu haben; statt dessen die nachgewiesenen JTL-Feldnamen und das 13-Reiter-Modell nutzen.
- Offizieller JTL-Guide: [Allgemein](https://guide.jtl-software.com/jtl-wawi/artikel/artikelstammdaten-allgemein/), [Beschreibung](https://guide.jtl-software.com/jtl-wawi/artikel/artikelstammdaten-beschreibung/), [Lieferanten](https://guide.jtl-software.com/jtl-wawi/artikel/artikelstammdaten-lieferanten/), [Ameise-Artikelstamm](https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikeldaten-importieren/).

## 3. Neue Artikelmaske im Release-Kandidaten

**Reihenfolge und Funktion:**
1. **Artikelidentität:** Artikelnummer, GTIN, HAN, ASIN, Artikelname, Hersteller, Artikeltyp.
2. **Einordnung & Steuern:** Warengruppe, Kategorie Ebene 1–3, Steuerklasse, Herkunftsland, Währung.
3. **Einkaufs-/Verkaufspreise:** Kosten, Netto-VK, Brutto-VK, Lieferant, Lieferantenartikelnummer, Netto-EK in Standardwährung.
4. **Beschreibung & SEO:** Kurzbeschreibung, Beschreibung, SEO-Titel, Meta-Description, URL-Pfad, Anmerkung.
5. **Inhalt, Versand & Standards:** Inhalt/Menge, Maßeinheit, Artikel- und Versandgewicht, Versandklasse, Lieferstatus, Artikel aktiv, Bestandsführung, Überverkäufe, Stückzahl teilbar, Bearbeitungs-/Beschaffungszeiten, Top-Artikel, Neu im Sortiment.

**Technischer Stand:** 41 eindeutig zugeordnete Formularfelder, alle im serverseitigen Feldkatalog des neuen Scanlistenendpunkts zugelassen. Fünf Kontrollpflichtfelder für den aktuellen Neuanlagen-Export: `Artikelnummer`, `Artikelname`, `Hersteller`, `Warengruppe`, `Steuerklasse`; GTIN wird aus dem Scan fest übernommen. Je nach konkretem JTL-Importprofil können weitere Angaben erforderlich sein. **Exportkopfzeilen sind vor einem echten JTL-Ameise-Import zu testen.**

**Sicherheitsregeln:** Überverkäufe = N als Standard, Y nur mit manueller ausdrücklicher Zustimmung; bestehende JTL-Artikel dürfen nicht still als Neuanlage exportiert werden; `Kosten` ist nicht mit dem JTL-Durchschnitts-EK zu verwechseln; Lagerbestände und WMS sind eigene Prozesse. Produktgruppe, Steuern und Marktpreise werden nicht aus Amazon-Textähnlichkeiten erfunden.

## 4. Gezielter Technik-Check statt wiederholter Backups

**Neuer GET-Endpunkt:** `api/scanliste-status.php`
- authentifizierter HTTPS-Aufruf, **nur lesend**, keine Datenbankänderung;
- prüft über die bereits vorhandene private **Lesekonfiguration** (wenn vorhanden), ob die fünf Tabellen der V11-/V12-Erweiterung existieren;
- prüft nur, ob eine zusätzliche private Scanlisten-Zugangskonfiguration vorhanden und eine lesende Testverbindung möglich ist; niemals Benutzer, Passwörter, Serverpfade oder DB-Inhalte ausgeben;
- neue UI zeigt den fehlenden Teil gezielt: `V11`, `V12` oder `scanlist-db.php`;
- ein erfolgreicher Diagnosestatus beweist **noch nicht**, dass alle Schreibrechte, Amazon-Rollen und echten Importabläufe funktionieren.

**Bekannter Screenshotbefund:** `database_not_configured` bedeutet im `api/scanliste.php`-Code **fehlendes `scanlist-db.php` am berechneten privaten Pfad**. Das ist eine spezielle neue Zugangskonfiguration, nicht der Beweis einer defekten Bestandsdatenbank. Die **bereits durchgeführten** Plesk- und MariaDB-Backups sowie die allgemeinen Pfadprüfungen sind abgeschlossen und werden nicht erneut angefordert.

## 5. GitHub-Fortschritt und Release-Verfahren

- **Entwicklungsbranch:** `feature/hw24-v422-ui-form-favicon-20261120`, aus gesichertem Staging-Commit `c0da9033a8373d7e6d210f075a7a4210a9670bc6`.
- **Technische Codechecks:** [GitHub Actions #44786470819](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44786470819) **grün** (PHP-Lint, JS-Syntax, verknüpfte Assets, SVG und Feldabdeckung).
- **Releasebranch:** `release/hw24-v422-ui-form-favicon-20261120`, nur Site-Dateien.
- **Test-Freigabe:** [PR #18](https://github.com/NicoVoss1/HW24-scanner/pull/18), neuer UI-/Scanlisten-Release, Ausgangspunkt `deploy/staging`.
- **GitHub-Schutz:** Gemergter Testcode enthält ausschließlich `index.html`, `scanliste.html`, `assets/`, `api/`. **Keine** `sql/`, `docs/`, `sync/`, `hw24_private/` im Plesk-Deploy-Branch.
- **Plesk:** Manuelle Freigabe `deploy/staging` → Pull → Bereitstellen nur auf `test-artikelmanager.hairworld24.de`. Kein Live-Deploy.
- **Echte E2E-Abnahme:** Weboberfläche, neue Lesediagnose, Scanlisten-Zugang, spätere V11/V12-Tabellen, 5–10 EAN und Ameise-Testimport erst nach serverseitigem Funktionstest.

## 6. Projektfortsetzung (keine Wiederholungen)

1. Nur die neue Testfreigabe nach GitHub-Merge in Plesk aktualisieren.
2. Auf `/scanliste.html` die gezielte Statusdiagnose ablesen.
3. **Nur** tatsächlich fehlende neue Scanlist-/Keyword-Cache-Tabellen oder private Scanlisten-Verbindung ergänzen; **keinen** allgemeinen Plesk-/Backupprozess wiederholen.
4. Nach Speichermöglichkeit 5–10 echte EANs testen und Importfelder freigeben.
5. Ergebnisse mit Zeit, Commit und Fehlernummer im [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md) aktualisieren.
