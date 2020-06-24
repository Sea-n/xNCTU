<?php
session_start(['read_and_close' => true]);
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

$CACHE = '/temp/xnctu-ranking.html';

$TITLE = '排行榜';
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php include('includes/head.php'); ?>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">

				<h1 class="ts header">排行榜</h1>
				<div class="description"><?= SITENAME ?></div>
			</div>
		</header>
		<div class="ts container" name="main">
			<p>排名積分會依時間遠近調整權重，24 小時內權重最高，而後每七天積分減半。</p>
			<p>正確的駁回 <a href="/deleted">已刪投稿</a> 將得到 10 倍分數。</a>
			<p>連續投票天數顯示最高連續天數，以台灣時間換日線為基準。如目前仍未中斷則標記 ⚡️ 符號。</p>
			<p>點擊名字可將頁尾圖表切換為個人投票記錄。</p>

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
<?php
/* Show cached page and exit */
include($CACHE);
fastcgi_finish_request();

/* Only update cache if expired */
if (time() - filemtime($CACHE) < 30)
	exit;


$time_start = microtime(true);
ob_start();

$DELS = $db->getDeletedSubmissions(0);
$DEL = array_map(function ($item) {
	return $item['uid'];
}, $DELS);

$VOTES = $db->getVotes();

$user_count = [];
$vote_sum = [1=>0, -1=>0];
foreach ($VOTES as $item) {
	if (!isset($user_count[ $item['stuid'] ])) {
		$user_count[ $item['stuid'] ] = [
			1 => 0, -1 => 0,
			'pt' => 0,
			'id' => $item['stuid'],
		];
	}

	$user_count[ $item['stuid'] ][ $item['vote'] ]++;
	$vote_sum[ $item['vote'] ]++;

	/* After 1 day, half the score every week */
	$dt = time() - strtotime($item['created_at']);
	$dt = $dt / 24 / 60 / 60;
	$dt = max($dt-1, 0);
	$pt = pow(0.5, $dt/7);

	if (in_array($item['uid'], $DEL)) {
		if ($item['vote'] == 1)
			$pt = 0;
		else
			$pt *= 10;
	}

	$user_count[ $item['stuid'] ]['pt'] += $pt;
}

foreach($user_count as $k => $v) {
	$user = $db->getUserByStuid($v['id']);

	if (!isset($user['tg_name']))
		$user_count[$k]['pt'] *= 0.8;

	if (!isset($user['tg_photo']))
		$user_count[$k]['pt'] *= 0.8;

	if ($user['name'] == $user['stuid'])
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
	$user_count[$k]['pt_int'] = (int) ($user_count[$k]['pt'] * 1000.0 / $pt_max);
}

$user_count = array_slice($user_count, 0, $end);
?>
				<tbody>
<?php
foreach ($user_count as $i => $item) {
	$emoji = ['🥇', '🥈', '🥉'];
	$no = $emoji[$i] ?? ($i+1);
	$id = $item['id'];
	$dep = idToDep($id);
	$name = toHTML($item['user']['name']);
	if (!empty($item['user']['tg_photo']))
		$photo = "/img/tg/{$item['user']['tg_id']}-x64.jpg";
	else
		$photo = genPic($id);

	$lv = strtotime($item['user']['last_vote']);
	$streak = $item['user']['highest_vote_streak'];
	if ($item['user']['current_vote_streak'] == $item['user']['highest_vote_streak']
	&& (date('Ymd') == date('Ymd', $lv) || date('Ymd') == date('Ymd', $lv-24*60*60)))
		$streak = "⚡️ {$streak} 天";
	else
		$streak = "⬜️ {$streak} 天";
?>
					<tr title="<?= $item['pt_int'] ?> pt (<?= round($item['pt'], 1) ?>)">
						<td><?= $no ?></td>
						<td><?= $dep ?></td>
						<td><img class="ts circular avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';"></td>
						<td><a onclick="changeChart('<?= $i ?>')"><?= $name ?></a></td>
						<td><?= $item[1] ?></td>
						<td><?= $item[-1] ?></td>
						<td><?= $streak ?></td>
					</tr>
<?php } ?>
					<tr>
						<td>*</td>
						<td>ALL</td>
						<td><img class="ts circular avatar image" src="/assets/img/logo-64.png"></td>
						<td><a onclick="changeChart('ALL')">沒有人</a></td>
						<td><?= $vote_sum[1] ?></td>
						<td><?= $vote_sum[-1] ?></td>
						<td>-</td>
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
				var data = {};
				data['ALL'] = <?= json_encode(genData('')) ?>;
<?php foreach ($user_count as $i => $item) { ?>
				data['<?= $i ?>'] = <?= json_encode(genData($item['user']['stuid'])) ?>;
<?php } ?>

				var d = JSON.parse(JSON.stringify(data['ALL']));  // Deep copy
				renderGraph('chart_wrap', d, true);

				function changeChart(id) {
					document.getElementById('chart_wrap').innerHTML = '';
					var d = JSON.parse(JSON.stringify(data[id]));  // Deep copy
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
		</div>
<?php
include('includes/footer.php');
$time_end = microtime(true);
$dt = ($time_end - $time_start) * 1000.0;
$dt = number_format($dt, 2, '.', '');
?>
		<!-- Page generated in <?= $dt ?>ms  (<?= date('Y-m-d H:i:s') ?>) -->
	</body>
</html>


<?php
/* Save to cache file */
$htmlStr = ob_get_contents();
ob_end_clean();
file_put_contents($CACHE, $htmlStr);

function genData(string $id) {
	global $db, $VOTES;

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
				strtotime("today 24:00") * 1000
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

	if (!empty($id)) {
		$dep = idToDep($id);
		$USER = $db->getUserByStuid($id);
		$name = "{$dep} {$USER['name']}";
		$step = 6*60*60;
	} else {
		$name = '所有人';
		$step = 2*60*60;
		$data['subchart']['defaultZoom'][0] = strtotime("7 days ago") * 1000;
	}

	$data['title'] = $name;
	$begin = strtotime("2020-02-21 00:00");
	if (!empty($id))
		$begin = strtotime(explode(' ', $USER['created_at'], 2)[0] . " 00:00");
	$end = strtotime("today 24:00");

	for ($i=$begin; $i<=$end; $i+=$step) {
		$data['columns'][0][] = $i*1000;
		$data['columns'][1][] = 0;
		$data['columns'][2][] = 0;
	}

	foreach ($VOTES as $vote) {
		if (!empty($id) && $vote['stuid'] != $id)
			continue;

		$ts = strtotime($vote['created_at']);
		$y = $vote['vote'] == 1 ? 1 : 2;
		$time = 1 + floor(($ts-$begin)/$step);
		$data['columns'][$y][$time]++;
	}

	return $data;
}
