@extends('layout.main')
@section('title') {{ 'Daily Transaction Email Logs' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-sm-0">Daily Transaction Email Audit Logs</h4>
                    <div class="page-title-right">
                        <a href="{{ route('daily-transaction-email.index') }}" class="btn btn-sm btn-outline-primary" style="color: #0F766E; border-color: #0F766E;">
                            <i class="fas fa-arrow-left me-1"></i> Back to Live Summary
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border" style="border-radius: 12px;">
                    <div class="card-body">

                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="fas fa-history fa-lg mr-3 me-3 text-info"></i>
                            <div>
                                <strong>Enterprise Dispatch History:</strong>
                                Historical log of all automated and manual daily executive reports dispatched, detailing business dates, aggregated sales, expenses, and cash shortages.
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label font-size-12 text-uppercase fw-semibold text-muted">Business Date From</label>
                                    <input type="date" id="filter_from_date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-size-12 text-uppercase fw-semibold text-muted">Business Date To</label>
                                    <input type="date" id="filter_to_date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 d-flex">
                                    <button id="reset_filters" class="btn btn-sm btn-outline-secondary w-100" style="height: 38px;">
                                        <i class="fas fa-undo me-1"></i> Reset Date Filter
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
