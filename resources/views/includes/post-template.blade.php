<template id="post-template">
    <div class="ts card" id="post-XXXX" style="margin-bottom: 42px;">
        <div class="image">
            <img id="img" class="post-image" style="max-height: 40vh; width: auto; cursor: zoom-in;"/>
        </div>
        <div class="content">
            <div class="header"><a id="hashtag">#{{ env('HASHTAG') }}0000</a></div>
            <div id="body"></div>
        </div>
        <div class="extra content">
            <div class="right floated author">
                <img class="ts circular avatar image" id="author-photo"> <span id="author-name">Sean</span>
                <br><span class="right floated" id="ip-outer">(<span id="ip-inner">140.113.***.*87</span>)</span>
            </div>
            <p style="margin-top: 0; line-height: 1.7em">
                <span>審核狀況：<button class="ts vote positive button">通過</button>
                    &nbsp;<span id="approvals">42</span>&nbsp;票 /&nbsp;<button class="ts vote negative button">
                        駁回</button>&nbsp;<span id="rejects">6</span>&nbsp;票</span><br>
                <span>投稿時間：<time id="time">06 月 04 日 02:40</time></span>
            </p>
        </div>
    </div>
</template>
