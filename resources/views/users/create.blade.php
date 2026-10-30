@extends('layout.main')
@section('title') {{ 'Create User' }} @endsection

@section('content')

    <div class="page-content">
        <div class="container-fluid">

            <!-- Breadcrumbs -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">User Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">Create User</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Multi-Outlet Central Governance Guidance -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info border-0 shadow-sm mb-0" style="background: #e6f7f5; border-left: 4px solid #0F766E !important; color: #034141;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="mdi mdi-shield-account-outline mr-2 font-size-20" style="color: #0F766E;"></i>
                            <strong class="font-size-15" style="color: #034141;">Central Multi-Outlet User Governance</strong>
                            <span class="badge ml-2" style="background-color: #0F766E; color: #fff;">Central Managed</span>
                        </div>
                        <p class="mb-0 font-size-13" style="line-height: 1.6;">
                            User creation is centralized. Every account must be explicitly assigned to an outlet station:
                            <br>
                            &bull; <strong>Central / Headquarters Outlet:</strong> Enterprise administrative staff with authority across all branches, master catalog pricing, warehouse transfers, and consolidated reporting.
                            <br>
                            &bull; <strong>Branch Station Assigned:</strong> Store staff and cashiers scoped strictly to their physical branch inventory, localized sales, and cash drawers.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                        <div class="card-header d-flex justify-content-between align-items-center py-3" style="background: linear-gradient(135deg, #034141 0%, #0F766E 100%); color: #fff;">
                            <div>
                                <h5 class="card-title mb-0 text-white font-size-16">
                                    <i class="mdi mdi-account-plus-outline mr-1"></i> Create New User Account
                                </h5>
                                <small class="text-white-50">Configure credentials, roles, and branch assignment</small>
                            </div>

                            <a href="{{ route('users.index') }}" class="btn btn-modern-header">
                                <i class="mdi mdi-arrow-left mr-1"></i> Back to Users
                            </a>
                        </div>

                        <div class="card-body p-4 bg-white">

                            <form action="{{ route('users.store') }}" method="POST" id="myForm" enctype="multipart/form-data" novalidate>
                                @csrf

                                {{-- backend error alerts --}}
                                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="mdi mdi-alert-circle mr-1"></i>
                                        <strong>Please correct the following errors:</strong>
                                    </div>
                                    <ul id="errors" class="mb-0 pl-3"></ul>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="name" class="font-weight-bold text-dark">
                                                Full Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. John Doe" onkeypress="return isCharKey(event)">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="username" class="font-weight-bold text-dark">
                                                Username <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="username" name="username" class="form-control" value="{{ old('username') }}" placeholder="e.g. jdoe" onkeypress="return isCharKey(event)">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="phone" class="font-weight-bold text-dark">
                                                Phone Number <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="10-digit mobile number" pattern="\d{10}" maxlength="10" minlength="10" onkeypress="return isNumberKey(event)">
                                            <small class="text-muted">Must be exactly 10 numeric digits</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="email" class="font-weight-bold text-dark">
                                                Email Address <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="name@company.com">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="password" class="font-weight-bold text-dark">
                                                Password <span class="text-danger">*</span>
                                            </label>
                                            <div class="position-relative">
                                                <input type="password" id="password" name="password" class="form-control" style="padding-right: 40px;" placeholder="Enter secure password" autocomplete="new-password">
                                                <span id="password_valid_tick" class="position-absolute d-none" style="right: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #10b981; pointer-events: none;">
                                                    <i class="fa fa-check-circle"></i>
                                                </span>
                                            </div>
                                            <!-- Live Password Requirements Box -->
                                            <div id="password_checklist" class="mt-2 p-2 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px;">
                                                <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom">
                                                    <span class="text-muted font-weight-bold" style="font-size: 11.5px;">Password Requirements:</span>
                                                    <span id="password_status_badge" class="badge bg-secondary text-white" style="font-size: 10px; padding: 3px 7px;">Required</span>
                                                </div>
                                                <div class="row gx-2 gy-1">
                                                    <div class="col-sm-6">
                                                        <div id="req_length" class="text-muted d-flex align-items-center py-1">
                                                            <i class="fa fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px;"></i>
                                                            <span>Min. 6 characters</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div id="req_uppercase" class="text-muted d-flex align-items-center py-1">
                                                            <i class="fa fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px;"></i>
                                                            <span>1 uppercase (A-Z)</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div id="req_lowercase" class="text-muted d-flex align-items-center py-1">
                                                            <i class="fa fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px;"></i>
                                                            <span>1 lowercase (a-z)</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div id="req_number" class="text-muted d-flex align-items-center py-1">
                                                            <i class="fa fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px;"></i>
                                                            <span>1 number (0-9)</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div id="req_symbol" class="text-muted d-flex align-items-center py-1">
                                                            <i class="fa fa-circle me-1 mr-1 req-icon" style="font-size: 8px; width: 14px;"></i>
                                                            <span>1 symbol (!@#$%...)</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Tick icon indicator when password format is met -->
                                            <div id="password_format_tick_msg" class="d-none mt-2 text-success font-weight-bold align-items-center" style="font-size: 12.5px;">
                                                <i class="fa fa-check-circle fa-lg me-1 mr-1 text-success"></i>
                                                <span>Password format requirements met!</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="confirm_password" class="font-weight-bold text-dark">
                                                Confirm Password <span class="text-danger">*</span>
                                            </label>
                                            <div class="position-relative">
                                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" style="padding-right: 40px;" placeholder="Re-type password" autocomplete="new-password">
                                                <span id="confirm_valid_tick" class="position-absolute d-none" style="right: 12px; top: 50%; transform: translateY(-50%); font-size: 17px; color: #10b981; pointer-events: none;">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            </div>
                                            <div id="confirm_feedback" class="mt-2 font-size-12 d-none"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="roles" class="font-weight-bold text-dark">
                                                Role Assignment <span class="text-danger">*</span>
                                            </label>
                                            <select id="roles" name="roles[]" class="form-control select2" multiple data-placeholder="Select one or more roles...">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role }}" {{ (collect(old('roles'))->contains($role)) ? 'selected' : '' }}>
                                                        {{ $role }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Users can have multiple roles. Selecting 'Cashier' enforces an assigned branch.</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="user_type" class="font-weight-bold text-dark">
                                                User Type <span class="text-danger">*</span>
                                            </label>
                                            <select id="user_type" name="user_type" class="form-control">
                                                <option value="" disabled {{ old('user_type') ? '' : 'selected' }}>Select User Type</option>
                                                <option value="Admin" {{ old('user_type') == 'Admin' ? 'selected' : '' }}>Admin (Administrative Access)</option>
                                                <option value="User" {{ old('user_type') == 'User' ? 'selected' : '' }}>User (Standard Staff / Cashier)</option>
                                            </select>
                                            <small class="text-muted">Use 'User' for operational store staff and cashiers.</small>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <div class="p-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                            <div class="form-group mb-0">
                                                <label for="outlet_id" class="font-weight-bold text-dark d-flex align-items-center">
                                                    <span>Assigned Outlet / Branch Station</span>
                                                    <span class="text-danger ml-1">*</span>
                                                </label>
                                                <select id="outlet_id" name="outlet_id" class="form-control select2" data-placeholder="Select assigned outlet...">
                                                    <option value="" disabled {{ old('outlet_id') ? '' : 'selected' }}>Select Assigned Outlet / Branch Station</option>
                                                    @foreach($outlets as $outlet)
                                                        <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                                            {{ $outlet->name }} ({{ $outlet->code }}) {{ $outlet->is_central ? '• Central Headquarters (Enterprise Scope)' : '• Branch Station' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted mt-1 d-block">
                                                    Select <strong>Central Branch (HQ)</strong> for enterprise directors, accountants, and warehouse managers. Select a physical branch for store staff and cashiers.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                {{-- form rows end --}}

                                <div class="row mt-4 pt-3 border-top">
                                    <div class="col-12 text-right">
                                        <a href="{{ route('users.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                            <i class="fas fa-times me-1 mr-1"></i> Cancel
                                        </a>
                                        <button type="submit" id="btnSubmit" class="btn btn-modern-primary">
                                            <i class="fas fa-user-plus me-1 mr-1"></i> Create User Account
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

@endsection

@section('styles')
<style>
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .form-control:focus {
        border-color: #0F766E !important;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.25) !important;
    }

    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: 4px;
        min-height: 38px;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #0F766E !important;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.25) !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #0F766E !important;
        border-color: #0F766E !important;
        color: #fff !important;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 13px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff !important;
        margin-right: 5px;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #0F766E !important;
        color: #fff !important;
    }

    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.2) !important;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        // Initialize Select2 components
        if ($.fn.select2) {
            $('#roles').select2({
                placeholder: 'Select one or more roles...',
                width: '100%',
                allowClear: true
            });

            $('#outlet_id').select2({
                placeholder: 'Select assigned outlet or branch station...',
                width: '100%',
                allowClear: false
            });
        }

        var autoSetByCashier = false;
        var previousUserType = $('#user_type').val() || '';

        $('#user_type').on('change', function () {
            // When user manually changes user_type, cancel automatic revert
            autoSetByCashier = false;
            previousUserType = $(this).val() || '';
        });

        // Dynamically suggest User type for cashier role
        function syncCashierRequirements() {
            var selectedRoles = $('#roles').val() || [];
            var isCashier = selectedRoles.indexOf('Cashier') !== -1;

            if (isCashier) {
                if ($('#user_type').val() !== 'User') {
                    previousUserType = $('#user_type').val() || '';
                    $('#user_type').val('User');
                    autoSetByCashier = true;
                }
            } else {
                if (autoSetByCashier) {
                    $('#user_type').val(previousUserType);
                    autoSetByCashier = false;
                }
            }
        }

        $('#roles').on('change', syncCashierRequirements);
        syncCashierRequirements();

        // Real-time email validation
        $('#email').on('input blur', function() {
            var email = $(this).val();
            var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

            if (email && !emailRegex.test(email)) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Please enter a valid email address (e.g., test@example.com)</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        // Real-time phone validation
        $('#phone').on('input', function() {
            var phone = $(this).val();
            if (phone && phone.length !== 10) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Phone number must be exactly 10 digits</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        // Password Complexity Functions
        function checkPasswordComplexity(pwd) {
            return {
                length: (pwd || '').length >= 6,
                uppercase: /[A-Z]/.test(pwd || ''),
                lowercase: /[a-z]/.test(pwd || ''),
                number: /[0-9]/.test(pwd || ''),
                symbol: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(pwd || '')
            };
        }

        function updateRequirement(elId, isMet) {
            var $el = $('#' + elId);
            var $icon = $el.find('.req-icon');
            if (isMet) {
                $el.removeClass('text-muted text-secondary').addClass('text-success font-weight-bold');
                $icon.removeClass('fa-circle text-muted text-secondary').addClass('fa-check-circle text-success').css('font-size', '13px');
            } else {
                $el.removeClass('text-success font-weight-bold').addClass('text-muted');
                $icon.removeClass('fa-check-circle text-success').addClass('fa-circle text-muted').css('font-size', '8px');
            }
        }

        function evaluatePassword(isEditMode) {
            var pwd = $('#password').val() || '';
            var confirmPwd = $('#confirm_password').val() || '';

            if (isEditMode && pwd === '' && confirmPwd === '') {
                $('#password_valid_tick').addClass('d-none');
                $('#password_format_tick_msg').removeClass('d-flex').addClass('d-none');
                $('#password_status_badge').removeClass('bg-success bg-danger').addClass('bg-secondary').text('Optional');
                updateRequirement('req_length', false);
                updateRequirement('req_uppercase', false);
                updateRequirement('req_lowercase', false);
                updateRequirement('req_number', false);
                updateRequirement('req_symbol', false);
                $('#confirm_valid_tick').addClass('d-none');
                $('#confirm_feedback').addClass('d-none').empty();
                $('#password, #confirm_password').removeClass('is-invalid is-valid');
                return true;
            }

            var res = checkPasswordComplexity(pwd);
            updateRequirement('req_length', res.length);
            updateRequirement('req_uppercase', res.uppercase);
            updateRequirement('req_lowercase', res.lowercase);
            updateRequirement('req_number', res.number);
            updateRequirement('req_symbol', res.symbol);

            var allMet = res.length && res.uppercase && res.lowercase && res.number && res.symbol;

            if (allMet) {
                $('#password_status_badge').removeClass('bg-secondary bg-danger').addClass('bg-success').html('<i class="fa fa-check me-1 mr-1"></i> Format Met');
                $('#password_valid_tick').removeClass('d-none');
                $('#password_format_tick_msg').removeClass('d-none').addClass('d-flex');
                $('#password').removeClass('is-invalid').addClass('is-valid');
            } else {
                $('#password_status_badge').removeClass('bg-success').addClass(pwd.length > 0 ? 'bg-danger' : 'bg-secondary').text(pwd.length > 0 ? 'Incomplete' : (isEditMode ? 'Optional' : 'Required'));
                $('#password_valid_tick').addClass('d-none');
                $('#password_format_tick_msg').removeClass('d-flex').addClass('d-none');
                if (pwd.length > 0) {
                    $('#password').removeClass('is-valid').addClass('is-invalid');
                } else {
                    $('#password').removeClass('is-valid is-invalid');
                }
            }

            if (confirmPwd.length > 0) {
                if (allMet && confirmPwd === pwd) {
                    $('#confirm_valid_tick').removeClass('d-none');
                    $('#confirm_password').removeClass('is-invalid').addClass('is-valid');
                    $('#confirm_feedback').removeClass('d-none text-danger').addClass('text-success').html('<i class="fa fa-check-circle me-1 mr-1"></i> Passwords match');
                } else {
                    $('#confirm_valid_tick').addClass('d-none');
                    $('#confirm_password').removeClass('is-valid').addClass('is-invalid');
                    $('#confirm_feedback').removeClass('d-none text-success').addClass('text-danger').html('<i class="fa fa-times-circle me-1 mr-1"></i> ' + (confirmPwd !== pwd ? 'Passwords do not match' : 'Password format not met yet'));
                }
            } else {
                $('#confirm_valid_tick').addClass('d-none');
                $('#confirm_password').removeClass('is-valid is-invalid');
                $('#confirm_feedback').addClass('d-none').empty();
            }

            return allMet;
        }

        $('#password, #confirm_password').on('input keyup change paste', function() {
            evaluatePassword(false);
        });

        // Form Submission Handling
        $('#myForm').on('submit', function(e) {
            e.preventDefault();

            // Client-side validations with Notiflix failure pop-ups for all inputs
            var name = $('#name').val() ? $('#name').val().trim() : '';
            if (!name) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter the full name');
                } else {
                    alert('Please enter the full name');
                }
                $('#name').addClass('is-invalid').focus();
                return;
            }

            var username = $('#username').val() ? $('#username').val().trim() : '';
            if (!username) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter the username');
                } else {
                    alert('Please enter the username');
                }
                $('#username').addClass('is-invalid').focus();
                return;
            }

            var phone = $('#phone').val() ? $('#phone').val().trim() : '';
            if (!phone) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter the phone number');
                } else {
                    alert('Please enter the phone number');
                }
                $('#phone').addClass('is-invalid').focus();
                return;
            }
            if (phone.length !== 10 || !/^\d{10}$/.test(phone)) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Phone number must be exactly 10 numeric digits');
                } else {
                    alert('Phone number must be exactly 10 numeric digits');
                }
                $('#phone').addClass('is-invalid').focus();
                return;
            }

            var email = $('#email').val() ? $('#email').val().trim() : '';
            var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!email) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter the email address');
                } else {
                    alert('Please enter the email address');
                }
                $('#email').addClass('is-invalid').focus();
                return;
            }
            if (!emailRegex.test(email)) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a valid email address (e.g., name@company.com)');
                } else {
                    alert('Please enter a valid email address (e.g., name@company.com)');
                }
                $('#email').addClass('is-invalid').focus();
                return;
            }

            var password = $('#password').val();
            if (!password) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a password');
                } else {
                    alert('Please enter a password');
                }
                $('#password').addClass('is-invalid').focus();
                return;
            }

            var complexity = checkPasswordComplexity(password);
            if (!complexity.length || !complexity.uppercase || !complexity.lowercase || !complexity.number || !complexity.symbol) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Password must meet all requirements: min. 6 characters, 1 uppercase, 1 lowercase, 1 number, and 1 symbol.');
                } else {
                    alert('Password must meet all requirements: min. 6 characters, 1 uppercase, 1 lowercase, 1 number, and 1 symbol.');
                }
                $('#password').addClass('is-invalid').focus();
                return;
            }

            var confirmPassword = $('#confirm_password').val();
            if (!confirmPassword) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please confirm your password');
                } else {
                    alert('Please confirm your password');
                }
                $('#confirm_password').addClass('is-invalid').focus();
                return;
            }

            if (password !== confirmPassword) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Passwords do not match');
                } else {
                    alert('Passwords do not match');
                }
                $('#confirm_password').addClass('is-invalid').focus();
                return;
            }

            var selectedRoles = $('#roles').val() || [];
            if (selectedRoles.length === 0) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select at least one role');
                } else {
                    alert('Please select at least one role');
                }
                if ($('#roles').data('select2')) {
                    $('#roles').select2('open');
                } else {
                    $('#roles').focus();
                }
                return;
            }

            if (!$('#user_type').val()) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select a User Type');
                } else {
                    alert('Please select a User Type');
                }
                $('#user_type').addClass('is-invalid').focus();
                return;
            }

            // Mandatory outlet check for all users
            var outletId = $('#outlet_id').val();
            if (!outletId) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select an assigned outlet or branch station');
                } else {
                    alert('Please select an assigned outlet or branch station');
                }
                if ($('#outlet_id').data('select2')) {
                    $('#outlet_id').select2('open');
                } else {
                    $('#outlet_id').focus();
                }
                return;
            }

            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Creating user account, please wait...');
            }
            $('#btnSubmit').prop('disabled', true);

            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                data: new FormData(this),
                contentType: false,
                processData: false,
                success: function(data) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }
                    $('#btnSubmit').prop('disabled', false);

                    if (data.status === 200) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.success(data.message);
                        } else {
                            alert(data.message);
                        }
                        $('#myForm')[0].reset();
                        $('.is-invalid').removeClass('is-invalid');
                        $('#roles').val(null).trigger('change');
                        $('#outlet_id').val('').trigger('change');

                        setTimeout(function() {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = "{{ route('users.index') }}";
                            }
                        }, 1000);
                    } else {
                        var warnMsg = data.message || 'Operation failed';
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure(warnMsg);
                        } else {
                            alert('Error: ' + warnMsg);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }
                    $('#btnSubmit').prop('disabled', false);
                    $('#errors').empty();

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $('#errorAlert').show();
                        $.each(xhr.responseJSON.errors, function(field, messages) {
                            $('#errors').append('<li>' + messages[0] + '</li>');
                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.failure(messages[0]);
                            } else {
                                alert('Validation Error: ' + messages[0]);
                            }
                            $('#' + field).addClass('is-invalid');
                        });
                        $('html, body').animate({
                            scrollTop: $('#errorAlert').offset().top - 100
                        }, 500);
                    } else {
                        var serverMessage = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'An unexpected error occurred. Please try again later.';
                        $('#errorAlert').show();
                        $('#errors').append('<li>' + serverMessage + '</li>');
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure(serverMessage);
                        } else {
                            alert(serverMessage);
                        }
                    }
                }
            });
        });

        // Real-time clearance of is-invalid state
        $('input, select').on('input change', function() {
            $(this).removeClass('is-invalid');
        });
    });
</script>
@endsection
