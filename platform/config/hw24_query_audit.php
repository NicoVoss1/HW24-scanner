<?php
declare(strict_types=1);

return [
    // Remains OFF until operator approves isolated platform migration and audit tests.
    'enabled' => (bool) env('HW24_QUERY_AUDIT_ENABLED', false),
];
