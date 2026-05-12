<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fujiba_tax_rates')) {
            return;
        }

        Schema::create('fujiba_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();       // e.g. consumption_standard, withholding_a
            $table->string('name_ja', 128);
            $table->string('name_en', 128)->nullable();
            $table->string('name_my', 128)->nullable();
            // consumption / withholding / employment_insurance / health_insurance / pension / other
            $table->string('category', 32);
            $table->decimal('rate', 9, 4)->nullable();   // single-rate variant (% e.g. 10.0000)
            $table->json('brackets')->nullable();        // bracket-based (e.g. 源泉甲欄)
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_tax_rates');
    }
};
