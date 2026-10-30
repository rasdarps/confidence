@extends('layout.main')
@section('title') {{ 'Change Password' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-8 col-md-10 col-12">
                <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <span class="card-title mb-0 font-weight-bold" style="font-size: 19px; color: #0F766E;">
                            <i class="fas fa-key me-1 mr-1"></i> Update Security Password
                        </span>
                        <a href="{{ route('admin.profile') }}" class="btn btn-modern-header">
                            <i class="fas fa-arrow-left me-1 mr-1"></i> Back to Profile
                        </a>
                    </div>

                    <div class="card-body p-4">
                        @if (session('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-1 mr-1"></i> {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form method="post" action="{{ route('update.password') }}" id="changePasswordForm">
                            @csrf

                            {{-- Current Password --}}
                            <div class="form-group mb-3">
                                <label for="oldpassword" class="font-weight-bold text-dark">
                                    Current Password <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input name="oldpassword" class="form-control" type="password" id="oldpassword" placeholder="Enter your current password" required autocomplete="current-password">
                                </div>
                            </div>

                            {{-- New Password --}}
                            <div class="form-group mb-3">
                                <label for="newpassword" class="font-weight-bold text-dark">
                                    New Password <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input name="newpassword" class="form-control" type="password" id="newpassword" style="padding-right: 42px;" placeholder="Create new strong password" required autocomplete="new-password">
                                    <span id="password_valid_tick" class="position-absolute" style="display: none; right: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #10b981; pointer-events: none; z-index: 5;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                </div>

                                {{-- Password Requirements Box --}}
                                <div class="mt-2 p-3 bg-light rounded border" style="font-size: 12px; background-color: #f8fafc !important;">
                                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                        <span class="text-muted font-weight-bold" style="font-size: 11.5px;">Password Requirements:</span>
                                        <span id="password_status_badge" class="badge bg-secondary text-white" style="font-size: 10px; padding: 4px 8px;">Required</span>
                                    </div>
                                    <div class="row gx-2 gy-1">
                                        <div class="col-sm-6">
                                            <div id="req_length" class="text-muted d-flex align-items-center py-1">
                                                <i class="fas fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px; text-align: center;"></i>
                                                <span>Min. 6 characters</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="req_uppercase" class="text-muted d-flex align-items-center py-1">
                                                <i class="fas fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px; text-align: center;"></i>
                                                <span>1 uppercase (A-Z)</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="req_lowercase" class="text-muted d-flex align-items-center py-1">
                                                <i class="fas fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px; text-align: center;"></i>
                                                <span>1 lowercase (a-z)</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="req_number" class="text-muted d-flex align-items-center py-1">
                                                <i class="fas fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px; text-align: center;"></i>
                                                <span>1 number (0-9)</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="req_symbol" class="text-muted d-flex align-items-center py-1">
                                                <i class="fas fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px; text-align: center;"></i>
                                                <span>1 symbol (!@#$%^&*...)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="password_format_tick_msg" class="mt-2 text-success font-weight-bold align-items-center" style="display: none; font-size: 13px;">
                                    <i class="fas fa-check-circle fa-lg me-1 mr-1 text-success"></i>
                                    <span>Password format requirements met!</span>
                                </div>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="form-group mb-4">
                                <label for="confirm_password" class="font-weight-bold text-dark">
                                    Confirm New Password <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input name="confirm_password" class="form-control" type="password" id="confirm_password" style="padding-right: 42px;" placeholder="Re-type new password" required autocomplete="new-password">
                                    <span id="confirm_valid_tick" class="position-absolute" style="display: none; right: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #10b981; pointer-events: none; z-index: 5;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                </div>
                                <div id="confirm_feedback" class="mt-2 font-size-12" style="display: none;"></div>
                            </div>

                            {{-- Form Actions --}}
                            <div class="row pt-3 border-top">
                                <div class="col-12 text-center">
                                    <a href="{{ route('admin.profile') }}" class="btn btn-modern-cancel mr-2 me-2">
                                        <i class="fas fa-times me-1 mr-1"></i> Cancel
                                    </a>
                                    <button type="submit" id="btnSubmit" class="btn btn-modern-primary">
                                        <i class="fas fa-check-circle me-1 mr-1"></i> Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Immediate inline execution so listeners attach even before full scripts bundle resolves --}}
<script type="text/javascript">
    (function () {
        function checkComplexity(pwd) {
            return {
                length: pwd.length >= 6,
                uppercase: /[A-Z]/.test(pwd),
                lowercase: /[a-z]/.test(pwd),
                number: /[0-9]/.test(pwd),
                symbol: /[^A-Za-z0-9]/.test(pwd)
            };
        }

        function setReq(id, met) {
            var el = document.getElementById(id);
            if (!el) return;
            var icon = el.querySelector('.req-icon');
            if (met) {
                el.classList.remove('text-muted');
                el.classList.add('text-success', 'font-weight-bold');
                if (icon) {
                    icon.className = 'fas fa-check-circle me-1 mr-1 req-icon text-success';
                    icon.style.fontSize = '12px';
                }
            } else {
                el.classList.remove('text-success', 'font-weight-bold');
                el.classList.add('text-muted');
                if (icon) {
                    icon.className = 'fas fa-circle me-1 mr-1 req-icon text-muted';
                    icon.style.fontSize = '8px';
                }
            }
        }

        function runEval() {
            var newPwdEl = document.getElementById('newpassword');
            var confirmPwdEl = document.getElementById('confirm_password');
            if (!newPwdEl) return;

            var pwd = newPwdEl.value || '';
            var confirmPwd = confirmPwdEl ? (confirmPwdEl.value || '') : '';

            var c = checkComplexity(pwd);
            setReq('req_length', c.length);
            setReq('req_uppercase', c.uppercase);
            setReq('req_lowercase', c.lowercase);
            setReq('req_number', c.number);
            setReq('req_symbol', c.symbol);

            var allMet = c.length && c.uppercase && c.lowercase && c.number && c.symbol;

            var tickEl = document.getElementById('password_valid_tick');
            var tickMsg = document.getElementById('password_format_tick_msg');
            var badge = document.getElementById('password_status_badge');

            if (allMet) {
                if (tickEl) tickEl.style.display = 'inline-block';
                if (tickMsg) tickMsg.style.display = 'flex';
                if (badge) {
                    badge.className = 'badge bg-success text-white';
                    badge.innerHTML = '<i class="fas fa-check me-1 mr-1"></i> Format Met';
                }
                newPwdEl.classList.remove('is-invalid');
                newPwdEl.classList.add('is-valid');
            } else {
                if (tickEl) tickEl.style.display = 'none';
                if (tickMsg) tickMsg.style.display = 'none';
                if (badge) {
                    badge.className = 'badge ' + (pwd.length > 0 ? 'bg-danger text-white' : 'bg-secondary text-white');
                    badge.textContent = pwd.length > 0 ? 'Incomplete' : 'Required';
                }
                if (pwd.length > 0) {
                    newPwdEl.classList.remove('is-valid');
                    newPwdEl.classList.add('is-invalid');
                } else {
                    newPwdEl.classList.remove('is-valid', 'is-invalid');
                }
            }

            var confirmTick = document.getElementById('confirm_valid_tick');
            var confirmFeedback = document.getElementById('confirm_feedback');

            if (confirmPwd.length > 0) {
                if (allMet && confirmPwd === pwd) {
                    if (confirmTick) confirmTick.style.display = 'inline-block';
                    if (confirmPwdEl) {
                        confirmPwdEl.classList.remove('is-invalid');
                        confirmPwdEl.classList.add('is-valid');
                    }
                    if (confirmFeedback) {
                        confirmFeedback.style.display = 'block';
                        confirmFeedback.className = 'mt-2 font-size-12 text-success font-weight-bold';
                        confirmFeedback.innerHTML = '<i class="fas fa-check-circle me-1 mr-1 text-success"></i> Passwords match!';
                    }
                } else {
                    if (confirmTick) confirmTick.style.display = 'none';
                    if (confirmPwdEl) {
                        confirmPwdEl.classList.remove('is-valid');
                        confirmPwdEl.classList.add('is-invalid');
                    }
                    if (confirmFeedback) {
                        confirmFeedback.style.display = 'block';
                        confirmFeedback.className = 'mt-2 font-size-12 text-danger font-weight-bold';
                        confirmFeedback.innerHTML = '<i class="fas fa-times-circle me-1 mr-1 text-danger"></i> ' + (confirmPwd !== pwd ? 'Passwords do not match' : 'Password format requirements not met yet');
                    }
                }
            } else {
                if (confirmTick) confirmTick.style.display = 'none';
                if (confirmPwdEl) confirmPwdEl.classList.remove('is-valid', 'is-invalid');
                if (confirmFeedback) {
                    confirmFeedback.style.display = 'none';
                    confirmFeedback.innerHTML = '';
                }
            }

            return allMet;
        }

        window.__evaluatePasswordChange = runEval;

        function attachEvents() {
            var newEl = document.getElementById('newpassword');
            var confEl = document.getElementById('confirm_password');
            if (newEl) {
                newEl.addEventListener('input', runEval);
                newEl.addEventListener('keyup', runEval);
                newEl.addEventListener('change', runEval);
                newEl.addEventListener('paste', function () { setTimeout(runEval, 20); });
            }
            if (confEl) {
                confEl.addEventListener('input', runEval);
                confEl.addEventListener('keyup', runEval);
                confEl.addEventListener('change', runEval);
                confEl.addEventListener('paste', function () { setTimeout(runEval, 20); });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', attachEvents);
        } else {
            attachEvents();
        }
    })();
</script>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        if (typeof window.__evaluatePasswordChange === 'function') {
            $('#newpassword, #confirm_password').on('input keyup change paste', function () {
                window.__evaluatePasswordChange();
            });
            window.__evaluatePasswordChange();
        }

        $('#changePasswordForm').on('submit', function (e) {
            var oldPwd = $('#oldpassword').val();
            if (!oldPwd) {
                e.preventDefault();
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter your current password');
                } else {
                    alert('Please enter your current password');
                }
                $('#oldpassword').addClass('is-invalid').focus();
                return false;
            }

            var newPwd = $('#newpassword').val() || '';
            var hasLength = newPwd.length >= 6;
            var hasUpper = /[A-Z]/.test(newPwd);
            var hasLower = /[a-z]/.test(newPwd);
            var hasNumber = /[0-9]/.test(newPwd);
            var hasSymbol = /[^A-Za-z0-9]/.test(newPwd);

            if (!hasLength || !hasUpper || !hasLower || !hasNumber || !hasSymbol) {
                e.preventDefault();
                var msg = 'New password must meet all security requirements: minimum 6 characters, at least 1 uppercase, at least 1 lowercase, at least 1 number, and at least 1 symbol.';
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure(msg);
                } else {
                    alert(msg);
                }
                $('#newpassword').addClass('is-invalid').focus();
                return false;
            }

            if (newPwd === oldPwd) {
                e.preventDefault();
                var msgDiff = 'New password must be different from current password';
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure(msgDiff);
                } else {
                    alert(msgDiff);
                }
                $('#newpassword').addClass('is-invalid').focus();
                return false;
            }

            var confirmPwd = $('#confirm_password').val() || '';
            if (newPwd !== confirmPwd) {
                e.preventDefault();
                var msgMatch = 'New password and confirm password do not match';
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure(msgMatch);
                } else {
                    alert(msgMatch);
                }
                $('#confirm_password').addClass('is-invalid').focus();
                return false;
            }

            return true;
        });

        $('input').on('input change', function () {
            $(this).removeClass('is-invalid');
        });
    });
</script>
@endsection
