<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('value')->nullable();
            $table->timestamps();
        });
        DB::table('periods')->insert(
          [ array('name'=>'1m','created_at'=>Carbon::now()),
          array('name'=>'3m','created_at'=>Carbon::now()),
          array('name'=>'6m','created_at'=>Carbon::now()),
          array('name'=>'1y','created_at'=>Carbon::now()),
          array('name'=>'2y','created_at'=>Carbon::now())
          ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('periods');
    }
}
