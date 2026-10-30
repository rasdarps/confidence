@extends('layout.main')
@section('title') {{ 'View Profit' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<style>
    .summary-card {
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .summary-card:hover {
        background-color: #f8f9fa;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.08);
    }
    .summary-card-link {
        display: block;
        color: inherit;
    }
    .summary-card-link .summary-card {
        cursor: pointer;
    }
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
    $canFilterUsers = auth()->check() && auth()->user()->can('user-filter-view');
@endphp

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Profit Report</h4>
                    <a href="{{ route('sales.create') }}" class="btn btn-dark btn-rounded waves-effect waves-light">
                        <i class="fas fa-plus-circle me-1"></i> New Sales
                    </a>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Profit & Margin Breakdown</h4>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="card border shadow-sm mb-0 summary-card h-100">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Gross Profit (Gh¢)</small>
                                        <h5 id="summary_gross_profit" class="mb-1">0.00</h5>
                                        <small class="text-muted">Total Profit before expenses</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('reports.expense') }}" class="summary-card-link text-decoration-none h-100">
                                    <div class="card border shadow-sm mb-0 summary-card h-100">
                                        <div class="card-body py-3">
                                            <small class="text-muted d-block">Total Expenses (Gh¢)</small>
                                            <h5 id="summary_total_expenses" class="mb-1">0.00</h5>
                                            <small class="text-muted">Click to open expense report</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <div class="card border shadow-sm mb-0 summary-card h-100">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Net Profit (Gh¢)</small>
                                        <h5 id="summary_net_profit" class="mb-1">0.00</h5>
                                        <small class="text-muted">Total Profit after expenses & adjustments</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border shadow-sm mb-0 summary-card h-100">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Cash Adjustment (Gh¢)</small>
                                        <h5 id="summary_cash_adjustment" class="mb-1">0.00</h5>
                                        <small class="text-muted">Shortage lowers profit, overage raises profit</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border shadow-sm mb-0 summary-card h-100">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Stock Adjustment (Gh¢)</small>
                                        <h5 id="summary_stock_adjustment" class="mb-1">0.00</h5>
                                        <small class="text-muted">Shortage lowers profit, overage raises profit</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Panel -->
                        <div class="report-filter-panel">
                            <!-- Row 1: Primary Entity Filters -->
                            <div class="row g-3 mb-3">
                                <div class="{{ $canFilterUsers ? 'col-lg-3 col-md-6' : 'col-lg-4 col-md-6' }}">
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

                                <div class="{{ $canFilterUsers ? 'col-lg-3 col-md-6' : 'col-lg-4 col-md-6' }}">
                                    <label for="filter_invoice_id" class="filter-label">
                                        <i class="ri-file-list-3-line me-1 text-primary"></i>Sale Number
                                    </label>
                                    <select id="filter_invoice_id" class="form-control select2">
                                        <option value="">All Sales</option>
                                        @foreach($sales as $Sale)
                                            <option value="{{ $Sale->id }}">{{ $Sale->sale_no }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="{{ $canFilterUsers ? 'col-lg-3 col-md-6' : 'col-lg-4 col-md-12' }}">
                                    <label for="filter_customer_id" class="filter-label">
                                        <i class="ri-user-3-line me-1 text-primary"></i>Customer Name
                                    </label>
                                    <select id="filter_customer_id" class="form-control select2">
                                        <option value="">All Customers</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @can('user-filter-view')
                                    <div class="col-lg-3 col-md-6">
                                        <label for="filter_created_by" class="filter-label">
                                            <i class="ri-user-follow-line me-1 text-primary"></i>Created By
                                        </label>
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
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div> <!-- container-fluid -->
</div>
@endsection

@section('scripts')
    {{ $dataTable->scripts() }}
    <script src="{{ asset('notiflix/notiflix-aio-3.2.8.min.js') }}"></script>
    <script>
        $(function() {
            let table = $('#profitreport-table').DataTable();
            const summaryUrl = "{{ route('reports.profit.summary') }}";

            function currentFilters() {
                return {
                    outlet_id: $('#filter_outlet_id').val(),
                    sale_id: $('#filter_invoice_id').val(),
                    customer_id: $('#filter_customer_id').val(),
                    from_date: $('#filter_from_date').val(),
                    to_date: $('#filter_to_date').val(),
                    created_by: $('#filter_created_by').val(),
                };
            }

            function formatMoney(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function loadSummary() {
                $.get(summaryUrl, currentFilters())
                    .done(function(res) {
                        $('#summary_gross_profit').text(formatMoney(res.gross_profit));
                        $('#summary_total_expenses').text(formatMoney(res.total_expenses));
                        $('#summary_net_profit').text(formatMoney(res.net_profit));
                        $('#summary_cash_adjustment').text(formatMoney(res.cash_net_adjustment));
                        $('#summary_stock_adjustment').text(formatMoney(res.stock_net_adjustment));
                    })
                    .fail(function() {
                        $('#summary_gross_profit').text('0.00');
                        $('#summary_total_expenses').text('0.00');
                        $('#summary_net_profit').text('0.00');
                        $('#summary_cash_adjustment').text('0.00');
                        $('#summary_stock_adjustment').text('0.00');
                    });
            }

            // Reload table when filters change
            $('#filter_outlet_id, #filter_invoice_id, #filter_customer_id, #filter_from_date, #filter_to_date, #filter_created_by').on('change', function() {
                table.ajax.reload();
                loadSummary();
            });

            // Reset filters
            $('#reset_filters').on('click', function() {
                @if(!$lockedOutletId)
                    $('#filter_outlet_id').val('').trigger('change.select2');
                @endif
                $('#filter_invoice_id').val(null).trigger('change.select2');
                $('#filter_customer_id').val(null).trigger('change.select2');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                if ($('#filter_created_by').length) {
                    $('#filter_created_by').val(null).trigger('change.select2');
                }
                table.ajax.reload();
                loadSummary();
            });

            // Pass filter values into AJAX
            table.on('preXhr.dt', function(e, settings, data) {
                data.outlet_id = $('#filter_outlet_id').val();
                data.sale_id = $('#filter_invoice_id').val();
                data.customer_id = $('#filter_customer_id').val();
                data.from_date = $('#filter_from_date').val();
                data.to_date = $('#filter_to_date').val();
                data.created_by = $('#filter_created_by').val();
            });

            // Handle DataTables errors gracefully
            $.fn.dataTable.ext.errMode = 'none';
            $('#profitreport-table').on('error.dt', function(e, settings, techNote, message) {
                Notiflix.Notify.failure("DataTables error: " + message);
            });

            loadSummary();
        });
    </script>
@endsection

@stack('scripts')
