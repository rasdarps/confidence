@extends('layouts.app')
@section('title') {{'Login'}} @endsection


@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Sora:wght@500;600;700;800&display=swap');

    body {
        position: relative;
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        background-color: #0b1f2a !important;
        background-image:
            radial-gradient(circle at 12% 18%, rgba(255, 193, 7, 0.24) 0%, rgba(255, 193, 7, 0) 38%),
            radial-gradient(circle at 85% 82%, rgba(56, 189, 248, 0.22) 0%, rgba(56, 189, 248, 0) 40%),
            linear-gradient(130deg, rgba(7, 31, 43, 0.9) 0%, rgba(15, 23, 42, 0.82) 52%, rgba(9, 35, 48, 0.9) 100%),
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
        min-height: calc(100vh - 90px);
        display: flex;
        justify-content: center;
        padding: 28px 0;
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
        background: #38bdf8;
        box-shadow: 0 0 14px rgba(56, 189, 248, 0.85);
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
        background: linear-gradient(90deg, #f59e0b 0%, #38bdf8 50%, #0f172a 100%);
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
        justify-content: flex-start;
        gap: 10px;
        margin-top: 18px;
    }

    .btn-auth {
        background: #0f172a;
        border: none;
        border-radius: 10px;
        color: #fff;
        font-weight: 600;
        padding: 10px 18px;
        min-width: 126px;
    }

    .btn-auth:hover {
        background: #1e293b;
        color: #fff;
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
            <p class="auth-kicker">Rasdarps POS</p>
            <p class="auth-welcome">Welcome to Rasdarps Enterprise POS System</p>
            <p class="auth-subtext">Manage inventory, monitor sales, and keep operations in sync from one dashboard built for busy retail teams.</p>
            <ul class="auth-points">
                <li>Fast Stock Tracking</li>
                <li>Daily Sales Insight</li>
                <li>Secure Access</li>
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
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Enter password">
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
                        DarPOS V 1.0
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
