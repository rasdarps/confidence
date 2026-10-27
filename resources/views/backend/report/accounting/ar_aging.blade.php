@extends('layout.main')
@section('title', 'AR Aging')

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Accounts Receivable Aging</h4>
                <div class="d-flex gap-2">
                    <input type="date" id="filter_to_date" class="form-control" value="{{ $asOfDate }}">
                    <button class="btn btn-primary" id="apply_filters">Filter</button>
                </div>
            </div>
        </div>
        <div class="card"><div class="card-body table-responsive">
            {!! $dataTable->table(['class' => 'table table-bordered table-striped table-sm'], true) !!}
        </div></div>
    </div>
</div>
@endsection

@section('scripts')
{{ $dataTable->scripts() }}
<script>
    $(function () {
        let table = $('#ar-aging-table').DataTable();

        table.on('preXhr.dt', function (e, settings, data) {
            data.to_date = $('#filter_to_date').val();
        });

        $('#apply_filters').on('click', function () {
            table.ajax.reload();
        });
    });
</script>
@endsection

@stack('scripts')
