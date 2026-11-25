<details style="margin-top:12px;border:1px solid #dbe5ef;border-radius:10px;padding:12px;font-size:12px">
    <summary style="cursor:pointer;font-weight:700">Modulstatus · TEST-Umgebung</summary>
    <p style="margin:8px 0">Entwicklungsnachweis je Funktionsumfang. Die Anzeige ersetzt keine fachliche Freigabe. Produktiver Einsatz der neuen Module ist separat zu bestätigen.</p>
    <div style="display:grid;gap:8px">
        @foreach([
            ['Scan-Erfassung / Wiederverwendung','Ja','Ja','Ja','Einzel-Scan bestätigt','Nein'],
            ['CSV-Import und Queue-Anmeldung','Ja','Ja','Ja','CSV-Praxistest offen','Nein'],
            ['Amazon / verschlüsseltes Preisarchiv','Ja','Ja','Ja','Live-Daten bestätigt','Nein'],
            ['Persistente Recherche / Multi-User','Ja','Ja','Ja','CLI und Paralleltest bestätigt','Nein'],
            ['JTL-Feldprüfung / Vergleichsartikel','Ja','Ja','Ja','Vergleichslesezugriff bestätigt','Nein'],
            ['Drei-Schritte-Bedienung','Ja','CI separat prüfen','Diese Ansicht installiert','Browserabnahme offen','Nein'],
            ['Zeitgesteuerte Recherche','Teilweise','Teilweise','Nicht zeitgesteuert','Pilot offen','Nein'],
            ['Rollierende Bestandspflege / Lernhistorie','Geplant','Offen','Nicht installiert','Offen','Nein'],
        ] as $row)
            <div style="border-top:1px solid #dbe5ef;padding-top:8px;overflow-wrap:anywhere">
                <strong>{{ $row[0] }}</strong>
                <div>Programmiert: {{ $row[1] }} · Getestet: {{ $row[2] }} · TEST: {{ $row[3] }}</div>
                <div>Fachlich: {{ $row[4] }} · Produktiv freigegeben: {{ $row[5] }}</div>
            </div>
        @endforeach
    </div>
    <p style="margin-top:8px">Nachweis bisheriger Module: 25.11.2026, TEST-Stand f2941072. Neue Funktionen separat abnehmen.</p>
</details>
