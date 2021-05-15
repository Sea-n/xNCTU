<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\VerifyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::post('/telegram/webhook/{token}', [TelegramController::class, 'webhook']);

Route::apiResource('/posts', PostController::class);

Route::post('/vote', function (Request $request) {
    if (!Auth::check())
        return Response::json([
            'ok' => false,
            'msg' => 'Please login first. 請先登入',
        ]);

    $uid = $request->input('uid', '');
    $stuid = Auth::id();
    $vote = $request->input('vote', 0);
    $reason = $request->input('reason', '');

    $result = voteSubmission($uid, $stuid, $vote, $reason);
    return Response::json($result);
});

# Route::post('/verify', [VerifyController::class, 'store'])->name('verify.store');
# Route::patch('/verify', [VerifyController::class, 'update'])->name('verify.update');

Route::get('/ranking/{tg_id}', [RankingController::class, 'show']);
