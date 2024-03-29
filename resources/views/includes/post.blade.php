<?php

use App\Models\Post;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;

/**
 * @var Post $post
 * @var bool|null $static
 */

$time = humanTime($post->submitted_at ?? now());
$ts = strtotime($post->submitted_at ?? now());

$author_name = "匿名, {$post->ip_from}";
if ($post->author)
    $author_name = $post->author->dep() . ' ' . $post->author->name;

$ip_masked = $post->ip_addr;
if (strpos($post->ip_from, '境外') === false && $post->ip_from != 'VPS')
    $ip_masked = ip_mask($ip_masked);
if (!Auth::check())
    $ip_masked = ip_mask_anon($ip_masked);
if ($post->author)
    $ip_masked = false;

if (!isset($static)) {
    $author_photo = genPic($ip_masked);
    if ($post->author) {
        $author_photo = genPic($post->author_id);
        if ($post->author->tg_photo)
            $author_photo = "/avatar/tg/{$post->author->tg_id}-x64.jpg";
    }
}

if ($post->orig) {
    $rendererOptions = [
        'detailLevel' => 'char',
        'language' => 'cht',
        'lineNumbers' => false,
        'showHeader' => false,
        'spacesToNbsp' => true,
        'mergeThreshold' => 1,
    ];
    $differ = new Differ(explode("\n", $post->orig), explode("\n", $post->body), []);
    $renderer = RendererFactory::make('Combined', $rendererOptions); // or your own renderer object
    $diff = $renderer->render($differ);
}

?>

@isset ($post->deleted_at)
    <div class="ts negative message">
        <div class="header">此文已刪除</div>
        <p>刪除原因：{!! x($post->delete_note) !!}</p>
    </div>
@endisset

<article itemscope itemtype="http://schema.org/Article" class="ts card"
         id="post-{{ $post->uid }}" style="margin-bottom: 42px;">
    @if (isset($static))
        <meta itemprop="image" content="/assets/img/logo.png">
    @elseif ($post->media == 1)
        <div class="image">
            <img itemprop="image" class="post-image" src="/img/{{ $post->uid }}.jpg"
                 onclick="showImg('{{ $post->uid }}');" style="max-height: 40vh; width: auto; cursor: zoom-in;"/>
        </div>
    @elseif ($post->media == 2)
        <div class="image">
            <video autoplay muted loop onclick="showImg('{{ $post->uid }}', 2);"
                   style="max-width: 100%; max-height: 40vh; cursor: zoom-in;">
                <source src="/img/{{ $post->uid }}.mp4" type="video/mp4">
            </video>
        </div>
    @elseif ($post->media == 3)
        <div class="image">
            <video controls style="max-width: 100%; max-height: 40vh">
                <source src="/img/{{ $post->uid }}.mp4" type="video/mp4">
            </video>
        </div>
    @else
        <meta itemprop="image" content="/assets/img/logo.png">
    @endif

    <div class="content">
        @isset ($post->id)
            @isset ($single)
                <div itemprop="headline" class="header">#{{ env('HASHTAG') }}{{ $post->id }}</div>
            @else
                <div itemprop="headline" class="header">
                    <a href="/post/{{ $post->id }}">#{{ env('HASHTAG') }}{{ $post->id }}</a>
                </div>
            @endif
        @else
            @isset ($single)
                <div itemprop="headline" class="header">#投稿{{ $post->uid }}</div>
            @else
                <div itemprop="headline" class="header">
                    <a href="/review/{{ $post->uid }}">#投稿{{ $post->uid }}</a>
                </div>
            @endif
        @endisset

        <p itemprop="articleBody">{!! x($post->body) !!}</p>
        @isset ($diff, $single)
            <details class="ts accordion" id="diff" open>
                <summary>
                    <i class="dropdown icon"></i> 原貼文內容
                </summary>
                <div class="ts secondary segment">
                    {!! $diff !!}
                </div>
            </details>
        @endisset
    </div>

    <div class="extra content" id="extra">
        @isset ($single)
            @if ($post->telegram_id > 1)
                <span><i class="telegram icon"></i> Telegram: <a target="_blank" href="{{ $post->getUrl('telegram') }}">
                        @<span>{{ env('TELEGRAM_USERNAME') }}</span>/{{ $post->telegram_id }}</a></span>
                <br>
            @endif

            @if ($post->discord_id > 42)
                <span><i class="rocket icon"></i> Discord: <a target="_blank" href="{{ $post->getUrl('discord') }}">
                        {{ $post->discord_id }}</a></span>
                <br>
            @endif

            @if ($post->facebook_id > 87)
                <span><i class="facebook icon"></i> Facebook: <a target="_blank" href="{{ $post->getUrl('facebook') }}">
                        @<span>{{ env('FACEBOOK_USERNAME') }}</span>/{{ $post->facebook_id }}</a>
                        <small>({{ $post->fb_likes }} likes)</small></span>
                <br>
            @endif

            @if (strlen($post->instagram_id) > 1)
                <span><i class="instagram icon"></i> Instagram:
                    <a target="_blank" href="{{ $post->getUrl('instagram') }}">
                        @<span>{{ env('INSTAGRAM_USERNAME') }}</span>/{{ $post->instagram_id }}</a></span>
                <br>
            @endif

            @if ($post->plurk_id > 69)
                <span><i class="talk icon"></i> Plurk: <a target="_blank" href="{{ $post->getUrl('plurk') }}">
                        @<span>{{ env('PLURK_USERNAME') }}</span>/{{ base_convert($post->plurk_id, 10, 36) }}</a></span>
                <br>
            @endif

            @if ($post->twitter_id > 42)
                <span><i class="twitter icon"></i> Twitter: <a target="_blank" href="{{ $post->getUrl('twitter') }}">
                        @<span>{{ env('TWITTER_USERNAME') }}</span>/{{ $post->twitter_id }}</a></span>
            @endif
        @endisset

        <div itemprop="author" itemscope itemtype="http://schema.org/Person" class="right floated author">
            @unless (isset($static))
                <img itemprop="image" class="ts circular avatar image"
                     src="{{ $author_photo }}" onerror="this.src='/assets/img/avatar.jpg';">
            @endunless
            <span itemprop="name">{{ $author_name }}</span>
            @if ($ip_masked)
                <br><span class="right floated">({{ $ip_masked }})</span>
            @endif
        </div>

        <div style="line-height: 1.7em">
            @if ($post->status == 0)
                <br><span>送出時間：<time data-ts="0">{{ $time }}</time></span>
            @else
                <span>{{ in_array($post->status, [1, 2, 3]) ? '審核狀況' : '審核結果' }}：
                    @if (Auth::check() && canVote($post->uid, Auth::id())['ok'])
                        <button class="ts vote positive button"
                                onclick="approve('{{ $post->uid }}');">通過</button>
                        <span id="approvals">{{ $post->approvals }}</span>&nbsp;票 /
                        <button class="ts vote negative button"
                                onclick="reject('{{ $post->uid }}');">駁回</button>
                        <span id="rejects">{{ $post->rejects }}</span>&nbsp;票</span>
                    @else
                        <button class="ts vote positive button">通過</button>
                        <span id="approvals">{{ $post->approvals }}</span>&nbsp;票 /
                        <button class="ts vote negative button">駁回</button>
                        <span id="rejects">{{ $post->rejects }}</span>&nbsp;票</span>
                    @endif
                <br><span>投稿時間：<time datetime="{{ $post->submitted_at }}" data-ts="{{ $ts }}"
                                     itemprop="dateCreated">{{ $time }}</time></span>
            @endif
        </div>

        <div itemprop="publisher" itemscope itemtype="http://schema.org/Organization" style="display: none;">
            <div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
                <meta itemprop="url" content="/assets/img/logo.png">
            </div>
            <span itemprop="name">{{ env('APP_CHINESE_NAME') }}</span>
        </div>

        <link itemprop="mainEntityOfPage" href="{{ url()->current() }}"/>
    </div>

    @if ($post->status == 0)
        <div class="ts fluid bottom attached large buttons">
            <button id="confirm-button" class="ts positive button" onclick="confirmSubmission('{{ $post->uid }}');">
                確認貼文
            </button>
            <button id="delete-button" class="ts negative button" onclick="deleteSubmission('{{ $post->uid }}');">
                刪除投稿
            </button>
        </div>
    @elseif (Auth::check() && canVote($post->uid, Auth::id())['ok'])
        <div class="ts fluid bottom attached large buttons">
            <button class="ts positive button" onclick="approve('{{ $post->uid }}');">通過貼文</button>
            <button class="ts negative button" onclick="reject('{{ $post->uid }}');">駁回投稿</button>
        </div>
    @endif
</article>
