@php
    $deploymentDir=dirname(base_path(),2).'/deploy-state';
    $readDeploymentLine=static function(string $name) use($deploymentDir): string {
        $p=$deploymentDir.'/'.$name;
        if(!is_file($p)||is_link($p)||!is_readable($p))return '';
        $line=@file_get_contents($p,false,null,0,2048);
        return is_string($line)?trim($line):'';
    };
    $deploymentStatus=$readDeploymentLine('monitor.status');
    $deploymentIncident=$readDeploymentLine('last-incident');
    $deploymentPending=$readDeploymentLine('mail-pending')!=='';
    $deploymentStamp=$deploymentStatus!==''?strtotime(explode(' ',$deploymentStatus,2)[0]):false;
    $deploymentStale=$deploymentStamp===false||time()-$deploymentStamp>360||$deploymentStamp>time()+60;
    $deploymentError=$deploymentStale||preg_match('/^\S+ (?:FAILED|ROLLBACK|BLOCKED) /',$deploymentStatus);
@endphp
<div role="status" style="margin-top:12px;border:1px solid {{ $deploymentError?'#dc2626':'#cbd5e1' }};border-radius:10px;padding:12px;font-size:12px;overflow-wrap:anywhere">
    <strong>TEST-Bereitstellung · {{ $deploymentError?'Prüfung erforderlich':'Überwachung aktiv' }}</strong>
    @if($deploymentStale)
        <p>Keine aktuelle Bestätigung der Deployment-Überwachung. Serverstatus prüfen.</p>
    @endif
    @if($deploymentStatus!=='')<p>{{ $deploymentStatus }}</p>@endif
    @if($deploymentIncident!=='')
        <details @if($deploymentError||$deploymentPending) open @endif>
            <summary style="cursor:pointer;font-weight:700;color:#b45309">Letzter gespeicherter Fehler / Rollback</summary>
            <p>{{ $deploymentIncident }}</p>
            @if($deploymentPending)<p>E-Mail-Warnung noch nicht vom Maildienst angenommen.</p>@endif
            <p>Der Vorfall bleibt auch nach einem erfolgreichen Update dokumentiert.</p>
        </details>
    @endif
</div>
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
