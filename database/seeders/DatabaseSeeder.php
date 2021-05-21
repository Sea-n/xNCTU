<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $db = DB::connection('legacy');

        echo "Migrating users...\n";
        $users = $db->table('users')->get();
        foreach ($users as $item) {
            Schema::disableForeignKeyConstraints();
            DB::table('users')->insert([
                'stuid' => $item->stuid,
                'name' => $item->name,
                'email' => $item->mail,
                'tg_id' => $item->tg_id,
                'tg_name' => $item->tg_name
                'tg_username' => $item->tg_username,
                'tg_photo' => $item->tg_photo,

                'approvals' => $item->approvals,
                'rejects' => $item->rejects,
                'current_vote_streak' => $item->current_vote_streak,
                'highest_vote_streak' => $item->highest_vote_streak,
                'last_vote' => $item->last_vote,
                'last_login_nctu' => $item->last_login,
                'created_at' => $item->created_at,
                'updated_at' => $item->created_at,
            ]);
            Schema::enableForeignKeyConstraints();
        }

        echo "Migrating posts...\n";
        $posts = $db->table('posts')->get();
        foreach ($posts as $item) {
            if (strpos($item->author_name, '匿名, ') !== false)
                $ip_from = mb_substr($item->author_name, 4);
            else
                $ip_from = ip_from($item->ip_addr);

            DB::table('posts')->insert([
                'uid' => $item->uid,
                'id' => $item->id,
                'body' => $item->body,
                'orig' => null,
                'media' => $item->has_img ? 1 : 0,
                'author_id' => empty($item->author_id) ? null : $item->author_id,
                'ip_addr' => $item->ip_addr,
                'ip_from' => $ip_from,

                'status' => $item->status,
                'approvals' => $item->approvals,
                'rejects' => $item->rejects,
                'fb_likes' => $item->fb_likes,

                'telegram_id' => $item->telegram_id,
                'plurk_id' => $item->plurk_id,
                'twitter_id' => $item->twitter_id,
                'facebook_id' => $item->facebook_id,
                'instagram_id' => $item->instagram_id,

                'created_at' => $item->created_at,
                'updated_at' => $item->created_at,
                'submitted_at' => in_array($item->status, [-3, -12, -13]) ? null : $item->created_at,
                'posted_at' => $item->posted_at,
                'deleted_at' => $item->deleted_at,
                'delete_note' => $item->delete_note,
            ]);
        }

        echo "Migrating votes...\n";
        $votes = $db->table('votes')->get();
        foreach ($votes as $item) {
            DB::table('votes')->insert(get_object_vars($item));
        }

        echo "Migrating google_accounts...\n";
        $accounts = $db->table('google_accounts')->get();
        foreach ($accounts as $item) {
            DB::table('google_accounts')->insert([
                'sub' => $item->sub,
                'email' => $item->email,
                'name' => $item->name,
                'avatar' => $item->picture,
                'stuid' => empty($item->stuid) ? null : $item->stuid,
                'created_at' => $item->created_at,
                'updated_at' => $item->created_at,
                'last_login' => $item->created_at,
            ]);
        }
    }
}
