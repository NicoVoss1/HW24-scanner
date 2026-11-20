# HW24 Scanliste: einmaliger PuTTY-Start (V4.22 TEST)
**Stand:** 20.11.2026 · **Produktivseite unberührt** · **SQL-Ausführung noch nicht bestätigt**

## Nachgewiesener Befund vom Nutzer-Screenshot (22:15 Uhr MEZ)
- Neue `/scanliste.html` funktioniert als Oberfläche und ist über Plesk veröffentlicht.
- In der gezielten read-only Diagnose steht: **Die bisherige JTL-/MariaDB-Integration muss dafür nicht neu eingerichtet werden.**
- Neue Scanlisten-Tabellen fehlen bzw. sind nicht vollständig (**V11**); neue Amazon-Stichwortcache-Tabelle fehlt (**V12**); private Zusatz-Konfigurationsdatei `hw24_private/scanlist-db.php` fehlt.
- Beim Erfassungsversuch erscheint **„Bitte zuerst einen Stapel erstellen“**. Das ist Folge des noch nicht konfigurierten Scanlisten-Backends. Eine EAN wurde noch nicht serverseitig gespeichert.
- Bereits vorhandene Plesk-/MariaDB-Sicherungen und das Verzeichnis-/GitHub-Setup wurden **schon erledigt**, keine Wiederholung.
- Der originale Amazon-SP-API-Zugang bleibt bestehend. Die neuen Schreibvorgänge für `hw24_amazon_keyword_cache` wurden im Test-PR #19 auf eine **getrennte** Scanner-DB-Verbindung umgestellt, damit die bisherige read-only `db.php` geschützt bleibt.

## Nächster Arbeitsschritt (Nutzer in PuTTY)

**Kein Passwort in Chat oder GitHub eintragen.** Der Installer erzeugt selbst ein neues 64-stelliges Zufallspasswort, speichert es ausschließlich in der privaten Datei und gibt es nicht aus. Die vorhandene `db.php` wird nicht überschrieben. Die bestehende Plesk-/MariaDB-Sicherung bleibt gültiger Ausgangspunkt; nicht erneut anfordern.

1. In Plesk auf **`deploy/staging`** bleiben und nach Merge von [PR #19](https://github.com/NicoVoss1/HW24-scanner/pull/19) **„Jetzt Pull ausführen“ → „Jetzt bereitstellen“**. Nur Testdomain.
2. Per **PuTTY/SSH** auf dem **gleichen Plesk-Testserver** anmelden. In die Root-Shell wechseln: `sudo -i` (bei fehlender Berechtigung nicht improvisieren; Abbruchmeldung melden).
3. Den geprüften Installer aus dem GitHub-Commit `a3f0d8592a926fb348b589a76b2170cee1ab67c3` laden, Syntax prüfen, Inhalt ansehen, dann einmalig ausführen:

```bash
sudo -i
curl -fL --proto '=https' --tlsv1.2 \
  'https://raw.githubusercontent.com/NicoVoss1/HW24-scanner/a3f0d8592a926fb348b589a76b2170cee1ab67c3/ops/HW24_INSTALL_SCANLIST_TEST.sh' \
  -o /root/HW24_INSTALL_SCANLIST_TEST.sh
bash -n /root/HW24_INSTALL_SCANLIST_TEST.sh
less /root/HW24_INSTALL_SCANLIST_TEST.sh
bash /root/HW24_INSTALL_SCANLIST_TEST.sh
```

4. Das Skript sucht **ausschließlich** eine `test-artikelmanager`-API im Plesk-Verzeichnis, berechnet aus dem bestehenden PHP-Code den privaten Pfad `hw24_private` und verifiziert `db.php` ohne sie zu verändern. Es verlangt die explizite Eingabe **TEST** vor der Änderung.
5. Über `plesk db` wird ausschließlich die bekannte MariaDB `hw24_artikelmanager` angesprochen. `CREATE TABLE IF NOT EXISTS` legt die **vier neuen Scanner-Tabellen** und die **eine Cache-Tabelle** an. Es erstellt den separaten minimal privilegierten Nutzer `hw24_scan_rw` mit zufälligem Passwort für `localhost` und `127.0.0.1`, Tabellen-spezifischen `SELECT/INSERT/UPDATE` bzw. `SELECT`-Rechten (keine DDL/DELETE/JTL-Schreibrechte), legt die private `scanlist-db.php` mit den Eigentümerdaten der bereits funktionierenden `db.php` an und setzt Dateirechte 0640.
6. Die Installation ist **kein** JTL-Wawi-Import und schreibt keine Lager-/WMS-Bestände. Das Skript **überschreibt keinen bestehenden privaten Scanlist-Zugang**; bei Zweifeln oder fehlenden Shell-Rechten bricht es ab.

## Einziger Test danach

- **Testseite:** https://test-artikelmanager.hairworld24.de/scanliste.html
- Browser neu laden, lesen, ob der Diagnosekasten verschwindet und die Scan-Stapel-Auswahl funktioniert.
- Stapel `04-001` anlegen; danach EAN `8033219164159` aus dem Screenshot scannen. Der gespeicherte Scan muss nach erneutem Seitenaufruf in der Übersicht erscheinen.
- Bei einem Fehler **nur dessen Wortlaut/HTTP-Status ohne Passwörter** übermitteln. Keine ungezielte Wiederholung der ganzen Einrichtung und nicht einfach den Installer noch einmal ausführen.

## Nachweis- und Statusliste

| Thema | Status |
|---|---|
| Test-UI und Screenshotbefund | **Bestätigt, 20.11.2026** |
| Amazon-DB-Konto-Isolation | **Code gemerged**, PR #19, Staging-SHA `2175c375fdce4afe2b335dafb43167505f7123a0` |
| Installer-PuTTY-Code | **Vorbereitet, nicht auf Plesk ausgeführt** |
| Bash-Syntax/PHP-Lint | **GitHub Actions-Initialprüfung grün**: [Run #44819368745](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44819368745). Nach der Verbesserung der eindeutigen Testpfadsuche folgt erneute Prüfung: [Run #44819742904](https://github.com/NicoVoss1/HW24-scanner/actions/runs/44819742904) |
| Tabellen und privater DB-Nutzer | **FEHLT laut Diagnose / noch nicht installiert** |
| Erfolgreiche EAN-Scans in MariaDB | **NOCH NICHT BESTÄTIGT** |
| JTL-Wawi/Ameise-Echtimport | **NICHT durchgeführt** |
| Live-Site / Live-Wawi | **Unverändert** |

**Ausweichweg:** Wenn PuTTY kein root/sudo oder keinen Plesk-DB-Zugriff hat, den Installer **nicht** mit anderen Privilegien probieren. Dann stattdessen die zuvor vorbereiteten Migrationen `sql/HW24_11_SCANLISTE.sql`, `sql/HW24_12_AMAZON_KEYWORD_CACHE.sql` im authentifizierten Plesk-phpMyAdmin importieren und per Plesk DB-Verwaltung den minimalen Nutzer anlegen. Die Benutzerrechte müssen vor Freigabe separat geprüft werden.

Weitere Dokumentation: [Projektstatus](./HW24_PROJEKTSTAND_AKTUELL.md) · [Arbeitsjournal](./HW24_ARBEITSJOURNAL.md). Keine Secrets im GitHub.
