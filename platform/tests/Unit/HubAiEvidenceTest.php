<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubAiEvidence as A;
use PHPUnit\Framework\TestCase;

final class HubAiEvidenceTest extends TestCase
{
    private function catalog(): array
    {
        return ['item'=>['asin'=>'B01MZALQW6','title'=>'Goldwell Trockenshampoo 250 ml','brand'=>'Goldwell']];
    }
    private function response(): array
    {
        return ['ok'=>true,'data'=>['scope'=>'shared_workspace','reviews'=>[[
            'id'=>12,'ean'=>'4021609029274','model'=>'test-model',
            'input'=>['article'=>['ean'=>'4021609029274'],'amazon_candidates'=>[[
                'asin'=>'B01MZALQW6','title'=>'Goldwell Trockenshampoo 250 ml','brand'=>'Goldwell',
                'ean_confirmed'=>true,'pack_kind'=>'single','pack_units'=>1]]],
            'review'=>['status'=>'pass','suggestedWarengruppe'=>'Shampoo','productFacts'=>['suggestedTitle'=>'Goldwell Trockenshampoo 250 ml','packUnits'=>1],
                'asinChecks'=>[['asin'=>'B01MZALQW6','status'=>'pass']]],
        ]]]];
    }

    public function test_matching_archive_provides_only_validated_search_hint(): void
    {
        $a=A::evaluate('4021609029274',$this->catalog(),$this->response());
        self::assertSame('verified_review',$a['state']);
        self::assertSame('Shampoo',A::groupHint(['Artikelname'=>'Goldwell Trockenshampoo 250 ml'],$a));
        self::assertSame('',A::groupHint(['Artikelname'=>'Goldwell Haarfarbe'],$a));
        self::assertSame('',A::groupHint(['Artikelname'=>'Goldwell Shampoo','Warengruppe'=>'Haarpflege'],$a));
    }

    public function test_changed_identity_review_or_account_only_archive_is_not_reused(): void
    {
        $r=$this->response();$r['data']['scope']='authenticated_user';
        self::assertSame('unavailable',A::evaluate('4021609029274',$this->catalog(),$r)['state']);
        $r=$this->response();$r['data']['reviews'][0]['input']['amazon_candidates'][0]['title']='Anderes Produkt';
        self::assertSame('stale',A::evaluate('4021609029274',$this->catalog(),$r)['state']);
        $r=$this->response();$r['data']['reviews'][0]['review']['status']='mismatch';
        self::assertSame('identity_conflict',A::evaluate('4021609029274',$this->catalog(),$r)['state']);
        $r=$this->response();$r['data']['reviews'][0]['review']['asinChecks'][0]['status']='review';
        self::assertSame('review',A::evaluate('4021609029274',$this->catalog(),$r)['state']);
        self::assertSame('missing',A::evaluate('4021609029274',$this->catalog(),['ok'=>true,'data'=>['scope'=>'shared_workspace','reviews'=>[]]])['state']);
    }
}
