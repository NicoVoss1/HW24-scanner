<?php
/**
 * Isolierter HW24-Kundenservice. Fail-closed: keine API-Aufrufe oder Sendungen beim Öffnen.
 * Tracking-CSV/Snapshot enthält personenbezogene Daten; niemals Webroot/Git nutzen.
 */
return [
    'tracking_snapshot' => env('HW24_SUPPORT_TRACKING_SQLITE', ''),
    'snapshot_max_age_hours' => (int) env('HW24_SUPPORT_SNAPSHOT_MAX_AGE_HOURS', 12),
    'ai_enabled' => filter_var(env('HW24_SUPPORT_AI_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'ai_api_key' => env('HW24_SUPPORT_AI_API_KEY', ''),
    'ai_model' => env('HW24_SUPPORT_AI_MODEL', 'gpt-4.1-mini'),
    // Amazon Messaging: ausdrücklich noch NICHT implementiert / keine Freigabe.
    'amazon_messaging_enabled' => false,
];