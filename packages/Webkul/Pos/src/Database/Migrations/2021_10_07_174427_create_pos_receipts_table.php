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
        Schema::create('pos_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->boolean('status')->nullable();
            $table->boolean('display_outlet_name')->nullable();
            $table->boolean('display_date')->nullable();
            $table->boolean('display_order_id')->nullable();
            $table->string('order_id_label')->nullable();
            $table->boolean('display_customer_name')->nullable();
            $table->boolean('display_sub_total')->nullable();
            $table->string('sub_total_label')->nullable();
            $table->boolean('display_discount')->nullable();
            $table->string('discount_label')->nullable();
            $table->boolean('display_tax')->nullable();
            $table->string('tax_label')->nullable();
            $table->boolean('display_credit_amount')->nullable();
            $table->string('credit_amount_label')->nullable();
            $table->boolean('display_change_amount')->nullable();
            $table->string('credit_change_label')->nullable();
            $table->boolean('display_cashier_name')->nullable();
            $table->string('cashier_label')->nullable();
            $table->boolean('display_outlet_address')->nullable();
            $table->string('grand_total_label')->nullable();
            $table->boolean('display_logo')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_width')->nullable();
            $table->string('logo_height')->nullable();
            $table->string('logo_alt')->nullable();
            $table->longText('header_content')->nullable();
            $table->longText('footer_content')->nullable();
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
        Schema::dropIfExists('pos_receipts');
    }
};
