<?php
declare(strict_types=1);
/* HW24 JTL SQL Server -> MariaDB snapshot receiver, PHP 8.4+
   This endpoint requires Plesk HTTP auth PLUS its own 32+ character sync token.
   Private settings outside document root: hw24_private/jtl_sync.php
   Keys: user, password, token_sha256, min_rows (suggest 2500).
   Use an independent DB account permitted only to write to the two JTL mirror tables.
   No new snapshot becomes visible to the reader until the final row-count check succeeds.
*/
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function reply(int $code, array $body): void {
 http_response_code($code);
 echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
 exit;
}
function strField($v, int $max): ?string {
 if ($v===null || $v==='') return null;
 if (!is_scalar($v) || strlen((string)$v)>$max) throw new RuntimeException('invalid_field');
 return (string)$v;
}
function numericField($v): ?string {
 if ($v===null || $v==='') return null;
 $s=(string)$v;
 if (!preg_match('/^-?[0-9]{1,12}(\.[0-9]{1,14})?$/D',$s)) throw new RuntimeException('invalid_numeric');
 // JTL stores prices at higher precision; MariaDB mirror DECIMAL(16,4).
 return number_format((float)$s,4,'.','');
}
function dateField($v): ?string {
 if ($v===null || $v==='') return null;
 $s=(string)$v;
 if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2}(\.\d{1,6})?)?$/D',$s)) throw new RuntimeException('invalid_date');
 if ((int)substr($s,0,4)<1000) return null;
 return $s;
}
if (($_SERVER['REQUEST_METHOD']??'')!=='POST') {header('Allow: POST');reply(405,['ok'=>false,'error'=>'method']);}
if (empty($_SERVER['HTTPS']) || strtolower((string)$_SERVER['HTTPS'])==='off') reply(403,['ok'=>false,'error'=>'https_required']);
$file=dirname(__DIR__,2).'/hw24_private/jtl_sync.php';
if (!is_file($file)) reply(503,['ok'=>false,'error'=>'not_configured']);
$cfg=require $file;
if (!is_array($cfg)||empty($cfg['user'])||!isset($cfg['password'])||empty($cfg['token_sha256'])) reply(503,['ok'=>false,'error'=>'not_configured']);
$token=(string)($_SERVER['HTTP_X_HW24_SYNC_TOKEN']??'');
if (strlen($token)<32 || !hash_equals(strtolower((string)$cfg['token_sha256']),hash('sha256',$token))) {
 reply(401,['ok'=>false,'error'=>'authentication_failed']);
}
$raw=file_get_contents('php://input',false,null,0,1500001);
if ($raw===false||strlen($raw)>1500000||strlen($raw)<2) reply(413,['ok'=>false,'error'=>'payload_size']);
$in=json_decode($raw,true);
if (!is_array($in)) reply(400,['ok'=>false,'error'=>'invalid_json']);
$run=(string)($in['run_id']??'');
if (!preg_match('/^[a-f0-9]{32}$/D',$run)) reply(400,['ok'=>false,'error'=>'invalid_run']);
$source='eazybusiness_'.$run;
$total=filter_var($in['total']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>200000]]);
if ($total===false) reply(400,['ok'=>false,'error'=>'invalid_total']);
$mode=(string)($in['action']??'');
try {
 $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
   (string)$cfg['user'],(string)$cfg['password'],[
     PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_EMULATE_PREPARES=>false,
     PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
     PDO::ATTR_TIMEOUT=>10
   ]);
 if ($mode==='begin') {
   $q=$pdo->prepare('SELECT status FROM hw24_jtl_sync_meta WHERE source_id=?');
   $q->execute([$source]);
   if ($q->fetchColumn()!==false) reply(409,['ok'=>false,'error'=>'run_exists']);
   $q=$pdo->prepare("INSERT INTO hw24_jtl_sync_meta (source_id,status,last_run_id,last_row_count,last_message,updated_utc)
        VALUES (?,'staging',?,?,'In Arbeit',UTC_TIMESTAMP(6))");
   $q->execute([$source,$run,$total]);
   reply(200,['ok'=>true,'status'=>'staging']);
 }
 $q=$pdo->prepare('SELECT status,last_row_count FROM hw24_jtl_sync_meta WHERE source_id=?');
 $q->execute([$source]);$meta=$q->fetch();
 if (!$meta || $meta['status']!=='staging'||(int)$meta['last_row_count']!==$total) reply(409,['ok'=>false,'error'=>'not_staging']);
 if ($mode==='batch') {
   $rows=$in['rows']??null;
   if (!is_array($rows)||count($rows)<1||count($rows)>30) reply(400,['ok'=>false,'error'=>'batch_size']);
   $columns=['source_id','jtl_article_id','artikelnummer','ean','han','asin','artikelname',
     'kurzbeschreibung','beschreibung','seo_titel','seo_beschreibung','hersteller',
     'netto_vk','netto_ek','letzter_ek','letzter_ek_datum','amazon_vk','ebay_preis',
     'bestand','source_modified_at','raw_json','beschreibungen_json','synced_at_utc'];
   $params=implode(',',array_fill(0,count($columns)-1,'?')).',UTC_TIMESTAMP(6)';
   $updates=[];
   foreach(array_slice($columns,2,-1) as $column) $updates[]=$column.'=VALUES('.$column.')';
   $updates[]='synced_at_utc=UTC_TIMESTAMP(6)';
   $sql='INSERT INTO hw24_jtl_articles ('.implode(',',$columns).') VALUES ('.$params.')
         ON DUPLICATE KEY UPDATE '.implode(',',$updates);
   $write=$pdo->prepare($sql);
   $pdo->beginTransaction();$seen=[];
   foreach ($rows as $r) {
     if (!is_array($r)||!isset($r['jtl_article_id'])) throw new RuntimeException('invalid_row');
     $aid=strField($r['jtl_article_id'],80);
     if (!$aid||isset($seen[$aid])) throw new RuntimeException('invalid_or_duplicate_id');
     $seen[$aid]=true;
     $rawJson=json_encode($r['raw']??new stdClass(),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
     $descJson=json_encode($r['descriptions']??[],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
     if ($rawJson===false||$descJson===false) throw new RuntimeException('invalid_raw_json');
     $values=[$source,$aid];
     foreach (['artikelnummer'=>140,'ean'=>32,'han'=>140,'asin'=>32,'artikelname'=>600,
       'kurzbeschreibung'=>30000,'beschreibung'=>100000,'seo_titel'=>400,
       'seo_beschreibung'=>30000,'hersteller'=>255] as $k=>$max) $values[]=strField($r[$k]??null,$max);
     foreach(['netto_vk','netto_ek','letzter_ek'] as $k) $values[]=numericField($r[$k]??null);
     $values[]=dateField($r['letzter_ek_datum']??null);
     foreach(['amazon_vk','ebay_preis','bestand'] as $k) $values[]=numericField($r[$k]??null);
     $values[]=dateField($r['source_modified_at']??null);
     $values[]=$rawJson;$values[]=$descJson;
     $write->execute($values);
   }
   $pdo->commit();
   reply(200,['ok'=>true,'accepted'=>count($rows),'status'=>'staging']);
 }
 if ($mode==='finish') {
   $q=$pdo->prepare('SELECT COUNT(*) FROM hw24_jtl_articles WHERE source_id=?');
   $q->execute([$source]);$actual=(int)$q->fetchColumn();
   $minimum=(int)($cfg['min_rows']??2500);
   if ($actual!==$total || $actual<$minimum) reply(409,['ok'=>false,'error'=>'snapshot_incomplete','actual'=>$actual,'expected'=>$total,'minimum'=>$minimum]);
   $q=$pdo->prepare("UPDATE hw24_jtl_sync_meta
         SET status='ready',last_completed_utc=UTC_TIMESTAMP(6),
             last_message='Vollstaendig',updated_utc=UTC_TIMESTAMP(6)
         WHERE source_id=? AND status='staging'");
   $q->execute([$source]);
   // Preserve last three successful snapshots for rollback.
   // Older READY snapshots are deleted; the historical Excel archive stays untouched.
   try {
     $old=$pdo->query("SELECT source_id FROM hw24_jtl_sync_meta
                       WHERE status='ready' ORDER BY last_completed_utc DESC
                       LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);
     if (count($old)>3) {
       $deleteArticles=$pdo->prepare('DELETE FROM hw24_jtl_articles WHERE source_id=?');
       $deleteMeta=$pdo->prepare("DELETE FROM hw24_jtl_sync_meta WHERE source_id=? AND status='ready'");
       foreach (array_slice($old,3) as $previous) {
         $deleteArticles->execute([$previous]);
         $deleteMeta->execute([$previous]);
       }
     }
   } catch(Throwable $cleanupError) {
     error_log('HW24 old JTL snapshot cleanup failed: '.$cleanupError->getMessage());
   }
   reply(200,['ok'=>true,'status'=>'ready','articles'=>$actual]);
 }
 reply(400,['ok'=>false,'error'=>'unknown_action']);
}catch(Throwable $e) {
 if(isset($pdo)&&$pdo->inTransaction()) $pdo->rollBack();
 error_log('HW24 JTL Sync: '.$e->getMessage());
 reply(503,['ok'=>false,'error'=>'sync_error']);
}
