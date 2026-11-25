<?php
declare(strict_types=1);

/** CLI only, synthetic data exclusively. Never use against HW24 production. */
if (PHP_SAPI!=='cli') exit(1);
$name=(string)getenv('HW24_SMOKE_DB');
if (!preg_match('/^hw24_price_smoke_[0-9a-f]{16}$/D',$name)) {
    throw new RuntimeException('Temporary smoke DB only');
}
require __DIR__.'/../app/Services/AmazonPriceArchive.php';
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$name.';charset=utf8mb4','root','',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES=>false,
]);
if ($db->query('SELECT DATABASE()')->fetchColumn()!==$name) throw new RuntimeException('Wrong database');
function check(bool $pass,string $message): void {
    if (!$pass) throw new RuntimeException($message);
    echo 'PASS: '.$message.PHP_EOL;
}
$tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
sort($tables);
check($tables===['hw24_amazon_price_offers','hw24_amazon_price_snapshots'],'isolated schema only');
$archive=new App\Services\AmazonPriceArchive($db,1);
function sample(int $age,string $amount): array {
    $p=['amount'=>$amount,'currency'=>'EUR'];
    return ['ok'=>true,'source'=>'amazon_live','marketplace_id'=>'A1PA6795UKMFR9',
        'asin'=>'B000000001','retrieved_at_utc'=>gmdate('c',time()-$age),
        'total_offer_count'=>25,'buybox_prices'=>[['landed_price'=>$p]],'lowest_prices'=>[],
        'offers'=>[['seller_id'=>'TESTSELLER1','item_price'=>$p,'shipping'=>['amount'=>1,'currency'=>'EUR'],
            'is_fba'=>false],['seller_id'=>'TESTSELLER2','item_price'=>$p,'shipping'=>null],
            ['seller_id'=>'TESTSELLER3','item_price'=>$p,'shipping'=>['amount'=>1,'currency'=>'GBP']]]];
}
$old=sample(120,'8.25');
$new=sample(60,'7.25');
$a=$archive->record($old,'B000000001');
$b=$archive->record($new,'B000000001');
check($a['inserted']&&$b['inserted']&&$a['id']!==$b['id'],'two observations persisted');
check($archive->record($old,'B000000001')===['id'=>$a['id'],'inserted'=>false],'replay deduplicated');
$rows=$archive->latest('B000000001');
check(count($rows)===2&&(int)$rows[0]['id']===$b['id']&&(int)$rows[1]['id']===$a['id'],'chronological old/new');
check((string)$rows[0]['offers'][0]['total_price']==='8.2500'&&(string)$rows[1]['offers'][0]['total_price']==='9.2500','price plus shipping');
check($rows[0]['offers'][1]['total_price']===null&&$rows[0]['offers'][2]['total_price']===null,'no guessed shipping or currency');
check((int)$rows[0]['total_offer_count']===25&&(int)$rows[0]['returned_offer_count']===3,'total vs returned offers');
check($archive->latest('B000000002')===[],'ASIN isolation');
foreach (['cache','invalid_source'] as $src) {
    $bad=$new;$bad['source']=$src;$rejected=false;
    try { $archive->record($bad,'B000000001'); } catch (InvalidArgumentException) { $rejected=true; }
    check($rejected,'reject '.$src);
}
$before=(int)$db->query('SELECT COUNT(*) FROM hw24_amazon_price_snapshots')->fetchColumn();
$db->exec("CREATE TRIGGER smoke_fail_offer BEFORE INSERT ON hw24_amazon_price_offers FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic failure'");
$rejected=false;
try { $archive->record(sample(30,'5.25'),'B000000001'); } catch (PDOException) { $rejected=true; }
$db->exec('DROP TRIGGER smoke_fail_offer');
check($rejected&&(int)$db->query('SELECT COUNT(*) FROM hw24_amazon_price_snapshots')->fetchColumn()===$before,'atomic rollback on offer insert error');
$q=$db->prepare('UPDATE hw24_amazon_price_snapshots SET expires_at_utc=? WHERE id=?');
$q->execute(['2000-01-01 00:00:00',$a['id']]);
check(count($archive->latest('B000000001'))===1,'expired rows invisible');
check($archive->purgeExpired()===1&&(int)$db->query('SELECT COUNT(*) FROM hw24_amazon_price_offers')->fetchColumn()===3,'expiry and foreign-key cascade');
echo "HW24 MARIADB PRICE ARCHIVE SMOKE PASS\n";
