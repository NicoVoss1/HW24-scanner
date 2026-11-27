<?php
declare(strict_types=1);
namespace App\Services;

/** Common decision contract. Only source-bound options are selectable. */
final class HubFieldDecisions
{
    public static function options(array $proposal):array
    {
        if(!in_array($proposal['state']??null,['review','missing'],true))return [];
        $out=[];
        foreach($proposal['fields']??[] as $f=>$v){
            $source=$proposal['sources'][$f]??null;
            if(!in_array($f,ScanResearch::FIELDS,true)||!is_string($v)||trim($v)===''||!is_string($source)||trim($source)==='')continue;
            $out[$f][hash('sha256',$f."\0".$v)]=['value'=>$v,'source'=>$source];
        }
        return $out;
    }

    public static function unique(array $options):array
    {
        $selected=[];
        foreach($options as $field=>$choices)if(is_array($choices)&&count($choices)===1&&!((array_values($choices)[0]['replacement']??false)))$selected[$field]=(string)array_key_first($choices);
        return $selected;
    }

    public static function resolve(array $options,array $selection):array
    {
        $fields=[];$sources=[];
        foreach($selection as $field=>$token){
            if($token==='')continue;
            if(!is_string($token)||!isset($options[$field][$token]))throw new \RuntimeException('Unknown field selection');
            $value=$options[$field][$token];
            $fields[$field]=$value['value'];$sources[$field]=$value['source'];
        }
        return ['fields'=>$fields,'sources'=>$sources];
    }
}
