<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AmazonPriceArchiveRuntime;
use App\Services\LegacyReadBridge;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AmazonPriceArchiveRuntimeTest extends TestCase
{
    public function test_disabled_or_unready_archive_does_not_block_live_prices(): void
    {
        config()->set('hw24_legacy.base_url','https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user','test-only');
        config()->set('hw24_legacy.password','fake-test-only');
        Http::preventStrayRequests();
        Http::fake(['https://artikelmanager.hairworld24.de/api/amazon-pricing.php*'=>Http::response([
            'ok'=>true,'source'=>'amazon_live','asin'=>'B000000001',
            'marketplace_id'=>'A1PA6795UKMFR9','offers'=>[],
            'retrieved_at_utc'=>gmdate('c')
        ])]);
        config()->set('hw24_price_archive.enabled',false);
        $off=AmazonPriceArchiveRuntime::fetch('B000000001',new LegacyReadBridge());
        self::assertSame('disabled',$off['archive']['state']);
        self::assertTrue($off['response']['ok']);
        // In the isolated CI checkout no private credential/heartbeat exists.
        config()->set('hw24_price_archive.enabled',true);
        $unready=AmazonPriceArchiveRuntime::fetch('B000000001',new LegacyReadBridge());
        self::assertSame('storage_not_ready',$unready['archive']['state']);
        self::assertTrue($unready['response']['ok']);
        Http::assertSentCount(2);
    }

    public function test_only_summary_is_sent_to_page(): void
    {
        $result=AmazonPriceArchiveRuntime::summary(['state'=>'recorded','history'=>[
            ['id'=>2,'observed_at_utc'=>'2026-11-25 12:00:00','total_offer_count'=>25,
             'returned_offer_count'=>20,'buybox'=>[],'lowest'=>[],'offers'=>[['seller_id'=>'TESTSELLER']]],
        ]]);
        self::assertSame(2,$result['history'][0]['id']);
        self::assertArrayNotHasKey('offers',$result['history'][0]);
    }
}
