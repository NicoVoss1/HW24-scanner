# HW24 Artikelmanager · Projektstand 14.11.2026

## Zielbild
1. Artikel mit EAN / GTIN scannen.
2. Frischen JTL-Spiegel auf Artikelnummer, GTIN, HAN, ASIN prüfen. Bei fehlender GTIN nie allein aus einer leeren EAN-Suche schließen, dass der Artikel wirklich neu ist.
3. Bereits vorhandenen Artikel mit Stammdatenqualität vergleichen: Grün = von aktueller Quelle bestätigt oder plausibel gepflegter Offline-Export (Quelle deutlich benennen); Orange = Vorschlag oder unbestätigt; Rot = fehlend, ungültig oder widersprüchlich.
4. Historische Excel-Recherche, gespeicherte Amazon-Verknüpfungen, ähnliche gepflegte JTL-Produkte und nachrichtlich nachvollziehbare externe Recherche als Vorschläge zusammenstellen.
5. Fehlende Felder mit einem Klick aus belastbaren Vorschlägen ergänzen, kritische Kennungen/Preise ohne belegte Quelle offen zur Prüfung lassen.
6. Eine Hauptimport-CSV (Artikelstammdaten) für Neuanlage oder Aktualisierung erzeugen. Weitere Ameise-Dateien nur, wenn JTL getrennte Importprofile für Attribute/Merkmale/Bilder etc. verlangt.

## Ausgangsbestand
JTL-Export-Artikelstammdaten-14112026.csv: 2691 Artikel, 872 Spalten.
- Gepflegte Friseurbedarf-Kategorie: 1928 Artikel (71,6%), gute Referenzgruppe.
- Kategorie 1 Amazon-Import-mit-Bestand: 750 Artikel (27,9%).
- 710 der Amazon-Importartikel ohne GTIN, 709 ohne Hersteller, 712 mit Kosten=0.
- 2690 Artikel mit Überverkäufe möglich=N, EIN Artikel mit Y. **NEIN ist zwingend der Default**.
- Die 13 JTL-Kartenreiter sind im App-Überblick abgebildet; Originalexport enthält keine separaten Bild-, Attribut-/Merkmals-, Variations- oder Datei-Datensätze.
- Einzelartikel Beispiel EAN 4015600040482: Quelle hat Kosten 1,50 €, Brutto-VK 14,99 €, 1 auf Lager, 1 in Aufträgen, 0 verfügbar, Wella, Friseurbedarf/Haarfarben, Überverkäufe=N. UI-Screenshot vom 14.11.2026 zeigt abweichend "Artikel inaktiv"-Checkbox ungekreuzt; dies muss der aktuelle JTL-Stand klären, nicht durch CSV-Vermutung überschrieben werden.

## Code-Status: GitHub v4.0
- [x] Korrektur Überverkäufe: Standard N, Y nur nach manueller Dialogbestätigung.
- [x] JTL-CSV-Import mit sichtbarer Importfunktion und Fehlermeldungen.
- [x] Historische MariaDB-Recherche unabhängig vom JTL-API-Status (Code).
- [x] Hauptimport-Entwurf für JTL-Ameise.
- [x] Tatsächliches Kosten-Feld statt GLD-Verwechslung; Live-Lagerdaten schreibgeschützt.
- [x] Geprüfte Artikelnummern bei Bestandsartikeln erhalten; Nummernvergabe nur bei bestätigten Neuanlagen und nach JTL-Ameise-Test.
- [x] Grün/Orange/Rot-Feldprüfung und Ableitungen aus gepflegter Referenzgruppe.
- [x] Sichtbare Projektübersicht und Übersicht aller 13 JTL-Kartenreiter.
- [x] Immer verfügbare Quellbezeichnungen, getrennte Kategorie- und Marktplatzdaten.
- [ ] Live-Bereitstellung auf artikelmanager.hairworld24.de nach Plesk-Git-Pull/Deployment bestätigen.
- [ ] MariaDB-Tabellen HW24_06 + HW24_07 + HW24_09 auf echtem Server ausführen und verifizieren.
- [ ] Separaten MariaDB-Synchronisationsbenutzer mit eingeschränkten Rechten einrichten.
- [ ] JTL-Wawi-DB ausschließlich lesend über Windows-Tool aufnehmen und Sync-Token privat ablegen.
- [ ] Frischen JTL-Spiegel vollständig synchronisieren; EAN, HAN, Artikelnummer, ASIN inklusive Mehrdeutigkeiten prüfen.
- [ ] Aktuelle Hersteller-Klarnamen, Lagerorte, verfügbare/reservierte Mengen aus verifizierten JTL-Tabellen ergänzen.
- [ ] Entwürfe/Änderungen serverseitig mit gesichertem Zugriff und vollständigem Audit persistieren.
- [ ] Erweiterung für herstellerspezifische Attribute, Amazon-Produkttyp/Browse-Node-Mapping und Bildquelle.
- [ ] Ameise-Importprofil an JTL Wawi 2.2.2.0 mit genau einem nichtkritischen Testartikel prüfen; erst danach produktive Sammelimporte.

## Reihenfolge nächster Arbeitsschritte
1. Plesk Git: Jetzt Pull ausführen → Jetzt bereitstellen → https://artikelmanager.hairworld24.de/ prüfen (v4.0).
2. JTL-CSV 14.11.2026 laden, Artikel EAN 4015600040482 und 2 mangelhafte Amazon-Importartikel mit Ampel prüfen. Keine Echtänderung.
3. MariaDB-Backup, Tabellenstatus lesen: sql/HW24_08_MARIADB_STATUS_PRUEFEN_LESEND.sql. Fehlende Tabellen anlegen: HW24_06 und HW24_07. Falls überverkäufe=Y als DB-Default gespeichert wurde, HW24_09 ausführen.
4. MariaDB-Schreibbenutzer separat absichern; hw24_app bleibt SELECT.
5. JTL SQL-Server read-only service account + Powershell sync prüfen (Konfiguration/Token dürfen nie ins öffentliche GitHub).
6. Zentral gespeicherten Entwurf, Änderungshistorie und JTL-Ameise-Importversuch testen.

**Statusdisclaimer:** Code im GitHub ist nicht automatisch auf Plesk oder in der MariaDB installiert. Die v4.0-App kann vor Server-Deployment offline getestet werden, aber nicht mit einer noch nicht konfigurierten Live-Synchronisation.
