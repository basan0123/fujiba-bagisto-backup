<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersPostageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('orders_postage')) {
            Schema::create('orders_postage', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->string('delivery_date')->nullable()->comment('配送日付 (YYYY-MM-DD)');
                $table->string('delivery_time')->nullable()->comment('配送時間帯 (午前中, 12:00-14:00 など)');
                $table->string('prefecture')->nullable()->comment('都道府県');
                $table->integer('delivery_days')->nullable()->comment('配送予定日数 (1-4)');
                $table->text('notes')->nullable()->comment('特記事項');
                $table->timestamps();

                // Foreign key
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->onDelete('cascade');

                // Indexes
                $table->index('delivery_date');
                $table->index('prefecture');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders_postage');
    }
}
