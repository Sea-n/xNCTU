<?php
use App\Models\Post;

if (Auth::check()) {
    $votes = Auth::user()->approvals + Auth::user()->rejects;
    $count = max(floor($votes/10), 3);
    $posts = Post::whereIn('status', [-2, -4])->orderBy('deleted_at', 'desc')->take($count)->get();
}
?>

@extends('layouts.master')

@section('title', '已刪投稿')

@section('head')
    <script src="/assets/js/review.js"></script>
@stop

@section('content')

@guest
    <div class="ts negative message">
        <div class="header">你不是交大生</div>
        <p>這邊僅限交大使用者瀏覽，外校生僅可在知道投稿編號的情況下看到刪除記錄，例如 <a href="/review/2C8j">#投稿2C8j</a>。</p>
    </div>
@endguest

@auth
    <p>此頁面列出所有已刪除的投稿，預設顯示最近 3 篇，依照審文數量增加。</p>
    <p>除未通過投票門檻的投稿外，您也可以在 <a href="/transparency">透明度報告</a> 頁面看到貼文遭下架的理由。</p>

    @foreach ($posts as $post)
        @include('includes.post')
    @endforeach

    <p>您目前審核 {{ $votes }} 篇文，可看見 {{ $count }} 篇已刪投稿。歡迎多參與審核工作，共同把持{{ env('APP_CHINESE_NAME') }} 貼文品質。</p>
@endauth

@stop
