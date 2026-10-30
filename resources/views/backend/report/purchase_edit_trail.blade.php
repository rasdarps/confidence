@extends('layout.main')
@section('title') {{ 'Purchase Edit Trail' }} @endsection

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
    $centralOutlet = \App\Models\Outlet::where('is_central', true)->first();
@endphp

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Purchase Edit Trail</h4>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                        <i class="ri-building-4-line me-1"></i>All Purchase Edits Adjust Stock in: {{ $centralOutlet->name ?? 'Central Warehouse' }} (Central)
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Review detailed purchase edits with server-side filters and line-level change details.</h4>

                        <div class="report-filter-panel">
                            <!-- Row 1: Location, Purchase & Edited By -->
                            <div class="row g-3 mb-3">
                                <div class="col-lg-4 col-md-6">
                                    <label class="filter-label">
                                        <i class="ri-building-4-line me-1 text-primary"></i>Receiving Location
                                    </label>
                                    <input type="text" class="form-control bg-light text-muted" value="{{ ($centralOutlet->name ?? 'Central Warehouse') . ' (Central)' }}" disabled>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label for="filter_purchase_id" class="filter-label">
                                        <i class="ri-file-list-3-line me-1 text-primary"></i>Purchase Number
                                    </label>
                                    <select id="filter_purchase_id" class="form-control select2">
                                        <option value="">All Purchases</option>
                                        @foreach($purchases as $purchase)
                                            <option value="{{ $purchase->id }}">{{ $purchase->purchase_no }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-12">
                                    <label for="filter_edited_by" class="filter-label">
                                        <i class="ri-user-follow-line me-1 text-primary"></i>Edited By
                                    </label>
                                    <select id="filter_edited_by" class="form-control select2">
                                        <option value="">All Users</option>
                                        @foreach($editedByUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
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
        const table = $('#purchase-edit-trail-table').DataTable();

        $('#filter_purchase_id, #filter_edited_by, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function () {
            $('#filter_purchase_id').val(null).trigger('change.select2');
            $('#filter_edited_by').val(null).trigger('change.select2');
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload();
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.purchase_id = $('#filter_purchase_id').val();
            data.edited_by = $('#filter_edited_by').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        $.fn.dataTable.ext.errMode = 'none';
        $('#purchase-edit-trail-table').on('error.dt', function (e, settings, techNote, message) {
            Notiflix.Notify.failure('DataTables error: ' + message);
        });
    });
</script>
@endsection

@stack('scripts')
