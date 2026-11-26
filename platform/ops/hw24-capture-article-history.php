<?php
declare(strict_types=1);

/** Standalone CLI; uses existing read-only mirror credentials and private SQLite.
 * Never writes to MariaDB/JTL. No raw article/customer data is committed to GitHub.
 */
final class Hw24ArticleHistoryCapture
{
    public const FIELDS=[
        'artikelnummer'=>'Artikelnummer','ean'=>'EAN / GTIN','artikelname'=>'Artikelname','hersteller'=>'Hersteller',
        'warengruppe'=>'Warengruppe','label_namen'=>'Kennzeichnungen','artikelgewicht'=>'Artikelgewicht',
        'versandgewicht'=>'Versandgewicht','herkunftsland'=>'Herkunftsland','menge'=>'Inhalt/Menge',
        'einheit_id'=>'Maßeinheit (ID)','kWarengruppe'=>'Warengruppe (ID)','kHersteller'=>'Hersteller (ID)',
        'kLieferStatus'=>'Lieferstatus (ID)','kSteuerklasse'=>'Steuerklasse (ID)','kVersandklasse'=>'Versandklasse (ID)',
        'cLagerAktiv'=>'Bestandsführung aktiv','cLagerKleinerNull'=>'Überverkäufe möglich','cTeilbar'=>'Stückzahl teilbar',
    ];
    public static function readMirror(PDO $db): array
    {
        $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $db->beginTransaction();
        try {
            $meta=$db->query('SELECT source_id,last_completed_utc,last_row_count,age_minutes FROM hw24_hub_mirror_state ORDER BY last_completed_utc DESC,source_id LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
            $m=$meta[0]??null;
            if(!$m||!is_numeric($m['age_minutes'])||(int)$m['age_minutes']<0||(int)$m['age_minutes']>45||
                !is_numeric($m['last_row_count'])||(int)$m['last_row_count']<1||
                (isset($meta[1])&&$meta[1]['last_completed_utc']===$m['last_completed_utc']))
                throw new RuntimeException('Aktueller eindeutiger Spiegel nicht bestätigt.');
            $q=$db->prepare('SELECT d.*,p.jtl_article_id AS peer_id,p.kWarengruppe,p.kHersteller,p.kLieferStatus,p.kSteuerklasse,p.kVersandklasse,p.cLagerAktiv,p.cLagerKleinerNull,p.cTeilbar FROM hw24_hub_article_details d LEFT JOIN hw24_hub_peer_source p ON p.source_id=d.source_id AND p.jtl_article_id=d.jtl_article_id WHERE d.source_id=? ORDER BY d.jtl_article_id');
            $q->execute([$m['source_id']]);$rows=[];
            while($row=$q->fetch(PDO::FETCH_ASSOC)){
                $id=(int)$row['jtl_article_id'];
                if($id<1||isset($rows[$id])||(int)($row['peer_id']??0)!==$id)throw new RuntimeException('Spiegelprojektion nicht eindeutig/vollständig.');
                $fields=[];
                foreach(self::FIELDS as $key=>$label){
                    if(!array_key_exists($key,$row))throw new RuntimeException('Feld fehlt in Projektion.');
                    $v=$row[$key]===null?null:(string)$row[$key];
                    if($v!==null&&in_array($key,['artikelgewicht','versandgewicht','menge'],true)&&preg_match('/^-?\d+\.\d+$/D',$v))$v=rtrim(rtrim($v,'0'),'.');
                    $fields[$key]=$v;
                }
                $rows[$id]=$fields;
            }
            if(count($rows)!==(int)$m['last_row_count'])throw new RuntimeException('Spiegelzeilenzahl stimmt nicht.');
            $db->commit();return [$m,$rows];
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public static function schema(PDO $db): void
    {
        $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=10000;');
        $db->exec('CREATE TABLE IF NOT EXISTS history_runs(id INTEGER PRIMARY KEY, source_id TEXT NOT NULL, snapshot_at TEXT NOT NULL, captured_at TEXT NOT NULL, row_count INTEGER NOT NULL, fingerprint TEXT NOT NULL, kind TEXT NOT NULL, UNIQUE(source_id,snapshot_at));
          CREATE TABLE IF NOT EXISTS history_current(article_id INTEGER PRIMARY KEY, fields_json TEXT NOT NULL);
          CREATE TABLE IF NOT EXISTS history_versions(run_id INTEGER NOT NULL REFERENCES history_runs(id), article_id INTEGER NOT NULL, kind TEXT NOT NULL, fields_json TEXT, PRIMARY KEY(run_id,article_id));
          CREATE TABLE IF NOT EXISTS history_events(id INTEGER PRIMARY KEY, run_id INTEGER NOT NULL REFERENCES history_runs(id), article_id INTEGER NOT NULL, article_number TEXT, ean TEXT, article_name TEXT, kind TEXT NOT NULL, field_key TEXT, old_value TEXT, new_value TEXT);
          CREATE INDEX IF NOT EXISTS history_events_run ON history_events(run_id,id);
          CREATE INDEX IF NOT EXISTS history_events_article ON history_events(article_id,id);');
    }
    public static function capture(PDO $db,array $m,array $rows): array
    {
        if(count($rows)!==(int)$m['last_row_count']||!$rows)throw new RuntimeException('Incomplete snapshot');
        ksort($rows,SORT_NUMERIC);
        $json=static fn($x)=>json_encode($x,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $hash=hash('sha256',$json($rows));
        self::schema($db);
        $db->exec('BEGIN IMMEDIATE');
        try {
            $q=$db->prepare('SELECT fingerprint FROM history_runs WHERE source_id=? AND snapshot_at=?');
            $q->execute([$m['source_id'],$m['last_completed_utc']]);$seen=$q->fetchColumn();
            if($seen!==false){
                if(!hash_equals($seen,$hash))throw new RuntimeException('Snapshot timestamp reused with different content.');
                $db->exec('COMMIT');return ['status'=>'already_captured','articles'=>count($rows)];
            }
            $last=$db->query('SELECT * FROM history_runs ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if($last&&strcmp($m['last_completed_utc'],$last['snapshot_at'])<=0)throw new RuntimeException('Snapshot chronology uncertain.');
            $baseline=!$last||$last['source_id']!==$m['source_id'];
            $old=[];
            if(!$baseline)foreach($db->query('SELECT article_id,fields_json FROM history_current') as $r)
                $old[(int)$r['article_id']]=json_decode($r['fields_json'],true,32,JSON_THROW_ON_ERROR);
            $q=$db->prepare('INSERT INTO history_runs(source_id,snapshot_at,captured_at,row_count,fingerprint,kind) VALUES(?,?,?,?,?,?)');
            $q->execute([$m['source_id'],$m['last_completed_utc'],gmdate('Y-m-d H:i:s'),count($rows),$hash,$baseline?'baseline':'comparison']);
            $run=(int)$db->lastInsertId();
            $version=$db->prepare('INSERT INTO history_versions(run_id,article_id,kind,fields_json) VALUES(?,?,?,?)');
            $event=$db->prepare('INSERT INTO history_events(run_id,article_id,article_number,ean,article_name,kind,field_key,old_value,new_value) VALUES(?,?,?,?,?,?,?,?,?)');
            $upsert=$db->prepare('INSERT INTO history_current(article_id,fields_json) VALUES(?,?) ON CONFLICT(article_id) DO UPDATE SET fields_json=excluded.fields_json');
            $delete=$db->prepare('DELETE FROM history_current WHERE article_id=?');
            $counts=['status'=>$baseline?'baseline':'compared','run'=>$run,'articles'=>count($rows),'new'=>0,'changed'=>0,'fields'=>0,'missing'=>0];
            if($baseline)$db->exec('DELETE FROM history_current');
            foreach($rows as $id=>$row){
                $prev=$old[$id]??null;
                if($baseline||$prev===null){
                    $version->execute([$run,$id,$baseline?'baseline':'new',$json($row)]);
                    $upsert->execute([$id,$json($row)]);
                    if(!$baseline){$counts['new']++;$event->execute([$run,$id,$row['artikelnummer'],$row['ean'],$row['artikelname'],'new',null,null,null]);}
                }elseif($prev!==$row){
                    $counts['changed']++;
                    $version->execute([$run,$id,'changed',$json($row)]);$upsert->execute([$id,$json($row)]);
                    foreach(self::FIELDS as $key=>$label)if($prev[$key]!==$row[$key]){
                        $counts['fields']++;
                        $event->execute([$run,$id,$row['artikelnummer'],$row['ean'],$row['artikelname'],'changed',$key,$prev[$key],$row[$key]]);
                    }
                }
            }
            if(!$baseline)foreach($old as $id=>$row)if(!isset($rows[$id])){
                $counts['missing']++;$version->execute([$run,$id,'missing',null]);$delete->execute([$id]);
                $event->execute([$run,$id,$row['artikelnummer'],$row['ean'],$row['artikelname'],'missing',null,null,null]);
            }
            $db->exec('COMMIT');return $counts;
        }catch(Throwable $e){$db->exec('ROLLBACK');throw $e;}
    }
}
if(PHP_SAPI==='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
    try{
        umask(0077);
        $private='/var/www/vhosts/hairworld24.de/hw24-console-private';
        $cfgPath=$private.'/hw24-hub-reader.json';$dir=$private.'/article-history';$path=$dir.'/history.sqlite';
        if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0077)!==0||is_link($path)||
            (file_exists($path)&&(fileperms($path)&0077)!==0)||!is_file($cfgPath)||is_link($cfgPath)||(fileperms($cfgPath)&0077)!==0)
            throw new RuntimeException('Private paths not confirmed');
        $cfg=json_decode(file_get_contents($cfgPath),true,16,JSON_THROW_ON_ERROR);
        if(($cfg['user']??null)!=='hw24_hub_reader'||!is_string($cfg['password']??null))throw new RuntimeException('Reader not confirmed');
        $db=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=hw24_artikelmanager;charset=utf8mb4',$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
        [$m,$rows]=Hw24ArticleHistoryCapture::readMirror($db);
        $history=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        echo json_encode(Hw24ArticleHistoryCapture::capture($history,$m,$rows)+['source_id'=>$m['source_id'],'snapshot_at'=>$m['last_completed_utc']],JSON_THROW_ON_ERROR).PHP_EOL;
    }catch(Throwable $e){fwrite(STDERR,"STOP: Historie nicht bestätigt. Keine JTL-Schreibaktion. ".get_class($e).PHP_EOL);exit(1);}
}
