<?php
declare(strict_types=1);
/*
 * Isolierter JTL-Tracking-CSV-Stager, keine JTL-/MariaDB-/Amazon-Mutation.
 * Usage:
 * php platform/ops/jtl-tracking-csv-stage.php --input=/private/tracking.csv
 * php platform/ops/jtl-tracking-csv-stage.php --input=/private/tracking.csv --db=/private/tracking-20261124.sqlite --expected-count=N --sha256=HASH --apply
 * --apply erstellt ausschließlich eine NEUE private SQLite-Datei (niemals Überschreiben).
 * Die Anwendung liest diesen Snapshot danach ausschließlich read-only.
 */
function failTracking(string $message): never { fwrite(STDERR,"Tracking-Staging: ".$message.PHP_EOL); exit(2); }
// Once staging begins, throw instead of exit so catch/finally remove temporary customer data.
function abortTracking(string $message): never { throw new RuntimeException($message); }
function optionsTracking(array $args): array {
    $out=[];
    foreach ($args as $arg) {
        if ($arg==='--apply') { $out['apply']=true; continue; }
        if (!preg_match('/^--([a-z0-9-]+)=(.+)$/D',$arg,$m) || isset($out[$m[1]]) ||
            !in_array($m[1],['input','db','sha256','expected-count'],true)) failTracking('unbekannte oder doppelte Option');
        $out[$m[1]]=$m[2];
    }
    if (!isset($out['input'])) failTracking('--input fehlt');
    $out['apply']=$out['apply']??false;
    return $out;
}
const COLUMNS_TRACKING=[
 'amazon_order_id','jtl_order','delivery_note','tracking_number','carrier',
 'package_id','shipment_id','state_id','event_at_raw','status_code',
 'event_text','provider_status','scan_location','country'
];
$opts=optionsTracking(array_slice($argv,1));
$path=realpath($opts['input']);
if (!$path || !is_file($path) || !is_readable($path) || filesize($path)>60*1024*1024) {
    failTracking('Quelle fehlt oder Datei zu groß (max. 60 MiB)');
}
$sha=hash_file('sha256',$path);
if (!is_string($sha)) failTracking('kein SHA256');
$fh=fopen($path,'rb');
if (!$fh) failTracking('CSV nicht lesbar');
$header=fgetcsv($fh,0,';','"','');
if (!is_array($header)) failTracking('CSV-Header fehlt');
$header[0]=preg_replace('/^\xEF\xBB\xBF/','',$header[0]);
if ($header!==COLUMNS_TRACKING) failTracking('CSV-Header stimmt nicht mit JTL-Export überein');
$rows=0;$events=0;$packages=[];
$temporary=null;$pdo=null;
try {
    if ($opts['apply']) {
        if (!extension_loaded('pdo_sqlite')) abortTracking('PDO_SQLITE fehlt');
        foreach (['db','expected-count','sha256'] as $key) if (!isset($opts[$key])) abortTracking('--apply benötigt --'.$key);
        if (!ctype_digit($opts['expected-count']) || (int)$opts['expected-count']<1
             || !preg_match('/^[0-9a-f]{64}$/iD',$opts['sha256'])
             || !hash_equals(strtolower($sha),strtolower($opts['sha256']))) abortTracking('Anzahl oder SHA256 nicht bestätigt');
        $privateRoot=realpath(__DIR__.'/../storage/app/private');
        $parent=realpath(dirname($opts['db']));
        if (!$privateRoot || !$parent || !str_starts_with($parent.'/',rtrim($privateRoot,'/').'/')
             || is_link($opts['db']) || file_exists($opts['db'])
             || ((fileperms($parent)&0077)!==0)) abortTracking('Zieldatei muss neu im privaten Storage-Verzeichnis (chmod 700) sein');
        $temporary=tempnam($parent,'.tracking-stage-');
        if (!$temporary) abortTracking('Temporärdatei konnte nicht erstellt werden');
        chmod($temporary,0600);
        $pdo=new PDO('sqlite:'.$temporary);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys=ON');
        $pdo->exec('CREATE TABLE snapshot_info(id INTEGER PRIMARY KEY CHECK(id=1),
            source_sha256 TEXT NOT NULL, imported_at_utc TEXT NOT NULL,
            source_rows INTEGER NOT NULL, package_count INTEGER NOT NULL, event_count INTEGER NOT NULL,
            time_zone_verified INTEGER NOT NULL DEFAULT 0)');
        $pdo->exec('CREATE TABLE packages(
            package_id INTEGER PRIMARY KEY, shipment_id INTEGER NOT NULL,
            amazon_order_id TEXT NOT NULL, jtl_order TEXT, delivery_note TEXT,
            tracking_number TEXT NOT NULL, carrier TEXT, UNIQUE(tracking_number,shipment_id))');
        $pdo->exec('CREATE INDEX ix_packages_amazon_order ON packages(amazon_order_id)');
        $pdo->exec('CREATE TABLE tracking_events(
            state_id INTEGER PRIMARY KEY, package_id INTEGER NOT NULL REFERENCES packages(package_id),
            event_at_raw TEXT NOT NULL, status_code INTEGER, event_text TEXT,
            provider_status TEXT, scan_location TEXT, country TEXT)');
        $pdo->exec('CREATE INDEX ix_events_package_date ON tracking_events(package_id,event_at_raw DESC)');
        $pdo->beginTransaction();
        $insP=$pdo->prepare('INSERT INTO packages(package_id,shipment_id,amazon_order_id,jtl_order,delivery_note,tracking_number,carrier) VALUES (?,?,?,?,?,?,?)');
        $insE=$pdo->prepare('INSERT INTO tracking_events(state_id,package_id,event_at_raw,status_code,event_text,provider_status,scan_location,country) VALUES (?,?,?,?,?,?,?,?)');
    }
    while (($row=fgetcsv($fh,0,';','"',''))!==false) {
        if ($row===[null]) continue;
        $rows++;
        if ($rows>50000 || count($row)!==14) abortTracking('CSV-Zeile unvollständig oder 50.000-Zeilen-Limit erreicht');
        $r=array_combine(COLUMNS_TRACKING,$row);
        foreach ($r as $key=>$v) {
            if (!is_string($v) || strlen($v)>1600) abortTracking('CSV-Feld zu lang');
            $r[$key]=trim($v)==='\N'?'':trim($v);
        }
        if (!preg_match('/^[0-9]{3}-[0-9]{7}-[0-9]{7}$/D',$r['amazon_order_id'])
            || !preg_match('/^[A-Za-z0-9_-]{5,100}$/D',$r['tracking_number'])
            || !preg_match('/^[1-9][0-9]{0,10}$/D',$r['package_id'])
            || !preg_match('/^[1-9][0-9]{0,10}$/D',$r['shipment_id'])) abortTracking('ungültiger Auftrags- oder Paketschlüssel');
        $pid=(int)$r['package_id'];
        $identity=$r['amazon_order_id'].'|'.$r['shipment_id'].'|'.$r['tracking_number'].'|'.$r['jtl_order'].'|'.$r['delivery_note'];
        if (isset($packages[$pid]) && $packages[$pid]!==$identity) abortTracking('Paket-ID mit widersprüchlichen Auftragsdaten');
        if (!isset($packages[$pid])) {
            $packages[$pid]=$identity;
            if ($pdo) $insP->execute([$pid,(int)$r['shipment_id'],$r['amazon_order_id'],
                $r['jtl_order'],$r['delivery_note'],$r['tracking_number'],$r['carrier']]);
        }
        if ($r['state_id']!=='') {
            if (!preg_match('/^[1-9][0-9]{0,10}$/D',$r['state_id']) ||
                !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d{1,3})?$/D',$r['event_at_raw']) ||
                ($r['status_code']!=='' && !ctype_digit($r['status_code']))) abortTracking('ungültiger Ereignisschlüssel oder Zeitpunkt');
            $events++;
            if ($pdo) $insE->execute([(int)$r['state_id'],$pid,$r['event_at_raw'],
                $r['status_code']===''?null:(int)$r['status_code'],
                $r['event_text'],$r['provider_status'],$r['scan_location'],$r['country']]);
        }
    }
    if ($rows===0) abortTracking('leere Exportdatei');
    if ($pdo) {
        if ($rows!==(int)$opts['expected-count']) abortTracking('Zeilenzahl nicht bestätigt');
        $pdo->prepare('INSERT INTO snapshot_info(id,source_sha256,imported_at_utc,source_rows,package_count,event_count,time_zone_verified) VALUES (1,?,?,?,?,?,0)')
            ->execute([$sha,gmdate('Y-m-d H:i:s'),$rows,count($packages),$events]);
        $pdo->commit();
        if ($pdo->query('PRAGMA integrity_check')->fetchColumn()!=='ok') abortTracking('SQLite-Integrität fehlgeschlagen');
        $pdo=null;
        chmod($temporary,0600);
        if (file_exists($opts['db']) || !rename($temporary,$opts['db'])) abortTracking('Ziel vorhanden oder atomarer Commit nicht möglich');
        $temporary=null;
    }
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    if ($temporary && is_file($temporary)) @unlink($temporary);
    failTracking('Importprüfung fehlgeschlagen (keine Quelldaten verändert): '.$e->getMessage());
} finally {
    fclose($fh);
}
echo json_encode([
    'ok'=>true,'mode'=>$opts['apply']?'private_stage_created':'validated_only',
    'rows'=>$rows,'packages'=>count($packages),'events'=>$events,
    'sha256'=>$sha,'snapshot_is_live'=>false,'timezone_verified'=>false,
],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE).PHP_EOL;
