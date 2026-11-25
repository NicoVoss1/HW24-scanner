<?php
declare(strict_types=1);
namespace App\Services;

/** Conservative evidence engine. Same group AND manufacturer, distinct source article IDs.
 * Only shared operational defaults are eligible; never copy product identity, prices,
 * stock, dimensions, fill volume, descriptions, images or seller listings from peers.
 */
final class HubPeerDefaults
{
    public const FIELDS=['Lieferstatus'=>'kLieferStatus','Steuerklasse'=>'kSteuerklasse',
        'Versandklasse'=>'kVersandklasse','Bestandsführung aktiv'=>'cLagerAktiv',
        'Überverkäufe möglich'=>'cLagerKleinerNull','Stückzahl teilbar'=>'cTeilbar'];
    public static function suggest(array $target,array $peers): array
    {
        $group=(int)($target['kWarengruppe']??0);$brand=(int)($target['kHersteller']??0);
        if($group<1||$brand<1)return [];
        $unique=[];
        foreach($peers as $row){
            $id=(int)($row['jtl_article_id']??0);
            if($id<1||$id===(int)($target['jtl_article_id']??0)||
                (int)($row['kWarengruppe']??0)!==$group||(int)($row['kHersteller']??0)!==$brand)continue;
            $unique[$id]=$row;
        }
        if(count($unique)<3)return [];
        $out=[];
        foreach(self::FIELDS as $label=>$key){
            // Existing explicit values, including zero/false, must not be overwritten.
            if(array_key_exists($key,$target)&&$target[$key]!==null&&$target[$key]!=='')continue;
            $counts=[];$ids=[];
            foreach($unique as $id=>$row){
                $v=$row[$key]??null;
                if(!is_scalar($v)||trim((string)$v)==='')continue;
                if(str_starts_with($key,'k')&&(!ctype_digit((string)$v)||(int)$v<1))continue;
                if(str_starts_with($key,'c')&&!in_array((string)$v,['Y','N'],true))continue;
                $v=(string)$v;$counts[$v]=($counts[$v]??0)+1;$ids[$v][]=$id;
            }
            arsort($counts);
            $value=array_key_first($counts);
            if($value===null||$counts[$value]<3||$counts[$value]/count($unique)<0.9)continue;
            $out[$label]=['value'=>(string)$value,'source_field'=>$key,'support'=>$counts[$value],
                'sample_count'=>count($unique),'source_article_ids'=>array_slice($ids[$value],0,25),
                'rule'=>'same_group_and_manufacturer_consensus_v1','approval_required'=>true];
        }
        return $out;
    }
}
