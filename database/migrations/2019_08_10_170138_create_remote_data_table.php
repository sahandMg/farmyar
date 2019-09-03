<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemoteDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
   {
       Schema::create('remote_data', function (Blueprint $table) {
           $table->bigIncrements('id');
           $table->text('data')->nullable();
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
        Schema::dropIfExists('remote_data');
    }
}
