@extends('layout.main')
@section('title', 'AR Aging')

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
    $defaultAsOfDate = now()->toDateString();
@endphp

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Accounts Receivable (AR) Aging</h4>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">0–30 Days (Gh¢)</small>
                        <h4 class="mb-0 text-success" id="ar_total_current">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">31–60 Days (Gh¢)</small>
                        <h4 class="mb-0 text-info" id="ar_total_31_60">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">61–90 Days (Gh¢)</small>
                        <h4 class="mb-0 text-warning" id="ar_total_61_90">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">90+ Days Overdue (Gh¢)</small>
                        <h4 class="mb-0 text-danger" id="ar_total_90_plus">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-12 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Receivable Balance (Gh¢)</small>
                        <h4 class="mb-0 text-primary" id="ar_total_balance">0.00</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Customer Receivable Aging Schedule</h4>

                <div class="report-filter-panel">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
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

                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="filter_customer_id" class="filter-label">
                                <i class="ri-user-3-line me-1 text-primary"></i>Customer
                            </label>
                            <select id="filter_customer_id" class="form-control select2">
                                <option value="">All Customers</option>
                                @foreach($customers ?? [] as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->name }}{{ $customer->mobile_no ? ' (' . $customer->mobile_no . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="filter_aging_bucket" class="filter-label">
                                <i class="ri-time-line me-1 text-primary"></i>Aging Bucket
                            </label>
                            <select id="filter_aging_bucket" class="form-control">
                                <option value="">All Aging Buckets</option>
                                <option value="0_30">0–30 Days (Current)</option>
                                <option value="31_60">31–60 Days</option>
                                <option value="61_90">61–90 Days</option>
                                <option value="90_plus">90+ Days Overdue</option>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="filter_to_date" class="filter-label">
                                <i class="ri-calendar-check-line me-1 text-primary"></i>As Of Date
                            </label>
                            <input type="date" id="filter_to_date" class="form-control" value="{{ $asOfDate }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
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
        let table = $('#ar-aging-table').DataTable();

        function formatMoney(value) {
            const num = Number(value || 0);
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        table.on('preXhr.dt', function (e, settings, data) {
            data.outlet_id = $('#filter_outlet_id').val();
            data.customer_id = $('#filter_customer_id').val();
            data.aging_bucket = $('#filter_aging_bucket').val();
            data.to_date = $('#filter_to_date').val();
        });

        table.on('xhr.dt', function (e, settings, json) {
            const summary = (json && json.summary) ? json.summary : {};

            $('#ar_total_current').text(formatMoney(summary.total_current));
            $('#ar_total_31_60').text(formatMoney(summary.total_31_60));
            $('#ar_total_61_90').text(formatMoney(summary.total_61_90));
            $('#ar_total_90_plus').text(formatMoney(summary.total_90_plus));
            $('#ar_total_balance').text(formatMoney(summary.total_balance));
        });

        $('#filter_outlet_id, #filter_customer_id, #filter_aging_bucket, #filter_to_date').on('change', function () {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function () {
            @if(!$lockedOutletId)
                $('#filter_outlet_id').val('').trigger('change.select2');
            @endif
            $('#filter_customer_id').val('').trigger('change.select2');
            $('#filter_aging_bucket').val('');
            $('#filter_to_date').val('{{ $defaultAsOfDate }}');
            table.ajax.reload();
        });
    });
</script>
@endsection

@stack('scripts')
