@extends('layout.main')
@section('title') {{ 'Expense Report' }} @endsection

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
    $canFilterUsers = auth()->check() && auth()->user()->can('user-filter-view');
@endphp

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Expense Report</h4>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border shadow-sm mb-0">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Filtered Total (Gh¢)</small>
                        <h4 id="filtered_total" class="mb-0">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-sm mb-0">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">This Month (Gh¢)</small>
                        <h4 class="mb-0">{{ number_format((float) $monthTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-sm mb-0">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Overall Total (Gh¢)</small>
                        <h4 class="mb-0">{{ number_format((float) $overallTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Expense Vouchers & Line Items</h4>

                        <div class="report-filter-panel">
                            <!-- Row 1: Primary Entity Filters -->
                            <div class="row g-3 mb-3">
                                <div class="{{ $canFilterUsers ? 'col-lg-4 col-md-6' : 'col-lg-6 col-md-6' }}">
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

                                <div class="{{ $canFilterUsers ? 'col-lg-4 col-md-6' : 'col-lg-6 col-md-6' }}">
                                    <label for="filter_category" class="filter-label">
                                        <i class="ri-price-tag-3-line me-1 text-primary"></i>Category
                                    </label>
                                    <select id="filter_category" class="form-control select2">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @can('user-filter-view')
                                    <div class="col-lg-4 col-md-12">
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
        let table = $('#expensereport-table').DataTable();

        function updateFilteredTotal() {
            let amountIndex = table.columns().indexes().toArray().find(function (idx) {
                return (table.settings()[0].aoColumns[idx] || {}).data === 'amount';
            });

            if (typeof amountIndex === 'undefined') {
                return;
            }

            let intVal = function (value) {
                if (typeof value === 'string') {
                    return value.replace(/[^0-9.-]/g, '') * 1;
                }
                if (typeof value === 'number') {
                    return value;
                }
                return 0;
            };

            let total = table.column(amountIndex, {search: 'applied'}).data().reduce(function (a, b) {
                return intVal(a) + intVal(b);
            }, 0);

            $('#filtered_total').text(total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }

        $('#filter_outlet_id, #filter_category, #filter_created_by, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload(updateFilteredTotal);
        });

        $('#reset_filters').on('click', function () {
            @if(!$lockedOutletId)
                $('#filter_outlet_id').val('').trigger('change.select2');
            @endif
            $('#filter_category').val('').trigger('change.select2');
            if ($('#filter_created_by').length) {
                $('#filter_created_by').val('').trigger('change.select2');
            }
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload(updateFilteredTotal);
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.outlet_id = $('#filter_outlet_id').val();
            data.category = $('#filter_category').val();
            data.created_by = $('#filter_created_by').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        table.on('draw.dt', function () {
            updateFilteredTotal();
        });

        $.fn.dataTable.ext.errMode = 'none';
        $('#expensereport-table').on('error.dt', function (e, settings, techNote, message) {
            Notiflix.Notify.failure('DataTables error: ' + message);
        });

        updateFilteredTotal();
    });
</script>
@endsection

@stack('scripts')
