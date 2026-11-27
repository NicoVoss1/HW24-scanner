<?php
declare(strict_types=1);
namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

/** Authenticated AES-256-GCM archive; only timestamps and keyed indexes in cleartext. */
final class EncryptedAmazonPriceArchive implements PriceArchiveStore
{
    private string $encryptionKey;
    private string $indexKey;
    public function __construct(private PDO $db,string $master,private int $retentionDays)
    {
        if(strlen($master)!==32||$retentionDays<1||$retentionDays>90||
            !function_exists('openssl_encrypt')||!in_array('aes-256-gcm',openssl_get_cipher_methods(),true)||
            $db->getAttribute(PDO::ATTR_ERRMODE)!==PDO::ERRMODE_EXCEPTION)
            throw new InvalidArgumentException('Secure price archive configuration invalid');
        $this->encryptionKey=hash_hkdf('sha256',$master,32,'hw24-price-encryption-v1');
        $this->indexKey=hash_hkdf('sha256',$master,32,'hw24-price-index-v1');
    }

    private static function json(array $v): string
    {
        return json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    private function index(string $text): string
    {
        return hash_hmac('sha256',$text,$this->indexKey);
    }
    private function lookup(string $asin): string
    {
        if(!preg_match('/^[A-Z0-9]{10}$/D',$asin)) throw new InvalidArgumentException('Invalid ASIN');
        return $this->index('lookup|A1PA6795UKMFR9|New|'.$asin);
    }
    private static function aad(string $lookup,string $observation,string $observed,string $expires): string
    {
        return 'hw24-price-v1|'.$lookup.'|'.$observation.'|'.$observed.'|'.$expires;
    }
    private function seal(string $plain,string $aad): string
    {
        $iv=random_bytes(12);$tag='';
        $cipher=openssl_encrypt($plain,'aes-256-gcm',$this->encryptionKey,OPENSSL_RAW_DATA,$iv,$tag,$aad,16);
        if($cipher===false||strlen($tag)!==16) throw new RuntimeException('Encryption failed');
        return base64_encode($iv.$tag.$cipher);
    }
    private function unseal(string $encoded,string $aad): array
    {
        $blob=base64_decode($encoded,true);
        if($blob===false||strlen($blob)<29) throw new RuntimeException('Invalid encrypted observation');
        $plain=openssl_decrypt(substr($blob,28),'aes-256-gcm',$this->encryptionKey,OPENSSL_RAW_DATA,substr($blob,0,12),substr($blob,12,16),$aad);
        if($plain===false) throw new RuntimeException('Archive authentication failed');
        $v=json_decode($plain,true,32,JSON_THROW_ON_ERROR);
        if(!is_array($v)) throw new RuntimeException('Invalid observation');
        return $v;
    }
    public function record(array $raw,string $expectedAsin): array
    {
        $v=AmazonPriceArchive::normalize($raw,$expectedAsin);
        $observed=$v['observed_at'];
        $expires=(new DateTimeImmutable($observed,new DateTimeZone('UTC')))->modify('+'.$this->retentionDays.' days')->format('Y-m-d H:i:s');
        if($expires<=gmdate('Y-m-d H:i:s')) throw new InvalidArgumentException('Expired observation');
        $plain=self::json($v);
        $lookup=$this->lookup($expectedAsin);
        $observation=$this->index('observation|'.$plain);
        $find=$this->db->prepare('SELECT id FROM hw24_amazon_price_history_secure WHERE observation_key=?');
        $find->execute([$observation]);
        if(($old=$find->fetchColumn())!==false) return ['id'=>(int)$old,'inserted'=>false];
        $cipher=$this->seal($plain,self::aad($lookup,$observation,$observed,$expires));
        try {
            $q=$this->db->prepare('INSERT INTO hw24_amazon_price_history_secure
                (lookup_key,observation_key,observed_at_utc,expires_at_utc,key_version,ciphertext) VALUES (?,?,?,?,1,?)');
            $q->execute([$lookup,$observation,$observed,$expires,$cipher]);
            return ['id'=>(int)$this->db->lastInsertId(),'inserted'=>true];
        } catch(PDOException $e) {
            if((string)$e->getCode()==='23000'){
                $find->execute([$observation]);
                if(($old=$find->fetchColumn())!==false) return ['id'=>(int)$old,'inserted'=>false];
            }
            throw $e;
        }
    }
    public function latest(string $asin,int $limit=2): array
    {
        $lookup=$this->lookup($asin);
        if($limit<1||$limit>100) throw new InvalidArgumentException('Invalid history limit');
        $q=$this->db->prepare('SELECT id,lookup_key,observation_key,observed_at_utc,expires_at_utc,key_version,ciphertext
            FROM hw24_amazon_price_history_secure WHERE lookup_key=? AND expires_at_utc>?
            ORDER BY observed_at_utc DESC,id DESC LIMIT '.(int)$limit);
        $q->execute([$lookup,gmdate('Y-m-d H:i:s')]);
        $result=[];
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){
            if((int)$r['key_version']!==1) throw new RuntimeException('Unsupported key version');
            $v=$this->unseal($r['ciphertext'],self::aad($r['lookup_key'],$r['observation_key'],$r['observed_at_utc'],$r['expires_at_utc']));
            if(($v['asin']??null)!==$asin||($v['observed_at']??null)!==$r['observed_at_utc']||
                !hash_equals($r['observation_key'],$this->index('observation|'.self::json($v))))
                throw new RuntimeException('Archive identity mismatch');
            $result[]=['id'=>(int)$r['id'],'observed_at_utc'=>$r['observed_at_utc'],
                'total_offer_count'=>$v['total_count'],'returned_offer_count'=>$v['returned_count'],
                'buybox'=>$v['buybox'],'lowest'=>$v['lowest'],'offers'=>$v['offers']];
        }
        return $result;
    }
    public function purgeExpired(): int
    {
        $q=$this->db->prepare('DELETE FROM hw24_amazon_price_history_secure WHERE expires_at_utc<=?');
        $q->execute([gmdate('Y-m-d H:i:s')]);
        return $q->rowCount();
    }
}
