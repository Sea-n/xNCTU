@extends('layouts.master')

@section('title', '排行榜')

@section('head')

@section('content')
<?php
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;

$time_start = microtime(true);

$CACHE = storage_path() . '/app/cache/ranking-all.html';
$dir = dirname($CACHE);
if (!file_exists($dir))
    mkdir($dir);

if (time() - filemtime($CACHE) > 5 * 0) {
    ob_start();

    $DEL = Post::where('status', '<', 0)->pluck('uid')->toArray();

    $VOTES = Vote::all();

    $user_count = [];
    $vote_sum = [1=>0, -1=>0];
    foreach ($VOTES as $item) {
        if (!isset($user_count[ $item->stuid ])) {
            $user_count[ $item->stuid ] = [
                1 => 0, -1 => 0,
                'pt' => 0,
                'id' => $item->stuid,
            ];
        }

        $user_count[ $item->stuid ][ $item->vote ]++;
        $vote_sum[ $item->vote ]++;

        /* After 1 day, half the score every week */
        $dt = time() - strtotime($item->created_at);
        $dt = $dt / 24 / 60 / 60;
        $dt = max($dt-1, 0);
        $pt = pow(0.5, $dt/7);

        if (in_array($item->uid, $DEL)) {
            if ($item->vote == 1)
                $pt = 0;
            else
                $pt *= 10;
        }

        $user_count[ $item->stuid ]['pt'] += $pt;
    }

    $time_end = microtime(true); $dt = ($time_end - $time_start) * 1000.0; $dt = number_format($dt, 2, '.', ''); echo '<!-- ' . __LINE__ . ": in {$dt}ms -->\n";

    foreach($user_count as $k => $v) {
        $user = User::find($v['id']);

        if (!isset($user->tg_name))
            $user_count[$k]['pt'] *= 0.8;

        if (!isset($user->tg_photo))
            $user_count[$k]['pt'] *= 0.8;

        if ($user->name == $user->stuid)
            $user_count[$k]['pt'] *= 0.8;

        $user_count[$k]['user'] = $user;
    }

    usort($user_count, function($A, $B) {
        return $A['pt'] < $B['pt'];
    });

    $pt_max = $user_count[0]['pt'];
    foreach($user_count as $k => $v) {
        if ($k > 0 && $k%5 == 0 && $user_count[$k]['pt'] < 5) {
            $end = $k;
            break;
        }
        $user_count[$k]['pt_pc'] = round($user_count[$k]['pt'] * 100.0 / $pt_max, 1);
    }

    $user_count = array_slice($user_count, 0, $end);
?>
    <p>排名積分會依時間遠近調整權重，24 小時內權重最高，而後每七天積分減半，正確的駁回 <a href="/deleted">已刪投稿</a> 將得到 10 倍分數。</a>
    <p>連續投票天數以台灣時間 24:00 為計算基準，如當日已投票、仍未中斷將標記 ⚡️ 符號。</p>
    <p>游標移至每列將顯示各別積分，點擊名字可將頁尾圖表切換為個人投票記錄。</p>

    <table class="ts table">
        <thead>
            <tr>
                <th>#</th>
                <th>系級</th>
                <th></th>
                <th>暱稱</th>
                <th>✅ 通過</th>
                <th>❌ 駁回</th>
                <th>🚀 連續投票</th>
            </tr>
        </thead>
        <tbody>
<?php
    $smx = 0;
    foreach ($user_count as $i => $item) {
        $emoji = ['🥇', '🥈', '🥉'];
        if (isset($item['user']->tg_photo))
            $photo = "/img/tg/{$item['user']->tg_id}-x64.jpg";
        else
            $photo = genPic($item['id']);

        $lv = strtotime($item['user']->last_vote);
        $sc = $item['user']->current_vote_streak;
        $sh = $item['user']->highest_vote_streak;
        $smx = max($smx, $sh);

        if (date('Ymd') == date('Ymd', $lv))
            $streak = "$sc 天 ⚡️";  // Currently streak
        else if (date('Ymd') == date('Ymd', $lv + 24*60*60))
            $streak = "$sc 天";  // Not voted today
        else
            $streak = "<sub>最高 $sh 天</sub>";

        if ($streak[-1] != ">" && $sc != $sh)
            $streak .= "<sub> / 最高 $sh 天</sub>";
?>
					<tr title="{{ round($item['pt'], 1) }} pt ({{ $item['pt_pc'] }}%)">
						<td>{{ $emoji[$i] ?? ($i+1) }}</td>
						<td>{{ idToDep($item['id']) }}</td>
						<td><img class="ts circular avatar image" src="{{ $photo }}" onerror="this.src='/assets/img/avatar.jpg';"></td>
						<td><a>{{ $item['user']->name }}</a></td>
						<td>{{ $item[1] }}</td>
						<td>{{ $item[-1] }}</td>
						<td>{!! $streak !!}</td>
					</tr>
<?php } ?>
					<tr>
						<td>*</td>
						<td>ALL</td>
						<td><img class="ts circular avatar image" src="/assets/img/logo-64.png"></td>
						<td><a onclick="changeChart('ALL')">沒有人</a></td>
						<td>{{ $vote_sum[1] }}</td>
						<td>{{ $vote_sum[-1] }}</td>
						<td><sub>總共 {{ $smx }} 天</sub></td>
					</tr>
				</tbody>
			</table>
		</div>
<?php
    $time_end = microtime(true);
    $dt = ($time_end - $time_start) * 1000.0;
    $dt = number_format($dt, 2, '.', '');

    $htmlStr = ob_get_contents();
    ob_end_clean();
    file_put_contents($CACHE, $htmlStr);
?>
<!-- Page generated in {{ $dt }}ms  ({{ date('Y-m-d H:i:s') }}) -->
<?php
}
include($CACHE);
?>
@stop
