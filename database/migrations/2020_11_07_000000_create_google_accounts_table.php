<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoogleAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->string('sub')->primary();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('avatar')->default('');
            $table->string('stuid')->nullable();
            $table->foreign('stuid')->references('stuid')->on('users');
            $table->timestamps();
            $table->dateTime('last_login')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('last_verify')->default(DB::raw('2020-01-01 00:00:00'));
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('google_accounts');
    }
}
