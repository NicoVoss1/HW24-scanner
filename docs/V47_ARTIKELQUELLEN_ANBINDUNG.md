# HW24 v4.7 TEST · Artikelbezogene JTL-Tabellen sicher anbinden

## Zweck
HW24 soll fehlende Artikelstammdaten aus JTL-Wawi 2.2.2.0 (SQL Server
\`eazybusiness\`) mit klarer Datenherkunft ergänzen. Der Browser greift NICHT
direkt auf SQL Server zu. Der Windows-Synchronisierer liest JTL im internen
Netz, überträgt den vollständigen Snapshot per HTTPS an die bestehende
MariaDB und der Artikelmanager fragt den Snapshot schnell ab.

**Nur Entwicklungsbranch.** Kein Merge nach \`main\`, keine JTL-Schreibzugriffe.

## Was aus der Artikelhaupttabelle bereits vorliegt
\`dbo.tArtikel\` und \`dbo.tArtikelBeschreibung\` werden durch die
Windows-Synchronisation ausgelesen. \`fEKNetto\` ist der gleitende
Durchschnitts-EK und \`fLetzterEK\` der letzte gebuchte EK, nicht das
separat im JTL-Export verwendete Feld "Kosten". \`fAmazonVK\`, \`cASIN\`
und \`fEbayPreis\` kommen bereits aus \`tArtikel\`; sie ersetzen
keine Abfrage des Amazon-Angebots- oder Marktplatzstatus.

## Welche Quellen wir verifizieren
| Bereich | Bereits gesichert | Noch zu ermitteln |
| --- | --- | --- |
| Hersteller | tArtikel.kHersteller | Referenztabelle und Klarname, SELECT-Rechte |
| Einkauf | tArtikel.fEKNetto, fLetzterEK, dLetzterEK | Separates Feld "Kosten", Lieferanten-EK und Zuordnung |
| Amazon | tArtikel.cASIN, fAmazonVK | Angebote, Seller-SKU, B2B/B2C und ggf. Plattformpreise |
| Warengruppen/Kategorien | tArtikel.kWarengruppe | Warengruppen- und Kategoriebezeichnungen sowie Zuordnung |
| Steuer/Versand | kSteuerklasse, kVersandklasse | Klarnamen/Regeln, keine Ersetzung durch ID |
| Lieferant | noch keine verifizierte Relation | Standardlieferant, Preise, Artikelnummer des Lieferanten |
| Attribute/Merkmale | tArtikelAttribut/tArtikelMerkmal vorhanden | Tatsächliche Zuordnungstabellen und Werte |
| WMS | tArtikel.nLagerbestand | Lagerbestand je Lagerplatz, verfügbar/reserviert |

Die Tabelle \`dbo.tHersteller\` mit den Spalten \`kHersteller,cName\` ist
**ein Kandidat**, keine ohne Abfrage verifizierte Struktur. Bereits im
Synchronisierer vorbereitet: wenn sie existiert und gelesen werden darf,
wird der Name in das bisherige Feld \`hersteller\` übernommen; andernfalls
bleibt es leer.

## Was wurde im GitHub-Branch bereits geändert?
- \`sync/HW24_JTL_Synchronisieren.ps1\`: Herstellerauflösung (nur bei
  verifizierter Struktur); optionaler Aufruf weiterer freigegebener Quellen.
- \`sync/HW24_JTL_Zusatzquellen.ps1\`: konfigurierbare read-only Abfrage
  für höchstens acht verifizierte Tabellen. Nur angegebene Spalten, maximal
  drei Originalzeilen pro Schlüssel und sechs Quellfelder je Tabelle.
  Ungültige/nicht vorhandene Namen führen zum Abbruch VOR dem Snapshot.
  Eine gekürzte Trefferliste wird als \`truncated\` markiert.
- \`api/jtl.php\`: bereits verfügbare Amazon/Ebay-Basiswerte im
  Feldkatalog und optionale verifizierte JTL-Daten als \`related_sources\`.
- \`assets/hw24-workdesk.js\`: Zusatzquellen nur unter
  "Weitere Funktionen und technische Details → Weitere verfügbare
  JTL-Quellfelder ansehen", damit der EAN-Hauptbildschirm schlank bleibt.
- \`assets/hw24-workdesk.css\`: gut sichtbare weiße Versionsmarkierung.
- \`sql/JTL_08_ARTIKEL_QUELLINVENTUR_LESEND.sql\`: reine
  Metadaten- und Testartikel-Abfragen für die echte SQL-Server-Struktur.
- \`sync/jtl-related-sources.example.json\`: **alle optionalen Quellen
  zunächst deaktiviert**. Keine geratenen Tabellen automatisch starten.

## Einmalige manuelle Admin-Schritte

### A. Auf dem Windows-Rechner mit JTL-Wawi
1. SQL Server Management Studio (SSMS) starten und mit der Instanz
   verbinden, auf der \`eazybusiness\` liegt.
2. In GitHub die Datei
   \`sql/JTL_08_ARTIKEL_QUELLINVENTUR_LESEND.sql\` öffnen, den
   gesamten SQL-Text kopieren, in einem neuen Abfragefenster einfügen.
3. Abfrage ausführen. Es sind nur \`SELECT\`-Abfragen enthalten.
4. Die fünf Ergebnisraster (Tabellen, Spalten, Fremdschlüssel,
   Wella-Testartikel und Hersteller-Spalten) als CSV bzw. Screenshot
   speichern. KEINE Zugangsdaten oder kompletten Kundendatensätze teilen.

### B. Danach konkrete Zuordnung von JTL zu HW24
Die fünf Ergebnismengen werden benutzt, um **echte** Tabellen,
JOIN-Spalten, Wertefelder und Kardinalitäten festzulegen. Beispiele:
Hersteller-ID → Name, Artikel-ID → Lieferantenpreise,
Artikel-ID → Amazon-Angebotsdaten, Artikel-ID → Kategorien.
Es darf keine ungeprüfte 1:n-Beziehung als eindeutiger Artikelwert
übernommen werden.

### C. Windows-Synchronisationsdateien nach Prüfung aktualisieren
Die Änderungen an Plesk/GitHub installieren KEINE PowerShell-Dateien
auf Deinem Windows-Server. Dort müssen die aktualisierten Dateien
\`sync/HW24_JTL_Synchronisieren.ps1\` und
\`sync/HW24_JTL_Zusatzquellen.ps1\` in das vom Aufgabenplaner
benutzte, versionierte Verzeichnis gelangen. Empfehlenswert ist
künftig ein separates Git-Checkout auf dem Windows-Rechner
(mit kontrollierter Freigabe statt manueller Dateikopien).

Nach finaler Feldprüfung wird dort eine lokale Datei
\`C:\HW24\jtl-related-sources.json\` angelegt, **falls**
\`C:\HW24\jtl-sync.json\` bereits im selben Verzeichnis liegt.
Alternativ kann in der lokalen Sync-Konfiguration
\`"related_sources_file":"C:\\HW24\\jtl-related-sources.json"\`
hinterlegt werden. Die Datei gehört NICHT in GitHub und enthält
keine Zugangsdaten.

Der Inhalt ist anfangs:
\`\`\`json
{"sources":[]}
\`\`\`
Erst nach Bestätigung der realen Schemaabfrage werden pro Quelle
die konkreten Felder ergänzt (max. acht). Schlüssel:
\`id\`, \`schema\`, \`table\`, \`article_column\`,
\`table_column\`, \`fields\` (1 bis 6 Spalten).
Beispiel NUR falls \`dbo.tHersteller(kHersteller,cName)\`
aus Ergebnis 05 bestätigt wurde:
\`\`\`json
{
  "sources": [{
    "id": "hersteller_pruefung",
    "schema": "dbo",
    "table": "tHersteller",
    "article_column": "kHersteller",
    "table_column": "kHersteller",
    "fields": ["cName"]
  }]
}
\`\`\`
Der Hersteller wird zusätzlich über das schon bestehende
\`hersteller\`-Feld aufgelöst; die optionale Quelle dient hier
nur dem Abnahmetest, nicht als zwingendes zusätzliches Mapping.

### D. Rechte
Auf **SQL Server \`eazybusiness\`** braucht der für die
Windows-Synchronisation genutzte Benutzer \`SELECT\` auf den
betreffenden JTL-Tabellen. Schreibrechte auf JTL sind NICHT nötig.
Die Berechtigungen des MariaDB-Benutzers sind ein anderes Thema:
Der vorhandene \`hw24_sync\` schreibt nur in die bisherigen
Mirror-Tabellen, \`hw24_app\` liest den Spiegel. Für den vorliegenden
Zusatzquellen-Mechanismus sind keine neuen MariaDB-Tabellen nötig;
zusätzliche geprüfte Felder landen innerhalb von \`raw_json\`.
Keine globalen Datenbankrechte vergeben.

### E. Testen und freigeben
1. Erst Backup der vorhandenen Windows-Sync-Skripte und Datenbank.
2. Windows-Synchronisierer unter dem vorgesehenen Konto mit
   vorhandener verschlüsselter Konfiguration einmal kontrolliert
   ausführen; bisherigen Task ggf. für den Test anhalten, nicht parallel.
3. Testdomain \`/api/jtl-status.php\` muss weiterhin einen
   vollständigen frischen Snapshot zeigen.
4. Test \`/api/jtl.php?ean=4015600040482\`: Herstellerklarname
   erst als JTL-Daten ausgeben, wenn der SQL-Join korrekt ist.
   Neue Zusatzquellen erscheinen unter \`related_sources\`;
   bei noch leerem JSON-Mapping ist \`related_sources: {}\` korrekt.
5. Ein einzelner unbekannter/mehrdeutiger Datensatz darf keine
   automatischen Preise, Kosten, ASIN, Lagerbestände oder Hersteller
   erzeugen. Historische Excel-Werte sind gesondert gekennzeichnet.
6. In der Testoberfläche nur bei ausdrücklich freigegebenem
   Ameise-Feldmapping Werte zum Export zulassen.
7. JTL-Ameise-Trockenlauf mit 1-3 Artikeln, Importbericht prüfen,
   erst danach Pull Request freigeben und \`main\` bereitstellen.

## Was ich in GitHub erledigen konnte
Code, Leselogik, Quellinventur, API, Version und Dokumentation.

## Was ich NICHT selbst aus GitHub ändern kann
- Windows SQL Server direkt auslesen / reale SQL-Ergebnisse bestätigen
- SQL Server SELECT-Rechte vergeben (Admin-Aktion)
- Windows-Synchronisierer installieren und geplante Aufgabe aktualisieren
- Plesk-Git bereitstellen oder geschützte Testwebsite ohne Zugriff prüfen
- Den produktiven JTL-Ameise-Import ausführen

Diese Aktionen benötigen den Adminzugriff auf Deine Systeme. Vorliegen
der verifizierten Tabellenergebnisse ist der entscheidende nächste Schritt.
