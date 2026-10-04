@extends('layouts.admin')
@section('title', 'Settings | Wonder Godoro Point')
@push('styles')
<style>
.wgp-settings{max-width:1180px;margin:0 auto;padding:0 0 40px}.wgp-settings-head{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin:20px 0}.wgp-settings-head h1{margin:0;font-size:30px;color:#182235}.wgp-settings-head p{margin:6px 0 0;color:#738095}.wgp-settings-grid{display:grid;grid-template-columns:1.25fr .75fr;gap:18px}.wgp-settings-card{background:#fff;border:1px solid #e7ebf1;border-radius:16px;box-shadow:0 10px 35px rgba(20,34,55,.07);padding:22px}.wgp-settings-card h2{margin:0;color:#182235;font-size:16px}.wgp-settings-card>p{margin:6px 0 18px;color:#738095;font-size:13px}.wgp-brand-preview{display:flex;align-items:center;gap:18px;padding:18px;border:1px solid #edf0f4;border-radius:14px;background:#f8f9fb;margin-bottom:18px}.wgp-brand-preview img{width:72px;height:72px;object-fit:contain;border-radius:14px;background:#0b1728;padding:8px}.wgp-field{display:grid;gap:7px;margin-top:15px}.wgp-field label{font-size:12px;font-weight:800;color:#27344a}.wgp-field input,.wgp-field select{width:100%;box-sizing:border-box;border:1px solid #dfe5ec;border-radius:10px;padding:11px 12px;background:#fff;color:#182235}.wgp-field input:focus,.wgp-field select:focus{outline:0;border-color:#d6a62d;box-shadow:0 0 0 3px rgba(214,166,45,.12)}.wgp-upload{font-size:12px;color:#738095}.wgp-save{margin-top:20px;border:0;border-radius:10px;background:linear-gradient(135deg,#d6a62d,#f1c85b);color:#101827;font-weight:800;padding:12px 18px;cursor:pointer}.wgp-status{padding:12px 14px;border-radius:11px;background:#edf9f2;color:#176b43;font-size:13px;margin-bottom:16px}.wgp-health{display:grid;gap:10px}.wgp-health-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #edf0f4;font-size:13px}.wgp-pill{font-size:11px;font-weight:800;padding:5px 9px;border-radius:999px;background:#edf9f2;color:#176b43}.wgp-pill.warn{background:#fff5dc;color:#8a6200}.wgp-note{margin-top:16px;padding:12px;border-radius:11px;background:#f7f8fa;color:#738095;font-size:12px;line-height:1.5}@media(max-width:900px){.wgp-settings-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="wgp-settings">
  <div class="wgp-settings-head"><div><h1>Settings</h1><p>Control your business identity and system configuration.</p></div></div>
  @if(session('status'))<div class="wgp-status">✓ {{ session('status') }}</div>@endif
  @if($errors->any())<div class="wgp-status" style="background:#fff0f0;color:#a32626">{{ $errors->first() }}</div>@endif
  <div class="wgp-settings-grid">
    <div class="wgp-settings-card">
      <h2>Branding & Splash Screen</h2><p>Choose the identity your team and customers see when the system opens.</p>
      <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">@csrf @method('PATCH')
        <div class="wgp-brand-preview"><img src="{{ $brandLogo ? asset($brandLogo) : asset('img/logo.png') }}" alt="Brand logo"><div><strong>{{ $brandName }}</strong><div class="wgp-upload">Current system logo</div></div></div>
        <div class="wgp-field"><label>Business / App Name</label><input name="brand_name" value="{{ old('brand_name', $brandName) }}" required></div>
        <div class="wgp-field"><label>App Logo</label><input type="file" name="brand_logo" accept="image/png,image/jpeg,image/webp"><span class="wgp-upload">PNG, JPG or WebP · max 4 MB</span></div>
        <div class="wgp-field"><label>Splash Logo</label><input type="file" name="splash_logo" accept="image/png,image/jpeg,image/webp"><span class="wgp-upload">Leave empty to keep the current splash logo.</span></div>
        <div class="wgp-field"><label>Splash Duration</label><select name="splash_duration"><option value="1800" @selected($splashDuration==='1800')>1.8 seconds</option><option value="2200" @selected($splashDuration==='2200')>2.2 seconds</option><option value="2600" @selected($splashDuration==='2600')>2.6 seconds</option><option value="3000" @selected($splashDuration==='3000')>3 seconds</option></select></div>
        <button class="wgp-save" type="submit">Save Branding</button>
      </form>
    </div>
    <div class="wgp-settings-card"><h2>System Health</h2><p>Current integration and infrastructure status.</p><div class="wgp-health">
      <div class="wgp-health-row"><span>WhatsApp Cloud API</span><span class="wgp-pill {{ $whatsappConfigured?'':'warn' }}">{{ $whatsappConfigured?'Configured':'Needs setup' }}</span></div>
      <div class="wgp-health-row"><span>Real-time / Reverb</span><span class="wgp-pill {{ $reverbConfigured?'':'warn' }}">{{ $reverbConfigured?'Configured':'Needs setup' }}</span></div>
      <div class="wgp-health-row"><span>Queue</span><strong>{{ $queueConnection }}</strong></div>
      <div class="wgp-health-row"><span>Database</span><strong>{{ $databaseConnection }}</strong></div>
    </div><div class="wgp-note">Branding is managed from this screen. Integration secrets remain environment-managed.</div></div>
  </div>
</div>
@endsection
