<?php
declare(strict_types=1);
namespace App\Services;

use App\Domain\Amazon\OfferEvidence;
use Closure;
use Throwable;

/**
 * Bounded, read-only provider pipeline. Catalog/price evidence is stored in the
 * existing archives; queue persists only status and references. No paid AI.
 * Caller must supply trusted catalog DB verification and authorized price store.
 */
final class ResearchJobProcessor
{
    public function __construct(private LegacyReadBridge $bridge,
        private PriceArchiveStore $prices,
        private Closure $verifyCatalog,
        private Closure $capturePrice,
        private Closure $pause)
    {}

    private static function result(string $outcome,array $evidence,int $delay=0): array
    {
        return ['outcome'=>$outcome,'retry_after'=>$delay,'evidence'=>$evidence];
    }
    private static function failure(array $r,array $e): array
    {
        $d=is_array($r['data']??null)?$r['data']:[];
        $limited=($r['http_status']??null)===429||($d['upstream_status']??null)===429;
        $retry=max((int)($r['retry_after_seconds']??0),(int)($d['retry_after_seconds']??0));
        return self::result($limited?'rate_limited':(($r['http_status']??null)===null?'network_error':'provider_error'),
            $e,min(86400,max(0,$retry)));
    }
    public function process(string $ean): array
    {
        ResearchQueue::canonical($ean);
        $e=[];
        $j=$this->bridge->fetch('jtl.article',['q'=>$ean]);
        $jraw=$j['data']??[];
        $matches=$jraw['matches']??null;
        if(!($j['ok']??false)||($jraw['jtl_status']??null)!=='current_snapshot'||!is_array($matches)){
            $e['jtl_state']='unverified';
            return self::failure($j,$e);
        }
        $e['jtl_state']=match(count($matches)){0=>'new_candidate',1=>'existing',default=>'multiple'};
        // These are read-only historical sources. No zero from a failed request.
        $h=$this->bridge->fetch('history.article',['ean'=>$ean,'include_legacy'=>'1']);
        $e['history_state']=($h['ok']??false)?'read':'unavailable';
        if($h['ok']??false)$e['history_count']=max(0,(int)($h['data']['total_returned']??0));
        $ai=$this->bridge->fetch('ai.history',['ean'=>$ean,'limit'=>1]);
        $e['ai_state']=($ai['ok']??false)?'read':'unavailable';
        if($ai['ok']??false){
            $e['ai_scope']=($ai['data']['scope']??null)==='shared_workspace'?'shared_workspace':'legacy_account';
            $e['ai_count']=count(is_array($ai['data']['reviews']??null)?$ai['data']['reviews']:[]);
        }
        if($e['jtl_state']==='multiple')return self::result('identity_conflict',$e);
        $c=$this->bridge->fetch('amazon.catalog',['ean'=>$ean]);
        if(!($c['ok']??false)){
            $e['catalog_state']='unavailable';
            return self::failure($c,$e);
        }
        $raw=$c['data']??[];
        $r=ScanResearch::fromResponses($ean,$j,$c);
        $id=$r['snapshot_id'];
        if($id<1||!in_array($r['source'],['live','cache'],true)||!($this->verifyCatalog)($id,$ean)){
            $e['catalog_state']='unverified';
            return self::result('archive_unavailable',$e);
        }
        $e['catalog_state']=$r['source'];$e['catalog_snapshot_id']=$id;
        $items=$raw['items']??null;
        if(!is_array($items)||($raw['has_next_page']??null)!==false){
            $e['price_state']='identity_unverified';return self::result('identity_conflict',$e);
        }
        if(count($items)===0){$e['price_state']='no_match';return self::result('no_catalog_match',$e);}
        // Conservative pilot: exactly one catalog item, proven GTIN and single pack.
        $candidate=$r['items'][0]??null;
        if(count($items)!==1||count($r['items'])!==1||!($candidate['gtin_match']??false)||($candidate['pack_kind']??null)!=='single'){
            $e['price_state']='identity_unverified';return self::result('identity_conflict',$e);
        }
        $asin=$candidate['asin'];
        // A recent archived observation can be reused, never relabeled as live.
        $rows=$this->prices->latest($asin,1);
        $prior=$rows[0]??null;
        $stamp=is_array($prior)?strtotime($prior['observed_at_utc'].' UTC'):false;
        if($stamp!==false&&$stamp>=time()-900&&$stamp<=time()+60){
            $e['price_state']='archive_reused';$e['price_snapshot_id']=(int)$prior['id'];
        }else{
            // Queue pilot pacing; does not yet coordinate legacy direct manual requests.
            ($this->pause)(15);
            $capture=($this->capturePrice)($asin);
            $p=$capture['response']??[];$a=$capture['archive']??[];
            if(!($p['ok']??false)){$e['price_state']='unavailable';return self::failure($p,$e);}
            if(($a['state']??null)!=='recorded'||!is_int($a['id']??null)||$a['id']<1){
                $e['price_state']='archive_unverified';return self::result('archive_unavailable',$e);
            }
            $latest=$this->prices->latest($asin,2);
            $match=null;
            foreach($latest as $row)if((int)$row['id']===$a['id']){$match=$row;break;}
            if($match===null){$e['price_state']='archive_unverified';return self::result('archive_unavailable',$e);}
            $prior=$match;$e['price_state']='live_archived';$e['price_snapshot_id']=$a['id'];
        }
        // Resolve known names only; no guessed names and no extra SP-API pricing call.
        $ids=[];
        foreach($prior['offers']??[] as $offer){
            $sid=$offer['seller_id']??null;
            if(is_string($sid)&&preg_match('/^[A-Z0-9]{5,40}$/D',$sid))$ids[$sid]=true;
        }
        if($ids===[]){$e['seller_state']='no_ids';$e['seller_count']=0;}
        else{
            $n=$this->bridge->fetch('amazon.sellers',['ids'=>implode(',',array_slice(array_keys($ids),0,20))]);
            $e['seller_state']=($n['ok']??false)?'read':'unavailable';
            if($n['ok']??false)$e['seller_count']=count(array_intersect_key((array)($n['data']['names']??[]),$ids));
        }
        // Core research verified; unavailable optional sources remain explicitly marked.
        return self::result('completed',$e);
    }
}
