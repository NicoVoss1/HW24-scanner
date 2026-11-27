<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;

/**
 * Explicit opt-in price archive. No network calls, no JTL writes, no automatic
 * migrations. Caller supplies private PDO; never uses framework SQLite by
 * default. Only normalized successful live observations; not cache reads.
 */
final class AmazonPriceArchive implements PriceArchiveStore
{
    public function __construct(private PDO $db, private int $retentionDays)
    {
        // Conservative technical ceiling; operator must set justified shorter
        // retention and enable automatic purge BEFORE production activation.
        if ($retentionDays < 1 || $retentionDays > 90) {
            throw new InvalidArgumentException('Archive retention must be 1-90 days');
        }
        if ($db->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            throw new InvalidArgumentException('Archive requires PDO exceptions');
        }
    }

    private static function amount(mixed $raw): ?array
    {
        if (!is_array($raw) || !isset($raw['amount'],$raw['currency'])
            || !is_numeric($raw['amount']) || !is_string($raw['currency'])
            || !preg_match('/^[A-Z]{3}$/D',$raw['currency'])) return null;
        $v=(float)$raw['amount'];
        if (!is_finite($v) || $v<0 || $v>1000000000000) return null;
        return ['amount'=>number_format($v,4,'.',''),'currency'=>$raw['currency']];
    }

    private static function short(mixed $v,int $max): ?string
    {
        return is_string($v)&&strlen($v)<=$max&&$v!==''?$v:null;
    }

    private static function summary(mixed $raw): array
    {
        $out=[];
        if (!is_array($raw)) return $out;
        foreach (array_slice($raw,0,15) as $r) {
            if (!is_array($r)) continue;
            $out[]=['condition'=>self::short($r['condition']??null,40),
                'fulfillment'=>self::short($r['fulfillment']??null,40),
                'listing_price'=>self::amount($r['listing_price']??null),
                'landed_price'=>self::amount($r['landed_price']??null)];
        }
        return $out;
    }

    public static function normalize(array $raw, string $expectedAsin): array
    {
        if (($raw['ok']??false)!==true || ($raw['source']??'')!=='amazon_live'
            || ($raw['marketplace_id']??'')!=='A1PA6795UKMFR9'
            || !preg_match('/^[A-Z0-9]{10}$/D',$expectedAsin)
            || ($raw['asin']??'')!==$expectedAsin
            || !is_array($raw['offers']??null) || count($raw['offers'])>20) {
            throw new InvalidArgumentException('Not an eligible live price observation');
        }
        $at=$raw['retrieved_at_utc']??null;
        if (!is_string($at) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|\+00:00)$/D',$at)) {
            throw new InvalidArgumentException('Missing UTC observation timestamp');
        }
        $date=new DateTimeImmutable($at);
        if ($date->format('Y-m-d\TH:i:s')!==substr($at,0,19)
            || $date->getTimestamp()>time()+300) {
            throw new InvalidArgumentException('Invalid observation timestamp');
        }
        $offers=[];
        foreach ($raw['offers'] as $o) {
            if (!is_array($o)) throw new InvalidArgumentException('Malformed price offer');
            $seller=$o['seller_id']??null;
            $item=self::amount($o['item_price']??null);
            $ship=self::amount($o['shipping']??null);
            $total=null;
            if ($item!==null && $ship!==null && $item['currency']===$ship['currency']) {
                $total=['amount'=>number_format((float)$item['amount']+(float)$ship['amount'],4,'.',''),
                    'currency'=>$item['currency']];
            }
            $offers[]=['seller_id'=>is_string($seller)&&preg_match('/^[A-Z0-9]{5,40}$/D',$seller)?$seller:null,
                'item'=>$item,'shipping'=>$ship,'total'=>$total,
                'fba'=>is_bool($o['is_fba']??null)?$o['is_fba']:null,
                'buybox'=>is_bool($o['is_buybox_winner']??null)?$o['is_buybox_winner']:null,
                'condition'=>self::short($o['condition']??null,40)];
        }
        $count=$raw['total_offer_count']??null;
        return ['marketplace'=>'A1PA6795UKMFR9','asin'=>$expectedAsin,'condition'=>'New',
            'observed_at'=>$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'total_count'=>is_int($count)&&$count>=count($offers)?$count:null,
            'returned_count'=>count($offers),'buybox'=>self::summary($raw['buybox_prices']??[]),
            'lowest'=>self::summary($raw['lowest_prices']??[]),'offers'=>$offers];
    }

    private static function json(array $v): string
    {
        return json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    /** Replay of same timestamp + normalized payload is idempotent. */
    public function record(array $raw,string $expectedAsin): array
    {
        $v=self::normalize($raw,$expectedAsin);
        $expiry=(new DateTimeImmutable($v['observed_at'],new DateTimeZone('UTC')))
            ->modify('+'.$this->retentionDays.' days')->format('Y-m-d H:i:s');
        if ($expiry<=gmdate('Y-m-d H:i:s')) throw new InvalidArgumentException('Expired observation');
        $key=hash('sha256',self::json($v));
        $find=$this->db->prepare('SELECT id FROM hw24_amazon_price_snapshots WHERE observation_key=?');
        $find->execute([$key]);
        if (($old=$find->fetchColumn())!==false) return ['id'=>(int)$old,'inserted'=>false];
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('INSERT INTO hw24_amazon_price_snapshots
                (observation_key,marketplace_id,asin,item_condition,observed_at_utc,expires_at_utc,total_offer_count,returned_offer_count,buybox_json,lowest_json)
                VALUES (?,?,?,?,?,?,?,?,?,?)');
            $s->execute([$key,$v['marketplace'],$v['asin'],$v['condition'],$v['observed_at'],$expiry,
                $v['total_count'],$v['returned_count'],self::json($v['buybox']),self::json($v['lowest'])]);
            $id=(int)$this->db->lastInsertId();
            $s=$this->db->prepare('INSERT INTO hw24_amazon_price_offers
                (snapshot_id,offer_index,seller_id,item_price,item_currency,shipping_price,shipping_currency,total_price,total_currency,is_fba,is_buybox_winner,offer_condition)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            foreach ($v['offers'] as $i=>$o) {
                $s->execute([$id,$i,$o['seller_id'],$o['item']['amount']??null,$o['item']['currency']??null,
                    $o['shipping']['amount']??null,$o['shipping']['currency']??null,
                    $o['total']['amount']??null,$o['total']['currency']??null,
                    $o['fba']===null?null:(int)$o['fba'],$o['buybox']===null?null:(int)$o['buybox'],$o['condition']]);
            }
            $this->db->commit();
            return ['id'=>$id,'inserted'=>true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof PDOException && (string)$e->getCode()==='23000') {
                $find->execute([$key]);
                if (($old=$find->fetchColumn())!==false) return ['id'=>(int)$old,'inserted'=>false];
            }
            throw $e;
        }
    }

    /** Newest and previous observations, same marketplace/ASIN/condition. */
    public function latest(string $asin,int $limit=2): array
    {
        if (!preg_match('/^[A-Z0-9]{10}$/D',$asin)||$limit<1||$limit>100)
            throw new InvalidArgumentException('Invalid history query');
        $s=$this->db->prepare("SELECT id,observed_at_utc,total_offer_count,returned_offer_count,buybox_json,lowest_json
            FROM hw24_amazon_price_snapshots WHERE marketplace_id=? AND asin=? AND item_condition=?
            AND expires_at_utc>? ORDER BY observed_at_utc DESC,id DESC LIMIT ".(int)$limit);
        $s->execute(['A1PA6795UKMFR9',$asin,'New',gmdate('Y-m-d H:i:s')]);
        $rows=$s->fetchAll(PDO::FETCH_ASSOC);
        $o=$this->db->prepare('SELECT seller_id,item_price,item_currency,shipping_price,shipping_currency,total_price,total_currency,is_fba,is_buybox_winner,offer_condition
            FROM hw24_amazon_price_offers WHERE snapshot_id=? ORDER BY offer_index');
        foreach ($rows as &$r) {
            $r['buybox']=json_decode($r['buybox_json'],true,32,JSON_THROW_ON_ERROR);
            $r['lowest']=json_decode($r['lowest_json'],true,32,JSON_THROW_ON_ERROR);
            unset($r['buybox_json'],$r['lowest_json']);
            $o->execute([$r['id']]);
            $r['offers']=$o->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($r);
        return $rows;
    }

    /** Explicit retention maintenance; FK cascade only affects new archive. */
    public function purgeExpired(): int
    {
        $s=$this->db->prepare('DELETE FROM hw24_amazon_price_snapshots WHERE expires_at_utc<=?');
        $s->execute([gmdate('Y-m-d H:i:s')]);
        return $s->rowCount();
    }
}
