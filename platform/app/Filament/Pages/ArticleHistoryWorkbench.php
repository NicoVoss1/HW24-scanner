<?php
declare(strict_types=1);
namespace App\Filament\Pages;

use Filament\Pages\Page;
use PDO;

final class ArticleHistoryWorkbench extends Page
{
    protected static ?string $title='Artikelhistorie';
    protected static ?string $navigationLabel='Artikelhistorie';
    protected static ?string $slug='artikelhistorie';
    protected static ?int $navigationSort=25;
    protected string $view='filament.pages.article-history-workbench';
    public function getMaxContentWidth(): \Filament\Support\Enums\Width { return \Filament\Support\Enums\Width::Full; }
    public string $scope='extended';
    public string $search='';
    public string $kind='';
    public string $run='';
    public string $field='';
    public int $pageNumber=1;
    public function updated($name): void { if(in_array($name,['search','kind','run','field','scope'],true))$this->pageNumber=1;if($name==='scope'){$this->run='';$this->field='';} }
    public function selectRun(int $id): void { $this->run=(string)$id;$this->pageNumber=1; }
    public function movePage(int $delta): void { $this->pageNumber=max(1,min(100000,$this->pageNumber+max(-1,min(1,$delta)))); }

    public static function canAccess(): bool
    {
        return QueryAuditWorkbench::canAccess();
    }
    public function mount(): void { abort_unless(static::canAccess(),403); }
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(),403);
        $empty=['available'=>false,'runs'=>[],'events'=>[],'total'=>0,'labels'=>[],'scopeLabel'=>'','fieldCount'=>0];
        try{
            $dir=dirname(base_path(),2).'/article-history';
            $expanded=$this->scope==='extended'&&is_file($dir.'/history-expanded.sqlite');
            $path=$dir.($expanded?'/history-expanded.sqlite':'/history.sqlite');
            if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0077)!==0||!is_file($path)||is_link($path)||(fileperms($path)&0077)!==0)return $empty;
            require_once base_path('ops/hw24-capture-article-history.php');
            $labels=$expanded?\Hw24ExtendedArticleHistoryCapture::FIELDS:\Hw24ArticleHistoryCapture::FIELDS;
            $db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $db->exec('PRAGMA query_only=ON; PRAGMA busy_timeout=3000');
            $runs=$db->query("SELECT r.*, (SELECT COUNT(*) FROM history_events e WHERE e.run_id=r.id AND e.kind='new') AS new_count,
                (SELECT COUNT(DISTINCT article_id) FROM history_events e WHERE e.run_id=r.id AND e.kind='changed') AS changed_count,
                (SELECT COUNT(*) FROM history_events e WHERE e.run_id=r.id AND e.kind='missing') AS missing_count
                FROM history_runs r ORDER BY r.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            $clauses=[];$params=[];
            if(in_array($this->kind,['new','changed','missing'],true)){$clauses[]='e.kind=?';$params[]=$this->kind;}
            if(preg_match('/^[1-9][0-9]{0,15}$/D',$this->run)){$clauses[]='e.run_id=?';$params[]=(int)$this->run;}
            if(array_key_exists($this->field,$labels)){$clauses[]='e.field_key=?';$params[]=$this->field;}
            $search=mb_substr(trim($this->search),0,100);
            if($search!==''){$clauses[]='(instr(COALESCE(e.ean,\'\'),?)>0 OR instr(COALESCE(e.article_number,\'\'),?)>0 OR instr(lower(COALESCE(e.article_name,\'\')),lower(?))>0)';array_push($params,$search,$search,$search);}
            $where=$clauses?' WHERE '.implode(' AND ',$clauses):'';
            $q=$db->prepare('SELECT COUNT(*) FROM history_events e'.$where);$q->execute($params);$total=(int)$q->fetchColumn();
            $offset=(max(1,min($this->pageNumber,max(1,(int)ceil($total/100))))-1)*100;
            $q=$db->prepare('SELECT e.*,r.snapshot_at,r.captured_at,
                (SELECT p.snapshot_at FROM history_runs p WHERE p.id<r.id ORDER BY p.id DESC LIMIT 1) AS previous_at
                FROM history_events e JOIN history_runs r ON r.id=e.run_id'.$where.' ORDER BY e.id DESC LIMIT 100 OFFSET '.$offset);
            $q->execute($params);
            $events=$q->fetchAll(PDO::FETCH_ASSOC);
            $versions=$db->prepare("SELECT fields_json FROM history_versions WHERE article_id=? AND run_id<=? AND kind<>'missing' ORDER BY run_id DESC LIMIT 1");
            $observations=config('hw24_jtl_reference_observations',[]);
            foreach($events as &$e){
                foreach(['old_value'=>-1,'new_value'=>0] as $side=>$delta){
                    $version=[];
                    if(in_array($e['field_key'],['kHersteller','kWarengruppe'],true)){
                        $versions->execute([$e['article_id'],(int)$e['run_id']+$delta]);
                        $v=$versions->fetchColumn();
                        if(is_string($v))$version=json_decode($v,true,32,JSON_THROW_ON_ERROR);
                    }
                    $e[$side.'_display']=self::displayValue((string)($e['field_key']??''),$e[$side],$version,$observations);
                }
            }
            unset($e);
            return ['available'=>true,'runs'=>$runs,'events'=>$events,'total'=>$total,'page'=>intdiv($offset,100)+1,'labels'=>$labels,'fieldCount'=>count($labels),'scopeLabel'=>$expanded?'Erweiterte Spiegelhistorie':'Bisherige Spiegelhistorie (19 Felder)'];
        }catch(\Throwable){return $empty;}
    }
    public static function displayValue(string $field,?string $value,array $version,array $observations):string
    {
        if($value===null)return 'Nicht belegt';
        if($value==='')return 'Leer';
        if(in_array($field,['cLagerAktiv','cLagerKleinerNull','cTeilbar','cAktiv','cTopArtikel','cNeu'],true)){
            return match(strtoupper($value)){'Y','1'=>'Ja ('.$value.')','N','0'=>'Nein ('.$value.')',default=>$value};
        }
        $name=match($field){
            'kHersteller'=>($version['kHersteller']??null)===$value?($version['hersteller']??null):null,
            'kWarengruppe'=>($version['kWarengruppe']??null)===$value?($version['warengruppe']??null):null,
            'kLieferStatus'=>$observations['delivery_status_by_language'][$value.':1']??null,
            'kSteuerklasse'=>$observations['tax_classes'][$value]['name']??null,
            'einheit_id'=>$observations['mass_unit_examples'][$value]['display_code']??null,
            default=>null,
        };
        if(in_array($field,['kHersteller','kWarengruppe','kLieferStatus','kSteuerklasse','einheit_id','kVersandklasse','kBenutzerLetzteAenderung'],true)){
            $dated=in_array($field,['kLieferStatus','kSteuerklasse','einheit_id'],true);
            return is_string($name)&&trim($name)!==''?$name.' (ID '.$value.')'.($dated?' · Referenz 23.11.2026':''):'Klartext nicht übermittelt (ID '.$value.')';
        }
        return $value;
    }
}
