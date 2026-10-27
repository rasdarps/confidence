@extends('layout.main')
@section('title') {{'Edit User'}} @endsection

@section('content')

    <!--Validator link-->
    <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
 
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header with-border">
                            <span class="card-title" style="font-size:20px;">Edit | User </span>

                            <a href="{{route('users.index')}}" style="float:right">
                                <button type="button" class="btn btn-primary modal_btn">
                                    View Users
                                </button>
                            </a>

                            <hr>

                            <div class="card-body">

                                <form action="{{ route('users.update', Crypt::encrypt($user->id)) }}" method="POST" id="myForm" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')

                                    {{-- backend error alerts --}}
                                    <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                        <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                            <span><i class="mdi mdi-close"></i></span>
                                        </button>
                                        <ul id="errors"></ul>
                                    </div>

                                    <div class="row col-md-10 mx-auto">

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name"><strong>Full Name: <span class="text-danger">*</span></strong></label>
                                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" placeholder="Full Name" onkeypress="return isCharKey(event)">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username"><strong>Username: <span class="text-danger">*</span></strong></label>
                                                <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $user->username) }}" placeholder="Username" onkeypress="return isCharKey(event)">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone"><strong>Phone: <span class="text-danger">*</span></strong></label>
                                                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="Phone"  pattern="\d{10}" maxlength="10" minlength="10" onkeypress="return isNumberKey(event)">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email"><strong>Email: <span class="text-danger">*</span></strong></label>
                                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" placeholder="Email">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password"><strong>Password: <small class="text-muted">(Leave blank to keep current password)</small></strong></label>
                                                <input type="password" id="password" name="password" class="form-control" placeholder="New Password (optional)">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirm_password"><strong>Confirm Password:</strong></label>
                                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm New Password">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="roles"><strong>Role: <span class="text-danger">*</span></strong></label>
                                                <select id="roles" name="roles[]" class="form-control" multiple>
                                                    @foreach($roles as $role)
                                                        <option value="{{ $role }}" 
                                                            {{ (collect(old('roles', $userRole))->contains($role)) ? 'selected' : '' }}>
                                                            {{ $role }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="user_type"><strong>User Type: <span class="text-danger">*</span></strong></label>
                                                <select id="user_type" name="user_type" class="form-control">
                                                    <option value="" disabled {{ old('user_type', $user->user_type) ? '' : 'selected' }}>Select User Type</option>
                                                    <option value="Admin" {{ old('user_type', $user->user_type) == 'Admin' ? 'selected' : '' }}>Admin</option>
                                                    <option value="User" {{ old('user_type', $user->user_type) == 'User' ? 'selected' : '' }}>User</option>
                                                </select>
                                            </div>
                                        </div>
                                    
                                    </div>
                                    {{-- row 1 ends --}}

                                    <div class="row">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-success mt-3">Update User</button>
                                        </div>
                                    </div>
                                    {{-- row 2 ends --}}
                            
                                </form>
                            
                            </div>
                                
                        </div>
                        
                    </div>
                </div>
                <!-- end col 12-->
            </div>
            <!-- end row -->
        </div>
        <!-- container fluid ends -->
    </div>
    <!-- page content ends -->

@endsection 

@section('styles')
<style>

    .card {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease-in-out;
        border-radius: 15px;
        overflow: hidden;
        transform: translateY(0);
    }

    .card:hover {
        box-shadow: 0 8px 25px 0 rgba(0, 0, 0, 0.3);
        transform: translateY(-5px);
        border-radius: 20px;
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s;
        z-index: 1;
    }

    .card:hover::before {
        left: 100%;
    }

    .card-body {
        position: relative;
        z-index: 2;
    }

    .card {
        background: linear-gradient(145deg, #ffffff, #f0f0f0);
        border: none;
    }

    .card:hover {
        background: linear-gradient(145deg, #f8f9fa, #e9ecef);
    }

    .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
    
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
</style>
@endsection

@section('scripts')

 <script type="text/javascript">
        $(document).ready(function (){
           
            // Real-time email validation
            $('#email').on('input blur', function() {
                var email = $(this).val();
                var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                
                if (email && !emailRegex.test(email)) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Please enter a valid email (e.g., test@test.com)</div>');
                    }
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            });

            // Real-time phone validation
            $('#phone').on('input', function() {
                var phone = $(this).val();
                if (phone && (phone.length > 10 || phone.length < 10)) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Phone number must be exactly 10 digits</div>');
                    }
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            });

            
            // Handle form submission
            $('#myForm').on('submit', function(e) {
                e.preventDefault();

                // Client-side validation
                if(!$('#name').val()) {
                    Notiflix.Notify.failure('Please enter your full Name');
                    return;
                }
                if(!$('#username').val()) {
                    Notiflix.Notify.failure('Please enter your Username');
                    return;
                }
                if(!$('#phone').val()) {
                    Notiflix.Notify.failure('Please enter your Phone Number');
                    return;
                }
                if($('#phone').val().length != 10) {
                    Notiflix.Notify.failure('Phone Number must be exactly 10 digits');
                    return;
                }
                
                if(!$('#email').val()) {
                    Notiflix.Notify.failure('Please enter your Email');
                    return;
                }
                
                // Enhanced email validation
                var email = $('#email').val();
                var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                
                if(!emailRegex.test(email)) {
                    Notiflix.Notify.failure('Please enter a valid email address in format: test@test.com');
                    return;
                }
                
                // Check if email contains at least one dot after @
                var atIndex = email.indexOf('@');
                var dotIndex = email.lastIndexOf('.');
                if (atIndex === -1 || dotIndex === -1 || dotIndex <= atIndex) {
                    Notiflix.Notify.failure('Email must contain @ and . (example: test@test.com)');
                    return;
                }
               
                // Password validation (only if password is provided)
                if($('#password').val() || $('#confirm_password').val()) {
                    if(!$('#password').val()) {
                        Notiflix.Notify.failure('Please enter your Password');
                        return;
                    }
                    if(!$('#confirm_password').val()) {
                        Notiflix.Notify.failure('Please enter your Confirm Password');
                        return;
                    }
                    if(!($('#password').val() === $('#confirm_password').val())) {
                        Notiflix.Notify.failure('Passwords do not match');
                        return;
                    }
                }
                
                if(!$('#roles').val() || $('#roles').val().length === 0) {
                    Notiflix.Notify.failure('Please select your Role');
                    return;
                }

                if(!$('#user_type').val()) {
                    Notiflix.Notify.failure('Please select User Type');
                    return;
                }

                Notiflix.Loading.standard('Updating user, please wait...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: new FormData(this),
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        Notiflix.Loading.remove();
                        if (data.status === 200) {
                            Notiflix.Notify.success(data.message);
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            Notiflix.Notify.failure(data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        Notiflix.Loading.remove();
                        $('#errors').empty();
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            $('#errorAlert').show();
                            $.each(xhr.responseJSON.errors, function(field, messages) {
                                $('#errors').append('<li>' + messages[0] + '</li>');
                                Notiflix.Notify.failure(messages[0]);
                            });
                            $('html, body').animate({
                                scrollTop: $('#errorAlert').offset().top - 100
                            }, 500);
                        } else {
                            var serverMessage = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : 'An error occurred. Please try again later.';
                            $('#errorAlert').show();
                            $('#errors').append('<li>' + serverMessage + '</li>');
                            Notiflix.Notify.failure(serverMessage);
                        }
                    }
                });
            });

            
        });
    </script>

@endsection