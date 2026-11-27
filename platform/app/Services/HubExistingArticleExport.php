<?php
declare(strict_types=1);
namespace App\Services;

/** Existing JTL article only. Sparse, explicitly selected update CSV, never a create file. */
final class HubExistingArticleExport
{
    public const FIELDS=['GTIN','ASIN',...HubArticleFieldEditor::MANUAL,...HubArticleFieldEditor::FLAGS,...HubArticleFieldEditor::REFERENCE];
    public const REPLACEABLE=['Artikelgewicht','Artikelname','Kurzbeschreibung','Beschreibung','Titel-Tag (SEO)','Meta-Description (SEO)'];
    public static function candidates(array $current,array $article,array $catalog,array $ai,array $references,array $seo,array $manufacturers=[]):array
    {
        $out=[];
        $add=static function(string $f,mixed $v,string $source)use(&$out,$current):void{
            if(!in_array($f,self::FIELDS,true)||!is_string($v)||trim($v)===''||mb_strlen($v)>30000)return;
            $old=$current[$f]??[];
            // Only confirmed empty snapshot fields. Unmapped and ID-only fields are not editable here.
            $oldValue=trim((string)($old['value']??''));
            $replacement=($old['state']??null)==='value'&&$oldValue!==''&&in_array($f,self::REPLACEABLE,true);
            if(!$replacement&&(($old['state']??null)!=='empty_snapshot'||$oldValue!==''))return;
            if($replacement&&$oldValue===trim($v))return;
            $v=trim($v);
            if(preg_match('/^[=+\-@]/u',$v)||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$v))return;
            if($f==='ASIN'&&!preg_match('/^[A-Z0-9]{10}$/D',$v))return;
            if($f==='HAN'&&mb_strlen($v)>140)return;
            $token=hash('sha256',$f."\0".$v);
            if(!isset($out[$f][$token]))$out[$f][$token]=['value'=>$v,'source'=>$source,'old'=>$oldValue,'replacement'=>$replacement];
        };
        $p=$catalog['item']??[];
        $identity=($p['gtin_match']??false)===true&&($p['pack_kind']??null)==='single'&&
            (trim((string)($article['asin']??''))===''||$article['asin']===$p['asin'])&&
            !empty($article['hersteller'])&&!empty($p['manufacturer'])&&HubManufacturerPolicy::equivalent($article['hersteller'],$p['manufacturer']);
        // Missing manufacturer cannot be a prerequisite to researching itself.
        // Match a verified single-pack EAN and non-conflicting current ASIN; only
        // suggest an existing name/ID pair, never create a new manufacturer.
        $brandEvidence=[];
        $asin=trim((string)($article['asin']??''));
        $catalogIdentity=($p['gtin_match']??false)===true&&($p['pack_kind']??null)==='single'&&
            ($asin===''||$asin===($p['asin']??null));
        if($catalogIdentity&&!empty($p['manufacturer']))$brandEvidence[]=['name'=>$p['manufacturer'],'source'=>'Amazon-Katalogarchiv #'.($catalog['id']??'?')];
        foreach($references['ameise']['rows']??[] as $r){
            if(($references['ameise']['state']??null)!=='found'||(string)($r['article_id']??'')!==(string)$article['jtl_article_id'])continue;
            $h=$r['fields']??[];
            if(!empty($h['Hersteller'])&&($asin===''||empty($h['ASIN'])||$h['ASIN']===$asin))
                $brandEvidence[]=['name'=>$h['Hersteller'],'source'=>'Ameise-Archiv #'.($references['ameise']['archive']??'?').' · Zeile '.$r['row']];
        }
        $historical=$references['historical']??[];
        if(($historical['state']??null)==='found'&&count($historical['rows']??[])===1&&!($historical['rows'][0]['requires_review']??true)){
            $h=$historical['rows'][0]['fields']??[];
            if(!empty($h['Hersteller'])&&!empty($h['ASIN'])&&($h['ASIN']===$asin||($catalogIdentity&&$h['ASIN']===($p['asin']??null))))
                $brandEvidence[]=['name'=>$h['Hersteller'],'source'=>'Historische Artikeldaten #'.($historical['archive']??'?').' · Zeile '.$historical['rows'][0]['row']];
        }
        $resolvedBrand=null;
        if($brandEvidence&&trim((string)($article['hersteller']??''))===''){
            $first=$brandEvidence[0]['name'];$agree=true;
            foreach($brandEvidence as $e)if(!HubManufacturerPolicy::equivalent($first,$e['name']))$agree=false;
            $brand=$agree?HubManufacturerPolicy::resolve($first,$manufacturers):null;
            if($brand!==null){
                $resolvedBrand=$brand['name'];
                $add('Hersteller',$brand['name'],implode(' + ',array_column($brandEvidence,'source')).' · JTL-Hersteller '.$brand['name'].' (ID '.$brand['id'].')');
            }
        }
        if($catalogIdentity&&$resolvedBrand!==null&&HubManufacturerPolicy::equivalent($resolvedBrand,(string)($p['manufacturer']??'')))$identity=true;
        // A GTIN-shaped SKU is a search hint only. The caller must independently
        // verify the exact catalog EAN and matching stored ASIN before supplying it.
        $gtin=$catalog['verified_missing_gtin']??null;
        if($catalogIdentity&&is_string($gtin)&&$asin!==''&&$asin===($p['asin']??null)){
            try{ResearchQueue::canonical($gtin);$add('GTIN',$gtin,'Amazon-Katalogarchiv #'.($catalog['id']??'?').' · GTIN und bestehende ASIN bestätigt');}catch(\Throwable){}
        }
        if($identity){
            $origin='Amazon-Katalogarchiv #'.($catalog['id']??'?');
            foreach(HubCatalogFacts::productFields($catalog) as $f=>$fact)if($f!=='Hersteller')$add($f,$fact['value'],$origin.$fact['suffix']);
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
        foreach(HubTemplateDefaults::emptyTexts(['Artikelname'=>$article['artikelname']??'']) as $f=>$v)if(($current[$f]['state']??null)==='empty_snapshot')$add($f,$v,'HW24-Regel: eigener Artikelname');
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
