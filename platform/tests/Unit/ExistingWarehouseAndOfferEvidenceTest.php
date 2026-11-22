<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Amazon\OfferEvidence;
use App\Domain\Jtl\RelatedSourceEvidence;
use PHPUnit\Framework\TestCase;

final class ExistingWarehouseAndOfferEvidenceTest extends TestCase
{
    public function test_existing_jtl_related_sources_supply_available_orders_incoming_and_real_bins(): void
    {
        $source=[
            'bestand'=>'11',
            'related_sources'=>[
                'bestand'=>['confirmed'=>true,'truncated'=>false,'rows'=>[[
                    'fLagerbestand'=>'11','fVerfuegbar'=>'7','fInAuftraegen'=>'3',
                    'fZulauf'=>'4',
                ]]],
                'wms_lagerplaetze'=>['confirmed'=>true,'truncated'=>false,'rows'=>[
                    [
                        'warenlager_name'=>'WMS Berlin','warenlager_id'=>'1',
                        'lagerplatz'=>'A-01-05','lagerplatz_id'=>103,
                        'menge'=>'5','gesperrt'=>false,'auslieferung_gesperrt'=>false,
                        'letzte_bewegung_utc'=>'2026-11-22 20:17:00',
                    ],
                    [
                        'warenlager_name'=>'Retouren','lagerplatz'=>'R-01',
                        'menge'=>'0','gesperrt'=>true,'auslieferung_gesperrt'=>true,
                    ],
                ]],
                'amazon_angebote'=>['confirmed'=>true,'rows'=>[[
                    'asin'=>'B091J3HV6B','seller_sku'=>'MAT-6C-90',
                    'plattform'=>'Amazon.de','preis_angebot'=>'14.25',
                    'menge_angebot'=>'2','fulfillment'=>'FBA',
                ]]],
            ],
        ];
        $value=RelatedSourceEvidence::present($source);
        self::assertSame('7',$value['stock']['available']);
        self::assertSame('3',$value['stock']['in_orders']);
        self::assertSame('4',$value['stock']['incoming']);
        self::assertNull($value['stock']['reservations']);
        self::assertTrue($value['wms']['confirmed']);
        self::assertSame(2,$value['wms']['count']);
        self::assertSame(1,$value['wms']['positive_bins']);
        self::assertSame(1,$value['wms']['locked_bins']);
        self::assertSame('0',$value['wms']['bins'][1]['quantity']);
        self::assertTrue($value['wms']['bins'][1]['shipping_locked']);
        self::assertSame('14.25',$value['amazon_jtl']['offers'][0]['jtl_price']);
    }

    public function test_unconfirmed_or_truncated_warehouse_mapping_is_not_invented(): void
    {
        $source=['bestand'=>'0','related_sources'=>[
            'bestand'=>['truncated'=>true,'rows'=>[['fVerfuegbar'=>'99']]],
            'wms_lagerplaetze'=>['confirmed'=>false,'rows'=>[
                ['warenlager_name'=>'Falsche Angaben','menge'=>'99999'],
            ]],
            'amazon_angebote'=>['confirmed'=>false,'rows'=>[
                ['asin'=>'B091J3HV6B','preis_angebot'=>'9.99'],
            ]],
        ]];
        $value=RelatedSourceEvidence::present($source);
        self::assertSame('0',$value['stock']['total']);
        self::assertNull($value['stock']['available']);
        self::assertNull($value['stock']['in_orders']);
        self::assertSame([],$value['wms']['bins']);
        self::assertSame([],$value['amazon_jtl']['offers']);
    }

    public function test_amazon_buy_box_is_not_conflated_with_cheapest_visible_offer(): void
    {
        $api=[
            'retrieved_at_utc'=>'2026-11-22T21:10:00Z',
            'total_offer_count'=>3,
            'buybox_prices'=>[['landed_price'=>['amount'=>16.70,'currency'=>'EUR']]],
            'lowest_prices'=>[['landed_price'=>['amount'=>14.10,'currency'=>'EUR']]],
            'offers'=>[
                [
                    'seller_id'=>'SELLERA1234','is_buybox_winner'=>true,
                    'is_featured_merchant'=>true,'is_fba'=>true,
                    'total'=>['amount'=>16.70,'currency'=>'EUR'],
                    'item_price'=>['amount'=>15.70,'currency'=>'EUR'],
                    'shipping'=>['amount'=>1.00,'currency'=>'EUR'],
                    'seller_feedback'=>['positive_percent'=>97.5,'feedback_count'=>140],
                ],
                [
                    'seller_id'=>'SELLERB1234','is_buybox_winner'=>false,
                    'is_featured_merchant'=>true,'is_fba'=>false,
                    'total'=>['amount'=>14.10,'currency'=>'EUR'],
                    'seller_feedback'=>['positive_percent'=>92.0,'feedback_count'=>50],
                ],
            ],
        ];
        self::assertSame(['SELLERA1234','SELLERB1234'],OfferEvidence::sellerIds($api));
        $result=OfferEvidence::present($api,['SELLERA1234'=>'Händler Alpha']);
        self::assertSame(2,$result['returned']);
        self::assertSame(1,$result['winners']);
        self::assertSame('Händler Alpha',$result['offers'][0]['seller_name']);
        self::assertTrue($result['offers'][0]['winner']);
        self::assertSame(16.70,$result['buy_box']['amount']);
        self::assertSame(14.10,$result['lowest_summary']['amount']);
        self::assertSame(14.10,$result['lowest_visible']['amount']);
        self::assertSame(97.5,$result['offers'][0]['feedback_percent']);
        self::assertSame(140,$result['offers'][0]['feedback_count']);
        self::assertNotSame($result['buy_box']['amount'],$result['lowest_summary']['amount']);
    }

    public function test_incomplete_price_and_unverified_seller_names_do_not_become_amount_or_identity(): void
    {
        $api=['offers'=>[
            ['seller_id'=>'BAD!!','is_buybox_winner'=>true,
                'total'=>['amount'=>'1','currency'=>'EUR']],
            ['seller_id'=>'SELLERVALID1','is_buybox_winner'=>false,
                'item_price'=>['amount'=>'3','currency'=>'EUR'],'shipping'=>null,
                'total'=>null,'seller_feedback'=>['positive_percent'=>120]],
        ]];
        $r=OfferEvidence::present($api);
        self::assertSame(1,$r['returned']);
        self::assertNull($r['offers'][0]['total']);
        self::assertNull($r['offers'][0]['seller_name']);
        self::assertNull($r['offers'][0]['feedback_percent']);
        self::assertNull($r['lowest_visible']);
    }
}
