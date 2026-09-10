<?php

use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\VerifyMetaWebhookSignature;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */

    Route::middleware('admin')
        ->get('/admin', function () {
            return view('admin.dashboard');
        })
        ->name('admin');

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    */

    Route::middleware(['admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            /*
            | Customer Inbox
            */

            Route::get('/inbox', [InboxController::class, 'index'])
                ->name('inbox');

            /*
            | Fetch messages newer than a specific message ID
            */

            Route::get(
                '/inbox/{conversation}/messages',
                [InboxController::class, 'messages']
            )->name('inbox.messages');

            /*
            | Send / save outbound message
            */

            Route::post(
                '/inbox/{conversation}/messages',
                [InboxController::class, 'store']
            )->name('inbox.messages.store');

            /*
            | Products
            */

            Route::resource('products', ProductController::class);
        });
});

/*
|--------------------------------------------------------------------------
| WhatsApp Webhook
|--------------------------------------------------------------------------
*/

Route::prefix('webhooks/whatsapp')->group(function () {

    /*
    | Meta webhook verification
    */

    Route::get('/', [WhatsAppWebhookController::class, 'verify'])
        ->withoutMiddleware([
            ValidateCsrfToken::class,
            'auth',
            'admin',
        ])
        ->name('whatsapp.webhook.verify');

    /*
    | Meta webhook events
    */

    Route::post('/', [WhatsAppWebhookController::class, 'receive'])
        ->withoutMiddleware([
            ValidateCsrfToken::class,
            'auth',
            'admin',
        ])
        ->middleware(VerifyMetaWebhookSignature::class)
        ->name('whatsapp.webhook.receive');
});