<?php

use App\Models\Post;

$likes = request()->input('likes', '');
$media = request()->input('media', '');

$query = Post::where('status', '=', 5);
if (is_numeric($likes))
    $query = $query->where('max_likes', '>=', $likes);
if (is_numeric($media))
    $query = $query->where('media', '=', $media);

$posts = $query->orderByDesc('id')->take(50)->get();
?>

@extends('layouts.master')

@section('title', '文章列表')

@section('head')
    <script src="/assets/js/posts.js"></script>
@stop

@section('content')
    <details class="ts accordion filter">
        <summary><i class="icon dropdown"></i>貼文篩選</summary>
        <form action="" method="GET">
            <div class="ts labeled icon button">
                <i class="image icon"></i>
                <span class="text">
                    多媒體類型
                    <select name="media">
                        <option value="" selected>不限</option>
                        <option value="0">純文字</option>
                        <option value="1">圖片</option>
                    </select>
                </span>
            </div>

            <div class="ts labeled icon button">
                <i class="like outline icon"></i>
                <span class="text">
                    貼文讚數
                    <select name="likes">
                        <option value="" selected>不限</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </span>
            </div>
            <button class="ts primary button" type="submit">送出</button>
        </form>
        <br>
    </details>

    <div id="posts">
        @foreach ($posts as $post)
            @include('includes.post')
        @endforeach
    </div>

    <button id="more" class="ts primary button" onclick="more();" data-offset="50">顯示更多文章</button>

    <div class="ts modals dimmer" id="img-container-wrapper" style="margin-top: 40px;">
        <dialog id="modal" class="ts basic fullscreen closable modal" open>
            <i class="close icon"></i>
            <div class="ts icon header"></div>
            <div class="content">
                <img id="img-container-inner" style="width: 100%;">
            </div>
            <div class="actions">
                <button class="ts inverted basic cancel button">關閉</button>
            </div>
        </dialog>
    </div>

    <template id="post-template">
        <div class="ts card" id="post-XXXX" style="margin-bottom: 42px;">
            <div class="image">
                <img id="img" class="post-image" style="max-height: 40vh; width: auto; cursor: zoom-in;"/>
            </div>
            <div class="content">
                <div class="header"><a id="hashtag">#靠交000</a></div>
                <div id="body"></div>
            </div>
            <div class="extra content">
                <div class="right floated author">
                    <img class="ts circular avatar image" id="author-photo"> <span id="author-name">Sean</span>
                    <br><span class="right floated" id="ip-outer">(<span id="ip-inner">140.113.***.*87</span>)</span>
                </div>
                <p style="margin-top: 0; line-height: 1.7em">
                    <span>審核狀況：<button class="ts vote positive button">通過</button>
                        &nbsp;<span id="approvals">87</span>&nbsp;票 /&nbsp;<button class="ts vote negative button">
                            駁回</button>&nbsp;<span id="rejects">42</span>&nbsp;票</span><br>
                    <span>投稿時間：<time id="time">01 月 11 日 08:17</time></span>
                </p>
            </div>
        </div>
    </template>
@stop
