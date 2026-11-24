<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Metadata-only audit at the trusted platform bridge boundary.
 * An audit failure never changes an already executed upstream result or
 * encourages an automatic retry of a paid call. No raw payloads/credentials.
 */
final class BridgeQueryAudit
{
    public static function finish(string $service, array $parameters, int $started, array $response): array
    {
        if (!config('hw24_query_audit.enabled', false)) {
            return $response + ['audit_state' => 'disabled'];
        }

        $source = explode('.', $service, 2)[0];
        if ($source === 'scan') $source = 'scanner';
        if ($source === 'curated') $source = 'legacy';
        $identifierType = null;
        $identifier = null;
        // Strict product identifiers only; the recorder independently
        // validates ASIN/GTIN (including check digit).
        foreach (['ean', 'asin', 'q', 'eans'] as $key) {
            $value = $parameters[$key] ?? null;
            if (!is_string($value)) continue;
            if ($key === 'asin' && preg_match('/^[A-Z0-9]{10}$/D', $value)) {
                $identifierType = 'asin';
                $identifier = $value;
                break;
            }
            if ($key !== 'asin' && preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D', $value)) {
                $identifierType = 'ean';
                $identifier = $value;
                break;
            }
        }
        $status = $response['http_status'] ?? null;
        $result = $status === 429 ? 'throttled' : (($response['ok'] ?? false) === true ? 'success' : 'failure');
        try {
            $saved = app(QueryAuditRecorder::class)->record(
                $source, $service, 'manual', $identifierType, $identifier,
                is_int($status) ? $status : null,
                min(86400000, max(0, (int) ((hrtime(true) - $started) / 1000000))),
                $result
            );
            return $response + ['audit_state' => $saved ? 'recorded' : 'disabled'];
        } catch (\Throwable) {
            // Do not log the request, upstream body or exception text.
            logger()->error('HW24 bridge audit persistence failed', ['service' => $service]);
            return $response + ['audit_state' => 'persistence_failed'];
        }
    }
}
