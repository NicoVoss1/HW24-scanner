<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubResearchEvidence;
use PHPUnit\Framework\TestCase;

final class HubResearchEvidenceTest extends TestCase
{
    public function test_existing_comparison_does_not_overwrite_or_accept_identity_conflict(): void
    {
        $catalog=['id'=>84,'at'=>'2026-11-25 20:56:32','item'=>['asin'=>'B01MZALQW6','title'=>'Neuer Titel','manufacturer'=>'Goldwell']];
        $review=['ok'=>true,'fields'=>[
            'ASIN'=>['value'=>'B01MZALQW6','state'=>'value'],
            'Artikelname'=>['value'=>'Alter Titel','state'=>'value'],
            'Hersteller'=>['value'=>'','state'=>'missing_source'],
        ]];
        $r=HubResearchEvidence::existingComparison($review,$catalog);
        self::assertSame('review',$r['state']);
        self::assertSame('Alter Titel',$r['differences']['Artikelname']['old']);
        self::assertSame('Neuer Titel',$r['differences']['Artikelname']['candidate']);
        self::assertSame('missing_source',$r['differences']['Hersteller']['old_state']);
        self::assertArrayNotHasKey('ASIN',$r['differences']);
        $review['fields']['ASIN']['value']='B000000001';
        self::assertSame(['state'=>'identity_conflict','differences'=>[]],HubResearchEvidence::existingComparison($review,$catalog));
    }
}
