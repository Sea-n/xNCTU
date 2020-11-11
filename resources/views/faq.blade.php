@extends('layouts.master')

@section('title', '常見問答')

@section('head')

@section('content')
    <p>下面列出了幾個關於此服務的問題，如有疏漏可聯絡開發團隊，將儘快答覆您。</p>

    <div class="faq-anchor" id="modify-name"></div><h2 class="ts header">Q：如何更改暱稱</h2>
    <p>目前此功能僅實作於 Telegram bot 中，請點擊首頁下方按鈕連結 Telegram 帳號。</p>
    <p>於 Telegram 使用 /name 指令即可更改您的暱稱，所有過往的投稿、投票也會一起修正。</p>

    <div class="faq-anchor" id="modify-dep"></div><h2 class="ts header">Q：如何更改科系</h2>
    <p>目前系級判斷是從學號來的，如果您曾經轉系、希望顯示新的科系，麻煩透過 mail 與開發團隊聯絡。</p>

    <div class="faq-anchor" id="length-limit"></div><h2 class="ts header">Q：字數上限是多少</h2>
    <p>純文字投稿的字數上限為 3,600 字、附圖投稿為 870 字。</p>
    <p>遊走字數上限發文時請注意，最好在發出前自行備份，避免因伺服器判斷誤差造成投稿失敗。</p>

    <div class="faq-anchor" id="link-preview"></div><h2 class="ts header">Q：怎麼在 Facebook 貼文顯示連結預覽</h2>
    <p>請將連結獨立放在投稿的最後一行文字，系統將會自動為您產生預覽。</p>
    <p>另外，如果是 Facebook 貼文連結的話，因為臉書的限制無法自動產生預覽，將由維護團隊手動補上。</p>

    <div class="faq-anchor" id="post-schedule"></div><h2 class="ts header">Q：投稿什麼時候會發出</h2>
    <p>通過審核之文章將會進入發文佇列，由系統<b>每 5 分鐘</b> po 出一篇至各大社群平台，如欲搶先看也可申請加入審核團隊。</p>
    <p>所謂無人 <button class="ts vote negative button">駁回</button> 門檻意指 <button class="ts vote positive button">通過</button> - <button class="ts vote negative button">駁回</button> * 2，以降低個人誤觸影響。</p>

    <div class="faq-anchor" id="deleted-submissions"></div><h2 class="ts header">Q：被駁回的機制是什麼</h2>
    <p>當投稿被多數人駁回，或是放了很久卻達不到通過標準，就會被系統自動清理。</p>
    <p>詳細判斷標準如下：</p>
    <ul>
        <li>1 小時以內：達到 5 個&nbsp;<button class="ts vote negative button">駁回</button></li>
        <li>1 小時至 12 小時：達到 3 個&nbsp;<button class="ts vote negative button">駁回</button></li>
        <li>12 小時以後：不論條件，全數回收</li>
    </ul>
    <p>使用境外 IP 位址發文者，達到 2 個 <button class="ts vote negative button">駁回</button> 即刪除。</p>

    <div class="faq-anchor" id="deleted-submissions"></div><h2 class="ts header">Q：可以去哪找到被黑箱的投稿</h2>
    <p>如果達到上述駁回條件，或是管理團隊覺得投稿不適合發出，就會放到 <a href="/deleted">已刪投稿</a> 頁面。</p>
    <p>目前此區域限制只有已登入交大帳號的使用者才能檢閱，可見篇數將依審文數量而定，預設會顯示最近 3 篇被駁回的投稿。</p>

    <div class="faq-anchor" id="apply-account"></div><h2 class="ts header">Q：怎麼註冊帳號</h2>
    <p>如果您是交大的學生、老師、校友，請直接點擊右上角 Login 使用 NCTU OAuth 登入，不需另外註冊即可使用。</p>
    <p>對於準交大生，{{ env('APP_CHINESE_NAME') }} 團隊特別提供帳號申請服務，不管您是百川、資工、電機特殊選才，還是碩班、博班正取生，只要將相關證明寄至維護團隊，即可幫您配發一個臨時帳號，正常使用此服務。</p>
    <p>但若您是友校學生、高三考生、其他親友，在這邊就只能跟您說聲抱歉了，目前不開放非交大使用者註冊，但您仍然可以正常投稿、瀏覽文章。</p>

    <div class="faq-anchor" id="migrate-stuid"></div><h2 class="ts header">Q：怎麼轉移帳號</h2>
    <p>對於直升研究所的交大生會拿到新的學號，您可以選擇如何利用兩個學號：</p>
    <ol>
        <li>不做任何轉移，保留舊學號綁定原帳號，將新學號作為全新的帳號使用</li>
        <li>通知維護團隊，將原帳號所有紀錄更正為新學號，將舊學號作為全新的帳號使用</li>
    </ol>
    <p>不論最後選擇哪個方案，您都會有兩個身份可以使用，在必要時可以手動投下第二張票。</p>

    <div class="faq-anchor" id="ip-mask"></div><h2 class="ts header">Q：隱藏 IP 位址的機制是什麼</h2>
    <p>所有已登入的交大人都看得到匿名發文者的部分 IP 位址，一方面知道幾篇文是同一個人發的可能性，另一方面又保留匿名性。</p>
    <p>對於大部分的位址，會使用 140.113.***.*87 (IPv4) 或 2001:288:4001:***:1234 (IPv6) 的格式，在無法追溯個人的前提下，盡可能提供最多資訊。</p>
    <p>其中 140.113.136.218 - 140.113.136.221 是<b>校內無線網路</b>的 IP 位址、140.113.0.229 是<b>交大 VPN</b> 的 IP 位址，一個人可以拿到多個 IP 位址、也會有非常多人拿到同一個位址。公開出來無法識別出個人，讓審核者們知道投稿者<b>不一定是交大師生</b>，並將套用 (C) 台灣 IP 位址規則。</p>
    <p>另外，對於境外投稿者將會揭露完整 IP 位址，供審核者們自行判斷意圖。</p>

    <div class="faq-anchor" id="rate-limit"></div><h2 class="ts header">Q：發文速率有限制嗎</h2>
    <p>{{ env('APP_CHINESE_NAME') }} 應該是所有人共有的，為避免淪為少數人的個板，目前針對匿名發文有限制發文速率。</p>
    <ul>
        <li>校內 IP 位址：每 10 分鐘最多 5 篇</li>
        <li>台灣 IP 位址：<b>每 3 小時最多 3 篇</b></li>
        <li>境外 IP 位址：每 12 小時最多 1 篇</li>
    </ul>
    <p>為減輕遭受濫用時的危害，匿名發文時全系統每 3 分鐘最多接受 5 篇投稿，如遇特殊事件無法發文時，請先點擊右上角 Login 後具名投稿。</p>
@stop
