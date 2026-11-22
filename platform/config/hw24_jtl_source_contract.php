<?php
declare(strict_types=1);

/**
 * HW24 JTL source contract v1: METADATA ONLY.
 *
 * Provenance: HeidiSQL operator-supplied SQL Server 2017 eazybusiness
 * sys.tables / sys.columns / sys.indexes / sys.foreign_keys snapshots
 * evaluated 2026-11-23. Nothing here opens SQL Server connections,
 * copies rows, changes JTL, invokes paid APIs or grants permissions.
 *
 * A table's PK, columns and optional FK references are facts from the
 * operator inventory; synchronization strategies are CANDIDATES only.
 */
return [
    'enabled' => false, // No automated import until operator approval.
    'source_system' => 'jtl_wawi',
    'source_database' => 'eazybusiness',
    'observed_sql_major' => 14,
    'tables' => [
        'dbo.tArtikel' => [
            'primary_key' => ['kArtikel'],
            'unique_keys' => [['cArtNr']],
            'change_source_candidate' => 'bRowversion',
            'change_mode' => 'rowversion_candidate',
            'data_class' => 'business_article',
        ],
        'dbo.tArtikelHistory' => [
            'primary_key' => ['kArtikelHistory'],
            'unique_keys' => [],
            'change_source_candidate' => 'bRowversion',
            'change_mode' => 'rowversion_candidate',
            'data_class' => 'business_history',
        ],
        'dbo.tArtikelShop' => [
            'primary_key' => ['kArtikel', 'kShop'],
            'unique_keys' => [],
            'change_source_candidate' => 'bRowversion',
            'change_mode' => 'rowversion_candidate',
            'data_class' => 'business_article_channel',
        ],
        'dbo.tlagerbestandProLagerLagerartikel' => [
            'primary_key' => ['kArtikel', 'kWarenlager'],
            'unique_keys' => [],
            'change_source_candidate' => 'bRowversion',
            'change_mode' => 'rowversion_candidate',
            'data_class' => 'business_inventory',
        ],
        'dbo.tWarenLagerPlatzArtikel' => [
            'primary_key' => ['kWarenLagerPlatz', 'kArtikel'],
            'unique_keys' => [],
            'change_source_candidate' => 'bRowversion',
            'change_mode' => 'rowversion_candidate',
            'data_class' => 'business_inventory',
        ],
        'dbo.pf_amazon_angebot' => [
            'primary_key' => ['cSellerSKU', 'kUser', 'nPlattform'],
            'unique_keys' => [],
            'change_source_candidate' => null,
            'change_mode' => 'full_reconcile_candidate',
            'data_class' => 'business_channel_offer',
        ],
        'dbo.pf_amazon_angebot_ext' => [
            'primary_key' => ['cSellerSKU', 'kUser', 'nPlattform'],
            'unique_keys' => [],
            'change_source_candidate' => null,
            'change_mode' => 'full_reconcile_candidate',
            'data_class' => 'business_channel_offer',
        ],
        'SCX.tOffer' => [
            'primary_key' => ['kOffer'],
            'unique_keys' => [['kArtikel', 'kShop']],
            'change_source_candidate' => null,
            'change_mode' => 'full_reconcile_candidate',
            'data_class' => 'business_channel_offer',
        ],
        'SCX.tOfferPrice' => [
            'primary_key' => ['kOfferPrice'],
            'unique_keys' => [],
            'change_source_candidate' => null,
            'change_mode' => 'full_reconcile_candidate',
            'data_class' => 'business_channel_offer',
        ],
        'SCX.tVerkaufskanalArtikelData' => [
            'primary_key' => ['kVerkaufskanalArtikelData'],
            'unique_keys' => [['kArtikel', 'kShopScx']],
            'change_source_candidate' => null,
            'change_mode' => 'full_reconcile_candidate',
            'data_class' => 'business_channel_offer',
        ],
        'Verkauf.tAuftragPosition' => [
            'primary_key' => ['kAuftragPosition'],
            'unique_keys' => [],
            'change_source_candidate' => null,
            'change_mode' => 'not_approved',
            'data_class' => 'restricted_order',
        ],
        'Verkauf.tAuftragPositionEckdaten' => [
            'primary_key' => ['kAuftragPosition'],
            'unique_keys' => [],
            'change_source_candidate' => null,
            'change_mode' => 'not_approved',
            'data_class' => 'restricted_order',
        ],
    ],
    'foreign_key_evidence' => [
        ['SCX.tOfferPrice', 'kOffer', 'SCX.tOffer', 'kOffer'],
        ['SCX.tOfferPrice', 'kChannelPriceType', 'SCX.tChannelPriceType', 'kChannelPriceType'],
        ['SCX.tVerkaufskanalArtikelData', 'kArtikel', 'dbo.tArtikel', 'kArtikel'],
        ['dbo.tPreis', 'kArtikel', 'dbo.tArtikel', 'kArtikel'],
        ['Artikel.tArtikelType', 'kArtikel', 'dbo.tArtikel', 'kArtikel'],
        ['Artikel.tArtikelAsin', 'kArtikel', 'dbo.tArtikel', 'kArtikel'],
        ['Artikel.tArtikelkosten', 'kArtikel', 'dbo.tArtikel', 'kArtikel'],
    ],
    'restrictions' => [
        'no_jtl_ddl_or_dml',
        'no_auto_sync',
        'no_credential_storage',
        'no_unverified_customer_or_order_copy',
        'no_unverified_amazon_seller_id_join',
        'no_deletes_from_rowversion_alone',
        'no_price_type_inference',
        'no_automatic_rowversion_index_creation',
    ],
];
