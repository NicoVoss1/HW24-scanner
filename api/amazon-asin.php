<?php
declare(strict_types=1);
/*
 * Read-only, explicitly requested Amazon.de Catalog Items lookup by an ASIN
 * already linked to the given EAN in the latest complete JTL mirror.
 * No SQL INSERT/UPDATE, no catalog snapshots, no automatic ASIN approval.
 * Secrets and the unfiltered Amazon payload are never returned to the browser.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function respond(int $code, array $data): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    respond(405, ['ok'=>false,'error'=>'method_not_allowed']);
}
if (($_SERVER['PHP_AUTH_USER'] ?? $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? '')==='') {
    respond(403, ['ok'=>false,'error'=>'site_authentication_required']);
}
if (($_SERVER['HTTPS'] ?? '')!=='on' &&
    ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')!=='https') {
    respond(403, ['ok'=>false,'error'=>'https_required']);
}
$ean=trim((string)($_GET['ean']??''));
$asin=strtoupper(trim((string)($_GET['asin']??'')));
if (!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean) ||
    !preg_match('/^[A-Z0-9]{10}$/D',$asin)) {
    respond(400,['ok'=>false,'error'=>'invalid_identifiers']);
}
$sum=0;
$weight=3;
for($i=strlen($ean)-2;$i>=0;$i--){$sum+=((int)$ean[$i])*$weight;$weight=$weight===3?1:3;}
if(preg_match('/^0+$/D',$ean)||(10-$sum%10)%10!==(int)$ean[strlen($ean)-1]) {
    respond(400,['ok'=>false,'error'=>'invalid_ean_checksum']);
}
$private=dirname(__DIR__,2).'/hw24_private/';
if(!is_file($private.'db.php')||!is_file($private.'amazon/amazon_spapi.php')) {
    respond(503,['ok'=>false,'error'=>'private_config_missing']);
}
$stage='init';
try {
    $db=require $private.'db.php';
    $config=require $private.'amazon/amazon_spapi.php';
    if(!is_array($db)||empty($db['user'])||!array_key_exists('password',$db)||
       !is_array($config)||empty($config['lwa_client_id'])||
       empty($config['lwa_client_secret'])||empty($config['refresh_token'])) {
        respond(503,['ok'=>false,'error'=>'private_config_invalid']);
    }
    $pdo=new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
        $db['user'],$db['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES=>false,
         PDO::ATTR_TIMEOUT=>5]
    );
    $stage='verify_jtl_asin_link';
    $latest=$pdo->query("SELECT source_id,
            TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
         FROM hw24_jtl_sync_meta
         WHERE status='ready' AND last_completed_utc IS NOT NULL
         ORDER BY last_completed_utc DESC LIMIT 1")->fetch();
    if(!$latest||$latest['age_minutes']===null||
       (int)$latest['age_minutes']<0||(int)$latest['age_minutes']>45) {
        respond(409,['ok'=>false,'error'=>'jtl_mirror_not_current']);
    }
    $q=$pdo->prepare('SELECT asin,raw_json FROM hw24_jtl_articles
                      WHERE source_id=? AND ean=? ORDER BY jtl_article_id LIMIT 16');
    $q->execute([$latest['source_id'],$ean]);
    $linked=false;
    $rows=$q->fetchAll();
    foreach($rows as $row) {
        $candidates=[];
        $candidates[]=$row['asin']??null;
        $raw=json_decode((string)($row['raw_json']??''),true);
        $related=is_array($raw)?($raw['HW24_RelatedSources']??null):null;
        if(is_array($related)){
            foreach(['artikel_asin'=>['cAsin'],'amazon_angebote'=>['asin']] as $group=>$keys){
                $entries=$related[$group]['rows']??[];
                if(!is_array($entries))continue;
                foreach(array_slice($entries,0,8) as $entry){
                    if(!is_array($entry))continue;
                    foreach($keys as $key)$candidates[]=$entry[$key]??null;
                }
            }
        }
        foreach($candidates as $candidate){
            if(is_scalar($candidate)&&strtoupper(trim((string)$candidate))===$asin) {
                $linked=true;break 2;
            }
        }
    }
    if(!$linked)respond(403,['ok'=>false,'error'=>'asin_not_linked_to_jtl_article']);
    if(!function_exists('curl_init'))respond(503,['ok'=>false,'error'=>'curl_unavailable']);
    $stage='lwa_token';
    $ch=curl_init('https://api.amazon.com/auth/o2/token');
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query([
            'grant_type'=>'refresh_token','refresh_token'=>$config['refresh_token'],
            'client_id'=>$config['lwa_client_id'],'client_secret'=>$config['lwa_client_secret']
        ]),
        CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,
        CURLOPT_FOLLOWLOCATION=>false
    ]);
    $tokenBody=curl_exec($ch);
    $tokenStatus=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    $token=is_string($tokenBody)?json_decode($tokenBody,true):null;
    if($tokenStatus!==200||!is_array($token)||empty($token['access_token'])) {
        respond(503,['ok'=>false,'error'=>'amazon_auth_unavailable','stage'=>$stage]);
    }
    $stage='get_catalog_item';
    $marketplace='A1PA6795UKMFR9';
    $url='https://sellingpartnerapi-eu.amazon.com/catalog/2022-04-01/items/'.
        rawurlencode($asin).'?'.http_build_query([
            'marketplaceIds'=>$marketplace,
            'includedData'=>'summaries,identifiers,attributes,productTypes'
        ],'','&',PHP_QUERY_RFC3986);
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_HTTPHEADER=>[
            'x-amz-access-token: '.$token['access_token'],
            'Accept: application/json','User-Agent: HW24-Scanner/1.0 (Language=PHP)'
        ],
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,
        CURLOPT_TIMEOUT=>35,CURLOPT_FOLLOWLOCATION=>false
    ]);
    $body=curl_exec($ch);
    $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($status===404)respond(200,[
        'ok'=>true,'found'=>false,'source'=>'amazon_spapi_by_asin',
        'asin'=>$asin,'ean'=>$ean,'marketplace_id'=>$marketplace,
        'note'=>'Amazon.de hat diese verknüpfte ASIN nicht gefunden. Keine Artikelzuordnung geändert.'
    ]);
    if(!is_string($body)||$status!==200)respond(502,[
        'ok'=>false,'error'=>'amazon_catalog_request_failed',
        'upstream_status'=>$status,'stage'=>$stage
    ]);
    if(strlen($body)>4*1024*1024)respond(502,['ok'=>false,'error'=>'amazon_catalog_payload_too_large']);
    $product=json_decode($body,true,512,JSON_THROW_ON_ERROR);
    if(!is_array($product)||strtoupper((string)($product['asin']??''))!==$asin) {
        respond(502,['ok'=>false,'error'=>'amazon_catalog_invalid_asin']);
    }
    $summary=$product['summaries'][0]??[];
    $codes=[];
    foreach(($product['identifiers']??[]) as $part){
        if(!is_array($part))continue;
        foreach(($part['identifiers']??[]) as $id){
            if(!is_array($id))continue;
            if(strtoupper((string)($id['identifierType']??''))!=='EAN')continue;
            $code=trim((string)($id['identifier']??''));
            if(preg_match('/^\d{8,14}$/D',$code))$codes[$code]=true;
        }
    }
    $codes=array_keys($codes);
    $evidence=in_array($ean,$codes,true)?'exact_ean':
      (count($codes)>0?'other_ean':'ean_not_supplied');
    respond(200,[
        'ok'=>true,'found'=>true,'source'=>'amazon_spapi_by_asin',
        'marketplace_id'=>$marketplace,'ean'=>$ean,'asin'=>$asin,
        'title'=>substr(trim((string)($summary['itemName']??'')),0,500),
        'brand'=>substr(trim((string)($summary['brand']??'')),0,150),
        'manufacturer'=>substr(trim((string)($summary['manufacturer']??'')),0,150),
        'ean_evidence'=>$evidence,'ean_identifiers'=>array_slice($codes,0,12),
        'review_required'=>true,
        'note'=>'Amazon-Katalogdaten als Prüfvorschlag, keine automatische EAN-ASIN-Verknüpfung und kein JTL-Schreibzugriff.'
    ]);
} catch(Throwable $e){
    error_log('HW24 manually requested Amazon ASIN lookup stage='.$stage.
              ' ['.get_class($e).']: '.$e->getMessage());
    respond(503,['ok'=>false,'error'=>'amazon_asin_lookup_unavailable','stage'=>$stage]);
}
