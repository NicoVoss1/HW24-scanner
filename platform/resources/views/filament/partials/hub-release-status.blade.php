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
    $deployment=\App\Services\HubDeploymentStatus::present($deploymentStatus,$deploymentIncident,$deploymentPending,time());
@endphp
<div role="status" style="margin-top:12px;border:1px solid {{ $deployment['healthy']?'#81b5a0':'#dc2626' }};border-radius:8px;padding:10px 12px;font-size:12px;overflow-wrap:anywhere">
    <strong>{{ $deployment['healthy']?'✓':'⚠' }} {{ $deployment['label'] }}</strong>
    @if($deployment['head']!=='') · Version {{ $deployment['head'] }} @endif
    <span> · Geprüft: {{ $deployment['checked'] }}</span>
    @if(!$deployment['fresh'])<p>Keine aktuelle Bestätigung der Deployment-Überwachung. Serverstatus prüfen.</p>@endif
    @if($deploymentPending)<p style="font-weight:600">E-Mail-Warnung noch nicht vom lokalen Maildienst angenommen.</p>@endif
    <details @if(!$deployment['healthy']||$deploymentPending) open @endif>
        <summary style="cursor:pointer;margin-top:5px;font-weight:600">Technische Details @if($deployment['incident_history']) · früherer Vorfall, Deployment wieder aktuell @elseif($deploymentIncident!=='') · letzter gespeicherter Vorfall @endif</summary>
        @if($deploymentStatus!=='')<p>{{ $deploymentStatus }}</p>@endif
        @if($deploymentIncident!=='')<p>{{ $deploymentIncident }}</p>@endif
        <p>Frühere Vorfälle bleiben dokumentiert. Die Anzeige prüft die Bereitstellung, nicht die fachliche Funktion aller Module.</p>
    </details>
</div>
