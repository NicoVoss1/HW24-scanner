# HW24 V4.22 TEST · SP-API-Abfragen und Einkaufschancen
Stand 20.11.2026 · Entwurf · keine Produktivfreigabe

## Verbindliche Versionsgrundlage
- Genaue Codebasis: Commit `dca30b015f062955c29306d2a5e6fa5265d0f45b` aus `deploy/staging`, Anzeige `HW24 Artikelmanager v4.22 TEST`.
- `main` ist **nicht** der aktuelle V4.22-Stand. PR #14 ist eine getrennte **V4.35**-Weiterentwicklung. Alte PR #15 stützt sich auf #14 und gehört nicht in die Versionstreue-Linie.
- Neue Scanlistenfunktion bleibt ein isoliertes Zusatzmodul, JTL, WMS, historische Excel-Quellen, Artikeldaten, Exporte und Amazon-Preisansichten der V4.22 bleiben unberührt.
- Kein Merge nach `deploy/staging`, keine SQL-Ausführung und kein automatisches Plesk-Deployment durch diesen Branch.

## Automatische Katalogabfrage

### Vorhanden in V4.22
- Bei gültiger EAN wird `api/amazon.php?ean=` bereits im bestehenden Artikelmanager automatisch aufgerufen.
- Bei eindeutiger JTL-Artikelnummer/HAN wird erst die verifizierte GTIN aus dem JTL-Spiegel aufgelöst und dann automatisch der Amazon-Katalog geprüft.
- Die vorhandene Amazon-Katalog-API nutzt einen MariaDB-Snapshot-Cache für 360 Minuten. Preise laufen über `api/amazon-pricing.php` **gesondert** und sind bisher On-Demand.

### Neu in dieser isolierten Scanliste
- Jeder erfolgreich **gespeicherte** neue Scan reiht die GTIN in eine deduplizierte, browserseitig gedrosselte Amazon-Warteschlange ein.
- Ein vor 360 Minuten oder weniger erfolgreich abgefragter Katalogdatensatz wird vom bestehenden Server-Cache geliefert; sonst erfolgt ein SP-API-Katalogabruf. Keine zusätzlichen Scans durch die Recherche.
- Die Warteschlange läuft höchstens mit einer Anfrage pro 1,5 Sekunden nach Abschluss der vorherigen Anfrage und bleibt bei Seitenwechsel in `localStorage` erhalten. Bei Server- oder Amazon-Fehlern stoppt sie sicher. Ein manueller Neustart ist vorgesehen.
- Die Scan-Erfassung und JTL-Datenbank bleiben unabhängig von Amazon-Verfügbarkeit. Unklare Produktidentität führt **nicht** zu einer automatischen Ameise-Freigabe.
- Der momentane Scanner verwendet **nur** eine GTIN als Suchkennung. Echte Freitext-/Farbnummernsuche über Amazon `searchCatalogItems(keywords)` ist **noch nicht implementiert** und muss mit JTL-Kandidatenauswahl und strengem Cache kommen.

### Technische Folgeschritte
1. Server-seitige **globale** Rate-Limit-Steuerung mit Antwort-Headern, `429`, Backoff, konkurrierenden Sessions, persistenten Jobs und gesperrten Fehlversuchen. Client-Drosselung allein reicht für mehrere Geräte nicht.
2. Eindeutige Identitätsauflösung für SKU, HAN, EAN, ASIN und Produkttitel/Farbnummer; automatische Amazon-Suche beim abgeschlossenen Suchvorgang, nie bei jedem Tastendruck.
3. Trennung von langlebigen Artikelidentitäten und kurzlebigen Marktplatzpreis-Snapshots; Rechte, Cachefristen, Aufbewahrung und Amazon-Vertragsbedingungen prüfen.
4. Relevante Amazon-Katalogvorschläge mit JTL/Historie und verifizierten Kategorien zusammenführen; beim Übernehmen Quelle und Zeitpunkt speichern.

## Einkaufschancen: nachgelagertes eigenes Modul
Eine günstige Amazon-Anzeige ist **nicht** automatisch ein profitabler, verfügbarer und rechtmäßig wiederverkaufbarer Artikel. Die Prüfung erfolgt bei einer eindeutigen ASIN mit **aktuellem** Angebotspreis und echten Kosten.

Erforderliche belegte Daten:
- Einkauf: tatsächlich bestellbarer Gesamtpreis, Versand zum Empfänger, Stückzahl, Zustand neu, Originalzustand/Versiegelung, Händler, Lagerfügbarkeit, Rechnungs- und Vorsteuerfrage, Liefer- und Rückgabe-/Retourenkosten.
- Verkauf: **realistisch erzielbarer** Brutto-/Netto-VK und Vergleichs-Buy-Box (nicht bloß ein hoher Listenpreis), echte Amazon-Referral-/FBA-/FBM-Gebühren, Versand, Verpackung, Provisionen, Umsatzsteuer, Abwicklung, erwartete Retouren-/Preisrisiken.
- Händler-/ASIN-Zulassung, Marke, Originalware, Cosmetics-/Kennzeichnung, MHD/PAO, Mindestrestlaufzeit und Rechnung, die Amazon bei einer Freischaltung akzeptiert.
- Marge und Kapitalbindung über Konfiguration, beispielsweise zunächst mindestens 5 EUR kalkulierter Deckungsbeitrag und 25 % konservativer ROI (nur **Prüfschwellen**, keine standardmäßige Kaufentscheidung).

Beispiel:
`erwarteter DB = realistisch erzielbarer Nettoverkaufspreis - vorsteuerbereinigter Waren-EK - Versand und Handling - Netto-Marktplatzgebühren - Retouren-/Preisrisiko`.
Keine Mischung aus brutto/netto. Bei unbestätigtem Vorsteuerabzug konservativ mit Brutto-EK rechnen. Ausgang `Kaufchance prüfen` mit direktem Angebotslink und letzte Preisprüfung, **kein automatischer Amazon-Kauf**.

## Nächtliche Kontrolle (nicht aktiv!)
- Katalogabfragen für bereits erfolgreich gecachte identische Produkte nicht jede Nacht wiederholen.
- Preisüberwachung gesondert über `Product Pricing`; offizielles `getItemOffers`-Default-Limit kann 0,5 Anfragen/Sekunde betragen (2.000 Einzel-ASINs dann *mindestens* ~67 Minuten, ohne Overhead/429). Für 2.700 Stück mindestens ~90 Minuten, wenn diese Rate gilt.
- Zuerst tägliche **Prioritäts-Watchlist** (Bestands-/Marge-/Nachfrage-/Volatilitätsfilter), restliche ASINs rotierend; passende Batch-Endpunkte prüfen, bevor eine Vollüberwachung kalkuliert wird.
- SP-API-`ANY_OFFER_CHANGED`-Benachrichtigungen können Preisänderungen bei **eigenen aktiven Listings** liefern. Sie decken nicht automatisch fremde Artikel ab, die man erst einkaufen möchte.
- Tagesbudget, Rate-Limits pro API-Operation und Nutzer-App, Retry-After, backoff, deduplizierte Preisjobs, Sperre bei 403/429 und übersichtlicher Monitoring-Bericht sind Voraussetzungen für einen echten nächtlichen Job.
- Vor Einsatz Berechtigungen und Aufbewahrung gemäß Amazon-Verträgen prüfen. Keine automatischen Bestellungen.

Quellen:
- https://developer-docs.amazon.com/sp-api/reference/searchcatalogitems
- https://developer-docs.amazon.com/sp-api/reference/getitemoffers
- https://developer-docs.amazon.com/sp-api/docs/notification-type-values
- https://developer-docs.amazon.com/sp-api/docs/onboarding-overview
