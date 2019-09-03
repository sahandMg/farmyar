<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemotePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remote_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('devices');
            $table->integer('months');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('remote_users')->onDelete('cascade');
            $table->unsignedInteger('trans_id');
            $table->foreign('trans_id')->references('id')->on('remote_transactions')->onDelete('cascade');
            $table->boolean('expired')->default(false);
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
        Schema::dropIfExists('remote_plans');
    }
}
