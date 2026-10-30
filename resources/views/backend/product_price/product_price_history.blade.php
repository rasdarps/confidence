@extends('layout.main')
@section('title') {{'Product Price History'}} @endsection

@section('content')

<style>
    .report-filter-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px 18px;
        margin-bottom: 20px;
    }
    .report-filter-panel label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #475569;
        margin-bottom: 6px;
        display: block;
    }
</style>

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Product Price History</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Product Price History (Central Master Catalog)</h4>

                        @php
                            $canFilterUsers = auth()->check() && auth()->user()->can('user-filter-view');
                        @endphp

                        <div class="report-filter-panel">
                            <div class="row g-3 align-items-end">
                                <div class="{{ $canFilterUsers ? 'col-lg-3 col-md-6' : 'col-lg-3 col-md-6' }}">
                                    <label for="filter_product_id">Product</label>
                                    <select id="filter_product_id" class="form-control select2">
                                        <option value="">All Products</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="{{ $canFilterUsers ? 'col-lg-2 col-md-6' : 'col-lg-3 col-md-6' }}">
                                    <label for="filter_from_date">From Date</label>
                                    <input type="date" id="filter_from_date" class="form-control">
                                </div>
                                <div class="{{ $canFilterUsers ? 'col-lg-2 col-md-6' : 'col-lg-3 col-md-6' }}">
                                    <label for="filter_to_date">To Date</label>
                                    <input type="date" id="filter_to_date" class="form-control">
                                </div>
                                @can('user-filter-view')
                                    <div class="col-lg-3 col-md-6">
                                        <label for="filter_created_by">Created By</label>
                                        <select id="filter_created_by" class="form-control select2">
                                            <option value="">All Users</option>
                                            @php
                                                $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                                            @endphp
                                            @foreach($users as $user)
                                                @if($viewerIsSuperAdmin || strtolower((string) ($user->user_type ?? '')) !== 'super admin')
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                @endcan
                                <div class="{{ $canFilterUsers ? 'col-lg-2 col-md-12' : 'col-lg-3 col-md-6' }}">
                                    <button id="reset_filters" type="button" class="btn btn-dark w-100">
                                        <i class="ri-refresh-line me-1"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            {!! $dataTable->table(['class' => 'table table-bordered table-striped'], true) !!}
                        </div>

                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->

    </div> <!-- container-fluid -->
</div>

@endsection
@section('scripts')
    {{ $dataTable->scripts() }}
    <script>
        $(function() {
            let table = $('#productpricehistory-table').DataTable();

            $('#filter_product_id, #filter_from_date, #filter_to_date, #filter_created_by').on('change', function() {
                table.ajax.reload();
            });

            $('#reset_filters').on('click', function() {
                $('#filter_product_id').val(null).trigger('change');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                $('#filter_created_by').val(null).trigger('change');
                table.ajax.reload();
            });

            table.on('preXhr.dt', function(e, settings, data) {
                data.product_id = $('#filter_product_id').val();
                data.from_date = $('#filter_from_date').val();
                data.to_date = $('#filter_to_date').val();
                data.created_by = $('#filter_created_by').val();
            });
        });
    </script>
@endsection
