@extends('layout.main')
@section('title') Receipt Print Audit @endsection

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
                    <h4 class="mb-sm-0">Receipt Print Audit</h4>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Receipt Print Events</h4>

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
                                        <option value="{{ $outlet->id }}" @selected(($lockedOutletId ?? ($filters['outlet_id'] ?? '')) == $outlet->id)>
                                            {{ $outlet->name }}{{ $outlet->is_central ? ' (Central)' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="filter_sale_no" class="filter-label">
                                <i class="ri-file-list-3-line me-1 text-primary"></i>Invoice No
                            </label>
                            <input type="text" id="filter_sale_no" class="form-control" value="{{ $filters['sale_no'] ?? '' }}" placeholder="Search invoice no...">
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="filter_operator_id" class="filter-label">
                                <i class="ri-user-follow-line me-1 text-primary"></i>Operator
                            </label>
                            <select id="filter_operator_id" class="form-control select2">
                                <option value="">All Operators</option>
                                @foreach($operators as $operator)
                                    <option value="{{ $operator->id }}" @selected(($filters['operator_id'] ?? '') == $operator->id)>{{ $operator->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="filter_event_type" class="filter-label">
                                <i class="ri-printer-line me-1 text-primary"></i>Print Event
                            </label>
                            <select id="filter_event_type" class="form-select">
                                <option value="">All Events</option>
                                <option value="original" @selected(($filters['event_type'] ?? '') === 'original')>Original Print</option>
                                <option value="reprint" @selected(($filters['event_type'] ?? '') === 'reprint')>Reprint</option>
                                <option value="delivery_verified" @selected(($filters['event_type'] ?? '') === 'delivery_verified')>Delivery Verified</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Delivery Status, Date Range & Reset Action -->
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_delivery_status" class="filter-label">
                                <i class="ri-checkbox-circle-line me-1 text-primary"></i>Delivery Status
                            </label>
                            <select id="filter_delivery_status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="delivered" @selected(($filters['delivery_status'] ?? '') === 'delivered')>Delivered</option>
                                <option value="pending" @selected(($filters['delivery_status'] ?? '') === 'pending')>Pending</option>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="filter_from_date" class="filter-label">
                                <i class="ri-calendar-event-line me-1 text-primary"></i>Printed From
                            </label>
                            <input type="date" id="filter_from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="filter_to_date" class="filter-label">
                                <i class="ri-calendar-check-line me-1 text-primary"></i>Printed To
                            </label>
                            <input type="date" id="filter_to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <button type="button" id="reset_filters" class="btn btn-outline-secondary btn-filter-action w-100">
                                <i class="ri-refresh-line"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-bordered table-striped align-middle'], true) !!}
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
        const table = $('#receipt-audit-table').DataTable();
        let saleNoTimer = null;

        table.on('preXhr.dt', function (event, settings, data) {
            data.outlet_id = $('#filter_outlet_id').val();
            data.sale_no = $('#filter_sale_no').val();
            data.operator_id = $('#filter_operator_id').val();
            data.event_type = $('#filter_event_type').val();
            data.delivery_status = $('#filter_delivery_status').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        $('#filter_outlet_id, #filter_operator_id, #filter_event_type, #filter_delivery_status, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
        });

        $('#filter_sale_no').on('input keydown', function (event) {
            if (event.type === 'keydown' && event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(saleNoTimer);
                table.ajax.reload();
                return;
            }
            clearTimeout(saleNoTimer);
            saleNoTimer = setTimeout(function () {
                table.ajax.reload();
            }, 350);
        });

        $('#reset_filters').on('click', function () {
            @if(!$lockedOutletId)
                $('#filter_outlet_id').val('').trigger('change.select2');
            @endif
            $('#filter_sale_no, #filter_from_date, #filter_to_date').val('');
            $('#filter_operator_id').val('').trigger('change.select2');
            $('#filter_event_type, #filter_delivery_status').val('');
            table.search('').columns().search('');
            table.ajax.reload();
        });
    });
</script>
@endsection

@stack('scripts')
