<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CrawlerController;
use App\Http\Controllers\VerifyController;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::get('/submit', function () {
    if (Session()->has('uid')) {
        $uid = Session()->get('uid');
        return redirect("review/$uid");
    }

    return view('submit');
})->name('submit');


Route::get('/review', function () {
    return view('review-all');
})->name('review');

Route::get('/deleted', function () {
    return view('review-deleted');
})->name('deleted');

Route::get('/review/{post}', function (Post $post) {
    if (session()->has('uid')) {
        $uid = session()->get('uid');
        if (Post::find($uid)->status != 0)
            session()->forget('uid');
    }

    if ($post->id)
        return redirect("/post/{$post->id}");

    return view('review-one', ['post' => $post]);
})->whereAlphaNumeric('post')->name('submission');


Route::get('/posts', function () {
    if (preg_match("/BingBot|FacebookExternalHit|GoogleBot|SlackBot|TelegramBot|TwitterBot|Yandex/i", request()->userAgent()))
        return view('posts-static');
    return view('posts');
})->name('posts');

Route::get('/post/{post:id}', function (Post $post) {
    return view('post', ['post' => $post]);
})->whereNumber('id')->name('post');


Route::name('login.')->group(function () {
    Route::get('/login', function () {
        return redirect('/login/nctu');
    })->name('index');

    Route::get('/login/google', [LoginController::class, 'redirectToGoogle'])->name('google');
    Route::get('/login/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::get('/login/nctu', [LoginController::class, 'redirectToNCTU'])->name('nctu');
    Route::get('/login/nctu/callback', [LoginController::class, 'handleNCTUCallback'])->name('nctu.callback');
    Route::get('/login/tg', [LoginController::class, 'handleTGCallback'])->name('tg');
    Route::get('/login-tg', [LoginController::class, 'handleTGCallback'])->name('tg.legacy');  // Backward compatibility for before Feb 2021
    Route::get('/login/fb', [LoginController::class, 'redirectToFB'])->name('fb');
    Route::get('/login/fb/callback', [LoginController::class, 'handleFBCallback'])->name('fb.callback');
});

Route::post('/logout', function () {
    session()->forget('google_sub');
    Auth::logout();
    return redirect(url()->previous());
});


Route::get('/verify', [VerifyController::class, 'index'])->name('verify.index');
# Route::get('/verify/confirm', [VerifyController::class, 'show'])->name('verify.show');


Route::get('/sitemap.xml', [CrawlerController::class, 'sitemap']);


Route::get('/ranking', function () {
    return view('ranking-all');
})->name('ranking');


Route::get('/faq', function () {
    return view('faq');
})->name('faq');

Route::get('/policies', function () {
    return view('policies');
})->name('policies');

Route::get('/transparency', function () {
    return view('transparency');
})->name('transparency');
