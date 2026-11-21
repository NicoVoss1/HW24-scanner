<?php
declare(strict_types=1);
/**
 * HW24 AI archive. Immutable historical analysis, read-only, authenticated.
 * Data is scoped to the same actor who started the model review.
 * Private restricted DB credentials: hw24_private/scanlist-db.php.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
function send(int $status,array $data): never {
 http_response_code($status);
 echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
 exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){
 header('Allow: GET');send(405,['ok'=>false,'error'=>'method_not_allowed']);
}
$actor=trim((string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??''));
if($actor===''||strlen($actor)>180)send(403,['ok'=>false,'error'=>'authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
 send(403,['ok'=>false,'error'=>'https_required']);
$ean=trim((string)($_GET['ean']??''));
if($ean!==''&&!preg_match('/^[0-9]{8,14}$/D',$ean))
 send(400,['ok'=>false,'error'=>'invalid_ean']);
$before=filter_var($_GET['before_id']??null,FILTER_VALIDATE_INT);
if($before===false||($before!==null&&$before<1))
 send(400,['ok'=>false,'error'=>'invalid_cursor']);
$limit=filter_var($_GET['limit']??20,FILTER_VALIDATE_INT);
if(!$limit||$limit<1||$limit>50)$limit=20;
$path=dirname(__DIR__,2).'/hw24_private/scanlist-db.php';
if(!is_file($path))send(503,['ok'=>false,'error'=>'review_storage_unavailable']);
try{
 $cfg=require $path;
 if(!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg))
  throw new RuntimeException('database_config_unavailable');
 $db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
  (string)$cfg['user'],(string)$cfg['password'],[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
   PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5
  ]);
 $query='SELECT id,ean,article_name,model,ai_status,review_json,input_json,
        usd_cost,tokens_input,tokens_cached,tokens_output,created_at_utc
        FROM hw24_ai_reviews WHERE actor=?';
 $params=[$actor];
 if($ean!==''){$query.=' AND ean=?';$params[]=$ean;}
 if($before!==null){$query.=' AND id<?';$params[]=$before;}
 $query.=' ORDER BY id DESC LIMIT '.(int)$limit;
 $stmt=$db->prepare($query);$stmt->execute($params);
 $reviews=[];
 foreach($stmt->fetchAll() as $row){
  $analysis=json_decode((string)$row['review_json'],true);
  $source=json_decode((string)$row['input_json'],true);
  if(!is_array($analysis))$analysis=[];
  if(!is_array($source))$source=[];
  $reviews[]=['id'=>(int)$row['id'],'ean'=>$row['ean'],
   'article_name'=>$row['article_name'],'model'=>$row['model'],
   'status'=>$row['ai_status'],'review'=>$analysis,'input'=>$source,
   'usd_cost'=>(float)$row['usd_cost'],
   'tokens'=>['input'=>(int)$row['tokens_input'],
      'cached'=>(int)$row['tokens_cached'],'output'=>(int)$row['tokens_output']],
   'created_at_utc'=>$row['created_at_utc']];
 }
 send(200,['ok'=>true,'stored'=>true,'scope'=>'authenticated_user',
   'ean_filter'=>$ean,'count'=>count($reviews),
   'next_before_id'=>count($reviews)===$limit?(int)end($reviews)['id']:null,
   'reviews'=>$reviews]);
}catch(Throwable $e){
 error_log('HW24 AI archive GET failed: '.get_class($e));
 send(503,['ok'=>false,'error'=>'review_storage_unavailable',
  'hint'=>'AI-review SQL schema and restricted DB permissions must be installed']);
}
