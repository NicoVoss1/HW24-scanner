<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$name=(string)getenv('HW24_SMOKE_DB');
if(!preg_match('/^hw24_price_smoke_[0-9a-f]{16}$/D',$name))throw new RuntimeException('Temporary DB only');
foreach(['PriceArchiveStore','AmazonPriceArchive','EncryptedAmazonPriceArchive'] as $class)
    require __DIR__.'/../app/Services/'.$class.'.php';
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$name.';charset=utf8mb4','root','',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
function check(bool $pass,string $label):void{
    if(!$pass)throw new RuntimeException($label);
    echo 'PASS: '.$label.PHP_EOL;
}
check($db->query('SELECT DATABASE()')->fetchColumn()===$name,'isolated database');
check($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)===['hw24_amazon_price_history_secure'],'secure table only');
$key=random_bytes(32);
$a=new App\Services\EncryptedAmazonPriceArchive($db,$key,7);
function sample(int $age,float $price):array{
    return ['ok'=>true,'source'=>'amazon_live','marketplace_id'=>'A1PA6795UKMFR9',
        'asin'=>'B000000001','retrieved_at_utc'=>gmdate('c',time()-$age),'total_offer_count'=>1,
        'buybox_prices'=>[],'lowest_prices'=>[],
        'offers'=>[['seller_id'=>'TESTSELLER1','item_price'=>['amount'=>$price,'currency'=>'EUR'],
            'shipping'=>['amount'=>1,'currency'=>'EUR']]]];
}
$first=$a->record(sample(120,8.25),'B000000001');
$sample=sample(60,7.25);
$last=$a->record($sample,'B000000001');
check($first['inserted']&&$last['inserted'],'two encrypted observations');
check($a->record($sample,'B000000001')===['id'=>$last['id'],'inserted'=>false],'replay deduplicated');
$rows=$a->latest('B000000001');
check(count($rows)===2&&$rows[0]['id']===$last['id']&&$rows[1]['id']===$first['id'],'old/new sequence');
check($rows[0]['offers'][0]['total']['amount']==='8.2500','authenticated decryption and price');
$raw=$db->query('SELECT * FROM hw24_amazon_price_history_secure ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$dump=json_encode($raw);
check(!str_contains($dump,'B000000001')&&!str_contains($dump,'TESTSELLER1')&&!str_contains($dump,'8.2500'),'no ASIN seller or price in plaintext');
check($a->latest('B000000002')===[],'ASIN isolation');
$wrong=new App\Services\EncryptedAmazonPriceArchive($db,random_bytes(32),7);
check($wrong->latest('B000000001')===[],'wrong key cannot find observation');
$blob=base64_decode($raw[1]['ciphertext'],true);
$blob[30]=chr(ord($blob[30])^1);
$q=$db->prepare('UPDATE hw24_amazon_price_history_secure SET ciphertext=? WHERE id=?');
$q->execute([base64_encode($blob),$last['id']]);
$rejected=false;
try{$a->latest('B000000001');}catch(RuntimeException){$rejected=true;}
check($rejected,'tampered ciphertext rejected');
$q->execute([$raw[1]['ciphertext'],$last['id']]);
check(count($a->latest('B000000001'))===2,'restored synthetic observation decrypts');
$q=$db->prepare('UPDATE hw24_amazon_price_history_secure SET expires_at_utc=? WHERE id=?');
$q->execute(['2000-01-01 00:00:00',$first['id']]);
check(count($a->latest('B000000001'))===1&&$a->purgeExpired()===1,'expiry filtering and purge');
echo "HW24 SECURE PRICE ARCHIVE SMOKE PASS\n";
