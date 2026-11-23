<?php
declare(strict_types=1);
/* Standalone smoke check: synthetic data only, no external network or JTL DB. */
$root=realpath(__DIR__.'/../storage/app/private');
if (!$root) {fwrite(STDERR,"private storage folder missing\n");exit(2);}
$folder=$root.'/ci-tracking-'.bin2hex(random_bytes(6));
if (!mkdir($folder,0700)) exit(2);
$csv=$folder.'/source.csv'; $db=$folder.'/snapshot.sqlite';
$headers=['amazon_order_id','jtl_order','delivery_note','tracking_number','carrier',
 'package_id','shipment_id','state_id','event_at_raw','status_code',
 'event_text','provider_status','scan_location','country'];
$record=['302-1111111-2222222','AU-TEST-1','LS-TEST-1','00340000000000000001','DHL Test',
 '1775','123','88','2026-11-19 10:36:00.000','7',
 'Die Sendung liegt in der Filiale Teststr. 1, 11111 Teststadt zur Abholung bereit.',
 'standard: LA','','Deutschland'];
$fh=fopen($csv,'wb');
fputcsv($fh,$headers,';','"','');
fputcsv($fh,$record,';','"','');
fclose($fh);chmod($csv,0600);
function launch(array $args): array {
    $cmd=array_merge([PHP_BINARY,__DIR__.'/../ops/jtl-tracking-csv-stage.php'],$args);
    $p=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    if (!is_resource($p)) throw new RuntimeException('stage process unavailable');
    fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);
    $code=proc_close($p);
    return [$code,$out,$err];
}
try {
    [$code,$out,$err]=launch(['--input='.$csv]);
    $dry=json_decode($out,true);
    if ($code!==0||!is_array($dry)||$dry['rows']!==1||$dry['events']!==1) throw new RuntimeException('dry-run failed: '.$err);
    [$code,$out,$err]=launch(['--input='.$csv,'--db='.$db,
        '--expected-count=1','--sha256='.$dry['sha256'],'--apply']);
    $applied=json_decode($out,true);
    if ($code!==0||!is_array($applied)||$applied['mode']!=='private_stage_created'||!is_file($db))
        throw new RuntimeException('private stage failed: '.$err);
    $pdo=new PDO('sqlite:'.$db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    if ((int)$pdo->query('SELECT COUNT(*) FROM packages')->fetchColumn()!==1
        || (int)$pdo->query('SELECT COUNT(*) FROM tracking_events')->fetchColumn()!==1)
        throw new RuntimeException('stage rows missing');
    $pdo=null;
    [$code]=$again=launch(['--input='.$csv,'--db='.$db,
        '--expected-count=1','--sha256='.$dry['sha256'],'--apply']);
    if ($code===0) throw new RuntimeException('stage silently overwrote prior snapshot');
    echo "HW24 tracking-stage smoke passed: 1 package / 1 event; no overwrite\n";
} finally {
    foreach ([$db,$csv] as $file) if (is_file($file)) unlink($file);
    rmdir($folder);
}
