<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'whatsappConfigured' => filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id')),
            'reverbConfigured' => filled(config('broadcasting.connections.reverb.key')),
            'queueConnection' => config('queue.default'),
            'databaseConnection' => config('database.default'),
        ]);
    }

    public function update(Request $request)
    {
        return back()->with('status', 'Runtime settings are environment-managed. Update .env and restart the application.');
    }
}
