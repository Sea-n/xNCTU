<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('stuid')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->integer('tg_id')->unique()->nullable();
            $table->string('tg_name')->nullable();
            $table->string('tg_username')->nullable();
            $table->string('tg_photo')->nullable();

            $table->integer('approvals')->default(0);
            $table->integer('rejects')->default(0);
            $table->integer('current_vote_streak')->default(0);
            $table->integer('highest_vote_streak')->default(0);
            $table->dateTime('last_vote')->default(DB::raw('"2020-01-01 00:00:00"'));

            $table->dateTime('last_login_tg')->nullable();
            $table->dateTime('last_login_nctu')->nullable();
            $table->dateTime('last_login_google')->nullable();
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
        Schema::dropIfExists('users');
    }
}
