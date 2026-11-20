# HW24 · Änderung mit Projektdokumentation

> **Vor dem Bearbeiten:** [Verbindlichen Projektstand](../docs/HW24_PROJEKTSTAND_AKTUELL.md) und [Arbeitsjournal](../docs/HW24_ARBEITSJOURNAL.md) lesen. Die Anwendungsbasis ist die zuletzt **bestätigte** V4.22-Stagingversion plus danach ausdrücklich freigegebene Änderungen, **nicht automatisch `main`**.

## Ausgangspunkt und Version
- GitHub-Ausgangsbranch / letzter bestätigter SHA:
- Plesk-Test-/Live-Stand (tatsächlich bestätigt, nicht vermutet):
- Bestehende Funktionen, die unverändert erhalten bleiben:
- Bereits erledigte Vorarbeiten, die **nicht erneut** angefordert werden:

## Ziel und genaue Änderungen
- Fachliche Aufgabe:
- Geänderte Dateien / betroffene API-Endpunkte:
- Neue Tabellen/DB-Migration (ja/nein, separat, Ausführungsstatus):
- Explizit **nicht** geändert (Live, JTL, WMS, Credentials, Datenbank):

## Belege und Tests
- [ ] Dateibaum geprüft: öffentlich nur freigegebene Site-Dateien; keine `sql/`, `docs/`, `sync/`, `hw24_private/` auf dem Plesk-Webtree
- [ ] GitHub CI-Syntax-/Paketchecks dokumentiert (URL / SHA):
- [ ] Tatsächliche HTTP-/DB-/Plesk-Tests durchgeführt **oder offen** markiert
- [ ] Status von SP-API-Berechtigungen, Cache, Limits und 429 dokumentiert (sofern relevant)
- [ ] JTL-Ameise-Importprofil und Testlauf dokumentiert (sofern relevant)
- [ ] Keine neue Backup-/Ordnerprüfung gefordert, wenn schon erledigt und kein neuer technischer Anlass besteht
- [ ] Rollback-Ziel und Abhängigkeiten nachvollziehbar
- [ ] Fehlversuche und offene Risiken ausdrücklich dokumentiert

## Fortlaufendes Journal
- [ ] [Aktueller Projektstatus](../docs/HW24_PROJEKTSTAND_AKTUELL.md) ist entsprechend dem neuen bestätigten Stand aktualisiert **oder** begründet unverändert
- [ ] [Arbeitsjournal](../docs/HW24_ARBEITSJOURNAL.md) um tatsächlich durchgeführten Schritt, Prüfergebnis, SHA und nächste Aktion ergänzt (direkt in der zentralen Dokumentationsquelle `main`; nicht in öffentlich deploybaren Website-Baum kopieren)

## Freigabe
- [ ] Kein automatisches Merge/Deployment in `deploy/staging` ausgelöst
- [ ] Testabnahme erfolgt bzw. ausdrücklich als offen ausgewiesen
- [ ] Produktive Wawi/Live-Website bleiben unangetastet, solange nicht gesondert freigegeben

**Genau nächster unerledigter Schritt:**

**Wichtige offene Informationen (falls vorhanden, ohne Wiederholung erledigter Schritte):**
