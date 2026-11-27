<?php
declare(strict_types=1);
namespace App\Services;

use App\Domain\Amazon\OfferEvidence;

/** Shared operator-triggered provider actions. No writes on rendering, no implicit paid calls. */
final class HubArticleResearchWorkspace
{
    /** Existing encrypted observation, visibly historical; no pricing call. */
    public static function archivedOffers(string $asin,LegacyReadBridge $bridge):array
    {
        if(!AmazonPriceArchiveRuntime::enabled())return [];
        try{$row=AmazonPriceArchiveRuntime::open()->latest($asin,1)[0]??null;}catch(\Throwable){return [];}
        if(!$row)return [];
        $raw=['retrieved_at_utc'=>$row['observed_at_utc'].' UTC','total_offer_count'=>$row['total_offer_count'],
            'buybox_prices'=>$row['buybox'],'lowest_prices'=>$row['lowest'],'offers'=>[]];
        foreach($row['offers'] as $offer){
            if(!is_array($offer))continue;
            $raw['offers'][]=['seller_id'=>$offer['seller_id']??null,'item_price'=>$offer['item']??null,
                'shipping'=>$offer['shipping']??null,'total'=>$offer['total']??null,'is_fba'=>$offer['fba']??null,
                'is_buybox_winner'=>$offer['buybox']??null,'condition'=>$offer['condition']??null];
        }
        $names=[];$ids=OfferEvidence::sellerIds($raw);
        if($ids){
            try{$n=$bridge->fetch('amazon.sellers',['ids'=>implode(',',$ids)]);
                if(($n['ok']??false)===true&&is_array($n['data']['names']??null))$names=$n['data']['names'];
            }catch(\Throwable){}
        }
        return ['ok'=>true,'asin'=>$asin,'source'=>'archive','archive_id'=>$row['id']]+OfferEvidence::present($raw,$names);
    }
    public static function offers(string $asin,LegacyReadBridge $bridge):array
    {
        $capture=AmazonPriceArchiveRuntime::fetch($asin,$bridge);
        $r=$capture['response'];$raw=$r['data']??[];
        if(($r['ok']??false)!==true||!is_array($raw)){
            $old=self::archivedOffers($asin,$bridge);
            $delay=(int)($r['retry_after_seconds']??0);
            $msg=($r['http_status']??null)===429?'Amazon-Abfrage derzeit gedrosselt. '.($delay>0?'Erneut frühestens in '.$delay.' Sekunden.':'Bitte später erneut prüfen.'):'Amazon-Angebote nicht bestätigt.';
            return $old?($old+['message'=>$msg]):['ok'=>false,'message'=>$msg];
        }
        $names=[];$ids=OfferEvidence::sellerIds($raw);
        if($ids){
            $n=$bridge->fetch('amazon.sellers',['ids'=>implode(',',$ids)]);
            if(($n['ok']??false)===true&&is_array($n['data']['names']??null))$names=$n['data']['names'];
        }
        $board=OfferEvidence::present($raw,$names);
        return ['ok'=>true,'asin'=>$asin,'archive'=>$capture['archive']]+$board;
    }
    public static function aiOnce(string $ean,array $article,LegacyReadBridge $read,LegacyApprovedActionBridge $write,bool $web,array $catalog=[]):array
    {
        ResearchQueue::canonical($ean);
        if(config('hw24_legacy.ai_paid_enabled')!==true)return ['ok'=>false,'message'=>'Kostenpflichtige KI nicht freigegeben.'];
        $archive=$read->fetch('ai.history',['ean'=>$ean,'limit'=>1]);$raw=$archive['data']??[];
        if(($archive['ok']??false)!==true||($raw['scope']??null)!=='shared_workspace'||!is_array($raw['reviews']??null))
            return ['ok'=>false,'message'=>'Gemeinsames KI-Archiv nicht bestätigt. Keine kostenpflichtige Anfrage.'];
        if($raw['reviews'])return ['ok'=>false,'message'=>'Bericht bereits vorhanden. Vorhandenes Archiv verwenden.'];
        $budget=$read->fetch('ai.budget');
        if($block=HubManualAiResearch::budgetBlock($budget,$web))return ['ok'=>false,'message'=>$block];
        $name=mb_strcut(trim((string)($article['artikelname']??'')),0,500);
        if($name===''&&!$web)return ['ok'=>false,'message'=>'Produktname fehlt.'];
        $payload=['ean'=>$ean,'articleName'=>$name,'brand'=>mb_strcut((string)($article['hersteller']??''),0,180),
            'warengruppe'=>mb_strcut((string)($article['warengruppe_name']??''),0,150),'category'=>'','unit'=>'','jtl_status'=>'current_snapshot','candidates'=>[]];
        if($web)$payload['review_version']=3;
        else{
            $p=$catalog['item']??[];
            if(($p['gtin_match']??false)!==true||($p['pack_kind']??null)!=='single'||
                ($p['pack_units']??null)!==1||!preg_match('/^[A-Z0-9]{10}$/D',(string)($p['asin']??''))||
                (!empty($article['asin'])&&$article['asin']!==$p['asin'])||
                (!empty($article['hersteller'])&&!HubManufacturerPolicy::equivalent($article['hersteller'],(string)($p['manufacturer']??'')))||
                !in_array(2,$budget['data']['review_versions']??[],true))
                return ['ok'=>false,'message'=>'Für exportierbare KI-Textvorschläge ist ein bestätigter Amazon-Einzelartikel und die erweiterte KI-Schnittstelle erforderlich. Keine neue Kostenabfrage.'];
            if(mb_strlen((string)$p['title'])>500||mb_strlen((string)($p['brand']??''))>180)return ['ok'=>false,'message'=>'Produktkontext zu lang; keine Kostenabfrage.'];
            $payload['review_version']=2;$payload['product_facts']=HubAiContent::facts($catalog);
            $payload['candidates']=[['asin'=>$p['asin'],'title'=>$p['title'],'brand'=>$p['brand']??'','eanConfirmed'=>true,'packKind'=>'single','packUnits'=>1]];
        }
        $r=$write->post('ai.review',$payload);
        return ['ok'=>($r['ok']??false)===true,'message'=>($r['ok']??false)===true?'KI-Antwort erhalten. Archiv erneut prüfen.':'KI-Auftrag nicht bestätigt. Vor Wiederholung Archiv und Kosten kontrollieren.'];
    }
}
