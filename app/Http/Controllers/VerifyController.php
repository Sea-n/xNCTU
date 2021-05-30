<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::check()) {
            session()->forget('google_sub');
            return redirect('/');
        }

        if (!session()->has('google_sub'))
            return redirect('/login/google');

        $google = GoogleAccount::find(session()->get('google_sub'));

        if (!empty($google->stuid)) {
            session()->forget('google_sub');
            $user = User::find($google->stuid);
            $user->update(['last_login_google' => Carbon::now()]);
            Auth::login($user);
            return redirect('/');
        }

        return view('verify', ['google' => $google]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $stuid = $request->input('stuid', '');
        $sub = $request->input('sub', '');
        $code = $request->input('code', '');

        $data_check_string = "verify_{$stuid}_{$sub}";
        $hash = hash_hmac('sha256', $data_check_string, env('VERIFY_SECRET'));
        $hash = substr($hash, 0, 8);
        if (!hash_equals($hash, $code))
            return 'Verify show failed. 驗證碼錯誤';

        $google = GoogleAccount::find($sub);

        return view('verify', [
            'stuid' => $stuid,
            'google' => $google,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $sub = session()->get('google_sub');
        $google = GoogleAccount::find($sub);
        if (!$google)
            return response()->json([
                'ok' => false,
                'msg' => 'Please login first. 請先登入'
            ]);

        $last = strtotime($google->last_verify);
        if (time() - $last < 10)
            return response()->json([
                'ok' => false,
                'msg' => 'Retry later. 冷卻中，請稍後再試'
            ]);

        $stuid = $request->input('stuid', '');

        if (!preg_match('#^1\d{2}\d{6}$#', $stuid))
            return response()->json([
                'ok' => false,
                'msg' => 'stuid format error. 學號格式錯誤'
            ]);

        $year = substr($stuid, 0, 3);
        $to = "s{$stuid}@m{$year}.nthu.edu.tw";
        $subject = '=?UTF-8?B?' . base64_encode('[' . env('APP_CHINESE_NAME') . "] 帳號驗證 - {$stuid}") . '?=';

        $data_check_string = "verify_{$stuid}_{$sub}";
        $hash = hash_hmac('sha256', $data_check_string, env('VERIFY_SECRET'));
        $code = substr($hash, 0, 8);
        $verify_link = url("/verify/confirm?stuid={$stuid}&sub={$sub}&code={$code}");

        $date = date("Y-m-d");

        $ip_addr = $_SERVER['REMOTE_ADDR'];
        $ip_from = ip_from($ip_addr);

        /* Plaintext version */
        $plain = "{$google->name} 您好，

感謝您註冊 " . env('APP_CHINESE_NAME') . "，請點擊下方連結啟用帳號：
$verify_link

為了維持更新頻率，" . env('APP_CHINESE_NAME') . " 將審核工作下放至全體師生，因此您的每一票對我們來說都相當重要。
雖然所有審核者皆為自由心證，未經過訓練也不強制遵從統一標準；但透過保留所有審核紀錄、被駁回的投稿皆提供全校師生檢視，增加審核標準的透明度。

有了您的貢獻，期望能以嶄新的姿態，將" . env('APP_CHINESE_NAME') . " 推向靠北生態系巔峰。

" . env('HASHTAG') . "維護團隊
{$date}


由於 {$google->name} <{$google->email}> 在" . env('APP_CHINESE_NAME') . " 網站申請寄送驗證碼，因此寄發本信件給您。（來自「{$ip_from}」，IP 位址為 {$ip_addr}）
如不是由您本人註冊，很可能是同學手滑打錯學號了，請不要點擊驗證按鈕以避免爭議。
若是未來不想再收到相關信件，請來信 與我們聯絡，將會盡快將您的學號放入拒收清單內。";

        /* HTML version */
        $heml = view('heml.verify', [
            'google' => $google,
            'verify_link' => $verify_link,
            'date' => $date,
            'ip_addr' => $ip_addr,
            'ip_from' => $ip_from,
        ])->render();

        /* Convert HEML to HTML */
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://heml-api.herokuapp.com/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode([
                'heml' => $heml,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
            ],
        ]);
        $data = curl_exec($curl);
        curl_close($curl);
        $html = json_decode($data)->html;

        /* Merge HTML and plaintext to body */
        $boundary = md5(microtime());
        $body = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=\"UTF-8\"\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plain));

        $body .= "--$boundary\r\n" .
            "Content-Type: text/html; charset=\"UTF-8\"\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html));

        $body .= "--$boundary--";

        /* Mail Headers */
        $mail_from = base64_encode(env('APP_CHINESE_NAME') . ' 自動驗證系統');
        $mail_cc   = base64_encode(env('APP_CHINESE_NAME') . ' 維護團隊');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: =?UTF-8?B?$mail_from?= <no-reply@sean.taipei>\r\n";
        $headers .= "Cc: =?UTF-8?B?$mail_cc?= <" . env('MAIL_FROM_ADDRESS') . ">\r\n";
        $headers .= "Message-Id: <xnthu.verify.$stuid@sean.taipei>\r\n";
        $headers .= "List-Unsubscribe: <" . url("/?unsubscribe=$stuid") . ">\r\n";
        $headers .= "X-Mailer: xNTHU/2.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        mail($to, $subject, $body, $headers);

        $google->update(['last_verify' => Carbon::now()]);

        return response()->json([
            'ok' => true,
            'msg' => '寄送成功'
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $stuid = $request->input('stuid', '');
        $sub = $request->input('sub', '');
        $code = $request->input('code', '');

        $data_check_string = "verify_{$stuid}_{$sub}";
        $hash = hash_hmac('sha256', $data_check_string, env('VERIFY_SECRET'));
        $hash = substr($hash, 0, 8);
        if (!hash_equals($hash, $code))
            return response()->json([
                'ok' => false,
                'msg' => 'Verify confirm failed. 驗證碼錯誤'
            ]);

        $google = GoogleAccount::find($sub);
        $user = User::find($stuid);

        if ($google->stuid == $stuid) {
            return response()->json([
                'ok' => true,
                'msg' => '您已綁定過此帳號'
            ]);
        }

        if (!$user)
            User::create([
                'name' => $stuid,
                'stuid' => $stuid,
                'email' => $google->email,
            ]);
        $google->update(['stuid' => $stuid]);
        session()->forget('google_sub');

        return response()->json([
            'ok' => true,
            'msg' => '驗證成功！'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
