<?php
declare(strict_types=1);
namespace App\Services;

/** Existing JTL article only. Sparse, explicitly selected update CSV, never a create file. */
final class HubExistingArticleExport
{
    public const FIELDS=['HAN','ASIN','Artikelname','Kurzbeschreibung','Beschreibung','Titel-Tag (SEO)','Meta-Description (SEO)','Artikelgewicht','Inhalt/Menge','Maßeinheit'];
    public static function candidates(array $current,array $article,array $catalog,array $ai,array $references,array $seo):array
    {
        $out=[];
        $add=static function(string $f,mixed $v,string $source)use(&$out,$current):void{
            if(!in_array($f,self::FIELDS,true)||!is_string($v)||trim($v)===''||mb_strlen($v)>30000)return;
            $old=$current[$f]??[];
            // Only confirmed empty snapshot fields. Unmapped and ID-only fields are not editable here.
            if(($old['state']??null)!=='empty_snapshot' || trim((string)($old['value']??''))!=='')return;
            $v=trim($v);
            if(preg_match('/^[=+\-@]/u',$v)||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$v))return;
            if($f==='ASIN'&&!preg_match('/^[A-Z0-9]{10}$/D',$v))return;
            if($f==='HAN'&&mb_strlen($v)>140)return;
            $token=hash('sha256',$f."\0".$v);
            if(!isset($out[$f][$token]))$out[$f][$token]=['value'=>$v,'source'=>$source];
        };
        $p=$catalog['item']??[];
        $identity=($p['gtin_match']??false)===true&&($p['pack_kind']??null)==='single'&&
            (trim((string)($article['asin']??''))===''||$article['asin']===$p['asin'])&&
            !empty($article['hersteller'])&&!empty($p['manufacturer'])&&HubManufacturerPolicy::equivalent($article['hersteller'],$p['manufacturer']);
        if($identity){
            $origin='Amazon-Katalogarchiv #'.($catalog['id']??'?');
            foreach(HubCatalogFacts::productFields($catalog) as $f=>$fact)$add($f,$fact['value'],$origin.$fact['suffix']);
            // No volume/unit partial update without confirmed current paired value.
            if(($ai['state']??null)==='verified_review'&&($ai['content']['state']??null)==='review')
                foreach($ai['content']['fields']??[] as $f=>$v)$add($f,$v,'KI-Archiv #'.($ai['id']??'?').' · prüfen');
        }
        foreach($references as $source=>$ref){
            if(($ref['state']??null)!=='found')continue;
            $rows=$ref['rows']??[];
            if($source==='ameise'){
                $rows=array_values(array_filter($rows,fn($r)=>(string)($r['article_id']??'')===(string)$article['jtl_article_id']));
            }elseif($source==='historical'){
                if(count($rows)!==1||($rows[0]['requires_review']??true))continue;
                $h=$rows[0]['fields']??[];
                if(empty($h['ASIN'])||empty($h['Hersteller'])||
                    !HubManufacturerPolicy::equivalent($h['Hersteller'],(string)($article['hersteller']??''))||
                    ($h['ASIN']!==($article['asin']??'')&&(!$identity||$h['ASIN']!==($p['asin']??''))))continue;
            }else continue;
            // Conflicting same-article supplier rows are review options, not auto-selected.
            foreach($rows as $r)foreach(['HAN','Artikelname','Kurzbeschreibung','Beschreibung','Titel-Tag (SEO)','Meta-Description (SEO)'] as $f)
                $add($f,$r['fields'][$f]??null,($source==='ameise'?'Ameise-Archiv':'Historische Artikeldaten').' #'.($ref['archive']??'?').' · Zeile '.$r['row']);
        }
        foreach($seo as $f=>$v)$add($f,$v,'HW24-SEO-Basisregel');
        foreach(HubTemplateDefaults::emptyTexts(['Artikelname'=>$article['artikelname']??'']) as $f=>$v)$add($f,$v,'HW24-Regel: eigener Artikelname');
        return $out;
    }

    public static function csv(string $number,array $selected,array $options):string
    {
        if(trim($number)===''||count($selected)>count(self::FIELDS))throw new \RuntimeException('Invalid article update');
        $patch=HubFieldDecisions::resolve($options,$selected);
        $headers=['Artikelnummer'];$values=[$number];
        foreach(self::FIELDS as $f){
            if(!isset($patch['fields'][$f]))continue;
            $v=$patch['fields'][$f];
            if(!is_string($v)||trim($v)==='')throw new \RuntimeException('Empty update');
            $headers[]=$f;$values[]=$v;
        }
        foreach($selected as $f=>$token)if(!in_array($f,self::FIELDS,true))throw new \RuntimeException('Unknown field');
        if(count($headers)<2)throw new \RuntimeException('No changes selected');
        $esc=static function(string $v):string{
            $v=trim(preg_replace('/[\r\n\x00-\x1f\x7f]/u',' ',$v)??'');
            if($v===''||preg_match('/^[=+\-@]/u',$v))throw new \RuntimeException('Unsafe CSV value');
            return '"'.str_replace('"','""',$v).'"';
        };
        return "\xEF\xBB\xBF".implode(';',array_map($esc,$headers))."\r\n".implode(';',array_map($esc,$values))."\r\n";
    }
}
