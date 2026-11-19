# HW24 Artikelmanager v4.6 TEST: EAN-First-Arbeitsplatz

## Ziel
Im Alltag nur **eine** EAN-Eingabe, **eine** editierbare Stammdatenübersicht
mit Herkunft/Alter der Informationen und **eine** Aktion für die
JTL-Ameise-Prüf-CSV. Das bisherige System bleibt technisch erhalten:
JTL-Status, alte Detailmodule, Massenpflege, Kategorisierung, Profilverwaltung,
Namensnormalisierung, separate Lagererfassung und Originalquellen können
in den optionalen Bereichen geöffnet werden.

## Technische Änderungen
- \`assets/hw24-workdesk.js\` und \`assets/hw24-workdesk.css\`: kompakte
  Hauptansicht, EAN-Feld und bestehender Scan-Button werden mit ihren
  vorhandenen Event-Handlern in die neue Ansicht **verschoben**.
- Beim Klick auf "Artikel prüfen" wird genau eine Abfrage gestartet:
  die frühere zusätzliche Abfrage beim Verlassen des EAN-Feldes ist deaktiviert.
  Ein Scanner mit Enter als Abschluss funktioniert weiterhin.
- Die bestehende \`search()\` startet JTL- und Historienabfrage
  **parallel**; \`hw24-smartscan.js\` führt sie nach Abschluss zusammen.
- Fehlende Felder der gewählten Exportvorlage werden nach oben gezogen,
  die anderen sind in "Weitere Felder" abrufbar.
- Änderungen der sichtbaren Felder aktualisieren den bestehenden
  lokalen Entwurf und markieren sie als manuell bearbeitet.
- Zeitangabe der JTL-Quelle stammt vom tatsächlichen Snapshot.
  Historische Excel-Daten sind **nicht live** und dürfen nicht mit einem
  erfundenen Änderungsdatum pro Wert versehen werden.
- Historische, eindeutig zugeordnete Textwerte dürfen nur leere Felder
  des lokalen Entwurfs ergänzen und werden mit Quelle gekennzeichnet.
  **Ungeprüfte historische Vorschläge werden nicht exportiert.**
  Die Quellenfreigabe ist für alle Vorschläge eine einzige Aktion.
- Eigene Vorlagen wählen die Exportspalten; bislang weiterhin
  **lokaler Browserspeicher**, kein serverseitiges Mehrbenutzerprofil.
- Bis zu 100 eindeutig identifizierte weitere Bestandsartikel können
  mit **ihren bestätigten Änderungen** in derselben Prüf-CSV stehen.
  Diese Warteschlange ist zunächst **nur in der Browser-Sitzung** vorhanden.
  Eine automatische filterbasierte JTL-Sammelabfrage ist noch nicht gebaut.
- Originale Detailmodule bleiben über "Weitere Funktionen" nutzbar.
  Optionaler Quellenkatalog zeigt vorhandene JTL-Rohwerte bzw. IDs an,
  **keine erfundenen Klartextwerte**.

## Noch nicht gelöst / getrennte Quellarbeiten
- Herstellername: nur ein UPDATE des Windows-PowerShell-Synchronisierers
  mit bestätigtem \`dbo.tHersteller\`-Schema und SELECT-Berechtigung
  kann \`hersteller\` im JTL-Spiegel füllen. Plesk-Deployment allein reicht nicht.
- Feld "Kosten": nicht automatisch \`fEKNetto\` (gleitender Durchschnitt)
  oder \`fLetzterEK\` (letzter Einkaufspreis) zuordnen.
  Echte JTL-Quelle/Lieferantenpreis zunächst per SQL lesen und verifizieren.
- Weitere JTL-Tabellen (Kategorieklartexte, Lieferanten, Maße,
  WMS-Lagerplätze und -Mengen, Plattformzuordnungen, Bilder und Attribute)
  sind nicht vollständig angebunden.
- Feldkatalog nach Import der 872-Spalten-CSV zeigt auch noch nicht
  zugeordnete Felder an, gibt diese aber **nicht blind** für Ameise frei.
- Kein JTL-Schreibzugriff, kein automatischer Ameise-Import, keine
  WMS-Buchung, keine automatische Online-Freigabe.

## Bilder (Ausbau nach Artikelstammdaten-Prüfung)
JTL-Wawi kann Bilder per Drag and Drop oder URL importieren; JTL-Ameise
kann im Bilderimport sowohl lokale Pfade als auch **öffentlich erreichbare**
HTTP(S)-URLs anhand einer eindeutigen Artikelnummer verwenden.
Die Bilder werden nach dem Import in der Wawi-Datenbank gehalten.

Empfohlen zuerst: **eigene Produktfotos**, Bildbearbeitung ohne
Verzerrung, Import über separate Ameise-Bildervorlage. Für häufige
mobile Uploads kann später ein geschützter Speicherbereich außerhalb
des öffentlich ausführbaren Webroots plus ein isolierter
Bildbereitstellungs-/Importdienst ergänzt werden. Kein öffentlicher
anonymer Upload. Bildgrößen und Portalanforderungen müssen pro
Ausgabeweg verifiziert werden. Möglichst 1600 px auf einer Seite
(als Basis für Zoom), für quadratische Hauptbilder weißen Rand statt
Packung/Etikett abzuschneiden. Vorhandene Bilder bei Aktualisierung
nicht automatisch entfernen; Bildrechte prüfen.

Quellen:
https://guide.jtl-software.com/jtl-wawi/jtl-ameise/artikelbilder-importieren/
https://guide.jtl-software.com/jtl-wawi/artikel/bildgroessen-bearbeiten/
https://guide.jtl-software.com/jtl-wawi/artikel/artikelstammdaten-bilder/

## Sicherer Test
1. Test-Subdomain in Plesk weiterhin mit HTTPS und Passwort sichern.
2. In Plesk Git auf dem Branch
   \`improve/encoding-dashboard-sync-20261115\` erst Pull, dann Deployment.
3. Testoberfläche zeigt **v4.6 TEST**.
4. EAN \`4015600040482\` mit ENTER und Button jeweils einmal testen.
   Kein doppelter Scan; JTL 1 Treffer, Snapshot-Datum/Alter korrekt.
5. Prüfen, ob Hersteller leer bleibt, solange Hersteller-Sync nicht
   wirklich auf Windows umgestellt wurde. Nicht aus \`kHersteller=1\` raten.
6. Historische Ergänzungen prüfen und einmal gesammelt freigeben.
   Test mit widersprüchlichen historischen Quellen darf nicht ungefragt
   Felder überschreiben.
7. Direkt in der Hauptmaske ein zulässiges Textfeld ändern; CSV
   herunterladen, Header/Artikelnummer/Inhalte vergleichen.
8. Weitere Artikel mit bestätigten Änderungen zur Liste hinzufügen,
   alle Zeilen in der gleichen Ameise-Prüf-CSV verifizieren.
9. JTL-Ameise zuerst über ein gespeichertes Importprofil **trocken testen**.
   Leere Textwerte nicht überschreiben, Bestände/Bilder nicht löschen.
10. Mobile Ansicht testen; Browser-Konsole und Plesk-Logs auf Fehler prüfen.
11. Erst nach schriftlicher Freigabe in \`main\` mergen. Nicht automatisch
    die produktive Plesk-Seite aktualisieren.
