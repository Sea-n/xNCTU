<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTgMsgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tg_msgs', function (Blueprint $table) {
            $table->char('uid', 4);
            $table->integer('chat_id');
            $table->integer('msg_id');
            $table->timestamps();

            $table->foreign('uid')->references('uid')->on('posts');
            $table->foreign('chat_id')->references('tg_id')->on('users');
            $table->primary(['uid', 'chat_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tg_msgs');
    }
}
