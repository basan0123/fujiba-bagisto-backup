<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_fixed_expenses')) {
            $this->createFixedExpensesTable();
        }

        if (! Schema::hasTable('pos_fixed_expense_applications')) {
            $this->createFixedExpenseApplicationsTable();
        }
    }

    private function createFixedExpensesTable(): void
    {
        Schema::create('pos_fixed_expenses', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('outlet_id')->nullable();
            $table->foreign('outlet_id')->references('id')->on('pos_outlets')->onDelete('cascade');

            $table->string('name', 191);

            // rent, electricity, gas, water, salary, insurance, lease, other
            $table->string('category', 50)->default('other');

            $table->decimal('amount', 12, 4)->default(0);
            $table->string('currency', 10)->nullable();

            // Day of month to auto-apply (1-31)
            $table->unsignedTinyInteger('billing_day')->default(1);

            $table->boolean('is_active')->default(1);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    private function createFixedExpenseApplicationsTable(): void
    {
        Schema::create('pos_fixed_expense_applications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('fixed_expense_id');
            $table->foreign('fixed_expense_id')->references('id')->on('pos_fixed_expenses')->onDelete('cascade');

            $table->unsignedInteger('outlet_id')->nullable();

            // YYYY-MM format
            $table->string('month', 7);

            $table->decimal('applied_amount', 12, 4)->default(0);

            // auto, manual, skipped
            $table->string('status', 20)->default('auto');

            $table->text('note')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['fixed_expense_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_fixed_expense_applications');
        Schema::dropIfExists('pos_fixed_expenses');
    }
};
