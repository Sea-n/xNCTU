<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
session_start(['read_and_close' => true]);
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

$CACHE = '/temp/xnctu-ranking.html';

$TITLE = '排行榜';
include('includes/head.php');
?>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">

				<h1 class="ts header">排行榜</h1>
				<div class="description">靠北交大 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
			<p>為鼓勵用心審文，避免全部通過/全部駁回，排名基本計算公式為： 總投票數 + min(少數票, 多數票/4)</p>
			<p>意即「&nbsp;<button class="ts vote positive button">通過</button>&nbsp;40 票」與「&nbsp;<button class="ts vote positive button">通過</button>&nbsp;20 票 +&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;5 票」的排名相同</p>
			<p>得到積分會再依時間遠近調整權重，短期內大量通過/駁回皆會影響排名，詳細計算方式可參見此頁面原始碼</p>

			<table class="ts table">
				<thead>
					<tr>
						<th>#</th>
						<th>系級</th>
						<th></th>
						<th>暱稱</th>
						<th>✅ 通過</th>
						<th>❌ 駁回</th>
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

$VOTES = $db->getVotes();

$user_count = [];
$vote_sum = [1=>0, -1=>0];
foreach ($VOTES as $item) {
	if (!isset($user_count[ $item['voter'] ])) {
		$user_count[ $item['voter'] ] = [
			1 => 0, -1 => 0,
			2 => 0, -2 => 0,
			3 => 0, -3 => 0,
			4 => 0, -4 => 0,
			5 => 0, -5 => 0,
			'id' => $item['voter']
		];
	}

	$user_count[ $item['voter'] ][ $item['vote'] ]++;
	$vote_sum[ $item['vote'] ]++;

	if (time() - strtotime($item['created_at']) < 7*24*60*60)
		$user_count[ $item['voter'] ][ $item['vote'] * 2 ]++;
	if (time() - strtotime($item['created_at']) < 30*24*60*60)
		$user_count[ $item['voter'] ][ $item['vote'] * 3 ]++;
}

foreach($user_count as $k => $v) {
	$pt = 0;
	$TABLE = [
		2 => 4,  // within  7 days
		3 => 2,  // within 30 days
		1 => 1,  // all the time
	];

	foreach ($TABLE as $i => $weight) {
		$total = $v[$i] + $v[-$i];
		$min = min($v[$i], $v[-$i]);
		$max = max($v[$i], $v[-$i]);
		$pt += ($total + min($min, $max/4)) * $weight;
	}

	$user_count[$k]['pt'] = $pt;
}

usort($user_count, function($A, $B) {
	return $A['pt'] < $B['pt'];
});
$pt_max = $user_count[0]['pt'];

$user_count = array_slice($user_count, 0, 50);

foreach($user_count as $k => $v) {
	$user = $db->getUserByNctu($v['id']);
	$user_count[$k]['user'] = $user;
	$user_count[$k]['pt_int'] = (int) ($user_count[$k]['pt'] * 1000.0 / $pt_max);
}
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
?>
					<tr title="<?= $item['pt_int'] ?> pt">
						<td><?= $no ?></td>
						<td><?= $dep ?></td>
						<td><img class="ts circular avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';"></td>
						<td><a onclick="changeChart('<?= $i ?>')"><?= $name ?></a></td>
						<td><?= $item[1] ?></td>
						<td><?= $item[-1] ?></td>
					</tr>
<?php } ?>
					<tr>
						<td>*</td>
						<td>ALL</td>
						<td><img class="ts circular avatar image" src="/assets/img/logo-64.png"></td>
						<td><a onclick="changeChart('ALL')">沒有人</a></td>
						<td><?= $vote_sum[1] ?></td>
						<td><?= $vote_sum[-1] ?></td>
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
				data['<?= $i ?>'] = <?= json_encode(genData($item['user']['nctu_id'])) ?>;
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
				strtotime("14 days ago") * 1000,
				strtotime("now") * 1000
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
		$USER = $db->getUserByNctu($id);
		$name = "{$dep} {$USER['name']}";
		$step = 6*60*60;
	} else {
		$name = '所有人';
		$step = 60*60;
		$data['subchart']['defaultZoom'][0] = strtotime("3 days ago") * 1000;
	}

	$data['title'] = $name;
	$begin = strtotime("2020-02-21 00:00");
	$end = strtotime("today 24:00");

	for ($i=$begin; $i<=$end; $i+=$step) {
		$data['columns'][0][] = $i*1000;
		$data['columns'][1][] = 0;
		$data['columns'][2][] = 0;
	}

	foreach ($VOTES as $vote) {
		if (!empty($id) && $vote['voter'] != $id)
			continue;

		$ts = strtotime($vote['created_at']);
		$y = $vote['vote'] == 1 ? 1 : 2;
		$time = 1 + floor(($ts-$begin)/$step);
		$data['columns'][$y][$time]++;
	}

	return $data;
}
