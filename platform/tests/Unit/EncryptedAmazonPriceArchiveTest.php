<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\EncryptedAmazonPriceArchive;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EncryptedAmazonPriceArchiveTest extends TestCase
{
    private function db(): PDO
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec('CREATE TABLE hw24_amazon_price_history_secure(id INTEGER PRIMARY KEY,
            lookup_key TEXT, observation_key TEXT UNIQUE, observed_at_utc TEXT,
            expires_at_utc TEXT, key_version INTEGER,ciphertext TEXT)');
        return $db;
    }
    private function sample(int $age=30): array
    {
        return ['ok'=>true,'source'=>'amazon_live','marketplace_id'=>'A1PA6795UKMFR9',
            'asin'=>'B000000001','retrieved_at_utc'=>gmdate('c',time()-$age),
            'total_offer_count'=>1,'buybox_prices'=>[],'lowest_prices'=>[],
            'offers'=>[['seller_id'=>'TESTSELLER1','item_price'=>['amount'=>7.25,'currency'=>'EUR'],
                'shipping'=>['amount'=>1,'currency'=>'EUR']]]];
    }
    public function test_encrypted_storage_roundtrip_and_replay(): void
    {
        $db=$this->db();$key=random_bytes(32);
        $a=new EncryptedAmazonPriceArchive($db,$key,7);
        $v=$this->sample();
        $first=$a->record($v,'B000000001');
        self::assertTrue($first['inserted']);
        self::assertSame(['id'=>$first['id'],'inserted'=>false],$a->record($v,'B000000001'));
        $a->record($this->sample(120),'B000000001');
        $rows=$a->latest('B000000001');
        self::assertCount(2,$rows);
        self::assertSame($first['id'],$rows[0]['id']);
        self::assertSame('8.2500',$rows[0]['offers'][0]['total']['amount']);
        self::assertSame([], $a->latest('B000000002'));
        $raw=json_encode($db->query('SELECT * FROM hw24_amazon_price_history_secure')->fetchAll(PDO::FETCH_ASSOC));
        foreach(['B000000001','TESTSELLER1','8.2500','EUR'] as $secret) {
            // Check structured plaintext is not present; short random ciphertext substrings can coincide.
            if($secret!=='EUR') self::assertStringNotContainsString($secret,$raw);
        }
        $wrong=new EncryptedAmazonPriceArchive($db,random_bytes(32),7);
        self::assertSame([], $wrong->latest('B000000001'));
        $db->exec("UPDATE hw24_amazon_price_history_secure SET expires_at_utc='2000-01-01 00:00:00'");
        self::assertSame([], $a->latest('B000000001'));
        self::assertSame(2,$a->purgeExpired());
    }
    public function test_ciphertext_tampering_fails_closed(): void
    {
        $db=$this->db();$a=new EncryptedAmazonPriceArchive($db,random_bytes(32),7);
        $a->record($this->sample(),'B000000001');
        $v=$db->query('SELECT ciphertext FROM hw24_amazon_price_history_secure')->fetchColumn();
        $raw=base64_decode($v,true);$raw[30]=chr(ord($raw[30])^1);
        $q=$db->prepare('UPDATE hw24_amazon_price_history_secure SET ciphertext=?');
        $q->execute([base64_encode($raw)]);
        $this->expectException(RuntimeException::class);
        $a->latest('B000000001');
    }
}
