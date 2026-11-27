<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubSeoDefaults;
use App\Services\ScanResearchPresentation;
use PHPUnit\Framework\TestCase;

final class HubArticleEnrichmentTest extends TestCase
{
    public function test_empty_seo_only_and_no_claims(): void
    {
        $r=HubSeoDefaults::propose(['Artikelname'=>'Goldwell Trockenshampoo 250 ml','Titel-Tag (SEO)'=>'Bestehender Titel']);
        self::assertSame(['Meta-Description (SEO)'=>'Goldwell Trockenshampoo 250 ml. Bei HAIRWORLD24.de entdecken.'],$r);
        self::assertSame([],HubSeoDefaults::propose(['Artikelname'=>'']));
    }

    public function test_weights_are_mapped_and_gross_is_not_misrepresented(): void
    {
        $obs=require dirname(__DIR__,2).'/config/hw24_jtl_reference_observations.php';
        $r=ScanResearchPresentation::jtlFields('4021609029274',['ok'=>true,'data'=>['jtl_status'=>'current_snapshot','matches'=>[
            ['ean'=>'4021609029274','artikelgewicht'=>'0.250000','versandgewicht'=>'0.3000','herkunftsland'=>'DE',
            'netto_vk'=>'19.3193','brutto_vk'=>null,'netto_ek'=>'1.50','steuerklasse_id'=>1]
        ]]],$obs);
        self::assertSame('0.25',$r['fields']['Artikelgewicht']['value']);
        self::assertSame('0.3',$r['fields']['Versandgewicht']['value']);
        self::assertSame('DE',$r['fields']['Herkunftsland']['value']);
        self::assertSame('empty_snapshot',$r['fields']['Brutto-VK']['state']);
        self::assertSame('22,99',$r['gross_calculation']['value']);
        self::assertSame('1.50',$r['average_ek_net']);
        self::assertNotSame('value',$r['fields']['Netto-EK in Standardwährung']['state']);
        self::assertNull(ScanResearchPresentation::grossCalculation(['steuerklasse_id'=>2,'netto_vk'=>'10'],$obs));
        self::assertNull(ScanResearchPresentation::grossCalculation(['steuerklasse_id'=>1,'netto_vk'=>'10','brutto_vk'=>'11.90'],$obs));
    }
}
