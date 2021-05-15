<?php
$ip_addr = Request::ip();
$ip_from = ip_from($ip_addr);

$ip_masked = $ip_addr;
if (strpos($ip_from, '境外') === false)
    $ip_masked = ip_mask($ip_masked);

$captcha_q = "請輸入「交大ㄓㄨˊㄏㄨˊ」（四個字）";
$captcha_a = "";
if (Auth::check() || $ip_from == '交大')
    $captcha_a = "交大竹湖";
?>

@extends('layouts.master')

@section('title', '文章投稿')

@section('head')
    <script src="/assets/js/submit.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('content')
    <div id="rule">
        <h2>投稿規則</h2>
        <ol>
            <li>攻擊性投稿內容不能含有姓名、暱稱等可能洩漏對方身分的資料，請把關鍵字自行碼掉。
                <ol><li>登入後具名投稿者，不受此條文之限制。</li></ol></li>
            <li>在不影響整體內容的前提下，審核團隊有權對投稿進行微調，包括但不限於&nbsp;<a href="https://github.com/vinta/pangu.js/#readme">中英文空格</a>、<a href="https://github.com/sparanoid/chinese-copywriting-guidelines#readme">排版格式</a>、錯別字、偏激措辭。</li>
            <li>如果對文章感到不舒服，請&nbsp;<a href="mailto:{{ env('MAIL_FROM_ADDRESS') }}">來信審核團隊</a>，如有合理理由將協助刪文。</li>
        </ol>
    </div>

    <br>
    <div id="submit-section">
        <h2>立即投稿</h2>
@if (Auth::check())
        <div id="warning-name" class="ts warning message">
            <div class="header">注意：您目前為登入狀態</div>
            <p>一但送出投稿後，所有人都能看到您（{{ Auth::user()->name }}）具名投稿，如想匿名投稿請於下方勾選「匿名投稿」。</p>
        </div>
@endif
        <div id="warning-ip" class="ts info message" style="{{ Auth::check() ? 'display: none;' : '' }}">
            <div class="header">注意</div>
            <p>一但送出投稿後，所有人都能看到您的網路服務商（{{ $ip_from }}），已登入的交大人能看見您的部分 IP 位址 ({{ $ip_masked }}) 。</p>
        </div>
        <form id ="submit-post" class="ts form" action="/submit" method="POST" enctype="multipart/form-data">
            <div id="body-field" class="required resizable field">
                <label>貼文內容</label>
                <textarea id="body-area" name="body" rows="6" placeholder="請在這輸入您的投稿內容。"></textarea>
                <span>目前字數：<span id="body-wc">0</span></span>
            </div>
            <div id="warning-preview" class="ts negative segment" style="display: none;">
                <p>Tips: 請將網址單獨寫在最後一行時，系統才會自動顯示頁面預覽。第一行不可為網址。</p>
            </div>
            <div class="inline field">
                <label>附加圖片</label>
                <div class="two wide"><input type="file" id="img" name="img" accept="image/png, image/jpeg" style="display: inline-block;" /></div>
                <div class="ts spaced bordered fluid image" style="display: none !important;">
                    <div style="width: 100%; height: 100%; z-index: 1; position: absolute; display: flex; align-items: center; justify-content: center; text-align: center;">
                        <p style="transform: rotate(-30deg);text-align: center; font-size: 8vw;opacity: 0.6;">Preview</p>
                    </div>
                    <img id="img-preview" />
                </div>
            </div>
            <div id="captcha-field" class="required inline field">
                <label>驗證問答</label>
                <div class="two wide"><input id="captcha-input" name="captcha" data-len="4" placeholder="{{ $captcha_a }}" value="{{ $captcha_a }}"/></div>
                <span>&nbsp; {{ $captcha_q }}</span>
            </div>
            <div id="field" class="inline field" style="{{ Auth::check() ? '' : 'display: none;' }}">
                <label for="anon">匿名投稿</label>
                <div class="ts toggle checkbox">
                    <input id="anon" type="checkbox" onchange="changeAnon();">
                    <label for="anon"></label>
                </div>
            </div>
            <input id="submit" type="submit" class="ts disabled button" value="提交貼文" />
        </form>
    </div>
@stop
