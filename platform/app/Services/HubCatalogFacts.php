<?php
declare(strict_types=1);
namespace App\Services;

/** Only explicit single-item volume evidence. Do not equate pack count, mass and volume. */
final class HubCatalogFacts
{
    /** Common product facts after the shared GTIN/single-pack catalog check.
     * Operational defaults and own prices are deliberately excluded. */
    public static function productFields(array $catalog):array
    {
        $p=$catalog['item']??[];$attrs=$catalog['attributes']??[];
        if(($p['gtin_match']??false)!==true||($p['pack_kind']??null)!=='single')return [];
        $out=[];
        foreach(['ASIN'=>'asin','Artikelname'=>'title','Hersteller'=>'manufacturer'] as $field=>$key){
            $v=$p[$key]??null;
            if(is_string($v)&&trim($v)!=='')$out[$field]=['value'=>$v,'suffix'=>''];
        }
        $parts=[];
        foreach(is_array($attrs['part_number']??null)?$attrs['part_number']:[] as $a){
            if(!is_array($a)||isset($a['marketplace_id'])&&$a['marketplace_id']!=='A1PA6795UKMFR9')continue;
            $v=$a['value']??null;
            if(is_string($v)&&trim($v)!==''&&mb_strlen(trim($v))<=140)$parts[trim($v)]=true;
        }
        if(count($parts)===1)$out['HAN']=['value'=>(string)array_key_first($parts),'suffix'=>' part_number'];
        $weight=self::itemWeight($attrs);
        foreach($weight['fields'] as $f=>$v)$out[$f]=['value'=>$v,'suffix'=>' '.$weight['source']];
        return $out;
    }

    public static function volume(string $title,array $attributes,array $draft): array
    {
        $values=[];$origins=[];
        if(preg_match_all('/\b(\d+(?:[,.]\d+)?)\s*(ml|milliliter|millilitres?|l|liter|litres?)\b/iu',$title,$m,PREG_SET_ORDER)){
            foreach($m as $match){
                $n=self::ml(str_replace(',','.',$match[1]),$match[2]);
                if($n!==null){$values[$n]=true;$origins['Titelangabe']=true;}
            }
        }
        foreach(is_array($attributes['item_volume']??null)?$attributes['item_volume']:[] as $a){
            if(!is_array($a)||isset($a['marketplace_id'])&&$a['marketplace_id']!=='A1PA6795UKMFR9')continue;
            $n=self::ml($a['value']??null,$a['unit']??null);
            if($n!==null){$values[$n]=true;$origins['item_volume']=true;}
        }
        if(count($values)>1)return ['state'=>'conflict','fields'=>[]];
        if(!$values)return ['state'=>'missing','fields'=>[]];
        $v=(string)array_key_first($values);
        $old=trim((string)($draft['Inhalt/Menge']??''));
        $unit=trim((string)($draft['Maßeinheit']??''));
        if(($unit!==''&&mb_strtolower($unit)!=='ml')||
            ($old!==''&&(!is_numeric(str_replace(',','.',$old))||(float)str_replace(',','.',$old)!==(float)$v)))
            return ['state'=>'conflict','fields'=>[]];
        $fields=[];
        if($old==='')$fields['Inhalt/Menge']=$v;
        if($unit==='')$fields['Maßeinheit']='ml';
        return ['state'=>'verified','fields'=>$fields,'source'=>implode(' + ',array_keys($origins))];
    }

    /** Item weight only: JTL uses kilograms. Never substitute package or dispatch weight. */
    public static function itemWeight(array $attributes):array
    {
        $values=[];
        foreach(is_array($attributes['item_weight']??null)?$attributes['item_weight']:[] as $a){
            if(!is_array($a)||isset($a['marketplace_id'])&&$a['marketplace_id']!=='A1PA6795UKMFR9')continue;
            $value=$a['value']??null;$unit=$a['unit']??null;
            if(!is_scalar($value)||!is_string($unit)||!preg_match('/^\d+(?:\.\d{1,8})?$/D',(string)$value))continue;
            $factor=match(mb_strtolower(trim($unit))){
                'kg','kilogram','kilograms'=>1,
                'g','gram','grams'=>0.001,
                'mg','milligram','milligrams'=>0.000001,
                'lb','pound','pounds'=>0.45359237,
                'oz','ounce','ounces'=>0.028349523125,default=>null};
            if($factor===null)continue;
            $n=(float)$value*$factor;
            if(!is_finite($n)||$n<0.000001||$n>10000)continue;
            $v=rtrim(rtrim(number_format($n,6,'.',''),'0'),'.');
            if($v!=='0')$values[$v]=true;
        }
        if(count($values)>1)return ['state'=>'conflict','fields'=>[]];
        if(!$values)return ['state'=>'missing','fields'=>[]];
        return ['state'=>'verified','fields'=>['Artikelgewicht'=>(string)array_key_first($values)],'source'=>'item_weight (kg)'];
    }

    private static function ml(mixed $value,mixed $unit): ?string
    {
        if(!is_scalar($value)||!is_string($unit)||!preg_match('/^\d+(?:\.\d{1,6})?$/D',(string)$value))return null;
        $factor=match(mb_strtolower(trim($unit))){
            'ml','milliliter','milliliters','millilitre','millilitres'=>1,
            'l','liter','liters','litre','litres'=>1000,default=>null};
        if($factor===null)return null;
        $n=(float)$value*$factor;
        if(!is_finite($n)||$n<=0||$n>1000000)return null;
        return rtrim(rtrim(number_format($n,6,'.',''),'0'),'.');
    }
}
