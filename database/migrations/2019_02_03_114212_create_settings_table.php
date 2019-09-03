<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->double('total_th',12,8)->nullable();
            $table->float('collab_benefit')->nullable();
            $table->double('total_benefit',12,8)->nullable();
            $table->float('usd_per_hash')->nullable();
            $table->float('usd_toman')->nullable();
            $table->integer('hardware_fee')->nullable();
            $table->integer('remote_fee')->nullable();
            $table->double('maintenance_fee_per_th_per_day',8,3)->nullable();
            $table->double('bitcoin_income_per_month_per_th',8,8)->nullable();
            $table->float('available_th')->nullable();
            $table->float('sharing_discount')->nullable();
            $table->float('hash_life')->nullable();
            $table->float('minimum_redeem')->nullable();
            $table->string('apikey')->nullable();
            $table->string('privatekey')->nullable();
            $table->string('publickey')->nullable();
            $table->boolean('alarms')->default(1);
            $table->boolean('power_off')->default(1);
            $table->string('paystar_pin')->nullable();
            $table->string('zarrin_pin')->nullable();
            $table->boolean('zarrin_active')->default(0);
            $table->boolean('paystar_active')->default(0);
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'total_th'=> 100,
            'available_th'=> 100,
            'usd_per_hash'=>70,
            'maintenance_fee_per_th_per_day'=> 0.09,
            'bitcoin_income_per_month_per_th'=> 0.00099100,
            'hash_life'=> 2,
            'minimum_redeem'=>0.01,
            'sharing_discount'=>0.03,
            'apikey'=>'60312e9f-8cb0-4f39-9cca-a271c3f3a4d1',
            'publickey'=>'37905AAMU6s6Bitcoin77BTCPUBvcBJk5qAm8oayz3MwFYeh6L',
            'privatekey'=>'37905AAMU6s6Bitcoin77BTCPRVaGHpTpmfrIrnSZBdhLIISVz',
            'paystar_pin'=>'7DC84A8791E676C3DD7C',
            'zarrin_pin'=>'ed8eea3e-068c-11e9-9efd-005056a205be',
            'created_at'=>\Carbon\Carbon::now(),
            'updated_at'=>\Carbon\Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
