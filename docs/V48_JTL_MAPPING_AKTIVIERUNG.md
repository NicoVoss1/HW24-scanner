# HW24 v4.8 TEST: 33 Tabellen / 452 Spalten aus SQL09 verifiziert

Quelle: vom Nutzer am 17.11.2026 hochgeladene Datei
\`1_Artikelmanager DB Mapping.csv\` (SQL09-Ergebnis: 452 Spalteneintraege,
33 Tabellen, 15 deklarierte FK-Spaltenbeziehungen).

## Konkrete bestaetigte Beziehungen

| Artikelbereich | Link / Felder im echten SQL09-Ergebnis | Verwendung |
|---|---|---|
| Hersteller | dbo.tArtikel.kHersteller = dbo.tHersteller.kHersteller, Name dbo.tHersteller.cName | Herstellername im JTL-Snapshot |
| Kosten | Artikel.tArtikelkosten.kArtikel -> dbo.tArtikel.kArtikel, fArtikelkosten | Eigenstaendige JTL-Artikelkosten, getrennt von fEKNetto / fLetzterEK |
| Warengruppe | dbo.tArtikel.kWarengruppe = dbo.tWarengruppe.kWarengruppe, cName | lesbarer Warengruppenname |
| Steuerklasse | dbo.tArtikel.kSteuerklasse = dbo.tSteuerklasse.kSteuerklasse, cName | Klassenname, nicht 19%-Satz erfinden |
| Versandklasse | dbo.tArtikel.kVersandklasse = dbo.tVersandklasse.kVersandklasse, cName | lesbare Versandklasse |
| Mengeneinheit | dbo.tArtikel.kMassEinheit = dbo.tMassEinheit.kMassEinheit, cCode/cDisplayCode | Einheit, keine freie Umrechnung |
| Lieferantenartikel | dbo.tliefartikel.tArtikel_kArtikel -> dbo.tArtikel.kArtikel, fEKNetto/fEKBrutto/cLiefArtNr/nStandard/tLieferant_kLieferant | mehrere Lieferantenartikel moeglich; NICHT automatisch als Kosten verwenden |
| Lieferantenpreise | dbo.teklieferantpreise.kArtikel + kLieferant, Mengen und Preise | mehrere Preisstaffeln moeglich |
| Bestand | dbo.tlagerbestand.kArtikel, fLagerbestand/fVerfuegbar/fInAuftraegen | read-only Anzeige, KEINE Bestandsbuchung |
| Kategoriezuordnung | dbo.tkategorieartikel.kArtikel -> dbo.tArtikel.kArtikel, kKategorie | Kategorie-ID; Klartextname erfordert weiteren Join auf tKategorieSprache |
| Amazon-Zuordnung | dbo.pf_amazon_angebot_mapping.kArtikel -> dbo.tArtikel.kArtikel; cSellerSKU+kUser | zugeordnete Seller-SKUs, mehrfach moeglich |
| Amazon-Angebot | Mapping cSellerSKU+kUser = pf_amazon_angebot cSellerSKU+kUser; Angebot nPlattform; ext zusaetzlich nPlattform | getrennte Angebote nach Plattform, keine beliebige Preisuebernahme |
| Artikel-ASIN | Artikel.tArtikelAsin.kArtikel -> dbo.tArtikel.kArtikel; cAsin | zusaetzliche ASINs nur als Quellwerte |
| Verkaufskanaldaten | SCX.tVerkaufskanalArtikelData.kArtikel, kShopScx | kanalbezogene Attribute und Limits |

WICHTIG: FK-Metadaten bestaetigen nicht jede fachliche JOIN-Relation;
beispielsweise Amazon hat namensgleiche zusammengesetzte Schluessel,
aber in diesem Export keine FK-Deklaration. Testartikelwerte und
Kardinalitaeten muessen vor Aktivierung geprueft werden.

## Neu in GitHub (nur Entwicklungsbranch)

- \`sync/jtl-related-sources.verified.example.json\`: 12 konkrete,
  gegen SQL09-Spaltenmetadaten verifizierte Quellzuordnungen,
  optional mit Amazon-Angeboten (zusammengesetzter Schluessel).
  Die reine Herstellerauflösung erfolgt unabhaengig davon direkt
  im Windows-Sync nach Schema- und Berechtigungspruefung.
- \`sync/HW24_JTL_Zusatzquellen.ps1\`: 12 konfigurierbare
  Verknuepfungen plus optionaler read-only Amazon-Join.
  Maximal 3 Zeilen je normaler Beziehung und 5 Amazon-Angebote
  je Artikel; \`truncated=true\` signalisiert weitere Treffer.
  Keine Daten werden in JTL bearbeitet.
- \`api/jtl.php\`: \`kosten\`, \`warengruppe_name\`,
  \`steuerklasse_name\`, \`versandklasse_name\` und
  \`bestand_verfuegbar\` nur aus EINDEUTIGEN 1:1-Quellen,
  sonst \`null\`; mehrere Lieferanten/Amazon bleiben in
  \`related_sources\`. \`kosten_source\` benennt die Rohquelle.
- \`index.html\`, \`assets/ameise.js\`, \`assets/hw24-smartscan.js\`:
  bekannte, eindeutig zuordenbare Werte in den lokalen
  Artikeldatenentwurf uebernehmen, JTL als Quelle anzeigen und
  ueber die gewaehlte Ameise-Vorlage exportierbar machen.
- \`sql/JTL_10_WELLA_QUELLWERTE_LESEND.sql\`: ausschliesslich
  lesende Praxispruefung (Hersteller, Kosten, Lieferanten,
  Amazon, Kategorien) fuer EAN 4015600040482.
- Hauptansicht \`v4.8 TEST\` mit weiterhin gut lesbarem
  Versions-Badge.

## Was Du auf Windows einmalig erledigen musst

1. **Vor der Installation** SQL10 (Link oben) einmal in SSMS
   auf \`eazybusiness\` ausfuehren. Kontrollieren, dass
   Artikelkosten wirklich dem JTL-Exportwert "Kosten"
   entsprechen. Amazon-Join und mehrfache Lieferanten
   anhand der realen Ergebniszeilen pruefen. Ohne realen
   Datenvergleich fachlich nicht als 100% validiert betrachten.
2. Vorhandene, laufende **Windows**-Sync-PS1-Datei und
   konfigurierte Aufgabe sichern. Auf keinen Fall den
   Plesk-Sync-Empfaenger oder \`main\` aendern.
3. In den vom Aufgabenplaner benutzten Windows-Sync-Ordner
   \`HW24_JTL_Synchronisieren.ps1\` UND
   \`HW24_JTL_Zusatzquellen.ps1\` aus dem GitHub-Testbranch
   kopieren (Versionen muessen zusammenpassen).
4. Im VERZEICHNIS der privaten Windows-Konfiguration
   \`jtl-sync.json\` eine zweite Datei
   \`jtl-related-sources.json\` mit dem Inhalt von
   \`sync/jtl-related-sources.verified.example.json\` anlegen.
   Keine Zugangsdaten in diese neue Datei schreiben.
   Alternativ einen absoluten Pfad mit
   \`"related_sources_file": "C:\\\\...\\\\jtl-related-sources.json"\`
   in der vorhandenen Konfiguration angeben.
5. SQL-Benutzer, unter dem die Windows-Synchronisation
   laeuft, muss **SELECT** auf den zusaetzlichen 12 Tabellen
   sowie den drei Amazon-Tabellen haben. Falls Berechtigungen
   fehlen, gezielt \`GRANT SELECT\` nur fuer diese JTL-Tabellen.
   MariaDB-Schreibbenutzer bleibt unveraendert.
6. Den bestehenden 15-Minuten-Task fuer den Test pausieren
   (nicht zwei Vollsynchronisierungen parallel starten).
   Manuell EINEN vollstaendigen Lauf mit der vorhandenen
   verschluesselten Konfiguration und dem vorgesehenen Konto
   ausfuehren. Auf Warnungen, Payload-Groesse und
   Anzahl der fertig synchronisierten Artikel pruefen.
7. Die beiden Abfragen auf der **Testdomain** vergleichen:
   - \`/api/jtl-status.php\`: frischer, vollstaendiger Snapshot
   - \`/api/jtl.php?ean=4015600040482\`:
     \`hersteller\`, \`kosten\`, \`kosten_source\`,
     \`warengruppe_name\`, \`related_sources\`.
   Nach einem erfolgreichen Lauf kann der unveraenderte
   bisherige 15-Minuten-Task wieder gestartet werden.
8. Plesk-Testsubdomain: Git Pull und Bereitstellung des
   Branches \`improve/encoding-dashboard-sync-20261115\`;
   v4.8 TEST im Browser pruefen. Plesk-Deployment aktualisiert
   die Windows-PS1-Dateien NICHT.

## Sicherheits- und Qualitaetsgrenzen

- Nur SQL-Server **SELECT** zum Lesen. Etwaige bestehende
  Schreibrechte werden **nicht** genutzt.
- Test/Produktion nutzen derzeit denselben JTL-Spiegel;
  ein neuer Windows-Snapshot ist damit auch fuer die alte
  Lesestelle sichtbar. Die alte Schnittstelle bleibt
  kompatibel, trotzdem Ruecksicherung vorbereiten.
- Ein fehlgeschlagener kompletter Snapshot wird nicht
  als neuer fertiger Stand freigegeben.
- JTL-Ameise-Import weiterhin nur als Pruef-CSV/Trockenlauf,
  kein direkter Schreibzugriff auf JTL-Tabellen.
- Fuer 1:n-Lieferanten, Amazon-Angebote und Kategorien
  **keine** stille Uebernahme willkuerlicher Zeilen als
  Artikelstandard. Kategorie-Klarnamen und Lieferanten-
  Firmennamen aus weiterem JOIN sind als Ausbaustufe offen.
- Auf dem ChatGPT-System ist keine Windows-Datenbank- oder
  Aufgabenplaner-Ausfuehrung erfolgt. Ein End-to-End-Test
  auf Deinem Server ist noch ausstehend.
