<?php
declare(strict_types=1);
namespace App\Services;

/** Explicit observed catalog-to-JTL name equivalences. No fuzzy manufacturer matching. */
final class HubManufacturerPolicy
{
    public static function jtlSearchName(string $name): string
    {
        $name=trim($name);
        return match(mb_strtolower($name)){
            'revlon professional','revlon'=>'Revlon',
            default=>$name,
        };
    }

    public static function equivalent(string $a,string $b): bool
    {
        return mb_strtolower(self::jtlSearchName($a))===mb_strtolower(self::jtlSearchName($b));
    }
    /** Resolve only a single existing name/ID pair in the current JTL mirror. */
    public static function resolve(string $candidate,array $rows):?array
    {
        if(trim($candidate)==='')return null;
        $matches=[];
        foreach($rows as $r){
            $name=$r['hersteller']??null;$id=$r['kHersteller']??null;
            if(!is_string($name)||trim($name)===''||!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<1||
                !self::equivalent($candidate,$name))continue;
            $matches[(string)$id."\0".$name]=['name'=>$name,'id'=>(string)$id];
        }
        return count($matches)===1?array_values($matches)[0]:null;
    }

    /** Read actual observed manufacturer names and IDs, never invent a master record. */
    public static function currentReferences(\PDO $db,?string $snapshot=null):array
    {
        $m=$db->query('SELECT source_id,last_completed_utc,age_minutes FROM hw24_hub_mirror_state ORDER BY last_completed_utc DESC LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        if(!$m||(int)$m['age_minutes']<0||(int)$m['age_minutes']>45||
            ($snapshot!==null&&$snapshot!==(string)$m['last_completed_utc']))throw new \RuntimeException('Manufacturer source unavailable');
        $q=$db->prepare("SELECT DISTINCT d.hersteller,p.kHersteller FROM hw24_hub_article_details d JOIN hw24_hub_peer_source p ON p.source_id=d.source_id AND p.jtl_article_id=d.jtl_article_id WHERE d.source_id=? AND p.kHersteller>0 AND d.hersteller IS NOT NULL AND d.hersteller<>'' LIMIT 1001");
        $q->execute([$m['source_id']]);$rows=$q->fetchAll(\PDO::FETCH_ASSOC);
        if(count($rows)>1000)throw new \RuntimeException('Manufacturer reference exceeds limit');
        return $rows;
    }
}
