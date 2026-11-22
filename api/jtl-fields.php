<?php
declare(strict_types=1);
/*
 * HW24: all currently mirrored JTL article source fields, read-only.
 *
 * Source of truth is the last complete Windows SQL snapshot:
 * - dbo.tArtikel: all original columns in hw24_jtl_articles.raw_json
 * - dbo.tArtikelBeschreibung: all rows in beschreibungen_json
 * - verified Windows supplementary rows in raw.HW24_RelatedSources
 * - optional HW24_* mapped values with explicit provenance
 *
 * This endpoint does not discover new SQL Server tables or alter DB schema.
 * It NEVER exposes raw_json wholesale and NEVER updates JTL, WMS or MariaDB.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function answer(int $status, array $result): never {
    http_response_code($status);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    answer(405, ['ok'=>false, 'error'=>'method_not_allowed']);
}
$actor=trim((string)($_SERVER['PHP_AUTH_USER'] ?? $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? ''));
if ($actor === '' || strlen($actor)>180) answer(403,['ok'=>false,'error'=>'authentication_required']);
if (($_SERVER['HTTPS'] ?? '')!=='on' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')!=='https') {
    answer(403,['ok'=>false,'error'=>'https_required']);
}
$id=trim((string)($_GET['article_id'] ?? ''));
$ean=trim((string)($_GET['ean'] ?? ''));
if (!preg_match('/^[0-9]{1,12}$/D',$id) ||
    !preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)) {
    answer(400,['ok'=>false,'error'=>'invalid_article_identifier']);
}
$cfgPath=dirname(__DIR__,2).'/hw24_private/db.php';
if (!is_file($cfgPath)) answer(503,['ok'=>false,'error'=>'mirror_not_configured']);

// Never propagate a password, token, authorization header or other credential
// accidentally introduced into an optional Windows source row.
function allowedField(string $name): bool {
    return strlen($name)>0 && strlen($name)<=128 &&
        preg_match('/^[\pL\pN_.-]+$/uD',$name)===1 &&
        !preg_match('/(?:password|passwort|secret|token|api.?key|auth|credential|cookie|session|private.?key|connection.?string)/i',$name);
}
function visibleValue(mixed $input): array {
    if ($input===null) return ['value'=>null,'truncated'=>false];
    if (!is_scalar($input)) return ['value'=>null,'truncated'=>false];
    $text=is_bool($input)?($input?'1':'0'):(string)$input;
    // An original large value stays available in the DB, only the browser
    // preview is capped to avoid excessive payloads/slow mobile rendering.
    $max=8192;
    $truncated=strlen($text)>$max;
    return ['value'=>$truncated?substr($text,0,$max):$text,'truncated'=>$truncated];
}
function rowsFromSource(array $rows, string $source, int $limit=24): array {
    $fields=[];
    $maxRows=min(count($rows),$limit);
    for($i=0;$i<$maxRows;$i++){
        $row=$rows[$i];
        if (!is_array($row)) continue;
        foreach($row as $name=>$value){
            if (!is_string($name) || !allowedField($name) || !is_scalar($value) && $value!==null) continue;
            $fields[]=array_merge([
                'source'=>$source, 'row'=>$i+1, 'field'=>$name
            ],visibleValue($value));
        }
    }
    return $fields;
}
try {
    $cfg=require $cfgPath;
    if (!is_array($cfg) || empty($cfg['user']) || !array_key_exists('password',$cfg)) {
        answer(503,['ok'=>false,'error'=>'mirror_not_configured']);
    }
    $db=new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
        (string)$cfg['user'],(string)$cfg['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]
    );
    $ready=$db->query("SELECT source_id,last_completed_utc,
            TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
        FROM hw24_jtl_sync_meta
        WHERE status='ready' AND last_completed_utc IS NOT NULL
        ORDER BY last_completed_utc DESC LIMIT 1")->fetch();
    if (!$ready || $ready['age_minutes']===null || (int)$ready['age_minutes']<0 ||
        (int)$ready['age_minutes']>45) {
        answer(200,['ok'=>true,'status'=>'stale','fields'=>[],
            'note'=>'Kein vollständiger, höchstens 45 Minuten alter JTL-Snapshot verfügbar.']);
    }
    $q=$db->prepare('SELECT jtl_article_id,artikelnummer,ean,asin,netto_vk,amazon_vk,ebay_preis,
            raw_json,beschreibungen_json
        FROM hw24_jtl_articles
        WHERE source_id=? AND jtl_article_id=? AND ean=? LIMIT 1');
    $q->execute([(string)$ready['source_id'],$id,$ean]);
    $article=$q->fetch();
    if (!$article) answer(404,['ok'=>false,'error'=>'article_not_in_current_snapshot']);
    $raw=json_decode((string)$article['raw_json'],true);
    if (!is_array($raw)) answer(503,['ok'=>false,'error'=>'article_raw_unavailable']);
    $descriptions=json_decode((string)($article['beschreibungen_json'] ?? ''),true);
    $descriptions=is_array($descriptions)?$descriptions:[];

    // Present every original tArtikel scalar as a separate observed field.
    // Exclude HW24_* synthetic keys here; display verified keys separately.
    $fields=[];
    $original=[];
    foreach($raw as $name=>$value){
        if (!is_string($name) || str_starts_with($name,'HW24_') ||
            !allowedField($name) || (!is_scalar($value) && $value!==null)) continue;
        $original[$name]=$value;
    }
    $fields=array_merge($fields,rowsFromSource([$original],'dbo.tArtikel',1));
    $fields=array_merge($fields,rowsFromSource($descriptions,'dbo.tArtikelBeschreibung',24));

    $mapped=[
        'HW24_VKBrutto'=>'JTL VK brutto (nur bei geprüfter Quelle)',
        'HW24_VKBruttoQuelle'=>'Originalquelle JTL VK brutto',
        'HW24_Waehrung'=>'Währung (quellengeprüft)',
        'HW24_Artikeltyp'=>'Artikeltyp (quellengeprüft)',
        'HW24_WarengruppeName'=>'Warengruppe (aufgelöst)'
    ];
    foreach ($mapped as $key=>$display){
        if (!array_key_exists($key,$raw) || (!is_scalar($raw[$key]) && $raw[$key]!==null)) continue;
        $fields[]=array_merge([
            'source'=>'HW24.WindowsSync.verified', 'row'=>1,
            'field'=>$key, 'label'=>$display
        ],visibleValue($raw[$key]));
    }

    $related=$raw['HW24_RelatedSources'] ?? [];
    $sourceSummary=[];
    if (is_array($related)){
        foreach($related as $sourceName=>$group){
            if (!is_string($sourceName) || !allowedField($sourceName) || !is_array($group)) continue;
            $rows=$group['rows']??[];
            if (!is_array($rows)) continue;
            $safeRows=array_values(array_filter($rows,'is_array'));
            $name='JTL.Zusatzquelle.'.$sourceName;
            $sourceSummary[]=[
                'source'=>$name,
                'observed_rows'=>count($safeRows),
                'truncated'=>!empty($group['truncated'])||count($safeRows)>24
            ];
            $fields=array_merge($fields,rowsFromSource($safeRows,$name,24));
        }
    }
    // Preserve channel meanings. A generic JTL tArtikel price is NOT a
    // verified LIVE marketplace offer and may not be per eBay account.
    $priceValue=static function($value): ?string {
        if (!is_scalar($value) || trim((string)$value)==='') return null;
        $s=trim((string)$value);
        return preg_match('/^-?[0-9]{1,12}(?:\.[0-9]{1,14})?$/D',$s)?$s:null;
    };
    $verifiedGross=$priceValue($raw['HW24_VKBrutto']??null);
    $grossSource=$raw['HW24_VKBruttoQuelle']??null;
    if (!is_string($grossSource)||trim($grossSource)==='')$verifiedGross=null;
    $prices=[
        'jtl_net'=>$priceValue($raw['fVKNetto']??$article['netto_vk']),
        'jtl_net_source'=>'dbo.tArtikel.fVKNetto',
        'jtl_gross'=>$verifiedGross,
        'jtl_gross_source'=>$verifiedGross!==null?substr(trim($grossSource),0,200):null,
        'amazon_jtl_value'=>$priceValue($raw['fAmazonVK']??$article['amazon_vk']),
        'amazon_jtl_source'=>'dbo.tArtikel.fAmazonVK',
        'ebay_jtl_value'=>$priceValue($raw['fEbayPreis']??$article['ebay_preis']),
        'ebay_jtl_source'=>'dbo.tArtikel.fEbayPreis'
    ];
    answer(200,[
        'ok'=>true, 'status'=>'current_snapshot',
        'article_id'=>$article['jtl_article_id'], 'artikelnummer'=>$article['artikelnummer'],
        'ean'=>$article['ean'],'snapshot_at'=>$ready['last_completed_utc'],
        'age_minutes'=>(int)$ready['age_minutes'],
        'fields'=>$fields, 'field_count'=>count($fields),
        'source_summary'=>$sourceSummary,'prices'=>$prices,
        'scope'=>'article_only_read_only',
        'note'=>'Originalfelder nur aus dem JTL-Spiegel; zugehörige Tabellen erscheinen nur, wenn die Windows-Synchronisation sie bereits geliefert hat. Fehlende eBay-Kontopreise und Shopify-Livepreise bleiben ungeklärt.'
    ]);
} catch(Throwable $e) {
    error_log('HW24 JTL source explorer: '.$e->getMessage());
    answer(503,['ok'=>false,'error'=>'source_view_unavailable']);
}
