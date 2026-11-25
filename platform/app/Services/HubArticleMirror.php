<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

final class HubArticleMirror
{
    private static function meta(PDO $db): array
    {
        $m=$db->query('SELECT source_id,last_completed_utc,age_minutes FROM hw24_hub_mirror_state ORDER BY last_completed_utc DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if(!$m||(int)$m['age_minutes']<0||(int)$m['age_minutes']>45)throw new RuntimeException('Mirror stale');
        return $m;
    }
    public function enrich(array $response): array
    {
        $raw=$response['data']??[];
        if(($response['ok']??false)!==true||($raw['jtl_status']??null)!=='current_snapshot'||
            !is_array($raw['matches']??null)||count($raw['matches'])!==1)return $response;
        $a=$raw['matches'][0];$id=(int)($a['jtl_article_id']??0);
        if($id<1)return $response;
        try {
            $db=HubPeerMirror::connect();$db->beginTransaction();$m=self::meta($db);
            // Do not combine two different mirror generations.
            if((string)$m['last_completed_utc']!==(string)($raw['snapshot_at']??''))throw new RuntimeException('Snapshot changed');
            $q=$db->prepare('SELECT ean,artikelgewicht,versandgewicht,herkunftsland,label_namen FROM hw24_hub_article_details WHERE source_id=? AND jtl_article_id=? LIMIT 2');
            $q->execute([$m['source_id'],$id]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)!==1||(string)$rows[0]['ean']!==(string)($a['ean']??''))throw new RuntimeException('Identity mismatch');
            foreach(['artikelgewicht','versandgewicht','herkunftsland','label_namen'] as $f)$a[$f]=$rows[0][$f];
            $db->commit();$response['data']['matches'][0]=$a;
        } catch(\Throwable){return $response;}
        return $response;
    }

    /** Explicit template label + exact manufacturer/group/quantity/unit; no guessed equivalences. */
    public function templates(array $draft,array $observations): array
    {
        $brand=trim((string)($draft['Hersteller']??''));$group=trim((string)($draft['Warengruppe']??''));
        $amount=str_replace(',','.',trim((string)($draft['Inhalt/Menge']??'')));
        $unit=trim((string)($draft['Maßeinheit']??''));
        if($brand===''||$group===''||!preg_match('/^\d+(?:\.\d+)?$/D',$amount)||!is_finite((float)$amount)||(float)$amount<=0||$unit==='')
            return ['ok'=>false,'message'=>'Hersteller, Warengruppe, Füllmenge und Maßeinheit zuerst belegen.'];
        $ids=[];
        foreach($observations['mass_unit_examples']??[] as $id=>$value)
            if(mb_strtolower((string)($value['display_code']??''))===mb_strtolower($unit))$ids[]=(int)$id;
        if(count($ids)!==1)return ['ok'=>false,'message'=>'Maßeinheit noch nicht eindeutig zugeordnet.'];
        try{
            $db=HubPeerMirror::connect();$db->beginTransaction();$m=self::meta($db);
            $q=$db->prepare("SELECT artikelnummer,artikelname,hersteller,warengruppe,ean,menge,einheit_id,label_namen
                FROM hw24_hub_article_details WHERE source_id=? AND label_namen LIKE ? AND hersteller=? AND warengruppe=? AND einheit_id=? ORDER BY jtl_article_id LIMIT 201");
            $q->execute([$m['source_id'],'%Vorlage zur Anlage%',$brand,$group,(string)$ids[0]]);
            $rows=$q->fetchAll(PDO::FETCH_ASSOC);$db->commit();
            if(count($rows)>200)throw new RuntimeException('Too many candidates');
            $out=[];
            foreach($rows as $row){
                if(mb_strtolower(trim((string)$row['hersteller']))!==mb_strtolower($brand)||
                   mb_strtolower(trim((string)$row['warengruppe']))!==mb_strtolower($group)||
                   !is_numeric($row['menge'])||(float)$row['menge']!==(float)$amount||
                   (string)$row['ean']===(string)($draft['GTIN']??''))continue;
                // Exact label or delimited label, not a substring of a different label.
                $labels=preg_split('/[;,|\r\n]+/u',(string)$row['label_namen']);
                if(!in_array('Vorlage zur Anlage',array_map('trim',$labels?:[]),true))continue;
                $out[]=['number'=>$row['artikelnummer'],'name'=>$row['artikelname'],'amount'=>$row['menge'],'unit'=>$unit];
            }
            return ['ok'=>true,'items'=>$out,'snapshot_at'=>ScanEntryPresentation::germanDate($m['last_completed_utc'])];
        }catch(\Throwable){return ['ok'=>false,'message'=>'Vorlagenquelle noch nicht verfügbar oder Spiegel nicht aktuell.'];}
    }
}
