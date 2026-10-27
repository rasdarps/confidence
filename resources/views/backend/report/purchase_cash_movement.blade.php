@extends('layout.main')
@section('title') {{ 'Purchase Cash Movement Report' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Purchase Cash Movement Report</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Cash Paid For Purchases (Full + Partial)</h4>

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
                                <label>Purchase Number</label>
                                <select id="filter_purchase_id" class="form-control select2">
                                    <option value="">All Purchases</option>
                                    @foreach($purchases as $purchase)
                                        <option value="{{ $purchase->id }}">{{ $purchase->purchase_no }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Supplier</label>
                                <select id="filter_supplier_id" class="form-control select2">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
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
        const summaryUrl = '{{ route('reports.purchase-cash-movement.summary') }}';
        let table = $('#purchase-cash-movement-table').DataTable();

        function getFilters() {
            return {
                purchase_id: $('#filter_purchase_id').val(),
                supplier_id: $('#filter_supplier_id').val(),
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

        $('#filter_purchase_id, #filter_supplier_id, #filter_paid_status, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
            loadSummary();
        });

        $('#reset_filters').on('click', function () {
            $('#filter_purchase_id').val(null).trigger('change');
            $('#filter_supplier_id').val(null).trigger('change');
            $('#filter_paid_status').val('');
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload();
            loadSummary();
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.purchase_id = $('#filter_purchase_id').val();
            data.supplier_id = $('#filter_supplier_id').val();
            data.paid_status = $('#filter_paid_status').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        $.fn.dataTable.ext.errMode = 'none';
        $('#purchase-cash-movement-table').on('error.dt', function (e, settings, techNote, message) {
            Notiflix.Notify.failure('DataTables error: ' + message);
        });

        loadSummary();
    });
</script>
@endsection

@stack('scripts')
