<?php
declare(strict_types=1);

/**
 * HW24 Amazon catalog snapshot history, read-only.
 * Mirrors existing api/amazon.php authorization and uses the existing
 * private restricted DB credential file. No new Amazon API request,
 * no marketplace writes, no full response_json exposure.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function archiveReply(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
    exit;
}
if (($_SERVER['REQUEST_METHOD']??'')!=='GET') {
    header('Allow: GET');archiveReply(405,['ok'=>false,'error'=>'method_not_allowed']);
}
$actor=trim((string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??''));
if ($actor==='' || strlen($actor)>180) archiveReply(403,['ok'=>false,'error'=>'authentication_required']);
if (($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
    archiveReply(403,['ok'=>false,'error'=>'https_required']);
$ean=trim((string)($_GET['ean']??''));
if (!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean) ||
    preg_match('/^0+$/D',$ean)) archiveReply(400,['ok'=>false,'error'=>'invalid_ean']);
$weight=3;$sum=0;
for ($i=strlen($ean)-2;$i>=0;$i--) {
    $sum+=(int)$ean[$i]*$weight;
    $weight=$weight===3?1:3;
}
if ((10-$sum%10)%10!==(int)$ean[strlen($ean)-1])
    archiveReply(400,['ok'=>false,'error'=>'invalid_ean_checksum']);
$limit=filter_var($_GET['limit']??10,FILTER_VALIDATE_INT);
if (!$limit || $limit>20) $limit=10;
$cfgPath=dirname(__DIR__,2).'/hw24_private/db.php';
if (!is_file($cfgPath)) archiveReply(503,['ok'=>false,'error'=>'database_not_configured']);
try {
    $cfg=require $cfgPath;
    if (!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg))
        throw new RuntimeException('Invalid archive credential config');
    $db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
        (string)$cfg['user'],(string)$cfg['password'],[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_TIMEOUT=>5,
        ]);
    // Snapshot rows are catalog search evidence only, not per-actor seller offers.
    // The existing snapshots table does not have an actor column.
    $query=$db->prepare(
        'SELECT id, marketplace_id, searched_ean, fetched_at_utc, response_status, item_count
         FROM hw24_amazon_catalog_snapshots
         WHERE marketplace_id=? AND searched_ean=? AND response_status=200
         ORDER BY id DESC LIMIT '.(int)$limit
    );
    $query->execute(['A1PA6795UKMFR9',$ean]);
    $snapshots=[];
    foreach ($query->fetchAll() as $row) {
        $snapshots[]=[
            'id'=>(int)$row['id'],
            'marketplace_id'=>'A1PA6795UKMFR9',
            'ean'=>$ean,
            'fetched_at_utc'=>(string)$row['fetched_at_utc'],
            'count'=>(int)$row['item_count'],
        ];
    }
    archiveReply(200,[
        'ok'=>true,'source'=>'hw24_amazon_catalog_snapshots',
        'scope'=>'catalog_ean_not_seller_account',
        'ean'=>$ean,'count'=>count($snapshots),'snapshots'=>$snapshots,
        'note'=>'Gespeicherte Katalog-Suchnachweise, keine eigenen Amazon-Angebote.',
    ]);
} catch (\Throwable $e) {
    error_log('HW24 Amazon archive read unavailable: '.get_class($e));
    archiveReply(503,['ok'=>false,'error'=>'amazon_archive_unavailable']);
}
