<?php

use App\Models\Post;

$posts = Post::where('status', 5)->orderByDesc('id')->take(1000)->get();
?>

@extends('layouts.master')

@section('title', '文章列表')

@section('head')
    <script src="/assets/js/posts.js"></script>
@stop

@section('content')
    <div id="posts">
        @foreach ($posts as $i => $post)
            @if ($i < 10)
                @include('includes.post')
            @else
                @include('includes.post', ['static' => true])
            @endif
        @endforeach
    </div>

    <button id="more" class="ts primary button" onclick="more();" data-offset="50">顯示更多文章</button>

    @include('includes.imgbox')

    @include('includes.post-template')
@stop
