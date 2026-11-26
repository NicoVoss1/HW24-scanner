<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubReferenceEvidence as R;
use PDO;
use PHPUnit\Framework\TestCase;

final class HubReferenceEvidenceTest extends TestCase
{
    private function proposal():array
    {
        return ['state'=>'missing','draft'=>['Artikelname'=>'Goldwell Shampoo','Kurzbeschreibung'=>'Goldwell Shampoo'],
            'fields'=>['Kurzbeschreibung'=>'Goldwell Shampoo'],'sources'=>['Kurzbeschreibung'=>'HW24-Regel: eigener Artikelname']];
    }
    public function test_private_lookup_preserves_zero_and_rejects_conflicting_identifiers():void
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE TABLE workbook_sheets(id INTEGER,name TEXT,workbook_id INTEGER);
            INSERT INTO workbook_sheets VALUES(1,'JTL-Import aus AB 1Produkte_ALL',1);
            CREATE TABLE workbook_rows(sheet_id INTEGER,row_number INTEGER,values_json TEXT)");
        $q=$db->prepare('INSERT INTO workbook_rows VALUES(1,?,?)');
        $q->execute([5,json_encode(['4'=>'615908418057','5'=>'0615908418057','56'=>'000123','8'=>'Test','49'=>'Goldwell'])]);
        $r=R::read($db,'historical','0615908418057');
        self::assertSame('found',$r['state']);
        self::assertSame('000123',$r['rows'][0]['fields']['HAN']);
        $q->execute([6,json_encode(['4'=>'4021609029274','5'=>'0615908418057'])]);
        self::assertSame('ambiguous',R::read($db,'historical','0615908418057')['state']);
    }

    public function test_historical_text_only_with_confirmed_identity_and_no_overwrite():void
    {
        $r=['historical'=>['state'=>'found','archive'=>1,'rows'=>[['row'=>5,'requires_review'=>false,'fields'=>[
            'ASIN'=>'B01MZALQW6','Hersteller'=>'Goldwell','HAN'=>'000123','Kurzbeschreibung'=>'Historischer Text','Netto-VK'=>'99.99']]]]];
        $catalog=['item'=>['asin'=>'B01MZALQW6','manufacturer'=>'Goldwell']];
        $p=R::merge($this->proposal(),[],$catalog,$r);
        self::assertSame('000123',$p['fields']['HAN']);
        self::assertSame('Historischer Text',$p['fields']['Kurzbeschreibung']);
        self::assertArrayNotHasKey('Netto-VK',$p['fields']);
        $p=R::merge($this->proposal(),['Kurzbeschreibung'=>'Eigener Text'],$catalog,$r);
        self::assertSame('Goldwell Shampoo',$p['fields']['Kurzbeschreibung']);
        $r['ameise']=['state'=>'found','rows'=>[['row'=>1]]];
        $p=R::merge($this->proposal(),[],$catalog,$r);
        self::assertSame('waiting',$p['state']);self::assertSame([],$p['fields']);
    }
    public function test_han_fallback_preserves_gtin_string():void
    {
        $p=R::hanFallback($this->proposal(),'0615908418057');
        self::assertSame('0615908418057',$p['fields']['HAN']);
        self::assertSame('0615908418057',R::hanFallback($p,'4021609029274')['draft']['HAN']);
    }
}
