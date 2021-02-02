<?php
use App\Models\User;
?>
<table class="ts votes table" id="votes">
    <thead>
        <tr>
            <th>#</th>
            <th></th>
            <th>系級</th>
            <th>暱稱</th>
            <th>理由</th>
        </tr>
    </thead>
    <tbody>
@if (Auth::check() || in_array($post->uid, ['DEMO', '2C8j']))
@foreach ($votes as $i => $vote)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $vote->vote == 1 ? '✅ 通過' : '❌ 駁回' }}</td>
            <td>{{ $vote->user->dep() }}</td>
            <td>{{ $vote->user->name }}</td>
            <td>{!! x($vote->reason) !!}</td>
        </tr>
@endforeach
@else
        <tr>
            <td colspan="5">
                <div class="ts info message">
                    <div class="header">此區域僅限交大使用者查看</div>
                    <p>您可以打開 <a href="/review/DEMO">#投稿DEMO </a>，免登入即可預覽投票介面</p>
                </div>
            </td>
        </tr>
@endif
    </tbody>
</table>
