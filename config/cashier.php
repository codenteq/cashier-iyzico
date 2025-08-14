<?php

return [
    /*
    |--------------------------------------------------------------------------
    | İyzico API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure your İyzico API credentials and settings.
    | You can get these values from your İyzico merchant dashboard.
    |
    */
    'iyzico' => [
        'api_key' => env('IYZICO_API_KEY'),
        'secret_key' => env('IYZICO_SECRET_KEY'),
        'base_url' => env('IYZICO_BASE_URL', 'https://api.iyzipay.com'),
        'card_update_callback_url' => env('IYZICO_CARD_UPDATE_CALLBACK_URL', 'http://localhost/iyzico/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when generating charges
    | from your application. Of course, you are welcome to use any of the
    | various world currencies that are currently supported via İyzico.
    |
    */
    'currency' => env('CASHIER_CURRENCY', 'TRY'),

    /*
    |--------------------------------------------------------------------------
    | Currency Symbol
    |--------------------------------------------------------------------------
    |
    | This is the currency symbol that will be used when formatting currency
    | amounts for display in your application. You can change this to any
    | symbol that is appropriate for your application's currency.
    |
    */
    'currency_symbol' => env('CASHIER_CURRENCY_SYMBOL', '₺'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure webhook-related settings for your İyzico
    | integration. This includes signature verification and tolerance.
    |
    */

    'webhook' => [
        'verify' => env('IYZICO_WEBHOOK_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashier Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used for subscriptions and billing.
    | Typically, this will be the User model in your application.
    |
    */

    'model' => env('CASHIER_MODEL', App\Models\User::class),
];
