<?php
declare(strict_types=1);
namespace App\Services;

use PDO;

/** Shared read-only research evidence for scan intake and existing product care. */
final class HubResearchEvidence
{
    public static function archivedCatalog(PDO $db,array $job): array
    {
        $e=$job['evidence']??[];
        if(($job['status']??null)!=='completed'||!in_array($e['catalog_state']??null,['live','cache'],true)||
            (int)($e['catalog_snapshot_id']??0)<1)return [];
        $q=$db->prepare('SELECT * FROM hw24_hub_catalog_reuse WHERE snapshot_id=? LIMIT 2');
        $q->execute([(int)$e['catalog_snapshot_id']]);
        $found=$q->fetchAll(PDO::FETCH_ASSOC);
        return count($found)===1?$found[0]:[];
    }

    public static function catalogItem(string $ean,array $job,array $archive): ?array
    {
        $e=$job['evidence']??[];
        if(($job['status']??null)!=='completed'||!in_array($e['catalog_state']??null,['live','cache'],true)||
            (int)($e['catalog_snapshot_id']??0)<1||(int)($archive['snapshot_id']??0)!==(int)$e['catalog_snapshot_id'])return null;
        try {
            if(ResearchQueue::canonical($ean)!==ResearchQueue::canonical((string)($archive['searched_ean']??'')))return null;
            $raw=json_decode((string)($archive['item_json']??''),true,64,JSON_THROW_ON_ERROR);
        }catch(\Throwable){return null;}
        if(!is_array($raw))return null;
        $item=['asin'=>$archive['asin']??'','title'=>$archive['item_name']??'','manufacturer'=>$archive['manufacturer']??'',
            'brand'=>$archive['brand']??'','identifiers'=>$raw['identifiers']??[],'attributes'=>$raw['attributes']??[]];
        $p=ScanResearch::fromResponses($ean,[],['ok'=>true,'data'=>['items'=>[$item]]])['items'][0]??[];
        if(!($p['gtin_match']??false)||($p['pack_kind']??null)!=='single')return null;
        return ['item'=>$p,'attributes'=>$raw['attributes']??[],'id'=>(int)$archive['snapshot_id'],
            'at'=>$archive['fetched_at_utc']??null];
    }

    /** One explicit catalog response can be reused directly after verifying its archive. */
    public static function directCatalog(string $ean,array $result,PDO $db):?array
    {
        if(($result['amazon_ok']??false)!==true||($result['more']??null)!==false||
            count($result['items']??[])!==1||!in_array($result['source']??null,['live','cache'],true)||
            (int)($result['snapshot_id']??0)<1)return null;
        $job=['status'=>'completed','evidence'=>['catalog_state'=>$result['source'],'catalog_snapshot_id'=>$result['snapshot_id']]];
        $catalog=self::catalogItem($ean,$job,self::archivedCatalog($db,$job));
        if($catalog===null||$catalog['item']['asin']!==($result['items'][0]['asin']??null))return null;
        return $catalog;
    }

    public static function marketPrice(array $job,string $asin): ?array
    {
        try{
            $id=(int)($job['evidence']['price_snapshot_id']??0);
            if($id<1||!AmazonPriceArchiveRuntime::enabled())return null;
            foreach(AmazonPriceArchiveRuntime::open()->latest($asin,2) as $price){
                if((int)$price['id']!==$id)continue;
                return ['id'=>$id,'at'=>$price['observed_at_utc'],'buybox'=>$price['buybox'],'lowest'=>$price['lowest']];
            }
        }catch(\Throwable){}
        return null;
    }

    /** Display differences, not approved replacement values. Unknown JTL mappings stay unknown. */
    public static function existingComparison(array $fieldReview,array $catalog): array
    {
        if(($fieldReview['ok']??false)!==true)return ['state'=>'unavailable','differences'=>[]];
        $fields=$fieldReview['fields']??[];$p=$catalog['item'];
        $asin=$fields['ASIN']['value']??'';
        if($asin!==''&&strtoupper($asin)!==$p['asin'])return ['state'=>'identity_conflict','differences'=>[]];
        $differences=[];
        foreach(['Artikelname'=>'title','Hersteller'=>'manufacturer','ASIN'=>'asin'] as $field=>$key){
            $new=trim((string)($p[$key]??''));$current=$fields[$field]??[];
            $old=(string)($current['value']??'');
            if($new===''||($current['state']??null)==='value'&&trim($old)===$new)continue;
            $differences[$field]=['old'=>$old,'old_state'=>$current['state']??'unmapped',
                'candidate'=>$new,'source'=>'Amazon-Katalogarchiv #'.$catalog['id']];
        }
        return ['state'=>'review','differences'=>$differences,'catalog_id'=>$catalog['id'],'at'=>$catalog['at']];
    }
}
