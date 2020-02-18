<?php
function check_cf_ip(string $addr): bool {
	$range = [
		"173.245.48.0" => 20,
		"103.21.244.0" => 22,
		"103.22.200.0" => 22,
		"103.31.4.0" => 22,
		"141.101.64.0" => 18,
		"108.162.192.0" => 18,
		"190.93.240.0" => 20,
		"188.114.96.0" => 20,
		"197.234.240.0" => 22,
		"198.41.128.0" => 17,
		"162.158.0.0" => 15,
		"104.16.0.0" => 12,
		"172.64.0.0" => 13,
		"131.0.72.0" => 22
	];

	foreach ($range as $base => $cidr)
		if (ip2long($addr)>>(32-$cidr)
		=== ip2long($base)>>(32-$cidr))
			return true;

	return false;
}

function ip_from(string $ip): string {
	if ($_SERVER["HTTP_CF_IPCOUNTRY"] != 'TW')
		return "境外, {$_SERVER["HTTP_CF_IPCOUNTRY"]}";

	$tanet = [
		'140.113.' => '交大',
		'140.112.' => '台大',
		'140.114.' => '清大',
		'140.115.' => '中央',
		'140.116.' => '成大',
		'140.118.' => '台科',
		'140.119.' => '政大',
		'140.121.' => '海大',
		'140.122.' => '師大',
		'140.124.' => '北科',
		'140.129.' => '陽明',
	];

	foreach ($tanet as $prefix => $name)
		if (substr($ip, 0, 8) == $prefix)
			return $name;

	$curl = curl_init('https://rms.twnic.net.tw/query_whois1.php');
	curl_setopt($curl, CURLOPT_POSTFIELDS, "q=$ip");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$resp = curl_exec($curl);
	if (preg_match('#<tr><td>Chinese Name</td><td>([^<]+?)(股份|有限|公司|台灣|分公司)*</td></tr>#', $resp, $matches))
		return $matches[1];
	else
		return "台灣";
}

function ip_mask(string $ip): string {
	if (strpos($ip, '.') !== false) { // IPv4
		$ip4 = explode('.', $ip);
		$ip4[2] = 'xxx';
		$ip4[3] = 'x' . ($ip4[3]%100);
		$ip4 = join('.', $ip4);
		return $ip4;
	}

	$ip6 = $ip;
	if (strpos($ip6, '::') !== false) {
		$missing = 7 - substr_count($ip6, ':');
		while ($missing--)
			$ip6 = str_replace('::', ':0::', $ip6);
		$ip6 = str_replace('::', ':0:', $ip6);
	}
	$ip6 = explode(':', $ip6);
	$ip6[7] = substr('00'.$ip6[7], 0, 2);
	$ip6 = "{$ip6[0]}:{$ip6[1]}:${ip6[2]}:___{$ip6[7]}";
	return $ip6;
}

function rand58(int $len = 1): string {
	$base58 = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

	$rand = '';
	for ($_=0; $_<$len; $_++)
		$rand .= $base58[rand(0, 57)];

	return $rand;
}

function toHTML(string $text): string {
	$text = htmlentities($text);
	return $text;
}

function humanTime(string $date): string {
	$ts = strtotime($date);
	$now = time();
	$dt = $now - $ts;

	$time = date("H:i", $ts);
	if ($dt <= 120)
		return "$time ($dt 秒前)";

	$dt = floor($dt / 60);
	if ($dt <= 90)
		return "$time ($dt 分鐘前)";

	$time = date("m 月 d 日 H:i", $ts);
	$dt = floor($dt / 60);
	if ($dt <= 48)
		return "$time ($dt 小時前)";

	$dt = floor($dt / 24);
	if ($dt <= 45)
		return "$time ($dt 天前)";

	$time = date("Y 年 m 月 d 日 H:i", $ts);

	$dt = floor($dt / 30);
	return "$time ($dt 個月前)";
}
