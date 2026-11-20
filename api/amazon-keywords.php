<?php
declare(strict_types=1);
/**
 * V4.22 TEST · read-only Amazon catalog keyword/ASIN candidates.
 * Requires SQL HW24_12 and the same protected private Amazon configuration as amazon.php.
 * Never creates a JTL article, changes a listing or infers an exact EAN from a title.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function reply(int $code,array $payload): never {
  http_response_code($code);
  echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PARTIAL_OUTPUT_ON_ERROR);
  exit;
}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')reply(405,['ok'=>false,'error'=>'method_not_allowed']);
$actor=(string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??'');
if($actor==='')reply(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on' && ($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
  reply(403,['ok'=>false,'error'=>'https_required']);
$q=trim((string)($_GET['q']??''));
$q=preg_replace('/\s+/u',' ',$q)??'';
$len=function_exists('mb_strlen')?mb_strlen($q,'UTF-8'):strlen($q);
if($len<3||$len>120||!preg_match('/^[\p{L}\p{N} .,_\/()+\-]+$/uD',$q))
  reply(400,['ok'=>false,'error'=>'invalid_search_term']);
$asinSearch=(bool)preg_match('/^[A-Z0-9]{10}$/D',strtoupper($q));
$marketplace='A1PA6795UKMFR9';
$normalized=function_exists('mb_strtolower')?mb_strtolower($q,'UTF-8'):strtolower($q);
$normalized=trim($normalized);
$hash=hash('sha256',($asinSearch?'ASIN:':'KEYWORD:').$normalized);
$settingsFile=dirname(__DIR__,2).'/hw24_private/amazon/amazon_spapi.php';
$dbFile=dirname(__DIR__,2).'/hw24_private/db.php';
if(!is_file($settingsFile)||!is_file($dbFile))
  reply(503,['ok'=>false,'error'=>'private_config_missing']);
function cleanItems(array $items): array {
  $out=[];
  foreach(array_slice($items,0,20) as $item){
    if(!is_array($item))continue;
    $summary=$item['summaries'][0]??[];
    $ids=[];
    foreach(($item['identifiers']??[]) as $set){
      if(!is_array($set))continue;
      foreach(($set['identifiers']??[]) as $v){
        if(!is_array($v))continue;
        if(in_array(strtoupper((string)($v['identifierType']??'')),['EAN','UPC','GTIN'],true)){
          $id=trim((string)($v['identifier']??''));
          if(preg_match('/^\d{8,14}$/D',$id))$ids[$id]=true;
        }
      }
    }
    $asin=strtoupper((string)($item['asin']??''));
    if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))continue;
    $out[]=['asin'=>$asin,'title'=>substr((string)($summary['itemName']??''),0,600),
      'brand'=>substr((string)($summary['brand']??''),0,180),
      'manufacturer'=>substr((string)($summary['manufacturer']??''),0,180),
      'gtins'=>array_slice(array_keys($ids),0,12)];
  }
  return $out;
}
function cached(PDO $pdo,string $market,string $hash): ?array {
  $s=$pdo->prepare('SELECT response_json,item_count,fetched_at_utc FROM hw24_amazon_keyword_cache
                   WHERE marketplace_id=? AND query_hash=?
                   AND fetched_at_utc >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 360 MINUTE)');
  $s->execute([$market,$hash]);$r=$s->fetch();
  if(!$r)return null;
  $items=json_decode((string)$r['response_json'],true);
  if(!is_array($items))return null;
  return ['ok'=>true,'source'=>'cache','count'=>(int)$r['item_count'],
          'fetched_at_utc'=>$r['fetched_at_utc'],'items'=>$items];
}
$pdo=null;
try{
  $db=require $dbFile;$settings=require $settingsFile;
  if(!is_array($db)||empty($db['user'])||!isset($db['password']) ||
     !is_array($settings)||empty($settings['lwa_client_id'])||
     empty($settings['lwa_client_secret'])||empty($settings['refresh_token']))
    throw new RuntimeException('configuration_incomplete');
  $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
   $db['user'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,
    PDO::ATTR_TIMEOUT=>5]);
  $hit=cached($pdo,$marketplace,$hash);
  if($hit)reply(200,$hit);
  // Serialize keyword cache misses across requests. Concurrency on the existing
  // EAN endpoint remains independent; a shared global quota worker is future work.
  $lock=$pdo->prepare("SELECT GET_LOCK('hw24_amazon_keywords_v1',10)");
  $lock->execute();
  if((int)$lock->fetchColumn()!==1)reply(429,['ok'=>false,'error'=>'keyword_queue_busy']);
  $hit=cached($pdo,$marketplace,$hash);
  if($hit)reply(200,$hit);
  if(!function_exists('curl_init'))throw new RuntimeException('curl_unavailable');
  $ch=curl_init('https://api.amazon.com/auth/o2/token');
  curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query([
     'grant_type'=>'refresh_token','client_id'=>$settings['lwa_client_id'],
     'client_secret'=>$settings['lwa_client_secret'],'refresh_token'=>$settings['refresh_token']]),
     CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
     CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_CONNECTTIMEOUT=>8,
     CURLOPT_FOLLOWLOCATION=>false]);
  $auth=curl_exec($ch);$authCode=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
  $token=is_string($auth)?json_decode($auth,true):null;
  if($authCode!==200||!is_array($token)||empty($token['access_token']))
    throw new RuntimeException('amazon_auth_unavailable');
  $params=['marketplaceIds'=>$marketplace,'includedData'=>'summaries,identifiers',
            'pageSize'=>10,'locale'=>'de_DE'];
  if($asinSearch){$params['identifiers']=strtoupper($q);$params['identifiersType']='ASIN';}
  else{$params['keywords']=$q;$params['keywordsLocale']='de_DE';}
  $url='https://sellingpartnerapi-eu.amazon.com/catalog/2022-04-01/items?'.
   http_build_query($params,'','&',PHP_QUERY_RFC3986);
  // Conservative pacing against free-text rate limits.
  usleep(900000);
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_HTTPHEADER=>['x-amz-access-token: '.$token['access_token'],
    'Accept: application/json','User-Agent: HW24-Scanner/1.0'],
    CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>35,CURLOPT_CONNECTTIMEOUT=>8,
    CURLOPT_FOLLOWLOCATION=>false]);
  $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
  if($status!==200){
    reply($status===429?429:($status===403?403:502),[
      'ok'=>false,'error'=>$status===429?'amazon_rate_limited':
      ($status===403?'amazon_role_missing':'amazon_keyword_lookup_failed'),
      'upstream_status'=>$status]);
  }
  if(!is_string($raw)||strlen($raw)>4*1024*1024)throw new RuntimeException('bad_response');
  $doc=json_decode($raw,true,256,JSON_THROW_ON_ERROR);
  if(!is_array($doc)||!is_array($doc['items']??null))throw new RuntimeException('invalid_response');
  $items=cleanItems($doc['items']);
  $s=$pdo->prepare('INSERT INTO hw24_amazon_keyword_cache
    (marketplace_id,query_hash,normalized_query,response_json,item_count)
    VALUES (?,?,?,?,?)
    ON DUPLICATE KEY UPDATE normalized_query=VALUES(normalized_query),
    response_json=VALUES(response_json),item_count=VALUES(item_count),
    fetched_at_utc=UTC_TIMESTAMP(6)');
  $s->execute([$marketplace,$hash,$normalized,
    json_encode($items,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),count($items)]);
  reply(200,['ok'=>true,'source'=>'live','count'=>count($items),
    'fetched_at_utc'=>gmdate('c'),'items'=>$items,
    'has_next_page'=>!empty($doc['pagination']['nextToken'])]);
}catch(Throwable $e){
  error_log('HW24 Amazon keywords ['.get_class($e).']: '.$e->getMessage());
  reply(503,['ok'=>false,'error'=>'keyword_cache_or_api_unavailable']);
}
