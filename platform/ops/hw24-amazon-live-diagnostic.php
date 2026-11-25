<?php
declare(strict_types=1);

/**
 * Operator-only diagnostic, run with PHP CLI from platform/ via STDIN or file.
 * No deployment, migration, JTL/WMS writes, paid OpenAI, forced cache bypass,
 * or product/offer mutation. Existing Amazon API can write its normal logs
 * and catalog snapshots. Use one genuine GTIN per execution.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
$ean = trim((string)getenv('HW24_DIAG_EAN'));
if (!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D', $ean) || preg_match('/^0+$/D', $ean)) {
    fwrite(STDERR, "STOP: Gueltige GTIN erforderlich.\n"); exit(1);
}
$sum=0; $weight=3;
for ($i=strlen($ean)-2; $i>=0; $i--, $weight=$weight===3?1:3) $sum+=(int)$ean[$i]*$weight;
if ((10-$sum%10)%10 !== (int)$ean[strlen($ean)-1]) {
    fwrite(STDERR, "STOP: GTIN-Pruefziffer ungueltig.\n"); exit(1);
}
require getcwd().'/vendor/autoload.php';
$app=require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$bridge=$app->make(App\Services\LegacyReadBridge::class);

function report(string $name, array $data): void {
    echo "\n=== ".$name." ===\n".json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n";
}
function status(array $r): array {
    $d=is_array($r['data']??null)?$r['data']:[];
    return ['ok'=>$r['ok']??false,'http'=>$r['http_status']??null,'state'=>$r['state']??null,
        'error'=>$d['error']??null,'upstream_status'=>$d['upstream_status']??null,'stage'=>$d['stage']??null];
}
function archiveInfo(array $r): array {
    return status($r)+['snapshots'=>array_map(static fn($x)=>array_intersect_key($x,array_flip(['id','fetched_at_utc','count'])),array_slice($r['data']['snapshots']??[],0,3))];
}
report('TESTKONTEXT', ['ean'=>$ean,'utc'=>gmdate('c'),'amazon_auto_config'=>config('hw24_legacy.amazon_auto_calls'),'openai_auto_config'=>config('hw24_legacy.openai_auto_calls')]);
$before=$bridge->fetch('amazon.archive',['ean'=>$ean,'limit'=>3]);
report('KATALOGARCHIV VORHER',archiveInfo($before));
$ai=$bridge->fetch('ai.history',['ean'=>$ean,'limit'=>1]);
report('KI ARCHIV NUR LESEN',status($ai)+['scope'=>$ai['data']['scope']??null,'returned_count'=>$ai['data']['count']??null]);

// Existing verified integration and GTIN/pack conflict detector.
$r=App\Services\ScanResearch::research($ean,$bridge);
report('JTL UND AMAZON KATALOG', $r);
$after=$bridge->fetch('amazon.archive',['ean'=>$ean,'limit'=>3]);
report('KATALOGARCHIV NACHHER',archiveInfo($after));
$beforeIds=array_column($before['data']['snapshots']??[],'id');
$afterIds=array_column($after['data']['snapshots']??[],'id');
$id=$r['snapshot_id']??0;
$catalogProof=($r['amazon_ok']??false) && ($r['source']??'')==='live'
    ? (($before['ok']??false)&&($after['ok']??false)&&$id>0&&!in_array($id,$beforeIds,true)&&in_array($id,$afterIds,true)
        ?'live_and_new_snapshot_confirmed':'live_reported_archive_not_independently_confirmed')
    : (($r['amazon_ok']??false)&&($r['source']??'')==='cache'?'cache_only':'not_confirmed');
report('KATALOG NACHWEIS',['result'=>$catalogProof,'snapshot_id'=>$id]);

// Do not treat missing pack evidence as one. Pricing is a separate read.
$matches=array_values(array_filter($r['items']??[],static fn($x)=>($x['gtin_match']??false)===true));
$priceOk=false;
if (($r['amazon_ok']??false) && !($r['more']??true) && count($matches)===1 && ($matches[0]['pack_kind']??'')==='single') {
    $asin=$matches[0]['asin'];
    $p=$bridge->fetch('amazon.pricing',['asin'=>$asin]);
    $data=is_array($p['data']??null)?$p['data']:[];
    $priceOk=($p['ok']??false)&&($data['source']??'')==='amazon_live'&&($data['asin']??'')===$asin;
    report('MARKTPREISE',status($p)+['asin'=>$asin,'source'=>$data['source']??null,'observed_at_utc'=>$data['retrieved_at_utc']??null,
        'buybox'=>$data['buybox_prices']??[],'lowest'=>$data['lowest_prices']??[],
        'offer_count'=>$data['total_offer_count']??null,'returned_count'=>$data['returned_offer_count']??null,
        'live_reported'=>$priceOk]);
    if ($priceOk) {
        $offers=array_slice(is_array($data['offers']??null)?$data['offers']:[],0,20);
        $ids=array_values(array_unique(array_filter(array_column($offers,'seller_id'),static fn($x)=>is_string($x)&&preg_match('/^[A-Z0-9]{5,40}$/D',$x))));
        $names=[];
        if ($ids) {
            $n=$bridge->fetch('amazon.sellers',['ids'=>implode(',',$ids)]);
            $names=is_array($n['data']['names']??null)?$n['data']['names']:[];
            report('HAENDLERNAMEN ARCHIV',status($n)+['requested'=>count($ids),'verified_names'=>count($names)]);
        }
        $rows=[];
        foreach ($offers as $offer) {
            $sid=$offer['seller_id']??'';
            $rows[]=['seller_id'=>$sid,'verified_name'=>$names[$sid]??null,
                'item_price'=>$offer['item_price']??null,'shipping'=>$offer['shipping']??null,
                'total'=>$offer['total']??null,'is_fba'=>$offer['is_fba']??null];
        }
        report('HAENDLER UND PREISE',['offers'=>$rows]);
    }
} else {
    report('MARKTPREISE',['result'=>'skipped','reason'=>'GTIN/Packung nicht eindeutig oder Katalog nicht vollstaendig; keine automatische ASIN-Auswahl']);
}
report('ABSCHLUSS',['catalog_proof'=>$catalogProof,'pricing_live_reported'=>$priceOk,
    'new_paid_ai_calls'=>0,'price_archive_tested'=>false,'jtl_wms_writes'=>0]);
