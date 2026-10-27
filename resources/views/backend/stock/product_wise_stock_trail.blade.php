@extends('layout.main')
@section('title') {{ 'Product-wise Stock Trail' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Product-wise Stock Trail (Select a product first. The table stays empty until a product is chosen.)</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Track Activities For One Product</h4>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Product <span class="text-danger">*</span></label>
                                <select id="filter_product" class="form-control select2">
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Movement Type</label>
                                <select id="filter_movement_type" class="form-control select2">
                                    <option value="">All Types</option>
                                    <option value="purchase">Purchase</option>
                                    <option value="sale">Sale</option>
                                    <option value="adjustment_increase">Adjustment Increase</option>
                                    <option value="adjustment_decrease">Adjustment Decrease</option>
                                    <option value="purchase_cancel">Purchase Cancel</option>
                                    <option value="sale_cancel">Sale Cancel</option>
                                </select>
                            </div>
                            @can('user-filter-view')
                                <div class="col-md-3">
                                    <label>Created By</label>
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
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="reset_filters" class="btn btn-dark w-100">Reset Filters</button>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>From Date</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                            
                        </div>

                        <div class="table-responsive">
                            {!! $dataTable->table(['class' => 'table table-bordered table-striped'], true) !!}
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
<script src="{{ asset('notiflix/notiflix-aio-3.2.8.min.js') }}"></script>
<script>
$(function() {
    let table = $('#productwisestockreport-table').DataTable();

    $('#filter_product, #filter_movement_type, #filter_created_by, #filter_from_date, #filter_to_date').on('change', function() {
        table.ajax.reload();
    });

    $('#reset_filters').on('click', function() {
        $('#filter_product').val(null).trigger('change');
        $('#filter_movement_type').val(null).trigger('change');
        $('#filter_created_by').val(null).trigger('change');
        $('#filter_from_date').val('');
        $('#filter_to_date').val('');
        table.ajax.reload();
    });

    table.on('preXhr.dt', function(e, settings, data) {
        data.product_id = $('#filter_product').val();
        data.movement_type = $('#filter_movement_type').val();
        data.created_by = $('#filter_created_by').val();
        data.from_date = $('#filter_from_date').val();
        data.to_date = $('#filter_to_date').val();
    });

    $.fn.dataTable.ext.errMode = 'none';
    $('#productwisestockreport-table').on('error.dt', function(e, settings, techNote, message) {
        Notiflix.Notify.failure('DataTables error: ' + message);
    });
});
</script>
@endsection

@stack('scripts')
