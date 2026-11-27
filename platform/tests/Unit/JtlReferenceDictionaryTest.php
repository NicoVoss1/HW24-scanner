<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class JtlReferenceDictionaryTest extends TestCase
{
    public function test_reference_sources_are_disabled_and_use_allowlisted_columns(): void
    {
        $config=config('hw24_jtl_reference_dictionary');
        $this->assertFalse($config['enabled']);
        $this->assertSame('eazybusiness',$config['database']);
        $this->assertSame(
            ['kShop','cName','nTyp','nAktiv','kFirma'],
            $config['safe_reference_sources']['dbo.tShop']['columns']
        );
        $this->assertTrue($config['safe_reference_sources']['dbo.tShop']['sensitive_entire_row']);
        $denied=array_map('strtolower',$config['deny_columns_globally']);
        foreach ($config['safe_reference_sources'] as $spec) {
            foreach ($spec['columns'] as $column) {
                $this->assertNotContains(strtolower($column),$denied);
            }
        }
    }

    public function test_unverified_jtl_article_type_and_monetary_fields_are_not_guessed(): void
    {
        $config=config('hw24_jtl_reference_dictionary');
        $this->assertSame(
            0,
            $config['unresolved']['Artikel.tArtikelType']['observed_approx_rows']
        );
        $this->assertArrayHasKey('dbo.tPreisDetail',$config['unresolved']);
        $this->assertNotContains(
            'fVKBrutto',
            $config['unresolved']['dbo.tPreis']['columns']
        );
        $this->assertContains(
            'no_unverified_tax_rate_derivation',
            $config['restrictions']
        );
    }
}
