<?php
use App\Models\User;

$time = humanTime($post->created_at);
$ts = strtotime($post->created_at);

$author_name = $post->ip_from;
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
@elseif (isset($post->id))
    <div class="ts positive message">
        <div class="header">文章已發出</div>
        <p>您可以在 <a href="/post/{{ $post->id }}">#靠交{{ $post->id }}</a> 找到這篇文章</p>
    </div>
@endisset

<div class="ts card" id="post-{{ $post->uid }}" style="margin-bottom: 42px;">
@if ($post->media == 1)
    <div class="image">
@if ($single ?? false)
        <img class="post-image" src="/img/{{ $post->uid }}.jpg" />
@else
        <img class="post-image" src="/img/{{ $post->uid }}.jpg" onclick="showImg(this);" style="max-height: 40vh; width: auto; cursor: zoom-in;" />
@endif
    </div>
@endif

    <div class="content">
@if ($single ?? false)
        <div class="header">#投稿{{ $post->uid }}</div>
@else
        <div class="header"><a href="/review/{{ $post->uid }}">#投稿{{ $post->uid }}</a></div>
@endif

        <div>{{ $post->body }}</div>
    </div>

    <div class="extra content">
        <div class="right floated author">
            <img class="ts circular avatar image" src="{{ $author_photo }}" onerror="this.src='/assets/img/avatar.jpg';"> {{ $author_name }}
@if ($ip_masked)
            <br><span class="right floated">({{ $ip_masked }})</span>
@endif
        </div>

        <p style="margin-top: 0; line-height: 1.7em">
@if ($post->status == 0)
            <br><span>送出時間：<time data-ts="{{ $ts }}">{{ $time }}</time></span>
@else
            <span>審核狀況：
                <button class="ts vote positive button">通過</button>&nbsp;<span id="approvals">{{ $post->approvals }}</span>&nbsp;票 /&nbsp;
                <button class="ts vote negative button">駁回</button>&nbsp;<span id="rejects">{{ $post->rejects }}</span>&nbsp;票</span>
            <br><span>投稿時間：<time data-ts="{{ $ts }}">{{ $time }}</time></span>
@endif
        </p>
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
</div>
