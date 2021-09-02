<?php

use App\Models\Post;

$likes = request()->input('likes', '');
$media = request()->input('media', '');
$keyword = request()->input('keyword', '');

$query = Post::where('status', 5);
if (is_numeric($likes))
    $query = $query->where('fb_likes', '>=', $likes);
if (is_numeric($media))
    $query = $query->where('media', $media);
if (mb_strlen($keyword))
    $query = $query->where('body', 'LIKE', "%$keyword%");

$posts = $query->orderByDesc('id')->take(50)->get();
?>

@extends('layouts.master')

@section('title', '文章列表')

@section('head')
    <script src="/assets/js/posts.js"></script>
@stop

@section('content')
    <form action="" method="GET">
        <div class="ts labeled left action input">
            <div class="ts label">
                <i class="image icon"></i>
                <span class="text">多媒體類型</span>
            </div>
            <select name="media" class="ts basic dropdown">
                <option value="" selected>不限</option>
                <option value="0">純文字</option>
                <option value="1">圖片</option>
            </select>
        </div>

        <div class="ts labeled left action input">
            <div class="ts label">
                <i class="like outline icon"></i>
                <span class="text">貼文讚數</span>
            </div>
            <select name="likes" class="ts basic dropdown">
                <option value="" selected>不限</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="500">500</option>
            </select>
        </div>

        <div class="ts labeled left action input">
            <div class="ts label">
                <i class="search icon"></i>
                <span class="text">關鍵字</span>
            </div>
            <input name="keyword" placeholder="武漢肺炎" size="8">
        </div>

        <button class="ts primary button" type="submit" style="margin-top: -3px;">
            送出
        </button>
    </form>
    <br>

    <div id="posts">
        @each('includes.post', $posts, 'post')
    </div>

    <button id="more" class="ts primary button" onclick="more();" data-offset="50">顯示更多文章</button>

    @include('includes.imgbox')

    @include('includes.post-template')
@stop
