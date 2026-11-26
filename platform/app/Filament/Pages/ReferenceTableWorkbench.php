<?php
declare(strict_types=1);
namespace App\Filament\Pages;

use Filament\Pages\Page;
use PDO;

class ReferenceTableWorkbench extends Page
{
    protected static ?string $title='Historische Artikeldaten';
    protected static ?string $navigationLabel='Historische Artikeldaten';
    protected static ?string $slug='historische-artikeldaten';
    protected static ?int $navigationSort=27;
    protected string $view='filament.pages.reference-table-workbench';
    protected const AMEISE=false;
    public string $sheet='';
    public string $search='';
    public string $filterColumn='';
    public string $filterValue='';
    public string $status='';
    public bool $showEmpty=false;
    public int $columnPage=1;
    public int $rowPage=1;
    public static function canAccess(): bool{return QueryAuditWorkbench::canAccess();}
    public function mount():void{abort_unless(static::canAccess(),403);}
    public function updated($name):void{if(in_array($name,['sheet','search','filterColumn','filterValue','status','showEmpty'],true)){$this->rowPage=1;if(in_array($name,['sheet','showEmpty'],true))$this->columnPage=1;}}
    public function step(string $axis,int $delta):void{
        if(!in_array($axis,['rowPage','columnPage'],true))return;
        $this->$axis=max(1,min(100000,$this->$axis+max(-1,min(1,$delta))));
    }
    protected function getViewData():array{
        abort_unless(static::canAccess(),403);
        $empty=['available'=>false,'sources'=>[],'selected'=>null,'columns'=>[],'allColumns'=>[],'rows'=>[],'total'=>0,'rp'=>1,'cp'=>1,'columnTotal'=>0,'ameise'=>static::AMEISE];
        try{
            $dir=dirname(base_path(),2).'/'.(static::AMEISE?'ameise-reference':'historical-reference');$path=$dir.'/reference.sqlite';
            if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0077)!==0||!is_file($path)||is_link($path)||(fileperms($path)&0077)!==0)return $empty;
            $db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $db->exec('PRAGMA query_only=ON; PRAGMA busy_timeout=3000');
            $sources=static::AMEISE?$db->query("SELECT id,export_date AS name,row_count FROM ameise_exports ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC):
                $db->query("SELECT s.id,s.name||' · Archiv #'||s.workbook_id AS name,s.row_count FROM workbook_sheets s ORDER BY s.workbook_id DESC,s.id")->fetchAll(PDO::FETCH_ASSOC);
            if(!$sources)return $empty;
            $ids=array_map(fn($r)=>(string)$r['id'],$sources);
            $id=in_array($this->sheet,$ids,true)?(int)$this->sheet:(int)$sources[0]['id'];
            $selected=null;foreach($sources as $source)if((int)$source['id']===$id){$selected=$source;break;}
            $ct=static::AMEISE?'ameise_columns':'workbook_columns';$rt=static::AMEISE?'ameise_rows':'workbook_rows';$fk=static::AMEISE?'export_id':'sheet_id';
            $q=$db->prepare("SELECT position,name,filled_count FROM $ct WHERE $fk=? ORDER BY position");$q->execute([$id]);$all=$q->fetchAll(PDO::FETCH_ASSOC);
            $cols=array_values(array_filter($all,fn($c)=>!static::AMEISE||$this->showEmpty||(int)$c['filled_count']>0));
            $cp=max(1,min($this->columnPage,max(1,(int)ceil(count($cols)/12))));
            $visible=array_slice($cols,($cp-1)*12,12);
            $where="$fk=?";$params=[$id];
            $search=mb_substr(trim($this->search),0,100);
            if($search!==''){$where.=" AND EXISTS (SELECT 1 FROM json_each(values_json) v WHERE instr(lower(CAST(v.value AS TEXT)),lower(?))>0)";$params[]=$search;}
            $positions=array_map(fn($c)=>(string)$c['position'],$all);
            if(in_array($this->filterColumn,$positions,true)&&$this->filterValue!==''){
                $where.=" AND EXISTS (SELECT 1 FROM json_each(values_json) v WHERE v.key=? AND instr(lower(CAST(v.value AS TEXT)),lower(?))>0)";
                array_push($params,$this->filterColumn,mb_substr($this->filterValue,0,200));
            }
            if(!static::AMEISE&&in_array($this->status,['LP geb.','WE geb.'],true)){$where.=' AND status=?';$params[]=$this->status;}
            $q=$db->prepare("SELECT COUNT(*) FROM $rt WHERE $where");$q->execute($params);$total=(int)$q->fetchColumn();
            $rp=max(1,min($this->rowPage,max(1,(int)ceil($total/50))));$offset=($rp-1)*50;
            $extra=static::AMEISE?'':' ,status';
            $q=$db->prepare("SELECT row_number,values_json $extra FROM $rt WHERE $where ORDER BY row_number LIMIT 50 OFFSET $offset");$q->execute($params);
            $rows=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
                $row['values']=json_decode($row['values_json'],true,32,JSON_THROW_ON_ERROR);
                unset($row['values_json']);$rows[]=$row;
            }
            return ['available'=>true,'sources'=>$sources,'selected'=>$selected,'columns'=>$visible,'allColumns'=>$all,'rows'=>$rows,'total'=>$total,'rp'=>$rp,'cp'=>$cp,'columnTotal'=>count($cols),'ameise'=>static::AMEISE];
        }catch(\Throwable){return $empty;}
    }
    public static function excelColumn(int $position):string{
        $s='';while($position>0){$position--;$s=chr(65+$position%26).$s;$position=intdiv($position,26);}return $s;
    }
}
