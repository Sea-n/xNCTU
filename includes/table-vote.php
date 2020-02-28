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
foreach ($VOTES as $i => $vote) {
	$type = $vote['vote'] == 1 ? '✅ 通過' : '❌ 駁回';
	$id = $vote['voter'];
	$user = $db->getUserByNctu($id);
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
<?php } ?>
	</tbody>
</table>
