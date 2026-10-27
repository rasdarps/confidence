@extends('layout.main')
@section('title') {{ 'Sales Credit Movement Report' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Sales Credit Movement Report</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Credit Exposure From Sales</h4>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card border shadow-sm mb-0">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Total Credit</small>
                                        <h5 class="mb-0" id="total_credit_amount_card">0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border shadow-sm mb-0">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Partial Paid Amount</small>
                                        <h5 class="mb-0" id="partial_paid_amount_card">0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border shadow-sm mb-0">
                                    <div class="card-body py-3">
                                        <small class="text-muted d-block">Due Amount To Be Paid</small>
                                        <h5 class="mb-0" id="due_amount_to_be_paid_card">0.00</h5>
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
                                <label>Credit Type</label>
                                <select id="filter_paid_status" class="form-control">
                                    <option value="">All</option>
                                    <option value="full_credit">Full Credit</option>
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
        const summaryUrl = '{{ route('reports.sales-credit-movement.summary') }}';
        let table = $('#sales-credit-movement-table').DataTable();

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
                    $('#total_credit_amount_card').text(formatMoney(res.total_credit_amount));
                    $('#partial_paid_amount_card').text(formatMoney(res.partial_paid_amount));
                    $('#due_amount_to_be_paid_card').text(formatMoney(res.due_amount_to_be_paid));
                })
                .fail(function () {
                    Notiflix.Notify.failure('Could not load credit summary cards.');
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
        $('#sales-credit-movement-table').on('error.dt', function (e, settings, techNote, message) {
            Notiflix.Notify.failure('DataTables error: ' + message);
        });

        loadSummary();
    });
</script>
@endsection

@stack('scripts')
