@extends('layout.main')
@section('title') {{ 'Product-wise Stock Trail' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

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
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Product-wise Stock Trail <small class="text-muted fs-6">(Select a product first)</small></h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Track Activities For One Product</h4>

                        @php
                            $userOutletId = auth()->check() ? auth()->user()->outlet_id : null;
                            $canFilterUsers = auth()->check() && auth()->user()->can('user-filter-view');
                            $row2ColClass = $canFilterUsers ? 'col-lg-3 col-md-6' : 'col-lg-4 col-md-4';
                        @endphp

                        <div class="report-filter-panel">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-4 col-md-6">
                                    <label for="filter_product">Product <span class="text-danger">*</span></label>
                                    <select id="filter_product" class="form-control select2">
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <label for="filter_outlet">Outlet / Warehouse</label>
                                    <select id="filter_outlet" class="form-control select2" {{ $userOutletId ? 'disabled' : '' }}>
                                        @if(!$userOutletId)
                                            <option value="">All Warehouses & Outlets</option>
                                        @endif
                                        @foreach($outlets as $outlet)
                                            @if(!$userOutletId || (int) $userOutletId === (int) $outlet->id)
                                                <option value="{{ $outlet->id }}" {{ (int) $userOutletId === (int) $outlet->id ? 'selected' : '' }}>
                                                    {{ $outlet->name }} {{ $outlet->is_central ? '(Central)' : '' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <label for="filter_movement_type">Movement Type</label>
                                    <select id="filter_movement_type" class="form-control select2">
                                        <option value="">All Types</option>
                                        <option value="purchase">Purchase</option>
                                        <option value="transfer_out">Transfer Out (Dispatched)</option>
                                        <option value="transfer_in">Transfer In (Received)</option>
                                        <option value="sale">Sale</option>
                                        <option value="adjustment_increase">Adjustment Increase</option>
                                        <option value="adjustment_decrease">Adjustment Decrease</option>
                                        <option value="purchase_cancel">Purchase Cancel</option>
                                        <option value="sale_cancel">Sale Cancel</option>
                                    </select>
                                </div>

                                @can('user-filter-view')
                                    <div class="{{ $row2ColClass }}">
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

                                <div class="{{ $row2ColClass }}">
                                    <label for="filter_from_date">From Date</label>
                                    <input type="date" id="filter_from_date" class="form-control">
                                </div>
                                <div class="{{ $row2ColClass }}">
                                    <label for="filter_to_date">To Date</label>
                                    <input type="date" id="filter_to_date" class="form-control">
                                </div>
                                <div class="{{ $row2ColClass }}">
                                    <button id="reset_filters" type="button" class="btn btn-dark w-100">
                                        <i class="ri-refresh-line me-1"></i>Reset Filters
                                    </button>
                                </div>
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

    $('#filter_product, #filter_outlet, #filter_movement_type, #filter_created_by, #filter_from_date, #filter_to_date').on('change', function() {
        table.ajax.reload();
    });

    $('#reset_filters').on('click', function() {
        $('#filter_product').val(null).trigger('change');
        @if(!$userOutletId)
            $('#filter_outlet').val(null).trigger('change');
        @endif
        $('#filter_movement_type').val(null).trigger('change');
        $('#filter_created_by').val(null).trigger('change');
        $('#filter_from_date').val('');
        $('#filter_to_date').val('');
        table.ajax.reload();
    });

    table.on('preXhr.dt', function(e, settings, data) {
        data.product_id = $('#filter_product').val();
        data.outlet_id = $('#filter_outlet').val();
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
