@extends('layout.main')
@section('title', 'General Ledger')

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
    $defaultFromDate = now()->startOfMonth()->toDateString();
    $defaultToDate = now()->toDateString();
@endphp

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">General Ledger</h4>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border shadow-sm mb-0">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Debits (Gh¢)</small>
                        <h4 class="mb-0 text-success" id="gl_total_debit">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-sm mb-0">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Credits (Gh¢)</small>
                        <h4 class="mb-0 text-primary" id="gl_total_credit">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-sm mb-0">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Net Movement (Debit - Credit) (Gh¢)</small>
                        <h4 class="mb-0 text-dark" id="gl_net_movement">0.00</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Journal Ledger Lines</h4>

                <div class="report-filter-panel">
                    <div class="row g-3 align-items-end">
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
                                        <option value="{{ $outlet->id }}" {{ ($lockedOutletId ?? ($outletId ?? '')) == $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }}{{ $outlet->is_central ? ' (Central)' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="filter_account_id" class="filter-label">
                                <i class="ri-book-2-line me-1 text-primary"></i>Account
                            </label>
                            <select class="form-control select2" id="filter_account_id">
                                <option value="">All Accounts</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (int)$accountId === (int)$acc->id ? 'selected' : '' }}>
                                        {{ $acc->code }} - {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label for="filter_source_type" class="filter-label">
                                <i class="ri-file-list-3-line me-1 text-primary"></i>Source Type
                            </label>
                            <select class="form-control select2" id="filter_source_type">
                                <option value="">All Sources</option>
                                <option value="sale">Sale</option>
                                <option value="sale_receipt">Sale Receipt</option>
                                <option value="purchase">Purchase (Central)</option>
                                <option value="purchase_settlement">Purchase Settlement (Central)</option>
                                <option value="expense_voucher">Expense Voucher</option>
                                <option value="stock_adjustment">Stock Adjustment</option>
                                <option value="cash_reconciliation">Cash Reconciliation</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label for="filter_from_date" class="filter-label">
                                <i class="ri-calendar-event-line me-1 text-primary"></i>From Date
                            </label>
                            <input type="date" id="filter_from_date" class="form-control" value="{{ $fromDate }}">
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label for="filter_to_date" class="filter-label">
                                <i class="ri-calendar-check-line me-1 text-primary"></i>To Date
                            </label>
                            <input type="date" id="filter_to_date" class="form-control" value="{{ $toDate }}">
                        </div>

                        <div class="col-lg-12 d-flex justify-content-end mt-3">
                            <button type="button" id="reset_filters" class="btn btn-outline-secondary btn-filter-action px-4">
                                <i class="ri-refresh-line"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-bordered table-striped table-sm'], true) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{ $dataTable->scripts() }}
<script>
    $(function () {
        let table = $('#general-ledger-table').DataTable();

        function formatMoney(value) {
            const num = Number(value || 0);
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        table.on('preXhr.dt', function (e, settings, data) {
            data.outlet_id = $('#filter_outlet_id').val();
            data.account_id = $('#filter_account_id').val();
            data.source_type = $('#filter_source_type').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        table.on('xhr.dt', function (e, settings, json) {
            const summary = (json && json.summary) ? json.summary : {};

            $('#gl_total_debit').text(formatMoney(summary.total_debit));
            $('#gl_total_credit').text(formatMoney(summary.total_credit));
            $('#gl_net_movement').text(formatMoney(summary.net_movement));
        });

        $('#filter_outlet_id, #filter_account_id, #filter_source_type, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function () {
            @if(!$lockedOutletId)
                $('#filter_outlet_id').val('').trigger('change.select2');
            @endif
            $('#filter_account_id').val('').trigger('change.select2');
            $('#filter_source_type').val('').trigger('change.select2');
            $('#filter_from_date').val('{{ $defaultFromDate }}');
            $('#filter_to_date').val('{{ $defaultToDate }}');
            table.ajax.reload();
        });
    });
</script>
@endsection

@stack('scripts')
