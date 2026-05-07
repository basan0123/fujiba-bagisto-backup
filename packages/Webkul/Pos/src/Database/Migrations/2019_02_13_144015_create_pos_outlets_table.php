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
        Schema::create('pos_outlets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->nullable();
            $table->string('address', 250);
            $table->string('country', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postcode', 10)->nullable();
            $table->boolean('status')->default(0);
            $table->integer('receipt_id')->unsigned()->nullable();

            $table->integer('inventory_source_id')->unsigned()->nullable();
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->onDelete('cascade');

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
        Schema::dropIfExists('pos_outlets');
    }
};
