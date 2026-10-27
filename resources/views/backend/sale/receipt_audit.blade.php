@extends('layout.main')
@section('title') Receipt Print Audit @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Receipt Print Audit</h4>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-2 col-md-4">
                        <label for="filter_sale_no" class="form-label">Invoice No</label>
                        <input type="text" id="filter_sale_no" class="form-control" value="{{ $filters['sale_no'] ?? '' }}" placeholder="Search invoice">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="filter_operator_id" class="form-label">Operator</label>
                        <select id="filter_operator_id" class="form-control">
                            <option value="">All users</option>
                            @foreach($operators as $operator)
                                <option value="{{ $operator->id }}" @selected(($filters['operator_id'] ?? '') == $operator->id)>{{ $operator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="filter_event_type" class="form-label">Print Event</label>
                        <select id="filter_event_type" class="form-control">
                            <option value="">All events</option>
                            <option value="original" @selected(($filters['event_type'] ?? '') === 'original')>Original print</option>
                            <option value="reprint" @selected(($filters['event_type'] ?? '') === 'reprint')>Reprint</option>
                            <option value="delivery_verified" @selected(($filters['event_type'] ?? '') === 'delivery_verified')>Delivery verified</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="filter_delivery_status" class="form-label">Delivery Status</label>
                        <select id="filter_delivery_status" class="form-control">
                            <option value="">All statuses</option>
                            <option value="delivered" @selected(($filters['delivery_status'] ?? '') === 'delivered')>Delivered</option>
                            <option value="pending" @selected(($filters['delivery_status'] ?? '') === 'pending')>Pending</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="filter_from_date" class="form-label">Printed From</label>
                        <input type="date" id="filter_from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="filter_to_date" class="form-label">Printed To</label>
                        <input type="date" id="filter_to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="button" id="apply_filters" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                        <button type="button" id="reset_filters" class="btn btn-dark">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Receipt Print Events</h4>
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-bordered table-striped align-middle'], true) !!}
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
        const table = $('#receipt-audit-table').DataTable();

        table.on('preXhr.dt', function (event, settings, data) {
            data.sale_no = $('#filter_sale_no').val();
            data.operator_id = $('#filter_operator_id').val();
            data.event_type = $('#filter_event_type').val();
            data.delivery_status = $('#filter_delivery_status').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });

        $('#apply_filters').on('click', function () {
            table.ajax.reload();
        });

        $('#filter_sale_no').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                table.ajax.reload();
            }
        });

        $('#reset_filters').on('click', function () {
            $('#filter_sale_no, #filter_from_date, #filter_to_date').val('');
            $('#filter_operator_id, #filter_event_type, #filter_delivery_status').val('');
            table.search('').columns().search('');
            table.ajax.reload();
        });
    });
</script>
@endsection