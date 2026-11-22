<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class JtlReferenceObservationsTest extends TestCase
{
    public function test_confirmed_reference_values_have_source_and_cannot_be_used_as_live_data(): void
    {
        $data=config('hw24_jtl_reference_observations');
        $this->assertIsArray($data);
        $this->assertFalse($data['enabled_for_runtime_data']);
        $this->assertSame('eazybusiness_operator_readonly_sql', $data['source']);
        $this->assertSame('2-4 Werktage', $data['delivery_status_by_language']['1:1']);
        $this->assertSame(19, $data['tax_rate_percent_by_class_and_zone']['1:3']);
        $this->assertSame(0, $data['tax_rate_percent_by_class_and_zone']['2:4']);
        $this->assertFalse($data['tax_zone_geography_confirmed']);
        $this->assertSame('ml', $data['mass_unit_examples'][16]['display_code']);
        $this->assertFalse($data['article_mass_quantity_scale_confirmed']);
        $this->assertFalse($data['jtl_live_offer_price_confirmed']);
        $this->assertFalse($data['secrets_and_customer_rows_import_allowed']);
    }

    public function test_all_twelve_price_types_are_associated_with_known_shops(): void
    {
        $data=config('hw24_jtl_reference_observations');
        $this->assertCount(12, $data['channel_price_types']);
        $this->assertCount(7, $data['shops']);
        foreach ($data['channel_price_types'] as $spec) {
            $this->assertArrayHasKey($spec['shop_id'], $data['shops']);
            $this->assertContains($spec['code'], ['B2C', 'B2B', 'B2CMin']);
        }
        $this->assertSame('Shopify Fb24',$data['shops'][3]['name']);
        $this->assertArrayNotHasKey(3, $data['observed_scx_offer_counts']);
        $this->assertFalse($data['shopify_catalog_empty_confirmed']);
    }

    public function test_observed_offer_counts_do_not_mix_total_with_listed(): void
    {
        $counts=config('hw24_jtl_reference_observations.observed_scx_offer_counts');
        $this->assertSame(16049, array_sum(array_column($counts, 'all')));
        $this->assertSame(7913, array_sum(array_column($counts, 'jtl_listed_flag_1')));
        $this->assertSame(2294, $counts[4]['jtl_listed_flag_1']);
    }
}
