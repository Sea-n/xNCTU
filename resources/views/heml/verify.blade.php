<?php
use App\Model\GoogleAccount;

/**
 * @var GoogleAccount $google
 * @var string $verify_link
 * @var string $date
 * @var string $ip_addr
 * @var string $ip_from
 */
?>
<heml>
<head>
	<style>
		body {
			font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
			color: #333;
			line-height: 1.5;
		}

		.main {
			margin: auto;
			max-width: 600px;
		}

		a {
			color: #108ee9;
			text-decoration: none;
		}

		b {
			color: #000;
		}
	</style>
</head>

<body>
	<div class="main">
		<p>{{ $google->name }} 您好，</p>

		<p>感謝您註冊 <a href="{{ url('/') }}">{{ env('APP_CHINESE_NAME') }}</a>，請點擊下方連結啟用帳號：<br>
		<span style="font-size: 12px;"><a href="{!! $verify_link !!}">{{ $verify_link }}</a></span></p>

		<p>為了維持更新頻率，{{ env('APP_CHINESE_NAME') }} 將審核工作下放至全體師生，因此您的每一票對我們來說都相當重要。<br>
		雖然所有審核者皆為自由心證，未經過訓練也不強制遵從統一標準；但透過保留所有審核紀錄、被駁回的投稿皆提供全校師生檢視，增加審核標準的透明度。</p>

		<p>有了您的貢獻，期望能以嶄新的姿態，將{{ env('APP_CHINESE_NAME') }} 推向靠北生態系巔峰。</p>

		<p style="text-align: right;">{{ env('HASHTAG') }}維護團隊<br>{{ $date }}</p>

		<p style="text-align: center; font-size: 10px; color: #888;">
			由於 <a href="mailto:{{ $google->email }}">{{ $google->name }} &lt;{{ $google->email }}&gt;</a> 在{{ env('APP_CHINESE_NAME') }} 網站申請寄送驗證碼，因此寄發本信件給您。
			（來自「{{ $ip_from }}」，IP 位址為 <code>{{ $ip_addr }}</code>）
			如不是由您本人註冊，很可能是同學手滑打錯學號了，請不要點擊驗證按鈕以避免爭議。
			若是未來不想再收到相關信件，請來信 <a href="mailto:{{ env('MAIL_FROM_ADDRESS') }}">與我們聯絡</a>，將會盡快將您的學號放入拒收清單內。
		</p>
	</div>
</body>
</heml>
