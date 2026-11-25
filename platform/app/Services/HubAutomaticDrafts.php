<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

/**
 * Explicitly scoped background intake preparation. Only the selected open
 * TEST batch of the configured legacy account is eligible. Existing and
 * manually edited drafts are never touched. No provider call or release.
 */
final class HubAutomaticDrafts
{
    public function __construct(private LegacyReadBridge $read,
        private LegacyApprovedActionBridge $write,private HubBatchPreparation $prepare,
        private HubStockReader $stock){}

    public static function allowedBatch(array $batches,int $id): bool
    {
        if($id<1)return false;
        foreach($batches as $batch)
            if(is_array($batch)&&(int)($batch['id']??0)===$id&&
                preg_match('/^TEST(?:\b|[-_])/iu',(string)($batch['label']??'')))return true;
        return false;
    }

    public static function eligible(array $row): bool
    {
        if(($row['resolution']??null)!=='new_candidate'||($row['review_state']??null)!=='scan_only'||
            (int)($row['id']??0)<1||(int)($row['revision']??-1)!==0||($row['draft']??[])!==[])return false;
        try{ResearchQueue::canonical((string)($row['ean']??''));}catch(\Throwable){return false;}
        return true;
    }

    public function run(int $batchId,bool $confirmed,bool $allowOnePaidReview=false): array
    {
        if(!$confirmed||!app()->runningInConsole()||config('hw24_research_queue.enabled')!==true||
            config('hw24_research_queue.scan_intake_enabled')!==true||
            config('hw24_legacy.scan_writes_enabled')!==true)throw new RuntimeException('Automatic draft preparation not approved');
        $b=$this->read->fetch('scan.batches',['op'=>'batches']);
        if(($b['ok']??false)!==true||!self::allowedBatch($b['data']['batches']??[],$batchId))
            throw new RuntimeException('Open TEST batch not verified');
        $r=$this->read->fetch('scan.items',['op'=>'items','batch_id'=>$batchId]);
        if(($r['ok']??false)!==true||!is_array($r['data']['items']??null))throw new RuntimeException('Batch unavailable');
        $items=$r['data']['items'];
        if(count($items)>=3000)throw new RuntimeException('Batch limit reached');
        $rows=[];
        foreach($items as $row){
            if(!is_array($row)||!self::eligible($row))continue;
            $rows[]=['id'=>(int)$row['id'],'revision'=>0,'ean'=>(string)$row['ean'],
                'resolution'=>'new_candidate','review'=>'scan_only','draft'=>[]];
        }
        $summary=['batch'=>$batchId,'eligible'=>count($rows),'saved'=>0,'waiting'=>0,'exceptions'=>0,'status'=>'ok'];
        if(!$rows)return $summary;
        $jobs=ResearchQueueRuntime::queue()->statuses(array_column($rows,'ean'));
        $completed=[];
        foreach($rows as $row){
            $job=$jobs[$row['ean']]??[];
            if(($job['status']??null)!=='completed'){$summary['waiting']++;continue;}
            if(($job['evidence']['jtl_state']??null)!=='new_candidate'){$summary['exceptions']++;continue;}
            $completed[]=$row;
        }
        if(!$completed)return $summary;
        // At most 50 read-only evaluations and 3 draft writes per cycle.
        $completed=array_slice($completed,0,50);
        $stock=$this->stock->read(array_column($completed,'ean'));
        if(($stock['ok']??false)!==true)throw new RuntimeException('Fresh mirror required');
        // Limit archive requests and provider time per CLI invocation.
        $confirmedRows=[];
        foreach($completed as $row){
            if(($stock['items'][$row['ean']]['state']??null)!=='absent'){$summary['exceptions']++;continue;}
            $confirmedRows[]=$row;
        }
        $completed=array_slice($confirmedRows,0,3);
        if(!$completed)return $summary;
        $proposals=$this->prepare->prepare($completed,$jobs,$allowOnePaidReview);
        foreach($completed as $row){
            if($summary['saved']>=3)break;
            $ean=$row['ean'];$p=$proposals[$ean]??[];
            if(($p['ai']['state']??null)==='paid_not_confirmed'){
                $summary['status']='paid_review_not_confirmed';return $summary;
            }
            if(($stock['items'][$ean]['state']??null)!=='absent'||
                !in_array($p['state']??null,['review','missing'],true)||empty($p['fields'])||
                ($p['revision']??null)!==0||($p['item_id']??null)!==$row['id']){
                $summary['exceptions']++;continue;
            }
            if(HubCategoryPolicy::conflict((string)($p['draft']['Artikelname']??''),(string)($p['draft']['Warengruppe']??''),
                (string)($p['draft']['Kategorie Ebene 1']??''),(string)($p['draft']['Kategorie Ebene 2']??''))){
                $summary['exceptions']++;continue;
            }
            $sources=[];
            foreach($p['fields'] as $f=>$v){
                if(!in_array($f,ScanResearch::FIELDS,true)||!is_string($v)||$v===''||!is_string($p['sources'][$f]??null))
                    throw new RuntimeException('Invalid prepared field');
                $sources[$f]='Automatischer TEST-Entwurf: '.$p['sources'][$f];
            }
            $reply=$this->write->post('scan.edit',['op'=>'edit','batch_id'=>$batchId,'item_id'=>$row['id'],
                'expected_revision'=>0,'fields'=>$p['fields'],'field_sources'=>$sources,'mark_ready'=>false]);
            $d=$reply['data']??[];
            if(($reply['ok']??false)!==true||($d['review_state']??null)!=='draft'||(int)($d['revision']??-1)!==1||
                (int)($d['item_id']??0)!==$row['id']){
                $summary['status']='write_not_confirmed';return $summary;
            }
            $summary['saved']++;
        }
        return $summary;
    }
}
