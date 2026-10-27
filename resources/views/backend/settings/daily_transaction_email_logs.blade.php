@extends('layout.main')
@section('title') {{ 'Daily Transaction Email Logs' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Daily Transaction Email Logs</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('daily-transaction-email.index') }}" class="btn btn-dark" style="float:right;">
                            <i class="fas fa-envelope-open-text"></i> Daily Transaction Report
                        </a>
                        <br><br>
                        <h4 class="card-title">All Daily Email Records</h4>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Business Date From</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>Business Date To</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
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
        var table = $('#daily-transaction-email-table').DataTable();

        $('#filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function () {
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload();
        });

        table.on('preXhr.dt', function (e, settings, data) {
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });
    });
</script>
@endsection

@stack('scripts')
