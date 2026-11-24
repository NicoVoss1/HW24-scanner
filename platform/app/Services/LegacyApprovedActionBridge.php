<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Strictly controlled operator-initiated POST bridge.
 *
 * NEVER invoke from lifecycle/mount/render/poll. Calls are only from validated
 * Livewire actions with server-side identity, approval, and private feature gates.
 * The original LIVE API keeps its own approval, origin and actor checks.
 * No JTL/WMS, Shopify, Amazon listing or price-write service is exposed here.
 */
final class LegacyApprovedActionBridge
{
    private const PATHS = [
        'scan.create' => 'api/scanliste.php',
        'scan.scan' => 'api/scanliste.php',
        'scan.import' => 'api/scanliste.php',
        'ai.review' => 'api/ai-review.php',
    ];

    public function post(string $service, array $payload, array $extraHeaders = []): array
    {
        $started = hrtime(true);
        $finish = static fn(array $result): array => BridgeQueryAudit::finish($service, $payload, $started, $result);
        $path = self::PATHS[$service] ?? null;
        if ($path === null) {
            throw new RuntimeException('HW24 write or paid service not approved');
        }

        $scan = str_starts_with($service, 'scan.');
        if (($scan && config('hw24_legacy.scan_writes_enabled') !== true) ||
            (!$scan && config('hw24_legacy.ai_paid_enabled') !== true)) {
            return $finish(['ok' => false, 'state' => 'disabled_by_operator', 'http_status' => null, 'data' => null]);
        }
        if (($payload['op'] ?? null) !== (match($service) {'scan.create'=>'create','scan.import'=>'import_rows',default=>'scan'})
            && $scan) {
            throw new RuntimeException('Legacy scan operation and service mismatch');
        }

        $base = rtrim((string) config('hw24_legacy.base_url'), '/');
        $user = (string) config('hw24_legacy.user');
        $password = (string) config('hw24_legacy.password');
        if ($base !== 'https://artikelmanager.hairworld24.de' || $user === '' || $password === '') {
            return $finish(['ok' => false, 'state' => 'unconfigured', 'http_status' => null, 'data' => null]);
        }
        // Only these two explicit recheck headers; all others forbidden to avoid
        // bypass of legacy approval or client-controlled HTTP headers.
        foreach ($extraHeaders as $key => $value) {
            if (!in_array($key, ['X-HW24-Recheck', 'X-HW24-Previous-Review'], true)
                || !is_string($value) || !preg_match('/^[a-zA-Z0-9]{1,18}$/D', $value)) {
                throw new RuntimeException('Unapproved HW24 action header');
            }
        }

        // Never send arbitrary oversized frontend state or credentials in bodies.
        $serialized = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($serialized) > ($scan ? 23000 : 10000)) {
            throw new RuntimeException('HW24 action payload too large');
        }

        try {
            $response = Http::withBasicAuth($user, $password)->acceptJson()->asJson()
                ->withHeaders(array_merge([
                    'Origin' => $base,
                    'X-HW24-Approval' => 'confirmed',
                ], $extraHeaders))
                ->timeout($scan ? 14 : 43)
                ->withoutRedirecting()
                ->post($base.'/'.$path, $payload);
            $json = $response->json();
            return $finish([
                'ok' => $response->successful() && is_array($json)
                    && (($json['ok'] ?? false) === true),
                'state' => $response->successful() ? 'responded' : 'upstream_error',
                'http_status' => $response->status(),
                // Only machine data from the trusted pinned endpoint; callers whitelist
                // fields before rendering to prevent exposing arbitrary upstream data.
                'data' => is_array($json) ? $json : null,
            ]);
        } catch (\Throwable) {
            return $finish(['ok' => false, 'state' => 'upstream_unreachable',
                'http_status' => null, 'data' => null]);
        }
    }
}
