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
    public string $search='';
    public string $kind='';
    public string $run='';
    public string $field='';
    public int $pageNumber=1;
    public function updated($name): void { if(in_array($name,['search','kind','run','field'],true))$this->pageNumber=1; }
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
        $empty=['available'=>false,'runs'=>[],'events'=>[],'total'=>0,'labels'=>[]];
        try{
            $dir=dirname(base_path(),2).'/article-history';$path=$dir.'/history.sqlite';
            if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0077)!==0||!is_file($path)||is_link($path)||(fileperms($path)&0077)!==0)return $empty;
            require_once base_path('ops/hw24-capture-article-history.php');
            $db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $db->exec('PRAGMA query_only=ON; PRAGMA busy_timeout=3000');
            $runs=$db->query("SELECT r.*, (SELECT COUNT(*) FROM history_events e WHERE e.run_id=r.id AND e.kind='new') AS new_count,
                (SELECT COUNT(DISTINCT article_id) FROM history_events e WHERE e.run_id=r.id AND e.kind='changed') AS changed_count,
                (SELECT COUNT(*) FROM history_events e WHERE e.run_id=r.id AND e.kind='missing') AS missing_count
                FROM history_runs r ORDER BY r.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            $clauses=[];$params=[];
            if(in_array($this->kind,['new','changed','missing'],true)){$clauses[]='e.kind=?';$params[]=$this->kind;}
            if(preg_match('/^[1-9][0-9]{0,15}$/D',$this->run)){$clauses[]='e.run_id=?';$params[]=(int)$this->run;}
            if(array_key_exists($this->field,\Hw24ArticleHistoryCapture::FIELDS)){$clauses[]='e.field_key=?';$params[]=$this->field;}
            $search=mb_substr(trim($this->search),0,100);
            if($search!==''){$clauses[]='(instr(COALESCE(e.ean,\'\'),?)>0 OR instr(COALESCE(e.article_number,\'\'),?)>0 OR instr(lower(COALESCE(e.article_name,\'\')),lower(?))>0)';array_push($params,$search,$search,$search);}
            $where=$clauses?' WHERE '.implode(' AND ',$clauses):'';
            $q=$db->prepare('SELECT COUNT(*) FROM history_events e'.$where);$q->execute($params);$total=(int)$q->fetchColumn();
            $offset=(max(1,min($this->pageNumber,max(1,(int)ceil($total/100))))-1)*100;
            $q=$db->prepare('SELECT e.*,r.snapshot_at,r.captured_at,
                (SELECT p.snapshot_at FROM history_runs p WHERE p.id<r.id ORDER BY p.id DESC LIMIT 1) AS previous_at
                FROM history_events e JOIN history_runs r ON r.id=e.run_id'.$where.' ORDER BY e.id DESC LIMIT 100 OFFSET '.$offset);
            $q->execute($params);
            return ['available'=>true,'runs'=>$runs,'events'=>$q->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>intdiv($offset,100)+1,'labels'=>\Hw24ArticleHistoryCapture::FIELDS];
        }catch(\Throwable){return $empty;}
    }
}
