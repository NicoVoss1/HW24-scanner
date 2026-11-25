<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

/** Current JTL mirror stock, batch read only. No guessed quantities or warehouse writes. */
final class HubStockReader
{
    public static function number(mixed $v): ?string
    {
        if(!is_scalar($v)||!preg_match('/^-?\d{1,15}(?:\.\d{1,13})?$/D',(string)$v))return null;
        $s=(string)$v;
        return str_contains($s,'.')?rtrim(rtrim($s,'0'),'.'):$s;
    }

    public static function present(array $matches): array
    {
        if(count($matches)===0)return ['state'=>'absent'];
        if(count($matches)!==1)return ['state'=>'multiple'];
        $a=$matches[0];
        $out=['state'=>'existing','article_id'=>(int)$a['jtl_article_id'],'total'=>null,'available'=>null,'orders'=>null,'incoming'=>null];
        if(($a['rows_type']??null)!=='ARRAY'||(int)($a['row_count']??0)!==1||
            !in_array($a['truncated_flag']??null,[null,'false','0'],true))return $out;
        foreach(['total'=>'total_stock','available'=>'available_stock','orders'=>'in_orders','incoming'=>'incoming'] as $key=>$src)
            $out[$key]=self::number($a[$src]??null);
        return $out;
    }

    public function read(array $eans): array
    {
        $db=HubPeerMirror::connect();
        $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $db->beginTransaction();
        try {
            $m=$db->query('SELECT source_id,last_completed_utc,last_row_count,age_minutes FROM hw24_hub_mirror_state ORDER BY last_completed_utc DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if(!$m||(int)$m['age_minutes']<0||(int)$m['age_minutes']>45||(int)$m['last_row_count']<1)throw new RuntimeException('Mirror not current');
            $q=$db->prepare('SELECT COUNT(*) FROM hw24_hub_stock_source WHERE source_id=?');
            $q->execute([$m['source_id']]);
            if((int)$q->fetchColumn()!==(int)$m['last_row_count'])throw new RuntimeException('Mirror incomplete');
            $groups=[];
            $eans=array_values(array_unique(array_filter($eans,static fn($v)=>is_string($v)&&preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$v))));
            if(count($eans)>3000)throw new RuntimeException('Batch too large');
            $canonical=[];
            foreach($eans as $ean){try{$canonical[$ean]=ResearchQueue::canonical($ean);}catch(\Throwable){}}
            foreach(array_chunk(array_values(array_unique($canonical)),250) as $chunk){
                $q=$db->prepare('SELECT jtl_article_id,ean,truncated_flag,rows_type,row_count,total_stock,available_stock,in_orders,incoming FROM hw24_hub_stock_source WHERE source_id=? AND CHAR_LENGTH(ean) IN (8,12,13,14) AND LPAD(ean,14,\'0\') IN ('.implode(',',array_fill(0,count($chunk),'?')).')');
                $q->execute([$m['source_id'],...$chunk]);
                while($r=$q->fetch(PDO::FETCH_ASSOC)){
                    try{$key=ResearchQueue::canonical((string)$r['ean']);}catch(\Throwable){continue;}
                    if(in_array($key,$chunk,true))$groups[$key][]=$r;
                }
            }
            $out=[];
            foreach($canonical as $ean=>$key)$out[$ean]=self::present($groups[$key]??[]);
            $db->commit();
            return ['ok'=>true,'at'=>ScanEntryPresentation::germanDate($m['last_completed_utc']),'items'=>$out];
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
}
