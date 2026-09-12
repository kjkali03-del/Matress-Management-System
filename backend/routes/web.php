<?php

use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PipelineController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\VerifyMetaWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [LoginController::class, 'store'])
    ->middleware('guest')
    ->name('login.store');

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/admin', function () {
    return view('admin.dashboard');
})
    ->middleware(['auth', 'admin'])
    ->name('admin');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Inbox
        Route::get('/inbox', [InboxController::class, 'index'])
            ->name('inbox');

        Route::get('/inbox/{conversation}/messages', [InboxController::class, 'messages'])
            ->name('inbox.messages');

        Route::post(
            '/inbox/{conversation}/messages',
            [InboxController::class, 'store']
        )->name('inbox.messages.store');

        Route::post(
            '/inbox/{conversation}/image',
            [InboxController::class, 'sendImage']
        )->name('inbox.messages.image');

        Route::post(
            '/inbox/{conversation}/video',
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

        // Customer notes
        Route::patch(
            '/inbox/customers/{customer}/notes',
            [InboxController::class, 'updateCustomerNotes']
        )->name('inbox.customers.notes.update');

        Route::get(
            '/inbox/customers/{customer}/notes/download',
            [InboxController::class, 'downloadCustomerNotes']
        )->name('inbox.customers.notes.download');

        // Backward-compatible customer notes route
        Route::post(
            '/customers/{customer}/notes',
            [InboxController::class, 'updateCustomerNotes']
        )->name('customers.notes.update');

        // Customers
        Route::get('/customers', [CustomerController::class, 'index'])
            ->name('customers.index');

        Route::get('/customers/{customer}', [CustomerController::class, 'show'])
            ->name('customers.show');

        Route::put('/customers/{customer}', [CustomerController::class, 'update'])
            ->name('customers.update');

        // Sales Pipeline
        Route::get('/pipeline', [PipelineController::class, 'index'])
            ->name('pipeline.index');

        Route::patch(
            '/pipeline/customers/{customer}/status',
            [PipelineController::class, 'updateStatus']
        )->name('pipeline.customers.status.update');

        // Tags
        Route::get('/tags', [TagController::class, 'index'])
            ->name('tags.index');

        Route::post('/tags', [TagController::class, 'store'])
            ->name('tags.store');

        Route::put('/tags/{tag}', [TagController::class, 'update'])
            ->name('tags.update');

        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])
            ->name('tags.destroy');

        // Products
        Route::resource('products', ProductController::class);

        // Orders
        Route::resource('orders', OrderController::class);

        // Delivery
        Route::get('/delivery', function () {
            return view('admin.dashboard');
        })->name('delivery.index');

        // Reports
        Route::get('/reports', function () {
            return view('admin.dashboard');
        })->name('reports.index');

        // Settings
        Route::get('/settings', function () {
            return view('admin.dashboard');
        })->name('settings.index');

        // Automations
        Route::resource('automations', AutomationController::class);

        Route::post(
            '/automations/{automation}/toggle',
            [AutomationController::class, 'toggle']
        )->name('automations.toggle');
    });

// WhatsApp Webhook
Route::get(
    '/webhooks/whatsapp',
    [WhatsAppWebhookController::class, 'verify']
)->name('webhooks.whatsapp.verify');

Route::post(
    '/webhooks/whatsapp',
    [WhatsAppWebhookController::class, 'handle']
)
    ->middleware(VerifyMetaWebhookSignature::class)
    ->name('webhooks.whatsapp.handle');