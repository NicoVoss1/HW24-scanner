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
    /** Read-only name/ASIN selection; load a chosen article through exact JTL lookup. */
    public function searchCandidates(string $query):array
    {
        $query=trim($query);
        if(mb_strlen($query)<3||mb_strlen($query)>100||preg_match('/[\x00-\x1f\x7f]/u',$query))throw new RuntimeException('Invalid search');
        $db=HubPeerMirror::connect();$m=self::meta($db);
        $like='%'.str_replace(['=','%','_'],['==','=%','=_'],$query).'%';
        $q=$db->prepare("SELECT d.artikelnummer,d.artikelname,d.ean,d.hersteller,x.asin FROM hw24_hub_article_details d JOIN hw24_hub_history_extended x ON x.source_id=d.source_id AND x.jtl_article_id=d.jtl_article_id WHERE d.source_id=? AND (x.asin=? OR d.artikelname LIKE ? ESCAPE '=') ORDER BY d.artikelname,d.jtl_article_id LIMIT 21");
        $q->execute([$m['source_id'],strtoupper($query),$like]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
        return ['rows'=>array_slice($rows,0,20),'limited'=>count($rows)>20];
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

    /** Exact operator-specified label, case insensitive; no substring matches. */
    public static function hasTemplateLabel(string $labels): bool
    {
        $parts=preg_split('/[;,|\r\n]+/u',$labels)?:[];
        foreach($parts as $part)if(mb_strtolower(trim($part))==='vorlagen zur anlage')return true;
        return false;
    }

    /** Actual label, name volume and stored volume must agree. */
    public static function consistentVolume(string $name,mixed $quantity,string $unit): bool
    {
        if(mb_strtolower($unit)!=='ml'||!is_numeric($quantity)||(float)$quantity<=0)return false;
        if(preg_match_all('/\\b(\\d+(?:[,.]\\d+)?)\\s*ml\\b/iu',$name,$m)!==1)return false;
        return (float)str_replace(',','.',$m[1][0])===(float)$quantity;
    }

    /** Same source generation, verified label, exact brand/group/volume. No provider requests. */
    public function standardProposals(PDO $db,array $draft,array $observations,string $groupHint=''): array
    {
        $brand=HubManufacturerPolicy::jtlSearchName((string)($draft['Hersteller']??''));
        $group=trim((string)($draft['Warengruppe']??''));
        $category=HubCategoryPolicy::suggest((string)($draft['Artikelname']??''),$group);
        // The category is a search key, never an invented JTL group.
        if($group==='')$group=$groupHint!==''?$groupHint:($category['Kategorie Ebene 2']??'');
        $amount=str_replace(',','.',trim((string)($draft['Inhalt/Menge']??'')));
        $unit=trim((string)($draft['Maßeinheit']??''));
        if($brand===''||$group===''||!self::consistentVolume((string)($draft['Artikelname']??''),$amount,$unit))
            return ['fields'=>[],'sources'=>[],'count'=>0,'reason'=>'Hersteller, Warengruppe oder Füllmenge noch nicht eindeutig'];
        $ids=[];
        foreach($observations['mass_unit_examples']??[] as $id=>$v)
            if(mb_strtolower((string)($v['display_code']??''))===mb_strtolower($unit))$ids[]=(int)$id;
        if(count($ids)!==1)return ['fields'=>[],'sources'=>[],'count'=>0,'reason'=>'Einheit nicht eindeutig belegt'];
        $db->beginTransaction();
        try{
            $m=self::meta($db);
            $q=$db->prepare("SELECT d.jtl_article_id,d.artikelname,d.hersteller,d.warengruppe,d.ean,d.menge,d.label_namen,
                p.kLieferStatus,p.kSteuerklasse,p.kVersandklasse,p.cLagerAktiv,p.cLagerKleinerNull,p.cTeilbar
                FROM hw24_hub_article_details d JOIN hw24_hub_peer_source p
                ON p.source_id=d.source_id AND p.jtl_article_id=d.jtl_article_id
                WHERE d.source_id=? AND d.hersteller=? AND d.warengruppe=? AND d.einheit_id=?
                AND d.label_namen LIKE '%Vorlagen%' ORDER BY d.jtl_article_id LIMIT 201");
            $q->execute([$m['source_id'],$brand,$group,(string)$ids[0]]);
            $rows=$q->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)>200)throw new RuntimeException('Too many templates');
            // Common group standards, independent of brand or size, only when all labelled rows agree.
            $q=$db->prepare("SELECT d.jtl_article_id,d.hersteller,d.warengruppe,d.artikelname,d.ean,d.label_namen,
                p.kLieferStatus,p.kSteuerklasse,p.cLagerAktiv,p.cLagerKleinerNull,p.cTeilbar
                FROM hw24_hub_article_details d JOIN hw24_hub_peer_source p
                ON p.source_id=d.source_id AND p.jtl_article_id=d.jtl_article_id
                WHERE d.source_id=? AND d.warengruppe=? AND d.label_namen LIKE '%Vorlagen%'
                ORDER BY d.jtl_article_id LIMIT 501");
            $q->execute([$m['source_id'],$group]);$groupRows=$q->fetchAll(PDO::FETCH_ASSOC);$db->commit();
            if(count($groupRows)>500)$groupRows=[];
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        $valid=[];
        foreach($rows as $row){
            if(!self::hasTemplateLabel((string)$row['label_namen'])||
                mb_strtolower(trim((string)$row['hersteller']))!==mb_strtolower($brand)||
                mb_strtolower(trim((string)$row['warengruppe']))!==mb_strtolower($group)||
                !self::consistentVolume((string)$row['artikelname'],$row['menge'],$unit)||
                (float)$row['menge']!==(float)$amount||
                (string)$row['ean']===(string)($draft['GTIN']??''))continue;
            $valid[(int)$row['jtl_article_id']]=$row;
        }
        $result=self::templateConsensus(array_values($valid),$draft,$observations);
        // An explicit alias is only proposed as the actual JTL value after a matching labelled template.
        if($valid&&trim((string)($draft['Hersteller']??''))!==$brand){
            $result['manufacturer_hint']=$brand;
        }
        if(!$valid)$result['reason']=$rows?'Gekennzeichnete Kandidaten gefunden, aber Label, Menge oder Identität nicht eindeutig passend':'Keine Vorlage für Hersteller '.$brand.' / Warengruppe '.$group.' / '.$amount.' '.$unit.' gefunden';
        $common=self::groupStandards($groupRows,$draft,$group,$observations);
        foreach($common['fields'] as $field=>$value){
            if(isset($result['fields'][$field]))continue;
            $result['fields'][$field]=$value;$result['sources'][$field]=$common['sources'][$field];
        }
        if($common['fields'])$result['reason'].=' · Herstellerübergreifende Warengruppenstandards geprüft';
        return $result;
    }

    public static function groupStandards(array $rows,array $draft,string $group,array $observations): array
    {
        $valid=[];$brands=[];
        foreach($rows as $r){
            if(!self::hasTemplateLabel((string)($r['label_namen']??''))||
                mb_strtolower(trim((string)($r['warengruppe']??'')))!==mb_strtolower(trim($group))||
                trim((string)($r['hersteller']??''))===''||(int)($r['jtl_article_id']??0)<1||
                (string)($r['ean']??'')===(string)($draft['GTIN']??''))continue;
            $valid[(int)$r['jtl_article_id']]=$r;
            $brands[mb_strtolower(HubManufacturerPolicy::jtlSearchName($r['hersteller']))]=true;
        }
        if(count($brands)<2)return ['fields'=>[],'sources'=>[]];
        $out=self::templateConsensus(array_values($valid),$draft,$observations);
        foreach($out['sources'] as &$source)$source='Herstellerübergreifender Warengruppenstandard: '.$source;
        unset($source);
        return $out;
    }

    /** Only unanimous observed operational defaults; unknown reference labels stay open. */
    public static function templateConsensus(array $rows,array $draft,array $o): array
    {
        $out=['fields'=>[],'sources'=>[],'count'=>count($rows),'reason'=>'Keine passende Vorlage'];
        if(!$rows)return $out;
        $out['reason']='Passende JTL-Vorlagen; nur übereinstimmende Standardfelder';
        $ids=array_map(fn($r)=>(int)$r['jtl_article_id'],$rows);
        $source='JTL Vorlagen zur Anlage: '.count($ids).' Artikel, kArtikel '.implode(',',array_slice($ids,0,8));
        $map=['Warengruppe'=>'warengruppe','Steuerklasse'=>'kSteuerklasse','Lieferstatus'=>'kLieferStatus',
            'Bestandsführung aktiv'=>'cLagerAktiv','Überverkäufe möglich'=>'cLagerKleinerNull','Stückzahl teilbar'=>'cTeilbar'];
        foreach($map as $field=>$key){
            if(trim((string)($draft[$field]??''))!=='')continue;
            $v=array_map(fn($r)=>trim((string)($r[$key]??'')),$rows);
            if(count(array_unique($v))!==1||$v[0]==='')continue;
            $value=match($field){
                'Steuerklasse'=>$o['tax_classes'][(int)$v[0]]['name']??null,
                'Lieferstatus'=>$o['delivery_status_by_language'][(int)$v[0].':1']??null,
                'Warengruppe'=>$v[0],
                default=>in_array($v[0],['Y','N'],true)?$v[0]:null};
            if(!is_string($value)||trim($value)==='')continue;
            $out['fields'][$field]=$value;$out['sources'][$field]=$source;
        }
        return $out;
    }

    /** Explicit template label + exact manufacturer/group/quantity/unit; no guessed equivalences. */
    public function templates(array $draft,array $observations): array
    {
        $brand=HubManufacturerPolicy::jtlSearchName((string)($draft['Hersteller']??''));$group=trim((string)($draft['Warengruppe']??''));
        if($group==='')$group=HubCategoryPolicy::suggest((string)($draft['Artikelname']??''))['Kategorie Ebene 2']??'';
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
            $q->execute([$m['source_id'],'%Vorlagen%',$brand,$group,(string)$ids[0]]);
            $rows=$q->fetchAll(PDO::FETCH_ASSOC);$db->commit();
            if(count($rows)>200)throw new RuntimeException('Too many candidates');
            $out=[];
            foreach($rows as $row){
                if(mb_strtolower(trim((string)$row['hersteller']))!==mb_strtolower($brand)||
                   mb_strtolower(trim((string)$row['warengruppe']))!==mb_strtolower($group)||
                   !is_numeric($row['menge'])||(float)$row['menge']!==(float)$amount||
                   (string)$row['ean']===(string)($draft['GTIN']??''))continue;
                // Exact label or delimited label, not a substring of a different label.
                if(!self::hasTemplateLabel((string)$row['label_namen']))continue;
                if(!self::consistentVolume((string)$row['artikelname'],$row['menge'],$unit))continue;
                $out[]=['number'=>$row['artikelnummer'],'name'=>$row['artikelname'],'amount'=>$row['menge'],'unit'=>$unit];
            }
            return ['ok'=>true,'items'=>$out,'search_group'=>$group,'search_manufacturer'=>$brand,
                'message'=>$out?'Passende gekennzeichnete Vorlagen gefunden':'Keine eindeutig passende gekennzeichnete Vorlage für '.$brand.' / '.$group.' / '.$amount.' '.$unit,
                'snapshot_at'=>ScanEntryPresentation::germanDate($m['last_completed_utc'])];
        }catch(\Throwable){return ['ok'=>false,'message'=>'Vorlagenquelle noch nicht verfügbar oder Spiegel nicht aktuell.'];}
    }
}
