<?php
use App\Models\Post;

$posts = Post::whereIn('status', [1, 2, 3, 10])->orderBy('created_at', 'desc')->get();

foreach ($posts as $key => $item)
    if (!Auth::check())
        $posts[$key]['canVote'] = true;
    else {
        $canVote = canVote($item->uid, Auth::id());
        $posts[$key]['canVote'] = $canVote['ok'];
    }
?>

@extends('layouts.master')

@section('title', '貼文審核')

@section('head')
    <script src="/assets/js/review.js"></script>
@stop

@section('content')
    <div id="rule">
        <h2>審核規範</h2>
        <ol>
            <li>依照直覺憑良心審文，為自己的投票負責，可以參考過往的&nbsp;<a href="/deleted" target="_blank">已刪投稿</a>。</li>
            <li>符合&nbsp;<a href="https://zh-tw.facebook.com/communitystandards/" target="_blank">Facebook 社群守則</a>，例如禁止不實訊息、暴力、煽動仇恨、性誘惑等。</li>
            <li>遵從台灣法律規定，駁回無根據的誹謗、抹黑、帶風向投稿。</li>
        </ol>
    </div>

    <?php $header = false; ?>
    @foreach ($posts as $post)
        @if (!$post->canVote)
            @continue
        @endif

        @if (!$header)
            <h2>待審貼文</h2>
            <?php $header = true; ?>
        @endif

        @include('includes.post')
    @endforeach

    <?php $header = false; ?>
    @foreach ($posts as $post)
        @if ($post->canVote)
            @continue
        @endif

        @if (!$header)
            <h2>已審貼文</h2>
            <?php $header = true; ?>
        @endif

        @include('includes.post')
    @endforeach

    <hr>
    <h2 class="ts header">排行榜</h2>
    <p>排名積分會依時間遠近調整權重，正確的駁回 <a href="/deleted">已刪投稿</a> 將大幅提升排名。</p>
    <p>您可以在 <a href="/ranking">這個頁面</a> 查看排行榜。</p>
@stop
