# HW24 v4.22 TEST + Amazon-Angebotspreise: Deploy- und Sicherheitsplan

## Stand am 19.11.2026
- Der Artikelmanager v4.22 TEST wurde aus dem letzten geprüften HTML-Artefakt in diesen Entwicklungszweig übertragen.
- Die bestehende Amazon Catalog Items API unter `api/amazon.php` lieferte für EAN 4021609029274 Live- und Cache-Daten. Der jeweilige Plesk-Stand ist separat geprüft worden.
- Neue Dateien: `api/amazon-pricing.php`, `assets/amazon-market.js`.
- Die neue Preisfunktion verwendet **getItemOffers**, **nicht** das Catalog Items API. Sie fragt ASIN und MarketplaceId Amazon.de für Zustand New ab.
- In der Artikelkarte erscheint nach genau einem ASIN-Treffer ein Button zur Preisabfrage. Es werden Buy Box, Gesamtpreise, Verkäufer-IDs und FBA-Kennzeichnungen gezeigt, **sofern Amazon sie liefert**.
- Für die Pricing API ist die separate Rolle **Pricing** erforderlich; 403 kann fehlende Autorisierung bedeuten. Amazon-Rate-Limits beachten.
- Preisabfrage: expliziter Klick, keine Massenscans. Sie speichert **keine** neuen Preis-Snapshots, damit Archivierung und Aufbewahrungsfristen zuvor geprüft werden können.
- Keine automatischen Änderungen an JTL, Seller Central oder Preisen.
- Händler-IDs auf Amazon sind keine verifizierten Lieferanten, Bezugsquellen oder Einkaufskonditionen.

## KRITISCH: GitHub ist noch kein vollständiges Abbild des Plesk-Webroots
Die v4.22-Seite referenziert folgende **nicht im GitHub-Branch enthaltene** statische Dateien:
- `assets/hw24-ui.css`
- `assets/hw24-workdesk.css`
- `assets/hw24-operations.css`
- `assets/hw24-batch.css`
- `assets/hw24-vorlagen.js`
- `assets/hw24-smartscan.js`
- `assets/hw24-workdesk.js`
- `assets/hw24-operations.js`
- `assets/hw24-batch.js`

Diese Dateien können auf der Plesk-Testdomain vorhanden sein, wurden aber nicht über den GitHub-Zugriff eingelesen. **Vor einem vollständigen Git-Deployment zuerst die Plesk-Dateien verlustfrei in das Repository aufnehmen** und Versionsstände vergleichen. Keinesfalls fehlende Dateien durch leere Platzhalter ersetzen.

## Gewünschter Release-Ablauf
1. **Code zentral in GitHub:** Feature-Branches, Review, Tests, Pull Request.
2. **Testdomain:** Nur einen geprüften Branch und das richtige Webroot in Plesk verbinden. Vor dem ersten Deploy bestehenden Webroot sichern, zusätzliche lokale Assets in Git aufnehmen. In Plesk zunächst **manuelles** Deployment wählen.
3. **Test:** JTL-Abfrage, EAN mit Historie, Amazon Catalog, Preisabfrage für ASIN, 403 ohne HTTP-Auth, Rate-Limit/Fehlermeldungen, Ameise-CSV, mobile Ansicht, PHP-Logs.
4. **Freigabe:** Release-Tag nach bestandener Testabnahme. Produktionsdomain aus diesem freigegebenen Commit ausrollen, nicht aus unfertigen Feature-Branches.
5. **Rollback:** Vorherige Produktionsdateien und Release-Commit bereit halten; private DB/Token bleiben außerhalb des Git-Repositories.

## Serverseitige Installation der neuen Preis-API
- Nur `api/amazon-pricing.php` und `assets/amazon-market.js` plus die angepasste `index.html` gehören zum Preisfeature.
- Privates `hw24_private/amazon/amazon_spapi.php` bleibt unverändert und darf nie ins Git gelangen.
- Die HTTPS- und Plesk-HTTP-Authentifizierungsprüfung darf nicht entfernt werden.
- Endpunkttest mit Anmeldung: `GET /api/amazon-pricing.php?asin=B01MZALQW6` (kein garantierter Preis). Ohne Anmeldung muss gesperrt sein.
- Bei `pricing_role_or_access_missing` ist das Amazon-Entwicklerprofil / Rollenfreigabe zu prüfen. Bei 429 kein Wiederholungs-Sturm.
- Validierung der erhaltenen Angebote und Seller-IDs am realen Marktplatz vor geschäftlichen Entscheidungen.

## Noch nicht implementiert
- Automatische regelmäßige Preisüberwachung, Benachrichtigungen, repricing oder Preisupdates
- Historisierung von Wettbewerberpreisen in MariaDB
- Lieferanten-/Bezugsquellenrecherche über Amazon-Händler hinaus
- Automatischer Abgleich mit Live-JTL-Kosten oder belastbare Margenberechnung
- Produktivdeployment auf `artikelmanager.hairworld24.de`

## Warum Git-Pull allein aktuell nicht genügt
Plesk Git arbeitet abhängig von Branch, Zielverzeichnis und Deploy-Modus. Ein Pull auf einem alten oder unvollständigen Branch kann den bestehenden HTML- und Asset-Stand überschreiben. Erst vorhandene Plesk-Dateien mit GitHub versöhnen, danach Staging und Produktion getrennt verbinden.
