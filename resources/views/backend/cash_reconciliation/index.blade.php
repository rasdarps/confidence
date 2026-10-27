@extends('layout.main')
@section('title') {{ 'Cash Reconciliations' }} @endsection

@section('content')
@php
    $currentUser = auth()->user();
    $isCashier = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('Cashier');
    $defaultAccountingStatus = $isCashier ? 'unposted' : '';
@endphp
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Cash Reconciliations</h4>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('cash-reconciliations.create') }}" class="btn btn-dark" style="float:right;">
                            <i class="fas fa-plus-circle"></i> Add Daily Reconciliation
                        </a>
                        <br><br>

                        <h4 class="card-title">All Daily Cash Reconciliations</h4>
                        @if(!empty($isRestrictedToOwnReconciles))
                            <div class="alert alert-info py-2 mb-3" role="alert">
                                <i class="fas fa-info-circle me-1"></i>
                                Showing your reconciles only.
                            </div>
                        @endif
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filter_from_date">From Date</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_to_date">To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_accounting_status">Accounting Status</label>
                                <select id="filter_accounting_status" class="form-control">
                                    <option value="" {{ $defaultAccountingStatus === '' ? 'selected' : '' }}>All</option>
                                    <option value="posted" {{ $defaultAccountingStatus === 'posted' ? 'selected' : '' }}>Posted</option>
                                    <option value="unposted" {{ $defaultAccountingStatus === 'unposted' ? 'selected' : '' }}>Unposted</option>
                                    <option value="reversed" {{ $defaultAccountingStatus === 'reversed' ? 'selected' : '' }}>Reversed</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end mt-2 mt-md-0">
                                <button id="reset_filters" class="btn btn-dark w-100">Reset Filters</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
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
<script>
    $(function () {
        const defaultAccountingStatus = @json($defaultAccountingStatus);
        let table = $('#cash-reconciliation-table').DataTable();

        $('#filter_from_date, #filter_to_date, #filter_accounting_status').on('change', function () {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function () {
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            $('#filter_accounting_status').val(defaultAccountingStatus);
            table.ajax.reload();
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
            data.accounting_status = $('#filter_accounting_status').val();
        });
    });
</script>
@endsection

@stack('scripts')
