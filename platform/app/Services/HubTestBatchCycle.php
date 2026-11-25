<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

/** One explicitly approved cycle for one open TEST intake batch. */
final class HubTestBatchCycle
{
    public function __construct(private LegacyReadBridge $read,private HubAutomaticDrafts $drafts){}

    /** Research is once per valid GTIN, never once per captured unit. */
    public static function intake(array $items): array
    {
        $out=[];
        foreach($items as $r){
            if(!is_array($r)||($r['review_state']??null)==='removed'||
                !in_array($r['resolution']??null,['existing','new_candidate'],true))continue;
            $ean=$r['ean']??null;
            if(!is_string($ean))continue;
            try{$key=ResearchQueue::canonical($ean);}catch(\Throwable){continue;}
            if(!isset($out[$key]))$out[$key]=$ean;
        }
        return array_values($out);
    }

    public static function scope(array $jobs): array
    {
        $ids=[];
        foreach($jobs as $job){
            if(!in_array($job['status']??null,['queued','retry'],true))continue;
            $id=(int)($job['id']??0);
            if($id>0)$ids[$id]=$id;
        }
        return array_values($ids);
    }

    public function run(int $batchId,bool $confirmed,bool $allowResearch): array
    {
        if(!$confirmed||!app()->runningInConsole()||
            config('hw24_research_queue.enabled')!==true||
            config('hw24_research_queue.scan_intake_enabled')!==true||
            config('hw24_legacy.scan_writes_enabled')!==true)
            throw new RuntimeException('TEST cycle not approved');
        $b=$this->read->fetch('scan.batches',['op'=>'batches']);
        if(($b['ok']??false)!==true||!HubAutomaticDrafts::allowedBatch($b['data']['batches']??[],$batchId))
            throw new RuntimeException('Open TEST batch not verified');
        $r=$this->read->fetch('scan.items',['op'=>'items','batch_id'=>$batchId]);
        if(($r['ok']??false)!==true||!is_array($r['data']['items']??null)||count($r['data']['items'])>=3000)
            throw new RuntimeException('Batch unavailable or incomplete');
        $eans=self::intake($r['data']['items']);
        $result=['batch'=>$batchId,'identities'=>count($eans),'enqueued'=>0,'research'=>null,'drafts'=>null,'status'=>'ok'];
        if($allowResearch&&$eans){
            $q=ResearchQueueRuntime::queue();
            // Instantiate and verify worker before adding new work.
            $worker=ResearchQueueRuntime::worker($this->read);
            $jobs=$q->statuses($eans);
            $missing=[];
            foreach($eans as $ean)if(!isset($jobs[$ean]))$missing[]=$ean;
            if($missing){
                foreach($q->enqueueMany(array_slice($missing,0,3),'test_batch_cli') as $added)
                    if($added['created'])$result['enqueued']++;
                $jobs=$q->statuses($eans);
            }
            $scope=self::scope($jobs);
            if($scope)$result['research']=$worker->runOne('test_batch_cli',$scope);
        }
        // Always reuse completed evidence even when the provider queue is paused.
        // This mode never triggers paid OpenAI or an export/stock/marketplace write.
        $result['drafts']=$this->drafts->run($batchId,true,false);
        if(($result['drafts']['status']??null)!=='ok')$result['status']='draft_not_confirmed';
        return $result;
    }
}
