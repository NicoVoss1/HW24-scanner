<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class JtlSourceContractTest extends TestCase
{
    public function test_source_contract_is_read_only_and_disabled(): void
    {
        $contract = config('hw24_jtl_source_contract');
        $this->assertIsArray($contract);
        $this->assertFalse($contract['enabled']);
        $this->assertSame('eazybusiness', $contract['source_database']);
        $this->assertContains('no_auto_sync', $contract['restrictions']);
        $this->assertContains('no_jtl_ddl_or_dml', $contract['restrictions']);
        $this->assertContains('no_deletes_from_rowversion_alone', $contract['restrictions']);
        $this->assertContains('no_price_type_inference', $contract['restrictions']);
    }

    public function test_composite_business_keys_are_not_truncated(): void
    {
        $tables = config('hw24_jtl_source_contract.tables');
        $this->assertSame(
            ['cSellerSKU', 'kUser', 'nPlattform'],
            $tables['dbo.pf_amazon_angebot']['primary_key']
        );
        $this->assertSame(
            ['kArtikel', 'kWarenlager'],
            $tables['dbo.tlagerbestandProLagerLagerartikel']['primary_key']
        );
        $this->assertSame(
            ['kWarenLagerPlatz', 'kArtikel'],
            $tables['dbo.tWarenLagerPlatzArtikel']['primary_key']
        );
        $this->assertSame(
            [['kArtikel', 'kShop']],
            $tables['SCX.tOffer']['unique_keys']
        );
    }

    public function test_only_verified_rowversions_are_declared(): void
    {
        $tables = config('hw24_jtl_source_contract.tables');
        foreach ([
            'dbo.tArtikel',
            'dbo.tArtikelShop',
            'dbo.tArtikelHistory',
            'dbo.tlagerbestandProLagerLagerartikel',
            'dbo.tWarenLagerPlatzArtikel',
        ] as $tableName) {
            $this->assertSame('bRowversion', $tables[$tableName]['change_source_candidate']);
            $this->assertSame('rowversion_candidate', $tables[$tableName]['change_mode']);
        }
        foreach ([
            'dbo.pf_amazon_angebot',
            'SCX.tOffer',
            'SCX.tOfferPrice',
        ] as $tableName) {
            $this->assertNull($tables[$tableName]['change_source_candidate']);
            $this->assertSame('full_reconcile_candidate', $tables[$tableName]['change_mode']);
        }
        $this->assertSame('not_approved', $tables['Verkauf.tAuftragPosition']['change_mode']);
    }
}
