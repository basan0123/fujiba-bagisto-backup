<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_waste_entries')) {
            return;
        }

        Schema::create('pos_waste_entries', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('outlet_id')->nullable();
            $table->foreign('outlet_id')->references('id')->on('pos_outlets')->onDelete('set null');

            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('pos_users')->onDelete('set null');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name', 191);
            $table->string('product_sku', 100)->nullable();

            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);

            // damaged, expired, theft, spoilage, other
            $table->string('reason', 50)->default('other');
            $table->text('notes')->nullable();

            // verified_by stores admin user ID
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            // pending, verified, rejected
            $table->string('status', 20)->default('pending');

            $table->date('wasted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_waste_entries');
    }
};
