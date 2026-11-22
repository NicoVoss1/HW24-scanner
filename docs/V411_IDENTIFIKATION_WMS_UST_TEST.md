# HW24 Artikelmanager v4.11 TEST · Artikelnummer, EAN, HAN, WMS-Fächer, Steuerzone

Stand 17.11.2026. Basis sind die vier vom Nutzer direkt gelieferten
SQL12-Ergebnisraster für Testartikel 4015600040482.

## Verifizierter Testartikel

| Eigenschaft | Nachweis |
| --- | --- |
| JTL-Artikel-ID | 2594 |
| Artikelnummer / EAN | 4015600040482 |
| Hersteller / Warengruppe | Wella / Haarfarben (vorherige SQL10-Mappingprobe) |
| Lagerfach | dbo.vWarenlagerPlatzArtikel + dbo.tWarenLagerPlatz |
| Warenlager-ID | 4 |
| Fach-ID / Name | 66 / R2-RF-F13-H |
| Fachmenge | 0 (existierende Zuordnung muss TROTZ Nullbestand gezeigt werden) |
| nGesperrt / nAuslieferungGesperrt | 0 / False |
| Steuerklasse | 1 / Normaler Steuersatz |
| Steuerzone / Zielland | 3 (EU-Zone) / DE |
| Firma / Satz | kFirma=1 / fSteuersatz=19 |
| DE-Ländersteuersatz | fNormalerSatz=19, fErmaessigterSatz=7 |
| JTL WMS-Artikelview | fMwSt=19 |

Die USt 19 % ist damit für diesen Artikel und **den geprüften
deutschen Firmen-/Zonenkontext** dreifach bestätigt. Diese Werte
dürfen NICHT als Satz für alle Zielgebiete, B2B-Konstellationen
oder EU-Lieferungen verallgemeinert werden. JTL-Wawi wird bei einer
konkreten Bestellung nach dem Steuerkontext entscheiden.

## Such- und Anzeigeverhalten

- Gemeinsames Suchfeld akzeptiert **EAN/GTIN, Artikelnummer (inkl. AR...)
  und HAN**, auch alphanumerisch.
- Die read-only API \`api/jtl.php\` prüft Eingaben und fragt per SQL
  Prepared Statement \`ean OR han OR artikelnummer\` ab.
  Der bisherige URL-Parameter \`ean\` bleibt kompatibel, \`q\`
  ist zusätzlich möglich.
- Bei numerischer gültiger EAN werden JTL-Spiegel und historisches
  Excel-Archiv parallel abgefragt.
- Bei einer SKU/HAN zuerst JTL nach der Kennung abfragen;
  wenn genau ein Treffer mit gültiger GTIN zurückkommt,
  wird im **gleichen Suchvorgang** die Historie anhand der
  echten EAN ergänzt. Ohne eindeutige EAN keine erfundene
  historische Suche und keine automatische Neuanlage.
- In der Artikelübersicht und in der Lagerplatzübersicht
  **Artikelname, Artikelnummer, EAN/GTIN, HAN, Hersteller,
  Warengruppe** aus dem JTL-Spiegel anzeigen. Nichts aus IDs raten.
- Die Fachliste enthält zusätzlich Warenlager, Lagerfach,
  Menge, Fachsperre/Auslieferungssperre und WMS-Quellfelder.

## Retoure, Sperren und Inventur

Gesicherte SQL-Spalten in \`dbo.tWarenLagerPlatz\`:
\`kWarenLagerPlatzTyp\`, \`nStatus\`, \`nGesperrt\`,
\`nAuslieferungGesperrt\`, \`kWmsInventur\`,
\`dWmsInventurDatum\`, \`nPreInvStatus\`, \`nInvGezaehlt\`,
\`cKommentar\`.

- Verifizierte Fach-/Auslieferungssperren werden erkennbar
  als **gesperrt** markiert, auch wenn die Menge 0 ist.
- Inventurbezug/Datum/Preinventur-Status werden mit ihren
  JTL-Rohwerten ausgewiesen. Die Bedeutung der einzelnen
  numerischen Status-/Typ-IDs ist noch nicht belegt.
- Wenn Fachname oder Fachkommentar das Wort „Retour“
  enthält, kennzeichnen wir ausdrücklich nur einen
  **Retourenhinweis im Text**. Das beweist NICHT, dass
  JTL den Lagerplatztyp offiziell als Retourenplatz führt.
- Zur belastbaren Bezeichnung „Retourenplatz“, „Gesperrt
  (Inventur)“ oder „Klärplatz“ müssen die numerischen
  Typ-/Status-IDs anhand der zu Deiner JTL-Version
  passenden Stammdaten und Statusdefinitionen zugeordnet
  werden. Bis dahin keine geratenen Statusnamen.

Die JTL-Dokumentation unterscheidet ausdrücklich zwischen
aktiven, gesperrten und inventurgesperrten Lagerplätzen
sowie speziellen Retourenplätzen:
https://guide.jtl-software.com/jtl-wms/einstellungen/lagerplatz-anlegen/
https://guide.jtl-software.com/jtl-wms/einstellungen/lageroptionen-lagerplatzuebersicht/

## Technische Änderungen auf dem Entwicklungsbranch

- \`api/jtl.php\`: geschützte parametrisierte Suche anhand aller
  drei Kennungen; unveränderte Lese- und Snapshot-Sicherungen.
- \`index.html\`: ein Suchvorgang für alle Kennungen,
  korrekte automatische historische EAN-Auflösung für SKU/HAN.
- \`assets/hw24-smartscan.js\`: Historienrückgabe der aus JTL
  aufgelösten GTIN wird dem ursprünglichen Scan zugeordnet.
- \`assets/hw24-workdesk.js\`: alle Identitätsfelder
  + Artikelname + Warengruppe immer sichtbar.
- \`assets/hw24-operations.js/.css\`: eigene, übersichtliche
  WMS-Artikelkarte und die Fachliste einschließlich Sperrungen
  und JTL-Inventurhinweisen.
- \`sync/HW24_JTL_SteuerWMS.ps1\`: liest ab jetzt auch
  **Fachzuordnungen mit fAnzahl=0**, Lagerplatztyp-ID,
  nStatus, Inventur-/Sperrwerte, Lagerfach-Kommentar.
- Der deutsche JTL-Steuersatz wird **nur mit Firma und
  Steuerzone** ausgegeben, sobald die Steuer-Synchronisation
  wirklich aktiviert und erfolgreich abgeschlossen ist.
  Kein 19-%-Hardcoding im Ameise-Import.

## Was noch aktiv geschaltet werden muss

1. Produktive Windows-PowerShell-Synchronisation und letzte
   erfolgreiche JTL-Spiegeldatenbank sichern.
2. Die zusammengehörigen Dateien aus dem Branch aktualisieren:
   \`HW24_JTL_Synchronisieren.ps1\`,
   \`HW24_JTL_Zusatzquellen.ps1\`,
   \`HW24_JTL_SteuerWMS.ps1\`.
3. Die bisherige private Windows-Config und
   \`jtl-related-sources.json\` nur lokal bearbeiten.
   In der bestätigten Mappingkonfiguration nach einem
   kontrollierten Probe- und Berechtigungstest die beiden
   Optionen \`include_wms_bins\` und \`include_tax_de\`
   auf \`true\` setzen. Repository-Beispiel bleibt
   vorsichtshalber **false**.
4. Den automatischen 15-Minuten-Task für genau einen
   kontrollierten Lauf pausieren; keine parallel laufenden
   Synchronisierungen. Test- und Live-Artikelmanager teilen
   zurzeit denselben Spiegel!
5. Prüfen: \`/api/jtl.php?ean=4015600040482\` mit Hersteller,
   Warengruppe, Steuerzonenzeile und **Lagerfach R2-RF-F13-H,
   Menge 0**. Danach \`?ean=AR...\` und \`?ean=<HAN>\`
   mit echten Bestandskennungen testen.
6. Testdomain via Plesk Git Pull/Bereitstellung aktualisieren;
   oben **v4.11 TEST**. Auf iPhone mit Tastatur und Kamera prüfen.
7. Erst nach Tests eine Freigabe für Pull Request/Merge und
   für den Ameise-Import. Kein direktes SQL-Schreiben in JTL/WMS.

## Noch offener Abnahmepunkt

Die endgültige fachliche Übersetzung von \`nStatus\` und
\`kWarenLagerPlatzTyp\` in „Retourenplatz“,
„Inventurgesperrt“ und andere JTL-Statusbezeichnungen
muss gegen die konkrete Versionszuordnung geprüft werden.
Die momentan implementierten Roh-IDs sind dafür
transparent sichtbar. Keine unbestätigte Festlegung.

**Teststatus:** Code- und Einbindungsprüfung im Entwicklungsbranch;
kein Windows-PowerShell-Lauf, keine echte neue JTL-Synchronisation,
keine getestete WMS-Bestandsbuchung und keine automatische
Produktivbereitstellung erfolgt.
