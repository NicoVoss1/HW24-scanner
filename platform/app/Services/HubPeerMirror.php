<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

/** Read only the fixed projections exposed by the private mirror views. */
final class HubPeerMirror
{
    private static function connect(): PDO
    {
        $p=dirname(base_path(),2).'/hw24-hub-reader.json';
        if(!is_file($p)||is_link($p)||!is_readable($p)||(fileperms($p)&0077)!==0)
            throw new RuntimeException('Private reader unavailable');
        $cfg=json_decode((string)file_get_contents($p),true,16,JSON_THROW_ON_ERROR);
        if(($cfg['user']??null)!=='hw24_hub_reader'||!is_string($cfg['password']??null)||strlen($cfg['password'])<32)
            throw new RuntimeException('Invalid reader');
        return new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=hw24_artikelmanager;charset=utf8mb4',
            $cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
    }
    public function read(string $ean): array
    {
        ResearchQueue::canonical($ean);
        $db=self::connect();
        $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $db->beginTransaction();
        try {
            $meta=$db->query('SELECT source_id,last_completed_utc,last_row_count,age_minutes FROM hw24_hub_mirror_state ORDER BY last_completed_utc DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if(!$meta||(int)$meta['age_minutes']<0||(int)$meta['age_minutes']>45)
                throw new RuntimeException('JTL mirror not current');
            $q=$db->prepare('SELECT * FROM hw24_hub_peer_source WHERE source_id=? AND ean=? LIMIT 2');
            $q->execute([$meta['source_id'],$ean]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)!==1)throw new RuntimeException('Unique JTL target required');
            $target=$rows[0];
            if(!ctype_digit((string)$target['kWarengruppe'])||!ctype_digit((string)$target['kHersteller'])||
                (int)$target['kWarengruppe']<1||(int)$target['kHersteller']<1)
                throw new RuntimeException('Group or manufacturer not confirmed');
            $q=$db->prepare('SELECT * FROM hw24_hub_peer_source WHERE source_id=? AND kWarengruppe=? AND kHersteller=? AND jtl_article_id<>? ORDER BY jtl_article_id LIMIT 1001');
            $q->execute([$meta['source_id'],$target['kWarengruppe'],$target['kHersteller'],$target['jtl_article_id']]);
            $peers=$q->fetchAll(PDO::FETCH_ASSOC);
            if(count($peers)>1000)throw new RuntimeException('Peer cohort exceeds bounded sample');
            foreach([$target,...$peers] as $row)if(!is_array($row))throw new RuntimeException('Invalid row');
            $out=['ok'=>true,'snapshot_at'=>ScanEntryPresentation::germanDate($meta['last_completed_utc']),
                'peer_count'=>count($peers),'suggestions'=>HubPeerDefaults::suggest($target,$peers)];
            $db->commit();return $out;
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
}
