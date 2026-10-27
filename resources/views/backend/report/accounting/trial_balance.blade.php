@extends('layout.main')
@section('title', 'Trial Balance')

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Trial Balance</h4>
                <div class="d-flex gap-2">
                    <input type="date" id="filter_from_date" class="form-control" value="{{ $fromDate }}">
                    <input type="date" id="filter_to_date" class="form-control" value="{{ $toDate }}">
                    <button class="btn btn-primary" id="apply_filters">Filter</button>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Debits</h6>
                        <h4 class="mb-0 text-success" id="tb_total_debit">{{ number_format((float) $totalDebit, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Credits</h6>
                        <h4 class="mb-0 text-primary" id="tb_total_credit">{{ number_format((float) $totalCredit, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-sm'], true) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{ $dataTable->scripts() }}
<script>
    $(function () {
        let table = $('#trial-balance-table').DataTable();

        function formatMoney(value) {
            const num = Number(value || 0);
            return num.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        table.on('preXhr.dt', function (e, settings, data) {
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        table.on('xhr.dt', function (e, settings, json) {
            const summary = (json && json.summary) ? json.summary : {};

            $('#tb_total_debit').text(formatMoney(summary.total_debit));
            $('#tb_total_credit').text(formatMoney(summary.total_credit));
        });

        $('#apply_filters').on('click', function () {
            table.ajax.reload();
        });
    });
</script>
@endsection

@stack('scripts')
