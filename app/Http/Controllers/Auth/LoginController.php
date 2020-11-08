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
                'sub' => $auth->getId(),
                'email' => $auth->getEmail(),
                'name' => $auth->getName(),
                'avatar' => $auth->getAvatar(),
            ]
        );

        $google = GoogleAccount::where('sub', '=', $auth->getId())->first();

        if (isset($google['stuid'])) {
            $user = User::where('stuid', '=', $google['stuid'])->first();
            Auth::login($user);
        } else {
            var_dump($google);
            exit;
        }

        return redirect('/');
    }

    /**
     * Obtain the user information from NCTU OAuth.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleNCTUCallback()
    {
        $auth = Socialite::driver('nctu')->user();

        User::updateOrCreate(
            ['stuid' => $auth->getId()],
            [
                'stuid' => $auth->getId(),
                'name'  => $auth->getId(),
                'email' => $auth->getEmail(),
            ],
        );

        $user = User::where('stuid', '=', $auth->getId())->first();
        Auth::login($user, true);

        return redirect('/');
    }
}
