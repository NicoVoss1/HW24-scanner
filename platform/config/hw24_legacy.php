<?php
/**
 * Separate Laravel test app. DO NOT reuse the old public Basic Auth credentials
 * in browser-side JavaScript or GitHub. Set through server environment only.
 */
return [
    'base_url'=>env('HW24_LEGACY_URL'),
    'admin_email'=>env('HW24_ADMIN_EMAIL', ''),
    'user'=>env('HW24_LEGACY_BASIC_USER'),
    'password'=>env('HW24_LEGACY_BASIC_PASSWORD'),
    'writes_enabled'=>false,
    // Operator-owned private toggles. Default false: no paid OpenAI or write to
    // existing HW24 scanlist MariaDB until separate explicit enablement.
    'scan_writes_enabled'=>env('HW24_SCAN_WRITES_ENABLED', false) === true || env('HW24_SCAN_WRITES_ENABLED') === 'true',
    'ai_paid_enabled'=>env('HW24_AI_PAID_ENABLED', false) === true || env('HW24_AI_PAID_ENABLED') === 'true',
    'openai_auto_calls'=>false,
    'amazon_auto_calls'=>false,
    'jtl_read_only'=>true,
    'channel_prices'=>['jtl','shopify','amazon','ebay'],
];
