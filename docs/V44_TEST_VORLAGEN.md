# HW24 v4.4 Testumgebung: Quelle, Namen und Ameise-Vorlagen

Die Schnellansicht in `assets/hw24-vorlagen.js` ist **nur auf dem Entwicklungsbranch**.
Die vorhandenen APIs und die SQL-Server-Synchronisation werden dadurch nicht verändert.

## Neu im Test
- Ausklappbarer Vorlageneditor mit Feldsuche. Der Katalog enthält alle bereits
  im Ameise-Prüfexport unterstützen Spalten und nach optionalem JTL-CSV-Import auch
  dessen Quellspalten.
- Noch nicht zugeordnete CSV-Felder werden sichtbar als *nicht exportierbar*
  gekennzeichnet. Eine Exportspalte ist erst nach realem JTL-Ameise-Mapping freizugeben.
- Ein Profil bestimmt die tatsächlich in die einzelne Ameise-Prüf-CSV geschriebenen
  Spalten. Identitätsfelder sind geschützt, vorhandene Datensätze brauchen weiterhin
  eine eindeutige Artikelnummer. Der Import muss weiterhin getestet werden.
- Vorlagen sind vorläufig lokal im Browser gespeichert (localStorage), noch nicht
  benutzerübergreifend auf dem Server. Kein automatischer JTL-Import.
- Der Artikelnamen-Editor bietet die Reihenfolge Hersteller, Produktlinie,
  Nuancennummer, Farbname und ` - (60 ml)`; nur nach manuellem Bestätigen
  wird der lokale Artikelentwurf angepasst, nicht die JTL-Datenbank.

## Datenquellen, die noch fehlen
- Aktueller PowerShell-Sync setzt `hersteller=$null`, da `kHersteller` nur
  eine ID ist. Erst reale Hersteller-Tabelle/-Spalten prüfen, dann den
  Windows-Synchronisierer und ggf. dessen SQL-Leserechte erweitern.
- `fEKNetto` ist Ø EK; `fLetzterEK` samt Datum sind letzte Buchung.
  Eigenständiges Feld `Kosten` bzw. Lieferanten-EK erst in JTL auflösen.
- WMS-Lagerplätze, reservierte/verfügbare Mengen, Kategorien, Preisgruppen,
  Attribute, Merkmale sowie gebuchte Bestände sind gesonderte Quellen.

Verifikation: `sql/JTL_07_HERSTELLER_EK_QUELLEN_LESEND.sql` auf dem Windows-Server
nur lesend ausführen. Kein produktiver Ameise-Import, bevor ein Testartikel und
sein Importprofil validiert sind.

## Deployment
Test-Subdomain zieht den Entwicklungsbranch.
Nach Prüfläufen: PR nach `main` mergen und produktive Plesk-Git-Bereitstellung
ausführen. Änderungen am PowerShell-Sync benötigen ein gesondertes Windows-Update.
