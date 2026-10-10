<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Owner's verdict per crawled item on the "Suggested new" tab.
        Schema::create('sf_suggest_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawled_product_id')->unique()->constrained('sf_crawled_products')->cascadeOnDelete();
            $table->string('decision', 10);                 // wanted | rejected
            $table->string('decided_by')->nullable();
            $table->timestamps();
        });

        // Style knowledge shared by every shop: block = never "looks like ours", want = Japanese-pattern word.
        Schema::create('sf_style_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 60);
            $table->string('kind', 10);                     // block | want
            $table->string('source', 10)->default('seed');  // seed | learned
            $table->unsignedInteger('from_item')->nullable();
            $table->timestamps();
            $table->unique(['word', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sf_style_words');
        Schema::dropIfExists('sf_suggest_decisions');
    }
};
