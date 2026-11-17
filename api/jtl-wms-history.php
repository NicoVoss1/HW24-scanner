<?php
declare(strict_types=1);
/* Independent WMS history mirror. Protected by existing Plesk HTTP auth.
   POST requires existing sync token; GET uses read-only DB config.
   No direct JTL writes; no arbitrary SQL or free-text comments. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function result(int $status,array $data):void{
 http_response_code($status);
 echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
 exit;
}
function posInt($v,bool $zero=false):int{
 $n=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>$zero?0:1,'max_range'=>2147483647]]);
 if($n===false)throw new InvalidArgumentException('bad_integer');
 return $n;
}
function runId($v):string{
 if(!is_string($v)||!preg_match('/^[0-9a-f]{32}$/D',$v))throw new InvalidArgumentException('bad_run');
 return $v;
}
function field($v,int $max):?string{
 if($v===null||$v==='')return null;
 if(!is_scalar($v)||strlen((string)$v)>$max)throw new InvalidArgumentException('bad_field');
 return (string)$v;
}
function connection(array $cfg):PDO{
 return new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
  (string)$cfg['user'],(string)$cfg['password'],[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>10
  ]);
}
if(empty($_SERVER['HTTPS'])||strtolower((string)$_SERVER['HTTPS'])==='off')result(403,['ok'=>false,'error'=>'https_required']);
$method=$_SERVER['REQUEST_METHOD']??'';
if(!in_array($method,['GET','POST'],true))result(405,['ok'=>false,'error'=>'method']);
try{
 $configPath=dirname(__DIR__,2).'/hw24_private/'.($method==='POST'?'jtl_sync.php':'db.php');
 if(!is_file($configPath))result(503,['ok'=>false,'error'=>'not_configured']);
 $cfg=require $configPath;
 if(!is_array($cfg)||empty($cfg['user'])||!isset($cfg['password']))result(503,['ok'=>false,'error'=>'not_configured']);
 if($method==='POST'){
  $token=(string)($_SERVER['HTTP_X_HW24_SYNC_TOKEN']??'');
  if(strlen($token)<32||empty($cfg['token_sha256'])||!hash_equals(strtolower((string)$cfg['token_sha256']),hash('sha256',$token)))result(401,['ok'=>false,'error'=>'authentication_failed']);
 }
 $pdo=connection($cfg);
 if($method==='GET'){
  $aid=posInt($_GET['article_id']??null);
  $offset=posInt($_GET['offset']??0,true);
  $limit=50;
  // Browser pins the same completed run when advancing pages.
  if(isset($_GET['run_id'])){
   $run=runId($_GET['run_id']);
   $q=$pdo->prepare("SELECT run_id,completed_utc FROM hw24_wms_history_runs WHERE status='ready' AND run_id=?");
   $q->execute([$run]);
  }else{
   $q=$pdo->query("SELECT run_id,completed_utc FROM hw24_wms_history_runs WHERE status='ready' ORDER BY completed_utc DESC,run_id DESC LIMIT 1");
  }
  $meta=$q->fetch();
  if(!$meta)result(200,['ok'=>true,'status'=>'not_synchronized','rows'=>[]]);
  $q=$pdo->prepare('SELECT COUNT(*) FROM hw24_wms_history WHERE run_id=? AND article_id=?');
  $q->execute([$meta['run_id'],$aid]);$total=(int)$q->fetchColumn();
  $q=$pdo->prepare('SELECT movement_id,moved_at AS zeitstempel,type_id AS buchungsart_id,type_name AS buchungsart,qty AS menge,
                  from_id AS start_id,from_name AS lagerplatz_von,to_id AS ziel_id,to_name AS lagerplatz_nach
                  FROM hw24_wms_history WHERE run_id=? AND article_id=?
                  ORDER BY moved_at DESC,movement_id DESC LIMIT '.$limit.' OFFSET '.$offset);
  $q->execute([$meta['run_id'],$aid]);$rows=$q->fetchAll();
  result(200,['ok'=>true,'status'=>'ready','run_id'=>$meta['run_id'],'snapshot_at'=>$meta['completed_utc'],
               'total'=>$total,'offset'=>$offset,'rows'=>$rows,'has_more'=>($offset+count($rows)<$total),
               'note'=>'Vollstaendige verknuepfbare JTL-Quellhistorie zum Synchronisationsstand; kein Datumsfilter.']);
 }
 $body=file_get_contents('php://input',false,null,0,1500001);
 if($body===false||strlen($body)>1500000)result(413,['ok'=>false,'error'=>'payload']);
 $in=json_decode($body,true,512,JSON_THROW_ON_ERROR);
 $action=$in['action']??'';
 if($action==='probe'){
  $pdo->query('SELECT run_id FROM hw24_wms_history_runs LIMIT 0');
  $pdo->query('SELECT movement_id FROM hw24_wms_history LIMIT 0');
  result(200,['ok'=>true,'status'=>'receiver_ready']);
 }
 $run=runId($in['run_id']??null);
 if($action==='begin'){
  $q=$pdo->prepare("INSERT INTO hw24_wms_history_runs (run_id,status,created_utc) VALUES (?,'staging',UTC_TIMESTAMP(6))");
  $q->execute([$run]);
  result(200,['ok'=>true,'status'=>'staging']);
 }
 $pdo->beginTransaction();
 $q=$pdo->prepare('SELECT status FROM hw24_wms_history_runs WHERE run_id=? FOR UPDATE');
 $q->execute([$run]);
 if($q->fetchColumn()!=='staging')throw new InvalidArgumentException('not_staging');
 if($action==='batch'){
  $rows=$in['rows']??null;
  if(!is_array($rows)||count($rows)<1||count($rows)>500)throw new InvalidArgumentException('batch_size');
  $q=$pdo->prepare('INSERT INTO hw24_wms_history(run_id,movement_id,article_id,moved_at,type_id,type_name,qty,from_id,from_name,to_id,to_name)
                    VALUES(?,?,?,?,?,?,?,?,?,?,?)');
  foreach($rows as $row){
   $time=field($row['zeitstempel']??null,23);
   if($time!==null&&!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d{3})?$/D',$time))throw new InvalidArgumentException('bad_time');
   $qty=field($row['menge']??null,30);
   if($qty!==null&&!preg_match('/^-?\d{1,15}(?:\.\d{1,13})?$/D',$qty))throw new InvalidArgumentException('bad_quantity');
   $q->execute([$run,posInt($row['bewegung_id']??null),posInt($row['artikel_id']??null),$time,
    field($row['buchungsart_id']??null,11),field($row['buchungsart']??null,1020),$qty,
    field($row['start_id']??null,11),field($row['lagerplatz_von']??null,1020),
    field($row['ziel_id']??null,11),field($row['lagerplatz_nach']??null,1020)]);
  }
  $pdo->commit();result(200,['ok'=>true,'accepted'=>count($rows)]);
 }
 if($action==='finish'){
  $expected=posInt($in['total']??null,true);
  $q=$pdo->prepare('SELECT COUNT(*) FROM hw24_wms_history WHERE run_id=?');$q->execute([$run]);
  $actual=(int)$q->fetchColumn();
  if($actual!==$expected)throw new InvalidArgumentException('incomplete_history');
  $q=$pdo->prepare("UPDATE hw24_wms_history_runs SET status='ready',row_count=?,completed_utc=UTC_TIMESTAMP(6) WHERE run_id=?");
  $q->execute([$actual,$run]);
  $pdo->commit();
  result(200,['ok'=>true,'status'=>'ready','rows'=>$actual]);
 }
 throw new InvalidArgumentException('unknown_action');
}catch(InvalidArgumentException $e){
 if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
 result(400,['ok'=>false,'error'=>$e->getMessage()]);
}catch(Throwable $e){
 if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
 error_log('HW24 WMS history: '.$e->getMessage());
 result(503,['ok'=>false,'error'=>'history_unavailable']);
}
