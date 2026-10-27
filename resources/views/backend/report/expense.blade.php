@extends('layout.main')
@section('title') {{ 'Expense Report' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

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
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-1">Filtered Total</h6>
                        <h4 id="filtered_total" class="mb-0">0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-1">This Month</h6>
                        <h4 class="mb-0">{{ number_format((float) $monthTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-1">Overall Total</h6>
                        <h4 class="mb-0">{{ number_format((float) $overallTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Expense Report</h4>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Category</label>
                                <select id="filter_category" class="form-control">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @can('user-filter-view')
                                <div class="col-md-3">
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
                            <div class="col-md-2">
                                <label>From Date</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
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

        $('#filter_category, #filter_created_by, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload(updateFilteredTotal);
        });

        $('#reset_filters').on('click', function () {
            $('#filter_category').val('');
            $('#filter_created_by').val('');
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload(updateFilteredTotal);
        });

        table.on('preXhr.dt', function (e, settings, data) {
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
