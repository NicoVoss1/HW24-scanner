<?php
declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\ObservedSourcesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SourceCatalogSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_isolated_source_catalog_tables_exist_without_jtl_modification(): void
    {
        $this->assertTrue(Schema::hasTable('source_field_catalog'));
        $this->assertTrue(Schema::hasTable('source_reference_observations'));
        $this->assertTrue(Schema::hasTable('channel_account_registry'));
        $this->assertSame(0,DB::table('source_field_catalog')->count());
        $this->assertSame(0,DB::table('channel_account_registry')->count());
    }

    public function test_opt_in_seed_stores_only_schema_labels_and_unverified_accounts(): void
    {
        (new ObservedSourcesSeeder)->run();
        $this->assertGreaterThanOrEqual(35,DB::table('source_field_catalog')->count());
        $this->assertSame(4,DB::table('channel_account_registry')->count());
        $this->assertSame(2,DB::table('channel_account_registry')
            ->where('channel','ebay')->count());
        $this->assertSame(1,DB::table('source_field_catalog')
            ->where('source_entity','BI.tKostenTyp')
            ->where('field_name','cKostentyp')
            ->where('verification_state','metadata_only')
            ->count());
        $this->assertSame(0,DB::table('source_reference_observations')->count());
        $this->assertSame(0,DB::table('channel_account_registry')
            ->whereNotNull('external_account_id')->count());
        (new ObservedSourcesSeeder)->run();
        $this->assertSame(4,DB::table('channel_account_registry')->count(),
            'Opt-in seeding must be idempotent');
    }
}
