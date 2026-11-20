<?php
declare(strict_types=1);
/**
 * HW24 approved catalog master-data overlays.
 * Protect with HTTP auth + HTTPS. Writes are user-initiated with an explicit
 * approval and an optimistic revision check. Does NOT write to historical Excel,
 * JTL, the marketplace or any external system.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function answer(int $code,array $payload): void {
 http_response_code($code);
 echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PARTIAL_OUTPUT_ON_ERROR);
 exit;
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,['GET','POST'],true)){
 header('Allow: GET, POST');answer(405,['ok'=>false,'error'=>'method_not_allowed']);
}
$actor=(string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??'');
if($actor==='')answer(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
 answer(403,['ok'=>false,'error'=>'https_required']);
if($method==='POST'){
 $origin=$_SERVER['HTTP_ORIGIN']??'';
 $host=strtolower(explode(':',(string)($_SERVER['HTTP_HOST']??''))[0]);
 $originHost=strtolower((string)parse_url($origin,PHP_URL_HOST));
 $originScheme=(string)parse_url($origin,PHP_URL_SCHEME);
 if($originHost===''||$originHost!==$host||$originScheme!=='https')
  answer(403,['ok'=>false,'error'=>'cross_origin_denied']);
 if(($_SERVER['HTTP_X_HW24_APPROVAL']??'')!=='confirmed')
  answer(403,['ok'=>false,'error'=>'explicit_approval_required']);
 if(!str_starts_with(strtolower($_SERVER['CONTENT_TYPE']??''),'application/json'))
  answer(415,['ok'=>false,'error'=>'json_required']);
 if((int)($_SERVER['CONTENT_LENGTH']??0)>20000)answer(413,['ok'=>false,'error'=>'payload_too_large']);
}
$body=null;
if($method==='POST'){
 try{
  $body=json_decode(file_get_contents('php://input'),true,64,JSON_THROW_ON_ERROR);
 }catch(Throwable $e){answer(400,['ok'=>false,'error'=>'invalid_json']);}
 if(!is_array($body)||($body['confirmed']??false)!==true)
  answer(400,['ok'=>false,'error'=>'explicit_approval_required']);
}
$ean=trim((string)($method==='GET'?($_GET['ean']??''):($body['ean']??'')));
if(!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean))
 answer(400,['ok'=>false,'error'=>'invalid_ean']);
function validEan(string $s): bool {
 if(preg_match('/^0+$/D',$s))return false;
 $sum=0;$weight=3;
 for($i=strlen($s)-2;$i>=0;$i--,$weight=$weight===3?1:3)$sum+=(int)$s[$i]*$weight;
 return (10-$sum%10)%10===(int)$s[strlen($s)-1];
}
if(!validEan($ean))answer(400,['ok'=>false,'error'=>'invalid_ean_checksum']);
$dbfile=dirname(__DIR__,2).'/hw24_private/db.php';
if(!is_file($dbfile))answer(503,['ok'=>false,'error'=>'database_not_configured']);
try{
 $db=require $dbfile;
 if(!is_array($db)||empty($db['user'])||!isset($db['password']))
  throw new RuntimeException('invalid_database_config');
 $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
  $db['user'],$db['password'],[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
    PDO::ATTR_TIMEOUT=>5
  ]);
 if($method==='GET'){
  $q=$pdo->prepare('SELECT approved_fields_json,revision,approved_by,approved_at_utc FROM hw24_curated_articles WHERE ean=? LIMIT 1');
  $q->execute([$ean]);$r=$q->fetch();
  if(!$r)answer(200,['ok'=>true,'ean'=>$ean,'exists'=>false,'revision'=>0,'fields'=>new stdClass()]);
  $fields=json_decode((string)$r['approved_fields_json'],true);
  $auditRows=[];
  $aq=$pdo->prepare('SELECT revision,changed_at_utc,approved_by,previous_json,approved_json FROM hw24_curated_audit WHERE ean=? ORDER BY id DESC LIMIT 15');
  $aq->execute([$ean]);
  foreach($aq->fetchAll() as $item){
   $before=json_decode((string)$item['previous_json'],true);
   $after=json_decode((string)$item['approved_json'],true);
   if(!is_array($before))$before=[];
   if(!is_array($after))$after=[];
   $changes=[];
   foreach($after as $field=>$v){
    if(!array_key_exists($field,$before)||$before[$field]!==$v)$changes[]=(string)$field;
   }
   $auditRows[]=['revision'=>(int)$item['revision'],'at_utc'=>(string)$item['changed_at_utc'],
    'approved_by'=>(string)$item['approved_by'],'changed_fields'=>$changes];
  }
  answer(200,['ok'=>true,'ean'=>$ean,'exists'=>true,'revision'=>(int)$r['revision'],
   'fields'=>is_array($fields)?$fields:new stdClass(),
   'approved_at_utc'=>(string)$r['approved_at_utc'],'audit'=>$auditRows]);
 }
 $allow=['Artikelname'=>450,'Hersteller'=>180,'Kurzbeschreibung'=>3500,
  'Beschreibung'=>12000,'Warengruppe'=>250,'Artikelgewicht'=>100,
  'Versandgewicht'=>100,'Brutto-VK'=>60,'HAN'=>100,
  'Netto-VK'=>60,'Kosten'=>60,'Netto-EK'=>60,
  'Inhalt/Menge'=>120,'Inhalt'=>120,'Inhaltseinheit'=>120,
  'Steuerklasse'=>120,'Steuersatz in %'=>40,'Steuerschlüssel'=>120,
  'Lieferant'=>240,'Artikelnummer (Lieferant)'=>150,
  'Kategorie Ebene 1'=>250,'Kategorie Ebene 2'=>250,'Kategorie Ebene 3'=>250,
  'Versandklasse'=>120,'Währung'=>20,'Artikeltyp'=>120,
  'Maßeinheit'=>40,'ASIN'=>20];
 $requested=$body['fields']??null;$expected=$body['expected_revision']??null;
 if(!is_array($requested)||!count($requested)||count($requested)>count($allow))
  answer(400,['ok'=>false,'error'=>'fields_required']);
 if(!is_int($expected)||$expected<0)answer(400,['ok'=>false,'error'=>'revision_required']);
 $clean=[];
 foreach($requested as $key=>$value){
  if(!array_key_exists($key,$allow)||!is_string($value))
   answer(400,['ok'=>false,'error'=>'invalid_field','field'=>(string)$key]);
  $v=trim($value);
  if($key==='ASIN'&&!preg_match('/^[A-Z0-9]{10}$/D',strtoupper($v)))
   answer(400,['ok'=>false,'error'=>'invalid_asin']);
  if($v===''||strlen($v)>$allow[$key]*4)
   answer(400,['ok'=>false,'error'=>'invalid_field_length','field'=>$key]);
  if(function_exists('mb_strlen')&&mb_strlen($v,'UTF-8')>$allow[$key])
   answer(400,['ok'=>false,'error'=>'invalid_field_length','field'=>$key]);
  $clean[$key]=$v;
 }
 $pdo->beginTransaction();
 $lock=$pdo->prepare('SELECT approved_fields_json,revision FROM hw24_curated_articles WHERE ean=? FOR UPDATE');
 $lock->execute([$ean]);$old=$lock->fetch();
 $previous=$old?json_decode((string)$old['approved_fields_json'],true):[];
 if(!is_array($previous))$previous=[];
 $currentRevision=$old?(int)$old['revision']:0;
 if($currentRevision!==$expected){
  $pdo->rollBack();answer(409,['ok'=>false,'error'=>'revision_conflict','current_revision'=>$currentRevision]);
 }
 $new=array_replace($previous,$clean);$newRevision=$currentRevision+1;
 $newJson=json_encode($new,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 $previousJson=json_encode($previous,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 if($old){
  $u=$pdo->prepare('UPDATE hw24_curated_articles SET approved_fields_json=?,revision=?,approved_by=?,approved_at_utc=UTC_TIMESTAMP() WHERE ean=? AND revision=?');
  $u->execute([$newJson,$newRevision,substr($actor,0,180),$ean,$currentRevision]);
  if($u->rowCount()!==1)throw new RuntimeException('concurrent_revision_change');
 }else{
  $u=$pdo->prepare('INSERT INTO hw24_curated_articles(ean,approved_fields_json,revision,approved_by,approved_at_utc) VALUES (?,?,?,?,UTC_TIMESTAMP())');
  $u->execute([$ean,$newJson,$newRevision,substr($actor,0,180)]);
 }
 $audit=$pdo->prepare('INSERT INTO hw24_curated_audit(ean,revision,approved_by,changed_at_utc,previous_json,approved_json) VALUES (?,?,?,UTC_TIMESTAMP(),?,?)');
 $audit->execute([$ean,$newRevision,substr($actor,0,180),$previousJson,$newJson]);
 $pdo->commit();
 answer(200,['ok'=>true,'ean'=>$ean,'exists'=>true,'revision'=>$newRevision,
  'fields'=>$new,'approved_at_utc'=>gmdate('Y-m-d H:i:s')]);
}catch(Throwable $e){
 if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
 error_log('HW24 curated data API ['.get_class($e).']: '.$e->getMessage());
 answer(503,['ok'=>false,'error'=>'curated_storage_unavailable']);
}
