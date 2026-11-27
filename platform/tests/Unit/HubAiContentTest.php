<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubAiContent as C;
use PHPUnit\Framework\TestCase;

final class HubAiContentTest extends TestCase
{
    private function catalog(): array
    {
        return ['item'=>['title'=>'Wella Haarfarbe 60 ml','brand'=>'Wella','manufacturer'=>'Wella','pack_kind'=>'single'],
            'attributes'=>['color'=>[['value'=>'Braun']],'part_number'=>[['value'=>'A123']]]];
    }
    private function review(): array
    {
        return ['contentVersion'=>2,'content'=>[
            'shortDescription'=>'Wella Haarfarbe mit 60 ml Inhalt.',
            'description'=>'Haarfarbe von Wella. Farbton Braun, Inhalt 60 ml.',
            'seoTitle'=>'Wella Haarfarbe Braun 60 ml',
            'metaDescription'=>'Wella Haarfarbe in Braun mit 60 ml Inhalt.',
            'articleAttributes'=>[['name'=>'Farbton','value'=>'Braun'],['name'=>'Füllmenge','value'=>'90']],
            'shopifyAttributes'=>[['name'=>'Marke','value'=>'Wella']],
            'ebayAttributes'=>[['name'=>'EAN','value'=>'4006381333931'],['name'=>'Maßeinheit','value'=>'ml']],
        ]];
    }
    public function test_only_current_facts_and_bounded_text_are_returned(): void
    {
        $catalog=$this->catalog();$input=['review_version'=>2,'product_facts'=>C::facts($catalog)];
        $r=C::validate($this->review(),$input,$catalog);
        self::assertSame('review',$r['state']);
        self::assertCount(4,$r['fields']);
        self::assertSame(['Farbton'=>'Braun'],$r['attributes']['article']);
        self::assertSame(['Marke'=>'Wella'],$r['attributes']['shopify']);
        self::assertSame(['Maßeinheit'=>'ml'],$r['attributes']['ebay']);
        $v=$this->review();$v['content']['seoTitle']=str_repeat('x',66);$v['content']['description']='<script>x</script>';
        $r=C::validate($v,$input,$catalog);
        self::assertArrayNotHasKey('Titel-Tag (SEO)',$r['fields']);
        self::assertArrayNotHasKey('Beschreibung',$r['fields']);
        $input['product_facts']['Farbton']='Rot';
        self::assertSame('legacy_or_changed',C::validate($this->review(),$input,$catalog)['state']);
        self::assertSame('legacy_or_changed',C::validate($this->review(),[],$catalog)['state']);
    }
    public function test_merge_replaces_only_current_generated_defaults_not_existing_text(): void
    {
        $p=['draft'=>['Kurzbeschreibung'=>'Wella Haarfarbe','Beschreibung'=>'Eigener Text','Titel-Tag (SEO)'=>'Wella Haarfarbe'],
            'fields'=>['Kurzbeschreibung'=>'Wella Haarfarbe','Titel-Tag (SEO)'=>'Wella Haarfarbe'],
            'sources'=>['Kurzbeschreibung'=>'HW24-Regel: eigener Artikelname','Titel-Tag (SEO)'=>'HW24-SEO-Basisregel (kein KI-Aufruf)']];
        $catalog=$this->catalog();
        $a=['state'=>'verified_review','id'=>12,'content'=>C::validate($this->review(),['review_version'=>2,'product_facts'=>C::facts($catalog)],$catalog)];
        $r=C::merge($p,$a,['Beschreibung'=>'Eigener Text']);
        self::assertSame('Wella Haarfarbe mit 60 ml Inhalt.',$r['draft']['Kurzbeschreibung']);
        self::assertSame('Eigener Text',$r['draft']['Beschreibung']);
        self::assertStringContainsString('KI-Archiv #12',$r['sources']['Kurzbeschreibung']);
        $a['state']='stale';
        self::assertSame($p,C::merge($p,$a,[]));
    }
}
