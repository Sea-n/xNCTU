<?php
use App\Models\Post;
use App\Models\Vote;

/**
 * @var Post $post
 */

$IMG = url('/assets/img/og.png');

$hashtag = "#靠交{$post->id}";

$DESC = $post->body;
$TITLE = "$hashtag $DESC";

if (mb_strlen($TITLE) > 40)
    $TITLE = mb_substr($TITLE, 0, 40) . '...';

if (mb_strlen($DESC) > 150)
    $DESC = mb_substr($DESC, 0, 150) . '...';

if ($post->media)
    $IMG = $post->getUrl('image');

$single = 1;

$votes = Vote::where('uid', '=', $post->uid)->orderBy('created_at')->get();

/* Recommended posts */
$posts = Post::where('status', '=', 5)->orderByDesc('id')->take(500)->get();
$posts = $posts->getIterator()->getArrayCopy();

$id = $post->id;
$posts = array_filter($posts, function($item) {
    global $id;
    if ($item['facebook_id'] < 10)
        return false;
    return $item['id'] != $id;
});

usort($posts, function (Post $a, Post $b) {
    return $b['max_likes'] <=> $a['max_likes'];
});
$posts = array_slice($posts, 0, 50);

$posts2 = [];
for ($i=1; $i<=8; $i++) {
    $pos = $post->id % ($i*3);
    if (count($posts) > $pos)
        $posts2[] = array_splice($posts, $pos, 1)[0];
}

?>

@extends('layouts.rabbit')

@section('title', $TITLE)
@section('desc', $DESC)
@section('img', $IMG)

@section('head')
    <script src="/assets/js/review.js"></script>
@stop

@section('content')

@include('includes.post')

@include('includes.votes')

<br><hr>
<div class="recommended-posts">
    <h2 class="ts header">推薦文章</h2>
    <div class="ts two cards">
    @foreach ($posts2 as $i => $item)
        <div class="ts card" onclick="location.href = '/post/{{ $item->id }}';" style="cursor: pointer;">
            <div class="content">
                <div class="header"><a href="/post/{{ $item->id }}">#靠交{{ $item->id }}</a></div>
                <div class="description" style="height: 360px; overflow-y: hidden;">{!! x(mb_substr($item->body, 0, 360)) . '...' !!}</div>
                <div id="hide-box">
                    <sub>點擊打開全文</sub>
                </div>
            </div>
        </div>
    @endforeach
</div>

@stop
