@extends('layout.main')
@section('title') {{'Show User'}} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:20px;">User | Details</span>
                        <a href="{{ route('users.index') }}" style="float:right">
                            <button type="button" class="btn btn-primary modal_btn">View All Users</button>
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width:220px;">Full Name</th>
                                        <td>{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Username</th>
                                        <td>{{ $user->username ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email Address</th>
                                        <td>{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td>{{ $user->phone ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>User Type</th>
                                        <td>{{ $user->user_type ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Roles</th>
                                        <td>
                                            @forelse($user->getRoleNames() as $role)
                                                <span class="badge badge-success">{{ $role }}</span>
                                            @empty
                                                <span class="text-muted">No roles assigned</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td>{{ $user->created_at ? $user->created_at->format('jS F, Y g:i A') : '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('users.edit', Illuminate\Support\Facades\Crypt::encrypt($user->id)) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-edit"></i> Edit User
                            </a>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm ml-2">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection