<?php

use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\VerifyMetaWebhookSignature;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::middleware('admin')
        ->get('/admin', function () {
            return view('admin.dashboard');
        })
        ->name('admin');

    Route::middleware(['admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get('/inbox', [InboxController::class, 'index'])
                ->name('inbox');

            Route::get(
                '/inbox/{conversation}/messages',
                [InboxController::class, 'messages']
            )->name('inbox.messages');

            Route::post(
                '/inbox/{conversation}/messages',
                [InboxController::class, 'store']
            )->name('inbox.messages.store');

            Route::post(
                '/inbox/{conversation}/media/image',
                [InboxController::class, 'sendImage']
            )->name('inbox.messages.image');

            Route::post(
                '/inbox/{conversation}/media/video',
                [InboxController::class, 'sendVideo']
            )->name('inbox.messages.video');

            Route::post(
                '/inbox/{conversation}/location',
                [InboxController::class, 'sendLocation']
            )->name('inbox.messages.location');

            Route::delete(
                '/inbox/messages/{message}',
                [InboxController::class, 'destroyMessage']
            )->name('inbox.messages.destroy');

            Route::patch(
                '/inbox/customers/{customer}/notes',
                [InboxController::class, 'updateCustomerNotes']
            )->name('inbox.customers.notes.update');

            Route::get(
                '/inbox/customers/{customer}/notes/download',
                [InboxController::class, 'downloadCustomerNotes']
            )->name('inbox.customers.notes.download');

            Route::resource('products', ProductController::class);
        });
});

Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [WhatsAppWebhookController::class, 'verify'])
        ->withoutMiddleware([
            ValidateCsrfToken::class,
            'auth',
            'admin',
        ])
        ->name('whatsapp.webhook.verify');

    Route::post('/', [WhatsAppWebhookController::class, 'receive'])
        ->withoutMiddleware([
            ValidateCsrfToken::class,
            'auth',
            'admin',
        ])
        ->middleware(VerifyMetaWebhookSignature::class)
        ->name('whatsapp.webhook.receive');
});