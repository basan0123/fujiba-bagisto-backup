<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_channel_tokens')) {
            Schema::create('customer_channel_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('customer_id');
                $table->string('provider', 24);
                $table->string('page_id', 64)->nullable();
                $table->string('token', 191);
                $table->json('meta')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('customer_channel_tokens', 'customer_channel_tokens_lookup_uniq')) {
            Schema::table('customer_channel_tokens', function (Blueprint $table) {
                $table->unique(
                    ['customer_id', 'provider', 'page_id', 'token'],
                    'customer_channel_tokens_lookup_uniq'
                );
            });
        }

        if (! Schema::hasIndex('customer_channel_tokens', 'customer_channel_tokens_provider_idx')) {
            Schema::table('customer_channel_tokens', function (Blueprint $table) {
                $table->index(['provider', 'page_id', 'is_active'], 'customer_channel_tokens_provider_idx');
            });
        }

        if (! Schema::hasIndex('customer_channel_tokens', 'customer_channel_tokens_customer_idx')) {
            Schema::table('customer_channel_tokens', function (Blueprint $table) {
                $table->index('customer_id', 'customer_channel_tokens_customer_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_channel_tokens');
    }
};
