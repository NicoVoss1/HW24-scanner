<?php
declare(strict_types=1);
/**
 * Read-only Amazon Product Fees v0 estimate for an ASIN and own hypothetical gross price.
 * Never saves estimated prices or fees and never updates Amazon/JTL.
 * A fee estimate is not a tax invoice or guaranteed final seller payout.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function failFees(int $status, array $data): void {
 http_response_code($status);
 echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PARTIAL_OUTPUT_ON_ERROR);
 exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){header('Allow: GET');failFees(405,['ok'=>false,'error'=>'method_not_allowed']);}
$auth=$_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??'';
if($auth==='')failFees(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
 failFees(403,['ok'=>false,'error'=>'https_required']);
$asin=strtoupper(trim((string)($_GET['asin']??'')));
if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))failFees(400,['ok'=>false,'error'=>'invalid_asin']);
$price=filter_var($_GET['price']??null,FILTER_VALIDATE_FLOAT);
$shipping=filter_var($_GET['shipping']??'0',FILTER_VALIDATE_FLOAT);
if($price===false||$price<=0||$price>100000||$shipping===false||$shipping<0||$shipping>10000)
 failFees(400,['ok'=>false,'error'=>'invalid_price']);
$price=round((float)$price,2);$shipping=round((float)$shipping,2);
$fulfilled=($_GET['fulfillment']??'FBM')==='FBA';
$marketplace='A1PA6795UKMFR9';
$config=dirname(__DIR__,2).'/hw24_private/amazon/amazon_spapi.php';
if(!is_file($config))failFees(503,['ok'=>false,'error'=>'private_config_missing']);
try {
 $cfg=require $config;
 if(!is_array($cfg))throw new RuntimeException('invalid_config');
 foreach(['lwa_client_id','lwa_client_secret','refresh_token'] as $key)
  if(!is_string($cfg[$key]??null)||$cfg[$key]==='')throw new RuntimeException('missing_credentials');
 if(!function_exists('curl_init'))throw new RuntimeException('curl_unavailable');
 $handle=curl_init('https://api.amazon.com/auth/o2/token');
 curl_setopt_array($handle,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query([
  'grant_type'=>'refresh_token','client_id'=>$cfg['lwa_client_id'],
  'client_secret'=>$cfg['lwa_client_secret'],'refresh_token'=>$cfg['refresh_token']
 ]),CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
 CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false]);
 $tokenRaw=curl_exec($handle);$tokenStatus=(int)curl_getinfo($handle,CURLINFO_HTTP_CODE);curl_close($handle);
 $token=is_string($tokenRaw)?json_decode($tokenRaw,true):null;
 if($tokenStatus!==200||!is_array($token)||empty($token['access_token']))
  throw new RuntimeException('lwa_auth_failed');
 $payload=['FeesEstimateRequest'=>[
  'MarketplaceId'=>$marketplace,'IsAmazonFulfilled'=>$fulfilled,
  'PriceToEstimateFees'=>[
   'ListingPrice'=>['CurrencyCode'=>'EUR','Amount'=>$price],
   'Shipping'=>['CurrencyCode'=>'EUR','Amount'=>$shipping]
  ],
  'Identifier'=>'hw24-'.substr(hash('sha256',$asin.'|'.$price.'|'.$shipping.'|'.(int)$fulfilled),0,18)
 ]];
 $handle=curl_init('https://sellingpartnerapi-eu.amazon.com/products/fees/v0/items/'.rawurlencode($asin).'/feesEstimate');
 curl_setopt_array($handle,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload,JSON_THROW_ON_ERROR),
  CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json',
   'x-amz-access-token: '.$token['access_token'],'User-Agent: HW24-Scanner/1.0 (Language=PHP)'],
  CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>30,CURLOPT_FOLLOWLOCATION=>false]);
 $raw=curl_exec($handle);$status=(int)curl_getinfo($handle,CURLINFO_HTTP_CODE);curl_close($handle);
 if($status!==200){
  failFees($status===429?429:($status===403?403:502),
   ['ok'=>false,'error'=>$status===403?'fees_role_or_access_missing':
    ($status===429?'amazon_rate_limited':'amazon_fees_request_failed'),'upstream_status'=>$status]);
 }
 if(!is_string($raw)||strlen($raw)>1024*1024)throw new RuntimeException('bad_fees_response');
 $response=json_decode($raw,true,128,JSON_THROW_ON_ERROR);
 $root=$response['payload']['FeesEstimateResult']??$response['FeesEstimateResult']??null;
 if(!is_array($root))throw new RuntimeException('fees_response_missing');
 $estimate=is_array($root['FeesEstimate']??null)?$root['FeesEstimate']:[];
 $state=(string)($root['Status']??'Unknown');
 $total=$estimate['TotalFeesEstimate']??null;
 $totalValue=is_array($total)&&is_numeric($total['Amount']??null)?round((float)$total['Amount'],2):null;
 $details=[];
 foreach(array_slice(is_array($estimate['FeeDetailList']??null)?$estimate['FeeDetailList']:[],0,30) as $fee){
  if(!is_array($fee))continue;
  $v=$fee['FeeAmount']??null;
  $details[]=['type'=>(string)($fee['FeeType']??'Other'),
   'amount'=>is_array($v)&&is_numeric($v['Amount']??null)?round((float)$v['Amount'],2):null,
   'currency'=>(string)($v['CurrencyCode']??'EUR')];
 }
 failFees(200,['ok'=>$totalValue!==null,'status'=>$state,
  'error'=>$totalValue===null?'estimate_not_available':null,
  'asin'=>$asin,'marketplace_id'=>$marketplace,'fulfillment'=>$fulfilled?'FBA':'FBM',
  'listing_gross_price'=>$price,'shipping_charged_to_buyer'=>$shipping,
  'total_estimated_fees'=>$totalValue,'currency'=>'EUR','fee_breakdown'=>$details,
  'retrieved_at_utc'=>gmdate('c'),
  'note'=>'Amazon fee estimate; not a tax invoice, final payout or guaranteed profit.']);
}catch(Throwable $e){
 error_log('HW24 Amazon fee estimate ['.get_class($e).']: '.$e->getMessage());
 failFees(503,['ok'=>false,'error'=>'amazon_fees_unavailable']);
}
