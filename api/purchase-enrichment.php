<?php
declare(strict_types=1);
/**
 * Durable, operator-audited Amazon catalog enrichment for purchases.
 * Enqueue is separate from catalog lookup. A browser using the EXISTING
 * api/amazon.php runs leased jobs, then ACK verifies the catalog snapshot
 * in MariaDB before marking completion. No JTL/WMS writes, no OpenAI calls.
 *
 * Queued jobs persist when the browser closes. Without an approved server
 * worker they resume only when the user opens a HW24 purchase tab.
 * Requires manually approved additive purchase schema. No auto migrations.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function enqueueReply(int $http,array $body): never {
    http_response_code($http);
    echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
    exit;
}
function enqueueId(mixed $value): int {
    $id=filter_var($value,FILTER_VALIDATE_INT);
    if($id===false||$id<1)throw new InvalidArgumentException('invalid_id');
    return $id;
}
function enqueueEan(mixed $value): string {
    if(!is_string($value)||!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$value)||
       preg_match('/^0+$/D',$value))throw new InvalidArgumentException('invalid_ean');
    $w=3;$sum=0;
    for($i=strlen($value)-2;$i>=0;$i--){$sum+=(int)$value[$i]*$w;$w=$w===3?1:3;}
    if((10-$sum%10)%10!==(int)$value[strlen($value)-1])
        throw new InvalidArgumentException('invalid_ean_checksum');
    return $value;
}
function enqueueEvent(PDO $db,int $jobId,string $actor,string $ean,string $type,?string $source=null,?string $detail=null): void {
    $db->prepare('INSERT INTO hw24_purchase_amazon_events
      (job_id,created_by,ean,event_type,event_actor,source_kind,detail_code)
      VALUES (?,?,?,?,?,?,?)')
      ->execute([$jobId,$actor,$ean,$type,$actor,$source,$detail]);
}
function enqueueOwnedPurchase(PDO $db,int $purchaseId,string $actor,bool $allowClosed=false): int {
    $s=$db->prepare('SELECT p.batch_id FROM hw24_purchase_headers p
      WHERE p.id=? AND p.created_by=?'.($allowClosed?'':' AND p.status="open"'));
    $s->execute([$purchaseId,$actor]);$batch=$s->fetchColumn();
    if($batch===false)throw new RuntimeException('purchase_not_found_or_closed',404);
    return (int)$batch;
}

$actor=trim((string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??''));
if($actor===''||strlen($actor)>180)enqueueReply(403,['ok'=>false,'error'=>'site_authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
    enqueueReply(403,['ok'=>false,'error'=>'https_required']);
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,['GET','POST'],true))enqueueReply(405,['ok'=>false,'error'=>'method_not_allowed']);
$body=[];
if($method==='POST'){
    $src=parse_url((string)($_SERVER['HTTP_ORIGIN']??''));
    $dst=parse_url('https://'.(string)($_SERVER['HTTP_HOST']??''));
    if(!is_array($src)||!is_array($dst)||($src['scheme']??'')!=='https'||
       strtolower((string)($src['host']??''))!==strtolower((string)($dst['host']??''))||
       (int)($src['port']??443)!==(int)($dst['port']??443))
       enqueueReply(403,['ok'=>false,'error'=>'origin_rejected']);
    if(($_SERVER['HTTP_X_HW24_APPROVAL']??'')!=='confirmed')
       enqueueReply(403,['ok'=>false,'error'=>'approval_header_required']);
    if(!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json'))
       enqueueReply(415,['ok'=>false,'error'=>'json_required']);
    if((int)($_SERVER['CONTENT_LENGTH']??0)>4096)
       enqueueReply(413,['ok'=>false,'error'=>'too_large']);
    $raw=file_get_contents('php://input',false,null,0,4097);
    if(!is_string($raw)||strlen($raw)>4096)
       enqueueReply(413,['ok'=>false,'error'=>'too_large']);
    try{$body=json_decode($raw,true,16,JSON_THROW_ON_ERROR);}
    catch(Throwable $e){enqueueReply(400,['ok'=>false,'error'=>'invalid_json']);}
    if(!is_array($body))enqueueReply(400,['ok'=>false,'error'=>'invalid_body']);
}
$op=(string)($method==='GET'?($_GET['op']??'queue'):($body['op']??''));
$cfgPath=dirname(__DIR__,2).'/hw24_private/scanlist-db.php';
if(!is_file($cfgPath))enqueueReply(503,['ok'=>false,'error'=>'purchase_database_not_configured']);
try{
    $cfg=require $cfgPath;
    if(!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg))
        throw new RuntimeException('purchase_database_not_configured',503);
    $db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
      (string)$cfg['user'],(string)$cfg['password'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
    if($op==='queue'&&$method==='GET'){
        $purchaseId=enqueueId($_GET['purchase_id']??null);
        $batch=enqueueOwnedPurchase($db,$purchaseId,$actor,true);
        $q=$db->prepare('SELECT q.id,q.ean,q.status,q.requested_by,q.requested_at_utc,
          q.attempt_count,q.last_attempt_at_utc,q.last_source,
          q.last_snapshot_id,q.last_catalog_at_utc,q.last_error_code,q.completed_at_utc,
          q.next_attempt_utc
          FROM hw24_purchase_amazon_jobs q
          WHERE EXISTS(SELECT 1 FROM hw24_scan_items i
            WHERE i.batch_id=? AND i.ean=q.ean AND i.review_state<>"removed")
          ORDER BY q.requested_at_utc DESC LIMIT 3000');
        $q->execute([$batch]);$jobs=$q->fetchAll();
        $counts=['queued'=>0,'leased'=>0,'retry'=>0,'completed'=>0,'blocked'=>0,'missing'=>0];
        foreach($jobs as $j)if(isset($counts[$j['status']]))$counts[$j['status']]++;
        $s=$db->prepare('SELECT COUNT(*) FROM hw24_scan_items i
            WHERE i.batch_id=? AND i.review_state<>"removed"
            AND NOT EXISTS(SELECT 1 FROM hw24_purchase_amazon_jobs q
              WHERE q.ean=i.ean)');
        $s->execute([$batch]);$counts['missing']=(int)$s->fetchColumn();
        enqueueReply(200,['ok'=>true,'purchase_id'=>$purchaseId,
          'jobs'=>$jobs,'counts'=>$counts,'scope'=>'shared_workspace','source'=>'hw24_purchase_amazon_jobs',
          'processing'=>'browser_worker_only; durable when tab is closed']);
    }
    if($op==='events'&&$method==='GET'){
        $purchaseId=enqueueId($_GET['purchase_id']??null);
        $batch=enqueueOwnedPurchase($db,$purchaseId,$actor,true);
        $ean=enqueueEan($_GET['ean']??null);
        $s=$db->prepare('SELECT id FROM hw24_scan_items
            WHERE batch_id=? AND ean=? AND review_state<>"removed" LIMIT 1');
        $s->execute([$batch,$ean]);
        if(!$s->fetchColumn())throw new RuntimeException('scan_item_not_found',404);
        // Personal actor stays in the immutable admin audit, not on this
        // general shared-product UI. Authenticated users see source, action,
        // status and UTC dates without exposing coworker account names.
        $s=$db->prepare('SELECT e.event_type,e.source_kind,
            e.detail_code,e.occurred_at_utc
            FROM hw24_purchase_amazon_events e
            WHERE e.ean=?
            ORDER BY e.id DESC LIMIT 50');
        $s->execute([$ean]);
        enqueueReply(200,['ok'=>true,'scope'=>'shared_workspace','ean'=>$ean,
            'events'=>$s->fetchAll()]);
    }
    if($op==='enqueue'&&$method==='POST'){
        $purchaseId=enqueueId($body['purchase_id']??null);
        $ean=enqueueEan($body['ean']??null);
        $batch=enqueueOwnedPurchase($db,$purchaseId,$actor);
        $s=$db->prepare('SELECT id FROM hw24_scan_items
             WHERE batch_id=? AND ean=? AND review_state<>"removed" LIMIT 1');
        $s->execute([$batch,$ean]);
        if(!$s->fetchColumn())throw new RuntimeException('scan_item_not_found',404);
        $db->beginTransaction();
        // Atomic workspace-wide deduplication: every employee reuses the
        // same EAN result. A duplicate request is an audit "linked" event,
        // never a second live Amazon request.
        $ins=$db->prepare('INSERT IGNORE INTO hw24_purchase_amazon_jobs
            (created_by,ean,requested_by) VALUES (?,?,?)');
        $ins->execute([$actor,$ean,$actor]);$created=$ins->rowCount()===1;
        $s=$db->prepare('SELECT id,status FROM hw24_purchase_amazon_jobs
            WHERE ean=? FOR UPDATE');
        $s->execute([$ean]);$job=$s->fetch();
        if(!$job)throw new RuntimeException('shared_queue_job_unavailable',503);
        $jobId=(int)$job['id'];$status=$job['status'];
        enqueueEvent($db,$jobId,$actor,$ean,
            $created?'queued':'linked',null,$created?'scan_or_manual_request':'workspace_result_reused');
        $db->commit();
        enqueueReply(200,['ok'=>true,'scope'=>'shared_workspace',
             'job_id'=>$jobId,'ean'=>$ean,
             'status'=>$status,'new_job'=>$created,'source'=>'purchase_scan']);
    }
    if($op==='enqueue_batch'&&$method==='POST'){
        $purchaseId=enqueueId($body['purchase_id']??null);
        $batch=enqueueOwnedPurchase($db,$purchaseId,$actor);
        $after=$body['after_item_id']??0;
        if(!is_int($after)||$after<0)throw new InvalidArgumentException('invalid_cursor');
        // One bounded transaction per 100 positions. UNIQUE(ean)
        // deduplicates requests across every authenticated employee.
        $s=$db->prepare('SELECT id,ean,resolution FROM hw24_scan_items
             WHERE batch_id=? AND id>? AND review_state<>"removed"
             ORDER BY id ASC LIMIT 100');
        $s->execute([$batch,$after]);$items=$s->fetchAll();
        $created=0;$skipped=0;$last=$after;
        $db->beginTransaction();
        $upsert=$db->prepare('INSERT IGNORE INTO hw24_purchase_amazon_jobs
             (created_by,ean,requested_by) VALUES (?,?,?)');
        $find=$db->prepare('SELECT id FROM hw24_purchase_amazon_jobs WHERE ean=?');
        foreach($items as $row){
            $last=(int)$row['id'];
            if(!in_array($row['resolution'],['new_candidate','unverified'],true)){
                $skipped++;continue;
            }
            $ean=enqueueEan((string)$row['ean']);
            $upsert->execute([$actor,$ean,$actor]);
            if($upsert->rowCount()===1){
                $find->execute([$ean]);$jobId=(int)$find->fetchColumn();
                enqueueEvent($db,$jobId,$actor,$ean,'queued',null,'purchase_batch_request');
                $created++;
            }else $skipped++;
        }
        $db->commit();
        enqueueReply(200,['ok'=>true,'scope'=>'shared_workspace','purchase_id'=>$purchaseId,
           'received'=>count($items),'created'=>$created,'skipped'=>$skipped,
           'last_item_id'=>$last,'has_more'=>count($items)===100]);
    }
    if($op==='lease'&&$method==='POST'){
        // ONE shared workspace throttle prevents API bursts across all
        // simultaneous authenticated users, not merely browser tabs.
        $db->beginTransaction();
        $db->prepare('INSERT IGNORE INTO hw24_purchase_amazon_throttle
            (scope_key,next_allowed_utc) VALUES (?,NULL)')->execute(['shared_workspace']);
        $t=$db->prepare('SELECT next_allowed_utc,
            CASE WHEN next_allowed_utc>UTC_TIMESTAMP(6) THEN 1 ELSE 0 END AS blocked
            FROM hw24_purchase_amazon_throttle WHERE scope_key=? FOR UPDATE');
        $t->execute(['shared_workspace']);$throttle=$t->fetch();
        $next=$throttle['next_allowed_utc']??null;
        if($throttle && (int)$throttle['blocked']===1){
            $db->commit();
            enqueueReply(200,['ok'=>true,'ready'=>false,'reason'=>'rate_limited',
                'next_allowed_utc'=>$next]);
        }
        // Recover final timed-out jobs; do not leave them leased forever.
        $stale=$db->prepare('SELECT id,ean FROM hw24_purchase_amazon_jobs
             WHERE status="leased" AND attempt_count>=4
             AND lease_until_utc<=UTC_TIMESTAMP(6) FOR UPDATE');
        $stale->execute();
        foreach($stale->fetchAll() as $dead){
            $db->prepare('UPDATE hw24_purchase_amazon_jobs SET
                status="blocked",last_error_code="lease_expired_max_attempts",
                leased_by=NULL,lease_token=NULL,lease_until_utc=NULL
                WHERE id=?')->execute([(int)$dead['id']]);
            enqueueEvent($db,(int)$dead['id'],$actor,$dead['ean'],'blocked',null,'lease_expired');
        }
        $s=$db->prepare('SELECT id,ean,attempt_count FROM hw24_purchase_amazon_jobs
             WHERE ((status IN ("queued","retry")
                    AND (next_attempt_utc IS NULL OR next_attempt_utc<=UTC_TIMESTAMP(6)))
                   OR (status="leased" AND lease_until_utc<=UTC_TIMESTAMP(6)))
               AND attempt_count<4
             ORDER BY requested_at_utc ASC,id ASC LIMIT 1 FOR UPDATE');
        $s->execute();$job=$s->fetch();
        if(!$job){
            $db->commit();enqueueReply(200,['ok'=>true,'ready'=>false,'reason'=>'queue_empty_or_delayed']);
        }
        $leaseId=sprintf('%s-%s-%s-%s-%s',bin2hex(random_bytes(4)),bin2hex(random_bytes(2)),
            '4'.substr(bin2hex(random_bytes(2)),1),
            '8'.substr(bin2hex(random_bytes(2)),1),bin2hex(random_bytes(6)));
        $db->prepare('UPDATE hw24_purchase_amazon_jobs SET
             status="leased",attempt_count=attempt_count+1,
             leased_by=?,lease_token=?,lease_until_utc=DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 180 SECOND),
             last_attempt_at_utc=UTC_TIMESTAMP(6),last_error_code=NULL
             WHERE id=?')
          ->execute([$actor,$leaseId,$job['id']]);
        $db->prepare('UPDATE hw24_purchase_amazon_throttle
             SET next_allowed_utc=DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 10 SECOND)
             WHERE scope_key=?')->execute(['shared_workspace']);
        enqueueEvent($db,(int)$job['id'],$actor,$job['ean'],'leased','amazon_catalog','browser_worker');
        $db->commit();
        enqueueReply(200,['ok'=>true,'scope'=>'shared_workspace','ready'=>true,'job_id'=>(int)$job['id'],
             'ean'=>$job['ean'],'lease_token'=>$leaseId,
             'attempt_count'=>(int)$job['attempt_count']+1,'minimum_next_claim_seconds'=>10]);
    }
    if($op==='ack'&&$method==='POST'){
        $id=enqueueId($body['job_id']??null);
        $token=$body['lease_token']??null;
        if(!is_string($token)||!preg_match('/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/D',$token))
            throw new InvalidArgumentException('invalid_lease_token');
        $success=$body['success']??null;
        if(!is_bool($success))throw new InvalidArgumentException('invalid_result');
        $db->beginTransaction();
        $s=$db->prepare('SELECT id,ean,status,attempt_count FROM hw24_purchase_amazon_jobs
            WHERE id=? AND created_by=? AND leased_by=? AND lease_token=?
            FOR UPDATE');
        $s->execute([$id,$actor,$actor,$token]);$job=$s->fetch();
        if(!$job || $job['status']!=='leased')
            throw new RuntimeException('lease_not_current',409);
        $source=null;$snapshot=null;$catalogAt=null;$errorCode=null;
        if($success){
            $snapshot=enqueueId($body['snapshot_id']??null);
            $source=$body['source']??null;
            if(!in_array($source,['cache','live'],true))
                throw new InvalidArgumentException('invalid_catalog_source');
            // Client claims cannot establish catalog success: verify a real
            // immutable server snapshot matching the leased EAN/marketplace.
            $s=$db->prepare('SELECT fetched_at_utc FROM hw24_amazon_catalog_snapshots
               WHERE id=? AND searched_ean=? AND marketplace_id=? AND response_status=200');
            $s->execute([$snapshot,$job['ean'],'A1PA6795UKMFR9']);
            $catalogAt=$s->fetchColumn();
            if($catalogAt===false)throw new RuntimeException('catalog_snapshot_not_verified',409);
            $next='completed';
            $db->prepare('UPDATE hw24_purchase_amazon_jobs SET
                status="completed",last_source=?,last_snapshot_id=?,
                last_catalog_at_utc=?,last_error_code=NULL,completed_at_utc=UTC_TIMESTAMP(6),
                leased_by=NULL,lease_token=NULL,lease_until_utc=NULL,next_attempt_utc=NULL
                WHERE id=? AND created_by=?')
               ->execute([$source,$snapshot,$catalogAt,$id,$actor]);
            enqueueEvent($db,$id,$actor,$job['ean'],'completed',$source,'snapshot_verified');
        }else{
            $errorCode=$body['error_code']??'amazon_api_error';
            if(!is_string($errorCode)||
                !in_array($errorCode,['amazon_rate_limited','amazon_api_error',
                  'network_error','invalid_response','browser_error'],true))
                throw new InvalidArgumentException('invalid_error_code');
            $attempt=(int)$job['attempt_count'];
            $next=$attempt>=4?'blocked':'retry';
            $delay=$errorCode==='amazon_rate_limited'?1800:min(3600,90*(2**max(0,$attempt-1)));
            $db->prepare('UPDATE hw24_purchase_amazon_jobs SET
                status=?,next_attempt_utc=CASE WHEN ?="retry"
                    THEN DATE_ADD(UTC_TIMESTAMP(6),INTERVAL '.$delay.' SECOND) ELSE NULL END,
                last_error_code=?,leased_by=NULL,lease_token=NULL,lease_until_utc=NULL
                WHERE id=? AND created_by=?')
               ->execute([$next,$next,$errorCode,$id,$actor]);
            enqueueEvent($db,$id,$actor,$job['ean'],$next,null,$errorCode);
        }
        $db->commit();
        enqueueReply(200,['ok'=>true,'job_id'=>$id,'status'=>$next,'source'=>$source,
            'catalog_snapshot_id'=>$snapshot,'error_code'=>$errorCode]);
    }
    if($op==='requeue'&&$method==='POST'){
        $id=enqueueId($body['job_id']??null);
        if(($body['confirm']??null)!==true)throw new InvalidArgumentException('explicit_requeue_confirmation_required');
        $db->beginTransaction();
        $s=$db->prepare('SELECT ean,status FROM hw24_purchase_amazon_jobs
            WHERE id=? AND created_by=? FOR UPDATE');
        $s->execute([$id,$actor]);$job=$s->fetch();
        if(!$job)throw new RuntimeException('job_not_found',404);
        if($job['status']==='leased')throw new RuntimeException('job_in_progress',409);
        $db->prepare('UPDATE hw24_purchase_amazon_jobs SET
             status="queued",attempt_count=0,next_attempt_utc=NULL,
             leased_by=NULL,lease_token=NULL,lease_until_utc=NULL,last_error_code=NULL,
             requested_by=?,requested_at_utc=UTC_TIMESTAMP(6)
             WHERE id=? AND created_by=?')->execute([$actor,$id,$actor]);
        enqueueEvent($db,$id,$actor,$job['ean'],'requeued',null,'operator_confirmed');
        $db->commit();
        enqueueReply(200,['ok'=>true,'job_id'=>$id,'status'=>'queued']);
    }
    enqueueReply(404,['ok'=>false,'error'=>'unknown_operation']);
}catch(InvalidArgumentException $e){
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    enqueueReply(400,['ok'=>false,'error'=>$e->getMessage()]);
}catch(RuntimeException $e){
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    enqueueReply(in_array($e->getCode(),[404,409,503],true)?$e->getCode():503,
      ['ok'=>false,'error'=>$e->getMessage()]);
}catch(PDOException $e){
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    error_log('HW24 purchase queue database error: '.$e->getCode());
    enqueueReply(503,['ok'=>false,'error'=>'queue_tables_or_permissions_unavailable']);
}catch(Throwable $e){
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    error_log('HW24 purchase queue unexpected failure: '.get_class($e));
    enqueueReply(503,['ok'=>false,'error'=>'purchase_queue_unavailable']);
}
