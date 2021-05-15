<?php

namespace App\Services;

use App\Models\Post;
use Exception;
use Log;

class FacebookService extends BaseService implements PostContract
{

    protected $tips_all;

    protected $go_all = [
        "立即投稿",
        "匿名投稿",
        "投稿連結",
        "投稿點我",
        "我要投稿",
    ];

    public function __construct()
    {
        $this->tips_all = [
            "投稿時將網址放在最後一行，發文會自動顯示頁面預覽",
            "電腦版投稿可以使用 Ctrl-V 上傳圖片",
            "使用交大網路投稿會自動填入驗證碼",
            "如想投稿 GIF 可上傳至 Giphy，並將連結置於文章末行",

            "透過自動化審文系統，多數投稿會在 10 分鐘內發出",
            "所有人皆可匿名投稿，全校師生皆可具名審核",
            env('APP_CHINESE_NAME') . " 採自助式審文，全校師生皆能登入審核",
            env('APP_CHINESE_NAME') . " 有 50% 以上投稿來自交大 IP 位址",
            "登入後可看到 140.113.**.*42 格式的部分 IP 位址",

            env('APP_CHINESE_NAME') . " 除了 Facebook 外，還支援 Twitter、Plurk 等平台\nhttps://twitter.com/" . env('TWITTER_USERNAME'),
            env('APP_CHINESE_NAME') . " 除了 Facebook 外，還支援 Plurk、Twitter 等平台\nhttps://www.plurk.com/" . env('PLURK_USERNAME'),
            "加入" . env('APP_CHINESE_NAME') . " Telegram 頻道，第一時間看到所有貼文\nhttps://t.me/" . env('TELEGRAM_USERNAME'),
            "你知道靠交也有 Instagram 帳號嗎？只要投稿圖片就會同步發佈至 IG 喔\nhttps://www.instagram.com/" . env('INSTAGRAM_USERNAME'),

            "審核紀錄公開透明，你可以看到誰以什麼原因通過/駁回了投稿\n" . url('/posts'),
            "覺得審核太慢嗎？你也可以來投票\n" . url('/review'),
            "網站上「已刪投稿」區域可以看到被黑箱的記錄\n" . url('/deleted'),
            "知道都是哪些系的同學在審文嗎？打開排行榜看看吧\n" . url('/ranking'),
            "秉持公開透明原則，您可以在透明度報告看到師長同學請求刪文的紀錄\n" . url('/transparency'),
            "靠交 2.0 是交大資工學生自行開發的系統，程式原始碼公開於 GitHub 平台\nhttps://github.com/Sea-n/" . env('APP_NAME'),
        ];
    }

    public function publish(Post $post)
    {
        $msg = "#靠交{$post->id}\n\n";
        $msg .= "{$post->body}";

        $url = 'https://graph.facebook.com/v6.0/' . env('FACEBOOK_PAGES_ID') . ($post->media == 0 ? '/feed' : '/photos');

        $data = ['access_token' => env('FACEBOOK_ACCESS_TOKEN')];
        if ($post->media == 0) {
            $data['message'] = $msg;

            $lines = explode("\n", $post->body);
            $end = end($lines);
            if (filter_var($end, FILTER_VALIDATE_URL) && strpos($end, 'facebook') === false)
                $data['link'] = $end;
        } else {
            $data['url'] = $post->getUrl('image');
            $data['caption'] = $msg;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data
        ]);

        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result);

        $fb_id = $result->post_id ?? $result->id ?? '0_0';
        $post_id = (int)explode('_', $fb_id)[1];

        if ($post_id == 0) {
            Log::error('Facebook failed. ');
            Log::error(json_encode($result));
            return;
        }

        $post->update(['facebook_id' => $post_id]);
    }

    /**
     * @param Post $post
     * @throws Exception
     */
    public function comment(Post $post)
    {
        assert(count($this->tips_all) % 7 != 0);  // current count = 20
        $tips = $this->tips_all[($post->id * 7) % count($this->tips_all)];
        $go = $this->go_all[mt_rand(0, count($this->go_all) - 1)];

        $msg = "\n";  // First line is empty
        $time = date("Y 年 m 月 d 日 H:i", strtotime($post->submitted_at));
        $dt = floor(strtotime($post->posted_at) / 60) - floor(strtotime($post->submitted_at) / 60);  // Use what user see (without seconds)
        if ($dt <= 90)
            $msg .= "🕓 投稿時間：{$time} ({$dt} 分鐘前)\n\n";
        else
            $msg .= "🕓 投稿時間：{$time}\n\n";

        if ($post->rejects)
            $msg .= "🗳 審核結果：✅ 通過 {$post->approvals} 票 / ❌ 駁回 {$post->rejects} 票\n";
        else
            $msg .= "🗳 審核結果：✅ 通過 {$post->approvals} 票\n";
        $msg .= "{$post->getUrl('website')}\n\n";

        $msg .= "---\n\n";
        $msg .= "💡 {$tips}\n\n";
        $msg .= "👉 {$go}： " . url('/submit');

        $url = 'https://graph.facebook.com/v6.0/' . env('FACEBOOK_PAGES_ID') . "_{$post->facebook_id}/comments";

        $data = [
            'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
            'message' => $msg,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data
        ]);

        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result);

        if (strlen($result->id ?? '') > 10)
            return;  // Success, id = Comment ID

        $fb_id = $result->post_id ?? $result->id ?? '0_0';
        $post_id = (int)explode('_', $fb_id)[0];

        if ($post_id != $post->facebook_id) {
            Log::error("Facebook comment error:");
            Log::error(json_encode($result));
        }
    }
}

