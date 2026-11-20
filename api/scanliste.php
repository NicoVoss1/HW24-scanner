<?php
declare(strict_types=1);
/**
 * HW24 Scanliste v1: durable intake + review drafts. No JTL/WMS writes.
 * Requires migration sql/HW24_11_SCANLISTE.sql, HTTPS, site authentication,
 * a dedicated least-privilege DB user, and an administrator-controlled deploy.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function respond(int $status, array $data): never {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
  exit;
}
function gtinValid(string $ean): bool {
  if (!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D', $ean)
      || preg_match('/^0+$/D', $ean)) return false;
  $sum=0;$weight=3;
  for($i=strlen($ean)-2;$i>=0;$i--) { $sum+=(int)$ean[$i]*$weight; $weight=($weight===3?1:3); }
  return (10-$sum%10)%10===(int)$ean[strlen($ean)-1];
}
function getBatch(PDO $db, int $id, string $actor, bool $lock=false): array {
  $sql='SELECT id,label,closed_at_utc FROM hw24_scan_batches WHERE id=? AND created_by=?';
  if($lock) $sql.=' FOR UPDATE';
  $s=$db->prepare($sql);$s->execute([$id,$actor]);$r=$s->fetch();
  if(!$r)respond(404,['ok'=>false,'error'=>'batch_not_found']);
  if($r['closed_at_utc']!==null)respond(409,['ok'=>false,'error'=>'batch_closed']);
  return $r;
}
function resolve(PDO $db, string $ean): array {
  $meta=$db->query("SELECT source_id,last_completed_utc,last_row_count,
                   TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age
                   FROM hw24_jtl_sync_meta WHERE status='ready'
                   AND last_completed_utc IS NOT NULL
                   ORDER BY last_completed_utc DESC LIMIT 1")->fetch();
  if(!$meta || (int)$meta['last_row_count']<1 || (int)$meta['age']<0 || (int)$meta['age']>45)
    return ['resolution'=>'unverified','jtl_article_id'=>null,'artikelnummer'=>null,
            'artikelname'=>null,'snapshot_at_utc'=>null];
  // EAN is not guaranteed unique. HAN and article number must be checked too.
  $s=$db->prepare('SELECT jtl_article_id,artikelnummer,artikelname FROM hw24_jtl_articles
                   WHERE source_id=? AND (ean=? OR han=? OR artikelnummer=?)
                   ORDER BY jtl_article_id LIMIT 2');
  $s->execute([$meta['source_id'],$ean,$ean,$ean]);$matches=$s->fetchAll();
  if(count($matches)>1) return ['resolution'=>'multiple','jtl_article_id'=>null,
    'artikelnummer'=>null,'artikelname'=>null,'snapshot_at_utc'=>$meta['last_completed_utc']];
  if(count($matches)===1) return ['resolution'=>'existing',
    'jtl_article_id'=>$matches[0]['jtl_article_id'],
    'artikelnummer'=>$matches[0]['artikelnummer'],
    'artikelname'=>$matches[0]['artikelname'],
    'snapshot_at_utc'=>$meta['last_completed_utc']];
  return ['resolution'=>'new_candidate','jtl_article_id'=>null,
          'artikelnummer'=>null,'artikelname'=>null,'snapshot_at_utc'=>$meta['last_completed_utc']];
}
function rowData(array $r): array {
  $fields=json_decode((string)($r['draft_json']??''),true);
  $r['draft']=is_array($fields)?$fields:new stdClass();
  unset($r['draft_json'],$r['source_json']);
  return $r;
}
$actor=trim((string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??''));
if($actor==='')respond(403,['ok'=>false,'error'=>'authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on' && ($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
  respond(403,['ok'=>false,'error'=>'https_required']);
if(strlen($actor)>180)respond(403,['ok'=>false,'error'=>'actor_invalid']);
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,['GET','POST'],true))respond(405,['ok'=>false,'error'=>'method_not_allowed']);
$body=[];
if($method==='POST'){
  $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
  $host=strtolower((string)parse_url('https://'.($_SERVER['HTTP_HOST']??''),PHP_URL_HOST));
  if(!$origin || strtolower((string)parse_url($origin,PHP_URL_HOST))!==$host ||
     parse_url($origin,PHP_URL_SCHEME)!=='https')respond(403,['ok'=>false,'error'=>'origin_rejected']);
  if(($_SERVER['HTTP_X_HW24_APPROVAL']??'')!=='confirmed')
    respond(403,['ok'=>false,'error'=>'approval_header_required']);
  if(!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json'))
    respond(415,['ok'=>false,'error'=>'json_required']);
  if((int)($_SERVER['CONTENT_LENGTH']??0)>24000)respond(413,['ok'=>false,'error'=>'too_large']);
  try{$body=json_decode(file_get_contents('php://input'),true,30,JSON_THROW_ON_ERROR);}
  catch(Throwable $e){respond(400,['ok'=>false,'error'=>'invalid_json']);}
  if(!is_array($body))respond(400,['ok'=>false,'error'=>'invalid_body']);
}
$op=(string)($method==='GET'?($_GET['op']??'batches'):($body['op']??''));
$config=dirname(__DIR__,2).'/hw24_private/db.php';
if(!is_file($config))respond(503,['ok'=>false,'error'=>'database_not_configured']);
try {
  $cfg=require $config;
  if(!is_array($cfg)||empty($cfg['user'])||!isset($cfg['password']))
    throw new RuntimeException('invalid_database_config');
  $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
    $cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
  if($op==='batches'&&$method==='GET'){
    $s=$pdo->prepare('SELECT id,label,created_at_utc,updated_at_utc FROM hw24_scan_batches
                       WHERE created_by=? AND closed_at_utc IS NULL ORDER BY id DESC LIMIT 50');
    $s->execute([$actor]);respond(200,['ok'=>true,'batches'=>$s->fetchAll()]);
  }
  if($op==='items'&&$method==='GET'){
    $id=filter_var($_GET['batch_id']??null,FILTER_VALIDATE_INT);
    if(!$id||$id<1)respond(400,['ok'=>false,'error'=>'batch_id_required']);
    getBatch($pdo,$id,$actor);
    $s=$pdo->prepare('SELECT id,batch_id,ean,scan_count,resolution,jtl_article_id,
                       artikelnummer,artikelname,review_state,draft_json,revision,
                       snapshot_at_utc,first_scanned_utc,last_scanned_utc,updated_at_utc
                       FROM hw24_scan_items WHERE batch_id=? ORDER BY id DESC LIMIT 3000');
    $s->execute([$id]);respond(200,['ok'=>true,'items'=>array_map('rowData',$s->fetchAll())]);
  }
  if($op==='create'&&$method==='POST'){
    $label=trim((string)($body['label']??''));
    if($label===''||mb_strlen($label,'UTF-8')>160)respond(400,['ok'=>false,'error'=>'invalid_label']);
    $s=$pdo->prepare('INSERT INTO hw24_scan_batches(label,created_by) VALUES (?,?)');
    $s->execute([$label,$actor]);
    respond(201,['ok'=>true,'batch_id'=>(int)$pdo->lastInsertId()]);
  }
  if($op==='scan'&&$method==='POST'){
    $id=filter_var($body['batch_id']??null,FILTER_VALIDATE_INT);
    $ean=trim((string)($body['ean']??''));
    if(!$id||$id<1||!gtinValid($ean))respond(400,['ok'=>false,'error'=>'invalid_batch_or_gtin']);
    $pdo->beginTransaction();getBatch($pdo,$id,$actor,true);
    $match=resolve($pdo,$ean);
    $s=$pdo->prepare('INSERT INTO hw24_scan_items
      (batch_id,ean,resolution,jtl_article_id,artikelnummer,artikelname,snapshot_at_utc)
      VALUES (?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE scan_count=scan_count+1,
      last_scanned_utc=UTC_TIMESTAMP(6),updated_at_utc=UTC_TIMESTAMP(6),
      resolution=VALUES(resolution),jtl_article_id=VALUES(jtl_article_id),
      snapshot_at_utc=VALUES(snapshot_at_utc),
      artikelnummer=IF(VALUES(resolution)="existing",VALUES(artikelnummer),artikelnummer),
      artikelname=IF(VALUES(resolution)="existing",VALUES(artikelname),artikelname)');
    $s->execute([$id,$ean,$match['resolution'],$match['jtl_article_id'],
      $match['artikelnummer'],$match['artikelname'],$match['snapshot_at_utc']]);
    $s=$pdo->prepare('INSERT INTO hw24_scan_events(batch_id,ean,scanned_by) VALUES (?,?,?)');
    $s->execute([$id,$ean,$actor]);
    $s=$pdo->prepare('UPDATE hw24_scan_batches SET updated_at_utc=UTC_TIMESTAMP(6) WHERE id=?');
    $s->execute([$id]);
    $s=$pdo->prepare('SELECT id,batch_id,ean,scan_count,resolution,jtl_article_id,
      artikelnummer,artikelname,review_state,draft_json,revision,snapshot_at_utc,
      first_scanned_utc,last_scanned_utc,updated_at_utc
      FROM hw24_scan_items WHERE batch_id=? AND ean=?');
    $s->execute([$id,$ean]);$row=$s->fetch();$pdo->commit();
    respond(200,['ok'=>true,'item'=>rowData($row)]);
  }
  if($op==='edit'&&$method==='POST'){
    $batch=filter_var($body['batch_id']??null,FILTER_VALIDATE_INT);
    $item=filter_var($body['item_id']??null,FILTER_VALIDATE_INT);
    $expected=$body['expected_revision']??null;
    $data=$body['fields']??null;
    if(!$batch||!$item||!is_int($expected)||$expected<0||!is_array($data)
        ||count($data)>45)respond(400,['ok'=>false,'error'=>'invalid_edit']);
    $allowed=[
      'Artikelnummer'=>140,'Artikelname'=>500,'GTIN'=>14,'HAN'=>140,'ASIN'=>10,
      'Hersteller'=>180,'Warengruppe'=>180,'Steuerklasse'=>120,
      'Kurzbeschreibung'=>3000,'Beschreibung'=>10000,'Kosten'=>40,'Netto-VK'=>40,
      'Brutto-VK'=>40,'Inhalt/Menge'=>40,'Maßeinheit'=>40,'Versandgewicht'=>40,
      'Artikelgewicht'=>40,'Versandklasse'=>120,'Artikel aktiv'=>1,
      'Bestandsführung aktiv'=>1,'Überverkäufe möglich'=>1,'Artikeltyp'=>120,
      'Lieferstatus'=>180,'Kategorie Ebene 1'=>180,'Kategorie Ebene 2'=>180,
      'Kategorie Ebene 3'=>180,'Titel-Tag (SEO)'=>250,
      'Meta-Description (SEO)'=>600,'URL-Pfad'=>300];
    $clean=[];
    foreach($data as $k=>$v){
      if(!array_key_exists($k,$allowed)||!is_string($v) ||
         strlen($v)>$allowed[$k]*4 || mb_strlen($v,'UTF-8')>$allowed[$k])
        respond(400,['ok'=>false,'error'=>'invalid_field','field'=>$k]);
      $v=trim($v);
      if(($k==='GTIN' && $v!=='' && !gtinValid($v)) ||
         ($k==='ASIN' && $v!=='' && !preg_match('/^[A-Z0-9]{10}$/iD',$v)))
        respond(400,['ok'=>false,'error'=>'invalid_identifier','field'=>$k]);
      if(in_array($k,['Artikel aktiv','Bestandsführung aktiv','Überverkäufe möglich'],true)
         && $v!=='' && !in_array($v,['Y','N'],true))
        respond(400,['ok'=>false,'error'=>'invalid_boolean','field'=>$k]);
      if(in_array($k,['Kosten','Netto-VK','Brutto-VK'],true) && $v!=='' &&
        !preg_match('/^(?:0|[1-9][0-9]*)(?:[,.][0-9]{1,4})?$/D',$v))
        respond(400,['ok'=>false,'error'=>'invalid_price','field'=>$k]);
      $clean[$k]=$v;
    }
    $pdo->beginTransaction();getBatch($pdo,$batch,$actor,true);
    $s=$pdo->prepare('SELECT * FROM hw24_scan_items WHERE id=? AND batch_id=? FOR UPDATE');
    $s->execute([$item,$batch]);$old=$s->fetch();
    if(!$old){$pdo->rollBack();respond(404,['ok'=>false,'error'=>'item_not_found']);}
    if((int)$old['revision']!==$expected){$pdo->rollBack();respond(409,['ok'=>false,
      'error'=>'revision_conflict','current_revision'=>(int)$old['revision']]);}
    $prev=json_decode((string)($old['draft_json']??''),true);
    if(!is_array($prev))$prev=[];
    $combined=array_replace($prev,$clean);
    // The scanned GTIN is fixed. No synthetic or changed identifiers.
    if(isset($combined['GTIN']) && $combined['GTIN']!==$old['ean']){
      $pdo->rollBack();respond(400,['ok'=>false,'error'=>'gtin_must_match_scan']);
    }
    $state='draft';
    $ready=!empty($combined['Artikelnummer'])&&!empty($combined['Artikelname'])&&
      !empty($combined['Hersteller'])&&!empty($combined['Warengruppe'])&&
      !empty($combined['Steuerklasse'])&&$old['resolution']==='new_candidate';
    if(($body['mark_ready']??false)===true){
      if(!$ready){$pdo->rollBack();respond(409,['ok'=>false,'error'=>'not_ready']);}
      // A fresh JTL check is required immediately before allowing ready state.
      $match=resolve($pdo,(string)$old['ean']);
      if($match['resolution']!=='new_candidate'){$pdo->rollBack();
        respond(409,['ok'=>false,'error'=>'jtl_match_changed','resolution'=>$match['resolution']]);}
      $sku=trim((string)$combined['Artikelnummer']);
      $check=$pdo->prepare('SELECT COUNT(*) FROM hw24_jtl_articles WHERE source_id=
         (SELECT source_id FROM hw24_jtl_sync_meta WHERE status="ready"
           ORDER BY last_completed_utc DESC LIMIT 1) AND artikelnummer=?');
      $check->execute([$sku]);
      if((int)$check->fetchColumn()>0){$pdo->rollBack();
        respond(409,['ok'=>false,'error'=>'artikelnummer_already_in_jtl']);}
      $state='ready';
    }
    $s=$pdo->prepare('UPDATE hw24_scan_items SET draft_json=?,review_state=?,
      revision=revision+1,reviewed_at_utc=UTC_TIMESTAMP(6),
      updated_at_utc=UTC_TIMESTAMP(6) WHERE id=?');
    $s->execute([json_encode($combined,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
      $state,$item]);$pdo->commit();
    respond(200,['ok'=>true,'item_id'=>$item,'revision'=>$expected+1,
      'review_state'=>$state,'draft'=>$combined]);
  }
  respond(400,['ok'=>false,'error'=>'unsupported_operation']);
} catch(Throwable $e){
  if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
  error_log('HW24 scanlist ['.get_class($e).']: '.$e->getMessage());
  respond(503,['ok'=>false,'error'=>'scanlist_unavailable']);
}
