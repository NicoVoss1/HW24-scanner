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

    /** One new analysis only from explicitly confirmed CLI pilot, never retry on uncertain outcome. */
    public function analyze(string $ean,array $catalog,array $draft,LegacyApprovedActionBridge $write): array
    {
        if(!app()->runningInConsole()||config('hw24_legacy.ai_paid_enabled')!==true)
            return ['state'=>'paid_disabled'];
        $previous=$this->read($ean,$catalog);
        if($previous['state']!=='missing')return $previous;
        $r=$this->read->fetch('ai.budget');$b=$r['data']??[];
        if(($r['ok']??false)!==true||($b['enabled']??false)!==true||($b['archive_ready']??false)!==true||
            !is_numeric($b['usd_month']??null)||!is_numeric($b['usd_budget']??null)||
            (float)$b['usd_month']<0||(float)$b['usd_budget']<=0||
            (float)$b['usd_month']>=(float)$b['usd_budget']-0.04||
            !is_numeric($b['daily_calls']??null)||!is_numeric($b['daily_limit']??null)||
            (int)$b['daily_calls']>=(int)$b['daily_limit'])
            return ['state'=>'budget_blocked'];
        $p=$catalog['item']??[];
        if(($p['gtin_match']??false)!==true||($p['pack_kind']??null)!=='single'||($p['pack_units']??null)!==1||
            trim((string)($p['title']??''))===''||!preg_match('/^[A-Z0-9]{10}$/D',(string)($p['asin']??'')))
            return ['state'=>'identity_conflict'];
        $payload=['ean'=>$ean,'articleName'=>mb_strcut($p['title'],0,600),
            'brand'=>mb_strcut((string)($draft['Hersteller']??''),0,180),
            'warengruppe'=>mb_strcut((string)($draft['Warengruppe']??''),0,150),
            'category'=>mb_strcut((string)($draft['Kategorie Ebene 2']??''),0,150),
            'unit'=>mb_strcut((string)($draft['Maßeinheit']??''),0,80),
            'jtl_status'=>'new_candidate','candidates'=>[[
                'asin'=>$p['asin'],'title'=>mb_strcut($p['title'],0,500),'brand'=>mb_strcut((string)($p['brand']??''),0,180),
                'eanConfirmed'=>true,'packKind'=>'single','packUnits'=>1,
            ]]];
        // Truncation of identity evidence could make the archived comparison invalid.
        if($payload['candidates'][0]['title']!==$p['title']||$payload['candidates'][0]['brand']!==($p['brand']??''))
            return ['state'=>'review'];
        // Request the extended format only from an explicitly advertising endpoint.
        if(in_array(2,$b['review_versions']??[],true)){
            $payload['review_version']=2;
            $payload['product_facts']=HubAiContent::facts($catalog);
        }else{
            return ['state'=>'extended_api_unavailable'];
        }
        $answer=$write->post('ai.review',$payload);
        if(($answer['ok']??false)!==true||($answer['data']['stored']??false)!==true||
            (int)($answer['data']['review_id']??0)<1)return ['state'=>'paid_not_confirmed'];
        $archived=$this->read($ean,$catalog);
        if((int)($archived['id']??0)!==(int)$answer['data']['review_id'])return ['state'=>'paid_not_confirmed'];
        return $archived;
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
            'notes'=>$txt($facts['notes']??null,1000),'model'=>$txt($row['model']??null,80),
            'content'=>HubAiContent::validate($review,$input,$catalog)]+$base;
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
