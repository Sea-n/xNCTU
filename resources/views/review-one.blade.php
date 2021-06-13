<?php

use App\Models\Post;
use App\Models\Vote;

/**
 * @var Post $post
 */

$IMG = url('/assets/img/og.png');

$hashtag = "#投稿{$post->uid}";

$DESC = $post->body;
$TITLE = "$hashtag $DESC";

if (mb_strlen($TITLE) > 40)
    $TITLE = mb_substr($TITLE, 0, 40) . '...';

if (mb_strlen($DESC) > 150)
    $DESC = mb_substr($DESC, 0, 150) . '...';

if ($post->media)
    $IMG = $post->getUrl('image');

if (Auth::check())
    $canVote = canVote($post->uid, Auth::id())['ok'];

$single = 1;

if ($post->status != 0) {
    $votes = Vote::where('uid', $post->uid)->orderBy('created_at')->get();
}

?>

@extends('layouts.rabbit')

@section('title', $TITLE)
@section('desc', $DESC)
@section('img', $IMG)

@section('head')
    <script src="/assets/js/review.js"></script>
    <meta name="uid" content="{{ $post->uid }}">
@stop

@section('content')

@include('includes.post')

@isset ($votes)

@include('includes.votes')

@if (Auth::check() && in_array($post->status, [1, 2, 3, 10]))
    <button id="refresh" class="ts primary button" onclick="updateVotes('{{ $post->uid }}');">重新整理</button>
@endif

@endisset

@stop
