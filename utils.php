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
		$ip4[2] = '***';
		$ip4[3] = '*' . ($ip4[3]%100);
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
	$ip6 = "{$ip6[0]}:{$ip6[1]}:${ip6[2]}:***:{$ip6[7]}";
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
	$text = str_replace("\n", "\n<br>", $text);
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

function idToDep(string $id): string {
	$TABLE = [ "00000"=>"BEGIN",
		"10501"=>"光電系", "10701"=>"電機系", "11001"=>"機械系", "11201"=>"土木系", "11501"=>"材料系", "11601"=>"奈米學士班", "12001"=>"電物系", "12201"=>"應數系", "12501"=>"應化系", "13101"=>"管科系", "13201"=>"運物系", "13301"=>"工管系", "13401"=>"資財系", "15301"=>"人社系", "15401"=>"傳科系", "16001"=>"資工系", "17001"=>"生科系", "19001"=>"外文系", "19801"=>"百川",
		"44001"=>"台中一中", "44101"=>"百川", "44121"=>"選讀",
		"50101"=>"電子碩", "50501"=>"光電碩", "50701"=>"電機碩", "51001"=>"機械碩", "51201"=>"土木碩", "51501"=>"材料碩", "51601"=>"材料系奈米碩", "51701"=>"環工碩", "51801"=>"加速器學程碩", "51901"=>"機器人碩士", "52001"=>"電物碩", "52201"=>"應數碩", "52301"=>"應數碩", "52401"=>"應化系分子碩", "52451"=>"跨分子科碩", "52501"=>"應化碩", "52601"=>"統計所碩", "52701"=>"物理所碩", "52801"=>"理院專數位組", "52901"=>"理院專應科組", "53001"=>"企管學程", "53101"=>"管科碩", "53201"=>"物管碩", "53301"=>"工管碩", "53401"=>"資管碩", "53501"=>"科管碩", "53601"=>"運物系交通碩", "53701"=>"經管碩", "53801"=>"科法碩", "53901"=>"資財系財金碩", "54001"=>"半導體碩", "54101"=>"越南境外碩",
		"55201"=>"族文碩", "55401"=>"傳科系", "55501"=>"客家專班", "56001"=>"資科工碩", "56401"=>"國防資安專", "56501"=>"網工碩", "56601"=>"多媒體所碩", "56701"=>"數據碩", "56801"=>"資訊專", "57001"=>"生科碩", "57101"=>"分醫所碩", "57201"=>"生資所碩", "58001"=>"光電學程碩", "58101"=>"照明學程碩", "58201"=>"影像學程碩", "58301"=>"光電科技專", "59001"=>"外語碩", "59101"=>"傳播碩", "59201"=>"應藝碩", "59301"=>"音樂碩", "59501"=>"建築碩", "59601"=>"教育碩", "59701"=>"社文碩", "59801"=>"英教碩", "59901"=>"亞際文化碩",
		"60001"=>"電控碩", "60201"=>"電信碩", "60401"=>"生工碩", "60501"=>"電機專", "60801"=>"電機碩", "60901"=>"人工智慧", "61001"=>"產安專", "61101"=>"精密專", "61201"=>"工程專", "61301"=>"半導體專班", "61501"=>"環科專", "63001"=>"EMBA", "63101"=>"管科專", "63301"=>"工管專", "63401"=>"資管專", "63501"=>"科管專", "63601"=>"物流專", "63701"=>"經管專", "63801"=>"科法碩專", "63901"=>"財金專", "66001"=>"資電亥客",
		"80001"=>"電控博", "80101"=>"電子博", "80201"=>"電信博", "80301"=>"電機博", "80501"=>"光電博", "80681"=>"光電博士學程", "80701"=>"電機博", "80801"=>"電機博", "81001"=>"機械博", "81201"=>"土木博", "81501"=>"材料博", "81601"=>"材料系奈米博", "81701"=>"環工博", "81751"=>"環境博學", "81801"=>"加速器學程博", "82001"=>"電物博", "82201"=>"應數博", "82401"=>"應化系分子博", "82481"=>"永續化學博", "82501"=>"應化博", "82601"=>"統計所博", "82701"=>"物理所博",
		"83101"=>"管科博", "83201"=>"運物博", "83301"=>"工管博", "83401"=>"資管博", "83501"=>"科管博", "83701"=>"經管博", "83801"=>"科法博", "83901"=>"資財系財金博", "84001"=>"半導體博", "85001"=>"客家博", "86001"=>"資科工博", "86901"=>"資訊博", "87001"=>"生科博", "87101"=>"分醫所博", "87201"=>"生資所博", "87301"=>"生醫科工博", "87401"=>"跨神經科", "87501"=>"生科產業博", "88401"=>"綠能博士", "88501"=>"光電博", "89201"=>"應藝博", "89601"=>"教育博", "89701"=>"社文博",
		"99999"=>"END"];

	if (!preg_match('#^\d{7}$#', $id))
		return "非學生 $id";

	$idB = substr($id, 2);
	foreach ($TABLE as $s => $n) {
		if ($idB > $s)
			$dep = $n;
	}

	$deg = 4 + (int)(($id[0] > '3' ? '0' : '1') . $id[0] . $id[1]);

	return "$dep $deg 級 $id";
}
