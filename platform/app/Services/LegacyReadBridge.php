<?php
declare(strict_types=1);
namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * TEMPORARY READ-ONLY adapter to the existing, protected HW24 LIVE LEGACY APIs.
 * This class cannot write to Wawi, WMS, Shopify, Amazon or OpenAI.
 * No upstream request is made unless a caller explicitly invokes fetch().
 */
final class LegacyReadBridge
{
    private const PATHS = [
        'jtl.article' => 'api/jtl.php',
        // Available only AFTER the source-explorer Legacy PR has passed test deployment.
        'jtl.fields' => 'api/jtl-fields.php',
        'jtl.quality' => 'api/jtl-quality.php',
        'jtl.wms.history' => 'api/jtl-wms-history.php', // GET-only movements mirror, not current available bins
        'jtl.options' => 'api/jtl-options.php',
        'amazon.catalog' => 'api/amazon.php',
        'amazon.archive' => 'api/amazon-archive.php', // Requires separate LIVE read-only API release
        'amazon.keywords' => 'api/amazon-keywords.php',
        'amazon.pricing' => 'api/amazon-pricing.php',
        'amazon.sellers' => 'api/amazon-sellers.php', // verified seller names, GET only
        'ai.history' => 'api/ai-reviews.php',
        'ai.summary' => 'api/ai-reviews.php', // historical count per actor/EAN, GET-only
        'ai.budget' => 'api/ai-review.php', // GET only, never billable POST
        // Strictly read-only legacy scan batches, scoped by existing Basic-Auth actor.
        // Mutations (create, scan, review, CSV/Import) remain in the original site.
        'scan.batches' => 'api/scanliste.php',
        'scan.items' => 'api/scanliste.php',
        'scan.export' => 'api/scanliste.php',
        'curated.article' => 'api/curated.php',
        'history.article' => 'api/historie.php',
    ];

    public function fetch(string $service, array $parameters = []): array
    {
        $started = hrtime(true);
        $finish = static fn(array $result): array => BridgeQueryAudit::finish($service, $parameters, $started, $result);
        $path = self::PATHS[$service] ?? null;
        if ($path === null) throw new RuntimeException('HW24 unknown or forbidden API endpoint');
        $base = rtrim((string) config('hw24_legacy.base_url'), '/');
        $user = (string) config('hw24_legacy.user');
        $password = (string) config('hw24_legacy.password');

        // Pin integration to the established legacy LIVE host. Prevent user-directed SSRF
        // or accidental Basic credentials disclosure to redirects/other domains.
        if (parse_url($base, PHP_URL_SCHEME) !== 'https' ||
            parse_url($base, PHP_URL_HOST) !== 'artikelmanager.hairworld24.de' ||
            !$user || !$password) {
            return $finish(['ok'=>false,'service'=>$service,'state'=>'unconfigured','http_status'=>null,'data'=>null]);
        }
        foreach ($parameters as $key=>$value) {
            if (!is_string($key) || !preg_match('/^[a-z_]{1,35}$/D',$key) || !is_scalar($value))
                throw new RuntimeException('Invalid HW24 read parameter');
        }
        $send=static function() use($user,$password,$base,$path,$parameters,$service): array {
        try {
            $response = Http::withBasicAuth($user, $password)->acceptJson()
                ->timeout(12)->withoutRedirecting()->get($base.'/'.$path,$parameters);
            $status=$response->status();
            $json=$response->json();
            $retryAfter=$response->header('Retry-After');
            $retrySeconds=null;
            if(is_string($retryAfter)&&preg_match('/^\d{1,6}$/D',$retryAfter)) {
                $retrySeconds=min(86400,(int)$retryAfter);
            } elseif(is_string($retryAfter)&&($stamp=strtotime($retryAfter))!==false) {
                $retrySeconds=min(86400,max(0,$stamp-time()));
            }
            return [
                'ok'=>$response->successful() && is_array($json) && (($json['ok']??false)===true),
                'service'=>$service,
                'state'=>$response->successful()?'responded':'upstream_error',
                'http_status'=>$status,
                'retry_after_seconds'=>$retrySeconds,
                'data'=>is_array($json)?$json:null,
            ];
        } catch (\Throwable $e) {
            // No secrets, URL query params or raw upstream payloads in logs.
            return ['ok'=>false,'service'=>$service,'state'=>'upstream_unreachable',
                'http_status'=>null,'data'=>null];
        }
        };
        return $finish(NativeAmazonGate::run($service,$send));
    }
}
