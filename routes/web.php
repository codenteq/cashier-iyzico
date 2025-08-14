<?php

use Codenteq\Iyzico\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/cashier-iyzico', function () {
    return view('welcome');
});

Route::post('iyzico/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');
