<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AmazonPriceArchive;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

final class AmazonPriceArchiveTest extends TestCase
{
    private function db(): PDO
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA foreign_keys=ON');
        $db->exec('CREATE TABLE hw24_amazon_price_snapshots (
            id INTEGER PRIMARY KEY, observation_key TEXT UNIQUE, marketplace_id TEXT,
            asin TEXT,item_condition TEXT,observed_at_utc TEXT,expires_at_utc TEXT,
            total_offer_count INTEGER,returned_offer_count INTEGER,buybox_json TEXT,lowest_json TEXT)');
        $db->exec('CREATE TABLE hw24_amazon_price_offers (
            snapshot_id INTEGER REFERENCES hw24_amazon_price_snapshots(id) ON DELETE CASCADE,
            offer_index INTEGER,seller_id TEXT,item_price TEXT,item_currency TEXT,shipping_price TEXT,
            shipping_currency TEXT,total_price TEXT,total_currency TEXT,is_fba INTEGER,
            is_buybox_winner INTEGER,offer_condition TEXT,PRIMARY KEY(snapshot_id,offer_index))');
        return $db;
    }

    private function sample(int $secondsAgo=10): array
    {
        $price=['amount'=>7.25,'currency'=>'EUR'];
        return ['ok'=>true,'source'=>'amazon_live','marketplace_id'=>'A1PA6795UKMFR9',
            'asin'=>'B000000001','retrieved_at_utc'=>gmdate('c',time()-$secondsAgo),
            'total_offer_count'=>25,'buybox_prices'=>[['landed_price'=>$price]],'lowest_prices'=>[],
            'offers'=>[['seller_id'=>'A12345','item_price'=>$price,'shipping'=>['amount'=>1,'currency'=>'EUR'],
                'total'=>['amount'=>1,'currency'=>'EUR'],'is_fba'=>false],
                ['seller_id'=>'A67890','item_price'=>$price,'shipping'=>null],
                ['seller_id'=>'A99999','item_price'=>$price,'shipping'=>['amount'=>1,'currency'=>'GBP']]]];
    }

    public function test_record_replay_history_and_retention(): void
    {
        $db=$this->db();
        $a=new AmazonPriceArchive($db,30);
        $old=$this->sample(60);
        $new=$this->sample();
        $first=$a->record($old,'B000000001');
        self::assertTrue($first['inserted']);
        self::assertSame(['id'=>$first['id'],'inserted'=>false],$a->record($old,'B000000001'));
        $second=$a->record($new,'B000000001');
        $rows=$a->latest('B000000001');
        self::assertCount(2,$rows);
        self::assertSame($second['id'],(int)$rows[0]['id']);
        self::assertSame('8.2500',$rows[0]['offers'][0]['total_price']);
        self::assertNull($rows[0]['offers'][1]['total_price']);
        self::assertNull($rows[0]['offers'][2]['total_price']);
        self::assertSame(25,(int)$rows[0]['total_offer_count']);
        self::assertCount(3,$rows[0]['offers']);
        self::assertSame([], $a->latest('B000000002'));
        $db->exec("UPDATE hw24_amazon_price_snapshots SET expires_at_utc='2000-01-01 00:00:00' WHERE id=".$first['id']);
        self::assertCount(1,$a->latest('B000000001'));
        self::assertSame(1,$a->purgeExpired());
        self::assertSame(3,(int)$db->query('SELECT COUNT(*) FROM hw24_amazon_price_offers')->fetchColumn());
    }

    public function test_cache_cannot_become_a_new_observation(): void
    {
        $v=$this->sample();
        $v['source']='cache';
        $this->expectException(InvalidArgumentException::class);
        (new AmazonPriceArchive($this->db(),30))->record($v,'B000000001');
    }

    public function test_mismatched_asin_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AmazonPriceArchive::normalize($this->sample(),'B000000002');
    }
}
