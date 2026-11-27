# HW24 JTL Phase 5 – tatsächlich beobachtete Referenzwerte

**Stand der Betreiberabfragen:** 2026-11-23 · **SQL-Server-Datenbank:** `eazybusiness`
**Quelle:** Betreiberantwort zu `platform/ops/jtl-heidisql-phase5-reference-values-readonly.sql`
**Verwendungszweck:** dauerhafter Quellbeleg und Testreferenz, **kein automatisch aktueller Livewert**.

## Lieferstatus
| kLieferStatus | kSprache | cName |
|---:|---:|---|
| 1 | 1 | 2-4 Werktage |

## Steuerklassen
| kSteuerklasse | cName | nStandard | nTyp |
|---:|---|---:|---:|
| 1 | Normaler Steuersatz | 1 | 4 |
| 2 | Ermäßigter Steuersatz | 0 | 3 |

## Steuersätze
| kSteuersatz | kSteuerklasse | kSteuerzone | fSteuersatz | nPrio |
|---:|---:|---:|---:|---|
| 5 | 1 | 3 | 19 | NULL |
| 7 | 1 | 4 | 19 | NULL |
| 6 | 2 | 3 | 7 | NULL |
| 8 | 2 | 4 | 0 | NULL |

**Wichtig:** Die Bedeutung der Steuerzonen 3 und 4 wurde nicht ermittelt. Ohne Bestimmungsort/Steuerzonenzuordnung keinen endgültigen Bruttoverkaufspreis ableiten. Prozentsätze sind Quellwerte, nicht automatisch einer konkreten Marktplatztransaktion zugeordnet.

## Maßeinheiten (alle 31 vom Betreiber gemeldeten Datensätze)
| ID | cCode | Anzeige | Bezugs-ID | Faktor | Unit-Code |
|---:|---|---|---:|---:|---|
| 1 | m | m | 0 | 0 | MTR |
| 2 | mm | mm | 1 | 0.001 | MMT |
| 3 | cm | cm | 1 | 0.01 | CMT |
| 4 | dm | dm | 1 | 0.1 | DMT |
| 5 | [in_i] | in | 1 | 0.0254 | INH |
| 6 | km | km | 1 | 1000 | KMT |
| 7 | kg | kg | 0 | 0 | KGM |
| 8 | mg | mg | 7 | 1E-6 | MGM |
| 9 | g | g | 7 | 0.001 | GRM |
| 10 | [lb_av] | lb | 7 | 0.45359237 | LBR |
| 11 | t | t | 7 | 1000 | TNE |
| 12 | m2 | m² | 0 | 0 | MTK |
| 13 | mm2 | mm² | 12 | 1E-6 | MMK |
| 14 | cm2 | cm² | 12 | 0.0001 | CMK |
| 15 | L | l | 0 | 0 | LTR |
| 16 | mL | ml | 15 | 0.001 | MLT |
| 17 | dL | dl | 15 | 0.1 | DLT |
| 18 | cL | cl | 15 | 0.01 | CLT |
| 19 | m3 | m³ | 0 | 0 | MTQ |
| 20 | cm3 | cm³ | 19 | 1E-6 | CMQ |
| 21 | *(leer)* | Stk | 0 | 0 | NULL |
| 22 | floz | floz | 0 | 0 | *(leer)* |
| 23 | ft | ft | 0 | 0 | *(leer)* |
| 24 | ft2 | ft2 | 0 | 0 | *(leer)* |
| 25 | gal | gal | 0 | 0 | *(leer)* |
| 26 | in | in | 0 | 0 | *(leer)* |
| 27 | lb | lb | 0 | 0 | *(leer)* |
| 28 | oz | oz | 0 | 0 | *(leer)* |
| 29 | pt | pt | 0 | 0 | *(leer)* |
| 30 | qt | qt | 0 | 0 | *(leer)* |
| 31 | yd | yd | 0 | 0 | *(leer)* |

**Hinweis:** `fMassMenge` wurde am Artikel **noch nicht anhand eines konkreten Beispielartikels** bestätigt. Eine Umrechnung `90000` → `90` wäre ohne Quellwert-/Einheitentest nicht gerechtfertigt. `0.001` ist der *mL→L* Bezugsfaktor; nicht automatisch ein Skalierungsfaktor für falsch angezeigte Artikel-Füllmengen.

## JTL-Shops
| kShop | cName | nTyp | nAktiv | kFirma |
|---:|---|---:|---|---:|
| 1 | Friseurbedarf24.shop (Kaufland.de) | 6 | True | 1 |
| 3 | Shopify Fb24 | 1 | True | 1 |
| 4 | Amazon.de Lister | 6 | True | 1 |
| 5 | Amazon.fr Lister | 6 | True | 1 |
| 6 | Amazon.it Lister | 6 | True | 1 |
| 7 | Amazon.es Lister | 6 | True | 1 |
| 8 | Amazon.co.uk Lister | 6 | True | 1 |

**Keinerlei** Werte aus `dbo.tShop.cAPIKey`, `cPasswortWeb`, `cBenutzerWeb` oder aus `dbo.tShopScx.cSellerId` wurden ausgelesen oder gespeichert.

## SCX-Preisarten und Märkte
| kChannelPriceType | kChannel | kShop | cPriceTypeId | cDisplayName | kKundenGruppe |
|---:|---:|---:|---|---|---:|
| 1 | 19 | 1 | B2CMin | Minimal Preis | 6 |
| 2 | 19 | 1 | B2C | Endkunden Preis | 5 |
| 3 | 37 | 4 | B2B | Geschäftskunden Preis | 4 |
| 4 | 37 | 4 | B2C | Endkunden Preis | 3 |
| 5 | 34 | 5 | B2B | Geschäftskunden Preis | 4 |
| 6 | 34 | 5 | B2C | Endkunden Preis | 3 |
| 7 | 33 | 6 | B2B | Geschäftskunden Preis | 4 |
| 8 | 33 | 6 | B2C | Endkunden Preis | 3 |
| 9 | 36 | 7 | B2B | Geschäftskunden Preis | 4 |
| 10 | 36 | 7 | B2C | Endkunden Preis | 3 |
| 11 | 35 | 8 | B2B | Geschäftskunden Preis | 4 |
| 12 | 35 | 8 | B2C | Endkunden Preis | 3 |

Beschreibung `B2CMin` enthält historische `real.de`-Terminologie; daraus **nicht** ableiten, dass automatisches Repricing für Kaufland aktuell aktiv ist. Es sind **Preisart-Bezeichnungen, keine echten Preisbeobachtungen**. Die Währung je Verkaufskanal ist noch offen.

## SCX-Angebotszahlen (aggregierte Momentaufnahme)
| kShop | alle Angebote | nIsListed=1 |
|---:|---:|---:|
| 1 | 3205 | 1159 |
| 4 | 2735 | 2294 |
| 5 | 2662 | 1118 |
| 6 | 2658 | 1275 |
| 7 | 2658 | 1279 |
| 8 | 2131 | 788 |
| **Summe** | **16049** | **7913** |

Kein Eintrag für Shopify `kShop=3` in dieser aggregierten `SCX.tOffer`-Tabelle. **Nicht** mit `0 Shopify-Produkten` gleichsetzen; alternative JTL-Shop- und Shopify-API-Quellen bleiben zu prüfen. `nIsListed=1` ist ein internes JTL-Merkmal, keine extern bestätigte Veröffentlichung.

## Nachgewiesene Quellstruktur für den ersten Datenexport
- `dbo.tPreisDetail`: `kPreis`, `nAnzahlAb`, `fNettoPreis`, `fProzent`, `bRowversion`. `dbo.tPreis` enthält Kunden-, Shop- und Kundengruppen-Kontext.
- `dbo.tKundenGruppe`: `kKundenGruppe`, `cName`, `nNettoPreise`, `fRabatt`, weitere Felder.
- `dbo.tShopWaehrung`: `kWebShop`, `kWaehrung`, `nStandard`, `bRowversion`; Währungsreferenz `dbo.tWaehrung` bislang nur als Tabellenname belegt.
- `dbo.tSteuerzone`: `kSteuerzone`, `kFirma`, `cName`, `cLandISO`, weitere Felder. Referenzwerte/Geografie fehlen.
- `dbo.tShopScx`: `kShopScx`, `kShop`, `kChannel`, `cChannel`, `cSellerId` und viele betriebliche Felder. Seller-ID **nicht** ohne Zweck/Datenschutzprüfung exportieren.
- `SCX.tChannel`: `kChannel`, `cChannel`, `cDisplayName`, `cCurrency`, zusätzlich `cJson` und weitere Konfigurationsfelder. **cJson nicht ausgeben**: potenziell sensitive Konfiguration.

## Status und nächste reale Aktion
- Nur Metadaten und eng freigegebene Referenzwerte erfasst, **kein** Datenspiegel installiert.
- `platform/ops/jtl-heidisql-phase6-article-export-readonly.sql` ist der erste echte Artikel-SELECT mit vollständiger, expliziter Spalten-Whitelist. SQL 1 zunächst prüfen, SQL 2 enthält optional den vollen Artikel-Basisexport. Datei **nur lokal/privat** speichern.
- Export wird erst nach Prüfung von Ergebnis-/Quellzuordnung, Datenschutz, Integrität und Laufzeit für einen privaten Import verwendet.
