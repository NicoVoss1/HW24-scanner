<?php
declare(strict_types=1);
/**
 * Read-only JTL reference suggestions from the existing complete READY MariaDB mirror.
 * These are VALUES OBSERVED in imported articles, not the authoritative full SQL Server
 * tHersteller/tKategorie/tWarengruppe/tLieferstatus catalog. Never claim otherwise.
 * No JTL, WMS, DB schema or snapshot writes.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
function reply(int $code,array $json): never {
 http_response_code($code);
 echo json_encode($json,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
 exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){header('Allow: GET');reply(405,['ok'=>false,'error'=>'method']);}
$file=dirname(__DIR__,2).'/hw24_private/db.php';
if(!is_file($file))reply(503,['ok'=>false,'error'=>'mirror_not_configured']);
try{
 $cfg=require $file;
 if(!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg))
   reply(503,['ok'=>false,'error'=>'mirror_not_configured']);
 $db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
  (string)$cfg['user'],(string)$cfg['password'],[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
   PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5
  ]);
 $meta=$db->query("SELECT source_id,last_completed_utc,last_row_count,
   TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
   FROM hw24_jtl_sync_meta WHERE status='ready' AND last_completed_utc IS NOT NULL
   ORDER BY last_completed_utc DESC LIMIT 1")->fetch();
 if(!$meta)reply(200,['ok'=>true,'status'=>'no_snapshot','lists'=>new stdClass()]);
 $age=(int)$meta['age_minutes'];
 if($age<0||$age>45)reply(200,['ok'=>true,'status'=>'stale',
    'age_minutes'=>$age,'lists'=>new stdClass(),'source'=>'JTL-MariaDB-Spiegel']);
 $source=(string)$meta['source_id'];
 $result=[
  'Hersteller'=>[],'Warengruppe'=>[],'Lieferstatus'=>[],
  'Kategorie Ebene 1'=>[],'Kategorie Ebene 2'=>[],'Kategorie Ebene 3'=>[],
  'Zusätzliche Bearbeitungszeit'=>[],'Beschaffungszeit manuell setzen'=>[],
  'Beschaffungszeit automatisch ermitteln'=>[]
 ];
 $add=static function(string $key,$value) use (&$result): void {
  if(!isset($result[$key])||!is_scalar($value))return;
  $s=trim((string)$value);
  if($s===''||strlen($s)>130||preg_match('/[\x00-\x1f\x7f]/',$s))return;
  if(count($result[$key])<250)$result[$key][$s]=$s;
 };
 $q=$db->prepare("SELECT DISTINCT hersteller FROM hw24_jtl_articles
      WHERE source_id=? AND hersteller IS NOT NULL AND hersteller<>'' ORDER BY hersteller LIMIT 250");
 $q->execute([$source]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $v)$add('Hersteller',$v);
 // Read only observed values from a ready snapshot; never infer foreign-key names.
 $select=[
  'Warengruppe'=>"JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.HW24_WarengruppeName'))",
  'Lieferstatus'=>"JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.HW24_RelatedSources.lieferstatus.rows[0].cName'))",
  'Kategorie Ebene 1'=>"JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.HW24_RelatedSources.kategorien.rows[0].cName'))",
  'Zusätzliche Bearbeitungszeit'=>"JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.nBearbeitungszeit'))",
  'Beschaffungszeit manuell setzen'=>"JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.nLiefertageWennAusverkauft'))",
  'Beschaffungszeit automatisch ermitteln'=>"JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.nAutomatischeLiefertageberechnung'))"
 ];
 foreach($select as $field=>$expression){
   $sql="SELECT DISTINCT $expression AS item FROM hw24_jtl_articles
      WHERE source_id=? AND raw_json IS NOT NULL
      HAVING item IS NOT NULL AND item<>'' AND item<>'null'
      ORDER BY item LIMIT 250";
   $q=$db->prepare($sql);$q->execute([$source]);
   foreach($q->fetchAll(PDO::FETCH_COLUMN) as $v)$add($field,$v);
 }
 // A single article category membership is not a verified global category
 // hierarchy. Thus L2 and L3 intentionally remain empty.
 foreach($result as $key=>$values){
  $sorted=array_values($values);sort($sorted,SORT_NATURAL|SORT_FLAG_CASE);
  $result[$key]=$sorted;
 }
 reply(200,['ok'=>true,'status'=>'ready','scope'=>'observed_values_only',
   'note'=>'Werte aus dem letzten vollständigen JTL-Spiegel; kein vollständiger SQL-Server-Referenzkatalog und keine Kategorien-Hierarchie',
   'snapshot_at'=>$meta['last_completed_utc'],'age_minutes'=>$age,
   'rows_in_snapshot'=>(int)$meta['last_row_count'],'lists'=>$result]);
}catch(Throwable $error){
 error_log('HW24 jtl reference lookup: '.$error->getMessage());
 reply(503,['ok'=>false,'error'=>'reference_query_unavailable']);
}
