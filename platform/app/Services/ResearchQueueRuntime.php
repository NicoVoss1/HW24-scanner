<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

/** TEST-only trusted CLI entrypoint. No config/DDL on boot, no scheduled work yet. */
final class ResearchQueueRuntime
{
    private static function db(): PDO
    {
        if(config('hw24_research_queue.enabled')!==true)throw new RuntimeException('Queue disabled');
        $path=dirname(base_path(),2).'/hw24-research-queue.json';
        if(!is_file($path)||is_link($path)||!is_readable($path)||(fileperms($path)&0077)!==0)
            throw new RuntimeException('Private queue config unavailable');
        $cfg=json_decode((string)file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
        if(!is_array($cfg)||($cfg['database']??null)!=='hw24_artikelmanager'||
            ($cfg['user']??null)!=='hw24_research_queue'||!is_string($cfg['password']??null)||strlen($cfg['password'])<32)
            throw new RuntimeException('Invalid queue config');
        return new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=hw24_artikelmanager;charset=utf8mb4',
            $cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
    }
    public static function queue(): ResearchQueue
    {
        return new ResearchQueue(self::db());
    }
    public static function worker(LegacyReadBridge $bridge): ResearchQueueWorker
    {
        if(config('hw24_research_queue.worker_enabled')!==true)throw new RuntimeException('Queue worker disabled');
        if(!AmazonPriceArchiveRuntime::enabled())throw new RuntimeException('Price archive disabled');
        $heartbeat=dirname(base_path(),2).'/hw24-price-archive-purge.ok';
        $ts=is_file($heartbeat)&&!is_link($heartbeat)?trim((string)file_get_contents($heartbeat)):'';
        if(!ctype_digit($ts)||(int)$ts<time()-3600||(int)$ts>time()+60)
            throw new RuntimeException('Price archive purge unavailable');
        $db=self::db();
        $price=AmazonPriceArchiveRuntime::open();
        // Independently verify the exact persisted catalog observation. No JSON/raw data read.
        $verify=static function(int $id,string $ean)use($db):bool{
            $q=$db->prepare('SELECT 1 FROM hw24_amazon_catalog_snapshots
                WHERE id=? AND marketplace_id=? AND searched_ean=? AND response_status=200 LIMIT 1');
            $q->execute([$id,'A1PA6795UKMFR9',$ean]);
            return $q->fetchColumn()!==false;
        };
        return new ResearchQueueWorker(new ResearchQueue($db),
            new ResearchJobProcessor($bridge,$price,$verify,
                static fn(string $asin)=>AmazonPriceArchiveRuntime::fetch($asin,$bridge),
                static fn(int $s)=>sleep($s)));
    }
}
