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
    'openai_auto_calls'=>false,
    'amazon_auto_calls'=>false,
    'jtl_read_only'=>true,
    'channel_prices'=>['jtl','shopify','amazon','ebay'],
];
