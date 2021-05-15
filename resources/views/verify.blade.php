<?php
use App\Models\GoogleAccount;

/**
 * @var GoogleAccount $google
 * @var string|null $stuid
 */


$gname = "{$google->name} ({$google->email})";
?>
@extends('layouts.master')

@section('title', '驗證交清身份')

@section('head')
<script src="/assets/js/verify.js"></script>
@stop

@section('content')
@if (empty($stuid))
<!--
    <h2 class="ts header">清大信箱驗證</h2>
    <p>為確認學生身份，請輸入您的學號，驗證信將寄送至 <b>s<span id="mail-stuid">108062000</span>@m<span id="mail-year">108</span>.nthu.edu.tw</b> 信箱。</p>
    <form id="send-verify" class="ts form" action="/api/verify" method="POST">
        <div class="required inline field">
            <label>學號</label>
            <div class="two wide">
                <input name="stuid" id="stuid" placeholder="108062000" />
            </div>
        </div>
        <input id="submit" type="submit" class="ts button" value="發送驗證信" />
    </form>
    <p>寄出驗證信後，請開啟 <a id="mail-url" target="_blank" href="https://m108-mail.nthu.edu.tw/">https://m108-mail.nthu.edu.tw/</a> 信箱收驗證碼，如未收到麻煩檢查垃圾信件，重寄後三分鐘仍未收到請聯絡維護團隊。</p>
-->

    <h2 class="ts header">如果你是交大生...</h2>
    <p>請先 <a href="/login/nctu">點我綁定 NCTU OAuth</a> 帳號</p>

    <h2 class="ts header">選錯帳號？</h2>
    <form action="/logout" method="POST">
        @csrf
        <p>請 <a onclick="this.parentNode.parentNode.submit();">點我登出</a> <u>{{ $gname }}</u> 帳號</p>
    </form>
@else
    <h2 class="ts header">清大信箱驗證</h2>
    <p>請確認是否將 <u>{{ $gname }}</u> 綁定至學號 <u>{{ $stuid }}</u>？以後您可以用此 Google 帳號登入{{ env('APP_CHINESE_NAEM') }}。</p>
    <div class="ts buttons">
        <button class="ts positive button" onclick="confirmVerify();">確認</button>
        <button class="ts negative button" onclick="location.href = '/';">取消</button>
    </div>
@endif
@stop
