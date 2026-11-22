<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Jtl\SourceSnapshot;
use PHPUnit\Framework\TestCase;

final class SourceSnapshotTest extends TestCase
{
    public function test_one_article_fields_and_channels_keep_original_meaning(): void
    {
        $out=SourceSnapshot::present([
            'ok'=>true,'status'=>'current_snapshot',
            'article_id'=>'1428','ean'=>'3474630337398',
            'snapshot_at'=>'2026-11-22 13:32:10','field_count'=>4,
            'offset'=>0,'returned_count'=>4,'has_more'=>false,
            'fields'=>[
                ['source'=>'dbo.tArtikel','field'=>'fVKNetto','value'=>'11.76470588','row'=>1],
                ['source'=>'dbo.tArtikel','field'=>'fMassMenge','value'=>'90','row'=>1],
                ['source'=>'dbo.tArtikel','field'=>'passwordToken','value'=>'SECRET','row'=>1],
                ['source'=>'JTL.Zusatzquelle.steuerklasse','field'=>'cName','value'=>'Normaler Steuersatz','row'=>1],
            ],
            'prices'=>[
                'jtl_net'=>'11.76470588','jtl_net_source'=>'dbo.tArtikel.fVKNetto',
                'jtl_gross'=>null,
                'amazon_jtl_value'=>'14','amazon_jtl_source'=>'dbo.tArtikel.fAmazonVK',
                'ebay_jtl_value'=>'11','ebay_jtl_source'=>'dbo.tArtikel.fEbayPreis',
            ]
        ]);
        $this->assertTrue($out['ok']);
        $this->assertCount(3,$out['fields']);
        $this->assertSame('11.76470588',$out['fields'][0]['value']);
        $this->assertSame('90',$out['fields'][1]['value']);
        $this->assertSame(4,$out['returned_count'],'Cursor must skip hidden/sensitive fields');
        $this->assertCount(8,$out['prices']);
        $this->assertNull($out['prices'][1]['value'],'Unverified JTL gross must not be fabricated');
        $this->assertSame('14',$out['prices'][2]['value']);
        $this->assertSame('11',$out['prices'][3]['value']);
        $this->assertSame('www.serverler24.shop',$out['prices'][4]['account']);
        $this->assertSame('friseurbedarf24.shop',$out['prices'][5]['account']);
        $this->assertNull($out['prices'][4]['value']);
        $this->assertNull($out['prices'][5]['value']);
        $this->assertNull($out['prices'][6]['value']);
        $this->assertNull($out['prices'][7]['value']);
        $this->assertNull($out['prices'][2]['currency']);
    }

    public function test_stale_snapshot_fails_closed(): void
    {
        $out=SourceSnapshot::present(['ok'=>true,'status'=>'stale','fields'=>[
            ['source'=>'dbo.tArtikel','field'=>'fVKNetto','value'=>'14']
        ]]);
        $this->assertFalse($out['ok']);
        $this->assertSame([],$out['fields']);
    }
}
