<?php
declare(strict_types=1);
namespace App\Services;

/** The deployment monitor is not a general application-health probe. */
final class HubDeploymentStatus
{
    public static function present(string $status,string $incident,bool $pending,int $now):array
    {
        $parts=preg_split('/\s+/',trim($status),4);
        $stamp=strtotime($parts[0]??'');
        $kind=$parts[1]??'';
        $head=preg_match('/^HEAD=([a-f0-9]{40})$/D',$parts[2]??'',$m)?substr($m[1],0,7):'';
        $fresh=$stamp!==false&&$stamp<=$now+60&&$now-$stamp<=360;
        $healthy=$fresh&&$head!==''&&in_array($kind,['CURRENT','DEPLOYED'],true);
        $prior=strtotime(explode(' ',trim($incident),2)[0]??'');
        $historical=$healthy&&$prior!==false&&$prior<$stamp;
        return ['healthy'=>$healthy,'fresh'=>$fresh,'head'=>$head,'kind'=>$kind,
            'checked'=>$stamp!==false?gmdate('d.m.Y H:i:s',$stamp).' UTC':'Unbekannt',
            'incident_history'=>$historical,'notification_pending'=>$pending,
            'label'=>$healthy?'TEST-Version aktuell':(!$fresh?'Deployment-Überwachung nicht aktuell':'Deployment prüfen')];
    }
}
