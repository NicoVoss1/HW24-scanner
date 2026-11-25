<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubTemplateDefaults as T;
use PHPUnit\Framework\TestCase;

final class HubTemplateDefaultsTest extends TestCase
{
    private function response(): array
    {
        return ['ok'=>true,'data'=>['jtl_status'=>'current_snapshot','snapshot_at'=>'2026-11-25 21:17:10','matches'=>[[
            'jtl_article_id'=>12,'artikelnummer'=>'VORLAGE-1','ean'=>'4006381333931',
            'artikelname'=>'Schwarzkopf Igora Royal Haarfarbe','hersteller'=>'Schwarzkopf','warengruppe_name'=>'Haarfarben',
            'han'=>'OLD-HAN','asin'=>'B000000001','beschreibung'=>'Fremder Text','netto_vk'=>'99.00',
            'jtl_source_fields'=>['kLieferStatus'=>1,'kSteuerklasse'=>1,'cLagerAktiv'=>'Y','fMassMenge'=>'60'],
        ]]]];
    }
    public function test_only_empty_standard_fields_from_explicit_template(): void
    {
        $o=require dirname(__DIR__,2).'/config/hw24_jtl_reference_observations.php';
        $r=T::propose('VORLAGE-1','5901234123457',['Artikelname'=>'Schwarzkopf Igora Royal Haarfarbe 7-0',
            'GTIN'=>'5901234123457','Steuerklasse'=>'Manuell','Netto-VK'=>'0'],$this->response(),$o);
        self::assertTrue($r['ok']);
        self::assertTrue($r['category_confirmed']);
        self::assertSame(['Hersteller'=>'Schwarzkopf','Warengruppe'=>'Haarfarben',
            'Lieferstatus'=>'2-4 Werktage','Bestandsführung aktiv'=>'Y'],$r['fields']);
        foreach(['Artikelnummer','GTIN','HAN','ASIN','Netto-VK','Beschreibung','Inhalt/Menge'] as $field)
            self::assertArrayNotHasKey($field,$r['fields']);
    }
    public function test_rejects_wrong_brand_product_group_identifier_and_stale_mirror(): void
    {
        $r=$this->response();
        self::assertFalse(T::propose('VORLAGE-1','5901234123457',['Hersteller'=>'Wella'],$r)['ok']);
        self::assertFalse(T::propose('VORLAGE-1','5901234123457',['Artikelname'=>'Schwarzkopf Shampoo'],$r)['ok']);
        self::assertFalse(T::propose('OLD-HAN','5901234123457',[],$r)['ok']);
        self::assertFalse(T::propose('VORLAGE-1','4006381333931',[],$r)['ok']);
        $r['data']['jtl_status']='stale';
        self::assertFalse(T::propose('VORLAGE-1','5901234123457',[],$r)['ok']);
    }
    public function test_empty_description_uses_target_name_and_never_overwrites_text(): void
    {
        self::assertSame(['Kurzbeschreibung'=>'Neuer Artikel'],T::emptyTexts([
            'Artikelname'=>'Neuer Artikel','Kurzbeschreibung'=>'','Beschreibung'=>'Bereits gepflegt']));
        self::assertSame([],T::emptyTexts(['Artikelname'=>'']));
    }
}
