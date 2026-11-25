<?php
declare(strict_types=1);
namespace App\Services;

/** Shared AI review: never treat model output as an authoritative product identifier. */
final class HubAiEvidence
{
    public function __construct(private LegacyReadBridge $read){}

    public function read(string $ean,array $catalog): array
    {
        return self::evaluate($ean,$catalog,$this->read->fetch('ai.history',['ean'=>$ean,'limit'=>1]));
    }

    public static function evaluate(string $ean,array $catalog,array $response): array
    {
        $raw=$response['data']??[];
        if(($response['ok']??false)!==true||($raw['scope']??null)!=='shared_workspace'||
            !is_array($raw['reviews']??null))return ['state'=>'unavailable'];
        if($raw['reviews']===[])return ['state'=>'missing'];
        $row=$raw['reviews'][0];$input=$row['input']??[];$review=$row['review']??[];
        $p=$catalog['item']??[];
        $id=(int)($row['id']??0);
        $base=['id'=>$id,'at'=>ScanEntryPresentation::germanDate($row['created_at_utc']??null)];
        try{
            if($id<1||ResearchQueue::canonical((string)($row['ean']??''))!==ResearchQueue::canonical($ean)||
                ResearchQueue::canonical((string)($input['article']['ean']??''))!==ResearchQueue::canonical($ean))
                return ['state'=>'identity_conflict']+$base;
        }catch(\Throwable){return ['state'=>'identity_conflict']+$base;}
        $c=$input['amazon_candidates']??null;
        if(!is_array($c)||count($c)!==1||!is_array($c[0])||
            ($c[0]['asin']??null)!==($p['asin']??null)||($c[0]['title']??null)!==($p['title']??null)||
            ($c[0]['ean_confirmed']??null)!==true||($c[0]['pack_kind']??null)!=='single'||
            (int)($c[0]['pack_units']??0)!==1||
            ($c[0]['brand']??'')!==($p['brand']??''))
            return ['state'=>'stale']+$base;
        $status=$review['status']??null;
        $checks=$review['asinChecks']??null;
        if($status==='mismatch')return ['state'=>'identity_conflict']+$base;
        if($status!=='pass'||!is_array($checks)||count($checks)!==1||
            ($checks[0]['asin']??null)!==($p['asin']??null)||($checks[0]['status']??null)!=='pass')
            return ['state'=>'review']+$base;
        $facts=$review['productFacts']??[];
        if(isset($facts['packUnits'])&&(int)$facts['packUnits']!==1)return ['state'=>'identity_conflict']+$base;
        $txt=static fn(mixed $v,int $max):string=>is_string($v)?mb_substr(trim($v),0,$max):'';
        $group=$txt($review['suggestedWarengruppe']??null,150);
        $title=$txt($facts['suggestedTitle']??null,500);
        return ['state'=>'verified_review','group_hint'=>$group,'title_hint'=>$title,
            'notes'=>$txt($facts['notes']??null,1000),'model'=>$txt($row['model']??null,80)]+$base;
    }

    /** AI only narrows a real template query. It does not create JTL groups. */
    public static function groupHint(array $draft,array $ai): string
    {
        if(($ai['state']??null)!=='verified_review'||trim((string)($draft['Warengruppe']??''))!=='')return '';
        $group=trim((string)($ai['group_hint']??''));
        if($group==='')return '';
        $target=HubCategoryPolicy::suggest((string)($draft['Artikelname']??''));
        $candidate=HubCategoryPolicy::suggest($group);
        return $target!==null&&$target===$candidate?$group:'';
    }
}
