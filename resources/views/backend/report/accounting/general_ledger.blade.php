@extends('layout.main')
@section('title', 'General Ledger')

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4>General Ledger</h4>
                <div class="row g-2">
                    <div class="col-md-3">
                        <select class="form-control" id="filter_account_id" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ (int)$accountId === (int)$acc->id ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="date" id="filter_from_date" class="form-control" value="{{ $fromDate }}"></div>
                    <div class="col-md-3"><input type="date" id="filter_to_date" class="form-control" value="{{ $toDate }}"></div>
                    <div class="col-md-2"><button class="btn btn-primary" id="apply_filters">Filter</button></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                @if($account)
                    <h6>{{ $account->code }} - {{ $account->name }}</h6>
                @endif
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
        let table = $('#general-ledger-table').DataTable();

        table.on('preXhr.dt', function (e, settings, data) {
            data.account_id = $('#filter_account_id').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        $('#apply_filters').on('click', function () {
            table.ajax.reload();
        });

        $('#filter_account_id, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
        });
    });
</script>
@endsection

@stack('scripts')
