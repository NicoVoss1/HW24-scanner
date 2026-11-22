<?php
declare(strict_types=1);

/**
 * Evidence-based JTL reference dictionary.
 * HeidiSQL SQL Server 2017 eazybusiness PHASE 4 schema inventory, 2026-11-23.
 * Default OFF; metadata is NOT data rows. No connector or scheduled job here.
 *
 * NEVER bulk mirror dbo.tShop or any secret-bearing source: the
 * explicitly selected safe columns are the entire allowed read surface
 * for this source; privacy classification comes before any other source.
 */
return [
    'enabled' => false,
    'database' => 'eazybusiness',
    'status' => 'metadata_verified_values_unverified',
    'safe_reference_sources' => [
        'dbo.tLieferStatus' => [
            'observed_approx_rows' => 1,
            'columns' => ['kLieferStatus', 'kSprache', 'cName'],
            'purpose' => 'language_scoped_delivery_status',
        ],
        'dbo.tSteuerklasse' => [
            'observed_approx_rows' => 2,
            'columns' => ['kSteuerklasse', 'cName', 'nStandard', 'nTyp'],
            'purpose' => 'tax_class_labels',
        ],
        'dbo.tSteuersatz' => [
            'observed_approx_rows' => 4,
            'columns' => ['kSteuersatz', 'kSteuerzone', 'kSteuerklasse', 'fSteuersatz', 'nPrio'],
            'purpose' => 'tax_zone_scoped_rates',
        ],
        'dbo.tMassEinheit' => [
            'observed_approx_rows' => 31,
            'columns' => [
                'kMassEinheit', 'cCode', 'cDisplayCode',
                'kBezugsMassEinheit', 'fBezugsMassEinheitFaktor', 'cUnitCode',
            ],
            'purpose' => 'explicit_unit_conversion',
        ],
        'SCX.tChannelPriceType' => [
            'observed_approx_rows' => 12,
            'columns' => [
                'kChannelPriceType', 'kChannel', 'kShop',
                'cPriceTypeId', 'cDisplayName', 'cDescription', 'kKundenGruppe',
            ],
            'purpose' => 'scoped_channel_price_labels',
        ],
        'dbo.tShop' => [
            'observed_approx_rows' => 7,
            'columns' => ['kShop', 'cName', 'nTyp', 'nAktiv', 'kFirma'],
            'purpose' => 'shop_identity_only',
            'sensitive_entire_row' => true,
        ],
    ],
    'unresolved' => [
        'Artikel.tArtikelType' => [
            'observed_approx_rows' => 0,
            'meaning' => 'cannot infer default article type from an empty table',
        ],
        'dbo.tPreis' => [
            'observed_approx_rows' => 49,
            'columns' => ['kPreis', 'kArtikel', 'kKundenGruppe', 'kShop', 'kKunde', 'bRowversion'],
            'meaning' => 'price context keys; no price amounts in supplied six columns',
        ],
        'dbo.tPreisDetail' => [
            'meaning' => 'schema and monetary fields not yet verified',
        ],
        'SCX.tChannel' => [
            'meaning' => 'channel labels and account verification outstanding',
        ],
    ],
    'deny_columns_globally' => [
        'cAPIKey', 'cPasswortWeb', 'cBenutzerWeb',
    ],
    'restrictions' => [
        'no_auto_copy',
        'no_all_columns_star_select',
        'no_secrets_or_customer_data',
        'no_unverified_tax_rate_derivation',
        'no_unverified_article_type_defaults',
        'no_unverified_live_offer_price_claims',
    ],
];
