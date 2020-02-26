<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

$after = strtotime("14 day ago 24:00");
$before = strtotime("today 24:00");

$VOTES = $db->getVotesByTime($after, $before);

$user_count = [];
foreach ($VOTES as $item) {
	if (!isset($user_count[ $item['voter'] ])) {
		$voter = $db->getUserByNctu($item['voter']);
		$user_count[ $item['voter'] ] = [
			1 => 0,
			-1 => 0,
			'voter' => $voter
		];
	}

	$user_count[ $item['voter']  ][ $item['vote'] ]++;
}
$user_count = array_filter($user_count, function($item) {
#	if ($item[1] + $item[-1] < 10)
#		return false;

#	if ($item[1] >= $item[-1] * 5)
#		return false;

	if ($item[-1] >= $item[1] * 3)
		return false;

	return true;
});
foreach($user_count as $k => $v) {
	$total = $v[1] + $v[-1];
	$min = min($v[1], $v[-1]);
	$max = max($v[1], $v[-1]);
	$a = $total + min($min, $max/2)*3;
	$user_count[$k][0] = $a;
}

usort($user_count, function($A, $B) {
	if ($A[0] == $B[0])
		return $A['voter']['created_at'] > $B['voter']['created_at'];

	return $A[0] < $B[0];
});

$user_count = array_slice($user_count, 0, 10);
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '審核者排行榜';
include('includes/head.php');
?>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">

				<h1 class="ts header">審核者排行榜</h1>
				<div class="description">靠北交大 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
			<table class="ts table">
				<thead>
					<tr>
						<th>#</th>
						<th>系級</th>
						<th></th>
						<th>暱稱</th>
						<th>✅ 通過</th>
						<th>❌ 駁回</th>
						<th>註冊日期</th>
					</tr>
				</thead>
				<tbody>
<?php
foreach ($user_count as $i => $item) {
	$emoji = ['🥇', '🥈', '🥉'];
	$no = $emoji[$i] ?? ($i+1);
	$id = $item['voter']['nctu_id'];
	$dep = idToDep($id);
	$name = toHTML($item['voter']['name']);
	$reg = date('Y 年 m 月 d 日', strtotime($item['voter']['created_at']));
?>
					<tr>
						<td><?= $no ?></td>
						<td><?= $dep ?></td>
						<td><img class="ts circular avatar image" src="<?= $item['voter']['tg_photo'] ?? '' ?>" onerror="this.src='/assets/img/avatar.jpg';"></td>
						<td><a href="?id=<?= $id ?>#chart_wrap"><?= $name ?></a></td>
						<td><?= $item[1] ?></td>
						<td><?= $item[-1] ?></td>
						<td><?= $reg ?></td>
					</tr>
<?php } ?>
				</tbody>
			</table>
			<hr>

			<script src="/assets/js/tchart.js"></script>
			<script src="/assets/js/health.js"></script>
			<link href="/assets/css/tchart.css" rel="stylesheet">
			<div id="chart_wrap" class="unstyled" style="min-height: 450px;"></div>
<?php
	$id = $_GET['id'] ?? '';
	if (!empty($id)) {
		$dep = idToDep($id);
		$USER = $db->getUserByNctu($id);
		$name = "{$dep} {$USER['name']}";
	} else
		$name = '所有人';

$data = [
	'title' => "{$name} 的審核紀錄",
	'columns' => [
		['x'],
		['y0'],
		['y1'],
	],
	'subchart' => [
		'show' => true,
		'defaultZoom' => [
			strtotime("7 day ago") * 1000,
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

	$STEP = 6*60*60;
	if (empty($id))
		$STEP = 60*60;

for ($i=$after; $i<=$before; $i+=$STEP) {
	$data['columns'][0][] = $i*1000;
	$data['columns'][1][] = 0;
	$data['columns'][2][] = 0;
}

foreach ($VOTES as $vote) {
	if (!empty($id) && $vote['voter'] != $id)
		continue;

	$ts = strtotime($vote['created_at']);
	$time = 1 + floor(($ts-$after)/$STEP);
	$data['columns'][ $vote['vote'] == 1 ? 1 : 2 ][$time]++;
}
?>
			<script>
				data = <?= json_encode($data); ?>;
				renderGraph('chart_wrap', data, true);
			</script>
<?php include('includes/footer.php'); ?>
		</div>
	</body>
</html>
