<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->char('uid', 4);
            $table->string('stuid');
            $table->integer('vote');
            $table->string('reason');
            $table->timestamps();

            $table->foreign('uid')->references('uid')->on('posts');
            $table->foreign('stuid')->references('stuid')->on('users');
            $table->primary(['uid', 'stuid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('votes');
    }
}
