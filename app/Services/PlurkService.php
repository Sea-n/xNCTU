<?php

namespace App\Services;

use App\Models\Post;
use Exception;
use Log;
use OAuth;
use OAuthException;

class PlurkService extends BaseService implements PostContract
{
    protected $go_all = [
        "立即投稿",
        "匿名投稿",
        "投稿連結",
        "投稿點我",
        "我要投稿",
    ];

    public function __construct()
    {
        //
    }

    public function publish(Post $post)
    {
        $msg = $post->media == 1 ? ($post->getUrl('image') . "\n") : '';
        $msg .= '#' . env('HASHTAG') . "{$post->id}\n{$post->body}";

        if (mb_strlen($msg) > 290)
            $msg = mb_substr($msg, 0, 290) . '...';

        $msg .= "\n\n✅ {$post->getUrl('website')} ({$post->getUrl('website')})";

        /* Add Plurk */
        $url = 'https://www.plurk.com/APP/Timeline/plurkAdd?' . http_build_query([
                'content' => $msg,
                'qualifier' => 'says',
                'lang' => 'tr_ch',
            ]);

        $result = $this->oauth($url);

        if ($result)
            $post->update(['plurk_id' => $result->plurk_id]);
    }

    public function comment(Post $post)
    {
        $time = date("Y 年 m 月 d 日 H:i", strtotime($post->submitted_at));
        $msg = "🕓 投稿時間：{$time}\n\n";

        if ($post->rejects)
            $msg .= "審核結果：✅ 通過 {$post->approvals} 票 / ❌ 駁回 {$post->rejects} 票\n\n";
        else
            $msg .= "審核結果：✅ 通過 {$post->approvals} 票\n\n";

        $msg .= '🥙 其他平台：';
        if ($post->facebook_id > 10)
            $msg .= "{$post->getUrl('facebook')} (Facebook)、";
        if ($post->twitter_id > 10)
            $msg .= "{$post->getUrl('twitter')} (Twitter)、";
        if ($post->instagram_id != '')
            $msg .= "{$post->getUrl('instagram')} (Instagram)、";
        $msg .= "{$post->getUrl('telegram')} (Telegram)\n\n";

        $go = $this->go_all[mt_rand(0, count($this->go_all) - 1)];
        $msg .= "👉 {$go}：" . url('/submit') . ' (' . url('/submit') . ')';

        /* Response to Plurk */
        $url = 'https://www.plurk.com/APP/Responses/responseAdd?' . http_build_query([
                'plurk_id' => $post->plurk_id,
                'content' => $msg,
                'qualifier' => 'freestyle',
                'lang' => 'tr_ch',
            ]);

        $this->oauth($url);
    }

    /**
     * Make GET request to Plurk API with OAuth signature.
     * @param string $url
     * @return object
     */
    protected function oauth(string $url)
    {
        $nonce = md5(time());
        $timestamp = time();

        try {
            $oauth = new OAuth(env('PLURK_CONSUMER_KEY'), env('PLURK_CONSUMER_SECRET'), OAUTH_SIG_METHOD_HMACSHA1);
        } catch (OAuthException $e) {
            Log::error($e->getMessage());
            return null;
        }
        $oauth->enableDebug();
        $oauth->setToken(env('PLURK_TOKEN'), env('PLURK_TOKEN_SECRET'));
        $oauth->setNonce($nonce);
        $oauth->setTimestamp($timestamp);

        try {
            $oauth->fetch($url);
            $result = $oauth->getLastResponse();
            $result = json_decode($result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }

        return $result;
    }
}

