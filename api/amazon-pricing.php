<?php
declare(strict_types=1);
/**
 * HW24 Amazon Product Pricing v0, getItemOffers (read only).
 * On-demand only. Seller IDs are marketplace identifiers, NOT supplier records.
 * Same HTTPS + Plesk HTTP authentication checks as api/amazon.php.
 * Prices are not archived at this stage: honor data-use/retention requirements.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function send(int $code,array $data): void {
 http_response_code($code);
 echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PARTIAL_OUTPUT_ON_ERROR);
 exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){
 header('Allow: GET');send(405,['ok'=>false,'error'=>'method_not_allowed']);
}
$auth=$_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??'';
if($auth==='')send(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on' && ($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
 send(403,['ok'=>false,'error'=>'https_required']);
$asin=strtoupper(trim((string)($_GET['asin']??'')));
if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))
 send(400,['ok'=>false,'error'=>'invalid_asin']);
$config=dirname(__DIR__,2).'/hw24_private/amazon/amazon_spapi.php';
if(!is_file($config))send(503,['ok'=>false,'error'=>'private_config_missing']);
function price(?array $v): ?array {
 if(!$v||!is_numeric($v['Amount']??null)||!is_string($v['CurrencyCode']??null)||
   !preg_match('/^[A-Z]{3}$/D',$v['CurrencyCode']))return null;
 $amount=(float)$v['Amount'];
 return is_finite($amount)?['amount'=>$amount,'currency'=>$v['CurrencyCode']]:null;
}
function summaryPrices(array $list): array {
 $out=[];
 foreach(array_slice($list,0,15) as $row){
  if(!is_array($row))continue;
  $out[]=['condition'=>(string)($row['condition']??$row['Condition']??''),
   'fulfillment'=>(string)($row['fulfillmentChannel']??$row['FulfillmentChannel']??''),
   'listing_price'=>price(is_array($row['ListingPrice']??null)?$row['ListingPrice']:null),
   'shipping'=>price(is_array($row['Shipping']??null)?$row['Shipping']:null),
   'landed_price'=>price(is_array($row['LandedPrice']??null)?$row['LandedPrice']:null)];
 }
 return $out;
}
try{
 $cfg=require $config;
 if(!is_array($cfg))throw new RuntimeException('config_not_array');
 foreach(['lwa_client_id','lwa_client_secret','refresh_token'] as $k)
  if(!is_string($cfg[$k]??null)||$cfg[$k]==='')throw new RuntimeException('missing_credentials');
 if(!function_exists('curl_init'))throw new RuntimeException('curl_missing');
 $h=curl_init('https://api.amazon.com/auth/o2/token');
 curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query([
  'grant_type'=>'refresh_token','client_id'=>$cfg['lwa_client_id'],
  'client_secret'=>$cfg['lwa_client_secret'],'refresh_token'=>$cfg['refresh_token']
 ]),CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
 CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false]);
 $tokenRaw=curl_exec($h);$tokenStatus=(int)curl_getinfo($h,CURLINFO_HTTP_CODE);curl_close($h);
 $token=is_string($tokenRaw)?json_decode($tokenRaw,true):null;
 if($tokenStatus!==200||!is_array($token)||empty($token['access_token']))
  throw new RuntimeException('lwa_failed');
 $marketplace='A1PA6795UKMFR9';
 $url='https://sellingpartnerapi-eu.amazon.com/products/pricing/v0/items/'.rawurlencode($asin).
  '/offers?'.http_build_query(['MarketplaceId'=>$marketplace,'ItemCondition'=>'New'],'','&',PHP_QUERY_RFC3986);
 $h=curl_init($url);
 curl_setopt_array($h,[CURLOPT_HTTPHEADER=>['x-amz-access-token: '.$token['access_token'],
  'Accept: application/json','User-Agent: HW24-Scanner/1.0 (Language=PHP)'],
  CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>30,CURLOPT_FOLLOWLOCATION=>false]);
 $raw=curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_HTTP_CODE);curl_close($h);
 if($status!==200){
  $code=$status===429?429:($status===403?403:502);
  send($code,['ok'=>false,'error'=>$status===429?'amazon_rate_limited':
   ($status===403?'pricing_role_or_access_missing':'amazon_pricing_request_failed'),
   'upstream_status'=>$status]);
 }
 if(!is_string($raw)||strlen($raw)>4*1024*1024)throw new RuntimeException('bad_response_size');
 $body=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
 $payload=$body['payload']??$body['body']['payload']??null;
 if(!is_array($payload))throw new RuntimeException('pricing_payload_missing');
 $rawOffers=$payload['Offers']??[];
 $offers=[];
 if(is_array($rawOffers))foreach(array_slice($rawOffers,0,20) as $offer){
  if(!is_array($offer))continue;
  $item=price(is_array($offer['ListingPrice']??null)?$offer['ListingPrice']:null);
  $ship=price(is_array($offer['Shipping']??null)?$offer['Shipping']:null);
  $total=($item&&$ship&&$item['currency']===$ship['currency'])
    ?['amount'=>round($item['amount']+$ship['amount'],2),'currency'=>$item['currency']]:null;
  $offers[]=[
   'seller_id'=>(string)($offer['SellerId']??''),
   'is_own_offer'=>is_bool($offer['MyOffer']??null)?$offer['MyOffer']:null,
   'is_buybox_winner'=>($offer['IsBuyBoxWinner']??false)===true,
   'is_featured_merchant'=>($offer['IsFeaturedMerchant']??false)===true,
   'is_fba'=>is_bool($offer['IsFulfilledByAmazon']??null)?$offer['IsFulfilledByAmazon']:null,
   'condition'=>(string)($offer['SubCondition']??''),
   'item_price'=>$item,'shipping'=>$ship,'total'=>$total,
   'seller_feedback'=>is_array($offer['SellerFeedbackRating']??null)?[
    'positive_percent'=>isset($offer['SellerFeedbackRating']['SellerPositiveFeedbackRating'])?
     (float)$offer['SellerFeedbackRating']['SellerPositiveFeedbackRating']:null,
    'feedback_count'=>isset($offer['SellerFeedbackRating']['FeedbackCount'])?
     (int)$offer['SellerFeedbackRating']['FeedbackCount']:null
   ]:null,
   // Amazon ShippingTime describes dispatch/handling, NOT guaranteed arrival.
   'shipping_time'=>is_array($offer['ShippingTime']??null)?[
    'min_hours'=>isset($offer['ShippingTime']['MinimumHours'])?(int)$offer['ShippingTime']['MinimumHours']:null,
    'max_hours'=>isset($offer['ShippingTime']['MaximumHours'])?(int)$offer['ShippingTime']['MaximumHours']:null,
    'availability'=>(string)($offer['ShippingTime']['AvailabilityType']??''),
    'available_date'=>(string)($offer['ShippingTime']['AvailableDate']??'')
   ]:null,
   'ships_from'=>is_array($offer['ShipsFrom']??null)?[
    'country'=>(string)($offer['ShipsFrom']['Country']??''),
    'state'=>(string)($offer['ShipsFrom']['State']??'')
   ]:null,
   'prime'=>isset($offer['PrimeInformation']['IsPrime'])?
    ($offer['PrimeInformation']['IsPrime']===true):null
  ];
 }
 $s=is_array($payload['Summary']??null)?$payload['Summary']:[];
 send(200,[
  'ok'=>true,'asin'=>$asin,'marketplace_id'=>$marketplace,'source'=>'amazon_live',
  'retrieved_at_utc'=>gmdate('c'),
  'total_offer_count'=>(int)($s['TotalOfferCount']??count($offers)),
  'returned_offer_count'=>count($offers),
  'buybox_prices'=>summaryPrices(is_array($s['BuyBoxPrices']??null)?$s['BuyBoxPrices']:[]),
  'lowest_prices'=>summaryPrices(is_array($s['LowestPrices']??null)?$s['LowestPrices']:[]),
  'offers'=>$offers
 ]);
}catch(Throwable $e){
 error_log('HW24 Amazon pricing API ['.get_class($e).']: '.$e->getMessage());
 send(503,['ok'=>false,'error'=>'amazon_pricing_unavailable']);
}
