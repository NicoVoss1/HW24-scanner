# HW24 Artikelmanager: verbindlicher Funktions- und Abnahmeplan (20.11.2026)

## Status / Vorfall
- Ursache der regressiven Anzeige "Nicht synchronisiert": Die neue `api/jtl.php` gab `raw_json.HW24_RelatedSources` nicht mehr als `related_sources` zurück. Bestehende Windows-JTL-Synchronisation und MariaDB-Daten wurden nicht entfernt.
- Die WMS-Historien-API war im Deploy-Branch durch eine kleine Statusantwort ersetzt worden. Auf der TEST-Domain wurde der originale Empfänger aus der Produktivinstallation bereits manuell restauriert.
- Nachweis aus `hw24_jtl_articles` / `api/jtl.php?q=4016660006753`: `bestand.fVerfuegbar=2`, `fInAuftraegen=0`, `fZulauf=0`, WMS-Lagerfach 09-011 mit 2 Stück, Warengruppe Haarfarben, `amazon_angebote.preis_angebot=14.99`.
- GitHub ist alleinige Quelle des Anwendungscodes; Plesk wird ausschließlich aus einem geprüften Release versorgt. Keine weiteren manuellen Produktivkopien und keine automatischen JTL-Schreibzugriffe.
- Dieser Wiederherstellungs-PR verändert weder Windows-Jobs noch MariaDB-Schemata oder Plesk.

## P0: Rückgewinnung und Regressionstests (in diesem PR vorbereitet)
- Wiederherstellung des ausführlichen `api/jtl.php` und `api/jtl-wms-history.php` auf einem nicht automatisch deployenden Feature-Fix-Branch.
- CI muss alle PHP-Dateien syntaktisch prüfen und die `related_sources`- und WMS-Paginierungsverträge absichern.
- Abnahme: EAN 4016660006753 zeigt Lagerbestand der Quelle `dbo.tlagerbestand` = 2, verfügbar = 2, Aufträge = 0, Zulauf = 0, WMS-Fach 09-011 = 2. Unterschied `tArtikel.nLagerbestand=0` klar als Quellabweichung ausweisen, nicht still überschreiben.
- WMS-Historie: alle Bewegungen über paginierte `offset`/`run_id`-Abfragen, 50 Einträge pro Seite, keine künstliche Gesamtdatumsgrenze; Folgeseiten müssen verlässlich nachgeladen werden.
- Alle Datumsanzeigen `Europe/Berlin` einschließlich MEZ/MESZ, kein doppeltes Konvertieren bereits lokaler SQL-Quellfelder.
- Manuelles Plesk-Deployment erst nach Review, grüner CI und Testabnahme; produktiver Stand unverändert.

## P1: Eine globale Suche für Scanner, WMS und Artikelpflege (offen)
- EINE Suchkomponente und EINE Such-API für EAN/GTIN, HAN, JTL-Artikelnummer, ASIN, Name, Hersteller, Farbnummer, Teilbezeichnungen und Zahlenvarianten (`7.0`/`7,0`). Prüfen, welche Farbcodes aus JTL-Name/HAN oder validierten Attributen ableitbar sind.
- Über alle Artikel des aktuellen vollständigen JTL-Spiegels suchen; ergebnisbezogene Sortierung: exakter Identifikator, exakte Farbnummer + Marke, exakte Phrase, Namensfragmente, unscharfe Kandidaten. Mehrdeutige Treffer ausdrücklich wählbar, niemals automatisch zuordnen.
- Nach Auswahl eines eindeutigen Artikels verwenden alle Arbeitsbereiche dieselbe `jtl_article_id`, EAN und ASIN. Die WMS-Historie nach `article_id` öffnen und bei Bedarf nachladen.
- Tastatureingabe mit Verzögerung/Enter, mobile Kamera direkt als EAN, keine Suche bei jedem Tastendruck; Paginierung, maximale Trefferzahl, SQL-Parameterbindung, Performance-Indizes nach Prüfung der tatsächlichen Tabellen.

## P2: Amazon-Daten pro eindeutigem Suchvorgang anreichern (offen)
- Vorhandenes `api/amazon.php` unterstützt gültige EAN und archiviert SP-API-Katalog-Snapshots in MariaDB (360-Minuten-Cache); das bleibt erhalten.
- Automatisch abfragen, sobald eine Suche zu einer gültigen EAN/ASIN eindeutig aufgelöst wurde. Bei Namen/Farbnummern zuerst Kandidaten aus dem JTL-Spiegel wählen, dann Amazon abfragen; alternativ Amazon `searchCatalogItems` Keywords getrennt mit Trefferfreigabe. Kein automatischer Match aus mehrdeutigen Amazon-Suchergebnissen.
- Jede eindeutige Suchabsicht als Audit-Eintrag; gecachte Antworten nicht erneut live anfragen. Wiederholte Eingaben desselben Artikels dürfen das Amazon-Kontingent nicht unnötig belasten. Rate-Limits, Retry-After, 429, fehlende Berechtigung und leere Antworten sichtbar.
- Getrennte, versionierte Quellwerte: eigener JTL-VK, tatsächlicher JTL-Amazon-Angebots-VK, Amazon-SP-API-Marktpreis, eBay-Angebots-VK, Timestamp, Herkunft und Gültigkeit. Amazon ist Katalog-/Angebotsquelle, nicht autoritative JTL-Bestands- oder Einkaufspreisquelle.
- Produktlink zur Amazon.de-ASIN `/dp/{ASIN}` und Link zur Amazon.de-Angebotsübersicht `/gp/offer-listing/{ASIN}` in neuem Tab mit `rel=noopener noreferrer`. Linkfehler nicht als API-Erfolg ausgeben.

## P3: Klartext statt IDs und JTL-vorbelegte Artikelpflege (offen)
- Artikel-Hersteller und Warengruppe aus verifizierten JTL-Namensfeldern befüllen; interne IDs dürfen technisch verfügbar bleiben, stehen aber nicht primär in der Pflegeoberfläche.
- Echte Listen von JTL-Herstellern, Warengruppen, Steuerklassen, Versandklassen, Lieferstatus, Lieferanten und Mengeneinheiten lesen und als suchbare Auswahl zeigen. Keine statischen Fantasie-Listen, keine Umdeutung von Schlüsseln.
- Boolesche Flags wie `cLagerAktiv=Y`, `cAktiv=Y` als `Ja/Nein` mit JTL-konformer Exportabbildung zeigen; JTL-Feldhilfen zu Bearbeitungszeit, Lieferzeiten bei Ausverkauf, Überverkäufen und Bestandsführung. Freitextfelder als solche kennzeichnen.
- JTL-Lieferant ist nicht dasselbe wie Amazon Seller-ID. JTL-Lieferantennamen müssen über verifizierte `tLieferant`-/Adressdaten-Beziehung aufgelöst werden, nicht aus Seller-IDs geraten.
- Amazon Product Pricing `SellerId` liefert nicht automatisch einen menschlichen Verkäufernamen. Vorhandenes `api/amazon-sellers.php` liest nur manuell verifizierte Zuordnungen. Automatische Klartextanreicherung nur aus rechtlich/technisch zulässiger und belegter Quelle oder einer dafür bereitgestellten API, immer mit Quelle und Aktualisierungsdatum; keine unbelegte Namensvermutung.
- Verkäuferprofil-Link `/sp?seller={SellerId}` und Amazon-Angebotsübersicht je ASIN als Prüfweg, kaputte Amazon-Profil-Links als solche kenntlich machen.

## P4: Schnelle, sichere JTL-Ameise-Anreicherung (offen)
- Keine direkte Änderung der SQL-Server-Produktionsdatenbank.
- Vollständige, an offiziellen Ameise-Importfeldern verifizierte Bearbeitungsmaske mit vorgefüllten JTL-Werten, nur freigegebene Änderungen schreiben in HW24-Kuratierungsdaten.
- Einzelartikel- und Stapelpflege; Änderungsprotokoll mit altem Wert, neuem Wert, Quelle, Status, Zeitpunkt in Europe/Berlin.
- CSV-Trockenlauf mit Vorschau, manueller Freigabe und konfliktfreiem Import über JTL-Ameise. Erkennbare Zustände Neu / Korrigieren / Unverändert; keine ungewollte Leerwert- oder Null-Preis-Überschreibung.

## Release-Vertrag
1. Änderungen immer vom passenden GitHub-Featurebranch mit Pull Request; niemals direkt auf `deploy/staging` oder Plesk experimentieren.
2. CI mit Syntax-, Interface- und fallbezogenen Tests muss grün sein. Vor dem Deploy Diff von `api/`, `assets/`, `index.html` gegen letzten stabilen Release prüfen.
3. Separater Test mit EAN 4016660006753 (Dusy) plus Mehrfachmatch, Artikel ohne ASIN, Amazon-429, WMS-Paginierung und unbekannter Seller-ID.
4. Erst nach manueller UI-Abnahme durch den Betreiber die geprüften Website-Dateien in den Staging-Deploy-Branch übernehmen; Plesk-Deployment bleibt gesondert kontrolliert.
5. Vor jedem Release eine Rollback-Referenz und ein Build-Manifest; Änderungen an Datenbankschemata separat und rücksetzbar.
