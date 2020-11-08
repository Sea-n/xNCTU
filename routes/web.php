<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Models\Post;

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

Route::get('/review/{post}', function (Post $post) {
    if (session()->has('uid')) {
        $uid = session()->get('uid');
        if (Post::find($uid)->status != 0)
            session()->forget('uid');
    }

    return view('review', ['post' => $post]);
})->name('review');

Route::get('login', function () {
    return redirect('login/nctu');
})->name('login');

Route::get('login/google', [LoginController::class, 'redirectToGoogle']);
Route::get('login/google/callback', [LoginController::class, 'handleGoogleCallback']);
Route::get('login/nctu', [LoginController::class, 'redirectToNCTU']);
Route::get('login/nctu/callback', [LoginController::class, 'handleNCTUCallback']);
