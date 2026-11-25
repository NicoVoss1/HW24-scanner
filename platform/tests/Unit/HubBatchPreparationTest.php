<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubBatchPreparation as P;
use PHPUnit\Framework\TestCase;

final class HubBatchPreparationTest extends TestCase
{
    private function row(): array
    {
        return ['ean'=>'4021609029274','resolution'=>'new_candidate','review'=>'scan_only',
            'draft'=>['Beschreibung'=>'Eigener Text','Netto-VK'=>'0']];
    }
    private function job(): array
    {
        return ['status'=>'completed','evidence'=>['jtl_state'=>'new_candidate','catalog_state'=>'live','catalog_snapshot_id'=>3]];
    }
    private function archive(): array
    {
        return ['snapshot_id'=>3,'searched_ean'=>'4021609029274','asin'=>'B01MZALQW6',
            'item_name'=>'Goldwell Trockenshampoo 250 ml','manufacturer'=>'Goldwell','brand'=>'Goldwell',
            'item_json'=>json_encode(['identifiers'=>[['identifiers'=>[['identifierType'=>'EAN','identifier'=>'4021609029274']]]],
                'attributes'=>['number_of_items'=>[['value'=>1]],'part_number'=>[['value'=>'P123']]]],JSON_THROW_ON_ERROR)];
    }
    public function test_reuses_archive_and_preserves_existing_values(): void
    {
        $p=P::proposal($this->row(),$this->job(),$this->archive());
        self::assertSame('missing',$p['state']);
        self::assertSame('B01MZALQW6',$p['fields']['ASIN']);
        self::assertSame('250',$p['fields']['Inhalt/Menge']);
        self::assertSame('P123',$p['fields']['HAN']);
        self::assertSame('Haarpflege',$p['fields']['Kategorie Ebene 2']);
        self::assertSame('Goldwell Trockenshampoo 250 ml',$p['fields']['Kurzbeschreibung']);
        self::assertArrayNotHasKey('Beschreibung',$p['fields']);
        self::assertArrayNotHasKey('Netto-VK',$p['fields']);
        self::assertArrayNotHasKey('Artikelnummer',$p['fields']);
        self::assertSame(['Warengruppe','Steuerklasse'],$p['missing']);
    }
    public function test_refuses_wrong_snapshot_existing_article_and_unverified_pack(): void
    {
        $a=$this->archive();$a['snapshot_id']=4;
        self::assertSame([],P::proposal($this->row(),$this->job(),$a)['fields']);
        $row=$this->row();$row['resolution']='existing';
        self::assertSame([],P::proposal($row,$this->job(),$this->archive())['fields']);
        $a=$this->archive();$a['item_json']='{}';
        self::assertSame([],P::proposal($this->row(),$this->job(),$a)['fields']);
    }
}
