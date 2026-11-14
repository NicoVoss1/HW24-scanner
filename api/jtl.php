<?php
declare(strict_types=1);
/* Read-only JTL mirror query. Never declares "new" without a fresh, complete sync.
   The actual SQL Server is internal and will later be read by a dedicated Windows synchroniser. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function out(int $status, array $data): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
  header('Allow: GET');
  out(405,['ok'=>false,'error'=>'method_not_allowed']);
}
$ean=trim((string)($_GET['ean'] ?? ''));
if (!preg_match('/^[0-9]{8,14}$/D',$ean)) {
  out(400,['ok'=>false,'error'=>'invalid_identifier_format']);
}
$path=dirname(__DIR__,2).'/hw24_private/db.php';
if (!is_file($path)) out(200,['ok'=>true,'jtl_status'=>'not_configured','matches'=>[]]);
try {
  $cfg=require $path;
  if (!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg)) {
    out(200,['ok'=>true,'jtl_status'=>'not_configured','matches'=>[]]);
  }
  $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
    $cfg['user'],$cfg['password'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
      PDO::ATTR_TIMEOUT=>5
    ]);
  $t=$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES
                   WHERE TABLE_SCHEMA=DATABASE()
                     AND TABLE_NAME IN ('hw24_jtl_sync_meta','hw24_jtl_articles')");
  if ((int)$t->fetchColumn()!==2) {
    out(200,['ok'=>true,'jtl_status'=>'not_configured','matches'=>[]]);
  }
  $q=$pdo->prepare('SELECT source_id,status,last_completed_utc,last_row_count,
                            TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
                     FROM hw24_jtl_sync_meta
                     WHERE status=? AND last_completed_utc IS NOT NULL
                     ORDER BY last_completed_utc DESC LIMIT 1');
  $q->execute(['ready']);
  $meta=$q->fetch();
  if (!$meta) {
    out(200,['ok'=>true,'jtl_status'=>'not_connected','matches'=>[]]);
  }
  $age=(int)$meta['age_minutes'];
  $fresh=$age>=0 && $age<=30;
  if (!$fresh) {
    out(200,['ok'=>true,'jtl_status'=>'stale','snapshot_at'=>$meta['last_completed_utc'],
      'age_minutes'=>$age,'matches'=>[]]);
  }
  $q=$pdo->prepare('SELECT jtl_article_id,artikelnummer,ean,han,asin,artikelname,hersteller,kurzbeschreibung,beschreibung,seo_titel,seo_beschreibung,
                           netto_vk,netto_ek,letzter_ek,letzter_ek_datum,amazon_vk,ebay_preis,bestand,synced_at_utc
                    FROM hw24_jtl_articles WHERE source_id=? AND ean=?
                    ORDER BY jtl_article_id LIMIT 15');
  $q->execute([$meta['source_id'],$ean]);
  out(200,[
    'ok'=>true,'jtl_status'=>'current_snapshot',
    'snapshot_at'=>$meta['last_completed_utc'],'age_minutes'=>$age,
    'matches'=>$q->fetchAll(),
    'limited'=>false,
    'note'=>'JTL-Spiegelstand aus der letzten abgeschlossenen Synchronisation; keine direkte JTL-Datenbankverbindung'
  ]);
} catch (Throwable $e) {
  error_log('HW24 JTL mirror lookup failed: '.$e->getMessage());
  out(200,['ok'=>true,'jtl_status'=>'unavailable','matches'=>[]]);
}
