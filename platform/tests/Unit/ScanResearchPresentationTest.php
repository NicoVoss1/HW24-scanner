<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\ScanResearchPresentation as P;
use PHPUnit\Framework\TestCase;

final class ScanResearchPresentationTest extends TestCase
{
    public function test_source_status_does_not_claim_shared_ai_or_new_query(): void
    {
        $v=P::sources(['evidence'=>['ai_state'=>'read','ai_scope'=>'legacy_account','ai_count'=>0,
            'price_state'=>'archive_reused','price_snapshot_id'=>3]]);
        self::assertStringContainsString('Bisheriges Konto',$v['OpenAI-Archiv']);
        self::assertSame('Archiv wiederverwendet · Archiv #3',$v['Amazon-Marktpreise']);
        self::assertSame('Nicht belegt',$v['Amazon-Katalog']);
    }

    public function test_draft_coverage_counts_zero_and_missing_required_separately(): void
    {
        $v=P::draftCoverage(['ean'=>'4021609029274','name'=>'Vollständiger Artikelname','draft'=>['Netto-VK'=>'0']]);
        self::assertSame(3,$v['filled']);
        self::assertSame(41,$v['total']);
        self::assertSame(['Hersteller','Warengruppe','Steuerklasse'],$v['missing_required']);
    }

    public function test_jtl_field_review_is_read_only_and_does_not_guess_missing_mappings(): void
    {
        $ean='4021609029274';$title=str_repeat('Artikelname ',25);
        $raw=['ok'=>true,'data'=>['jtl_status'=>'current_snapshot','snapshot_at'=>'2026-11-25 20:00:00',
            'matches'=>[['ean'=>$ean,'artikelname'=>$title,'netto_vk'=>'0','brutto_vk'=>null,
                'jtl_source_fields'=>['kWarengruppe'=>17]]]]];
        $v=P::jtlFields($ean,$raw);
        self::assertTrue($v['ok']);
        self::assertSame($title,$v['name']);
        self::assertSame('25.11.2026 · 21:00:00 MEZ',$v['snapshot_at']);
        self::assertSame('0',$v['fields']['Netto-VK']['value']);
        self::assertSame('reference',$v['fields']['Warengruppe']['state']);
        self::assertSame('empty_snapshot',$v['fields']['Brutto-VK']['state']);
        self::assertSame('unmapped',$v['fields']['Lieferant']['state']);
        self::assertFalse(P::jtlFields('4006381333931',$raw)['ok']);
        $raw['data']['jtl_status']='stale';
        self::assertFalse(P::jtlFields($ean,$raw)['ok']);
    }
}
