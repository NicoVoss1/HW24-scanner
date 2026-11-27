<?php
declare(strict_types=1);
namespace App\Services;

/** Operator-approved category hierarchy. Suggestions only, never change existing JTL data. */
final class HubCategoryPolicy
{
    public static function suggest(string $title,string $group=''): ?array
    {
        $t=mb_strtolower(trim($title));
        $care=preg_match('/\b(?:trocken[\s-]*shampoo|dry[\s-]*shampoo|shampoo|conditioner|haarspülung|haarkur|haarmaske)\b/u',$t);
        $colour=preg_match('/\b(?:haarfarben?|haarfarbcreme|haarcoloration|haarcolor|coloration|koleston|igora royal|color touch|illumina color|socolor|super brillant touch|shinfinity|hair colo[u]?r|permanent colo[u]?r)\b/u',$t);
        // Verified Revlon product family: Colorsmetique High Coverage is hair colour.
        // Do not classify every Revlon product or every "color" title as hair dye.
        if(preg_match('/\\brevlon(?:issimo)?\\b/u',$t)&&preg_match('/\\bcolorsmetique\\b.*\\bhigh\\s*coverage\\b/u',$t))$colour=1;
        $style=preg_match('/\b(?:haar[\s-]*spray|hair[\s-]*spray|styling[\s-]*spray|haargel|stylinggel|haarwachs|stylingwachs|schaumfestiger)\b/u',$t);
        $n=(int)$care+(int)$colour+(int)$style;
        if($n===0&&trim($group)!=='')return self::suggest($group,'');
        if($n!==1)return null; // ambiguous titles require manual review
        return ['Kategorie Ebene 1'=>'Friseurbedarf','Kategorie Ebene 2'=>$care?'Haarpflege':($colour?'Haarfarben':'Hairstyling')];
    }

    public static function conflict(string $title,string $group,string $category1,string $category2): bool
    {
        $s=self::suggest($title,$group);
        if($s===null)return false;
        return ($category1!==''&&mb_strtolower(trim($category1))!==mb_strtolower($s['Kategorie Ebene 1']))||
            ($category2!==''&&mb_strtolower(preg_replace('/\s+/u','',trim($category2)))!==mb_strtolower($s['Kategorie Ebene 2']));
    }
}
