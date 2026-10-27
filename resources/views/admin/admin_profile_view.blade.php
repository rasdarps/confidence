@extends('layout.main')
@section('title') {{'View Profile'}} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <div class="row mb-3 align-items-center">
            <div class="col-md-6">
                <h4 class="mb-0">My Profile</h4>
            </div>
            <div class="col-md-6 text-md-right mt-2 mt-md-0">
                <a href="{{ route('change.password') }}" class="btn btn-outline-warning btn-sm mr-2">Change Password</a>
                <a href="{{ route('edit.profile') }}" class="btn btn-primary btn-sm">Edit Profile</a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card">
                    <div class="card-body text-center py-4">
                        <img class="rounded-circle shadow-sm mb-3" width="120" height="120" src="{{ asset('images/bg_logo.PNG') }}" alt="Profile Image">
                        <h5 class="mb-1">{{ $adminData->name }}</h5>
                        <p class="text-muted mb-0">{{ $adminData->username }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:18px;">Account Details</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width:220px;">Full Name</th>
                                        <td>{{ $adminData->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Username</th>
                                        <td>{{ $adminData->username }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email Address</th>
                                        <td>{{ $adminData->email }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection 
