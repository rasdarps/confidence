@extends('layout.main')
@section('title') {{ 'Role Details' }} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="card-title font-size-20 font-weight-bold">Role | Details</span>
                            <p class="text-muted mb-0 mt-1">Review role configuration and associated module permissions.</p>
                        </div>
                        <a href="{{ route('roles.index') }}" class="btn btn-modern-header">
                            <i class="fas fa-arrow-left me-1 mr-1"></i> View All Roles
                        </a>
                    </div>

                    <div class="card-body px-4 py-4">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width:240px; background: #f8fafc;" class="text-dark font-weight-semibold">Role Name</th>
                                        <td class="font-weight-bold text-dark font-size-15">{{ $role->name }}</td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark font-weight-semibold">Date Created</th>
                                        <td>{{ $role->created_at ? $role->created_at->format('jS F, Y g:i A') : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="background: #f8fafc;" class="text-dark font-weight-semibold">
                                            Assigned Permissions
                                            <span class="badge bg-primary-subtle text-primary ml-1 ms-1">{{ count($rolePermissions) }}</span>
                                        </th>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse($rolePermissions as $permission)
                                                    <span class="badge px-2 py-1 mr-1 mb-1 font-size-12" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                                        <i class="fas fa-check-circle me-1 mr-1" style="font-size: 10px;"></i>{{ $permission->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-muted">No permissions assigned</span>
                                                @endforelse
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('roles.index') }}" class="btn btn-modern-cancel">
                                <i class="fas fa-arrow-left me-1 mr-1"></i> Back to Roles
                            </a>

                            @if(auth()->check() && auth()->user()->isCentral() && auth()->user()->can('role-edit') && strtolower(trim((string)$role->name)) !== 'super admin')
                                <a href="{{ route('roles.edit', Illuminate\Support\Facades\Crypt::encrypt($role->id)) }}" class="btn btn-modern-primary">
                                    <i class="fas fa-edit me-1 mr-1"></i> Edit Role
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

@section('styles')
<style>
    .btn-modern-header {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        color: #0F766E;
        border: 1.5px solid #cbd5e1;
        font-weight: 600;
        font-size: 13.5px;
        border-radius: 8px;
        padding: 8px 18px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
    }

    .btn-modern-header:hover {
        background: #f0fdfa;
        color: #0F766E;
        border-color: #0F766E;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.15);
    }

    .btn-modern-cancel {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #475569;
        border: 1.5px solid #cbd5e1;
        font-weight: 600;
        font-size: 14px;
        border-radius: 10px;
        padding: 9px 26px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
    }

    .btn-modern-cancel:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #94a3b8;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .btn-modern-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #0F766E 0%, #0d9488 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 14.5px;
        border-radius: 10px;
        padding: 10px 36px;
        letter-spacing: 0.2px;
        box-shadow: 0 4px 14px rgba(15, 118, 110, 0.35);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
    }

    .btn-modern-primary:hover {
        background: linear-gradient(135deg, #115e59 0%, #0F766E 100%);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(15, 118, 110, 0.45);
    }
</style>
@endsection
