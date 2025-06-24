<?php

return [
    'api_key' => env('DODO_PAYMENT'),
    'sandbox' => env('DODO_SANDBOX', false),
    'currency' => env('DODO_CURRENCY', 'USD'),
    'currency_locale' => env('DODO_CURRENCY_LOCALE', 'en'),
    'webhook_secret' => env('DODO_WEBHOOK_SECRET', ''),
    'path' => env('DODO_PATH', 'dodo'),
    'overlay_checkout' => env('DODO_CHECKOUT', 'true'),
    'user_model' => App\Models\User::class,
];
