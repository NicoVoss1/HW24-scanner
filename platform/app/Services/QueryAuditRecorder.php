<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Opt-in metadata-only audit for future explicitly wired HW24 requests.
 * This service does NOT call any external API and does NOT archive API payloads.
 * Throws if enabled and persistence fails: never claim a request was audited
 * when the isolated database did not persist it.
 */
final class QueryAuditRecorder
{
    public function record(
        string $sourceSystem,
        string $operation,
        string $triggerType,
        ?string $identifierType,
        ?string $identifier,
        ?int $httpStatus,
        ?int $durationMs,
        string $result
    ): bool {
        if (! config('hw24_query_audit.enabled', false)) {
            return false;
        }
        if (! in_array($sourceSystem, ['jtl', 'amazon', 'ebay', 'shopify', 'ai', 'scanner', 'legacy'], true)
            || ! preg_match('/^[a-zA-Z][a-zA-Z0-9._-]{0,63}$/D', $operation)
            || ! in_array($triggerType, ['manual', 'scheduled', 'system'], true)
            || ! in_array($result, ['success', 'failure', 'throttled'], true)
            || ($httpStatus !== null && ($httpStatus < 100 || $httpStatus > 599))
            || ($durationMs !== null && ($durationMs < 0 || $durationMs > 86400000))) {
            throw new InvalidArgumentException('Invalid HW24 audit metadata');
        }

        $type = null;
        $key = null;
        // Only verified product identifiers are allowed. Never store raw URLs,
        // query strings, customer names, order details or API credentials.
        if ($identifierType === 'asin' && is_string($identifier)
            && preg_match('/^[A-Z0-9]{10}$/D', strtoupper($identifier))) {
            $type = 'asin';
            $key = strtoupper($identifier);
        } elseif ($identifierType === 'ean' && is_string($identifier)
            && preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D', $identifier)
            && $this->hasValidGtinCheckDigit($identifier)) {
            $type = 'ean';
            $key = $identifier;
        }

        $actor = Auth::id();
        DB::table('query_audit_events')->insert([
            'request_id' => (string) Str::uuid(),
            'source_system' => $sourceSystem,
            'operation' => $operation,
            'trigger_type' => $triggerType,
            'actor_ref' => $actor === null ? null : mb_substr((string) $actor, 0, 128),
            'identifier_type' => $type,
            'identifier' => $key,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'result' => $result,
            'requested_at' => now('UTC'),
            'created_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
        return true;
    }

    private function hasValidGtinCheckDigit(string $gtin): bool
    {
        $sum = 0;
        $weight = 3;
        for ($index = strlen($gtin) - 2; $index >= 0; $index--) {
            $sum += ((int) $gtin[$index]) * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }
        return (10 - $sum % 10) % 10 === (int) $gtin[strlen($gtin) - 1];
    }
}
