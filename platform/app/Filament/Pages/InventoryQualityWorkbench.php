<?php
declare(strict_types=1);
namespace App\Filament\Pages;

use App\Services\HubFieldPolicy;
use App\Services\HubInventoryQuality;
use Filament\Pages\Page;

final class InventoryQualityWorkbench extends Page
{
    protected static ?string $title='Artikelqualität · Bestandsprüfung';
    protected static ?string $navigationLabel='Bestandsqualität & Pflichtfeldlücken';
    protected static ?string $slug='bestandsqualitaet';
    protected static ?int $navigationSort=86;
    protected string $view='filament.pages.inventory-quality-workbench';
    public string $search='';
    public string $field='';
    public string $kind='all';
    public int $pageNumber=1;
    public static function canAccess():bool{return QueryAuditWorkbench::canAccess();}
    public function updated(string $name):void{if(in_array($name,['search','field','kind'],true))$this->pageNumber=1;}
    public function move(int $direction):void{$this->pageNumber=max(1,min(1000,$this->pageNumber+($direction<0?-1:1)));}
    public function clearFilters():void{$this->reset(['search','field','kind','pageNumber']);}
    protected function getViewData():array
    {
        abort_unless(static::canAccess(),403);
        $empty=['ok'=>false,'articles'=>0,'affected'=>0,'counts'=>[],'rows'=>[],'total'=>0,'at'=>'','page'=>1,'revision'=>0];
        try{
            $policy=HubFieldPolicy::read();
            $r=app(HubInventoryQuality::class)->read($policy['required'],config('hw24_jtl_reference_observations',[]));
            $search=mb_strtolower(trim(mb_substr($this->search,0,100)));
            $field=in_array($this->field,$policy['required'],true)?$this->field:'';
            $rows=array_values(array_filter($r['issues'],function($a)use($search,$field){
                $fields=match($this->kind){'missing'=>$a['missing'],'unverified'=>$a['unverified'],default=>[...$a['missing'],...$a['unverified']]};
                if(!$fields||($field!==''&&!in_array($field,$fields,true)))return false;
                return $search===''||str_contains(mb_strtolower(implode(' ',[$a['number'],$a['ean'],$a['name'],$a['manufacturer'],$a['asin']])), $search);
            }));
            $total=count($rows);$page=max(1,min($this->pageNumber,(int)max(1,ceil($total/50))));
            unset($r['issues']);
            return $r+['rows'=>array_slice($rows,($page-1)*50,50),'total'=>$total,'page'=>$page,'revision'=>$policy['revision']];
        }catch(\Throwable){return $empty;}
    }
}
