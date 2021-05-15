<?php

use App\Jobs\ReviewVoted;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;

function ip_from(string $ip_addr): string
{
    /* Only convert Taiwan IP addresses */
    if (($_SERVER["HTTP_CF_IPCOUNTRY"] ?? 'xx') != 'TW'
        && strpos(php_sapi_name(), 'cli') === false)
        return '境外, ' . ccToZh($_SERVER["HTTP_CF_IPCOUNTRY"] ?? 'xx');

    /* Known IP address prefix for TANet */
    $tanet = [
        '120.126.102.' => '陽明',  # 120.126.64.0 - 120.126.111.255
        '140.109.' => '中研院',
        '140.110.' => '國網中心',
        '140.112.' => '台大',
        '140.113.' => '交大',
        '140.114.' => '清大',
        '140.115.' => '中央',
        '140.116.' => '成大',
        '140.117.' => '中山',
        '140.118.' => '台科',
        '140.119.' => '政大',
        '140.120.' => '中興',
        '140.121.' => '海大',
        '140.122.' => '師大',
        '140.123.' => '中正',
        '140.124.' => '北科',
        '140.129.' => '陽明',
        '140.130.118.' => '嘉義大學',
        '163.13.' => '淡江',
        '2001:f18:' => '交大',
        '2001:288:4001:' => '交大',
        '2001:288:e001:' => '清大',
        '2001:288:1001:' => '台大',
    ];

    foreach ($tanet as $prefix => $name)
        if (substr($ip_addr, 0, strlen($prefix)) == $prefix)
            return $name;


    /* Query TWNIC whois */
    $curl = curl_init('https://rms.twnic.tw/query_whois1.php');
    curl_setopt($curl, CURLOPT_POSTFIELDS, "q=$ip_addr");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($curl);

    if (preg_match('#<tr>\s*<td>Chinese Name</td>\s*<td>([^<]+?)(股份|有限|公司|台灣|分公司)*</td>\s*</tr>#', $resp, $matches)) {
        $name = $matches[1];
        /* Check TANet Whois */
        if ($name == '教育部') {
            $curl = curl_init("http://whois.tanet.edu.tw/showWhoisPublic.php?queryString=$ip_addr");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($curl);
            if (preg_match('#.*<tr><td>Chinese Name</td><td>(國立|私立)*([^<]+?)(大學|區網|中心)*</td></tr>#', $resp, $matches)) {
                $name = $matches[2];

                $name = str_replace("交通", "交大", $name);
                $name = str_replace("清華", "清大", $name);
                $name = str_replace("陽明交通", "陽交大", $name);

                return $name;
            }
        }

        $name = str_replace("台灣之星電信", "台灣之星", $name);
        $name = str_replace("台灣寬頻通訊顧問", "台灣寬頻通訊", $name);
        $name = str_replace("台灣碩網網路娛樂", "台灣碩網", $name);
        $name = str_replace("大新店民主有線電視", "大新店寬頻", $name);
        $name = str_replace("國家發展委員會", "國發會", $name);
        $name = str_replace("中央研究院資訊服務處", "中研院", $name);
        return $name;
    }

    /* IPv6 address only have English org name for unknown reason */
    if (preg_match('#<tr>\s*<td>Organization Name</td>\s*<td>([^<]+?)(\s*Co.,\s*Ltd.)*</td>\s*</tr>#i', $resp, $matches)) {
        $name = $matches[1];
        /* Check TANet Whois */
        if ($name == 'Ministry of Education Computer Center') {
            $curl = curl_init("http://whois.tanet.edu.tw/showWhoisPublic.php?queryString=$ip_addr");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($curl);
            if (preg_match('#.*<tr><td>Chinese Name</td><td>(國立|私立)*([^<]+?)(大學|區網|中心)*</td></tr>#', $resp, $matches)) {
                $name = $matches[2];
                return $name;
            }
        }

        $name = str_replace("Far EasTone Telecommunication", "遠傳電信", $name);
        $name = str_replace("Chunghwa Telecom", "中華電信", $name);
        $name = str_replace("ASIA PACIFIC TELECOM", "亞太電信", $name);
        $name = str_replace("Taiwan Mobile", "台灣大哥大", $name);
        $name = str_replace("TBC", "台灣寬頻通訊", $name);
        return $name;
    }

    /* Use PTR record then mapping by human */
    $ptr = gethostbyaddr($ip_addr);
    if ($ptr != $ip_addr) {
        $ptr_array = explode('.', $ptr);
        $ptr_array = array_slice($ptr_array, -3, 3);
        $ptr = join('.', $ptr_array);
        $ptr = str_replace("emome-ip6.hinet.net", "中華電信", $ptr);
        $ptr = str_replace("dynamic-ip.hinet.net", "中華電信", $ptr);
        $ptr = str_replace("dynamic-ip6.hinet.net", "中華電信", $ptr);
        $ptr = str_replace("dynamic.kbtelecom.net", "中嘉和網", $ptr);
        $ptr = str_replace("hyabd.com.tw", "中嘉和網", $ptr);
        $ptr = str_replace("static.kbtelecom.net", "中嘉和網", $ptr);
        return $ptr;
    }

    /* Unknown ISP */
    return "台灣";
}

function ccToZh(string $cc)
{
    $TABLE = [
        'AD' => '安道爾', 'AE' => '阿拉伯聯合大公國', 'AF' => '阿富汗', 'AG' => '安地卡及巴布達', 'AI' => '英屬安圭拉', 'AL' => '阿爾巴尼亞', 'AM' => '亞美尼亞', 'AN' => '荷屬安地列斯', 'AO' => '安哥拉', 'AQ' => '南極洲', 'AR' => '阿根廷', 'AS' => '美屬薩摩亞', 'AT' => '奧地利', 'AU' => '澳大利亞', 'AW' => '阿魯巴', 'AZ' => '亞塞拜然', 'BA' => '波士尼亞', 'BB' => '巴貝多', 'BD' => '孟加拉', 'BE' => '比利時', 'BF' => '布吉納法索', 'BG' => '保加利亞', 'BH' => '巴林', 'BI' => '蒲隆地', 'BJ' => '貝南', 'BM' => '百慕達', 'BN' => '汶萊', 'BO' => '玻利維亞', 'BR' => '巴西', 'BS' => '巴哈馬', 'BT' => '不丹', 'BV' => '波維特島', 'BW' => '波扎那', 'BY' => '白俄羅斯', 'BZ' => '貝里斯',
        'CA' => '加拿大', 'CC' => '可可斯群島', 'CD' => '薩伊', 'CF' => '中非共和國', 'CG' => '剛果', 'CH' => '瑞士', 'CI' => '象牙海岸', 'CK' => '科克群島', 'CL' => '智利', 'CM' => '喀麥隆', 'CN' => '中國', 'CO' => '哥倫比亞', 'CR' => '哥斯大黎加', 'CS' => '塞爾維亞與蒙特尼哥羅', 'CU' => '古巴', 'CV' => '佛德角', 'CX' => '聖誕島', 'CY' => '塞普路斯', 'CZ' => '捷克', 'DE' => '德國', 'DJ' => '吉布地', 'DK' => '丹麥', 'DM' => '多米尼克', 'DO' => '多明尼加', 'DZ' => '阿爾及利亞', 'EC' => '厄瓜多爾', 'EE' => '愛沙尼亞', 'EG' => '埃及', 'EH' => '西撒哈拉', 'ER' => '厄利垂亞', 'ES' => '西班牙', 'ET' => '衣索比亞', 'FI' => '芬蘭', 'FJ' => '斐濟', 'FK' => '福克蘭群島', 'FM' => '密克羅尼西亞', 'FO' => '法羅群島', 'FR' => '法國',
        'GA' => '加彭', 'GB' => '英國', 'GD' => '格瑞那達', 'GE' => '喬治亞', 'GF' => '法屬圭亞那', 'GG' => '根息島', 'GH' => '迦納', 'GI' => '直布羅陀', 'GL' => '格陵蘭', 'GM' => '甘比亞', 'GN' => '幾內亞', 'GP' => '瓜德魯普島', 'GQ' => '赤道幾內亞', 'GR' => '希臘', 'GT' => '瓜地馬拉', 'GU' => '關島', 'GW' => '幾內亞比索', 'GY' => '蓋亞那', 'HK' => '香港', 'HM' => '赫德及麥當勞群島', 'HN' => '宏都拉斯', 'HR' => '克羅埃西亞', 'HT' => '海地', 'HU' => '匈牙利', 'ID' => '印尼', 'IE' => '愛爾蘭', 'IL' => '以色列', 'IM' => '英屬曼島', 'IN' => '印度', 'IO' => '英屬印度洋地區', 'IQ' => '伊拉克', 'IR' => '伊朗', 'IS' => '冰島', 'IT' => '義大利', 'JE' => '澤西島', 'JM' => '牙買加', 'JO' => '約旦', 'JP' => '日本',
        'KE' => '肯亞', 'KG' => '吉爾吉斯', 'KH' => '高棉', 'KI' => '吉里巴斯', 'KM' => '葛摩', 'KN' => '聖克里斯多福', 'KP' => '北韓', 'KR' => '南韓', 'KW' => '科威特', 'KY' => '開曼群島', 'KZ' => '哈薩克', 'LA' => '寮國', 'LB' => '黎巴嫩', 'LC' => '聖露西亞', 'LI' => '列支敦斯堡', 'LK' => '斯里蘭卡', 'LR' => '賴比瑞亞', 'LS' => '賴索托', 'LT' => '立陶宛', 'LU' => '盧森堡', 'LV' => '拉脫維亞', 'LY' => '利比亞', 'MA' => '摩洛哥', 'MC' => '摩納哥', 'MD' => '摩爾多瓦', 'MG' => '馬達加斯加', 'MH' => '馬紹爾群島', 'MK' => '馬其頓', 'ML' => '馬利', 'MM' => '緬甸', 'MN' => '蒙古', 'MO' => '澳門', 'MP' => '北里亞納群島', 'MQ' => '法屬馬丁尼克', 'MR' => '茅利塔尼亞', 'MS' => '蒙瑟拉特島', 'MT' => '馬爾他', 'MU' => '模里西斯', 'MV' => '馬爾地夫', 'MW' => '馬拉威', 'MX' => '墨西哥', 'MY' => '馬來西亞', 'MZ' => '莫三鼻給',
        'NA' => '納米比亞', 'NC' => '新喀里多尼亞', 'NE' => '尼日', 'NF' => '諾福克群島', 'NG' => '奈及利亞', 'NI' => '尼加拉瓜', 'NL' => '荷蘭', 'NO' => '挪威', 'NP' => '尼伯爾', 'NR' => '諾魯', 'NU' => '紐威島', 'NZ' => '紐西蘭', 'OM' => '阿曼', 'PA' => '巴拿馬', 'PE' => '秘魯', 'PF' => '法屬玻里尼西亞', 'PG' => '巴布亞新幾內亞', 'PH' => '菲律賓', 'PK' => '巴基斯坦', 'PL' => '波蘭', 'PM' => '聖匹及密啟倫群島', 'PN' => '皮特康島', 'PR' => '波多黎各', 'PT' => '葡萄牙', 'PW' => '帛琉', 'PY' => '巴拉圭', 'PZ' => '巴拿馬運河區', 'QA' => '卡達', 'RE' => '留尼旺', 'RO' => '羅馬尼亞', 'RU' => '俄羅斯', 'RW' => '盧安達',
        'SA' => '沙烏地阿拉伯', 'SB' => '索羅門群島', 'SC' => '塞席爾', 'SD' => '蘇丹', 'SE' => '瑞典', 'SG' => '新加坡', 'SH' => '聖赫勒拿島', 'SI' => '斯洛凡尼亞', 'SJ' => '斯瓦巴及尖棉島', 'SK' => '斯洛伐克', 'SL' => '獅子山', 'SM' => '聖馬利諾', 'SN' => '塞內加爾', 'SO' => '索馬利亞', 'SR' => '蘇利南', 'ST' => '聖托馬-普林斯浦', 'SV' => '薩爾瓦多', 'SY' => '敘利亞', 'SZ' => '史瓦濟蘭', 'TC' => '土克斯及開科斯群島', 'TD' => '查德', 'TF' => '法屬南部屬地', 'TG' => '多哥', 'TH' => '泰國', 'TJ' => '塔吉克', 'TK' => '托克勞群島', 'TM' => '土庫曼', 'TN' => '突尼西亞', 'TO' => '東加', 'TP' => '帝汶', 'TR' => '土耳其', 'TT' => '千里達-托貝哥', 'TV' => '吐瓦魯', 'TW' => '台灣', 'TZ' => '坦尚尼亞',
        'UA' => '烏克蘭', 'UG' => '烏干達', 'UM' => '美屬邊疆群島', 'US' => '美國', 'UY' => '烏拉圭', 'UZ' => '烏玆別克', 'VA' => '教廷', 'VC' => '聖文森', 'VE' => '委內瑞拉', 'VG' => '英屬維爾京群島', 'VI' => '美屬維爾京群島', 'VN' => '越南', 'VU' => '萬那杜', 'WF' => '沃里斯及伏塔那島', 'WS' => '薩摩亞群島', 'YE' => '北葉門', 'YT' => '美亞特', 'ZA' => '南非共和國', 'ZM' => '尚比亞', 'ZW' => '辛巴威',
        'XX' => '未知', 'T1' => 'Tor',
    ];

    return $TABLE[$cc] ?? "{$cc} (未知)";
}

function ip_mask(string $ip_addr): string
{
    if (strpos($ip_addr, '.') !== false) { // IPv4
        if (preg_match('/^140\.113\.136\.2(09|1[0-9]|2[01])$/', $ip_addr))
            return $ip_addr;  // NCTU Wireless NAT

        if (preg_match('/^140\.113\.163\.2(3[89]|4[012])$/', $ip_addr))
            return $ip_addr;  // NCTU Wireless

        if (preg_match('/^140\.113\.0\.229$/', $ip_addr))
            return $ip_addr;  // NCTU VPN

        if (preg_match('/^140\.114\.25[23]/', $ip_addr))
            return $ip_addr;  // NTHU VPN

        $ip4 = explode('.', $ip_addr);
        $ip4[2] = '***';
        $ip4[3] = '*' . substr('0' . ($ip4[3] % 100), -2);
        $ip4 = join('.', $ip4);
        return $ip4;
    }

    $ip6 = $ip_addr;
    if (strpos($ip6, '::') !== false) {
        if (preg_match('/^2001:288:e001:253:/', $ip_addr))
            return $ip_addr;  // NTHU VPN

        $missing = 7 - substr_count($ip6, ':');
        while ($missing--)
            $ip6 = str_replace('::', ':0::', $ip6);
        $ip6 = str_replace('::', ':0:', $ip6);
        if ($ip6[-1] == ':')
            $ip6 .= '0';
    }
    $ip6 = explode(':', $ip6);
    $ip6 = "{$ip6[0]}:{$ip6[1]}:${ip6[2]}:***:{$ip6[7]}";
    return $ip6;
}

function ip_mask_anon(string $ip_addr): string
{
    if (strpos($ip_addr, '.') !== false) { // IPv4
        $ip4 = explode('.', $ip_addr);
        $ip4 = $ip4[0] . '.***.***.***';
        return $ip4;
    }

    $ip6 = $ip_addr;
    $ip6 = explode(':', $ip6);
    $ip6 = $ip6[0] . ':' . $ip6[1] . ':****:****';
    return $ip6;
}

function rand58(int $len = 1): string
{
    $base58 = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    $rand = '';
    for ($_ = 0; $_ < $len; $_++)
        $rand .= $base58[rand(0, 57)];

    return $rand;
}

/**
 * Convert plain text to HTML
 *
 * Supported:
 * 1. Newline to <br>
 * 2. Single line URL to <a>
 *
 * @param string $text
 * @return string
 */
function x(string $text = ''): string
{
    $text = htmlentities($text);
    $text = explode("\n", $text);
    foreach ($text as $k1 => $v1) {
        $text[$k1] = explode(' ', $v1);
        foreach ($text[$k1] as $k2 => $v2) {
            if (filter_var($v2, FILTER_VALIDATE_URL))
                $text[$k1][$k2] = "<a target='_blank' href='$v2'>$v2</a>";
            else if (preg_match('/^#靠交(\d+)$/', $v2, $matches))
                $text[$k1][$k2] = "<a target='_blank' href='https://x.nctu.app/post/{$matches[1]}'>$v2</a>";
            else if (preg_match('/^#靠清(\d+)$/', $v2, $matches))
                $text[$k1][$k2] = "<a target='_blank' href='https://x.nthu.io/post/{$matches[1]}'>$v2</a>";
            else if (preg_match('/^#告白交大(\d+)$/', $v2, $matches))
                $text[$k1][$k2] = "<a target='_blank' href='https://crush.nctu.app/post/{$matches[1]}'>$v2</a>";
            else if (preg_match('/^#投稿(\w+)$/', $v2, $matches))
                $text[$k1][$k2] = "<a target='_blank' href='/review/{$matches[1]}'>$v2</a>";
            else if (preg_match('/^#[^ ]+$/', $v2, $matches))
                $text[$k1][$k2] = "<a href='javascript:;'>$v2</a>";
        }
        $text[$k1] = join(" ", $text[$k1]);
    }
    $text = join("\n<br>", $text);
    return $text;
}

function enHTML(string $str = ''): string
{
    $search = array('&', '"', '<', '>');
    $replace = array('&amp;', '&quot;', '&lt;', '&gt;');
    $str = str_replace($search, $replace, $str);
    return $str;
}

function humanTime(string $date): string
{
    $ts = strtotime($date);
    $now = time();
    $dt = $now - $ts;

    $time = date("H:i", $ts);
    if ($dt <= 120)
        return "$time ($dt 秒前)";

    $dt = floor($now / 60) - floor($ts / 60);
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

function idToDep(string $id): string
{
    if (preg_match('#^[A-Z][A-Z0-9][0-9]{3}$#', $id))
        return '教職員';

    if (preg_match('#^\d{7}$#', $id))
        return idToDepNCTU($id);

    if (preg_match('#^\d{9}$#', $id))
        return idToDepNYCU($id);

    return "非學生 $id";
}

function idToDepNCTU(string $id): string
{
    $TABLE = ["00000" => "BEGIN", "10001" => "未知",
        "10001" => "電資學士班", "10101" => "電工系", "10501" => "光電系", "10701" => "電機系", "11001" => "機械系", "11201" => "土木系", "11501" => "材料系", "11601" => "奈米學士班", "12001" => "電物系", "12201" => "應數系", "12501" => "應化系", "13101" => "管科系", "13201" => "運物系", "13301" => "工管系", "13401" => "資財系", "15301" => "人社系", "15401" => "傳科系", "16001" => "資工系", "17001" => "生科系", "19001" => "外文系", "19801" => "百川",
        "20000" => "未知",
        "30000" => "未知",
        "40000" => "選讀", "44001" => "台中一中", "44101" => "百川", "44121" => "選讀",
        "50000" => "碩士", "50101" => "電子碩", "50501" => "光電碩", "50701" => "電機碩", "51001" => "機械碩", "51201" => "土木碩", "51501" => "材料碩", "51601" => "奈米碩", "51701" => "環工碩", "51801" => "加速器學程碩", "51901" => "機器人碩士", "52001" => "電物碩", "52201" => "應數碩", "52301" => "應數碩", "52401" => "分子碩", "52451" => "跨分子科碩", "52501" => "應化碩", "52601" => "統計所碩", "52701" => "物理所碩", "52801" => "理院專", "52901" => "理院專", "53001" => "企管學程", "53101" => "管科碩", "53201" => "物管碩", "53301" => "工管碩", "53401" => "資管碩", "53501" => "科管碩", "53601" => "交通運輸碩", "53701" => "經管碩", "53801" => "科法碩", "53901" => "財金碩", "54001" => "半導體碩", "54101" => "越南境外碩",
        "55201" => "族文碩", "55401" => "傳科系", "55501" => "客家專班", "56001" => "資科工碩", "56401" => "國防資安專", "56501" => "網工所", "56601" => "多媒體所", "56701" => "數據所", "56801" => "資訊專", "57001" => "生科碩", "57101" => "分醫所碩", "57201" => "生資碩", "58001" => "光電系統所", "58101" => "照能所", "58201" => "影生所", "58301" => "光電專", "59001" => "外語碩", "59101" => "傳播碩", "59201" => "應藝碩", "59301" => "音樂碩", "59501" => "建築碩", "59601" => "教育碩", "59701" => "社文碩", "59801" => "英教碩", "59901" => "亞際文化碩",
        "60001" => "電控碩", "60201" => "電信碩", "60401" => "生工碩", "60501" => "電機專", "60801" => "電機碩", "60901" => "人工智慧", "61001" => "產安專", "61101" => "精密專", "61201" => "工程專", "61301" => "半導體專班", "61501" => "環科專", "63001" => "EMBA", "63101" => "管科專", "63301" => "工管專", "63401" => "資管專", "63501" => "科管專", "63601" => "物流專", "63701" => "經管專", "63801" => "科法碩專", "63901" => "財金專", "66001" => "資電亥客",
        "70000" => "未知",
        "80001" => "電控博", "80101" => "電子博", "80201" => "電信博", "80301" => "電機博", "80501" => "光電博", "80681" => "光電博學程", "80701" => "電機博", "80801" => "電機博", "81001" => "機械博", "81201" => "土木博", "81501" => "材料博", "81601" => "奈米博", "81701" => "環工博", "81751" => "環境博學", "81801" => "加速器學程博", "82001" => "電物博", "82201" => "應數博", "82401" => "分子博", "82481" => "永續化學博", "82501" => "應化博", "82601" => "統計所博", "82701" => "物理所博",
        "83101" => "管科博", "83201" => "運物博", "83301" => "工管博", "83401" => "資管博", "83501" => "科管博", "83701" => "經管博", "83801" => "科法博", "83901" => "財金博", "84001" => "半導體博", "85001" => "客家博", "86001" => "資科工博", "86901" => "資訊博", "87001" => "生科博", "87101" => "分醫所博", "87201" => "生資所博", "87301" => "生醫科工博", "87401" => "跨神經科", "87501" => "生科產業博", "88401" => "綠能博士", "88501" => "光電博", "89201" => "應藝博", "89601" => "教育博", "89701" => "社文博",
        "90000" => "未知", "99999" => "END"];

    $idB = substr($id, 2);
    $dep = "未知";
    foreach ($TABLE as $s => $n) {
        if ($idB >= $s)
            $dep = $n;
    }

    /* 040 ~ 139 */
    $deg = (int)(($id[0] > '3' ? '0' : '1') . $id[0] . $id[1]);
    if ($id[2] == '5' || $id[2] == '6')
        $deg += 2;
    else if ($id[2] == '4')
        $deg += 1;
    else
        $deg += 4;

    /* Exception for changed department */
    if ($id == '0711239') $dep = '資工系';

    return "$dep $deg 級";
}

function idToDepNYCU(string $id): string
{
    $idA = $id[0];  // Degree(1): Bachelor, X, Master, Doctor, Part-time
    $idB = $id[1] . $id[2];  // Year(2)
    $idC = $id[3] . $id[4] . $id[5];  // College(1) and Department(2)
//    $idD = $id[6] . $id[7] . $id[8];  // Serial number(3)

    $TABLE = [
        "1-101" => "電機系",
        "1-483" => "傳科系",
        "1-511" => "電機系",
        "1-550" => "資工系",
        "1-610" => "奈米學士班", "1-611" => "機械系", "1-612" => "土木系", "1-613" => "材料系",
        "1-651" => "電物系", "1-652" => "應數系", "1-654" => "應化系",
        "1-700" => "管科系", "1-701" => "運物系", "1-704" => "工管系", "1-705" => "資財系",
        "1-950" => "百川",

        "3-510" => "電子碩", "3-511" => "電機碩", "3-512" => "電控碩", "3-513" => "電信碩", "3-514" => "光電碩",
        "3-551" => "資科工碩", "3-552" => "網工所", "3-553" => "多媒體所", "3-554" => "數據所", "3-555" => "資電亥客",
        "3-611" => "機械碩", "3-612" => "土木碩", "3-613" => "材料碩", "3-614" => "奈米碩",

        "5-606" => "產安專",
    ];

    $dep = $TABLE["$idA-$idC"] ?? '';

    if (empty($dep))
        return "未知 $id";

    $deg = (int)('1' . $idB);
    if ($idA == '3')
        $deg += 2;
    else
        $deg += 4;

    return "$dep $deg 級";
}

function idToDepNTHU(string $id): string
{
    $TABLE = [
        "0000" => "清華學院學士班", "0001" => "清華學院學士班", "0002" => "清華學院學士班", "0020" => "學科所", "0061" => "清華學院國際學士班", "0062" => "清華學院國際學士班",  # 跨院系所
        "0100" => "原科院學士班", "0111" => "工科系", "0112" => "工科系", "0120" => "醫環系",  # 原子科學院
        "0200" => "理學院學士班", "0211" => "數學系", "0212" => "數學系", "0215" => "數學碩", "0216" => "數學碩", "0218" => "數學博", "0221" => "物理系", "0222" => "物理系", "0225" => "物理所", "0230" => "化學系",  # 理學院
        "0300" => "工學院學士班", "0311" => "材料系", "0312" => "材料系", "0320" => "化工系", "0325" => "化工碩", "0328" => "化工博", "0331" => "動機系", "0332" => "動機系", "0335" => "動機碩", "0336" => "動機碩", "0338" => "動機博", "0340" => "工工系", "0345" => "工工所", "0346" => "工工所",  # 工學院
        "0410" => "中文系", "0415" => "中文所", "0418" => "中文碩", "0420" => "外文系", "0425" => "外文所", "0426" => "外文所", "0465" => "人類所", "0467" => "人類所", "0445" => "語言碩", "0481" => "人文社會學院學士班", "0482" => "人文社會學院學士班", "0495" => "台文所",  # 人文社會學院
        "0600" => "電資學士班", "0611" => "電機系", "0612" => "電機系", "0621" => "資工系", "0622" => "資工系", "0623" => "資工系",  # 電機資訊學院
        "0615" => "電機所", "0616" => "電機所", "0625" => "資工所", "0626" => "資工所", "0655" => "資應所",  # 電機資訊學院
        "0700" => "科管院學士班", "0710" => "計財系", "0715" => "計財所", "0716" => "計財所", "0721" => "經濟系", "0722" => "經濟系", "0745" => "科法所", "0746" => "科法所",  # 科技管理學院
        "0800" => "生科院學士班", "0805" => "生科所", "0806" => "生科所", "0810" => "生科系", "0820" => "醫科系",  # 生命科學院
        "0911" => "教科系", "0950" => "特教系", "1950" => "環文系",  # 竹師教育學院
        "5900" => "藝術院學士班", "5910" => "音樂學系", "音樂碩", "5931" => "藝設系", "5932" => "藝設系", "5935" => "藝設碩",  # 藝術學院
        "9999" => "UNKNOWN"];

    $idA = substr($id, 0, 3);
    $idB = substr($id, 3, 4);

    $dep = $TABLE[$idB] ?? '未知';

    /* 4 year + (100 ~ 199) + 1911 */
    $deg = (int)$idA;
    $deg = $deg - 100 + 11;
    if ($id[6] == '5' || $id[6] == '6')
        $deg += 2;
    else
        $deg += 4;

    return "$dep $deg 級";
}

function genPic(string $seed)
{
    $seed = preg_replace('/[^A-Za-z0-9]/', '_', $seed);
    $seed = preg_replace('/____+/', '___', $seed);
    $seed = preg_replace('/[^A-Za-z0-9]/', '_', $seed);
    $seed = preg_replace('/___+/', '__', $seed);
    $seed = substr($seed, 0, 42) . '';
    $seed = substr(md5($seed), 0, 6);
    $file = storage_path("app/avatar/{$seed}.jpg");

    $dir = dirname($file);
    if (!file_exists($dir))
        mkdir($dir);

    // render the picture:
    if (!file_exists($file)) {
        srand(hexdec($seed));

        // throw the dice for body parts
        $parts = [
            'body' => rand(1, 15),
            'fur' => rand(1, 10),
            'eyes' => rand(1, 15),
            'mouth' => rand(1, 10),
            'accessorie' => rand(1, 20),
        ];

        // restore random seed
        if ($seed) srand();


        // create background
        $cat = @imagecreatetruecolor(70, 70)
        or die("GD image create failed");
        $white = imagecolorallocate($cat, 255, 255, 255);
        imagefill($cat, 0, 0, $white);

        // add parts
        foreach ($parts as $part => $num) {
            $comp = resource_path("avatars/{$part}_{$num}.png");

            $im = @imagecreatefrompng($comp);
            if (!$im) die("Failed to load $comp.");
            imageSaveAlpha($im, true);
            imagecopy($cat, $im, 0, 0, 0, 0, 70, 70);
            imagedestroy($im);
        }

        imagejpeg($cat, $file, 87);
        imagedestroy($cat);
    }

    return "/avatar/$seed.jpg";
}

/**
 *  Check can user vote for certain submission or not
 *
 * @param string $uid
 * @param string $stuid
 * @return array   ok and error msg
 */
function canVote(string $uid, string $stuid): array
{
    $post = Post::findOrFail($uid);
    if ($post->status < 0)
        return [
            'ok' => false,
            'msg' => '投稿已刪除，理由：' . $post->delete_note
        ];

    if ($post->status == 0)
        return ['ok' => false, 'msg' => 'Submission not confirmed. 請先確認投稿'];

    if ($post->status > 3 && $post->status != 10)
        return ['ok' => false, 'msg' => 'Already posted. 太晚囉，貼文已發出'];

    $vote = Vote::where([['uid', '=', $uid], ['stuid', '=', $stuid]])->count();
    if ($vote)
        return ['ok' => false, 'msg' => 'Already voted. 您已投過票'];

    return ['ok' => true];
}

function voteSubmission(string $uid, string $stuid, int $vote, string $reason)
{
    if ($vote == 1)
        $type = 'approvals';
    else if ($vote == -1)
        $type = 'rejects';
    else
        return ['ok' => false, 'msg' => 'Unknown vote. 未知的投票類型'];

    if (mb_strlen($reason) > 100)
        return ['ok' => false, 'msg' => 'Reason too long. 附註文字過長'];

    $post = Post::find($uid);
    $user = User::find($stuid);

    $check = canVote($uid, $stuid);
    if (!$check['ok'])
        return $check;

    Vote::create([
        'uid' => $uid,
        'stuid' => $stuid,
        'vote' => $vote,
        'reason' => $reason,
    ]);

    /* Caution: use string combine in SQL query */
    $post->increment($type);

    /* Calculate vote streak, the users table record is independent from votes table */
    $user->increment($type);

    $lv = date('Ymd', strtotime($user->last_vote));
    $user->last_vote = Carbon::now();

    if ($lv == date('Ymd')) {  // Already voted today
        echo '';
    } else if ($lv == date('Ymd', time() - 24 * 60 * 60)) {  // Streak from yesterday
        if ($user->current_vote_streak == $user->highest_vote_streak)
            $user->highest_vote_streak++;
        $user->current_vote_streak++;
    } else {  // New day
        $user->current_vote_streak = 1;
        if ($user->highest_vote_streak == 0)
            $user->highest_vote_streak = 1;
    }
    $user->save();

    /* Send vote to log group */
    ReviewVoted::dispatchAfterResponse($post, $user);

    /* Return votes for submission */
    return [
        'ok' => true,
        'approvals' => $post->approvals,
        'rejects' => $post->rejects,
    ];
}
