<?php
declare(strict_types=1);
/*
 * HW24 · Read-only JTL API readiness check.
 * Returns safe, operational status codes only. Never returns passwords,
 * tokens, SQL server addresses, private file paths or user data.
 * This route must remain behind the Plesk HTTP Basic protection.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function jtlStatusReply(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    jtlStatusReply(405,['ok'=>false,'error'=>'method_not_allowed']);
}
$https=($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string)$_SERVER['HTTPS']) !== 'off';
if (!$https) jtlStatusReply(403,['ok'=>false,'error'=>'https_required']);

$result=[
    'ok'=>true,
    'service'=>'hw24-jtl',
    'api_version'=>'v1',
    'php_ok'=>PHP_VERSION_ID>=80400,
    'pdo_mysql'=>extension_loaded('pdo_mysql'),
    'read_config'=>'missing',
    'sync_config'=>'missing',
    'mirror_tables'=>'unknown',
    'mirror_ready'=>false,
    'latest_snapshot_utc'=>null,
    'age_minutes'=>null,
    'latest_rows'=>null,
    'next'=>'setup_db'
];
$folder=dirname(__DIR__,2).'/hw24_private';
$result['read_config']=is_file($folder.'/db.php')?'present':'missing';
$result['sync_config']=is_file($folder.'/jtl_sync.php')?'present':'missing';
if (!$result['php_ok']) {$result['next']='upgrade_php';jtlStatusReply(200,$result);}
if (!$result['pdo_mysql']) {$result['next']='enable_pdo_mysql';jtlStatusReply(200,$result);}
if ($result['read_config']!=='present') {$result['next']='configure_read_api';jtlStatusReply(200,$result);}
try {
    $cfg=require $folder.'/db.php';
    if (!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg)) {
        $result['read_config']='invalid';$result['next']='configure_read_api';
        jtlStatusReply(200,$result);
    }
    $pdo=new PDO(
      'mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
      (string)$cfg['user'],(string)$cfg['password'],
      [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
       PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]
    );
    $q=$pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE()
          AND TABLE_NAME IN ('hw24_jtl_articles','hw24_jtl_sync_meta')");
    $tables=$q->fetchAll(PDO::FETCH_COLUMN);
    $result['mirror_tables']=count($tables)===2?'present':'missing';
    if (count($tables)!==2) {$result['next']='install_mirror_tables';jtlStatusReply(200,$result);}
    $q=$pdo->query("SELECT last_completed_utc,last_row_count,
       TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
       FROM hw24_jtl_sync_meta
       WHERE status='ready' AND last_completed_utc IS NOT NULL
       ORDER BY last_completed_utc DESC LIMIT 1");
    $snapshot=$q->fetch();
    if (!$snapshot) {$result['next']='configure_and_run_windows_sync';jtlStatusReply(200,$result);}
    $result['latest_snapshot_utc']=$snapshot['last_completed_utc'];
    $result['latest_rows']=(int)$snapshot['last_row_count'];
    $result['age_minutes']=(int)$snapshot['age_minutes'];
    $result['mirror_ready']=($result['age_minutes']>=0 && $result['age_minutes']<=30);
    $result['next']=$result['mirror_ready']?'query_known_ean':'rerun_windows_sync';
    jtlStatusReply(200,$result);
} catch (Throwable $e) {
    error_log('HW24 JTL status check: '.$e->getMessage());
    $result['mirror_tables']='unavailable';
    $result['next']='fix_mariadb_access';
    jtlStatusReply(200,$result);
}
