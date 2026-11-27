<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubInventoryQuality as Q;
use PHPUnit\Framework\TestCase;

final class HubInventoryQualityTest extends TestCase
{
    public function test_quality_checks_all_articles_and_distinguishes_missing_from_unmapped():void
    {
        $base=['jtl_article_id'=>'1','artikelnummer'=>'00123','ean'=>'0615908418057','artikelname'=>'Shampoo',
            'hersteller'=>'Matrix','kHersteller'=>'7','warengruppe'=>'Pflege','kWarengruppe'=>'2','kSteuerklasse'=>'1','han'=>'00012'];
        $rows=[$base,array_replace($base,['jtl_article_id'=>'2','artikelnummer'=>'00234','ean'=>'','hersteller'=>'','kHersteller'=>'0','han'=>'']),
            array_replace($base,['jtl_article_id'=>'3','artikelnummer'=>'00345','ean'=>'4021609029274','kSteuerklasse'=>'99'])];
        $r=Q::evaluate($rows,['GTIN','Hersteller','Steuerklasse','HAN','Währung'],['tax_classes'=>[1=>['name'=>'Normaler Steuersatz']]]);
        self::assertSame(3,$r['articles']);self::assertSame(3,$r['affected']);
        self::assertSame(['confirmed'=>2,'missing'=>1,'unverified'=>0],$r['counts']['Hersteller']);
        self::assertSame(['confirmed'=>2,'missing'=>0,'unverified'=>1],$r['counts']['Steuerklasse']);
        self::assertSame(3,$r['counts']['Währung']['unverified']);
        self::assertSame('00234',$r['issues'][0]['number']);
        self::assertSame('missing_or_invalid',$r['issues'][0]['gtin_state']);
    }
    public function test_duplicate_gtins_are_not_verified_for_bulk_research():void
    {
        $r=Q::evaluate([['jtl_article_id'=>'1','ean'=>'0615908418057'],['jtl_article_id'=>'2','ean'=>'00615908418057']],['GTIN'],[]);
        self::assertSame(2,$r['counts']['GTIN']['unverified']);
        self::assertSame('duplicate',$r['issues'][0]['gtin_state']);
    }
    public function test_duplicate_internal_id_fails_closed():void
    {
        $this->expectException(\RuntimeException::class);
        Q::evaluate([['jtl_article_id'=>'1'],['jtl_article_id'=>'1']],['GTIN'],[]);
    }
}
