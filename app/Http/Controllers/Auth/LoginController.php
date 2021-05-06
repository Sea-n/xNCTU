<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\AvatarTelegram;
use App\Models\GoogleAccount;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Telegram;

class LoginController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return RedirectResponse
     */
    public function redirectToGoogle()
    {
        session(['redirect' => url()->previous()]);
        return Socialite::driver('google')->redirect();
    }

    /**
     * Redirect the user to the NCTU OAuth authentication page.
     *
     * @return RedirectResponse
     */
    public function redirectToNCTU()
    {
        session(['redirect' => url()->previous()]);
        return Socialite::driver('nctu')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return RedirectResponse
     */
    public function handleGoogleCallback()
    {
        $auth = Socialite::driver('google')->user();

        GoogleAccount::updateOrCreate(
            ['sub' => $auth->getId()],
            [
                'sub' => $auth->getId(),
                'email' => $auth->getEmail(),
                'name' => $auth->getName(),
                'avatar' => $auth->getAvatar(),
                'last_login' => Carbon::now(),
            ]
        );

        $google = GoogleAccount::find($auth->getId());

        if (isset($google->stuid)) {
            $user = User::find($google->stuid);
            $user->update(['last_login_google' => Carbon::now()]);
            Auth::login($user, true);
            $redirect = session('redirect', '/');
            session()->forget('redirect');
            return redirect($redirect);
        } else {
            session()->put('google_sub', $google->sub);
            return redirect('/verify');
        }
    }

    /**
     * Obtain the user information from NCTU OAuth.
     *
     * @return Response|RedirectResponse
     */
    public function handleNCTUCallback()
    {
        try {
            $auth = Socialite::driver('nctu')->user();
        } catch (Exception $e) {
            return $this->redirectToNCTU();
        }

        $user = User::find($auth->getId());

        if ($user)
            $user->update([
                'email' => $auth->getEmail(),
                'last_login_nctu' => Carbon::now(),
            ]);
        else
            $user = User::create([
                'stuid' => $auth->getId(),
                'name' => $auth->getId(),
                'email' => $auth->getEmail(),
                'last_login_nctu' => Carbon::now(),
            ],
            );

        Auth::login($user, true);
        $this->postLogin();

        $redirect = session('redirect', '/');
        session()->forget('redirect');
        return redirect($redirect);
    }

    /**
     * @return Response|RedirectResponse
     * @throws Telegram\Bot\Exceptions\TelegramResponseException
     */
    public function handleTGCallback()
    {
        $redirect = '/';  # Default value

        try {
            $auth_data = request()->all();
            if (isset($auth_data['r'])) {
                $redirect = $auth_data['r'];
                unset($auth_data['r']);
            }
            $auth_data = $this->checkTGAuth($auth_data);
        } catch (Exception $e) {
            return response($e->getMessage(), 401);
        }

        $auth_data['name'] = $auth_data['first_name'];
        if (isset($auth_data['last_name']))
            $auth_data['name'] .= ' ' . $auth_data['last_name'];

        $user = User::where('tg_id', '=', $auth_data['id'])->first();

        if ($user) {
            if (Auth::check() && Auth::id() != $user->stuid) {
                $msg = "⚠️ 您已連結過此帳號\n\n" .
                    "目前無法將不同的 NCTU OAuth 帳號連結至同一個 Telegram 帳號\n\n" .
                    'NCTU ID from session: ' . Auth::id() . "\n" .
                    "NCTU ID from database: {$user->stuid}\n" .
                    "Telegram UID: {$auth_data['id']}";

                Telegram::sendMessage([
                    'chat_id' => $auth_data['id'],
                    'text' => $msg,
                ]);

                return redirect($redirect);
            }

            $old_photo = $user->tg_photo ?? '';

            $user->update([
                'tg_name' => $auth_data['name'],
                'tg_username' => $auth_data['username'] ?? '',
                'tg_photo' => $auth_data['photo_url'] ?? '',
                'last_login_tg' => date('Y-m-d H:i:s'),
            ]);

            if ($user->name == $user->stuid)
                $user->update(['name' => $user->tg_name]);

            if (isset($auth_data['photo_url']) && $old_photo != $auth_data['photo_url']) {
                AvatarTelegram::dispatchAfterResponse($user);
            }

            Auth::login($user, true);
            $this->postLogin();

            return redirect($redirect);
        }

        if (Auth::guest())
            return $this->redirectToNCTU();

        Auth::user()->update([
            'tg_id' => $auth_data['id'],
            'tg_name' => $auth_data['name'],
            'tg_username' => $auth_data['username'] ?? '',
            'tg_photo' => $auth_data['photo_url'] ?? '',
            'last_login_tg' => Carbon::now(),
        ]);

        if (isset($auth_data['photo_url']))
            AvatarTelegram::dispatchAfterResponse(Auth::user());

        $msg = "🎉 連結成功！\n\n將來有新投稿時，您將會收到推播，並可用 Telegram 內的按鈕審核貼文。";
        Telegram::sendMessage([
            'chat_id' => Auth::user()->tg_id,
            'text' => $msg
        ]);

        return redirect($redirect);
    }

    /**
     * @param $auth_data
     * @return mixed
     * @throws Exception
     */
    protected function checkTGAuth($auth_data)
    {
        if (!isset($auth_data['id']))
            throw new Exception('No User ID.');

        if (!isset($auth_data['username']))
            throw new Exception('No username.');

        if (!isset($auth_data['hash']))
            throw new Exception('No Telegram hash.');

        if (empty(env('TELEGRAM_BOT_TOKEN')))
            throw new Exception('No Telegram Bot Token in env config.');

        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);

        $data_check_arr = [];

        foreach ($auth_data as $key => $value)
            $data_check_arr[] = $key . '=' . $value;

        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);

        $secret_key = hash('sha256', env('TELEGRAM_BOT_TOKEN'), true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        if (!hash_equals($hash, $check_hash))
            throw new Exception('Data is NOT from Telegram.');

        if ((time() - $auth_data['auth_date']) > 365 * 24 * 60 * 60)
            throw new Exception('Session expired.');

        $auth_data['hash'] = $check_hash;
        return $auth_data;
    }

    /**
     * Redirect the user to the Facebook OAuth page.
     *
     * @return RedirectResponse
     */
    public function redirectToFB()
    {
        $url = 'https://www.facebook.com/v8.0/dialog/oauth'
            . '?client_id=' . env('FACEBOOK_APP_ID')
            . '&redirect_uri=' . urlencode(url('/login/fb/callback'))
            . '&response_type=code'
            . '&scope=pages_show_list,pages_read_engagement,pages_manage_metadata,pages_read_user_content,pages_manage_posts,pages_manage_engagement,public_profile';

        return redirect($url);
    }

    /**
     * Obtain access token from Facebook.
     *
     * @return RedirectResponse
     */
    public function handleFBCallback()
    {
        $url = 'https://graph.facebook.com/v8.0/oauth/access_token'
            . '?client_id=' . env('FACEBOOK_APP_ID')
            . '&redirect_uri=' . urlencode(url('/login/fb/callback'))
            . '&client_secret=' . env('FACEBOOK_APP_SECRET')
            . '&code=' . urlencode(request()->input('code'));
        $data = file_get_contents($url);
        $data = json_decode($data);

        if ($data->error->code ?? 0 == 100)
            return redirect('/login/fb');

        if (!$data->access_token)
            return 'No access token';

        $user_token = $data->access_token;

        $me = file_get_contents("https://graph.facebook.com/v8.0/me?access_token=$user_token");
        $me = json_decode($me);

        $accounts = file_get_contents("https://graph.facebook.com/{$me->id}/accounts?access_token=$user_token");
        $accounts = json_decode($accounts);

        $page_tokens = [];
        foreach ($accounts->data as $item)
            $page_tokens[ $item->id  ] = $item->access_token;

        $short_token = $page_tokens[env('FACEBOOK_PAGES_ID')];

        $url = 'https://graph.facebook.com/v8.0/oauth/access_token'
            . '?client_id=' . env('FACEBOOK_APP_ID')
            . '&client_secret=' . env('FACEBOOK_APP_SECRET')
            . '&fb_exchange_token=' . $short_token
            . '&grant_type=fb_exchange_token';
        $data = file_get_contents($url);
        $data = json_decode($data);

        $long_token = $data->access_token;
        echo $long_token;
    }

    public function postLogin()
    {
        if (session()->has('google_sub')) {
            $google = GoogleAccount::find(session()->get('google_sub'));

            if (Auth::check()) {
                if ($google->stuid != Auth::id())
                    $google->update([
                        'stuid' => Auth::id(),
                        'last_login' => Carbon::now(),
                    ]);

                session()->forget('google_sub');
                Auth::user()->update(['last_login_google' => Carbon::now()]);
            }
        }
    }
}
