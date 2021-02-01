<?php

namespace App\Console\Commands;

use App\Jobs\PublishFacebook;
use App\Jobs\PublishInstagram;
use App\Jobs\PublishPlurk;
use App\Jobs\PublishTelegram;
use App\Jobs\PublishTwitter;
use App\Jobs\ReviewDelete;
use App\Jobs\UpdateFacebook;
use App\Jobs\UpdatePlurk;
use App\Jobs\UpdateTelegram;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:send {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send eligible post to social media';

    private $time;
    private $dt;
    private $link;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->argument('id')) {
            $post = Post::where('id', '=', $this->argument('id'))->firstOrFail();

            UpdateTelegram::dispatch($post);
            return 0;
        }


        /* Check unfinished post */
        $post = Post::where('status', '=', 4)->first();

        /* Get all pending submissions, oldest first */
        if (!isset($post)) {
            $submissions = Post::where('status', '=', 3)->orderBy('submitted_at')->get();

            foreach ($submissions as $item) {
                if ($this->checkEligible($item)) {
                    $post = $item;
                    $id = Post::orderBy('id', 'desc')->first()->id ?? 0;
                    $post->update([
                        'id' => $id + 1,
                        'status' => 4,
                        'posted_at' => Carbon::now(),
                    ]);
                    break;
                }
            }
        }


        if (!isset($post))
            return 0;

        /* Prepare post content */
        $created = strtotime($post->created_at);
        $this->time = date("Y 年 m 月 d 日 H:i", $created);
        $this->dt = floor(time() / 60) - floor($created / 60);  // Use what user see (without seconds)

        $this->link = env('APP_URL') . "/post/{$post->id}";

        /* Send post to each platforms */
        if (env('TELEGRAM_ENABLE', false) && $post->telegram_id == 0)
            PublishTelegram::dispatch($post);

        if (env('TWITTER_ENABLE', false) && $post->twitter_id == 0)
            PublishTwitter::dispatch($post);

        if (env('INSTAGRAM_ENABLE', false) && $post->instagram_id == '')
            PublishInstagram::dispatch($post);

        if (env('PLURK_ENABLE', false) && $post->plurk_id == 0)
            PublishPlurk::dispatch($post);

        if (env('FACEBOOK_ENABLE', false) && $post->facebook_id == 0)
            PublishFacebook::dispatch($post);


        /* Comment on some platforms */
        if (env('FACEBOOK_ENABLE', false) && $post->facebook_id > 0)
            UpdateFacebook::dispatch($post);

        if (env('PLURK_ENABLE', false) && $post->plurk_id > 0)
            UpdatePlurk::dispatch($post);

        if (env('TELEGRAM_ENABLE', false) && $post->telegram_id > 0)
            UpdateTelegram::dispatch($post);


        /* Remove un-voted messages in Telegram */
        ReviewDelete::dispatch($post);

        return 0;
    }

    /**
     *
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return base_path('stubs/posts.stub');
    }


    private function checkEligible(Post $post): bool
    {
        /* Prevent publish demo post */
        if ($post->id || $post->status != 3)
            return false;

        $dt = floor(time() / 60) - floor(strtotime($post['created_at']) / 60);
        $vote = $post['approvals'] - $post['rejects'];
        $vote2 = $post['approvals'] - $post['rejects'] * 2;

        /* Rule for Logged-in users */
        if (!empty($post['author_id'])) {
            /* No reject: 3 votes */
            if ($dt < 10)
                return ($vote2 >= 3);

            /* More than 10 min */
            return ($vote >= 0);
        }

        /* Rule for NCTU IP address */
        if ($post['author_name'] == '匿名, 交大'
            && $post['ip_addr'] != ip_mask($post['ip_addr'])) {
            /* Night mode */
            if (strtotime("03:00") <= time() && time() <= strtotime("09:00"))
                if ($vote < 3)
                    return false;

            if (strtotime("02:30") <= time() && time() <= strtotime("09:30"))
                if ($vote < 2)
                    return false;

            if (strtotime("02:00") <= time() && time() <= strtotime("10:00"))
                if ($vote < 1)
                    return false;

            /* No reject: 5 votes */
            if ($dt < 10)
                return ($vote2 >= 5);

            /* 10 min - 1 hour */
            if ($dt < 60)
                return ($vote >= 3);

            /* More than 1 hour */
            return ($vote >= 0);
        }

        /* Rule for Taiwan IP address */
        if (strpos($post['author_name'], '境外') === false) {
            /* No reject: 7 votes */
            if ($dt < 10)
                return ($vote2 >= 7);

            /* 10 min - 1 hour */
            if ($dt < 60)
                return ($vote >= 5);

            /* More than 1 hour */
            return ($vote >= 3);
        }

        /* Rule for Foreign IP address */
        if (true) {
            return ($vote >= 10);
        }
    }


    private function updatePostSns(Post $post, string $type, int $pid): void
    {
        if (!in_array($type, ['telegram', 'plurk', 'twitter', 'facebook']))
            return;

        /* Caution: use string combine in SQL query */
        $post->update(["{$type}_id" => $pid]);

        if ($post->telegram_id > 0
            && $post->plurk_id > 0
            && $post->facebook_id > 0
            && $post->twitter_id > 0)
            $post->update(['status' => 5]);
    }


    private function send_facebook(Post $post): int
    {
        $msg = "#靠交{$post['id']}\n\n";
        $msg .= "{$post['body']}";

        $URL = 'https://graph.facebook.com/v6.0/' . env('FB_PAGES_ID') . ($post['has_img'] ? '/photos' : '/feed');

        $data = ['access_token' => env('FB_ACCESS_TOKEN')];
        if (!$post['has_img']) {
            $data['message'] = $msg;

            $lines = explode("\n", $post['body']);
            $end = end($lines);
            if (filter_var($end, FILTER_VALIDATE_URL) && strpos($end, 'facebook') === false)
                $data['link'] = $end;
        } else {
            $data['url'] = env('APP_URL') . "/img/{$post['uid']}.jpg";
            $data['caption'] = $msg;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data
        ]);

        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result, true);

        $fb_id = $result['post_id'] ?? $result['id'] ?? '0_0';
        $post_id = (int)explode('_', $fb_id)[1];

        if ($post_id == 0) {
            echo "Facebook result error:";
            var_dump($result);
        }

        return $post_id;
    }


    private function update_facebook(Post $post)
    {
        $tips_all = [
            "投稿時將網址放在最後一行，發文會自動顯示頁面預覽",
            "電腦版投稿可以使用 Ctrl-V 上傳圖片",
            "使用交大網路投稿會自動填入驗證碼",
            "如想投稿 GIF 可上傳至 Giphy，並將連結置於文章末行",

            "透過自動化審文系統，多數投稿會在 10 分鐘內發出",
            "所有人皆可匿名投稿，全校師生皆可具名審核",
            "靠北交大 2.0 採自助式審文，全校師生皆能登入審核",
            "靠北交大 2.0 有 50% 以上投稿來自交大 IP 位址",
            "登入後可看到 140.113.**.*87 格式的部分 IP 位址",

            "靠北交大 2.0 除了 Facebook 外，還支援 Twitter、Plurk 等平台\nhttps://twitter.com/x_NCTU/",
            "靠北交大 2.0 除了 Facebook 外，還支援 Plurk、Twitter 等平台\nhttps://www.plurk.com/xNCTU",
            "加入靠北交大 2.0 Telegram 頻道，第一時間看到所有貼文\nhttps://t.me/xNCTU",
            "你知道靠交也有 Instagram 帳號嗎？只要投稿圖片就會同步發佈至 IG 喔\nhttps://www.instagram.com/x_nctu/",
            "告白交大 2.0 使用同套系統，在此為大家服務\nhttps://www.facebook.com/CrushNCTU/",

            "審核紀錄公開透明，你可以看到誰以什麼原因通過/駁回了投稿\nhttps://x.nctu.app/posts",
            "覺得審核太慢嗎？你也可以來投票\nhttps://x.nctu.app/review",
            "網站上「已刪投稿」區域可以看到被黑箱的記錄\nhttps://x.nctu.app/deleted",
            "知道都是哪些系的同學在審文嗎？打開排行榜看看吧\nhttps://x.nctu.app/ranking",
            "秉持公開透明原則，您可以在透明度報告看到師長同學請求刪文的紀錄\nhttps://x.nctu.app/transparency",
            "靠交 2.0 是交大資工學生自行開發的系統，程式原始碼公開於 GitHub 平台\nhttps://github.com/Sea-n/xNCTU",
        ];
        assert(count($tips_all) % 7 != 0);  // current count = 20
        $tips = $tips_all[($post['id'] * 7) % count($tips_all)];

        $go_all = [
            "立即投稿",
            "匿名投稿",
            "投稿連結",
            "投稿點我",
            "我要投稿",
        ];
        $go = $go_all[mt_rand(0, count($go_all) - 1)];

        $msg = "\n";  // First line is empty
        if ($this->dt <= 60)
            $msg .= "🕓 投稿時間：{$this->time} ({$this->dt} 分鐘前)\n\n";
        else
            $msg .= "🕓 投稿時間：{$this->time}\n\n";

        if ($post['rejects'])
            $msg .= "🗳 審核結果：✅ 通過 {$post['approvals']} 票 / ❌ 駁回 {$post['rejects']} 票\n";
        else
            $msg .= "🗳 審核結果：✅ 通過 {$post['approvals']} 票\n";
        $msg .= "{$this->link}\n\n";

        $msg .= "---\n\n";
        $msg .= "💡 $tips\n\n";
        $msg .= "👉 {$go}： https://x.nctu.app/submit";

        $URL = 'https://graph.facebook.com/v6.0/' . env('FB_PAGES_ID') . "_{$post['facebook_id']}/comments";

        $data = [
            'access_token' => env('FB_ACCESS_TOKEN'),
            'message' => $msg,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data
        ]);

        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result, true);

        if (strlen($result['id'] ?? '') > 10)
            return;  // Success, id = Comment ID

        $fb_id = $result['post_id'] ?? $result['id'] ?? '0_0';
        $post_id = (int)explode('_', $fb_id)[0];

        if ($post_id != $post['facebook_id']) {
            echo "Facebook comment error:";
            var_dump($result);
        }
    }
}
