<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

/** Private, append-audited overlay. The original workbook cells and XLSX never change. */
final class HubHistoricalEdits
{
    public static function connect(bool $write=false):PDO
    {
        $dir=dirname(base_path(),2).'/historical-reference';$path=$dir.'/reference.sqlite';
        if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0077)!==0||!is_file($path)||is_link($path)||(fileperms($path)&0077)!==0)throw new RuntimeException('Archive unavailable');
        $db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA busy_timeout=5000');
        if(!$write)$db->exec('PRAGMA query_only=ON');
        return $db;
    }
    public static function installed(PDO $db):bool
    {
        return (int)$db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name IN ('workbook_row_edits','workbook_edit_events')")->fetchColumn()===2;
    }
    public static function install(PDO $db):void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS workbook_row_edits(
            sheet_id INTEGER NOT NULL,row_number INTEGER NOT NULL,revision INTEGER NOT NULL,
            patch_json TEXT NOT NULL,PRIMARY KEY(sheet_id,row_number));
            CREATE TABLE IF NOT EXISTS workbook_edit_events(
            id INTEGER PRIMARY KEY,sheet_id INTEGER NOT NULL,row_number INTEGER NOT NULL,revision INTEGER NOT NULL,
            position INTEGER NOT NULL,old_value TEXT NOT NULL,new_value TEXT NOT NULL,actor TEXT NOT NULL,at_utc TEXT NOT NULL);
            CREATE INDEX IF NOT EXISTS workbook_edit_events_row ON workbook_edit_events(sheet_id,row_number,id)");
    }
    public static function editable(string $sheet,int $position,array $formulas):bool
    {
        // Identity and cached formula cells need a dedicated reconciliation workflow.
        if($position<1||isset($formulas[(string)$position]))return false;
        return match($sheet){
            'JTL-Import aus AB 1Produkte_ALL'=>$position<=68&&!in_array($position,[4,5],true),
            'Artikel_im_Lager_nicht_angelegt'=>$position<=14&&$position!==3,
            default=>false,
        };
    }
    public static function row(PDO $db,int $sheet,int $row):array
    {
        $q=$db->prepare('SELECT r.values_json,r.formulas_json,s.name FROM workbook_rows r JOIN workbook_sheets s ON s.id=r.sheet_id WHERE r.sheet_id=? AND r.row_number=?');
        $q->execute([$sheet,$row]);$r=$q->fetch(PDO::FETCH_ASSOC);
        if(!$r)throw new RuntimeException('Row not found');
        $original=json_decode($r['values_json'],true,32,JSON_THROW_ON_ERROR);
        $formulas=json_decode($r['formulas_json'],true,32,JSON_THROW_ON_ERROR);
        $revision=0;$patch=[];
        if(self::installed($db)){
            $q=$db->prepare('SELECT revision,patch_json FROM workbook_row_edits WHERE sheet_id=? AND row_number=?');
            $q->execute([$sheet,$row]);$e=$q->fetch(PDO::FETCH_ASSOC);
            if($e){$revision=(int)$e['revision'];$patch=json_decode($e['patch_json'],true,32,JSON_THROW_ON_ERROR);}
        }
        $q=$db->prepare('SELECT position,name FROM workbook_columns WHERE sheet_id=? ORDER BY position');$q->execute([$sheet]);
        return ['name'=>$r['name'],'revision'=>$revision,'values'=>array_replace($original,$patch),'original'=>$original,'formulas'=>$formulas,'patch'=>$patch,'columns'=>$q->fetchAll(PDO::FETCH_ASSOC)];
    }
    /** Existing values only. Global choices are limited to categorical fields;
     * product-specific values require exact validated GTIN identity. */
    public static function choices(PDO $db,int $sheet,int $row,?array $r=null):array
    {
        $r??=self::row($db,$sheet,$row);
        $main=$r['name']==='JTL-Import aus AB 1Produkte_ALL';
        $identity=static function(array $v)use($main):?string{
            $keys=$main?['4','5']:['3'];$ids=[];
            foreach($keys as $k){
                $value=$v[$k]??'';
                if(!is_string($value)||trim($value)==='')continue;
                try{$ids[ResearchQueue::canonical(trim($value))]=true;}catch(\Throwable){}
            }
            return count($ids)===1?(string)array_key_first($ids):null;
        };
        $same=[];$id=$identity($r['original']);
        if($id!==null){
            $paths=$main?['4','5']:['3'];
            $conditions=[];$params=[$sheet];
            foreach($paths as $p){
                $conditions[]="ltrim(trim(CAST(json_extract(values_json,'$.\"".$p."\"') AS TEXT)),'0')=?";
                $params[]=ltrim($id,'0');
            }
            $q=$db->prepare('SELECT values_json FROM workbook_rows WHERE sheet_id=? AND ('.implode(' OR ',$conditions).') LIMIT 51');
            $q->execute($params);$matches=$q->fetchAll(PDO::FETCH_COLUMN);
            if(count($matches)<=50)foreach($matches as $json){
                $values=json_decode($json,true,32,JSON_THROW_ON_ERROR);
                if($identity($values)===$id)$same[]=$values;
            }
        }
        $out=[];
        foreach($r['columns'] as $c){
            $p=(int)$c['position'];$key=(string)$p;
            if(!self::editable($r['name'],$p,$r['formulas']))continue;
            $values=[(string)($r['values'][$key]??''),(string)($r['original'][$key]??'')];
            $categorical=$main&&(
                in_array($p,[11,12,49,62,63],true)||
                in_array(mb_strtolower(trim((string)$c['name'])),['hersteller','warengruppe','artikeltyp','steuerklasse','lieferstatus','versandklasse','maßeinheit','verkaufseinheit','herkunftsland','währung','artikel aktiv','bestandsführung aktiv','überverkäufe möglich','stückzahl teilbar'],true)
            );
            if($categorical){
                $q=$db->prepare("SELECT DISTINCT CAST(json_extract(values_json,?) AS TEXT) AS v FROM workbook_rows WHERE sheet_id=? AND json_type(values_json,?)='text' AND trim(CAST(json_extract(values_json,?) AS TEXT))<>'' AND length(CAST(json_extract(values_json,?) AS TEXT))<=300 ORDER BY v COLLATE NOCASE LIMIT 201");
                $path='$."'.$p.'"';$q->execute([$path,$sheet,$path,$path,$path]);
                $found=$q->fetchAll(PDO::FETCH_COLUMN);
                // Large or unconfirmed domains are not treated as standard lists.
                if(count($found)<=200)array_push($values,...$found);
            }else{
                foreach($same as $v)if(is_string($v[$key]??null)&&trim($v[$key])!=='')$values[]=$v[$key];
            }
            $out[$key]=array_values(array_unique(array_filter($values,fn($v)=>is_string($v)&&mb_strlen($v)<=30000&&!str_contains($v,"\0")),SORT_STRING));
        }
        return $out;
    }

    public static function save(PDO $db,int $sheet,int $row,int $expected,array $changes,string $actor):int
    {
        if(!preg_match('/^[0-9]{1,18}$/D',$actor)||$sheet<1||$row<1||$expected<0||count($changes)>68)throw new RuntimeException('Invalid edit');
        $db->exec('BEGIN IMMEDIATE');
        try{
            self::install($db);
            $r=self::row($db,$sheet,$row);
            if($r['revision']!==$expected)throw new RuntimeException('Revision conflict');
            $positions=array_map(fn($c)=>(string)$c['position'],$r['columns']);
            $patch=$r['patch'];$events=[];$revision=$expected+1;
            $choices=self::choices($db,$sheet,$row,$r);
            foreach($changes as $pos=>$value){
                $key=(string)$pos;
                if(!in_array($key,$positions,true)||!self::editable($r['name'],(int)$key,$r['formulas'])||!is_string($value)||mb_strlen($value)>30000||str_contains($value,"\0"))throw new RuntimeException('Invalid field');
                $before=(string)($r['values'][$key]??'');
                if($before===$value)continue;
                if(!in_array($value,$choices[$key]??[],true))throw new RuntimeException('Value not in verified dropdown');
                $patch[$key]=$value;$events[]=[$sheet,$row,$revision,(int)$key,$before,$value,$actor,gmdate('Y-m-d\TH:i:s\Z')];
            }
            if($events){
                $q=$db->prepare('INSERT INTO workbook_row_edits(sheet_id,row_number,revision,patch_json) VALUES(?,?,?,?) ON CONFLICT(sheet_id,row_number) DO UPDATE SET revision=excluded.revision,patch_json=excluded.patch_json');
                $q->execute([$sheet,$row,$revision,json_encode((object)$patch,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
                $q=$db->prepare('INSERT INTO workbook_edit_events(sheet_id,row_number,revision,position,old_value,new_value,actor,at_utc) VALUES(?,?,?,?,?,?,?,?)');
                foreach($events as $e)$q->execute($e);
            }
            $db->exec('COMMIT');return $events?$revision:$expected;
        }catch(\Throwable $e){$db->exec('ROLLBACK');throw $e;}
    }
    public static function events(PDO $db,int $sheet,int $row):array
    {
        if(!self::installed($db))return [];
        $q=$db->prepare('SELECT e.revision,e.position,c.name,e.old_value,e.new_value,e.actor,e.at_utc FROM workbook_edit_events e LEFT JOIN workbook_columns c ON c.sheet_id=e.sheet_id AND c.position=e.position WHERE e.sheet_id=? AND e.row_number=? ORDER BY e.id DESC LIMIT 100');
        $q->execute([$sheet,$row]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Effective read-only projection used by table, search and research. */
    public static function projection(PDO $db):string
    {
        if(!self::installed($db))return 'workbook_rows';
        return "(SELECT r.sheet_id,r.row_number,json_patch(r.values_json,COALESCE(e.patch_json,'{}')) AS values_json,
            CASE WHEN s.name='JTL-Import aus AB 1Produkte_ALL' THEN CASE
              WHEN json_extract(json_patch(r.values_json,COALESCE(e.patch_json,'{}')),'$.\"63\"')='LP geb.' THEN 'LP geb.'
              WHEN json_extract(json_patch(r.values_json,COALESCE(e.patch_json,'{}')),'$.\"62\"')='WE geb.' THEN 'WE geb.' ELSE '' END
            ELSE r.status END AS status,COALESCE(e.revision,0) AS revision
            FROM workbook_rows r JOIN workbook_sheets s ON s.id=r.sheet_id LEFT JOIN workbook_row_edits e ON e.sheet_id=r.sheet_id AND e.row_number=r.row_number)";
    }
}
