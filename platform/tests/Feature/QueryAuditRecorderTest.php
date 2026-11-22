<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Services\QueryAuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class QueryAuditRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_audit_is_disabled_by_default(): void
    {
        config()->set('hw24_query_audit.enabled', false);
        $saved = app(QueryAuditRecorder::class)->record(
            'amazon', 'getItemOffers', 'manual', 'asin', 'B091J3HV6B', 200, 52, 'success'
        );
        $this->assertFalse($saved);
        $this->assertDatabaseCount('query_audit_events', 0);
    }

    public function test_enabled_audit_stores_only_verified_metadata(): void
    {
        config()->set('hw24_query_audit.enabled', true);
        $saved = app(QueryAuditRecorder::class)->record(
            'jtl', 'lookup', 'manual', 'ean', '3474630337398', 200, 19, 'success'
        );
        $this->assertTrue($saved);
        $this->assertDatabaseHas('query_audit_events', [
            'source_system' => 'jtl', 'identifier_type' => 'ean',
            'identifier' => '3474630337398', 'http_status' => 200,
            'duration_ms' => 19, 'result' => 'success'
        ]);
    }

    public function test_untrusted_query_and_invalid_gtin_are_not_stored(): void
    {
        config()->set('hw24_query_audit.enabled', true);
        $recorder = app(QueryAuditRecorder::class);
        $recorder->record('amazon', 'getItemOffers', 'manual', 'url',
            'https://example.org/?access_token=secret', 200, 20, 'success');
        $recorder->record('jtl', 'lookup', 'manual', 'ean', '3474630337399',
            404, 20, 'failure');
        $this->assertDatabaseCount('query_audit_events', 2);
        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('query_audit_events')
            ->whereNotNull('identifier')->count());
    }

    public function test_invalid_metadata_fails_closed_without_writing(): void
    {
        config()->set('hw24_query_audit.enabled', true);
        $this->expectException(InvalidArgumentException::class);
        app(QueryAuditRecorder::class)->record(
            'amazon', 'getItemOffers', 'manual', 'asin', 'B091J3HV6B',
            999, 20, 'success'
        );
    }
}
