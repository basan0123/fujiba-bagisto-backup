<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS-I — how well each enabled, in-stock product suits each content format.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fi_product_format_fit')) {
            return;
        }

        Schema::create('fi_product_format_fit', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('product_id')->index();
            $t->string('format_code', 64)->index();
            $t->decimal('score', 4, 3);
            $t->text('reason')->nullable();
            $t->timestamps();

            $t->unique(['product_id', 'format_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fi_product_format_fit');
    }
};
