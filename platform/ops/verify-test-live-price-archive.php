<?php
declare(strict_types=1);

/** Operator-only, two explicit SP-API Pricing reads for the previously confirmed ASIN.
 * No JTL, WMS or paid AI actions. No secrets, raw provider payload or ciphertext printed.
 * Execute from installed TEST platform as its application user, not root.
 */
if(PHP_SAPI!=='cli'||getenv('HW24_CONFIRM_LIVE_PRICE_TEST')!=='two-reads') exit(1);
if(realpath(getcwd())!=='/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform')
    throw new RuntimeException('Expected installed TEST platform');
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AmazonPriceArchiveRuntime as Runtime;
use App\Services\LegacyReadBridge;

function out(string $label,array $value):void {
    echo "\n=== ".$label." ===\n".json_encode($value,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
}
$asin='B01MZALQW6'; // EAN 4021609029274: operator's earlier live diagnostic matched a single unit.
if(!Runtime::enabled()||config('hw24_legacy.amazon_auto_calls')!==false||
    config('hw24_legacy.openai_auto_calls')!==false)
    throw new RuntimeException('Unexpected pilot flags');
$store=Runtime::open();
$heartbeat=dirname(base_path(),2).'/hw24-price-archive-purge.ok';
$ts=is_file($heartbeat)&&!is_link($heartbeat)?trim((string)file_get_contents($heartbeat)):'';
if(!ctype_digit($ts)||(int)$ts<time()-3600||(int)$ts>time()+60)
    throw new RuntimeException('Purge heartbeat missing or stale; no provider call');
$before=$store->latest($asin,2);
out('ARCHIV VORHER',['asin'=>$asin,'count'=>count($before),'ids'=>array_column($before,'id')]);
$bridge=$app->make(LegacyReadBridge::class);
$ids=[];
for($i=1;$i<=2;$i++){
    if($i===2) sleep(15);
    $r=Runtime::fetch($asin,$bridge);
    $a=$r['archive'];$p=$r['response'];$d=$p['data']??[];
    out('LIVE PREISABFRAGE '.$i,['ok'=>$p['ok']??false,'http'=>$p['http_status']??null,
        'source'=>$d['source']??null,'observed_at_utc'=>$d['retrieved_at_utc']??null,
        'error'=>$d['error']??null,'upstream_status'=>$d['upstream_status']??null,
        'archive_state'=>$a['state']??null,'archive_id'=>$a['id']??null,'inserted'=>$a['inserted']??false,
        'history'=>$a['history']??[]]);
    if(($p['ok']??false)!==true||($p['http_status']??null)!==200||($d['source']??null)!=='amazon_live'||
        ($a['state']??null)!=='recorded'||($a['inserted']??false)!==true||!is_int($a['id']??null))
        throw new RuntimeException('Live capture not confirmed; stop without retry');
    $ids[]=$a['id'];
}
// New connection; retrieve from the persistent store independently of response.
$after=Runtime::open()->latest($asin,2);
$readIds=array_column($after,'id');
if(count($after)!==2||$ids[0]===$ids[1]||$readIds!==array_reverse($ids))
    throw new RuntimeException('Two distinct latest observations not independently confirmed');
foreach($after as $i=>$row){
    out($i===0?'NEUE BEOBACHTUNG':'VORHERIGE BEOBACHTUNG',[
        'id'=>$row['id'],'observed_at_utc'=>$row['observed_at_utc'],
        'buybox'=>$row['buybox'],'lowest'=>$row['lowest'],
        'total_offer_count'=>$row['total_offer_count'],'returned_offer_count'=>$row['returned_offer_count'],
        'decrypted_offer_rows'=>count($row['offers'])]);
    if(count($row['offers'])!==$row['returned_offer_count'])
        throw new RuntimeException('Archived offer count mismatch');
}
out('ABSCHLUSS',['result'=>'HW24 LIVE PRICE ARCHIVE PASS','ids'=>$ids,'pricing_calls'=>2,
    'paid_ai_calls'=>0,'jtl_wms_writes'=>0]);
