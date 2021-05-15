<?php

namespace App\Http\Controllers;

use App\Jobs\ReviewSend;
use App\Models\Post;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $likes = $request->input('likes', '');
        $media = $request->input('media', '');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 50);

        $query = Post::where('status', '=', 5);
        if (is_numeric($likes))
            $query = $query->where('max_likes', '>=', $likes);
        if (is_numeric($media))
            $query = $query->where('media', '=', $media);

        $posts = $query->orderByDesc('id')
                       ->skip($offset)->take($limit)
                       ->get();

        $results = [];
        foreach ($posts as $post) {
            $author_name = "匿名, {$post->ip_from}";
            if ($post->author_id)
                $author_name = $post->author->dep() . ' ' . $post->author->name;

            $ip_masked = $post->ip_addr;
            if (strpos($author_name, '境外') === false)
                $ip_masked = ip_mask($ip_masked);
            if (!Auth::check())
                $ip_masked = ip_mask_anon($ip_masked);
            if (isset($post->author))
                $ip_masked = false;

            $author_photo = genPic($ip_masked);
            if (isset($post->author)) {
                $author_photo = genPic($post->author_id);
                if (isset($post->author->tg_photo))
                    $author_photo = "/avatar/tg/{$post->author->tg_id}-x64.jpg";
            }

            $results[] = [
                'id' => $post->id,
                'uid' => $post->uid,
                'body' => $post->body,
                'media' => $post->media,
                'ip_masked' => $ip_masked,
                'author_name' => $author_name,
                'author_photo' => $author_photo,
                'approvals' => $post->approvals,
                'rejects' => $post->rejects,
                'time' => strtotime($post->submitted_at),
            ];
        }

        return response()->json($results);
    }

    /**
     * @param Post $post
     * @return JsonResponse
     */
    public function show(Post $post)
    {
        if ($post->status < 0)
            return response()->json([
                'ok' => true,
                'reload' => true,
            ]);

        if ($post->id)
            return response()->json([
                'ok' => true,
                'id' => $post->id,
            ]);

        $votes = Vote::where('uid', '=', $post->uid)->orderBy('created_at')->get();
        $results = [];
        foreach ($votes as $item)
            $results[] = [
                'vote' => $item->vote,
                'stuid' => $item->stuid,
                'dep' => $item->user->dep(),
                'name' => $item->user->name,
                'reason' => $item->reason,
            ];

        return response()->json([
            'ok' => true,
            'approvals' => $post->approvals,
            'rejects' => $post->rejects,
            'votes' => $results,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        /* Check CSRF Token */
        $csrf = $request->input('csrf_token', '');
        if (csrf_token() !== $csrf)
            return response()->json([
                'ok' => false,
                'msg' => 'Invalid CSRF Token. 請重新操作',
            ]);

        if ($request->session()->has('uid'))
            return response()->json([
                'ok' => false,
                'msg' => 'You have unconfirmed submission. 您有個投稿尚未確認',
                'uid' => $request->session()->get('uid'),
            ]);

        /* Prepare post content */
        $body = $request->input('body', '');
        $body = str_replace("\r", "", $body);
        $body = preg_replace("#\n\s+\n#", "\n\n", $body);
        $body = preg_replace("#[&?](fbclid|igshid|utm_[a-z]+)=[a-zA-Z0-9_-]+#", "", $body);
        $body = trim($body);

        $has_img = $request->hasFile('img');
        $media = $has_img ? 1 : 0;

        /* Check POST data */
        $error = $this->checkSubmitData($body, $has_img);
        if (!empty($error))
            return response()->json([
                'ok' => false,
                'msg' => $error,
            ]);

        /*
         * Generate UID in base58 space
         *
         * If the uid is already in use, it will pick another one.
         */
        do {
            $uid = rand58(4);
        } while (Post::where('uid', '=', $uid)->first());

        /* Upload Image */
        if ($has_img) {
            $error = $this->uploadImage($uid);
            if (!empty($error))
                return response()->json([
                    'ok' => false,
                    'msg' => $error,
                ]);
        }

        $ip_addr = $request->ip();
        $ip_from = ip_from($ip_addr);

        /* Get Author Name */
        if (Auth::check() && !$request->input('anon', 0)) {
            $author_id = Auth::user()->stuid;
        } else {
            $author_id = null;
        }

        /* Insert record */
        Post::create([
            'uid' => $uid,
            'body' => $body,
            'media' => $media,
            'author_id' => $author_id,
            'ip_addr' => $ip_addr,
            'ip_from' => $ip_from,
        ]);

        /* Check rate limit */
        if (empty($author_id)) {
            $rules = [
                'A' => [
                    'msg' => '具名發文不受限制',
                ],
                'B' => [
                    'period' => 10 * 60,
                    'limit' => 5,
                    'msg' => '校內匿名發文限制 10 分鐘內僅能發 5 篇文',
                ],
                'C' => [
                    'period' => 3 * 60 * 60,
                    'limit' => 3,
                    'msg' => '校外 IP 限制 3 小時內僅能發 3 篇文',
                ],
                'D' => [
                    'period' => 12 * 60 * 60,
                    'limit' => 1,
                    'msg' => '境外 IP 限制 12 小時內僅能發 1 篇文',
                ],
            ];

            if ($ip_from == '交大')
                $rule = $rules['B'];
            else if (strpos($ip_from, '境外') === false)
                $rule = $rules['C'];
            else
                $rule = $rules['D'];

            $posts = Post::where('ip_addr', '=', $rule['limit'] + 1)
                ->orderBy('created_at', 'desc')
                ->get()->take($rule['limit'] + 1);
            if (count($posts) == $rule['limit'] + 1) {
                $last = strtotime($posts[$rule['limit']]->created_at);
                $cd = $rule['period'] - (time() - $last);
                if ($cd > 0) {
                    Post::where('uid', '=', $uid)->update([
                        'status' => -12,
                        'deleted_at' => Carbon::now(),
                        'delete_note' => $rule['msg'],
                    ]);
                    return response()->json([
                        'ok' => false,
                        'msg' => "Please retry after $cd seconds. {$rule['msg']}",
                    ]);
                }
            }

            /* Global rate limit for guest users */
            $max = 5;
            $posts = Post::orderBy('created_at', 'desc')->get()->take($max + 1);
            if (count($posts) == $max + 1) {
                $last = strtotime($posts[$max]['created_at']);
                $cd = 3 * 60 - (time() - $last);
                if ($cd > 0) {
                    Post::where('uid', '=', $uid)->update([
                        'status' => -12,
                        'deleted_at' => Carbon::now(),
                        'delete_note' => 'Global rate limit',
                    ]);
                    return response()->json([
                        'ok' => false,
                        'msg' => "Please retry after $cd seconds. 系統全域限制未登入者 3 分鐘內僅能發 $max 篇文",
                    ]);
                }
            }
        }

        $request->session()->put('uid', $uid);

        return response()->json([
            'ok' => true,
            'uid' => $uid,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function update(Request $request, Post $post)
    {
        if ($request->input('status', '') != 'confirmed')
            return response()->json([
                'ok' => false,
                'msg' => 'Unknown status.',
            ]);

        if ($post->status > 0)
            return response()->json([
                'ok' => true,
                'msg' => 'Already confirmed. 投稿已送出',
            ]);

        if ($post->status < 0)
            return response()->json([
                'ok' => false,
                'msg' => 'Already deleted. 投稿已刪除：' . $post->delete_note,
            ]);

        if ($request->session()->get('uid', '') != $post->uid)
            return response()->json([
                'ok' => false,
                'msg' => 'Session mismatched. 無法驗證身份，請使用同個瀏覽器確認',
            ]);


        $request->session()->forget('uid');

        $post->update([
            'status' => 1,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);

        ReviewSend::dispatchAfterResponse($post);

        return response()->json([
            'ok' => true,
            'msg' => 'Confirmed. 投稿已送出',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function destroy(Request $request, Post $post)
    {
        if ($post->status > 0)
            return response()->json([
                'ok' => false,
                'msg' => '刪除失敗：已進入審核程序',
            ]);

        if ($post->status < 0)
            return response()->json([
                'ok' => true,
                'msg' => 'Already deleted. 投稿已刪除：' . $post->delete_note,
            ]);

        if ($request->session()->get('uid', '') != $post->uid)
            return response()->json([
                'ok' => false,
                'msg' => 'Session mismatched. 無法驗證身份，請使用同個瀏覽器刪除',
            ]);

        $reason = $request->input('reason');
        $reason = trim($reason);
        if (mb_strlen($reason) < 1 || mb_strlen($reason) > 100)
            return response()->json([
                'ok' => false,
                'msg' => 'Please input 1-100 chars. 請輸入 1-100 字刪除附註',
            ]);

        $request->session()->forget('uid');

        $post->update([
            'status' => -3,
            'deleted_at' => Carbon::now(),
            'delete_note' => "自刪 $reason",
        ]);

        return response()->json([
            'ok' => true,
            'msg' => 'Deleted. 刪除成功',
        ]);
    }

    /**
     *  Return error string or empty on success
     *
     * @param string $body
     * @param boolean $has_img
     * @return string  $error
     */
    private function checkSubmitData(string $body, bool $has_img): string
    {
        /* Check CAPTCHA */
        $captcha = trim(request()->input('captcha', 'X'));
        if (!in_array($captcha, ['交大竹湖', '交大竹狐'])) {
            if (mb_strlen($captcha) > 1 && mb_strlen($captcha) < 20)
                error_log("Captcha failed: $captcha.");
            return 'Are you human? 驗證碼錯誤';
        }

        /* Check Body */
        if (mb_strlen($body) < 1)
            return 'Body is empty. 請輸入文章內容';

        if ($has_img && mb_strlen($body) > 960)
            return 'Body too long (' . mb_strlen($body) . ' chars). 文章過長';

        if (mb_strlen($body) > 4000)
            return 'Body too long (' . mb_strlen($body) . ' chars). 文章過長';

        $lines = explode("\n", $body);
        if (preg_match('#https?://#', $lines[0]))
            return 'First line cannot be URL. 第一行不能有網址';

        return '';
    }

    /**
     * Return error message or empty
     *
     * @param string $uid
     * @return string  $error
     */
    private function uploadImage(string $uid): string
    {
        /* Check file type */
        $mime = request()->file('img')->getMimeType();
        if (!in_array($mime, [
            'image/jpeg',
            'image/png',
        ]))
            return 'Extension not recognized. 圖片副檔名錯誤';

        $img = request()->file('img')->storeAs('img', "$uid.jpg");

        /* Check image size */
        $size = getimagesize($img);
        $width = $size[0];
        $height = $size[1];

        if ($width * $height < 160 * 160)
            $err = 'Image must be at least 160x160.';

        if ($width / 8 > $height)
            $err = 'Image must be at least 8:1.';

        if ($width < $height / 4)
            $err = 'Image must be at least 1:4.';

        if (isset($err)) {
            unlink($img);
            return $err;
        }

        /* Fix orientation */
        $orien = shell_exec("exiftool -Orientation -S -n $img |cut -c14- |tr -d '\\n'");
        switch ($orien) {
            case '1':  # Horizontal (normal)
                $transpose = "";
                break;
            case '2':  # Mirror horizontal
                $transpose = "-vf transpose=0,transpose=1";
                break;
            case '3':  # Rotate 180
                $transpose = "-vf transpose=1,transpose=1";
                break;
            case '4':  # Mirror vertical
                $transpose = "-vf transpose=3,transpose=1";
                break;
            case '5':  # Mirror horizontal and rotate 270 CW
                $transpose = "-vf transpose=0";
                break;
            case '6':  # Rotate 90 CW
                $transpose = "-vf transpose=1";
                break;
            case '7':  # Mirror horizontal and rotate 90 CW
                $transpose = "-vf transpose=3";
                break;
            case '8':  # Rotate 270 CW
                $transpose = "-vf transpose=2";
                break;
            default:
                $transpose = "";
                break;
        }

        /* Convert all file type to jpg */
        exec("ffmpeg -i $img -q:v 1 $transpose $img.jpg");
        rename("$img.jpg", $img);

        while (filesize($img) > 1 * 1000 * 1000) {
            exec("ffmpeg -i $img -q:v 1 -vf scale='(iw/2):(ih/2)' $img.jpg 2>&1");
            rename("$img.jpg", $img);
        }

        return '';
    }
}
