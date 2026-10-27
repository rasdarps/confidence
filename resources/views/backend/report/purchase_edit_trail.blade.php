@extends('layout.main')
@section('title') {{ 'Purchase Edit Trail' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Purchase Edit Trail</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Review detailed purchase edits with server-side filters and line-level change details.</h4>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Purchase</label>
                                <select id="filter_purchase_id" class="form-control select2">
                                    <option value="">All Purchases</option>
                                    @foreach($purchases as $purchase)
                                        <option value="{{ $purchase->id }}">{{ $purchase->purchase_no }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Edited By</label>
                                <select id="filter_edited_by" class="form-control select2">
                                    <option value="">All Users</option>
                                    @foreach($editedByUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>From Date</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
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
        const table = $('#purchase-edit-trail-table').DataTable();

        $('#filter_purchase_id, #filter_edited_by, #filter_from_date, #filter_to_date').on('change', function () {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function () {
            $('#filter_purchase_id').val(null).trigger('change');
            $('#filter_edited_by').val(null).trigger('change');
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
