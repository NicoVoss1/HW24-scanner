<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubArticleFieldEditor as E;
use App\Services\HubExistingArticleExport as X;
use App\Services\HubFieldDecisions as D;
use App\Services\HubFieldPolicy as P;
use PHPUnit\Framework\TestCase;

final class HubArticleFieldEditorTest extends TestCase
{
    public function test_manual_numeric_and_text_values_are_explicit_and_exportable():void
    {
        $current=['Artikelgewicht'=>['state'=>'value','value'=>'0.05'],'Versandgewicht'=>['state'=>'empty_snapshot','value'=>'']];
        $o=E::manualOptions($current,['Artikelgewicht'=>'0,09','Versandgewicht'=>'0,12','HAN'=>'000123']);
        self::assertSame('0.09',array_values($o['Artikelgewicht'])[0]['value']);
        self::assertSame('0.05',array_values($o['Artikelgewicht'])[0]['old']);
        self::assertSame('0.12',array_values($o['Versandgewicht'])[0]['value']);
        self::assertSame([],D::unique($o));
        $selected=['HAN'=>array_key_first($o['HAN']),'Versandgewicht'=>array_key_first($o['Versandgewicht'])];
        $csv=X::csv('00123',$selected,$o);
        self::assertStringContainsString('"00123";"000123";"0.12"',$csv);
        self::assertStringNotContainsString('"Artikelgewicht"',$csv);
        self::assertNull(E::manualValue('Versandgewicht','-1'));
        self::assertNull(E::manualValue('Hersteller','Unbekannter Hersteller'));
        self::assertNull(E::manualValue('GTIN','0615908418057'));
        self::assertNull(E::manualValue('Zusätzliche Bearbeitungszeit','2.5'));
        self::assertSame('0',E::manualValue('Kosten','0'));
        self::assertSame('N',E::manualValue('Überverkäufe möglich','N'));
    }

    public function test_reference_options_are_visible_but_never_automatically_selected():void
    {
        $o=E::referenceOptions([],[],[],[],['Artikeltyp'=>['Standard'],'Währung'=>['EUR'],'Netto-VK'=>['99.99']]);
        self::assertSame('Standard',array_values($o['Artikeltyp'])[0]['value']);
        self::assertSame('EUR',array_values($o['Währung'])[0]['value']);
        self::assertArrayNotHasKey('Netto-VK',$o);
        self::assertSame([],D::unique($o));
    }

    public function test_calculated_gross_meets_requirement_only_without_tax_change():void
    {
        $current=['Brutto-VK'=>['state'=>'calculated','value'=>'11,90'],'Netto-VK'=>['state'=>'value','value'=>'10']];
        self::assertSame([],P::missingAfterUpdate($current,[],['Brutto-VK']));
        self::assertSame(['Brutto-VK'],P::missingAfterUpdate($current,['Steuerklasse'=>'Ermäßigter Steuersatz'],['Brutto-VK']));
    }
}
