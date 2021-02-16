@extends('layouts.rabbit')

@section('title', '首頁')

@section('head')
    <script src="/assets/js/index.js"></script>
@stop

@section('content')
    <h2 class="ts header">成立契機</h2>
    <p>2014 年初，原版 <a target="_blank" href="https://www.facebook.com/CowBeiNCTU">@CowBeiNCTU</a> 成立，當時有公正的審文標準、系統性的
        <a target="_blank" href="https://www.facebook.com/castnet.nctu/videos/2329809693899900/">交接傳承</a>，無奈於 2020 年 1
        月中剛突破十萬篇時，遭不明人士檢舉下架。</p>
    <p>隨後有人開設了 <a target="_blank" href="https://www.facebook.com/grumbleNCTU">@grumbleNCTU</a>
        頂替，但因更新不夠即時、審文標準不夠明確等問題遭到同學們詬病。</p>
    <p>2020 年 2 月中，由資工系學生推出{{ env('APP_CHINESE_NAME') }}，並做出了數項改變避免重蹈覆徹：</p>
    <ul>
        <li>為了維持更新頻率，將審核工作下放至全體師生，任何師生只要登入 NCTU OAuth 帳號即可自助審文；除緊急刪除明顯違法文章外，所有師生與管理者票票等值。</li>
        <li>為了達到審文透明化，雖然所有審核者皆為自由心證，未經過訓練也不強制遵從統一標準；但透過保留所有審核紀錄、被駁回的投稿皆提供全校師生檢視，增加審核標準的透明度。</li>
        <li>除了改善制度面，也參考&nbsp;<a target="_blank" href="https://www.facebook.com/init.kobeengineer/">靠北工程師</a>
            &nbsp;概念，提供不同社群平台支援。
        </li>
    </ul>
    <p>期望能以嶄新的姿態，透過不斷精進制度與架構，將偉大大學推向靠北生態系巔峰。</p>
    <p>此專案收錄於 <a target="_blank" href="https://awesome.nctu.app/">Awesome NCTU</a>
        網站，也可參考 <a target="_blank" href="https://x.nthu.io/">靠北清大 2.0</a> 系統。</p>

    <h3 class="ts header">坎坷歷程</h3>
    <p>作為交大學生的靠北平台，也吸引到了不少各校來的投稿，其中不乏遊走道德邊緣的貼文，從四月到十月共有
        5 篇貼文遭 Facebook 檢舉刪除，完整列表可參見 <a href="/transparency">透明度報告</a>。</p>
    <p>在 2020 年 11 月初，原粉專 <a href="https://www.facebook.com/xNCTU">@xNCTU</a> 遭到 Facebook 無預警下架，隨後開設
        <a href="https://www.facebook.com/xNCTU2.0">@xNCTU2.0</a> 並恢復熱門貼文，繼續提供大家屬於交大人的靠交。</p>

    <h2 class="ts header">社群平台</h2>
    <p>除了本站文章列表外，您可以在以下 5 個社群媒體平台追蹤{{ env('APP_CHINESE_NAME') }} 帳號。</p>
    <div class="icon-row">
        <a id="telegram-icon" class="ts link tiny rounded image" target="_blank"
           href="https://t.me/{{ env('TELEGRAM_USERNAME') }}">
            <img src="https://image.flaticon.com/icons/svg/2111/2111646.svg" alt="Telegram"></a>
        <a id="twitter-icon" class="ts link tiny rounded image" target="_blank"
           href="https://twitter.com/{{ env('TWITTER_USERNAME') }}">
            <img src="https://image.flaticon.com/icons/svg/124/124021.svg" alt="Twitter"></a>
        <a id="plurk-icon" class="ts link tiny rounded image" target="_blank"
           href="https://www.plurk.com/{{ env('PLURK_USERNAME') }}">
            <img src="https://image.flaticon.com/icons/svg/124/124026.svg" alt="Plurk"></a>
        <a id="facebook-icon" class="ts link tiny rounded image" target="_blank"
           href="https://www.facebook.com/{{ env('FACEBOOK_USERNAME') }}">
            <img src="https://image.flaticon.com/icons/svg/220/220200.svg" alt="Facebook"></a>
        <a id="instagram-icon" class="ts link tiny rounded image" target="_blank"
           href="https://www.instagram.com/{{ env('INSTAGRAM_USERNAME') }}">
            <img src="https://image.flaticon.com/icons/svg/2111/2111463.svg" alt="Instagram"></a>
    </div>

    <h2 class="ts header">審文機制</h2>
    <div id="review-content" style="height: 320px; overflow-y: hidden;">
        <p>新版{{ env('APP_CHINESE_NAME') }} 採自助式審文，所有交大師生皆可加入審核者的行列，以下是系統判斷標準</p>

        <h4>(A) 具名投稿</h4>
        <p>點擊右上角使用 NCTU OAuth 登入後，可用師生身份具名投稿，即使無人審核也會在 10 分鐘內自動發出，詳細判斷條件如下：</p>
        <ul>
            <li>10 分鐘以內：達到 3 個&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;且無&nbsp;<button class="ts vote negative button">駁回</button>
            </li>
            <li>10 分鐘以後：&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;不少於&nbsp;<button class="ts vote negative button">駁回</button>
            </li>
        </ul>

        <h4>(B) 交大 IP 位址</h4>
        <p>在宿舍、系館使用 113 位址投稿者，滿足以下條件即發出：</p>
        <ul>
            <li>10 分鐘以內：達到 5 個&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;且無&nbsp;<button class="ts vote negative button">駁回</button>
            </li>
            <li>10 分鐘至 1 小時：&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;比&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;多 3 個
            </li>
            <li>1 小時以後：&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;不少於&nbsp;<button class="ts vote negative button">駁回</button>
            </li>
        </ul>
        <div class="ts negative raised compact segment">
            <h5>例外狀況</h5>
            <p>為避免非法文章意外通過，每天 03:00 - 09:00 門檻提升為&nbsp;<button class="ts vote positive button">通過</button>
                需比&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;多 3 個
            </p>
        </div>

        <h4>(C) 使用台灣 IP 位址</h4>
        <p>熱門投稿會快速登上版面，審核者們也有足夠時間找出惡意投稿，滿足以下條件即發出：</p>
        <ul>
            <li>10 分鐘以內：達到 7 個&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;且無&nbsp;<button class="ts vote negative button">駁回</button>
            </li>
            <li>10 分鐘至 1 小時：&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;比&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;多 5 個
            </li>
            <li>1 小時以後：&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;比&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;多 3 個
            </li>
        </ul>

        <h4>(D) 境外 IP 位址</h4>
        <p>保留境外發文是為了提供更安心的靠北管道，並不鼓勵發日常廢文使用。因此設下較為嚴格的審核標準，除非通過群眾一致認同有其必要，否則一概不予發出。</p>
        <ul>
            <li>1 小時以內：&nbsp;<button class="ts vote positive button">通過</button>
                &nbsp;比&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;多 10 個
            </li>
            <li>1 小時以後：投稿掰掰</li>
        </ul>

    </div>
    <div id="hide-box">
        <big onclick="more();" style="cursor: pointer; color: black;">展開完整規則 <i class="dropdown icon"></i></big>
    </div>

    <div class="ts horizontal divider">現在開始</div>
    <div class="ts fluid stackable buttons"><a class="ts massive positive button" href="/submit">我要投稿</a>
        <a class="ts massive info button" href="/review">我想審核</a></div>

    @if (Auth::guest())
    <h2 class="ts header">使用 Telegram 登入</h2>
    <p>只要您曾綁定 NCTU 帳號，點擊下面按鈕即可以 Telegram 登入服務。</p>
    <script async src="https://telegram.org/js/telegram-widget.js?7" data-telegram-login="{{ env('APP_NAME') }}bot"
            data-size="large" data-auth-url="https://{{ $app['url']->to('/') }}/login/tg"
            data-request-access="write"></script>
    @elseif (!Auth::user()->tg_name)
    <h2 class="ts header">使用 Telegram 快速審核</h2>
    <p>點擊下面按鈕即可綁定 Telegram 帳號，讓您收到最即時的投稿通知，並快速通過/駁回貼文。</p>
    <script async src="https://telegram.org/js/telegram-widget.js?7" data-telegram-login="{{ env('APP_NAME') }}bot"
            data-size="large" data-auth-url="https://{{ $app['url']->to('/') }}/login/tg"
            data-request-access="write"></script>
    @elseif (Auth::user()->name == Auth::user()->stuid)
    <h2 class="ts header">使用 Telegram 快速審核</h2>
    <div class="ts positive message">
        <div class="header">您已連結成功！</div>
        <p>Tips: 使用 /name 指令即可修改您的暱稱</p>
    </div>
    @endif

    <h2 class="ts header">排行榜</h2>
    <p>排名積分會依時間遠近調整權重，正確的駁回 <a href="/deleted">已刪投稿</a> 將大幅提升排名。</p>
    <p>您可以在 <a href="/ranking">這個頁面</a> 查看排行榜。</p>

    <h2 class="ts header">服務聲明</h2>
    <p>感謝您使用「{{ env('APP_CHINESE_NAME') }}」（以下簡稱本網站），本網站之所有文章皆為不特定使用者自行投稿、不特定師生進行審核，並不代表本網站立場。</p>
    <p>如有侵害您權益之貼文，麻煩寄信至服務團隊，將在最短時間協助您撤下貼文或進行澄清。</p>
    <p>投稿者如散播不實訊息而遭司法單位追究，在司法機關提供調取票等充分條件下，本網站將依法提供 IP 位址配合偵辦，並公開於 <a href="/transparency">透明度報告</a> 頁面，切勿以身試法。</p>
@stop
