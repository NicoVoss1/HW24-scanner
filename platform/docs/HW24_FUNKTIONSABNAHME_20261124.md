# HW24 – Funktionsinventur und responsive Abnahme

**Stand:** 24.11.2026  
**Branch:** `feature/hw24-ux-no-horizontal-scroll-audit-20261124`  
**Baseline:** bestehender Pilot aus PR #49 und dessen Basisbranch.  
**Umgebung:** GitHub-Quellprüfung und automatisierte CI; **kein neuer Server-Deploy**.

## Geltende Produktentscheidung

- Bestehende Domain `test-artikelmanager.hairworld24.de` bis zur vollständigen Funktionsabnahme beibehalten. **Keine neue Hub-Domain anlegen**.
- Eine zentrale, rollenbasierte Modulübersicht im vorhandenen Laravel-/Filament-System, später individuell ein-/ausblendbare Kacheln. Die Kachel-Funktion ist bislang **Ziel, nicht umgesetzt**.
- Einheitlicher, gemeinsam nutzbarer Artikelstamm und erlaubte historische Produkt-/KI-Quellen. Kunden- und auftragsbezogene Daten strikt gesondert nach Berechtigung behandeln.
- **Keine horizontalen Scrollleisten** in Arbeitsmasken. Bei knapper Breite wechseln Datentabellen in beschriftete Karten, statt Inhalte abzuschneiden.

## Bestandsprüfung – tatsächlicher Nachweisgrad

| Modul | Quellstand / vorhandene Funktion | Automatischer Nachweis | Echter angemeldeter Test auf Testdomain |
| --- | --- | --- | --- |
| Startseite / Dashboard | Filament Dashboard + Cockpit mit manuell ausgelösten JTL-/KI-API-Proben; keine automatische bezahlte Abfrage beim Öffnen | Laravel Featuretests für GET-Gates und Auth existieren | **offen** |
| Artikelmanager `/admin/artikel` | EAN/HAN/SKU-Suche aus Legacy-JTL-Spiegel, Anzeige Originalquellen, Links zu bisherigen Funktionen | Artikel-/Quellen-/Authtests vorhanden | **offen**, reale JTL-Snapshot-Frische prüfen |
| KI und Amazon `/admin/intelligence` | Manuelle Amazon-Katalog/Preis-Abfragen und KI-Archiv-GET; gebührenpflichtige KI durch bestätigte Freigabe gated | Fake-HTTP-/Sicherheits-Featuretests vorhanden | **offen**, private API-Rechte/Live-Reaktionen ungeprüft |
| Lager + Preise `/admin/lager-preise` | WMS-Lagerfach-/Bewegungsquellen, Kanalpreisseparation und Amazon-Händlerübersicht | WMS- und Preisboard-Featuretests vorhanden | **offen**; geprüft werden müssen tatsächliche Bestände/Bewegungen |
| WMS-Bewegungshistorie | Getrenntes Livewire-Panel; vorher zweispaltig mit starrer Mindestbreite 690px | **Quellfehler bestätigt**; responsive Layout und CI-Regressionstest im neuen Branch | **offen**, nach Test-Deploy Desktop + Mobile prüfen |
| Scanliste `/admin/scanliste` | Vorhandene Legacy-Scan-Stapel lesen, zusätzliche Schreibaktionen nur nach privater Freigabe | Lese-/Schreibfreigabe-Fake-Tests vorhanden | **offen**, bestehende Scan-Stapel ohne Änderungen kontrollieren |
| JTL-Ameise | Bestehende Prüfdateien/alte Module verlinkt, kein neuer Direktimport | Contracttests für CSV-/Freigabegates vorhanden | **offen**, kein Import freigeben |
| Amazon, eBay, Shopify-Kanalpreise | Quellen nach Preisart/Konto/Stand trennbar; fehlende verifizierte eigene Angebote werden nicht als aktiv dargestellt | Preisbeobachtungs-/Trennungstests vorhanden | **teilweise/weiter offen**, tatsächliche Kanal-API-Aufrufe separat abnehmen |
| Kundenservice `/admin/kundenservice` | JTL-Tracking-Snapshot, DHL-Verlauf, manuelle Vorlage und optionaler KI-Entwurf in PR #49 | Isolierte CI-/Featuretests zuletzt grün | **nicht deployed, daher nicht live-funktionsgeprüft** |
| Paketstatus & Track & Trace | Tatsächliche JTL-Tabellen-/View-Relation inkl. Filialadresse erfolgreich vom Betreiber abgefragt | Read-only SQL-Join belegt, Staging-Smoke/Test vorhanden | **privater End-to-End-Snapshot-Import und laufender Sync fehlen** |
| Einkauf / Ankauf | Draft-Code im parallelen Branch #46; vorhandene Scanliste als Mengengrundlage | Regel-/Quelltests existieren | **nicht produktiv integriert**, DB-/Realabnahme offen |
| Startkacheln + individuelle Sichtbarkeit | Fachlich beschlossen | Noch kein UI-/Benutzerpräferenztest | **noch nicht implementiert** |
| Amazon-Kundennachrichten versenden | Kein autonomer Versand; nur Copy/Manual, Messaging-Berechtigung noch zu prüfen | Absicherungs-Gates vorhanden | **noch nicht implementiert** |

**Achtung:** „Featuretest vorhanden“ bedeutet ein reproduzierbares Code-/Mock-Szenario, nicht automatisch eine erfolgreiche Verbindung zur aktuellen privaten Testdomain.

## Behobener konkreter Darstellungsfehler

Die starre Kombination aus `min-width:690px`, `overflow:auto` und einem nur halben Panel erzeugte horizontales Scrollen in der WMS-Historie. Diese Konstruktion wurde im neuen Branch ersetzt:

- WMS-Bewegungshistorie: **volle Grid-Breite**.
- WMS-Lagerplätze: Originalwerte, Sperrkennzeichen und Bewegungsdaten bleiben erhalten.
- Bei begrenzter **Panelbreite** (nicht nur Bildschirmbreite) werden Tabellen in beschriftete, umbruchfähige Datenkarten umgewandelt.
- Scan-Stapel, Scanpositionen, JTL-Artikel-Suchergebnisse und SQL-Originalwerttabelle erhalten dieselbe Behandlung.
- Zeitstempel, Lagerorte, Artikel-IDs, EAN, Mengen und KI-Archiv-Marker werden angezeigt, nicht ausgeblendet.
- Keine allgemeine `body { overflow-x:hidden }`-Kaschierung, die Fehler unsichtbar macht.
- Statische Regression `tests/hw24-responsive-no-horizontal-scroll.test.cjs` prüft sieben UI-Vorlagen, sechs Tabellen, Label-Vollständigkeit, keine erzwungenen breiten Mindesttabellen und keine horizontalen Scrollbereiche.
- **CI allein kann keinen Pixel-/Browser-Beweis liefern.** Browseransichten müssen separat mit echtem Login und Daten getestet werden.

## Abnahme im Browser – nachdem der isolierte Code sicher auf TEST installiert ist

1. Mit autorisiertem Testbenutzer anmelden; Dashboard und alle vorhandenen Menüpunkte anklicken. Unter 320, 375, 768, 1024 und 1440 Pixel Viewport sowie geöffneter Filament-Sidebar prüfen.
2. Auf `/admin/lager-preise` eine bekannte gültige EAN laden. Lagerplätze und Bewegungshistorie öffnen. Alle WMS-Zeilen einschließlich **Von/Nach**, **Buchungsart**, **Menge**, **Zeitpunkt** und **Quell-ID** müssen ohne horizontalen Scroll sichtbar und lesbar bleiben.
3. `/admin/scanliste`: bestehende Stapel und deren Positionen lesen, KI-Archivmarker prüfen. **Keine Schreibaktion**, wenn private Freigabe deaktiviert ist.
4. `/admin/artikel`: bekannte EAN suchen, Originalwerte/weitere Quellen blättern; prüfen, dass längere Codes und Datentexte umbrechen und es keine seitlichen Scrollleisten gibt.
5. `/admin/intelligence`: kostenlosen Archivabruf und Amazon-Katalog separat auslösen; Netzwerkantwort, Quellstand, Datenfrische und Fehlermeldungen kontrollieren. **Keine gebührenpflichtige KI ohne gesonderte Zustimmung.**
6. `/admin/kundenservice` erst nach gesondertem TEST-Deployment und privatem Tracking-Snapshot öffnen; Paketlauf ohne Versand und ohne personenbezogene Beispiel-Screenshots in öffentlichen Logs prüfen.
7. Für jede Seite im Browser mit Entwicklertools `document.documentElement.scrollWidth <= document.documentElement.clientWidth` sowie lokale Tabellen/Karten auf Überlauf prüfen. Das ist ein **durchzuführender Test**, kein bereits erzieltes Ergebnis.
8. Negativtests: abgelaufene Snapshots, fehlender API-Zugang, kein Lagerplatz, leere KI-Archive, keine Amazon-SKU, mehrere Treffer, Testbenutzer ohne Rechte; System muss klar und ohne Datenänderung reagieren.

## Nicht automatisch erledigt

- Kein authentifizierter Browserzugang zu Testdomain in dieser Sitzung; der Browser-Connector konnte die vorhandene Browser-Session nicht auslesen, öffentlicher URL-Zugriff ist geschützt.
- Kein Plesk-/Windows-/SQL-Server-Test durchgeführt, keine Live-Domain oder produktive Datenbank geändert.
- Kein GitHub-Commit ist eine TEST-/LIVE-Abnahme. CI nach letzter Änderung muss auf aktuellem Commit verifiziert werden.
- Keine neue Domain, Datenbank, JTL-API oder Marktplatzfunktion automatisch freigeschaltet.

## Freigabegates

A. Responsive-Regression + vorhandene Laravel/Node/PHP-Tests grün.  
B. Privater Test-Deploy mit Backup, Rollback, authentifiziertem E2E, echten Testartikeln/Sendungen.  
C. Befunde im kanonischen `docs/HW24_ARBEITSJOURNAL.md` und `docs/HW24_PROJEKTSTATUS_AKTUELL.md` nachführen.  
D. Erst danach nächste Modul-/Kachelfunktionen integrieren; keine separate Hub-Domain bis zur Gesamtabnahme.
