<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Jtl\ArticleCard;
use PHPUnit\Framework\TestCase;

final class ArticleCardTest extends TestCase
{
    public function test_keeps_only_real_prices_and_never_invents_gross_or_status_labels(): void
    {
        $card = ArticleCard::fromMatch([
            'jtl_article_id' => 1428,
            'artikelnummer' => '000123',
            'ean' => '3474630337398',
            'artikelname' => 'Matrix 6C',
            'bestand' => 3,
            'netto_vk' => '11.7647',
            'netto_ek' => '7.21',
            'brutto_vk' => '14.00',
            'waehrung' => null,
            'jtl_source_fields' => ['fMassMenge' => '90.000', 'kLieferStatus' => 1, 'kSteuerklasse' => 2],
        ]);

        self::assertSame('000123', $card['artikelnummer']);
        self::assertSame('3474630337398', $card['ean']);
        self::assertSame(90.0, $card['masseinheit_menge']);
        self::assertSame('1', $card['lieferstatus_id']);
        self::assertNull($card['lieferstatus_name']);
        self::assertSame('2', $card['steuerklasse_id']);
        self::assertNull($card['steuerklasse_name']);
        self::assertSame(11.7647, $card['netto_vk']);
        self::assertNull($card['brutto_vk'], 'Gross VK without a verified source must remain unknown');
        self::assertNull($card['waehrung'], 'EUR must not be guessed from the country');
        self::assertNull($card['artikeltyp'], 'Standard type must not be invented');
    }

    public function test_accepts_only_explicitly_sourced_gross_and_keeps_channel_asin_unverified(): void
    {
        $card = ArticleCard::fromMatch([
            'ean' => '3474630337398',
            'brutto_vk' => '14.00',
            'brutto_vk_source' => 'verified Windows SQL tax and group price view',
            'waehrung' => 'EUR',
            'artikeltyp' => 'Standard',
            'asin' => 'B091J3HV6B',
            'lieferstatus_name' => 'Lieferbar',
        ]);

        self::assertSame(14.0, $card['brutto_vk']);
        self::assertSame('verified Windows SQL tax and group price view', $card['brutto_vk_source']);
        self::assertSame('EUR', $card['waehrung']);
        self::assertSame('Standard', $card['artikeltyp']);
        self::assertSame('B091J3HV6B', $card['asin']);
        self::assertSame('Lieferbar', $card['lieferstatus_name']);
        self::assertArrayNotHasKey('amazon_ean_verified', $card);
    }

    public function test_rejects_non_scalar_source_data_and_invalid_asin(): void
    {
        $card = ArticleCard::fromMatch([
            'artikelname' => ['unexpected'],
            'asin' => 'wrong!',
            'netto_vk' => ['99'],
            'brutto_vk' => -5,
            'brutto_vk_source' => ['not a source'],
            'jtl_source_fields' => ['fMassMenge' => ['90']],
        ]);

        self::assertSame('', $card['artikelname']);
        self::assertNull($card['asin']);
        self::assertNull($card['netto_vk']);
        self::assertNull($card['brutto_vk']);
        self::assertNull($card['masseinheit_menge']);
    }
}
