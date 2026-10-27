@extends('layout.main')
@section('title') {{ 'Sales Cash Movement Report' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

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
                        <h4 class="card-title">Cash Received From Sales (Full + Partial)</h4>

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

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Sale Number</label>
                                <select id="filter_sale_id" class="form-control select2">
                                    <option value="">All Sales</option>
                                    @foreach($sales as $sale)
                                        <option value="{{ $sale->id }}">{{ $sale->sale_no }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Customer</label>
                                <select id="filter_customer_id" class="form-control select2">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Payment Type</label>
                                <select id="filter_paid_status" class="form-control">
                                    <option value="">All</option>
                                    <option value="full_payment">Full Payment</option>
                                    <option value="partial_payment">Partial Payment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>From Date</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="reset_filters" class="btn btn-dark w-100">Reset Filters</button>
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

        $('#filter_sale_id, #filter_customer_id, #filter_paid_status, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
            loadSummary();
        });

        $('#reset_filters').on('click', function () {
            $('#filter_sale_id').val(null).trigger('change');
            $('#filter_customer_id').val(null).trigger('change');
            $('#filter_paid_status').val('');
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload();
            loadSummary();
        });

        table.on('preXhr.dt', function (e, settings, data) {
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
