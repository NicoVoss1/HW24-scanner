<?php
declare(strict_types=1);
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
    if (!is_string($value) || !preg_match('/^(?:0|[1-9][0-9]{0,6})(?:[,.][0-9]{1,2})?$/D',$value))
        throw new InvalidArgumentException('invalid_unit_net');
    $parts=preg_split('/[,.]/',$value);
    return (int)$parts[0]*100+(int)str_pad($parts[1]??'0',2,'0');
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
function purchaseDetails(PDO $pdo,int $id,string $actor): array {
    $p=purchaseHeader($pdo,$id,$actor);
    $s=$pdo->prepare('SELECT i.id AS scan_item_id,i.ean,i.scan_count,i.resolution,i.artikelnummer,
       i.artikelname,i.review_state,i.last_scanned_utc,
       c.unit_net_cents,c.vat_percent,c.updated_at_utc AS cost_updated_at
       FROM hw24_scan_items i LEFT JOIN hw24_purchase_costs c
       ON c.purchase_id=? AND c.scan_item_id=i.id
       WHERE i.batch_id=? ORDER BY i.id ASC LIMIT 3001');
    $s->execute([$id,(int)$p['batch_id']]);$rows=$s->fetchAll();
    if(count($rows)>3000)throw new RuntimeException('too_many_scan_positions',409);
    $net=0;$gross=0;$totalQty=0;$unpricedQty=0;$activeLines=0;
    foreach($rows as &$row) {
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
        $lineNet=$qty*$row['unit_net_cents'];
        // Half-up rounding to cents, done at the line total, not every scan.
        $lineTax=intdiv($lineNet*$row['vat_percent']+50,100);
        $row['line_net_cents']=$lineNet;
        $row['line_gross_cents']=$lineNet+$lineTax;
        $net+=$lineNet;$gross+=$lineNet+$lineTax;
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
    ],'source'=>'existing_hw24_scanliste'];
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
    $host=strtolower((string)parse_url('https://'.($_SERVER['HTTP_HOST']??''),PHP_URL_HOST));
    if(!$origin||parse_url($origin,PHP_URL_SCHEME)!=='https'||
       strtolower((string)parse_url($origin,PHP_URL_HOST))!==$host)
        purchaseReply(403,['ok'=>false,'error'=>'origin_rejected']);
    if(($_SERVER['HTTP_X_HW24_APPROVAL']??'')!=='confirmed')
        purchaseReply(403,['ok'=>false,'error'=>'approval_header_required']);
    if(!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json'))
        purchaseReply(415,['ok'=>false,'error'=>'json_required']);
    if((int)($_SERVER['CONTENT_LENGTH']??0)>4096)
        purchaseReply(413,['ok'=>false,'error'=>'too_large']);
    try{$input=json_decode(file_get_contents('php://input'),true,16,JSON_THROW_ON_ERROR);}
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
    if($op==='contact_create'&&$method==='POST'){
        $supplier=purchaseText($input['supplier']??null,180,'supplier');
        $contact=purchaseText($input['contact']??null,180,'contact');
        $prefix=strtoupper(purchaseText($input['prefix']??null,8,'prefix'));
        if(!preg_match('/^[A-Z0-9]{2,8}$/D',$prefix))
            throw new InvalidArgumentException('invalid_prefix');
        // IDs remain null until their actual JTL source has been independently checked.
        $s=$pdo->prepare('INSERT INTO hw24_purchase_contacts
           (created_by,supplier_label,contact_name,code_prefix) VALUES (?,?,?,?)');
        $s->execute([$actor,$supplier,$contact,$prefix]);
        purchaseReply(201,['ok'=>true,'contact_id'=>(int)$pdo->lastInsertId(),'prefix'=>$prefix,
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
        $code=$contact['code_prefix'].$month.'-'.$num;
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
