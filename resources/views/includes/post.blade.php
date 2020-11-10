<?php
use App\Models\User;

$time = humanTime($post->submitted_at ?? now());
$ts = strtotime($post->submitted_at ?? now());

$author_name = "匿名, {$post->ip_from}";
if (isset($post->author)) {
    $author = User::find($post->author);

    $dep = idToDep($post->author);
    $author_name = $dep . ' ' . $author->name;
}

$ip_masked = $post->ip_addr;
if (strpos($author_name, '境外') === false)
    $ip_masked = ip_mask($ip_masked);
if (!Auth::check())
    $ip_masked = ip_mask_anon($ip_masked);
if (isset($post->author))
    $ip_masked = false;

$author_photo = genPic($ip_masked);
if (isset($post->author)) {
    $author_photo = genPic($post->author);
    if (isset($author->tg_photo))
        $author_photo = "/img/tg/{$author->tg_id}-x64.jpg";
}
?>

@isset ($post->deleted_at)
    <div class="ts negative message">
        <div class="header">此文已刪除</div>
        <p>刪除原因：{{ $post->delete_note }}</p>
    </div>
@elseif (isset($post->id) && strpos(url()->current(), '/post') === false)
    <div class="ts positive message">
        <div class="header">文章已發出</div>
        <p>您可以在 <a href="/post/{{ $post->id }}">#靠交{{ $post->id }}</a> 找到這篇文章</p>
    </div>
@endisset

<article itemscope itemtype="http://schema.org/Article" class="ts card" id="post-{{ $post->uid }}" style="margin-bottom: 42px;">
    @if ($post->media == 1)
        <div class="image">
            @isset ($single)
                    <img itemprop="image" class="post-image" src="/img/{{ $post->uid }}.jpg" />
            @else
                    <img itemprop="image" class="post-image" src="/img/{{ $post->uid }}.jpg" onclick="showImg(this);" style="max-height: 40vh; width: auto; cursor: zoom-in;" />
            @endif
        </div>
    @else
        <meta itemprop="image" content="/assets/img/logo.png">
    @endif

    <div class="content">
        @isset ($post->id)
            @isset ($single)
                <div itemprop="headline" class="header">#靠交{{ $post->id }}</div>
            @else
                <div itemprop="headline" class="header">
                    <a href="/post/{{ $post->id }}">#靠交{{ $post->id }}</a>
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

        <div itemprop="articleBody">{!! x($post->body) !!}</div>
    </div>

    <div class="extra content" id="extra">
        @isset ($single)
            @if ($post->telegram_id > 1)
                <p><span><i class="telegram icon"></i> Telegram: <a target="_blank" href="https://t.me/s/xNCTU/{{ $post->telegram_id }}">@xNCTU/{{ $post->telegram_id }}</a></span><br>
            @endif

            @if ($post->facebook_id > 87)
                <span><i class="facebook icon"></i> Facebook: <a target="_blank" href="https://www.facebook.com/xNCTU2.0/posts/{{ $post->facebook_id }}">@xNCTU2.0/{{ $post->facebook_id }}</a> <small>({{ $post->fb_likes }} likes)</small></span><br>
            @endif

            @if (strlen($post->instagram_id) > 1)
                <span><i class="instagram icon"></i> Instagram: <a target="_blank" href="https://www.instagram.com/p/{{ $post->instagram_id }}">@x_nctu/{{ $post->instagram_id }}</a></span><br>
            @endif

            @if ($post->plurk_id > 69)
                <span><i class="talk icon"></i> Plurk: <a target="_blank" href="https://www.plurk.com/p/{{ base_convert($post->plurk_id, 10, 36) }}">@xNCTU/{{ base_convert($post->plurk_id, 10, 36) }}</a></span><br>
            @endif

            @if ($post->twitter_id > 42)
                <span><i class="twitter icon"></i> Twitter: <a target="_blank" href="https://twitter.com/x_NCTU/status/{{ $post->twitter_id }}">@x_NCTU/{{ $post->twitter_id }}</a></span></p>
            @endif
        @endisset

        <div itemprop="author" itemscope itemtype="http://schema.org/Person" class="right floated author">
            <img itemprop="image" class="ts circular avatar image" src="{{ $author_photo }}" onerror="this.src='/assets/img/avatar.jpg';">
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
                    <button class="ts vote positive button">通過</button>&nbsp;<span id="approvals">{{ $post->approvals }}</span>&nbsp;票 /&nbsp;
                    <button class="ts vote negative button">駁回</button>&nbsp;<span id="rejects">{{ $post->rejects }}</span>&nbsp;票</span>
                <br><span>投稿時間：<time itemprop="dateCreated" datetime="{{ $post->submitted_at }}" data-ts="{{ $ts }}">{{ $time }}</time></span>
            @endif
        </div>

        <div itemprop="publisher" itemscope itemtype="http://schema.org/Organization" style="display: none;">
            <div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
                <meta itemprop="url" content="/assets/img/logo.png">
            </div>
            <span itemprop="name">靠北交大 2.0</span>
        </div>

        <link itemprop="mainEntityOfPage" href="{{ url()->current() }}" />
    </div>

    @if ($post->status == 0)
        <div class="ts fluid bottom attached large buttons">
            <button id="confirm-button" class="ts positive button" onclick="confirmSubmission('{{ $post->uid }}');">確認貼文</button>
            <button id="delete-button" class="ts negative button" onclick="deleteSubmission('{{ $post->uid }}');">刪除投稿</button>
        </div>
    @elseif (Auth::check() && canVote($post->uid, Auth::id())['ok'])
        <div class="ts fluid bottom attached large buttons">
            <button class="ts positive button" onclick="approve('{{ $post->uid }}');">通過貼文</button>
            <button class="ts negative button" onclick="reject('{{ $post->uid }}');">駁回投稿</button>
        </div>
    @endif
</article>
