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
        Schema::create('pos_user_drawer', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('pos_users')->onDelete('cascade');

            $table->integer('outlet_id')->unsigned()->nullable();
            $table->foreign('outlet_id')->references('id')->on('pos_outlets')->onDelete('cascade');

            $table->string('base_currency', 50)->nullable();
            $table->string('opening_currency', 50)->nullable();
            $table->decimal('opening_amount', 12, 4);
            $table->string('remark', 250)->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('pos_user_drawer');
    }
};
