@extends('layout.main')
@section('title') {{'Outlets & Branches'}} @endsection
@section('styles')
    <style>
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background: #034141;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        #outlets-table tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
@endsection
@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Outlets & Branches Management</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            @can('outlet-create')
                                <a href="{{ route('outlets.create') }}" class="btn btn-dark" style="float:right;">
                                    <i class="fas fa-plus-circle"> Add Outlet</i>
                                </a>
                                <br><br>
                            @endcan

                            <h4 class="card-title">All Outlets & Warehouses</h4>
                            <p class="card-title-desc">Manage Central Warehouse and Retail Outlets. Central handles vendor purchases and accounting; retail outlets sell local inventory and receive stock transfers.</p>

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

