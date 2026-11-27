<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubExistingArticleExport as E;
use PHPUnit\Framework\TestCase;
final class HubExistingArticleExportTest extends TestCase
{
    public function test_only_empty_fields_and_same_article_references_are_exportable():void
    {
        $current=['HAN'=>['state'=>'empty_snapshot','value'=>''],'Beschreibung'=>['state'=>'value','value'=>'Eigener Text'],
            'Kurzbeschreibung'=>['state'=>'empty_snapshot','value'=>''],'ASIN'=>['state'=>'missing_source','value'=>'']];
        $a=['jtl_article_id'=>'12','artikelnummer'=>'00123','ean'=>'0615908418057','artikelname'=>'Shampoo','hersteller'=>'Goldwell','asin'=>'B01MZALQW6'];
        $refs=['ameise'=>['state'=>'found','archive'=>1,'rows'=>[
            ['row'=>1,'article_id'=>'12','fields'=>['HAN'=>'000123','Beschreibung'=>'Alt','Kurzbeschreibung'=>'Originaltext']],
            ['row'=>2,'article_id'=>'99','fields'=>['HAN'=>'999999']]
        ]]];
        $options=E::candidates($current,$a,[],[],$refs,[]);
        self::assertCount(1,$options['HAN']);
        self::assertArrayNotHasKey('Beschreibung',$options);
        self::assertArrayNotHasKey('ASIN',$options);
        $csv=E::csv('00123',['HAN'=>array_key_first($options['HAN'])],$options);
        self::assertSame("\xEF\xBB\xBF\"Artikelnummer\";\"HAN\"\r\n\"00123\";\"000123\"\r\n",$csv);
    }
    public function test_unknown_selection_refused():void
    {
        $this->expectException(\RuntimeException::class);
        E::csv('00123',['HAN'=>'invented'],[]);
    }
    public function test_empty_selection_refused():void
    {
        $this->expectException(\RuntimeException::class);
        E::csv('00123',[],[]);
    }
}
