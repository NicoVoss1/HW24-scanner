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
            $out['Titel-Tag (SEO)']=self::brandTitle($name);
        }
        if(trim((string)($fields['Meta-Description (SEO)']??''))===''){
            $out['Meta-Description (SEO)']=self::brandDescription($name.' entdecken.');
        }
        return $out;
    }
    /** Store branding is for SEO only, not the product name or manufacturer. */
    public static function brandTitle(string $text): string
    {
        $text=trim($text);
        if(preg_match('/\\bHAIRWORLD24(?:\\.de)?\\s*$/iu',$text))return self::shorten($text,65);
        // Do not cut an exact long product title solely to force a shop suffix.
        $suffix=' | HAIRWORLD24';
        return mb_strlen($text.$suffix)<=65?$text.$suffix:self::shorten($text,65);
    }

    public static function brandDescription(string $text): string
    {
        $text=trim($text);
        if(preg_match('/\\bHAIRWORLD24\\.de\\b/iu',$text))return self::shorten($text,160);
        $suffix=' Bei HAIRWORLD24.de entdecken.';
        // Reserve room for the brand, without adding a duplicate call to action.
        $text=trim(preg_replace('/\\s+entdecken[.!]?$/iu','',$text)??$text);
        $text=rtrim(self::shorten($text,160-mb_strlen($suffix)-1),' .');
        return $text.'.'.$suffix;
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
