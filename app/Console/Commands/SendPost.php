<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use OAuth;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use App\Models\Post;

class SendPost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:send';

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
        $cmd = $argv[1] ?? '';
        if ($this->hasArgument('id')) {
            $post = Post::where('id', '=', $this->argument('id'))->firstOrFail();

            $this->update_telegram($post);
            return 0;
        }


        /* Check unfinished post */
        $post = Post::where('status', '=', 4)->first();

        /* Get all pending submissions, oldest first */
        if (!isset($post)) {
            $submissions = Post::where('status', '=', 3)->orderBy('submitted_at')->get();

            foreach ($submissions as $post) {
                if ($this->checkEligible($post)) {
                    $id = Post::orderBy('id', 'desc')->first()->id ?? 0;
                    $post->update(['id' => $id + 1]);
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

        /* Send post to every SNS */
        $sns = [
            'Telegram' => 'telegram',
            'Twitter' => 'twitter',
            'Instagram' => 'instagram',
            'Plurk' => 'plurk',
            'Facebook' => 'facebook',
        ];
        foreach ($sns as $name => $key) {
            try {
                $func = "send_$key";
                if (isset($post["{$key}_id"]) && ($post["{$key}_id"] > 0 || strlen($post["{$key}_id"]) > 1))
                    continue;

                $pid = $func($post);

                if ($pid <= 0) { // Retry limit exceed
                    $dtP = floor(time() / 60) - floor(strtotime($post->posted_at) / 60);
                    if ($dtP > 3 * 5) // Total 3 times
                        $pid = 1;
                }

                if ($pid > 0)
                    $this->updatePostSns($post, $key, $pid);
            } catch (Exception $e) {
                echo "Send $name Error " . $e->getCode() . ': ' . $e->getMessage() . "\n";
                echo $e->lastResponse . "\n\n";
            }
        }

        /* Update SNS ID (mainly for Instagram) */
        $post = Post::find($post->id);

        /* Update with link to other SNS */
        $sns = [
            'Facebook' => 'facebook',
            'Plurk' => 'plurk',
            'Telegram' => 'telegram',
        ];
        foreach ($sns as $name => $key) {
            try {
                $func = "update_$key";
                if (!isset($post["{$key}_id"]) || $post["{$key}_id"] < 10)
                    continue;  // not posted, could be be edit

                $func($post);
            } catch (Exception $e) {
                echo "Edit $name Error " . $e->getCode() . ': ' . $e->getMessage() . "\n";
                echo $e->lastResponse . "\n\n";
            }
        }

        /* Remove vote keyboard in Telegram */
        $msgs = $db->getTgMsgsByUid($post->uid);
        foreach ($msgs as $item) {
            $TG->deleteMsg($item['chat_id'], $item['msg_id']);
            $db->deleteTgMsg($post->uid, $item['chat_id']);
        }

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
        if ($post['status'] != 3)
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


    private function send_telegram(Post $post): int
    {
        /* Check latest line */
        $lines = explode("\n", $post['body']);
        $end = end($lines);
        $is_url = filter_var($end, FILTER_VALIDATE_URL);
        if (!$post['has_img'] && $is_url)
            $msg = "<a href='$end'>#</a><a href='{$this->link}'>靠交{$post['id']}</a>";
        else
            $msg = "<a href='{$this->link}'>#靠交{$post['id']}</a>";

        $msg .= "\n\n" . enHTML($post['body']);


        /* Send to @xNCTU */
        if (!$post['has_img'])
            $result = $TG->sendMsg([
                'chat_id' => '@xNCTU',
                'text' => $msg,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => !$is_url
            ]);
        else
            $result = $TG->sendPhoto([
                'chat_id' => '@xNCTU',
                'photo' => env('APP_URL') . "/img/{$post['uid']}.jpg",
                'caption' => $msg,
                'parse_mode' => 'HTML',
            ]);

        $tg_id = $result['result']['message_id'] ?? 1;

        return $tg_id;
    }

    private function send_twitter(Post $post): int
    {
        $msg = "#靠交{$post['id']}\n\n{$post['body']}";
        if (strlen($msg) > 250)
            $msg = mb_substr($msg, 0, 120) . '...';
        $msg .= "\n\n✅ {$this->link} .";

        if ($post['has_img']) {
            $file = ['media' => curl_file_create(__DIR__ . "/img/{$post['uid']}.jpg")];

            $result = $this->send_twitter_api('https://upload.twitter.com/1.1/media/upload.json?media_category=tweet_image', $file);
            if (isset($result['media_id_string']))
                $img_id = $result['media_id_string'];
            else
                echo "Twitter upload error: " . json_encode($result) . "\n";
        }

        $query = ['status' => $msg];
        if (!empty($img_id))
            $query['media_ids'] = $img_id;
        $URL = 'https://api.twitter.com/1.1/statuses/update.json?' . http_build_query($query);

        $result = $this->send_twitter_api($URL);
        if (!isset($result['id_str'])) {
            echo "Twitter error: ";
            var_dump($result);

            if ($result['errors']['message'] == "We can't complete this request because this link has been identified by Twitter or our partners as being potentially harmful. Visit our Help Center to learn more.")
                return 1;

            return 0;
        }

        return $result['id_str'];
    }

    private function send_twitter_api(string $url, array $post = null): array
    {
        $nonce = md5(time());
        $timestamp = time();

        $oauth = new OAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), env('OAUTH_SIG_METHOD_HMACSHA1'));
        $oauth->enableDebug();
        $oauth->setToken(env('TWITTER_TOKEN'), env('TWITTER_TOKEN_SECRET'));
        $oauth->setNonce($nonce);
        $oauth->setTimestamp($timestamp);
        $signature = $oauth->generateSignature('POST', $url);

        $auth = [
            'oauth_consumer_key' => env('TWITTER_CONSUMER_KEY'),
            'oauth_nonce' => $nonce,
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => env('TWITTER_TOKEN')
        ];

        $authStr = 'OAuth ';
        foreach ($auth as $key => $val)
            $authStr .= $key . '="' . urlencode($val) . '", ';
        $authStr .= 'oauth_version="1.0"';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [
                "Authorization: $authStr"
            ]
        ]);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result, true);
        return $result;
    }

    private function send_plurk(Post $post): int
    {
        $msg = $post['has_img'] ? (env('APP_URL') . "/img/{$post['uid']}.jpg\n") : '';
        $msg .= "#靠交{$post['id']}\n{$post['body']}";

        if (mb_strlen($msg) > 290)
            $msg = mb_substr($msg, 0, 290) . '...';

        $msg .= "\n\n✅ {$this->link} ({$this->link})";

        $nonce = md5(time());
        $timestamp = time();

        /* Add Plurk */
        $URL = 'https://www.plurk.com/APP/Timeline/plurkAdd?' . http_build_query([
                'content' => $msg,
                'qualifier' => 'says',
                'lang' => 'tr_ch',
            ]);

        $oauth = new OAuth(env('PLURK_CONSUMER_KEY'), env('PLURK_CONSUMER_SECRET'), env('OAUTH_SIG_METHOD_HMACSHA1'));
        $oauth->enableDebug();
        $oauth->setToken(env('PLURK_TOKEN'), env('PLURK_TOKEN_SECRET'));
        $oauth->setNonce($nonce);
        $oauth->setTimestamp($timestamp);
        $signature = $oauth->generateSignature('POST', $URL);

        try {
            $oauth->fetch($URL);
            $result = $oauth->getLastResponse();
            $result = json_decode($result, true);
            return $result['plurk_id'];
        } catch (Exception $e) {
            echo "Plurk Message: $msg\n\n";
            echo 'Error ' . $e->getCode() . ': ' . $e->getMessage() . "\n";
            echo $e->lastResponse . "\n";
            return 0;
        }
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

    private function send_instagram(Post $post): int
    {
        if (!$post['has_img'])
            return -1;

        system("node " . __DIR__ . "/send-ig.js {$post['id']} "
            . ">> /temp/xnctu-ig.log 2>> /temp/xnctu-ig.err");

        return 0;
    }


    private function update_telegram(Post $post)
    {
        global $TG;

        $buttons = [];

        if ($post['facebook_id'] > 10)
            $buttons[] = [
                'text' => 'Facebook',
                'url' => "https://www.facebook.com/xNCTU/posts/{$post['facebook_id']}"
            ];

        $plurk = base_convert($post['plurk_id'], 10, 36);
        if (strlen($plurk) > 1)
            $buttons[] = [
                'text' => 'Plurk',
                'url' => "https://www.plurk.com/p/$plurk"
            ];

        if ($post['twitter_id'] > 10)
            $buttons[] = [
                'text' => 'Twitter',
                'url' => "https://twitter.com/x_NCTU/status/{$post['twitter_id']}"
            ];

        if (strlen($post['instagram_id']) > 1)
            $buttons[] = [
                'text' => 'Instagram',
                'url' => "https://www.instagram.com/p/{$post['instagram_id']}"
            ];

        $TG->editMarkup([
            'chat_id' => '@xNCTU',
            'message_id' => $post['telegram_id'],
            'reply_markup' => [
                'inline_keyboard' => [
                    $buttons
                ]
            ]
        ]);
    }


    private function update_plurk(Post $post)
    {
        if ($this->dt <= 60)
            $msg = "🕓 投稿時間：{$this->time} ({$this->dt} 分鐘前)\n\n";
        else
            $msg = "🕓 投稿時間：{$this->time}\n\n";

        if ($post['rejects'])
            $msg .= "審核結果：✅ 通過 {$post['approvals']} 票 / ❌ 駁回 {$post['rejects']} 票\n\n";
        else
            $msg .= "審核結果：✅ 通過 {$post['approvals']} 票\n\n";

        $msg .= "🥙 其他平台：https://www.facebook.com/xNCTU/posts/{$post['facebook_id']} (Facebook)"
            . "、https://twitter.com/x_NCTU/status/{$post['twitter_id']} (Twitter)";
        if (strlen($post['instagram_id']) > 1)
            $msg .= "、https://www.instagram.com/p/{$post['instagram_id']} (Instagram)";
        $msg .= "、https://t.me/xNCTU/{$post['telegram_id']} (Telegram)\n\n";

        $msg .= "👉 立即投稿：https://x.nctu.app/submit (https://x.nctu.app/submit)";

        $nonce = md5(time());
        $timestamp = time();

        /* Add Plurk */
        $URL = 'https://www.plurk.com/APP/Responses/responseAdd?' . http_build_query([
                'plurk_id' => $post['plurk_id'],
                'content' => $msg,
                'qualifier' => 'freestyle',
                'lang' => 'tr_ch',
            ]);

        $oauth = new OAuth(env('PLURK_CONSUMER_KEY'), env('PLURK_CONSUMER_SECRET'), env('OAUTH_SIG_METHOD_HMACSHA1'));
        $oauth->enableDebug();
        $oauth->setToken(env('PLURK_TOKEN'), env('PLURK_TOKEN_SECRET'));
        $oauth->setNonce($nonce);
        $oauth->setTimestamp($timestamp);
        $signature = $oauth->generateSignature('POST', $URL);

        try {
            $oauth->fetch($URL);
            $oauth->getLastResponse();
        } catch (Exception $e) {
            echo "Plurk Message: $msg\n\n";
            echo 'Error ' . $e->getCode() . ': ' . $e->getMessage() . "\n";
            echo $e->lastResponse . "\n";
        }
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
