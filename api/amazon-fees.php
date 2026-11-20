<?php
declare(strict_types=1);
/**
 * Read-only Amazon Product Fees v0 estimate for a seller SKU or ASIN and a gross price.
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
function feeAmount($money): ?float {
 if(!is_array($money)||($money['CurrencyCode']??null)!=='EUR'||!is_numeric($money['Amount']??null))return null;
 $amount=(float)$money['Amount'];
 return is_finite($amount)?round($amount,2):null;
}
function feeDetails($rows): array {
 $details=[];
 foreach(array_slice(is_array($rows)?$rows:[],0,30) as $fee){
  if(!is_array($fee)||!is_string($fee['FeeType']??null))continue;
  $base=feeAmount($fee['FeeAmount']??null);
  $promotion=feeAmount($fee['FeePromotion']??null);
  $tax=feeAmount($fee['TaxAmount']??null);
  $final=feeAmount($fee['FinalFee']??null)??($base!==null?round($base-($promotion??0),2):null);
  if($final===null)continue;
  $details[]=['type'=>$fee['FeeType'],'amount'=>$final,'currency'=>'EUR',
   'base_amount'=>$base,'promotion_amount'=>$promotion,'tax_amount'=>$tax];
 }
 return $details;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){header('Allow: GET');failFees(405,['ok'=>false,'error'=>'method_not_allowed']);}
$auth=$_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??'';
if($auth==='')failFees(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
 failFees(403,['ok'=>false,'error'=>'https_required']);
$asin=is_string($_GET['asin']??null)?strtoupper(trim($_GET['asin'])):'';
if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))failFees(400,['ok'=>false,'error'=>'invalid_asin']);
$sku=$_GET['seller_sku']??'';
if(!is_string($sku)||($sku!==''&&(!preg_match('/^[^\p{C}]{1,40}$/uD',$sku)||trim($sku)===''||in_array($sku,['.','..'],true))))
 failFees(400,['ok'=>false,'error'=>'invalid_seller_sku']);
$price=filter_var($_GET['price']??null,FILTER_VALIDATE_FLOAT);
$shipping=filter_var($_GET['shipping']??'0',FILTER_VALIDATE_FLOAT);
if($price===false||$price<0.01||$price>100000||$shipping===false||$shipping<0||$shipping>10000)
 failFees(400,['ok'=>false,'error'=>'invalid_price']);
$price=round((float)$price,2);$shipping=round((float)$shipping,2);
$fulfillment=$_GET['fulfillment']??'FBM';
if(!in_array($fulfillment,['FBA','FBM'],true))failFees(400,['ok'=>false,'error'=>'invalid_fulfillment']);
$fulfilled=$fulfillment==='FBA';
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
  'Identifier'=>'hw24-'.substr(hash('sha256',$asin.'|'.$sku.'|'.$price.'|'.$shipping.'|'.(int)$fulfilled),0,18)
 ]];
 $itemPath=$sku!==''?'listings/'.rawurlencode($sku):'items/'.rawurlencode($asin);
 $handle=curl_init('https://sellingpartnerapi-eu.amazon.com/products/fees/v0/'.$itemPath.'/feesEstimate');
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
 $totalValue=$state==='Success'?feeAmount($estimate['TotalFeesEstimate']??null):null;
 failFees(200,['ok'=>$totalValue!==null,'status'=>$state,
  'error'=>$totalValue===null?'estimate_not_available':null,
  'asin'=>$asin,'seller_sku'=>$sku!==''?$sku:null,'marketplace_id'=>$marketplace,'fulfillment'=>$fulfillment,
  'listing_gross_price'=>$price,'shipping_charged_to_buyer'=>$shipping,
  'total_estimated_fees'=>$totalValue,'currency'=>'EUR','fee_breakdown'=>feeDetails($estimate['FeeDetailList']??null),
  'retrieved_at_utc'=>gmdate('c'),
  'note'=>'Amazon fee estimate; not a tax invoice, final payout or guaranteed profit.']);
}catch(Throwable $e){
 error_log('HW24 Amazon fee estimate ['.get_class($e).']: '.$e->getMessage());
 failFees(503,['ok'=>false,'error'=>'amazon_fees_unavailable']);
}
