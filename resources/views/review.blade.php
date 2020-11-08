<?php
use App\Models\Vote;

$IMG = env('APP_URL') . '/assets/img/og.png';

$hashtag = "#投稿{$post->uid}";

$DESC = $post['body'];
$TITLE = "$hashtag $DESC";

if (mb_strlen($TITLE) > 40)
    $TITLE = mb_substr($TITLE, 0, 40) . '...';

if (mb_strlen($DESC) > 150)
    $DESC = mb_substr($DESC, 0, 150) . '...';

if ($post['has_img'])
    $IMG = env('APP_URL') . "img/{$post->uid}.jpg";

if (Auth::check())
    $canVote = canVote($post->uid, Auth::id())['ok'];

$single = 1;

if ($post['status'] != 0) {
    $votes = Vote::where('uid', '=', $post->uid)->get();
}

?>

@extends('layouts.turkey')

@section('title', $TITLE)
@section('desc', $DESC)
@section('header', '貼文審核')

@section('head')
    <script src="/assets/js/review.js"></script>
    <meta name="uid" content="{{ $post->uid }}">
@stop

@section('content')

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

@include('includes.post')

@isset ($votes)

@include('includes.votes')

@if (Auth::check() && in_array($post->status, [1, 2, 3, 10]))
    <button id="refresh" class="ts primary button" onclick="updateVotes('{{ $post->uid }}');">重新整理</button>
@endif

@endisset

@stop
