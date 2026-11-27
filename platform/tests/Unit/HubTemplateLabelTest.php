<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubArticleMirror;
use PHPUnit\Framework\TestCase;

final class HubTemplateLabelTest extends TestCase
{
    public function test_revlon_high_coverage_classification(): void
    {
        $p=\App\Services\HubCategoryPolicy::suggest('REVLON PROFESSIONAL RVL Colorsmetique High CoverAge 5.13 60ml');
        self::assertSame(['Kategorie Ebene 1'=>'Friseurbedarf','Kategorie Ebene 2'=>'Haarfarben'],$p);
        self::assertNull(\App\Services\HubCategoryPolicy::suggest('Revlon Professional Colorsmetique'));
        self::assertNull(\App\Services\HubCategoryPolicy::suggest('Revlon Colorsmetique High Coverage Shampoo'));
    }
    
    public function test_observed_alias_and_group_consensus_do_not_copy_product_fields(): void
    {
        self::assertSame('Revlon',\App\Services\HubManufacturerPolicy::jtlSearchName('Revlon Professional'));
        self::assertFalse(\App\Services\HubManufacturerPolicy::equivalent('Revlon Professional','Wella'));
        $o=require dirname(__DIR__,2).'/config/hw24_jtl_reference_observations.php';
        $a=['jtl_article_id'=>1,'hersteller'=>'Revlon','warengruppe'=>'Haarfarben','label_namen'=>'Vorlagen zur Anlage','kSteuerklasse'=>'1','cLagerAktiv'=>'Y','kLieferStatus'=>'1','ean'=>'8432225099521'];
        $b=array_replace($a,['jtl_article_id'=>2,'hersteller'=>'Wella','ean'=>'4006381333931','kLieferStatus'=>'2']);
        self::assertSame([],HubArticleMirror::groupStandards([$a],[],'Haarfarben',$o)['fields']);
        $r=HubArticleMirror::groupStandards([$a,$b],['Hersteller'=>'Matrix'],'Haarfarben',$o);
        self::assertSame('Normaler Steuersatz',$r['fields']['Steuerklasse']);
        self::assertSame('Y',$r['fields']['Bestandsführung aktiv']);
        self::assertArrayNotHasKey('Lieferstatus',$r['fields']);
        foreach(['Hersteller','Netto-VK','HAN','Inhalt/Menge','Artikelgewicht'] as $f)self::assertArrayNotHasKey($f,$r['fields']);
    }

    public function test_only_exact_operator_confirmed_label(): void
    {
        foreach(['Vorlagen zur Anlage',' VORLAGEN ZUR ANLAGE ','Neu; Vorlagen zur Anlage','Vorlagen zur Anlage, Pflege','A|Vorlagen zur Anlage'] as $s)
            self::assertTrue(HubArticleMirror::hasTemplateLabel($s),$s);
        foreach(['Vorlage','Vorlagen','Vorlage zur Anlage','Keine Vorlagen zur Anlage','Vorlagenarchiv',''] as $s)
            self::assertFalse(HubArticleMirror::hasTemplateLabel($s),$s);
    }
    public function test_inconsistent_template_volume_is_excluded(): void
    {
        self::assertTrue(HubArticleMirror::consistentVolume('Haarfarbe (60 ml)','60.0000','ml'));
        self::assertFalse(HubArticleMirror::consistentVolume('Haarfarbe (60 ml)','0.06','ml'));
        self::assertFalse(HubArticleMirror::consistentVolume('Haarfarbe (60 ml)','50','ml'));
    }
    public function test_template_consensus_preserves_existing_values_and_refuses_disagreement(): void
    {
        $o=require dirname(__DIR__,2).'/config/hw24_jtl_reference_observations.php';
        $rows=[
            ['jtl_article_id'=>1,'warengruppe'=>'Haarfarben','kLieferStatus'=>'1','kSteuerklasse'=>'1','cLagerAktiv'=>'Y','cTeilbar'=>'N'],
            ['jtl_article_id'=>2,'warengruppe'=>'Haarfarben','kLieferStatus'=>'1','kSteuerklasse'=>'2','cLagerAktiv'=>'Y','cTeilbar'=>'N'],
        ];
        $p=HubArticleMirror::templateConsensus($rows,['Stückzahl teilbar'=>'Y'],$o);
        self::assertSame('Haarfarben',$p['fields']['Warengruppe']);
        self::assertSame('2-4 Werktage',$p['fields']['Lieferstatus']);
        self::assertSame('Y',$p['fields']['Bestandsführung aktiv']);
        self::assertArrayNotHasKey('Steuerklasse',$p['fields']);
        self::assertArrayNotHasKey('Stückzahl teilbar',$p['fields']);
        self::assertArrayNotHasKey('Netto-VK',$p['fields']);
    }

}
