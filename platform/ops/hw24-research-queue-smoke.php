<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$name=(string)getenv('HW24_SMOKE_DB');
if(!preg_match('/^hw24_queue_smoke_[a-f0-9]{16}$/D',$name))throw new RuntimeException('Temporary DB only');
require __DIR__.'/../app/Services/ResearchQueue.php';
function conn(string $name):PDO{
    return new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname='.$name.';charset=utf8mb4','root','',[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
}
function check(bool $ok,string $msg):void{
    if(!$ok)throw new RuntimeException($msg);
    echo 'PASS: '.$msg.PHP_EOL;
}
$db=conn($name);$db2=conn($name);
$tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);sort($tables);
check($db->query('SELECT DATABASE()')->fetchColumn()===$name&&$tables===['hw24_research_events','hw24_research_gate','hw24_research_jobs'],'isolated queue schema');
$a=new App\Services\ResearchQueue($db);$b=new App\Services\ResearchQueue($db2);
$x=$a->enqueue('4006381333931','test-a');
$y=$b->enqueue('04006381333931','test-b');
check($x['created']&&!$y['created']&&$x['id']===$y['id'],'canonical GTIN shared deduplication');
$z=$b->enqueue('5901234123457','test-b');
check($z['created']&&$z['id']!==$x['id'],'distinct GTIN separate job');
try{$a->enqueue('4006381333932','test-a');throw new RuntimeException('accepted invalid GTIN');}catch(InvalidArgumentException){}
check((int)$db->query('SELECT COUNT(*) FROM hw24_research_jobs')->fetchColumn()===2,'invalid GTIN rejected');
$l=$a->claim('worker-a');
check($l!==null&&$l['id']===$x['id']&&$b->claim('worker-b')===null,'exclusive shared lease on two connections');
foreach([['worker-b',$l['token']],['worker-a',str_repeat('0',32)]] as [$actor,$token]){
    $rejected=false;
    try{$b->finish($l['id'],$token,$actor,'completed');}catch(RuntimeException){$rejected=true;}
    check($rejected,'wrong worker or token rejected');
}
check($a->finish($l['id'],$l['token'],'worker-a','rate_limited',120)==='retry','rate limit schedules retry');
check($b->claim('worker-b')===null,'global rate pause blocks other job');
check(strtotime($a->status($x['id'])['next_attempt_utc'].' UTC')>=time()+115,'Retry-After honored');
$db->exec("UPDATE hw24_research_gate SET next_allowed_utc='2000-01-01 00:00:00'");
$l2=$b->claim('worker-b');
check($l2!==null&&$l2['id']===$z['id'],'next eligible job processed');
$db->exec("UPDATE hw24_research_jobs SET lease_until_utc='2000-01-01 00:00:00' WHERE id=".(int)$z['id']);
check($a->claim('worker-a')===null&&$a->status($z['id'])['status']==='retry','expired lease safely recovered');
$rejected=false;
try{$b->finish($l2['id'],$l2['token'],'worker-b','completed');}catch(RuntimeException){$rejected=true;}
check($rejected,'expired worker cannot acknowledge');
$db->exec("UPDATE hw24_research_gate SET next_allowed_utc='2000-01-01 00:00:00'");
$db->exec("UPDATE hw24_research_jobs SET next_attempt_utc='2000-01-01 00:00:00' WHERE id=".(int)$x['id']);
$l3=$a->claim('worker-a');
check($l3!==null&&$l3['id']===$x['id']&&$l3['attempt']===2,'retry resumes persistent job');
check($a->finish($l3['id'],$l3['token'],'worker-a','completed')==='completed','trusted worker completion');
check($b->enqueue('4006381333931','test-b')['status']==='completed','repeat scan reuses completed job');
check(!array_key_exists('leased_by',$b->status($x['id']))&&!array_key_exists('lease_token',$b->status($x['id'])),'status hides actors and tokens');
$b->requeue($x['id'],'test-b');
check($b->status($x['id'])['status']==='queued'&&(int)$b->status($x['id'])['attempt_count']===0,'explicit refresh resets attempts');
check((int)$db->query('SELECT COUNT(*) FROM hw24_research_events')->fetchColumn()>=10,'audit events retained');
echo "HW24 RESEARCH QUEUE SMOKE PASS\n";
