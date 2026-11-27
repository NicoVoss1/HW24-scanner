<?php
declare(strict_types=1);
namespace App\Services;

/** Intake quantities are independent from research jobs and booked JTL/WMS stock. */
final class HubScanSummary
{
    public static function fromRows(array $rows): array
    {
        $out=['positions'=>0,'quantity'=>0,'scan_events'=>0,'events_complete'=>true,
            'existing'=>0,'new'=>0,'unresolved'=>0,'removed'=>0,'possibly_limited'=>count($rows)>=3000];
        foreach($rows as $r){
            if(($r['review']??null)==='removed'){$out['removed']++;continue;}
            $out['positions']++;
            $out['quantity']+=max(0,(int)($r['scans']??0));
            if(!isset($r['scan_events']))$out['events_complete']=false;
            else $out['scan_events']+=max(0,(int)$r['scan_events']);
            $key=match($r['resolution']??null){'existing'=>'existing','new_candidate'=>'new',default=>'unresolved'};
            $out[$key]++;
        }
        return $out;
    }
}
