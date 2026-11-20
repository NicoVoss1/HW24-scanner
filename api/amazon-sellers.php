<?php
declare(strict_types=1);
/* Verified seller display names for Amazon.de. No scraping or inferred identities. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function replyNames(int $status,array $body): void {
 http_response_code($status);
 echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PARTIAL_OUTPUT_ON_ERROR);
 exit;
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,['GET','POST'],true)){header('Allow: GET, POST');replyNames(405,['ok'=>false,'error'=>'method_not_allowed']);}
$user=(string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??'');
if($user==='')replyNames(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on' && ($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')replyNames(403,['ok'=>false,'error'=>'https_required']);
$data=null;
if($method==='POST'){
 $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
 $originHost=strtolower((string)parse_url($origin,PHP_URL_HOST));
 $host=strtolower(explode(':',(string)($_SERVER['HTTP_HOST']??''))[0]);
 if($originHost===''||$originHost!==$host||parse_url($origin,PHP_URL_SCHEME)!=='https')
  replyNames(403,['ok'=>false,'error'=>'cross_origin_denied']);
 if(($_SERVER['HTTP_X_HW24_APPROVAL']??'')!=='confirmed')
  replyNames(403,['ok'=>false,'error'=>'approval_required']);
 if(!str_starts_with(strtolower($_SERVER['CONTENT_TYPE']??''),'application/json'))
  replyNames(415,['ok'=>false,'error'=>'json_required']);
 if((int)($_SERVER['CONTENT_LENGTH']??0)>4096)replyNames(413,['ok'=>false,'error'=>'payload_too_large']);
 try{$data=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);}
 catch(Throwable $e){replyNames(400,['ok'=>false,'error'=>'invalid_json']);}
 if(!is_array($data)||($data['confirmed']??false)!==true)
  replyNames(400,['ok'=>false,'error'=>'approval_required']);
}
$marketplace='A1PA6795UKMFR9';
$ids=[];
if($method==='GET'){
 $raw=(string)($_GET['ids']??'');
 $ids=array_values(array_unique(array_filter(array_map('trim',explode(',',$raw)))));
 if(count($ids)>20)replyNames(400,['ok'=>false,'error'=>'too_many_ids']);
 foreach($ids as $id)if(!preg_match('/^[A-Z0-9]{5,40}$/D',$id))replyNames(400,['ok'=>false,'error'=>'invalid_seller_id']);
}else{
 $id=trim((string)($data['seller_id']??''));
 $display=trim((string)($data['display_name']??''));
 $url=trim((string)($data['source_url']??''));
 if(!preg_match('/^[A-Z0-9]{5,40}$/D',$id)||$display===''||strlen($display)>900)
  replyNames(400,['ok'=>false,'error'=>'invalid_seller_name']);
 if(!preg_match('~^https://(?:www\.)?amazon\.de/(?:sp|gp/help/seller/at-a-glance\.html)\?~i',$url)||strlen($url)>512)
  replyNames(400,['ok'=>false,'error'=>'invalid_source_url']);
 parse_str((string)parse_url($url,PHP_URL_QUERY),$query);
 if((string)($query['seller']??'')!==$id)
  replyNames(400,['ok'=>false,'error'=>'source_does_not_match_seller']);
}
$config=dirname(__DIR__,2).'/hw24_private/db.php';
if(!is_file($config))replyNames(503,['ok'=>false,'error'=>'database_not_configured']);
try{
 $cfg=require $config;
 if(!is_array($cfg)||empty($cfg['user'])||!isset($cfg['password']))throw new RuntimeException('db_config_invalid');
 $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
 $cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
 if($method==='GET'){
  if(!$ids)replyNames(200,['ok'=>true,'names'=>new stdClass()]);
  $placeholders=implode(',',array_fill(0,count($ids),'?'));
  $q=$pdo->prepare('SELECT seller_id,display_name FROM hw24_amazon_seller_names WHERE marketplace_id=? AND seller_id IN ('.$placeholders.')');
  $q->execute(array_merge([$marketplace],$ids));
  $out=[];foreach($q->fetchAll() as $r)$out[$r['seller_id']]=$r['display_name'];
  replyNames(200,['ok'=>true,'names'=>$out?:new stdClass()]);
 }
 $q=$pdo->prepare('INSERT INTO hw24_amazon_seller_names(marketplace_id,seller_id,display_name,source_url,verified_by,verified_at_utc) VALUES (?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE display_name=VALUES(display_name),source_url=VALUES(source_url),verified_by=VALUES(verified_by),verified_at_utc=UTC_TIMESTAMP()');
 $q->execute([$marketplace,$id,$display,$url,substr($user,0,180)]);
 replyNames(200,['ok'=>true,'seller_id'=>$id,'display_name'=>$display,'verified'=>true]);
}catch(Throwable $e){
 error_log('HW24 seller names API ['.get_class($e).']: '.$e->getMessage());
 replyNames(503,['ok'=>false,'error'=>'seller_names_unavailable']);
}
