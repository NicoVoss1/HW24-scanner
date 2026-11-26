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
    public string $search='';
    public string $kind='';
    public string $run='';

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
            $search=mb_substr(trim($this->search),0,100);
            if($search!==''){$clauses[]='(instr(COALESCE(e.ean,\'\'),?)>0 OR instr(COALESCE(e.article_number,\'\'),?)>0 OR instr(lower(COALESCE(e.article_name,\'\')),lower(?))>0)';array_push($params,$search,$search,$search);}
            $where=$clauses?' WHERE '.implode(' AND ',$clauses):'';
            $q=$db->prepare('SELECT COUNT(*) FROM history_events e'.$where);$q->execute($params);$total=(int)$q->fetchColumn();
            $q=$db->prepare('SELECT e.*,r.snapshot_at,r.captured_at,
                (SELECT p.snapshot_at FROM history_runs p WHERE p.id<r.id ORDER BY p.id DESC LIMIT 1) AS previous_at
                FROM history_events e JOIN history_runs r ON r.id=e.run_id'.$where.' ORDER BY e.id DESC LIMIT 200');
            $q->execute($params);
            return ['available'=>true,'runs'=>$runs,'events'=>$q->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'labels'=>\Hw24ArticleHistoryCapture::FIELDS];
        }catch(\Throwable){return $empty;}
    }
}
