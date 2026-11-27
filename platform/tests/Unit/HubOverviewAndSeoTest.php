<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubBatchOverview;
use App\Services\HubSeoDefaults;
use PHPUnit\Framework\TestCase;

final class HubOverviewAndSeoTest extends TestCase
{
    public function test_stock_totals_count_each_jtl_article_once_and_never_multiply_by_scan_quantity(): void
    {
        $stock=['04006381333931'=>['state'=>'existing','article_id'=>1,'total'=>'12','available'=>'10'],
            '05901234123457'=>['state'=>'existing','article_id'=>2,'total'=>'8','available'=>'6'],
            '00036000291452'=>['state'=>'absent']];
        $v=HubBatchOverview::stockSummary(['4006381333931','4006381333931','04006381333931','5901234123457','036000291452'],$stock);
        self::assertSame('20',$v['total']);
        self::assertSame('16',$v['available']);
        self::assertSame(2,$v['existing']);
        self::assertSame(1,$v['absent']);
        self::assertSame(0,$v['unknown']);
    }
    public function test_incomplete_stock_is_not_misreported_as_zero(): void
    {
        $stock=['04006381333931'=>['state'=>'existing','article_id'=>1,'total'=>'0','available'=>null]];
        $v=HubBatchOverview::stockSummary(['4006381333931'],$stock);
        self::assertSame('0',$v['total']);self::assertNull($v['available']);
        $v=HubBatchOverview::stockSummary(['4006381333931','5901234123457'],$stock);
        self::assertNull($v['total']);self::assertSame(1,$v['unknown']);
    }
    public function test_brand_is_only_added_to_generated_seo_and_is_not_repeated(): void
    {
        $name='Wella Koleston Perfect 7/0, 60 ml';
        $r=HubSeoDefaults::propose(['Artikelname'=>$name]);
        self::assertSame($name.' | HAIRWORLD24',$r['Titel-Tag (SEO)']);
        self::assertStringContainsString('HAIRWORLD24.de',$r['Meta-Description (SEO)']);
        self::assertSame($r['Titel-Tag (SEO)'],HubSeoDefaults::brandTitle($r['Titel-Tag (SEO)']));
        self::assertSame($r['Meta-Description (SEO)'],HubSeoDefaults::brandDescription($r['Meta-Description (SEO)']));
        self::assertSame([],HubSeoDefaults::propose(['Artikelname'=>$name,'Titel-Tag (SEO)'=>'Eigener Titel','Meta-Description (SEO)'=>'Eigene Beschreibung']));
        $long=str_repeat('Produktinformation ',20).'60 ml';
        self::assertLessThanOrEqual(65,mb_strlen(HubSeoDefaults::brandTitle($long)));
        self::assertLessThanOrEqual(160,mb_strlen(HubSeoDefaults::brandDescription($long)));
    }
}
