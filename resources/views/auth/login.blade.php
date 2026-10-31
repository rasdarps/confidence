@extends('layouts.app')
@section('title') {{'Login'}} @endsection


@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Sora:wght@500;600;700;800&display=swap');

    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    @media (min-height: 600px) {
        body {
            overflow-y: hidden;
        }
    }

    .navbar-laravel {
        display: none !important;
    }

    main {
        padding: 0 !important;
    }

    body {
        position: relative;
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        background-color: #090e1a !important;
        background-image:
            radial-gradient(circle at 12% 18%, rgba(16, 185, 129, 0.18) 0%, rgba(16, 185, 129, 0) 40%),
            radial-gradient(circle at 85% 82%, rgba(5, 150, 105, 0.20) 0%, rgba(5, 150, 105, 0) 42%),
            linear-gradient(130deg, rgba(9, 14, 26, 0.94) 0%, rgba(15, 23, 42, 0.88) 52%, rgba(9, 14, 26, 0.94) 100%),
            url('{{ asset('images/posbg1.jpg') }}') !important;
        background-repeat: no-repeat, no-repeat, no-repeat, no-repeat;
        background-size: auto, auto, cover, cover;
        background-position: left top, right bottom, center center, center center;
        background-attachment: fixed;
    }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        pointer-events: none;
        background-image: linear-gradient(rgba(255, 255, 255, 0.045) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.045) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.1) 78%);
        opacity: 0.28;
        z-index: 0;
    }

    #app {
        position: relative;
        z-index: 1;
    }

    .auth-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 0;
        box-sizing: border-box;
    }

    .auth-stage {
        width: min(1140px, 95vw);
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        align-items: center;
        gap: 28px;
    }

    .auth-hero {
        color: #f8fafc;
        padding: 14px 6px;
        animation: enterFade 0.6s ease-out;
    }

    .auth-kicker {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(15, 23, 42, 0.46);
        border: 1px solid rgba(226, 232, 240, 0.28);
        border-radius: 999px;
        font-size: 0.77rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 7px 12px;
    }

    .auth-kicker::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #10b981;
        box-shadow: 0 0 14px rgba(16, 185, 129, 0.85);
    }

    .auth-welcome {
        margin: 14px 0 0;
        color: #f8fafc;
        font-family: 'Sora', 'Segoe UI', sans-serif;
        font-size: clamp(2rem, 3.3vw, 3.2rem);
        line-height: 1.12;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-shadow: 0 10px 34px rgba(2, 6, 23, 0.5);
    }

    .auth-subtext {
        margin: 14px 0 0;
        max-width: 580px;
        color: rgba(241, 245, 249, 0.88);
        font-size: clamp(0.95rem, 1.2vw, 1.08rem);
        line-height: 1.65;
    }

    .auth-points {
        margin: 18px 0 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .auth-points li {
        background: rgba(15, 23, 42, 0.48);
        border: 1px solid rgba(226, 232, 240, 0.3);
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 0.83rem;
        font-weight: 700;
        color: #f8fafc;
    }

    .auth-card {
        width: 100%;
        max-width: 520px;
        justify-self: end;
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 14px;
        box-shadow: 0 20px 45px rgba(2, 12, 27, 0.45);
        overflow: hidden;
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(2px);
        position: relative;
        animation: enterUp 0.52s ease-out;
    }

    .auth-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, #10b981 0%, #059669 50%, #0f172a 100%);
    }

    .auth-header {
        padding: 24px 24px 20px;
        background: #0f172a;
        color: #ffffff;
    }

    .auth-header h5 {
        margin: 0;
        font-weight: 700;
        letter-spacing: 0.2px;
    }

    .auth-header p {
        margin: 6px 0 0;
        opacity: 0.85;
        font-size: 13px;
    }

    .auth-body {
        padding: 24px;
    }

    .auth-body label {
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
        display: block;
    }

    .auth-body .form-control {
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        min-height: 46px;
    }

    .auth-body .form-control:focus {
        border-color: #0f172a;
        box-shadow: 0 0 0 0.2rem rgba(15, 23, 42, 0.12);
    }

    .auth-actions {
        display: flex;
        align-items: center;
        width: 100%;
        margin-top: 22px;
    }

    .btn-auth {
        width: 100%;
        background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
        border: none;
        border-radius: 10px;
        color: #fff;
        font-weight: 700;
        font-size: 15px;
        padding: 12px 24px;
        letter-spacing: 0.3px;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-auth:hover {
        background: linear-gradient(135deg, #047857 0%, #059669 100%) !important;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
    }

    @keyframes enterUp {
        from {
            opacity: 0;
            transform: translateY(18px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes enterFade {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 991.98px) {
        body {
            background-attachment: scroll;
            background-size: auto, auto, cover, cover;
        }

        .auth-shell {
            min-height: auto;
            padding: 16px 0 10px;
        }

        .auth-stage {
            width: min(560px, 95vw);
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .auth-hero {
            text-align: center;
            padding: 4px 0;
        }

        .auth-kicker {
            margin: 0 auto;
        }

        .auth-welcome {
            margin-top: 10px;
            font-size: clamp(1.42rem, 5.2vw, 1.9rem);
            line-height: 1.2;
        }

        .auth-subtext {
            margin: 10px auto 0;
            font-size: 0.92rem;
            max-width: 500px;
        }

        .auth-points {
            justify-content: center;
            margin-top: 12px;
        }

        .auth-card {
            max-width: 560px;
            justify-self: stretch;
        }
    }

    @media (max-width: 575.98px) {
        .auth-shell {
            padding: 8px 0 6px;
        }

        .auth-stage {
            gap: 12px;
        }

        .auth-kicker {
            font-size: 0.68rem;
            padding: 6px 10px;
        }

        .auth-subtext,
        .auth-points {
            display: none;
        }

        .auth-card {
            border-radius: 12px;
        }

        .auth-header {
            padding: 18px 16px;
        }

        .auth-header h5 {
            font-size: 1rem;
        }

        .auth-header p {
            font-size: 12px;
        }

        .auth-body {
            padding: 16px;
        }

        .auth-body .form-control {
            min-height: 44px;
            font-size: 0.95rem;
        }
    }

    @media (min-width: 1400px) {
        .auth-stage {
            width: min(1260px, 94vw);
            gap: 36px;
        }

        .auth-card {
            max-width: 560px;
        }

        .auth-welcome {
            font-size: clamp(2.3rem, 3vw, 3.5rem);
        }
    }

    @media (min-width: 1800px) {
        .auth-stage {
            width: min(1360px, 90vw);
            grid-template-columns: 1.15fr 0.85fr;
        }
    }
</style>

<div class="auth-shell">
    <div class="auth-stage">
        <div class="auth-hero">
            <p class="auth-kicker">DarpPOS</p>
            <p class="auth-welcome">Welcome to DarpPOS Multi-outlet System</p>
            <p class="auth-subtext">Centralize warehouse inventory, track branch stock transfers, and monitor real-time sales across all your outlets from one unified dashboard.</p>
            <ul class="auth-points">
                <li>Central & Branch Stock</li>
                <li>Inter-Outlet Transfers</li>
                <li>Consolidated Sales</li>
            </ul>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <h5>{{ __('Admin Login') }}</h5>
                <p>Sign in to access your dashboard.</p>
            </div>

            <div class="auth-body">
                <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                    @csrf

                    <div class="mb-3">
                        @php
                            $loginErrorMessage = $errors->first('login') ?: $errors->first('email') ?: $errors->first('username');
                            $isLockoutMessage = $loginErrorMessage && \Illuminate\Support\Str::contains($loginErrorMessage, 'Too many login attempts');
                            $isCredentialMismatch = $loginErrorMessage && \Illuminate\Support\Str::contains($loginErrorMessage, 'These credentials do not match our records.');
                            $lockoutAlertMessage = null;

                            if ($isLockoutMessage) {
                                $lockoutMinutes = 1;

                                if (preg_match('/(\d+)\s*seconds?/i', $loginErrorMessage, $matches)) {
                                    $lockoutMinutes = max(1, (int) ceil(((int) $matches[1]) / 60));
                                } elseif (preg_match('/(\d+)\s*minutes?/i', $loginErrorMessage, $matches)) {
                                    $lockoutMinutes = max(1, (int) $matches[1]);
                                }

                                $lockoutAlertMessage = str_replace(':minutes', (string) $lockoutMinutes, 'Too many login attempts. Please wait :minutes minutes before trying again');
                            }

                            $topAuthAlertMessage = $isLockoutMessage ? $lockoutAlertMessage : ($isCredentialMismatch ? $loginErrorMessage : null);
                        @endphp

                        <div id="loginTopAlert" class="alert {{ $isLockoutMessage ? 'alert-warning' : 'alert-danger' }} {{ $topAuthAlertMessage ? '' : 'd-none' }}" role="alert" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <span>
                                <strong>Login failed:</strong>
                                <span id="loginTopAlertText">{{ $topAuthAlertMessage ? ' ' . $topAuthAlertMessage : '' }}</span>
                            </span>
                            <button type="button" id="loginTopAlertClose" aria-label="Close" style="border:none; background:transparent; font-size:20px; line-height:1; font-weight:700; color:inherit; cursor:pointer; padding:0 4px;">&times;</button>
                        </div>

                        <label for="login">{{ __('Email or Username') }}</label>
                        <input id="login" type="text" class="form-control @error('login') @error('email') @error('username') is-invalid @enderror @enderror @enderror" name="login" value="{{ old('login') }}" required autocomplete="login" autofocus placeholder="Enter email or username">

                        @error('login')
                            @if(!\Illuminate\Support\Str::contains($message, 'Too many login attempts') && !\Illuminate\Support\Str::contains($message, 'These credentials do not match our records.'))
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @endif
                        @enderror
                        @error('email')
                            @if(!\Illuminate\Support\Str::contains($message, 'Too many login attempts') && !\Illuminate\Support\Str::contains($message, 'These credentials do not match our records.'))
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @endif
                        @enderror
                        @error('username')
                            @if(!\Illuminate\Support\Str::contains($message, 'Too many login attempts') && !\Illuminate\Support\Str::contains($message, 'These credentials do not match our records.'))
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @endif
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password">{{ __('Password') }}</label>
                        <div class="position-relative">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Enter password" style="padding-right: 44px;">
                            <button type="button" id="togglePasswordBtn" class="btn btn-link position-absolute" style="top: 50%; right: 4px; transform: translateY(-50%); color: #64748b; text-decoration: none; padding: 6px 10px; z-index: 5; border: none; background: transparent; display: inline-flex; align-items: center;" aria-label="Toggle password visibility">
                                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="eyeOffIcon" class="d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        <small class="form-text text-muted d-block mt-1">For your security, 3 failed login attempts will lock the system for 3 minutes.</small>

                        @error('password')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            {{ __('Remember Me') }}
                        </label>
                    </div>

                    <div class="auth-actions">
                        <button id="loginSubmitBtn" type="submit" class="btn btn-auth">
                            {{ __('Login') }}
                        </button>
                    </div>

                    <div class="text-center mt-3" style="font-size:12px; color:#64748b; font-weight:600;">
                        DarpPOS V 2.0
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @php
        $authNotifyMessage = $errors->first('login')
            ?: $errors->first('email')
            ?: $errors->first('username')
            ?: $errors->first('password');

        if (!$authNotifyMessage && session('error')) {
            $authNotifyMessage = session('error');
        }
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Notify.init({
                    position: 'right-top',
                    timeout: 3000,
                    showOnlyTheLastOne: true
                });
            }

            var form = document.getElementById('loginForm');
            var loginInput = document.getElementById('login');
            var passwordInput = document.getElementById('password');
            var submitBtn = document.getElementById('loginSubmitBtn');
            var topAlert = document.getElementById('loginTopAlert');
            var topAlertText = document.getElementById('loginTopAlertText');
            var topAlertClose = document.getElementById('loginTopAlertClose');
            var togglePasswordBtn = document.getElementById('togglePasswordBtn');
            var eyeIcon = document.getElementById('eyeIcon');
            var eyeOffIcon = document.getElementById('eyeOffIcon');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function () {
                    var isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    if (eyeIcon && eyeOffIcon) {
                        eyeIcon.classList.toggle('d-none', isPassword);
                        eyeOffIcon.classList.toggle('d-none', !isPassword);
                    }
                });
            }
            var authNotifyMessage = @json($authNotifyMessage);
            var hasShownAuthNotify = false;

            function notify(msg) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure(msg);
                } else {
                    alert(msg);
                }
            }

            function showTopAlert(msg, type) {
                if (!topAlert) {
                    return;
                }

                var finalMessage = String(msg || '').replace(/^login failed\s*:\s*/i, '');
                topAlert.classList.remove('d-none', 'alert-warning', 'alert-danger');
                topAlert.classList.add(type === 'warning' ? 'alert-warning' : 'alert-danger');
                if (topAlertText) {
                    topAlertText.textContent = ' ' + finalMessage;
                }
            }

            if (topAlertClose && topAlert) {
                topAlertClose.addEventListener('click', function () {
                    topAlert.classList.add('d-none');
                });
            }

            function showAuthNotifyOnce() {
                if (!authNotifyMessage || hasShownAuthNotify) {
                    return;
                }

                hasShownAuthNotify = true;
                notify(authNotifyMessage);
            }

            if (authNotifyMessage) {
                showAuthNotifyOnce();
                window.setTimeout(showAuthNotifyOnce, 200);
                window.addEventListener('load', showAuthNotifyOnce, { once: true });
            }

            if (!form || !loginInput || !passwordInput || !submitBtn) {
                return;
            }

            form.addEventListener('submit', function (e) {
                var login = (loginInput.value || '').trim();
                var password = (passwordInput.value || '').trim();

                if (!login) {
                    e.preventDefault();
                    showTopAlert('Please enter your email or username.', 'danger');
                    notify('Please enter your email or username.');
                    loginInput.focus();
                    return;
                }

                if (!password) {
                    e.preventDefault();
                    showTopAlert('Please enter your password.', 'danger');
                    notify('Please enter your password.');
                    passwordInput.focus();
                    return;
                }

                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Loading.standard('Logging in...');
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Please wait...';
            });

            window.addEventListener('pageshow', function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
            });
        });
    </script>
@endsection
