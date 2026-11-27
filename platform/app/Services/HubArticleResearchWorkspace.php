<?php
declare(strict_types=1);
namespace App\Services;

use App\Domain\Amazon\OfferEvidence;

/** Shared operator-triggered provider actions. No writes on rendering, no implicit paid calls. */
final class HubArticleResearchWorkspace
{
    public static function offers(string $asin,LegacyReadBridge $bridge):array
    {
        $capture=AmazonPriceArchiveRuntime::fetch($asin,$bridge);
        $r=$capture['response'];$raw=$r['data']??[];
        if(($r['ok']??false)!==true||!is_array($raw))return ['ok'=>false,'message'=>'Amazon-Angebote nicht bestätigt.'];
        $names=[];$ids=OfferEvidence::sellerIds($raw);
        if($ids){
            $n=$bridge->fetch('amazon.sellers',['ids'=>implode(',',$ids)]);
            if(($n['ok']??false)===true&&is_array($n['data']['names']??null))$names=$n['data']['names'];
        }
        $board=OfferEvidence::present($raw,$names);
        return ['ok'=>true,'asin'=>$asin,'archive'=>$capture['archive']]+$board;
    }
    public static function aiOnce(string $ean,array $article,LegacyReadBridge $read,LegacyApprovedActionBridge $write,bool $web):array
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
        $r=$write->post('ai.review',$payload);
        return ['ok'=>($r['ok']??false)===true,'message'=>($r['ok']??false)===true?'KI-Antwort erhalten. Archiv erneut prüfen.':'KI-Auftrag nicht bestätigt. Vor Wiederholung Archiv und Kosten kontrollieren.'];
    }
}
