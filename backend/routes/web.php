<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [WhatsAppWebhookController::class, 'verify'])
        ->withoutMiddleware([ValidateCsrfToken::class, 'auth', 'admin'])
        ->name('whatsapp.webhook.verify');
    Route::post('/', [WhatsAppWebhookController::class, 'receive'])
        ->withoutMiddleware([ValidateCsrfToken::class, 'auth', 'admin'])
        ->middleware('webhook.secret')
        ->name('whatsapp.webhook.receive');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', function () {
            return view('admin');
        })->name('admin');

        Route::get('/inbox', [InboxController::class, 'index'])->name('admin.inbox');
        Route::post('/inbox/{conversation}/messages', [InboxController::class, 'store'])
            ->name('admin.inbox.messages.store');
    });
});
