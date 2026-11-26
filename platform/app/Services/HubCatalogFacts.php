<?php
declare(strict_types=1);
namespace App\Services;

/** Only explicit single-item volume evidence. Do not equate pack count, mass and volume. */
final class HubCatalogFacts
{
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
