# HW24 v4.10 TEST · Steuerzonen und echte WMS-Fachmengen

**Basis:** Upload der SQL11-Spaltenmetadaten am 17.11.2026. Die beiden
Steuer-Dateien sind inhaltlich identisch; die dritte Datei enthält die
WMS-Tabellen-, View- und Spaltendefinitionen. Es wurden **keine realen
Steuersatz-Werte oder Fachmengen** mitgeschickt.

## Verifizierte Quelle für Fachmengen

`dbo.vWarenlagerPlatzArtikel`:
- `kArtikel`, `kWarenLagerPlatz`, `fAnzahl`

`dbo.tWarenLagerPlatz`:
- `kWarenLagerPlatz`, `cName` (Fachkennzeichen), `kWarenLager`
- `nGesperrt`, `nAuslieferungGesperrt`

Ein *lesender* JOIN über `kWarenLagerPlatz` ermöglicht
pro `kArtikel` eine Liste mit **Warenlager-ID, Fachbezeichnung,
Fachmenge und Sperrstatus**. Wir verwenden `fAnzahl` aus der
View, nicht das bloße Stammdatenfeld `nLagerbestand`.

Die vorhandene Oberfläche zeigt bei deaktivierter Quelle weiterhin
"noch nicht synchronisiert" und darf keine Lagerfachmenge erfinden.
Wenn die Quelle aktiv ist und keine positiven/negativen Fachmengen
liefert, zeigt sie explizit "keine gebuchte Fachmenge".

## Verifizierte Steuerstruktur

- `dbo.tArtikel.kSteuerklasse` (Testartikel: 1)
- `dbo.tSteuerklasse.cName` (Testartikel: Normaler Steuersatz)
- `dbo.tSteuersatz(kSteuerklasse,kSteuerzone,fSteuersatz,nPrio)`
- `dbo.tSteuerzone(kSteuerzone,kFirma,cName)`
- `dbo.tSteuerzoneLand(kSteuerzone,cISO)`
- `dbo.tLandSteuersatz(cISOLand,fNormalerSatz)`
- `Steuern.vSteuerdaten` bietet weitere ermittelte Kontextwerte
- `dbo.vWMSArtikel.fMwSt` enthält einen weiteren Wert zum Quervergleich

Die Umsatzsteuer wird **nicht aus der Steuerklassen-ID 1 auf 19 %**
abgeleitet. Ein Steuersatz hängt von Firma, Versand-/Zielland und
ggf. weiteren JTL-Regeln ab. Deshalb lesen wir zunächst nur die
in Deutschland konfigurierten Werte mit Zone und Firma aus.
Die Einheit/Skalierung des Quellwerts muss anhand von SQL12
kontrolliert werden. Keine Änderung des JTL-Steuersatzes und
**keine** automatische Prozent-Spalte im Ameise-Export.

## Neue GitHub-Datei

`sync/HW24_JTL_SteuerWMS.ps1`

- Verifiziert echte SQL-Spalten, bevor eine Abfrage erfolgt
- Fragt Fachmengen in 100er-Artikel-Chunks mit SQL-Parametern ab
- Liefert je Artikel `related_sources.wms_lagerplaetze` mit
  `lagerplatz`, `lagerplatz_id`, `warenlager_id`,
  `menge`, Sperrstatus und `confirmed=true`
- Maximal 100 Fachpositionen je Artikel; weitere Treffer führen
  zu `truncated=true` und einer sichtbaren Warnung
- Liest deutsche Steuersätze nach Steuerklasse, Firma und
  Zone in `related_sources.steuer_saetze_de`
- Keine Schreibbefehle auf den JTL-SQL-Server
- Die bestehenden API-Rohdaten `raw_json` nehmen die
  Zusatzquellen auf; dafür ist keine neue MariaDB-Tabelle nötig

Die Zuordnungen sind jetzt im Branch `improve/encoding-dashboard-sync-20261115`.
Sie wurden **noch nicht auf Windows ausgeführt** und dürfen nicht
vor einer kontrollierten Prüfung des tatsächlichen SQL-Ergebnisses
als funktionierende Echtzeit-Lagerabfrage bezeichnet werden.

## Sicherer nächster Schritt

1. Die rein lesende `sql/JTL_12_STEUER_WMS_WERTE_LESEND.sql`
   im SSMS gegen `eazybusiness` ausführen.
2. Die vier Ergebnisse mitsamt Spaltenüberschriften als CSV
   schicken: Fachmengen; deutsche Steuerzonen; Ländersteuersätze;
   JTL-WMS-View-Vergleich.
3. Prüfen, ob bei EAN `4015600040482` (Bestand 0)
   tatsächlich keine befüllten Lagerplätze vorliegen und
   welcher deutsche Steuersatz für den passenden Firmen-/Zonenkontext
   angezeigt wird.
4. Zuerst auf dem Windows-Host eine Sicherung des aktiven
   Synchronisierers anlegen. Die drei zueinander passenden Dateien
   `sync/HW24_JTL_Synchronisieren.ps1`,
   `sync/HW24_JTL_Zusatzquellen.ps1` und
   `sync/HW24_JTL_SteuerWMS.ps1` in einen separaten
   Testordner kopieren.
5. In der **privaten lokalen Windows**-Datei
   `jtl-related-sources.json` erst nach erfolgreicher
   SQL12- und Trockenprüfung setzen:

   ```json
   "include_wms_bins": true,
   "include_tax_de": true
   ```

   **Wichtig:** Im Repository bleiben diese beiden Schalter
   für die Installation standardmäßig `false`.
6. Auf dem Windows-Host das regelmäßige Sync-Task für einen
   **einzigen kontrollierten Lauf** pausieren, anschließend
   die Ausführung gegen den bestehenden HTTPS-Empfänger prüfen.
   Weil Test und Live denselben Spiegel verwenden, ist die
   neue SQL-Server-Abfrage **nicht** automatisch eine isolierte
   Test-Synchronisation. Vorher den letzten guten Snapshot sichern.
7. `/api/jtl.php?ean=4015600040482` kontrollieren:
   `related_sources.wms_lagerplaetze` und
   `related_sources.steuer_saetze_de`.
   Bei Fehlern zu alter Version zurückkehren und die aktivierte
   Quelle deaktivieren. Keine WMS-Buchung durch SQL-UPDATE.
8. Plesk-Git-Testdomain aktualisieren und neue Versionskennung
   prüfen. Die UI bleibt lesend; der Ameise-Export ändert
   keine JTL-Bestände.

## Zusätzlich

Der neue WMS-Reiter enthält **keinen** Warenzugang und keine
Lagerplatzumbuchung. Auch wenn der Datenbanknutzer Schreibrechte
hätte, dürfen wir JTL- bzw. WMS-Buchungsvorgänge nicht durch direkte
SQL-Änderungen an physischen Bestandstabellen ersetzen.

**Teststatus:** GitHub-Code vorbereitet. Ohne Windows PowerShell
oder JTL-Datenbanklauf in dieser Unterhaltung sind noch keine
end-to-end synchronisierten Lagerplatzdaten nachgewiesen.
