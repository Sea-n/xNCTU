<?php
use App\Models\GoogleAccount;
?>
<nav class="ts basic fluid borderless menu horizontally scrollable">
    <div class="ts container">
        <a class="@if (Request::is('/')) active @endif item" href="/">首頁</a>
        <a class="@if (Request::is('/submit')) active @endif item" href="/submit">投稿</a>
        <a class="@if (Request::is('/review')) active @endif item" href="/review">審核</a>
        <a class="@if (Request::is('/posts')) active @endif item" href="/posts">文章</a>

        <div class="right fitted item" id="nav-right">
            @if (Auth::check())
                @empty(Auth::user()->tg_photo)
                    <img class="ts circular related avatar image" src="{{ genPic(Auth::user()->stuid) }}"
                         onerror="this.src='/assets/img/avatar.jpg';">
                @else
                    <img class="ts circular related avatar image" src="/avatar/tg/{{ Auth::user()->tg_id }}-x64.jpg"
                         onerror="this.src='/assets/img/avatar.jpg';">
                @endisset

                &nbsp;<b id="nav-name" style="overflow: hidden;">{{ Auth::user()->name }}</b>&nbsp;
                <form method="POST" action="/logout">
                    @csrf
                    <a class="item" href="#" data-type="logout" type="submit" onclick="this.parentNode.submit(); return false;">
                        <i class="log out icon"></i>
                        <span class="tablet or large device only">Logout</span>
                    </a>
                </form>
            @elseif (session()->has('google_sub'))
                <?php $google = GoogleAccount::find(session()->get('google_sub')); ?>
                <img class="ts circular related avatar image" src="{{ $google->avatar ?? genPic($google->sub) }}"
                     onerror="this.src='/assets/img/avatar.jpg';">
                &nbsp;<b id="nav-name" style="overflow: hidden;">Guest</b>&nbsp;
                <a class="item" href="/verify" data-type="login">Verify</a>
            @else

                <a class="item" href="/login" data-type="login"
                   onclick="document.getElementById('login-wrapper').style.display = ''; return false;">Login</a>
            @endif
        </div>
    </div>
</nav>

<div class="login-wrapper" id="login-wrapper" style="display: none;">
    <div class="login-background" onclick="this.parentNode.style.display = 'none';"></div>
    <div class="login-inner">
        <dialog class="ts fullscreen modal" open>
            <div class="header">
                {{ env('APP_CHINESE_NAME') }} 登入
            </div>
            <div class="content">
                <div style="display: inline-flex; width: 100%; justify-content: space-around;">
                    <a href="/login/nctu">
                        <img class="logo" src="/assets/img/login-nctu.png">
                    </a>
                    <a href="/login/google">
                        <img class="logo" src="/assets/img/login-google.png">
                    </a>
                    <a href="https://t.me/{{ env('APP_NAME') }}bot?start=login">
                        <img class="logo" src="/assets/img/login-telegram.png">
                    </a>
                </div>
            </div>
        </dialog>
    </div>
</div>
