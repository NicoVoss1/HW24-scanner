<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubStockReader;
use App\Services\HubBatchRelease;
use PHPUnit\Framework\TestCase;

final class HubStockAndReleaseTest extends TestCase
{
    public function test_stock_preserves_zero_negative_and_unknown_and_never_sums_duplicates(): void
    {
        $a=['jtl_article_id'=>1,'rows_type'=>'ARRAY','row_count'=>1,'truncated_flag'=>'false',
            'total_stock'=>'0.0000','available_stock'=>'-2.0000','in_orders'=>'2.0000','incoming'=>null];
        $s=HubStockReader::present([$a]);
        self::assertSame('existing',$s['state']);
        self::assertSame('0',$s['total']);
        self::assertSame('-2',$s['available']);
        self::assertSame('2',$s['orders']);
        self::assertNull($s['incoming']);
        self::assertSame(['state'=>'multiple'],HubStockReader::present([$a,$a]));
        self::assertSame(['state'=>'absent'],HubStockReader::present([]));
        $a['truncated_flag']='true';
        self::assertNull(HubStockReader::present([$a])['total']);
        self::assertNull(HubStockReader::present([['jtl_article_id'=>1,'bestand'=>'0']])['total']);
    }

    public function test_release_requires_saved_valid_draft_and_fresh_absence(): void
    {
        $row=['ean'=>'4006381333931','resolution'=>'new_candidate','review'=>'draft','draft'=>[
            'GTIN'=>'4006381333931','Artikelname'=>'Wella Koleston Haarfarbe','Hersteller'=>'Wella',
            'Warengruppe'=>'Haarfarben','Steuerklasse'=>'Normaler Steuersatz',
            'Kategorie Ebene 1'=>'Friseurbedarf','Kategorie Ebene 2'=>'Haarfarben',
        ]];
        $absent=['state'=>'absent'];
        self::assertNull(HubBatchRelease::reason($row,$absent));
        self::assertNotNull(HubBatchRelease::reason($row,['state'=>'existing']));
        self::assertNotNull(HubBatchRelease::reason($row,[]));
        $row['draft']['Kategorie Ebene 2']='Hairstyling';
        self::assertSame('Kategorie-Konflikt',HubBatchRelease::reason($row,$absent));
        $row['draft']['Kategorie Ebene 2']='Haarfarben';
        $row['draft']['Steuerklasse']='';
        self::assertStringContainsString('Steuerklasse',HubBatchRelease::reason($row,$absent));
        $row['review']='removed';
        self::assertNotNull(HubBatchRelease::reason($row,$absent));
    }
}
