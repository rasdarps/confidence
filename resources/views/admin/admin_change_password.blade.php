@extends('layout.main')
@section('title') {{'Change Password'}} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:20px;">Update | Password</span>
                        <a href="{{ route('admin.profile') }}" style="float:right">
                            <button type="button" class="btn btn-primary modal_btn" style="background-color:#0F766E; color:white;">Back to Profile</button>
                        </a>
                    </div>

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="post" action="{{ route('update.password') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="form-group mb-3">
                                        <label for="oldpassword"><strong>Old Password</strong></label>
                                        <input name="oldpassword" class="form-control" type="password" id="oldpassword" placeholder="Enter old password" required>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="newpassword"><strong>New Password</strong></label>
                                        <input name="newpassword" class="form-control" type="password" id="newpassword" placeholder="Enter new password" required>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="confirm_password"><strong>Confirm Password</strong></label>
                                        <input name="confirm_password" class="form-control" type="password" id="confirm_password" placeholder="Confirm new password" required>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-success waves-effect waves-light">Change Password</button>
                                    </div>
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
