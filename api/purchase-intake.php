<?php
declare(strict_types=1);
require_once __DIR__.'/purchase-intake-domain.php';
/**
 * HW24 purchase intake V1. OPT-IN ADDITIVE TABLES ONLY.
 * Reuses the authenticated legacy scan batch and its idempotent scan endpoint.
 * NO JTL/WMS writes, catalog API calls, paid AI calls or file uploads.
 * Requires operator-reviewed platform/ops/hw24-purchase-checklist-v1-manual-migration.sql.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function purchaseReply(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    exit;
}
function purchaseText(mixed $value, int $max, string $field): string {
    if (!is_string($value) || strlen($value)>$max*4 ||
        (function_exists('mb_strlen') ? mb_strlen($value,'UTF-8') : strlen($value))>$max)
        throw new InvalidArgumentException('invalid_'.$field);
    $v=trim($value);
    if ($v==='' || str_contains($v,"\0")) throw new InvalidArgumentException('invalid_'.$field);
    return $v;
}
function purchaseId(mixed $x): int {
    $id=filter_var($x,FILTER_VALIDATE_INT);
    if ($id===false || $id<1) throw new InvalidArgumentException('invalid_id');
    return $id;
}
function purchaseCents(mixed $value): int {
    if(!is_string($value))throw new InvalidArgumentException('invalid_unit_net');
    return hw24PurchaseUnitCents($value);
}
function purchaseHeader(PDO $pdo,int $id,string $actor,bool $lock=false): array {
    $sql='SELECT p.id,p.created_by,p.contact_id,p.batch_id,p.purchase_code,p.status,
           c.contact_name,c.supplier_label,c.code_prefix
          FROM hw24_purchase_headers p
          JOIN hw24_purchase_contacts c ON c.id=p.contact_id AND c.created_by=p.created_by
          WHERE p.id=? AND p.created_by=?';
    if($lock)$sql.=' FOR UPDATE';
    $s=$pdo->prepare($sql);$s->execute([$id,$actor]);$r=$s->fetch();
    if(!$r)throw new RuntimeException('purchase_not_found',404);
    return $r;
}
/** Reuse the existing shared catalog evidence for scanned EANs; no provider call. */
function purchaseArchivedCatalog(PDO $pdo,int $batchId): array {
    // The current DB credential may not yet have SELECT on both archive
    // tables. If so, callers can still scan and price items normally.
    $query=$pdo->prepare('SELECT s.searched_ean,s.id AS snapshot_id,s.fetched_at_utc,
           s.item_count,COUNT(c.asin) AS stored_item_count,
           MAX(c.asin) AS asin,MAX(c.item_name) AS item_name,
           MAX(c.brand) AS brand,MAX(c.manufacturer) AS manufacturer
         FROM (SELECT DISTINCT ean FROM hw24_scan_items
               WHERE batch_id=? AND review_state<>"removed") scan
         JOIN hw24_amazon_catalog_snapshots s ON s.id=(
           SELECT MAX(a.id) FROM hw24_amazon_catalog_snapshots a
           WHERE a.searched_ean=scan.ean AND a.marketplace_id=?
             AND a.response_status=200)
         LEFT JOIN hw24_amazon_catalog_items c
           ON c.snapshot_id=s.id AND c.marketplace_id=s.marketplace_id
             AND c.searched_ean=s.searched_ean
         GROUP BY s.searched_ean,s.id,s.fetched_at_utc,s.item_count');
    $query->execute([$batchId,'A1PA6795UKMFR9']);
    $found=[];
    foreach($query->fetchAll() as $row){
        $found[(string)$row['searched_ean']]=hw24PurchaseArchiveCandidate($row);
    }
    return $found;
}

function purchaseDetails(PDO $pdo,int $id,string $actor): array {
    $p=purchaseHeader($pdo,$id,$actor);
    // One-to-one article ID match ONLY, never take an arbitrary first EAN
    // when multiple JTL articles share a barcode. Existing mirror is a
    // possibly stale snapshot; always disclose its observation date.
    $meta=$pdo->query("SELECT source_id,last_completed_utc,
       TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
       FROM hw24_jtl_sync_meta WHERE status='ready'
       ORDER BY last_completed_utc DESC LIMIT 1")->fetch();
    $sourceId=$meta['source_id']??null;
    $s=$pdo->prepare('SELECT i.id AS scan_item_id,i.ean,i.scan_count,i.resolution,i.artikelnummer,
       i.artikelname,i.review_state,i.last_scanned_utc,i.draft_json,
       j.han AS jtl_han,j.hersteller AS jtl_manufacturer,
       j.artikelname AS jtl_article_name,j.artikelnummer AS jtl_article_number,
       c.unit_net_cents,c.vat_percent,c.updated_at_utc AS cost_updated_at
       FROM hw24_scan_items i LEFT JOIN hw24_purchase_costs c
       ON c.purchase_id=? AND c.scan_item_id=i.id
       LEFT JOIN hw24_jtl_articles j ON j.source_id=? AND j.jtl_article_id=i.jtl_article_id
       WHERE i.batch_id=? ORDER BY i.id ASC LIMIT 3001');
    $s->execute([$id,$sourceId,(int)$p['batch_id']]);$rows=$s->fetchAll();
    if(count($rows)>3000)throw new RuntimeException('too_many_scan_positions',409);
    $catalogArchiveAvailable=true;
    try {
        $archivedCatalog=purchaseArchivedCatalog($pdo,(int)$p['batch_id']);
    } catch (PDOException $e) {
        $catalogArchiveAvailable=false;
        $archivedCatalog=[];
        error_log('HW24 purchase catalog archive read unavailable: '.$e->getCode());
    }
    $net=0;$gross=0;$totalQty=0;$unpricedQty=0;$activeLines=0;
    foreach($rows as &$row) {
        $draft=json_decode((string)($row['draft_json']??''),true);
        if(!is_array($draft))$draft=[];
        $jtlManufacturer=trim((string)($row['jtl_manufacturer']??''));
        $jtlHan=trim((string)($row['jtl_han']??''));
        $row['manufacturer']=$jtlManufacturer!==''?$jtlManufacturer:
             trim((string)($draft['Hersteller']??''));
        $row['manufacturer_source']=$jtlManufacturer!==''?'jtl_mirror':
            ($row['manufacturer']!==''?'scan_draft':'unavailable');
        $row['han']=$jtlHan!==''?$jtlHan:trim((string)($draft['HAN']??''));
        $row['han_source']=$jtlHan!==''?'jtl_mirror':
             ($row['han']!==''?'scan_draft':'unavailable');
        $row['article_number']=$row['artikelnummer']?:($row['jtl_article_number']??'');
        $row['article_name_display']=$row['artikelname']?:($row['jtl_article_name']??
             ($draft['Artikelname']??''));
        unset($row['draft_json'],$row['jtl_han'],$row['jtl_manufacturer'],
              $row['jtl_article_name'],$row['jtl_article_number']);
        // Keep confirmed JTL and existing scan drafts unchanged; external
        // evidence is always separate, including the explicit review status.
        $row['amazon_candidate']=$catalogArchiveAvailable
            ? ($archivedCatalog[(string)$row['ean']]??hw24PurchaseArchiveCandidate(null))
            : ['status'=>'archive_unavailable'];
        $removed=$row['review_state']==='removed';
        $qty=$removed?0:(int)$row['scan_count'];
        if($qty>0){$activeLines++;$totalQty+=$qty;}
        $row['quantity']=$qty;
        $row['unit_net_cents']=$row['unit_net_cents']===null?null:(int)$row['unit_net_cents'];
        $row['vat_percent']=$row['vat_percent']===null?null:(int)$row['vat_percent'];
        if($row['unit_net_cents']===null||$row['vat_percent']===null){
            if($qty>0)$unpricedQty+=$qty;
            $row['line_net_cents']=null;$row['line_gross_cents']=null;
            continue;
        }
        $line=hw24PurchaseLineCents($qty,$row['unit_net_cents'],$row['vat_percent']);
        $row['line_net_cents']=$line['net_cents'];
        $row['line_gross_cents']=$line['gross_cents'];
        $net+=$line['net_cents'];$gross+=$line['gross_cents'];
    }
    unset($row);
    return ['ok'=>true,'purchase'=>[
      'id'=>(int)$p['id'],'batch_id'=>(int)$p['batch_id'],
      'code'=>$p['purchase_code'],'supplier'=>$p['supplier_label'],
      'contact'=>$p['contact_name'],'status'=>$p['status']
    ],'items'=>$rows,'totals'=>[
      'unique_products'=>$activeLines,'total_quantity'=>$totalQty,
      'unpriced_quantity'=>$unpricedQty,'net_cents'=>$net,
      'gross_cents'=>$gross,'complete'=>$unpricedQty===0
    ],'source'=>'existing_hw24_scanliste',
       'catalog_archive_available'=>$catalogArchiveAvailable,
       'jtl_mirror'=>[
          'snapshot_at_utc'=>$meta['last_completed_utc']??null,
          'age_minutes'=>!isset($meta['age_minutes'])?null:(int)$meta['age_minutes'],
          'is_fresh'=>$meta && (int)$meta['age_minutes']>=0 && (int)$meta['age_minutes']<=45
       ]];
}

$actor=trim((string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??''));
if($actor===''||strlen($actor)>180)purchaseReply(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
    purchaseReply(403,['ok'=>false,'error'=>'https_required']);
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,['GET','POST'],true))purchaseReply(405,['ok'=>false,'error'=>'method_not_allowed']);
$input=[];
if($method==='POST') {
    $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
    $targetUrl='https://'.(string)($_SERVER['HTTP_HOST']??'');
    $originParts=parse_url($origin);
    $targetParts=parse_url($targetUrl);
    if(!is_array($originParts)||!is_array($targetParts)||
       ($originParts['scheme']??'')!=='https'||
       strtolower((string)($originParts['host']??''))!==strtolower((string)($targetParts['host']??''))||
       (int)($originParts['port']??443)!==(int)($targetParts['port']??443))
        purchaseReply(403,['ok'=>false,'error'=>'origin_rejected']);
    if(($_SERVER['HTTP_X_HW24_APPROVAL']??'')!=='confirmed')
        purchaseReply(403,['ok'=>false,'error'=>'approval_header_required']);
    if(!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json'))
        purchaseReply(415,['ok'=>false,'error'=>'json_required']);
    if((int)($_SERVER['CONTENT_LENGTH']??0)>4096)
        purchaseReply(413,['ok'=>false,'error'=>'too_large']);
    $rawBody=file_get_contents('php://input',false,null,0,4097);
    if(!is_string($rawBody)||strlen($rawBody)>4096)
        purchaseReply(413,['ok'=>false,'error'=>'too_large']);
    try{$input=json_decode($rawBody,true,16,JSON_THROW_ON_ERROR);}
    catch(Throwable $e){purchaseReply(400,['ok'=>false,'error'=>'invalid_json']);}
    if(!is_array($input))purchaseReply(400,['ok'=>false,'error'=>'invalid_body']);
}
$op=(string)($method==='GET'?($_GET['op']??'contacts'):($input['op']??''));
$config=dirname(__DIR__,2).'/hw24_private/scanlist-db.php';
if(!is_file($config))purchaseReply(503,['ok'=>false,'error'=>'scanlist_database_not_configured']);
try {
    $cfg=require $config;
    if(!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg))
        throw new RuntimeException('scanlist_database_not_configured',503);
    $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
        $cfg['user'],$cfg['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5
    ]);
    if($op==='contacts'&&$method==='GET'){
        $s=$pdo->prepare('SELECT id,supplier_label,contact_name,code_prefix,jtl_supplier_id,jtl_contact_id
                          FROM hw24_purchase_contacts WHERE created_by=? AND active=1 ORDER BY contact_name LIMIT 500');
        $s->execute([$actor]);
        purchaseReply(200,['ok'=>true,'contacts'=>$s->fetchAll(),'jtl_contact_sync'=>'not_verified']);
    }
    if($op==='purchases'&&$method==='GET'){
        $s=$pdo->prepare('SELECT p.id,p.purchase_code AS code,p.batch_id,p.status,p.created_at_utc,
                          c.supplier_label AS supplier,c.contact_name AS contact
                          FROM hw24_purchase_headers p JOIN hw24_purchase_contacts c
                          ON c.id=p.contact_id AND c.created_by=p.created_by
                          WHERE p.created_by=? ORDER BY p.id DESC LIMIT 100');
        $s->execute([$actor]);
        purchaseReply(200,['ok'=>true,'purchases'=>$s->fetchAll()]);
    }
    if($op==='detail'&&$method==='GET'){
        purchaseReply(200,purchaseDetails($pdo,purchaseId($_GET['purchase_id']??null),$actor));
    }
    if($op==='ai_costs'&&$method==='GET'){
        $purchaseId=purchaseId($_GET['purchase_id']??null);
        $p=purchaseHeader($pdo,$purchaseId,$actor);
        // Costs are from previous, actor-scoped OpenAI archive requests
        // associated solely by EAN. They are NOT purchases' material costs.
        // MariaDB aggregate uses COUNT() and SUM() on persisted usage values.
        $q=$pdo->prepare('SELECT COUNT(*) AS calls,
              COALESCE(SUM(r.usd_cost),0) AS usd_total,
              COALESCE(SUM(r.tokens_input),0) AS tokens_input,
              COALESCE(SUM(r.tokens_cached),0) AS tokens_cached,
              COALESCE(SUM(r.tokens_output),0) AS tokens_output
              FROM hw24_ai_reviews r INNER JOIN hw24_scan_items i
                ON i.ean=r.ean AND i.batch_id=? AND i.review_state<>"removed"
              WHERE 1=1');
        $q->execute([(int)$p['batch_id']]);$total=$q->fetch();
        // Cursor pagination ensures every archived AI request can be read,
        // without returning thousands of analysis records in one response.
        $beforeRaw=$_GET['before_id']??null;
        $before=null;
        if($beforeRaw!==null){
            if(!is_string($beforeRaw)||!preg_match('/^[1-9][0-9]{0,17}$/D',$beforeRaw))
                throw new InvalidArgumentException('invalid_cursor');
            $before=filter_var($beforeRaw,FILTER_VALIDATE_INT);
            if($before===false||$before<1)
                throw new InvalidArgumentException('invalid_cursor');
        }
        $sql='SELECT r.id AS review_id,r.ean,r.article_name,r.model,
                r.ai_status AS status,r.usd_cost,r.tokens_input,r.tokens_cached,
                r.tokens_output,r.created_at_utc
            FROM hw24_ai_reviews r INNER JOIN hw24_scan_items i
              ON i.ean=r.ean AND i.batch_id=? AND i.review_state<>"removed"
            WHERE 1=1';
        $params=[(int)$p['batch_id']];
        if($before!==null){$sql.=' AND r.id<?';$params[]=$before;}
        $sql.=' ORDER BY r.id DESC LIMIT 51';
        $q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();
        $hasMore=count($rows)>50;
        if($hasMore)$rows=array_slice($rows,0,50);
        $next=$hasMore&&$rows?(int)end($rows)['review_id']:null;
        purchaseReply(200,['ok'=>true,'purchase_id'=>$purchaseId,
          'source'=>'hw24_ai_reviews','scope'=>'shared_workspace_same_ean_historical',
          'totals'=>[
            'calls'=>(int)$total['calls'],
            'usd_total'=>(string)$total['usd_total'],
            'tokens_input'=>(int)$total['tokens_input'],
            'tokens_cached'=>(int)$total['tokens_cached'],
            'tokens_output'=>(int)$total['tokens_output']
          ],'reviews'=>$rows,'limit'=>50,'has_more'=>$hasMore,
          'next_before_id'=>$next,
          'note'=>'Gemeinsame historische KI-Kosten zu EANs aller HW24-Autoren; nicht zwangsläufig während dieses Einkaufs entstanden oder ihm finanziell zuzurechnen.']);
    }
    if($op==='article_search'&&$method==='GET'){
        // Read-only catalog search over the EXISTING last completed JTL
        // mirror; never expose an empty/stale search as proof of no JTL item.
        $needle=trim((string)($_GET['q']??''));
        $maker=trim((string)($_GET['manufacturer']??''));
        if((strlen($needle)>120)||(strlen($maker)>120)||
           ($needle!==''&&mb_strlen($needle,'UTF-8')<2)||
           ($maker!==''&&mb_strlen($maker,'UTF-8')<2)||
           ($needle===''&&$maker===''))
            throw new InvalidArgumentException('search_requires_at_least_two_characters');
        $minRaw=trim((string)($_GET['price_min']??''));
        $maxRaw=trim((string)($_GET['price_max']??''));
        $min=$minRaw===''?null:purchaseCents($minRaw);
        $max=$maxRaw===''?null:purchaseCents($maxRaw);
        if($min!==null&&$max!==null&&$min>$max)
            throw new InvalidArgumentException('invalid_price_range');
        $meta=$pdo->query("SELECT source_id,last_completed_utc,last_row_count,
            TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
            FROM hw24_jtl_sync_meta WHERE status='ready'
            ORDER BY last_completed_utc DESC LIMIT 1")->fetch();
        if(!$meta)purchaseReply(200,['ok'=>true,'jtl_status'=>'not_connected',
             'matches'=>[],'count'=>0,'snapshot_at_utc'=>null]);
        $clauses=['source_id=?'];$params=[$meta['source_id']];
        if($needle!==''){
            $clauses[]='(LOCATE(?,artikelnummer)>0 OR LOCATE(?,ean)>0 OR
                 LOCATE(?,han)>0 OR LOCATE(?,artikelname)>0 OR
                 LOCATE(?,hersteller)>0)';
            array_push($params,$needle,$needle,$needle,$needle,$needle);
        }
        if($maker!==''){
            $clauses[]='LOCATE(?,hersteller)>0';
            $params[]=$maker;
        }
        if($min!==null){$clauses[]='netto_ek>=?';$params[]=number_format($min/100,2,'.','');}
        if($max!==null){$clauses[]='netto_ek<=?';$params[]=number_format($max/100,2,'.','');}
        $sql='SELECT jtl_article_id,artikelnummer,ean,han,artikelname,hersteller,
           netto_ek,netto_vk,synced_at_utc
           FROM hw24_jtl_articles WHERE '.implode(' AND ',$clauses).'
           ORDER BY artikelname,jtl_article_id LIMIT 61';
        $stmt=$pdo->prepare($sql);$stmt->execute($params);
        $results=$stmt->fetchAll();
        $truncated=count($results)>60;
        if($truncated)$results=array_slice($results,0,60);
        $age=$meta['age_minutes']===null?null:(int)$meta['age_minutes'];
        $fresh=$age!==null&&$age>=0&&$age<=45;
        purchaseReply(200,['ok'=>true,'jtl_status'=>$fresh?'current_snapshot':'stale',
          'snapshot_at_utc'=>$meta['last_completed_utc'],'age_minutes'=>$age,
          'source'=>'hw24_jtl_articles','price_context'=>'jtl_net_purchase_price_snapshot_not_lot_cost',
          'match_context'=>'matches_in_last_completed_mirror_not_global_proof',
          'count'=>count($results),'truncated'=>$truncated,'matches'=>$results]);
    }
    if($op==='contact_create'&&$method==='POST'){
        $supplier=purchaseText($input['supplier']??null,180,'supplier');
        $contact=purchaseText($input['contact']??null,180,'contact');
        $prefix=strtoupper(purchaseText($input['prefix']??null,8,'prefix'));
        if(!preg_match('/^[A-Z0-9]{2,8}$/D',$prefix))
            throw new InvalidArgumentException('invalid_prefix');
        // The operator must explicitly confirm the last LEGACY number for
        // this contact in the current Berlin-local month. Never silently
        // restart at -1 when historical ML/AB purchase IDs may exist.
        $last=$input['last_used_this_month']??null;
        if(!is_int($last)||$last<0||$last>999998)
            throw new InvalidArgumentException('last_used_purchase_number_required');
        $month=(new DateTimeImmutable('now',new DateTimeZone('Europe/Berlin')))->format('Ym');
        // JTL IDs remain null until their actual source has been verified.
        $pdo->beginTransaction();
        $s=$pdo->prepare('INSERT INTO hw24_purchase_contacts
           (created_by,supplier_label,contact_name,code_prefix) VALUES (?,?,?,?)');
        $s->execute([$actor,$supplier,$contact,$prefix]);
        $contactId=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO hw24_purchase_sequences
           (created_by,contact_id,year_month,next_number) VALUES (?,?,?,?)')
            ->execute([$actor,$contactId,$month,$last+1]);
        $pdo->commit();
        purchaseReply(201,['ok'=>true,'contact_id'=>$contactId,'prefix'=>$prefix,
                       'first_code'=>hw24PurchaseCode($prefix,$month,$last+1),
                       'jtl_contact_sync'=>'not_verified']);
    }
    if($op==='purchase_create'&&$method==='POST'){
        $contactId=purchaseId($input['contact_id']??null);
        $pdo->beginTransaction();
        $s=$pdo->prepare('SELECT id,contact_name,code_prefix FROM hw24_purchase_contacts
                          WHERE id=? AND created_by=? AND active=1 FOR UPDATE');
        $s->execute([$contactId,$actor]);$contact=$s->fetch();
        if(!$contact)throw new RuntimeException('contact_not_found',404);
        $month=(new DateTimeImmutable('now',new DateTimeZone('Europe/Berlin')))->format('Ym');
        $insertSeq=$pdo->prepare('INSERT IGNORE INTO hw24_purchase_sequences
                                  (created_by,contact_id,year_month,next_number) VALUES (?,?,?,1)');
        $insertSeq->execute([$actor,$contactId,$month]);
        $seq=$pdo->prepare('SELECT next_number FROM hw24_purchase_sequences
                             WHERE created_by=? AND contact_id=? AND year_month=? FOR UPDATE');
        $seq->execute([$actor,$contactId,$month]);$num=(int)$seq->fetchColumn();
        if($num<1||$num>999999)throw new RuntimeException('purchase_sequence_exhausted',409);
        $pdo->prepare('UPDATE hw24_purchase_sequences SET next_number=? 
          WHERE created_by=? AND contact_id=? AND year_month=?')
          ->execute([$num+1,$actor,$contactId,$month]);
        $code=hw24PurchaseCode($contact['code_prefix'],$month,$num);
        $label='Einkauf '.$code.' · '.$contact['contact_name'];
        $pdo->prepare('INSERT INTO hw24_scan_batches(label,created_by) VALUES (?,?)')
            ->execute([mb_substr($label,0,160),$actor]);
        $batchId=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO hw24_purchase_headers
           (created_by,contact_id,batch_id,purchase_code) VALUES (?,?,?,?)')
           ->execute([$actor,$contactId,$batchId,$code]);
        $purchaseId=(int)$pdo->lastInsertId();
        $pdo->commit();
        purchaseReply(201,['ok'=>true,'purchase_id'=>$purchaseId,
               'batch_id'=>$batchId,'code'=>$code]);
    }
    if($op==='price_set'&&$method==='POST'){
        $purchaseId=purchaseId($input['purchase_id']??null);
        $itemId=purchaseId($input['scan_item_id']??null);
        $cents=purchaseCents($input['unit_net']??null);
        $vat=$input['vat_percent']??null;
        if(!is_int($vat)||!in_array($vat,[0,7,19],true))
            throw new InvalidArgumentException('invalid_vat_rate');
        $expected=$input['expected_cost_updated_at']??null;
        if($expected!==null && (!is_string($expected)||strlen($expected)>32))
            throw new InvalidArgumentException('invalid_expected_revision');
        $pdo->beginTransaction();
        $purchase=purchaseHeader($pdo,$purchaseId,$actor,true);
        if($purchase['status']!=='open')throw new RuntimeException('purchase_closed',409);
        $s=$pdo->prepare('SELECT id,review_state FROM hw24_scan_items WHERE batch_id=? AND id=? FOR UPDATE');
        $s->execute([(int)$purchase['batch_id'],$itemId]);$item=$s->fetch();
        if(!$item||$item['review_state']==='removed')throw new RuntimeException('scan_item_not_found',404);
        $s=$pdo->prepare('SELECT unit_net_cents,vat_percent,updated_at_utc
           FROM hw24_purchase_costs WHERE purchase_id=? AND scan_item_id=? FOR UPDATE');
        $s->execute([$purchaseId,$itemId]);$before=$s->fetch();
        // First-write revision must be null; subsequent writes carry the exact
        // last-change timestamp. If changed concurrently, force a reload.
        $actual=$before?(string)$before['updated_at_utc']:null;
        if($expected!==$actual)throw new RuntimeException('purchase_price_revision_conflict',409);
        if($before){
            $pdo->prepare('UPDATE hw24_purchase_costs
               SET unit_net_cents=?,vat_percent=?,last_changed_by=?,updated_at_utc=UTC_TIMESTAMP(6)
               WHERE purchase_id=? AND scan_item_id=?')
               ->execute([$cents,$vat,$actor,$purchaseId,$itemId]);
        }else{
            $pdo->prepare('INSERT INTO hw24_purchase_costs
               (purchase_id,scan_item_id,unit_net_cents,vat_percent,last_changed_by)
               VALUES (?,?,?,?,?)')->execute([$purchaseId,$itemId,$cents,$vat,$actor]);
        }
        $pdo->prepare('INSERT INTO hw24_purchase_cost_audit
           (purchase_id,scan_item_id,before_unit_net_cents,after_unit_net_cents,
            before_vat_percent,after_vat_percent,changed_by) VALUES (?,?,?,?,?,?,?)')
            ->execute([$purchaseId,$itemId,$before?$before['unit_net_cents']:null,
                $cents,$before?$before['vat_percent']:null,$vat,$actor]);
        $pdo->commit();
        purchaseReply(200,['ok'=>true,'scan_item_id'=>$itemId,'unit_net_cents'=>$cents,'vat_percent'=>$vat]);
    }
    purchaseReply(404,['ok'=>false,'error'=>'unknown_operation']);
} catch(InvalidArgumentException $e) {
    if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
    purchaseReply(400,['ok'=>false,'error'=>$e->getMessage()]);
} catch(PDOException $e) {
    if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
    error_log('HW24 purchase intake database error: '.$e->getCode());
    if($e->getCode()==='23000')purchaseReply(409,['ok'=>false,'error'=>'purchase_prefix_or_record_duplicate']);
    purchaseReply(503,['ok'=>false,'error'=>'purchase_tables_or_permissions_unavailable']);
} catch(RuntimeException $e) {
    if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
    $code=$e->getCode();
    purchaseReply(in_array($code,[404,409,503],true)?$code:503,
        ['ok'=>false,'error'=>$e->getMessage()]);
} catch(Throwable $e) {
    if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
    error_log('HW24 purchase unexpected error: '.get_class($e));
    purchaseReply(503,['ok'=>false,'error'=>'purchase_service_unavailable']);
}
