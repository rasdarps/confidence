@extends('layout.main')
@section('title') {{'Stock Transfers'}} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Stock Transfers (Inter-Outlet Movement)</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @can('stock-transfer-create')
                            <a href="{{ route('stock-transfers.create') }}" class="btn btn-dark" style="float:right;">
                                <i class="fas fa-plus-circle"> New Transfer (Dispatch)</i>
                            </a>
                            <br><br>
                        @endcan

                        <h4 class="card-title">Inter-Outlet Stock Transfers</h4>
                        <p class="card-title-desc">Track goods transferred from Central Warehouse to Retail Outlets or between branches.</p>

                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    {{ $dataTable->scripts() }}
@endsection

