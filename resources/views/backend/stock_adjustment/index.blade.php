@extends('layout.main')
@section('title') {{ 'Stock Adjustments' }} @endsection

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
                    <h4 class="mb-sm-0">Stock Adjustments</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('stock-adjustments.create') }}" class="btn btn-dark" style="float:right;">
                            <i class="fas fa-plus-circle"></i> Add Stock Adjustment
                        </a>
                        <br><br>

                        <h4 class="card-title">All Stock Adjustments</h4>
                        <div class="row mb-3">
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
        let table = $('#stock-adjustment-table').DataTable();

        $('#filter_accounting_status').on('change', function () {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function () {
            $('#filter_accounting_status').val(defaultAccountingStatus);
            table.ajax.reload();
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.accounting_status = $('#filter_accounting_status').val();
        });
    });
</script>
@endsection

@stack('scripts')
