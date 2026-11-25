<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$dbname=(string)getenv('HW24_SMOKE_DB');
$dir=(string)getenv('HW24_PARALLEL_DIR');
$worker=(string)($argv[1]??'');
if(!preg_match('/^hw24_queue_smoke_[a-f0-9]{16}$/D',$dbname)||!is_dir($dir)||!in_array($worker,['a','b'],true))
    throw new RuntimeException('Temporary parallel test only');
require __DIR__.'/../app/Services/ResearchQueue.php';
$db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$dbname.';charset=utf8mb4','root','',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
$q=new App\Services\ResearchQueue($db);
file_put_contents($dir.'/ready-'.$worker,'1');
$deadline=microtime(true)+15;
while(!is_file($dir.'/go')){if(microtime(true)>$deadline)throw new RuntimeException('Barrier timeout');usleep(10000);}
$job=$q->enqueue($worker==='a'?'4006381333931':'04006381333931','parallel-'.$worker);
$lease=$q->claim('parallel-'.$worker);
echo json_encode(['job'=>$job['id'],'created'=>$job['created'],'claimed'=>$lease!==null],JSON_THROW_ON_ERROR).PHP_EOL;
