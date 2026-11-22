<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ONLY the isolated Laravel platform DB (SQLite pilot, later separate MariaDB).
 * This is a registry for SOURCE METADATA and selectively approved reference
 * source rows. It does not modify hw24_artikelmanager or JTL-Wawi.
 *
 * Do not automatically mirror every SQL Server table: source-specific privacy
 * and account-identity checks precede each reference-source import.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('source_field_catalog',function(Blueprint $table){
            $table->id();
            $table->string('source_system',40);  // jtl, shopify, amazon, ebay
            $table->string('source_entity',150); // dbo.tArtikel, BI.tKostenTyp
            $table->string('field_name',150);
            $table->string('sql_type',50)->nullable();
            $table->string('display_label',200)->nullable();
            $table->string('business_group',70)->nullable();
            $table->string('verification_state',36)->default('metadata_only');
            $table->boolean('present_in_mirror')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['source_system','source_entity','field_name'],'uq_hw24_source_field');
            $table->index(['source_system','business_group'],'idx_hw24_field_group');
        });
        Schema::create('source_reference_observations',function(Blueprint $table){
            $table->id();
            $table->string('source_system',40);
            $table->string('source_entity',150);
            $table->string('record_key',190); // only from an identified source primary key
            $table->string('verification_state',36)->default('unverified');
            $table->json('source_values'); // approved whitelisted business data only
            $table->timestamp('observed_at')->nullable();
            $table->timestamps();
            $table->index(['source_system','source_entity','record_key'],'idx_hw24_reference_key');
        });
        Schema::create('channel_account_registry',function(Blueprint $table){
            $table->id();
            $table->string('channel',30); // ebay, amazon, shopify
            $table->string('account_label',180);
            $table->string('external_account_id',190)->nullable();
            $table->string('source_system',50)->nullable();
            $table->string('verification_state',36)->default('label_only');
            $table->timestamp('verified_at')->nullable();
            $table->json('account_context')->nullable();
            $table->timestamps();
            $table->index(['channel','external_account_id'],'idx_hw24_external_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_account_registry');
        Schema::dropIfExists('source_reference_observations');
        Schema::dropIfExists('source_field_catalog');
    }
};