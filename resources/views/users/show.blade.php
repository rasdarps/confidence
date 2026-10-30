@extends('layout.main')
@section('title') {{ 'Show User' }} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- Breadcrumbs -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">User Details</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                            <li class="breadcrumb-item active">View User</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header d-flex justify-content-between align-items-center py-3" style="background: linear-gradient(135deg, #034141 0%, #0F766E 100%); color: #fff;">
                        <div>
                            <h5 class="card-title mb-0 text-white font-size-16">
                                <i class="mdi mdi-account-details mr-1"></i> User Profile: {{ $user->name }}
                            </h5>
                            <small class="text-white-50">Operational scope, permissions, and station assignment</small>
                        </div>

                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-light font-weight-bold shadow-sm" style="color: #034141;">
                            <i class="mdi mdi-arrow-left mr-1"></i> Back to Users
                        </a>
                    </div>

                    <div class="card-body p-4 bg-white">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 260px; background: #f8fafc;" class="text-dark">Full Name</th>
                                        <td class="font-weight-bold">{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark">Username</th>
                                        <td><code>{{ $user->username ?? '—' }}</code></td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark">Email Address</th>
                                        <td><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark">Phone Number</th>
                                        <td>{{ $user->phone ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark">User Classification</th>
                                        <td>
                                            @if($user->user_type === 'Admin')
                                                <span class="badge badge-primary px-2 py-1 font-size-12">Admin</span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1 font-size-12">User (Standard Staff)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark">Assigned Outlet / Scope</th>
                                        <td>
                                            @if($user->outlet)
                                                @if($user->outlet->is_central)
                                                    <span class="badge badge-dark px-2 py-1 font-size-12">
                                                        <i class="mdi mdi-shield-crown mr-1"></i> {{ $user->outlet->name }} ({{ $user->outlet->code }}) • Central Headquarters (Enterprise Scope)
                                                    </span>
                                                @else
                                                    <span class="badge px-2 py-1 font-size-12" style="background-color: #0F766E; color: #fff;">
                                                        <i class="mdi mdi-store mr-1"></i> {{ $user->outlet->name }} ({{ $user->outlet->code }}) • Branch Station
                                                    </span>
                                                @endif
                                            @else
                                                @if(strtolower((string)$user->user_type) === 'super admin' || $user->hasRole('Super Admin'))
                                                    <span class="badge badge-dark px-2 py-1 font-size-12">
                                                        <i class="mdi mdi-shield-crown mr-1"></i> Super Admin (Global System Access)
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning px-2 py-1 font-size-12">
                                                        <i class="mdi mdi-alert mr-1"></i> Unassigned
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark">Assigned Roles</th>
                                        <td>
                                            @forelse($user->getRoleNames() as $role)
                                                <span class="badge badge-success px-2 py-1 font-size-12 mr-1">{{ $role }}</span>
                                            @empty
                                                <span class="text-muted">No roles assigned</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark">Account Created</th>
                                        <td>{{ $user->created_at ? $user->created_at->format('jS F, Y g:i A') : '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('users.index') }}" class="btn btn-modern-cancel">
                                <i class="fas fa-arrow-left me-1 mr-1"></i> Back to Users
                            </a>

                            @if(Auth::user()->isCentral() && auth()->user()->can('user-edit'))
                                <a href="{{ route('users.edit', Illuminate\Support\Facades\Crypt::encrypt($user->id)) }}" class="btn btn-modern-primary">
                                    <i class="fas fa-user-edit me-1 mr-1"></i> Edit User Account
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
