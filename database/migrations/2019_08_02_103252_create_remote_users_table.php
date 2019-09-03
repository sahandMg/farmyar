<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemoteUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
//    public function up()
//    {
//        Schema::create('remote_users', function (Blueprint $table) {
//            $table->increments('id');
//            $table->string('name')->nullable();
//            $table->string('email')->unique();
//            $table->string('password')->nullable();
//            $table->string('code');
//            $table->index('code');
//            $table->string('ip')->nullable();
//            $table->string('country')->nullable();
//            $table->string('avatar')->nullable();
//            $table->boolean('block')->default(0);;
//            $table->boolean('verified')->default(false);
//            $table->rememberToken();
//            $table->timestamp('email_verified_at')->nullable();
//            $table->timestamps();
//        });
//    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('remote_users');
    }
}
