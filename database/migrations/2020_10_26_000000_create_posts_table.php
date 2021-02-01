<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->char('uid', 4)->primary();  // Case-insensitive
            $table->integer('id')->unique()->nullable();
            $table->string('body', 4200);
            $table->string('orig', 4200)->nullable();
            $table->integer('media')->default(0);
            $table->string('author_id')->nullable();
            $table->foreign('author_id')->references('stuid')->on('users');
            $table->ipAddress('ip_addr');
            $table->string('ip_from');

            $table->integer('status')->default(0);
            $table->integer('approvals')->default(0);
            $table->integer('rejects')->default(0);
            $table->integer('fb_likes')->default(0);
            $table->integer('old_likes')->default(0);
            $table->integer('max_likes')->default(0);

            $table->integer('telegram_id')->default(0);
            $table->integer('plurk_id')->default(0);
            $table->bigInteger('twitter_id')->default(0);
            $table->bigInteger('facebook_id')->default(0);
            $table->string('instagram_id')->default('');

            $table->timestamps();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->softDeletes();
            $table->string('delete_note')->nullable();
        });
    }

    /*
     * status codes:
     *
     * 0 created
     * 1 submitted
     * 2 sening review message
     * 3 sent all review
     * 4 selected to post
     * 5 posted to all SNS
     *
     * 10 on hold / demo post
     *
     * -1 reserved
     * -2 rejected
     * -3 deleted by author (hidden)
     * -4 deleted by admin
     * -11 deleted and by admin (hidden)
     * -12 rate limited
     * -13 unconfirmed timeout
     */

    /*
     * media codes:
     *
     * 0 plaintext
     * 1 JPEG image
     * 2 GIF
     * 3 MP4 video
     */

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
