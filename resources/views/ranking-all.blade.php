@extends('layouts.master')

@section('title', '排行榜')

@section('head')
@endsection

@section('content')

    <?php
    use App\Models\Post;
    use App\Models\Vote;
    use Carbon\Carbon;

    $time_start = microtime(true);

    $CACHE = storage_path() . '/app/cache/ranking-all.html';
    $dir = dirname($CACHE);
    if (!file_exists($dir))
        mkdir($dir);

    if (!file_exists($CACHE) || time() - filemtime($CACHE) > 60 * 60) {
    ob_start();

    $del = Post::where('status', '<', 0)->pluck('uid')->toArray();

    $votes = Vote::where('created_at', '>', Carbon::today()->subMonths(6))->get();

    $user_count = [];
    $vote_sum = [1 => 0, -1 => 0];
    foreach ($votes as $item) {
        if (!isset($user_count[$item->stuid])) {
            $user_count[$item->stuid] = [
                1 => 0, -1 => 0,
                'pt' => 0,
                'user' => $item->user,
            ];
        }

        $user_count[$item->stuid][$item->vote]++;
        $vote_sum[$item->vote]++;

        /* After 1 day, half the score every week */
        $dt = time() - strtotime($item->created_at);
        $dt = $dt / 24 / 60 / 60;
        $dt = max($dt - 1, 0);
        $pt = pow(0.5, $dt / 7);

        if (in_array($item->uid, $del)) {
            if ($item->vote == 1)
                $pt = 0;
            else
                $pt *= 10;
        }

        $user_count[$item->stuid]['pt'] += $pt;
    }

    foreach ($user_count as $k => $v) {
        if (!$v['user']->tg_name)
            $user_count[$k]['pt'] *= 0.8;

        if (!$v['user']->tg_photo)
            $user_count[$k]['pt'] *= 0.8;

        if ($v['user']->name == $v['user']->stuid)
            $user_count[$k]['pt'] *= 0.8;

        $user_count[$k]['user'] = $v['user'];
    }

    usort($user_count, function ($A, $B) {
        return $A['pt'] < $B['pt'];
    });

    $pt_max = $user_count[0]['pt'];
    $end = 5;
    foreach ($user_count as $k => $v) {
        if ($k > 0 && $k % 5 == 0 && $user_count[$k]['pt'] < 20) {
            $end = $k;
            break;
        }
        $user_count[$k]['pt_pc'] = round($user_count[$k]['pt'] * 100.0 / $pt_max, 1);
    }

    $user_count = array_slice($user_count, 0, $end);
    $emoji = ['🥇', '🥈', '🥉'];
    $smx = 0;
    ?>
    <div id="rules">
        <p>本頁面顯示近 6 個月的投票數量，排名積分會依時間遠近調整權重，24 小時內權重最高，而後每七天積分減半，正確的駁回 <a href="/deleted">已刪投稿</a> 將得到 10 倍分數。</p>
        <p>連續投票天數以台灣時間 24:00 為計算基準，如當日已投票、仍未中斷將標記 ⚡️ 符號。</p>
        <p>積分 20 以上的使用者會出現於排行榜上，游標移至每列將顯示各別積分，點擊名字可將頁尾圖表切換為個人投票記錄。</p>
    </div>

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
        @foreach ($user_count as $i => $item)
            <?php
            if (file_exists(public_path("/avatar/tg/{$item['user']->tg_id}-x64.jpg")))
                $photo = "/avatar/tg/{$item['user']->tg_id}-x64.jpg";
            else
                $photo = genPic($item['user']->stuid);

            $lv = strtotime($item['user']->last_vote);
            $sc = $item['user']->current_vote_streak;
            $sh = $item['user']->highest_vote_streak;
            $smx = max($smx, $sh);

            if (date('Ymd') == date('Ymd', $lv))
                $streak = "$sc 天 ⚡️";  // Currently streak
            else if (date('Ymd') == date('Ymd', $lv + 24 * 60 * 60))
                $streak = "$sc 天";  // Not voted today
            else
                $streak = "<sub>最高 $sh 天</sub>";

            if ($streak[-1] != ">" && $sc != $sh)
                $streak .= "<sub> / 最高 $sh 天</sub>";
            ?>
            <tr title="{{ round($item['pt'], 1) }} pt ({{ $item['pt_pc'] }}%)">
                <td>{{ $emoji[$i] ?? ($i+1) }}</td>
                <td>{{ $item['user']->dep() }}</td>
                <td><img class="ts circular avatar image" src="{{ $photo }}"
                         onerror="this.src='/assets/img/avatar.jpg';"></td>
                <td><a onclick="changeChart('{{ $item['user']->tg_id ?? $item['user']->stuid }}')">{{ $item['user']->name }}</a></td>
                <td>{{ $item[1] }}</td>
                <td>{{ $item[-1] }}</td>
                <td>{!! $streak !!}</td>
            </tr>
        @endforeach
        <tr>
            <td>*</td>
            <td>ALL</td>
            <td><img class="ts circular avatar image" src="/assets/img/logo-64.png"></td>
            <td><a onclick="changeChart('ALL')">沒有人</a></td>
            <td>{{ $vote_sum[1] }}</td>
            <td>{{ $vote_sum[-1] }}</td>
            <td><sub>最高 {{ $smx }} 天</sub></td>
        </tr>
        </tbody>
    </table>

    <div id="chart_wrap" class="unstyled" style="min-height: 300px;"></div>

    <div class="ts snackbar">
        <div class="content"></div>
        <a class="action"></a>
    </div>

    <script src="/assets/js/tchart.min.js"></script>
    <script src="/assets/js/health.js"></script>
    <link href="/assets/css/tchart.css" rel="stylesheet">
    <script>
        const data = {'ALL': @json(genData())};
        const d = JSON.parse(JSON.stringify(data['ALL']));  // Deep copy
        renderGraph('chart_wrap', d, true);

        function changeChart(id) {
            if (!data[id]) {
                fetch('/api/ranking/' + id, {})
                    .then((resp) => resp.json())
                    .then((resp) => {
                        data[id] = resp;
                        changeChart(id);
                    });
                return;
            }

            document.getElementById('chart_wrap').innerHTML = '';
            const d = JSON.parse(JSON.stringify(data[id]));  // Deep copy
            renderGraph('chart_wrap', d, true);

            ts('.snackbar').snackbar({
                content: '已載入 ' + d['title'] + ' 的統計資料',
                action: '點我查看',
                actionEmphasis: 'info',
                onAction: () => {
                    location.href = '#chart_wrap';
                    setTimeout(() => {
                        history.pushState(null, null, location.pathname);
                    }, 1000);
                }
            });
        }
    </script>

    <?php
    $time_end = microtime(true);
    $dt = ($time_end - $time_start) * 1000.0;
    $dt = number_format($dt, 2, '.', '');
    ?>
    <!-- Page generated in {{ $dt }}ms  ({{ Carbon::now() }}) -->

    <?php
    $htmlStr = ob_get_contents();
    ob_end_clean();
    file_put_contents($CACHE, $htmlStr);
    }
    include($CACHE);
    ?>
@stop

<?php
function genData()
{
    $data = [
        'columns' => [
            ['x'],
            ['y0'],
            ['y1'],
        ],
        'subchart' => [
            'show' => true,
            'defaultZoom' => [
                strtotime("28 days ago") * 1000,
                strtotime(" 0 days ago") * 1000
            ]
        ],
        'types' => ['y0' => 'bar', 'y1' => 'bar', 'x' => 'x'],
        'names' => ['y0' => '通過', 'y1' => '駁回'],
        'colors' => ['y0' => '#7FA45F', 'y1' => '#B85052'],
        'hidden' => [],
        'strokeWidth' => 2,
        'xTickFormatter' => 'statsFormat("hour")',
        'xTooltipFormatter' => 'statsFormat("hour")',
        'xRangeFormatter' => 'null',
        'yTooltipFormatter' => 'statsFormatTooltipValue',
        'stacked' => true,
        'sideLegend' => 'statsNeedSideLegend()',
        'tooltipOnHover' => true,
    ];

    $data['title'] = '所有人';
    $begin = strtotime("6 months ago 00:00");
    $end = strtotime("today 24:00");
    $step = 2 * 60 * 60;

    for ($i = $begin; $i <= $end; $i += $step) {
        $data['columns'][0][] = $i * 1000;
        $data['columns'][1][] = 0;
        $data['columns'][2][] = 0;
    }

    $VOTES = Vote::where('created_at', '>', Carbon::today()->subMonths(6))->get();
    foreach ($VOTES as $vote) {
        $ts = strtotime($vote['created_at']);
        $y = $vote['vote'] == 1 ? 1 : 2;
        $time = 1 + floor(($ts - $begin) / $step);
        $data['columns'][$y][$time]++;
    }

    return $data;
}
?>
