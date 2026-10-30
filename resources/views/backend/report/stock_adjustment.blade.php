@extends('layout.main')
@section('title') {{ 'Stock Adjustment Report' }} @endsection

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
    .report-summary-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
</style>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Stock Adjustment Report</h4>
                </div>
            </div>
        </div>

        <div class="row mb-3 g-3">
            <div class="col-md-4">
                <div class="card report-summary-card mb-0">
                    <div class="card-body py-3">
                        <h6 class="text-muted text-uppercase font-size-12 mb-1">Filtered Total Value</h6>
                        <h4 id="filtered_total" class="mb-0 text-primary fw-bold">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card report-summary-card mb-0">
                    <div class="card-body py-3">
                        <h6 class="text-muted text-uppercase font-size-12 mb-1">This Month Value</h6>
                        <h4 class="mb-0 text-teal fw-bold">{{ number_format((float) $monthTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card report-summary-card mb-0">
                    <div class="card-body py-3">
                        <h6 class="text-muted text-uppercase font-size-12 mb-1">Overall Value</h6>
                        <h4 class="mb-0 text-dark fw-bold">{{ number_format((float) $overallTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Stock Adjustment Report</h4>

                        @php
                            $userOutletId = auth()->check() ? auth()->user()->outlet_id : null;
                            $canFilterUsers = auth()->check() && auth()->user()->can('user-filter-view');
                            $row2ColClass = $canFilterUsers ? 'col-lg-3 col-md-6' : 'col-lg-4 col-md-4';
                        @endphp

                        <div class="report-filter-panel">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-4 col-md-6">
                                    <label for="filter_outlet_id">Outlet / Warehouse</label>
                                    <select id="filter_outlet_id" class="form-control select2" {{ $userOutletId ? 'disabled' : '' }}>
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
                                <div class="col-lg-4 col-md-6">
                                    <label for="filter_adjustment_type">Adjustment Type</label>
                                    <select id="filter_adjustment_type" class="form-control select2">
                                        <option value="">All Types</option>
                                        <option value="increase">Increase</option>
                                        <option value="decrease">Decrease</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <label for="filter_product_id">Product</label>
                                    <select id="filter_product_id" class="form-control select2">
                                        <option value="">All Products</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
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
    $(function () {
        let table = $('#stockadjustmentreport-table').DataTable();

        function updateFilteredTotal() {
            let amountIndex = table.columns().indexes().toArray().find(function (idx) {
                return (table.settings()[0].aoColumns[idx] || {}).data === 'amount';
            });

            if (typeof amountIndex === 'undefined') {
                return;
            }

            let intVal = function (value) {
                if (typeof value === 'string') {
                    return value.replace(/[^0-9.-]/g, '') * 1;
                }
                if (typeof value === 'number') {
                    return value;
                }
                return 0;
            };

            let total = table.column(amountIndex, {search: 'applied'}).data().reduce(function (a, b) {
                return intVal(a) + intVal(b);
            }, 0);

            $('#filtered_total').text(total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }

        $('#filter_outlet_id, #filter_adjustment_type, #filter_product_id, #filter_created_by, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload(updateFilteredTotal);
        });

        $('#reset_filters').on('click', function () {
            @if(!$userOutletId)
                $('#filter_outlet_id').val(null).trigger('change');
            @endif
            $('#filter_adjustment_type').val(null).trigger('change');
            $('#filter_product_id').val(null).trigger('change');
            $('#filter_created_by').val(null).trigger('change');
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload(updateFilteredTotal);
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.outlet_id = $('#filter_outlet_id').val();
            data.adjustment_type = $('#filter_adjustment_type').val();
            data.product_id = $('#filter_product_id').val();
            data.created_by = $('#filter_created_by').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        table.on('draw.dt', function () {
            updateFilteredTotal();
        });

        $.fn.dataTable.ext.errMode = 'none';
        $('#stockadjustmentreport-table').on('error.dt', function (e, settings, techNote, message) {
            Notiflix.Notify.failure('DataTables error: ' + message);
        });

        updateFilteredTotal();
    });
</script>
@endsection

@stack('scripts')
