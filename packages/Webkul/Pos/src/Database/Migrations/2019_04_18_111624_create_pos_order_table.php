<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pos_order', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('order_id')->unsigned()->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->string('order_ref_id', 100)->default('N/A');

            $table->integer('outlet_id')->unsigned()->nullable();
            $table->foreign('outlet_id')->references('id')->on('pos_outlets')->onDelete('set null');

            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('pos_users')->onDelete('set null');

            $table->string('order_note', 250)->nullable();
            $table->decimal('discount_amount', 12, 4);
            $table->decimal('base_discount_amount', 12, 4);
            $table->string('order_currency', 50)->nullable();
            $table->string('order_barcode_path', 100)->nullable();

            $table->string('bank_name', 50)->nullable();
            $table->integer('card_detail')->unsigned()->nullable();

            $table->decimal('cash_total', 12, 4)->nullable();
            $table->decimal('card_total', 12, 4)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pos_order');
    }
};
