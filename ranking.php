<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

$user = $_GET['id'] ?? $_SESSION['nctu_id'] ?? '0816146';
$dep = idToDep($user);

$USER = $db->getUserByNctu($user);

$after = strtotime("7 day ago 00:00");
$before = strtotime("today 24:00");

$VOTES = $db->getVotesByTime($after, $before);

$data = [
	'title' => "{$dep} {$USER['name']} 的審核紀錄",
	'columns' => [
		['x'],
		['y0'],
		['y1'],
	],
	'types' => [
		'y0' => 'bar',
		'y1' => 'bar',
		'x' => 'x'
	],
	'names' => [
		'y0' => '通過',
		'y1' => '駁回',
	],
	'colors' => [
		'y0' => '#7FA45F',
		'y1' => '#B85052',
	],
	'hidden' => [],
	'subchart' => [
		'show' => true,
		'defaultZoom' => [
			strtotime("1 day ago") * 1000,
			time() * 1000
		]
	],
	'strokeWidth' => 2,
	'xTickFormatter' => 'statsFormat("hour")',
	'xTooltipFormatter' => 'statsFormat("hour")',
	'xRangeFormatter' => 'null',
	'yTooltipFormatter' => 'statsFormatTooltipValue',
	'stacked' => true,
	'sideLegend' => 'statsNeedSideLegend()',
	'tooltipOnHover' => true,
];

for ($i=$after; $i<=$before; $i+=6*60*60) {
	$data['columns'][0][] = $i*1000;
	$data['columns'][1][] = 0;
	$data['columns'][2][] = 0;
}

foreach ($VOTES as $vote) {
	if ($vote['voter'] != $user)
		continue;

	$ts = strtotime($vote['created_at']);
	$time = 1 + floor(($ts-$after)/6/60/60);
	$data['columns'][ $vote['vote'] == 1 ? 1 : 2 ][$time]++;
}
?>

<script src="/assets/js/tchart.js"></script>
<script src="/assets/js/health.js"></script>
<link href="/assets/css/tchart.css" rel="stylesheet">

<div id="chart_wrap" style="min-height: 450px;"></div>
<script>
data = <?= json_encode($data); ?>;
renderGraph('chart_wrap', data, true);
</script>

<?php
$ids = ['0816146', '0516034', '0816132', '0510831', '0711239', '0719803', '0751919', '0653415', '0510150', '0816160'];
foreach ($ids as $i => $id) {
$dep = idToDep($id);
?>
	<p><?= $i+1 ?>. <a href="?id=<?= $id ?>"><?= "$dep $id" ?></a></p>
<?php } ?>
