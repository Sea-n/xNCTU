<?php

namespace App\Http\Controllers;

use Request;
use Response;
use Telegram;

class TelegramController extends Controller
{
    public function webhook(string $token, Request $request)
    {
        if ($token != sha1(env('TELEGRAM_BOT_TOKEN')))
            return Response::json(['msg' => 'You are not from Telegram'], 401);

        $updates = Telegram::getWebhookUpdates();
        Telegram::sendMessage([
            'chat_id' => $updates->message->chat->id,
            'text' => json_encode($updates, JSON_PRETTY_PRINT),
        ]);
        return 'ok';
    }
}
