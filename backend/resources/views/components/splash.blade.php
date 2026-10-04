@php
    $brandName = \App\Models\Setting::value('brand_name', 'Wonder Godoro Point');
    $brandLogo = \App\Models\Setting::value('brand_logo', 'img/logo.png');
    $splashLogo = \App\Models\Setting::value('splash_logo', $brandLogo);
    $duration = (int) \App\Models\Setting::value('splash_duration', '2600');
@endphp
<div class="startup-splash" data-startup-splash data-duration="{{ $duration }}" aria-label="{{ $brandName }}" role="status">
    <div class="startup-splash__scan startup-splash__scan--one" aria-hidden="true"></div>
    <div class="startup-splash__scan startup-splash__scan--two" aria-hidden="true"></div>
    <div class="startup-splash__grid" aria-hidden="true"></div>
    <div class="startup-splash__glow" aria-hidden="true"></div>
    <div class="startup-splash__content">
        <div class="startup-splash__logo-wrap">
            <span class="startup-splash__orbit startup-splash__orbit--one" aria-hidden="true"></span>
            <span class="startup-splash__orbit startup-splash__orbit--two" aria-hidden="true"></span>
            <span class="startup-splash__line startup-splash__line--top" aria-hidden="true"></span>
            <span class="startup-splash__line startup-splash__line--bottom" aria-hidden="true"></span>
            <img class="startup-splash__logo" src="{{ asset($splashLogo ?: 'img/logo.png') }}" alt="{{ $brandName }}">
        </div>
        <div class="startup-splash__copy">
            <p class="startup-splash__title">{{ strtoupper($brandName) }}</p>
            <p class="startup-splash__subtitle">ADMIN WORKSPACE</p>
        </div>
        <span class="startup-splash__rule" aria-hidden="true"></span>
    </div>
</div>
