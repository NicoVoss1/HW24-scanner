<?php
declare(strict_types=1);

/**
 * Strict parser for 'php artisan route:list --path=admin --json'.
 * Avoid grep-based false negatives when Laravel pretty-prints JSON whitespace.
 * Usage: ... | php platform/ops/verify-admin-routes-json.php admin/scanliste ...
 */
if (PHP_SAPI !== 'cli' || $argc < 2) {
    fwrite(STDERR, "Expected JSON input on STDIN and route names as arguments\n");
    exit(2);
}
try {
    $data = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !array_is_list($data)) {
        throw new RuntimeException('Route list must be JSON array');
    }
    $found = [];
    foreach ($data as $entry) {
        if (!is_array($entry) || !is_string($entry['uri'] ?? null)) {
            continue;
        }
        $found[$entry['uri']] = true;
    }
    foreach (array_slice($argv, 1) as $route) {
        if (!preg_match('/^admin(?:\/[a-z0-9-]+)+$/D', $route) ||
            !isset($found[$route])) {
            fwrite(STDERR, "STOP: Filament route missing: ".preg_replace('/[^a-z0-9\/-]/','?',(string)$route)."\n");
            exit(2);
        }
    }
    echo "OK: Required Filament routes parsed from JSON\n";
} catch (Throwable $e) {
    fwrite(STDERR, "STOP: Invalid Laravel route-list JSON\n");
    exit(2);
}
