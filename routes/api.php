<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\RankingController;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

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

Route::get('/votes/{post}', function (Post $post) {
    $votes = Vote::where('uid', '=', $post->uid)->get();
    $results = [];
    foreach ($votes as $item)
        $results[] = [
            'vote' => $item->vote,
            'stuid' => $item->stuid,
            'dep' => $item->user->dep(),
            'name' => $item->user->name,
            'reason' => $item->reason,
        ];

    return Response::json([
        'ok' => true,
        'approvals' => $post->approvals,
        'rejects' => $post->rejects,
        'votes' => $results,
    ]);
});

Route::get('/ranking/{tg_id}', [RankingController::class, 'show']);
