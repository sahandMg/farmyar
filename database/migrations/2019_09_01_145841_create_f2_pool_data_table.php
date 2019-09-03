<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateF2PoolDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('f2_pool_data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('value_last_day');
            $table->string('hashes_last_day');
            $table->string('balance');
            $table->string('hash_rate');
            $table->string('paid');
            $table->string('value');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('remote_users');
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
        Schema::dropIfExists('f2_pool_data');
    }
}
