<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\GoogleAccount;

class LoginController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Redirect the user to the NCTU OAuth authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToNCTU()
    {
        return Socialite::driver('nctu')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGoogleCallback()
    {
        $auth = Socialite::driver('google')->user();

        GoogleAccount::updateOrCreate(
            ['sub' => $auth->getId()],
            [
                'sub'        => $auth->getId(),
                'email'      => $auth->getEmail(),
                'name'       => $auth->getName(),
                'avatar'     => $auth->getAvatar(),
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
     * @return \Illuminate\Http\Response
     */
    public function handleNCTUCallback()
    {
        $auth = Socialite::driver('nctu')->user();

        $user = User::find($auth->getId());

        if ($user)
            $user->update([
                'email'      => $auth->getEmail(),
                'last_login' => date('Y-m-d H:i:s'),
            ]);
        else
            $user = User::create([
                    'stuid'      => $auth->getId(),
                    'name'       => $auth->getId(),
                    'email'      => $auth->getEmail(),
                    'last_login' => date('Y-m-d H:i:s'),
                ],
            );

        Auth::login($user, true);

        return redirect('/');
    }
}
