<?php
declare(strict_types=1);

/**
 * Operator-observed (NOT real-time) JTL reference values.
 * Source: HeidiSQL SQL Server eazybusiness, submitted 2026-11-23.
 *
 * Documentation/test fixtures ONLY. Never substitute for current JTL data,
 * never infer destination tax zones, historical prices, source currencies,
 * live listings or account credentials from these static observations.
 */
return [
    'enabled_for_runtime_data' => false,
    'observed_date' => '2026-11-23',
    'source' => 'eazybusiness_operator_readonly_sql',
    'delivery_status_by_language' => [
        '1:1' => '2-4 Werktage',
    ],
    'tax_classes' => [
        1 => ['name' => 'Normaler Steuersatz', 'is_default_raw' => 1, 'type_raw' => 4],
        2 => ['name' => 'Ermäßigter Steuersatz', 'is_default_raw' => 0, 'type_raw' => 3],
    ],
    'tax_rate_percent_by_class_and_zone' => [
        '1:3' => 19,
        '1:4' => 19,
        '2:3' => 7,
        '2:4' => 0,
    ],
    'tax_zone_geography_confirmed' => false,
    'mass_unit_examples' => [
        16 => [
            'code' => 'mL',
            'display_code' => 'ml',
            'base_unit_id' => 15,
            'base_conversion_factor' => 0.001,
        ],
        21 => [
            'code' => '',
            'display_code' => 'Stk',
            'base_unit_id' => 0,
            'base_conversion_factor' => 0,
        ],
    ],
    'article_mass_quantity_scale_confirmed' => false,
    'shops' => [
        1 => ['name' => 'Friseurbedarf24.shop (Kaufland.de)', 'active' => true],
        3 => ['name' => 'Shopify Fb24', 'active' => true],
        4 => ['name' => 'Amazon.de Lister', 'active' => true],
        5 => ['name' => 'Amazon.fr Lister', 'active' => true],
        6 => ['name' => 'Amazon.it Lister', 'active' => true],
        7 => ['name' => 'Amazon.es Lister', 'active' => true],
        8 => ['name' => 'Amazon.co.uk Lister', 'active' => true],
    ],
    'channel_price_types' => [
        1 => ['shop_id' => 1, 'channel_id' => 19, 'code' => 'B2CMin', 'label' => 'Minimal Preis', 'customer_group_id' => 6],
        2 => ['shop_id' => 1, 'channel_id' => 19, 'code' => 'B2C', 'label' => 'Endkunden Preis', 'customer_group_id' => 5],
        3 => ['shop_id' => 4, 'channel_id' => 37, 'code' => 'B2B', 'label' => 'Geschäftskunden Preis', 'customer_group_id' => 4],
        4 => ['shop_id' => 4, 'channel_id' => 37, 'code' => 'B2C', 'label' => 'Endkunden Preis', 'customer_group_id' => 3],
        5 => ['shop_id' => 5, 'channel_id' => 34, 'code' => 'B2B', 'label' => 'Geschäftskunden Preis', 'customer_group_id' => 4],
        6 => ['shop_id' => 5, 'channel_id' => 34, 'code' => 'B2C', 'label' => 'Endkunden Preis', 'customer_group_id' => 3],
        7 => ['shop_id' => 6, 'channel_id' => 33, 'code' => 'B2B', 'label' => 'Geschäftskunden Preis', 'customer_group_id' => 4],
        8 => ['shop_id' => 6, 'channel_id' => 33, 'code' => 'B2C', 'label' => 'Endkunden Preis', 'customer_group_id' => 3],
        9 => ['shop_id' => 7, 'channel_id' => 36, 'code' => 'B2B', 'label' => 'Geschäftskunden Preis', 'customer_group_id' => 4],
        10 => ['shop_id' => 7, 'channel_id' => 36, 'code' => 'B2C', 'label' => 'Endkunden Preis', 'customer_group_id' => 3],
        11 => ['shop_id' => 8, 'channel_id' => 35, 'code' => 'B2B', 'label' => 'Geschäftskunden Preis', 'customer_group_id' => 4],
        12 => ['shop_id' => 8, 'channel_id' => 35, 'code' => 'B2C', 'label' => 'Endkunden Preis', 'customer_group_id' => 3],
    ],
    'observed_scx_offer_counts' => [
        1 => ['all' => 3205, 'jtl_listed_flag_1' => 1159],
        4 => ['all' => 2735, 'jtl_listed_flag_1' => 2294],
        5 => ['all' => 2662, 'jtl_listed_flag_1' => 1118],
        6 => ['all' => 2658, 'jtl_listed_flag_1' => 1275],
        7 => ['all' => 2658, 'jtl_listed_flag_1' => 1279],
        8 => ['all' => 2131, 'jtl_listed_flag_1' => 788],
    ],
    'scx_shopify_listing_count_observed' => 0,
    'shopify_catalog_empty_confirmed' => false,
    'jtl_live_offer_price_confirmed' => false,
    'jtl_channel_price_currency_confirmed' => false,
    'secrets_and_customer_rows_import_allowed' => false,
];
