<?php
declare(strict_types=1);
namespace App\Services;

/** Versioned, source-bound KI copy and channel suggestions. No marketplace write. */
final class HubAiContent
{
    public const VERSION=2;
    public const TEXT_FIELDS=[
        'shortDescription'=>['Kurzbeschreibung',500],
        'description'=>['Beschreibung',2500],
        'seoTitle'=>['Titel-Tag (SEO)',65],
        'metaDescription'=>['Meta-Description (SEO)',160],
    ];
    public const ATTRIBUTE_KEYS=['Marke','Hersteller','Farbton','Farbnummer','Füllmenge','Maßeinheit','Produktart','Serie','Packungsgröße'];

    /** Product facts only: never send seller data, prices, secrets or free-form marketing claims. */
    public static function facts(array $catalog): array
    {
        $a=$catalog['attributes']??[];$p=$catalog['item']??[];
        $out=[];
        foreach(['manufacturer'=>'Hersteller','brand'=>'Marke'] as $key=>$label){
            $v=$p[$key]??null;
            if(is_string($v)&&trim($v)!==''&&mb_strlen($v)<=180)$out[$label]=trim($v);
        }
        foreach(['color'=>'Farbton','color_name'=>'Farbton','size'=>'Größe','part_number'=>'HAN','item_type_name'=>'Produktart'] as $key=>$label){
            $values=[];
            foreach(is_array($a[$key]??null)?$a[$key]:[] as $entry){
                $v=$entry['value']??null;
                if(is_string($v)&&trim($v)!==''&&mb_strlen($v)<=140)$values[trim($v)]=true;
            }
            if(count($values)===1&&!isset($out[$label]))$out[$label]=(string)array_key_first($values);
        }
        if(($p['pack_kind']??null)==='single')$out['Packungsgröße']='1';
        if(preg_match_all('/\b(\d+(?:[,.]\d+)?)\s*ml\b/iu',(string)($p['title']??''),$m)===1){
            $out['Füllmenge']=str_replace(',','.',$m[1][0]);$out['Maßeinheit']='ml';
        }
        return $out;
    }

    /** Unknown or malformed output is not a fallback value. Model texts are review candidates. */
    public static function validate(array $review,array $input,array $catalog): array
    {
        if(($input['review_version']??null)!==self::VERSION||
            ($input['product_facts']??null)!==self::facts($catalog)||
            ($review['contentVersion']??null)!==self::VERSION||!is_array($review['content']??null))
            return ['state'=>'legacy_or_changed','fields'=>[],'attributes'=>[]];
        $c=$review['content'];$out=['state'=>'review','fields'=>[],'attributes'=>[]];
        foreach(self::TEXT_FIELDS as $key=>[$field,$limit]){
            $v=$c[$key]??null;
            if(!is_string($v))continue;
            $v=trim($v);
            if($v===''||mb_strlen($v)>$limit||$v!==strip_tags($v)||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u',$v)||
                preg_match('~https?://|www\.|[<>{}]~iu',$v))continue;
            if($key==='seoTitle')$v=HubSeoDefaults::brandTitle($v);
            if($key==='metaDescription')$v=HubSeoDefaults::brandDescription($v);
            $out['fields'][$field]=$v;
        }
        $facts=self::facts($catalog);
        foreach(['article','shopify','ebay'] as $channel){
            $list=$c[$channel.'Attributes']??[];
            if(!is_array($list)||count($list)>20)continue;
            $values=[];
            foreach($list as $entry){
                if(!is_array($entry))continue;
                $key=$entry['name']??null;$value=$entry['value']??null;
                if(!is_string($key)||!is_string($value)||!in_array($key,self::ATTRIBUTE_KEYS,true)||
                    !isset($facts[$key])||trim($value)!==$facts[$key])continue;
                $values[$key]=trim($value);
            }
            if($values)$out['attributes'][$channel]=$values;
        }
        return $out;
    }

    /** Replace only defaults created in this very proposal, not a persisted or manual value. */
    public static function merge(array $p,array $ai,array $original): array
    {
        if(($ai['state']??null)!=='verified_review'||($ai['content']['state']??null)!=='review')return $p;
        $id=(int)($ai['id']??0);
        if($id<1)return $p;
        foreach($ai['content']['fields']??[] as $f=>$v){
            if(trim((string)($original[$f]??''))!=='')continue;
            $source=(string)($p['sources'][$f]??'');
            if(isset($p['draft'][$f])&&trim((string)$p['draft'][$f])!==''&&
                !str_starts_with($source,'HW24-Regel: eigener Artikelname')&&!str_starts_with($source,'HW24-SEO-Basisregel'))continue;
            $p['draft'][$f]=$v;$p['fields'][$f]=$v;$p['sources'][$f]='KI-Archiv #'.$id.' · Textvorschlag, fachlich prüfen';
        }
        return $p;
    }
}
