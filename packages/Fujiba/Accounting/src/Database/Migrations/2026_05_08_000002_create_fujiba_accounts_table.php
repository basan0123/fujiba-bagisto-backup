<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fujiba_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();        // 4-digit (or extended) account code
            $table->string('name_ja', 128);
            $table->string('name_en', 128)->nullable();
            $table->string('name_my', 128)->nullable();
            // 資産/負債/純資産/収益/費用
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            // Sub-type (e.g. current_asset, fixed_asset, sga, cogs, non_op, special)
            $table->string('sub_type', 32);
            // tax handling: standard / reduced / exempt / non_taxable / out_of_scope / withholding / none
            $table->string('tax_category', 24)->default('none');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_postable')->default(true); // false for header/group rows
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('sub_type');
            $table->index('parent_id');

            $table->foreign('parent_id')
                ->references('id')->on('fujiba_accounts')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_accounts');
    }
};
