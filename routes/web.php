<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

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

Route::get('login', function () {
    return redirect('login/nctu');
})->name('login');

Route::get('login/google', [LoginController::class, 'redirectToGoogle']);
Route::get('login/google/callback', [LoginController::class, 'handleGoogleCallback']);
Route::get('login/nctu', [LoginController::class, 'redirectToNCTU']);
Route::get('login/nctu/callback', [LoginController::class, 'handleNCTUCallback']);
