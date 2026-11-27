<?php
declare(strict_types=1);
namespace App\Filament\Pages;
use App\Services\HubFieldPolicy;
use App\Services\ScanResearch;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

final class FieldPolicyWorkbench extends Page
{
    protected static ?string $title='Artikelqualität · Pflichtfelder';
    protected static ?string $navigationLabel='Pflichtfelder & Feldkatalog';
    protected static ?string $slug='pflichtfelder';
    protected static ?int $navigationSort=85;
    protected string $view='filament.pages.field-policy-workbench';
    #[Locked] public int $revision=0;
    public array $selected=[];
    public string $message='';
    public string $search='';
    public static function canAccess():bool{return QueryAuditWorkbench::canAccess();}
    public function mount():void{$this->reloadPolicy();}
    public function reloadPolicy():void{
        abort_unless(static::canAccess(),403);
        try{$p=HubFieldPolicy::read();$this->revision=$p['revision'];$this->selected=$p['required'];$this->message='';}
        catch(\Throwable){$this->message='Pflichtfeldkonfiguration nicht lesbar. Keine Änderung möglich.';}
    }
    public function savePolicy():void{
        abort_unless(static::canAccess(),403);
        try{$p=HubFieldPolicy::save($this->selected,$this->revision,(string)auth()->id());$this->revision=$p['revision'];$this->selected=$p['required'];$this->message='Verbindliche Pflichtfelder gespeichert · Revision '.$this->revision;}
        catch(\Throwable){$this->message='Nicht gespeichert. Konfiguration prüfen bzw. neu laden.';}
    }
    protected function getViewData():array{
        abort_unless(static::canAccess(),403);
        $columns=[];$available=false;
        try{
            $dir=dirname(base_path(),2).'/ameise-reference';$file=$dir.'/reference.sqlite';
            if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0077)!==0||!is_file($file)||is_link($file)||(fileperms($file)&0077)!==0)throw new \RuntimeException('Archive unavailable');
            $db=new \PDO('sqlite:'.$file,null,null,[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
            $db->exec('PRAGMA query_only=ON');
            $columns=$db->query('SELECT position,name,filled_count FROM ameise_columns WHERE export_id=(SELECT id FROM ameise_exports ORDER BY export_date DESC,id DESC LIMIT 1) ORDER BY position')->fetchAll(\PDO::FETCH_ASSOC);
            $available=true;
        }catch(\Throwable){}
        $q=mb_strtolower(trim($this->search));
        return ['fields'=>array_values(array_filter(ScanResearch::FIELDS,fn($f)=>$q===''||str_contains(mb_strtolower($f),$q))),
            'core'=>ScanResearch::REQUIRED,'columns'=>array_values(array_filter($columns,fn($c)=>$q===''||str_contains(mb_strtolower((string)$c['name']),$q))),
            'archiveAvailable'=>$available,'columnCount'=>count($columns)];
    }
}
