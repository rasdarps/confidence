@extends('layout.main')
@section('title') {{'Show Role'}} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:20px;">Role | Details</span>
                        <a href="{{ route('roles.index') }}" style="float:right">
                            <button type="button" class="btn btn-primary modal_btn">View All Roles</button>
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width:220px;">Role Name</th>
                                        <td>{{ $role->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td>{{ $role->created_at ? $role->created_at->format('jS F, Y g:i A') : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Permissions</th>
                                        <td>
                                            @forelse($rolePermissions as $permission)
                                                <span class="badge badge-success mr-1 mb-1">{{ $permission->name }}</span>
                                            @empty
                                                <span class="text-muted">No permissions assigned</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('roles.edit', Illuminate\Support\Facades\Crypt::encrypt($role->id)) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-edit"></i> Edit Role
                            </a>
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm ml-2">
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