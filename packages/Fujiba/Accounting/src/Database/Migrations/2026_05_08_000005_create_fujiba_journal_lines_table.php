<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fujiba_journal_lines')) {
            return;
        }

        Schema::create('fujiba_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedInteger('line_no')->default(1);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            // tax classification cached on the line for reporting (FK to tax_rates is loose)
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('memo', 255)->nullable();
            // optional analytical splits
            $table->unsignedInteger('channel_id')->nullable();
            $table->string('cost_center', 32)->nullable();
            $table->string('partner_name', 128)->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('journal_entry_id');

            $table->foreign('journal_entry_id')
                ->references('id')->on('fujiba_journal_entries')
                ->onDelete('cascade');

            $table->foreign('account_id')
                ->references('id')->on('fujiba_accounts')
                ->onDelete('restrict');

            $table->foreign('tax_rate_id')
                ->references('id')->on('fujiba_tax_rates')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fujiba_journal_lines');
    }
};
