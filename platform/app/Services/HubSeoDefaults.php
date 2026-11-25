<?php
declare(strict_types=1);
namespace App\Services;

/** Deterministic, free baseline only. Not an OpenAI-generated text and not a publication. */
final class HubSeoDefaults
{
    public static function propose(array $fields): array
    {
        $name=trim(preg_replace('/\s+/u',' ',strip_tags((string)($fields['Artikelname']??'')))??'');
        if($name==='')return [];
        $out=[];
        if(trim((string)($fields['Titel-Tag (SEO)']??''))===''){
            $out['Titel-Tag (SEO)']=self::shorten($name,65);
        }
        if(trim((string)($fields['Meta-Description (SEO)']??''))===''){
            $out['Meta-Description (SEO)']=self::shorten($name,145).' entdecken.';
        }
        return $out;
    }
    private static function shorten(string $s,int $max): string
    {
        if(mb_strlen($s)<=$max)return $s;
        $part=mb_substr($s,0,$max-1);
        $pos=mb_strrpos($part,' ');
        if($pos!==false&&$pos>=$max*0.65)$part=mb_substr($part,0,$pos);
        return rtrim($part,' ,.;-').'…';
    }
}
