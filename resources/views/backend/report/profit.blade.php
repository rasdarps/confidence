@extends('layout.main')
@section('title') {{'View Profit'}} @endsection

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
</style>

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Profit Report</h4>  
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <a href="{{ route('sales.create') }}" class="btn btn-dark btn-rounded waves-effect waves-light" style="float:right;">
                            <i class="fas fa-plus-circle"></i> New Sales
                        </a> 
                        <br><br>               

                        <h4 class="card-title">Profit Report</h4>      

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card border mb-0 summary-card">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Gross Profit</small>
                                        <h5 id="summary_gross_profit" class="mb-0">0.00</h5>
                                        <small class="text-muted">Total Profit before expenses</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('reports.expense') }}" class="summary-card-link text-decoration-none">
                                    <div class="card border mb-0 summary-card">
                                        <div class="card-body py-2">
                                            <small class="text-muted">Total Expenses</small>
                                            <h5 id="summary_total_expenses" class="mb-0">0.00</h5>
                                            <small class="text-muted">Click to open expense report</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <div class="card border mb-0 summary-card">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Net Profit</small>
                                        <h5 id="summary_net_profit" class="mb-0">0.00</h5>
                                        <small class="text-muted">Total Profit after expenses</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <div class="card border mb-0 summary-card">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Cash Adjustment</small>
                                        <h5 id="summary_cash_adjustment" class="mb-0">0.00</h5>
                                        <small class="text-muted">Shortage lowers profit, overage raises profit</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <div class="card border mb-0 summary-card">
                                    <div class="card-body py-2">
                                        <small class="text-muted">Stock Adjustment</small>
                                        <h5 id="summary_stock_adjustment" class="mb-0">0.00</h5>
                                        <small class="text-muted">Shortage lowers profit, overage raises profit</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ðŸ”Ž Filter boxes -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Sale Number</label>
                                <select id="filter_invoice_id" class="form-control select2">
                                    <option value="">All sales</option>
                                    @foreach($sales as $Sale)
                                        <option value="{{ $Sale->id }}">{{ $Sale->sale_no }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Customer Name</label>
                                <select id="filter_customer_id" class="form-control">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>From Date</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                            @can('user-filter-view')
                                <div class="col-md-3 mt-2">
                                    <label>Created By</label>
                                    <select id="filter_created_by" class="form-control">
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
                            <div class="col-md-3 mt-2 d-flex align-items-end">
                                <button id="reset_filters" class="btn btn-dark w-100">Reset Filters</button>
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
            $('#filter_invoice_id, #filter_customer_id, #filter_from_date, #filter_to_date, #filter_created_by').on('change', function() {
                table.ajax.reload();
                loadSummary();
            });

            // Reset filters
            $('#reset_filters').on('click', function() {
                $('#filter_invoice_id').val(null).trigger('change');
                $('#filter_customer_id').val(null).trigger('change');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                $('#filter_created_by').val(null).trigger('change');
                table.ajax.reload();
                loadSummary();
            });

            // Pass filter values into AJAX
            table.on('preXhr.dt', function(e, settings, data) {
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

