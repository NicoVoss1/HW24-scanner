<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Isolated PLATFORM database only. Do not run on hw24_artikelmanager, JTL or WMS.
 * Market offers are source observations, NOT canonical JTL selling prices.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('channel_price_observations',function(Blueprint $table){
            $table->id();
            $table->string('product_key',100);
            $table->string('ean',14)->nullable();
            $table->string('jtl_article_number',100)->nullable();
            $table->string('channel',20);
            $table->string('price_type',32);
            $table->char('currency',3);
            $table->decimal('gross',14,4)->nullable();
            $table->decimal('net',14,4)->nullable();
            $table->string('status',20); // verified, derived, unverified, unavailable
            $table->string('source',255)->nullable();
            $table->string('source_reference',255)->nullable();
            $table->string('marketplace',32)->nullable();
            $table->string('account',100)->nullable();
            $table->string('tax_country',2)->nullable();
            $table->string('customer_group',100)->nullable();
            $table->string('offer_id',120)->nullable();
            $table->string('publication_status',50)->nullable();
            $table->dateTime('observed_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->index(['product_key','channel','observed_at'],'idx_price_product_channel_time');
            $table->index(['ean','channel','observed_at'],'idx_price_ean_channel_time');
            $table->index(['channel','expires_at'],'idx_price_expiry');
        });
    }
    public function down(): void { Schema::dropIfExists('channel_price_observations'); }
};
