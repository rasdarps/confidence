@extends('layouts.app')
@section('title') {{'Reset Password'}} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card" style="color:#000; font-weight:bold;">
                <div class="card-header" style="background-color:#000; color:#fff">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}" id="resetForm" novalidate>
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary" style="background-color:#0F766E; color:#fff; border:none;">
                                    {{ __('Reset Password') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
    <!-- Notiflix CSS & JS -->
    <link rel="stylesheet" href="{{ asset('notiflix/notiflix-3.2.8.min.css') }}" />
    <script src="{{ asset('notiflix/notiflix-3.2.8.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#resetForm').on('submit', function(e) {
                e.preventDefault();
                var email = $('#email').val().trim();
                var password = $('#password').val().trim();
                var passwordConfirm = $('#password-confirm').val().trim();
                function notify(msg) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Notify.failure(msg, {
                            timeout: 3000,
                            showOnlyTheLastOne: true,
                            position: 'right-top',
                        });
                    } else {
                        alert(msg);
                    }
                }
                if (!email) {
                    notify('Please enter your email address.');
                    $('#email').focus();
                    return false;
                }
                if (!password) {
                    notify('Please enter your new password.');
                    $('#password').focus();
                    return false;
                }
                if (!passwordConfirm) {
                    notify('Please confirm your new password.');
                    $('#password-confirm').focus();
                    return false;
                }
                if (password.length < 6) {
                    notify('Password must be at least 6 characters.');
                    $('#password').focus();
                    return false;
                }
                if (password !== passwordConfirm) {
                    notify('Passwords do not match.');
                    $('#password-confirm').focus();
                    return false;
                }

                // Show Notiflix loading
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Loading.standard('Resetting password...');
                }

                var form = this;
                $.ajax({
                    url: $(form).attr('action'),
                    type: $(form).attr('method'),
                    data: $(form).serialize(),
                    success: function(data, status, xhr) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Loading.remove();
                        }
                        // If reset is successful, Laravel usually redirects (status 200 or 302)
                        if (xhr.responseURL && xhr.responseURL !== window.location.href) {
                            window.location.href = xhr.responseURL;
                        } else if (data && typeof data === 'object' && data.message) {
                            Notiflix.Notify.success(data.message);
                        } else {
                            Notiflix.Notify.success('Password reset successful!');
                            setTimeout(function(){ location.reload(); }, 1000);
                        }
                    },
                    error: function(xhr) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Loading.remove();
                        }
                        let msg = 'Password reset failed. Please check your input.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        notify(msg);
                    }
                });
            });
        });
    </script>
@endsection
@section('scripts')

@endsection
