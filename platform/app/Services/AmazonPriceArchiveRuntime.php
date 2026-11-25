<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/** Explicit TEST opt-in; private credential file, no DDL or remote DB access. */
final class AmazonPriceArchiveRuntime
{
    private static function privateFile(): string
    {
        return dirname(base_path(),2).'/hw24-price-archive.json';
    }

    public static function enabled(): bool
    {
        return config('hw24_price_archive.enabled')===true;
    }

    public static function open(): AmazonPriceArchive
    {
        if (!self::enabled()) throw new RuntimeException('Price archive disabled');
        $file=self::privateFile();
        if (!is_file($file)||is_link($file)||!is_readable($file)||(fileperms($file)&0077)!==0)
            throw new RuntimeException('Private archive configuration unavailable');
        $raw=file_get_contents($file);
        $cfg=is_string($raw)?json_decode($raw,true,16,JSON_THROW_ON_ERROR):null;
        if (!is_array($cfg)||($cfg['database']??null)!=='hw24_artikelmanager'
            || ($cfg['user']??null)!=='hw24_price_archive'
            || !is_string($cfg['password']??null)||strlen($cfg['password'])<32
            || !is_int($cfg['retention_days']??null)||$cfg['retention_days']<1||$cfg['retention_days']>90)
            throw new RuntimeException('Invalid archive configuration');
        $pdo=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=hw24_artikelmanager;charset=utf8mb4',
            $cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
        return new AmazonPriceArchive($pdo,$cfg['retention_days']);
    }

    private static function heartbeat(): string
    {
        return dirname(base_path(),2).'/hw24-price-archive-purge.ok';
    }

    /** Purge scheduled outside request; new captures require recent success. */
    public static function purge(): int
    {
        $n=self::open()->purgeExpired();
        $file=self::heartbeat();
        if (is_link($file)) throw new RuntimeException('Invalid heartbeat path');
        $old=umask(0077);
        try {
            if (file_put_contents($file,(string)time(),LOCK_EX)===false) throw new RuntimeException('Cannot persist purge heartbeat');
        } finally { umask($old); }
        return $n;
    }

    private static function ready(): bool
    {
        $file=self::heartbeat();
        if (!is_file($file)||is_link($file)||!is_readable($file)) return false;
        $ts=trim((string)file_get_contents($file));
        return ctype_digit($ts)&&(int)$ts<=time()+60&&(int)$ts>=time()-3600;
    }

    /** Only provider summary with original context; no historical offer payload in Livewire. */
    public static function summary(array $archive): array
    {
        $history=[];
        foreach (array_slice($archive['history']??[],0,2) as $row) {
            $history[]=['id'=>(int)$row['id'],'observed_at_utc'=>$row['observed_at_utc'],
                'total_offer_count'=>$row['total_offer_count'],'returned_offer_count'=>$row['returned_offer_count'],
                'buybox'=>$row['buybox'],'lowest'=>$row['lowest']];
        }
        $archive['history']=$history;
        return $archive;
    }

    /** Exactly one pricing GET. Failure of storage never triggers a second call. */
    public static function fetch(string $asin,LegacyReadBridge $bridge): array
    {
        if (!self::enabled()) return AmazonPriceResearch::fetch($asin,$bridge);
        $archive=null;
        try { if (self::ready()) $archive=self::open(); } catch (Throwable) { /* fail closed */ }
        $result=AmazonPriceResearch::fetch($asin,$bridge,$archive);
        if ($archive===null) $result['archive']['state']='storage_not_ready';
        if ($archive!==null && $result['archive']['history']===[]) {
            try { $result['archive']['history']=$archive->latest($asin,2); } catch (Throwable) { /* leave status explicit */ }
        }
        $result['archive']=self::summary($result['archive']);
        return $result;
    }
}
