# HW24 KI-Kundenservice · Architektur und Implementierungsvertrag (23.11.2026)

Status: **Preflight / erster isolierter GitHub-Arbeitsschritt**. Keine neue API auf Test/LIVE bereitgestellt, keine neuen JTL-/Amazon-Schreibaktionen.

## Ziel: Ein zentraler Fall-Workflow

Der Operator soll eine Amazon-Kundenanfrage entweder (a) als Screenshot einfügen/hochladen, (b) als Amazon-Benachrichtigungsmail über einen **gesondert autorisierten** Mailhub-Eingang erhalten oder (c) manuell eingeben. Ein KI-gestützter, aber vom Verkäufer freizugebender Assistent erstellt dann einen sachlich geprüften Nachrichtenentwurf mit Daten aus vorhandenen JTL-/DHL-/Amazon-Quellen.

**Betriebssicherheit:** Kein Umbau der bestehenden scanliste.html, JTL-Artikelspiegel-, Amazon-Katalog-/Pricing- oder OpenAI-Review-Endpunkte. Entwicklung als separates Modul auf dem TEST-Zweig. LIVE-Deployment nur nach gesonderter Abnahme.

## Nachgewiesene Bestandsquellen und offene Grenzen

- Aktuelles Legacy-Git `deploy/staging`: `api/jtl.php` nutzt die vorhandenen `hw24_jtl_articles`-/`hw24_jtl_sync_meta`-Spiegel; `api/jtl-wms-history.php` zeigt WMS-**Bewegungen**, nicht automatisch Versandtracking.
- `api/ai-review.php` ist eine optional konfigurierte, freigabe- und budgetgesicherte **Artikel**-KI-Prüfung. Sie soll **nicht** zweckentfremdet oder ohne separates Daten-/Kostenkonzept für personenbezogene Käuferkommunikation verwendet werden.
- Eine produktionsreife JTL-Track-&-Trace-Relation (Auftrag -> Paket -> Scans -> Filiale) wurde hier **noch nicht aus der echten SQL-Server-Schemaquelle** verifiziert. Erster sicherer Nachweis: `sql/JTL_07_TRACK_TRACE_INVENTUR_LESEND.sql` unter dem bestehenden Read-only-JTL-Konto ausführen. Nur Metadaten, keine Kundendaten.
- Amazon Messaging API v1 hat **keinen allgemeinen Inbox-GET-Endpunkt**. `getMessagingActionsForOrder` listet die je Bestellung erlaubten ausgehenden Nachrichtentypen, nicht die eingegangenen Nachrichten. Für Inhalte zunächst Screenshot/Benachrichtigungsmail nutzen. Die erlaubte Sendemethode ist für den individuellen Fall per API/Compliance-Gate zu prüfen.
- Vorhandene offene Filament/Laravel-Draft-PRs (#45/#46) sind von diesem LEGACY-Testmodul organisatorisch getrennt. Keine Übernahme aus einem Draft und kein Merge ohne gezielte Diffprüfung.

## Modulablauf

1. **Eingang:** Screenshot-Datei, Bild aus Zwischenablage, PDF/Bild oder Text/Email-Connector. Upload niemals in einen öffentlich zugänglichen Pfad; Dateityp/Größe prüfen und authentifizierten Actor protokollieren.
2. **Extraktion:** Bestellnummer (Amazon: `000-0000000-0000000`), Kunde, Anlass, Artikel, erkannte Sprache und Text mit *Erkennungsgrad*. Bei uneindeutiger Bestellnummer ausdrücklich manuelle Auswahl, nicht raten.
3. **Klassifikation:** Wo ist mein Paket? / nicht angekommen / Paketfiliale / Retoure / Erstattung / Reklamation / Rechnung / Sonstiges. Der Verkäufer kann die Klassifikation korrigieren.
4. **Belege sammeln (nur lesend):** Amazon-Order-ID -> verifizierter JTL-Auftrag/Externe Nummer -> tatsächliche JTL-Paketsendung -> Sendungsnummer -> bestätigte Track-&-Trace-Scanereignisse. Nur letzte **bestätigte** Ereignisse und ihre Zeitstempel verwenden. EAN/ASIN, Kanal, Versanddatum, Retouren-/Gutschriftstatus separat.
5. **Abgleich:** Widersprüche zwischen Amazon-Auftrag, JTL-Paket und Scan-Liste sichtbar machen. Bei nicht eindeutigem Match oder altem Snapshot kein Zustell-/Abholversprechen.
6. **Antwortentwurf:** freundlich, konkret, auf Wunsch DE/EN/FR/PL; sendet nur belegte Aussagen und alle für die Frage nötigen Informationen (z. B. echte Filialadresse, Bereitstellungszeitpunkt, Trackingnummer); fehlende Informationen als fehlend markieren. Keine Vermutung über Verschulden, Rückzahlung oder Lieferfrist als Tatsache.
7. **Vorschau:** Benutzer kann Text/Empfänger/Bestell-ID prüfen, bearbeiten, übersetzen oder kopieren; ausdrückliche einzelne Freigabe erforderlich.
8. **Versand:** Zuerst über `getMessagingActionsForOrder` das für **diese Bestellung** erlaubte Nachrichtenverfahren feststellen. Nur passende erlaubte Aktion und zulässige Nachricht durchführen. Wenn es **keine** zulässige freie Antwortaktion gibt, stattdessen `In Seller Central öffnen / Text kopieren`; nicht verdeckt per unzulässiger API oder privater Relay-Adresse senden.
9. **Nachweis:** Vom Anbieter bestätigtes Sendeergebnis mit Zeit, User, Bestell-ID, Kanal und API-Status; keine Erfolgsanzeige vor bestätigtem Response, keine unbemerkten Doppelversendungen. Retention-/PII-Löschkonzept festlegen.

## Datenvertrag (Entwurf, noch nicht live)

```json
{
  "order_id": "000-0000000-0000000",
  "origin": "screenshot|email|manual",
  "case_type": "where_is_my_parcel",
  "evidence": {
    "jtl_order_verified": false,
    "tracking_match_verified": false,
    "shipment_number": null,
    "carrier": "DHL",
    "last_scan_at": null,
    "last_scan_text": null,
    "collection_location": null,
    "snapshot_at": null
  },
  "draft": {
    "language": "de",
    "message": "",
    "approval_required": true
  },
  "send": {
    "state": "not_requested",
    "amazon_action_verified": false
  }
}
```

## Arbeitspakete und Gates

**A · Quellnachweis:** SQL-Metadateninventur lesend, echte Tabellen/Schlüssel und Zeitpunkt der JTL-Track-&-Trace-Aktualisierung bestätigen; prüfen ob Bestände/Scans im MariaDB-Spiegel bereits ankommen. Falls nein, separate additive Sync-Erweiterung mit Ready-Run-Gate planen. **Kein SQL Server Write.**

**B · Read-only Shipment Evidence API:** Auth/HTTPS, Order-ID-Bindung an vorhandenen Kundenauftrag, feste SQL-Statements/Prepared Statements, letzter erfolgreicher Snapshot, begrenzte Ereignisanzahl, PII-minimale Antwort. Tests: unbekannte Bestellung, Doppelmatch, veraltete Daten, kein Abholort, Lieferung abgeholt, Retoure.

**C · Eingabemaske:** Screenshot/Clipboard/Text, nur auf TEST. Lesezugriff auf Input und Preview auth-gated. Datei-Inhalte niemals im öffentlich erreichbaren Plesk-Webroot oder Git.

**D · KI-Antwortgenerator:** eigene private OpenAI-Konfiguration und Budget, DLP/Redaktion (Name/Adresse/Kontakt wenn nicht nötig entfernen), Prompt-Injection-Schutz für Kundenbilder/-texte, strukturierter Ergebnisvertrag mit Belegliste, manuell editierbar.

**E · Amazon Versandfähigkeit:** Bestellbezogene Actions abrufen, Berechtigungen/Textspezifikation/Rate Limits kontrollieren, Operator-Freigabe, Audit/Idempotenz. GET-Inbox nicht versprechen; andernfalls Seller-Central-Copy-Pfad. Erst Sandbox/Testkontakt, keine echten Kunden ohne Prüfung anschreiben.

**F · Release:** Unit- und Integrationstests, Backups/Hashes, Plesk-TEST-Pull manuell, Smoke-Tests, Freigabe. Keine LIVE-Übernahme oder Änderung anderer APIs per Default.

## Akzeptanztests

- Nachweisbarer JTL-Track-&-Trace-Verlauf liefert dieselbe Filiale und Uhrzeit wie die gespeicherte DHL-Quelle. Fehlt das Filialfeld, bleibt es **leer** und wird nicht halluziniert.
- Screenshot mit einer Amazon-Bestellnummer findet exakt einen passenden JTL-Auftrag oder meldet `match_unverified`.
- Kein automatischer KI-Aufruf beim Öffnen; **explizit** vom Operator auslösen und Kosten anzeigen.
- Keine Nachricht wird ohne Freigabe gesendet; bei nicht unterstützter Amazon-Aktion nur kopierbarer Entwurf.
- Doppelte Klicks, Netzwerkfehler und abgelaufene Session erzeugen keine doppelte Kundenantwort.
- Keine Änderungen an Live-JTL, WMS, Shopify, Scanlisten-/Artikelbeständen.

## Referenzen

- Amazon `getMessagingActionsForOrder`: https://developer-docs.amazon.com/sp-api/reference/getmessagingactionsfororder
- Amazon 'Send a message': https://developer-docs.amazon.com/sp-api/docs/send-a-message
- Projektübergabe: `README.md` auf main, `docs/HW24_HANDOVER.md` auf main.
- Exploratives GitHub-Vorhaben: nur dieser Draft-Branch, kein Deploy.
