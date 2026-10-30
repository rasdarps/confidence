@extends('layout.main')
@section('title') {{ 'Sales Cash Movement Report' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<style>
    .report-filter-panel {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 18px 20px;
        margin-bottom: 20px;
    }
    .report-filter-panel .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #495057;
        margin-bottom: 6px;
        display: block;
    }
    .report-filter-panel .form-control,
    .report-filter-panel .form-select {
        height: 38px;
        border-radius: 6px;
        border-color: #dee2e6;
        font-size: 0.875rem;
    }
    .report-filter-panel .select2-container .select2-selection--single {
        height: 38px !important;
        border-radius: 6px !important;
        border-color: #dee2e6 !important;
        display: flex;
        align-items: center;
    }
    .report-filter-panel .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        font-size: 0.875rem;
        padding-left: 12px;
    }
    .report-filter-panel .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .report-filter-panel .btn-filter-action {
        height: 38px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
</style>

@php
    $lockedOutletId = auth()->check() ? auth()->user()->outlet_id : null;
@endphp

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Sales Cash Movement Report</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Cash Received From Sales (Full + Partial)</h4>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card border shadow-sm mb-0">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Total Full Payment Cash</small>
                                        <h5 class="mb-0" id="full_payment_cash_card">0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border shadow-sm mb-0">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Total Partial Payment Cash</small>
                                        <h5 class="mb-0" id="partial_payment_cash_card">0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border shadow-sm mb-0">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Total Cash</small>
                                        <h5 class="mb-0" id="total_cash_card">0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border shadow-sm mb-0">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Total Due Amount</small>
                                        <h5 class="mb-0" id="total_due_amount_card">0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-panel">
                            <!-- Row 1: 4 Primary Filters -->
                            <div class="row g-3 mb-3">
                                <div class="col-lg-3 col-md-6">
                                    <label for="filter_outlet_id" class="filter-label">
                                        <i class="ri-store-2-line me-1 text-primary"></i>Outlet / Branch
                                    </label>
                                    <select id="filter_outlet_id" class="form-control select2" {{ $lockedOutletId ? 'disabled' : '' }}>
                                        @if(!$lockedOutletId)
                                            <option value="">All Outlets (Combined)</option>
                                        @endif
                                        @foreach($outlets ?? [] as $outlet)
                                            @if(!$lockedOutletId || $lockedOutletId == $outlet->id)
                                                <option value="{{ $outlet->id }}" {{ $lockedOutletId == $outlet->id ? 'selected' : '' }}>
                                                    {{ $outlet->name }}{{ $outlet->is_central ? ' (Central)' : '' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label for="filter_sale_id" class="filter-label">
                                        <i class="ri-file-list-3-line me-1 text-primary"></i>Sale Number
                                    </label>
                                    <select id="filter_sale_id" class="form-control select2">
                                        <option value="">All Sales</option>
                                        @foreach($sales as $sale)
                                            <option value="{{ $sale->id }}">{{ $sale->sale_no }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label for="filter_customer_id" class="filter-label">
                                        <i class="ri-user-3-line me-1 text-primary"></i>Customer
                                    </label>
                                    <select id="filter_customer_id" class="form-control select2">
                                        <option value="">All Customers</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label for="filter_paid_status" class="filter-label">
                                        <i class="ri-bank-card-line me-1 text-primary"></i>Payment Type
                                    </label>
                                    <select id="filter_paid_status" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="full_payment">Full Payment</option>
                                        <option value="partial_payment">Partial Payment</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Row 2: Date Range & Reset Action -->
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-4 col-md-6">
                                    <label for="filter_from_date" class="filter-label">
                                        <i class="ri-calendar-event-line me-1 text-primary"></i>From Date
                                    </label>
                                    <input type="date" id="filter_from_date" class="form-control">
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label for="filter_to_date" class="filter-label">
                                        <i class="ri-calendar-check-line me-1 text-primary"></i>To Date
                                    </label>
                                    <input type="date" id="filter_to_date" class="form-control">
                                </div>

                                <div class="col-lg-4 col-md-12">
                                    <button id="reset_filters" type="button" class="btn btn-outline-secondary btn-filter-action w-100">
                                        <i class="ri-refresh-line"></i> Reset Filters
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
        const summaryUrl = '{{ route('reports.sales-cash-movement.summary') }}';
        let table = $('#sales-cash-movement-table').DataTable();

        function getFilters() {
            return {
                outlet_id: $('#filter_outlet_id').val(),
                sale_id: $('#filter_sale_id').val(),
                customer_id: $('#filter_customer_id').val(),
                paid_status: $('#filter_paid_status').val(),
                from_date: $('#filter_from_date').val(),
                to_date: $('#filter_to_date').val(),
            };
        }

        function formatMoney(value) {
            return Number(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function loadSummary() {
            $.get(summaryUrl, getFilters())
                .done(function (res) {
                    $('#full_payment_cash_card').text(formatMoney(res.full_payment_cash));
                    $('#partial_payment_cash_card').text(formatMoney(res.partial_payment_cash));
                    $('#total_cash_card').text(formatMoney(res.total_cash));
                    $('#total_due_amount_card').text(formatMoney(res.total_due_amount));
                })
                .fail(function () {
                    Notiflix.Notify.failure('Could not load cash summary cards.');
                });
        }

        $('#filter_outlet_id, #filter_sale_id, #filter_customer_id, #filter_paid_status, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
            loadSummary();
        });

        $('#reset_filters').on('click', function () {
            @if(!$lockedOutletId)
                $('#filter_outlet_id').val('').trigger('change.select2');
            @endif
            $('#filter_sale_id').val(null).trigger('change.select2');
            $('#filter_customer_id').val(null).trigger('change.select2');
            $('#filter_paid_status').val('');
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload();
            loadSummary();
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.outlet_id = $('#filter_outlet_id').val();
            data.sale_id = $('#filter_sale_id').val();
            data.customer_id = $('#filter_customer_id').val();
            data.paid_status = $('#filter_paid_status').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        $.fn.dataTable.ext.errMode = 'none';
        $('#sales-cash-movement-table').on('error.dt', function (e, settings, techNote, message) {
            Notiflix.Notify.failure('DataTables error: ' + message);
        });

        loadSummary();
    });
</script>
@endsection

@stack('scripts')
