<?php
declare(strict_types=1);
/**
 * HW24 V4.23 TEST. Read-only whole-snapshot quality inventory from the already
 * configured JTL -> MariaDB mirror. No direct JTL/WMS/Shopify writes or OpenAI calls.
 * The resulting issues are candidates for manual review, NOT definitive compliance.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function auditReply(int $status,array $data): never {
 http_response_code($status);
 echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
 exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){
 header('Allow: GET');auditReply(405,['ok'=>false,'error'=>'method_not_allowed']);
}
$actor=trim((string)($_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??''));
if($actor===''||strlen($actor)>180)auditReply(403,['ok'=>false,'error'=>'authentication_required']);
if(($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https')
 auditReply(403,['ok'=>false,'error'=>'https_required']);
require_once __DIR__.'/hw24-quality-rules.php';
$q=(string)($_GET['q']??'');
if(strlen($q)>100||preg_match('/[\x00-\x1f\x7f]/',$q))auditReply(400,['ok'=>false,'error'=>'invalid_search']);
$q=trim($q);
$filter=(string)($_GET['issue']??'all');
$catalog=hw24QualityCatalog();
if($filter!=='all'&&!array_key_exists($filter,$catalog))
 auditReply(400,['ok'=>false,'error'=>'invalid_filter']);
$severity=(string)($_GET['severity']??'all');
if(!in_array($severity,['all','critical','warning','no_detected_issues'],true))
 auditReply(400,['ok'=>false,'error'=>'invalid_severity']);
$page=filter_var($_GET['page']??1,FILTER_VALIDATE_INT);
$perPage=filter_var($_GET['per_page']??25,FILTER_VALIDATE_INT);
if($page===false||$page<1||$page>10000||$perPage===false||
 !in_array($perPage,[25,50,100],true))
 auditReply(400,['ok'=>false,'error'=>'invalid_pagination']);
$file=dirname(__DIR__,2).'/hw24_private/db.php';
if(!is_file($file))auditReply(503,['ok'=>false,'error'=>'jtl_mirror_unavailable']);
try{
 $cfg=require $file;
 if(!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg))
  auditReply(503,['ok'=>false,'error'=>'jtl_mirror_unavailable']);
 $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
  (string)$cfg['user'],(string)$cfg['password'],[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
   PDO::ATTR_EMULATE_PREPARES=>false,
   PDO::ATTR_TIMEOUT=>10
  ]);
 $meta=$pdo->query("SELECT source_id,last_completed_utc,last_row_count,
  TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
  FROM hw24_jtl_sync_meta WHERE status='ready'
   AND last_completed_utc IS NOT NULL
  ORDER BY last_completed_utc DESC LIMIT 1")->fetch();
 if(!$meta)auditReply(200,['ok'=>true,'snapshot_status'=>'no_ready_snapshot',
   'note'=>'Kein vollständig abgeschlossener JTL-Spiegel verfügbar',
   'total'=>null,'items'=>[]]);
 $stmt=$pdo->prepare('SELECT COUNT(*) FROM hw24_jtl_articles WHERE source_id=?');
 $stmt->execute([$meta['source_id']]);
 $actual=(int)$stmt->fetchColumn();
 $expected=(int)$meta['last_row_count'];
 if($actual===0||$expected!==$actual)
  auditReply(503,['ok'=>false,'error'=>'incomplete_jtl_snapshot',
    'note'=>'Keine Qualitätsaussage aus unvollständigem Snapshot']);
 // The first safe release is deliberately bounded; a future streaming job will
 // process larger catalogs without blocking PHP-FPM or claiming partial coverage.
 if($actual>20000)auditReply(503,['ok'=>false,'error'=>'audit_streaming_required',
  'note'=>'Der Datenbestand überschreitet die abgesicherte Einzelseitenprüfung. Streaming-Audit erforderlich']);
 $age=(int)$meta['age_minutes'];
 $fresh=$age>=0&&$age<=45;
 $sql="SELECT jtl_article_id,artikelnummer,ean,han,artikelname,hersteller,
   netto_ek,letzter_ek,bestand,
   CASE WHEN kurzbeschreibung IS NOT NULL AND CHAR_LENGTH(TRIM(kurzbeschreibung))>0
       THEN 1 ELSE 0 END AS has_short,
   CASE WHEN beschreibung IS NOT NULL AND CHAR_LENGTH(TRIM(beschreibung))>0
       THEN 1 ELSE 0 END AS has_description,
   CASE WHEN JSON_VALID(raw_json)
       THEN JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.HW24_WarengruppeName'))
       ELSE NULL END AS warengruppe_name,
   CASE WHEN JSON_VALID(raw_json)
       THEN JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.kWarengruppe'))
       ELSE NULL END AS warengruppe_id,
   CASE WHEN JSON_VALID(raw_json)
       THEN JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.kLieferStatus'))
       ELSE NULL END AS lieferstatus_id
  FROM hw24_jtl_articles WHERE source_id=?
  ORDER BY jtl_article_id";
 $stmt=$pdo->prepare($sql);$stmt->execute([$meta['source_id']]);
 $stats=['total'=>$actual,'with_issues'=>0,'without_detected_issues'=>0,
   'critical_articles'=>0,'stock_without_ek'=>0,'with_stock'=>0,
   'issue_counts'=>array_fill_keys(array_keys($catalog),0)];
 $selected=[];
 $queryMatches=static function(array $a,string $needle): bool {
  if($needle==='')return true;
  foreach(['artikelnummer','ean','han','artikelname','hersteller'] as $field){
   $s=(string)($a[$field]??'');
   if($s!==''&&(function_exists('mb_stripos')?mb_stripos($s,$needle,0,'UTF-8')!==false:
      stripos($s,$needle)!==false))return true;
  }
  return false;
 };
 $seen=0;
 while($a=$stmt->fetch()){
  $seen++;
  $result=hw24QualityEvaluate($a);
  if($result['has_stock'])$stats['with_stock']++;
  if($result['issue_count'])$stats['with_issues']++;
  else $stats['without_detected_issues']++;
  if($result['critical_count'])$stats['critical_articles']++;
  foreach($result['issues'] as $code)$stats['issue_counts'][$code]++;
  if(in_array('stock_without_ek',$result['issues'],true))$stats['stock_without_ek']++;
  if(($filter!=='all'&&!in_array($filter,$result['issues'],true))||
     ($severity!=='all'&&$severity!==$result['severity'])||
     !$queryMatches($a,$q))continue;
  $selected[]=[
   'jtl_article_id'=>(string)$a['jtl_article_id'],
   'artikelnummer'=>(string)($a['artikelnummer']??''),
   'ean'=>(string)($a['ean']??''),
   'han'=>(string)($a['han']??''),
   'artikelname'=>(string)($a['artikelname']??''),
   'hersteller'=>(string)($a['hersteller']??''),
   'bestand'=>$a['bestand']===null?null:(float)$a['bestand'],
   'netto_ek'=>$a['netto_ek']===null?null:(float)$a['netto_ek'],
   'letzter_ek'=>$a['letzter_ek']===null?null:(float)$a['letzter_ek'],
   'warengruppe'=>(string)($a['warengruppe_name']??''),
   'lieferstatus_id'=>(string)($a['lieferstatus_id']??''),
   'issues'=>$result['issues'],
   'issue_count'=>$result['issue_count'],
   'critical_count'=>$result['critical_count'],
   'severity'=>$result['severity']
  ];
 }
 if($seen!==$actual)
  auditReply(503,['ok'=>false,'error'=>'snapshot_changed_during_audit',
   'note'=>'Artikelzahl hat sich während der Prüfung verändert; bitte erneut prüfen']);
 usort($selected,static function(array $a,array $b): int {
  return $b['critical_count']<=>$a['critical_count']
   ?: $b['issue_count']<=>$a['issue_count']
   ?: strnatcasecmp($a['artikelname'],$b['artikelname'])
   ?: strcmp($a['jtl_article_id'],$b['jtl_article_id']);
 });
 $totalFiltered=count($selected);
 $items=array_slice($selected,($page-1)*$perPage,$perPage);
 auditReply(200,[
  'ok'=>true,'snapshot_status'=>$fresh?'fresh':'stale',
  'snapshot_at_utc'=>$meta['last_completed_utc'],'age_minutes'=>$age,
  'scope'=>'last_complete_read_only_jtl_mirror',
  'basis'=>'source_field_coverage_not_product_certification',
  'note'=>'Nur beobachtete JTL-Spiegelfelder. Verfügbarkeit, Pflichtfeldgeltung, Kosmetik-Compliance und echte Einkaufsbelege müssen gesondert geprüft werden.',
  'not_measurable'=>[
   'responsible_person'=>'Nicht eindeutig als verifiziertes Feld im aktuellen JTL-Spiegel vorhanden',
   'legal_compliance'=>'Nicht durch diese technische Feldprüfung nachgewiesen',
   'supplier_cost_evidence'=>'Kostenbeleg und Kommissionsstatus nicht vollständig in den beiden EK-Spiegelfeldern'
  ],
  'rule_catalog'=>$catalog,'summary'=>$stats,'total_filtered'=>$totalFiltered,
  'page'=>(int)$page,'per_page'=>(int)$perPage,'total_pages'=>max(1,(int)ceil($totalFiltered/$perPage)),
  'items'=>$items
 ]);
}catch(Throwable $e){
 error_log('HW24 quality read-only audit: '.get_class($e).': '.$e->getMessage());
 auditReply(503,['ok'=>false,'error'=>'quality_audit_unavailable']);
}
