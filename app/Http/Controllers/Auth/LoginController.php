<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\AvaterTelegram;
use App\Models\GoogleAccount;
use App\Models\User;
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
        return Socialite::driver('google')->redirect();
    }

    /**
     * Redirect the user to the NCTU OAuth authentication page.
     *
     * @return RedirectResponse
     */
    public function redirectToNCTU()
    {
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
                'last_login' => date('Y-m-d H:i:s'),
            ]
        );

        $google = GoogleAccount::find($auth->getId());

        if (isset($google['stuid'])) {
            Auth::login(User::find($google['stuid']));
            return redirect('/');
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
        dd(Socialite::driver('nctu')->user());
        try {
            $auth = Socialite::driver('nctu')->user();
        } catch (Exception $e) {
            return $this->redirectToNCTU();
        }

        $user = User::find($auth->getId());

        if ($user)
            $user->update([
                'email' => $auth->getEmail(),
                'last_login' => date('Y-m-d H:i:s'),
            ]);
        else
            $user = User::create([
                'stuid' => $auth->getId(),
                'name' => $auth->getId(),
                'email' => $auth->getEmail(),
                'last_login' => date('Y-m-d H:i:s'),
            ],
            );

        Auth::login($user, true);

        return redirect('/');
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
                AvaterTelegram::dispatchAfterResponse($user);
            }

            if (Auth::guest()) {
                Auth::login($user);
                return redirect($redirect);
            }

            if (Auth::id() == $user->stuid) {
                return redirect($redirect);
            }

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

        if (Auth::guest())
            return $this->redirectToNCTU();

        $user->update([
            'tg_id' => $auth_data['id'],
            'tg_name' => $auth_data['name'],
            'tg_username' => $auth_data['username'] ?? '',
            'tg_photo' => $auth_data['photo_url'] ?? '',
            'last_login_tg' => date('Y-m-d H:i:s'),
        ]);

        if (isset($auth_data['photo_url']))
            AvaterTelegram::dispatchAfterResponse($user);

        $msg = "🎉 連結成功！\n\n將來有新投稿時，您將會收到推播，並可用 Telegram 內的按鈕審核貼文。";
        Telegram::sendMessage([
            'chat_id' => $user->tg_id,
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
}
