<?php
declare(strict_types=1);
/**
 * HW24 Amazon Catalog lookup (read only).
 * Requirements: private amazon_spapi.php, private db.php, SQL schema.
 * Protect this API using the same server-side HTTP authentication as the HW24 site.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function reply(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    reply(405, ['ok'=>false,'error'=>'method_not_allowed']);
}
// Fail closed if HTTP authentication is not forwarded by Plesk.
$authUser = $_SERVER['PHP_AUTH_USER'] ?? $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? '';
if ($authUser === '') reply(403, ['ok'=>false,'error'=>'site_authentication_required']);
if (($_SERVER['HTTPS'] ?? '') !== 'on' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
    reply(403, ['ok'=>false,'error'=>'https_required']);
}
$ean = trim((string)($_GET['ean'] ?? ''));
if (!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D', $ean)) {
    reply(400, ['ok'=>false,'error'=>'invalid_ean_format']);
}
function gtinValid(string $value): bool {
    if (preg_match('/^0+$/D', $value)) return false;
    $sum = 0; $weight = 3;
    for ($i=strlen($value)-2; $i>=0; $i--, $weight=$weight===3?1:3) $sum += (int)$value[$i]*$weight;
    return ((10-$sum%10)%10)===(int)$value[strlen($value)-1];
}
if (!gtinValid($ean)) reply(400, ['ok'=>false,'error'=>'invalid_ean_checksum']);
$root=dirname(__DIR__,2).'/hw24_private/';
$configFile=$root.'amazon/amazon_spapi.php';
$dbFile=$root.'db.php';
if (!is_file($configFile)||!is_file($dbFile)) reply(503,['ok'=>false,'error'=>'private_config_missing']);

$stage='config_load';
try {
    $settings=require $configFile;
    $db=require $dbFile;
    if (!is_array($settings) || !is_array($db)) throw new RuntimeException('configuration_invalid');
    foreach (['lwa_client_id','lwa_client_secret','refresh_token'] as $name) {
        if (empty($settings[$name])) throw new RuntimeException('amazon_credentials_missing');
    }
    if (empty($db['user']) || !array_key_exists('password',$db)) throw new RuntimeException('db_credentials_missing');
    $stage='database_connect';
    $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
        $db['user'],$db['password'],[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_TIMEOUT=>5
        ]);
    $stage='schema_check';
    $pdo->query('SELECT id FROM hw24_amazon_catalog_snapshots LIMIT 0');
    $marketplace='A1PA6795UKMFR9'; // Amazon.de only: prevent arbitrary endpoints/regions.
    $endpoint='https://sellingpartnerapi-eu.amazon.com';
    $freshMinutes=360; // Respect Amazon API traffic and keep an audit of EVERY user lookup.
    $stage='cache_read';
    $find=$pdo->prepare('SELECT id, fetched_at_utc, response_json, item_count
         FROM hw24_amazon_catalog_snapshots
         WHERE marketplace_id=? AND searched_ean=? AND response_status=200
           AND fetched_at_utc >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
         ORDER BY id DESC LIMIT 1');
    $find->execute([$marketplace,$ean,$freshMinutes]);
    $cached=$find->fetch();
    if ($cached) {
        $data=json_decode((string)$cached['response_json'],true);
        if (is_array($data)) {
            $stage='cache_log';
            $log=$pdo->prepare('INSERT INTO hw24_amazon_lookup_log(marketplace_id,searched_ean,outcome,http_status,snapshot_id) VALUES (?,?,?,?,?)');
            $log->execute([$marketplace,$ean,'cache',200,$cached['id']]);
            reply(200, ['ok'=>true,'source'=>'cache','snapshot_id'=>(int)$cached['id'],'fetched_at_utc'=>$cached['fetched_at_utc'],
                'count'=>(int)$cached['item_count'],'items'=>summaries($data['items']??[]),'has_next_page'=>!empty($data['pagination']['nextToken'])]);
        }
    }
    $stage='lwa_token';
    if (!function_exists('curl_init')) throw new RuntimeException('curl_unavailable');
    $ch=curl_init('https://api.amazon.com/auth/o2/token');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query([
        'grant_type'=>'refresh_token',
        'refresh_token'=>$settings['refresh_token'],
        'client_id'=>$settings['lwa_client_id'],
        'client_secret'=>$settings['lwa_client_secret']
    ]),CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false]);
    $tokenText=curl_exec($ch);$authCode=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    $token=is_string($tokenText)?json_decode($tokenText,true):null;
    if ($authCode!==200 || empty($token['access_token'])) throw new RuntimeException('lwa_token_exchange_failed');
    $stage='catalog_request';
    $query=http_build_query([
        'marketplaceIds'=>$marketplace,'identifiers'=>$ean,'identifiersType'=>'EAN',
        'includedData'=>'attributes,classifications,dimensions,identifiers,images,productTypes,relationships,salesRanks,summaries'
    ],'', '&',PHP_QUERY_RFC3986);
    $ch=curl_init($endpoint.'/catalog/2022-04-01/items?'.$query);
    curl_setopt_array($ch,[
        CURLOPT_HTTPHEADER=>['x-amz-access-token: '.$token['access_token'],'Accept: application/json','User-Agent: HW24-Scanner/1.0 (Language=PHP)'],
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>35,CURLOPT_FOLLOWLOCATION=>false
    ]);
    $raw=curl_exec($ch);$httpStatus=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($raw) || $httpStatus!==200) {
        $log=$pdo->prepare('INSERT INTO hw24_amazon_lookup_log(marketplace_id,searched_ean,outcome,http_status) VALUES (?,?,?,?)');
        $log->execute([$marketplace,$ean,'amazon_error',$httpStatus]);
        reply(502,['ok'=>false,'error'=>'amazon_catalog_request_failed','upstream_status'=>$httpStatus]);
    }
    if (strlen($raw)>8*1024*1024) throw new RuntimeException('catalog_payload_too_large');
    $stage='catalog_decode';
    $response=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
    if (!is_array($response) || !isset($response['items']) || !is_array($response['items'])) throw new RuntimeException('amazon_catalog_response_invalid');
    $items=$response['items'];
    $stage='database_transaction';
    $pdo->beginTransaction();
    $stage='archive_snapshot';
    $q=$pdo->prepare('INSERT INTO hw24_amazon_catalog_snapshots(marketplace_id,searched_ean,fetched_at_utc,response_status,item_count,response_json)
        VALUES (?,?,UTC_TIMESTAMP(),?,?,?)');
    $q->execute([$marketplace,$ean,$httpStatus,count($items),$raw]);
    $snapshot=(int)$pdo->lastInsertId();
    $fetchedStamp=$pdo->prepare('SELECT fetched_at_utc FROM hw24_amazon_catalog_snapshots WHERE id=?');
    $fetchedStamp->execute([$snapshot]);
    $fetchedAt=(string)$fetchedStamp->fetchColumn();
    $stage='archive_items';
    $insert=$pdo->prepare('INSERT INTO hw24_amazon_catalog_items
       (snapshot_id,marketplace_id,searched_ean,asin,item_name,brand,manufacturer,item_json) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($items as $item) {
        if (!is_array($item) || empty($item['asin'])) continue;
        $summary=$item['summaries'][0]??[];
        $insert->execute([$snapshot,$marketplace,$ean,substr((string)$item['asin'],0,20),
            substr((string)($summary['itemName']??''),0,1000),
            substr((string)($summary['brand']??''),0,255),
            substr((string)($summary['manufacturer']??''),0,255),
            json_encode($item,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)]);
    }
    $stage='archive_log';
    $log=$pdo->prepare('INSERT INTO hw24_amazon_lookup_log(marketplace_id,searched_ean,outcome,http_status,snapshot_id) VALUES (?,?,?,?,?)');
    $log->execute([$marketplace,$ean,count($items)?'live':'not_found',200,$snapshot]);
    $stage='archive_commit';
    $pdo->commit();
    reply(200,['ok'=>true,'source'=>'live','snapshot_id'=>$snapshot,'fetched_at_utc'=>$fetchedAt,'count'=>count($items),'items'=>summaries($items),
       'has_next_page'=>!empty($response['pagination']['nextToken'])]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('HW24 Amazon catalog API stage='.$stage.' ['.get_class($e).']: '.$e->getMessage());
    reply(503,['ok'=>false,'error'=>'amazon_lookup_or_storage_unavailable','stage'=>$stage]);
}
function summaries(array $items): array {
    $out=[];
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $summary=$item['summaries'][0]??[];
        $attrs=$item['attributes']??[];
        $out[]=['asin'=>(string)($item['asin']??''),'title'=>(string)($summary['itemName']??''),
            'brand'=>(string)($summary['brand']??''),'manufacturer'=>(string)($summary['manufacturer']??''),
            'attributes'=>$attrs,'identifiers'=>$item['identifiers']??[],
            'dimensions'=>$item['dimensions']??[]];
    }
    return $out;
}
