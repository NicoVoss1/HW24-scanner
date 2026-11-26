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
            foreach($changes as $pos=>$value){
                $key=(string)$pos;
                if(!in_array($key,$positions,true)||!self::editable($r['name'],(int)$key,$r['formulas'])||!is_string($value)||mb_strlen($value)>30000||str_contains($value,"\0"))throw new RuntimeException('Invalid field');
                $before=(string)($r['values'][$key]??'');
                if($before===$value)continue;
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
