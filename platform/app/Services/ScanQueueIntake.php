<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

/** Scan UI only: enqueue/status, never claim/ack, never external provider calls. */
class ScanQueueIntake
{
    public function enabled(): bool
    {
        return config('hw24_research_queue.enabled')===true && config('hw24_research_queue.scan_intake_enabled')===true;
    }
    public function automatic(): bool
    {
        return $this->enabled() && config('hw24_research_queue.scan_auto_enqueue')===true;
    }
    public function enqueue(array $eans): array
    {
        $id=auth()->id();
        if(!$this->enabled()||$id===null)throw new RuntimeException('Queue intake not authorized');
        $q=ResearchQueueRuntime::queue();$total=0;$new=0;
        foreach(array_chunk(array_values(array_unique($eans)),1000) as $chunk){
            foreach($q->enqueueMany($chunk,'filament:'.(string)$id) as $row){
                $total++;if($row['created'])$new++;
            }
        }
        return ['total'=>$total,'new'=>$new];
    }
    public function statuses(array $eans): array
    {
        if(!$this->enabled()||auth()->id()===null)throw new RuntimeException('Queue intake not authorized');
        return ResearchQueueRuntime::queue()->statuses($eans);
    }
}
