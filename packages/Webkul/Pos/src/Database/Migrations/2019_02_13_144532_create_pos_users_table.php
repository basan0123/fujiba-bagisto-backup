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
        Schema::create('pos_users', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('outlet_id')->unsigned()->nullable();
            $table->foreign('outlet_id')->references('id')->on('pos_outlets')->onDelete('cascade');

            $table->string('username')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->unique();
            $table->string('image');
            $table->string('report_type', 100)->nullable()->default('monthly');
            $table->boolean('status')->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('pos_users');
    }
};
