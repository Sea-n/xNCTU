<?php
require_once('utils.php');
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
<?php
if (isset($USER) || ($uid ?? 'x') == 'DEMO' || ($uid ?? 'x') == '2C8j') {
	foreach ($VOTES as $i => $vote) {
		$type = $vote['vote'] == 1 ? '✅ 通過' : '❌ 駁回';
		$id = $vote['voter'];
		$user = $db->getUserByStuid($id);
		$dep = idToDep($id);
		$name = toHTML($user['name']);
?>
		<tr>
			<td><?= $i+1 ?></td>
			<td><?= $type ?></td>
			<td><?= $dep ?></td>
			<td><?= $name ?></td>
			<td><?= toHTML($vote['reason']) ?></td>
		</tr>
<?php } } else { ?>
		<tr>
			<td colspan="5">
				<div class="ts info message">
					<div class="header">此區域僅限交大使用者查看</div>
					<p>您可以打開 <a href="/review/DEMO">#投稿DEMO </a>，免登入即可預覽投票介面</p>
				</div>
			</td>
		</tr>
<?php } ?>
	</tbody>
</table>
