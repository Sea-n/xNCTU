<?php
require_once('config.php');
$TITLE = '透明度報告';
$IMG = "https://$DOMAIN/assets/img/og.png";
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
				<h1 class="ts header"><?= $TITLE ?></h1>
				<div class="description"><?= SITENAME ?></div>
			</div>
		</header>
		<div class="ts container" name="main">
			<p>秉持公開透明原則，除了 <a href="/deleted">已刪投稿</a> 保留完整審核紀錄外，如本站收到來自司法單位、校方、同學、個人的內容移除請求，也將定期於此頁面公開。</p>

			<h2>來自 Facebook 的刪除紀錄</h2>
			<table class="ts striped table">
				<thead>
					<tr>
						<th>日期</th>
						<th>貼文編號</th>
						<th>內容節錄</th>
						<th>理由</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>2020 May 25</td>
						<td><a href="https://x.nctu.app/post/1870" target="_blank">#靠交1870</a></td>
						<td>中國香港</td>
						<td>仇恨言論</td>
					</tr>
					<tr>
						<td>2020 May 19</td>
						<td><a href="https://x.nctu.app/post/1780" target="_blank">#靠交1780</a></td>
						<td>男友生日希望找男性做愛</td>
						<td>性交易</td>
					</tr>
					<tr>
						<td>2020 Apr 21</td>
						<td>
							<a href="https://x.nctu.app/post/1158" target="_blank">#靠交1158</a>、
							<a href="https://x.nctu.app/post/1159" target="_blank">#靠交1159</a>
						</td>
						<td>台女的一生、台男的一生</td>
						<td>仇恨言論</td>
					</tr>
				</tbody>
			</table>

			<h2>維護團隊主動刪除</h2>
			<table class="ts striped table">
				<thead>
					<tr>
						<th>日期</th>
						<th>貼文編號</th>
						<th>內容節錄</th>
						<th>理由</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>2020 Jun 22</td>
						<td><a href="https://x.nctu.app/post/2583" target="_blank">#靠交2583</a></td>
						<td>陳菊高雄彈劾案</td>
						<td>不實訊息</td>
					</tr>
				</tbody>
			</table>

			<h2>請求刪除紀錄</h2>
			<p>校方定義不限於正式信函通知，包含各處室、教職員工；此處同學僅計算交大在學學生，他校學生列入個人計算。此表格不包含各社群平台檢舉下架貼文。</p>
			<table class="ts striped table">
				<thead>
					<tr>
						<th>月份</th>
						<th>校方請求數</th>
						<th>同學請求數</th>
						<th>個人請求數</th>
						<th>實際受理貼文數</th>
					</tr>
				</thead>
				<tbody>
					<tr><td>2020 Jul</td><td>0</td><td>0</td><td>0</td><td>0</td></tr>
					<tr class="negative indicated"><td>2020 Jun</td><td>0</td><td>1</td><td>1</td><td>0</td></tr>
					<tr><td>2020 May</td><td>0</td><td>0</td><td>0</td><td>0</td></tr>
					<tr class="negative indicated"><td>2020 Apr</td><td>0</td><td>1</td><td>1</td><td>0</td></tr>
					<tr><td>2020 Mar</td><td>0</td><td>0</td><td>0</td><td>0</td></tr>
					<tr><td>2020 Feb</td><td>0</td><td>0</td><td>0</td><td>0</td></tr>
				</tbody>
			</table>

			<br>
			<p>收到任何刪除通知將人工更新至此頁面，在不造成二次傷害的前提下，本站會盡可能提供最多資訊，原則上收到請求後會在 7 天內公開揭露。</p>
			<p style="text-align: right;"><i>最後更新日期：2020 Jul 16</i></p>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
