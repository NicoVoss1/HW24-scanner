<?php
declare(strict_types=1);
namespace App\Services;

use PDO;

/** Overview only, for batch IDs already authorized by the legacy account. */
final class HubBatchOverview
{
    public function __construct(private HubStockReader $stock){}
    public function read(array $authorizedIds): array
    {
        $ids=array_values(array_unique(array_filter($authorizedIds,fn($v)=>is_int($v)&&$v>0)));
        $actor=(string)config('hw24_legacy.user');
        if(!$ids||count($ids)>50||$actor==='')return [];
        $db=HubPeerMirror::connect();
        $scope=' WHERE BINARY created_by=BINARY ? AND batch_id IN ('.implode(',',array_fill(0,count($ids),'?')).')';
        $args=[$actor,...$ids];$out=[];
        foreach($ids as $id)$out[$id]=['positions'=>0,'quantity'=>0,'stock'=>null];
        $db->beginTransaction();
        try{
            $q=$db->prepare('SELECT batch_id,COUNT(*) AS positions,COALESCE(SUM(scan_count),0) AS quantity FROM hw24_hub_scan_summary_source'.$scope.' GROUP BY batch_id');
            $q->execute($args);
            foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)
                $out[(int)$r['batch_id']]=['positions'=>(int)$r['positions'],'quantity'=>(int)$r['quantity'],'stock'=>null];
            $q=$db->prepare('SELECT DISTINCT batch_id,ean FROM hw24_hub_scan_summary_source'.$scope.' LIMIT 15001');
            $q->execute($args);$rows=$q->fetchAll(PDO::FETCH_ASSOC);$db->commit();
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        if(count($rows)>15000)return $out;
        $eans=[];$byBatch=[];
        foreach($rows as $row){
            $ean=(string)$row['ean'];$byBatch[(int)$row['batch_id']][]=$ean;
            try{$key=ResearchQueue::canonical($ean);$eans[$key]=$ean;}catch(\Throwable){}
        }
        if(count($eans)>3000)return $out;
        try{$snapshot=$this->stock->read(array_values($eans));}catch(\Throwable){return $out;}
        if(($snapshot['ok']??false)!==true)return $out;
        $byKey=[];
        foreach($eans as $key=>$ean)$byKey[$key]=$snapshot['items'][$ean]??[];
        foreach($out as $id=>&$v)$v['stock']=self::stockSummary($byBatch[$id]??[],$byKey)+['at'=>$snapshot['at']];
        unset($v);
        return $out;
    }
    public static function stockSummary(array $eans,array $byKey): array
    {
        $out=['total'=>null,'available'=>null,'existing'=>0,'absent'=>0,'unknown'=>0,'total_missing'=>0,'available_missing'=>0];
        $seen=[];$seenArticles=[];$sums=['total'=>0.0,'available'=>0.0];
        foreach($eans as $ean){
            try{$key=ResearchQueue::canonical((string)$ean);}catch(\Throwable){$out['unknown']++;continue;}
            if(isset($seen[$key]))continue;$seen[$key]=true;
            $s=$byKey[$key]??[];
            if(($s['state']??null)==='absent'){$out['absent']++;continue;}
            if(($s['state']??null)!=='existing'||(int)($s['article_id']??0)<1){$out['unknown']++;continue;}
            $id=(int)$s['article_id'];if(isset($seenArticles[$id]))continue;$seenArticles[$id]=true;
            $out['existing']++;
            foreach(['total','available'] as $f){
                if(!is_numeric($s[$f]??null))$out[$f.'_missing']++;
                else $sums[$f]+=(float)$s[$f];
            }
        }
        foreach(['total','available'] as $f)
            if(!$out['unknown']&&!$out[$f.'_missing'])$out[$f]=rtrim(rtrim(number_format($sums[$f],6,'.',''),'0'),'.');
        return $out;
    }
}
