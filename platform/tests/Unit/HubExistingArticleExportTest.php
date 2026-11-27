<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubExistingArticleExport as E;
use PHPUnit\Framework\TestCase;
final class HubExistingArticleExportTest extends TestCase
{
    public function test_explicit_text_corrections_and_same_article_references_are_exportable():void
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
        self::assertTrue(array_values($options['Beschreibung'])[0]['replacement']);
        self::assertSame('Eigener Text',array_values($options['Beschreibung'])[0]['old']);
        self::assertArrayNotHasKey('Beschreibung',\App\Services\HubFieldDecisions::unique($options));
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
    public function test_missing_manufacturer_can_be_repaired_using_shared_reference_not_existing_brand():void
    {
        $current=['Hersteller'=>['state'=>'empty_snapshot','value'=>'']];
        $a=['jtl_article_id'=>'12','hersteller'=>'','asin'=>'B01MZALQW6'];
        $c=['id'=>3,'item'=>['gtin_match'=>true,'pack_kind'=>'single','asin'=>'B01MZALQW6','manufacturer'=>'Revlon Professional'],'attributes'=>[]];
        $refs=[['hersteller'=>'Revlon','kHersteller'=>'7']];
        $options=E::candidates($current,$a,$c,[],[],[],$refs);
        self::assertSame('Revlon',array_values($options['Hersteller'])[0]['value']);
        self::assertStringContainsString('ID 7',array_values($options['Hersteller'])[0]['source']);
        self::assertSame([],E::candidates($current,$a,$c,[],[],[],[]));
        $ambiguous=[...$refs,['hersteller'=>'Revlon','kHersteller'=>'8']];
        self::assertSame([],E::candidates($current,$a,$c,[],[],[],$ambiguous));
        $c['item']['asin']='B000000001';
        self::assertSame([],E::candidates($current,$a,$c,[],[],[],$refs));
        $c['item']['asin']='B01MZALQW6';
        $current['Hersteller']=['state'=>'reference','value'=>'ID 9'];
        self::assertSame([],E::candidates($current,$a,$c,[],[],[],$refs));
    }
    public function test_missing_gtin_needs_independently_verified_catalog_and_matching_asin():void
    {
        $current=['GTIN'=>['state'=>'empty_snapshot','value'=>''],'Hersteller'=>['state'=>'empty_snapshot','value'=>''],
            'Artikelname'=>['state'=>'value','value'=>'Alter Name']];
        $a=['jtl_article_id'=>'12','hersteller'=>'','asin'=>'B01MZALQW6','ean'=>'','artikelname'=>'Alter Name'];
        $c=['id'=>3,'item'=>['gtin_match'=>true,'pack_kind'=>'single','asin'=>'B01MZALQW6','manufacturer'=>'Revlon Professional','title'=>'Neuer Name'],'attributes'=>[]];
        $refs=[['hersteller'=>'Revlon','kHersteller'=>'7']];
        $o=E::candidates($current,$a,$c,[],[],[],$refs);
        self::assertArrayNotHasKey('GTIN',$o);
        $c['verified_missing_gtin']='0615908418057';
        $o=E::candidates($current,$a,$c,[],[],[],$refs);
        self::assertSame('0615908418057',array_values($o['GTIN'])[0]['value']);
        self::assertTrue(array_values($o['Artikelname'])[0]['replacement']);
        self::assertArrayNotHasKey('Artikelname',\App\Services\HubFieldDecisions::unique($o));
        $c['item']['asin']='B000000001';
        self::assertArrayNotHasKey('GTIN',E::candidates($current,$a,$c,[],[],[],$refs));
    }
}
