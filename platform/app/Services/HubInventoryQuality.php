<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

/** One consistent, complete current mirror; no provider calls or product writes. */
final class HubInventoryQuality
{
    /** Use the same field presentation as individual article care.
     * Absent optional mappings remain unverified, never falsely reported as empty JTL values. */
    public static function fields(array $r,array $observations):array
    {
        $a=[];
        foreach(['artikelnummer','ean','han','asin','artikelname','hersteller','netto_vk','kurzbeschreibung','beschreibung','seo_titel','seo_beschreibung','artikelgewicht','versandgewicht','herkunftsland'] as $k){
            if(array_key_exists($k,$r))$a[$k]=$r[$k];
        }
        if(array_key_exists('warengruppe',$r))$a['warengruppe_name']=$r['warengruppe'];
        $a['jtl_source_fields']=[];
        foreach(['kHersteller','kWarengruppe','kSteuerklasse','kVersandklasse','kLieferStatus','cLagerAktiv','cLagerKleinerNull','cTeilbar','cAnmerkung','cAktiv','nBearbeitungszeit','nAutomatischeLiefertageberechnung','cTopArtikel','cNeu'] as $k)
            if(isset($r[$k]))$a['jtl_source_fields'][$k]=$r[$k];
        if(isset($r['menge']))$a['jtl_source_fields']['fMassMenge']=$r['menge'];
        if(isset($r['einheit_id']))$a['jtl_source_fields']['kMassEinheit']=$r['einheit_id'];
        $v=ScanResearchPresentation::jtlFields((string)($a['ean']??''),['ok'=>true,'data'=>['jtl_status'=>'current_snapshot','matches'=>[$a]]],$observations)['fields'];
        foreach(['Artikelgewicht','Versandgewicht','Herkunftsland','Warengruppe'] as $f)
            if(($v[$f]['state']??null)==='empty_snapshot')$v[$f]['state']='missing_source';
        return $v;
    }

    /** Counts every configured requirement, even when the source has not yet been mapped. */
    public static function evaluate(array $rows,array $required,array $observations):array
    {
        $counts=[];$issues=[];$seen=[];$gtins=[];
        foreach($required as $f)$counts[$f]=['confirmed'=>0,'missing'=>0,'unverified'=>0];
        foreach($rows as $r){
            $id=(string)($r['jtl_article_id']??'');
            if(!ctype_digit($id)||(int)$id<1||isset($seen[$id]))throw new RuntimeException('Mirror identity incomplete');
            $seen[$id]=true;
            try{$key=ResearchQueue::canonical((string)($r['ean']??''));$gtins[$key]=($gtins[$key]??0)+1;}catch(\Throwable){}
        }
        foreach($rows as $r){
            $fields=self::fields($r,$observations);
            $missing=[];$unknown=[];$key=null;
            try{$key=ResearchQueue::canonical((string)($r['ean']??''));}catch(\Throwable){}
            foreach($required as $f){
                $state=$fields[$f]['state']??'unmapped';
                if($f==='GTIN'&&$key===null)$state='empty_snapshot';
                if($f==='GTIN'&&$key!==null&&($gtins[$key]??0)>1)$state='reference';
                if(in_array($state,['value','observed_reference'],true))$counts[$f]['confirmed']++;
                elseif($state==='empty_snapshot'){$counts[$f]['missing']++;$missing[]=$f;}
                else{$counts[$f]['unverified']++;$unknown[]=$f;}
            }
            if($missing||$unknown)$issues[]=['id'=>(string)$r['jtl_article_id'],'number'=>(string)($r['artikelnummer']??''),
                'ean'=>(string)($r['ean']??''),'name'=>(string)($r['artikelname']??''),'manufacturer'=>(string)($r['hersteller']??''),
                'asin'=>(string)($r['asin']??''),'missing'=>$missing,'unverified'=>$unknown,
                'gtin_state'=>$key===null?'missing_or_invalid':(($gtins[$key]??0)>1?'duplicate':'verified')];
        }
        usort($issues,fn($a,$b)=>count($b['missing'])<=>count($a['missing']) ?: count($b['unverified'])<=>count($a['unverified']) ?: strcmp($a['number'],$b['number']));
        return ['articles'=>count($rows),'affected'=>count($issues),'counts'=>$counts,'issues'=>$issues];
    }

    public function read(array $required,array $observations):array
    {
        $db=HubPeerMirror::connect();
        $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');$db->beginTransaction();
        try{
            $m=$db->query('SELECT source_id,last_completed_utc,last_row_count,age_minutes FROM hw24_hub_mirror_state ORDER BY last_completed_utc DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if(!$m||(int)$m['age_minutes']<0||(int)$m['age_minutes']>45||(int)$m['last_row_count']<1)throw new RuntimeException('Mirror not current');
            $q=$db->prepare("SELECT d.jtl_article_id,d.artikelnummer,d.ean,d.artikelname,d.hersteller,d.warengruppe,d.artikelgewicht,d.versandgewicht,d.herkunftsland,d.menge,d.einheit_id,
                p.jtl_article_id AS peer_id,p.kHersteller,p.kWarengruppe,p.kSteuerklasse,p.kVersandklasse,p.kLieferStatus,p.cLagerAktiv,p.cLagerKleinerNull,p.cTeilbar,
                x.jtl_article_id AS extended_id,x.han,x.asin,x.netto_vk,x.cAnmerkung,x.cAktiv,x.nBearbeitungszeit,x.nAutomatischeLiefertageberechnung,x.cTopArtikel,x.cNeu,
                CASE WHEN x.kurzbeschreibung IS NULL OR TRIM(x.kurzbeschreibung)='' THEN '' ELSE 'belegt' END AS kurzbeschreibung,
                CASE WHEN x.beschreibung IS NULL OR TRIM(x.beschreibung)='' THEN '' ELSE 'belegt' END AS beschreibung,
                CASE WHEN x.seo_titel IS NULL OR TRIM(x.seo_titel)='' THEN '' ELSE 'belegt' END AS seo_titel,
                CASE WHEN x.seo_beschreibung IS NULL OR TRIM(x.seo_beschreibung)='' THEN '' ELSE 'belegt' END AS seo_beschreibung
                FROM hw24_hub_article_details d LEFT JOIN hw24_hub_peer_source p ON p.source_id=d.source_id AND p.jtl_article_id=d.jtl_article_id
                LEFT JOIN hw24_hub_history_extended x ON x.source_id=d.source_id AND x.jtl_article_id=d.jtl_article_id WHERE d.source_id=? ORDER BY d.jtl_article_id");
            $q->execute([$m['source_id']]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)!==(int)$m['last_row_count'])throw new RuntimeException('Mirror incomplete');
            foreach($rows as $r)if((string)$r['jtl_article_id']!==(string)$r['peer_id']||(string)$r['jtl_article_id']!==(string)$r['extended_id'])throw new RuntimeException('Projection incomplete');
            $result=self::evaluate($rows,$required,$observations);
            $db->commit();
            return ['ok'=>true,'at'=>$m['last_completed_utc'],'source'=>$m['source_id']]+$result;
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
}
