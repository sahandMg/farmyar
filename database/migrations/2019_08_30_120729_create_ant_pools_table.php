<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAntPoolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ant_pools', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nonce');
            $table->text('user_id');
            $table->text('api_key');
            $table->text('secret');
            $table->unsignedInteger('remote_id');
            $table->foreign('remote_id')->references('id')->on('remote_users')->onDelete('cascade');
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
        Schema::dropIfExists('ant_pools');
    }
}
