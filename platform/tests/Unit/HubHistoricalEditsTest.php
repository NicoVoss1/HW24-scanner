<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubHistoricalEdits as E;
use App\Services\HubReferenceEvidence as R;
use PDO;
use PHPUnit\Framework\TestCase;

final class HubHistoricalEditsTest extends TestCase
{
    private function db():PDO
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE TABLE workbook_sheets(id INTEGER,name TEXT,workbook_id INTEGER);
            INSERT INTO workbook_sheets VALUES(1,'JTL-Import aus AB 1Produkte_ALL',1);
            CREATE TABLE workbook_columns(sheet_id INTEGER,position INTEGER,name TEXT);
            INSERT INTO workbook_columns VALUES(1,5,'EAN'),(1,8,'Name'),(1,9,'Kurzbeschreibung'),(1,56,'HAN'),(1,63,'Lagerplatz');
            CREATE TABLE workbook_rows(sheet_id INTEGER,row_number INTEGER,values_json TEXT,formulas_json TEXT,status TEXT)");
        $db->prepare('INSERT INTO workbook_rows VALUES(1,5,?,?,?)')->execute([
            json_encode((object)['5'=>'0615908418057','8'=>'Alt','9'=>'Cache','56'=>'000123']),
            json_encode((object)['9'=>['text'=>'H5']]),'']);
        return $db;
    }
    public function test_edits_preserve_original_audit_and_are_used_by_research():void
    {
        $db=$this->db();
        self::assertSame(1,E::save($db,1,5,0,['8'=>'Neu','56'=>'000045','63'=>'LP geb.'],'7'));
        $row=E::row($db,1,5);
        self::assertSame('Alt',$row['original']['8']);
        self::assertSame('Neu',$row['values']['8']);
        self::assertSame('000045',$row['values']['56']);
        $q=$db->query('SELECT status,revision FROM '.E::projection($db).' WHERE sheet_id=1');
        self::assertSame('LP geb.',$q->fetch(PDO::FETCH_ASSOC)['status']);
        $r=R::read($db,'historical','0615908418057');
        self::assertSame('Neu',$r['rows'][0]['fields']['Artikelname']);
        self::assertSame('000045',$r['rows'][0]['fields']['HAN']);
        self::assertSame(3,(int)$db->query('SELECT COUNT(*) FROM workbook_edit_events')->fetchColumn());
        self::assertSame(1,E::save($db,1,5,1,['8'=>'Neu'],'7'));
        self::assertSame(2,E::save($db,1,5,1,['8'=>''],'7'));
        self::assertSame('',E::row($db,1,5)['values']['8']);
    }
    public function test_stale_revision_cannot_overwrite():void
    {
        $db=$this->db();E::save($db,1,5,0,['8'=>'Neu'],'7');
        $this->expectException(\RuntimeException::class);
        E::save($db,1,5,0,['8'=>'Veraltet'],'8');
    }
    public function test_protected_identity_and_formula_rollback_whole_row():void
    {
        $db=$this->db();
        foreach([5,9] as $pos){
            try{E::save($db,1,5,0,['8'=>'Neu',$pos=>'Falsch'],'7');self::fail('Protected field accepted');}
            catch(\RuntimeException){}
            self::assertSame('Alt',E::row($db,1,5)['values']['8']);
            self::assertSame(0,E::row($db,1,5)['revision']);
        }
    }
}
