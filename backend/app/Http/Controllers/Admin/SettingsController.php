<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
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
            'brandName' => Setting::value('brand_name', 'Wonder Godoro Point'),
            'brandLogo' => Setting::value('brand_logo'),
            'splashLogo' => Setting::value('splash_logo'),
            'splashDuration' => Setting::value('splash_duration', '2600'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:120'],
            'splash_duration' => ['required', 'integer', 'min:1200', 'max:5000'],
            'brand_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'splash_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        Setting::put('brand_name', $validated['brand_name']);
        Setting::put('splash_duration', (string) $validated['splash_duration']);

        $upload = function (string $field, string $key) use ($request): void {
            if (!$request->hasFile($field)) return;
            $file = $request->file($field);
            $dir = public_path('uploads/branding');
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $name = $key . '-' . uniqid('', true) . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);
            Setting::put($key, 'uploads/branding/' . $name);
        };

        $upload('brand_logo', 'brand_logo');
        $upload('splash_logo', 'splash_logo');

        return back()->with('status', 'Branding settings saved successfully.');
    }
}
