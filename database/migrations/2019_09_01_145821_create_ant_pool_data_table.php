<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAntPoolDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ant_pool_data', function (Blueprint $table) {
            $table->increments('id');
            $table->double('value_last_day',10,8);
            $table->double('value',10,8);
            $table->double('paid',10,8);
            $table->double('balance',10,8);
            $table->string('settleTime');
            $table->string('hashes_last_day');
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
        Schema::dropIfExists('ant_pool_data');
    }
}
