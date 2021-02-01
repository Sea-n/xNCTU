<?php

return [
    'bots' => [
        'mybot' => [
            'token' => env('TELEGRAM_BOT_TOKEN'),
            'webhook_url' => env('APP_URL') . '/api/telegram/webhook/' . sha1(env('TELEGRAM_BOT_TOKEN')),
        ],
    ],
];
