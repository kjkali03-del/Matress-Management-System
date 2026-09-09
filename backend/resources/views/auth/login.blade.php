<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin Login | Wonder Godoro Point</title>
        <link rel="stylesheet" href="{{ asset('css/splash.css') }}">
        <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    </head>
    <body class="auth-page">
        @include('components.splash')

        <main class="auth-shell">
            <section class="auth-intro" aria-labelledby="login-heading">
                <p class="auth-kicker">Wonder Godoro Point</p>
                <h1 id="login-heading">Welcome back.</h1>
                <p class="auth-description">Sign in to manage your mattress shop workspace.</p>
                <div class="auth-accent" aria-hidden="true"></div>
            </section>

            <section class="auth-card" aria-label="Administrator sign in">
                <div class="auth-brand">
                    <img src="{{ asset('img/logo.png') }}" alt="Wonder Godoro Point Mattress Shop">
                </div>

                <div class="auth-card-heading">
                    <p class="auth-eyebrow">Secure access</p>
                    <h2>Admin Login</h2>
                    <p>Use your account credentials to continue.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                    @csrf

                    <div class="auth-field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                        @error('email')
                            <p class="auth-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="password">Password</label>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                        @error('password')
                            <p class="auth-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="auth-remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Remember me on this device</span>
                    </label>

                    <button type="submit" class="auth-submit">Sign in <span aria-hidden="true">&rarr;</span></button>
                </form>
            </section>
        </main>

        <script src="{{ asset('js/splash.js') }}" defer></script>
    </body>
</html>