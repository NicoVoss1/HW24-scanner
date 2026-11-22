<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Isolated HW24 PLATFORM database only, never eazybusiness, WMS or legacy MariaDB.
 * Metadata audit, not a raw API response archive; no PII or credentials.
 * No automatic deployment or ingestion is enabled by this migration.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('query_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->string('source_system', 32);
            $table->string('operation', 64);
            $table->string('trigger_type', 16); // manual / scheduled / system
            $table->string('actor_ref', 128)->nullable(); // Auth ID, never Basic-Auth credentials
            $table->string('identifier_type', 12)->nullable(); // ean / asin only
            $table->string('identifier', 20)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('result', 24); // success / failure / throttled
            $table->timestamp('requested_at', 6);
            $table->timestamps();
            $table->index(['source_system', 'requested_at'], 'idx_hw24_audit_source_time');
            $table->index(['identifier_type', 'identifier', 'requested_at'], 'idx_hw24_audit_entity_time');
            $table->index(['actor_ref', 'requested_at'], 'idx_hw24_audit_actor_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_audit_events');
    }
};
