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
        Schema::create('pos_customer_credit', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('order_id')->unsigned()->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->integer('customer_id')->unsigned()->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->decimal('change_amount', 12, 4);
            $table->decimal('base_change_amount', 12, 4);
            $table->decimal('tendered_amount', 12, 4);
            $table->decimal('base_tendered_amount', 12, 4);
            $table->string('payment_mode', 50)->nullable();
            $table->boolean('used_status')->default(0);
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
        Schema::dropIfExists('pos_customer_credit');
    }
};
