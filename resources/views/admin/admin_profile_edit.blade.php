@extends('layout.main')
@section('title') {{'Edit Profile'}} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:20px;">Edit | Profile</span>
                        <a href="{{ route('admin.profile') }}" style="float:right">
                            <button type="button" class="btn btn-primary modal_btn">View Profile</button>
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

                        <form method="post" action="{{ route('store.profile') }}">
                            @csrf

                            <div class="row justify-content-center">
                                <div class="col-lg-8">
                                    <div class="form-group mb-3">
                                        <label for="name"><strong>Name</strong></label>
                                        <input id="name" name="name" class="form-control" type="text" value="{{ old('name', $editData->name) }}" placeholder="Enter full name">
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="username"><strong>Username</strong></label>
                                        <input id="username" name="username" class="form-control" type="text" value="{{ old('username', $editData->username) }}" placeholder="Enter username">
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="email"><strong>Email Address</strong></label>
                                        <input id="email" name="email" class="form-control" type="email" value="{{ old('email', $editData->email) }}" placeholder="Enter email address">
                                    </div>

                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success waves-effect waves-light">Update Profile</button>
                                <a href="{{ route('admin.profile') }}" class="btn btn-secondary waves-effect waves-light ml-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection 
