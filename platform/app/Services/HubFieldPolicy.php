<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

/** One persisted workspace policy. Existing core requirements cannot be removed. */
final class HubFieldPolicy
{
    private static function path():string {return storage_path('app/hw24-field-policy.json');}
    public static function normalize(array $fields):array
    {
        foreach($fields as $f)if(!is_string($f)||$f==='Artikelnummer'||!in_array($f,ScanResearch::FIELDS,true))throw new RuntimeException('Unsupported required field');
        return array_values(array_intersect(ScanResearch::FIELDS,array_unique([...ScanResearch::REQUIRED,...$fields])));
    }
    public static function read(?string $path=null):array
    {
        $path??=self::path();
        if(is_link($path))throw new RuntimeException('Invalid field policy');
        if(!file_exists($path))return ['revision'=>0,'required'=>self::normalize([]),'actor'=>null,'at'=>null];
        if(!is_file($path)||!is_readable($path)||(fileperms($path)&0077)!==0)throw new RuntimeException('Field policy inaccessible');
        $raw=file_get_contents($path);
        $p=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
        if(!is_array($p)||!is_int($p['revision']??null)||$p['revision']<1||!is_array($p['required']??null))throw new RuntimeException('Invalid field policy');
        $p['required']=self::normalize($p['required']);return $p;
    }
    public static function required():array{
        if(!\Illuminate\Container\Container::getInstance()->bound('path.storage'))return self::normalize([]);
        return self::read()['required'];
    }
    public static function save(array $fields,int $expected,string $actor,?string $path=null):array
    {
        $fields=self::normalize($fields);$path??=self::path();$dir=dirname($path);
        if(!ctype_digit($actor)||!is_dir($dir)||is_link($dir)||is_link($path.'.lock'))throw new RuntimeException('Invalid policy storage');
        $old=umask(0077);$lock=null;$tmp=null;
        try{
            $lock=fopen($path.'.lock','c');
            if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Policy lock unavailable');
            $p=self::read($path);
            if($p['revision']!==$expected)throw new RuntimeException('Policy changed');
            $p=['revision'=>$expected+1,'required'=>$fields,'actor'=>$actor,'at'=>gmdate('c')];
            $tmp=tempnam($dir,'.hw24-policy-');
            if($tmp===false||!chmod($tmp,0600)||file_put_contents($tmp,json_encode($p,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE))===false||!rename($tmp,$path))throw new RuntimeException('Policy not saved');
            $tmp=null;return $p;
        }finally{
            if(is_string($tmp)&&is_file($tmp))unlink($tmp);
            if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}
            umask($old);
        }
    }
    public static function missing(array $draft,?array $required=null):array
    {
        return array_values(array_filter($required??self::required(),fn($f)=>!is_scalar($draft[$f]??null)||trim((string)$draft[$f])===''));
    }
    /** Existing updates must leave every mandatory field confirmed or explicitly selected. */
    public static function missingAfterUpdate(array $current,array $patch,?array $required=null):array
    {
        $required??=self::required();
        return array_values(array_filter($required,static function($f)use($current,$patch){
            if(is_string($patch[$f]??null)&&trim($patch[$f])!=='')return false;
            if($f==='Brutto-VK'&&($current[$f]['state']??null)==='calculated'&&!isset($patch['Steuerklasse'])&&
                trim((string)($patch['Netto-VK']??$current['Netto-VK']['value']??''))!=='')return false;
            return !in_array($current[$f]['state']??null,['value','observed_reference'],true)||
                trim((string)($current[$f]['value']??''))==='';
        }));
    }
    /** Full current field checklist, not proof that unmapped JTL values are absent. */
    public static function review(array $current,array $options=[],?array $required=null):array
    {
        $required??=self::required();$out=[];
        foreach(ScanResearch::FIELDS as $f){
            $c=$current[$f]??[];
            $out[$f]=['required'=>in_array($f,$required,true),'state'=>$c['state']??'unmapped','value'=>$c['value']??'',
                'source'=>$c['source']??'Keine geprüfte Zuordnung','candidates'=>count($options[$f]??[])];
        }
        return $out;
    }
}
