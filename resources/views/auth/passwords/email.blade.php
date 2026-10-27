@extends('layouts.app')
@section('title') {{'Login'}} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="container" style="">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card" style="color:#000; font-weight:bold;">
                <div class="card-header" style="background-color:#000; color:#fff">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" id="passwordResetForm" novalidate>
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary" style="background-color:#0F766E; color:#fff; border:none;">
                                    {{ __('Send Password Reset Link') }}
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
    $('#passwordResetForm').on('submit', function(e) {
        var email = $('#email').val().trim();
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
            e.preventDefault();
            return false;
        }
        // Simple email format check
        var emailPattern = /^\S+@\S+\.\S+$/;
        if (!emailPattern.test(email)) {
            notify('Please enter a valid email address.');
            $('#email').focus();
            e.preventDefault();
            return false;
        }
        if (typeof Notiflix !== 'undefined') {
            Notiflix.Loading.standard('Sending reset link...');
        }
    });
});
</script>
@endsection
