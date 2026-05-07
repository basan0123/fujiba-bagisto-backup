<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_stocktake_sessions')) {
            Schema::create('pos_stocktake_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('outlet_id')->index();
                $table->unsignedBigInteger('created_by')->comment('pos_users.id');
                $table->enum('status', ['draft', 'in_progress', 'completed'])->default('in_progress');
                $table->date('start_date');
                $table->date('completed_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pos_stocktake_items')) {
            Schema::create('pos_stocktake_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('session_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('sku')->nullable();
                $table->integer('system_qty')->default(0)->comment('Snapshot at session start');
                $table->integer('counted_qty')->nullable()->comment('Entered by staff');
                $table->integer('difference')->nullable()->comment('counted_qty - system_qty, auto-calculated');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('session_id')->references('id')->on('pos_stocktake_sessions')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('pos_inventory_adjustments')) {
            Schema::create('pos_inventory_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('session_id')->nullable()->comment('pos_stocktake_sessions.id');
                $table->integer('before_qty');
                $table->integer('after_qty');
                $table->string('reason')->default('stocktake');
                $table->unsignedBigInteger('adjusted_by')->nullable()->comment('pos_users.id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pos_receipt_settings')) {
            Schema::create('pos_receipt_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('outlet_id')->unique();
                $table->string('logo_path')->nullable();
                $table->string('store_name')->nullable();
                $table->string('address_line1')->nullable();
                $table->string('address_line2')->nullable();
                $table->string('phone')->nullable();
                $table->string('url')->nullable();
                $table->text('footer_message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_inventory_adjustments');
        Schema::dropIfExists('pos_stocktake_items');
        Schema::dropIfExists('pos_stocktake_sessions');
        Schema::dropIfExists('pos_receipt_settings');
    }
};
