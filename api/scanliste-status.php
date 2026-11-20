<?php
declare(strict_types=1);
/**
 * HW24 V4.22 · read-only setup diagnosis for new scanliste tables/config.
 * No DDL, writes, credentials or absolute server paths returned.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
function answer(int $http,array $payload): never {
  http_response_code($http);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
  exit;
}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') answer(405,['ok'=>false,'error'=>'method_not_allowed']);
if(($_SERVER['HTTPS']??'')!=='on' && ($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
  answer(403,['ok'=>false,'error'=>'https_required']);
if(trim((string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??''))==='') 
  answer(403,['ok'=>false,'error'=>'authentication_required']);
$root=dirname(__DIR__,2).'/hw24_private/';
$source=$root.'db.php';
$scan=$root.'scanlist-db.php';
$readConfigured=is_file($source);
$scanConfigured=is_file($scan);
if(!$readConfigured)answer(200,['ok'=>true,'status'=>'read_config_unavailable',
  'read_config'=>false,'scan_config'=>$scanConfigured,'schema_status'=>'unverified',
  'next_step'=>'read_config_check']);
try{
  $db=require $source;
  if(!is_array($db)||empty($db['user'])||!array_key_exists('password',$db))
    answer(200,['ok'=>true,'status'=>'read_config_invalid','schema_status'=>'unverified',
      'read_config'=>true,'scan_config'=>$scanConfigured,'next_step'=>'read_config_check']);
  $dsn='mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4';
  $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5];
  $pdo=new PDO($dsn,$db['user'],$db['password'],$options);
  $required=[
   'hw24_scan_batches','hw24_scan_items','hw24_scan_events',
   'hw24_scan_draft_audit','hw24_amazon_keyword_cache'];
  $stmt=$pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (
     'hw24_scan_batches','hw24_scan_items','hw24_scan_events',
     'hw24_scan_draft_audit','hw24_amazon_keyword_cache')");
  $present=array_column($stmt->fetchAll(),'TABLE_NAME');
  $tables=[];
  foreach($required as $name)$tables[$name]=in_array($name,$present,true);
  $v11=$tables['hw24_scan_batches']&&$tables['hw24_scan_items']&&
       $tables['hw24_scan_events']&&$tables['hw24_scan_draft_audit'];
  $v12=$tables['hw24_amazon_keyword_cache'];
  $connection='missing';
  if($scanConfigured){
    try {
      $cfg=require $scan;
      if(!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg))
        $connection='invalid';
      else{
        $test=new PDO($dsn,$cfg['user'],$cfg['password'],$options);
        $test->query('SELECT 1')->fetchColumn();
        $connection='connected';
      }
    } catch(Throwable $e) {
      error_log('HW24 scanlist status private DB account check: '.$e->getMessage());
      $connection='unavailable';
    }
  }
  $next=(!$v11?'install_scan_tables':(!$v12?'install_keyword_cache':
    ($connection!=='connected'?'configure_scan_user':'test_scanning')));
  answer(200,[
    'ok'=>true,'status'=>$next==='test_scanning'?'ready_for_function_test':'setup_pending',
    'read_config'=>true,'scan_config'=>$scanConfigured,
    'scan_connection'=>$connection,'schema_status'=>'checked',
    'v11_ready'=>$v11,'v12_ready'=>$v12,'tables'=>$tables,'next_step'=>$next
  ]);
}catch(Throwable $e){
  error_log('HW24 scanlist read-only diagnosis: '.$e->getMessage());
  answer(503,['ok'=>false,'error'=>'status_check_unavailable','schema_status'=>'unverified']);
}
