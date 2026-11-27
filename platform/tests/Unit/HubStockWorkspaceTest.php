<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Domain\Jtl\RelatedSourceEvidence;
use App\Services\HubStockReader as S;
use PHPUnit\Framework\TestCase;

final class HubStockWorkspaceTest extends TestCase
{
    public function test_stale_master_zero_does_not_override_confirmed_stock():void
    {
        $row=['bestand'=>0,'related_sources'=>['bestand'=>['rows'=>[['fLagerbestand'=>'3.500','fVerfuegbar'=>'3','fInAuftraegen'=>'0.5','fZulauf'=>'0']]]]];
        $related=RelatedSourceEvidence::present($row);
        $p=S::workspace(['ok'=>true,'at'=>'Test','item'=>['total'=>'3.5','available'=>'3','orders'=>'0.50','incoming'=>'0']],$related);
        self::assertSame(['total'=>'3.5','available'=>'3','orders'=>'0.5','incoming'=>'0'],$p['item']);
        self::assertSame([],$p['conflicts']);
        self::assertSame('3,5',S::display($p['item']['total']));
        self::assertSame('0',S::display($p['item']['incoming']));
    }
    public function test_inconsistent_sources_and_missing_snapshot_are_not_reported_as_zero():void
    {
        $related=RelatedSourceEvidence::present(['bestand'=>0,'bestand_verfuegbar'=>3]);
        self::assertNull($related['stock']['total']);
        self::assertNull($related['stock']['available']);
        $p=S::workspace(['ok'=>false],$related);
        self::assertNull($p['item']['total']);
        self::assertSame('Nicht belegt',S::display($p['item']['total']));
        $related=RelatedSourceEvidence::present(['related_sources'=>['bestand'=>['rows'=>[['fLagerbestand'=>'3']]]]]);
        $p=S::workspace(['ok'=>true,'item'=>['total'=>'0']],$related);
        self::assertNull($p['item']['total']);
        self::assertSame(['mirror'=>'0','api'=>'3'],$p['conflicts']['total']);
    }
    public function test_bin_quantities_and_missing_lock_flags_preserved():void
    {
        $related=RelatedSourceEvidence::present(['related_sources'=>['wms_lagerplaetze'=>['confirmed'=>true,'rows'=>[
            ['warenlager_name'=>'Lager','lagerplatz'=>'A-01','menge'=>'1.250'],
            ['warenlager_name'=>'Lager','lagerplatz'=>'A-02','menge'=>'2','gesperrt'=>'1']
        ]]]]);
        self::assertSame('1.25',$related['wms']['bins'][0]['quantity']);
        self::assertFalse($related['wms']['bins'][0]['lock_known']);
        self::assertTrue($related['wms']['bins'][1]['locked']);
        self::assertSame(2,$related['wms']['positive_bins']);
    }
}
