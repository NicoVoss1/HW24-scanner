<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubCategoryPolicy as C;
use App\Services\HubPeerDefaults as P;
use App\Services\ScanResearchPresentation as R;
use PHPUnit\Framework\TestCase;

final class HubDataPolicyTest extends TestCase
{
    public function test_categories_are_distinct_and_old_styling_label_does_not_override_colour(): void
    {
        self::assertSame(['Kategorie Ebene 1'=>'Friseurbedarf','Kategorie Ebene 2'=>'Haarfarben'],C::suggest('Wella Koleston Perfect Haarfarbe','Hair Styling'));
        self::assertTrue(C::conflict('Igora Royal Haarfarbe','Hair Styling','Friseurbedarf','Hair Styling'));
        self::assertSame('Haarpflege',C::suggest('Goldwell Trockenshampoo')['Kategorie Ebene 2']);
        self::assertSame('Hairstyling',C::suggest('Wella Haarspray')['Kategorie Ebene 2']);
        self::assertNull(C::suggest('Haarfarbe Shampoo Set'));
    }
    public function test_reference_evidence_resolves_known_ids_and_preserves_unmapped_state(): void
    {
        $o=require dirname(__DIR__,2).'/config/hw24_jtl_reference_observations.php';
        $r=R::jtlFields('4021609029274',['ok'=>true,'data'=>['jtl_status'=>'current_snapshot','matches'=>[
            ['ean'=>'4021609029274','artikelname'=>'Test','beschreibung'=>'Echte Beschreibung','jtl_source_fields'=>[
                'kLieferStatus'=>1,'kSteuerklasse'=>1,'kMassEinheit'=>16,'fMassMenge'=>'90.0000']]
        ]]],$o);
        self::assertSame('2-4 Werktage (ID 1)',$r['fields']['Lieferstatus']['value']);
        self::assertSame('observed_reference',$r['fields']['Lieferstatus']['state']);
        self::assertSame('Normaler Steuersatz (ID 1)',$r['fields']['Steuerklasse']['value']);
        self::assertSame('ml (ID 16)',$r['fields']['Maßeinheit']['value']);
        self::assertSame('Echte Beschreibung',$r['fields']['Beschreibung']['value']);
    }
    public function test_peer_suggestions_require_distinct_compatible_articles_and_do_not_copy_prices(): void
    {
        $target=['jtl_article_id'=>1,'kWarengruppe'=>2,'kHersteller'=>3,'kSteuerklasse'=>2];
        $rows=[];
        foreach([2,3,4] as $id)$rows[]=['jtl_article_id'=>$id,'kWarengruppe'=>2,'kHersteller'=>3,
            'kLieferStatus'=>1,'kSteuerklasse'=>1,'netto_vk'=>'12.00','beschreibung'=>'Not copied'];
        $v=P::suggest($target,$rows);
        self::assertSame(['Lieferstatus'],array_keys($v));
        self::assertSame(3,$v['Lieferstatus']['support']);
        self::assertTrue($v['Lieferstatus']['approval_required']);
        $rows[2]['kHersteller']=4;
        self::assertSame([],P::suggest($target,$rows));
    }
}
